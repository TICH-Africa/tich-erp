<?php

namespace App\Services;

use App\Models\AcademicProgram;
use App\Models\CurriculumVersion;
use App\Models\CurriculumVersionPeriod;
use App\Models\ProgramTimetable;
use App\Models\Semester;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class ExamScheduleSyncService
{
    public function __construct(
        protected TimetableSchedulingService $timetables,
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

        $period = CurriculumVersionPeriod::query()
            ->where('curriculum_version_id', $intake->id)
            ->where('semester', $teachingPeriod)
            ->whereNull('block_id')
            ->first();

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

        return $synced;
    }

    public function resolveSemesterId(CurriculumVersion $intake, int $teachingPeriod): ?int
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

        return Semester::query()
            ->where('semester_number', $teachingPeriod)
            ->orderByDesc('id')
            ->value('id');
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
