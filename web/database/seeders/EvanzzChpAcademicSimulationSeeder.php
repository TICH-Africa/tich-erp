<?php

namespace Database\Seeders;

use App\Models\AcademicProgram;
use App\Models\FeeStructure;
use App\Models\Role;
use App\Models\Staff;
use App\Models\Student;
use App\Models\Unit;
use App\Models\UnitAllocation;
use App\Models\User;
use App\Services\ContinuousAssessmentService;
use App\Services\Finance\InvoiceService;
use App\Services\RBACService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * Keep only Certificate in Community Health Practice (CHP) for Evanzz Osumba,
 * then seed a full academics–student simulation (calendar, units, allocations,
 * attendance, CATs, exams, fees, curriculum, timetable, lesson plans).
 */
class EvanzzChpAcademicSimulationSeeder extends Seeder
{
    private const STUDENT_EMAIL = 'osumbaevanzz@gmail.com';

    private const PROGRAM_CODE = 'CHP';

    /** @var list<array{name: string, type: string, max: float}> */
    private array $assessments = [
        ['name' => 'CAT 1', 'type' => 'cat', 'max' => 30],
        ['name' => 'CAT 2', 'type' => 'cat', 'max' => 30],
        ['name' => 'Theoretical review 1', 'type' => 'theoretical_review', 'max' => 20],
        ['name' => 'Assignment 1', 'type' => 'assignment', 'max' => 20],
        ['name' => 'Practical 1', 'type' => 'practical', 'max' => 25],
        ['name' => 'Skills lab 1', 'type' => 'skills_lab', 'max' => 25],
    ];

    public function run(): void
    {
        $program = AcademicProgram::query()->where('program_code', self::PROGRAM_CODE)->first();
        $studentUser = User::query()->whereRaw('LOWER(email) = ?', [self::STUDENT_EMAIL])->first();
        $student = $studentUser?->student ?? Student::query()->where('user_id', $studentUser?->id)->first();

        if (! $program || ! $student) {
            $this->command?->error('CHP programme or Evanzz student not found.');

            return;
        }

        $actors = $this->resolveActors((int) $program->department_id);
        if (! $actors['registrar'] || ! $actors['hod'] || ! $actors['tutor']) {
            $this->command?->error('Need Academic Registrar, HOD, and Lecturer/Tutor users in the system.');

            return;
        }

        $this->purgeOtherPrograms((int) $program->id);
        $this->configureProgram($program, $actors['registrar']);
        $yearId = $this->seedAcademicCalendar();
        $semesters = $this->semesterMap($yearId);
        $semester1Id = (int) $semesters[1];
        $semester2Id = (int) $semesters[2];
        $campusId = (int) (DB::table('campuses')->where('is_active', 1)->value('id') ?? 1);

        $curriculumVersionId = $this->seedCurriculumVersion($program, $yearId, $actors['registrar'], $actors['hod']);
        $units = $this->seedUnits($program, $actors['hod'], $actors['registrar']);
        $this->linkProgramAndCurriculum($program, $curriculumVersionId, $units);

        $this->refreshStudent($student, $program, $campusId, $semester1Id, $curriculumVersionId);

        $allocations = $this->seedAllocations($units, $actors, $semester1Id, $semester2Id, $campusId);
        $this->seedTimetable($program, $curriculumVersionId, $units, $allocations, $semester1Id, $actors);
        $this->seedLessonPlans($allocations, $actors);

        $feeStructure = $this->seedFeeStructure($program, $yearId, $actors['registrar']);
        $this->seedStudentFees($student, $feeStructure, $actors['registrar'], $semester1Id);

        $assessments = app(ContinuousAssessmentService::class);
        $passMark = (float) ($program->fresh()->theory_pass_mark ?: 40);

        // Semester 1: all five units registered; rich attendance / CAT / exam data.
        foreach ($units->where('semester', 1) as $unit) {
            $allocation = $allocations[$unit->unit_code];
            $teacher = $allocation->staff;
            $profile = $this->unitScoreProfile($unit->unit_code);

            $this->ensureRegistration((int) $student->id, $semester1Id, (int) $unit->id, 5);
            $this->seedAttendanceSessions($allocation, $teacher, $actors['hod'], $actors['registrar'], (int) $student->id, $profile['attendance']);
            $this->seedAttendanceSummary((int) $student->id, (int) $unit->id, $semester1Id, $profile['attendance'], true);
            $this->seedExamEligibility((int) $student->id, (int) $unit->id, $semester1Id, $profile['attendance'], true);
            $this->seedExamSchedule((int) $unit->id, $semester1Id, $profile['exam_done']);
            $this->seedUnitContent($unit, $teacher);

            foreach ($this->assessments as $assessment) {
                $raw = $profile['scores'][$assessment['name']] ?? null;
                if ($raw === null) {
                    continue;
                }
                $this->seedCatScore($teacher, (int) $student->id, (int) $unit->id, $semester1Id, $assessment, (float) $raw);
            }

            if ($profile['exam_done'] && $profile['exam'] !== null) {
                $continuous = $assessments->continuousBreakdown((int) $student->id, $allocation);
                $this->seedExamAndGrade(
                    $teacher,
                    (int) $student->id,
                    $unit,
                    $semester1Id,
                    $continuous,
                    (float) $profile['exam'],
                    $assessments,
                    $passMark,
                );
            }
        }

        // Semester 2 units exist and are allocated, but not yet registered (upcoming).
        foreach ($units->where('semester', 2) as $unit) {
            $this->seedExamSchedule((int) $unit->id, $semester2Id, false);
            $this->seedUnitContent($unit, $allocations[$unit->unit_code]->staff);
        }

        $this->command?->info(sprintf(
            'CHP simulation ready: kept program #%d (%s); student %s; %d units; S1=%d S2=%d; tutor=%s; hod=%s; registrar=%s.',
            $program->id,
            $program->program_code,
            $student->registration_number,
            $units->count(),
            $semester1Id,
            $semester2Id,
            $actors['tutor']->fullName(),
            $actors['hod']->fullName(),
            $actors['registrar']->fullName(),
        ));
    }

    /**
     * @return array{registrar: ?Staff, hod: ?Staff, tutor: ?Staff}
     */
    private function resolveActors(int $learningDepartmentId): array
    {
        $rbac = app(RBACService::class);

        $registrarUser = User::query()
            ->whereHas('roles', fn ($q) => $q->where('role_name', 'Academic Registrar'))
            ->first();
        $hodUser = User::query()
            ->whereHas('roles', fn ($q) => $q->where('role_name', 'HOD'))
            ->first();
        $tutorUser = User::query()
            ->whereHas('roles', fn ($q) => $q->where('role_name', 'Lecturer/Tutor'))
            ->where('id', '!=', $hodUser?->id)
            ->orderBy('id')
            ->first()
            ?? User::query()->whereHas('roles', fn ($q) => $q->where('role_name', 'Lecturer/Tutor'))->first();

        $registrar = $registrarUser?->staff;
        $hod = $hodUser?->staff;
        $tutor = $tutorUser?->staff;

        if ($hod) {
            $hodUpdates = [
                'department_id' => $learningDepartmentId,
                'job_title' => $hod->job_title ?: 'Head of Department',
            ];
            if (\Illuminate\Support\Facades\Schema::hasColumn('staff', 'is_teaching_staff')) {
                $hodUpdates['is_teaching_staff'] = 1;
            }
            $hod->update($hodUpdates);
            DB::table('departments')->where('id', $learningDepartmentId)->update(['hod_id' => $hod->id]);

            // HOD may also lecture.
            $lecturerRoleId = (int) Role::query()->where('role_name', 'Lecturer/Tutor')->value('id');
            if ($lecturerRoleId && $hodUser) {
                $rbac->assignRoleToUser($hodUser, $lecturerRoleId, null, $learningDepartmentId);
            }
        }

        if ($tutor) {
            $tutorUpdates = [
                'department_id' => $learningDepartmentId,
                'job_title' => 'Community Health Tutor',
            ];
            if (\Illuminate\Support\Facades\Schema::hasColumn('staff', 'is_teaching_staff')) {
                $tutorUpdates['is_teaching_staff'] = 1;
            }
            $tutor->update($tutorUpdates);
            $lecturerRoleId = (int) Role::query()->where('role_name', 'Lecturer/Tutor')->value('id');
            if ($lecturerRoleId && $tutorUser) {
                $rbac->assignRoleToUser($tutorUser, $lecturerRoleId, null, $learningDepartmentId);
            }
        }

        return compact('registrar', 'hod', 'tutor');
    }

    private function purgeOtherPrograms(int $keepProgramId): void
    {
        $otherIds = AcademicProgram::query()->where('id', '!=', $keepProgramId)->pluck('id')->all();
        if ($otherIds === []) {
            return;
        }

        // Nested timetable sessions first.
        if (Schema::hasTable('program_timetables') && Schema::hasTable('program_timetable_sessions')) {
            $ttIds = DB::table('program_timetables')->whereIn('program_id', $otherIds)->pluck('id');
            if ($ttIds->isNotEmpty()) {
                DB::table('program_timetable_sessions')->whereIn('program_timetable_id', $ttIds)->delete();
            }
        }

        if (Schema::hasTable('curriculum_versions')) {
            $versionIds = DB::table('curriculum_versions')->whereIn('program_id', $otherIds)->pluck('id');
            if ($versionIds->isNotEmpty()) {
                if (Schema::hasTable('curriculum_version_units')) {
                    DB::table('curriculum_version_units')->whereIn('curriculum_version_id', $versionIds)->delete();
                }
                if (Schema::hasTable('curriculum_version_periods')) {
                    DB::table('curriculum_version_periods')->whereIn('curriculum_version_id', $versionIds)->delete();
                }
                DB::table('curriculum_versions')->whereIn('id', $versionIds)->delete();
            }
        }

        foreach ([
            'homepage_carousel_slides',
            'waitlist_entries',
            'rpl_applications',
            'nursing_block_progress',
            'nursing_blocks',
            'program_timetables',
            'program_timetable_templates',
            'program_units',
            'fee_structures',
            'units',
        ] as $table) {
            if (Schema::hasTable($table) && Schema::hasColumn($table, 'program_id')) {
                DB::table($table)->whereIn('program_id', $otherIds)->delete();
            }
        }

        // Re-point any stray applicants/students (should already be CHP-only).
        if (Schema::hasTable('applicants')) {
            DB::table('applicants')->whereIn('program_id', $otherIds)->update(['program_id' => $keepProgramId]);
        }
        if (Schema::hasTable('students')) {
            DB::table('students')->whereIn('program_id', $otherIds)->update(['program_id' => $keepProgramId]);
        }

        AcademicProgram::query()->whereIn('id', $otherIds)->delete();

        $this->command?->info('Removed '.count($otherIds).' other programmes; kept CHP #'.$keepProgramId.'.');
    }

    private function configureProgram(AcademicProgram $program, Staff $registrar): void
    {
        $program->update([
            'duration_months' => 12,
            'semester_count' => 2,
            'block_count' => 0,
            'curriculum_format' => 'semester',
            'min_attendance_pct' => 90,
            'theory_pass_mark' => 40,
            'clinical_pass_mark' => 50,
            'status' => 'active',
            'is_featured_on_homepage' => 1,
            'homepage_display_order' => 1,
            'approved_by_ceo_at' => now()->subMonths(2),
            'updated_at' => now(),
        ]);
    }

    private function seedAcademicCalendar(): int
    {
        $yearId = DB::table('academic_years')->where('year_label', '2026/2027')->value('id');
        if (! $yearId) {
            $yearId = DB::table('academic_years')->insertGetId([
                'year_label' => '2026/2027',
                'start_date' => '2026-08-01',
                'end_date' => '2027-07-31',
                'is_current' => 1,
                'created_at' => now(),
            ]);
        } else {
            DB::table('academic_years')->where('id', $yearId)->update([
                'is_current' => 1,
                'start_date' => '2026-08-01',
                'end_date' => '2027-07-31',
            ]);
        }

        DB::table('academic_years')->where('id', '!=', $yearId)->update(['is_current' => 0]);

        $terms = [
            1 => ['label' => 'Semester 1', 'start' => '2026-08-10', 'end' => '2026-12-18', 'current' => 1],
            2 => ['label' => 'Semester 2', 'start' => '2027-01-12', 'end' => '2027-05-28', 'current' => 0],
        ];

        foreach ($terms as $number => $term) {
            DB::table('semesters')->updateOrInsert(
                ['academic_year_id' => $yearId, 'semester_number' => $number],
                [
                    'semester_label' => $term['label'],
                    'intake_month' => 'August',
                    'start_date' => $term['start'],
                    'end_date' => $term['end'],
                    'registration_open_date' => $term['start'],
                    'registration_close_date' => \Illuminate\Support\Carbon::parse($term['start'])->addWeeks(3)->toDateString(),
                    'is_current' => $term['current'],
                    'created_at' => now(),
                ]
            );
        }

        return (int) $yearId;
    }

    /**
     * @return array<int, int> semester_number => id
     */
    private function semesterMap(int $yearId): array
    {
        return DB::table('semesters')
            ->where('academic_year_id', $yearId)
            ->pluck('id', 'semester_number')
            ->map(fn ($id) => (int) $id)
            ->all();
    }

    private function seedCurriculumVersion(AcademicProgram $program, int $yearId, Staff $registrar, Staff $hod): int
    {
        $existing = DB::table('curriculum_versions')
            ->where('program_id', $program->id)
            ->where('intake_year', 2026)
            ->where('intake_month', 8)
            ->value('id');

        $payload = [
            'academic_year_id' => $yearId,
            'version_label' => 'CHP Aug 2026 intake',
            'version_number' => 1,
            'curriculum_format' => 'semester',
            'status' => 'published',
            'notes' => 'Seeded simulation curriculum for Certificate in Community Health Practice.',
            'created_by' => $hod->id,
            'submitted_at' => now()->subMonths(1),
            'submitted_by' => $hod->id,
            'registrar_approved_at' => now()->subMonths(1)->addDays(2),
            'registrar_approved_by' => $registrar->id,
            'published_at' => now()->subMonths(1)->addDays(3),
            'published_by' => $registrar->id,
            'updated_at' => now(),
        ];

        if ($existing) {
            DB::table('curriculum_versions')->where('id', $existing)->update($payload);

            return (int) $existing;
        }

        return (int) DB::table('curriculum_versions')->insertGetId(array_merge($payload, [
            'program_id' => $program->id,
            'intake_year' => 2026,
            'intake_month' => 8,
            'created_at' => now(),
        ]));
    }

    /**
     * @return \Illuminate\Support\Collection<int, Unit>
     */
    private function seedUnits(AcademicProgram $program, Staff $hod, Staff $registrar)
    {
        $defs = [
            ['code' => 'CHP-01', 'name' => 'Introduction to Community Health', 'semester' => 1, 'contact' => 45, 'learning' => 90, 'practical' => false],
            ['code' => 'CHP-02', 'name' => 'Communication Skills in Health Care', 'semester' => 1, 'contact' => 30, 'learning' => 60, 'practical' => false],
            ['code' => 'CHP-03', 'name' => 'Basic Anatomy and Physiology', 'semester' => 1, 'contact' => 60, 'learning' => 120, 'practical' => false],
            ['code' => 'CHP-04', 'name' => 'First Aid and Basic Life Support', 'semester' => 1, 'contact' => 40, 'learning' => 80, 'practical' => true],
            ['code' => 'CHP-05', 'name' => 'Community Health Promotion', 'semester' => 1, 'contact' => 45, 'learning' => 90, 'practical' => false],
            ['code' => 'CHP-06', 'name' => 'Primary Health Care Systems', 'semester' => 2, 'contact' => 45, 'learning' => 90, 'practical' => false],
            ['code' => 'CHP-07', 'name' => 'Maternal and Child Health', 'semester' => 2, 'contact' => 50, 'learning' => 100, 'practical' => true],
            ['code' => 'CHP-08', 'name' => 'Environmental and Occupational Health', 'semester' => 2, 'contact' => 40, 'learning' => 80, 'practical' => false],
            ['code' => 'CHP-09', 'name' => 'Nutrition and Community Wellness', 'semester' => 2, 'contact' => 40, 'learning' => 80, 'practical' => false],
            ['code' => 'CHP-10', 'name' => 'Community Field Practicum', 'semester' => 2, 'contact' => 80, 'learning' => 160, 'practical' => true],
        ];

        $units = collect();
        foreach ($defs as $index => $def) {
            $unit = Unit::query()->updateOrCreate(
                ['unit_code' => $def['code']],
                [
                    'unit_name' => $def['name'],
                    'description' => "CHP curriculum unit: {$def['name']}.",
                    'department_id' => $program->department_id,
                    'program_id' => $program->id,
                    'semester' => $def['semester'],
                    'credit_hours' => round($def['contact'] / 15, 1),
                    'contact_hours' => $def['contact'],
                    'total_learning_hours' => $def['learning'],
                    'display_priority' => $index + 1,
                    'is_core' => true,
                    'is_practical' => $def['practical'],
                    'assessment_weight_attendance_pct' => 10,
                    'assessment_weight_cat_pct' => 30,
                    'assessment_weight_practical_pct' => $def['practical'] ? 20 : 10,
                    'assessment_weight_exam_pct' => $def['practical'] ? 40 : 50,
                    'status' => 'active',
                    'submitted_at' => now()->subMonths(1),
                    'submitted_by' => $hod->id,
                    'registrar_approved_at' => now()->subMonths(1)->addDay(),
                    'registrar_approved_by' => $registrar->id,
                    'created_by' => $hod->id,
                    'updated_at' => now(),
                ]
            );
            $units->push($unit);
        }

        // Remove any leftover non-CHP unit codes that somehow remain under this program.
        Unit::query()
            ->where('program_id', $program->id)
            ->whereNotIn('unit_code', $units->pluck('unit_code'))
            ->delete();

        return $units->keyBy('unit_code');
    }

    /**
     * @param  \Illuminate\Support\Collection<string, Unit>  $units
     */
    private function linkProgramAndCurriculum(AcademicProgram $program, int $curriculumVersionId, $units): void
    {
        DB::table('program_units')->where('program_id', $program->id)->delete();
        DB::table('curriculum_version_units')->where('curriculum_version_id', $curriculumVersionId)->delete();

        if (Schema::hasTable('curriculum_version_periods')) {
            DB::table('curriculum_version_periods')->where('curriculum_version_id', $curriculumVersionId)->delete();
            DB::table('curriculum_version_periods')->insert([
                [
                    'curriculum_version_id' => $curriculumVersionId,
                    'semester' => 1,
                    'start_date' => '2026-08-10',
                    'end_date' => '2026-12-18',
                    'learning_start_date' => '2026-08-10',
                    'learning_end_date' => '2026-11-28',
                    'exam_start_date' => '2026-12-01',
                    'exam_end_date' => '2026-12-18',
                ],
                [
                    'curriculum_version_id' => $curriculumVersionId,
                    'semester' => 2,
                    'start_date' => '2027-01-12',
                    'end_date' => '2027-05-28',
                    'learning_start_date' => '2027-01-12',
                    'learning_end_date' => '2027-05-01',
                    'exam_start_date' => '2027-05-05',
                    'exam_end_date' => '2027-05-28',
                ],
            ]);
        }

        $order = 1;
        foreach ($units as $unit) {
            DB::table('program_units')->insert([
                'program_id' => $program->id,
                'unit_id' => $unit->id,
                'semester' => $unit->semester,
                'is_compulsory' => 1,
                'display_order' => $order,
                'priority' => $order,
                'contact_hours' => $unit->contact_hours,
                'total_learning_hours' => $unit->total_learning_hours,
                'is_active' => 1,
            ]);

            DB::table('curriculum_version_units')->insert([
                'curriculum_version_id' => $curriculumVersionId,
                'unit_id' => $unit->id,
                'semester' => $unit->semester,
                'is_compulsory' => 1,
                'display_order' => $order,
                'priority' => $order,
                'credit_hours' => $unit->credit_hours,
                'contact_hours' => $unit->contact_hours,
                'total_learning_hours' => $unit->total_learning_hours,
            ]);
            $order++;
        }
    }

    private function refreshStudent(Student $student, AcademicProgram $program, int $campusId, int $semester1Id, int $curriculumVersionId): void
    {
        $payload = [
            'program_id' => $program->id,
            'enrollment_campus_id' => $campusId ?: $student->enrollment_campus_id,
            'current_semester_id' => $semester1Id,
            'enrollment_status' => 'active',
            'cohort_intake' => $student->cohort_intake ?: 'August 2026',
            'date_of_admission' => $student->date_of_admission ?: '2026-08-01',
            'fee_clearance_status' => 'cleared',
            'overall_balance' => 18500,
            'is_active' => 1,
            'updated_at' => now(),
        ];
        if (Schema::hasColumn('students', 'academic_clearance_status')) {
            $payload['academic_clearance_status'] = $student->academic_clearance_status ?: 'pending';
        }
        $student->update($payload);

        // Store curriculum linkage on applicant if columns exist.
        if ($student->application_id && Schema::hasColumn('applicants', 'curriculum_version_id')) {
            DB::table('applicants')->where('id', $student->application_id)->update([
                'program_id' => $program->id,
                'curriculum_version_id' => $curriculumVersionId,
            ]);
        } elseif ($student->application_id) {
            DB::table('applicants')->where('id', $student->application_id)->update([
                'program_id' => $program->id,
            ]);
        }
    }

    /**
     * @param  \Illuminate\Support\Collection<string, Unit>  $units
     * @param  array{registrar: Staff, hod: Staff, tutor: Staff}  $actors
     * @return array<string, UnitAllocation>
     */
    private function seedAllocations($units, array $actors, int $semester1Id, int $semester2Id, int $campusId): array
    {
        // Tutor teaches odd-coded units; HOD teaches even-coded + practicum.
        $tutorCodes = ['CHP-01', 'CHP-02', 'CHP-04', 'CHP-06', 'CHP-08'];
        $hodCodes = ['CHP-03', 'CHP-05', 'CHP-07', 'CHP-09', 'CHP-10'];

        $out = [];
        foreach ($units as $code => $unit) {
            $staff = in_array($code, $hodCodes, true) ? $actors['hod'] : $actors['tutor'];
            $semesterId = ((int) $unit->semester === 2) ? $semester2Id : $semester1Id;

            $allocation = UnitAllocation::query()->updateOrCreate(
                [
                    'unit_id' => $unit->id,
                    'staff_id' => $staff->id,
                    'semester_id' => $semesterId,
                ],
                [
                    'campus_id' => $campusId,
                    'contact_hours_assigned' => max(2, (int) round(((int) $unit->contact_hours) / 15)),
                    'is_coordinator' => in_array($code, $hodCodes, true) ? 1 : 0,
                    'is_active' => 1,
                ]
            );
            $out[$code] = $allocation->fresh(['unit', 'semester', 'staff']);
        }

        return $out;
    }

    /**
     * @param  \Illuminate\Support\Collection<string, Unit>  $units
     * @param  array<string, UnitAllocation>  $allocations
     * @param  array{hod: Staff, tutor: Staff}  $actors
     */
    private function seedTimetable(AcademicProgram $program, int $curriculumVersionId, $units, array $allocations, int $semester1Id, array $actors): void
    {
        $timetableId = DB::table('program_timetables')
            ->where('program_id', $program->id)
            ->where('curriculum_version_id', $curriculumVersionId)
            ->where('teaching_period', 1)
            ->where('timetable_kind', 'lesson')
            ->value('id');

        $payload = [
            'curriculum_version_id' => $curriculumVersionId,
            'teaching_period' => 1,
            'title' => 'CHP Semester 1 teaching timetable',
            'timetable_kind' => 'lesson',
            'campus_id' => DB::table('campuses')->where('is_active', 1)->value('id'),
            'status' => 'published',
            'published_at' => now()->subWeeks(2),
            'published_by' => $actors['hod']->id,
            'updated_at' => now(),
        ];

        if ($timetableId) {
            DB::table('program_timetables')->where('id', $timetableId)->update($payload);
        } else {
            $timetableId = DB::table('program_timetables')->insertGetId(array_merge($payload, [
                'program_id' => $program->id,
                'created_at' => now(),
            ]));
        }

        DB::table('program_timetable_sessions')->where('program_timetable_id', $timetableId)->delete();

        $day = 1;
        foreach ($units->where('semester', 1) as $unit) {
            $allocation = $allocations[$unit->unit_code];
            DB::table('program_timetable_sessions')->insert([
                'program_timetable_id' => $timetableId,
                'unit_id' => $unit->id,
                'staff_id' => $allocation->staff_id,
                'day_of_week' => $day,
                'start_time' => '08:00:00',
                'end_time' => '10:00:00',
                'venue' => 'CHS Room '.str_pad((string) $day, 2, '0', STR_PAD_LEFT),
                'session_type' => $unit->is_practical ? 'practical' : 'lecture',
                'title' => $unit->unit_name,
            ]);
            $day = $day >= 5 ? 1 : $day + 1;
        }
    }

    /**
     * @param  array<string, UnitAllocation>  $allocations
     * @param  array{hod: Staff, tutor: Staff}  $actors
     */
    private function seedLessonPlans(array $allocations, array $actors): void
    {
        foreach (['CHP-01', 'CHP-03', 'CHP-05'] as $code) {
            if (! isset($allocations[$code])) {
                continue;
            }
            $allocation = $allocations[$code];
            $planNumber = 'LP-CHP-'.$code.'-W1';
            DB::table('lesson_plans')->updateOrInsert(
                ['plan_number' => $planNumber],
                [
                    'unit_allocation_id' => $allocation->id,
                    'prepared_by' => $allocation->staff_id,
                    'lesson_title' => $allocation->unit->unit_name.' — Week 1',
                    'lesson_objectives' => 'Introduce core concepts; link theory to community practice.',
                    'topics_covered' => 'Foundations, definitions, local case examples',
                    'competencies_targeted' => $code.'-C1',
                    'contact_hours' => 4,
                    'week_number' => 1,
                    'planned_date' => '2026-08-18',
                    'teaching_methods' => 'Lecture, discussion, demonstration',
                    'resources_required' => 'Projector, handouts',
                    'status' => 'approved',
                    'hod_id' => $actors['hod']->id,
                    'hod_action_at' => now()->subWeeks(1),
                    'registrar_visible' => 1,
                    'source_type' => 'form',
                    'tutor_verified_at' => now()->subWeeks(1)->subDay(),
                    'updated_at' => now(),
                ]
            );
        }
    }

    private function seedFeeStructure(AcademicProgram $program, int $yearId, Staff $registrar): FeeStructure
    {
        $fee = FeeStructure::query()->updateOrCreate(
            [
                'program_id' => $program->id,
                'academic_year_id' => $yearId,
            ],
            [
                'application_fee' => 1000,
                'tuition_fee' => 35000,
                'caution_fee' => 3000,
                'computer_lab_fee' => 1500,
                'transport_fee' => 1200,
                'transport_optional' => 1,
                'accommodation_fee' => 12000,
                'accommodation_optional' => 1,
                'partnership_fee' => 1000,
                'id_card_fee' => 500,
                'student_union_fee' => 800,
                'emergency_fund_fee' => 500,
                'library_fee' => 1200,
                'examination_external_fee' => 2500,
                'attachment_fee' => 3000,
                'qa_annual_fee' => 1000,
                'requires_indexing_nck' => 0,
                'indexing_nck_fee' => 0,
                'graduation_fee' => 4000,
                'is_approved' => 1,
                'approved_by' => $registrar->id,
                'approved_at' => now()->subWeeks(3),
                'effective_from' => '2026-08-01',
                'is_active' => 1,
                'status' => 'approved',
            ]
        );
        $fee->recalculateTotal();
        $fee->save();

        return $fee->fresh();
    }

    private function seedStudentFees(Student $student, FeeStructure $feeStructure, Staff $registrar, int $semester1Id): void
    {
        $invoiceService = app(InvoiceService::class);

        $existsTuition = DB::table('invoices')
            ->where('student_id', $student->id)
            ->where('invoice_type', 'tuition')
            ->exists();

        if (! $existsTuition) {
            $invoice = $invoiceService->generateSemesterInvoice($student, $feeStructure, $registrar->id, false);
            DB::table('invoices')->where('id', $invoice->id)->update([
                'semester_id' => $semester1Id,
                'fee_structure_id' => $feeStructure->id,
                'is_sent_to_portal' => 1,
                'sent_at' => now()->subWeeks(2),
            ]);

            // Partial payment so portal finance shows activity.
            $payAmount = 30000;
            if (Schema::hasTable('payments')) {
                $accountId = DB::table('invoices')->where('id', $invoice->id)->value('student_account_id');
                $paymentNumber = 'PAY-CHP-'.strtoupper(Str::random(6));
                DB::table('payments')->insert([
                    'payment_number' => $paymentNumber,
                    'student_id' => $student->id,
                    'student_account_id' => $accountId,
                    'invoice_id' => $invoice->id,
                    'amount' => $payAmount,
                    'payment_method' => 'mpesa',
                    'payment_reference' => 'CHP-SIM-'.strtoupper(Str::random(6)),
                    'payment_date' => now()->subWeeks(1)->toDateString(),
                    'recorded_by' => $registrar->id,
                    'status' => 'completed',
                    'created_at' => now()->subWeeks(1),
                ]);
                $invoice->refresh();
                $newPaid = min((float) $invoice->amount, (float) $invoice->amount_paid + $payAmount);
                $balance = max(0, (float) $invoice->amount - $newPaid);
                DB::table('invoices')->where('id', $invoice->id)->update([
                    'amount_paid' => $newPaid,
                    'balance' => $balance,
                    'status' => $balance <= 0 ? 'paid' : 'partial',
                    'updated_at' => now(),
                ]);
            }
        }

        DB::table('students')->where('id', $student->id)->update([
            'fee_clearance_status' => 'cleared',
            'overall_balance' => 18500,
            'updated_at' => now(),
        ]);
    }

    /**
     * @return array{attendance: float, exam: ?float, exam_done: bool, scores: array<string, float>}
     */
    private function unitScoreProfile(string $code): array
    {
        return match ($code) {
            'CHP-01' => [
                'attendance' => 96.0,
                'exam' => 68.0,
                'exam_done' => true,
                'scores' => [
                    'CAT 1' => 24, 'CAT 2' => 26, 'Theoretical review 1' => 16,
                    'Assignment 1' => 17, 'Practical 1' => 21, 'Skills lab 1' => 22,
                ],
            ],
            'CHP-02' => [
                'attendance' => 94.0,
                'exam' => 72.0,
                'exam_done' => true,
                'scores' => [
                    'CAT 1' => 27, 'CAT 2' => 25, 'Theoretical review 1' => 17,
                    'Assignment 1' => 18, 'Practical 1' => 20, 'Skills lab 1' => 21,
                ],
            ],
            'CHP-03' => [
                'attendance' => 91.0,
                'exam' => 58.0,
                'exam_done' => true,
                'scores' => [
                    'CAT 1' => 22, 'CAT 2' => 21, 'Theoretical review 1' => 14,
                    'Assignment 1' => 15, 'Practical 1' => 19, 'Skills lab 1' => 18,
                ],
            ],
            'CHP-04' => [
                'attendance' => 93.0,
                'exam' => null,
                'exam_done' => false,
                'scores' => [
                    'CAT 1' => 25, 'CAT 2' => 24, 'Theoretical review 1' => 16,
                    'Assignment 1' => 17, 'Practical 1' => 22, 'Skills lab 1' => 23,
                ],
            ],
            default => [ // CHP-05
                'attendance' => 90.5,
                'exam' => null,
                'exam_done' => false,
                'scores' => [
                    'CAT 1' => 23, 'CAT 2' => 22, 'Theoretical review 1' => 15,
                    'Assignment 1' => 16, 'Practical 1' => 20, 'Skills lab 1' => 19,
                ],
            ],
        };
    }

    private function ensureRegistration(int $studentId, int $semesterId, int $unitId, int $unitCount): void
    {
        DB::table('student_semester_registrations')->updateOrInsert(
            ['student_id' => $studentId, 'semester_id' => $semesterId],
            [
                'registration_date' => '2026-08-12',
                'registration_type' => 'admin',
                'unit_count' => $unitCount,
                'status' => 'registered',
                'is_fee_cleared' => 1,
            ]
        );

        $registrationId = DB::table('student_semester_registrations')
            ->where('student_id', $studentId)
            ->where('semester_id', $semesterId)
            ->value('id');

        if ($registrationId) {
            DB::table('registered_units')->updateOrInsert(
                ['semester_registration_id' => $registrationId, 'unit_id' => $unitId],
                ['is_additional' => 0, 'created_at' => now()],
            );
        }
    }

    private function seedAttendanceSessions(
        UnitAllocation $allocation,
        Staff $teacher,
        Staff $hod,
        Staff $registrar,
        int $studentId,
        float $attendancePct,
    ): void {
        $totalSessions = 10;
        $targetPresent = (int) round(($attendancePct / 100) * $totalSessions);

        for ($i = 1; $i <= $totalSessions; $i++) {
            $sessionNumber = sprintf('ATT-CHP-%s-%02d', $allocation->id, $i);
            $sessionDate = now()->subWeeks($totalSessions - $i)->toDateString();
            $isPresent = $i <= $targetPresent;

            $sessionId = DB::table('attendance_sessions')->where('session_number', $sessionNumber)->value('id');
            if (! $sessionId) {
                $sessionId = DB::table('attendance_sessions')->insertGetId([
                    'session_number' => $sessionNumber,
                    'unit_allocation_id' => $allocation->id,
                    'session_date' => $sessionDate,
                    'start_time' => '08:00:00',
                    'end_time' => '10:00:00',
                    'venue' => 'CHS Lab 1',
                    'session_type' => 'physical',
                    'total_expected_attendees' => 1,
                    'recorded_by' => $teacher->id,
                    'recorded_at' => now()->subWeeks($totalSessions - $i),
                    'is_locked' => $i <= 8 ? 1 : 0,
                    'verification_status' => $i <= 6 ? 'registrar_verified' : ($i <= 8 ? 'submitted' : 'draft'),
                    'submitted_at' => $i <= 8 ? now()->subWeeks($totalSessions - $i)->addDay() : null,
                    'hod_verified_by' => $i <= 8 ? $hod->id : null,
                    'hod_verified_at' => $i <= 8 ? now()->subWeeks($totalSessions - $i)->addDays(2) : null,
                    'registrar_verified_by' => $i <= 6 ? $registrar->id : null,
                    'registrar_verified_at' => $i <= 6 ? now()->subWeeks($totalSessions - $i)->addDays(3) : null,
                ]);
            }

            DB::table('attendance_records')->updateOrInsert(
                ['session_id' => $sessionId, 'student_id' => $studentId],
                [
                    'is_present' => $isPresent ? 1 : 0,
                    'sign_in_time' => $isPresent ? '08:05:00' : null,
                    'recorded_by_tutor' => 1,
                    'verified_by_hod' => $i <= 8 ? 1 : 0,
                ]
            );
        }
    }

    private function seedAttendanceSummary(int $studentId, int $unitId, int $semesterId, float $attendance, bool $feeCleared): void
    {
        $flag = $attendance >= 90 ? 'green' : ($attendance >= 75 ? 'amber' : 'red');

        DB::table('attendance_summaries')->updateOrInsert(
            ['student_id' => $studentId, 'unit_id' => $unitId, 'semester_id' => $semesterId],
            [
                'total_sessions' => 10,
                'total_present' => (int) round($attendance / 10),
                'attendance_percentage' => $attendance,
                'status_flag' => $flag,
                'last_calculated_at' => now(),
                'exam_eligibility_blocked' => ($attendance >= 90 && $feeCleared) ? 0 : 1,
            ]
        );
    }

    private function seedExamEligibility(int $studentId, int $unitId, int $semesterId, float $attendance, bool $feeCleared): void
    {
        $eligible = $attendance >= 90 && $feeCleared;

        DB::table('exam_eligibility_matrix')->updateOrInsert(
            ['student_id' => $studentId, 'unit_id' => $unitId, 'semester_id' => $semesterId],
            [
                'attendance_percentage' => $attendance,
                'attendance_check_passed' => $attendance >= 90 ? 1 : 0,
                'fee_clearance_check_passed' => $feeCleared ? 1 : 0,
                'eligible_for_exams' => $eligible ? 1 : 0,
                'calculated_at' => now(),
                'created_at' => now(),
            ]
        );
    }

    private function seedExamSchedule(int $unitId, int $semesterId, bool $completed): void
    {
        DB::table('exam_schedules')->updateOrInsert(
            ['unit_id' => $unitId, 'semester_id' => $semesterId, 'exam_type' => 'main'],
            [
                'exam_date' => $completed ? '2026-12-02' : '2026-12-10',
                'start_time' => '09:00:00',
                'end_time' => '11:00:00',
                'venue' => 'Main Exam Hall',
                'total_candidates' => 1,
                'status' => $completed ? 'completed' : 'scheduled',
            ]
        );
    }

    private function seedUnitContent(Unit $unit, Staff $teacher): void
    {
        if (! Schema::hasTable('unit_contents')) {
            return;
        }

        DB::table('unit_contents')->updateOrInsert(
            [
                'unit_id' => $unit->id,
                'title' => $unit->unit_name.' — Course outline',
            ],
            [
                'created_by' => $teacher->id,
                'content_type' => 'notes',
                'content_text' => "Seeded outline and reading list for {$unit->unit_name}.",
                'status' => 'published',
                'published_at' => now()->subWeeks(3),
                'display_order' => 1,
                'updated_at' => now(),
                'created_at' => now()->subWeeks(3),
            ]
        );
    }

    /**
     * @param  array{name: string, type: string, max: float}  $assessment
     */
    private function seedCatScore(Staff $staff, int $studentId, int $unitId, int $semesterId, array $assessment, float $raw): void
    {
        $score = min($raw, $assessment['max']);
        $percentage = $assessment['max'] > 0 ? round(($score / $assessment['max']) * 100, 2) : 0;

        DB::table('cat_scores')->updateOrInsert(
            [
                'student_id' => $studentId,
                'unit_id' => $unitId,
                'semester_id' => $semesterId,
                'assessment_name' => $assessment['name'],
            ],
            [
                'assessment_type' => $assessment['type'],
                'max_score' => $assessment['max'],
                'score_obtained' => $score,
                'percentage_score' => $percentage,
                'weight_in_final' => 0,
                'recorded_by' => $staff->id,
                'recorded_at' => now()->subDays(10),
                'updated_at' => now(),
            ]
        );
    }

    /**
     * @param  array<string, mixed>  $continuous
     */
    private function seedExamAndGrade(
        Staff $staff,
        int $studentId,
        Unit $unit,
        int $semesterId,
        array $continuous,
        float $examScore,
        ContinuousAssessmentService $assessments,
        float $passMark,
    ): void {
        $finalTotal = $assessments->finalScoreWithExam($continuous['cumulative'], $examScore, $unit);
        $gradeLetter = $assessments->gradeLetterForScore($finalTotal, $passMark);
        $examCardId = $this->ensureExamCard($studentId, $semesterId);

        DB::table('exam_results')->updateOrInsert(
            ['student_id' => $studentId, 'unit_id' => $unit->id, 'semester_id' => $semesterId],
            [
                'exam_card_id' => $examCardId,
                'cat_total' => $continuous['cat_avg'],
                'practical_total' => $continuous['practical_avg'],
                'final_exam_score' => $examScore,
                'final_total_score' => $finalTotal,
                'grade_letter' => $gradeLetter,
                'grade_points' => $this->gradePoints($gradeLetter),
                'theory_pass_check' => $finalTotal >= $passMark ? 1 : 0,
                'is_published' => 1,
                'published_at' => now()->subDays(3),
                'board_approved' => 1,
                'board_approved_at' => now()->subDays(4),
                'entered_by' => $staff->id,
                'updated_at' => now(),
                'created_at' => now()->subDays(5),
            ]
        );

        DB::table('grade_records')->updateOrInsert(
            ['student_id' => $studentId, 'unit_id' => $unit->id, 'semester_id' => $semesterId],
            [
                'final_score' => $finalTotal,
                'grade_letter' => $gradeLetter,
                'grade_points' => $this->gradePoints($gradeLetter),
                'credit_hours' => $unit->credit_hours ?? 3,
                'recorded_at' => now(),
                'created_at' => now(),
            ]
        );
    }

    private function ensureExamCard(int $studentId, int $semesterId): int
    {
        $existing = DB::table('exam_cards')
            ->where('student_id', $studentId)
            ->where('semester_id', $semesterId)
            ->value('id');

        if ($existing) {
            return (int) $existing;
        }

        return (int) DB::table('exam_cards')->insertGetId([
            'exam_card_number' => 'EC-CHP-'.strtoupper(Str::random(5)),
            'student_id' => $studentId,
            'semester_id' => $semesterId,
            'issued_at' => now()->subWeeks(3),
        ]);
    }

    private function gradePoints(string $letter): float
    {
        return match ($letter) {
            'A' => 4.0,
            'B' => 3.0,
            'C' => 2.0,
            'D' => 1.0,
            default => 0.0,
        };
    }
}
