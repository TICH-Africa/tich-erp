<?php

namespace App\Services;

use App\Models\AcademicProgram;
use App\Models\CurriculumVersion;
use App\Models\CurriculumVersionUnit;
use App\Models\Department;
use App\Models\ProgramTimetable;
use App\Models\ProgramTimetableSegment;
use App\Models\ProgramTimetableSession;
use App\Models\ProgramTimetableTemplate;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class TimetableSchedulingService
{
    public function __construct(
        protected AcademicsAccessService $access,
        protected TimetableTemplateService $templates,
        protected AuditService $auditService,
    ) {}

    public function latestTimetable(
        int $programId,
        ?int $curriculumVersionId,
        int $teachingPeriod
    ): ?ProgramTimetable {
        return ProgramTimetable::query()
            ->with(['sessions.unit', 'sessions.staff', 'sessions.room', 'template.segments', 'template.days', 'curriculumVersion'])
            ->where('program_id', $programId)
            ->when($curriculumVersionId, fn ($query) => $query->where('curriculum_version_id', $curriculumVersionId))
            ->where('teaching_period', $teachingPeriod)
            ->orderByDesc('updated_at')
            ->orderByDesc('id')
            ->first();
    }

    public function publishedTimetable(
        int $programId,
        ?int $curriculumVersionId,
        int $teachingPeriod
    ): ?ProgramTimetable {
        return ProgramTimetable::query()
            ->with(['sessions.unit', 'sessions.staff', 'sessions.room', 'template.segments', 'template.days'])
            ->where('program_id', $programId)
            ->where('status', 'published')
            ->when($curriculumVersionId, fn ($query) => $query->where('curriculum_version_id', $curriculumVersionId))
            ->where('teaching_period', $teachingPeriod)
            ->orderByDesc('published_at')
            ->first();
    }

    public function generate(
        User $user,
        Department $hub,
        AcademicProgram $program,
        CurriculumVersion $intake,
        int $teachingPeriod,
        ?Request $request = null
    ): ProgramTimetable {
        abort_unless($this->access->userCanAccessProgramInHub($user, $hub, $program), 403);
        abort_unless((int) $intake->program_id === (int) $program->id, 404);

        $template = $this->templates->templateForProgram($program->id);
        $template->load(['days', 'segments']);

        $activeDays = collect($template->activeDayNumbers());
        $lessonSegments = $template->segments->filter(fn (ProgramTimetableSegment $segment) => $segment->segment_type === 'lesson');

        if ($activeDays->isEmpty()) {
            throw ValidationException::withMessages([
                'timetable' => 'Configure at least one active teaching day in the bell schedule.',
            ]);
        }

        if ($lessonSegments->isEmpty()) {
            throw ValidationException::withMessages([
                'timetable' => 'Add at least one lesson segment to the bell schedule before generating.',
            ]);
        }

        $units = CurriculumVersionUnit::query()
            ->with('unit')
            ->where('curriculum_version_id', $intake->id)
            ->where('semester', $teachingPeriod)
            ->orderBy('display_order')
            ->orderBy('priority')
            ->get();

        if ($units->isEmpty()) {
            throw ValidationException::withMessages([
                'timetable' => 'No units mapped to this teaching period. Assign units in Semester units first.',
            ]);
        }

        $slots = $this->buildSlots($activeDays, $lessonSegments);
        $sessions = $this->assignUnitsToSlots($units, $slots);

        return DB::transaction(function () use ($user, $program, $intake, $teachingPeriod, $template, $sessions, $units, $request) {
            ProgramTimetable::query()
                ->where('program_id', $program->id)
                ->where('curriculum_version_id', $intake->id)
                ->where('teaching_period', $teachingPeriod)
                ->where('status', 'draft')
                ->delete();

            $timetable = ProgramTimetable::create([
                'program_id' => $program->id,
                'curriculum_version_id' => $intake->id,
                'teaching_period' => $teachingPeriod,
                'template_id' => $template->id,
                'status' => 'draft',
                'generation_notes' => 'Auto-generated for '.$units->count().' unit(s).',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            foreach ($sessions as $session) {
                ProgramTimetableSession::create(array_merge($session, [
                    'program_timetable_id' => $timetable->id,
                    'session_type' => 'lesson',
                ]));
            }

            $this->auditService->log(
                'academics.timetable.generated',
                'program_timetables',
                $timetable->id,
                null,
                [
                    'program_id' => $program->id,
                    'curriculum_version_id' => $intake->id,
                    'teaching_period' => $teachingPeriod,
                    'session_count' => count($sessions),
                ],
                'Programme timetable draft generated',
                'success',
                $user->id,
                $request
            );

            return $timetable->load(['sessions.unit', 'sessions.staff', 'sessions.room', 'template.segments']);
        });
    }

    public function addSession(User $user, ProgramTimetable $timetable, array $data, ?Request $request = null): ProgramTimetableSession
    {
        abort_unless($timetable->status === 'draft', 422, 'Only draft timetables can be edited.');

        $session = ProgramTimetableSession::create([
            'program_timetable_id' => $timetable->id,
            'unit_id' => $data['unit_id'] ?? null,
            'staff_id' => $data['staff_id'] ?? null,
            'room_id' => $data['room_id'] ?? null,
            'day_of_week' => (int) $data['day_of_week'],
            'start_time' => $data['start_time'],
            'end_time' => $data['end_time'],
            'session_type' => $data['session_type'] ?? 'lesson',
            'title' => $data['title'] ?? null,
            'venue' => $data['venue'] ?? null,
            'class_group' => $data['class_group'] ?? null,
        ]);

        $session->load(['unit', 'staff', 'room']);

        $conflicts = $this->detectConflicts(
            $timetable->sessions()->with(['unit', 'staff', 'room'])->get()
        );

        if ($conflicts->isNotEmpty()) {
            $session->delete();
            throw ValidationException::withMessages([
                'session' => $conflicts->first()['message'],
            ]);
        }

        return $session->load(['unit', 'staff', 'room']);
    }

    public function publish(User $user, ProgramTimetable $timetable, ?Request $request = null): ProgramTimetable
    {
        abort_unless($timetable->status === 'draft', 422);

        ProgramTimetable::query()
            ->where('program_id', $timetable->program_id)
            ->where('curriculum_version_id', $timetable->curriculum_version_id)
            ->where('teaching_period', $timetable->teaching_period)
            ->where('status', 'published')
            ->update(['status' => 'archived', 'updated_at' => now()]);

        $timetable->update([
            'status' => 'published',
            'published_at' => now(),
            'published_by' => $user->id,
            'updated_at' => now(),
        ]);

        $this->auditService->log(
            'academics.timetable.published',
            'program_timetables',
            $timetable->id,
            ['status' => 'draft'],
            ['status' => 'published'],
            'Programme timetable published',
            'success',
            $user->id,
            $request
        );

        return $timetable->fresh(['sessions.unit', 'sessions.staff', 'sessions.room']);
    }

    /**
     * @return Collection<int, array{type: string, message: string}>
     */
    public function detectConflicts(Collection $sessions): Collection
    {
        $conflicts = collect();

        foreach ($sessions as $index => $session) {
            foreach ($sessions as $otherIndex => $other) {
                if ($otherIndex <= $index) {
                    continue;
                }

                if ((int) $session->day_of_week !== (int) $other->day_of_week) {
                    continue;
                }

                if (! $this->timesOverlap($session->start_time, $session->end_time, $other->start_time, $other->end_time)) {
                    continue;
                }

                if ($session->room_id && $other->room_id && (int) $session->room_id === (int) $other->room_id) {
                    $conflicts->push([
                        'type' => 'room',
                        'message' => 'Room conflict on '.TimetableTemplateService::dayLabels()[(int) $session->day_of_week]
                            .' between '.$session->displayTitle().' and '.$other->displayTitle().'.',
                    ]);
                }

                if ($session->staff_id && $other->staff_id && (int) $session->staff_id === (int) $other->staff_id) {
                    $conflicts->push([
                        'type' => 'lecturer',
                        'message' => 'Lecturer conflict on '.TimetableTemplateService::dayLabels()[(int) $session->day_of_week]
                            .' between '.$session->displayTitle().' and '.$other->displayTitle().'.',
                    ]);
                }

                if ($session->unit_id && $other->unit_id && (int) $session->unit_id === (int) $other->unit_id) {
                    $conflicts->push([
                        'type' => 'unit',
                        'message' => 'Duplicate unit slot on '.TimetableTemplateService::dayLabels()[(int) $session->day_of_week]
                            .' for '.$session->displayTitle().'.',
                    ]);
                }

                if ($session->class_group && $other->class_group
                    && $session->class_group === $other->class_group
                    && $session->session_type === 'lesson'
                    && $other->session_type === 'lesson') {
                    $conflicts->push([
                        'type' => 'students',
                        'message' => 'Student group conflict on '.TimetableTemplateService::dayLabels()[(int) $session->day_of_week]
                            .' for class '.$session->class_group.'.',
                    ]);
                }
            }
        }

        return $conflicts->unique(fn ($conflict) => $conflict['message'])->values();
    }

    /**
     * @param  Collection<int, int>  $activeDays
     * @param  Collection<int, ProgramTimetableSegment>  $lessonSegments
     * @return list<array{day_of_week: int, segment_id: int, start_time: string, end_time: string}>
     */
    private function buildSlots(Collection $activeDays, Collection $lessonSegments): array
    {
        $slots = [];

        foreach ($activeDays as $day) {
            foreach ($lessonSegments as $segment) {
                $slots[] = [
                    'day_of_week' => (int) $day,
                    'segment_id' => $segment->id,
                    'start_time' => $this->normalizeTime($segment->start_time),
                    'end_time' => $this->normalizeTime($segment->end_time),
                ];
            }
        }

        return $slots;
    }

    /**
     * @param  Collection<int, CurriculumVersionUnit>  $units
     * @param  list<array{day_of_week: int, segment_id: int, start_time: string, end_time: string}>  $slots
     * @return list<array<string, mixed>>
     */
    private function assignUnitsToSlots(Collection $units, array $slots): array
    {
        $sessions = [];
        $slotCount = count($slots);

        if ($slotCount === 0) {
            return $sessions;
        }

        foreach ($units->values() as $index => $mapping) {
            $slot = $slots[$index % $slotCount];

            $sessions[] = [
                'unit_id' => $mapping->unit_id,
                'staff_id' => null,
                'room_id' => null,
                'day_of_week' => $slot['day_of_week'],
                'start_time' => $slot['start_time'],
                'end_time' => $slot['end_time'],
                'title' => $mapping->unit?->unit_code.' — '.$mapping->unit?->unit_name,
                'segment_id' => $slot['segment_id'],
                'class_group' => 'programme',
            ];
        }

        return $sessions;
    }

    private function timesOverlap(mixed $startA, mixed $endA, mixed $startB, mixed $endB): bool
    {
        $startA = $this->normalizeTime($startA);
        $endA = $this->normalizeTime($endA);
        $startB = $this->normalizeTime($startB);
        $endB = $this->normalizeTime($endB);

        return $startA < $endB && $startB < $endA;
    }

    private function normalizeTime(mixed $time): string
    {
        if ($time instanceof \Carbon\CarbonInterface) {
            return $time->format('H:i:s');
        }

        $time = (string) $time;

        return strlen($time) === 5 ? $time.':00' : $time;
    }
}
