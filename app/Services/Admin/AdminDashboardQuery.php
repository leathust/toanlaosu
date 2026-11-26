<?php

namespace App\Services\Admin;

use App\Data\Admin\AdminDashboardFilters;
use Carbon\CarbonImmutable;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

final class AdminDashboardQuery
{
    /**
     * @return array<string, mixed>
     */
    public function overview(AdminDashboardFilters $filters): array
    {
        return Cache::remember($filters->cacheKey(), now()->addSeconds(30), function () use ($filters): array {
            $current = $this->metrics($filters, $filters->start(), $filters->end());
            $comparison = $filters->comparisonStart()
                ? $this->metrics($filters, $filters->comparisonStart(), $filters->comparisonEnd())
                : null;

            return [
                'kpis' => $this->kpis($current, $comparison, $filters),
                'trend' => $this->trend($filters),
                'courseHealth' => $this->courseHealth($filters),
                'freshness' => now()->toIso8601String(),
            ];
        });
    }

    public function forget(AdminDashboardFilters $filters): void
    {
        Cache::forget($filters->cacheKey());
    }

    /**
     * @return array<string, array{value: int|float, numerator: int, denominator: int, status: string}>
     */
    private function metrics(AdminDashboardFilters $filters, CarbonImmutable $from, CarbonImmutable $to): array
    {
        $activeLearners = $this->activityUsers($filters, $from, $to);
        $activeEnrollments = $this->activeEnrollments($filters, $to);
        $totalBlocks = $this->blockingBlocks($filters);
        $completedBlocks = $this->completedBlockingBlocks($filters, $from, $to);
        $submittedAttempts = $this->submittedAttempts($filters, $from, $to);
        $correctAttempts = $this->correctAttempts($filters, $from, $to);
        $examTotal = $this->examAttempts($filters, $from, $to);
        $examSubmitted = $this->examAttempts($filters, $from, $to, ['submitted', 'graded']);
        $aiTotal = $this->aiUsage($filters, $from, $to);
        $aiSuccess = $this->aiUsage($filters, $from, $to, 'succeeded');
        $reviewBacklog = $this->reviewBacklog($filters);

        $returnDenominator = $filters->comparisonStart()
            ? $this->activityUsers($filters, $filters->comparisonStart(), $filters->comparisonEnd())
            : collect();
        $returnNumerator = $activeLearners->intersect($returnDenominator)->count();

        return [
            'active_learners' => ['value' => $activeLearners->count(), 'numerator' => $activeLearners->count(), 'denominator' => 0, 'status' => 'ok'],
            'active_enrollments' => ['value' => $activeEnrollments, 'numerator' => $activeEnrollments, 'denominator' => 0, 'status' => 'ok'],
            'completion' => $this->ratioMetric($completedBlocks, $totalBlocks),
            'question_accuracy' => $this->ratioMetric($correctAttempts, $submittedAttempts),
            'return_rate' => $this->ratioMetric($returnNumerator, $returnDenominator->count()),
            'exam_health' => $this->ratioMetric($examSubmitted, $examTotal),
            'ai_success_rate' => $this->ratioMetric($aiSuccess, $aiTotal),
            'review_backlog' => ['value' => $reviewBacklog, 'numerator' => $reviewBacklog, 'denominator' => 0, 'status' => 'ok'],
        ];
    }

    /**
     * @param  array<string, array{value: int|float, numerator: int, denominator: int, status: string}>  $current
     * @param  array<string, array{value: int|float, numerator: int, denominator: int, status: string}>|null  $comparison
     * @return list<array<string, mixed>>
     */
    private function kpis(array $current, ?array $comparison, AdminDashboardFilters $filters): array
    {
        $labels = [
            'active_learners' => 'Active learners',
            'active_enrollments' => 'Active enrollments',
            'completion' => 'Course completion',
            'question_accuracy' => 'Question accuracy',
            'return_rate' => 'Return rate',
            'exam_health' => 'Exam health',
            'ai_success_rate' => 'AI success rate',
            'review_backlog' => 'Review backlog',
        ];
        $percentMetrics = ['completion', 'question_accuracy', 'return_rate', 'exam_health', 'ai_success_rate'];

        return collect($current)->map(function (array $metric, string $key) use ($comparison, $filters, $labels, $percentMetrics): array {
            $comparisonValue = $comparison[$key]['value'] ?? null;
            $comparisonData = $comparisonValue === null ? null : [
                'value' => $comparisonValue,
                'delta' => round($metric['value'] - $comparisonValue, 2),
                'percent' => $comparisonValue == 0 ? null : round((($metric['value'] - $comparisonValue) / $comparisonValue) * 100, 2),
            ];

            return [
                'key' => $key,
                'label' => $labels[$key],
                'value' => in_array($key, $percentMetrics, true) ? round((float) $metric['value'], 2) : $metric['value'],
                'unit' => in_array($key, $percentMetrics, true) ? 'percent' : 'count',
                'numerator' => $metric['numerator'],
                'denominator' => $metric['denominator'],
                'comparison' => $comparisonData,
                'window' => ['from' => $filters->start()->toIso8601String(), 'to' => $filters->end()->toIso8601String()],
                'timezone' => $filters->timezone,
                'updated_at' => now()->toIso8601String(),
                'freshness_seconds' => 0,
                'status' => $metric['status'],
                'drill_down_url' => '/admin?metric='.$key,
            ];
        })->values()->all();
    }

    /**
     * @return array{value: float, numerator: int, denominator: int, status: string}
     */
    private function ratioMetric(int $numerator, int $denominator): array
    {
        return [
            'value' => $denominator > 0 ? round(($numerator / $denominator) * 100, 2) : 0.0,
            'numerator' => $numerator,
            'denominator' => $denominator,
            'status' => $denominator > 0 ? 'ok' : 'empty',
        ];
    }

    private function activeEnrollments(AdminDashboardFilters $filters, CarbonImmutable $at): int
    {
        return $this->enrollmentQuery($filters, $at)->count();
    }

    private function blockingBlocks(AdminDashboardFilters $filters): int
    {
        return DB::table('lesson_blocks')
            ->join('lessons', 'lessons.id', '=', 'lesson_blocks.lesson_id')
            ->join('units', 'units.id', '=', 'lessons.unit_id')
            ->where('lesson_blocks.is_blocking', true)
            ->when($filters->courseId, fn (Builder $query, int $courseId) => $query->where('units.course_id', $courseId))
            ->count();
    }

    private function completedBlockingBlocks(AdminDashboardFilters $filters, CarbonImmutable $from, CarbonImmutable $to): int
    {
        return DB::table('lesson_block_progress')
            ->join('lesson_blocks', 'lesson_blocks.id', '=', 'lesson_block_progress.lesson_block_id')
            ->join('lessons', 'lessons.id', '=', 'lesson_blocks.lesson_id')
            ->join('units', 'units.id', '=', 'lessons.unit_id')
            ->where('lesson_blocks.is_blocking', true)
            ->where('lesson_block_progress.status', 'completed')
            ->whereBetween('lesson_block_progress.completed_at', [$from, $to])
            ->when($filters->courseId, fn (Builder $query, int $courseId) => $query->where('units.course_id', $courseId))
            ->count();
    }

    private function submittedAttempts(AdminDashboardFilters $filters, CarbonImmutable $from, CarbonImmutable $to): int
    {
        return DB::table('user_attempts')
            ->whereNotNull('submitted_at')
            ->whereBetween('submitted_at', [$from, $to])
            ->when($filters->courseId, fn (Builder $query, int $courseId) => $query
                ->join('lesson_blocks', 'lesson_blocks.id', '=', 'user_attempts.lesson_block_id')
                ->join('lessons', 'lessons.id', '=', 'lesson_blocks.lesson_id')
                ->join('units', 'units.id', '=', 'lessons.unit_id')
                ->where('units.course_id', $courseId))
            ->count();
    }

    private function correctAttempts(AdminDashboardFilters $filters, CarbonImmutable $from, CarbonImmutable $to): int
    {
        return DB::table('user_attempts')
            ->whereNotNull('submitted_at')
            ->where('is_correct', true)
            ->whereBetween('submitted_at', [$from, $to])
            ->when($filters->courseId, fn (Builder $query, int $courseId) => $query
                ->join('lesson_blocks', 'lesson_blocks.id', '=', 'user_attempts.lesson_block_id')
                ->join('lessons', 'lessons.id', '=', 'lesson_blocks.lesson_id')
                ->join('units', 'units.id', '=', 'lessons.unit_id')
                ->where('units.course_id', $courseId))
            ->count();
    }

    private function examAttempts(AdminDashboardFilters $filters, CarbonImmutable $from, CarbonImmutable $to, ?array $statuses = null): int
    {
        return DB::table('exam_attempts')
            ->join('exam_versions', 'exam_versions.id', '=', 'exam_attempts.exam_version_id')
            ->join('exams', 'exams.id', '=', 'exam_versions.exam_id')
            ->whereBetween('exam_attempts.created_at', [$from, $to])
            ->when($statuses, fn (Builder $query, array $statuses) => $query->whereIn('exam_attempts.status', $statuses))
            ->when($filters->courseId, fn (Builder $query, int $courseId) => $query->where('exams.course_id', $courseId))
            ->count();
    }

    private function aiUsage(AdminDashboardFilters $filters, CarbonImmutable $from, CarbonImmutable $to, ?string $status = null): int
    {
        return DB::table('ai_usage_logs')
            ->when($filters->courseId, fn (Builder $query, int $courseId) => $query
                ->join('chat_messages', 'chat_messages.id', '=', 'ai_usage_logs.chat_message_id')
                ->join('chat_threads', 'chat_threads.id', '=', 'chat_messages.chat_thread_id')
                ->where('chat_threads.course_id', $courseId))
            ->whereBetween('ai_usage_logs.occurred_at', [$from, $to])
            ->when($status, fn (Builder $query, string $status) => $query->where('ai_usage_logs.status', $status))
            ->count();
    }

    private function reviewBacklog(AdminDashboardFilters $filters): int
    {
        $questions = DB::table('questions')->where('status', 'draft')->when($filters->courseId, fn (Builder $query, int $courseId) => $query->whereExists(fn (Builder $subQuery) => $subQuery
            ->from('lesson_blocks')
            ->join('lessons', 'lessons.id', '=', 'lesson_blocks.lesson_id')
            ->join('units', 'units.id', '=', 'lessons.unit_id')
            ->whereColumn('lesson_blocks.question_id', 'questions.id')
            ->where('units.course_id', $courseId)))->count();
        $scenes = DB::table('visual_scenes')
            ->where('status', 'draft')
            ->when($filters->courseId, fn (Builder $query, int $courseId) => $query->whereExists(fn (Builder $subQuery) => $subQuery
                ->from('lesson_blocks')
                ->join('lessons', 'lessons.id', '=', 'lesson_blocks.lesson_id')
                ->join('units', 'units.id', '=', 'lessons.unit_id')
                ->whereColumn('lesson_blocks.visual_scene_id', 'visual_scenes.id')
                ->where('units.course_id', $courseId)))
            ->count();
        $courses = DB::table('courses')->where('status', 'draft')->when($filters->courseId, fn (Builder $query, int $courseId) => $query->where('id', $courseId))->count();

        return $questions + $scenes + $courses;
    }

    private function enrollmentQuery(AdminDashboardFilters $filters, CarbonImmutable $at): Builder
    {
        return DB::table('enrollments')
            ->where('status', 'active')
            ->where(function (Builder $query) use ($at): void {
                $query->whereNull('starts_at')->orWhere('starts_at', '<=', $at);
            })
            ->where(function (Builder $query) use ($at): void {
                $query->whereNull('ends_at')->orWhere('ends_at', '>=', $at);
            })
            ->when($filters->courseId, fn (Builder $query, int $courseId) => $query->where('course_id', $courseId));
    }

    /**
     * @return Collection<int, int>
     */
    private function activityUsers(AdminDashboardFilters $filters, CarbonImmutable $from, CarbonImmutable $to)
    {
        return DB::query()
            ->fromSub($this->activityQuery($filters, $from, $to), 'activities')
            ->distinct()
            ->pluck('user_id')
            ->map(fn (mixed $id): int => (int) $id);
    }

    private function activityQuery(AdminDashboardFilters $filters, CarbonImmutable $from, CarbonImmutable $to): Builder
    {
        $queries = [];
        $queries[] = DB::table('lesson_block_progress as progress')
            ->join('lesson_blocks', 'lesson_blocks.id', '=', 'progress.lesson_block_id')
            ->join('lessons', 'lessons.id', '=', 'lesson_blocks.lesson_id')
            ->join('units', 'units.id', '=', 'lessons.unit_id')
            ->whereNotNull('progress.last_interaction_at')
            ->whereBetween('progress.last_interaction_at', [$from, $to])
            ->when($filters->courseId, fn (Builder $query, int $courseId) => $query->where('units.course_id', $courseId))
            ->selectRaw('progress.user_id as user_id, progress.last_interaction_at as occurred_at');
        $queries[] = DB::table('user_attempts as attempts')
            ->when($filters->courseId, fn (Builder $query, int $courseId) => $query
                ->join('lesson_blocks', 'lesson_blocks.id', '=', 'attempts.lesson_block_id')
                ->join('lessons', 'lessons.id', '=', 'lesson_blocks.lesson_id')
                ->join('units', 'units.id', '=', 'lessons.unit_id')
                ->where('units.course_id', $courseId))
            ->whereNotNull('attempts.submitted_at')
            ->whereBetween('attempts.submitted_at', [$from, $to])
            ->selectRaw('attempts.user_id as user_id, attempts.submitted_at as occurred_at');
        $queries[] = DB::table('exam_attempts as attempts')
            ->join('exam_versions', 'exam_versions.id', '=', 'attempts.exam_version_id')
            ->join('exams', 'exams.id', '=', 'exam_versions.exam_id')
            ->whereBetween('attempts.created_at', [$from, $to])
            ->when($filters->courseId, fn (Builder $query, int $courseId) => $query->where('exams.course_id', $courseId))
            ->selectRaw('attempts.user_id as user_id, attempts.created_at as occurred_at');
        $queries[] = DB::table('chat_messages as messages')
            ->join('chat_threads', 'chat_threads.id', '=', 'messages.chat_thread_id')
            ->whereBetween('messages.created_at', [$from, $to])
            ->when($filters->courseId, fn (Builder $query, int $courseId) => $query->where('chat_threads.course_id', $courseId))
            ->selectRaw('chat_threads.user_id as user_id, messages.created_at as occurred_at');

        $union = array_shift($queries);
        foreach ($queries as $query) {
            $union->unionAll($query);
        }

        return $union;
    }

    /**
     * @return array{status: string, series: list<array<string, mixed>>}
     */
    private function trend(AdminDashboardFilters $filters): array
    {
        $from = $filters->start()->subDays(7);
        $rows = DB::query()
            ->fromSub($this->activityQuery($filters, $from, $filters->end()), 'activities')
            ->select('occurred_at', 'user_id')
            ->distinct()
            ->get();
        $grouped = $rows->groupBy(fn (object $row): string => CarbonImmutable::parse($row->occurred_at)->setTimezone($filters->timezone)->toDateString());
        $series = [];
        for ($date = $filters->start()->startOfDay(); $date <= $filters->end()->startOfDay(); $date = $date->addDay()) {
            $bucket = $date->setTimezone($filters->timezone)->toDateString();
            $active = $grouped->get($bucket, collect())->pluck('user_id')->map(fn (mixed $id): int => (int) $id);
            $prior = $rows
                ->filter(function (object $row) use ($date, $filters, $bucket): bool {
                    $rowDate = CarbonImmutable::parse($row->occurred_at)->setTimezone($filters->timezone);

                    return $rowDate->toDateString() < $bucket && $rowDate->greaterThanOrEqualTo($date->setTimezone($filters->timezone)->subDays(7)->toDateString());
                })
                ->pluck('user_id')
                ->unique();
            $series[] = ['label' => $bucket, 'activeLearners' => $active->unique()->count(), 'returningLearners' => $active->intersect($prior)->unique()->count()];
        }

        return ['status' => $series && collect($series)->sum('activeLearners') > 0 ? 'ok' : 'empty', 'series' => $series];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function courseHealth(AdminDashboardFilters $filters): array
    {
        $courses = DB::table('courses')
            ->when($filters->courseId, fn (Builder $query, int $courseId) => $query->where('id', $courseId))
            ->orderBy('title')
            ->get(['id', 'title']);
        $enrollments = $this->enrollmentQuery($filters, $filters->end())
            ->selectRaw('course_id, COUNT(DISTINCT user_id) as learners')
            ->groupBy('course_id')
            ->pluck('learners', 'course_id');
        $totalBlocks = DB::table('lesson_blocks')
            ->join('lessons', 'lessons.id', '=', 'lesson_blocks.lesson_id')
            ->join('units', 'units.id', '=', 'lessons.unit_id')
            ->where('lesson_blocks.is_blocking', true)
            ->groupBy('units.course_id')
            ->pluck(DB::raw('COUNT(*)'), 'units.course_id');
        $completedBlocks = DB::table('lesson_block_progress')
            ->join('lesson_blocks', 'lesson_blocks.id', '=', 'lesson_block_progress.lesson_block_id')
            ->join('lessons', 'lessons.id', '=', 'lesson_blocks.lesson_id')
            ->join('units', 'units.id', '=', 'lessons.unit_id')
            ->where('lesson_blocks.is_blocking', true)
            ->where('lesson_block_progress.status', 'completed')
            ->whereBetween('lesson_block_progress.completed_at', [$filters->start(), $filters->end()])
            ->groupBy('units.course_id')
            ->pluck(DB::raw('COUNT(*)'), 'units.course_id');
        $recentProgress = DB::table('lesson_block_progress')
            ->join('lesson_blocks', 'lesson_blocks.id', '=', 'lesson_block_progress.lesson_block_id')
            ->join('lessons', 'lessons.id', '=', 'lesson_blocks.lesson_id')
            ->join('units', 'units.id', '=', 'lessons.unit_id')
            ->whereBetween('lesson_block_progress.last_interaction_at', [$filters->start(), $filters->end()])
            ->groupBy('units.course_id')
            ->selectRaw('units.course_id, COUNT(DISTINCT lesson_block_progress.user_id) as learners, MAX(lesson_block_progress.last_interaction_at) as freshness')
            ->get()
            ->keyBy('course_id');

        return $courses->map(function (object $course) use ($enrollments, $totalBlocks, $completedBlocks, $recentProgress): array {
            $learners = (int) ($enrollments[$course->id] ?? 0);
            $completed = (int) ($completedBlocks[$course->id] ?? 0);
            $blocks = (int) ($totalBlocks[$course->id] ?? 0);
            $recent = $recentProgress->get($course->id);

            return [
                'courseId' => $course->id,
                'title' => $course->title,
                'enrollments' => $learners,
                'completion' => $blocks > 0 ? round(($completed / $blocks) * 100, 2) : null,
                'learnersAtRisk' => max(0, $learners - (int) ($recent?->learners ?? 0)),
                'freshness' => $recent?->freshness,
            ];
        })->values()->all();
    }
}
