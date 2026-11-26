<?php

namespace App\Actions\Operations;

use App\Jobs\ProcessExportJob;
use App\Models\ExportJob;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class QueueExport
{
    public function execute(User $actor, int $examVersionId, int $templateVersionId, string $idempotencyKey): ExportJob
    {
        $job = DB::transaction(fn (): ExportJob => ExportJob::query()->firstOrCreate(['idempotency_key' => $idempotencyKey], [
            'public_id' => (string) Str::ulid(), 'requested_by' => $actor->getKey(), 'exam_version_id' => $examVersionId,
            'latex_template_version_id' => $templateVersionId, 'mode' => 'latex', 'status' => 'queued', 'requested_at' => now(),
        ]));
        if ($job->wasRecentlyCreated) {
            ProcessExportJob::dispatch($job->id);
        }

        return $job;
    }
}
