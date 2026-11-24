<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\Lesson;
use App\Models\Question;
use App\Models\User;
use App\Models\VisualScene;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;

class TeacherController extends Controller
{
    public function dashboard(Request $request): View
    {
        $this->ensureTeacher($request->user());
        $courses = $request->user()->teachingCourses()->wherePivotNull('revoked_at')->with('units.lessons')->get();

        return view('teacher.dashboard', compact('courses'));
    }

    public function course(Request $request, Course $course): View
    {
        $this->ensureCourseAccess($request->user(), $course);
        $course->load('units.lessons.blocks');

        return view('teacher.course', compact('course'));
    }

    public function storeLesson(Request $request, Course $course): JsonResponse
    {
        $this->ensureCourseEditor($request->user(), $course);
        $data = $request->validate(['unit_id' => ['required', 'integer', 'exists:units,id'], 'title' => ['required', 'string', 'max:255'], 'summary' => ['nullable', 'string', 'max:2000']]);
        abort_unless($course->units()->whereKey($data['unit_id'])->exists(), 422);
        $lesson = Lesson::query()->create(['unit_id' => $data['unit_id'], 'slug' => Str::slug($data['title']).'-'.Str::lower(Str::random(5)), 'title' => $data['title'], 'summary' => $data['summary'] ?? null, 'status' => 'draft', 'sort_order' => (int) $course->units()->find($data['unit_id'])->lessons()->max('sort_order') + 1, 'created_by' => $request->user()->id, 'updated_by' => $request->user()->id]);

        return response()->json(['data' => $lesson], 201);
    }

    public function storeQuestion(Request $request): JsonResponse
    {
        $this->ensureTeacher($request->user());
        $data = $request->validate(['stem' => ['required', 'string', 'max:10000'], 'question_type' => ['required', 'string', 'in:single_choice,multiple_choice,numeric'], 'difficulty' => ['required', 'string', 'in:basic,intermediate,advanced']]);
        $question = DB::transaction(function () use ($data, $request): Question {
            $question = Question::query()->create(['public_id' => (string) Str::ulid(), 'owner_id' => $request->user()->id, 'question_type' => $data['question_type'], 'difficulty' => $data['difficulty'], 'status' => 'draft', 'created_by' => $request->user()->id, 'updated_by' => $request->user()->id]);
            $question->versions()->create(['version' => 1, 'stem' => $data['stem'], 'answer_config' => ['type' => $data['question_type']], 'scoring_policy' => ['points' => 1], 'allow_negative' => false, 'content_hash' => hash('sha256', $data['stem']), 'status' => 'draft', 'created_by' => $request->user()->id]);

            return $question->load('versions');
        });

        return response()->json(['data' => $question], 201);
    }

    public function storeScene(Request $request): JsonResponse
    {
        $this->ensureTeacher($request->user());
        $data = $request->validate(['name' => ['required', 'string', 'max:255'], 'scene_key' => ['required', 'string', 'max:100', 'regex:/^[a-z0-9-]+$/'], 'engine' => ['required', 'string', 'in:svg,canvas']]);
        $scene = DB::transaction(function () use ($data, $request): VisualScene {
            $scene = VisualScene::query()->create(['public_id' => (string) Str::ulid(), 'name' => $data['name'], 'engine' => $data['engine'], 'scene_key' => $data['scene_key'], 'status' => 'draft', 'created_by' => $request->user()->id, 'updated_by' => $request->user()->id]);
            $scene->versions()->create(['version' => 1, 'schema_version' => '1.0', 'config' => ['objects' => []], 'steps' => [], 'allowed_commands' => ['play', 'pause', 'reset', 'seek'], 'status' => 'draft', 'created_by' => $request->user()->id]);

            return $scene->load('versions');
        });

        return response()->json(['data' => $scene], 201);
    }

    private function ensureTeacher(User $user): void
    {
        abort_unless($user->roles()->where('name', 'teacher')->exists(), 403);
    }

    private function ensureCourseAccess(User $user, Course $course): void
    {
        $this->ensureTeacher($user);
        abort_unless($user->teachingCourses()->whereKey($course->id)->wherePivotNull('revoked_at')->exists(), 403);
    }

    private function ensureCourseEditor(User $user, Course $course): void
    {
        $this->ensureCourseAccess($user, $course);
        abort_unless($user->teachingCourses()->whereKey($course->id)->wherePivotIn('scope', ['editor', 'owner'])->wherePivotNull('revoked_at')->exists(), 403);
    }
}
