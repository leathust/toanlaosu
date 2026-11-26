<?php

namespace App\Services\Admin;

use App\Data\Admin\AdminDashboardFilters;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

final class AdminLearningAnalyticsQuery
{
    /** @return array<string, mixed> */
    public function overview(AdminDashboardFilters $filters): array
    {
        return [
            'filters' => ['range' => $filters->range, 'course' => $filters->courseId, 'timezone' => $filters->timezone],
            'courseOverview' => $this->courseOverview($filters),
            'funnel' => $this->funnel($filters),
            'questionDiagnostics' => $this->questionDiagnostics($filters),
            'mastery' => $this->mastery($filters),
            'metadata' => ['generatedAt' => now()->toIso8601String(), 'from' => $filters->start()->toIso8601String(), 'to' => $filters->end()->toIso8601String(), 'timezone' => $filters->timezone],
        ];
    }

    /** @return list<array<string, mixed>> */
    private function courseOverview(AdminDashboardFilters $filters): array
    {
        $courses = DB::table('courses')->when($filters->courseId, fn (Builder $query, int $id) => $query->where('id', $id))->orderBy('title')->get(['id', 'title']);
        $enrolled = DB::table('enrollments')->where('status', 'active')->when($filters->courseId, fn (Builder $query, int $id) => $query->where('course_id', $id))->groupBy('course_id')->selectRaw('course_id, COUNT(DISTINCT user_id) as learner_count')->get()->pluck('learner_count', 'course_id');
        $completed = DB::table('lesson_block_progress as progress')->join('lesson_blocks as blocks', 'blocks.id', '=', 'progress.lesson_block_id')->join('lessons', 'lessons.id', '=', 'blocks.lesson_id')->join('units', 'units.id', '=', 'lessons.unit_id')->where('blocks.is_blocking', true)->where('progress.status', 'completed')->whereBetween('progress.completed_at', [$filters->start(), $filters->end()])->when($filters->courseId, fn (Builder $query, int $id) => $query->where('units.course_id', $id))->groupBy('units.course_id')->selectRaw('units.course_id, COUNT(DISTINCT progress.user_id) as learner_count')->get()->pluck('learner_count', 'course_id');

        return $courses->map(function (object $course) use ($enrolled, $completed, $filters): array {
            $sampleSize = (int) ($enrolled[$course->id] ?? 0);

            return ['courseId' => $course->id, 'title' => $course->title, 'enrolledLearners' => $sampleSize, 'completingLearners' => (int) ($completed[$course->id] ?? 0), 'completionRate' => $sampleSize > 0 ? round(((int) ($completed[$course->id] ?? 0) / $sampleSize) * 100, 2) : null, 'sampleSize' => $sampleSize, 'reliable' => $sampleSize >= 5, 'drillDownUrl' => route('admin.analytics.learning', ['course' => $course->id, 'range' => $filters->range, 'timezone' => $filters->timezone])];
        })->values()->all();
    }

    /** @return list<array{stage: string, learners: int, rate: float|null}> */
    private function funnel(AdminDashboardFilters $filters): array
    {
        $enrolled = DB::table('enrollments')->where('status', 'active')->when($filters->courseId, fn (Builder $query, int $id) => $query->where('course_id', $id))->distinct()->count('user_id');
        $started = $this->progressQuery($filters)->whereNotNull('progress.started_at')->distinct()->count('progress.user_id');
        $completed = $this->progressQuery($filters)->where('progress.status', 'completed')->distinct()->count('progress.user_id');
        $returned = $this->progressQuery($filters)->whereBetween('progress.last_interaction_at', [$filters->start(), $filters->end()])->distinct()->count('progress.user_id');

        return collect([['stage' => 'enrolled', 'learners' => $enrolled], ['stage' => 'started', 'learners' => $started], ['stage' => 'completed', 'learners' => $completed], ['stage' => 'active_in_window', 'learners' => $returned]])->map(fn (array $stage): array => [...$stage, 'rate' => $enrolled > 0 ? round(($stage['learners'] / $enrolled) * 100, 2) : null])->all();
    }

    /** @return list<array<string, mixed>> */
    private function questionDiagnostics(AdminDashboardFilters $filters): array
    {
        return $this->progressQuestionQuery($filters)->selectRaw('blocks.question_id as question_id, MIN(lessons.id) as lesson_id, COUNT(attempts.id) as attempts, SUM(CASE WHEN attempts.is_correct = 1 THEN 1 ELSE 0 END) as correct, AVG(attempts.response_time_ms) as avg_response_time_ms')->groupBy('blocks.question_id')->orderByDesc('attempts')->limit(50)->get()->map(fn (object $row): array => ['questionId' => $row->question_id, 'lessonId' => $row->lesson_id, 'questionDrillDownUrl' => route('admin.analytics.learning.drilldown', ['type' => 'question', 'id' => $row->question_id, 'range' => $filters->range, 'timezone' => $filters->timezone]), 'lessonDrillDownUrl' => route('admin.analytics.learning.drilldown', ['type' => 'lesson', 'id' => $row->lesson_id, 'range' => $filters->range, 'timezone' => $filters->timezone]), 'attempts' => (int) $row->attempts, 'correct' => (int) $row->correct, 'accuracy' => ((int) $row->attempts) > 0 ? round(((int) $row->correct / (int) $row->attempts) * 100, 2) : null, 'avgResponseTimeMs' => $row->avg_response_time_ms !== null ? round((float) $row->avg_response_time_ms, 2) : null, 'sampleSize' => (int) $row->attempts, 'reliable' => (int) $row->attempts >= 5])->all();
    }

    /** @return list<array<string, mixed>> */
    private function mastery(AdminDashboardFilters $filters): array
    {
        return DB::table('user_mastery')->join('skills', 'skills.id', '=', 'user_mastery.skill_id')->selectRaw('skills.id as skill_id, skills.name, COUNT(DISTINCT user_mastery.user_id) as learners, AVG(user_mastery.mastery_score) as mastery, SUM(user_mastery.attempts_count) as attempts')->groupBy('skills.id', 'skills.name')->orderByDesc('mastery')->limit(50)->get()->map(fn (object $row): array => ['skillId' => $row->skill_id, 'name' => $row->name, 'learners' => (int) $row->learners, 'mastery' => round((float) $row->mastery, 2), 'attempts' => (int) $row->attempts, 'sampleSize' => (int) $row->learners, 'reliable' => (int) $row->learners >= 5])->all();
    }

    private function progressQuery(AdminDashboardFilters $filters): Builder
    {
        return DB::table('lesson_block_progress as progress')->join('lesson_blocks as blocks', 'blocks.id', '=', 'progress.lesson_block_id')->join('lessons', 'lessons.id', '=', 'blocks.lesson_id')->join('units', 'units.id', '=', 'lessons.unit_id')->where('blocks.is_blocking', true)->when($filters->courseId, fn (Builder $query, int $id) => $query->where('units.course_id', $id));
    }

    private function progressQuestionQuery(AdminDashboardFilters $filters): Builder
    {
        return DB::table('user_attempts as attempts')->join('lesson_blocks as blocks', 'blocks.id', '=', 'attempts.lesson_block_id')->join('lessons', 'lessons.id', '=', 'blocks.lesson_id')->join('units', 'units.id', '=', 'lessons.unit_id')->whereNotNull('attempts.submitted_at')->whereBetween('attempts.submitted_at', [$filters->start(), $filters->end()])->when($filters->courseId, fn (Builder $query, int $id) => $query->where('units.course_id', $id));
    }
}
