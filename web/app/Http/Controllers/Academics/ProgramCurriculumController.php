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
use App\Models\Student;
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
use App\Services\StudentUnitRegistrationService;
use App\Services\SupplementaryExamService;
use App\Services\WorkingIntakeService;
use App\Services\TimetableSchedulingService;
use App\Services\TimetableTemplateService;
use App\Services\UnitAllocationService;
use App\Services\UnitCatalogService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

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
        protected SupplementaryExamService $supplementaryMarks,
        protected PrintDocumentService $printDocuments,
        protected UnitAllocationService $unitAllocations,
        protected ExamScheduleSyncService $examScheduleSync,
        protected StudentUnitRegistrationService $studentRegistrations,
        AcademicsAccessService $access,
        DepartmentDashboardService $departmentDashboard,
    ) {
        parent::__construct($access, $departmentDashboard);
    }

    public function index(Request $request, Department $department): View
    {
        $hub = $this->authorizeHub($request, $department);
        $learningDepartmentId = $this->access->authorizedLearningDepartmentId(
            $request->user(),
            $hub,
            $request->integer('learning_department') ?: null
        );

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
            'canViewApplications' => $request->user()->hasPermission('academics.read'),
        ]);
    }

    public function show(Request $request, Department $department, AcademicProgram $program): View
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
        $lecturerCampusId = $this->lecturerCampusFilterId($request);

        if (in_array($section, ['intakes', 'semesters'], true)) {
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
            : collect(range(1, $program->scheduledPeriods()))->map(fn (int $number) => [
                'key' => 'sem-'.$number,
                'label' => $program->periodLabel($number),
                'semester' => $number,
                'block_id' => null,
            ]);

        $enrolledStudents = ['matched' => collect(), 'other' => collect()];
        $enrolledSummaries = collect();
        $enrolledYears = collect();
        $timetableYearCounts = collect();
        $expandedStudentRecord = null;
        $studentRegistration = null;
        $studentCorrections = null;
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
            $enrolledYears = $this->studentRegistrations->yearsForRoster(
                $enrolledStudents['matched']->merge($enrolledStudents['other']),
                $program,
                $selectedIntake
            );

            $expandedStudentId = $request->integer('student') ?: null;
            if ($expandedStudentId) {
                $expandedStudent = $enrolledStudents['matched']->firstWhere('id', $expandedStudentId)
                    ?? $enrolledStudents['other']->firstWhere('id', $expandedStudentId);

                if ($expandedStudent) {
                    $expandedAcademics = $this->studentAcademicRecords->forStudent($expandedStudent);
                    $expandedStudentRecord = [
                        'student' => $expandedStudent,
                        'academics' => $expandedAcademics,
                        'performance' => $this->studentAcademicRecords->semesterPerformance($expandedStudent, $expandedAcademics),
                    ];

                    // Corrections are per student, so they ride with the record rather than
                    // with the intake desk above it.
                    if ($selectedIntake && $request->user()->hasPermission('academics.write')) {
                        $studentCorrections = $this->studentRegistrations->corrections(
                            $expandedStudent,
                            $program,
                            $selectedIntake
                        );
                    }
                }
            }

            if ($selectedIntake) {
                $studentRegistration = $this->studentRegistrations->desk($request, $program, $selectedIntake);
            }
        }

        if ($section === 'timetable' && $selectedIntake) {
            // The years offered on the timetable page come from the admission records, so Year 2
            // means the students actually sitting Year 2 rather than a guess from the intake label.
            $timetableRoster = $this->studentAcademicRecords->enrolledForProgram($program, $selectedIntake);

            $timetableYearCounts = $this->studentRegistrations
                ->yearsForRoster($timetableRoster['matched']->merge($timetableRoster['other']), $program, $selectedIntake)
                ->countBy(fn (array $year) => (int) $year['year'])
                ->sortKeys();
        }

        // One resolution of the exams tab: it picks both the hub payload and the highlighted tab.
        $examTab = $section === 'exams'
            ? $this->programExams->resolveTab($request->string('exam_tab')->toString() ?: 'overview')
            : 'overview';

        return view('academics.programs.curriculum', [
            'department' => $hub,
            'learningDepartment' => $learningDepartment,
            'program' => $program,
            'periods' => $periods,
            'totalTeachingPeriods' => $program->totalTeachingPeriods(),
            'scheduledPeriods' => $program->scheduledPeriods(),
            'termsPerYear' => $program->termsPerYear(),
            'programYears' => $program->programYears(),
            'timetablePeriods' => $this->timetablePeriodSpan($program, $mappings, $selectedIntake, $timetableYearCounts),
            'timetableYearCounts' => $timetableYearCounts,
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
            'catalogUnits' => $this->unitCatalog->listForHub($request->user(), $hub, (int) $program->department_id),
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
            'enrolledYears' => $enrolledYears,
            'studentRegistration' => $studentRegistration,
            'studentCorrections' => $studentCorrections,
            'expandedStudentRecord' => $expandedStudentRecord,
            'enrollmentStatusFilter' => $enrollmentStatusFilter,
            'canViewApplications' => $request->user()->hasPermission('academics.read'),
            'timetableTemplate' => $section === 'timetable'
                ? $this->timetableTemplates->templateForProgram($program->id)
                : null,
            'timetableSegmentTypes' => TimetableTemplateService::segmentTypes(),
            'timetableContactHours' => $section === 'timetable'
                ? $this->contactHoursByUnit($selectedIntake, $request->integer('teaching_period') ?: 1)
                : collect(),
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
                ? $this->timetableScheduling->timetableForDisplay(
                    $program->id,
                    $selectedIntake->id,
                    $request->integer('teaching_period') ?: 1,
                    $this->timetableScheduling->normalizeTimetableKind(
                        in_array($request->string('timetable_kind')->toString(), array_merge(array_keys(TimetableSchedulingService::timetableKinds()), ['special_exam']), true)
                            ? $request->string('timetable_kind')->toString()
                            : 'lesson'
                    ),
                    $request->integer('timetable') ?: null
                )
                : null,
            'timetablesInScope' => ($section === 'timetable' && $selectedIntake)
                ? $this->timetableScheduling->timetablesInScope(
                    $program->id,
                    $selectedIntake->id,
                    $request->integer('teaching_period') ?: 1,
                    $this->timetableScheduling->normalizeTimetableKind(
                        in_array($request->string('timetable_kind')->toString(), array_merge(array_keys(TimetableSchedulingService::timetableKinds()), ['special_exam']), true)
                            ? $request->string('timetable_kind')->toString()
                            : 'lesson'
                    )
                )
                : collect(),
            'timetableChoices' => $section === 'timetable'
                ? $this->timetableScheduling->timetablesForPicker($program->id, $selectedIntake?->id)
                : collect(),
            'timetableKinds' => TimetableSchedulingService::timetableKinds(),
            'timetableRooms' => $section === 'timetable'
                ? Room::query()->with('campus')->where('is_active', 1)->orderBy('room_code')->get()
                : collect(),
            'timetableStaff' => $section === 'timetable'
                ? $this->staffChoices($request, $program)->limit(200)->get()
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
                    'schedule' === $examTab,
                    'supplementary' === $examTab,
                )
                : null,
            'examTab' => $examTab,
            'examStaff' => $section === 'exams'
                ? $this->staffChoices($request, $program)->limit(200)->get()
                : collect(),
            'catalogAllocations' => in_array($section, ['catalog', 'semesters'], true) && $selectedIntake
                ? $this->unitAllocations->forUnitsInIntake(
                    $this->unitCatalog->listForHub($request->user(), $hub, (int) $program->department_id)->pluck('id')->all(),
                    $selectedIntake,
                    $program,
                )
                : collect(),
            'allocationStaffList' => in_array($section, ['catalog', 'semesters'], true)
                ? $this->lecturerChoices($program, $lecturerCampusId)
                : collect(),
            'allocationCampuses' => in_array($section, ['catalog', 'semesters'], true)
                ? Campus::query()->where('is_active', 1)->orderBy('campus_name')->get()
                : collect(),
            'lecturerCampusId' => in_array($section, ['catalog', 'semesters'], true)
                ? $lecturerCampusId
                : null,
        ]);
    }

    public function updateFormat(Request $request, Department $department, AcademicProgram $program): RedirectResponse
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

    public function syncUnits(Request $request, Department $department, AcademicProgram $program): RedirectResponse
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

    public function createVersion(Request $request, Department $department, AcademicProgram $program): RedirectResponse
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

    public function syncIntakeUnits(Request $request, Department $department, AcademicProgram $program, CurriculumVersion $version): RedirectResponse
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

    public function syncIntakePeriods(Request $request, Department $department, AcademicProgram $program, CurriculumVersion $version): RedirectResponse
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

    public function syncTimetableTemplate(Request $request, Department $department, AcademicProgram $program): RedirectResponse
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

    public function syncTimetableKindSlots(Request $request, Department $department, AcademicProgram $program): RedirectResponse
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

    public function startTimetable(Request $request, Department $department, AcademicProgram $program, CurriculumVersion $version): RedirectResponse
    {
        $hub = $this->authorizeHub($request, $department);
        $program = $this->access->findProgramForHub($request->user(), $hub, $program->id);
        abort_unless((int) $version->program_id === (int) $program->id, 404);

        $validated = $request->validate([
            'title' => ['nullable', 'string', 'max:200'],
            'timetable_kind' => ['nullable', 'in:'.implode(',', array_keys(TimetableSchedulingService::timetableKinds()))],
            'cycle_type' => ['nullable', 'in:'.implode(',', array_keys(ProgramTimetable::cycleTypes()))],
            'cycle_no' => [
                \Illuminate\Validation\Rule::requiredIf(fn () => in_array($request->string('cycle_type')->toString(), ['weekly', 'monthly'], true)),
                'nullable',
                'integer',
                'min:1',
                'max:60',
            ],
            'copy_from' => ['nullable', 'integer'],
        ], [
            'cycle_no.required_with' => 'Say which week or month this timetable covers.',
        ]);

        $teachingPeriod = $request->integer('teaching_period') ?: 1;
        $timetableKind = $this->timetableScheduling->normalizeTimetableKind($validated['timetable_kind'] ?? 'lesson');

        $timetable = $this->timetableScheduling->startDraft(
            $request->user(),
            $program,
            $version,
            $teachingPeriod,
            $timetableKind,
            $validated['title'] ?? null,
            $request,
            $validated['cycle_type'] ?? null,
            isset($validated['cycle_no']) ? (int) $validated['cycle_no'] : null,
            isset($validated['copy_from']) ? (int) $validated['copy_from'] : null
        );

        $cycle = $timetable->cycleLabel();

        return $this->timetableRedirect(
            $request,
            $timetable,
            $cycle
                ? 'Opened the '.$cycle.' timetable - its classes are stamped with it, so nothing lands on another week.'
                : 'Timetable opened empty - add the classes as the school runs them.'
        );
    }

    public function generateTimetable(Request $request, Department $department, AcademicProgram $program, CurriculumVersion $version): RedirectResponse
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
            ->withFragment('timetable-display')
            ->with('status', 'Timetable draft generated. Review conflicts, then publish.');
    }

    public function updateTimetableSessionInvigilator(
        Request $request,
        Department $department,
        AcademicProgram $program,
        ProgramTimetable $timetable,
        ProgramTimetableSession $session
    ): RedirectResponse {
        $hub = $this->authorizeHub($request, $department);
        $program = $this->access->findProgramForHub($request->user(), $hub, $program->id);
        abort_unless((int) $timetable->program_id === (int) $program->id, 404);
        abort_unless((int) $session->program_timetable_id === (int) $timetable->id, 404);

        $validated = $request->validate([
            'staff_id' => ['nullable', 'exists:staff,id'],
        ]);

        $this->timetableScheduling->assignSessionInvigilator(
            $request->user(),
            $timetable,
            $session,
            isset($validated['staff_id']) ? (int) $validated['staff_id'] : null,
            $request
        );

        return redirect()
            ->route('departments.academics.programs.curriculum', array_filter([
                'program' => $program->id,
                'learning_department' => $request->integer('learning_department') ?: null,
                'intake' => $timetable->curriculum_version_id,
                'section' => 'timetable',
                'teaching_period' => $timetable->teaching_period,
                'timetable_kind' => $timetable->timetable_kind,
            ]))
            ->withFragment('timetable-display')
            ->with('status', 'Invigilator updated.');
    }

    public function addTimetableSession(Request $request, Department $department, AcademicProgram $program, ProgramTimetable $timetable): RedirectResponse
    {
        $hub = $this->authorizeHub($request, $department);
        $program = $this->access->findProgramForHub($request->user(), $hub, $program->id);
        abort_unless((int) $timetable->program_id === (int) $program->id, 404);

        $validated = $request->validate([
            'unit_id' => [
                \Illuminate\Validation\Rule::requiredIf(fn () => ProgramTimetableSession::needsUnit($request->string('session_type')->toString())),
                'nullable',
                'exists:units,id',
            ],
            'staff_id' => ['nullable', 'exists:staff,id'],
            'room_id' => ['nullable', 'exists:rooms,id'],
            'delivery_mode' => ['nullable', 'in:physical,online'],
            'recurrence' => ['nullable', 'in:'.implode(',', array_keys(ProgramTimetableSession::recurrences()))],
            'week_no' => ['nullable', 'integer', 'min:1', 'max:60', 'required_with:week_to'],
            'week_to' => ['nullable', 'integer', 'min:1', 'max:60', 'after_or_equal:week_no'],
            'every_day' => ['nullable', 'boolean'],
            'session_date' => ['nullable', 'date'],
            'contact_hours' => ['nullable', 'numeric', 'min:0', 'max:999'],
            'day_of_week' => ['required', 'integer', 'min:1', 'max:7'],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i', 'after:start_time'],
            'session_type' => ['required', 'in:'.implode(',', array_keys(TimetableTemplateService::segmentTypes()))],
            'title' => ['nullable', 'string', 'max:200'],
            'venue' => ['nullable', 'string', 'max:200'],
            'class_group' => ['nullable', 'string', 'max:100'],
        ]);

        $this->assertSessionRefsInDepartment($request, $hub, $program, $validated);

        $session = $this->timetableScheduling->addSession($request->user(), $timetable, $validated, $request);

        if (
            ! empty($validated['staff_id'])
            && in_array($timetable->timetable_kind, ['exam', 'supplementary', 'special_exam'], true)
        ) {
            $this->timetableScheduling->notifyInvigilatorAssigned(
                $session->load(['unit', 'staff', 'room', 'timetable.program', 'timetable.curriculumVersion'])
            );
        }

        return $this->timetableRedirect($request, $timetable, 'Session added to the '.lcfirst($timetable->kindLabel()).'.');
    }

    public function updateTimetableSession(
        Request $request,
        Department $department,
        AcademicProgram $program,
        ProgramTimetable $timetable,
        ProgramTimetableSession $session
    ): RedirectResponse {
        $hub = $this->authorizeHub($request, $department);
        $program = $this->access->findProgramForHub($request->user(), $hub, $program->id);
        abort_unless((int) $timetable->program_id === (int) $program->id, 404);
        abort_unless((int) $session->program_timetable_id === (int) $timetable->id, 404);

        $validated = $request->validate([
            'unit_id' => [
                \Illuminate\Validation\Rule::requiredIf(fn () => ProgramTimetableSession::needsUnit($request->string('session_type')->toString())),
                'nullable',
                'exists:units,id',
            ],
            'staff_id' => ['nullable', 'exists:staff,id'],
            'room_id' => ['nullable', 'exists:rooms,id'],
            'delivery_mode' => ['nullable', 'in:physical,online'],
            'recurrence' => ['nullable', 'in:'.implode(',', array_keys(ProgramTimetableSession::recurrences()))],
            'week_no' => ['nullable', 'integer', 'min:1', 'max:60', 'required_with:week_to'],
            'week_to' => ['nullable', 'integer', 'min:1', 'max:60', 'after_or_equal:week_no'],
            'session_date' => ['nullable', 'date'],
            'contact_hours' => ['nullable', 'numeric', 'min:0', 'max:999'],
            'day_of_week' => ['required', 'integer', 'min:1', 'max:7'],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i', 'after:start_time'],
            'session_type' => ['required', 'in:'.implode(',', array_keys(TimetableTemplateService::segmentTypes()))],
            'title' => ['nullable', 'string', 'max:200'],
            'venue' => ['nullable', 'string', 'max:200'],
            'class_group' => ['nullable', 'string', 'max:100'],
        ]);

        $this->assertSessionRefsInDepartment($request, $hub, $program, $validated);

        $updated = $this->timetableScheduling->updateSession($request->user(), $timetable, $session, $validated, $request);

        if (
            ! empty($validated['staff_id'])
            && in_array($timetable->timetable_kind, ['exam', 'supplementary', 'special_exam'], true)
        ) {
            $this->timetableScheduling->notifyInvigilatorAssigned(
                $updated->load(['unit', 'staff', 'room', 'timetable.program', 'timetable.curriculumVersion'])
            );
        }

        return $this->timetableRedirect($request, $timetable, 'Session updated: '.$updated->displayTitle().'.');
    }

    public function removeTimetableSession(
        Request $request,
        Department $department,
        AcademicProgram $program,
        ProgramTimetable $timetable,
        ProgramTimetableSession $session
    ): RedirectResponse {
        $hub = $this->authorizeHub($request, $department);
        $program = $this->access->findProgramForHub($request->user(), $hub, $program->id);
        abort_unless((int) $timetable->program_id === (int) $program->id, 404);
        abort_unless((int) $session->program_timetable_id === (int) $timetable->id, 404);

        $label = $session->loadMissing('unit')->displayTitle();

        $this->timetableScheduling->removeSession($request->user(), $timetable, $session, $request);

        return $this->timetableRedirect($request, $timetable, 'Session removed: '.$label.'.');
    }

    /**
     * The semester's own progress line: how far the weekly or monthly postings have got.
     */
    public function saveTimetableProgress(
        Request $request,
        Department $department,
        AcademicProgram $program,
        ProgramTimetable $timetable
    ): RedirectResponse {
        $hub = $this->authorizeHub($request, $department);
        $program = $this->access->findProgramForHub($request->user(), $hub, $program->id);
        abort_unless((int) $timetable->program_id === (int) $program->id, 404);

        $validated = $request->validate([
            'progress_note' => ['nullable', 'string', 'max:255'],
            'week_span' => ['nullable', 'integer', 'min:1', 'max:60'],
        ]);

        $saved = $this->timetableScheduling->saveTimetable($request->user(), $timetable, $validated, $request);

        return $this->timetableRedirect(
            $request,
            $timetable,
            'Semester '.$timetable->teaching_period.' progress saved'.($saved->progress_note ? ': '.$saved->progress_note : '.')
        );
    }

    /**
     * Every timetable write lands back on the same tab, scrolled to the grid it just changed.
     */
    /**
     * How far the timetable page offers years and semesters. The programme's own duration sets the
     * floor, but a unit mapped - or a timetable already posted - for a later semester must never
     * hide the year it belongs to.
     */
    private function timetablePeriodSpan(AcademicProgram $program, Collection $mappings, ?CurriculumVersion $intake, Collection $yearCounts = new Collection): int
    {
        $span = max(1, (int) $program->scheduledPeriods());
        $mapped = (int) $mappings->max('semester');

        $posted = $intake
            ? (int) ProgramTimetable::query()
                ->where('program_id', $program->id)
                ->where('curriculum_version_id', $intake->id)
                ->max('teaching_period')
            : 0;

        // A student admitted into Year 3 must have that year on the timetable page even when the
        // programme is only configured to run one year.
        $enrolled = ((int) $yearCounts->keys()->max()) * max(1, (int) $program->termsPerYear());

        return max($span, $mapped, $posted, $enrolled);
    }

    private function timetableRedirect(Request $request, ProgramTimetable $timetable, string $status): RedirectResponse
    {
        return redirect()
            ->route('departments.academics.programs.curriculum', array_filter([
                'program' => $timetable->program_id,
                'learning_department' => $request->integer('learning_department') ?: null,
                'intake' => $timetable->curriculum_version_id,
                'section' => 'timetable',
                'teaching_period' => $timetable->teaching_period,
                'timetable_kind' => $timetable->timetable_kind,
                'timetable' => $timetable->id,
            ]))
            ->withFragment('timetable-display')
            ->with('status', $status);
    }

    public function moveTimetableSession(Request $request, Department $department, AcademicProgram $program, ProgramTimetable $timetable, ProgramTimetableSession $session): JsonResponse|RedirectResponse
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

    public function publishTimetable(Request $request, Department $department, AcademicProgram $program, ProgramTimetable $timetable): RedirectResponse
    {
        $hub = $this->authorizeHub($request, $department);
        $program = $this->access->findProgramForHub($request->user(), $hub, $program->id);
        abort_unless((int) $timetable->program_id === (int) $program->id, 404);

        $this->timetableScheduling->publish($request->user(), $timetable, $request);

        return redirect()
            ->route('departments.academics.programs.curriculum', array_filter([
                'program' => $program->id,
                'learning_department' => $request->integer('learning_department') ?: null,
                'intake' => $timetable->curriculum_version_id,
                'section' => 'timetable',
                'teaching_period' => $timetable->teaching_period,
                'timetable_kind' => $timetable->timetable_kind,
            ]))
            ->withFragment('timetable-display')
            ->with('status', 'Timetable published. Students can now view it in the portal.');
    }

    public function printTimetable(Request $request, Department $department, AcademicProgram $program, ProgramTimetable $timetable): View
    {
        return $this->printDocuments->render(
            'academics.programs.timetable-print',
            $this->timetablePrintData($request, $department, $program, $timetable),
        );
    }

    public function downloadTimetablePdf(Request $request, Department $department, AcademicProgram $program, ProgramTimetable $timetable): StreamedResponse
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

    public function addIntakeUnit(Request $request, Department $department, AcademicProgram $program, CurriculumVersion $version, int $semester): RedirectResponse
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

    public function publishVersion(Request $request, Department $department, CurriculumVersion $version): RedirectResponse
    {
        $hub = $this->authorizeHub($request, $department);
        $this->versions->publishAsAcademics($request->user(), $hub, $version, $request);

        return back()->with('status', 'Intake published. Units, timetables and enrolled students now read this curriculum.');
    }

    public function updateExamSchedule(Request $request, Department $department, AcademicProgram $program, int $schedule): RedirectResponse
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

        if (! empty($validated['invigilator_id'])) {
            abort_unless(
                $this->staffChoices($request, $program)->find((int) $validated['invigilator_id']),
                422,
                'Assign an invigilator who belongs to this department.'
            );
        }

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

    public function updateUnitAssessmentWeights(Request $request, Department $department, AcademicProgram $program, Unit $unit): RedirectResponse
    {
        $hub = $this->authorizeHub($request, $department);
        $program = $this->access->findProgramForHub($request->user(), $hub, $program->id);

        abort_unless(
            $this->access->unitsInScope($hub, $program->department_id)->contains('id', $unit->id),
            403,
            'That unit does not belong to this programme.'
        );

        $validated = $request->validate([
            'cat_weight' => ['required', 'numeric', 'min:0', 'max:100'],
            'practical_weight' => ['required', 'numeric', 'min:0', 'max:100'],
            'attendance_weight' => ['required', 'numeric', 'min:0', 'max:100'],
            'exam_weight' => ['required', 'numeric', 'min:0', 'max:100'],
            'pass_mark' => ['nullable', 'numeric', 'min:1', 'max:100'],
            'teaching_period' => ['nullable', 'integer', 'min:1'],
            'intake' => ['nullable', 'integer'],
        ], [
            'pass_mark.numeric' => 'Type the mark a student needs to pass this unit, or leave it empty to use the programme mark.',
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

    public function recordSupplementaryMarks(Request $request, Department $department, AcademicProgram $program): RedirectResponse
    {
        $hub = $this->authorizeHub($request, $department);
        abort_unless($request->user()->hasPermission('academics.write'), 403);
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
            'marks' => ['required', 'array', 'min:1'],
            'marks.*' => ['nullable', 'numeric', 'min:0', 'max:100'],
        ], [
            'marks.required' => 'Type the supplementary mark next to the student it belongs to before saving.',
            'marks.*.numeric' => 'A supplementary mark must be a number out of 100.',
            'marks.*.min' => 'A supplementary mark cannot be below 0.',
            'marks.*.max' => 'A supplementary mark cannot be above 100.',
        ]);

        $saved = $this->supplementaryMarks->record($request->user(), $program, $validated['marks']);

        $summary = collect($saved)->map(function (array $row) {
            $row['exam_score'] = number_format((float) $row['exam_score'], 1);
            $row['grade_letter'] ??= '';

            return $row['registration_number'].' '.$row['unit_code'].' → '.$row['exam_score'].' ('.$row['grade_letter'].')';
        })->implode(', ');

        return redirect()
            ->route('departments.academics.programs.curriculum', array_filter([
                'program' => $program->id,
                'learning_department' => $request->integer('learning_department') ?: null,
                'intake' => $intake->id,
                'section' => 'exams',
                'exam_tab' => 'supplementary',
                'teaching_period' => $teachingPeriod,
            ]))
            ->with('status', 'Supplementary marks recorded: '.$summary.'. The failed grade is replaced on the transcript.');
    }

    public function storeAllocation(Request $request, Department $department, AcademicProgram $program, Unit $unit): RedirectResponse
    {
        $hub = $this->authorizeHub($request, $department);
        abort_unless($request->user()->hasPermission('academics.write'), 403);
        $program = $this->access->findProgramForHub($request->user(), $hub, $program->id);

        $validated = $request->validate([
            'intake' => ['required', 'integer'],
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

        // The unit comes from the address on the form, so a lecturer can never land on a
        // different unit from the one the row names.
        abort_unless((int) $unit->department_id === (int) $program->department_id, 404);
        $unit = $this->access->unitsInScope($hub, $program->department_id)->firstWhere('id', $unit->id);
        abort_unless($unit, 404);

        $lecturer = $this->lecturerQuery()
            ->whereKey((int) $validated['staff_id'])
            ->first();
        abort_unless($lecturer, 422, 'Assign a lecturer on the staff register.');

        $semesterId = $this->examScheduleSync->resolveSemesterId($intake, (int) $validated['teaching_period']);
        abort_unless($semesterId, 422, 'Could not resolve a semester for this intake and teaching period.');

        $this->unitAllocations->assign([
            'unit_id' => $unit->id,
            'program_id' => $program->id,
            'staff_id' => $lecturer->id,
            'semester_id' => $semesterId,
            'campus_id' => (int) $validated['campus_id'],
            'contact_hours_assigned' => ($validated['contact_hours_assigned'] ?? null) === null
                ? (int) ($unit->contact_hours ?? 0)
                : (int) $validated['contact_hours_assigned'],
            'is_coordinator' => ! empty($validated['is_coordinator']),
        ]);

        $section = $request->string('section')->toString() ?: 'catalog';

        $target = route('departments.academics.programs.curriculum', array_filter([
            'program' => $program->id,
            'learning_department' => $validated['learning_department'] ?? null,
            'intake' => $intake->id,
            'section' => $section,
        ])).'#unit-row-'.$unit->id;

        $hours = ($validated['contact_hours_assigned'] ?? null) === null
            ? (int) ($unit->contact_hours ?? 0)
            : (int) $validated['contact_hours_assigned'];

        return redirect()
            ->to($target)
            ->with('status', $lecturer->fullName().' assigned to '.$unit->displayLabel().' for '
                .$program->periodLabel((int) $validated['teaching_period']).' with '.$hours.' contact hours.');
    }

    public function destroyAllocation(Request $request, Department $department, AcademicProgram $program, UnitAllocation $unitAllocation): RedirectResponse
    {
        $hub = $this->authorizeHub($request, $department);
        abort_unless($request->user()->hasPermission('academics.write'), 403);
        $program = $this->access->findProgramForHub($request->user(), $hub, $program->id);

        $this->unitAllocations->assertAllocationInProgramDepartment($unitAllocation, $program);
        $this->unitAllocations->remove($unitAllocation);

        $section = $request->string('section')->toString() ?: 'catalog';

        return redirect()
            ->route('departments.academics.programs.curriculum', array_filter([
                'program' => $program->id,
                'learning_department' => $request->integer('learning_department') ?: null,
                'intake' => $request->integer('intake') ?: null,
                'section' => $section,
            ]))
            ->with('status', 'Lecturer allocation removed.');
    }

    /**
     * Put the ticked students onto the units their intake carries for one teaching period.
     */
    public function registerUnits(Request $request, Department $department, AcademicProgram $program): RedirectResponse
    {
        $hub = $this->authorizeHub($request, $department);
        abort_unless($request->user()->hasPermission('academics.write'), 403);
        $program = $this->access->findProgramForHub($request->user(), $hub, $program->id);

        $validated = $request->validate([
            'intake' => ['required', 'integer'],
            'registration_period' => ['required', 'string'],
            'unit_ids' => ['required', 'array', 'min:1'],
            'unit_ids.*' => ['integer'],
            'students' => ['required', 'array', 'min:1'],
            'students.*' => ['integer'],
            'learning_department' => ['nullable', 'integer'],
        ]);

        $intake = CurriculumVersion::query()
            ->where('program_id', $program->id)
            ->findOrFail((int) $validated['intake']);

        $desk = $this->studentRegistrations->desk($request, $program, $intake);
        $period = $desk['periodOptions']->get($validated['registration_period']);
        abort_unless($period, 422, 'Choose the teaching period to register.');

        $semesterId = $desk['semesterId'];
        abort_unless($semesterId, 422, 'Could not resolve a semester for this intake and teaching period.');

        // Only units mapped into the period the desk is showing may be registered.
        $allowedUnitIds = $desk['periodUnits']->pluck('unit_id')->map(fn ($id) => (int) $id)->all();
        $unitIds = array_values(array_intersect($validated['unit_ids'], $allowedUnitIds));

        $result = $this->studentRegistrations->register(
            $request->user(),
            $program,
            $intake,
            (int) $semesterId,
            $validated['students'],
            $unitIds,
        );

        return redirect()
            ->route('departments.academics.programs.curriculum', array_filter([
                'program' => $program->id,
                'learning_department' => $validated['learning_department'] ?? null,
                'intake' => $intake->id,
                'section' => 'enrolled',
                'registration_period' => $validated['registration_period'],
                'enrollment_status' => $request->string('enrollment_status')->toString() ?: null,
                'entry_pathway' => $request->string('entry_pathway')->toString() ?: null,
                'admission_year' => $request->integer('admission_year') ?: null,
            ]))
            ->with('status', "Registered {$result['students']} ".str('student')->plural($result['students'])." onto {$result['units']} unit ".str('place')->plural($result['units']).'.');
    }

    /**
     * Register each ticked student onto the units for the year the app works out for them,
     * from their admission year against the running academic year.
     */
    public function registerUnitsByYear(Request $request, Department $department, AcademicProgram $program): RedirectResponse
    {
        $hub = $this->authorizeHub($request, $department);
        abort_unless($request->user()->hasPermission('academics.write'), 403);
        $program = $this->access->findProgramForHub($request->user(), $hub, $program->id);

        $validated = $request->validate([
            'intake' => ['required', 'integer'],
            'students' => ['required', 'array', 'min:1'],
            'students.*' => ['integer'],
            'learning_department' => ['nullable', 'integer'],
        ]);

        $intake = CurriculumVersion::query()
            ->where('program_id', $program->id)
            ->findOrFail((int) $validated['intake']);

        $result = $this->studentRegistrations->registerByYear(
            $request->user(),
            $program,
            $intake,
            $validated['students'],
        );

        $redirect = redirect()->route(
            'departments.academics.programs.curriculum',
            $this->enrolledReturn($request, $program, $intake),
        );

        if ($result['students'] > 0) {
            $nothingToAdd = $result['units'] === 0;
            $redirect = $redirect->with('status', ($nothingToAdd ? 'Checked ' : 'Registered ').$result['students'].' '
                .str('student')->plural($result['students']).' against their own year ('.implode('; ', $result['groups']).')'
                .($nothingToAdd ? ' — they already hold every unit for that year, so nothing was added' : '')
                .'.');

            if ($result['skipped'] !== []) {
                $skippedCount = count($result['skipped']);
                $redirect = $redirect->with('warning', 'Skipped '.$skippedCount.' '.str('student')->plural($skippedCount).': '.implode(' ', $result['skipped']));
            }
        } else {
            $redirect = $redirect->with('error', 'No student was registered: '
                .($result['skipped'] !== [] ? implode(' ', $result['skipped']) : 'check each student has an admission year and that units are mapped to their year.'));
        }

        return $redirect;
    }

    /**
     * Put one extra unit on a single student, limited to the units their own year is due for.
     */
    public function addStudentUnit(Request $request, Department $department, AcademicProgram $program, Student $student): RedirectResponse
    {
        $hub = $this->authorizeHub($request, $department);
        abort_unless($request->user()->hasPermission('academics.write'), 403);
        $program = $this->access->findProgramForHub($request->user(), $hub, $program->id);
        abort_unless((int) $student->program_id === (int) $program->id, 404);

        $validated = $request->validate([
            'intake' => ['required', 'integer'],
            'unit_id' => ['required', 'integer'],
        ]);

        $intake = CurriculumVersion::query()
            ->where('program_id', $program->id)
            ->findOrFail((int) $validated['intake']);

        $this->studentRegistrations->addUnit(
            $request->user(),
            $student,
            $program,
            $intake,
            (int) $validated['unit_id'],
        );

        return redirect()
            ->to($this->studentRecordUrl($request, $program, $intake, $student))
            ->with('status', 'Unit added to '.$student->registration_number.'.');
    }

    /**
     * Take a unit off a student. Deliberately the slowest control on the record: it needs a
     * written reason and a ticked confirmation, and it is audited.
     */
    public function removeStudentUnit(Request $request, Department $department, AcademicProgram $program, Student $student): RedirectResponse
    {
        $hub = $this->authorizeHub($request, $department);
        abort_unless($request->user()->hasPermission('academics.write'), 403);
        $program = $this->access->findProgramForHub($request->user(), $hub, $program->id);
        abort_unless((int) $student->program_id === (int) $program->id, 404);

        $validated = $request->validate([
            'intake' => ['required', 'integer'],
            'registered_unit_id' => ['required', 'integer'],
            'reason' => ['required', 'string', 'min:8', 'max:500'],
            'confirm_removal' => ['accepted'],
        ], [
            'reason.required' => 'Say why this unit is being removed - the correction is auditable and cannot be left blank.',
            'reason.min' => 'Say why this unit is being removed (at least 8 characters) so the correction is auditable.',
            'confirm_removal.accepted' => 'Tick "I confirm" - un-assigning a student who is already registered is a deliberate change.',
        ]);

        $intake = CurriculumVersion::query()
            ->where('program_id', $program->id)
            ->findOrFail((int) $validated['intake']);

        $result = $this->studentRegistrations->removeUnit(
            $request->user(),
            $student,
            (int) $validated['registered_unit_id'],
            $validated['reason'],
        );

        return redirect()
            ->to($this->studentRecordUrl($request, $program, $intake, $student))
            ->with('status', $result['unit_code'].' removed from '.$student->registration_number.'. '.$result['unit_count'].' unit '.str('place')->plural($result['unit_count']).' remain on that registration.');
    }

    /**
     * Pin the student's year and semester of study, overriding the admission arithmetic for them alone.
     */
    public function pinStudentYear(Request $request, Department $department, AcademicProgram $program, Student $student): RedirectResponse
    {
        $hub = $this->authorizeHub($request, $department);
        abort_unless($request->user()->hasPermission('academics.write'), 403);
        $program = $this->access->findProgramForHub($request->user(), $hub, $program->id);
        abort_unless((int) $student->program_id === (int) $program->id, 404);

        $validated = $request->validate([
            'intake' => ['required', 'integer'],
            'current_year' => ['required', 'integer', 'min:1'],
            'current_term' => ['required', 'integer', 'min:1'],
            'reason' => ['required', 'string', 'min:8', 'max:500'],
        ], [
            'reason.required' => 'Say why this student sits a different year - the change is auditable and cannot be left blank.',
            'reason.min' => 'Say why this student sits a different year (at least 8 characters) so the change is auditable.',
        ]);

        $intake = CurriculumVersion::query()
            ->where('program_id', $program->id)
            ->findOrFail((int) $validated['intake']);

        $pinned = $this->studentRegistrations->pinYear(
            $request->user(),
            $student,
            $program,
            $intake,
            (int) $validated['current_year'],
            (int) $validated['current_term'],
            $validated['reason'],
        );

        return redirect()
            ->to($this->studentRecordUrl($request, $program, $intake, $student))
            ->with('status', $student->registration_number.' now sits '.$pinned['period_label'].'.');
    }

    /**
     * Lift the year pinned on a student, so their admission date works the year out again.
     */
    public function releaseStudentYear(Request $request, Department $department, AcademicProgram $program, Student $student): RedirectResponse
    {
        $hub = $this->authorizeHub($request, $department);
        abort_unless($request->user()->hasPermission('academics.write'), 403);
        $program = $this->access->findProgramForHub($request->user(), $hub, $program->id);
        abort_unless((int) $student->program_id === (int) $program->id, 404);

        $validated = $request->validate([
            'intake' => ['required', 'integer'],
            'reason' => ['required', 'string', 'min:8', 'max:500'],
        ], [
            'reason.required' => 'Say why the pinned year is being lifted - the change is auditable and cannot be left blank.',
            'reason.min' => 'Say why the pinned year is being lifted (at least 8 characters) so the change is auditable.',
        ]);

        $intake = CurriculumVersion::query()
            ->where('program_id', $program->id)
            ->findOrFail((int) $validated['intake']);

        $this->studentRegistrations->releaseYearPin($request->user(), $student, $validated['reason']);

        return redirect()
            ->to($this->studentRecordUrl($request, $program, $intake, $student))
            ->with('status', $student->registration_number.' now takes their year from their admission date again.');
    }

    /**
     * Lift the hold the cohort roll put on a student who owes a repeat unit and move them onto the
     * semester they are due for. The roll itself clears a hold as soon as the repeat is passed; this
     * is the officer's override, and it needs a written reason because the student goes forward
     * carrying a fail.
     */
    public function clearCohortHold(Request $request, Department $department, AcademicProgram $program, Student $student): RedirectResponse
    {
        $hub = $this->authorizeHub($request, $department);
        abort_unless($request->user()->hasPermission('academics.write'), 403);
        $program = $this->access->findProgramForHub($request->user(), $hub, $program->id);
        abort_unless((int) $student->program_id === (int) $program->id, 404);

        $validated = $request->validate([
            'intake' => ['required', 'integer'],
            'reason' => ['required', 'string', 'min:8', 'max:500'],
        ], [
            'reason.required' => 'Say why this student goes forward while owing a repeat - the change is auditable and cannot be left blank.',
            'reason.min' => 'Say why this student goes forward while owing a repeat (at least 8 characters) so the change is auditable.',
        ]);

        $intake = CurriculumVersion::query()
            ->where('program_id', $program->id)
            ->findOrFail((int) $validated['intake']);

        $cleared = $this->studentRegistrations->clearHold(
            $request->user(),
            $program,
            $intake,
            $student,
            $validated['reason'],
        );

        return redirect()
            ->to($this->studentRecordUrl($request, $program, $intake, $student))
            ->with('status', $student->registration_number.' released from the hold and registered on '
                .$cleared['period_label'].' ('.$cleared['units'].' unit '.str('place')->plural($cleared['units']).' written; '
                .'still owes '.implode(', ', $cleared['owed']).').');
    }

    /**
     * @return array<string, int|string>
     */
    private function enrolledReturn(Request $request, AcademicProgram $program, CurriculumVersion $intake): array
    {
        return array_filter([
            'program' => $program->id,
            'learning_department' => $request->integer('learning_department') ?: null,
            'intake' => $intake->id,
            'section' => 'enrolled',
            'enrollment_status' => $request->string('enrollment_status')->toString() ?: null,
            'entry_pathway' => $request->string('entry_pathway')->toString() ?: null,
            'admission_year' => $request->integer('admission_year') ?: null,
            'student_search' => trim($request->string('student_search')->toString()) ?: null,
        ]);
    }

    /**
     * The student's own record, scrolled back to the corrections panel.
     */
    private function studentRecordUrl(Request $request, AcademicProgram $program, CurriculumVersion $intake, Student $student): string
    {
        return route('departments.academics.programs.curriculum', array_merge(
            $this->enrolledReturn($request, $program, $intake),
            ['student' => $student->id],
        )).'#unit-corrections';
    }

    /**
     * Staff a user may schedule: an HOD is limited to their own department, registry roles see everyone.
     *
     * @return Builder<Staff>
     */
    private function staffChoices(Request $request, AcademicProgram $program): Builder
    {
        return Staff::query()
            ->when(
                ! $this->access->actsInstitutionWide($request->user()),
                fn (Builder $query) => $query->where('department_id', $program->department_id)
            )
            ->orderBy('surname');
    }

    /**
     * Anyone the institution can put in front of a unit.
     *
     * Lecturers move between campuses and departments freely, so this list is
     * institution-wide even for an HOD: HR has not finished mapping
     * staff.department_id for every teaching profile, and scoping it here simply
     * emptied the picker.
     *
     * @return Builder<Staff>
     */
    private function lecturerQuery(): Builder
    {
        return Staff::query()->teaching();
    }

    /**
     * Lecturers offered in the Assign lecturer forms.
     *
     * The campus control prioritises a campus instead of filtering one: every lecturer can
     * teach at any campus, so narrowing here hid assignable staff.
     *
     * @return Collection<int, Staff>
     */
    private function lecturerChoices(AcademicProgram $program, ?int $campusId = null): Collection
    {
        return $this->lecturerQuery()
            ->reorder()
            ->orderByRaw('staff.department_id = ? DESC', [$program->department_id])
            ->when(
                $campusId,
                fn (Builder $query) => $query->orderByRaw('staff.campus_id = ? DESC', [$campusId])
            )
            ->orderBy('surname')
            ->orderBy('first_name')
            ->with(['campus:id,campus_name', 'department:id,dept_name'])
            ->get(['id', 'title', 'first_name', 'middle_name', 'surname', 'employee_number', 'campus_id', 'department_id', 'employment_status', 'job_title']);
    }

    private function lecturerCampusFilterId(Request $request): ?int
    {
        $campusId = $request->integer('lecturer_campus');

        return $campusId > 0 ? $campusId : null;
    }

    /**
     * Contact hours are entered once - on the unit itself and on the lecturer's allocation - so
     * the timetable reads them from there instead of asking for them again.
     *
     * @return Collection<int, float>
     */
    private function contactHoursByUnit(?CurriculumVersion $intake, int $teachingPeriod): Collection
    {
        $allocations = UnitAllocation::query()
            ->where('is_active', 1)
            ->where('contact_hours_assigned', '>', 0);

        if ($intake) {
            $allocations->where('program_id', $intake->program_id);
            $semesterId = $this->examScheduleSync->resolveSemesterId($intake, $teachingPeriod);

            if ($semesterId) {
                $allocations->where('semester_id', $semesterId);
            }
        }

        $allocated = $allocations->pluck('contact_hours_assigned', 'unit_id');

        return Unit::query()->pluck('contact_hours', 'id')->map(
            fn ($hours, $unitId) => (float) ($allocated[$unitId] ?? 0) > 0 ? (float) $allocated[$unitId] : (float) $hours
        );
    }

    /**
     * Keep an HOD's timetable sessions inside their own department's units and lecturers.
     */
    private function assertSessionRefsInDepartment(Request $request, Department $hub, AcademicProgram $program, array $validated): void
    {
        if ($this->access->actsInstitutionWide($request->user())) {
            return;
        }

        if (! empty($validated['unit_id'])) {
            abort_unless(
                $this->access->unitsInScope($hub, $program->department_id)->contains('id', (int) $validated['unit_id']),
                422,
                'Schedule units from your own department.'
            );
        }

        if (! empty($validated['staff_id'])) {
            abort_unless(
                $this->staffChoices($request, $program)->whereKey((int) $validated['staff_id'])->exists(),
                422,
                'Schedule lecturers from your own department.'
            );
        }
    }
}







