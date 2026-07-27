<?php

namespace App\Http\Controllers\Academics;

use App\Models\Department;
use App\Models\LessonPlan;
use App\Models\Semester;
use App\Services\LessonPlanApprovalService;
use App\Services\StaffPortalService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LessonPlanController extends DepartmentAcademicsController
{
    public function __construct(
        protected LessonPlanApprovalService $lessonPlans,
        protected StaffPortalService $staffPortal,
        \App\Services\AcademicsAccessService $access,
        \App\Services\DepartmentDashboardService $departmentDashboard,
    ) {
        parent::__construct($access, $departmentDashboard);
    }

    public function index(Request $request, Department $department): View
    {
        $hub = $this->authorizeHub($request, $department);
        $learningDepartmentId = $request->integer('learning_department') ?: null;
        $departmentId = $learningDepartmentId ?: (int) ($hub->academicsScopeDepartmentIds()[0] ?? $hub->id);
        $status = $request->string('status')->toString() ?: null;

        return view('academics.lesson-plans.index', [
            'department' => $hub,
            'learningDepartment' => $learningDepartmentId ? Department::query()->find($learningDepartmentId) : null,
            'learningDepartments' => $this->access->learningDepartmentsInScope($request->user(), $hub),
            'plans' => $this->lessonPlans->inboxForDepartment($departmentId, $status),
            'selectedStatus' => $status,
            'canReview' => $request->user()->hasAnyRole(['HOD', 'Dean', 'Super Admin']),
        ]);
    }

    public function show(Request $request, Department $department, LessonPlan $plan): View
    {
        $hub = $this->authorizeHub($request, $department);
        $staff = $this->staffPortal->staffForUser($request->user());

        $plan->load(['allocation.unit', 'allocation.semester', 'preparedByStaff', 'hodStaff', 'approvals.approver']);

        abort_unless($plan->allocation?->unit, 404);

        $unitDepartmentId = (int) $plan->allocation->unit->department_id;
        abort_unless(
            in_array($unitDepartmentId, $hub->academicsScopeDepartmentIds(), true) || $this->access->canAccessAll($request->user()),
            404
        );

        $canReview = $staff && $this->lessonPlans->hodCanReview($plan, $staff, $request->user());

        return view('academics.lesson-plans.show', [
            'department' => $hub,
            'learningDepartment' => Department::query()->find($unitDepartmentId),
            'plan' => $plan,
            'canReview' => $canReview && $plan->status === 'submitted',
            'hubParams' => array_filter([
                'department' => $hub->id,
                'learning_department' => $request->integer('learning_department') ?: $unitDepartmentId,
            ]),
        ]);
    }

    public function audit(Request $request, Department $department): View
    {
        $hub = $this->authorizeHub($request, $department);
        abort_unless(
            $request->user()->hasAnyRole(['Academic Registrar', 'Super Admin', 'CEO', 'Dean']),
            403
        );

        $learningDepartmentId = $request->integer('learning_department') ?: null;
        $scopeIds = $hub->academicsScopeDepartmentIds();
        $departmentIds = $learningDepartmentId
            ? [(int) $learningDepartmentId]
            : $scopeIds;

        $status = $request->string('status')->toString() ?: null;
        $semesterId = $request->integer('semester') ?: null;

        return view('academics.lesson-plans.registrar-audit', [
            'department' => $hub,
            'learningDepartment' => $learningDepartmentId ? Department::query()->find($learningDepartmentId) : null,
            'learningDepartments' => $this->access->learningDepartmentsInScope($request->user(), $hub),
            'plans' => $this->lessonPlans->auditRepository($departmentIds, $status, $semesterId),
            'semesters' => Semester::query()->with('academicYear')->orderByDesc('id')->get(),
            'selectedStatus' => $status,
            'selectedSemesterId' => $semesterId,
        ]);
    }

    public function update(Request $request, Department $department, LessonPlan $plan): RedirectResponse
    {
        $hub = $this->authorizeHub($request, $department);
        $staff = $this->staffPortal->staffForUser($request->user());
        abort_unless($staff, 403);

        $validated = $request->validate([
            'lesson_objectives' => ['required', 'string'],
            'topics_covered' => ['nullable', 'string'],
            'competencies_targeted' => ['nullable', 'string'],
            'planned_date' => ['required', 'date'],
            'week_number' => ['nullable', 'integer', 'min:1'],
            'contact_hours' => ['required', 'integer', 'min:1'],
            'teaching_methods' => ['nullable', 'string', 'max:500'],
            'resources_required' => ['nullable', 'string', 'max:500'],
        ]);

        abort_unless($this->lessonPlans->hodCanReview($plan, $staff, $request->user()), 403);

        $this->lessonPlans->updateByHod($plan, $staff, $validated);

        return back()->with('status', 'Lesson plan updated.');
    }

    public function approve(Request $request, Department $department, LessonPlan $plan): RedirectResponse
    {
        $hub = $this->authorizeHub($request, $department);
        $staff = $this->staffPortal->staffForUser($request->user());
        abort_unless($staff, 403);

        $comments = $request->string('hod_comments')->toString() ?: null;
        $this->lessonPlans->approve($plan, $staff, $comments);

        return redirect()
            ->route('departments.academics.lesson-plans.index', ['department' => $hub->id, 'learning_department' => $request->input('learning_department')])
            ->with('status', 'Lesson plan approved. Timetable slots for this unit are now cleared.');
    }

    public function reject(Request $request, Department $department, LessonPlan $plan): RedirectResponse
    {
        $hub = $this->authorizeHub($request, $department);
        $staff = $this->staffPortal->staffForUser($request->user());
        abort_unless($staff, 403);

        $validated = $request->validate([
            'hod_comments' => ['required', 'string', 'max:2000'],
        ]);

        $this->lessonPlans->reject($plan, $staff, $validated['hod_comments']);

        return back()->with('status', 'Lesson plan rejected.');
    }

    public function requestModification(Request $request, Department $department, LessonPlan $plan): RedirectResponse
    {
        $hub = $this->authorizeHub($request, $department);
        $staff = $this->staffPortal->staffForUser($request->user());
        abort_unless($staff, 403);

        $validated = $request->validate([
            'hod_comments' => ['required', 'string', 'max:2000'],
        ]);

        $this->lessonPlans->requestModification($plan, $staff, $validated['hod_comments']);

        return back()->with('status', 'Revision requested. The tutor has been notified.');
    }
}
