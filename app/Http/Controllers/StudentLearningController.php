<?php

namespace App\Http\Controllers;

use App\Models\Enrollment;
use App\Models\LessonBlock;
use App\Models\LessonBlockProgress;
use App\Models\QuestionVersion;
use App\Models\UserAttempt;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class StudentLearningController extends Controller
{
    public function dashboard(Request $request): View
    {
        if (! $request->user()) {
            return view('student.dashboard', [
                'studentName' => 'An',
                'enrollmentCount' => 2,
                'isPrototype' => true,
            ]);
        }

        $now = now();
        $enrollments = Enrollment::query()
            ->with('course.units.lessons')
            ->where('user_id', $request->user()->id)
            ->where('status', 'active')
            ->where(function ($query) use ($now): void {
                $query->whereNull('starts_at')->orWhere('starts_at', '<=', $now);
            })
            ->where(function ($query) use ($now): void {
                $query->whereNull('ends_at')->orWhere('ends_at', '>=', $now);
            })
            ->latest('id')
            ->get();

        return view('student.dashboard', [
            'studentName' => $request->user()->name,
            'enrollmentCount' => $enrollments->count(),
            'enrollments' => $enrollments,
            'isPrototype' => false,
        ]);
    }

    public function completeBlock(Request $request, LessonBlock $lessonBlock): JsonResponse
    {
        $this->ensureCourseAccess($request, $lessonBlock);

        $progress = DB::transaction(function () use ($request, $lessonBlock): LessonBlockProgress {
            $progress = LessonBlockProgress::query()->firstOrCreate(
                ['user_id' => $request->user()->id, 'lesson_block_id' => $lessonBlock->id],
                ['status' => 'in_progress', 'started_at' => now(), 'last_interaction_at' => now()]
            );

            $progress->update([
                'status' => 'completed',
                'completed_at' => $progress->completed_at ?? now(),
                'last_interaction_at' => now(),
            ]);

            return $progress->refresh();
        });

        return response()->json(['data' => $progress], 200);
    }

    public function submitAttempt(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'question_version_id' => ['required', 'integer', 'exists:question_versions,id'],
            'lesson_block_id' => ['nullable', 'integer', 'exists:lesson_blocks,id'],
            'answer' => ['required'],
        ]);

        $version = QuestionVersion::query()->with('options')->findOrFail($validated['question_version_id']);
        if (! empty($validated['lesson_block_id'])) {
            $this->ensureCourseAccess($request, LessonBlock::query()->findOrFail($validated['lesson_block_id']));
        }

        $correct = $this->isAnswerCorrect($version, $validated['answer']);
        $maxScore = (float) ($version->scoring_policy['points'] ?? 1);
        $score = $correct ? $maxScore : 0.0;
        $attemptNumber = (int) UserAttempt::query()
            ->where('user_id', $request->user()->id)
            ->where('question_version_id', $version->id)
            ->max('attempt_number') + 1;

        $attempt = UserAttempt::query()->create([
            'user_id' => $request->user()->id,
            'question_version_id' => $version->id,
            'lesson_block_id' => $validated['lesson_block_id'] ?? null,
            'attempt_number' => $attemptNumber,
            'status' => 'submitted',
            'answer' => is_array($validated['answer']) ? $validated['answer'] : ['value' => $validated['answer']],
            'is_correct' => $correct,
            'score' => $score,
            'max_score' => $maxScore,
            'submitted_at' => now(),
        ]);

        return response()->json(['data' => $attempt], 201);
    }

    private function ensureCourseAccess(Request $request, LessonBlock $lessonBlock): void
    {
        $lessonBlock->loadMissing('lesson.unit');
        $now = now();
        $hasAccess = Enrollment::query()
            ->where('user_id', $request->user()->id)
            ->where('course_id', $lessonBlock->lesson->unit->course_id)
            ->where('status', 'active')
            ->where(function ($query) use ($now): void {
                $query->whereNull('starts_at')->orWhere('starts_at', '<=', $now);
            })
            ->where(function ($query) use ($now): void {
                $query->whereNull('ends_at')->orWhere('ends_at', '>=', $now);
            })
            ->exists();

        abort_unless($hasAccess, 403);
    }

    private function isAnswerCorrect(QuestionVersion $version, mixed $answer): bool
    {
        $type = $version->answer_config['type'] ?? $version->question?->question_type;
        if ($type === 'numeric') {
            $expected = (float) ($version->answer_config['expected'] ?? 0);
            $tolerance = (float) ($version->answer_config['tolerance'] ?? 0);

            return abs((float) $answer - $expected) <= $tolerance;
        }

        $selected = is_array($answer) ? ($answer['value'] ?? $answer) : [$answer];
        $selected = array_values(array_map('strval', (array) $selected));
        $correct = $version->options->where('is_correct', true)->pluck('option_key')->map(fn ($key): string => (string) $key)->values()->all();
        sort($selected);
        sort($correct);

        return $selected === $correct;
    }
}
