<?php

namespace App\Http\Controllers\Academics;

use App\Models\Department;
use App\Models\SupplementaryExamRequest;
use App\Services\StaffPortalService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SupplementaryRequestController extends DepartmentAcademicsController
{
    public function __construct(
        \App\Services\AcademicsAccessService $access,
        \App\Services\DepartmentDashboardService $departmentDashboard,
        protected StaffPortalService $staffPortal,
    ) {
        parent::__construct($access, $departmentDashboard);
    }

    protected function authorizeReviewer(Request $request, Department $department): Department
    {
        $hub = $this->authorizeHub($request, $department);
        abort_unless(
            $request->user()->hasAnyRole(['Academic Registrar', 'HOD', 'Super Admin', 'Head of Academics']),
            403,
            'Only HOD / Academic Registrar can review supplementary exam requests.'
        );

        return $hub;
    }

    public function index(Request $request, Department $department): View
    {
        $hub = $this->authorizeReviewer($request, $department);
        $status = (string) $request->query('status', 'pending_review');

        $requests = SupplementaryExamRequest::query()
            ->with(['student.applicant', 'student.program:id,program_code,program_name', 'unit', 'semester'])
            ->when($status !== '' && $status !== 'all', fn ($q) => $q->where('application_status', $status))
            ->orderByDesc('created_at')
            ->paginate(20)
            ->withQueryString();

        return view('academics.supplementary-requests.index', [
            'department' => $hub,
            'requests' => $requests,
            'status' => $status,
        ]);
    }

    public function show(Request $request, Department $department, SupplementaryExamRequest $supplementaryExamRequest): View
    {
        $hub = $this->authorizeReviewer($request, $department);
        $supplementaryExamRequest->load(['student.applicant', 'student.program', 'unit', 'semester', 'reviewer']);

        return view('academics.supplementary-requests.show', [
            'department' => $hub,
            'supplementaryRequest' => $supplementaryExamRequest,
        ]);
    }

    public function approve(Request $request, Department $department, SupplementaryExamRequest $supplementaryExamRequest): RedirectResponse
    {
        $this->authorizeReviewer($request, $department);
        $staff = $this->staffPortal->staffForUser($request->user());
        abort_unless($staff, 403);

        $validated = $request->validate([
            'reviewed_notes' => 'nullable|string|max:2000',
        ]);

        abort_unless(in_array($supplementaryExamRequest->application_status, ['pending_review', 'pending_fee'], true), 422);

        $supplementaryExamRequest->fill([
            'application_status' => 'approved',
            'reviewed_notes' => $validated['reviewed_notes'] ?? null,
            'reviewed_by' => $staff->id,
            'reviewed_at' => now(),
        ])->save();

        return redirect()
            ->route('departments.academics.supplementary-requests.show', array_merge(
                \App\Support\AcademicsRouteParams::fromRequest($request),
                ['supplementaryExamRequest' => $supplementaryExamRequest->id]
            ))
            ->with('success', 'Supplementary exam request approved.');
    }

    public function reject(Request $request, Department $department, SupplementaryExamRequest $supplementaryExamRequest): RedirectResponse
    {
        $this->authorizeReviewer($request, $department);
        $staff = $this->staffPortal->staffForUser($request->user());
        abort_unless($staff, 403);

        $validated = $request->validate([
            'reviewed_notes' => 'required|string|max:2000',
        ]);

        abort_unless(in_array($supplementaryExamRequest->application_status, ['pending_review', 'pending_fee'], true), 422);

        $supplementaryExamRequest->fill([
            'application_status' => 'rejected',
            'reviewed_notes' => $validated['reviewed_notes'],
            'reviewed_by' => $staff->id,
            'reviewed_at' => now(),
        ])->save();

        return redirect()
            ->route('departments.academics.supplementary-requests.show', array_merge(
                \App\Support\AcademicsRouteParams::fromRequest($request),
                ['supplementaryExamRequest' => $supplementaryExamRequest->id]
            ))
            ->with('success', 'Supplementary exam request rejected.');
    }
}
