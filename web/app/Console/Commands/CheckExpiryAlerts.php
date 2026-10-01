<?php

namespace App\Console\Commands;

use App\Models\Staff;
use App\Models\StaffContract;
use App\Models\StaffProfessionalLicense;
use App\Services\PlatformNotificationService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

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
        // One month and two weeks before end date — notify the employee and HR once per threshold.
        $thresholds = [30, 14];
        $hrUserIds = $this->hrNotifierUserIds();

        foreach ($thresholds as $days) {
            $targetDate = now()->startOfDay()->addDays($days)->toDateString();

            $expiringContracts = StaffContract::query()
                ->with(['staff', 'staff.user'])
                ->whereNotNull('end_date')
                ->whereDate('end_date', $targetDate)
                ->where(function ($query) {
                    $query->whereNull('renewal_status')
                        ->orWhereNotIn('renewal_status', ['renewed', 'terminated', 'expired']);
                })
                ->get();

            foreach ($expiringContracts as $contract) {
                $staff = $contract->staff;
                if (! $staff) {
                    continue;
                }

                $label = $days === 30 ? '1 month' : "{$days} days";
                $titleEmployee = "Contract expires in {$label}";
                $titleHr = "Staff contract expires in {$label}";
                $endDate = $contract->end_date->format('d M Y');
                $contractRef = $contract->contract_number ?: '#'.$contract->id;

                $employeeBody = sprintf(
                    'Your employment contract %s ends on %s (%s remaining). Please contact HR if you have questions about renewal or next steps.',
                    $contractRef,
                    $endDate,
                    $label
                );

                $hrBody = sprintf(
                    'Contract %s for %s (%s) ends on %s (%s remaining). Review renewal or offboarding in the HR contracts module.',
                    $contractRef,
                    $staff->fullName(),
                    $staff->employee_number ?: 'no employee number',
                    $endDate,
                    $label
                );

                $hrUrl = route('hr.contracts.show', $contract);
                $employeeUrl = route('employee.dashboard');

                if ($staff->user_id && ! $this->alreadyNotified((int) $staff->user_id, 'staff_contract', (string) $contract->id, $titleEmployee)) {
                    $notifications->notifyUser(
                        (int) $staff->user_id,
                        $titleEmployee,
                        $employeeBody,
                        'staff_contract',
                        (string) $contract->id,
                        'high',
                        $employeeUrl
                    );
                    $this->line("Employee contract alert ({$label}) → {$staff->fullName()}");
                }

                $hrRecipients = array_values(array_filter(
                    $hrUserIds,
                    fn (int $userId) => $userId !== (int) $staff->user_id
                ));

                foreach ($hrRecipients as $hrUserId) {
                    if ($this->alreadyNotified($hrUserId, 'staff_contract', (string) $contract->id, $titleHr)) {
                        continue;
                    }

                    $notifications->notifyUser(
                        $hrUserId,
                        $titleHr,
                        $hrBody,
                        'staff_contract',
                        (string) $contract->id,
                        'high',
                        $hrUrl
                    );
                }

                if ($hrRecipients !== []) {
                    $this->line('HR contract alert ('.$label.') → '.$staff->fullName().' ('.count($hrRecipients).' recipient(s))');
                }
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

    /**
     * HR contract alerts go to HR roles and users in the HR module department only —
     * not Super Admin / CEO / other executives.
     *
     * @return list<int>
     */
    private function hrNotifierUserIds(): array
    {
        $ids = [];

        if (Schema::hasTable('user_roles') && Schema::hasTable('roles')) {
            $roleIds = DB::table('user_roles as ur')
                ->join('roles as r', 'r.id', '=', 'ur.role_id')
                ->whereIn('r.role_name', ['HR Manager', 'Assistant HR Manager'])
                ->where(function ($query) {
                    $query->whereNull('ur.expires_at')
                        ->orWhere('ur.expires_at', '>', now());
                })
                ->distinct()
                ->pluck('ur.user_id')
                ->map(fn ($id) => (int) $id)
                ->all();

            $ids = array_merge($ids, $roleIds);
        }

        if (Schema::hasTable('staff') && Schema::hasTable('departments')) {
            $hrDepartmentIds = DB::table('departments')
                ->where(function ($query) {
                    $query->where('dept_code', 'HR')
                        ->orWhere('dept_name', 'like', 'Human Resource%');
                })
                ->pluck('id')
                ->map(fn ($id) => (int) $id)
                ->all();

            if (Schema::hasTable('department_modules')) {
                $moduleDeptIds = DB::table('department_modules')
                    ->where('module_key', 'hr')
                    ->pluck('department_id')
                    ->map(fn ($id) => (int) $id)
                    ->all();

                $hrDepartmentIds = array_values(array_unique(array_merge($hrDepartmentIds, $moduleDeptIds)));
            }

            if ($hrDepartmentIds !== []) {
                $deptUserIds = DB::table('staff')
                    ->whereIn('department_id', $hrDepartmentIds)
                    ->whereNotNull('user_id')
                    ->distinct()
                    ->pluck('user_id')
                    ->map(fn ($id) => (int) $id)
                    ->all();

                $ids = array_merge($ids, $deptUserIds);
            }
        }

        return array_values(array_unique(array_filter($ids)));
    }

    private function alreadyNotified(int $userId, string $entityType, string $entityId, string $title): bool
    {
        if (! Schema::hasTable('notifications')) {
            return false;
        }

        return DB::table('notifications')
            ->where('user_id', $userId)
            ->where('related_entity_type', $entityType)
            ->where('related_entity_id', $entityId)
            ->where('title', $title)
            ->exists();
    }
}
