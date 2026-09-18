<?php

namespace App\Http\Controllers\Procurement;

use App\Http\Controllers\Controller;
use App\Models\Asset;
use App\Models\AssetAudit;
use App\Models\Staff;
use App\Services\Procurement\AssetAuditService;
use App\Support\Pagination;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AssetAuditController extends Controller
{
    public function __construct(protected AssetAuditService $service) {}

    public function index(Request $request): View
    {
        $query = AssetAudit::query()->with(['asset', 'auditor', 'reviewer']);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $records = $query->orderByDesc('created_at')->paginate(Pagination::perPage($request))->withQueryString();

        return view('procurement.asset-audits.index', compact('records'));
    }

    public function create(): View
    {
        return view('procurement.asset-audits.create', [
            // Assets default to status "new"; do not limit to "active" only.
            'assets' => Asset::query()
                ->select(['id', 'asset_number', 'asset_name', 'location_name', 'asset_status'])
                ->where('asset_status', '!=', 'disposed')
                ->orderBy('asset_name')
                ->get(),
            'staff' => Staff::query()->orderBy('first_name')->get(['id', 'first_name', 'surname']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'asset_id' => ['required', 'exists:assets,id'],
            'auditor_id' => ['required', 'exists:staff,id'],
            'verification_status' => ['required', 'in:verified,missing,damaged,transferred'],
            'condition' => ['required', 'in:excellent,good,fair,poor,damaged'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $record = $this->service->submitVerification($validated);

        return redirect()
            ->route('procurement.asset-audits.show', $record)
            ->with('status', 'Verification submitted.');
    }

    public function show(AssetAudit $assetAudit): View
    {
        $assetAudit->load(['asset', 'auditor', 'reviewer']);

        return view('procurement.asset-audits.show', ['record' => $assetAudit]);
    }

    public function review(AssetAudit $assetAudit, Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'verification_status' => ['required', 'in:verified,missing,damaged,transferred'],
            'condition' => ['required', 'in:excellent,good,fair,poor,damaged'],
            'review_notes' => ['nullable', 'string'],
        ]);

        $this->service->reviewVerification($assetAudit, $validated);

        return back()->with('status', 'Verification reviewed.');
    }
}
