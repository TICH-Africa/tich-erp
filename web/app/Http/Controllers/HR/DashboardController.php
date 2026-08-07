<?php

namespace App\Http\Controllers\HR;

use App\Http\Controllers\Controller;
use App\Models\LeaveRequest;
use App\Models\RecruitmentApplication;
use App\Models\Staff;
use App\Models\StaffProfileChangeRequest;
use App\Services\ContractService;
use App\Services\HrDashboardStatsService;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __construct(
        protected ContractService $contractService,
        protected HrDashboardStatsService $statsService,
    ) {}

    public function __invoke(): View
    {
        $staffCount = Staff::count();
        $activeStaffCount = Staff::where('employment_status', 'active')->count();
        $onboardingCount = Staff::where('employment_status', 'onboarding')->count();
        $contractAlerts = $this->contractService->getExpiryAlerts(30);

        $applicationCount = RecruitmentApplication::count();
        $newApplicationsCount = RecruitmentApplication::query()
            ->where('status', 'submitted')
            ->where('is_viewed', 0)
            ->count();

        $pendingLeaveCount = LeaveRequest::query()->where('overall_status', 'pending_hr')->count();

        $pendingProfileChanges = StaffProfileChangeRequest::query()
            ->with(['staff.department'])
            ->where('status', StaffProfileChangeRequest::STATUS_PENDING)
            ->orderByDesc('created_at')
            ->limit(10)
            ->get();

        $pendingProfileChangeCount = StaffProfileChangeRequest::query()
            ->where('status', StaffProfileChangeRequest::STATUS_PENDING)
            ->count();

        return view('hr.dashboard', [
            'staffCount' => $staffCount,
            'activeStaffCount' => $activeStaffCount,
            'onboardingCount' => $onboardingCount,
            'contractAlerts' => $contractAlerts,
            'applicationCount' => $applicationCount,
            'newApplicationsCount' => $newApplicationsCount,
            'pendingLeaveCount' => $pendingLeaveCount,
            'pendingProfileChangeCount' => $pendingProfileChangeCount,
            'pendingProfileChanges' => $pendingProfileChanges,
            'chartData' => $this->statsService->chartData(),
        ]);
    }
}
