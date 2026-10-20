<?php

namespace App\Http\Controllers\Api\Procurement\mobile;

use App\Http\Controllers\Api\Procurement\mobile\Concerns\BroadcastsProcurementCounts;
use App\Http\Controllers\Controller;
use App\Models\ProcurementRequisition;
use App\Models\StockAlert;
use App\Services\Procurement\ProcurementMobilePortalService;
use App\Services\Procurement\RequisitionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StockAlertsController extends Controller
{
    use BroadcastsProcurementCounts;

    public function __construct(
        protected ProcurementMobilePortalService $portal,
        protected RequisitionService $requisitionService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $status = $request->string('status')->toString() ?: 'active';

        $alerts = StockAlert::query()
            ->with('inventoryItem')
            ->when($status !== '', fn ($q) => $q->where('status', $status))
            ->orderByDesc('created_at')
            ->limit(50)
            ->get()
            ->map(fn (StockAlert $a) => $this->portal->serializeStockAlert($a))
            ->values();

        return response()->json(['items' => $alerts]);
    }

    public function show(StockAlert $stockAlert): JsonResponse
    {
        return response()->json($this->portal->serializeStockAlert($stockAlert));
    }

    public function autoReorder(Request $request, StockAlert $stockAlert): JsonResponse
    {
        if ($stockAlert->status !== 'active') {
            return response()->json(['message' => 'Only active alerts can trigger auto-reorder.'], 422);
        }

        $staff = $this->portal->staffForUser($request->user());
        abort_unless($staff, 403, 'Linked employee profile required.');

        $item = $stockAlert->inventoryItem;
        abort_unless($item, 422, 'Inventory item not found for this alert.');

        $requisition = ProcurementRequisition::query()->create([
            'requisition_number' => $this->requisitionService->generateRequisitionNumber(),
            'requesting_department_id' => $staff->department_id ?? 1,
            'requested_by' => $staff->id,
            'request_date' => now()->format('Y-m-d'),
            'requested_item' => "Reorder: {$item->item_name} ({$item->item_code})",
            'estimated_cost' => (float) $item->unit_cost * (int) $stockAlert->recommended_quantity,
            'budget_code' => null,
            'justification' => 'Automated low-stock reorder from mobile at '.now()->format('d M Y H:i').'.',
            'status' => 'draft',
        ]);

        $stockAlert->update([
            'requisition_id' => $requisition->id,
            'status' => 'closed',
            'closed_at' => now(),
        ]);

        $this->broadcastProcurementCounts();

        return response()->json([
            'message' => 'Draft requisition created for reorder.',
            'requisition_id' => $requisition->id,
            'alert' => $this->portal->serializeStockAlert($stockAlert->fresh()),
        ]);
    }
}
