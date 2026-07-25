<?php

namespace App\Services;

use App\Models\ProgramTimetableSession;
use App\Models\Staff;
use App\Models\UnitAllocation;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class StaffPortalDashboardService
{
    public function __construct(
        protected TimetableTemplateService $timetableTemplates,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function forStaff(Staff $staff): array
    {
        $allocations = $this->allocations($staff);
        $allocationIds = $allocations->pluck('id')->all();
        $unitIds = $allocations->pluck('unit_id')->unique()->all();
        $semesterIds = $allocations->pluck('semester_id')->unique()->all();

        return [
            'allocations' => $allocations,
            'allocation_count' => $allocations->count(),
            'timetable_sessions' => $this->timetableSessions($staff),
            'lesson_plans' => $this->lessonPlans($allocationIds),
            'attendance_sessions' => $this->attendanceSessions($allocationIds),
            'cat_scores' => $this->catScores($staff->id, $unitIds, $semesterIds),
            'learning_content' => $this->learningContent($unitIds),
            'attendance_alerts' => $this->attendanceAlerts($unitIds, $semesterIds),
            'day_labels' => TimetableTemplateService::dayLabels(),
            'segment_types' => TimetableTemplateService::segmentTypes(),
        ];
    }

    /**
     * @return Collection<int, UnitAllocation>
     */
    public function allocations(Staff $staff): Collection
    {
        return UnitAllocation::query()
            ->with(['unit', 'semester.academicYear', 'campus'])
            ->where('staff_id', $staff->id)
            ->where('is_active', 1)
            ->orderByDesc('semester_id')
            ->get();
    }

    /**
     * @return Collection<int, ProgramTimetableSession>
     */
    public function timetableSessions(Staff $staff): Collection
    {
        return ProgramTimetableSession::query()
            ->with(['room.campus', 'timetable.program', 'unit'])
            ->where('staff_id', $staff->id)
            ->orderBy('day_of_week')
            ->orderBy('start_time')
            ->get();
    }

    /**
     * @param  list<int>  $allocationIds
     */
    private function lessonPlans(array $allocationIds): Collection
    {
        if ($allocationIds === []) {
            return collect();
        }

        return DB::table('lesson_plans as lp')
            ->join('unit_allocations as ua', 'ua.id', '=', 'lp.unit_allocation_id')
            ->join('units as u', 'u.id', '=', 'ua.unit_id')
            ->whereIn('lp.unit_allocation_id', $allocationIds)
            ->orderByDesc('lp.planned_date')
            ->select([
                'lp.*',
                'u.unit_code',
                'u.unit_name',
            ])
            ->get();
    }

    /**
     * @param  list<int>  $allocationIds
     */
    private function attendanceSessions(array $allocationIds): Collection
    {
        if ($allocationIds === []) {
            return collect();
        }

        return DB::table('attendance_sessions as s')
            ->join('unit_allocations as ua', 'ua.id', '=', 's.unit_allocation_id')
            ->join('units as u', 'u.id', '=', 'ua.unit_id')
            ->whereIn('s.unit_allocation_id', $allocationIds)
            ->orderByDesc('s.session_date')
            ->select([
                's.*',
                'u.unit_code',
                'u.unit_name',
            ])
            ->get();
    }

    /**
     * @param  list<int>  $unitIds
     * @param  list<int>  $semesterIds
     */
    private function catScores(int $staffId, array $unitIds, array $semesterIds): Collection
    {
        if ($unitIds === []) {
            return collect();
        }

        return DB::table('cat_scores as cs')
            ->join('units as u', 'u.id', '=', 'cs.unit_id')
            ->join('students as st', 'st.id', '=', 'cs.student_id')
            ->leftJoin('applicants as a', 'a.id', '=', 'st.application_id')
            ->where('cs.recorded_by', $staffId)
            ->when($semesterIds !== [], fn ($query) => $query->whereIn('cs.semester_id', $semesterIds))
            ->orderByDesc('cs.recorded_at')
            ->select([
                'cs.*',
                'u.unit_code',
                'u.unit_name',
                'st.registration_number',
                DB::raw("CONCAT(COALESCE(a.first_name,''), ' ', COALESCE(a.surname,'')) as student_name"),
            ])
            ->get();
    }

    /**
     * @param  list<int>  $unitIds
     */
    private function learningContent(array $unitIds): Collection
    {
        if ($unitIds === []) {
            return collect();
        }

        return DB::table('media_attachments')
            ->where('entity_type', 'unit')
            ->whereIn('entity_id', $unitIds)
            ->orderByDesc('uploaded_at')
            ->get();
    }

    /**
     * @param  list<int>  $unitIds
     * @param  list<int>  $semesterIds
     */
    private function attendanceAlerts(array $unitIds, array $semesterIds): Collection
    {
        if ($unitIds === []) {
            return collect();
        }

        return DB::table('attendance_summaries as a')
            ->join('students as st', 'st.id', '=', 'a.student_id')
            ->join('units as u', 'u.id', '=', 'a.unit_id')
            ->leftJoin('applicants as ap', 'ap.id', '=', 'st.application_id')
            ->whereIn('a.unit_id', $unitIds)
            ->when($semesterIds !== [], fn ($query) => $query->whereIn('a.semester_id', $semesterIds))
            ->where(function ($query) {
                $query->where('a.status_flag', 'red')
                    ->orWhere('a.status_flag', 'amber')
                    ->orWhere('a.attendance_percentage', '<', 90);
            })
            ->orderBy('a.attendance_percentage')
            ->select([
                'a.*',
                'u.unit_code',
                'u.unit_name',
                'st.registration_number',
                DB::raw("CONCAT(COALESCE(ap.first_name,''), ' ', COALESCE(ap.surname,'')) as student_name"),
            ])
            ->limit(50)
            ->get();
    }

    /**
     * @return Collection<int, object>
     */
    public function rosterForAllocation(int $allocationId): Collection
    {
        $allocation = UnitAllocation::query()->findOrFail($allocationId);

        return DB::table('registered_units as ru')
            ->join('student_semester_registrations as ssr', 'ssr.id', '=', 'ru.semester_registration_id')
            ->join('students as st', 'st.id', '=', 'ssr.student_id')
            ->leftJoin('applicants as a', 'a.id', '=', 'st.application_id')
            ->where('ssr.semester_id', $allocation->semester_id)
            ->where('ru.unit_id', $allocation->unit_id)
            ->orderBy('st.registration_number')
            ->select([
                'st.id as student_id',
                'st.registration_number',
                DB::raw("TRIM(CONCAT(COALESCE(a.first_name,''), ' ', COALESCE(a.surname,''))) as student_name"),
            ])
            ->get();
    }
}
