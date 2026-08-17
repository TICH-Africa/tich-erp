<?php

namespace App\Http\Controllers\Administration;

use App\Http\Controllers\Controller;
use App\Models\Administration\QuickbooksSyncLog;
use App\Services\Administration\AdministrationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class ProcurementLedgerController extends Controller
{
    public function __construct(protected AdministrationService $admin) {}

    public function procurementPay(): View
    {
        $snapshot = $this->admin->procurementToPaySnapshot();

        return view('administration.procurement-pay.index', [
            'snapshot' => $snapshot,
            'procurementUrl' => route('procurement.dashboard'),
        ]);
    }

    public function ledgerSync(): View
    {
        $logs = Schema::hasTable('admin_quickbooks_sync_logs')
            ? QuickbooksSyncLog::query()->latest()->paginate(20)
            : collect();

        return view('administration.ledger-sync.index', [
            'logs' => $logs,
            'enabled' => (bool) config('services.quickbooks.enabled', false),
        ]);
    }

    public function runSync(Request $request): RedirectResponse
    {
        try {
            $result = $this->admin->queuePendingPaymentsToQuickBooks($request->user()->id);
        } catch (\RuntimeException $e) {
            return back()->withErrors(['sync' => $e->getMessage()]);
        }

        return back()->with('status', "QuickBooks sync batch {$result['batch']}: {$result['synced']} synced, {$result['failed']} failed.");
    }
}
