<?php

namespace App\Http\Controllers\Academics;

use App\Models\AcademicProgram;
use App\Models\AcademicYear;
use App\Models\Campus;
use App\Models\CurriculumVersion;
use App\Models\Department;
use App\Models\NursingBlock;
use App\Models\ProgramTimetable;
use App\Models\ProgramTimetableSession;
use App\Models\Room;
use App\Models\Staff;
use App\Models\Unit;
use App\Models\UnitAllocation;
use App\Services\AcademicsAccessService;
use App\Services\CurriculumVersionService;
use App\Services\DepartmentDashboardService;
use App\Services\ExamScheduleSyncService;
use App\Services\PrintDocumentService;
use App\Services\ProgramExamService;
use App\Services\ProgramCurriculumService;
use App\Services\StudentAcademicRecordService;
use App\Services\WorkingIntakeService;
use App\Services\TimetableSchedulingService;
use App\Services\TimetableTemplateService;
use App\Services\UnitAllocationService;
use App\Services\UnitCatalogService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;

class ProgramCurriculumController extends DepartmentAcademicsController
{
    public function __construct(
        protected ProgramCurriculumService $curriculum,
        protected CurriculumVersionService $versions,
        protected UnitCatalogService $unitCatalog,
        protected TimetableTemplateService $timetableTemplates,
        protected TimetableSchedulingService $timetableScheduling,
        protected StudentAcademicRecordService $studentAcademicRecords,
        protected WorkingIntakeService $workingIntake,
        protected ProgramExamService $programExams,
        protected PrintDocumentService $printDocuments,
        protected UnitAllocationService $unitAllocations,
        protected ExamScheduleSyncService $examScheduleSync,
        AcademicsAccessService $access,
        DepartmentDashboardService $departmentDashboard,
    ) {
        parent::__construct($access, $departmentDashboard);
    }

    public function index(Request $request, Department $department): View
    {
        $hub = $this->authorizeHub($request, $department);
        $learningDepartmentId = $request->integer('learning_department') ?: null;

        if ($learningDepartmentId) {
            abort_unless(in_array($learningDepartmentId, $hub->academicsScopeDepartmentIds(), true), 404);
        }

        $programsQuery = $this->access->programsQueryForHub($request->user(), $hub);

        if ($learningDepartmentId) {
            $programsQuery->where('department_id', $learningDepartmentId);
        }

        $learningDepartment = $learningDepartmentId
            ? Department::query()->find($learningDepartmentId)
            : null;

        $programs = $programsQuery->get();

        return view('academics.programs.index', [
            'department' => $hub,
            'learningDepartment' => $learningDepartment,
            'programs' => $programs,
            'formats' => ProgramCurriculumService::curriculumFormats(),
            'pendingApplicationsByProgram' => $this->departmentDashboard->pendingApplicationsCountByProgram(
                $programs->pluck('id')->all()
            ),
            'canViewApplications' => $request->user()->hasPermission('admissions.read'),
        ]);
    }

    public function show(Request $request, AcademicProgram $program, Department $department): View
    {
        $hub = $this->authorizeHub($request, $department);
        $program = $this->access->findProgramForHub($request->user(), $hub, $program->id);
        $program->load(['department', 'nursingBlocks']);

        $blocks = NursingBlock::query()->where('program_id', $program->id)->orderBy('block_order')->get();
        $availableUnits = $this->access->unitsInScope($hub, $program->department_id)
            ->whereIn('status', CurriculumVersionService::mappableUnitStatuses())
            ->values();

        $catalogUnitCounts = $this->access->unitsInScope($hub, $program->department_id)
            ->groupBy('status')
            ->map->count();

        $learningDepartmentId = $request->integer('learning_department') ?: (int) $program->department_id;
        $learningDepartment = Department::query()->find($learningDepartmentId);

        $intakes = $this->versions->intakesForProgram($program->id);
        $section = $this->curriculum->resolveSection($request);

        if ($section === 'intakes') {
            $intakes->load(['items.unit']);
        }

        $selectedIntake = $this->workingIntake->resolve($program, $request);
        if ($selectedIntake) {
            $selectedIntake->load('academicYear');
        }
        $intakeSelectionRequired = $this->workingIntake->sectionRequiresIntake($section)
            && $this->workingIntake->programHasIntakes($program->id)
            && ! $selectedIntake;
        $mappings = $selectedIntake
            ? $this->versions->mappedUnits($selectedIntake)
            : collect();

        $periodDates = $selectedIntake
            ? $this->versions->periodsKeyed($selectedIntake)
            : collect();

        $periods = $program->usesBlocks()
            ? $blocks->map(fn (NursingBlock $block) => [
                'key' => 'block-'.$block->id,
                'label' => $block->block_label,
                'semester' => $block->block_order,
                'block_id' => $block->id,
            ])
            : collect(range(1, $program->totalTeachingPeriods()))->map(fn (int $number) => [
                'key' => 'sem-'.$number,
                'label' => $program->periodLabel($number),
                'semester' => $number,
                'block_id' => null,
            ]);

        $enrolledStudents = ['matched' => collect(), 'other' => collect()];
        $enrolledSummaries = collect();
        $expandedStudentRecord = null;
        $enrollmentStatusFilter = $request->string('enrollment_status')->toString() ?: null;

        if ($section === 'enrolled') {
            $enrolledStudents = $this->studentAcademicRecords->enrolledForProgram(
                $program,
                $selectedIntake,
                $enrollmentStatusFilter
            );

            $studentIds = $enrolledStudents['matched']
                ->pluck('id')
                ->merge($enrolledStudents['other']->pluck('id'))
                ->unique()
                ->values()
                ->all();

            $enrolledSummaries = $this->studentAcademicRecords->rosterSummaries($studentIds);

            $expandedStudentId = $request->integer('student') ?: null;
            if ($expandedStudentId) {
                $expandedStudent = $enrolledStudents['matched']->firstWhere('id', $expandedStudentId)
                    ?? $enrolledStudents['other']->firstWhere('id', $expandedStudentId);

                if ($expandedStudent) {
                    $expandedStudentRecord = [
                        'student' => $expandedStudent,
                        'academics' => $this->studentAcademicRecords->forStudent($expandedStudent),
                    ];
                }
            }
        }

        return view('academics.programs.curriculum', [
            'department' => $hub,
            'learningDepartment' => $learningDepartment,
            'program' => $program,
            'periods' => $periods,
            'totalTeachingPeriods' => $program->totalTeachingPeriods(),
            'termsPerYear' => $program->termsPerYear(),
            'programYears' => $program->programYears(),
            'intakes' => $intakes,
            'selectedIntake' => $selectedIntake,
            'mappings' => $mappings,
            'mappingsBySemester' => $mappings->groupBy('semester'),
            'mappingsByBlock' => $mappings->groupBy('block_id'),
            'periodDates' => $periodDates,
            'availableUnits' => $availableUnits,
            'catalogUnitCounts' => $catalogUnitCounts,
            'formats' => ProgramCurriculumService::curriculumFormats(),
            'intakeMonths' => CurriculumVersion::intakeMonths(),
            'blocks' => $blocks,
            'publishedVersion' => $this->versions->publishedVersionForProgram($program->id),
            'academicYears' => AcademicYear::query()->orderByDesc('start_date')->get(),
            'canApproveRegistry' => $this->access->canApproveRegistry($request->user()),
            'canApproveCeo' => $this->access->canApproveCeo($request->user()),
            'catalogUnits' => $this->unitCatalog->listForHub($hub, (int) $program->department_id),
            'statusLabels' => UnitCatalogService::statusLabels(),
            'section' => $section,
            'intakeSelectionRequired' => $intakeSelectionRequired,
            'intakeRequiredSections' => WorkingIntakeService::intakeRequiredSections(),
            'curriculumSidebarNavigation' => $this->curriculum->curriculumSidebarNavigation(
                $hub,
                $learningDepartment,
                $program,
                $selectedIntake,
                $request->user()
            ),
            'applications' => $section === 'applications'
                ? $this->curriculum->applicationsForIntake($program, $selectedIntake)
                : ['matched' => collect(), 'unassigned' => collect()],
            'enrolledStudents' => $enrolledStudents,
            'enrolledSummaries' => $enrolledSummaries,
            'expandedStudentRecord' => $expandedStudentRecord,
            'enrollmentStatusFilter' => $enrollmentStatusFilter,
            'canViewApplications' => $request->user()->hasPermission('admissions.read'),
            'timetableTemplate' => $section === 'timetable'
                ? $this->timetableTemplates->templateForProgram($program->id)
                : null,
            'timetableSegmentTypes' => TimetableTemplateService::segmentTypes(),
            'timetableDayLabels' => TimetableTemplateService::dayLabels(),
            'timetableTeachingPeriod' => $request->integer('teaching_period') ?: 1,
            'timetableKind' => $section === 'timetable'
                ? $this->timetableScheduling->normalizeTimetableKind(
                    in_array($request->string('timetable_kind')->toString(), array_merge(array_keys(TimetableSchedulingService::timetableKinds()), ['special_exam']), true)
                        ? $request->string('timetable_kind')->toString()
                        : 'lesson'
                )
                : 'lesson',
            'timetableDraftsByKind' => ($section === 'timetable' && $selectedIntake)
                ? $this->timetableScheduling->latestTimetablesByKind(
                    $program->id,
                    $selectedIntake->id,
                    $request->integer('teaching_period') ?: 1,
                )
                : collect(),
            'timetableDraft' => ($section === 'timetable' && $selectedIntake)
                ? $this->timetableScheduling->latestTimetable(
                    $program->id,
                    $selectedIntake->id,
                    $request->integer('teaching_period') ?: 1,
                    $this->timetableScheduling->normalizeTimetableKind(
                        in_array($request->string('timetable_kind')->toString(), array_merge(array_keys(TimetableSchedulingService::timetableKinds()), ['special_exam']), true)
                            ? $request->string('timetable_kind')->toString()
                            : 'lesson'
                    )
                )
                : null,
            'timetableKinds' => TimetableSchedulingService::timetableKinds(),
            'timetableRooms' => $section === 'timetable'
                ? Room::query()->with('campus')->where('is_active', 1)->orderBy('room_code')->get()
                : collect(),
            'timetableStaff' => $section === 'timetable'
                ? Staff::query()->orderBy('surname')->limit(200)->get()
                : collect(),
            'examHub' => $section === 'exams' && $selectedIntake
                ? $this->programExams->hubData(
                    $program,
                    $selectedIntake,
                    $periodDates->all(),
                    $this->programExams->resolveTeachingPeriod(
                        $selectedIntake,
                        $request->integer('teaching_period') ?: null
                    ),
                    $this->programExams->resolveTab($request->string('exam_tab')->toString() ?: 'overview') === 'schedule'
                )
                : null,
            'examTab' => $section === 'exams'
                ? $this->programExams->resolveTab($request->string('exam_tab')->toString() ?: 'overview')
                : 'overview',
            'examStaff' => $section === 'exams'
                ? Staff::query()->orderBy('surname')->limit(200)->get()
                : collect(),
            'catalogAllocations' => ($section === 'catalog' && $selectedIntake)
                ? $this->unitAllocations->forUnitsInIntake(
                    $this->unitCatalog->listForHub($hub, (int) $program->department_id)->pluck('id')->all(),
                    $selectedIntake,
                    $program,
                )
                : collect(),
            'allocationStaffList' => $section === 'catalog'
                ? Staff::query()->where('is_teaching_staff', 1)->where('employment_status', 'active')->orderBy('surname')->get()
                : collect(),
            'allocationCampuses' => $section === 'catalog'
                ? Campus::query()->where('is_active', 1)->orderBy('campus_name')->get()
                : collect(),
        ]);
    }

    public function updateFormat(Request $request, AcademicProgram $program, Department $department): RedirectResponse
    {
        $hub = $this->authorizeHub($request, $department);
        $program = $this->access->findProgramForHub($request->user(), $hub, $program->id);

        $validated = $request->validate([
            'curriculum_format' => ['required', 'in:'.implode(',', config('tich-academics.curriculum_formats'))],
            'semester_count' => ['required', 'integer', 'min:1', 'max:12'],
            'block_count' => ['nullable', 'integer', 'min:0', 'max:10'],
            'duration_months' => ['nullable', 'integer', 'min:1', 'max:120'],
        ]);

        $this->curriculum->updateProgramFormat($request->user(), $hub, $program, $validated, $request);

        return redirect()
            ->route('departments.academics.programs.curriculum', array_filter([
                'program' => $program->id,
                'learning_department' => $request->integer('learning_department') ?: null,
                'intake' => $request->integer('intake') ?: null,
                'section' => $request->string('return_section')->toString() ?: 'structure',
            ]))
            ->with('status', 'Programme structure updated.');
    }

    public function syncUnits(Request $request, AcademicProgram $program, Department $department): RedirectResponse
    {
        $hub = $this->authorizeHub($request, $department);
        $program = $this->access->findProgramForHub($request->user(), $hub, $program->id);

        $validated = $request->validate([
            'mappings' => ['nullable', 'array'],
            'mappings.*.unit_id' => ['required', 'exists:units,id'],
            'mappings.*.include' => ['nullable', 'boolean'],
            'mappings.*.semester' => ['nullable', 'integer', 'min:1', 'max:12'],
            'mappings.*.block_id' => ['nullable', 'exists:nursing_blocks,id'],
            'mappings.*.is_compulsory' => ['nullable', 'boolean'],
            'mappings.*.display_order' => ['nullable', 'integer', 'min:0'],
            'mappings.*.priority' => ['nullable', 'integer', 'min:0'],
            'mappings.*.contact_hours' => ['nullable', 'integer', 'min:0'],
            'mappings.*.total_learning_hours' => ['nullable', 'integer', 'min:0'],
        ]);

        $this->curriculum->syncProgramUnits(
            $request->user(),
            $hub,
            $program,
            $validated['mappings'] ?? [],
            $request
        );

        return back()->with('status', 'Programme unit mapping saved.');
    }

    public function createVersion(Request $request, AcademicProgram $program, Department $department): RedirectResponse
    {
        $hub = $this->authorizeHub($request, $department);
        $program = $this->access->findProgramForHub($request->user(), $hub, $program->id);

        $validated = $request->validate([
            'intake_year' => ['required', 'integer', 'min:2000', 'max:2100'],
            'intake_month' => ['required', 'integer', 'min:1', 'max:12'],
            'version_label' => ['nullable', 'string', 'max:100'],
            'academic_year_id' => ['nullable', 'exists:academic_years,id'],
            'copy_from_version_id' => ['nullable', 'exists:curriculum_versions,id'],
            'copy_from_program_template' => ['nullable', 'boolean'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $version = $this->versions->createDraft($request->user(), $hub, $program, $validated, $request);

        return redirect()
            ->route('departments.academics.programs.curriculum', array_filter([
                'program' => $program->id,
                'learning_department' => $request->integer('learning_department') ?: null,
                'intake' => $version->id,
                'section' => 'intakes',
            ]))
            ->with('status', "Intake {$version->intakeLabel()} created.");
    }

    public function syncIntakeUnits(Request $request, AcademicProgram $program, CurriculumVersion $version, Department $department): RedirectResponse
    {
        $hub = $this->authorizeHub($request, $department);
        $program = $this->access->findProgramForHub($request->user(), $hub, $program->id);
        abort_unless((int) $version->program_id === (int) $program->id, 404);

        $validated = $request->validate([
            'mappings' => ['nullable', 'array'],
            'mappings.*.unit_id' => ['required', 'exists:units,id'],
            'mappings.*.include' => ['nullable', 'boolean'],
            'mappings.*.semester' => ['nullable', 'integer', 'min:1', 'max:24'],
            'mappings.*.block_id' => ['nullable', 'exists:nursing_blocks,id'],
            'mappings.*.is_compulsory' => ['nullable', 'boolean'],
            'mappings.*.display_order' => ['nullable', 'integer', 'min:0'],
            'mappings.*.priority' => ['nullable', 'integer', 'min:0'],
            'mappings.*.contact_hours' => ['nullable', 'integer', 'min:0'],
            'mappings.*.total_learning_hours' => ['nullable', 'integer', 'min:0'],
        ]);

        $this->versions->syncVersionUnits(
            $request->user(),
            $hub,
            $version,
            $validated['mappings'] ?? [],
            $request
        );

        return back()->with('status', 'Intake unit mapping saved.');
    }

    public function syncIntakePeriods(Request $request, AcademicProgram $program, CurriculumVersion $version, Department $department): RedirectResponse
    {
        $hub = $this->authorizeHub($request, $department);
        $program = $this->access->findProgramForHub($request->user(), $hub, $program->id);
        abort_unless((int) $version->program_id === (int) $program->id, 404);

        $validated = $request->validate([
            'periods' => ['nullable', 'array'],
            'periods.*.semester' => ['required', 'integer', 'min:1', 'max:24'],
            'periods.*.block_id' => ['nullable', 'exists:nursing_blocks,id'],
            'periods.*.start_date' => ['nullable', 'date'],
            'periods.*.end_date' => ['nullable', 'date'],
            'periods.*.learning_start_date' => ['nullable', 'date'],
            'periods.*.learning_end_date' => ['nullable', 'date'],
            'periods.*.exam_start_date' => ['nullable', 'date'],
            'periods.*.exam_end_date' => ['nullable', 'date'],
        ]);

        $this->versions->syncPeriodDates(
            $request->user(),
            $hub,
            $version,
            $validated['periods'] ?? [],
            $request
        );

        $periodRow = collect($validated['periods'] ?? [])->first();

        return redirect()
            ->route('departments.academics.programs.curriculum', array_filter([
                'program' => $program->id,
                'learning_department' => $request->integer('learning_department') ?: null,
                'intake' => $version->id,
                'section' => 'semesters',
                'semester' => $periodRow['semester'] ?? null,
                'block_id' => $periodRow['block_id'] ?? null,
            ]))
            ->with('status', 'Semester dates saved.');
    }

    public function syncTimetableTemplate(Request $request, AcademicProgram $program, Department $department): RedirectResponse
    {
        $hub = $this->authorizeHub($request, $department);
        $program = $this->access->findProgramForHub($request->user(), $hub, $program->id);

        $validated = $request->validate([
            'name' => ['nullable', 'string', 'max:120'],
            'days' => ['nullable', 'array'],
            'days.*' => ['integer', 'min:1', 'max:7'],
            'segments' => ['nullable', 'array'],
            'segments.*.label' => ['nullable', 'string', 'max:120'],
            'segments.*.start_time' => ['nullable', 'date_format:H:i'],
            'segments.*.end_time' => ['nullable', 'date_format:H:i'],
            'segments.*.segment_type' => ['nullable', 'string', 'max:50'],
        ]);

        $this->timetableTemplates->syncTemplate(
            $request->user(),
            $program,
            $validated,
            $request
        );

        return redirect()
            ->route('departments.academics.programs.curriculum', array_filter([
                'program' => $program->id,
                'learning_department' => $request->integer('learning_department') ?: null,
                'intake' => $request->integer('intake') ?: null,
                'section' => 'timetable',
                'teaching_period' => $request->integer('teaching_period') ?: null,
                'timetable_kind' => $request->string('timetable_kind')->toString() ?: 'lesson',
            ]))
            ->with('status', 'Bell schedule saved.');
    }

    public function syncTimetableKindSlots(Request $request, AcademicProgram $program, Department $department): RedirectResponse
    {
        $hub = $this->authorizeHub($request, $department);
        $program = $this->access->findProgramForHub($request->user(), $hub, $program->id);

        $timetableKind = $this->timetableScheduling->normalizeTimetableKind(
            $request->string('timetable_kind')->toString()
        );

        $validated = $request->validate([
            'timetable_kind' => ['required', 'in:exam,supplementary,special_exam'],
            'segments' => ['nullable', 'array'],
            'segments.*.label' => ['nullable', 'string', 'max:120'],
            'segments.*.start_time' => ['nullable', 'date_format:H:i'],
            'segments.*.end_time' => ['nullable', 'date_format:H:i'],
        ]);

        $this->timetableTemplates->syncKindSlots(
            $request->user(),
            $program,
            $timetableKind,
            $validated,
            $request
        );

        return redirect()
            ->route('departments.academics.programs.curriculum', array_filter([
                'program' => $program->id,
                'learning_department' => $request->integer('learning_department') ?: null,
                'intake' => $request->integer('intake') ?: null,
                'section' => 'timetable',
                'teaching_period' => $request->integer('teaching_period') ?: null,
                'timetable_kind' => $timetableKind,
            ]))
            ->with('status', 'Exam slots saved.');
    }

    public function generateTimetable(Request $request, AcademicProgram $program, CurriculumVersion $version, Department $department): RedirectResponse
    {
        $hub = $this->authorizeHub($request, $department);
        $program = $this->access->findProgramForHub($request->user(), $hub, $program->id);
        abort_unless((int) $version->program_id === (int) $program->id, 404);

        $teachingPeriod = $request->integer('teaching_period') ?: 1;
        $timetableKind = $this->timetableScheduling->normalizeTimetableKind(
            $request->string('timetable_kind')->toString() ?: 'lesson'
        );

        $validated = $request->validate([
            'title' => ['nullable', 'string', 'max:200'],
            'timetable_kind' => ['nullable', 'in:'.implode(',', array_keys(TimetableSchedulingService::timetableKinds()))],
        ]);

        $this->timetableScheduling->generate(
            $request->user(),
            $hub,
            $program,
            $version,
            $teachingPeriod,
            $validated['timetable_kind'] ?? $timetableKind,
            $validated['title'] ?? null,
            $request
        );

        return redirect()
            ->route('departments.academics.programs.curriculum', array_filter([
                'program' => $program->id,
                'learning_department' => $request->integer('learning_department') ?: null,
                'intake' => $version->id,
                'section' => 'timetable',
                'teaching_period' => $teachingPeriod,
                'timetable_kind' => $validated['timetable_kind'] ?? $timetableKind,
            ]))
            ->with('status', 'Timetable draft generated. Review conflicts, then publish.');
    }

    public function addTimetableSession(Request $request, AcademicProgram $program, ProgramTimetable $timetable, Department $department): RedirectResponse
    {
        $hub = $this->authorizeHub($request, $department);
        $program = $this->access->findProgramForHub($request->user(), $hub, $program->id);
        abort_unless((int) $timetable->program_id === (int) $program->id, 404);

        $validated = $request->validate([
            'unit_id' => ['nullable', 'exists:units,id'],
            'staff_id' => ['nullable', 'exists:staff,id'],
            'room_id' => ['nullable', 'exists:rooms,id'],
            'day_of_week' => ['required', 'integer', 'min:1', 'max:7'],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i'],
            'session_type' => ['required', 'in:'.implode(',', array_keys(TimetableTemplateService::segmentTypes()))],
            'title' => ['nullable', 'string', 'max:200'],
            'venue' => ['nullable', 'string', 'max:200'],
            'class_group' => ['nullable', 'string', 'max:100'],
        ]);

        $this->timetableScheduling->addSession($request->user(), $timetable, $validated, $request);

        return back()->with('status', 'Session added to timetable draft.');
    }

    public function moveTimetableSession(Request $request, AcademicProgram $program, ProgramTimetable $timetable, ProgramTimetableSession $session, Department $department): JsonResponse|RedirectResponse
    {
        $hub = $this->authorizeHub($request, $department);
        $program = $this->access->findProgramForHub($request->user(), $hub, $program->id);
        abort_unless((int) $timetable->program_id === (int) $program->id, 404);
        abort_unless((int) $session->program_timetable_id === (int) $timetable->id, 404);

        $validated = $request->validate([
            'day_of_week' => ['required', 'integer', 'min:1', 'max:7'],
            'segment_id' => ['required', 'integer', 'exists:program_timetable_segments,id'],
            'swap_session_id' => ['nullable', 'integer', 'exists:program_timetable_sessions,id'],
        ]);

        $result = $this->timetableScheduling->moveSession(
            $request->user(),
            $timetable,
            $session,
            (int) $validated['day_of_week'],
            (int) $validated['segment_id'],
            isset($validated['swap_session_id']) ? (int) $validated['swap_session_id'] : null,
            $request
        );

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Session moved.',
                'session' => [
                    'id' => $result['session']->id,
                    'day_of_week' => $result['session']->day_of_week,
                    'segment_id' => $result['session']->segment_id,
                    'title' => $result['session']->displayTitle(),
                ],
                'swap_session' => $result['swap_session'] ? [
                    'id' => $result['swap_session']->id,
                    'day_of_week' => $result['swap_session']->day_of_week,
                    'segment_id' => $result['swap_session']->segment_id,
                    'title' => $result['swap_session']->displayTitle(),
                ] : null,
            ]);
        }

        return back()->with('status', 'Session moved.');
    }

    public function publishTimetable(Request $request, AcademicProgram $program, ProgramTimetable $timetable, Department $department): RedirectResponse
    {
        $hub = $this->authorizeHub($request, $department);
        $program = $this->access->findProgramForHub($request->user(), $hub, $program->id);
        abort_unless((int) $timetable->program_id === (int) $program->id, 404);

        $this->timetableScheduling->publish($request->user(), $timetable, $request);

        return back()->with('status', 'Timetable published. Students can now view it in the portal.');
    }

    public function printTimetable(Request $request, AcademicProgram $program, ProgramTimetable $timetable, Department $department): View
    {
        return $this->printDocuments->render(
            'academics.programs.timetable-print',
            $this->timetablePrintData($request, $department, $program, $timetable),
        );
    }

    public function downloadTimetablePdf(Request $request, AcademicProgram $program, ProgramTimetable $timetable, Department $department): Response
    {
        $data = $this->timetablePrintData($request, $department, $program, $timetable, includeActions: false);
        $program = $data['program'];

        return $this->printDocuments->downloadPdf(
            'academics.programs.timetable-print',
            $data,
            sprintf(
                'timetable-%s-sem%d-%s.pdf',
                $timetable->timetable_kind,
                $timetable->teaching_period,
                \Illuminate\Support\Str::slug($program->program_code ?? (string) $program->id)
            ),
            'landscape',
        );
    }

    /**
     * @return array<string, mixed>
     */
    protected function timetablePrintData(
        Request $request,
        Department $department,
        AcademicProgram $program,
        ProgramTimetable $timetable,
        bool $includeActions = true,
    ): array {
        $hub = $this->authorizeHub($request, $department);
        $program = $this->access->findProgramForHub($request->user(), $hub, $program->id);
        abort_unless((int) $timetable->program_id === (int) $program->id, 404);

        $payload = $this->timetableScheduling->documentPayload($timetable);
        $intake = $payload['intake'];
        $kindLabel = $payload['kindLabel'];

        $backUrl = route('departments.academics.programs.curriculum', \App\Support\AcademicsRouteParams::for([
            'program' => $program->id,
            'learning_department' => $request->integer('learning_department') ?: null,
            'intake' => $intake?->id,
            'section' => 'timetable',
            'teaching_period' => $timetable->teaching_period,
            'timetable_kind' => $timetable->timetable_kind,
        ]));

        $routeParams = [
            'program' => $program->id,
            'timetable' => $timetable->id,
        ];

        $pdfUrl = route('departments.academics.programs.timetable.pdf', $routeParams);

        return array_merge($payload, [
            'documentTitle' => $kindLabel,
            'documentSubtitle' => trim(($program->program_name ?? '').($intake ? ' · '.$intake->intakeLabel() : '')),
            'documentRef' => $this->printDocuments->documentRef(
                'TT',
                $program->program_code ?? $program->id,
                $timetable->teaching_period,
                $timetable->timetable_kind,
            ),
            'paperOrientation' => 'landscape',
            'metaRows' => [
                ['label' => 'Programme', 'value' => e($program->program_name ?? '-')],
                ['label' => 'Intake', 'value' => e($intake?->intakeLabel() ?? '-')],
                ['label' => 'Semester', 'value' => e((string) $timetable->teaching_period)],
                ['label' => 'Campus', 'value' => e($timetable->campus?->campus_name ?? '-')],
                ['label' => 'Status', 'value' => e(ucfirst($timetable->status))],
                ['label' => 'Timetable', 'value' => e($timetable->displayTitle()), 'full' => true],
            ],
            'backUrl' => $includeActions ? $backUrl : null,
            'pdfUrl' => $includeActions ? $pdfUrl : null,
        ]);
    }

    public function addIntakeUnit(Request $request, AcademicProgram $program, CurriculumVersion $version, int $semester, Department $department): RedirectResponse
    {
        $hub = $this->authorizeHub($request, $department);
        $program = $this->access->findProgramForHub($request->user(), $hub, $program->id);
        abort_unless((int) $version->program_id === (int) $program->id, 404);

        $validated = $request->validate([
            'unit_id' => ['nullable', 'exists:units,id'],
            'unit_ids' => ['nullable', 'array'],
            'unit_ids.*' => ['integer', 'exists:units,id'],
            'block_id' => ['nullable', 'exists:nursing_blocks,id'],
        ]);

        $unitIds = $validated['unit_ids'] ?? [];
        if (! empty($validated['unit_id'])) {
            $unitIds[] = (int) $validated['unit_id'];
        }

        $added = $this->versions->addUnitsToPeriod(
            $request->user(),
            $hub,
            $version,
            $unitIds,
            $semester,
            isset($validated['block_id']) ? (int) $validated['block_id'] : null,
            $request
        );

        $count = count($added);

        return redirect()
            ->route('departments.academics.programs.curriculum', array_filter([
                'program' => $program->id,
                'learning_department' => $request->integer('learning_department') ?: null,
                'intake' => $version->id,
                'section' => 'semesters',
                'semester' => $semester,
                'block_id' => $validated['block_id'] ?? null,
            ]))
            ->with('status', $count === 1 ? 'Unit added to semester.' : "{$count} units added to semester.");
    }

    public function reopenVersion(Request $request, Department $department, CurriculumVersion $version): RedirectResponse
    {
        $hub = $this->authorizeHub($request, $department);
        $this->versions->reopenDraft($request->user(), $hub, $version, $request);

        return back()->with('status', 'Intake reopened for editing. You can now assign units by semester.');
    }

    public function submitVersion(Request $request, Department $department, CurriculumVersion $version): RedirectResponse
    {
        $hub = $this->authorizeHub($request, $department);
        $this->versions->submit($request->user(), $hub, $version, $request);

        return back()->with('status', 'Curriculum version submitted for registry review.');
    }

    public function approveVersionRegistry(Request $request, Department $department, CurriculumVersion $version): RedirectResponse
    {
        $hub = $this->authorizeHub($request, $department);
        $this->versions->approveRegistry($request->user(), $hub, $version, $request);

        return back()->with('status', 'Curriculum version approved by registrar.');
    }

    public function approveVersionCeo(Request $request, Department $department, CurriculumVersion $version): RedirectResponse
    {
        $hub = $this->authorizeHub($request, $department);
        $this->versions->approveCeo($request->user(), $hub, $version, $request);

        return back()->with('status', 'Curriculum version published.');
    }

    public function updateExamSchedule(Request $request, AcademicProgram $program, int $schedule, Department $department): RedirectResponse
    {
        $hub = $this->authorizeHub($request, $department);
        $program = $this->access->findProgramForHub($request->user(), $hub, $program->id);

        $intakeId = $request->integer('intake');
        $intake = $intakeId
            ? CurriculumVersion::query()->where('program_id', $program->id)->findOrFail($intakeId)
            : null;

        abort_unless($intake, 404);

        $teachingPeriod = $this->programExams->resolveTeachingPeriod(
            $intake,
            $request->integer('teaching_period') ?: null
        );

        $validated = $request->validate([
            'exam_date' => ['required', 'date'],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i', 'after:start_time'],
            'venue' => ['required', 'string', 'max:200'],
            'exam_type' => ['required', 'in:main,supplementary,special,clinical'],
            'invigilator_id' => ['nullable', 'exists:staff,id'],
            'status' => ['required', 'in:scheduled,in_progress,completed,cancelled'],
        ]);

        $unitIds = $this->programExams->unitIdsForSemester($intake, $teachingPeriod);
        $this->programExams->updateExamSchedule($schedule, $validated, $unitIds);

        return redirect()
            ->route('departments.academics.programs.curriculum', array_filter([
                'program' => $program->id,
                'learning_department' => $request->integer('learning_department') ?: null,
                'intake' => $intake->id,
                'section' => 'exams',
                'exam_tab' => 'schedule',
                'teaching_period' => $teachingPeriod,
            ]))
            ->with('status', 'Exam session updated.');
    }

    public function updateUnitAssessmentWeights(Request $request, AcademicProgram $program, Unit $unit, Department $department): RedirectResponse
    {
        $hub = $this->authorizeHub($request, $department);
        $program = $this->access->findProgramForHub($request->user(), $hub, $program->id);

        $validated = $request->validate([
            'cat_weight' => ['required', 'numeric', 'min:0', 'max:100'],
            'practical_weight' => ['required', 'numeric', 'min:0', 'max:100'],
            'attendance_weight' => ['required', 'numeric', 'min:0', 'max:100'],
            'exam_weight' => ['required', 'numeric', 'min:0', 'max:100'],
            'teaching_period' => ['nullable', 'integer', 'min:1'],
            'intake' => ['nullable', 'integer'],
        ]);

        $this->programExams->updateUnitAssessmentWeights($unit, $validated);

        return redirect()
            ->route('departments.academics.programs.curriculum', array_filter([
                'program' => $program->id,
                'learning_department' => $request->integer('learning_department') ?: null,
                'intake' => $request->integer('intake') ?: null,
                'section' => 'exams',
                'exam_tab' => 'grading',
                'teaching_period' => $request->integer('teaching_period') ?: null,
            ]))
            ->with('status', 'Unit assessment weights updated.');
    }

    public function storeAllocation(Request $request, AcademicProgram $program, Department $department): RedirectResponse
    {
        $hub = $this->authorizeHub($request, $department);
        abort_unless($request->user()->hasPermission('academics.write'), 403);
        $program = $this->access->findProgramForHub($request->user(), $hub, $program->id);

        $validated = $request->validate([
            'intake' => ['required', 'integer'],
            'unit_id' => ['required', 'integer'],
            'staff_id' => ['required', 'integer'],
            'teaching_period' => ['required', 'integer', 'min:1'],
            'campus_id' => ['required', 'integer'],
            'contact_hours_assigned' => ['nullable', 'integer', 'min:0'],
            'is_coordinator' => ['nullable', 'boolean'],
            'learning_department' => ['nullable', 'integer'],
        ]);

        $intake = CurriculumVersion::query()
            ->where('program_id', $program->id)
            ->findOrFail((int) $validated['intake']);

        $unit = $this->access->unitsInScope($hub, $program->department_id)
            ->firstWhere('id', (int) $validated['unit_id']);
        abort_unless($unit, 404);

        $semesterId = $this->examScheduleSync->resolveSemesterId($intake, (int) $validated['teaching_period']);
        abort_unless($semesterId, 422, 'Could not resolve a semester for this intake and teaching period.');

        $this->unitAllocations->assign([
            'unit_id' => $unit->id,
            'staff_id' => (int) $validated['staff_id'],
            'semester_id' => $semesterId,
            'campus_id' => (int) $validated['campus_id'],
            'contact_hours_assigned' => (int) ($validated['contact_hours_assigned'] ?? $unit->contact_hours ?? 0),
            'is_coordinator' => ! empty($validated['is_coordinator']),
        ]);

        return redirect()
            ->route('departments.academics.programs.curriculum', array_filter([
                'program' => $program->id,
                'learning_department' => $validated['learning_department'] ?? null,
                'intake' => $intake->id,
                'section' => 'catalog',
            ]))
            ->with('status', 'Lecturer assigned to unit.');
    }

    public function destroyAllocation(Request $request, AcademicProgram $program, UnitAllocation $allocation, Department $department): RedirectResponse
    {
        $hub = $this->authorizeHub($request, $department);
        abort_unless($request->user()->hasPermission('academics.write'), 403);
        $program = $this->access->findProgramForHub($request->user(), $hub, $program->id);

        $this->unitAllocations->assertAllocationInProgramDepartment($allocation, $program);
        $this->unitAllocations->remove($allocation);

        return redirect()
            ->route('departments.academics.programs.curriculum', array_filter([
                'program' => $program->id,
                'learning_department' => $request->integer('learning_department') ?: null,
                'intake' => $request->integer('intake') ?: null,
                'section' => 'catalog',
            ]))
            ->with('status', 'Lecturer allocation removed.');
    }
}
