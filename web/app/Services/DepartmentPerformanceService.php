<?php

namespace App\Services;

use App\Models\Semester;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class DepartmentPerformanceService
{
    /**
     * @return array<string, mixed>
     */
    public function dashboard(int $departmentId, ?int $semesterId = null): array
    {
        $semesterId ??= (int) DB::table('semesters')->orderByDesc('id')->value('id');

        $semesterRow = DB::table('semesters')->where('id', $semesterId)->first(['semester_label', 'semester_number']);

        return [
            'semester_id' => $semesterId,
            'semester_label' => Semester::normalizeLabel(
                $semesterRow?->semester_label,
                $semesterRow?->semester_number ? (int) $semesterRow->semester_number : null,
            ),
            'campus_breakdown' => $this->campusBreakdown($departmentId, $semesterId),
            'unit_breakdown' => $this->unitBreakdown($departmentId, $semesterId),
            'failing_students' => $this->failingStudents($departmentId, $semesterId),
            'summary' => $this->departmentSummary($departmentId, $semesterId),
        ];
    }

    /**
     * @return Collection<int, object>
     */
    public function campusBreakdown(int $departmentId, int $semesterId): Collection
    {
        return DB::table('cat_scores as cs')
            ->join('students as st', 'st.id', '=', 'cs.student_id')
            ->join('campuses as c', 'c.id', '=', 'st.enrollment_campus_id')
            ->join('units as u', 'u.id', '=', 'cs.unit_id')
            ->where('u.department_id', $departmentId)
            ->where('cs.semester_id', $semesterId)
            ->groupBy('c.id', 'c.campus_name', 'c.campus_type', 'c.sub_county', 'c.county')
            ->selectRaw('
                c.id as campus_id,
                c.campus_name,
                c.campus_type,
                c.sub_county,
                c.county,
                COUNT(DISTINCT cs.student_id) as student_count,
                COUNT(cs.id) as assessment_count,
                ROUND(AVG(cs.percentage_score), 1) as avg_score,
                SUM(CASE WHEN cs.percentage_score < 40 THEN 1 ELSE 0 END) as failing_assessments,
                SUM(CASE WHEN cs.assessment_type IN (\'practical\', \'skills_lab\') THEN 1 ELSE 0 END) as practical_entries
            ')
            ->orderBy('c.campus_name')
            ->get();
    }

    /**
     * @return Collection<int, object>
     */
    public function unitBreakdown(int $departmentId, int $semesterId): Collection
    {
        return DB::table('grade_records as gr')
            ->join('units as u', 'u.id', '=', 'gr.unit_id')
            ->where('u.department_id', $departmentId)
            ->where('gr.semester_id', $semesterId)
            ->groupBy('u.id', 'u.unit_code', 'u.unit_name')
            ->selectRaw('
                u.id as unit_id,
                u.unit_code,
                u.unit_name,
                COUNT(gr.id) as student_count,
                ROUND(AVG(gr.final_score), 1) as class_average,
                SUM(CASE WHEN gr.final_score < 40 THEN 1 ELSE 0 END) as failing_count,
                SUM(CASE WHEN gr.grade_letter IN (\'A\', \'B\') THEN 1 ELSE 0 END) as high_performers
            ')
            ->orderBy('u.unit_code')
            ->get();
    }

    /**
     * @return Collection<int, object>
     */
    public function failingStudents(int $departmentId, int $semesterId, int $limit = 25): Collection
    {
        return DB::table('grade_records as gr')
            ->join('students as st', 'st.id', '=', 'gr.student_id')
            ->join('units as u', 'u.id', '=', 'gr.unit_id')
            ->leftJoin('applicants as a', 'a.id', '=', 'st.application_id')
            ->join('campuses as c', 'c.id', '=', 'st.enrollment_campus_id')
            ->where('u.department_id', $departmentId)
            ->where('gr.semester_id', $semesterId)
            ->where('gr.final_score', '<', 40)
            ->orderBy('gr.final_score')
            ->limit($limit)
            ->select([
                'st.registration_number',
                'u.unit_code',
                'gr.final_score',
                'gr.grade_letter',
                'c.campus_name',
                'c.sub_county',
                DB::raw("TRIM(CONCAT(COALESCE(a.first_name,''), ' ', COALESCE(a.surname,''))) as student_name"),
            ])
            ->get();
    }

    /**
     * @return array<string, float|int>
     */
    public function departmentSummary(int $departmentId, int $semesterId): array
    {
        $scores = DB::table('cat_scores as cs')
            ->join('units as u', 'u.id', '=', 'cs.unit_id')
            ->where('u.department_id', $departmentId)
            ->where('cs.semester_id', $semesterId);

        $registered = DB::table('registered_units as ru')
            ->join('student_semester_registrations as ssr', 'ssr.id', '=', 'ru.semester_registration_id')
            ->join('units as u', 'u.id', '=', 'ru.unit_id')
            ->where('u.department_id', $departmentId)
            ->where('ssr.semester_id', $semesterId)
            ->distinct()
            ->count('ssr.student_id');

        $withPractical = DB::table('cat_scores as cs')
            ->join('units as u', 'u.id', '=', 'cs.unit_id')
            ->where('u.department_id', $departmentId)
            ->where('cs.semester_id', $semesterId)
            ->whereIn('cs.assessment_type', ['practical', 'skills_lab'])
            ->distinct()
            ->count('cs.student_id');

        return [
            'avg_score' => round((float) ((clone $scores)->avg('cs.percentage_score') ?? 0), 1),
            'assessment_count' => (clone $scores)->count(),
            'registered_students' => $registered,
            'practical_completion_rate' => $registered > 0 ? round(($withPractical / $registered) * 100, 1) : 0,
            'failing_rate' => round((float) DB::table('grade_records as gr')
                ->join('units as u', 'u.id', '=', 'gr.unit_id')
                ->where('u.department_id', $departmentId)
                ->where('gr.semester_id', $semesterId)
                ->where('gr.final_score', '<', 40)
                ->count() / max($registered, 1) * 100, 1),
        ];
    }
}
