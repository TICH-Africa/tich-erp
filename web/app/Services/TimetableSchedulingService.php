<?php



namespace App\Services;



use App\Models\AcademicProgram;

use App\Models\CurriculumVersion;

use App\Models\CurriculumVersionPeriod;

use App\Models\CurriculumVersionUnit;

use App\Models\Department;

use App\Models\ProgramTimetable;

use App\Models\ProgramTimetableSegment;

use App\Models\ProgramTimetableTemplate;

use App\Models\ProgramTimetableSession;

use App\Models\User;

use App\Support\UiText;

use Illuminate\Http\Request;

use Illuminate\Support\Carbon;

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

    /**

     * @return array<string, string>

     */

    public static function timetableKinds(): array

    {

        return ProgramTimetable::kindLabels();

    }



    /**
     * @return Collection<string, ProgramTimetable|null>
     */
    public function latestTimetablesByKind(
        int $programId,
        ?int $curriculumVersionId,
        int $teachingPeriod
    ): Collection {
        $kinds = array_keys(self::timetableKinds());

        $timetables = ProgramTimetable::query()
            ->with(['sessions.unit', 'sessions.staff', 'sessions.room', 'template.segments', 'template.days', 'curriculumVersion'])
            ->where('program_id', $programId)
            ->when($curriculumVersionId, fn ($query) => $query->where('curriculum_version_id', $curriculumVersionId))
            ->where('teaching_period', $teachingPeriod)
            ->whereIn('timetable_kind', array_merge($kinds, ['special_exam']))
            ->orderByDesc('updated_at')
            ->orderByDesc('id')
            ->get();

        $byKind = $timetables->unique('timetable_kind')->keyBy('timetable_kind');

        return collect($kinds)->mapWithKeys(function (string $kind) use ($byKind) {
            if ($kind === 'supplementary') {
                return [$kind => $byKind->get('supplementary') ?? $byKind->get('special_exam')];
            }

            return [$kind => $byKind->get($kind)];
        });
    }

    /**
     * @return Collection<int, ProgramTimetableSegment>
     */
    public function scheduleSegmentsForKind(ProgramTimetableTemplate $template, string $timetableKind): Collection
    {
        if (in_array($timetableKind, ['exam', 'supplementary'], true)) {
            $this->templates->ensureKindSegments($template, $timetableKind);
            $template->load('segments');
        }

        return $this->templates->segmentsForKind($template, $timetableKind);
    }

    public function normalizeTimetableKind(string $kind): string
    {
        if ($kind === 'special_exam') {
            return 'supplementary';
        }

        return array_key_exists($kind, self::timetableKinds()) ? $kind : 'lesson';
    }

    public function latestTimetable(

        int $programId,

        ?int $curriculumVersionId,

        int $teachingPeriod,

        string $timetableKind = 'lesson'

    ): ?ProgramTimetable {

        $timetableKind = $this->normalizeTimetableKind($timetableKind);

        $latest = ProgramTimetable::query()

            ->with(['sessions.unit', 'sessions.staff', 'sessions.room', 'template.segments', 'template.days', 'curriculumVersion'])

            ->where('program_id', $programId)

            ->when($curriculumVersionId, fn ($query) => $query->where('curriculum_version_id', $curriculumVersionId))

            ->where('teaching_period', $teachingPeriod)

            ->where('timetable_kind', $timetableKind)

            ->orderByDesc('updated_at')

            ->orderByDesc('id')

            ->first();

        if ($latest || $timetableKind !== 'supplementary') {
            return $latest;
        }

        return ProgramTimetable::query()
            ->with(['sessions.unit', 'sessions.staff', 'sessions.room', 'template.segments', 'template.days', 'curriculumVersion'])
            ->where('program_id', $programId)
            ->when($curriculumVersionId, fn ($query) => $query->where('curriculum_version_id', $curriculumVersionId))
            ->where('teaching_period', $teachingPeriod)
            ->where('timetable_kind', 'special_exam')
            ->orderByDesc('updated_at')
            ->orderByDesc('id')
            ->first();
    }



    public function publishedTimetable(

        int $programId,

        ?int $curriculumVersionId,

        int $teachingPeriod,

        string $timetableKind = 'lesson'

    ): ?ProgramTimetable {

        $timetableKind = $this->normalizeTimetableKind($timetableKind);

        $published = ProgramTimetable::query()

            ->with(['sessions.unit', 'sessions.staff', 'sessions.room', 'template.segments', 'template.days'])

            ->where('program_id', $programId)

            ->where('status', 'published')

            ->when($curriculumVersionId, fn ($query) => $query->where('curriculum_version_id', $curriculumVersionId))

            ->where('teaching_period', $teachingPeriod)

            ->where('timetable_kind', $timetableKind)

            ->orderByDesc('published_at')

            ->first();

        if ($published || $timetableKind !== 'supplementary') {
            return $published;
        }

        return ProgramTimetable::query()
            ->with(['sessions.unit', 'sessions.staff', 'sessions.room', 'template.segments', 'template.days'])
            ->where('program_id', $programId)
            ->where('status', 'published')
            ->when($curriculumVersionId, fn ($query) => $query->where('curriculum_version_id', $curriculumVersionId))
            ->where('teaching_period', $teachingPeriod)
            ->where('timetable_kind', 'special_exam')
            ->orderByDesc('published_at')
            ->first();
    }



    /**

     * @return Collection<int, ProgramTimetable>

     */

    public function publishedTimetablesForPeriod(

        int $programId,

        ?int $curriculumVersionId,

        int $teachingPeriod

    ): Collection {

        return ProgramTimetable::query()

            ->with(['sessions.unit', 'sessions.staff', 'sessions.room', 'template.segments', 'template.days'])

            ->where('program_id', $programId)

            ->where('status', 'published')

            ->when($curriculumVersionId, fn ($query) => $query->where('curriculum_version_id', $curriculumVersionId))

            ->where('teaching_period', $teachingPeriod)

            ->orderBy('timetable_kind')

            ->orderByDesc('published_at')

            ->get()

            ->unique('timetable_kind')

            ->values();

    }



    public function generate(

        User $user,

        Department $hub,

        AcademicProgram $program,

        CurriculumVersion $intake,

        int $teachingPeriod,

        string $timetableKind = 'lesson',

        ?string $title = null,

        ?Request $request = null

    ): ProgramTimetable {

        abort_unless($this->access->userCanAccessProgramInHub($user, $hub, $program), 403);

        abort_unless((int) $intake->program_id === (int) $program->id, 404);

        $timetableKind = $this->normalizeTimetableKind($timetableKind);

        if (! array_key_exists($timetableKind, self::timetableKinds())) {

            throw ValidationException::withMessages([

                'timetable_kind' => 'Invalid timetable type.',

            ]);

        }



        $template = $this->templates->templateForProgram($program->id);

        $template->load(['days', 'segments']);



        $activeDays = collect($template->activeDayNumbers());

        $scheduleSegments = $this->scheduleSegmentsForKind($template, $timetableKind);

        if ($activeDays->isEmpty()) {

            throw ValidationException::withMessages([

                'timetable' => 'Configure at least one active teaching day in the bell schedule.',

            ]);

        }



        if ($scheduleSegments->isEmpty()) {

            throw ValidationException::withMessages([

                'timetable' => 'Add at least one '.strtolower(self::timetableKinds()[$timetableKind]).' segment to the bell schedule before generating.',

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



        $period = $this->periodForSemester($intake->id, $teachingPeriod);

        $sessions = $timetableKind === 'lesson'

            ? $this->buildLessonSessions($units, $activeDays, $scheduleSegments, $period)

            : $this->buildExamSessions($units, $activeDays, $scheduleSegments, $timetableKind);



        $defaultTitle = UiText::normalizeDash($title ?: ProgramTimetable::kindLabels()[$timetableKind].' - Semester '.$teachingPeriod);



        return DB::transaction(function () use (

            $user,

            $program,

            $intake,

            $teachingPeriod,

            $template,

            $sessions,

            $units,

            $timetableKind,

            $defaultTitle,

            $request

        ) {

            $draftKinds = $timetableKind === 'supplementary'
                ? ['supplementary', 'special_exam']
                : [$timetableKind];

            ProgramTimetable::query()

                ->where('program_id', $program->id)

                ->where('curriculum_version_id', $intake->id)

                ->where('teaching_period', $teachingPeriod)

                ->whereIn('timetable_kind', $draftKinds)

                ->where('status', 'draft')

                ->delete();



            $timetable = ProgramTimetable::create([

                'program_id' => $program->id,

                'curriculum_version_id' => $intake->id,

                'teaching_period' => $teachingPeriod,

                'title' => $defaultTitle,

                'timetable_kind' => $timetableKind,

                'template_id' => $template->id,

                'status' => 'draft',

                'generation_notes' => $this->generationNotes($timetableKind, $units->count(), count($sessions)),

                'created_at' => now(),

                'updated_at' => now(),

            ]);



            foreach ($sessions as $session) {

                ProgramTimetableSession::create(array_merge($session, [

                    'program_timetable_id' => $timetable->id,

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

                    'timetable_kind' => $timetableKind,

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

            'title' => array_key_exists('title', $data) ? UiText::normalizeDash($data['title']) : null,

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

    public function moveSession(
        User $user,
        ProgramTimetable $timetable,
        ProgramTimetableSession $session,
        int $dayOfWeek,
        int $segmentId,
        ?int $swapSessionId = null,
        ?Request $request = null
    ): array {
        abort_unless($timetable->status === 'draft', 422, 'Only draft timetables can be edited.');
        abort_unless((int) $session->program_timetable_id === (int) $timetable->id, 404);

        $timetable->loadMissing('template.segments');
        $segment = $timetable->template?->segments->firstWhere('id', $segmentId);

        if (! $segment || $segment->segment_type === 'break') {
            throw ValidationException::withMessages([
                'session' => 'Invalid time slot.',
            ]);
        }

        $swapSession = null;
        if ($swapSessionId) {
            $swapSession = ProgramTimetableSession::query()
                ->where('program_timetable_id', $timetable->id)
                ->where('id', $swapSessionId)
                ->first();

            abort_unless($swapSession, 404);
            abort_unless((int) $swapSession->id !== (int) $session->id, 422);
        }

        $originalSession = [
            'day_of_week' => $session->day_of_week,
            'start_time' => $this->normalizeTime($session->start_time),
            'end_time' => $this->normalizeTime($session->end_time),
            'segment_id' => $session->segment_id,
        ];

        $originalSwap = $swapSession ? [
            'day_of_week' => $swapSession->day_of_week,
            'start_time' => $this->normalizeTime($swapSession->start_time),
            'end_time' => $this->normalizeTime($swapSession->end_time),
            'segment_id' => $swapSession->segment_id,
        ] : null;

        $newStart = $this->normalizeTime($segment->start_time);
        $newEnd = $this->normalizeTime($segment->end_time);

        if ($swapSession) {
            $swapSession->update([
                'day_of_week' => $originalSession['day_of_week'],
                'start_time' => $originalSession['start_time'],
                'end_time' => $originalSession['end_time'],
                'segment_id' => $originalSession['segment_id'],
            ]);
        }

        $session->update([
            'day_of_week' => $dayOfWeek,
            'start_time' => $newStart,
            'end_time' => $newEnd,
            'segment_id' => $segmentId,
        ]);

        $conflicts = $this->detectConflicts(
            $timetable->sessions()->with(['unit', 'staff', 'room'])->get()
        );

        if ($conflicts->isNotEmpty()) {
            $session->update($originalSession);

            if ($swapSession && $originalSwap) {
                $swapSession->update($originalSwap);
            }

            throw ValidationException::withMessages([
                'session' => $conflicts->first()['message'],
            ]);
        }

        $this->auditService->log(
            'academics.timetable.session_moved',
            'program_timetable_sessions',
            $session->id,
            $originalSession,
            [
                'day_of_week' => $dayOfWeek,
                'segment_id' => $segmentId,
                'swap_session_id' => $swapSessionId,
            ],
            'Timetable session moved',
            'success',
            $user->id,
            $request
        );

        return [
            'session' => $session->fresh(['unit', 'staff', 'room']),
            'swap_session' => $swapSession?->fresh(['unit', 'staff', 'room']),
        ];
    }

    public function publish(User $user, ProgramTimetable $timetable, ?Request $request = null): ProgramTimetable

    {

        abort_unless($timetable->status === 'draft', 422);



        ProgramTimetable::query()

            ->where('program_id', $timetable->program_id)

            ->where('curriculum_version_id', $timetable->curriculum_version_id)

            ->where('teaching_period', $timetable->teaching_period)

            ->where('timetable_kind', $timetable->timetable_kind)

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

                        'message' => 'Overlapping slots on '.TimetableTemplateService::dayLabels()[(int) $session->day_of_week]

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

     * @param  Collection<int, CurriculumVersionUnit>  $units

     * @param  Collection<int, int>  $activeDays

     * @param  Collection<int, ProgramTimetableSegment>  $lessonSegments

     * @return list<array<string, mixed>>

     */

    private function buildLessonSessions(

        Collection $units,

        Collection $activeDays,

        Collection $lessonSegments,

        ?CurriculumVersionPeriod $period

    ): array {

        $slots = $this->buildSlots($activeDays, $lessonSegments);

        $segmentHours = $this->averageSegmentHours($lessonSegments);

        $weeks = $this->weeksInPeriod(

            $period?->learning_start_date ?? $period?->start_date,

            $period?->learning_end_date ?? $period?->end_date

        );



        return $this->assignUnitsToSlots($units, $slots, $segmentHours, $weeks, 'lesson');

    }



    /**

     * @param  Collection<int, CurriculumVersionUnit>  $units

     * @param  Collection<int, int>  $activeDays

     * @param  Collection<int, ProgramTimetableSegment>  $examSegments

     * @return list<array<string, mixed>>

     */

    private function buildExamSessions(

        Collection $units,

        Collection $activeDays,

        Collection $examSegments,

        string $timetableKind

    ): array {

        $slots = $this->buildSlots($activeDays, $examSegments);

        $sessionType = $this->segmentTypeForKind($timetableKind);



        return $this->assignUnitsToSlots($units, $slots, 0, 1, $sessionType, 1);

    }



    /**

     * @param  Collection<int, int>  $activeDays

     * @param  Collection<int, ProgramTimetableSegment>  $segments

     * @return list<array{day_of_week: int, segment_id: int, start_time: string, end_time: string}>

     */

    private function buildSlots(Collection $activeDays, Collection $segments): array

    {

        $slots = [];



        foreach ($activeDays as $day) {

            foreach ($segments as $segment) {

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

    private function assignUnitsToSlots(

        Collection $units,

        array $slots,

        float $segmentHours,

        int $weeksInPeriod,

        string $sessionType,

        int $maxWeeklySessionsPerUnit = 0

    ): array {

        if ($slots === []) {

            return [];

        }



        $slotsByDay = collect($slots)->groupBy('day_of_week')->sortKeys();

        $days = $slotsByDay->keys()->values()->all();

        $dayCount = count($days);

        $slotCursor = array_fill_keys($days, 0);

        $sessions = [];

        $unitDayOffset = 0;



        foreach ($units->values() as $mapping) {

            $contactHours = (int) ($mapping->contact_hours ?: $mapping->unit?->contact_hours ?: 0);

            $weeklySessions = $maxWeeklySessionsPerUnit > 0

                ? $maxWeeklySessionsPerUnit

                : $this->weeklySessionsForUnit($contactHours, $segmentHours, $weeksInPeriod, count($slots));



            for ($sessionIndex = 0; $sessionIndex < $weeklySessions; $sessionIndex++) {

                $day = $days[($unitDayOffset + $sessionIndex) % $dayCount];

                $daySlots = $slotsByDay[$day]->values();

                $slotIndex = $slotCursor[$day] % $daySlots->count();

                $slot = $daySlots[$slotIndex];

                $slotCursor[$day]++;



                $sessions[] = [

                    'unit_id' => $mapping->unit_id,

                    'staff_id' => null,

                    'room_id' => null,

                    'day_of_week' => $slot['day_of_week'],

                    'start_time' => $slot['start_time'],

                    'end_time' => $slot['end_time'],

                    'session_type' => $sessionType,

                    'title' => $mapping->unit?->displayLabel() ?: null,

                    'segment_id' => $slot['segment_id'],

                    'class_group' => 'programme',

                ];

            }



            $unitDayOffset++;

        }



        return $sessions;

    }



    private function weeklySessionsForUnit(int $contactHours, float $segmentHours, int $weeksInPeriod, int $weeklySlotCount): int

    {

        if ($contactHours <= 0 || $segmentHours <= 0) {

            return 1;

        }



        $totalSessions = (int) ceil($contactHours / $segmentHours);

        $weeklySessions = (int) ceil($totalSessions / max(1, $weeksInPeriod));



        return max(1, min($weeklySessions, $weeklySlotCount));

    }



    /**

     * @param  Collection<int, ProgramTimetableSegment>  $segments

     */

    private function averageSegmentHours(Collection $segments): float

    {

        if ($segments->isEmpty()) {

            return 2.0;

        }



        $hours = $segments->map(function (ProgramTimetableSegment $segment) {

            $start = Carbon::createFromFormat('H:i:s', $this->normalizeTime($segment->start_time));

            $end = Carbon::createFromFormat('H:i:s', $this->normalizeTime($segment->end_time));



            return max(0.5, $start->diffInMinutes($end) / 60);

        });



        return max(0.5, (float) $hours->avg());

    }



    private function weeksInPeriod(mixed $start, mixed $end, int $default = 12): int

    {

        if (! $start || ! $end) {

            return $default;

        }



        $startDate = $start instanceof Carbon ? $start : Carbon::parse($start);

        $endDate = $end instanceof Carbon ? $end : Carbon::parse($end);



        if ($endDate->lt($startDate)) {

            return $default;

        }



        return max(1, (int) ceil($startDate->diffInDays($endDate) / 7));

    }



    private function periodForSemester(int $curriculumVersionId, int $teachingPeriod): ?CurriculumVersionPeriod

    {

        return CurriculumVersionPeriod::query()

            ->where('curriculum_version_id', $curriculumVersionId)

            ->where('semester', $teachingPeriod)

            ->whereNull('block_id')

            ->first();

    }



    private function segmentTypeForKind(string $timetableKind): string

    {

        return match ($timetableKind) {
            'exam' => 'exam',
            'supplementary' => 'supplementary',
            default => 'lesson',
        };

    }



    private function generationNotes(string $timetableKind, int $unitCount, int $sessionCount): string

    {

        $kindLabel = self::timetableKinds()[$timetableKind] ?? ucfirst($timetableKind);



        return "Auto-generated {$kindLabel} for {$unitCount} unit(s), {$sessionCount} session(s).";

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


