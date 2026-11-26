<?php

namespace App\Services\Admin;

use App\Models\AdminAlertState;
use App\Models\AiUsageLog;
use App\Models\AuditLog;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

final class AdminActionCenterQuery
{
    /**
     * @param  array{severity?: string, source?: string, status?: string}  $filters
     * @return array{alerts: list<array<string, mixed>>, counts: array<string, int>}
     */
    public function list(array $filters = []): array
    {
        $alerts = $this->alerts();
        $states = AdminAlertState::query()
            ->whereIn('fingerprint', $alerts->pluck('id')->all())
            ->get()
            ->keyBy('fingerprint');

        $alerts = $alerts->map(function (array $alert) use ($states): array {
            $state = $states->get($alert['id']);

            return [
                ...$alert,
                'status' => $state?->status ?? 'open',
                'updatedAt' => $state?->updated_at?->toIso8601String(),
            ];
        });

        $filtered = $alerts
            ->when($filters['severity'] ?? null, fn (Collection $items, string $severity): Collection => $items->where('severity', $severity))
            ->when($filters['source'] ?? null, fn (Collection $items, string $source): Collection => $items->where('source', $source))
            ->when($filters['status'] ?? null, fn (Collection $items, string $status): Collection => $items->where('status', $status))
            ->sort(function (array $left, array $right): int {
                $severity = ['critical' => 0, 'warning' => 1, 'info' => 2];
                $severityOrder = ($severity[$left['severity']] ?? 9) <=> ($severity[$right['severity']] ?? 9);
                if ($severityOrder !== 0) {
                    return $severityOrder;
                }

                $dateOrder = strcmp((string) $left['occurredAt'], (string) $right['occurredAt']);

                return $dateOrder !== 0 ? $dateOrder : strcmp($left['id'], $right['id']);
            })
            ->values();

        $counts = $alerts->countBy('status')->all();
        foreach (['open', 'acknowledged', 'resolved'] as $status) {
            $counts[$status] ??= 0;
        }

        return [
            'alerts' => $filtered->all(),
            'counts' => $counts,
        ];
    }

    public function countOpen(): int
    {
        return count($this->list(['status' => 'open'])['alerts']);
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    private function alerts(): Collection
    {
        $now = CarbonImmutable::now();
        $alerts = collect();

        DB::table('failed_jobs')
            ->where('failed_at', '>=', $now->subDays(7))
            ->orderBy('failed_at')
            ->limit(50)
            ->get(['id', 'queue', 'failed_at'])
            ->each(function (object $job) use ($alerts): void {
                $alerts->push($this->alert(
                    source: 'failed_job',
                    sourceId: (string) $job->id,
                    severity: 'critical',
                    title: 'Failed queue job',
                    summary: 'Queue '.$job->queue.' cần được kiểm tra.',
                    occurredAt: $job->failed_at,
                    action: 'Mở runbook queue recovery',
                    href: '/admin/operations?source=failed_job',
                ));
            });

        DB::table('webhook_events')
            ->where('status', 'failed')
            ->where('failed_at', '>=', $now->subDays(7))
            ->orderBy('failed_at')
            ->limit(50)
            ->get(['id', 'provider', 'event_type', 'failed_at'])
            ->each(function (object $event) use ($alerts): void {
                $alerts->push($this->alert(
                    source: 'webhook',
                    sourceId: (string) $event->id,
                    severity: 'warning',
                    title: 'Webhook delivery failed',
                    summary: $event->provider.' · '.$event->event_type,
                    occurredAt: $event->failed_at,
                    action: 'Kiểm tra và retry webhook',
                    href: '/admin/operations?source=webhook',
                ));
            });

        DB::table('exam_attempts')
            ->where('status', 'in_progress')
            ->whereNotNull('deadline_at')
            ->where('deadline_at', '<', $now)
            ->orderBy('deadline_at')
            ->limit(50)
            ->get(['id', 'public_id', 'deadline_at'])
            ->each(function (object $attempt) use ($alerts): void {
                $alerts->push($this->alert(
                    source: 'exam',
                    sourceId: (string) $attempt->id,
                    severity: 'critical',
                    title: 'Exam attempt stuck past deadline',
                    summary: 'Attempt '.$attempt->public_id.' cần policy-safe recovery.',
                    occurredAt: $attempt->deadline_at,
                    action: 'Mở exam operations',
                    href: route('admin.exams.attempts.show', $attempt->id),
                ));
            });

        AiUsageLog::query()
            ->where('status', 'failed')
            ->where('occurred_at', '>=', $now->subDays(7))
            ->latest('occurred_at')
            ->limit(50)
            ->get(['id', 'provider', 'operation', 'occurred_at'])
            ->each(function (AiUsageLog $log) use ($alerts): void {
                $alerts->push($this->alert(
                    source: 'ai_provider',
                    sourceId: (string) $log->id,
                    severity: 'warning',
                    title: 'AI provider failure',
                    summary: $log->provider.' · '.$log->operation,
                    occurredAt: $log->occurred_at,
                    action: 'Mở AI usage và kiểm tra provider',
                    href: '/admin/ai-usage?status=failed',
                ));
            });

        $this->reviewAlert($alerts, 'questions', 'Draft questions đang chờ review', DB::table('questions')->where('status', 'draft')->count());
        $this->reviewAlert($alerts, 'scenes', 'Draft scenes đang chờ review', DB::table('visual_scenes')->where('status', 'draft')->count());
        $this->reviewAlert($alerts, 'courses', 'Draft courses đang chờ review', DB::table('courses')->where('status', 'draft')->count());

        $failedLogins = AuditLog::query()
            ->where('event', 'auth.login_failed')
            ->where('occurred_at', '>=', $now->subDay())
            ->count();
        $this->reviewAlert($alerts, 'account_security', 'Có login failure cần rà soát', $failedLogins, 'critical', '/admin/operations?event=auth.login_failed');

        return $alerts;
    }

    private function reviewAlert(Collection $alerts, string $source, string $title, int $count, string $severity = 'info', string $href = '/admin/content'): void
    {
        if ($count < 1) {
            return;
        }

        $alerts->push($this->alert(
            source: $source,
            sourceId: $source,
            severity: $severity,
            title: $title,
            summary: $count.' mục đang cần xử lý.',
            occurredAt: now(),
            action: 'Mở hàng đợi liên quan',
            href: $href,
        ));
    }

    /**
     * @return array{id: string, source: string, sourceId: string, severity: string, title: string, summary: string, occurredAt: string|null, owner: string, action: string, href: string}
     */
    private function alert(string $source, string $sourceId, string $severity, string $title, string $summary, mixed $occurredAt, string $action, string $href): array
    {
        return [
            'id' => hash('sha256', $source.':'.$sourceId),
            'source' => $source,
            'sourceId' => $sourceId,
            'severity' => $severity,
            'title' => $title,
            'summary' => $summary,
            'occurredAt' => $occurredAt ? CarbonImmutable::parse($occurredAt)->toIso8601String() : null,
            'owner' => 'Admin operations',
            'action' => $action,
            'href' => $href,
        ];
    }
}
