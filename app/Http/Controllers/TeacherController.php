<?php

namespace App\Http\Controllers;

use App\Actions\Authoring\DuplicateLessonDraft;
use App\Actions\Authoring\PublishLesson;
use App\Actions\Authoring\UpdateLessonDraft;
use App\Actions\Authoring\UpdateQuestionVersion;
use App\Actions\Authoring\UpdateVisualSceneVersion;
use App\Http\Requests\UpdateLessonDraftRequest;
use App\Http\Requests\UpdateQuestionVersionRequest;
use App\Http\Requests\UpdateVisualSceneVersionRequest;
use App\Models\ContentRevision;
use App\Models\Course;
use App\Models\Lesson;
use App\Models\Question;
use App\Models\VisualScene;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

class TeacherController extends Controller
{
    public function dashboard(Request $request): View
    {
        $this->authorize('viewAny', Course::class);
        $courses = $request->user()->teachingCourses()->wherePivotNull('revoked_at')->with('units.lessons')->get();

        return view('teacher.dashboard', compact('courses'));
    }

    public function course(Request $request, Course $course): View
    {
        $this->authorize('view', $course);
        $course->load('units.lessons.blocks');

        return view('teacher.course', compact('course'));
    }

    public function editLesson(Request $request, Lesson $lesson): Response
    {
        $lesson->loadMissing('unit.course');
        $this->authorize('update', $lesson->unit->course);

        return Inertia::render('Teacher/LessonEditor', [
            'course' => $lesson->unit->course->only(['id', 'slug', 'title']),
            'lesson' => $lesson->only(['id', 'slug', 'title', 'summary', 'status', 'lock_version', 'published_at']),
            'revisions' => ContentRevision::query()
                ->where('content_type', 'lesson')
                ->where('content_id', $lesson->getKey())
                ->latest('version')
                ->get(['id', 'version', 'snapshot', 'change_summary', 'created_by', 'created_at']),
        ]);
    }

    public function storeLesson(Request $request, Course $course): JsonResponse
    {
        $this->authorize('update', $course);
        $data = $request->validate(['unit_id' => ['required', 'integer', 'exists:units,id'], 'title' => ['required', 'string', 'max:255'], 'summary' => ['nullable', 'string', 'max:2000']]);
        abort_unless($course->units()->whereKey($data['unit_id'])->exists(), 422);
        $lesson = Lesson::query()->create(['unit_id' => $data['unit_id'], 'slug' => Str::slug($data['title']).'-'.Str::lower(Str::random(5)), 'title' => $data['title'], 'summary' => $data['summary'] ?? null, 'status' => 'draft', 'sort_order' => (int) $course->units()->find($data['unit_id'])->lessons()->max('sort_order') + 1, 'created_by' => $request->user()->id, 'updated_by' => $request->user()->id]);

        return response()->json(['data' => $lesson], 201);
    }

    public function updateLesson(UpdateLessonDraftRequest $request, Lesson $lesson, UpdateLessonDraft $action): JsonResponse
    {
        try {
            return response()->json(['data' => $action->execute($request->user(), $lesson, $request->validated())]);
        } catch (ConflictHttpException $exception) {
            return response()->json(['message' => $exception->getMessage(), 'data' => $lesson->fresh()->only(['id', 'title', 'summary', 'status', 'lock_version'])], 409);
        }
    }

    public function publishLesson(Request $request, Lesson $lesson, PublishLesson $action): JsonResponse
    {
        return response()->json(['data' => $action->execute($request->user(), $lesson)]);
    }

    public function duplicateLesson(Request $request, Lesson $lesson, DuplicateLessonDraft $action): JsonResponse
    {
        return response()->json(['data' => $action->execute($request->user(), $lesson)], 201);
    }

    public function lessonRevisions(Request $request, Lesson $lesson): JsonResponse
    {
        $lesson->loadMissing('unit.course');
        $this->authorize('update', $lesson->unit->course);

        $revisions = ContentRevision::query()
            ->where('content_type', 'lesson')
            ->where('content_id', $lesson->getKey())
            ->latest('version')
            ->get();

        return response()->json(['data' => $revisions]);
    }

    public function storeQuestion(Request $request): JsonResponse
    {
        $this->authorize('create', Question::class);
        $data = $request->validate(['stem' => ['required', 'string', 'max:10000'], 'question_type' => ['required', 'string', 'in:single_choice,multiple_choice,numeric'], 'difficulty' => ['required', 'string', 'in:basic,intermediate,advanced'], 'solution' => ['nullable', 'string', 'max:30000'], 'solution_blocks' => ['nullable', 'array', 'max:50'], 'solution_blocks.*.type' => ['required', 'string', 'in:text,math,image,video,scene'], 'solution_blocks.*.content' => ['nullable', 'string', 'max:30000'], 'solution_blocks.*.latex' => ['nullable', 'string', 'max:10000'], 'solution_blocks.*.media_id' => ['nullable', 'integer', 'exists:lesson_materials,id'], 'solution_blocks.*.video_id' => ['nullable', 'integer', 'exists:lesson_videos,id'], 'solution_blocks.*.scene_version_id' => ['nullable', 'integer', 'exists:visual_scene_versions,id']]);
        $question = DB::transaction(function () use ($data, $request): Question {
            $question = Question::query()->create(['public_id' => (string) Str::ulid(), 'owner_id' => $request->user()->id, 'question_type' => $data['question_type'], 'difficulty' => $data['difficulty'], 'status' => 'draft', 'created_by' => $request->user()->id, 'updated_by' => $request->user()->id]);
            $question->versions()->create(['version' => 1, 'stem' => $data['stem'], 'solution' => $data['solution'] ?? null, 'solution_blocks' => $data['solution_blocks'] ?? null, 'answer_config' => ['type' => $data['question_type']], 'scoring_policy' => ['points' => 1], 'allow_negative' => false, 'content_hash' => hash('sha256', $data['stem']), 'status' => 'draft', 'created_by' => $request->user()->id]);

            return $question->load('versions');
        });

        return response()->json(['data' => $question], 201);
    }

    public function editQuestion(Request $request, Question $question, int $version): Response
    {
        $this->authorize('update', $question);
        $questionVersion = $question->versions()->where('version', $version)->firstOrFail();

        return Inertia::render('Teacher/QuestionEditor', [
            'question' => $question->only(['id', 'public_id', 'question_type', 'difficulty', 'status']),
            'version' => $questionVersion->only(['id', 'version', 'stem', 'solution', 'solution_blocks', 'status', 'published_at']),
        ]);
    }

    public function updateQuestion(UpdateQuestionVersionRequest $request, Question $question, int $version, UpdateQuestionVersion $action): RedirectResponse
    {
        $questionVersion = $question->versions()->where('version', $version)->firstOrFail();
        $action->execute($request->user(), $question, $questionVersion, $request->validated());

        return redirect()->route('teacher.questions.edit', ['question' => $question->getKey(), 'version' => $version])->with('status', 'Question draft saved.');
    }

    public function storeScene(Request $request): JsonResponse
    {
        $this->authorize('create', VisualScene::class);
        $data = $request->validate(['name' => ['required', 'string', 'max:255'], 'scene_key' => ['required', 'string', 'max:100', 'regex:/^[a-z0-9-]+$/'], 'engine' => ['required', 'string', 'in:svg,canvas']]);
        $scene = DB::transaction(function () use ($data, $request): VisualScene {
            $scene = VisualScene::query()->create(['public_id' => (string) Str::ulid(), 'name' => $data['name'], 'engine' => $data['engine'], 'scene_key' => $data['scene_key'], 'status' => 'draft', 'created_by' => $request->user()->id, 'updated_by' => $request->user()->id]);
            $scene->versions()->create(['version' => 1, 'schema_version' => '1.0', 'config' => ['objects' => []], 'steps' => [], 'allowed_commands' => ['play', 'pause', 'reset', 'seek'], 'status' => 'draft', 'created_by' => $request->user()->id]);

            return $scene->load('versions');
        });

        return response()->json(['data' => $scene], 201);
    }

    public function editScene(Request $request, VisualScene $visualScene, int $version): Response
    {
        $this->authorize('update', $visualScene);
        $sceneVersion = $visualScene->versions()->where('version', $version)->firstOrFail();

        return Inertia::render('Teacher/SceneEditor', [
            'scene' => $visualScene->only(['id', 'public_id', 'name', 'engine', 'scene_key', 'status']),
            'version' => $sceneVersion->only(['id', 'version', 'schema_version', 'config', 'steps', 'allowed_commands', 'status', 'published_at']),
        ]);
    }

    public function updateScene(UpdateVisualSceneVersionRequest $request, VisualScene $visualScene, int $version, UpdateVisualSceneVersion $action): RedirectResponse
    {
        $sceneVersion = $visualScene->versions()->where('version', $version)->firstOrFail();
        $action->execute($request->user(), $visualScene, $sceneVersion, $request->validated());

        return redirect()->route('teacher.scenes.edit', ['visualScene' => $visualScene->getKey(), 'version' => $version])->with('status', 'Scene draft saved.');
    }
}
