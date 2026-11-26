<?php

namespace App\Http\Controllers;

use App\Actions\Scenes\ValidateSceneConfiguration;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Lesson;
use App\Models\LessonBlock;
use App\Models\LessonBlockProgress;
use App\Models\QuestionVersion;
use App\Models\UserAttempt;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Inertia\Inertia;
use Inertia\Response;

class StudentLearningController extends Controller
{
    public function inertiaDashboard(Request $request): Response
    {
        $user = $request->user();
        $enrollments = Enrollment::query()
            ->with('course.units.lessons')
            ->where('user_id', $user->id)
            ->activeAt(now())
            ->latest('id')
            ->get();

        $courses = $enrollments->map(function (Enrollment $enrollment) use ($user): array {
            $course = $enrollment->course;
            $lessonIds = $course->units->flatMap(fn ($unit) => $unit->lessons->pluck('id'));
            $totalBlocks = LessonBlock::query()->whereIn('lesson_id', $lessonIds)->count();
            $completedBlocks = LessonBlockProgress::query()
                ->where('user_id', $user->id)
                ->where('status', 'completed')
                ->whereHas('lessonBlock', fn ($query) => $query->whereIn('lesson_id', $lessonIds))
                ->count();

            return [
                'slug' => $course->slug,
                'title' => $course->title,
                'summary' => $course->summary,
                'difficulty' => $course->difficulty,
                'period' => $enrollment->period_number,
                'progress' => $totalBlocks > 0 ? (int) round(($completedBlocks / $totalBlocks) * 100) : 0,
                'lessons' => $course->units->flatMap(fn ($unit) => $unit->lessons->map(fn ($lesson) => [
                    'slug' => $lesson->slug,
                    'title' => $lesson->title,
                    'unit' => $unit->title,
                ]))->values()->all(),
            ];
        })->values()->all();

        return Inertia::render('Student/LearningDashboard', [
            'student' => ['name' => $user->name],
            'courses' => $courses,
        ]);
    }

    public function inertiaLesson(Request $request, Course $course, Lesson $lesson, ValidateSceneConfiguration $sceneValidator): Response
    {
        $lesson->loadMissing(['unit', 'blocks.question.publishedVersion.options', 'blocks.visualScene.publishedVersion']);
        abort_unless($lesson->unit?->course_id === $course->id, 404);
        abort_unless(Enrollment::query()->where('user_id', $request->user()->id)->where('course_id', $course->id)->activeAt(now())->exists(), 403);

        $progress = LessonBlockProgress::query()
            ->where('user_id', $request->user()->id)
            ->whereIn('lesson_block_id', $lesson->blocks->pluck('id'))
            ->get()
            ->keyBy('lesson_block_id');

        return Inertia::render('Student/Lesson', [
            'course' => ['slug' => $course->slug, 'title' => $course->title],
            'lesson' => [
                'slug' => $lesson->slug,
                'title' => $lesson->title,
                'summary' => $lesson->summary,
                'blocks' => $lesson->blocks->map(fn (LessonBlock $block): array => [
                    'id' => $block->id,
                    'type' => $block->block_type,
                    'body' => $block->body,
                    'settings' => $block->settings,
                    'completed' => $progress->get($block->id)?->status === 'completed',
                    'question' => $block->question?->publishedVersion ? [
                        'id' => $block->question->publishedVersion->id,
                        'stem' => $block->question->publishedVersion->stem,
                        'type' => $block->question->publishedVersion->answer_config['type'] ?? $block->question->question_type,
                        'options' => $block->question->publishedVersion->options->map(fn ($option): array => ['key' => $option->option_key, 'content' => $option->content])->values()->all(),
                    ] : null,
                    'scene' => $block->visualScene?->publishedVersion ? [
                        'public_id' => $block->visualScene->public_id,
                        'name' => $block->visualScene->name,
                        'scene_key' => $block->visualScene->scene_key,
                        'schema_version' => $block->visualScene->publishedVersion->schema_version,
                        'config' => $sceneValidator->execute($block->visualScene->publishedVersion->config, $block->visualScene->publishedVersion->allowed_commands ?? []),
                        'steps' => $block->visualScene->publishedVersion->steps ?? [],
                        'allowed_commands' => $block->visualScene->publishedVersion->allowed_commands ?? [],
                        'fallback' => $block->visualScene->publishedVersion->fallback_path ? ['disk' => $block->visualScene->publishedVersion->fallback_disk, 'path' => $block->visualScene->publishedVersion->fallback_path] : null,
                    ] : null,
                ])->values()->all(),
            ],
        ]);
    }

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
