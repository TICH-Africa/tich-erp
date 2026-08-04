<?php

namespace App\Http\Controllers\HR;

use App\Http\Controllers\Controller;
use App\Models\ClearanceChecklistItem;
use App\Models\OffboardingRequest;
use App\Models\Staff;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Support\Facades\DB;

class OffboardingController extends Controller
{
    public function index(): View
    {
        $offboardings = OffboardingRequest::with(['staff', 'initiator', 'approver'])
            ->orderByDesc('created_at')
            ->paginate(25);

        return view('hr.offboarding.index', ['offboardings' => $offboardings]);
    }

    public function create(): View
    {
        $staff = Staff::whereNotIn('employment_status', ['terminated', 'resigned', 'retired', 'deceased'])
            ->orderBy('first_name')
            ->get(['id', 'first_name', 'surname', 'employee_number', 'job_title']);

        return view('hr.offboarding.create', ['staff' => $staff]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'staff_id' => 'required|exists:staff,id',
            'exit_type' => 'required|string|in:resignation,retirement,non_renewal,termination,redundancy,death',
            'exit_date' => 'required|date',
            'notice_period_days' => 'nullable|integer|min:0',
            'reason' => 'nullable|string|max:2000',
            'termination_reason' => 'nullable|string|max:2000',
            'notes' => 'nullable|string|max:2000',
        ]);

        $staff = Staff::findOrFail($validated['staff_id']);

        DB::transaction(function () use ($validated, $staff, $request) {
            $lastWorkingDay = $validated['exit_date'];
            if ($validated['notice_period_days']) {
                $lastWorkingDay = \Carbon\Carbon::parse($validated['exit_date'])->subDays($validated['notice_period_days'])->toDateString();
            }

            $offboarding = OffboardingRequest::create([
                'staff_id' => $staff->id,
                'exit_type' => $validated['exit_type'],
                'status' => 'pending',
                'exit_date' => $validated['exit_date'],
                'notice_period_days' => $validated['notice_period_days'],
                'last_working_day' => $lastWorkingDay,
                'reason' => $validated['reason'],
                'termination_reason' => $validated['termination_reason'],
                'initiated_by' => $request->user()->staff_id ?? Staff::first()?->id,
                'notes' => $validated['notes'],
            ]);

            $this->createClearanceChecklist($offboarding->id);
        });

        return redirect()->route('hr.offboarding.index')->with('success', 'Offboarding request created successfully.');
    }

    public function show(int $id): View
    {
        $offboarding = OffboardingRequest::with(['staff', 'initiator', 'approver', 'clearanceItems'])->findOrFail($id);

        return view('hr.offboarding.show', ['offboarding' => $offboarding]);
    }

    public function approve(Request $request, int $id)
    {
        $offboarding = OffboardingRequest::with('staff')->findOrFail($id);

        $offboarding->update([
            'status' => 'approved',
            'approved_by' => $request->user()->staff_id ?? Staff::first()?->id,
            'approved_at' => now(),
        ]);

        return redirect()->route('hr.offboarding.show', $offboarding)->with('success', 'Offboarding request approved.');
    }

    public function reject(Request $request, int $id)
    {
        $offboarding = OffboardingRequest::findOrFail($id);

        $validated = $request->validate([
            'notes' => 'nullable|string|max:2000',
        ]);

        $offboarding->update([
            'status' => 'rejected',
            'notes' => $validated['notes'],
        ]);

        return redirect()->route('hr.offboarding.show', $offboarding)->with('success', 'Offboarding request rejected.');
    }

    public function startClearance(Request $request, int $id)
    {
        $offboarding = OffboardingRequest::findOrFail($id);

        $offboarding->update([
            'status' => 'in_progress',
            'processed_by' => $request->user()->staff_id ?? Staff::first()?->id,
            'processed_at' => now(),
        ]);

        return redirect()->route('hr.offboarding.show', $offboarding)->with('success', 'Clearance process started.');
    }

    public function completeClearance(Request $request, int $id)
    {
        $offboarding = OffboardingRequest::with('staff', 'clearanceItems')->findOrFail($id);

        $pendingItems = $offboarding->clearanceItems->where('is_completed', false);
        if ($pendingItems->isNotEmpty()) {
            return redirect()->route('hr.offboarding.show', $offboarding)->with('error', 'All clearance items must be completed before finalizing.');
        }

        DB::transaction(function () use ($offboarding, $request) {
            $offboarding->update([
                'status' => 'completed',
            ]);

            $staff = $offboarding->staff;
            $exitTypeMap = [
                'resignation' => 'resigned',
                'retirement' => 'retired',
                'non_renewal' => 'terminated',
                'termination' => 'terminated',
                'redundancy' => 'terminated',
                'death' => 'deceased',
            ];

            $staff->update([
                'employment_status' => $exitTypeMap[$offboarding->exit_type] ?? 'terminated',
                'exit_date' => $offboarding->exit_date,
                'exit_reason' => $offboarding->reason ?? $offboarding->termination_reason,
            ]);
        });

        return redirect()->route('hr.offboarding.index')->with('success', 'Offboarding completed successfully.');
    }

    public function completeClearanceItem(Request $request, int $offboardingId, int $itemId)
    {
        $item = ClearanceChecklistItem::where('offboarding_request_id', $offboardingId)->findOrFail($itemId);

        $validated = $request->validate([
            'remarks' => 'nullable|string|max:500',
        ]);

        $item->update([
            'is_completed' => true,
            'remarks' => $validated['remarks'],
            'completed_by' => $request->user()->staff_id ?? Staff::first()?->id,
            'completed_at' => now(),
        ]);

        return back()->with('success', 'Clearance item completed.');
    }

    private function createClearanceChecklist(int $offboardingRequestId): void
    {
        $items = [
            ['department' => 'HR', 'item' => 'Return of ID / Access Card'],
            ['department' => 'ICT', 'item' => 'Return of Laptop / Equipment'],
            ['department' => 'Library', 'item' => 'Library Books Returned'],
            ['department' => 'Finance', 'item' => 'Cash Advances / Imprest Cleared'],
            ['department' => 'SACCO', 'item' => 'SACCO Obligations Cleared'],
            ['department' => 'Supervisor', 'item' => 'Handover of Files / Documents'],
            ['department' => 'Finance', 'item' => 'Final Pay Processed'],
        ];

        foreach ($items as $item) {
            ClearanceChecklistItem::create(array_merge($item, ['offboarding_request_id' => $offboardingRequestId]));
        }
    }
}
