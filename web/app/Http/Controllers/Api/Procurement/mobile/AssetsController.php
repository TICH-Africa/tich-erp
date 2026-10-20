<?php

namespace App\Http\Controllers\Api\Procurement\mobile;

use App\Http\Controllers\Controller;
use App\Models\Asset;
use App\Services\Procurement\ProcurementMobilePortalService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AssetsController extends Controller
{
    public function __construct(
        protected ProcurementMobilePortalService $portal,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $search = $request->string('search')->toString();

        $assets = Asset::query()
            ->with('custodian')
            ->when($search !== '', function ($q) use ($search) {
                $q->where(function ($inner) use ($search) {
                    $inner->where('asset_number', 'like', "%{$search}%")
                        ->orWhere('asset_name', 'like', "%{$search}%")
                        ->orWhere('tag_number', 'like', "%{$search}%");
                });
            })
            ->orderBy('asset_number')
            ->limit(50)
            ->get()
            ->map(fn (Asset $a) => $this->portal->serializeAsset($a))
            ->values();

        return response()->json(['items' => $assets]);
    }

    public function show(Asset $asset): JsonResponse
    {
        return response()->json($this->portal->serializeAsset($asset));
    }
}
