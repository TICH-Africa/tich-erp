<?php

namespace App\Http\Controllers\Api\Student\mobile\Concerns;

use App\Models\Student;
use Illuminate\Support\Collection;

trait SerializesStudentPortal
{
    /**
     * @param  array<string, mixed>  $biodata
     * @return array<string, mixed>
     */
    protected function serializeStudent(Student $student, array $biodata = []): array
    {
        $identity = $biodata['identity'] ?? [];
        $academic = $biodata['academic'] ?? [];
        $enrollment = $biodata['enrollment'] ?? [];

        return [
            'id' => $student->id,
            'registration_number' => $student->registration_number,
            'full_name' => $identity['full_name'] ?? $student->applicant?->fullName(),
            'photo_url' => $identity['photo_url'] ?? $student->photoUrl(),
            'program' => $academic['program'] ?? $student->program?->program_name,
            'department' => $academic['department'] ?? $student->program?->department?->dept_name,
            'campus' => $enrollment['campus'] ?? $student->campus?->campus_name,
            'enrollment_status' => $enrollment['enrollment_status'] ?? $student->enrollment_status,
            'cohort_intake' => $academic['cohort_intake'] ?? $student->cohort_intake,
            'year_of_study' => $academic['year_of_study'] ?? null,
            'current_semester' => $academic['current_semester'] ?? null,
            'fee_clearance_status' => $enrollment['fee_clearance_status'] ?? $student->fee_clearance_status,
        ];
    }

    /**
     * @param  array<string, mixed>  $portalData
     * @return array<string, mixed>
     */
    protected function serializeAcademics(array $portalData): array
    {
        $a = $portalData['academics'] ?? [];

        return [
            'registered_unit_count' => (int) ($a['registered_unit_count'] ?? 0),
            'curriculum_is_published' => (bool) ($a['curriculum_is_published'] ?? false),
            'current_period_status' => $a['current_period_status'] ?? null,
            'current_semester' => $a['current_semester'] ? [
                'id' => $a['current_semester']->id,
                'label' => $a['current_semester']->semester_label,
                'number' => $a['current_semester']->semester_number,
                'year' => $a['current_semester']->academicYear?->year_label,
            ] : null,
            'current_period' => $a['current_period'] ? [
                'id' => $a['current_period']->id ?? null,
                'semester' => $a['current_period']->semester ?? null,
                'label' => $a['current_period']->label ?? $a['current_period']->period_label ?? null,
            ] : null,
            'registered_units' => $this->mapRows($a['registered_units'] ?? []),
            'curriculum_units' => collect($a['curriculum_units'] ?? [])->map(function ($item) {
                $unit = is_object($item) ? ($item->unit ?? null) : null;

                return [
                    'id' => $item->id ?? null,
                    'unit_id' => $item->unit_id ?? null,
                    'unit_code' => $unit?->unit_code ?? ($item->unit_code ?? null),
                    'unit_name' => $unit?->unit_name ?? ($item->unit_name ?? null),
                    'semester' => $item->semester ?? null,
                    'credit_hours' => $item->credit_hours ?? $unit?->credit_hours ?? null,
                    'is_core' => (bool) ($item->is_core ?? false),
                ];
            })->values()->all(),
            'grades' => $this->mapRows($a['grades'] ?? []),
            'cat_scores' => $this->mapRows($a['cat_scores'] ?? []),
            'exam_results' => $this->mapRows($a['exam_results'] ?? []),
            'attendance' => $this->serializeAttendance($a['attendance'] ?? []),
            'registrations' => $this->mapRows($a['registrations'] ?? []),
            'periods' => collect($a['periods'] ?? [])->map(fn ($p) => [
                'id' => $p->id ?? null,
                'semester' => $p->semester ?? null,
                'label' => $p->label ?? $p->period_label ?? null,
                'starts_at' => optional($p->starts_at ?? null)?->format('Y-m-d'),
                'ends_at' => optional($p->ends_at ?? null)?->format('Y-m-d'),
            ])->values()->all(),
            'unit_content' => collect($a['unit_content'] ?? [])->map(fn ($c) => [
                'id' => $c->id,
                'title' => $c->title,
                'content_type' => $c->content_type,
                'unit_code' => $c->unit?->unit_code,
                'unit_name' => $c->unit?->unit_name,
                'status' => $c->status,
                'published_at' => optional($c->published_at ?? $c->created_at)?->toIso8601String(),
            ])->values()->all(),
            'assessments' => collect($a['assessments'] ?? [])->map(function ($assessment) use ($a) {
                $submission = ($a['my_submissions'] ?? collect())->get($assessment->id);

                return [
                    'id' => $assessment->id,
                    'title' => $assessment->name ?? $assessment->title,
                    'status' => $assessment->status,
                    'unit_code' => $assessment->allocation?->unit?->unit_code,
                    'unit_name' => $assessment->allocation?->unit?->unit_name,
                    'question_count' => $assessment->questions?->count() ?? 0,
                    'submission' => $submission ? [
                        'id' => $submission->id,
                        'score' => $submission->score,
                        'status' => $submission->status,
                        'submitted_at' => optional($submission->submitted_at)?->toIso8601String(),
                    ] : null,
                ];
            })->values()->all(),
            'exam_portal' => $this->serializeExamPortal($a['exam_portal'] ?? null),
            'transcript' => $portalData['transcript'] ?? ['available' => false, 'units_completed' => 0],
        ];
    }

    /**
     * @param  array<string, mixed>  $portalData
     * @return array<string, mixed>
     */
    protected function serializeTimetable(array $portalData): array
    {
        $t = $portalData['timetable'] ?? [];
        $timetables = collect($t['timetables'] ?? []);

        return [
            'teaching_period' => $t['teaching_period'] ?? null,
            'period_label' => $t['period_label'] ?? null,
            'year_of_study' => $t['year_of_study'] ?? null,
            'is_provisional' => (bool) ($t['is_provisional'] ?? false),
            'current_timetable_id' => $t['timetable']?->id ?? null,
            'day_labels' => $t['day_labels'] ?? [],
            'active_days' => $t['active_days'] ?? [1, 2, 3, 4, 5],
            'timetables' => $timetables->map(function ($tt) use ($t) {
                return [
                    'id' => $tt->id,
                    'kind' => $tt->timetable_kind,
                    'status' => $tt->status,
                    'title' => $tt->title ?? ucfirst((string) $tt->timetable_kind).' timetable',
                    'display_title' => $tt->displayTitle(),
                    'cycle_type' => $tt->cycle_type,
                    'cycle_no' => $tt->cycle_no,
                    'cycle_label' => $tt->cycleLabel(),
                    'is_current' => (int) $tt->id === (int) ($t['timetable']?->id ?? 0),
                    'teaching_period' => $tt->teaching_period,
                    'sessions' => collect($tt->sessions ?? [])->map(fn ($s) => [
                        'id' => $s->id,
                        'day_of_week' => $s->day_of_week,
                        'start_time' => $s->start_time,
                        'end_time' => $s->end_time,
                        'unit_code' => $s->unit?->unit_code,
                        'unit_name' => $s->unit?->unit_name,
                        'staff_name' => $s->staff?->fullName(),
                        'room' => $s->room?->room_name ?? $s->room?->name,
                        'session_type' => $s->session_type,
                        'session_date' => $s->session_date?->format('Y-m-d'),
                        'week_no' => $s->week_no,
                        'week_to' => $s->week_to,
                        'recurrence' => $s->recurrence,
                    ])->values()->all(),
                ];
            })->values()->all(),
        ];
    }

    /**
     * @param  array<string, mixed>  $portalData
     * @return array<string, mixed>
     */
    protected function serializeFinance(array $portalData): array
    {
        $f = $portalData['finance'] ?? [];

        return [
            'summary' => $f['summary'] ?? [],
            'credits' => $f['credits'] ?? [],
            'default_phone' => $f['default_phone'] ?? '',
            'mpesa_enabled' => (bool) ($f['mpesa_enabled'] ?? false),
            'accounts' => $this->mapRows($f['accounts'] ?? []),
            'invoices' => $this->mapRows($f['invoices'] ?? []),
            'payments' => $this->mapRows($f['payments'] ?? []),
            'payable_invoices' => $this->mapRows($f['payable_invoices'] ?? []),
            'pending_stk_requests' => collect($f['pending_stk_requests'] ?? [])->map(fn ($r) => [
                'id' => $r->id,
                'status' => $r->status,
                'amount' => (float) $r->amount,
                'phone' => $r->phone,
                'invoice_number' => $r->invoice?->invoice_number,
                'created_at' => optional($r->created_at)?->toIso8601String(),
            ])->values()->all(),
            'statement' => $f['statement'] ?? [],
        ];
    }

    /**
     * @param  iterable<int, mixed>  $rows
     * @return list<array<string, mixed>>
     */
    protected function mapRows(iterable $rows): array
    {
        return collect($rows)->map(function ($row) {
            if ($row instanceof Collection) {
                return $row->toArray();
            }
            if (is_object($row)) {
                return (array) $row;
            }
            if (is_array($row)) {
                return $row;
            }

            return ['value' => $row];
        })->values()->all();
    }

    /**
     * @param  iterable<int, mixed>  $rows
     * @return list<array<string, mixed>>
     */
    protected function serializeAttendance(iterable $rows): array
    {
        return collect($rows)->map(function ($row) {
            $arr = $this->mapRows([$row])[0] ?? [];
            $pct = (float) ($arr['attendance_percentage'] ?? 0);
            $arr['status_label'] = $this->attendanceStatusLabel(
                $arr['status_flag'] ?? null,
                $pct,
            );

            return $arr;
        })->values()->all();
    }

    protected function attendanceStatusLabel(?string $flag, float $percentage): string
    {
        if ($percentage >= 90) {
            return 'Excellent';
        }
        if ($percentage >= 75) {
            return 'Good';
        }
        if ($percentage >= 60) {
            return 'Average';
        }

        return match ($flag) {
            'red' => 'At risk',
            'amber' => 'Needs improvement',
            default => 'At risk',
        };
    }

    /**
     * @return array<string, mixed>|null
     */
    protected function serializeExamPortal(mixed $portal): ?array
    {
        if (! is_array($portal)) {
            return null;
        }

        $card = $portal['exam_card'] ?? null;

        return [
            'semester_id' => $portal['semester_id'] ?? null,
            'semester_label' => $portal['semester_label'] ?? null,
            'can_view_exam_card' => (bool) ($portal['can_view_exam_card'] ?? false),
            'exam_card' => $card ? [
                'exam_card_number' => $card->exam_card_number ?? null,
                'examination_number' => $card->examination_number ?? null,
            ] : null,
            'upcoming_exams' => collect($portal['upcoming_exams'] ?? [])->map(fn ($exam) => [
                'unit_code' => $exam->unit_code ?? null,
                'unit_name' => $exam->unit_name ?? null,
                'exam_date' => $exam->exam_date ?? null,
                'start_time' => $exam->start_time ?? null,
                'end_time' => $exam->end_time ?? null,
                'venue' => $exam->venue ?? null,
                'exam_type' => $exam->exam_type ?? null,
                'eligible_for_exams' => (bool) ($exam->eligible_for_exams ?? false),
            ])->values()->all(),
        ];
    }
}
