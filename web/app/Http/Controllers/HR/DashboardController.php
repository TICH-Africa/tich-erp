<?php

namespace App\Http\Controllers\HR;

use App\Http\Controllers\Controller;
use App\Models\RecruitmentApplication;
use App\Models\Staff;
use App\Models\StaffContract;
use App\Services\ContractService;
use App\Services\StaffLifecycleService;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __construct(
        protected StaffLifecycleService $lifecycleService,
        protected ContractService $contractService,
    ) {}

    public function __invoke(): View
    {
        $staffCount = Staff::count();
        $activeStaffCount = Staff::where('employment_status', 'active')->count();
        $onboardingCount = Staff::where('employment_status', 'onboarding')->count();
        $contractAlerts = $this->contractService->getExpiryAlerts(30);

        $recentStaff = Staff::latest()->take(5)->get(['id', 'employee_number', 'first_name', 'surname', 'employment_status', 'created_at']);
        $recentContracts = StaffContract::with('staff')->latest()->take(5)->get();

        $applicationCount = RecruitmentApplication::count();
        $newApplicationsCount = RecruitmentApplication::query()
            ->where('status', 'submitted')
            ->where('is_viewed', 0)
            ->count();
        $recentApplications = RecruitmentApplication::query()
            ->with('vacancy')
            ->latest()
            ->take(5)
            ->get();

        return view('hr.dashboard', [
            'staffCount' => $staffCount,
            'activeStaffCount' => $activeStaffCount,
            'onboardingCount' => $onboardingCount,
            'contractAlerts' => $contractAlerts,
            'recentStaff' => $recentStaff,
            'recentContracts' => $recentContracts,
            'applicationCount' => $applicationCount,
            'newApplicationsCount' => $newApplicationsCount,
            'recentApplications' => $recentApplications,
        ]);
    }
}
