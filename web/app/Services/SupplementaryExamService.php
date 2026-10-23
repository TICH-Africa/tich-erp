<?php

namespace App\Services;

use App\Models\AcademicProgram;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * The supplementary sitting: a student who failed a unit's exam sits it again, and the new mark
 * replaces the fail everywhere the school reads a grade.
 *
 * A supplementary is recorded on its own exam_results row so the original sitting is not lost, but
 * it overwrites the single grade_records row the unit has for that student - that row is what the
 * transcript, the GPA and the cohort hold all read.
 */
class SupplementaryExamService
{
    public function __construct(
        protected ContinuousAssessmentService $assessments,
        protected StaffExamMarksService $marks,
        protected StaffPortalService $staffPortal,
        protected AuditService $audits,
    ) {}

    /**
     * Every unit this cohort still carries an F in, with the sitting that failed, what it took to
     * pass, and any supplementary already recorded against it.
     *
     * @param  list<int>  $unitIds
     * @param  list<int>  $studentIds
     * @return Collection<int, object>
     */
    public function outstanding(array $unitIds, array $studentIds): Collection
    {
        if ($unitIds === [] || $studentIds === []) {
            return collect();
        }

        $rows = DB::table('grade_records as gr')
            ->join('students as st', 'st.id', '=', 'gr.student_id')
            ->leftJoin('applicants as ap', 'ap.id', '=', 'st.application_id')
            ->join('units as u', 'u.id', '=', 'gr.unit_id')
            ->join('semesters as s', 's.id', '=', 'gr.semester_id')
            ->whereIn('gr.unit_id', $unitIds)
            ->whereIn('gr.student_id', $studentIds)
            ->whereRaw('UPPER(gr.grade_letter) = ?', ['F'])
            ->orderBy('st.registration_number')
            ->orderBy('u.unit_code')
            ->select([
                'gr.id as grade_record_id',
                'gr.student_id',
                'gr.unit_id',
                'gr.semester_id',
                'gr.final_score',
                'gr.credit_hours',
                'st.registration_number',
                'ap.first_name',
                'ap.surname',
                'u.unit_code',
                'u.unit_name',
                'u.program_id',
                's.semester_label',
                's.semester_number',
            ])
            ->get();

        if ($rows->isEmpty()) {
            return collect();
        }

        $recorded = DB::table('exam_results')
            ->whereIn('unit_id', $unitIds)
            ->whereIn('student_id', $studentIds)
            ->where('is_supplementary', 1)
            ->get(['student_id', 'unit_id', 'semester_id', 'final_exam_score', 'final_total_score', 'grade_letter', 'updated_at'])
            ->keyBy(fn ($row) => $row->student_id.'-'.$row->unit_id.'-'.$row->semester_id);

        $mainSittings = DB::table('exam_results')
            ->whereIn('unit_id', $unitIds)
            ->whereIn('student_id', $studentIds)
            ->where('is_supplementary', 0)
            ->get(['student_id', 'unit_id', 'semester_id', 'final_exam_score', 'is_published'])
            ->keyBy(fn ($row) => $row->student_id.'-'.$row->unit_id.'-'.$row->semester_id);

        $units = Unit::query()
            ->whereIn('id', $rows->pluck('unit_id')->unique()->all())
            ->get()
            ->keyBy('id');

        $passMarks = DB::table('academic_programs')
            ->whereIn('id', $rows->pluck('program_id')->unique()->filter()->all())
            ->pluck('theory_pass_mark', 'id');

        return $rows->map(function ($row) use ($recorded, $mainSittings, $units, $passMarks) {
            $key = $row->student_id.'-'.$row->unit_id.'-'.$row->semester_id;
            $unit = $units->get($row->unit_id);
            $weights = $this->assessments->weightProfile($unit);
            $passMark = (float) ($passMarks[$row->program_id] ?? 40);
            $continuous = $this->assessments->continuousBreakdownFor(
                (int) $row->student_id,
                (int) $row->unit_id,
                (int) $row->semester_id,
                $unit,
            );

            return (object) [
                'grade_record_id' => (int) $row->grade_record_id,
                'student_id' => (int) $row->student_id,
                'registration_number' => $row->registration_number,
                'student_name' => trim(($row->first_name ?? '').' '.($row->surname ?? '')),
                'unit_id' => (int) $row->unit_id,
                'unit_code' => $row->unit_code,
                'unit_name' => $row->unit_name,
                'semester_id' => (int) $row->semester_id,
                'semester_label' => $row->semester_label,
                'failed_total' => (float) $row->final_score,
                'failed_exam_score' => $mainSittings->get($key)?->final_exam_score !== null
                    ? (float) $mainSittings->get($key)->final_exam_score
                    : null,
                'continuous_cumulative' => $continuous['cumulative'],
                'exam_weight' => $weights['exam'],
                'pass_mark' => $passMark,
                'pass_exam_score' => $this->examScoreToPass($continuous['cumulative'], $weights, $passMark),
                'supplementary' => $recorded->get($key),
                'is_published' => (bool) ($mainSittings->get($key)?->is_published ?? false),
            ];
        });
    }

    /**
     * Record the supplementary marks an officer entered. Each key is a grade record - one student,
     * one unit, one semester - so the same screen can settle a whole class's repeats at once.
     *
     * @param  array<int, mixed>  $marks
     * @return list<array<string, mixed>>
     */
    public function record(User $officer, AcademicProgram $program, array $marks): array
    {
        $staff = $this->staffPortal->staffForUser($officer);

        if (! $staff) {
            throw ValidationException::withMessages([
                'marks' => 'Marks are recorded against a staff register entry, and this account has none. Ask ICT to link it to staff.',
            ]);
        }

        $records = DB::table('grade_records as gr')
            ->join('units as u', 'u.id', '=', 'gr.unit_id')
            ->join('students as st', 'st.id', '=', 'gr.student_id')
            ->whereIn('gr.id', array_keys($marks))
            ->where('u.program_id', $program->id)
            ->get(['gr.id', 'gr.student_id', 'gr.unit_id', 'gr.semester_id', 'gr.grade_letter', 'gr.final_score', 'u.unit_code', 'st.registration_number']);

        $saved = [];

        foreach ($records as $record) {
            $raw = $marks[$record->id] ?? null;

            if ($raw === null || $raw === '') {
                continue;
            }

            $saved[] = $this->recordOne($officer, $staff, $record, (float) $raw);
        }

        if ($saved === []) {
            throw ValidationException::withMessages([
                'marks' => 'Type the supplementary mark next to the student it belongs to before saving.',
            ]);
        }

        return $saved;
    }

    /**
     * @return array<string, mixed>
     */
    private function recordOne(User $officer, object $staff, object $record, float $examScore): array
    {
        $unit = Unit::query()->with('program')->findOrFail($record->unit_id);
        $weights = $this->assessments->weightProfile($unit);
        $passMark = (float) ($unit->program?->theory_pass_mark ?? 40);
        $examScore = min(max(0, $examScore), 100);

        $continuous = $this->assessments->continuousBreakdownFor(
            (int) $record->student_id,
            (int) $record->unit_id,
            (int) $record->semester_id,
            $unit,
        );
        $finalTotal = $this->assessments->finalScoreWithExam($continuous['cumulative'], $examScore, $unit);
        $letter = $this->assessments->gradeLetterForScore($finalTotal, $passMark);
        $points = $this->assessments->gradePoints($letter);
        $passed = strtoupper($letter) !== 'F';

        $match = [
            'student_id' => (int) $record->student_id,
            'unit_id' => (int) $record->unit_id,
            'semester_id' => (int) $record->semester_id,
        ];

        $main = DB::table('exam_results')->where($match)->where('is_supplementary', 0)->first(['id', 'exam_card_id', 'is_published']);
        $examCardId = (int) ($main?->exam_card_id ?? $this->marks->ensureExamCard((int) $record->student_id, (int) $record->semester_id));

        $payload = [
            'exam_card_id' => $examCardId,
            'cat_total' => $continuous['cat_avg'],
            'practical_total' => $continuous['practical_avg'],
            'final_exam_score' => $examScore,
            'final_total_score' => $finalTotal,
            'grade_letter' => $letter,
            'grade_points' => $points,
            'theory_pass_check' => $passed ? 1 : 0,
            'is_published' => (bool) ($main?->is_published ?? false),
            'entered_by' => (int) $staff->id,
            'updated_at' => now(),
        ];

        $supplementary = DB::table('exam_results')
            ->where($match)
            ->where('is_supplementary', 1)
            ->value('id');

        if ($supplementary) {
            DB::table('exam_results')->where('id', $supplementary)->update($payload);
        } else {
            $supplementary = DB::table('exam_results')->insertGetId([
                ...$match,
                ...$payload,
                'is_supplementary' => 1,
                'created_at' => now(),
            ]);
        }

        // The main sitting stays on record as the fail it was; the grade the school reads is the
        // supplementary one.
        if ($main) {
            DB::table('exam_results')->where('id', $main->id)->update([
                'supplementary_triggered' => 1,
                'updated_at' => now(),
            ]);
        }

        // One writer for the grade the school reads, so a later edit of the lecturer's marks cannot
        // pull the transcript back down to the sitting that failed.
        $this->assessments->recordGrade(
            (int) $record->student_id,
            (int) $record->unit_id,
            (int) $record->semester_id,
            $unit,
            $continuous['cumulative'],
            $passMark,
        );

        $this->audits->log(
            action: 'academics.exams.supplementary_recorded',
            entityType: 'grade_records',
            entityId: (int) $record->id,
            oldValue: [
                'final_score' => (float) $record->final_score,
                'grade_letter' => $record->grade_letter,
            ],
            newValue: [
                'supplementary_exam_score' => $examScore,
                'final_score' => $finalTotal,
                'grade_letter' => $letter,
                'exam_result_id' => (int) $supplementary,
                'unit_code' => $record->unit_code,
                'passed' => $passed,
            ],
            reason: 'Supplementary exam mark recorded for '.$record->unit_code.'.',
            userId: $officer->id,
        );

        return [
            'registration_number' => $record->registration_number ?? '',
            'unit_code' => $record->unit_code,
            'exam_score' => $examScore,
            'final_score' => $finalTotal,
            'grade_letter' => $letter,
            'passed' => $passed,
        ];
    }

    /**
     * The exam mark that carries the continuous score already banked to the pass mark.
     *
     * @param  array<string, float>  $weights
     */
    private function examScoreToPass(float $cumulative, array $weights, float $passMark): ?float
    {
        if ($weights['exam'] <= 0) {
            return null;
        }

        $continuousWeight = $weights['cat'] + $weights['practical'] + $weights['attendance'];
        $fromContinuous = $cumulative * $continuousWeight / 100;

        return round(max(0, min(100, (($passMark - $fromContinuous) * 100) / $weights['exam'])), 1);
    }
}
