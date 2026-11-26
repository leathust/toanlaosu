<?php

namespace App\Services\Operations;

use App\Models\AiFeedback;
use App\Models\AiUsageLog;
use App\Models\AuditLog;
use App\Models\ChatMessage;
use App\Models\ChatMessageSource;
use App\Models\ChatThread;
use App\Models\ExamAttempt;
use App\Models\ExamAttemptAnswer;
use App\Models\ExamEvent;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class RetentionCleanup
{
    /**
     * @return array{audit:int,exam_attempts:int,ai_usage:int,chat_threads:int}
     */
    public function run(bool $dryRun = false): array
    {
        $cutoff = now();
        $counts = [
            'audit' => AuditLog::query()->where('retention_expires_at', '<', $cutoff)->count(),
            'exam_attempts' => ExamAttempt::query()->where('retention_expires_at', '<', $cutoff)->count(),
            'ai_usage' => AiUsageLog::query()->where('retention_expires_at', '<', $cutoff)->count(),
            'chat_threads' => ChatThread::query()->where('retention_expires_at', '<', $cutoff)->count(),
        ];
        if ($dryRun) {
            Log::info('retention.cleanup.dry_run', $counts);
            Cache::forever('ops:retention:last_run', now()->toIso8601String());

            return $counts;
        }

        DB::transaction(function () use ($cutoff): void {
            AuditLog::query()->where('retention_expires_at', '<', $cutoff)->delete();
            AiUsageLog::query()->where('retention_expires_at', '<', $cutoff)->delete();
            ExamAttempt::query()->where('retention_expires_at', '<', $cutoff)->get()->each(function (ExamAttempt $attempt): void {
                $questionIds = $attempt->questions()->pluck('id');
                ExamAttemptAnswer::query()->whereIn('exam_attempt_question_id', $questionIds)->delete();
                ExamEvent::query()->where('exam_attempt_id', $attempt->id)->delete();
                $attempt->questions()->delete();
                $attempt->delete();
            });
            ChatThread::query()->where('retention_expires_at', '<', $cutoff)->get()->each(function (ChatThread $thread): void {
                $messageIds = $thread->messages()->pluck('id');
                AiFeedback::query()->whereIn('chat_message_id', $messageIds)->delete();
                ChatMessageSource::query()->whereIn('chat_message_id', $messageIds)->delete();
                ChatMessage::query()->whereIn('id', $messageIds)->delete();
                $thread->delete();
            });
        });

        Log::info('retention.cleanup.completed', $counts);
        Cache::forever('ops:retention:last_run', now()->toIso8601String());

        return $counts;
    }
}
