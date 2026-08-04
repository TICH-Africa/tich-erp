<?php

namespace App\Http\Controllers\HR;

use App\Http\Controllers\Controller;
use App\Models\LeaveRequest;
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

        $applicationsWithDocuments = $recentApplications->filter(
            fn (RecruitmentApplication $application) => $application->uploadedDocuments()->isNotEmpty()
        );

        $defaultApplication = $applicationsWithDocuments->first();

        $pendingLeaveCount = LeaveRequest::query()->where('overall_status', 'pending_hr')->count();
        $recentLeaveRequests = LeaveRequest::query()
            ->with(['staff', 'leaveType'])
            ->orderByDesc('created_at')
            ->limit(5)
            ->get();

        $applicationsPayload = $applicationsWithDocuments->map(function (RecruitmentApplication $application) {
            return [
                'id' => $application->id,
                'documents' => $application->uploadedDocuments()->map(function (array $document) use ($application) {
                    return [
                        'key' => $document['key'],
                        'label' => $document['label'],
                        'filename' => $document['filename'],
                        'mime_type' => $document['mime_type'],
                        'is_previewable' => $document['is_previewable'],
                        'view_url' => route('hr.recruitment.documents.show', [$application, $document['key']]),
                        'download_url' => route('hr.recruitment.documents.download', [$application, $document['key']]),
                        'external_url' => route('hr.recruitment.documents.viewer', [$application, $document['key']]),
                    ];
                })->values()->all(),
            ];
        })->values()->all();

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
            'applicationsWithDocuments' => $applicationsWithDocuments,
            'defaultApplication' => $defaultApplication,
            'applicationsPayload' => $applicationsPayload,
            'pendingLeaveCount' => $pendingLeaveCount,
            'recentLeaveRequests' => $recentLeaveRequests,
        ]);
    }
}
