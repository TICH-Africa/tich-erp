<?php

namespace App\Http\Controllers\Api\Procurement\mobile;

use App\Http\Controllers\Api\Procurement\mobile\Concerns\BroadcastsProcurementCounts;
use App\Http\Controllers\Controller;
use App\Models\AssetAudit;
use App\Services\Procurement\AssetAuditService;
use App\Services\Procurement\ProcurementMobilePortalService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AssetAuditsController extends Controller
{
    use BroadcastsProcurementCounts;

    public function __construct(
        protected ProcurementMobilePortalService $portal,
        protected AssetAuditService $service,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $status = $request->string('status')->toString();

        $items = AssetAudit::query()
            ->with(['asset', 'auditor'])
            ->when($status !== '', fn ($q) => $q->where('status', $status))
            ->orderByDesc('created_at')
            ->limit(50)
            ->get()
            ->map(fn (AssetAudit $r) => $this->portal->serializeAssetAudit($r))
            ->values();

        return response()->json(['items' => $items]);
    }

    public function show(AssetAudit $assetAudit): JsonResponse
    {
        return response()->json($this->portal->serializeAssetAudit($assetAudit));
    }

    public function review(Request $request, AssetAudit $assetAudit): JsonResponse
    {
        $validated = $request->validate([
            'verification_status' => ['required', 'in:verified,missing,damaged,transferred'],
            'condition' => ['required', 'in:excellent,good,fair,poor,damaged'],
            'review_notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $this->service->reviewVerification($assetAudit, $validated);
        $this->broadcastProcurementCounts();

        return response()->json([
            'message' => 'Audit reviewed.',
            'item' => $this->portal->serializeAssetAudit($assetAudit->fresh()),
        ]);
    }
}
