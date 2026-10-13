<?php

namespace App\Http\Controllers\Api\Employee\mobile;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Api\Employee\mobile\Concerns\ResolvesPortalStaff;
use App\Services\EmployeePortalService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    use ResolvesPortalStaff;

    public function __construct(
        protected EmployeePortalService $employeePortal,
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        $staff = $this->portalStaff($request);
        $data = $this->employeePortal->dashboardData($staff);

        $contract = $data['contractSummary'];
        foreach (['start_date', 'end_date', 'signed_date', 'probation_end_date'] as $key) {
            if (($contract[$key] ?? null) instanceof \DateTimeInterface) {
                $contract[$key] = $contract[$key]->format('Y-m-d');
            }
        }

        return response()->json([
            'staff' => [
                'id' => $staff->id,
                'employee_number' => $staff->employee_number,
                'full_name' => $staff->fullName(),
                'job_title' => $staff->job_title,
                'department' => $staff->department?->dept_name,
                'campus' => $staff->campus?->name ?? $staff->campus?->campus_name,
                'photo_url' => $staff->photoUrl(),
                'organisation_email' => $staff->organisation_email,
                'primary_email' => $staff->primary_email,
                'phone_number' => $staff->phone_number,
            ],
            'contract' => $contract,
            'compensation' => [
                'gross_monthly_salary' => $data['compensation']['gross_monthly_salary'],
                'allowances_total' => $data['compensation']['allowances_total'],
                'total_monthly' => $data['compensation']['total_monthly'],
                'salary_scale' => $data['compensation']['salary_scale'],
                'payroll_scheme' => $data['compensation']['payroll_scheme'],
                'employment_category' => $data['compensation']['employment_category'],
                'masked_account_number' => $data['compensation']['masked_account_number'],
                'allowances' => collect($data['compensation']['allowances'])->map(fn ($a) => [
                    'name' => $a->allowance_name ?? $a->name ?? 'Allowance',
                    'amount' => (float) ($a->amount ?? 0),
                ])->values(),
            ],
            'leave_balances' => collect($data['leaveBalances'])->map(fn ($b) => [
                'leave_type_name' => $b->leave_type_name,
                'entitled_days' => (float) $b->entitled_days,
                'days_taken' => (float) $b->days_taken,
                'days_pending' => (float) $b->days_pending,
                'balance_days' => (float) $b->balance_days,
                'accrual_type' => $b->accrual_type,
                'accrual_rate' => $b->accrual_rate !== null ? (float) $b->accrual_rate : null,
            ])->values(),
            'recent_leave_requests' => collect($data['recentLeaveRequests'])->map(fn ($r) => [
                'leave_type_name' => $r->leave_type_name,
                'start_date' => $r->start_date,
                'end_date' => $r->end_date,
                'days_requested' => (float) $r->days_requested,
                'overall_status' => $r->overall_status,
            ])->values(),
            'employment_duration' => $data['employmentDuration'],
            'pending_profile_changes' => (int) $data['pendingProfileChanges'],
            'trainings' => collect($data['trainings'])->map(fn ($t) => [
                'id' => $t->id,
                'title' => $t->training_title ?? $t->title ?? 'Training',
                'start_date' => optional($t->start_date)?->format('Y-m-d'),
                'end_date' => optional($t->end_date)?->format('Y-m-d'),
            ])->values(),
        ]);
    }
}
