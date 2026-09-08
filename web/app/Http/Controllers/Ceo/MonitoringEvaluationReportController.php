<?php

namespace App\Http\Controllers\Ceo;

use App\Http\Controllers\Controller;
use App\Models\Me\MeDepartmentHealthScore;
use App\Models\Me\MeQuarterlyReport;
use App\Services\Me\MeQuarterlyReportService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MonitoringEvaluationReportController extends Controller
{
    public function __construct(
        protected MeQuarterlyReportService $reports,
    ) {}

    public function index(): View
    {
        $items = MeQuarterlyReport::query()
            ->with(['department', 'quarter', 'technicalPlan', 'lines'])
            ->whereIn('status', ['ceo_delivered', 'me_verified'])
            ->orderByDesc('ceo_delivered_at')
            ->orderByDesc('id')
            ->paginate(15);

        $health = MeDepartmentHealthScore::query()
            ->with('department')
            ->orderByDesc('calculated_at')
            ->limit(12)
            ->get();

        return view('ceo.me.index', compact('items', 'health'));
    }

    public function show(MeQuarterlyReport $report): View
    {
        abort_unless(in_array($report->status, ['ceo_delivered', 'me_verified'], true), 404);
        $report->load(['department', 'quarter', 'lines', 'technicalPlan', 'meVerifier', 'submitter']);

        return view('ceo.me.show', compact('report'));
    }

    public function sign(Request $request, MeQuarterlyReport $report): RedirectResponse
    {
        $data = $request->validate([
            'ceo_signature' => ['required', 'string', 'max:300'],
            'ceo_notes' => ['nullable', 'string', 'max:3000'],
        ]);

        try {
            $this->reports->ceoSign(
                $report,
                $request->user(),
                $data['ceo_signature'],
                $data['ceo_notes'] ?? null
            );
        } catch (\RuntimeException $e) {
            return back()->withErrors(['report' => $e->getMessage()]);
        }

        return back()->with('status', 'Executive digital signature recorded on the M&E report.');
    }
}
