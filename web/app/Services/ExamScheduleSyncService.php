<?php

namespace App\Services;

use App\Models\AcademicProgram;
use App\Models\CurriculumVersion;
use App\Models\CurriculumVersionPeriod;
use App\Models\ProgramTimetable;
use App\Models\Semester;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ExamScheduleSyncService
{
    public function __construct(
        protected TimetableSchedulingService $timetables,
        protected AuditService $auditService,
    ) {}

    public function syncFromExamTimetable(
        AcademicProgram $program,
        CurriculumVersion $intake,
        int $teachingPeriod
    ): int {
        $timetable = $this->timetables->latestTimetable(
            $program->id,
            $intake->id,
            $teachingPeriod,
            'exam'
        );

        if (! $timetable) {
            return 0;
        }

        $timetable->load(['sessions.unit', 'sessions.staff', 'sessions.room']);

        $semesterId = $this->resolveSemesterId($intake, $teachingPeriod);
        if (! $semesterId) {
            return 0;
        }

        $period = CurriculumVersionPeriod::resolveFor((int) $intake->id, $teachingPeriod);

        $synced = 0;

        DB::transaction(function () use ($timetable, $semesterId, $period, $teachingPeriod, &$synced) {
            $sessionIds = [];

            foreach ($timetable->sessions as $session) {
                if (! $session->unit_id) {
                    continue;
                }

                if (! in_array($session->session_type, ['exam', 'supplementary', 'special_exam'], true)) {
                    continue;
                }

                $examDate = $this->resolveExamDate($period, (int) $session->day_of_week);
                if (! $examDate) {
                    continue;
                }

                $venue = $session->room?->room_name
                    ?: ($session->room?->room_code ?: ($session->venue ?: '-'));

                $payload = [
                    'unit_id' => $session->unit_id,
                    'semester_id' => $semesterId,
                    'program_timetable_session_id' => $session->id,
                    'exam_date' => $examDate->toDateString(),
                    'start_time' => $this->normalizeTime($session->start_time),
                    'end_time' => $this->normalizeTime($session->end_time),
                    'venue' => $venue,
                    'exam_type' => $this->examTypeForSession($session->session_type),
                    'invigilator_id' => $session->staff_id,
                ];

                $existing = DB::table('exam_schedules')
                    ->where('program_timetable_session_id', $session->id)
                    ->first();

                if ($existing) {
                    DB::table('exam_schedules')
                        ->where('id', $existing->id)
                        ->update(array_merge($payload, [
                            'status' => $existing->status ?? 'scheduled',
                        ]));
                } else {
                    DB::table('exam_schedules')->insert(array_merge($payload, [
                        'status' => 'scheduled',
                        'total_candidates' => 0,
                    ]));
                }

                $sessionIds[] = $session->id;
                $synced++;
            }

            if ($sessionIds !== []) {
                DB::table('exam_schedules')
                    ->where('semester_id', $semesterId)
                    ->whereIn('unit_id', $timetable->sessions->pluck('unit_id')->filter()->unique()->all())
                    ->whereNotNull('program_timetable_session_id')
                    ->whereNotIn('program_timetable_session_id', $sessionIds)
                    ->delete();
            }
        });

        if ($synced > 0) {
            $this->auditService->log(
                'academics.exam_schedule.synced_from_timetable',
                'program_timetables',
                $timetable->id,
                null,
                [
                    'program_id' => $program->id,
                    'curriculum_version_id' => $intake->id,
                    'teaching_period' => $teachingPeriod,
                    'schedules_synced' => $synced,
                ],
                'Exam schedules synced from timetable',
                'success',
                Auth::id(),
            );
        }

        return $synced;
    }

    /**
     * The calendar semester a teaching period of this intake runs in. Only a write should open a
     * missing term, so browsing an intake never invents calendar rows of its own.
     */
    public function resolveSemesterId(CurriculumVersion $intake, int $teachingPeriod, bool $openMissing = false): ?int
    {
        if ($intake->academic_year_id) {
            $semester = Semester::query()
                ->where('academic_year_id', $intake->academic_year_id)
                ->where('semester_number', $teachingPeriod)
                ->first();

            if ($semester) {
                return (int) $semester->id;
            }
        }

        $semester = Semester::query()
            ->where('semester_number', $teachingPeriod)
            ->orderByDesc('id')
            ->first();

        if ($semester) {
            return (int) $semester->id;
        }

        return $openMissing ? $this->openSemesterForPeriod($intake, $teachingPeriod) : null;
    }

    /**
     * A year of study beyond the terms the calendar was seeded with still needs a semester row
     * before its units can carry a lecturer, a cohort or an exam sitting. The dates come from the
     * dates the office saved for that period, or run on from the last term on the calendar.
     */
    private function openSemesterForPeriod(CurriculumVersion $intake, int $teachingPeriod): ?int
    {
        $yearId = $intake->academic_year_id
            ?: DB::table('academic_years')->where('is_current', 1)->orderByDesc('id')->value('id')
            ?: DB::table('academic_years')->orderByDesc('id')->value('id');

        if (! $yearId) {
            return null;
        }

        $dates = $this->periodDates($intake, $teachingPeriod, (int) $yearId);

        if (! $dates) {
            return null;
        }

        $semester = Semester::create([
            'academic_year_id' => $yearId,
            'semester_label' => "Semester {$teachingPeriod}",
            'semester_number' => $teachingPeriod,
            'start_date' => $dates[0],
            'end_date' => $dates[1],
            'is_current' => 0,
            'created_by' => Auth::id(),
        ]);

        $this->auditService->log(
            'academics.semester.period_opened',
            'semesters',
            (int) $semester->id,
            null,
            [
                'semester_number' => $teachingPeriod,
                'curriculum_version_id' => $intake->id,
                'start_date' => $dates[0],
                'end_date' => $dates[1],
            ],
            'Semester opened so a later year of study can be scheduled',
            'success',
            Auth::id(),
        );

        return (int) $semester->id;
    }

    /**
     * @return array{0: string, 1: string}|null
     */
    private function periodDates(CurriculumVersion $intake, int $teachingPeriod, int $yearId): ?array
    {
        $planned = CurriculumVersionPeriod::query()
            ->where('curriculum_version_id', $intake->id)
            ->where('semester', $teachingPeriod)
            ->first();

        if ($planned?->start_date && $planned->end_date) {
            return [$planned->start_date->toDateString(), $planned->end_date->toDateString()];
        }

        $last = Semester::query()->where('academic_year_id', $yearId)->orderByDesc('end_date')->first();

        if (! $last?->start_date || ! $last->end_date) {
            return null;
        }

        $length = max(1, (int) $last->start_date->diffInDays($last->end_date));
        $start = $last->end_date->copy()->addDay();

        return [$start->toDateString(), $start->copy()->addDays($length)->toDateString()];
    }

    private function resolveExamDate(?CurriculumVersionPeriod $period, int $dayOfWeek): ?Carbon
    {
        $anchor = $period?->exam_start_date
            ?? $period?->learning_end_date
            ?? $period?->end_date
            ?? now();

        $start = Carbon::parse($anchor)->startOfDay();

        for ($offset = 0; $offset < 7; $offset++) {
            $candidate = $start->copy()->addDays($offset);
            if ((int) $candidate->dayOfWeekIso === $dayOfWeek) {
                return $candidate;
            }
        }

        return $start;
    }

    private function examTypeForSession(string $sessionType): string
    {
        return match ($sessionType) {
            'supplementary' => 'supplementary',
            'special_exam' => 'special',
            default => 'main',
        };
    }

    private function normalizeTime(mixed $time): string
    {
        if ($time instanceof Carbon) {
            return $time->format('H:i:s');
        }

        $time = (string) $time;

        return strlen($time) === 5 ? $time.':00' : $time;
    }
}
