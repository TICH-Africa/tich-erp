<?php

namespace App\Http\Controllers\HR;

use App\Http\Controllers\Controller;
use App\Services\StaffLifecycleService;
use App\Services\ContractService;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __construct(
        protected StaffLifecycleService $lifecycleService,
        protected ContractService $contractService,
    ) {}

    public function __invoke(): View
    {
        $staffCount = \App\Models\Staff::count();
        $activeStaffCount = \App\Models\Staff::where('employment_status', 'active')->count();
        $onboardingCount = \App\Models\Staff::where('employment_status', 'onboarding')->count();
        $contractAlerts = $this->contractService->getExpiryAlerts(30);

        $recentStaff = \App\Models\Staff::latest()->take(5)->get(['id', 'employee_number', 'first_name', 'surname', 'employment_status', 'created_at']);
        $recentContracts = \App\Models\StaffContract::with('staff')->latest()->take(5)->get();

        return view('hr.dashboard', [
            'staffCount' => $staffCount,
            'activeStaffCount' => $activeStaffCount,
            'onboardingCount' => $onboardingCount,
            'contractAlerts' => $contractAlerts,
            'recentStaff' => $recentStaff,
            'recentContracts' => $recentContracts,
        ]);
    }
}
