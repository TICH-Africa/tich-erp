<?php

namespace App\Services;

use App\Models\AcademicProgram;
use App\Models\CurriculumVersion;
use App\Models\Unit;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ProgramExamService
{
    public function __construct(
        protected StudentAcademicRecordService $academicRecords,
        protected ExamScheduleSyncService $examScheduleSync,
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

        return [
            'teaching_period' => $teachingPeriod,
            'teaching_periods' => $teachingPeriods,
            'period' => $period,
            'unit_ids' => $semesterUnitIds,
            'student_ids' => $studentIds,
            'students' => $students,
            'summary' => $this->summary($semesterUnitIds, $studentIds),
            'exam_periods' => $examPeriods,
            'units' => $this->unitsWithAssessment($intake, $teachingPeriod),
            'schedules' => $this->examSchedules($semesterUnitIds),
            'papers' => $this->examinationPapers($semesterUnitIds),
            'grade_rows' => $this->gradeRows($semesterUnitIds, $studentIds),
            'exam_results' => $this->examResults($semesterUnitIds, $studentIds),
            'eligibility' => $this->eligibilitySummary($semesterUnitIds, $studentIds),
            'at_risk_students' => $this->atRiskStudents($semesterUnitIds, $studentIds),
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

        $unit->update([
            'assessment_weight_cat_pct' => $data['cat_weight'],
            'assessment_weight_practical_pct' => $data['practical_weight'],
            'assessment_weight_attendance_pct' => $data['attendance_weight'],
            'assessment_weight_exam_pct' => $data['exam_weight'],
        ]);

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

    public function resolveTab(string $tab): string
    {
        $tabs = ['overview', 'grading', 'schedule', 'papers', 'results'];

        return in_array($tab, $tabs, true) ? $tab : 'overview';
    }
}
