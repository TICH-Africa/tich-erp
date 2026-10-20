<?php

namespace App\Http\Controllers\Api\Procurement\mobile;

use App\Http\Controllers\Api\Procurement\mobile\Concerns\BroadcastsProcurementCounts;
use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\InventoryItem;
use App\Models\InventoryTransaction;
use App\Models\Staff;
use App\Models\StockIssue;
use App\Services\Procurement\InventoryService;
use App\Services\Procurement\ProcurementMobilePortalService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StockIssuesController extends Controller
{
    use BroadcastsProcurementCounts;

    public function __construct(
        protected ProcurementMobilePortalService $portal,
    ) {}

    public function formOptions(Request $request): JsonResponse
    {
        $staffSearch = $request->string('staff_search')->toString();

        $items = InventoryItem::query()
            ->where('current_stock', '>', 0)
            ->orderBy('item_name')
            ->limit(200)
            ->get(['id', 'item_code', 'item_name', 'current_stock', 'unit_of_measure', 'unit_cost'])
            ->map(fn (InventoryItem $item) => [
                'id' => $item->id,
                'item_code' => $item->item_code,
                'item_name' => $item->item_name,
                'current_stock' => (int) $item->current_stock,
                'unit_of_measure' => $item->unit_of_measure,
                'unit_cost' => (float) $item->unit_cost,
                'label' => "{$item->item_code} - {$item->item_name} ({$item->current_stock})",
            ])
            ->values();

        $departments = Department::query()
            ->where('is_active', 1)
            ->orderBy('dept_name')
            ->limit(200)
            ->get(['id', 'dept_name'])
            ->map(fn (Department $dept) => [
                'id' => $dept->id,
                'name' => $dept->dept_name,
            ])
            ->values();

        $staff = Staff::query()
            ->when($staffSearch !== '', function ($q) use ($staffSearch) {
                $q->where(function ($inner) use ($staffSearch) {
                    $inner->where('first_name', 'like', "%{$staffSearch}%")
                        ->orWhere('surname', 'like', "%{$staffSearch}%")
                        ->orWhere('employee_number', 'like', "%{$staffSearch}%");
                });
            })
            ->orderBy('first_name')
            ->limit(200)
            ->get(['id', 'first_name', 'surname'])
            ->map(fn (Staff $s) => [
                'id' => $s->id,
                'name' => $s->fullName(),
            ])
            ->values();

        return response()->json([
            'items' => $items,
            'departments' => $departments,
            'staff' => $staff,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'inventory_item_id' => ['required', 'exists:inventory_items,id'],
            'department_id' => ['required', 'exists:departments,id'],
            'requested_by' => ['required', 'exists:staff,id'],
            'quantity' => ['required', 'numeric', 'min:0.0001'],
            'unit_cost' => ['required', 'numeric', 'min:0'],
            'reason' => ['required', 'string', 'max:2000'],
        ]);

        $validated['total_cost'] = round((float) $validated['quantity'] * (float) $validated['unit_cost'], 2);
        $validated['approval_status'] = 'pending';
        $validated['status'] = 'pending';

        $issue = StockIssue::query()->create($validated);
        $issue->load(['item', 'department', 'requestedBy']);

        InventoryTransaction::query()->create([
            'inventory_item_id' => $validated['inventory_item_id'],
            'transaction_type' => 'issue_pending',
            'quantity' => (int) $validated['quantity'],
            'unit_cost' => $validated['unit_cost'],
            'total_cost' => $validated['total_cost'],
            'reference_table' => 'stock_issues',
            'reference_id' => $issue->id,
            'department_id' => $validated['department_id'],
            'recorded_by' => $validated['requested_by'],
            'transaction_date' => now()->format('Y-m-d'),
            'notes' => "Pending stock issue: {$issue->item?->item_name}",
        ]);

        $this->broadcastProcurementCounts();

        return response()->json([
            'message' => 'Stock issue request created.',
            'item' => $this->portal->serializeStockIssue($issue),
        ], 201);
    }

    public function index(): JsonResponse
    {
        $issues = StockIssue::query()
            ->with(['item', 'department', 'requestedBy'])
            ->orderByDesc('created_at')
            ->limit(50)
            ->get()
            ->map(fn (StockIssue $i) => $this->portal->serializeStockIssue($i))
            ->values();

        return response()->json(['items' => $issues]);
    }

    public function show(StockIssue $stockIssue): JsonResponse
    {
        return response()->json($this->portal->serializeStockIssue($stockIssue));
    }

    public function approve(Request $request, StockIssue $stockIssue, InventoryService $inventoryService): JsonResponse
    {
        if ($stockIssue->approval_status === 'approved') {
            return response()->json(['message' => 'Already approved.'], 422);
        }

        $item = $stockIssue->item;
        $qty = (int) $stockIssue->quantity;

        if (! $item || $item->current_stock < $qty) {
            return response()->json(['message' => 'Insufficient stock to approve this issue.'], 422);
        }

        $staffId = $request->user()->staff_id;
        abort_unless($staffId, 403, 'Linked employee profile required.');

        $stockIssue->update([
            'approval_status' => 'approved',
            'approved_by' => $staffId,
            'approved_at' => now(),
            'status' => 'completed',
        ]);

        $inventoryService->updateStock(
            $item->id,
            $qty,
            'issue',
            (int) $staffId,
            [
                'department_id' => $stockIssue->department_id,
                'reference_table' => 'stock_issues',
                'reference_id' => $stockIssue->id,
            ],
            ['notes' => "Stock issue: {$item->item_name}"]
        );

        InventoryTransaction::query()
            ->where('reference_table', 'stock_issues')
            ->where('reference_id', $stockIssue->id)
            ->where('transaction_type', 'issue_pending')
            ->delete();

        $this->broadcastProcurementCounts();

        return response()->json([
            'message' => 'Stock issue approved.',
            'item' => $this->portal->serializeStockIssue($stockIssue->fresh()),
        ]);
    }
}
