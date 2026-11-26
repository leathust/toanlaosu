<?php

namespace App\Jobs;

use App\Models\ExportJob;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class ProcessExportJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public function __construct(public int $exportJobId) {}

    public function handle(): void
    {
        $job = ExportJob::query()->findOrFail($this->exportJobId);
        if ($job->status === 'completed') {
            return;
        }
        $job->update(['status' => 'processing']);
        Log::info('export.processed', ['export_job_id' => $job->id, 'idempotency_key' => $job->idempotency_key]);
        $job->update(['status' => 'completed', 'completed_at' => now(), 'expires_at' => now()->addDay()]);
    }
}
