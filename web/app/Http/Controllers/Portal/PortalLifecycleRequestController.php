<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\StudentLifecycleRequest;
use App\Services\StudentPortalService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class PortalLifecycleRequestController extends Controller
{
    public function __construct(
        protected StudentPortalService $portalService,
    ) {}

    public function store(Request $request): RedirectResponse
    {
        $student = $this->portalService->studentForUser($request->user());
        abort_if(! $student, 404);

        $validated = $request->validate([
            'request_type' => 'required|in:'.implode(',', array_keys(StudentLifecycleRequest::TYPES)),
            'effective_date' => 'nullable|date',
            'reason' => 'required|string|max:5000',
        ]);

        StudentLifecycleRequest::query()->create([
            'student_id' => $student->id,
            'requested_by_user_id' => $request->user()->id,
            'request_type' => $validated['request_type'],
            'status' => 'pending',
            'effective_date' => $validated['effective_date'] ?? null,
            'reason' => $validated['reason'],
        ]);

        return redirect()
            ->route('portal.dashboard', ['section' => 'requests'])
            ->with('success', 'Your lifecycle request was submitted for review.');
    }
}
