<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Models\HrAppraisal;
use App\Models\HrAppraisalGoal;
use App\Models\Staff;
use App\Services\HrPerformanceAppraisalService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EmployeeAppraisalController extends Controller
{
    public function __construct(
        protected HrPerformanceAppraisalService $appraisals,
    ) {}

    public function index(Request $request): View
    {
        $staff = $this->employeeStaff($request);

        return view('employee.appraisals.index', [
            'staff' => $staff,
            'appraisals' => $this->appraisals->employeeIndex($staff),
            'teamAppraisals' => $this->appraisals->teamIndex($staff),
            'statuses' => config('tich-performance-appraisals.statuses', []),
            'ratingScale' => $this->appraisals->ratingScale(),
        ]);
    }

    public function show(Request $request, HrAppraisal $appraisal): View
    {
        $staff = $this->employeeStaff($request);
        abort_unless((int) $appraisal->staff_id === (int) $staff->id, 403);

        $appraisal->load(['cycle', 'lineManager', 'goals.corporateGoal', 'competencies', 'department']);

        return view('employee.appraisals.show', [
            'staff' => $staff,
            'appraisal' => $appraisal,
            'cascadingGoals' => $this->appraisals->cascadingGoalsFor($staff, $appraisal->cycle_id),
            'ratingScale' => $this->appraisals->ratingScale(),
            'statuses' => config('tich-performance-appraisals.statuses', []),
            'mode' => 'employee',
        ]);
    }

    public function storeGoal(Request $request, HrAppraisal $appraisal): RedirectResponse
    {
        $staff = $this->employeeStaff($request);
        abort_unless((int) $appraisal->staff_id === (int) $staff->id, 403);

        $data = $this->validatedGoal($request);

        try {
            $this->appraisals->upsertGoal($appraisal, $data);
        } catch (\RuntimeException $e) {
            return back()->withErrors(['goal' => $e->getMessage()]);
        }

        return back()->with('status', 'Goal saved.');
    }

    public function updateGoal(Request $request, HrAppraisal $appraisal, HrAppraisalGoal $goal): RedirectResponse
    {
        $staff = $this->employeeStaff($request);
        abort_unless((int) $appraisal->staff_id === (int) $staff->id, 403);
        abort_unless((int) $goal->appraisal_id === (int) $appraisal->id, 404);

        $data = $this->validatedGoal($request, $goal);

        try {
            $this->appraisals->upsertGoal($appraisal, $data, $goal);
        } catch (\RuntimeException $e) {
            return back()->withErrors(['goal' => $e->getMessage()]);
        }

        return back()->with('status', 'Goal updated.');
    }

    public function destroyGoal(Request $request, HrAppraisal $appraisal, HrAppraisalGoal $goal): RedirectResponse
    {
        $staff = $this->employeeStaff($request);
        abort_unless((int) $appraisal->staff_id === (int) $staff->id, 403);
        abort_unless((int) $goal->appraisal_id === (int) $appraisal->id, 404);

        try {
            $this->appraisals->deleteGoal($goal);
        } catch (\RuntimeException $e) {
            return back()->withErrors(['goal' => $e->getMessage()]);
        }

        return back()->with('status', 'Goal removed.');
    }

    public function submitGoals(Request $request, HrAppraisal $appraisal): RedirectResponse
    {
        $staff = $this->employeeStaff($request);

        try {
            $this->appraisals->submitGoalsForApproval($appraisal, $staff);
        } catch (\RuntimeException $e) {
            return back()->withErrors(['goals' => $e->getMessage()]);
        }

        return back()->with('status', 'Goals submitted to your immediate manager for approval.');
    }

    public function saveSelfAssessment(Request $request, HrAppraisal $appraisal): RedirectResponse
    {
        $staff = $this->employeeStaff($request);
        $data = $request->validate([
            'employee_self_comments' => ['nullable', 'string', 'max:5000'],
            'goals' => ['nullable', 'array'],
            'goals.*.employee_achievement' => ['nullable', 'string', 'max:5000'],
            'goals.*.self_rating' => ['nullable', 'integer', 'min:1', 'max:5'],
            'competencies' => ['nullable', 'array'],
            'competencies.*.self_rating' => ['nullable', 'integer', 'min:1', 'max:5'],
            'competencies.*.is_applicable' => ['nullable', 'boolean'],
        ]);

        try {
            $this->appraisals->saveSelfAssessment($appraisal, $staff, $data);
        } catch (\RuntimeException $e) {
            return back()->withErrors(['self' => $e->getMessage()]);
        }

        return back()->with('status', 'Self-assessment draft saved.');
    }

    public function submitSelfAssessment(Request $request, HrAppraisal $appraisal): RedirectResponse
    {
        $staff = $this->employeeStaff($request);
        $data = $request->validate([
            'employee_self_comments' => ['nullable', 'string', 'max:5000'],
            'goals' => ['nullable', 'array'],
            'goals.*.employee_achievement' => ['nullable', 'string', 'max:5000'],
            'goals.*.self_rating' => ['nullable', 'integer', 'min:1', 'max:5'],
            'competencies' => ['nullable', 'array'],
            'competencies.*.self_rating' => ['nullable', 'integer', 'min:1', 'max:5'],
            'competencies.*.is_applicable' => ['nullable', 'boolean'],
        ]);

        try {
            $this->appraisals->submitSelfAssessment($appraisal, $staff, $data);
        } catch (\RuntimeException $e) {
            return back()->withErrors(['self' => $e->getMessage()]);
        }

        return back()->with('status', 'Self-assessment submitted to your manager.');
    }

    public function teamShow(Request $request, HrAppraisal $appraisal): View
    {
        $manager = $this->employeeStaff($request);
        abort_unless((int) $appraisal->line_manager_id === (int) $manager->id, 403);

        $appraisal->load(['staff.department', 'cycle', 'goals.corporateGoal', 'competencies']);

        return view('employee.appraisals.show', [
            'staff' => $manager,
            'appraisal' => $appraisal,
            'cascadingGoals' => collect(),
            'ratingScale' => $this->appraisals->ratingScale(),
            'statuses' => config('tich-performance-appraisals.statuses', []),
            'mode' => 'manager',
        ]);
    }

    public function approveGoals(Request $request, HrAppraisal $appraisal): RedirectResponse
    {
        $manager = $this->employeeStaff($request);
        $data = $request->validate([
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        try {
            $this->appraisals->approveGoals($appraisal, $manager, $data['notes'] ?? null);
        } catch (\RuntimeException $e) {
            return back()->withErrors(['goals' => $e->getMessage()]);
        }

        return back()->with('status', 'Goals approved. Employee can complete self-assessment.');
    }

    public function returnGoals(Request $request, HrAppraisal $appraisal): RedirectResponse
    {
        $manager = $this->employeeStaff($request);
        $data = $request->validate([
            'reason' => ['required', 'string', 'max:2000'],
        ]);

        try {
            $this->appraisals->returnGoals($appraisal, $manager, $data['reason']);
        } catch (\RuntimeException $e) {
            return back()->withErrors(['goals' => $e->getMessage()]);
        }

        return back()->with('status', 'Goals returned to the employee.');
    }

    public function saveManagerReview(Request $request, HrAppraisal $appraisal): RedirectResponse
    {
        $manager = $this->employeeStaff($request);
        $data = $this->validatedManagerReview($request);

        try {
            $this->appraisals->saveManagerReview($appraisal, $manager, $data);
        } catch (\RuntimeException $e) {
            return back()->withErrors(['review' => $e->getMessage()]);
        }

        return back()->with('status', 'Manager review draft saved.');
    }

    public function submitManagerReview(Request $request, HrAppraisal $appraisal): RedirectResponse
    {
        $manager = $this->employeeStaff($request);
        $data = $this->validatedManagerReview($request);

        try {
            $this->appraisals->submitManagerReview($appraisal, $manager, $data);
        } catch (\RuntimeException $e) {
            return back()->withErrors(['review' => $e->getMessage()]);
        }

        return redirect()
            ->route('employee.appraisals.index')
            ->with('status', 'Manager review submitted to HR.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validatedGoal(Request $request, ?HrAppraisalGoal $goal = null): array
    {
        $rules = [
            'title' => ['required', 'string', 'max:300'],
            'description' => ['nullable', 'string', 'max:5000'],
            'smart_specific' => ['nullable', 'string', 'max:2000'],
            'smart_measurable' => ['nullable', 'string', 'max:2000'],
            'smart_achievable' => ['nullable', 'string', 'max:2000'],
            'smart_relevant' => ['nullable', 'string', 'max:2000'],
            'smart_timebound' => ['nullable', 'string', 'max:2000'],
            'target_date' => ['nullable', 'date'],
            'weight' => ['required', 'numeric', 'min:0', 'max:100'],
            'corporate_goal_id' => ['nullable', 'integer', 'exists:hr_corporate_goals,id'],
        ];

        if (! $goal?->isJdDuties()) {
            $rules['smart_specific'] = ['required', 'string', 'max:2000'];
            $rules['smart_measurable'] = ['required', 'string', 'max:2000'];
            $rules['smart_achievable'] = ['required', 'string', 'max:2000'];
            $rules['smart_relevant'] = ['required', 'string', 'max:2000'];
            $rules['smart_timebound'] = ['required', 'string', 'max:2000'];
        }

        return $request->validate($rules);
    }

    /**
     * @return array<string, mixed>
     */
    private function validatedManagerReview(Request $request): array
    {
        return $request->validate([
            'manager_objectives_comments' => ['nullable', 'string', 'max:5000'],
            'manager_competencies_comments' => ['nullable', 'string', 'max:5000'],
            'strengths' => ['nullable', 'string', 'max:5000'],
            'development_areas' => ['nullable', 'string', 'max:5000'],
            'training_recommendations' => ['nullable', 'string', 'max:5000'],
            'goals' => ['nullable', 'array'],
            'goals.*.manager_rating' => ['nullable', 'integer', 'min:1', 'max:5'],
            'goals.*.manager_comments' => ['nullable', 'string', 'max:2000'],
            'competencies' => ['nullable', 'array'],
            'competencies.*.manager_rating' => ['nullable', 'integer', 'min:1', 'max:5'],
            'competencies.*.is_applicable' => ['nullable', 'boolean'],
            'competencies.*.comments' => ['nullable', 'string', 'max:2000'],
        ]);
    }

    private function employeeStaff(Request $request): Staff
    {
        $staff = $request->user()?->staff;
        abort_unless($staff, 403, 'Employee staff profile required.');

        return $staff;
    }
}
