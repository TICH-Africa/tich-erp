<?php

namespace App\Http\Controllers\Ceo;

use App\Http\Controllers\Controller;
use App\Models\Finance\FinancialAdjustment;
use App\Services\Finance\StudentFinanceService;
use App\Services\RBACService;
use App\Support\ExecutivePortal;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class StudentWaiverController extends Controller
{
    public function __construct(
        protected StudentFinanceService $finance,
        protected RBACService $rbac,
    ) {}

    public function index(Request $request): View
    {
        $status = $request->string('status')->toString() ?: 'pending';

        $adjustments = FinancialAdjustment::query()
            ->with(['student', 'requestedBy', 'approvedBy'])
            ->when('all' !== $status, fn ($query) => $query->where('status', $status))
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();

        return view('ceo.waivers.index', [
            'adjustments' => $adjustments,
            'status' => $status,
            'pending' => FinancialAdjustment::query()->where('status', 'pending')->count(),
            'pendingValue' => (float) FinancialAdjustment::query()->where('status', 'pending')->sum('amount'),
        ]);
    }

    public function show(FinancialAdjustment $waiver, Request $request): View
    {
        $waiver->load(['student', 'invoice', 'requestedBy', 'approvedBy']);

        return view('ceo.waivers.show', [
            'adjustment' => $waiver,
            'canDecide' => $this->decisionRefusal($request, $waiver) === null,
        ]);
    }

    public function approve(Request $request, FinancialAdjustment $waiver): RedirectResponse
    {
        if ($refusal = $this->decisionRefusal($request, $waiver)) {
            return back()->with('error', $refusal);
        }

        $this->finance->approveAdjustment($waiver, $this->deciderStaffId($request));

        return redirect()
            ->route('ceo.waivers.index', ['status' => 'approved'])
            ->with('success', 'Waiver approved and applied to the student account.');
    }

    public function reject(Request $request, FinancialAdjustment $waiver): RedirectResponse
    {
        if ($refusal = $this->decisionRefusal($request, $waiver)) {
            return back()->with('error', $refusal);
        }

        $this->finance->rejectAdjustment($waiver, $this->deciderStaffId($request));

        return redirect()
            ->route('ceo.waivers.index', ['status' => 'rejected'])
            ->with('success', 'Waiver rejected. The student still owes the amount on their account.');
    }

    /**
     * The executive desk decides waivers itself, and never on one it raised, so a
     * request cannot close through the person who made it.
     */
    private function decisionRefusal(Request $request, FinancialAdjustment $waiver): ?string
    {
        if (! ExecutivePortal::canAccessCeoPortal($request->user())) {
            return 'Only the executive office can decide a waiver here.';
        }

        if (ExecutivePortal::isReadOnly($request->user())) {
            return 'Your executive access is read-only, so another officer has to decide this waiver.';
        }

        if ($waiver->status !== 'pending') {
            return 'This waiver was already '.ucfirst($waiver->status).', so there is nothing left to decide.';
        }

        $deciders = array_filter([(int) $request->user()->id, (int) $request->user()->staff_id]);
        if (in_array((int) $waiver->requested_by, $deciders, true)
            && ! $this->rbac->isPlatformAdministrator($request->user())) {
            return 'You requested this waiver, so another officer has to decide it.';
        }

        return null;
    }

    private function deciderStaffId(Request $request): int
    {
        return (int) ($request->user()->staff_id ?? $request->user()->id);
    }
}
