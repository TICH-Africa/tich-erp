<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\AcademicWorkplan;
use App\Models\Semester;
use App\Models\Staff;
use App\Services\AcademicWorkplanService;
use App\Services\StaffPortalDashboardService;
use App\Services\StaffPortalNavigationService;
use App\Services\StaffPortalService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class StaffWorkplanController extends Controller
{
    public function __construct(
        protected AcademicWorkplanService $workplans,
        protected StaffPortalService $staffPortal,
        protected StaffPortalNavigationService $navigation,
        protected StaffPortalDashboardService $dashboard,
    ) {}

    public function index(Request $request): View
    {
        $staff = $this->requireHodStaff($request);
        $departmentId = (int) ($staff->department_id ?? 0);

        return view('staff.workplans.index', array_merge($this->portalShell($staff), [
            'workplans' => $this->workplans->forDepartment($departmentId),
        ]));
    }

    public function create(Request $request): View
    {
        $staff = $this->requireHodStaff($request);

        return view('staff.workplans.create', array_merge($this->portalShell($staff), [
            'semesters' => Semester::query()->with('academicYear')->orderByDesc('id')->limit(24)->get(),
            'activities' => $this->emptyActivityRows(3),
        ]));
    }

    public function store(Request $request): RedirectResponse
    {
        $staff = $this->requireHodStaff($request);
        $departmentId = (int) ($staff->department_id ?? 0);
        abort_unless($departmentId > 0, 422, 'Your staff profile has no department.');

        $validated = $this->validateWorkplan($request);
        $workplan = $this->workplans->createDraft(
            $staff,
            $departmentId,
            (int) $validated['semester_id'],
            $validated['title'],
        );

        $this->workplans->update($staff, $workplan, $validated);

        if ($request->boolean('submit')) {
            $this->workplans->submit($staff, $workplan->fresh());

            return redirect()
                ->route('staff.workplans.show', $workplan)
                ->with('status', 'Semester workplan submitted for Academic Registrar and QA review.');
        }

        return redirect()
            ->route('staff.workplans.edit', $workplan)
            ->with('status', 'Draft semester workplan saved.');
    }

    public function show(Request $request, AcademicWorkplan $workplan): View
    {
        $staff = $this->requireHodStaff($request);
        abort_unless($this->workplans->hodOwns($staff, $workplan), 403);

        $workplan->load(['activities', 'department', 'semester.academicYear', 'preparedByStaff', 'registrarStaff', 'qaStaff']);

        return view('staff.workplans.show', array_merge($this->portalShell($staff), [
            'workplan' => $workplan,
            'summary' => $this->workplans->approvalSummary($workplan),
        ]));
    }

    public function edit(Request $request, AcademicWorkplan $workplan): View
    {
        $staff = $this->requireHodStaff($request);
        abort_unless($this->workplans->hodOwns($staff, $workplan), 403);
        abort_unless($workplan->isEditableByHod(), 422, 'This workplan cannot be edited.');

        $workplan->load(['activities', 'semester']);
        $existing = $workplan->activities->map(fn ($row) => [
            'activity' => $row->activity,
            'timeline_start' => $row->timeline_start?->format('Y-m-d'),
            'timeline_end' => $row->timeline_end?->format('Y-m-d'),
            'kpi' => $row->kpi,
            'resources' => $row->resources,
        ])->values()->all();

        while (count($existing) < 3) {
            $existing[] = ['activity' => '', 'timeline_start' => '', 'timeline_end' => '', 'kpi' => '', 'resources' => ''];
        }

        return view('staff.workplans.edit', array_merge($this->portalShell($staff), [
            'workplan' => $workplan,
            'semesters' => Semester::query()->with('academicYear')->orderByDesc('id')->limit(24)->get(),
            'activities' => $existing,
            'summary' => $this->workplans->approvalSummary($workplan),
        ]));
    }

    public function update(Request $request, AcademicWorkplan $workplan): RedirectResponse
    {
        $staff = $this->requireHodStaff($request);
        abort_unless($this->workplans->hodOwns($staff, $workplan), 403);

        $validated = $this->validateWorkplan($request);
        $this->workplans->update($staff, $workplan, $validated);

        if ($request->boolean('submit')) {
            $this->workplans->submit($staff, $workplan->fresh());

            return redirect()
                ->route('staff.workplans.show', $workplan)
                ->with('status', 'Semester workplan submitted for Academic Registrar and QA review.');
        }

        return redirect()
            ->route('staff.workplans.show', $workplan)
            ->with('status', 'Semester workplan updated.');
    }

    public function submit(Request $request, AcademicWorkplan $workplan): RedirectResponse
    {
        $staff = $this->requireHodStaff($request);
        $this->workplans->submit($staff, $workplan);

        return redirect()
            ->route('staff.workplans.show', $workplan)
            ->with('status', 'Semester workplan submitted for Academic Registrar and QA review.');
    }

    private function requireHodStaff(Request $request): Staff
    {
        abort_unless($request->user()?->hasAnyRole(['HOD', 'Super Admin']), 403, 'Only HODs can manage semester workplans.');
        $staff = $this->staffPortal->staffForUser($request->user());
        abort_unless($staff, 404);

        return $staff;
    }

    /**
     * @return array<string, mixed>
     */
    private function portalShell(Staff $staff): array
    {
        return [
            'staff' => $staff,
            'section' => 'hod-workplans',
            'sections' => $this->navigation->sections(),
            'sidebarNavigation' => $this->navigation->sidebarNavigation(),
            'portalData' => $this->dashboard->forStaff($staff),
            'portalTitle' => 'Semester workplans - Staff portal',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function validateWorkplan(Request $request): array
    {
        return $request->validate([
            'title' => ['required', 'string', 'max:300'],
            'semester_id' => ['required', 'integer', 'exists:semesters,id'],
            'objectives' => ['nullable', 'string'],
            'resources' => ['nullable', 'string'],
            'kpis' => ['nullable', 'string'],
            'activities' => ['nullable', 'array'],
            'activities.*.activity' => ['nullable', 'string', 'max:500'],
            'activities.*.timeline_start' => ['nullable', 'date'],
            'activities.*.timeline_end' => ['nullable', 'date', 'after_or_equal:activities.*.timeline_start'],
            'activities.*.kpi' => ['nullable', 'string', 'max:500'],
            'activities.*.resources' => ['nullable', 'string', 'max:500'],
            'submit' => ['nullable', 'boolean'],
        ]);
    }

    /**
     * @return list<array{activity: string, timeline_start: string, timeline_end: string, kpi: string, resources: string}>
     */
    private function emptyActivityRows(int $count): array
    {
        $rows = [];
        for ($i = 0; $i < $count; $i++) {
            $rows[] = ['activity' => '', 'timeline_start' => '', 'timeline_end' => '', 'kpi' => '', 'resources' => ''];
        }

        return $rows;
    }
}
