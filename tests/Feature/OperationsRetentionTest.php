<?php

use App\Actions\Operations\QueueExport;
use App\Jobs\ProcessExportJob;
use App\Models\AiUsageLog;
use App\Models\AuditLog;
use App\Models\ChatMessage;
use App\Models\ChatMessageSource;
use App\Models\ChatThread;
use App\Models\ExamAttempt;
use App\Models\ExamAttemptAnswer;
use App\Models\ExamAttemptQuestion;
use App\Models\ExamEvent;
use App\Models\ExamVersion;
use App\Models\LatexTemplateVersion;
use App\Models\User;
use App\Services\Operations\RetentionCleanup;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Queue;

uses(LazilyRefreshDatabase::class);

it('cleans expired audit, exam, AI and chat data while keeping active data', function () {
    $expired = now()->subSecond();
    $audit = AuditLog::factory()->create(['retention_expires_at' => $expired]);
    $usage = AiUsageLog::factory()->create(['retention_expires_at' => $expired]);
    $thread = ChatThread::factory()->create(['retention_expires_at' => $expired]);
    $message = ChatMessage::factory()->create(['chat_thread_id' => $thread->id]);
    ChatMessageSource::factory()->create(['chat_message_id' => $message->id]);
    $attempt = ExamAttempt::factory()->create(['retention_expires_at' => $expired]);
    $question = ExamAttemptQuestion::factory()->create(['exam_attempt_id' => $attempt->id]);
    ExamAttemptAnswer::factory()->create(['exam_attempt_question_id' => $question->id]);
    ExamEvent::factory()->create(['exam_attempt_id' => $attempt->id]);
    $active = AuditLog::factory()->create(['retention_expires_at' => now()->addDay()]);

    $counts = app(RetentionCleanup::class)->run();

    expect($counts)->toMatchArray(['audit' => 1, 'exam_attempts' => 1, 'ai_usage' => 1, 'chat_threads' => 1])
        ->and(AuditLog::query()->find($audit->id))->toBeNull()
        ->and(AiUsageLog::query()->find($usage->id))->toBeNull()
        ->and(ChatThread::query()->find($thread->id))->toBeNull()
        ->and(ExamAttempt::query()->find($attempt->id))->toBeNull()
        ->and(AuditLog::query()->find($active->id))->not->toBeNull();
});

it('supports dry-run retention cleanup and operational health command', function () {
    AuditLog::factory()->create(['retention_expires_at' => now()->subDay()]);

    $this->artisan('ops:cleanup-retention', ['--dry-run' => true])->assertExitCode(0);
    expect(AuditLog::query()->count())->toBe(1);
    $this->artisan('ops:health-check')->assertExitCode(0);
});

it('queues an export once and processes it idempotently', function () {
    Queue::fake();
    $user = User::factory()->create();
    $version = ExamVersion::factory()->create();
    $template = LatexTemplateVersion::factory()->create();
    $action = app(QueueExport::class);

    $first = $action->execute($user, $version->id, $template->id, 'export-once');
    $second = $action->execute($user, $version->id, $template->id, 'export-once');
    Queue::assertPushed(ProcessExportJob::class, 1);

    app(ProcessExportJob::class, ['exportJobId' => $first->id])->handle();
    expect($first->is($second))->toBeTrue()
        ->and($first->fresh()->status)->toBe('completed');
});
