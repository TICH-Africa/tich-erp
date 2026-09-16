<?php

namespace App\Http\Controllers\Procurement;

use App\Http\Controllers\Controller;
use App\Models\Asset;
use App\Models\AssetMovement;
use App\Models\Staff;
use App\Services\Procurement\AssetMovementService;
use Illuminate\Http\RedirectResponse;
use App\Support\Pagination;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AssetMovementController extends Controller
{
    public function __construct(protected AssetMovementService $service) {}

    public function index(Request $request): View
    {
        $query = AssetMovement::query()->with(['asset', 'requestedBy', 'approvedBy']);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $movements = $query->orderByDesc('created_at')->paginate(Pagination::perPage($request))->withQueryString();

        return view('procurement.asset-movements.index', compact('movements'));
    }

    public function create(): View
    {
        return view('procurement.asset-movements.create', [
            'assets' => Asset::query()->select(['id', 'asset_number', 'asset_name', 'location_name'])->orderBy('asset_name')->get(),
            'staff' => Staff::query()->orderBy('first_name')->get(['id', 'first_name', 'surname']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'asset_id' => ['required', 'exists:assets,id'],
            'from_location' => ['nullable', 'string', 'max:255'],
            'to_location' => ['required', 'string', 'max:255'],
            'reason' => ['required', 'string', 'max:2000'],
            'movement_type' => ['required', 'in:transfer,relocation,assignment'],
            'movement_date' => ['required', 'date_format:d/m/Y'],
        ]);

        $validated['movement_date'] = \Carbon\Carbon::createFromFormat('d/m/Y', $validated['movement_date'])->format('Y-m-d');

        $movement = $this->service->submitMovement($validated);

        return redirect()
            ->route('procurement.asset-movements.show', $movement)
            ->with('status', 'Movement request submitted.');
    }

    public function show(AssetMovement $movement): View
    {
        $movement->load(['asset', 'requestedBy', 'approvedBy']);

        return view('procurement.asset-movements.show', compact('movement'));
    }

    public function approve(AssetMovement $movement, AssetMovementService $service): RedirectResponse
    {
        if ($movement->approval_status === 'approved') {
            return back()->withErrors(['status' => 'Already approved.']);
        }

        $service->approveMovement($movement);

        $asset = $movement->asset;
        if ($asset && $movement->to_location) {
            $asset->update(['location_name' => $movement->to_location]);
        }

        return back()->with('status', 'Movement approved. Asset location updated.');
    }
}
