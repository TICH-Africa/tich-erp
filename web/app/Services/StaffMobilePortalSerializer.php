<?php

namespace App\Services;

use App\Models\AttendanceSession;
use App\Models\Staff;
use App\Models\UnitAllocation;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;

class StaffMobilePortalSerializer
{
    public function __construct(
        protected StaffPortalDashboardService $dashboard,
        protected StaffPortalNavigationService $navigation,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function overview(Staff $staff): array
    {
        $data = $this->dashboard->forStaff($staff);
        $allocations = $data['allocations'];

        return [
            'staff' => $this->staffCard($staff),
            'sections' => array_keys($this->navigation->sections()),
            'stats' => [
                'allocation_count' => (int) $data['allocation_count'],
                'lesson_plan_count' => $data['lesson_plans']->count(),
                'attendance_session_count' => $data['attendance_sessions']->count(),
                'content_count' => $data['learning_content']->count(),
                'alert_count' => $data['attendance_alerts']->count(),
            ],
            'units' => $allocations->take(8)->map(fn (UnitAllocation $a) => $this->allocationCard($a))->values(),
            'upcoming_sessions' => $this->dashboard->upcomingAttendanceSessions($staff)
                ->take(8)
                ->map(fn ($s) => $this->sessionCard($s))
                ->values(),
            'timetable_today' => collect($data['timetable_sessions'])
                ->filter(fn ($s) => (int) $s->day_of_week === (int) now('Africa/Nairobi')->dayOfWeekIso)
                ->values()
                ->map(fn ($s) => $this->timetableSessionCard($s))
                ->values(),
            'alerts' => collect($data['attendance_alerts'])->take(10)->map(fn ($a) => [
                'student_name' => trim((string) ($a->student_name ?? '')),
                'registration_number' => $a->registration_number,
                'unit_code' => $a->unit_code,
                'unit_name' => $a->unit_name,
                'attendance_percentage' => (float) ($a->attendance_percentage ?? 0),
                'status_flag' => $a->status_flag,
            ])->values(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function units(Staff $staff): array
    {
        $allocations = $this->dashboard->allocations($staff);

        return [
            'items' => $allocations->map(fn (UnitAllocation $a) => $this->allocationCard($a))->values(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function timetable(Staff $staff): array
    {
        $sessions = $this->dashboard->timetableSessions($staff);

        return [
            'day_labels' => TimetableTemplateService::dayLabels(),
            'sessions' => $sessions->map(fn ($s) => $this->timetableSessionCard($s))->values(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function attendanceIndex(Staff $staff): array
    {
        $data = $this->dashboard->forStaff($staff);

        return [
            'sessions' => collect($data['attendance_sessions'])->take(40)->map(fn ($s) => [
                'id' => (int) $s->id,
                'session_number' => $s->session_number,
                'session_date' => $s->session_date,
                'start_time' => $s->start_time,
                'end_time' => $s->end_time,
                'venue' => $s->venue,
                'unit_code' => $s->unit_code,
                'unit_name' => $s->unit_name,
                'verification_status' => $s->verification_status,
                'is_locked' => (bool) ($s->is_locked ?? false),
                'total_expected_attendees' => (int) ($s->total_expected_attendees ?? 0),
            ])->values(),
            'upcoming' => $this->dashboard->upcomingAttendanceSessions($staff)
                ->map(fn ($s) => $this->sessionCard($s))
                ->values(),
            'allocations' => collect($data['allocations'])->map(fn (UnitAllocation $a) => [
                'id' => $a->id,
                'label' => trim(($a->unit?->unit_code ?? '').' · '.($a->unit?->unit_name ?? '')),
            ])->values(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function attendanceShow(AttendanceSession $session): array
    {
        $session->load([
            'records.student.applicant',
            'allocation.unit',
            'allocation.semester.academicYear',
        ]);

        return [
            'session' => $this->sessionCard($session),
            'records' => $session->records->map(fn ($r) => [
                'id' => $r->id,
                'student_id' => (int) $r->student_id,
                'registration_number' => $r->student?->registration_number,
                'student_name' => trim(
                    ($r->student?->applicant?->first_name ?? '').' '.($r->student?->applicant?->surname ?? '')
                ) ?: ($r->student?->registration_number ?? 'Student'),
                'is_present' => (bool) $r->is_present,
            ])->values(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function grading(Staff $staff): array
    {
        $data = $this->dashboard->forStaff($staff);

        return [
            'allocations' => collect($data['allocations'])->map(fn (UnitAllocation $a) => $this->allocationCard($a))->values(),
            'recent_scores' => collect($data['cat_scores'])->take(40)->map(fn ($s) => [
                'id' => (int) $s->id,
                'assessment_name' => $s->assessment_name,
                'assessment_type' => $s->assessment_type,
                'unit_code' => $s->unit_code,
                'unit_name' => $s->unit_name,
                'registration_number' => $s->registration_number,
                'student_name' => trim((string) ($s->student_name ?? '')),
                'score_obtained' => (float) $s->score_obtained,
                'max_score' => (float) $s->max_score,
                'percentage_score' => (float) ($s->percentage_score ?? 0),
                'recorded_at' => $s->recorded_at,
            ])->values(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function lessonPlans(Staff $staff): array
    {
        $data = $this->dashboard->forStaff($staff);

        return [
            'allocations' => collect($data['allocations'])->map(fn (UnitAllocation $a) => [
                'id' => $a->id,
                'unit_code' => $a->unit?->unit_code,
                'unit_name' => $a->unit?->unit_name,
                'label' => collect([
                    $a->unit?->unit_code,
                    $a->unit?->unit_name,
                    $a->intake_label ?? $a->semester?->semester_label,
                ])->filter(fn ($v) => filled($v))->implode(' · '),
            ])->values(),
            'items' => collect($data['lesson_plans'])->take(40)->map(fn ($p) => [
                'id' => (int) $p->id,
                'plan_number' => $p->plan_number,
                'lesson_title' => $p->lesson_title,
                'unit_code' => $p->unit_code,
                'unit_name' => $p->unit_name,
                'planned_date' => $p->planned_date,
                'week_number' => (int) ($p->week_number ?? 0),
                'status' => $p->status,
                'contact_hours' => (int) ($p->contact_hours ?? 0),
            ])->values(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function content(Staff $staff): array
    {
        $data = $this->dashboard->forStaff($staff);

        return [
            'allocations' => collect($data['allocations'])->map(fn (UnitAllocation $a) => [
                'unit_id' => $a->unit_id,
                'label' => collect([
                    $a->unit?->unit_code,
                    $a->unit?->unit_name,
                    $a->intake_label ?? $a->semester?->semester_label,
                ])->filter(fn ($v) => filled($v))->implode(' · '),
                'cohort' => $this->dashboard->intakeLabelForSemester($a->semester),
            ])->values(),
            'items' => collect($data['learning_content'])->take(40)->map(function ($c) {
                $cohort = $c->unitAllocation
                    ? $this->dashboard->intakeLabelForSemester($c->unitAllocation->semester)
                    : null;

                return [
                    'id' => $c->id,
                    'title' => $c->title ?? $c->content_title ?? 'Content',
                    'content_type' => $c->content_type ?? $c->type ?? null,
                    'content_text' => $c->content_text,
                    'unit_code' => $c->unit?->unit_code,
                    'unit_name' => $c->unit?->unit_name,
                    'cohort' => $cohort,
                    'has_file' => filled($c->file_path),
                    'file_name' => $c->original_filename,
                    'created_at' => optional($c->created_at)?->toIso8601String(),
                    'published_at' => optional($c->published_at)?->toIso8601String(),
                ];
            })->values(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function staffCard(Staff $staff): array
    {
        return [
            'id' => $staff->id,
            'employee_number' => $staff->employee_number,
            'full_name' => $staff->fullName(),
            'job_title' => $staff->job_title,
            'department' => $staff->department?->dept_name,
            'is_teaching_staff' => (bool) $staff->is_teaching_staff,
            'photo_url' => method_exists($staff, 'photoUrl') ? $staff->photoUrl() : null,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function allocationCard(UnitAllocation $allocation): array
    {
        $allocation->loadMissing(['unit', 'semester.academicYear', 'campus']);
        $cohort = $this->dashboard->intakeLabelForSemester($allocation->semester);

        return [
            'id' => $allocation->id,
            'unit_id' => $allocation->unit_id,
            'unit_code' => $allocation->unit?->unit_code,
            'unit_name' => $allocation->unit?->unit_name,
            'semester_id' => $allocation->semester_id,
            'semester' => $allocation->semester?->name
                ?? $allocation->semester?->semester_name
                ?? $allocation->semester?->semester_label
                ?? ('Semester '.$allocation->semester?->semester_number),
            'cohort' => $cohort,
            'intake_label' => $cohort,
            'academic_year' => $allocation->semester?->academicYear?->year_label
                ?? $allocation->semester?->academicYear?->name,
            'campus' => $allocation->campus?->name ?? $allocation->campus?->campus_name,
            'is_active' => (bool) $allocation->is_active,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function sessionCard(mixed $session): array
    {
        if ($session instanceof AttendanceSession) {
            $session->loadMissing(['allocation.unit']);

            return [
                'id' => $session->id,
                'session_number' => $session->session_number,
                'session_date' => optional($session->session_date)?->format('Y-m-d'),
                'start_time' => $session->start_time,
                'end_time' => $session->end_time,
                'venue' => $session->venue,
                'unit_code' => $session->allocation?->unit?->unit_code,
                'unit_name' => $session->allocation?->unit?->unit_name,
                'verification_status' => $session->verification_status,
                'is_locked' => (bool) $session->is_locked,
                'has_signed_sheet' => (bool) $session->signed_sheet_image_path,
                'has_class_photo' => (bool) $session->class_photo_image_path,
                'signed_sheet_url' => $session->signed_sheet_image_path
                    ? url(Storage::disk('public')->url($session->signed_sheet_image_path))
                    : null,
                'class_photo_url' => $session->class_photo_image_path
                    ? url(Storage::disk('public')->url($session->class_photo_image_path))
                    : null,
                'total_expected_attendees' => (int) ($session->total_expected_attendees ?? 0),
            ];
        }

        return [
            'id' => (int) ($session->id ?? 0),
            'session_number' => $session->session_number ?? null,
            'session_date' => $session->session_date ?? null,
            'start_time' => $session->start_time ?? null,
            'end_time' => $session->end_time ?? null,
            'venue' => $session->venue ?? null,
            'unit_code' => $session->unit_code ?? null,
            'unit_name' => $session->unit_name ?? null,
            'verification_status' => $session->verification_status ?? null,
            'is_locked' => (bool) ($session->is_locked ?? false),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function timetableSessionCard(object $s): array
    {
        return [
            'id' => (int) ($s->id ?? 0),
            'day_of_week' => (int) ($s->day_of_week ?? 0),
            'start_time' => substr((string) ($s->start_time ?? ''), 0, 5),
            'end_time' => substr((string) ($s->end_time ?? ''), 0, 5),
            'session_type' => $s->session_type ?? 'lesson',
            'unit_code' => $s->unit?->unit_code ?? $s->unit_code ?? null,
            'unit_name' => $s->unit?->unit_name ?? $s->unit_name ?? null,
            'room' => $s->room?->name ?? $s->room?->room_name ?? $s->venue ?? null,
            'program' => $s->timetable?->program?->program_name
                ?? $s->timetable?->program?->name
                ?? null,
        ];
    }
}
