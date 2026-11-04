<?php

namespace App\Http\Controllers\Api\Procurement\mobile;

use App\Http\Controllers\Api\Procurement\mobile\Concerns\PaginatesMobileProcurementLists;
use App\Http\Controllers\Controller;
use App\Models\InventoryItem;
use App\Services\Procurement\ProcurementMobilePortalService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class InventoryItemsController extends Controller
{
    use PaginatesMobileProcurementLists;

    public function __construct(
        protected ProcurementMobilePortalService $portal,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $search = $request->string('search')->toString();

        $query = InventoryItem::query()
            ->select(['id', 'item_code', 'item_name', 'current_stock', 'reorder_level'])
            ->when($search !== '', function ($q) use ($search) {
                $q->where(function ($inner) use ($search) {
                    $inner->where('item_name', 'like', "%{$search}%")
                        ->orWhere('item_code', 'like', "%{$search}%");
                });
            })
            ->orderBy('item_name');

        return $this->paginatedItemsResponse(
            $request,
            $query,
            fn (InventoryItem $i) => $this->portal->serializeInventoryItemListItem($i),
        );
    }

    public function show(InventoryItem $inventoryItem): JsonResponse
    {
        return response()->json($this->portal->serializeInventoryItem($inventoryItem));
    }
}
