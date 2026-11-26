<?php

namespace App\Jobs\Admin;

use App\Data\Admin\AdminAuditFilters;
use App\Models\AdminAuditExport;
use App\Services\Admin\AdminOperationsQuery;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Throwable;

class GenerateAuditExport implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public function __construct(public int $exportId) {}

    public function handle(AdminOperationsQuery $query): void
    {
        $export = AdminAuditExport::query()->findOrFail($this->exportId);
        if ($export->status === 'completed' && $export->expires_at?->isFuture()) {
            return;
        }
        $export->update(['status' => 'processing']);
        $stored = $export->filters ?? [];
        $filters = new AdminAuditFilters(range: (string) ($stored['range'] ?? '30d'), event: (string) ($stored['event'] ?? ''), actorId: isset($stored['actor_id']) ? (int) $stored['actor_id'] : null, targetType: (string) ($stored['target_type'] ?? ''), targetId: isset($stored['target_id']) ? (int) $stored['target_id'] : null, severity: (string) ($stored['severity'] ?? ''), timezone: (string) ($stored['timezone'] ?? config('app.timezone')), perPage: (int) ($stored['per_page'] ?? 30));
        $rows = $query->exportRows($filters);
        $stream = fopen('php://temp', 'w+');
        fputcsv($stream, ['id', 'event', 'severity', 'actor', 'target_type', 'target_id', 'occurred_at', 'retention_expires_at', 'old_values', 'new_values']);
        foreach ($rows as $row) {
            fputcsv($stream, [$this->safe($row['id']), $this->safe($row['event']), $this->safe($row['severity']), $this->safe($row['actor']), $this->safe($row['targetType']), $this->safe($row['targetId']), $this->safe($row['occurredAt']), $this->safe($row['retentionExpiresAt']), $this->safe(json_encode($row['oldValues'], JSON_THROW_ON_ERROR)), $this->safe(json_encode($row['newValues'], JSON_THROW_ON_ERROR))]);
        }
        rewind($stream);
        $path = 'admin-audit-exports/'.$export->public_id.'.csv';
        Storage::disk('local')->put($path, stream_get_contents($stream));
        fclose($stream);
        $export->update(['status' => 'completed', 'disk' => 'local', 'path' => $path, 'row_count' => count($rows), 'completed_at' => now(), 'expires_at' => now()->addDay()]);
    }

    public function failed(Throwable $exception): void
    {
        AdminAuditExport::query()->whereKey($this->exportId)->update(['status' => 'failed', 'failed_at' => now(), 'error_message' => 'Export failed.']);
        Log::warning('admin.audit_export.failed', ['export_id' => $this->exportId]);
    }

    private function safe(mixed $value): string
    {
        $value = (string) ($value ?? '');

        return in_array($value[0] ?? '', ['=', '+', '-', '@'], true) ? "'".$value : $value;
    }
}
