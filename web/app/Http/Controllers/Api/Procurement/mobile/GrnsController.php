<?php

namespace App\Http\Controllers\Api\Procurement\mobile;

use App\Http\Controllers\Api\Procurement\mobile\Concerns\BroadcastsProcurementCounts;
use App\Http\Controllers\Api\Procurement\mobile\Concerns\PaginatesMobileProcurementLists;
use App\Http\Controllers\Controller;
use App\Models\GoodsReceivedNote;
use App\Services\Procurement\GrnService;
use App\Services\Procurement\ProcurementMobilePortalService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GrnsController extends Controller
{
    use BroadcastsProcurementCounts;
    use PaginatesMobileProcurementLists;

    public function __construct(
        protected ProcurementMobilePortalService $portal,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $status = $request->string('status')->toString();
        $search = $request->string('search')->toString();

        $query = GoodsReceivedNote::query()
            ->with(['purchaseOrder:id,po_number,supplier_id', 'purchaseOrder.supplier:id,supplier_name'])
            ->when($status !== '', fn ($q) => $q->where('inspection_status', $status))
            ->when($search !== '', function ($q) use ($search) {
                $q->where(function ($inner) use ($search) {
                    $inner->where('grn_number', 'like', "%{$search}%")
                        ->orWhereHas('purchaseOrder', fn ($po) => $po->where('po_number', 'like', "%{$search}%"));
                });
            })
            ->orderByDesc('created_at');

        return $this->paginatedItemsResponse(
            $request,
            $query,
            fn (GoodsReceivedNote $g) => $this->portal->serializeGrnListItem($g),
        );
    }

    public function show(GoodsReceivedNote $grn): JsonResponse
    {
        return response()->json($this->portal->serializeGrn($grn));
    }

    public function completeInspection(Request $request, GoodsReceivedNote $grn, GrnService $grnService): JsonResponse
    {
        if ($grn->inspection_status !== 'pending') {
            return response()->json(['message' => 'Inspection is not pending.'], 422);
        }

        $validated = $request->validate([
            'inspection_notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $grnService->completeInspection($grn, $validated['inspection_notes'] ?? null);

        $this->broadcastProcurementCounts();

        return response()->json([
            'message' => 'Inspection completed.',
            'item' => $this->portal->serializeGrn($grn->fresh()),
        ]);
    }
}
