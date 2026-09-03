<?php

namespace App\Services;

use App\Models\AcademicProgram;
use App\Models\CurriculumVersion;
use App\Models\CurriculumVersionPeriod;
use App\Models\ObjectiveAssessment;
use App\Models\ObjectiveSubmission;
use App\Models\Semester;
use App\Models\Student;
use App\Models\UnitAllocation;
use App\Models\UnitContent;
use App\Support\IntakeIdentity;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class StudentAcademicRecordService
{
    public function __construct(protected CurriculumVersionService $curriculumVersions) {}

    /**
     * @return array<string, mixed>
     */
    public function forStudent(Student $student, bool $allowProvisionalCurriculum = true): array
    {
        $student->loadMissing(['applicant', 'program', 'campus']);

        $curriculum = $this->resolveCurriculum($student, $allowProvisionalCurriculum);
        $curriculumUnits = $curriculum
            ? $curriculum->items->sortBy(['semester', 'display_order', 'priority'])->values()
            : collect();

        $periods = $curriculum
            ? $curriculum->periods->sortBy('semester')->values()
            : collect();

        $currentPeriod = $this->resolveCurrentPeriod($periods, $curriculumUnits);
        $currentPeriodUnits = $currentPeriod
            ? $curriculumUnits->filter(function ($mapping) use ($currentPeriod) {
                if ($currentPeriod->block_id) {
                    return (int) $mapping->block_id === (int) $currentPeriod->block_id;
                }

                return (int) $mapping->semester === (int) $currentPeriod->semester;
            })->values()
            : collect();

        $registrations = DB::table('student_semester_registrations as ssr')
            ->join('semesters as s', 's.id', '=', 'ssr.semester_id')
            ->leftJoin('academic_years as ay', 'ay.id', '=', 's.academic_year_id')
            ->where('ssr.student_id', $student->id)
            ->orderByDesc('ssr.registration_date')
            ->select([
                'ssr.*',
                's.semester_label',
                's.semester_number',
                'ay.year_label',
            ])
            ->get();

        $registeredUnits = $this->registeredUnitsForStudents([$student->id])->get($student->id, collect());
        $grades = $this->gradesForStudents([$student->id])->get($student->id, collect());
        $catScores = $this->catScoresForStudents([$student->id])->get($student->id, collect());
        $examResults = $this->examResultsForStudents([$student->id])->get($student->id, collect());
        $attendance = $this->attendanceForStudents([$student->id])->get($student->id, collect());

        $currentSemester = $student->current_semester_id
            ? Semester::query()->with('academicYear')->find($student->current_semester_id)
            : Semester::query()->with('academicYear')->where('is_current', 1)->first();

        $examPortal = app(StudentExamCardService::class)->portalData($student);

        $assessments = collect();
        $mySubmissions = collect();
        $unitContent = collect();
        if ($student->program_id) {
            $allocationIds = UnitAllocation::query()
                ->whereHas('unit', fn ($q) => $q->where('department_id', $student->program->department_id))
                ->where('is_active', 1)
                ->pluck('id');

            if ($allocationIds->isNotEmpty()) {
                $assessments = ObjectiveAssessment::query()
                    ->with(['allocation.unit', 'questions'])
                    ->whereIn('unit_allocation_id', $allocationIds)
                    ->whereIn('status', ['ready', 'graded'])
                    ->orderByDesc('created_at')
                    ->get();

                $mySubmissions = ObjectiveSubmission::query()
                    ->where('student_id', $student->id)
                    ->whereIn('objective_assessment_id', $assessments->pluck('id'))
                    ->get()
                    ->keyBy('objective_assessment_id');
            }

            $unitIds = $curriculumUnits->pluck('unit_id')->filter()->unique()->values()->merge(
                $registeredUnits->pluck('unit_id')->filter()->unique()->values()->all()
            )->unique()->values()->all();

            if ($unitIds !== []) {
                $unitContent = UnitContent::query()
                    ->with(['unit', 'unitAllocation'])
                    ->whereIn('unit_id', $unitIds)
                    ->where('status', 'published')
                    ->orderByDesc('display_order')
                    ->orderByDesc('created_at')
                    ->get();
            }
        }

        return [
            'curriculum' => $curriculum,
            'curriculum_is_published' => $curriculum?->isPublished() ?? false,
            'curriculum_units' => $curriculumUnits,
            'curriculum_by_semester' => collect($curriculumUnits->groupBy('semester')),
            'periods' => $periods,
            'periods_by_semester' => $periods->keyBy('semester'),
            'current_period' => $currentPeriod,
            'current_period_units' => $currentPeriodUnits,
            'current_period_status' => $this->periodStatus($currentPeriod),
            'registrations' => $registrations,
            'registered_units' => $registeredUnits,
            'registered_unit_count' => $registeredUnits->count(),
            'grades' => $grades,
            'cat_scores' => $catScores,
            'exam_results' => $examResults,
            'attendance' => $attendance,
            'current_semester' => $currentSemester,
            'exam_portal' => $examPortal,
            'assessments' => $assessments,
            'my_submissions' => $mySubmissions,
            'unit_content' => $unitContent,
        ];
    }

    /**
     * @param  list<int>  $studentIds
     * @return Collection<int, array{registered_units: int, grades: int, cat_scores: int, exam_results: int}>
     */
    public function rosterSummaries(array $studentIds): Collection
    {
        if ($studentIds === []) {
            return collect();
        }

        $registeredUnits = DB::table('registered_units as ru')
            ->join('student_semester_registrations as ssr', 'ssr.id', '=', 'ru.semester_registration_id')
            ->whereIn('ssr.student_id', $studentIds)
            ->groupBy('ssr.student_id')
            ->selectRaw('ssr.student_id, count(*) as total')
            ->pluck('total', 'student_id');

        $grades = DB::table('grade_records')
            ->whereIn('student_id', $studentIds)
            ->groupBy('student_id')
            ->selectRaw('student_id, count(*) as total')
            ->pluck('total', 'student_id');

        $catScores = DB::table('cat_scores')
            ->whereIn('student_id', $studentIds)
            ->groupBy('student_id')
            ->selectRaw('student_id, count(*) as total')
            ->pluck('total', 'student_id');

        $examResults = DB::table('exam_results')
            ->whereIn('student_id', $studentIds)
            ->groupBy('student_id')
            ->selectRaw('student_id, count(*) as total')
            ->pluck('total', 'student_id');

        return collect($studentIds)->mapWithKeys(function (int $studentId) use ($registeredUnits, $grades, $catScores, $examResults) {
            return [$studentId => [
                'registered_units' => (int) ($registeredUnits[$studentId] ?? 0),
                'grades' => (int) ($grades[$studentId] ?? 0),
                'cat_scores' => (int) ($catScores[$studentId] ?? 0),
                'exam_results' => (int) ($examResults[$studentId] ?? 0),
            ]];
        });
    }

    /**
     * @return array{matched: Collection<int, Student>, other: Collection<int, Student>}
     */
    public function enrolledForProgram(AcademicProgram $program, ?CurriculumVersion $intake, ?string $status = null): array
    {
        $base = Student::query()
            ->with(['applicant', 'campus', 'user:id,email,staff_id,student_id'])
            ->where('program_id', $program->id)
            ->orderBy('registration_number');

        if ($status) {
            $base->where('enrollment_status', $status);
        }

        $students = $base->get();

        if (! $intake?->intake_year || ! $intake?->intake_month) {
            return [
                'matched' => $students,
                'other' => collect(),
            ];
        }

        $matched = $students->filter(fn (Student $student) => IntakeIdentity::studentMatchesIntake($student, $intake))->values();

        $other = $students->reject(fn (Student $student) => IntakeIdentity::studentMatchesIntake($student, $intake))->values();

        return [
            'matched' => $matched,
            'other' => $other,
        ];
    }

    /**
     * @param  list<int>  $studentIds
     * @return Collection<int, Collection<int, object>>
     */
    private function registeredUnitsForStudents(array $studentIds): Collection
    {
        if ($studentIds === []) {
            return collect();
        }

        return DB::table('registered_units as ru')
            ->join('student_semester_registrations as ssr', 'ssr.id', '=', 'ru.semester_registration_id')
            ->join('units as u', 'u.id', '=', 'ru.unit_id')
            ->join('semesters as s', 's.id', '=', 'ssr.semester_id')
            ->whereIn('ssr.student_id', $studentIds)
            ->orderByDesc('ssr.registration_date')
            ->select([
                'ssr.student_id',
                'ru.id',
                'ru.is_additional',
                'u.unit_code',
                'u.unit_name',
                's.semester_label',
                's.semester_number',
                'ssr.status as registration_status',
                'ssr.registration_date',
            ])
            ->get()
            ->groupBy('student_id');
    }

    /**
     * @param  list<int>  $studentIds
     * @return Collection<int, Collection<int, object>>
     */
    private function gradesForStudents(array $studentIds): Collection
    {
        if ($studentIds === []) {
            return collect();
        }

        return DB::table('grade_records as gr')
            ->join('units as u', 'u.id', '=', 'gr.unit_id')
            ->join('semesters as s', 's.id', '=', 'gr.semester_id')
            ->whereIn('gr.student_id', $studentIds)
            ->orderByDesc('gr.recorded_at')
            ->select([
                'gr.student_id',
                'gr.final_score',
                'gr.grade_letter',
                'gr.grade_points',
                'gr.recorded_at',
                'u.unit_code',
                'u.unit_name',
                's.semester_label',
            ])
            ->get()
            ->groupBy('student_id');
    }

    /**
     * @param  list<int>  $studentIds
     * @return Collection<int, Collection<int, object>>
     */
    private function catScoresForStudents(array $studentIds): Collection
    {
        if ($studentIds === []) {
            return collect();
        }

        return DB::table('cat_scores as cs')
            ->join('units as u', 'u.id', '=', 'cs.unit_id')
            ->join('semesters as s', 's.id', '=', 'cs.semester_id')
            ->whereIn('cs.student_id', $studentIds)
            ->orderByDesc('cs.recorded_at')
            ->select([
                'cs.student_id',
                'cs.assessment_type',
                'cs.assessment_name',
                'cs.max_score',
                'cs.score_obtained',
                'cs.percentage_score',
                'cs.weight_in_final',
                'cs.verified_by_hod',
                'cs.recorded_at',
                'u.unit_code',
                'u.unit_name',
                's.semester_label',
            ])
            ->get()
            ->groupBy('student_id');
    }

    /**
     * @param  list<int>  $studentIds
     * @return Collection<int, Collection<int, object>>
     */
    private function examResultsForStudents(array $studentIds): Collection
    {
        if ($studentIds === []) {
            return collect();
        }

        return DB::table('exam_results as er')
            ->join('units as u', 'u.id', '=', 'er.unit_id')
            ->join('semesters as s', 's.id', '=', 'er.semester_id')
            ->whereIn('er.student_id', $studentIds)
            ->orderByDesc('er.created_at')
            ->select([
                'er.student_id',
                'er.cat_total',
                'er.practical_total',
                'er.final_exam_score',
                'er.final_total_score',
                'er.grade_letter',
                'er.grade_points',
                'er.is_supplementary',
                'er.is_special_exam',
                'er.is_published',
                'er.published_at',
                'er.created_at',
                'u.unit_code',
                'u.unit_name',
                's.semester_label',
            ])
            ->get()
            ->groupBy('student_id');
    }

    /**
     * @param  list<int>  $studentIds
     * @return Collection<int, Collection<int, object>>
     */
    private function attendanceForStudents(array $studentIds): Collection
    {
        if ($studentIds === []) {
            return collect();
        }

        return DB::table('attendance_summaries as a')
            ->join('units as u', 'u.id', '=', 'a.unit_id')
            ->join('semesters as s', 's.id', '=', 'a.semester_id')
            ->whereIn('a.student_id', $studentIds)
            ->orderByDesc('a.last_calculated_at')
            ->select([
                'a.student_id',
                'a.attendance_percentage',
                'a.total_present',
                'a.total_sessions',
                'a.status_flag',
                'u.unit_code',
                'u.unit_name',
                's.semester_label',
            ])
            ->get()
            ->groupBy('student_id');
    }

    private function resolveCurriculum(Student $student, bool $allowProvisionalCurriculum = true): ?CurriculumVersion
    {
        if (! $student->program_id) {
            return null;
        }

        $applicant = $student->applicant;
        $programId = (int) $student->program_id;
        $relations = ['items.unit', 'periods'];

        if ($applicant?->intake_year && $applicant?->intake_month) {
            $publishedIntake = CurriculumVersion::query()
                ->with($relations)
                ->where('program_id', $programId)
                ->where('status', 'published')
                ->where('intake_year', $applicant->intake_year)
                ->where('intake_month', $applicant->intake_month)
                ->orderByDesc('published_at')
                ->first();

            if ($publishedIntake) {
                return $publishedIntake;
            }
        }

        $published = $this->curriculumVersions->publishedVersionForProgram($programId);

        if ($published) {
            return $published->load($relations);
        }

        if (! $allowProvisionalCurriculum) {
            return null;
        }

        if ($applicant?->intake_year && $applicant?->intake_month) {
            $intakeMatch = CurriculumVersion::query()
                ->with($relations)
                ->where('program_id', $programId)
                ->whereNot('status', 'superseded')
                ->where('intake_year', $applicant->intake_year)
                ->where('intake_month', $applicant->intake_month)
                ->whereHas('items')
                ->orderByDesc('intake_year')
                ->orderByDesc('intake_month')
                ->first();

            if ($intakeMatch) {
                return $intakeMatch;
            }
        }

        return CurriculumVersion::query()
            ->with($relations)
            ->where('program_id', $programId)
            ->whereNot('status', 'superseded')
            ->whereHas('items')
            ->orderByDesc('intake_year')
            ->orderByDesc('intake_month')
            ->first();
    }

    /**
     * @param  Collection<int, CurriculumVersionPeriod>  $periods
     * @param  Collection<int, \App\Models\CurriculumVersionUnit>  $curriculumUnits
     */
    private function resolveCurrentPeriod(Collection $periods, Collection $curriculumUnits): ?CurriculumVersionPeriod
    {
        if ($periods->isNotEmpty()) {
            $today = now()->startOfDay();
            $ordered = $periods->sortBy('semester')->values();

            $inProgress = $ordered->first(fn (CurriculumVersionPeriod $period) => $period->isActiveOn($today));
            if ($inProgress) {
                return $inProgress;
            }

            $latestCompleted = $ordered
                ->filter(fn (CurriculumVersionPeriod $period) => $period->end_date && $period->end_date->lt($today))
                ->sortByDesc('semester')
                ->first();

            if ($latestCompleted) {
                $nextSemester = $ordered
                    ->first(fn (CurriculumVersionPeriod $period) => $period->semester > $latestCompleted->semester);

                if ($nextSemester) {
                    return $nextSemester;
                }

                return $latestCompleted;
            }

            $upcoming = $ordered
                ->filter(fn (CurriculumVersionPeriod $period) => $period->start_date && $period->start_date->gt($today))
                ->sortBy('start_date')
                ->first();

            if ($upcoming) {
                return $upcoming;
            }

            return $ordered->first();
        }

        if ($curriculumUnits->isEmpty()) {
            return null;
        }

        $semester = (int) $curriculumUnits->sortBy('semester')->first()->semester;

        return new CurriculumVersionPeriod([
            'semester' => $semester,
            'block_id' => null,
            'start_date' => null,
            'end_date' => null,
        ]);
    }

    private function periodStatus(?CurriculumVersionPeriod $period): ?string
    {
        if (! $period?->start_date || ! $period?->end_date) {
            return null;
        }

        $today = now()->startOfDay();

        if ($today->lt($period->start_date)) {
            return 'upcoming';
        }

        if ($today->gt($period->end_date)) {
            return 'completed';
        }

        return 'in_progress';
    }
}
