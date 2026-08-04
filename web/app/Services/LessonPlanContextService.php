<?php

namespace App\Services;

use App\Models\ProgramTimetableSession;
use App\Models\Staff;
use App\Models\UnitAllocation;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class LessonPlanContextService
{
    public function __construct(
        protected StaffPortalDashboardService $portalDashboard,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function defaultsForAllocation(Staff $staff, UnitAllocation $allocation, ?string $plannedDate = null): array
    {
        $allocation->loadMissing(['unit', 'semester.academicYear', 'campus']);

        $sessions = ProgramTimetableSession::query()
            ->with(['room', 'timetable.program', 'timetable.curriculumVersion'])
            ->where('staff_id', $staff->id)
            ->where('unit_id', $allocation->unit_id)
            ->orderBy('day_of_week')
            ->orderBy('start_time')
            ->get();

        $session = $this->pickSession($sessions, $plannedDate);

        $intakeClass = trim((string) ($session?->class_group ?? ''));
        if ($intakeClass === '') {
            $intakeClass = trim((string) ($session?->timetable?->curriculumVersion?->intakeLabel()
                ?? $this->portalDashboard->intakeLabelForSemester($allocation->semester)
                ?? $allocation->semester?->displayLabel()
                ?? ''));
        }

        $venue = trim((string) ($session?->venue ?? $session?->room?->name ?? $allocation->campus?->campus_name ?? ''));

        return [
            'week_number' => $this->resolveWeekNumber($allocation, $plannedDate, $session),
            'session_time' => $session ? $session->timeLabel() : '',
            'intake_class' => $intakeClass,
            'venue' => $venue,
            'contact_hours' => $this->resolveContactHours($session, $allocation),
            'unit_code' => $allocation->unit?->unit_code,
            'unit_name' => $allocation->unit?->unit_name,
            'timetable_session_id' => $session?->id,
        ];
    }

    /**
     * @param  Collection<int, ProgramTimetableSession>  $sessions
     */
    private function pickSession(Collection $sessions, ?string $plannedDate): ?ProgramTimetableSession
    {
        if ($sessions->isEmpty()) {
            return null;
        }

        if (! $plannedDate) {
            return $sessions->first();
        }

        $carbonDow = Carbon::parse($plannedDate)->dayOfWeek;
        $dbDow = $carbonDow === 0 ? 7 : $carbonDow;

        return $sessions->firstWhere('day_of_week', $dbDow) ?? $sessions->first();
    }

    private function resolveWeekNumber(UnitAllocation $allocation, ?string $plannedDate, ?ProgramTimetableSession $session): int
    {
        $semester = $allocation->semester;

        if ($plannedDate && $semester?->start_date) {
            $start = Carbon::parse($semester->start_date)->startOfDay();
            $planned = Carbon::parse($plannedDate)->startOfDay();

            if ($planned->gte($start)) {
                return max(1, (int) floor($start->diffInDays($planned) / 7) + 1);
            }
        }

        if ($session?->timetable?->teaching_period) {
            return max(1, (int) $session->timetable->teaching_period);
        }

        if ($semester?->semester_number) {
            return max(1, (int) $semester->semester_number);
        }

        return 1;
    }

    private function resolveContactHours(?ProgramTimetableSession $session, UnitAllocation $allocation): int
    {
        if ($session?->start_time && $session?->end_time) {
            $start = Carbon::parse($session->start_time);
            $end = Carbon::parse($session->end_time);
            $hours = max(1, (int) ceil($start->diffInMinutes($end) / 60));

            return $hours;
        }

        if ($allocation->contact_hours_assigned) {
            return max(1, (int) $allocation->contact_hours_assigned);
        }

        return 2;
    }
}
