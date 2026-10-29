<?php

namespace App\Services;

use App\Models\AcademicProgram;
use App\Models\ProgramTimetableSegment;
use App\Models\ProgramTimetableSession;
use App\Models\ProgramTimetableTemplate;
use App\Models\ProgramTimetableTemplateDay;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class TimetableTemplateService
{
    /**
     * How many classes the last bell schedule save carried to new hours.
     */
    public int $movedSessions = 0;

    public function __construct(protected AuditService $auditService) {}

    /**
     * @return array<string, string>
     */
    public static function segmentTypes(): array
    {
        return [
            'lesson' => 'Lesson',
            'orientation' => 'Orientation',
            'break' => 'Break',
            'lunch' => 'Lunch',
            'exam' => 'Exam',
            'supplementary' => 'Supplementary exam',
            'special_exam' => 'Special exam',
            'other' => 'Other',
        ];
    }

    /**
     * @return array<int, string>
     */
    public static function dayLabels(): array
    {
        return [
            1 => 'Monday',
            2 => 'Tuesday',
            3 => 'Wednesday',
            4 => 'Thursday',
            5 => 'Friday',
            6 => 'Saturday',
            7 => 'Sunday',
        ];
    }

    /**
     * @return list<string>
     */
    public static function segmentTypesForKind(string $timetableKind): array
    {
        return match ($timetableKind) {
            'exam' => ['exam'],
            'supplementary' => ['supplementary'],
            default => ['lesson', 'break'],
        };
    }

    /**
     * @return list<array{label: string, start_time: string, end_time: string, segment_type: string}>
     */
    public static function defaultSegmentsForKind(string $timetableKind): array
    {
        return match ($timetableKind) {
            'exam' => [
                ['label' => 'Exam session 1', 'start_time' => '08:00', 'end_time' => '10:00', 'segment_type' => 'exam'],
                ['label' => 'Exam session 2', 'start_time' => '11:00', 'end_time' => '13:00', 'segment_type' => 'exam'],
                ['label' => 'Exam session 3', 'start_time' => '14:00', 'end_time' => '16:00', 'segment_type' => 'exam'],
            ],
            'supplementary' => [
                ['label' => 'Retake session 1', 'start_time' => '08:00', 'end_time' => '10:00', 'segment_type' => 'supplementary'],
                ['label' => 'Retake session 2', 'start_time' => '11:00', 'end_time' => '13:00', 'segment_type' => 'supplementary'],
                ['label' => 'Retake session 3', 'start_time' => '14:00', 'end_time' => '16:00', 'segment_type' => 'supplementary'],
            ],
            default => [
                ['label' => 'Lesson 1', 'start_time' => '08:00', 'end_time' => '10:00', 'segment_type' => 'lesson'],
                ['label' => 'Lesson 2', 'start_time' => '10:00', 'end_time' => '12:00', 'segment_type' => 'lesson'],
                ['label' => 'Lunch break', 'start_time' => '12:00', 'end_time' => '14:00', 'segment_type' => 'break'],
                ['label' => 'Lesson 3', 'start_time' => '14:00', 'end_time' => '16:00', 'segment_type' => 'lesson'],
                ['label' => 'Lesson 4', 'start_time' => '16:00', 'end_time' => '18:00', 'segment_type' => 'lesson'],
            ],
        };
    }

    /**
     * @return \Illuminate\Support\Collection<int, ProgramTimetableSegment>
     */
    public function segmentsForKind(ProgramTimetableTemplate $template, string $timetableKind): \Illuminate\Support\Collection
    {
        if ($timetableKind === 'lesson') {
            return $template->segments
                ->filter(fn (ProgramTimetableSegment $segment) => $segment->segment_type === 'lesson')
                ->sortBy('sort_order')
                ->values();
        }

        $types = self::segmentTypesForKind($timetableKind);

        return $template->segments
            ->filter(fn (ProgramTimetableSegment $segment) => in_array($segment->segment_type, $types, true))
            ->sortBy('sort_order')
            ->values();
    }

    /**
     * @return \Illuminate\Support\Collection<int, ProgramTimetableSegment>
     */
    public function ensureKindSegments(ProgramTimetableTemplate $template, string $timetableKind): \Illuminate\Support\Collection
    {
        if ($timetableKind === 'lesson') {
            return $this->segmentsForKind($template, $timetableKind);
        }

        $existing = $this->segmentsForKind($template, $timetableKind);
        if ($existing->isNotEmpty()) {
            return $existing;
        }

        $sortOrder = (int) ($template->segments->max('sort_order') ?? -1) + 1;
        $created = collect();

        foreach (self::defaultSegmentsForKind($timetableKind) as $row) {
            $created->push(ProgramTimetableSegment::create([
                'template_id' => $template->id,
                'label' => $row['label'],
                'start_time' => $row['start_time'],
                'end_time' => $row['end_time'],
                'segment_type' => $row['segment_type'],
                'sort_order' => $sortOrder++,
            ]));
        }

        $template->unsetRelation('segments');
        $template->load('segments');

        return $created;
    }

    public function syncKindSlots(
        User $user,
        AcademicProgram $program,
        string $timetableKind,
        array $data,
        ?Request $request = null
    ): ProgramTimetableTemplate {
        if (! in_array($timetableKind, ['exam', 'supplementary'], true)) {
            throw ValidationException::withMessages([
                'timetable_kind' => 'Invalid timetable slot type.',
            ]);
        }

        $template = $this->templateForProgram($program->id);
        $this->movedSessions = 0;
        $segmentTypes = self::segmentTypesForKind($timetableKind);
        $rows = $data['segments'] ?? [];

        DB::transaction(function () use ($template, $segmentTypes, $rows, $program, $timetableKind) {
            $oldSegments = ProgramTimetableSegment::query()
                ->where('template_id', $template->id)
                ->whereIn('segment_type', $segmentTypes)
                ->get();

            ProgramTimetableSegment::query()
                ->where('template_id', $template->id)
                ->whereIn('segment_type', $segmentTypes)
                ->delete();

            $sortOrder = (int) (ProgramTimetableSegment::query()
                ->where('template_id', $template->id)
                ->max('sort_order') ?? -1) + 1;

            $created = 0;
            $newStarts = [];

            foreach ($rows as $index => $row) {
                $label = trim((string) ($row['label'] ?? ''));
                $start = $row['start_time'] ?? null;
                $end = $row['end_time'] ?? null;

                if ($label === '' || ! $start || ! $end) {
                    continue;
                }

                if ($start >= $end) {
                    throw ValidationException::withMessages([
                        "segments.{$index}.end_time" => 'End time must be after start time.',
                    ]);
                }

                ProgramTimetableSegment::create([
                    'template_id' => $template->id,
                    'label' => $label,
                    'start_time' => $start,
                    'end_time' => $end,
                    'segment_type' => $segmentTypes[0],
                    'sort_order' => $sortOrder++,
                ]);

                $newStarts[$label.'|'.$segmentTypes[0]] = substr((string) $start, 0, 5);

                $created++;
            }

            if ($created === 0) {
                throw ValidationException::withMessages([
                    'segments' => 'Add at least one time slot.',
                ]);
            }

            $this->movedSessions = $oldSegments->count() > 0
                ? $this->moveSessionsWithSlots($program->id, $timetableKind, $oldSegments, $newStarts)
                : 0;
        });

        $this->auditService->log(
            'academics.timetable_template.slots_updated',
            'program_timetable_templates',
            $template->id,
            null,
            ['program_id' => $program->id, 'timetable_kind' => $timetableKind],
            'Timetable slots updated',
            'success',
            $user->id,
            $request
        );

        return $template->fresh(['days', 'segments']);
    }

    public function templateForProgram(int $programId): ProgramTimetableTemplate
    {
        $template = ProgramTimetableTemplate::query()
            ->with(['days', 'segments'])
            ->where('program_id', $programId)
            ->where('is_default', 1)
            ->first();

        if ($template) {
            return $template;
        }

        return $this->seedDefaultTemplate($programId);
    }

    public function syncTemplate(User $user, AcademicProgram $program, array $data, ?Request $request = null): ProgramTimetableTemplate
    {
        $template = $this->templateForProgram($program->id);
        $this->movedSessions = 0;

        if (! empty($data['name'])) {
            $template->update([
                'name' => $data['name'],
                'updated_at' => now(),
            ]);
        }

        $activeDays = collect($data['days'] ?? [])
            ->map(fn ($day) => (int) $day)
            ->filter(fn ($day) => $day >= 1 && $day <= 7)
            ->unique()
            ->values();

        if ($activeDays->isEmpty()) {
            throw ValidationException::withMessages([
                'days' => 'Select at least one teaching day.',
            ]);
        }

        DB::transaction(function () use ($template, $activeDays, $data, $program) {
            // The classes on a slot follow the slot, so the old hours are kept before the rows go.
            $oldSegments = ProgramTimetableSegment::query()
                ->where('template_id', $template->id)
                ->whereIn('segment_type', ['lesson', 'break'])
                ->get();

            ProgramTimetableTemplateDay::query()
                ->where('template_id', $template->id)
                ->delete();

            foreach (array_keys(self::dayLabels()) as $dayNumber) {
                ProgramTimetableTemplateDay::create([
                    'template_id' => $template->id,
                    'day_of_week' => $dayNumber,
                    'is_active' => $activeDays->contains($dayNumber) ? 1 : 0,
                ]);
            }

            ProgramTimetableSegment::query()
                ->where('template_id', $template->id)
                ->whereIn('segment_type', ['lesson', 'break'])
                ->delete();

            $segments = $data['segments'] ?? [];
            $sortOrder = 0;
            $newStarts = [];

            foreach ($segments as $index => $row) {
                $label = trim((string) ($row['label'] ?? ''));
                $start = $row['start_time'] ?? null;
                $end = $row['end_time'] ?? null;

                if ($label === '' || ! $start || ! $end) {
                    continue;
                }

                if ($start >= $end) {
                    throw ValidationException::withMessages([
                        "segments.{$index}.end_time" => 'End time must be after start time.',
                    ]);
                }

                $type = (string) ($row['segment_type'] ?? 'lesson');
                if (! in_array($type, ['lesson', 'break'], true)) {
                    $type = 'lesson';
                }

                ProgramTimetableSegment::create([
                    'template_id' => $template->id,
                    'label' => $label,
                    'start_time' => $start,
                    'end_time' => $end,
                    'segment_type' => $type,
                    'sort_order' => $sortOrder++,
                ]);

                $newStarts[$label.'|'.$type] = substr((string) $start, 0, 5);
            }

            if ($sortOrder === 0) {
                throw ValidationException::withMessages([
                    'segments' => 'Add at least one lesson or break segment.',
                ]);
            }

            $this->movedSessions = $oldSegments->count() > 0
                ? $this->moveSessionsWithSlots($program->id, 'lesson', $oldSegments, $newStarts)
                : 0;
        });

        $this->auditService->log(
            'academics.timetable_template.updated',
            'program_timetable_templates',
            $template->id,
            null,
            ['program_id' => $program->id],
            'Programme timetable template updated',
            'success',
            $user->id,
            $request
        );

        return $template->fresh(['days', 'segments']);
    }

    /**
     * A bell slot that moves takes its classes with it: anything starting on the slot's old hour is
     * pushed by the same amount and keeps its own length. A class the officer timed away from the
     * slot is left exactly as it was typed.
     *
     * @param  \Illuminate\Support\Collection<int, ProgramTimetableSegment>  $oldSegments
     * @param  array<string, string>  $newStarts  slot label|type => the hour it now opens at
     */
    private function moveSessionsWithSlots(
        int $programId,
        string $timetableKind,
        \Illuminate\Support\Collection $oldSegments,
        array $newStarts
    ): int {
        $timetableIds = DB::table('program_timetables')
            ->where('program_id', $programId)
            ->where('timetable_kind', $timetableKind)
            ->pluck('id');

        if ($timetableIds->isEmpty()) {
            return 0;
        }

        $sessionsByStart = ProgramTimetableSession::query()
            ->whereIn('program_timetable_id', $timetableIds)
            ->get()
            ->groupBy(fn (ProgramTimetableSession $session) => substr((string) $session->start_time, 0, 5));

        $moved = 0;

        foreach ($oldSegments as $segment) {
            $oldStart = substr((string) $segment->start_time, 0, 5);
            $newStart = $newStarts[$segment->label.'|'.$segment->segment_type] ?? null;

            if ($newStart === null || $newStart === $oldStart || ! $sessionsByStart->has($oldStart)) {
                continue;
            }

            $delta = $this->minutes($newStart) - $this->minutes($oldStart);

            foreach ($sessionsByStart->get($oldStart) as $session) {
                $isRest = in_array((string) $session->session_type, ['break', 'lunch'], true);

                if (($segment->segment_type === 'break') !== $isRest) {
                    continue;
                }

                $start = $this->shiftTime((string) $session->start_time, $delta);
                $end = $this->shiftTime((string) $session->end_time, $delta);

                if ($start === null || $end === null) {
                    continue;
                }

                $session->update(['start_time' => $start, 'end_time' => $end]);
                $moved++;
            }
        }

        return $moved;
    }

    private function minutes(string $time): int
    {
        return (int) substr($time, 0, 2) * 60 + (int) substr($time, 3, 2);
    }

    private function shiftTime(string $time, int $delta): ?string
    {
        $minutes = $this->minutes($time) + $delta;

        if ($minutes < 0 || $minutes > 23 * 60 + 59) {
            return null;
        }

        return sprintf('%02d:%02d', intdiv($minutes, 60), $minutes % 60);
    }

    private function seedDefaultTemplate(int $programId): ProgramTimetableTemplate
    {
        return DB::transaction(function () use ($programId) {
            $template = ProgramTimetableTemplate::create([
                'program_id' => $programId,
                'name' => 'Default bell schedule',
                'is_default' => 1,
                'created_at' => now(),
            ]);

            foreach ([1, 2, 3, 4, 5] as $day) {
                ProgramTimetableTemplateDay::create([
                    'template_id' => $template->id,
                    'day_of_week' => $day,
                    'is_active' => 1,
                ]);
            }

            $defaults = [
                ['Lesson 1', '08:00', '10:00', 'lesson'],
                ['Lesson 2', '10:00', '12:00', 'lesson'],
                ['Lunch break', '12:00', '14:00', 'break'],
                ['Lesson 3', '14:00', '16:00', 'lesson'],
                ['Lesson 4', '16:00', '18:00', 'lesson'],
            ];

            foreach ($defaults as $order => [$label, $start, $end, $type]) {
                ProgramTimetableSegment::create([
                    'template_id' => $template->id,
                    'label' => $label,
                    'start_time' => $start,
                    'end_time' => $end,
                    'segment_type' => $type,
                    'sort_order' => $order,
                ]);
            }

            return $template->load(['days', 'segments']);
        });
    }
}
