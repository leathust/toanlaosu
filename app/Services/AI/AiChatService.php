<?php

namespace App\Services\AI;

use App\Contracts\AI\GenerativeProvider;
use App\Models\AiKnowledgeChunk;
use App\Models\AiUsageLog;
use App\Models\ChatMessage;
use App\Models\ChatMessageSource;
use App\Models\ChatThread;
use App\Models\SystemSetting;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use RuntimeException;

class AiChatService
{
    public function __construct(private readonly GenerativeProvider $provider, private readonly TextKnowledgeRetriever $retriever) {}

    /**
     * @param  array<int, int>  $sourceIds
     * @return array{user_message:ChatMessage,assistant_message:ChatMessage,sources:array<int, AiKnowledgeChunk>}
     */
    public function respond(User $user, ChatThread $thread, string $content, array $sourceIds = [], ?string $requestId = null): array
    {
        if ($requestId && $duplicate = $this->findDuplicate($thread, $requestId)) {
            return $duplicate;
        }
        if (Cache::get('ai:circuit:open', false)) {
            throw new RuntimeException('AI circuit is open.');
        }

        $sources = $sourceIds === [] ? $this->retriever->search($content) : AiKnowledgeChunk::query()->with('document')->whereIn('id', $sourceIds)->get();
        $sourcePayload = $sources->map(static fn (AiKnowledgeChunk $source): array => ['id' => $source->id, 'title' => $source->document?->title ?? 'Knowledge', 'content' => $source->content])->values()->all();
        $budget = (int) (SystemSetting::query()->where('key', 'ai.daily_budget')->value('value') ?? 100000);
        $used = (int) AiUsageLog::query()->where('user_id', $user->id)->where('occurred_at', '>=', now()->startOfDay())->sum(DB::raw('COALESCE(prompt_tokens, 0) + COALESCE(completion_tokens, 0)'));
        if ($used >= $budget) {
            throw new RuntimeException('AI daily budget exceeded.');
        }

        $started = microtime(true);
        $result = $this->completeWithRetry($content, $sourcePayload);
        $latency = (int) round((microtime(true) - $started) * 1000);

        return DB::transaction(function () use ($user, $thread, $content, $result, $sources, $latency, $requestId): array {
            $userMessage = $thread->messages()->create(['role' => 'user', 'status' => 'completed', 'content' => $content, 'created_at' => now()]);
            $assistantMessage = $thread->messages()->create(['parent_id' => $userMessage->id, 'role' => 'assistant', 'status' => 'completed', 'content' => $result['content'], 'model' => $result['model'], 'provider_message_id' => 'local-'.Str::ulid(), 'prompt_tokens' => $result['prompt_tokens'], 'completion_tokens' => $result['completion_tokens'], 'latency_ms' => $latency, 'created_at' => now()]);
            foreach ($sources as $order => $source) {
                ChatMessageSource::query()->create(['chat_message_id' => $assistantMessage->id, 'ai_knowledge_chunk_id' => $source->id, 'citation_order' => $order + 1, 'label' => 'Nguồn '.($order + 1)]);
            }
            $metadata = $thread->metadata ?? [];
            if ($requestId) {
                $metadata['ai_request_ids'][$requestId] = $assistantMessage->id;
            }
            $thread->update(['last_message_at' => now(), 'metadata' => $metadata]);
            AiUsageLog::query()->create(['user_id' => $user->id, 'chat_message_id' => $assistantMessage->id, 'provider' => $result['provider'], 'model' => $result['model'], 'operation' => 'chat_completion', 'status' => 'succeeded', 'prompt_tokens' => $result['prompt_tokens'], 'completion_tokens' => $result['completion_tokens'], 'latency_ms' => $latency, 'occurred_at' => now(), 'retention_expires_at' => now()->addYear()]);

            return ['user_message' => $userMessage, 'assistant_message' => $assistantMessage->load('sources'), 'sources' => $sources->all()];
        });
    }

    private function completeWithRetry(string $content, array $sources): array
    {
        $last = null;
        $maxRetries = max(1, (int) config('services.ai.max_retries', 2));
        for ($attempt = 0; $attempt < $maxRetries; $attempt++) {
            try {
                $result = $this->provider->complete($content, $sources);
                Cache::forget('ai:circuit:failures');

                return $result;
            } catch (RuntimeException $exception) {
                $last = $exception;
                Log::warning('ai.provider_error', ['provider' => config('services.ai.provider'), 'attempt' => $attempt + 1, 'error' => $exception->getMessage()]);
                $failures = (int) Cache::increment('ai:circuit:failures');
                if ($failures >= (int) config('services.ai.circuit_breaker_threshold', 3)) {
                    Cache::put('ai:circuit:open', true, now()->addMinute());
                }
            }
        }

        throw $last ?? new RuntimeException('AI provider failed.');
    }

    /**
     * @return array{user_message:ChatMessage,assistant_message:ChatMessage,sources:array<int, AiKnowledgeChunk>}|null
     */
    private function findDuplicate(ChatThread $thread, string $requestId): ?array
    {
        $messageId = $thread->metadata['ai_request_ids'][$requestId] ?? null;
        if (! $messageId) {
            return null;
        }
        $assistant = ChatMessage::query()->with('sources')->find($messageId);
        if (! $assistant) {
            return null;
        }
        $userMessage = $assistant->parent;

        return ['user_message' => $userMessage, 'assistant_message' => $assistant, 'sources' => AiKnowledgeChunk::query()->whereIn('id', $assistant->sources->pluck('ai_knowledge_chunk_id'))->get()->all()];
    }
}
