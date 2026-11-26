<?php

namespace App\Jobs;

use App\Services\Operations\RetentionCleanup;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class RunRetentionCleanup implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public int $tries = 3;

    public function __construct(public bool $dryRun = false) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        app(RetentionCleanup::class)->run($this->dryRun);
    }
}
