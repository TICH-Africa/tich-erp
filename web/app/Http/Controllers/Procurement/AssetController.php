<?php

namespace App\Http\Controllers\Procurement;

use App\Http\Controllers\Controller;
use App\Models\Asset;
use App\Models\PurchaseOrder;
use App\Models\Staff;
use App\Models\Supplier;
use App\Services\Procurement\AssetMovementService;
use App\Support\Pagination;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AssetController extends Controller
{
    public function __construct(protected AssetMovementService $movements) {}
    public function index(Request $request): View
    {
        $query = Asset::query()->with(['supplier', 'purchaseOrder', 'custodian']);

        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('asset_name', 'like', "%{$request->search}%")
                    ->orWhere('asset_number', 'like', "%{$request->search}%")
                    ->orWhere('asset_category', 'like', "%{$request->search}%");
            });
        }

        if ($request->filled('category')) {
            $query->where('asset_category', $request->category);
        }

        if ($request->filled('status')) {
            $query->where('asset_status', $request->status);
        }

        $assets = $query->orderByDesc('created_at')->paginate(Pagination::perPage($request))->withQueryString();

        $stats = [
            'total' => Asset::query()->count(),
            'active' => Asset::query()->active()->count(),
            'maintenance' => Asset::query()->underMaintenance()->count(),
            'total_cost' => Asset::query()->sum('acquisition_cost'),
        ];

        return view('procurement.assets.index', compact('assets', 'stats'));
    }

    public function create(): View
    {
        return view('procurement.assets.create', [
            'suppliers' => Supplier::query()->orderBy('supplier_name')->get(['id', 'supplier_name']),
            'purchaseOrders' => PurchaseOrder::query()->with('supplier')->whereIn('status', ['draft', 'approved', 'received'])->orderByDesc('created_at')->get(['id', 'po_number']),
            'requisitions' => \App\Models\ProcurementRequisition::query()->whereIn('status', ['draft', 'submitted', 'hod_approved', 'finance_approved', 'ceo_approved'])->orderByDesc('created_at')->get(['id', 'requisition_number']),
            'custodians' => Staff::query()->orderBy('first_name')->get(['id', 'first_name', 'surname', 'job_title']),
        ]);
    }

    public function store(Request $request): \Illuminate\Http\RedirectResponse
    {
        $presetCategories = ['furniture', 'ict_hardware', 'equipment', 'vehicle', 'building', 'other'];

        $validated = $request->validate([
            'asset_name' => ['required', 'string', 'max:300'],
            'asset_category' => ['required', 'string', 'in:'.implode(',', $presetCategories)],
            'asset_category_other' => ['nullable', 'required_if:asset_category,other', 'string', 'max:50'],
            'serial_number' => ['nullable', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:500'],
            'acquisition_date' => ['required', 'date_format:d/m/Y'],
            'acquisition_cost' => ['required', 'numeric', 'min:0'],
            'supplier_id' => ['required', 'exists:suppliers,id'],
            'purchase_order_id' => ['required', 'exists:purchase_orders,id'],
            'useful_life_years' => ['required', 'integer', 'min:1'],
            'depreciation_method' => ['required', 'in:straight_line,declining_balance'],
            'salvage_value' => ['nullable', 'numeric', 'min:0'],
            'warranty_expiry_date' => ['nullable', 'date'],
            'location_name' => ['nullable', 'string', 'max:255'],
            'building' => ['nullable', 'string', 'max:100'],
            'room' => ['nullable', 'string', 'max:100'],
            'custodian_id' => ['required', 'exists:staff,id'],
            'tag_number' => ['nullable', 'string', 'max:50'],
            'procurement_requisition_id' => ['nullable', 'exists:procurement_requisitions,id'],
        ]);

        if ($validated['asset_category'] === 'other') {
            $validated['asset_category'] = trim((string) ($validated['asset_category_other'] ?? ''));
        }
        unset($validated['asset_category_other']);

        $validated['acquisition_date'] = \Carbon\Carbon::createFromFormat('d/m/Y', $validated['acquisition_date'])->format('Y-m-d');
        $validated['warranty_expiry_date'] = $validated['warranty_expiry_date'] ?? null;

        $asset = \App\Services\Procurement\AssetService::registerAsset($validated);

        return redirect()
            ->route('procurement.assets.show', $asset)
            ->with('status', 'Asset registered successfully.');
    }

    public function show(Asset $asset): View
    {
        $asset->load(['supplier', 'purchaseOrder', 'custodian', 'movements', 'disposals', 'audits']);

        return view('procurement.assets.show', [
            'asset' => $asset,
            'custodians' => Staff::query()->orderBy('first_name')->get(['id', 'first_name', 'surname', 'job_title']),
        ]);
    }

    public function transfer(Request $request, Asset $asset): RedirectResponse
    {
        $validated = $request->validate([
            'movement_type' => ['required', 'in:transfer,relocation,assignment'],
            'to_location' => ['required', 'string', 'max:255'],
            'building' => ['nullable', 'string', 'max:100'],
            'room' => ['nullable', 'string', 'max:100'],
            'custodian_id' => ['nullable', 'exists:staff,id'],
            'movement_date' => ['required', 'date'],
            'reason' => ['required', 'string', 'max:1000'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $validated['from_location'] = $asset->location_name;

        $this->movements->transferAsset($asset, $validated);

        return redirect()
            ->route('procurement.assets.show', $asset)
            ->with('status', 'Asset transferred successfully.');
    }
}
