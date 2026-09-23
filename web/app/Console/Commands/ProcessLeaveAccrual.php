<?php

namespace App\Console\Commands;

use App\Services\LeaveAccrualService;
use Illuminate\Console\Command;

class ProcessLeaveAccrual extends Command
{
    protected $signature = 'leave:accrual {--staff= : Optional staff ID to accrue for a single employee}';

    protected $description = 'Accrue monthly annual leave (1.75 days) for eligible staff — run on the 1st of each month';

    public function handle(LeaveAccrualService $service): int
    {
        $staffId = $this->option('staff') ? (int) $this->option('staff') : null;

        if ($staffId) {
            $staff = \App\Models\Staff::query()->find($staffId);
            if (! $staff) {
                $this->error("Staff #{$staffId} not found.");

                return self::FAILURE;
            }

            $this->info("Recalculating leave accrual for {$staff->fullName()}...");
            $service->recalculateForStaff($staff);
            $this->info('Done.');

            return self::SUCCESS;
        }

        $this->info('Running monthly leave accrual for all eligible staff...');
        $service->accrueMonthly();
        $this->info('Done.');

        return self::SUCCESS;
    }
}
