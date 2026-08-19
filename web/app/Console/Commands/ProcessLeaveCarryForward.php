<?php

namespace App\Console\Commands;

use App\Services\LeaveAccrualService;
use Illuminate\Console\Command;

class ProcessLeaveCarryForward extends Command
{
    protected $signature = 'leave:carry-forward {--year= : The new year to carry forward into (defaults to current year)}';

    protected $description = 'Process approved leave carry-forward requests, rolling unused days into the new year balance';

    public function handle(LeaveAccrualService $service): int
    {
        $year = $this->option('year') ? (int) $this->option('year') : now()->year;

        $this->info("Processing carry-forward rollover into year {$year}...");

        $processed = $service->processYearEndCarryForward($year);

        $this->info("Done. {$processed} carry-forward balance(s) applied.");

        return self::SUCCESS;
    }
}
