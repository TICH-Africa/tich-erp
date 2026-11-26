<?php

namespace App\Console\Commands;

use App\Services\ContractService;
use Illuminate\Console\Command;

class ReconcileActiveStaffContracts extends Command
{
    protected $signature = 'hr:reconcile-active-contracts {--dry-run : List duplicates without updating}';

    protected $description = 'Mark older duplicate Active (pending) contracts as Renewed when an employee has more than one';

    public function handle(ContractService $contracts): int
    {
        if ($this->option('dry-run')) {
            $rows = $contracts->duplicateActiveContractRows();
            if ($rows === []) {
                $this->info('No duplicate active contracts found.');

                return self::SUCCESS;
            }
            foreach ($rows as $row) {
                $this->line(sprintf(
                    'Staff #%d: keep %s (#%d), supersede %s (#%d)',
                    $row['staff_id'],
                    $row['keep_number'],
                    $row['keep_id'],
                    $row['supersede_number'],
                    $row['supersede_id'],
                ));
            }

            return self::SUCCESS;
        }

        $fixed = $contracts->reconcileDuplicateActiveContracts((int) (auth()->id() ?? 0));
        $this->info("Superseded {$fixed} duplicate contract(s).");

        return self::SUCCESS;
    }
}