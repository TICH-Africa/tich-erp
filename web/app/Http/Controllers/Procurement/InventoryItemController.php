<?php

namespace App\Http\Controllers\Procurement;

use App\Http\Controllers\Controller;
use App\Models\InventoryItem;
use App\Models\Supplier;
use App\Support\Pagination;
use Illuminate\Http\Request;
use Illuminate\View\View;

class InventoryItemController extends Controller
{
    public function index(Request $request): View
    {
        $query = InventoryItem::query();

        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('item_name', 'like', "%{$request->search}%")
                    ->orWhere('item_code', 'like', "%{$request->search}%");
            });
        }

        if ($request->filled('category')) {
            $query->where('category', $request->category);
        }

        $items = $query->orderBy('item_name')->paginate(Pagination::perPage($request))->withQueryString();

        $stats = [
            'total' => InventoryItem::query()->count(),
            'low_stock' => InventoryItem::query()->whereRaw('current_stock <= reorder_level')->count(),
            'active' => InventoryItem::query()->where('is_active', 1)->count(),
        ];

        return view('procurement.inventory.index', compact('items', 'stats'));
    }

    public function create(): View
    {
        return view('procurement.inventory.create', [
            'suppliers' => Supplier::query()->orderBy('supplier_name')->get(['id', 'supplier_name']),
        ]);
    }

    public function store(Request $request): \Illuminate\Http\RedirectResponse
    {
        $validated = $request->validate([
            'item_code' => ['required', 'string', 'max:50', 'unique:inventory_items,item_code'],
            'item_name' => ['required', 'string', 'max:300'],
            'category' => ['nullable', 'string', 'max:100'],
            'unit_of_measure' => ['required', 'string', 'max:30'],
            'current_stock' => ['required', 'integer', 'min:0'],
            'reorder_level' => ['required', 'integer', 'min:0'],
            'minimum_stock' => ['nullable', 'integer', 'min:0'],
            'maximum_stock' => ['nullable', 'integer', 'min:0'],
            'unit_cost' => ['required', 'numeric', 'min:0'],
            'supplier_id' => ['required', 'exists:suppliers,id'],
            'store_location' => ['nullable', 'string', 'max:100'],
        ]);

        InventoryItem::query()->create($validated);

        return redirect()
            ->route('procurement.inventory-items.show', InventoryItem::latest('id')->first())
            ->with('status', 'Inventory item created successfully.');
    }

    public function show(InventoryItem $item): View
    {
        $item->load(['supplier', 'transactions', 'alerts']);

        return view('procurement.inventory.show', compact('item'));
    }
}
