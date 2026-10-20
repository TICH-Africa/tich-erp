<?php

namespace App\Http\Controllers\Api\Procurement\mobile;

use App\Http\Controllers\Api\Procurement\mobile\Concerns\BroadcastsProcurementCounts;
use App\Http\Controllers\Controller;
use App\Models\AssetDisposal;
use App\Services\Procurement\AssetDisposalService;
use App\Services\Procurement\ProcurementMobilePortalService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AssetDisposalsController extends Controller
{
    use BroadcastsProcurementCounts;

    public function __construct(
        protected ProcurementMobilePortalService $portal,
        protected AssetDisposalService $service,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $status = $request->string('status')->toString();

        $items = AssetDisposal::query()
            ->with(['asset', 'requestedBy'])
            ->when($status !== '', fn ($q) => $q->where('approval_status', $status))
            ->orderByDesc('created_at')
            ->limit(50)
            ->get()
            ->map(fn (AssetDisposal $r) => $this->portal->serializeAssetDisposal($r))
            ->values();

        return response()->json(['items' => $items]);
    }

    public function show(AssetDisposal $assetDisposal): JsonResponse
    {
        return response()->json($this->portal->serializeAssetDisposal($assetDisposal));
    }

    public function approve(AssetDisposal $assetDisposal): JsonResponse
    {
        if ($assetDisposal->approval_status === 'approved') {
            return response()->json(['message' => 'Already approved.'], 422);
        }

        $this->service->approveDisposal($assetDisposal);
        $this->broadcastProcurementCounts();

        return response()->json([
            'message' => 'Disposal approved.',
            'item' => $this->portal->serializeAssetDisposal($assetDisposal->fresh()),
        ]);
    }
}
