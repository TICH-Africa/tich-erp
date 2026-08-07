<?php

namespace App\Http\Controllers\HR;

use App\Http\Controllers\Controller;
use App\Models\StaffProfileChangeRequest;
use App\Services\EmployeeProfileChangeService;
use App\Services\StaffPortalService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class StaffProfileChangeController extends Controller
{
    public function __construct(
        protected EmployeeProfileChangeService $profileChanges,
        protected StaffPortalService $staffPortal,
    ) {}

    public function index(Request $request): View
    {
        $status = $request->string('status', 'pending')->toString();

        $requests = StaffProfileChangeRequest::query()
            ->with(['staff.department', 'requestedBy'])
            ->when($status !== 'all', fn ($query) => $query->where('status', $status))
            ->orderByDesc('created_at')
            ->paginate(20)
            ->withQueryString();

        $pendingCount = StaffProfileChangeRequest::query()
            ->where('status', StaffProfileChangeRequest::STATUS_PENDING)
            ->count();

        return view('hr.profile-changes.index', [
            'requests' => $requests,
            'status' => $status,
            'pendingCount' => $pendingCount,
        ]);
    }

    public function show(StaffProfileChangeRequest $profileChange): View
    {
        $profileChange->load(['staff.department', 'requestedBy', 'reviewedBy']);

        $attachmentUrl = null;
        if ($profileChange->attachment_path && Storage::disk('public')->exists($profileChange->attachment_path)) {
            $attachmentUrl = Storage::disk('public')->url($profileChange->attachment_path);
        }

        return view('hr.profile-changes.show', [
            'changeRequest' => $profileChange,
            'attachmentUrl' => $attachmentUrl,
        ]);
    }

    public function approve(Request $request, StaffProfileChangeRequest $profileChange): RedirectResponse
    {
        $validated = $request->validate([
            'hr_notes' => 'nullable|string|max:2000',
        ]);

        $reviewer = $this->staffPortal->staffForUser($request->user());
        abort_unless($reviewer, 403);

        try {
            $this->profileChanges->approve($profileChange, $reviewer, $validated['hr_notes'] ?? null);
        } catch (\InvalidArgumentException $exception) {
            return back()->withErrors(['form' => $exception->getMessage()]);
        }

        return redirect()
            ->back(fallback: route('hr.profile-changes.index'))
            ->with('success', 'Profile change approved and applied.');
    }

    public function reject(Request $request, StaffProfileChangeRequest $profileChange): RedirectResponse
    {
        $validated = $request->validate([
            'rejection_reason' => 'required|string|max:1000',
            'hr_notes' => 'nullable|string|max:2000',
        ]);

        $reviewer = $this->staffPortal->staffForUser($request->user());
        abort_unless($reviewer, 403);

        try {
            $this->profileChanges->reject(
                $profileChange,
                $reviewer,
                $validated['rejection_reason'],
                $validated['hr_notes'] ?? null,
            );
        } catch (\InvalidArgumentException $exception) {
            return back()->withErrors(['form' => $exception->getMessage()]);
        }

        return redirect()
            ->back(fallback: route('hr.profile-changes.index'))
            ->with('success', 'Profile change rejected.');
    }
}
