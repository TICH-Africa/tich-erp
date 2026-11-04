<?php

namespace App\Http\Controllers\Api\Procurement\mobile;

use App\Http\Controllers\Api\Procurement\mobile\Concerns\BroadcastsProcurementCounts;
use App\Http\Controllers\Api\Procurement\mobile\Concerns\PaginatesMobileProcurementLists;
use App\Http\Controllers\Controller;
use App\Models\AssetAudit;
use App\Services\Procurement\AssetAuditService;
use App\Services\Procurement\ProcurementMobilePortalService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AssetAuditsController extends Controller
{
    use BroadcastsProcurementCounts;
    use PaginatesMobileProcurementLists;

    public function __construct(
        protected ProcurementMobilePortalService $portal,
        protected AssetAuditService $service,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $status = $request->string('status')->toString();
        $search = $request->string('search')->toString();

        $query = AssetAudit::query()
            ->select(['id', 'asset_id', 'auditor_id', 'status', 'created_at'])
            ->with(['asset:id,asset_number,asset_name', 'auditor:id,first_name,surname'])
            ->when($status !== '', fn ($q) => $q->where('status', $status))
            ->when($search !== '', function ($q) use ($search) {
                $q->whereHas('asset', function ($asset) use ($search) {
                    $asset->where('asset_number', 'like', "%{$search}%")
                        ->orWhere('asset_name', 'like', "%{$search}%");
                });
            })
            ->orderByDesc('created_at');

        return $this->paginatedItemsResponse(
            $request,
            $query,
            fn (AssetAudit $r) => $this->portal->serializeAssetAuditListItem($r),
        );
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
