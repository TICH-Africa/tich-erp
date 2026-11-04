<?php

namespace App\Http\Controllers\Api\Procurement\mobile;

use App\Http\Controllers\Api\Procurement\mobile\Concerns\BroadcastsProcurementCounts;
use App\Http\Controllers\Api\Procurement\mobile\Concerns\PaginatesMobileProcurementLists;
use App\Http\Controllers\Controller;
use App\Models\Asset;
use App\Models\AssetDisposal;
use App\Services\Procurement\AssetDisposalService;
use App\Services\Procurement\ProcurementMobilePortalService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use RuntimeException;

class AssetDisposalsController extends Controller
{
    use BroadcastsProcurementCounts;
    use PaginatesMobileProcurementLists;

    public function __construct(
        protected ProcurementMobilePortalService $portal,
        protected AssetDisposalService $service,
    ) {}

    public function formOptions(Request $request): JsonResponse
    {
        $search = trim($request->string('search')->toString());
        $cacheKey = $search === ''
            ? 'procurement.mobile.asset_disposal_form_options.v1'
            : 'procurement.mobile.asset_disposal_form_options.search.'.md5($search);

        return response()->json(Cache::remember($cacheKey, 45, function () use ($search) {
            $assets = Asset::query()
                ->select(['id', 'asset_number', 'asset_name', 'current_value'])
                ->where('asset_status', '!=', 'disposed')
                ->when($search !== '', function ($q) use ($search) {
                    $q->where(function ($inner) use ($search) {
                        $inner->where('asset_number', 'like', "%{$search}%")
                            ->orWhere('asset_name', 'like', "%{$search}%");
                    });
                })
                ->orderBy('asset_name')
                ->limit($search === '' ? 80 : 60)
                ->get()
                ->map(fn (Asset $asset) => [
                    'id' => $asset->id,
                    'asset_number' => $asset->asset_number,
                    'asset_name' => $asset->asset_name,
                    'current_value' => (float) $asset->current_value,
                    'label' => "{$asset->asset_number} - {$asset->asset_name}",
                ])
                ->values();

            return [
                'assets' => $assets,
                'disposal_types' => [
                    ['value' => 'write_off', 'label' => 'Write-off'],
                    ['value' => 'donation', 'label' => 'Donation'],
                    ['value' => 'auction', 'label' => 'Auction'],
                ],
            ];
        }));
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'asset_id' => ['required', 'exists:assets,id'],
            'disposal_type' => ['required', 'in:write_off,donation,auction'],
            'disposal_value' => ['nullable', 'numeric', 'min:0'],
            'disposal_date' => ['nullable', 'date'],
            'reason' => ['required', 'string', 'max:2000'],
        ]);

        $validated['disposed_value'] = $validated['disposal_value'] ?? 0.0;
        unset($validated['disposal_value']);

        try {
            $record = $this->service->requestDisposal($validated);
        } catch (RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        $record->load(['asset', 'requestedBy']);
        $this->broadcastProcurementCounts();

        return response()->json([
            'message' => 'Disposal request submitted.',
            'item' => $this->portal->serializeAssetDisposal($record),
        ], 201);
    }

    public function index(Request $request): JsonResponse
    {
        $status = $request->string('status')->toString();
        $search = $request->string('search')->toString();

        $query = AssetDisposal::query()
            ->select(['id', 'asset_id', 'disposal_type', 'approval_status', 'created_at'])
            ->with(['asset:id,asset_number,asset_name'])
            ->when($status !== '', fn ($q) => $q->where('approval_status', $status))
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
            fn (AssetDisposal $r) => $this->portal->serializeAssetDisposalListItem($r),
        );
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
