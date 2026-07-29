<?php

namespace App\Services;

use App\Models\AttendanceSession;
use App\Models\CurriculumVersionPeriod;
use App\Models\ProgramTimetableSession;
use App\Models\Staff;
use App\Models\UnitAllocation;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Support\Collection;

class AttendanceSessionGenerationService
{
    public function __construct(
        protected StaffTeachingService $teaching,
    ) {}

    /**
     * @return array{created: int, skipped: int, sessions: Collection<int, AttendanceSession>}
     */
    public function syncForStaff(Staff $staff, int $weeksAhead = 8): array
    {
        $created = 0;
        $skipped = 0;
        $sessions = collect();

        $slots = ProgramTimetableSession::query()
            ->with(['timetable.program', 'room', 'unit'])
            ->where('staff_id', $staff->id)
            ->whereIn('session_type', ['lesson', 'physical', 'virtual', 'field_practical', 'clinical'])
            ->whereHas('timetable', fn ($query) => $query->where('timetable_kind', 'lesson'))
            ->get();

        foreach ($slots as $slot) {
            foreach ($this->datesForSlot($slot, $weeksAhead) as $date) {
                $exists = AttendanceSession::query()
                    ->where('program_timetable_session_id', $slot->id)
                    ->whereDate('session_date', $date)
                    ->exists();

                if ($exists) {
                    $skipped++;

                    continue;
                }

                $allocation = $this->allocationForSlot($staff, $slot);
                if (! $allocation) {
                    $skipped++;

                    continue;
                }

                $session = $this->teaching->createAttendanceSessionFromTimetable(
                    $staff,
                    $allocation,
                    $slot,
                    $date,
                );

                $created++;
                $sessions->push($session);
            }
        }

        return compact('created', 'skipped', 'sessions');
    }

    /**
     * @return list<Carbon>
     */
    private function datesForSlot(ProgramTimetableSession $slot, int $weeksAhead): array
    {
        $timetable = $slot->timetable;
        if (! $timetable) {
            return [];
        }

        $period = CurriculumVersionPeriod::query()
            ->where('curriculum_version_id', $timetable->curriculum_version_id)
            ->where('semester', $timetable->teaching_period)
            ->whereNull('block_id')
            ->first();

        $rangeStart = $period?->effectiveLearningStart();
        $rangeEnd = $period?->learning_end_date ?? $period?->end_date;

        $today = now()->startOfDay();
        $windowStart = $today->copy()->subWeek();
        $windowEnd = $today->copy()->addWeeks($weeksAhead);

        if ($rangeStart) {
            $windowStart = Carbon::parse($rangeStart)->max($windowStart);
        }
        if ($rangeEnd) {
            $windowEnd = Carbon::parse($rangeEnd)->min($windowEnd);
        }

        if ($windowStart->gt($windowEnd)) {
            return [];
        }

        $dates = [];
        foreach (CarbonPeriod::create($windowStart, $windowEnd) as $date) {
            if ((int) $date->dayOfWeekIso === (int) $slot->day_of_week) {
                $dates[] = $date->copy();
            }
        }

        return $dates;
    }

    private function allocationForSlot(Staff $staff, ProgramTimetableSession $slot): ?UnitAllocation
    {
        if (! $slot->unit_id) {
            return null;
        }

        $timetable = $slot->timetable;
        $teachingPeriod = $timetable?->teaching_period;

        $matched = UnitAllocation::query()
            ->with('semester')
            ->where('staff_id', $staff->id)
            ->where('unit_id', $slot->unit_id)
            ->where('is_active', 1)
            ->when($teachingPeriod, function ($query) use ($teachingPeriod) {
                $query->whereHas('semester', fn ($semesterQuery) => $semesterQuery->where('semester_number', $teachingPeriod));
            })
            ->orderByDesc('id')
            ->first();

        if ($matched) {
            return $matched;
        }

        return UnitAllocation::query()
            ->with('semester')
            ->where('staff_id', $staff->id)
            ->where('unit_id', $slot->unit_id)
            ->where('is_active', 1)
            ->orderByDesc('id')
            ->first();
    }
}
