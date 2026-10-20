<?php

namespace App\Http\Controllers\Api\Procurement\mobile;

use App\Http\Controllers\Controller;
use App\Models\InventoryItem;
use App\Services\Procurement\ProcurementMobilePortalService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class InventoryItemsController extends Controller
{
    public function __construct(
        protected ProcurementMobilePortalService $portal,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $search = $request->string('search')->toString();

        $items = InventoryItem::query()
            ->with('supplier')
            ->when($search !== '', function ($q) use ($search) {
                $q->where(function ($inner) use ($search) {
                    $inner->where('item_name', 'like', "%{$search}%")
                        ->orWhere('item_code', 'like', "%{$search}%");
                });
            })
            ->orderBy('item_name')
            ->limit(50)
            ->get()
            ->map(fn (InventoryItem $i) => $this->portal->serializeInventoryItem($i))
            ->values();

        return response()->json(['items' => $items]);
    }

    public function show(InventoryItem $inventoryItem): JsonResponse
    {
        return response()->json($this->portal->serializeInventoryItem($inventoryItem));
    }
}
