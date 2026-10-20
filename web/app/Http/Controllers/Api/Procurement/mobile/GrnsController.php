<?php

namespace App\Http\Controllers\Api\Procurement\mobile;

use App\Http\Controllers\Api\Procurement\mobile\Concerns\BroadcastsProcurementCounts;
use App\Http\Controllers\Controller;
use App\Models\GoodsReceivedNote;
use App\Services\Procurement\GrnService;
use App\Services\Procurement\ProcurementMobilePortalService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GrnsController extends Controller
{
    use BroadcastsProcurementCounts;

    public function __construct(
        protected ProcurementMobilePortalService $portal,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $status = $request->string('status')->toString();

        $grns = GoodsReceivedNote::query()
            ->with(['purchaseOrder.supplier', 'receivedBy'])
            ->when($status !== '', fn ($q) => $q->where('inspection_status', $status))
            ->orderByDesc('created_at')
            ->limit(50)
            ->get()
            ->map(fn (GoodsReceivedNote $g) => $this->portal->serializeGrn($g))
            ->values();

        return response()->json(['items' => $grns]);
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
