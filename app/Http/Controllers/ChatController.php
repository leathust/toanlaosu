<?php

namespace App\Http\Controllers;

use App\Models\AiFeedback;
use App\Models\AiUsageLog;
use App\Models\ChatMessage;
use App\Models\ChatMessageSource;
use App\Models\ChatThread;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ChatController extends Controller
{
    public function show(Request $request, ?ChatThread $thread = null): View
    {
        if ($thread) {
            $this->ensureOwner($request, $thread);
            $thread->load('messages.sources');
        }

        return view('student.chat', ['thread' => $thread]);
    }

    public function send(Request $request): JsonResponse
    {
        $data = $request->validate([
            'thread_id' => ['nullable', 'integer', 'exists:chat_threads,id'],
            'content' => ['required', 'string', 'max:4000'],
            'source_ids' => ['nullable', 'array', 'max:5'],
            'source_ids.*' => ['integer', 'exists:ai_knowledge_chunks,id'],
        ]);
        $thread = ! empty($data['thread_id']) ? ChatThread::query()->findOrFail($data['thread_id']) : ChatThread::query()->create([
            'public_id' => (string) Str::ulid(),
            'user_id' => $request->user()->id,
            'mode' => 'tutor',
            'status' => 'active',
            'title' => Str::limit($data['content'], 80),
            'retention_expires_at' => now()->addYear(),
        ]);
        $this->ensureOwner($request, $thread);

        [$userMessage, $assistantMessage] = DB::transaction(function () use ($thread, $data, $request): array {
            $userMessage = $thread->messages()->create(['role' => 'user', 'status' => 'completed', 'content' => $data['content'], 'created_at' => now()]);
            $assistantMessage = $thread->messages()->create([
                'parent_id' => $userMessage->id,
                'role' => 'assistant',
                'status' => 'completed',
                'content' => 'Hãy quan sát đại lượng đang thay đổi trong mô hình. Từ câu hỏi “'.Str::limit($data['content'], 120).'”, ta có thể kiểm tra từng bước rồi đối chiếu với công thức trong bài học.',
                'model' => 'local-tutor-prototype',
                'created_at' => now(),
            ]);
            foreach ($data['source_ids'] ?? [] as $order => $sourceId) {
                ChatMessageSource::query()->create(['chat_message_id' => $assistantMessage->id, 'ai_knowledge_chunk_id' => $sourceId, 'citation_order' => $order + 1, 'label' => 'Nguồn bài học '.($order + 1)]);
            }
            $thread->update(['last_message_at' => now()]);
            AiUsageLog::query()->create(['user_id' => $request->user()->id, 'chat_message_id' => $assistantMessage->id, 'provider' => 'local', 'model' => 'local-tutor-prototype', 'operation' => 'chat_completion', 'status' => 'succeeded', 'prompt_tokens' => 0, 'completion_tokens' => 0, 'latency_ms' => 0, 'occurred_at' => now(), 'retention_expires_at' => now()->addYear()]);

            return [$userMessage, $assistantMessage];
        });

        return response()->json(['thread_id' => $thread->id, 'user_message' => $userMessage, 'assistant_message' => $assistantMessage->load('sources')], 201);
    }

    public function stream(Request $request, ChatMessage $message): StreamedResponse
    {
        $this->ensureOwner($request, $message->thread);
        $content = $message->content;

        return response()->stream(function () use ($content): void {
            foreach (str_split($content, 24) as $chunk) {
                echo 'data: '.json_encode(['delta' => $chunk], JSON_UNESCAPED_UNICODE)."\n\n";
                if (ob_get_level() > 0) {
                    ob_flush();
                }
                flush();
            }
            echo "data: [DONE]\n\n";
        }, 200, ['Content-Type' => 'text/event-stream', 'Cache-Control' => 'no-cache', 'X-Accel-Buffering' => 'no']);
    }

    public function feedback(Request $request, ChatMessage $message): JsonResponse
    {
        $this->ensureOwner($request, $message->thread);
        $data = $request->validate(['rating' => ['required', 'integer', 'between:1,5'], 'reason' => ['nullable', 'string', 'max:100'], 'comment' => ['nullable', 'string', 'max:1000']]);

        return response()->json(['data' => AiFeedback::query()->updateOrCreate(['chat_message_id' => $message->id, 'user_id' => $request->user()->id], $data)]);
    }

    public function command(Request $request, ChatThread $thread): JsonResponse
    {
        $this->ensureOwner($request, $thread);
        $data = $request->validate(['command' => ['required', 'string', 'in:play,pause,reset,seek']]);

        return response()->json(['accepted' => true, 'command' => $data['command']]);
    }

    private function ensureOwner(Request $request, ChatThread $thread): void
    {
        abort_unless($thread->user_id === $request->user()->id, 403);
    }
}
