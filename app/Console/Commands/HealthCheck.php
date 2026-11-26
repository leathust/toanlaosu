<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class HealthCheck extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ops:health-check';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check database, cache and queue health.';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        try {
            DB::select('select 1');
            Cache::put('ops:health:last_check', now()->toIso8601String(), 60);
            $payload = ['status' => 'ok', 'queue_jobs' => DB::table('jobs')->count(), 'failed_jobs' => DB::table('failed_jobs')->count()];
            $this->line(json_encode($payload, JSON_THROW_ON_ERROR));

            return self::SUCCESS;
        } catch (\Throwable $exception) {
            $this->error(json_encode(['status' => 'failed', 'error' => $exception->getMessage()], JSON_THROW_ON_ERROR));

            return self::FAILURE;
        }
    }
}
