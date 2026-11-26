<?php

namespace App\Services\Admin;

use App\Data\Admin\AdminAuditFilters;
use App\Models\AuditLog;
use App\Models\SystemSetting;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class AdminOperationsQuery
{
    public function audits(AdminAuditFilters $filters): LengthAwarePaginator
    {
        $query = AuditLog::query()->with('actor:id,name')->whereBetween('occurred_at', [$filters->start(), $filters->end()])->when($filters->event !== '', fn (EloquentBuilder $builder) => $builder->where('event', 'like', '%'.$filters->event.'%'))->when($filters->actorId, fn (EloquentBuilder $builder, int $actorId) => $builder->where('actor_id', $actorId))->when($filters->targetType !== '', fn (EloquentBuilder $builder) => $builder->where('auditable_type', $filters->targetType))->when($filters->targetId, fn (EloquentBuilder $builder, int $targetId) => $builder->where('auditable_id', $targetId));
        $this->applySeverity($query, $filters->severity);

        return $query->orderByDesc('occurred_at')->orderByDesc('id')->paginate($filters->perPage)->withQueryString();
    }

    /** @return array<string, mixed> */
    public function auditPayload(AuditLog $audit, bool $includeValues = false): array
    {
        return ['id' => $audit->id, 'event' => $audit->event, 'severity' => $this->severity($audit->event), 'actor' => $audit->actor?->name ?? 'System', 'actorId' => $audit->actor_id, 'targetType' => $audit->auditable_type ? class_basename($audit->auditable_type) : null, 'targetId' => $audit->auditable_id, 'occurredAt' => $audit->occurred_at?->toIso8601String(), 'retentionExpiresAt' => $audit->retention_expires_at?->toIso8601String(), 'oldValues' => $includeValues ? $this->redact($audit->old_values ?? []) : null, 'newValues' => $includeValues ? $this->redact($audit->new_values ?? []) : null, 'detailHref' => route('admin.operations.audit.show', $audit)];
    }

    /** @return array<string, mixed> */
    public function health(): array
    {
        return Cache::remember('admin-operations:health:v1', now()->addSeconds(15), function (): array {
            $now = CarbonImmutable::now();
            $lastHealthCheck = Cache::get('ops:health:last_check');
            $lastRetention = Cache::get('ops:retention:last_run');
            $providerFailures = (int) Cache::get('ai:circuit:failures', 0);
            $providerOpen = (bool) Cache::get('ai:circuit:open', false);
            $healthStatus = $lastHealthCheck && CarbonImmutable::parse($lastHealthCheck)->greaterThan($now->subHours(2)) ? 'healthy' : 'stale';
            $retentionStatus = $lastRetention && CarbonImmutable::parse($lastRetention)->greaterThan($now->subHours(26)) ? 'healthy' : 'stale';
            $webhookFailures = DB::table('webhook_events')->where('status', 'failed')->where('failed_at', '>=', $now->subDays(7))->count();
            $settings = SystemSetting::query()->whereIn('key', ['audit_retention_months', 'exam_retention_years'])->pluck('value', 'key');

            return ['generatedAt' => now()->toIso8601String(), 'status' => $healthStatus === 'healthy' && $retentionStatus === 'healthy' && ! $providerOpen ? 'healthy' : 'attention', 'queue' => ['depth' => DB::table('jobs')->count(), 'failed' => DB::table('failed_jobs')->count(), 'href' => route('admin.action-center', ['source' => 'failed_job'])], 'webhooks' => ['failedLast7d' => $webhookFailures, 'href' => route('admin.action-center', ['source' => 'webhook'])], 'scheduler' => ['lastHealthCheck' => $lastHealthCheck, 'status' => $healthStatus, 'command' => 'ops:health-check', 'runbookHref' => '/docs/task-logs/4-operations-recovery-runbook.md'], 'retention' => ['lastRun' => $lastRetention, 'status' => $retentionStatus, 'command' => 'ops:cleanup-retention --dry-run', 'runbookHref' => '/docs/task-logs/4-operations-recovery-runbook.md', 'expired' => $this->expiredCounts()], 'retentionPolicy' => ['auditMonths' => (int) ($settings['audit_retention_months'] ?? 6), 'examYears' => (int) ($settings['exam_retention_years'] ?? 1), 'earlyDeletion' => 'CSKH runbook only'], 'provider' => ['provider' => (string) config('services.ai.provider', 'local'), 'status' => $providerOpen ? 'open' : ($providerFailures > 0 ? 'degraded' : 'healthy'), 'failures' => $providerFailures, 'href' => route('admin.ai')], 'links' => ['actionCenter' => route('admin.action-center'), 'audit' => route('admin.operations'), 'runbook' => '/docs/task-logs/4-operations-recovery-runbook.md']];
        });
    }

    /** @return array<string, int> */
    private function expiredCounts(): array
    {
        return ['audit' => AuditLog::query()->where('retention_expires_at', '<', now())->count(), 'examAttempts' => DB::table('exam_attempts')->where('retention_expires_at', '<', now())->count(), 'examAnswers' => DB::table('exam_attempt_answers')->whereIn('exam_attempt_question_id', DB::table('exam_attempt_questions')->whereIn('exam_attempt_id', DB::table('exam_attempts')->where('retention_expires_at', '<', now())->select('id'))->select('id'))->count()];
    }

    /** @return list<array{value:string, label:string}> */
    public function filterOptions(): array
    {
        $actors = DB::table('audit_logs')->join('users', 'users.id', '=', 'audit_logs.actor_id')->distinct()->orderBy('users.name')->get(['users.id', 'users.name'])->map(fn (object $row): array => ['value' => (string) $row->id, 'label' => $row->name])->values()->all();
        $targets = AuditLog::query()->whereNotNull('auditable_type')->distinct()->orderBy('auditable_type')->pluck('auditable_type')->map(fn (string $type): array => ['value' => $type, 'label' => class_basename($type)])->values()->all();

        return ['actors' => $actors, 'targetTypes' => $targets, 'severities' => [['value' => 'critical', 'label' => 'Critical'], ['value' => 'warning', 'label' => 'Warning'], ['value' => 'info', 'label' => 'Info']]];
    }

    /** @return list<array<string, mixed>> */
    public function exportRows(AdminAuditFilters $filters): array
    {
        $query = AuditLog::query()->with('actor:id,name')->whereBetween('occurred_at', [$filters->start(), $filters->end()])->when($filters->event !== '', fn (EloquentBuilder $builder) => $builder->where('event', 'like', '%'.$filters->event.'%'))->when($filters->actorId, fn (EloquentBuilder $builder, int $actorId) => $builder->where('actor_id', $actorId))->when($filters->targetType !== '', fn (EloquentBuilder $builder) => $builder->where('auditable_type', $filters->targetType))->when($filters->targetId, fn (EloquentBuilder $builder, int $targetId) => $builder->where('auditable_id', $targetId));
        $this->applySeverity($query, $filters->severity);

        return $query->orderByDesc('occurred_at')->orderByDesc('id')->limit(10000)->get()->map(fn (AuditLog $audit): array => $this->auditPayload($audit, true))->all();
    }

    private function applySeverity(EloquentBuilder $query, string $severity): void
    {
        if ($severity === '') {
            return;
        }
        $query->where(function (EloquentBuilder $builder) use ($severity): void {
            $critical = ['auth.login_failed', 'account.session_revoked', 'account.sessions_revoked'];
            $warning = ['%failed%', '%review%', '%setting%', 'admin.alert_%'];
            if ($severity === 'critical') {
                $builder->whereIn('event', $critical);
            } elseif ($severity === 'warning') {
                foreach ($warning as $pattern) {
                    $builder->orWhere('event', 'like', $pattern);
                }
                $builder->whereNotIn('event', $critical);
            } else {
                $builder->whereNotIn('event', $critical);
                foreach ($warning as $pattern) {
                    $builder->where('event', 'not like', $pattern);
                }
            }
        });
    }

    private function severity(string $event): string
    {
        if (in_array($event, ['auth.login_failed', 'account.session_revoked', 'account.sessions_revoked'], true)) {
            return 'critical';
        }
        foreach (['%failed%', '%review%', '%setting%', 'admin.alert_%'] as $pattern) {
            if (Str::is(str_replace('%', '*', $pattern), $event)) {
                return 'warning';
            }
        }

        return 'info';
    }

    private function redact(mixed $value, string $key = ''): mixed
    {
        if (preg_match('/secret|token|password|session|credential|api[_-]?key/i', $key)) {
            return '[REDACTED]';
        }
        if (is_array($value)) {
            $result = [];
            foreach ($value as $childKey => $childValue) {
                $result[(string) $childKey] = $this->redact($childValue, (string) $childKey);
            }

            return $result;
        }
        if (is_string($value)) {
            return Str::limit($value, 500);
        }

        return $value;
    }
}
