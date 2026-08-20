<?php

namespace App\Services;

use App\Models\ProfessionalDevelopment;
use App\Models\Staff;
use App\Models\StaffProfileChangeRequest;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class EmployeePortalService
{
    public function __construct(
        protected StaffPortalService $staffPortal,
    ) {}

    public function hasEmployeeProfile(User $user): bool
    {
        return $this->staffPortal->staffForUser($user) !== null;
    }

    public function staffForUser(User $user): ?Staff
    {
        return $this->staffPortal->staffForUser($user);
    }

    /**
     * Staff/admin accounts are employees. If an invited user has no staff row yet, create a
     * provisional one so My Employee Portal (and profile completion) always works.
     */
    public function ensureStaffProfile(User $user): ?Staff
    {
        if (! in_array($user->user_type, ['staff', 'admin'], true)) {
            return $this->staffForUser($user);
        }

        $existing = $this->staffForUser($user);
        if ($existing) {
            return $existing;
        }

        $departmentId = \App\Models\Department::query()
            ->where('is_active', true)
            ->whereNull('parent_dept_id')
            ->where('dept_code', 'HR')
            ->value('id')
            ?? \App\Models\Department::query()
                ->where('is_active', true)
                ->whereNull('parent_dept_id')
                ->orderBy('id')
                ->value('id');

        if (! $departmentId) {
            return null;
        }

        $email = (string) $user->email;
        $local = strstr($email, '@', true) ?: 'employee';
        $local = preg_replace('/[^a-zA-Z]+/', ' ', $local) ?: 'Employee';
        $parts = preg_split('/\s+/', trim((string) $local)) ?: ['Employee'];
        $firstName = ucfirst(strtolower($parts[0] ?? 'Employee'));
        $surname = ucfirst(strtolower($parts[1] ?? 'Invitee'));

        $lifecycle = app(StaffLifecycleService::class);

        $staff = Staff::query()->create([
            'employee_number' => $lifecycle->generateEmployeeNumber(),
            'first_name' => $firstName,
            'surname' => $surname,
            'date_of_birth' => '1990-01-01',
            'gender' => 'Other',
            'primary_email' => strtolower($email),
            'organisation_email' => Staff::organisationEmailFromName($firstName, $surname),
            'phone_number' => '0700000000',
            'department_id' => $departmentId,
            'job_title' => 'Pending assignment',
            'employment_category' => 'contract',
            'payroll_scheme' => 'employee',
            'employment_start_date' => now()->toDateString(),
            'employment_status' => 'onboarding',
            'is_profile_locked' => false,
            'gross_monthly_salary' => 0,
            'user_id' => $user->id,
        ]);

        if (! $user->staff_id) {
            $user->forceFill(['staff_id' => $staff->id])->save();
        }

        return $staff;
    }

    /**
     * @return array<string, mixed>
     */
    public function dashboardData(Staff $staff): array
    {
        $staff->load([
            'department',
            'campus',
            'lineManager',
            'bankAccount',
            'pensionScheme',
            'nextOfKin',
            'activeAllowances',
            'contracts' => fn ($query) => $query->orderByDesc('start_date'),
            'documents' => fn ($query) => $query->orderByDesc('created_at'),
            'qualifications' => fn ($query) => $query->orderByDesc('year_completed'),
            'professionalLicenses' => fn ($query) => $query->orderByDesc('expiry_date'),
            'performanceReviews' => fn ($query) => $query->orderByDesc('review_date')->limit(3),
        ]);

        $currentContract = $staff->contracts->first(fn ($contract) => ! $contract->isExpired());

        return [
            'staff' => $staff,
            'currentContract' => $currentContract,
            'contractSummary' => $this->contractSummary($staff, $currentContract),
            'compensation' => $this->compensationSummary($staff),
            'leaveBalances' => $this->leaveBalances($staff),
            'recentLeaveRequests' => $this->recentLeaveRequests($staff),
            'employmentDuration' => $this->employmentDurationLabel($staff),
            'trainings' => $this->staffTrainings($staff),
            'pendingProfileChanges' => StaffProfileChangeRequest::query()
                ->where('staff_id', $staff->id)
                ->where('status', StaffProfileChangeRequest::STATUS_PENDING)
                ->count(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function contractSummary(Staff $staff, ?\App\Models\StaffContract $currentContract): array
    {
        $endDate = $currentContract?->end_date ?? $staff->contract_end_date;
        $startDate = $currentContract?->start_date ?? $staff->employment_start_date;

        $daysRemaining = null;
        if ($endDate) {
            $daysRemaining = now()->startOfDay()->diffInDays($endDate, false);
        }

        $status = 'active';
        if ($endDate && $daysRemaining !== null && $daysRemaining < 0) {
            $status = 'expired';
        } elseif ($endDate && $daysRemaining !== null && $daysRemaining <= 30) {
            $status = 'expiring_soon';
        } elseif (! $endDate) {
            $status = 'open_ended';
        }

        return [
            'number' => $currentContract?->contract_number,
            'type' => $currentContract?->contract_type,
            'job_title' => $currentContract?->job_title ?? $staff->job_title,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'days_remaining' => $daysRemaining,
            'status' => $status,
            'is_signed' => (bool) ($currentContract?->is_signed ?? false),
            'signed_date' => $currentContract?->signed_date,
            'probation_end_date' => $staff->probation_end_date ?? $currentContract?->probation_end_date,
            'is_on_probation' => $staff->isOnProbation(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function compensationSummary(Staff $staff): array
    {
        $allowances = $staff->activeAllowances;

        return [
            'gross_monthly_salary' => (float) $staff->gross_monthly_salary,
            'allowances_total' => (float) $allowances->sum('amount'),
            'total_monthly' => (float) $staff->total_monthly_compensation,
            'salary_scale' => $staff->salary_scale,
            'payroll_scheme' => $staff->payrollSchemeLabel(),
            'employment_category' => config(
                'tich-payroll.employment_categories.'.$staff->employment_category,
                ucfirst(str_replace('_', ' ', (string) $staff->employment_category))
            ),
            'allowances' => $allowances,
            'masked_account_number' => $this->maskAccountNumber($staff->bankAccount?->account_number),
        ];
    }

    /**
     * @return Collection<int, object>
     */
    public function leaveBalancesFor(Staff $staff): Collection
    {
        return $this->leaveBalances($staff);
    }

    /**
     * @return Collection<int, object>
     */
    private function leaveBalances(Staff $staff): Collection
    {
        if (! Schema::hasTable('leave_balances') || ! Schema::hasTable('leave_types')) {
            return collect();
        }

        return DB::table('leave_balances as lb')
            ->join('leave_types as lt', 'lt.id', '=', 'lb.leave_type_id')
            ->where('lb.staff_id', $staff->id)
            ->where('lb.year', now()->year)
            ->orderBy('lt.leave_name')
            ->select([
                'lt.leave_name as leave_type_name',
                'lb.entitled_days',
                'lb.days_taken',
                'lb.days_pending',
                'lb.balance_days',
                'lt.accrual_type',
                'lt.accrual_rate',
                'lt.calculation_type',
            ])
            ->get();
    }

    /**
     * @return Collection<int, object>
     */
    private function recentLeaveRequests(Staff $staff): Collection
    {
        if (! Schema::hasTable('leave_requests') || ! Schema::hasTable('leave_types')) {
            return collect();
        }

        return DB::table('leave_requests as lr')
            ->join('leave_types as lt', 'lt.id', '=', 'lr.leave_type_id')
            ->where('lr.staff_id', $staff->id)
            ->orderByDesc('lr.created_at')
            ->limit(5)
            ->select([
                'lt.leave_name as leave_type_name',
                'lr.start_date',
                'lr.end_date',
                'lr.days_requested',
                'lr.overall_status',
            ])
            ->get();
    }

    private function employmentDurationLabel(Staff $staff): ?string
    {
        if (! $staff->employment_start_date) {
            return null;
        }

        return $staff->employment_start_date->diffForHumans(now(), true);
    }

    /**
     * @return Collection<int, object>
     */
    private function staffTrainings(Staff $staff): Collection
    {
        if (! Schema::hasTable('professional_development')) {
            return collect();
        }

        return ProfessionalDevelopment::where(function ($query) use ($staff) {
            $query->whereNull('staff_id')
                ->whereNull('staff_ids');
        })->orWhere(function ($query) use ($staff) {
            $query->where('staff_id', $staff->id);
        })->orWhere(function ($query) use ($staff) {
            $query->whereJsonContains('staff_ids', $staff->id);
        })->where('is_completed', false)
            ->orderByDesc('start_date')
            ->limit(5)
            ->get();
    }

    public function maskAccountNumber(?string $accountNumber): ?string
    {
        if (! $accountNumber) {
            return null;
        }

        $digits = preg_replace('/\D/', '', $accountNumber) ?: $accountNumber;
        if (strlen($digits) <= 4) {
            return str_repeat('*', max(0, strlen($digits) - 1)).substr($digits, -1);
        }

        return str_repeat('*', strlen($digits) - 4).substr($digits, -4);
    }
}
