<?php

use App\Contracts\AI\GenerativeProvider;
use App\Exceptions\AiTimeoutException;
use App\Models\AiKnowledgeChunk;
use App\Models\AiKnowledgeDocument;
use App\Models\AiUsageLog;
use App\Models\ChatMessageSource;
use App\Models\ChatThread;
use App\Models\SystemSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

it('retrieves indexed text and persists mandatory citations', function () {
    $user = User::factory()->create();
    $document = AiKnowledgeDocument::factory()->create(['title' => 'Radar basics', 'status' => 'indexed']);
    $chunk = AiKnowledgeChunk::factory()->for($document, 'document')->create(['content' => 'Radar distance determines detection range.']);

    $this->actingAs($user)
        ->postJson(route('student.chat.send'), ['content' => 'How does radar distance affect detection?'])
        ->assertCreated()
        ->assertJsonPath('assistant_message.sources.0.ai_knowledge_chunk_id', $chunk->id);

    expect(ChatMessageSource::query()->where('ai_knowledge_chunk_id', $chunk->id)->count())->toBe(1);
});

it('retries a transient provider failure and records usage', function () {
    $state = (object) ['attempts' => 0];
    app()->bind(GenerativeProvider::class, function () use ($state): GenerativeProvider {
        return new class($state) implements GenerativeProvider
        {
            public function __construct(private object $state) {}

            public function complete(string $prompt, array $sources): array
            {
                $this->state->attempts++;
                if ($this->state->attempts === 1) {
                    throw new RuntimeException('transient provider failure');
                }

                return ['content' => 'ok', 'model' => 'fake', 'provider' => 'fake', 'prompt_tokens' => 2, 'completion_tokens' => 1, 'citations' => []];
            }
        };
    });
    $user = User::factory()->create();

    $this->actingAs($user)->postJson(route('student.chat.send'), ['content' => 'retry me'])->assertCreated();

    expect($state->attempts)->toBe(2)->and(AiUsageLog::query()->where('provider', 'fake')->count())->toBe(1);
});

it('rejects chat when the daily AI budget is exhausted', function () {
    $user = User::factory()->create();
    SystemSetting::factory()->create(['key' => 'ai.daily_budget', 'value' => 1]);
    AiUsageLog::factory()->create(['user_id' => $user->id, 'prompt_tokens' => 1, 'completion_tokens' => 1, 'occurred_at' => now()]);

    $this->actingAs($user)
        ->postJson(route('student.chat.send'), ['content' => 'over budget'])
        ->assertStatus(429);
});

it('deduplicates a retried request by request id', function () {
    $user = User::factory()->create();
    $payload = ['content' => 'same request', 'request_id' => 'retry-123'];

    $this->actingAs($user)->postJson(route('student.chat.send'), $payload)->assertCreated();
    $this->actingAs($user)->postJson(route('student.chat.send'), $payload)->assertCreated();

    expect(ChatThread::query()->where('user_id', $user->id)->first()->messages()->count())->toBe(2);
});

it('returns service unavailable after provider timeout retries', function () {
    app()->bind(GenerativeProvider::class, fn (): GenerativeProvider => new class implements GenerativeProvider
    {
        public function complete(string $prompt, array $sources): array
        {
            throw new AiTimeoutException('provider timeout');
        }
    });

    $this->actingAs(User::factory()->create())
        ->postJson(route('student.chat.send'), ['content' => 'timeout'])
        ->assertStatus(503);
});
