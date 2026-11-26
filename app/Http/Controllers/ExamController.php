<?php

namespace App\Http\Controllers;

use App\Actions\Exams\SaveExamAnswer;
use App\Actions\Exams\SubmitExamAttempt;
use App\Http\Requests\SaveExamAnswerRequest;
use App\Models\Exam;
use App\Models\ExamAttempt;
use App\Models\ExamEvent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;

class ExamController extends Controller
{
    public function show(Request $request, Exam $exam): View
    {
        $attempt = $this->resolveAttempt($request, $exam);
        $attempt->load('questions.questionVersion.options', 'questions.answer');

        return view('student.exam', ['exam' => $exam, 'attempt' => $attempt, 'serverNow' => now()]);
    }

    public function saveAnswer(SaveExamAnswerRequest $request, ExamAttempt $attempt, SaveExamAnswer $saveExamAnswer): JsonResponse
    {
        $data = $request->validated();
        $result = $saveExamAnswer->execute($request->user(), $attempt, $data['exam_attempt_question_id'], $data['answer'] ?? null, $data['save_version']);

        return response()->json($result);
    }

    public function submit(Request $request, ExamAttempt $attempt, SubmitExamAttempt $submitExamAttempt): JsonResponse
    {
        $result = $submitExamAttempt->execute($request->user(), $attempt);

        return response()->json(['data' => $result['attempt'], 'idempotent' => $result['idempotent']]);
    }

    public function result(Request $request, ExamAttempt $attempt): View
    {
        $this->ensureOwner($request, $attempt);
        $attempt->load('examVersion.exam', 'questions.questionVersion.options', 'questions.answer');
        $exam = $attempt->examVersion->exam;
        $resultReleased = $exam->result_release_at === null || now()->gte($exam->result_release_at);
        $answersReleased = $exam->answer_release_at === null || now()->gte($exam->answer_release_at);

        return view('student.exam-result', compact('attempt', 'exam', 'resultReleased', 'answersReleased'));
    }

    public function history(Request $request, Exam $exam): View
    {
        $attempts = ExamAttempt::query()->with('examVersion')->where('user_id', $request->user()->id)->whereHas('examVersion', fn (Builder $query) => $query->where('exam_id', $exam->id))->latest('started_at')->get();

        return view('student.exam-history', compact('exam', 'attempts'));
    }

    private function resolveAttempt(Request $request, Exam $exam): ExamAttempt
    {
        abort_unless($exam->status === 'published', 404);
        abort_if($exam->starts_at && now()->lt($exam->starts_at), 403, 'Kỳ thi chưa mở.');
        abort_if($exam->ends_at && now()->gt($exam->ends_at), 409, 'Kỳ thi đã đóng.');

        return DB::transaction(function () use ($request, $exam): ExamAttempt {
            $version = $exam->versions()->where('status', 'published')->latest('version')->with('questions.questionVersion.options')->lockForUpdate()->firstOrFail();
            $attempt = $version->attempts()->where('user_id', $request->user()->id)->where('status', 'in_progress')->latest('id')->first();
            if ($attempt) {
                return $attempt;
            }

            $number = (int) $version->attempts()->where('user_id', $request->user()->id)->max('attempt_number') + 1;
            abort_if($number > $exam->max_attempts, 409, 'Đã hết số lần thi.');
            $startedAt = now();
            $attempt = $version->attempts()->create(['public_id' => (string) Str::ulid(), 'user_id' => $request->user()->id, 'attempt_number' => $number, 'status' => 'in_progress', 'started_at' => $startedAt, 'deadline_at' => $startedAt->copy()->addMinutes($exam->duration_minutes), 'max_score' => $version->total_points, 'retention_expires_at' => now()->addYear()]);
            foreach ($version->questions as $question) {
                $attempt->questions()->create(['exam_question_id' => $question->id, 'question_version_id' => $question->question_version_id, 'sort_order' => $question->sort_order, 'points' => $question->points, 'snapshot' => ['stem' => $question->questionVersion->stem, 'answer_config' => $question->questionVersion->answer_config]]);
            }
            $this->recordEvent($attempt, 'started');

            return $attempt;
        });
    }

    private function ensureOwner(Request $request, ExamAttempt $attempt): void
    {
        abort_unless($attempt->user_id === $request->user()->id, 403);
    }

    private function recordEvent(ExamAttempt $attempt, string $type, array $metadata = []): void
    {
        ExamEvent::query()->create(['exam_attempt_id' => $attempt->id, 'event_type' => $type, 'client_occurred_at' => now(), 'ip_address' => request()->ip(), 'user_agent' => request()->userAgent(), 'metadata' => $metadata]);
    }
}
