<?php

namespace App\Http\Controllers\Api\Procurement\mobile;

use App\Http\Controllers\Api\Procurement\mobile\Concerns\PaginatesMobileProcurementLists;
use App\Http\Controllers\Controller;
use App\Models\Asset;
use App\Services\Procurement\ProcurementMobilePortalService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AssetsController extends Controller
{
    use PaginatesMobileProcurementLists;

    public function __construct(
        protected ProcurementMobilePortalService $portal,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $search = $request->string('search')->toString();

        $query = Asset::query()
            ->select(['id', 'asset_number', 'asset_name', 'location_name'])
            ->when($search !== '', function ($q) use ($search) {
                $q->where(function ($inner) use ($search) {
                    $inner->where('asset_number', 'like', "%{$search}%")
                        ->orWhere('asset_name', 'like', "%{$search}%")
                        ->orWhere('tag_number', 'like', "%{$search}%");
                });
            })
            ->orderBy('asset_number');

        return $this->paginatedItemsResponse(
            $request,
            $query,
            fn (Asset $a) => $this->portal->serializeAssetListItem($a),
        );
    }

    public function show(Asset $asset): JsonResponse
    {
        return response()->json($this->portal->serializeAsset($asset));
    }
}
