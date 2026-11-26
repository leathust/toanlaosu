<?php

namespace App\Actions\Exams;

use App\Models\ExamAttempt;
use App\Models\ExamAttemptAnswer;
use App\Models\ExamEvent;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SaveExamAnswer
{
    /**
     * @return array{answer:ExamAttemptAnswer,save_version:int}
     */
    public function execute(User $actor, ExamAttempt $attempt, int $questionId, mixed $answer, int $saveVersion): array
    {
        return DB::transaction(function () use ($actor, $attempt, $questionId, $answer, $saveVersion): array {
            $lockedAttempt = ExamAttempt::query()->lockForUpdate()->findOrFail($attempt->id);
            abort_unless($lockedAttempt->user_id === $actor->id, 403);
            abort_if($lockedAttempt->status !== 'in_progress', 409, 'Bài thi không còn mở.');
            abort_if($lockedAttempt->deadline_at && now()->gt($lockedAttempt->deadline_at), 409, 'Bài thi đã hết giờ.');
            abort_if($saveVersion < $lockedAttempt->save_version, 409, 'Phiên bản trả lời đã cũ.');

            $question = $lockedAttempt->questions()->findOrFail($questionId);
            $nextVersion = $lockedAttempt->save_version + 1;
            $savedAnswer = ExamAttemptAnswer::query()->updateOrCreate(
                ['exam_attempt_question_id' => $question->id],
                ['answer' => is_array($answer) ? $answer : ['value' => $answer], 'save_version' => $nextVersion, 'first_saved_at' => now(), 'last_saved_at' => now()],
            );
            $lockedAttempt->update(['save_version' => $nextVersion]);
            Log::info('exam.answer_saved', ['attempt_id' => $lockedAttempt->id, 'question_id' => $question->id, 'save_version' => $nextVersion]);
            ExamEvent::query()->create(['exam_attempt_id' => $lockedAttempt->id, 'event_type' => 'answer_saved', 'client_occurred_at' => now(), 'ip_address' => request()->ip(), 'user_agent' => request()->userAgent(), 'metadata' => ['question_id' => $question->id, 'save_version' => $nextVersion]]);

            return ['answer' => $savedAnswer, 'save_version' => $nextVersion];
        });
    }
}
