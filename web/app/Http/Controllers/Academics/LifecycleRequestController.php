<?php

namespace App\Http\Controllers\Academics;

use App\Models\Department;
use App\Models\StudentLifecycleRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LifecycleRequestController extends DepartmentAcademicsController
{
    protected function authorizeRegistrar(Request $request, Department $department): Department
    {
        $hub = $this->authorizeHub($request, $department);
        abort_unless(
            $request->user()->hasAnyRole(['Academic Registrar', 'Super Admin', 'Head of Academics']),
            403,
            'Only Academic Registrar / Head of Academics can manage lifecycle requests.'
        );

        return $hub;
    }

    public function index(Request $request, Department $department): View
    {
        $hub = $this->authorizeRegistrar($request, $department);
        $status = (string) $request->query('status', 'pending');

        $requests = StudentLifecycleRequest::query()
            ->with(['student.applicant', 'student.program:id,program_code,program_name'])
            ->when($status !== '' && $status !== 'all', fn ($q) => $q->where('status', $status))
            ->orderByDesc('created_at')
            ->paginate(20)
            ->withQueryString();

        return view('academics.lifecycle-requests.index', [
            'department' => $hub,
            'requests' => $requests,
            'status' => $status,
        ]);
    }

    public function show(Request $request, Department $department, StudentLifecycleRequest $lifecycleRequest): View
    {
        $hub = $this->authorizeRegistrar($request, $department);
        $lifecycleRequest->load(['student.applicant', 'student.program']);

        return view('academics.lifecycle-requests.show', [
            'department' => $hub,
            'lifecycleRequest' => $lifecycleRequest,
            'types' => StudentLifecycleRequest::TYPES,
        ]);
    }

    public function approve(Request $request, Department $department, StudentLifecycleRequest $lifecycleRequest): RedirectResponse
    {
        $this->authorizeRegistrar($request, $department);
        $validated = $request->validate([
            'reviewer_notes' => 'nullable|string|max:2000',
        ]);

        abort_unless($lifecycleRequest->status === 'pending', 422);

        $lifecycleRequest->update([
            'status' => 'approved',
            'reviewer_notes' => $validated['reviewer_notes'] ?? null,
            'reviewed_by_user_id' => $request->user()->id,
            'reviewed_at' => now(),
        ]);

        return redirect()
            ->route('departments.academics.lifecycle-requests.show', array_merge(
                \App\Support\AcademicsRouteParams::fromRequest($request),
                ['lifecycleRequest' => $lifecycleRequest->id]
            ))
            ->with('success', 'Lifecycle request approved.');
    }

    public function reject(Request $request, Department $department, StudentLifecycleRequest $lifecycleRequest): RedirectResponse
    {
        $this->authorizeRegistrar($request, $department);
        $validated = $request->validate([
            'reviewer_notes' => 'required|string|max:2000',
        ]);

        abort_unless($lifecycleRequest->status === 'pending', 422);

        $lifecycleRequest->update([
            'status' => 'rejected',
            'reviewer_notes' => $validated['reviewer_notes'],
            'reviewed_by_user_id' => $request->user()->id,
            'reviewed_at' => now(),
        ]);

        return redirect()
            ->route('departments.academics.lifecycle-requests.show', array_merge(
                \App\Support\AcademicsRouteParams::fromRequest($request),
                ['lifecycleRequest' => $lifecycleRequest->id]
            ))
            ->with('success', 'Lifecycle request rejected.');
    }
}
