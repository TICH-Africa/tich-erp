<?php

namespace App\Services;

use App\Models\AcademicProgram;
use App\Models\CurriculumVersion;
use App\Models\Semester;
use App\Models\Student;
use App\Models\Unit;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ProgramExamService
{
    public function __construct(
        protected StudentAcademicRecordService $academicRecords,
        protected ExamScheduleSyncService $examScheduleSync,
        protected AuditService $auditService,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function hubData(AcademicProgram $program, ?CurriculumVersion $intake, array $periodDates, int $teachingPeriod = 1, bool $syncFromTimetable = false): array
    {
        if (! $intake) {
            return [
                'teaching_period' => $teachingPeriod,
                'teaching_periods' => collect(),
                'period' => null,
                'unit_ids' => [],
                'student_ids' => [],
                'students' => collect(),
                'summary' => [],
                'exam_periods' => collect(),
                'units' => collect(),
                'schedules' => collect(),
                'papers' => collect(),
                'grade_rows' => collect(),
                'exam_results' => collect(),
                'eligibility' => [],
                'at_risk_students' => collect(),
                'eligible_roster' => ['semester_id' => null, 'semester_label' => null, 'eligible' => collect(), 'blocked' => collect(), 'pending' => collect()],
                'timetable_synced' => 0,
            ];
        }

        $teachingPeriod = $this->resolveTeachingPeriod($intake, $teachingPeriod);
        $timetableSynced = $syncFromTimetable
            ? $this->examScheduleSync->syncFromExamTimetable($program, $intake, $teachingPeriod)
            : 0;
        $teachingPeriods = $this->teachingPeriodsForIntake($intake);
        $semesterUnitIds = $this->unitIdsForSemester($intake, $teachingPeriod);
        $students = $this->academicRecords->enrolledForProgram($program, $intake)['matched'];
        $studentIds = $students->pluck('id')->all();

        $periodKey = $teachingPeriod.':';
        $period = collect($periodDates)->get($periodKey);
        $examPeriods = $period && (! empty($period->exam_start_date) || ! empty($period->exam_end_date))
            ? collect([$period])
            : collect();

        $semesterUnits = $this->unitsWithAssessment($intake, $teachingPeriod);
        $semesterId = $this->examScheduleSync->resolveSemesterId($intake, $teachingPeriod);

        return [
            'teaching_period' => $teachingPeriod,
            'teaching_periods' => $teachingPeriods,
            'period' => $period,
            'unit_ids' => $semesterUnitIds,
            'student_ids' => $studentIds,
            'students' => $students,
            'summary' => $this->summary($semesterUnitIds, $studentIds),
            'exam_periods' => $examPeriods,
            'units' => $semesterUnits,
            'schedules' => $this->examSchedules($semesterUnitIds),
            'papers' => $this->examinationPapers($semesterUnitIds),
            'grade_rows' => $this->gradeRows($semesterUnitIds, $studentIds),
            'exam_results' => $this->examResults($semesterUnitIds, $studentIds),
            'eligibility' => $this->eligibilitySummary($semesterUnitIds, $studentIds),
            'at_risk_students' => $this->atRiskStudents($semesterUnitIds, $studentIds),
            'eligible_roster' => $this->semesterEligibleRoster($students, $semesterUnitIds, $semesterUnits, $semesterId),
            'timetable_synced' => $timetableSynced,
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function updateExamSchedule(int $scheduleId, array $data, array $unitIds): void
    {
        $schedule = DB::table('exam_schedules as es')
            ->where('es.id', $scheduleId)
            ->whereIn('es.unit_id', $unitIds)
            ->first();

        if (! $schedule) {
            throw ValidationException::withMessages([
                'schedule' => 'Exam schedule not found for this semester.',
            ]);
        }

        $old = (array) $schedule;

        DB::table('exam_schedules')
            ->where('id', $scheduleId)
            ->update([
                'exam_date' => $data['exam_date'],
                'start_time' => $data['start_time'],
                'end_time' => $data['end_time'],
                'venue' => $data['venue'],
                'exam_type' => $data['exam_type'],
                'invigilator_id' => $data['invigilator_id'] ?? null,
                'status' => $data['status'],
            ]);

        $this->auditService->log(
            'academics.exam_schedule.updated',
            'exam_schedules',
            $scheduleId,
            [
                'exam_date' => $old['exam_date'] ?? null,
                'start_time' => $old['start_time'] ?? null,
                'end_time' => $old['end_time'] ?? null,
                'venue' => $old['venue'] ?? null,
                'exam_type' => $old['exam_type'] ?? null,
                'status' => $old['status'] ?? null,
            ],
            $data,
            'Exam schedule updated',
            'success',
            Auth::id(),
        );
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function updateUnitAssessmentWeights(Unit $unit, array $data): Unit
    {
        $total = (float) $data['cat_weight']
            + (float) $data['practical_weight']
            + (float) $data['attendance_weight']
            + (float) $data['exam_weight'];

        if (abs($total - 100) > 0.01) {
            throw ValidationException::withMessages([
                'weights' => 'Assessment weights must add up to 100%.',
            ]);
        }

        $oldWeights = $unit->only([
            'assessment_weight_cat_pct',
            'assessment_weight_practical_pct',
            'assessment_weight_attendance_pct',
            'assessment_weight_exam_pct',
        ]);

        $unit->update([
            'assessment_weight_cat_pct' => $data['cat_weight'],
            'assessment_weight_practical_pct' => $data['practical_weight'],
            'assessment_weight_attendance_pct' => $data['attendance_weight'],
            'assessment_weight_exam_pct' => $data['exam_weight'],
        ]);

        $this->auditService->log(
            'academics.unit.assessment_weights_updated',
            'units',
            $unit->id,
            $oldWeights,
            [
                'assessment_weight_cat_pct' => $data['cat_weight'],
                'assessment_weight_practical_pct' => $data['practical_weight'],
                'assessment_weight_attendance_pct' => $data['attendance_weight'],
                'assessment_weight_exam_pct' => $data['exam_weight'],
            ],
            'Unit assessment weights updated',
            'success',
            Auth::id(),
        );

        return $unit->fresh();
    }

    public function resolveTeachingPeriod(CurriculumVersion $intake, ?int $requested = null): int
    {
        $periods = $this->teachingPeriodsForIntake($intake);

        if ($requested && $periods->contains($requested)) {
            return $requested;
        }

        return (int) ($periods->first() ?? 1);
    }

    /**
     * @return Collection<int, int>
     */
    public function teachingPeriodsForIntake(CurriculumVersion $intake): Collection
    {
        return $intake->items()
            ->get(['semester'])
            ->pluck('semester')
            ->unique()
            ->sort()
            ->values()
            ->map(fn ($semester) => (int) $semester);
    }

    /**
     * @return list<int>
     */
    public function unitIdsForSemester(CurriculumVersion $intake, int $teachingPeriod): array
    {
        return $intake->items()
            ->where('semester', $teachingPeriod)
            ->pluck('unit_id')
            ->unique()
            ->values()
            ->all();
    }

    /**
     * @param  list<int>  $unitIds
     * @param  list<int>  $studentIds
     * @return array<string, int|float>
     */
    private function summary(array $unitIds, array $studentIds): array
    {
        if ($unitIds === [] || $studentIds === []) {
            return [
                'students' => count($studentIds),
                'units' => count($unitIds),
                'cat_entries' => 0,
                'grade_records' => 0,
                'exam_results' => 0,
                'papers_ready' => 0,
                'schedules' => 0,
                'blocked_eligibility' => 0,
            ];
        }

        return [
            'students' => count($studentIds),
            'units' => count($unitIds),
            'cat_entries' => (int) DB::table('cat_scores')
                ->whereIn('unit_id', $unitIds)
                ->whereIn('student_id', $studentIds)
                ->count(),
            'grade_records' => (int) DB::table('grade_records')
                ->whereIn('unit_id', $unitIds)
                ->whereIn('student_id', $studentIds)
                ->count(),
            'exam_results' => (int) DB::table('exam_results')
                ->whereIn('unit_id', $unitIds)
                ->whereIn('student_id', $studentIds)
                ->count(),
            'papers_ready' => (int) DB::table('examination_papers')
                ->whereIn('unit_id', $unitIds)
                ->where('status', 'approved')
                ->count(),
            'schedules' => (int) DB::table('exam_schedules')
                ->whereIn('unit_id', $unitIds)
                ->count(),
            'blocked_eligibility' => (int) DB::table('exam_eligibility_matrix')
                ->whereIn('unit_id', $unitIds)
                ->whereIn('student_id', $studentIds)
                ->where('eligible_for_exams', 0)
                ->count(),
        ];
    }

    /**
     * @return Collection<int, object>
     */
    private function unitsWithAssessment(CurriculumVersion $intake, ?int $teachingPeriod = null): Collection
    {
        $query = $intake->items()->with('unit')->orderBy('display_order')->orderBy('priority');

        if ($teachingPeriod !== null) {
            $query->where('semester', $teachingPeriod);
        }

        return $query->get()
            ->map(function ($item) {
                $unit = $item->unit;

                return (object) [
                    'unit_id' => $item->unit_id,
                    'unit_code' => $unit?->unit_code,
                    'unit_name' => $unit?->unit_name,
                    'semester' => $item->semester,
                    'contact_hours' => $item->contact_hours,
                    'cat_weight' => $unit?->assessment_weight_cat_pct ?? 30,
                    'practical_weight' => $unit?->assessment_weight_practical_pct ?? 15,
                    'attendance_weight' => $unit?->assessment_weight_attendance_pct ?? 5,
                    'exam_weight' => $unit?->assessment_weight_exam_pct ?? 50,
                ];
            })
            ->sortBy('unit_code')
            ->values();
    }

    /**
     * @param  list<int>  $unitIds
     * @return Collection<int, object>
     */
    private function examSchedules(array $unitIds): Collection
    {
        if ($unitIds === []) {
            return collect();
        }

        return DB::table('exam_schedules as es')
            ->join('units as u', 'u.id', '=', 'es.unit_id')
            ->join('semesters as s', 's.id', '=', 'es.semester_id')
            ->leftJoin('staff as inv', 'inv.id', '=', 'es.invigilator_id')
            ->whereIn('es.unit_id', $unitIds)
            ->orderBy('es.exam_date')
            ->orderBy('es.start_time')
            ->select([
                'es.*',
                'u.unit_code',
                'u.unit_name',
                's.semester_label',
                'inv.first_name as invigilator_first',
                'inv.surname as invigilator_surname',
            ])
            ->get();
    }

    /**
     * @param  list<int>  $unitIds
     * @return Collection<int, object>
     */
    private function examinationPapers(array $unitIds): Collection
    {
        if ($unitIds === []) {
            return collect();
        }

        return DB::table('examination_papers as ep')
            ->join('units as u', 'u.id', '=', 'ep.unit_id')
            ->join('semesters as s', 's.id', '=', 'ep.semester_id')
            ->whereIn('ep.unit_id', $unitIds)
            ->orderByDesc('ep.created_at')
            ->select([
                'ep.*',
                'u.unit_code',
                'u.unit_name',
                's.semester_label',
            ])
            ->get();
    }

    /**
     * @param  list<int>  $unitIds
     * @param  list<int>  $studentIds
     * @return Collection<int, object>
     */
    private function gradeRows(array $unitIds, array $studentIds): Collection
    {
        if ($unitIds === [] || $studentIds === []) {
            return collect();
        }

        return DB::table('grade_records as gr')
            ->join('students as st', 'st.id', '=', 'gr.student_id')
            ->join('units as u', 'u.id', '=', 'gr.unit_id')
            ->join('semesters as s', 's.id', '=', 'gr.semester_id')
            ->whereIn('gr.unit_id', $unitIds)
            ->whereIn('gr.student_id', $studentIds)
            ->orderBy('st.registration_number')
            ->orderBy('u.unit_code')
            ->select([
                'gr.*',
                'st.registration_number',
                'u.unit_code',
                'u.unit_name',
                's.semester_label',
            ])
            ->get();
    }

    /**
     * @param  list<int>  $unitIds
     * @param  list<int>  $studentIds
     * @return Collection<int, object>
     */
    private function examResults(array $unitIds, array $studentIds): Collection
    {
        if ($unitIds === [] || $studentIds === []) {
            return collect();
        }

        return DB::table('exam_results as er')
            ->join('students as st', 'st.id', '=', 'er.student_id')
            ->join('units as u', 'u.id', '=', 'er.unit_id')
            ->join('semesters as s', 's.id', '=', 'er.semester_id')
            ->whereIn('er.unit_id', $unitIds)
            ->whereIn('er.student_id', $studentIds)
            ->orderByDesc('er.created_at')
            ->select([
                'er.*',
                'st.registration_number',
                'u.unit_code',
                'u.unit_name',
                's.semester_label',
            ])
            ->get();
    }

    /**
     * @param  list<int>  $unitIds
     * @param  list<int>  $studentIds
     * @return array<string, int>
     */
    private function eligibilitySummary(array $unitIds, array $studentIds): array
    {
        if ($unitIds === [] || $studentIds === []) {
            return ['eligible' => 0, 'blocked' => 0, 'pending' => 0];
        }

        $rows = DB::table('exam_eligibility_matrix')
            ->whereIn('unit_id', $unitIds)
            ->whereIn('student_id', $studentIds)
            ->selectRaw('
                SUM(CASE WHEN eligible_for_exams = 1 THEN 1 ELSE 0 END) as eligible,
                SUM(CASE WHEN eligible_for_exams = 0 THEN 1 ELSE 0 END) as blocked
            ')
            ->first();

        $total = (int) ($rows->eligible ?? 0) + (int) ($rows->blocked ?? 0);
        $expected = count($studentIds) * count($unitIds);

        return [
            'eligible' => (int) ($rows->eligible ?? 0),
            'blocked' => (int) ($rows->blocked ?? 0),
            'pending' => max(0, $expected - $total),
        ];
    }

    /**
     * @param  list<int>  $unitIds
     * @param  list<int>  $studentIds
     * @return Collection<int, object>
     */
    private function atRiskStudents(array $unitIds, array $studentIds): Collection
    {
        if ($unitIds === [] || $studentIds === []) {
            return collect();
        }

        return DB::table('grade_records as gr')
            ->join('students as st', 'st.id', '=', 'gr.student_id')
            ->join('units as u', 'u.id', '=', 'gr.unit_id')
            ->whereIn('gr.unit_id', $unitIds)
            ->whereIn('gr.student_id', $studentIds)
            ->where('gr.final_score', '<', 40)
            ->orderBy('gr.final_score')
            ->select([
                'gr.final_score',
                'gr.grade_letter',
                'st.id as student_id',
                'st.registration_number',
                'u.unit_code',
            ])
            ->limit(25)
            ->get();
    }

    /**
     * @param  Collection<int, Student>  $students
     * @param  list<int>  $unitIds
     * @param  Collection<int, object>  $units
     * @return array{semester_id: ?int, semester_label: ?string, eligible: Collection, blocked: Collection, pending: Collection}
     */
    private function semesterEligibleRoster(Collection $students, array $unitIds, Collection $units, ?int $semesterId): array
    {
        $empty = [
            'semester_id' => $semesterId,
            'semester_label' => null,
            'eligible' => collect(),
            'blocked' => collect(),
            'pending' => collect(),
        ];

        if ($students->isEmpty() || $unitIds === [] || ! $semesterId) {
            if ($semesterId) {
                $empty['semester_label'] = $this->semesterLabelFor($semesterId);
            }

            return $empty;
        }

        $students->loadMissing(['applicant', 'campus']);
        $studentIds = $students->pluck('id')->all();
        $semesterLabel = $this->semesterLabelFor($semesterId);

        $eligibility = DB::table('exam_eligibility_matrix')
            ->where('semester_id', $semesterId)
            ->whereIn('student_id', $studentIds)
            ->whereIn('unit_id', $unitIds)
            ->get()
            ->groupBy('student_id');

        $attendance = DB::table('attendance_summaries')
            ->where('semester_id', $semesterId)
            ->whereIn('student_id', $studentIds)
            ->whereIn('unit_id', $unitIds)
            ->get()
            ->keyBy(fn ($row) => "{$row->student_id}:{$row->unit_id}");

        $grades = DB::table('grade_records')
            ->where('semester_id', $semesterId)
            ->whereIn('student_id', $studentIds)
            ->whereIn('unit_id', $unitIds)
            ->get()
            ->keyBy(fn ($row) => "{$row->student_id}:{$row->unit_id}");

        $schedules = DB::table('exam_schedules')
            ->where('semester_id', $semesterId)
            ->whereIn('unit_id', $unitIds)
            ->get()
            ->keyBy('unit_id');

        $eligible = collect();
        $blocked = collect();
        $pending = collect();

        foreach ($students as $student) {
            $unitRows = [];
            $eligibleCount = 0;
            $blockedCount = 0;
            $pendingCount = 0;

            foreach ($units as $unit) {
                $unitId = (int) $unit->unit_id;
                $key = "{$student->id}:{$unitId}";
                $elig = $eligibility->get($student->id)?->firstWhere('unit_id', $unitId);
                $att = $attendance->get($key);
                $grade = $grades->get($key);
                $schedule = $schedules->get($unitId);
                $isEligible = $elig !== null ? (bool) $elig->eligible_for_exams : null;

                if ($isEligible === true) {
                    $eligibleCount++;
                } elseif ($isEligible === false) {
                    $blockedCount++;
                } else {
                    $pendingCount++;
                }

                $unitRows[] = (object) [
                    'unit_id' => $unitId,
                    'unit_code' => $unit->unit_code,
                    'unit_name' => $unit->unit_name,
                    'contact_hours' => $unit->contact_hours,
                    'attendance_percentage' => $elig?->attendance_percentage ?? $att?->attendance_percentage,
                    'status_flag' => $att?->status_flag,
                    'fee_cleared' => $elig ? (bool) $elig->fee_clearance_check_passed : ($student->fee_clearance_status === 'cleared'),
                    'eligible_for_exams' => $isEligible,
                    'block_reason' => $this->eligibilityBlockReason($elig, $att, $student),
                    'cumulative_score' => $grade?->final_score,
                    'grade_letter' => $grade?->grade_letter,
                    'grade_points' => $grade?->grade_points,
                    'exam_date' => $schedule?->exam_date,
                    'start_time' => $schedule?->start_time,
                    'end_time' => $schedule?->end_time,
                    'venue' => $schedule?->venue,
                    'exam_type' => $schedule?->exam_type,
                ];
            }

            $row = (object) [
                'student_id' => $student->id,
                'registration_number' => $student->registration_number,
                'student_name' => trim(($student->applicant?->first_name ?? '').' '.($student->applicant?->surname ?? '')),
                'fee_clearance_status' => $student->fee_clearance_status,
                'enrollment_status' => $student->enrollment_status,
                'campus_name' => $student->campus?->campus_name,
                'cohort_intake' => $student->cohort_intake,
                'eligible_unit_count' => $eligibleCount,
                'blocked_unit_count' => $blockedCount,
                'pending_unit_count' => $pendingCount,
                'total_units' => count($unitIds),
                'units' => $unitRows,
            ];

            if ($eligibleCount > 0) {
                $eligible->push($row);
            } elseif ($pendingCount === count($unitIds)) {
                $pending->push($row);
            } else {
                $blocked->push($row);
            }
        }

        return [
            'semester_id' => $semesterId,
            'semester_label' => $semesterLabel,
            'eligible' => $eligible->sortBy('registration_number')->values(),
            'blocked' => $blocked->sortBy('registration_number')->values(),
            'pending' => $pending->sortBy('registration_number')->values(),
        ];
    }

    private function eligibilityBlockReason(?object $eligibility, ?object $attendance, Student $student): ?string
    {
        if ($eligibility && $eligibility->eligible_for_exams) {
            return null;
        }

        if ($eligibility && ! $eligibility->eligible_for_exams) {
            if (! $eligibility->attendance_check_passed) {
                return 'Attendance below programme threshold';
            }
            if (! $eligibility->fee_clearance_check_passed) {
                return 'Fees not cleared';
            }

            return 'Exam eligibility blocked';
        }

        if ($attendance?->status_flag === 'red') {
            return 'Attendance RED flag';
        }

        if ($student->fee_clearance_status !== 'cleared') {
            return 'Fees not cleared';
        }

        return 'Eligibility not calculated yet';
    }

    private function semesterLabelFor(int $semesterId): ?string
    {
        $row = DB::table('semesters')->where('id', $semesterId)->first(['semester_label', 'semester_number']);

        if (! $row) {
            return null;
        }

        return Semester::normalizeLabel($row->semester_label, (int) $row->semester_number);
    }

    public function resolveTab(string $tab): string
    {
        $tabs = ['overview', 'grading', 'schedule', 'papers', 'results'];

        return in_array($tab, $tabs, true) ? $tab : 'overview';
    }
}
