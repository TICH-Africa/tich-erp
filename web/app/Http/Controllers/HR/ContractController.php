<?php

namespace App\Http\Controllers\HR;

use App\Http\Controllers\Controller;
use App\Models\Staff;
use App\Models\StaffContract;
use App\Services\AuditService;
use App\Services\ContractService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ContractController extends Controller
{
    public function __construct(
        protected ContractService $contractService,
        protected AuditService $auditService,
    ) {}

    public function index(Request $request)
    {
        $query = StaffContract::query()
            ->with(['staff', 'staff.department', 'department'])
            ->when($request->staff_id, fn ($q, $id) => $q->where('staff_id', $id))
            ->when($request->contract_type, fn ($q, $type) => $q->where('contract_type', $type))
            ->when($request->renewal_status, fn ($q, $status) => $q->where('renewal_status', $status))
            ->when($request->is_signed, fn ($q, $signed) => $q->where('is_signed', $signed))
            ->when($request->expiring_soon, function ($q) {
                $q->active()
                    ->whereNotNull('end_date')
                    ->where('end_date', '<=', now()->addDays(30))
                    ->where('end_date', '>=', now());
            })
            ->orderByDesc('start_date');

        $perPage = (int) ($request->per_page ?? 25);
        $contracts = $query->paginate($perPage)->appends($request->query());

        return response()->json([
            'data' => $contracts->items(),
            'meta' => [
                'total' => $contracts->total(),
                'per_page' => $contracts->perPage(),
                'current_page' => $contracts->currentPage(),
                'last_page' => $contracts->lastPage(),
            ],
        ]);
    }

    public function show(int $id)
    {
        $contract = StaffContract::with([
            'staff',
            'staff.department',
            'department',
            'newContract',
            'previousContract',
        ])->findOrFail($id);

        return response()->json(['data' => $contract]);
    }

    public function store(Request $request, int $staffId)
    {
        $staff = Staff::findOrFail($staffId);

        $validated = $request->validate([
            'contract_type' => 'required|string|in:permanent,contract,intern,visiting,casual,probation,consultancy',
            'job_title' => 'required|string|max:200',
            'department_id' => 'required|exists:departments,id',
            'gross_salary' => 'required|numeric|min:0',
            'start_date' => 'required|date',
            'duration' => 'nullable|string|max:50',
            'end_date' => 'nullable|date',
            'is_renewable' => 'boolean',
            'probation_end_date' => 'nullable|date',
            'contract_document_path' => 'nullable|string|max:500',
        ]);

        if (empty($validated['end_date']) && ! empty($validated['duration'])) {
            $calculated = $this->contractService->calculateEndDate($validated['start_date'], $validated['duration']);
            if ($calculated) {
                $validated['end_date'] = $calculated;
            }
        }

        $contract = $this->contractService->createContract($staffId, $validated, $request->user()->id);

        return response()->json(['data' => $contract->load('staff', 'department')], 201);
    }

    public function update(Request $request, int $id)
    {
        $contract = StaffContract::findOrFail($id);

        $validated = $request->validate([
            'contract_type' => 'sometimes|string|in:permanent,contract,intern,visiting,casual,probation,consultancy',
            'job_title' => 'sometimes|string|max:200',
            'department_id' => 'sometimes|exists:departments,id',
            'gross_salary' => 'sometimes|numeric|min:0',
            'start_date' => 'sometimes|date',
            'duration' => 'nullable|string|max:50',
            'end_date' => 'nullable|date',
            'is_renewable' => 'sometimes|boolean',
            'probation_end_date' => 'nullable|date',
            'probation_status' => 'sometimes|string|in:not_applicable,active,passed,failed',
            'contract_document_path' => 'nullable|string|max:500',
            'is_signed' => 'sometimes|boolean',
            'signed_date' => 'nullable|date',
            'witnessed_by' => 'nullable|string|max:200',
        ]);

        if (empty($validated['end_date']) && ! empty($validated['duration']) && ! empty($validated['start_date'])) {
            $startDate = $validated['start_date'] ?? $contract->start_date;
            if ($startDate) {
                $calculated = $this->contractService->calculateEndDate($startDate, $validated['duration']);
                if ($calculated) {
                    $validated['end_date'] = $calculated;
                }
            }
        } elseif (empty($validated['end_date']) && ! empty($validated['duration']) && ! empty($contract->start_date)) {
            $calculated = $this->contractService->calculateEndDate($contract->start_date, $validated['duration']);
            if ($calculated) {
                $validated['end_date'] = $calculated;
            }
        }

        $contract = $this->contractService->updateContract($id, $validated, $request->user()->id);

        return response()->json(['data' => $contract->load('staff', 'department')]);
    }

    public function destroy(Request $request, int $id)
    {
        $contract = StaffContract::findOrFail($id);

        $this->auditService->log(
            'staff.contract.deleted',
            'staff_contracts',
            $contract->id,
            $contract->toArray(),
            null,
            'Contract deleted',
            'success',
            $request->user()->id,
            $request
        );

        $contract->delete();

        return response()->json(null, 204);
    }

    public function renew(Request $request, int $id)
    {
        $validated = $request->validate([
            'start_date' => 'required|date',
            'duration' => 'nullable|string|max:50',
            'end_date' => 'nullable|date',
            'gross_salary' => 'required|numeric|min:0',
            'job_title' => 'sometimes|string|max:200',
            'contract_type' => 'sometimes|string|in:permanent,contract,intern,visiting,casual,probation,consultancy',
        ]);

        if (empty($validated['end_date']) && ! empty($validated['duration'])) {
            $calculated = $this->contractService->calculateEndDate($validated['start_date'], $validated['duration']);
            if ($calculated) {
                $validated['end_date'] = $calculated;
            }
        }

        $newContract = $this->contractService->renewContract($id, $validated, $request->user()->id);

        return redirect()->route('hr.contracts.show', $newContract)->with('success', 'Contract renewed successfully.');
    }

    public function terminate(Request $request, int $id)
    {
        $validated = $request->validate([
            'reason' => 'nullable|string|max:2000',
        ]);

        $contract = $this->contractService->terminateContract($id, $validated['reason'] ?? null, $request->user()->id);

        return response()->json(['data' => $contract->load('staff', 'department')]);
    }

    public function sign(Request $request, int $id)
    {
        $validated = $request->validate([
            'witnessed_by' => 'nullable|string|max:200',
        ]);

        $contract = $this->contractService->markContractSigned($id, $validated['witnessed_by'] ?? null, $request->user()->id);

        return redirect()->route('hr.contracts.show', $contract)->with('success', 'Contract marked as signed successfully.');
    }

    public function convertToPermanent(Request $request, int $id)
    {
        $contract = $this->contractService->convertToPermanent($id, $request->user()->id);

        return redirect()->route('hr.contracts.show', $contract)->with('success', 'Contract converted to permanent successfully.');
    }

    public function alerts(Request $request)
    {
        $days = (int) ($request->days ?? 30);
        $alerts = $this->contractService->getExpiryAlerts($days);

        return response()->json([
            'data' => $alerts,
            'meta' => [
                'days' => $days,
                'contracts_count' => $alerts['contracts']->count(),
                'licenses_count' => $alerts['licenses']->count(),
                'certificates_count' => $alerts['certificates']->count(),
            ],
        ]);
    }
}
