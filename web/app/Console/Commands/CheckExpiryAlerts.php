<?php

namespace App\Console\Commands;

use App\Models\Staff;
use App\Models\StaffContract;
use App\Models\StaffProfessionalLicense;
use App\Services\PlatformNotificationService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CheckExpiryAlerts extends Command
{
    protected $signature = 'app:check-expiry-alerts';
    protected $description = 'Check for expiring contracts, probation, and licenses and send alerts';

    public function handle(PlatformNotificationService $notifications): void
    {
        $this->info('Checking expiry alerts...');

        $this->checkContractExpiry($notifications);
        $this->checkProbationExpiry($notifications);
        $this->checkLicenseExpiry($notifications);

        $this->info('Expiry alerts check completed.');
    }

    private function checkContractExpiry(PlatformNotificationService $notifications): void
    {
        $thresholds = [30, 15, 7];

        foreach ($thresholds as $days) {
            $expiringContracts = StaffContract::query()
                ->with(['staff', 'staff.user'])
                ->whereNotNull('end_date')
                ->where('end_date', '<=', now()->addDays($days))
                ->where('end_date', '>=', now())
                ->where('renewal_status', '!=', 'renewed')
                ->get();

            foreach ($expiringContracts as $contract) {
                $staff = $contract->staff;
                if (! $staff || ! $staff->user_id) {
                    continue;
                }

                $userId = $staff->user_id;
                $lineManagerId = $staff->line_manager_id ? Staff::where('id', $staff->line_manager_id)->value('user_id') : null;

                $message = "Contract for {$staff->fullName()} ({$contract->contract_number}) expires on {$contract->end_date->format('Y-m-d')} ({$days} days remaining).";

                $notifications->notifyUser($userId, 'Contract Expiry Alert', $message, 'staff_contract', $contract->id, 'high');

                if ($lineManagerId) {
                    $notifications->notifyUser($lineManagerId, 'Contract Expiry Alert - Team Member', $message, 'staff_contract', $contract->id, 'high');
                }

                $this->line("Contract expiry alert sent for {$staff->fullName()} ({$days} days)");
            }
        }
    }

    private function checkProbationExpiry(PlatformNotificationService $notifications): void
    {
        $thresholds = [30, 15, 7];

        foreach ($thresholds as $days) {
            $staffOnProbation = Staff::query()
                ->with('user')
                ->where('is_on_probation', 1)
                ->whereNotNull('probation_end_date')
                ->where('probation_end_date', '<=', now()->addDays($days))
                ->where('probation_end_date', '>=', now())
                ->get();

            foreach ($staffOnProbation as $staff) {
                if (! $staff->user_id) {
                    continue;
                }

                $userId = $staff->user_id;
                $lineManagerId = $staff->line_manager_id ? Staff::where('id', $staff->line_manager_id)->value('user_id') : null;

                $message = "Probation period for {$staff->fullName()} ends on {$staff->probation_end_date->format('Y-m-d')} ({$days} days remaining). Please schedule a review.";

                $notifications->notifyUser($userId, 'Probation Expiry Alert', $message, 'staff', $staff->id, 'high');

                if ($lineManagerId) {
                    $notifications->notifyUser($lineManagerId, 'Probation Expiry Alert - Team Member', $message, 'staff', $staff->id, 'high');
                }

                $this->line("Probation expiry alert sent for {$staff->fullName()} ({$days} days)");
            }
        }
    }

    private function checkLicenseExpiry(PlatformNotificationService $notifications): void
    {
        $thresholds = [30, 15, 7];

        foreach ($thresholds as $days) {
            $expiringLicenses = StaffProfessionalLicense::query()
                ->with(['staff', 'staff.user'])
                ->whereNotNull('expiry_date')
                ->where('expiry_date', '<=', now()->addDays($days))
                ->where('expiry_date', '>=', now())
                ->get();

            foreach ($expiringLicenses as $license) {
                $staff = $license->staff;
                if (! $staff || ! $staff->user_id) {
                    continue;
                }

                $message = "Professional license '{$license->license_name}' for {$staff->fullName()} expires on {$license->expiry_date->format('Y-m-d')} ({$days} days remaining).";

                $notifications->notifyUser($staff->user_id, 'License Expiry Alert', $message, 'staff_professional_license', $license->id, 'high');

                $this->line("License expiry alert sent for {$staff->fullName()} - {$license->license_name} ({$days} days)");
            }
        }
    }
}
