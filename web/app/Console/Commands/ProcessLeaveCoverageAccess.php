<?php

namespace App\Console\Commands;

use App\Services\Leave\LeaveCoverageService;
use Illuminate\Console\Command;

class ProcessLeaveCoverageAccess extends Command
{
    protected $signature = 'leave:coverage-access {--date= : Process as of this date (Y-m-d), default today}';

    protected $description = 'Grant or revoke leave stand-in department access based on approved leave dates';

    public function handle(LeaveCoverageService $coverages): int
    {
        $dateOption = $this->option('date');
        $onDate = $dateOption
            ? \Carbon\Carbon::parse((string) $dateOption)->startOfDay()
            : now()->startOfDay();

        $this->info('Syncing leave stand-in access for '.$onDate->toDateString().'...');

        $result = $coverages->syncScheduledCoverageAccess($onDate);

        $this->info("Granted: {$result['granted']}, revoked: {$result['revoked']}.");

        return self::SUCCESS;
    }
}
