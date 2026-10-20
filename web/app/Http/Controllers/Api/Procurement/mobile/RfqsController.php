<?php

namespace App\Http\Controllers\Api\Procurement\mobile;

use App\Http\Controllers\Api\Procurement\mobile\Concerns\BroadcastsProcurementCounts;
use App\Http\Controllers\Controller;
use App\Models\Rfq;
use App\Services\Procurement\ProcurementMobilePortalService;
use App\Services\Procurement\RfqService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use InvalidArgumentException;

class RfqsController extends Controller
{
    use BroadcastsProcurementCounts;

    public function __construct(
        protected ProcurementMobilePortalService $portal,
        protected RfqService $rfqService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $search = $request->string('search')->toString();
        $status = $request->string('status')->toString();

        $items = Rfq::query()
            ->with(['requisition', 'awardedSupplier'])
            ->when($search !== '', fn ($q) => $q->where('rfq_number', 'like', "%{$search}%"))
            ->when($status !== '', fn ($q) => $q->where('status', $status))
            ->orderByDesc('created_at')
            ->limit(50)
            ->get()
            ->map(fn (Rfq $r) => $this->portal->serializeRfq($r))
            ->values();

        return response()->json(['items' => $items]);
    }

    public function show(Rfq $rfq): JsonResponse
    {
        return response()->json($this->portal->serializeRfq($rfq));
    }

    public function approveAward(Request $request, Rfq $rfq): JsonResponse
    {
        $validated = $request->validate([
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        try {
            $this->rfqService->approveAward($rfq, $validated['notes'] ?? null);
        } catch (InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        $this->broadcastProcurementCounts();

        return response()->json([
            'message' => 'Award approved.',
            'item' => $this->portal->serializeRfq($rfq->fresh()),
        ]);
    }

    public function rejectAward(Request $request, Rfq $rfq): JsonResponse
    {
        $validated = $request->validate([
            'notes' => ['required', 'string', 'max:1000'],
        ]);

        try {
            $this->rfqService->rejectAward($rfq, $validated['notes']);
        } catch (InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        $this->broadcastProcurementCounts();

        return response()->json([
            'message' => 'Award rejected.',
            'item' => $this->portal->serializeRfq($rfq->fresh()),
        ]);
    }
}
