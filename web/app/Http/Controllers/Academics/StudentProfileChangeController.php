<?php

namespace App\Http\Controllers\Academics;

use App\Models\Department;
use App\Models\StudentProfileChangeRequest;
use App\Services\StudentProfileChangeService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use InvalidArgumentException;

class StudentProfileChangeController extends DepartmentAcademicsController
{
    public function __construct(
        \App\Services\AcademicsAccessService $access,
        \App\Services\DepartmentDashboardService $departmentDashboard,
        protected StudentProfileChangeService $profileChanges,
    ) {
        parent::__construct($access, $departmentDashboard);
    }

    protected function authorizeRegistrar(Request $request, Department $department): Department
    {
        $hub = $this->authorizeHub($request, $department);
        abort_unless(
            $request->user()->hasAnyRole(['Academic Registrar', 'Super Admin', 'Head of Academics']),
            403,
            'Only Academic Registrar / Head of Academics can manage profile change requests.'
        );

        return $hub;
    }

    public function index(Request $request, Department $department): View
    {
        $hub = $this->authorizeRegistrar($request, $department);
        $status = (string) $request->query('status', 'pending');

        $requests = StudentProfileChangeRequest::query()
            ->with(['student.applicant', 'student.program:id,program_code,program_name', 'requester'])
            ->when($status !== '' && $status !== 'all', fn ($q) => $q->where('status', $status))
            ->orderByDesc('created_at')
            ->paginate(20)
            ->withQueryString();

        return view('academics.profile-changes.index', [
            'department' => $hub,
            'requests' => $requests,
            'status' => $status,
        ]);
    }

    public function show(Request $request, Department $department, StudentProfileChangeRequest $profileChange): View
    {
        $hub = $this->authorizeRegistrar($request, $department);
        $profileChange->load(['student.applicant', 'student.program', 'requester', 'reviewer']);

        return view('academics.profile-changes.show', [
            'department' => $hub,
            'profileChange' => $profileChange,
        ]);
    }

    public function approve(Request $request, Department $department, StudentProfileChangeRequest $profileChange): RedirectResponse
    {
        $hub = $this->authorizeRegistrar($request, $department);
        $validated = $request->validate([
            'reviewer_notes' => 'nullable|string|max:2000',
        ]);

        try {
            $this->profileChanges->approve($profileChange, $request->user(), $validated['reviewer_notes'] ?? null);
        } catch (InvalidArgumentException $e) {
            return back()->withErrors(['approve' => $e->getMessage()]);
        }

        return redirect()
            ->route('departments.academics.profile-changes.show', array_merge(
                \App\Support\AcademicsRouteParams::fromRequest($request),
                ['profileChange' => $profileChange->id]
            ))
            ->with('success', 'Profile change approved.');
    }

    public function reject(Request $request, Department $department, StudentProfileChangeRequest $profileChange): RedirectResponse
    {
        $this->authorizeRegistrar($request, $department);
        $validated = $request->validate([
            'rejection_reason' => 'required|string|max:2000',
            'reviewer_notes' => 'nullable|string|max:2000',
        ]);

        try {
            $this->profileChanges->reject(
                $profileChange,
                $request->user(),
                $validated['rejection_reason'],
                $validated['reviewer_notes'] ?? null
            );
        } catch (InvalidArgumentException $e) {
            return back()->withErrors(['reject' => $e->getMessage()]);
        }

        return redirect()
            ->route('departments.academics.profile-changes.show', array_merge(
                \App\Support\AcademicsRouteParams::fromRequest($request),
                ['profileChange' => $profileChange->id]
            ))
            ->with('success', 'Profile change rejected.');
    }
}
