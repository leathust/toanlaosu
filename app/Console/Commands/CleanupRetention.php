<?php

namespace App\Console\Commands;

use App\Services\Operations\RetentionCleanup;
use Illuminate\Console\Command;

class CleanupRetention extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ops:cleanup-retention {--dry-run : Report expired records without deleting them}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Delete records past their configured retention window.';

    /**
     * Execute the console command.
     */
    public function handle(RetentionCleanup $cleanup): int
    {
        $counts = $cleanup->run((bool) $this->option('dry-run'));
        $this->info(json_encode($counts, JSON_THROW_ON_ERROR));

        return self::SUCCESS;
    }
}
