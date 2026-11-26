<?php

namespace App\Actions\Exams;

use App\Models\ExamAttempt;
use App\Models\ExamEvent;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class SubmitExamAttempt
{
    /**
     * @return array{attempt:ExamAttempt,idempotent:bool}
     */
    public function execute(User $actor, ExamAttempt $attempt): array
    {
        return DB::transaction(function () use ($actor, $attempt): array {
            $lockedAttempt = ExamAttempt::query()->lockForUpdate()->with('questions.questionVersion.options')->findOrFail($attempt->id);
            abort_unless($lockedAttempt->user_id === $actor->id, 403);
            if ($lockedAttempt->status === 'submitted') {
                return ['attempt' => $lockedAttempt, 'idempotent' => true];
            }
            abort_if($lockedAttempt->status !== 'in_progress', 409, 'Bài thi không còn hiệu lực.');

            $score = 0.0;
            foreach ($lockedAttempt->questions as $question) {
                $answer = $question->answer;
                $value = $answer?->answer['value'] ?? null;
                $correct = $value !== null && $question->questionVersion->options->where('is_correct', true)->pluck('option_key')->map(fn ($key): string => (string) $key)->contains((string) $value);
                $points = (float) $question->points;
                $answer?->update(['is_correct' => $correct, 'score' => $correct ? $points : 0, 'submitted_at' => now()]);
                $score += $correct ? $points : 0;
            }

            abort_if($lockedAttempt->deadline_at && now()->gt($lockedAttempt->deadline_at), 409, 'Bài thi đã hết giờ.');
            $lockedAttempt->update(['status' => 'submitted', 'submitted_at' => now(), 'graded_at' => now(), 'score' => $score]);
            ExamEvent::query()->create(['exam_attempt_id' => $lockedAttempt->id, 'event_type' => 'submitted', 'client_occurred_at' => now(), 'ip_address' => request()->ip(), 'user_agent' => request()->userAgent(), 'metadata' => ['score' => $score]]);

            return ['attempt' => $lockedAttempt->fresh(), 'idempotent' => false];
        });
    }
}
