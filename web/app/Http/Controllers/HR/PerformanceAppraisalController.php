<?php

namespace App\Http\Controllers\HR;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\HrAppraisal;
use App\Models\HrAppraisalCycle;
use App\Models\HrCorporateGoal;
use App\Models\Staff;
use App\Services\HrPerformanceAppraisalService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PerformanceAppraisalController extends Controller
{
    public function __construct(
        protected HrPerformanceAppraisalService $appraisals,
    ) {}

    public function index(Request $request): View
    {
        $filters = $request->validate([
            'cycle_id' => ['nullable', 'integer', 'exists:hr_appraisal_cycles,id'],
            'status' => ['nullable', 'string', 'max:40'],
            'search' => ['nullable', 'string', 'max:120'],
        ]);

        if (! isset($filters['status'])) {
            $filters['status'] = 'pending_hr';
        }

        $cycles = HrAppraisalCycle::query()->orderByDesc('fiscal_year')->orderByDesc('quarter')->get();

        return view('hr.appraisals.index', [
            'appraisals' => $this->appraisals->hrIndex($filters),
            'filters' => $filters,
            'cycles' => $cycles,
            'statuses' => config('tich-performance-appraisals.statuses', []),
            'ratingScale' => $this->appraisals->ratingScale(),
        ]);
    }

    public function cycles(): View
    {
        $cycles = HrAppraisalCycle::query()
            ->withCount('appraisals')
            ->orderByDesc('fiscal_year')
            ->orderByDesc('quarter')
            ->get()
            ->map(function (HrAppraisalCycle $cycle) {
                $cycle->setAttribute('stats', $this->appraisals->cycleCompletionStats($cycle));

                return $cycle;
            });

        return view('hr.appraisals.cycles', [
            'cycles' => $cycles,
            'cycleStatuses' => config('tich-performance-appraisals.cycle_statuses', []),
        ]);
    }

    public function storeCycle(Request $request): RedirectResponse
    {
        $hr = $this->hrStaff($request);
        $data = $request->validate([
            'name' => ['required', 'string', 'max:200'],
            'fiscal_year' => ['required', 'integer', 'min:2020', 'max:2100'],
            'quarter' => ['required', 'integer', 'min:1', 'max:4'],
            'period_start' => ['required', 'date'],
            'period_end' => ['required', 'date', 'after_or_equal:period_start'],
            'instructions' => ['nullable', 'string', 'max:5000'],
        ]);

        try {
            $cycle = $this->appraisals->createCycle($hr, $data);
        } catch (\Throwable $e) {
            return back()->withInput()->withErrors(['cycle' => $e->getMessage()]);
        }

        return redirect()
            ->route('hr.appraisals.cycles')
            ->with('status', 'Appraisal cycle created as draft. Open it when ready to generate staff shells.');
    }

    public function openCycle(Request $request, HrAppraisalCycle $cycle): RedirectResponse
    {
        $hr = $this->hrStaff($request);

        try {
            $this->appraisals->openCycle($cycle, $hr);
        } catch (\RuntimeException $e) {
            return back()->withErrors(['cycle' => $e->getMessage()]);
        }

        return back()->with('status', 'Cycle opened and appraisal shells created for active staff.');
    }

    public function startCalibration(Request $request, HrAppraisalCycle $cycle): RedirectResponse
    {
        $hr = $this->hrStaff($request);

        try {
            $this->appraisals->startCalibration($cycle, $hr);
        } catch (\RuntimeException $e) {
            return back()->withErrors(['cycle' => $e->getMessage()]);
        }

        return back()->with('status', 'Calibration stage started. Adjust scores as needed, then sign off.');
    }

    public function closeCycle(Request $request, HrAppraisalCycle $cycle): RedirectResponse
    {
        $hr = $this->hrStaff($request);

        try {
            $this->appraisals->closeCycle($cycle, $hr);
        } catch (\RuntimeException $e) {
            return back()->withErrors(['cycle' => $e->getMessage()]);
        }

        return back()->with('status', 'Appraisal cycle closed.');
    }

    public function show(HrAppraisal $appraisal): View
    {
        $appraisal->load([
            'staff.department',
            'lineManager',
            'cycle',
            'goals.corporateGoal',
            'competencies',
            'hrSignedBy',
            'calibratedBy',
            'managerReviewedBy',
        ]);

        return view('hr.appraisals.show', [
            'appraisal' => $appraisal,
            'ratingScale' => $this->appraisals->ratingScale(),
            'statuses' => config('tich-performance-appraisals.statuses', []),
        ]);
    }

    public function calibrate(Request $request, HrAppraisal $appraisal): RedirectResponse
    {
        $hr = $this->hrStaff($request);
        $data = $request->validate([
            'calibrated_score' => ['required', 'numeric', 'min:1', 'max:5'],
            'calibration_reason' => ['required', 'string', 'max:2000'],
        ]);

        try {
            $this->appraisals->calibrateAppraisal(
                $appraisal,
                $hr,
                (float) $data['calibrated_score'],
                $data['calibration_reason']
            );
        } catch (\RuntimeException $e) {
            return back()->withErrors(['calibration' => $e->getMessage()]);
        }

        return back()->with('status', 'Calibrated score saved.');
    }

    public function signOff(Request $request, HrAppraisal $appraisal): RedirectResponse
    {
        $hr = $this->hrStaff($request);
        $data = $request->validate([
            'hr_comments' => ['nullable', 'string', 'max:5000'],
            'staff_agrees' => ['nullable', 'boolean'],
        ]);

        try {
            $this->appraisals->hrSignOff($appraisal, $hr, $data);
        } catch (\RuntimeException $e) {
            return back()->withErrors(['signoff' => $e->getMessage()]);
        }

        return redirect()
            ->route('hr.appraisals.index', ['status' => 'pending_hr'])
            ->with('status', 'Appraisal signed off and archived to the employee record.');
    }

    public function corporateGoals(Request $request): View
    {
        $cycleId = $request->integer('cycle_id') ?: null;

        $goals = HrCorporateGoal::query()
            ->with(['cycle', 'parent'])
            ->when($cycleId, fn ($q) => $q->where('cycle_id', $cycleId)->orWhereNull('cycle_id'))
            ->orderByDesc('is_active')
            ->orderBy('title')
            ->get();

        return view('hr.appraisals.corporate-goals', [
            'goals' => $goals,
            'cycles' => HrAppraisalCycle::query()->orderByDesc('fiscal_year')->orderByDesc('quarter')->get(),
            'departments' => Department::query()->orderBy('dept_name')->get(['id', 'dept_name']),
            'cycleId' => $cycleId,
        ]);
    }

    public function storeCorporateGoal(Request $request): RedirectResponse
    {
        $hr = $this->hrStaff($request);
        $data = $this->validatedCorporateGoal($request);

        $this->appraisals->saveCorporateGoal($hr, $data);

        return back()->with('status', 'Corporate / cascading goal saved.');
    }

    public function updateCorporateGoal(Request $request, HrCorporateGoal $goal): RedirectResponse
    {
        $hr = $this->hrStaff($request);
        $data = $this->validatedCorporateGoal($request);

        $this->appraisals->saveCorporateGoal($hr, $data, $goal);

        return back()->with('status', 'Corporate goal updated.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validatedCorporateGoal(Request $request): array
    {
        $data = $request->validate([
            'cycle_id' => ['nullable', 'integer', 'exists:hr_appraisal_cycles,id'],
            'parent_id' => ['nullable', 'integer', 'exists:hr_corporate_goals,id'],
            'code' => ['nullable', 'string', 'max:50'],
            'title' => ['required', 'string', 'max:300'],
            'description' => ['nullable', 'string', 'max:5000'],
            'role_scope' => ['required', 'in:all,department,job_title'],
            'scope_values' => ['nullable', 'array'],
            'scope_values.*' => ['nullable', 'string', 'max:200'],
            'weight_hint' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $data['is_active'] = $request->boolean('is_active', true);
        if (($data['role_scope'] ?? 'all') === 'all') {
            $data['scope_values'] = null;
        }

        return $data;
    }

    private function hrStaff(Request $request): Staff
    {
        $user = $request->user();
        $staff = $user?->staff;
        abort_unless($staff, 403, 'HR staff profile required.');

        return $staff;
    }
}
