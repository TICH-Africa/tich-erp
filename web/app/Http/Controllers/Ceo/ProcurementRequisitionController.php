<?php

namespace App\Http\Controllers\Ceo;

use App\Http\Controllers\Controller;
use App\Models\ProcurementRequisition;
use App\Services\Procurement\RequisitionService;
use App\Services\Sidebar\CeoSidebarNotificationService;
use App\Services\StaffPortalService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class ProcurementRequisitionController extends Controller
{
    public function __construct(
        protected RequisitionService $requisitions,
        protected StaffPortalService $staffPortal,
        protected CeoSidebarNotificationService $sidebar,
    ) {}

    public function index(Request $request): View
    {
        $status = $request->string('status')->toString() ?: 'awaiting_ceo';
        $search = $request->string('search')->toString();

        $items = Schema::hasTable('procurement_requisitions')
            ? ProcurementRequisition::query()
                ->with(['department', 'requester'])
                ->when($status === 'awaiting_ceo', fn ($q) => $q->pendingCeoApproval())
                ->when($status !== 'awaiting_ceo' && $status !== 'all', fn ($q) => $q->where('status', $status))
                ->when($search !== '', function ($q) use ($search) {
                    $q->where(function ($inner) use ($search) {
                        $inner->where('requisition_number', 'like', "%{$search}%")
                            ->orWhere('requested_item', 'like', "%{$search}%")
                            ->orWhere('justification', 'like', "%{$search}%")
                            ->orWhere('budget_code', 'like', "%{$search}%");
                    });
                })
                ->orderByDesc('request_date')
                ->orderByDesc('id')
                ->paginate(20)
                ->withQueryString()
            : collect();

        return view('ceo.procurement.index', [
            'items' => $items,
            'status' => $status,
            'search' => $search,
        ]);
    }

    public function show(ProcurementRequisition $requisition): View
    {
        $requisition->load(['department', 'requester', 'hodApprover', 'financeApprover', 'ceoApprover']);
        $budgetCheck = $this->requisitions->verifyBudget($requisition);

        return view('ceo.procurement.show', [
            'requisition' => $requisition,
            'budgetCheck' => $budgetCheck,
            'canDecide' => $this->requisitions->requiresCeoApproval($requisition),
        ]);
    }

    public function approve(Request $request, ProcurementRequisition $requisition): RedirectResponse
    {
        $staff = $this->staffPortal->staffForUser($request->user());
        abort_unless($staff, 403, 'CEO staff profile required.');

        $validated = $request->validate([
            'comments' => ['nullable', 'string', 'max:1000'],
        ]);

        try {
            $this->requisitions->approve($requisition, $staff, 'ceo', $validated['comments'] ?? null);
        } catch (\InvalidArgumentException|\RuntimeException $e) {
            return back()->withInput()->withErrors(['workflow' => $e->getMessage()]);
        }

        $this->sidebar->broadcastCounts();

        return redirect()
            ->route('ceo.procurement.show', $requisition)
            ->with('status', 'Requisition approved by CEO.');
    }

    public function reject(Request $request, ProcurementRequisition $requisition): RedirectResponse
    {
        $staff = $this->staffPortal->staffForUser($request->user());
        abort_unless($staff, 403, 'CEO staff profile required.');

        $validated = $request->validate([
            'comments' => ['required', 'string', 'max:1000'],
        ], [
            'comments.required' => 'Please provide a reason for rejection.',
        ]);

        try {
            $this->requisitions->reject($requisition, $staff, 'ceo', $validated['comments']);
        } catch (\InvalidArgumentException|\RuntimeException $e) {
            return back()->withInput()->withErrors(['workflow' => $e->getMessage()]);
        }

        $this->sidebar->broadcastCounts();

        return redirect()
            ->route('ceo.procurement.index')
            ->with('status', 'Requisition rejected.');
    }
}
