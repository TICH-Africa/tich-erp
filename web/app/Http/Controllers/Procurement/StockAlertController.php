<?php

namespace App\Http\Controllers\Procurement;

use App\Http\Controllers\Controller;
use App\Models\InventoryItem;
use App\Models\ProcurementRequisition;
use App\Models\StockAlert;
use App\Services\Procurement\InventoryService;
use App\Services\Procurement\RequisitionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class StockAlertController extends Controller
{
    public function __construct(
        protected InventoryService $inventoryService,
        protected RequisitionService $requisitionService,
    ) {}

    public function index(Request $request): View
    {
        $query = StockAlert::query()->with(['inventoryItem'])->where('status', 'active');

        if ($request->filled('search')) {
            $query->whereHas('inventoryItem', fn ($q) => $q->where('item_name', 'like', "%{$request->search}%")->orWhere('item_code', 'like', "%{$request->search}%"));
        }

        $alerts = $query->orderByDesc('triggered_at')->paginate(25)->withQueryString();

        $stats = [
            'active' => StockAlert::query()->where('status', 'active')->count(),
            'closed' => StockAlert::query()->where('status', 'closed')->count(),
            'triggered_today' => StockAlert::query()->whereDate('triggered_at', today())->count(),
        ];

        return view('procurement.stock-alerts.index', compact('alerts', 'stats'));
    }

    public function show(StockAlert $alert): View
    {
        $alert->load(['inventoryItem', 'requisition']);

        return view('procurement.stock-alerts.show', compact('alert'));
    }

    public function autoReorder(StockAlert $alert): RedirectResponse
    {
        $item = $alert->inventoryItem;

        $requisition = ProcurementRequisition::query()->create([
            'requisition_number' => $this->requisitionService->generateRequisitionNumber(),
            'requesting_department_id' => auth()->user()->staff?->department_id ?? 1,
            'requested_by' => auth()->id(),
            'request_date' => now()->format('Y-m-d'),
            'requested_item' => "Reorder: {$item->item_name} ({$item->item_code})",
            'estimated_cost' => $item->unit_cost * $alert->recommended_quantity,
            'budget_code' => null,
            'justification' => "Automated low-stock reorder triggered at {$alert->triggered_at->format('d M Y')}.",
            'status' => 'draft',
        ]);

        $alert->update([
            'requisition_id' => $requisition->id,
            'status' => 'closed',
            'closed_at' => now(),
        ]);

        return redirect()
            ->route('procurement.requisitions.show', $requisition)
            ->with('status', 'Draft requisition created for reorder.');
    }
}
