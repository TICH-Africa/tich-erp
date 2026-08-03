<?php

namespace App\Http\Controllers\HR;

use App\Http\Controllers\Controller;
use App\Models\Staff;
use App\Models\StaffStatusHistory;
use App\Services\AuditService;
use App\Services\StaffLifecycleService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class StatusHistoryController extends Controller
{
    public function __construct(
        protected StaffLifecycleService $lifecycleService,
        protected AuditService $auditService,
    ) {}

    public function index(Request $request, int $staffId)
    {
        $staff = Staff::findOrFail($staffId);

        $query = $staff->statusHistory()
            ->with('approvedBy')
            ->when($request->change_type, fn ($q, $type) => $q->where('change_type', $type))
            ->orderByDesc('effective_date');

        $perPage = (int) ($request->per_page ?? 25);
        $history = $query->paginate($perPage)->appends($request->query());

        return response()->json([
            'data' => $history->items(),
            'meta' => [
                'total' => $history->total(),
                'per_page' => $history->perPage(),
                'current_page' => $history->currentPage(),
                'last_page' => $history->lastPage(),
            ],
        ]);
    }

    public function store(Request $request, int $staffId)
    {
        $staff = Staff::findOrFail($staffId);

        $validated = $request->validate([
            'change_type' => 'required|string|in:promotion,transfer,acting,salary_review,sabbatical,study_leave,unpaid_leave,retirement,termination,resignation,redundancy,dismissal,confirmation,re_engagement,other',
            'new_status' => 'nullable|string|max:50',
            'reason' => 'nullable|string|max:2000',
            'metadata' => 'nullable|array',
            'approved_by' => 'nullable|exists:staff,id',
            'approval_reference' => 'nullable|string|max:100',
            'effective_date' => 'nullable|date',
        ]);

        $previousStatus = $staff->employment_status;

        $history = $this->lifecycleService->recordStatusChange(
            $staffId,
            $validated['change_type'],
            $previousStatus,
            $validated['new_status'] ?? $previousStatus,
            $validated['metadata'] ?? [],
            $validated['approved_by'] ?? null,
            $validated['approval_reference'] ?? null,
            $validated['effective_date']
        );

        return response()->json(['data' => $history], 201);
    }

    public function show(int $staffId, int $id)
    {
        $history = StaffStatusHistory::where('staff_id', $staffId)->with('approvedBy')->findOrFail($id);

        return response()->json(['data' => $history]);
    }
}
