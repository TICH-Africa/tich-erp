<?php

namespace App\Services;

use App\Models\ProgramTimetableSession;
use App\Models\Staff;
use App\Models\StaffAttendance;
use App\Models\Student;
use App\Models\StudentNotification;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

class MobileReminderService
{
    public function __construct(
        protected PlatformNotificationService $notifications,
    ) {}

    /**
     * Remind active staff who have not clocked in today (from 08:00 EAT).
     *
     * @return array{checked:int, reminded:int}
     */
    public function remindMissingClockIns(?Carbon $now = null): array
    {
        $now = ($now ?? now('Africa/Nairobi'))->timezone('Africa/Nairobi');
        if ((int) $now->format('H') < 8 || (int) $now->format('H') >= 18) {
            return ['checked' => 0, 'reminded' => 0];
        }

        // Weekends skipped by default.
        if ($now->isWeekend()) {
            return ['checked' => 0, 'reminded' => 0];
        }

        $today = $now->toDateString();
        $slot = $now->format('H').':'.(((int) $now->format('i') < 30) ? '00' : '30');

        $clockedInIds = StaffAttendance::query()
            ->whereDate('attendance_date', $today)
            ->whereNotNull('clock_in_time')
            ->pluck('staff_id')
            ->all();

        $staff = Staff::query()
            ->active()
            ->when(method_exists(Staff::class, 'scopeExcludePlatformOperators'),
                fn ($q) => $q->excludePlatformOperators())
            ->whereNotNull('user_id')
            ->whereNotIn('id', $clockedInIds)
            ->get(['id', 'user_id', 'first_name', 'surname', 'employee_number']);

        $reminded = 0;
        foreach ($staff as $member) {
            $cacheKey = "mobile:clockin-reminder:{$member->id}:{$today}:{$slot}";
            if (! Cache::add($cacheKey, 1, now()->addHours(12))) {
                continue;
            }

            $name = trim(($member->first_name ?? '').' '.($member->surname ?? '')) ?: 'there';
            $title = 'Clock-in reminder';
            $body = "Good day {$name}. You have not clocked in yet today. Please open TICH and clock in.";

            // notifyUser also fans out FCM when configured; in-app row keeps the counter.
            $this->notifications->notifyUser(
                (int) $member->user_id,
                $title,
                $body,
                'attendance_clock_in',
                (string) $member->id,
                'high',
                '/employee/attendance',
                false,
            );
            $reminded++;
        }

        return ['checked' => $staff->count(), 'reminded' => $reminded];
    }

    /**
     * Notify students about lessons starting in ~30 minutes.
     *
     * @return array{checked:int, reminded:int}
     */
    public function remindUpcomingLessons(?Carbon $now = null): array
    {
        $now = ($now ?? now('Africa/Nairobi'))->timezone('Africa/Nairobi');
        $day = (int) $now->dayOfWeekIso;
        $windowStart = $now->copy()->addMinutes(25)->format('H:i:s');
        $windowEnd = $now->copy()->addMinutes(35)->format('H:i:s');
        $today = $now->toDateString();

        if (! Schema::hasTable('program_timetable_sessions')) {
            return ['checked' => 0, 'reminded' => 0];
        }

        $sessions = ProgramTimetableSession::query()
            ->with(['unit', 'room', 'timetable'])
            ->where('day_of_week', $day)
            ->whereTime('start_time', '>=', $windowStart)
            ->whereTime('start_time', '<=', $windowEnd)
            ->where(function ($q) {
                $q->whereNull('session_type')
                    ->orWhereNotIn('session_type', ['break', 'lunch']);
            })
            ->whereHas('timetable', function ($q) {
                $q->where('status', 'published')
                    ->where(function ($inner) {
                        $inner->whereNull('timetable_kind')
                            ->orWhere('timetable_kind', 'lesson');
                    });
            })
            ->get();

        if ($sessions->isEmpty()) {
            return ['checked' => 0, 'reminded' => 0];
        }

        $reminded = 0;
        $students = Student::query()
            ->whereNotNull('program_id')
            ->whereIn('enrollment_status', ['active', 'enrolled', 'registered'])
            ->get(['id', 'user_id', 'program_id', 'campus_id', 'registration_number']);

        foreach ($students as $student) {
            $matching = $sessions->filter(function ($session) use ($student) {
                $tt = $session->timetable;
                if (! $tt) {
                    return false;
                }
                if ((int) $tt->program_id !== (int) $student->program_id) {
                    return false;
                }
                if ($tt->campus_id && $student->campus_id && (int) $tt->campus_id !== (int) $student->campus_id) {
                    return false;
                }

                return true;
            });

            foreach ($matching as $session) {
                $cacheKey = "mobile:lesson-reminder:{$student->id}:{$session->id}:{$today}";
                if (! Cache::add($cacheKey, 1, now()->addHours(6))) {
                    continue;
                }

                $unit = $session->unit?->unit_code
                    ? trim($session->unit->unit_code.' · '.($session->unit->unit_name ?? ''))
                    : 'your next class';
                $start = substr((string) $session->start_time, 0, 5);
                $room = $session->room?->room_name ?? $session->room?->name;
                $title = 'Class starting soon';
                $body = "{$unit} starts at {$start}"
                    .($room ? " in {$room}" : '')
                    .'.';

                if (Schema::hasTable('student_notifications')) {
                    StudentNotification::query()->create([
                        'student_id' => $student->id,
                        'category' => 'timetable',
                        'title' => $title,
                        'body' => $body,
                        'action_url' => '/portal?section=timetable',
                    ]);
                }

                if ($student->user_id) {
                    $this->notifications->notifyUser(
                        (int) $student->user_id,
                        $title,
                        $body,
                        'timetable_lesson',
                        (string) $session->id,
                        'high',
                        '/portal?section=timetable',
                        false,
                    );
                }
                $reminded++;
            }
        }

        return ['checked' => $sessions->count(), 'reminded' => $reminded];
    }
}
