<?php

namespace App\Http\Controllers\Procurement;

use App\Http\Controllers\Controller;
use App\Models\Asset;
use App\Models\AssetMaintenance;
use App\Models\Staff;
use App\Services\Procurement\AssetMaintenanceService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AssetMaintenanceController extends Controller
{
    public function __construct(protected AssetMaintenanceService $service) {}

    public function index(Request $request): View
    {
        $query = AssetMaintenance::query()->with(['asset', 'completedBy']);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('asset_id')) {
            $query->where('asset_id', $request->asset_id);
        }

        $records = $query->orderByDesc('created_at')->paginate(25)->withQueryString();

        return view('procurement.asset-maintenance.index', compact('records'));
    }

    public function create(): View
    {
        return view('procurement.asset-maintenance.create', [
            'assets' => Asset::query()->select(['id', 'asset_number', 'asset_name', 'location_name'])->orderBy('asset_name')->get(),
            'staff' => Staff::query()->orderBy('first_name')->get(['id', 'first_name', 'surname']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'asset_id' => ['required', 'exists:assets,id'],
            'maintenance_type' => ['required', 'in:repair,scheduled,preventive,emergency'],
            'fault_description' => ['required', 'string'],
            'priority' => ['required', 'in:low,medium,high,emergency'],
            'scheduled_date' => ['required', 'date_format:d/m/Y'],
        ]);

        $validated['scheduled_date'] = \Carbon\Carbon::createFromFormat('d/m/Y', $validated['scheduled_date'])->format('Y-m-d');

        $record = $this->service->scheduleMaintenance($validated);

        return redirect()
            ->route('procurement.asset-maintenance.show', $record)
            ->with('status', 'Maintenance scheduled.');
    }

    public function show(AssetMaintenance $record): View
    {
        $record->load(['asset', 'completedBy']);

        return view('procurement.asset-maintenance.show', compact('record'));
    }

    public function complete(AssetMaintenance $record, AssetMaintenanceService $service, Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'work_done' => ['required', 'string'],
            'parts_cost' => ['nullable', 'numeric', 'min:0'],
            'labour_cost' => ['nullable', 'numeric', 'min:0'],
            'technician_name' => ['required', 'string', 'max:200'],
        ]);

        $service->completeMaintenance($record, $validated);

        return back()->with('status', 'Maintenance marked complete.');
    }
}
