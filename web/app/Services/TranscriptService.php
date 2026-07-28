<?php

namespace App\Services;

use App\Models\Student;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class TranscriptService
{
    public function __construct(protected AuditService $auditService) {}
    /**
     * @return array<string, mixed>
     */
    public function build(Student $student): array
    {
        $student->loadMissing(['applicant', 'program.department', 'campus']);

        $rows = $this->transcriptRows($student);
        $semesterBlocks = $this->groupBySemester($rows);
        $cumulativeGpa = $this->calculateGpa($rows);
        $this->persistGpaSnapshots($student, $semesterBlocks, $cumulativeGpa);

        $this->auditService->log(
            'sis.transcript.generated',
            'students',
            $student->id,
            null,
            [
                'units_completed' => $rows->count(),
                'cumulative_gpa' => $cumulativeGpa,
            ],
            'Student transcript generated',
            'success',
            Auth::id(),
        );

        return [
            'student' => $student,
            'program' => $student->program,
            'rows' => $rows,
            'semester_blocks' => $semesterBlocks,
            'cumulative_gpa' => $cumulativeGpa,
            'total_credits' => round((float) $rows->sum('credit_hours'), 2),
            'units_completed' => $rows->count(),
            'generated_at' => now(),
        ];
    }

    /**
     * @return Collection<int, object>
     */
    private function transcriptRows(Student $student): Collection
    {
        return DB::table('grade_records as gr')
            ->join('units as u', 'u.id', '=', 'gr.unit_id')
            ->join('semesters as s', 's.id', '=', 'gr.semester_id')
            ->leftJoin('academic_years as ay', 'ay.id', '=', 's.academic_year_id')
            ->leftJoin('exam_results as er', function ($join) {
                $join->on('er.student_id', '=', 'gr.student_id')
                    ->on('er.unit_id', '=', 'gr.unit_id')
                    ->on('er.semester_id', '=', 'gr.semester_id');
            })
            ->where('gr.student_id', $student->id)
            ->orderBy('s.semester_number')
            ->orderBy('u.unit_code')
            ->select([
                'gr.id as grade_record_id',
                'gr.unit_id',
                'gr.semester_id',
                'gr.final_score as continuous_score',
                'gr.grade_letter',
                'gr.grade_points',
                'gr.credit_hours',
                'gr.gpa_at_time',
                'u.unit_code',
                'u.unit_name',
                's.semester_label',
                's.semester_number',
                'ay.year_label',
                'er.cat_total',
                'er.practical_total',
                'er.final_exam_score',
                'er.final_total_score as exam_final_score',
                'er.grade_letter as exam_grade_letter',
            ])
            ->get()
            ->map(function ($row) {
                $continuous = (float) $row->continuous_score;
                $examFinal = $row->exam_final_score !== null ? (float) $row->exam_final_score : null;
                $displayScore = $examFinal ?? $continuous;
                $displayGrade = $row->exam_grade_letter ?: $row->grade_letter;

                return (object) [
                    'grade_record_id' => (int) $row->grade_record_id,
                    'unit_id' => (int) $row->unit_id,
                    'semester_id' => (int) $row->semester_id,
                    'unit_code' => $row->unit_code,
                    'unit_name' => $row->unit_name,
                    'semester_label' => $row->semester_label,
                    'semester_number' => (int) $row->semester_number,
                    'year_label' => $row->year_label,
                    'credit_hours' => (float) $row->credit_hours,
                    'continuous_score' => $continuous,
                    'cat_total' => $row->cat_total !== null ? (float) $row->cat_total : null,
                    'practical_total' => $row->practical_total !== null ? (float) $row->practical_total : null,
                    'exam_score' => $row->final_exam_score !== null ? (float) $row->final_exam_score : null,
                    'final_score' => $displayScore,
                    'grade_letter' => $displayGrade,
                    'grade_points' => (float) $row->grade_points,
                    'has_exam_result' => $examFinal !== null,
                ];
            });
    }

    /**
     * @param  Collection<int, object>  $rows
     * @return list<array<string, mixed>>
     */
    private function groupBySemester(Collection $rows): array
    {
        return $rows->groupBy('semester_id')->map(function (Collection $semesterRows, $semesterId) {
            $first = $semesterRows->first();

            return [
                'semester_id' => (int) $semesterId,
                'semester_label' => $first->semester_label,
                'semester_number' => $first->semester_number,
                'year_label' => $first->year_label,
                'rows' => $semesterRows->values()->all(),
                'semester_gpa' => $this->calculateGpa($semesterRows),
                'credits' => round((float) $semesterRows->sum('credit_hours'), 2),
            ];
        })->sortBy('semester_number')->values()->all();
    }

    /**
     * @param  Collection<int, object>  $rows
     */
    private function calculateGpa(Collection $rows): float
    {
        $credits = (float) $rows->sum('credit_hours');
        if ($credits <= 0) {
            return 0.0;
        }

        $points = $rows->sum(fn ($row) => (float) $row->grade_points * (float) $row->credit_hours);

        return round($points / $credits, 2);
    }

    /**
     * @param  list<array<string, mixed>>  $semesterBlocks
     */
    private function persistGpaSnapshots(Student $student, array $semesterBlocks, float $cumulativeGpa): void
    {
        $runningGpa = $cumulativeGpa;

        foreach ($semesterBlocks as $block) {
            foreach ($block['rows'] as $row) {
                DB::table('grade_records')
                    ->where('id', $row->grade_record_id)
                    ->update(['gpa_at_time' => $runningGpa]);
            }
        }
    }
}
