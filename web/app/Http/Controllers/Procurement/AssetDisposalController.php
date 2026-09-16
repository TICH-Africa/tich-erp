<?php

namespace App\Http\Controllers\Procurement;

use App\Http\Controllers\Controller;
use App\Models\Asset;
use App\Models\AssetDisposal;
use App\Models\Staff;
use App\Services\Procurement\AssetDisposalService;
use Illuminate\Http\RedirectResponse;
use App\Support\Pagination;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AssetDisposalController extends Controller
{
    public function __construct(protected AssetDisposalService $service) {}

    public function index(Request $request): View
    {
        $query = AssetDisposal::query()->with(['asset', 'requestedBy', 'approvedBy']);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $records = $query->orderByDesc('created_at')->paginate(Pagination::perPage($request))->withQueryString();

        return view('procurement.asset-disposals.index', compact('records'));
    }

    public function create(): View
    {
        return view('procurement.asset-disposals.create', [
            'assets' => Asset::query()->select(['id', 'asset_number', 'asset_name', 'current_value'])->where('asset_status', '!=', 'disposed')->orderBy('asset_name')->get(),
            'staff' => Staff::query()->orderBy('first_name')->get(['id', 'first_name', 'surname']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'asset_id' => ['required', 'exists:assets,id'],
            'disposal_type' => ['required', 'in:write_off,donation,auction'],
            'disposal_value' => ['nullable', 'numeric', 'min:0'],
            'reason' => ['required', 'string', 'max:2000'],
        ]);

        $validated['disposed_value'] = $validated['disposal_value'] ?? 0.0;
        unset($validated['disposal_value']);

        $record = $this->service->requestDisposal($validated);

        return redirect()
            ->route('procurement.asset-disposals.show', $record)
            ->with('status', 'Disposal request submitted.');
    }

    public function show(AssetDisposal $record): View
    {
        $record->load(['asset', 'requestedBy', 'approvedBy']);

        return view('procurement.asset-disposals.show', compact('record'));
    }

    public function approve(AssetDisposal $record, AssetDisposalService $service): RedirectResponse
    {
        if ($record->approval_status === 'approved') {
            return back()->withErrors(['status' => 'Already approved.']);
        }

        $service->approveDisposal($record);

        $asset = $record->asset;
        if ($asset) {
            $asset->update([
                'asset_status' => 'disposed',
                'disposed_date' => $record->disposal_date ?? now()->format('Y-m-d'),
                'disposed_value' => $record->disposed_value,
                'disposed_reason' => $record->reason,
            ]);
        }

        return back()->with('status', 'Disposal approved. Asset marked disposed.');
    }
}
