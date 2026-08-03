<?php

namespace App\Http\Controllers\HR;

use App\Http\Controllers\Controller;
use App\Models\Staff;
use App\Models\StaffAllowance;
use App\Services\AuditService;
use App\Services\StaffLifecycleService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AllowanceController extends Controller
{
    public function __construct(
        protected StaffLifecycleService $lifecycleService,
        protected AuditService $auditService,
    ) {}

    public function index(Request $request, int $staffId)
    {
        $staff = Staff::findOrFail($staffId);

        $query = $staff->allowances()
            ->with('approver')
            ->when($request->is_active, fn ($q, $active) => $q->where('is_active', $active))
            ->when($request->type, fn ($q, $type) => $q->where('allowance_type', $type))
            ->orderByDesc('effective_date');

        $perPage = (int) ($request->per_page ?? 25);
        $allowances = $query->paginate($perPage)->appends($request->query());

        return response()->json([
            'data' => $allowances->items(),
            'meta' => [
                'total' => $allowances->total(),
                'per_page' => $allowances->perPage(),
                'current_page' => $allowances->currentPage(),
                'last_page' => $allowances->lastPage(),
            ],
        ]);
    }

    public function store(Request $request, int $staffId)
    {
        $staff = Staff::findOrFail($staffId);

        $validated = $request->validate([
            'allowance_type' => 'required|string|in:housing,transport,lunch,acting,responsibility,medical,other',
            'allowance_name' => 'required|string|max:200',
            'amount' => 'required|numeric|min:0',
            'effective_date' => 'required|date',
            'end_date' => 'nullable|date',
            'is_active' => 'boolean',
        ]);

        $allowance = $this->lifecycleService->addAllowance($staffId, $validated, $request->user()->id);

        return response()->json(['data' => $allowance], 201);
    }

    public function show(int $staffId, int $id)
    {
        $allowance = StaffAllowance::where('staff_id', $staffId)->findOrFail($id);

        return response()->json(['data' => $allowance]);
    }

    public function update(Request $request, int $staffId, int $id)
    {
        $allowance = StaffAllowance::where('staff_id', $staffId)->findOrFail($id);

        $validated = $request->validate([
            'allowance_type' => 'sometimes|string|in:housing,transport,lunch,acting,responsibility,medical,other',
            'allowance_name' => 'sometimes|string|max:200',
            'amount' => 'sometimes|numeric|min:0',
            'effective_date' => 'sometimes|date',
            'end_date' => 'nullable|date',
            'is_active' => 'sometimes|boolean',
        ]);

        $oldValues = $allowance->only(array_keys($validated));

        DB::transaction(function () use ($allowance, $validated, $request) {
            $allowance->update($validated);

            $this->auditService->log(
                'staff.allowance.updated',
                'staff_allowances',
                $allowance->id,
                $oldValues,
                $allowance->only(array_keys($validated)),
                'Allowance updated',
                'success',
                $request->user()->id,
                $request
            );
        });

        return response()->json(['data' => $allowance->fresh()]);
    }

    public function destroy(Request $request, int $staffId, int $id)
    {
        $allowance = StaffAllowance::where('staff_id', $staffId)->findOrFail($id);

        $this->auditService->log(
            'staff.allowance.deleted',
            'staff_allowances',
            $allowance->id,
            $allowance->toArray(),
            null,
            'Allowance deleted',
            'success',
            $request->user()->id,
            $request
        );

        $allowance->delete();

        return response()->json(null, 204);
    }
}
