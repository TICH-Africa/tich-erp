<?php

namespace App\Http\Controllers\Api\Procurement\mobile;

use App\Http\Controllers\Api\Procurement\mobile\Concerns\BroadcastsProcurementCounts;
use App\Http\Controllers\Controller;
use App\Models\InventoryTransaction;
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
