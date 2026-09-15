<?php

namespace App\Http\Controllers\Procurement;

use App\Http\Controllers\Controller;
use App\Models\GoodsReceivedNote;
use App\Models\PurchaseOrder;
use App\Models\Staff;
use App\Models\Supplier;
use App\Services\Procurement\GrnService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class GrnController extends Controller
{
    public function __construct(protected GrnService $grnService) {}

    public function index(Request $request): View
    {
        $query = GoodsReceivedNote::query()->with(['purchaseOrder.supplier', 'receivedBy']);

        if ($request->filled('status')) {
            $query->where('inspection_status', $request->status);
        }

        if ($request->filled('search')) {
            $query->where('grn_number', 'like', "%{$request->search}%")
                ->orWhereHas('purchaseOrder', fn ($q) => $q->where('po_number', 'like', "%{$request->search}%"));
        }

        $grns = $query->orderByDesc('created_at')->paginate(25)->withQueryString();

        $stats = [
            'total' => GoodsReceivedNote::query()->count(),
            'pending' => GoodsReceivedNote::query()->pending()->count(),
            'complete' => GoodsReceivedNote::query()->complete()->count(),
        ];

        return view('procurement.grns.index', compact('grns', 'stats', 'search'));
    }

    public function create(): View
    {
        return view('procurement.grns.create', [
            'purchaseOrders' => PurchaseOrder::query()->with('supplier')->whereIn('status', ['draft', 'approved', 'received'])->orderByDesc('created_at')->get(['id', 'po_number', 'total_amount']),
            'suppliers' => Supplier::query()->where('is_active', 1)->orderBy('supplier_name')->get(['id', 'supplier_name']),
            'staff' => Staff::query()->orderBy('first_name')->get(['id', 'first_name', 'surname']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'purchase_order_id' => ['required', 'exists:purchase_orders,id'],
            'supplier_id' => ['required', 'exists:suppliers,id'],
            'supplier_delivery_note' => ['nullable', 'string', 'max:100'],
            'received_date' => ['required', 'date_format:d/m/Y'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.item_name' => ['required', 'string', 'max:300'],
            'items.*.category' => ['nullable', 'string', 'max:50'],
            'items.*.classification' => ['required', 'in:asset,consumable'],
            'items.*.quantity_ordered' => ['required', 'numeric', 'min:0.0001'],
            'items.*.quantity_received' => ['required', 'numeric', 'min:0'],
            'items.*.unit_cost' => ['required', 'numeric', 'min:0'],
            'items.*.condition' => ['required', 'in:good,partial,damaged'],
        ]);

        $validated['received_date'] = \Carbon\Carbon::createFromFormat('d/m/Y', $validated['received_date'])->format('Y-m-d');

        $grn = $this->grnService->createGrn($validated);

        return redirect()
            ->route('procurement.grns.show', $grn)
            ->with('status', 'GRN created successfully.');
    }

    public function show(GoodsReceivedNote $grn): View
    {
        $grn->load(['purchaseOrder.supplier', 'receivedBy', 'items.supplier', 'items.purchaseOrder']);

        return view('procurement.grns.show', compact('grn'));
    }

    public function complete(GoodsReceivedNote $grn, Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'inspection_notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $grn = $this->grnService->completeInspection($grn, $validated['inspection_notes'] ?? null);

        return redirect()
            ->route('procurement.grns.show', $grn)
            ->with('status', 'GRN inspection complete and items registered.');
    }
}
