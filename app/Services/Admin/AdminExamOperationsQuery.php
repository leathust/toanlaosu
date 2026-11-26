<?php

namespace App\Services\Admin;

use App\Models\Exam;
use App\Models\ExamAttempt;
use App\Models\ExamEvent;
use App\Models\ExportJob;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

final class AdminExamOperationsQuery
{
    /**
     * @param  array{status?: string|null, issue?: string|null}  $filters
     * @return array<string, mixed>
     */
    public function overview(array $filters = []): array
    {
        $now = CarbonImmutable::now();

        return [
            'serverNow' => $now->toIso8601String(),
            'examStatuses' => $this->examStatuses($now),
            'attemptStatuses' => $this->attemptStatuses(),
            'issues' => $this->issues($now),
            'attempts' => $this->attempts($filters, $now),
            'exportJobs' => $this->exportJobs(),
            'aiPolicy' => [
                'state' => 'disabled',
                'label' => 'AI disabled during exam',
                'reason' => 'Exam context and scene commands are blocked by server policy.',
            ],
        ];
    }

    /** @return array<string, mixed> */
    public function detail(int $attemptId): array
    {
        $attempt = ExamAttempt::query()
            ->with([
                'examVersion.exam:id,public_id,title,status,starts_at,ends_at',
                'user:id,name',
                'events' => fn ($query) => $query
                    ->select(['id', 'exam_attempt_id', 'event_type', 'client_occurred_at', 'occurred_at', 'metadata'])
                    ->latest('occurred_at')
                    ->limit(100),
            ])
            ->withCount('questions')
            ->findOrFail($attemptId);
        $now = CarbonImmutable::now();

        return [
            'serverNow' => $now->toIso8601String(),
            'attempt' => $this->formatAttempt($attempt, $now),
            'events' => $attempt->events->map(fn (ExamEvent $event): array => [
                'id' => $event->id,
                'type' => $event->event_type,
                'occurredAt' => $event->occurred_at?->toIso8601String(),
                'clientOccurredAt' => $event->client_occurred_at?->toIso8601String(),
                'metadata' => $this->redactMetadata($event->metadata),
            ])->values()->all(),
            'recovery' => [
                'allowed' => $attempt->status === 'in_progress' && $attempt->deadline_at !== null && $attempt->deadline_at->isPast(),
                'action' => 'expire',
                'reasonRequired' => true,
                'idempotencyRequired' => true,
            ],
            'aiPolicy' => [
                'state' => 'disabled',
                'label' => 'AI disabled during exam',
                'reason' => 'Exam context and scene commands are blocked by server policy.',
            ],
        ];
    }

    /** @return array{upcoming:int,live:int,ended:int} */
    private function examStatuses(CarbonImmutable $now): array
    {
        $counts = ['upcoming' => 0, 'live' => 0, 'ended' => 0];
        Exam::query()->where('status', 'published')->get(['starts_at', 'ends_at'])->each(function (Exam $exam) use (&$counts, $now): void {
            if ($exam->starts_at !== null && $exam->starts_at->gt($now)) {
                $counts['upcoming']++;
            } elseif ($exam->ends_at !== null && $exam->ends_at->lt($now)) {
                $counts['ended']++;
            } else {
                $counts['live']++;
            }
        });

        return $counts;
    }

    /** @return array<string, int> */
    private function attemptStatuses(): array
    {
        return ExamAttempt::query()
            ->selectRaw('status, COUNT(*) as aggregate')
            ->groupBy('status')
            ->pluck('aggregate', 'status')
            ->map(fn ($value): int => (int) $value)
            ->all();
    }

    /** @return array<string, int> */
    private function issues(CarbonImmutable $now): array
    {
        $staleAt = $now->subMinutes(10);
        $eventWindow = $now->subDay();

        return [
            'overdue' => ExamAttempt::query()->where('status', 'in_progress')->whereNotNull('deadline_at')->where('deadline_at', '<', $now)->count(),
            'stuck' => ExamAttempt::query()->where('status', 'in_progress')->where('started_at', '<', $staleAt)->whereNotExists(fn ($query) => $query->from('exam_events')->whereColumn('exam_events.exam_attempt_id', 'exam_attempts.id')->where('occurred_at', '>=', $staleAt))->count(),
            'save_conflicts' => ExamEvent::query()->whereIn('event_type', ['save_conflict', 'answer_save_conflict'])->where('occurred_at', '>=', $eventWindow)->count(),
            'reconnects' => ExamEvent::query()->whereIn('event_type', ['reconnect', 'reconnected', 'offline', 'online'])->where('occurred_at', '>=', $eventWindow)->count(),
            'submit_failures' => ExamEvent::query()->whereIn('event_type', ['submit_failed', 'submission_failed'])->where('occurred_at', '>=', $eventWindow)->count(),
        ];
    }

    /** @return array{data:list<array<string,mixed>>,meta:array<string,mixed>} */
    private function attempts(array $filters, CarbonImmutable $now): array
    {
        $query = ExamAttempt::query()
            ->select(['id', 'public_id', 'exam_version_id', 'user_id', 'attempt_number', 'status', 'started_at', 'deadline_at', 'submitted_at', 'save_version', 'updated_at'])
            ->selectSub(ExamEvent::query()->select('occurred_at')->whereColumn('exam_attempt_id', 'exam_attempts.id')->latest('occurred_at')->limit(1), 'last_event_at')
            ->with(['examVersion.exam:id,public_id,title', 'user:id,name'])
            ->when($filters['status'] ?? null, fn (Builder $query, string $status): Builder => $query->where('status', $status))
            ->when(($filters['issue'] ?? null) === 'overdue', fn (Builder $query) => $query->where('status', 'in_progress')->whereNotNull('deadline_at')->where('deadline_at', '<', $now))
            ->when(($filters['issue'] ?? null) === 'stuck', fn (Builder $query) => $query->where('status', 'in_progress')->where('started_at', '<', $now->subMinutes(10))->whereNotExists(fn ($subQuery) => $subQuery->from('exam_events')->whereColumn('exam_events.exam_attempt_id', 'exam_attempts.id')->where('occurred_at', '>=', $now->subMinutes(10))))
            ->latest('updated_at')
            ->latest('id');

        return $this->formatPaginator($query->paginate(20)->withQueryString(), $now);
    }

    /** @return array{data:list<array<string,mixed>>,meta:array<string,mixed>} */
    private function formatPaginator(LengthAwarePaginator $paginator, CarbonImmutable $now): array
    {
        return [
            'data' => $paginator->getCollection()->map(fn (ExamAttempt $attempt): array => $this->formatAttempt($attempt, $now))->values()->all(),
            'meta' => [
                'currentPage' => $paginator->currentPage(),
                'lastPage' => $paginator->lastPage(),
                'perPage' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
        ];
    }

    /** @return array<string,mixed> */
    private function formatAttempt(ExamAttempt $attempt, CarbonImmutable $now): array
    {
        $lastEventAt = $attempt->getAttribute('last_event_at');
        $isOverdue = $attempt->status === 'in_progress' && $attempt->deadline_at?->isPast();
        $isStuck = $attempt->status === 'in_progress' && $attempt->started_at?->lt($now->subMinutes(10)) && ($lastEventAt === null || CarbonImmutable::parse($lastEventAt)->lt($now->subMinutes(10)));

        return [
            'id' => $attempt->id,
            'publicId' => $attempt->public_id,
            'status' => $attempt->status,
            'issue' => $isOverdue ? 'overdue' : ($isStuck ? 'stuck' : null),
            'exam' => ['id' => $attempt->examVersion?->exam?->id, 'title' => $attempt->examVersion?->exam?->title ?? 'Unknown exam'],
            'examStatus' => $attempt->examVersion?->exam?->status,
            'examWindow' => ['startsAt' => $attempt->examVersion?->exam?->starts_at?->toIso8601String(), 'endsAt' => $attempt->examVersion?->exam?->ends_at?->toIso8601String()],
            'learner' => ['id' => $attempt->user?->id, 'name' => $attempt->user?->name ?? 'Unknown learner'],
            'questionsCount' => (int) ($attempt->questions_count ?? 0),
            'attemptNumber' => $attempt->attempt_number,
            'startedAt' => $attempt->started_at?->toIso8601String(),
            'deadlineAt' => $attempt->deadline_at?->toIso8601String(),
            'submittedAt' => $attempt->submitted_at?->toIso8601String(),
            'saveVersion' => $attempt->save_version,
            'lastEventAt' => $lastEventAt ? CarbonImmutable::parse($lastEventAt)->toIso8601String() : null,
            'href' => route('admin.exams.attempts.show', $attempt->id),
        ];
    }

    /** @return list<array<string,mixed>> */
    private function exportJobs(): array
    {
        return ExportJob::query()
            ->with('examVersion.exam:id,title')
            ->latest('requested_at')
            ->limit(20)
            ->get(['id', 'public_id', 'exam_version_id', 'mode', 'status', 'requested_at', 'completed_at', 'failed_at', 'expires_at'])
            ->map(fn (ExportJob $job): array => [
                'id' => $job->id,
                'publicId' => $job->public_id,
                'examTitle' => $job->examVersion?->exam?->title ?? 'Unknown exam',
                'mode' => $job->mode,
                'status' => $job->status,
                'requestedAt' => $job->requested_at?->toIso8601String(),
                'completedAt' => $job->completed_at?->toIso8601String(),
                'failedAt' => $job->failed_at?->toIso8601String(),
                'expiresAt' => $job->expires_at?->toIso8601String(),
                'failureHref' => $job->status === 'failed' ? route('admin.action-center', ['source' => 'failed_job']) : null,
            ])->values()->all();
    }

    /** @return array<string, scalar|null> */
    private function redactMetadata(?array $metadata): array
    {
        return collect($metadata ?? [])->only(['question_id', 'save_version', 'score', 'reason', 'result'])->all();
    }
}
