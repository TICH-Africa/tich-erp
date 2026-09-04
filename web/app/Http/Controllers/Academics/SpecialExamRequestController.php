<?php

namespace App\Http\Controllers\Academics;

use App\Models\Department;
use App\Models\SpecialExamRequest;
use App\Services\StaffPortalService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SpecialExamRequestController extends DepartmentAcademicsController
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
            'Only HOD / Academic Registrar can review special exam requests.'
        );

        return $hub;
    }

    public function index(Request $request, Department $department): View
    {
        $hub = $this->authorizeReviewer($request, $department);
        $status = (string) $request->query('status', 'pending');

        $requests = SpecialExamRequest::query()
            ->with(['student.applicant', 'student.program:id,program_code,program_name', 'unit', 'semester'])
            ->when($status !== '' && $status !== 'all', fn ($q) => $q->where('status', $status))
            ->orderByDesc('created_at')
            ->paginate(20)
            ->withQueryString();

        return view('academics.special-exam-requests.index', [
            'department' => $hub,
            'requests' => $requests,
            'status' => $status,
        ]);
    }

    public function show(Request $request, Department $department, SpecialExamRequest $specialExamRequest): View
    {
        $hub = $this->authorizeReviewer($request, $department);
        $specialExamRequest->load(['student.applicant', 'student.program', 'unit', 'semester', 'reviewer']);

        return view('academics.special-exam-requests.show', [
            'department' => $hub,
            'specialExamRequest' => $specialExamRequest,
        ]);
    }

    public function approve(Request $request, Department $department, SpecialExamRequest $specialExamRequest): RedirectResponse
    {
        $this->authorizeReviewer($request, $department);
        $staff = $this->staffPortal->staffForUser($request->user());
        abort_unless($staff, 403);

        $validated = $request->validate([
            'reviewed_notes' => 'nullable|string|max:2000',
        ]);

        abort_unless($specialExamRequest->status === 'pending' || $specialExamRequest->status === 'on_hold', 422);

        $specialExamRequest->fill([
            'status' => 'approved',
            'reviewed_notes' => $validated['reviewed_notes'] ?? null,
            'reviewed_by' => $staff->id,
            'reviewed_at' => now(),
        ])->save();

        return redirect()
            ->route('departments.academics.special-exam-requests.show', array_merge(
                \App\Support\AcademicsRouteParams::fromRequest($request),
                ['specialExamRequest' => $specialExamRequest->id]
            ))
            ->with('success', 'Special exam request approved.');
    }

    public function hold(Request $request, Department $department, SpecialExamRequest $specialExamRequest): RedirectResponse
    {
        $this->authorizeReviewer($request, $department);
        $staff = $this->staffPortal->staffForUser($request->user());
        abort_unless($staff, 403);

        $validated = $request->validate([
            'reviewed_notes' => 'required|string|max:2000',
        ]);

        abort_unless(in_array($specialExamRequest->status, ['pending', 'on_hold'], true), 422);

        $specialExamRequest->fill([
            'status' => 'on_hold',
            'reviewed_notes' => $validated['reviewed_notes'],
            'reviewed_by' => $staff->id,
            'reviewed_at' => now(),
        ])->save();

        return redirect()
            ->route('departments.academics.special-exam-requests.show', array_merge(
                \App\Support\AcademicsRouteParams::fromRequest($request),
                ['specialExamRequest' => $specialExamRequest->id]
            ))
            ->with('success', 'Special exam request put on hold.');
    }

    public function reject(Request $request, Department $department, SpecialExamRequest $specialExamRequest): RedirectResponse
    {
        $this->authorizeReviewer($request, $department);
        $staff = $this->staffPortal->staffForUser($request->user());
        abort_unless($staff, 403);

        $validated = $request->validate([
            'reviewed_notes' => 'required|string|max:2000',
        ]);

        abort_unless(in_array($specialExamRequest->status, ['pending', 'on_hold'], true), 422);

        $specialExamRequest->fill([
            'status' => 'rejected',
            'reviewed_notes' => $validated['reviewed_notes'],
            'reviewed_by' => $staff->id,
            'reviewed_at' => now(),
        ])->save();

        return redirect()
            ->route('departments.academics.special-exam-requests.show', array_merge(
                \App\Support\AcademicsRouteParams::fromRequest($request),
                ['specialExamRequest' => $specialExamRequest->id]
            ))
            ->with('success', 'Special exam request rejected.');
    }
}
