<?php

namespace App\Services;

use App\Models\AcademicProgram;
use App\Models\AcademicYear;
use App\Models\CurriculumVersion;
use App\Models\CurriculumVersionPeriod;
use App\Models\CurriculumVersionUnit;
use App\Models\NursingBlock;
use App\Models\Semester;
use App\Models\Student;
use App\Models\User;
use App\Support\IntakeIdentity;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * The Academics desk that puts enrolled students onto the units of their intake.
 *
 * student_semester_registrations and registered_units drive the student's own unit
 * list, the lecturer's class roster, exam cards and exam eligibility, but nothing in
 * the app wrote them - only the LecturerSeeder demo method did.
 */
class StudentUnitRegistrationService
{
    public function __construct(
        protected CurriculumVersionService $versions,
        protected ExamScheduleSyncService $schedule,
        protected AuditService $audits,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function desk(Request $request, AcademicProgram $program, CurriculumVersion $intake): array
    {
        $mappings = $this->versions->mappedUnits($intake);
        $periodOptions = $this->periodOptions($program);
        $unitsByPeriod = $this->unitsByPeriod($mappings, $periodOptions);
        $students = $this->candidates($request, $program, $intake);
        $yearsByStudent = $this->yearsForRoster($students, $program, $intake);
        $allYearsByStudent = $yearsByStudent;

        $selectedKey = $this->resolvePeriodKey($request, $periodOptions, $yearsByStudent, $unitsByPeriod);
        $selected = $periodOptions->get($selectedKey);

        // A cohort that spans admission years is only useful split by the year each student
        // actually sits, so the filter reads the derived year rather than the admission date. It
        // opens on the year of the teaching period on screen: working Year 1 Semester 2 means the
        // Year 1 class, not every intake on the programme. "All years" is still one choice away.
        $studyYear = $request->integer('study_year');

        if ($selected && ! $request->has('study_year')) {
            $studyYear = $program->yearOfPeriod((int) $selected['semester']);
        }

        if ($studyYear > 0) {
            $students = $students->filter(fn (Student $student) => (int) ($yearsByStudent[$student->id]['year'] ?? 0) === $studyYear)->values();
            $yearsByStudent = $yearsByStudent->only($students->pluck('id')->all());
        }

        $units = $selected
            ? $this->mappingsForPeriod($mappings, $selected['semester'], $selected['block_id'])
            : collect();

        return [
            'periodOptions' => $periodOptions,
            'selectedPeriodKey' => $selectedKey,
            'selectedPeriod' => $selected,
            'semesterId' => $selected ? $this->schedule->resolveSemesterId($intake, $selected['semester']) : null,
            'periodUnits' => $units,
            'unitsByPeriod' => $unitsByPeriod,
            'lecturersByUnit' => $this->lecturersByUnit($units, $intake),
            'students' => $students,
            'yearsByStudent' => $yearsByStudent,
            'studyYearFilter' => $studyYear > 0 ? $studyYear : null,
            'studyYears' => $this->sortedUnique($allYearsByStudent->filter()->map(fn (array $year) => $year['year'])->values()->all()),
            'pathways' => $this->distinctValues($program, 'entry_pathway'),
            'admissionYears' => $this->admissionYears($program),
            'pathwayFilter' => $request->string('entry_pathway')->toString() ?: null,
            'yearFilter' => $request->integer('admission_year') ?: null,
            'search' => trim($request->string('student_search')->toString()),
            'registeredUnitIds' => $this->registeredUnitIds(
                $students->pluck('id')->all(),
                $units->pluck('unit_id')->filter()->all(),
            ),
            'holds' => $this->holdsFor($students, $program, $intake),
        ];
    }

    /**
     * How many units each teaching period carries for this intake, in period order. The desk
     * opens on a period that has units instead of an empty one.
     *
     * @param  Collection<string, array{semester: int, block_id: int|null}>  $periodOptions
     * @return Collection<string, int>
     */
    private function unitsByPeriod(Collection $mappings, Collection $periodOptions): Collection
    {
        return $periodOptions->map(
            fn (array $option) => $this->mappingsForPeriod($mappings, $option['semester'], $option['block_id'])->count()
        );
    }

    /**
     * The year of study for every student on a list, so the roster can group by it.
     *
     * @param  Collection<int, Student>  $students
     * @return Collection<int, array<string, mixed>>
     */
    public function yearsForRoster(Collection $students, AcademicProgram $program, ?CurriculumVersion $intake = null): Collection
    {
        return $students->mapWithKeys(fn (Student $student) => [
            $student->id => $this->yearOfStudy($student, $program, $intake),
        ])->filter();
    }

    /**
     * @param  Collection<int, int>  $values
     * @return list<int>
     */
    private function sortedUnique(array $values): array
    {
        return collect($values)->filter()->unique()->sort()->values()->all();
    }

    /**
     * Register students onto units for one teaching period.
     *
     * @param  list<int>  $studentIds
     * @param  list<int>  $unitIds
     * @return array{students: int, units: int}
     */
    public function register(
        User $registrar,
        AcademicProgram $program,
        CurriculumVersion $intake,
        int $semesterId,
        array $studentIds,
        array $unitIds,
    ): array {
        $studentIds = collect($studentIds)->map(fn ($id) => (int) $id)->filter()->unique()->values()->all();
        $unitIds = collect($unitIds)->map(fn ($id) => (int) $id)->filter()->unique()->values()->all();

        if ($studentIds === [] || $unitIds === []) {
            throw ValidationException::withMessages([
                'students' => 'Select at least one student and one unit to register.',
            ]);
        }

        if (Student::query()->where('program_id', $program->id)->whereIn('id', $studentIds)->count() !== count($studentIds)) {
            throw ValidationException::withMessages([
                'students' => 'Register students from this programme only.',
            ]);
        }

        $mappedUnits = CurriculumVersionUnit::query()
            ->where('curriculum_version_id', $intake->id)
            ->whereIn('unit_id', $unitIds)
            ->pluck('unit_id')
            ->map(fn ($id) => (int) $id)
            ->all();

        if (count($mappedUnits) !== count($unitIds)) {
            throw ValidationException::withMessages([
                'unit_ids' => 'Map every unit into this intake before registering students onto it.',
            ]);
        }

        $registered = 0;
        $unitsWritten = 0;

        DB::transaction(function () use ($registrar, $semesterId, $studentIds, $mappedUnits, &$registered, &$unitsWritten) {
            foreach ($studentIds as $studentId) {
                $registrationId = DB::table('student_semester_registrations')
                    ->where('student_id', $studentId)
                    ->where('semester_id', $semesterId)
                    ->value('id');

                if ($registrationId) {
                    DB::table('student_semester_registrations')
                        ->where('id', $registrationId)
                        ->update([
                            'registration_type' => 'admin',
                            'status' => 'registered',
                            'registered_by' => $registrar->id,
                            'registration_date' => now()->toDateString(),
                        ]);
                } else {
                    $registrationId = DB::table('student_semester_registrations')->insertGetId([
                        'student_id' => $studentId,
                        'semester_id' => $semesterId,
                        'registration_date' => now()->toDateString(),
                        'registered_by' => $registrar->id,
                        'registration_type' => 'admin',
                        'unit_count' => 0,
                        'status' => 'registered',
                        'is_fee_cleared' => 0,
                    ]);
                }

                $registered++;

                foreach ($mappedUnits as $unitId) {
                    $exists = DB::table('registered_units')
                        ->where('semester_registration_id', $registrationId)
                        ->where('unit_id', $unitId)
                        ->exists();

                    if ($exists) {
                        continue;
                    }

                    DB::table('registered_units')->insertOrIgnore([
                        'semester_registration_id' => $registrationId,
                        'unit_id' => $unitId,
                        'is_additional' => 0,
                        'created_at' => now(),
                    ]);

                    $unitsWritten++;
                }

                DB::table('student_semester_registrations')
                    ->where('id', $registrationId)
                    ->update([
                        'unit_count' => DB::table('registered_units')
                            ->where('semester_registration_id', $registrationId)
                            ->count(),
                    ]);
            }
        });

        return ['students' => $registered, 'units' => $unitsWritten];
    }

    /**
     * The year of study a student is sitting, and the teaching period that goes with it.
     *
     * The desk used to open every cohort on semester 1, so a 2025 intake was still being
     * handed first-year units. Counting the admission year against the academic year now
     * running gives the year. The semester inside that year comes from the dates on the
     * intake, so a class moves from Year 1 Semester 1 to Year 1 Semester 2 by itself when
     * those dates start, and the units mapped to the new semester are the ones they are due.
     * An officer can pin the year on the student record (students.current_year) and that
     * wins, because re-admits, repeats and transfers do not follow the arithmetic.
     *
     * @return array{year: int, period: int, period_key: string, period_label: string, term: int, terms: int, basis: string, semester_source: string, overridden: bool}|null
     */
    public function yearOfStudy(Student $student, AcademicProgram $program, ?CurriculumVersion $intake = null): ?array
    {
        if ($program->usesBlocks()) {
            return null;
        }

        $terms = max(1, (int) $program->termsPerYear());
        $years = $program->studyYears();

        $pinned = $student->current_year ? (int) $student->current_year : null;
        $yearPinned = $pinned !== null && $pinned >= 1 && $pinned <= $years;

        if ($yearPinned) {
            $year = $pinned;
            $basis = 'year pinned on the student record';
        } else {
            $admissionYear = $this->admissionYear($student, $intake);
            $runningYear = $this->currentAcademicYearStart();

            if (! $admissionYear || ! $runningYear) {
                return null;
            }

            $year = min(max($runningYear - $admissionYear + 1, 1), $years);
            $basis = 'admitted '.$admissionYear.', academic year '.$runningYear.'/'.($runningYear + 1);
        }

        $first = (($year - 1) * $terms) + 1;
        $last = $first + $terms - 1;
        $onRecord = $student->current_semester_id
            ? (int) Semester::query()->whereKey($student->current_semester_id)->value('semester_number')
            : 0;

        // The semester always comes from the intake dates when it carries them, including for a
        // pinned year: a repeating class sits its own year semester by semester, so pinning Year 1
        // must not freeze a student on Semester 1. The recorded semester only speaks when no dates
        // are on record.
        $calendar = $this->calendarPeriod($intake, $program, $year);

        if ($calendar) {
            $period = $calendar['period'];
            $basis .= '; '.$calendar['basis'];
            $semesterSource = 'semester dates on the intake';
        } elseif ($onRecord >= $first && $onRecord <= $last) {
            $period = $onRecord;
            $semesterSource = $yearPinned ? 'semester pinned on the student record' : 'semester last registered';
        } else {
            $period = $first;
            $semesterSource = 'start of the year';
        }

        return [
            'year' => $year,
            'period' => $period,
            'period_key' => 'sem-'.$period,
            'period_label' => $program->periodLabel($period),
            'term' => (($period - 1) % $terms) + 1,
            'terms' => $terms,
            'basis' => $basis,
            'semester_source' => $semesterSource,
            'overridden' => $yearPinned,
        ];
    }

    /**
     * Which semester of the year the intake's own dates put a cohort on.
     *
     * Years 2 to 4 teach in the same windows as year 1 unless the intake is given dates of
     * their own, so the running semester of the year is carried across every year of study.
     *
     * @return array{period: int, term: int, basis: string}|null
     */
    private function calendarPeriod(?CurriculumVersion $intake, AcademicProgram $program, int $year): ?array
    {
        if (! $intake) {
            return null;
        }

        $terms = max(1, (int) $program->termsPerYear());
        $first = (($year - 1) * $terms) + 1;

        $dated = CurriculumVersionPeriod::query()
            ->where('curriculum_version_id', $intake->id)
            ->whereNull('block_id')
            ->whereNotNull('start_date')
            ->orderBy('semester')
            ->get();

        if ($dated->isEmpty()) {
            return null;
        }

        $own = $dated->filter(fn (CurriculumVersionPeriod $period) => (int) $period->semester >= $first
            && (int) $period->semester < $first + $terms)->values();

        $running = CurriculumVersionPeriod::runningPeriod($own->isNotEmpty() ? $own : $dated);

        if (! $running?->start_date) {
            return null;
        }

        $term = ((max(1, (int) $running->semester) - 1) % $terms) + 1;

        return [
            'period' => $first + $term - 1,
            'term' => $term,
            'basis' => 'semester '.$term.' running from '.$running->start_date->toFormattedDateString(),
        ];
    }

    /**
     * Where each student actually sits: the last teaching period they were registered on, and the
     * units they still carry an F in. Two queries, so a whole cohort is read at once.
     *
     * @param  list<int>  $studentIds
     * @return array<int, array{sitting: int, owed: list<string>}>
     */
    public function progressionState(array $studentIds): array
    {
        $studentIds = collect($studentIds)->map(fn ($id) => (int) $id)->filter()->unique()->values()->all();

        if ($studentIds === []) {
            return [];
        }

        $state = [];

        foreach ($studentIds as $studentId) {
            $state[$studentId] = ['sitting' => 0, 'owed' => []];
        }

        // Only a registration that actually holds units counts as having sat a semester, so a
        // student cleared off every unit on a semester can be moved onto it again.
        $sat = DB::table('student_semester_registrations as ssr')
            ->join('semesters as s', 's.id', '=', 'ssr.semester_id')
            ->join('registered_units as ru', 'ru.semester_registration_id', '=', 'ssr.id')
            ->whereIn('ssr.student_id', $studentIds)
            ->groupBy('ssr.student_id')
            ->get(['ssr.student_id', DB::raw('MAX(s.semester_number) as sitting')]);

        foreach ($sat as $row) {
            $state[(int) $row->student_id]['sitting'] = max($state[(int) $row->student_id]['sitting'], (int) $row->sitting);
        }

        $graded = DB::table('grade_records as gr')
            ->join('units as u', 'u.id', '=', 'gr.unit_id')
            ->whereIn('gr.student_id', $studentIds)
            ->orderBy('gr.id')
            ->get(['gr.student_id', 'gr.unit_id', 'gr.grade_letter', 'u.unit_code']);

        $latest = [];

        foreach ($graded as $row) {
            $latest[(int) $row->student_id][(int) $row->unit_id] = $row;
        }

        foreach ($latest as $studentId => $units) {
            if (! isset($state[$studentId])) {
                continue;
            }

            foreach ($units as $row) {
                if (strtoupper((string) $row->grade_letter) === 'F') {
                    $state[$studentId]['owed'][] = $row->unit_code;
                }
            }

            sort($state[$studentId]['owed']);
        }

        return $state;
    }

    /**
     * Put a cohort on the semester its intake dates say it is sitting and hand it the units mapped
     * to that semester. Nobody presses anything: the scheduler runs this each night, so the class
     * that finished Semester 1 is on Semester 2 units the morning Semester 2 begins.
     *
     * A student owed a failed unit stays where they are, and a student already registered on the
     * semester they are due for is left alone, so re-running the roll adds nothing.
     *
     * @return array{moved: int, units: int, held: list<string>, waiting: list<string>, idle: int}
     */
    public function advanceCohort(User $actor, AcademicProgram $program, CurriculumVersion $intake): array
    {
        $result = ['moved' => 0, 'units' => 0, 'held' => [], 'waiting' => [], 'idle' => 0];

        if ($program->usesBlocks()) {
            return $result;
        }

        // Every active student on the programme is rolled, not only the ones admitted in this
        // intake's month: Year 2 to 4 sit the same curriculum and only their admission year tells
        // them apart. The command hands one intake per programme to this method, so nobody moves twice.
        $students = Student::query()
            ->where('program_id', $program->id)
            ->where('enrollment_status', 'active')
            ->with('applicant')
            ->orderBy('registration_number')
            ->get();

        if ($students->isEmpty()) {
            return $result;
        }

        $progression = $this->progressionState($students->pluck('id')->all());
        $mappings = $this->versions->mappedUnits($intake);

        foreach ($students as $student) {
            $due = $this->yearOfStudy($student, $program, $intake);

            if (! $due) {
                $result['waiting'][] = $student->registration_number.': no admission year on record.';

                continue;
            }

            $sitting = $progression[$student->id]['sitting'] ?? 0;
            $owed = $progression[$student->id]['owed'] ?? [];

            if ($due['period'] <= $sitting) {
                $result['idle']++;

                continue;
            }

            // Academics can lift a hold for the semester the student is due for, so the nightly roll
            // moves them without anyone registering the units by hand.
            $cleared = $student->hold_cleared_period !== null && (int) $student->hold_cleared_period === $due['period'];

            if ($owed !== [] && $sitting >= 1 && ! $cleared) {
                $result['held'][] = $student->registration_number.' stays on '.$program->periodLabel($sitting)
                    .' - owes '.implode(', ', $owed);

                $this->noteHold($actor, $student, $due, $owed);

                continue;
            }

            $semesterId = $this->schedule->resolveSemesterId($intake, $due['period'], true);

            if (! $semesterId) {
                $result['waiting'][] = $student->registration_number
                    .': this intake has no calendar semester for '.$due['period_label'].'.';

                continue;
            }

            $before = $student->current_semester_id ? (int) $student->current_semester_id : null;

            // Only the semester moves. students.current_year is the officer's pin, and writing it
            // here would freeze the cohort on this semester for good. The cleared hold marker is
            // spent once the roll has carried the student past it.
            $moving = ['current_semester_id' => (int) $semesterId];

            if ($student->hold_cleared_period !== null && (int) $student->hold_cleared_period <= $due['period']) {
                $moving['hold_cleared_period'] = null;
            }

            $student->forceFill($moving)->save();

            $unitIds = $this->mappingsForPeriod($mappings, $due['period'], null)
                ->pluck('unit_id')
                ->filter()
                ->unique()
                ->values()
                ->all();

            if ($unitIds !== []) {
                $written = $this->register($actor, $program, $intake, (int) $semesterId, [$student->id], $unitIds);
                $result['units'] += $written['units'];
            } else {
                $result['waiting'][] = $student->registration_number.': '.$due['period_label'].' has no units mapped yet.';
            }

            $this->audits->log(
                action: 'academics.student_cohort_advanced',
                entityType: 'students',
                entityId: $student->id,
                oldValue: ['current_semester_id' => $before],
                newValue: [
                    'current_semester_id' => (int) $semesterId,
                    'teaching_period' => $due['period'],
                    'period_label' => $due['period_label'],
                    'units' => count($unitIds),
                ],
                reason: ($cleared ? 'Hold on '.implode(', ', $owed).' lifted by Academics; ' : '').$due['basis'],
                userId: $actor->id,
            );

            $result['moved']++;
        }

        return $result;
    }

    /**
     * Lift a hold Academics are responsible for: the student owes a repeat unit, but the office is
     * moving them onto the next semester anyway. The marker only covers the semester it is set on,
     * so the next semester's results are read again from scratch.
     *
     * @return array{period_label: string, owed: list<string>, units: int}
     */
    public function clearHold(User $officer, AcademicProgram $program, CurriculumVersion $intake, Student $student, string $reason): array
    {
        $due = $this->yearOfStudy($student, $program, $intake);

        if (! $due) {
            throw ValidationException::withMessages([
                'student' => $student->registration_number.': no admission year on record, so there is no semester to move them to.',
            ]);
        }

        $progress = $this->progressionState([$student->id])[$student->id] ?? ['sitting' => 0, 'owed' => []];

        if ($progress['owed'] === []) {
            throw ValidationException::withMessages([
                'student' => $student->registration_number.' is not held back - no failed unit on record.',
            ]);
        }

        if ($due['period'] <= $progress['sitting']) {
            throw ValidationException::withMessages([
                'student' => $student->registration_number.' is already on '.$program->periodLabel($progress['sitting'])
                    .', so there is no hold to lift.',
            ]);
        }

        $semesterId = $this->schedule->resolveSemesterId($intake, $due['period'], true);

        if (! $semesterId) {
            throw ValidationException::withMessages([
                'student' => 'This intake has no calendar semester for '.$due['period_label'].'. Add it on the Semester units tab first.',
            ]);
        }

        $before = [
            'current_semester_id' => $student->current_semester_id ? (int) $student->current_semester_id : null,
            'hold_cleared_period' => $student->hold_cleared_period ? (int) $student->hold_cleared_period : null,
        ];

        $student->forceFill([
            'current_semester_id' => (int) $semesterId,
            'hold_cleared_period' => $due['period'],
        ])->save();

        $unitIds = $this->mappingsForPeriod($this->versions->mappedUnits($intake), $due['period'], null)
            ->pluck('unit_id')
            ->filter()
            ->unique()
            ->values()
            ->all();

        $units = 0;

        if ($unitIds !== []) {
            $units = $this->register($officer, $program, $intake, (int) $semesterId, [$student->id], $unitIds)['units'];
        }

        $this->audits->log(
            action: 'academics.student_cohort_hold_cleared',
            entityType: 'students',
            entityId: $student->id,
            oldValue: $before,
            newValue: [
                'current_semester_id' => (int) $semesterId,
                'teaching_period' => $due['period'],
                'period_label' => $due['period_label'],
                'owed_units' => $progress['owed'],
                'units' => $units,
            ],
            reason: $reason,
            userId: $officer->id,
        );

        return ['period_label' => $due['period_label'], 'owed' => $progress['owed'], 'units' => $units];
    }

    /**
     * Record a hold once per semester it applies to, so a nightly roll does not fill the trail.
     */
    private function noteHold(User $actor, Student $student, array $due, array $owed): void
    {
        $logged = DB::table('audit_logs')
            ->where('action', 'academics.student_cohort_held')
            ->where('entity_type', 'students')
            ->where('entity_id', $student->id)
            ->where('new_value', 'like', '%"teaching_period":'.$due['period'].',%')
            ->exists();

        if ($logged) {
            return;
        }

        $this->audits->log(
            action: 'academics.student_cohort_held',
            entityType: 'students',
            entityId: $student->id,
            newValue: ['teaching_period' => $due['period'], 'period_label' => $due['period_label'], 'owed_units' => $owed],
            reason: 'Held on their semester until '.implode(', ', $owed).' are passed.',
            userId: $actor->id,
        );
    }

    /**
     * Why the cohort roll is holding a student: the units still carrying an F and the semester they
     * are stuck on. A hold lifts on its own when the repeat is passed, and Academics can lift it for
     * one semester from the student's record.
     *
     * @param  array<string, mixed>|null  $year
     * @param  array{sitting: int, owed: list<string>}|null  $progress
     * @return array{held: bool, owed: list<string>, sitting: int, cleared: string|null}
     */
    public function holdNote(Student $student, ?array $year, ?array $progress = null): array
    {
        $note = ['held' => false, 'owed' => [], 'sitting' => 0, 'cleared' => null];

        if (! $year) {
            return $note;
        }

        $progress ??= $this->progressionState([$student->id])[$student->id] ?? ['sitting' => 0, 'owed' => []];
        $cleared = $student->hold_cleared_period !== null && (int) $student->hold_cleared_period === (int) $year['period'];

        return [
            'held' => $progress['owed'] !== []
                && (int) $progress['sitting'] >= 1
                && (int) $year['period'] > (int) $progress['sitting']
                && ! $cleared,
            'owed' => $progress['owed'],
            'sitting' => (int) $progress['sitting'],
            'cleared' => $cleared ? (string) $year['period_label'] : null,
        ];
    }

    /**
     * The hold note for every student on a list, read in one pass.
     *
     * @param  Collection<int, Student>  $students
     * @return array<int, array{held: bool, owed: list<string>, sitting: int, cleared: string|null}>
     */
    public function holdsFor(Collection $students, AcademicProgram $program, ?CurriculumVersion $intake = null): array
    {
        $progression = $this->progressionState($students->pluck('id')->all());

        return $students->mapWithKeys(fn (Student $student) => [
            (int) $student->id => $this->holdNote(
                $student,
                $this->yearOfStudy($student, $program, $intake),
                $progression[(int) $student->id] ?? null,
            ),
        ])->all();
    }

    /**
     * Register each student onto the units mapped to their own year of study.
     *
     * @param  list<int>  $studentIds
     * @return array{students: int, units: int, groups: list<string>, skipped: list<string>}
     */
    public function registerByYear(User $registrar, AcademicProgram $program, CurriculumVersion $intake, array $studentIds): array
    {
        $mappings = $this->versions->mappedUnits($intake);
        $groups = [];
        $skipped = [];

        $students = Student::query()
            ->where('program_id', $program->id)
            ->whereIn('id', collect($studentIds)->map(fn ($id) => (int) $id)->filter()->all())
            ->with('applicant')
            ->get();

        foreach ($students as $student) {
            $year = $this->yearOfStudy($student, $program, $intake);

            if (! $year) {
                $skipped[] = $student->registration_number.': no admission year on record.';

                continue;
            }

            $unitIds = $this->mappingsForPeriod($mappings, $year['period'], null)
                ->pluck('unit_id')
                ->filter()
                ->unique()
                ->values()
                ->all();

            if ($unitIds === []) {
                $skipped[] = $student->registration_number.': no units mapped to '.$year['period_label'].' yet.';

                continue;
            }

            $semesterId = $this->schedule->resolveSemesterId($intake, $year['period'], true);

            if (! $semesterId) {
                $skipped[] = $student->registration_number.': this intake has no calendar semester for '.$year['period_label'].'.';

                continue;
            }

            $groups[$year['period']]['semester_id'] = (int) $semesterId;
            $groups[$year['period']]['label'] = $year['period_label'];
            $groups[$year['period']]['students'][] = (int) $student->id;
            $groups[$year['period']]['units'] = $unitIds;
        }

        $registered = 0;
        $unitsWritten = 0;
        $written = [];

        foreach ($groups as $period => $group) {
            $result = $this->register(
                $registrar,
                $program,
                $intake,
                $group['semester_id'],
                $group['students'],
                $group['units'],
            );

            $registered += $result['students'];
            $unitsWritten += $result['units'];
            $written[] = $group['label'].': '.$result['students'].' '.str('student')->plural($result['students']).', '.$result['units'].' unit '.str('place')->plural($result['units']);
        }

        return [
            'students' => $registered,
            'units' => $unitsWritten,
            'groups' => $written,
            'skipped' => $skipped,
        ];
    }

    /**
     * Everything the corrections panel for one student needs: the year they sit, the units
     * they hold, and the units their year is due for that they do not hold yet.
     *
     * @return array<string, mixed>
     */
    public function corrections(Student $student, AcademicProgram $program, CurriculumVersion $intake): array
    {
        $year = $this->yearOfStudy($student, $program, $intake);
        $mappings = $this->versions->mappedUnits($intake);

        $rows = DB::table('registered_units as ru')
            ->join('student_semester_registrations as ssr', 'ssr.id', '=', 'ru.semester_registration_id')
            ->join('semesters as s', 's.id', '=', 'ssr.semester_id')
            ->join('units as u', 'u.id', '=', 'ru.unit_id')
            ->where('ssr.student_id', $student->id)
            ->orderBy('s.semester_number')
            ->get([
                'ru.id',
                'ru.unit_id',
                'ru.is_additional',
                'ssr.id as registration_id',
                'ssr.semester_id',
                's.semester_number',
                's.semester_label',
                'u.unit_code',
                'u.unit_name',
            ]);

        $unitIds = $rows->pluck('unit_id')->filter()->unique()->all();
        $graded = $unitIds === []
            ? collect()
            : DB::table('grade_records')
                ->where('student_id', $student->id)
                ->whereIn('unit_id', $unitIds)
                ->pluck('grade_letter', 'unit_id');
        $assessed = $unitIds === []
            ? collect()
            : DB::table('cat_scores')
                ->where('student_id', $student->id)
                ->whereIn('unit_id', $unitIds)
                ->pluck('score_obtained', 'unit_id');

        $duePeriod = $year['period'] ?? null;
        $dueUnits = $duePeriod
            ? $this->mappingsForPeriod($mappings, (int) $duePeriod, null)
            : collect();
        $heldUnitIds = $rows->pluck('unit_id')->map(fn ($id) => (int) $id)->all();

        return [
            'year' => $year,
            'semesterId' => $duePeriod ? $this->schedule->resolveSemesterId($intake, (int) $duePeriod) : null,
            'registered' => $rows->map(function ($row) use ($graded, $assessed, $duePeriod) {
                $unitId = (int) $row->unit_id;

                return [
                    'id' => (int) $row->id,
                    'unit_id' => $unitId,
                    'unit_code' => (string) $row->unit_code,
                    'unit_name' => (string) $row->unit_name,
                    'semester_id' => (int) $row->semester_id,
                    'semester_label' => (string) $row->semester_label,
                    'outside_their_year' => $duePeriod !== null && (int) $row->semester_number !== (int) $duePeriod,
                    'grade' => $graded[$unitId] ?? null,
                    'has_marks' => $graded->has($unitId) || $assessed->has($unitId),
                ];
            }),
            'missing' => $dueUnits
                ->reject(fn (CurriculumVersionUnit $mapping) => in_array((int) $mapping->unit_id, $heldUnitIds, true))
                ->map(function (CurriculumVersionUnit $mapping) {
                    $unit = $mapping->unit;

                    return [
                        'unit_id' => (int) $mapping->unit_id,
                        'unit_code' => (string) ($unit?->unit_code ?? ''),
                        'unit_name' => (string) ($unit?->unit_name ?? ''),
                    ];
                })
                ->values(),
            'dueUnits' => $dueUnits,
            'maxYear' => $program->studyYears(),
            'hold' => $this->holdNote($student, $year),
        ];
    }

    /**
     * Add one unit to the student's registration for their own year.
     *
     * @return array{students: int, units: int}
     */
    public function addUnit(User $officer, Student $student, AcademicProgram $program, CurriculumVersion $intake, int $unitId): array
    {
        $corrections = $this->corrections($student, $program, $intake);
        $semesterId = $corrections['semesterId'];

        if (! $semesterId) {
            throw ValidationException::withMessages([
                'unit_id' => 'This intake has no calendar semester for the student\'s teaching period yet. Set the period dates on the Semesters tab.',
            ]);
        }

        $due = collect($corrections['dueUnits'])
            ->first(fn (CurriculumVersionUnit $mapping) => (int) $mapping->unit_id === $unitId);

        if (! $due) {
            throw ValidationException::withMessages([
                'unit_id' => 'Only units mapped into this intake for the student\'s own teaching period can be added here.',
            ]);
        }

        return $this->register($officer, $program, $intake, (int) $semesterId, [$student->id], [$unitId]);
    }

    /**
     * Take a unit off a student.
     *
     * This is deliberately the slowest action on the panel: it needs a written reason, it
     * refuses while marks exist for the unit, and it is audited, so a registered student
     * cannot be un-assigned on a casual click.
     */
    public function removeUnit(User $officer, Student $student, int $registeredUnitId, string $reason): array
    {
        $row = DB::table('registered_units as ru')
            ->join('student_semester_registrations as ssr', 'ssr.id', '=', 'ru.semester_registration_id')
            ->join('units as u', 'u.id', '=', 'ru.unit_id')
            ->join('semesters as s', 's.id', '=', 'ssr.semester_id')
            ->where('ru.id', $registeredUnitId)
            ->where('ssr.student_id', $student->id)
            ->first(['ru.id', 'ru.unit_id', 'ssr.id as registration_id', 's.semester_label', 'u.unit_code', 'u.unit_name']);

        if (! $row) {
            throw ValidationException::withMessages([
                'unit' => 'That unit is not on this student\'s registration any more. Reload the page.',
            ]);
        }

        $grade = DB::table('grade_records')
            ->where('student_id', $student->id)
            ->where('unit_id', $row->unit_id)
            ->value('grade_letter');

        if ($grade) {
            throw ValidationException::withMessages([
                'unit' => $row->unit_code.' already carries a grade ('.$grade.'). Amend or clear the result before removing the unit.',
            ]);
        }

        $catCount = DB::table('cat_scores')
            ->where('student_id', $student->id)
            ->where('unit_id', $row->unit_id)
            ->count();

        if ($catCount > 0) {
            throw ValidationException::withMessages([
                'unit' => $row->unit_code.' has '.$catCount.' CAT '.str('entry')->plural($catCount).' recorded. Clear those marks before removing the unit.',
            ]);
        }

        DB::table('registered_units')->where('id', $row->id)->delete();

        $remaining = DB::table('registered_units')
            ->where('semester_registration_id', $row->registration_id)
            ->count();

        DB::table('student_semester_registrations')
            ->where('id', $row->registration_id)
            ->update(['unit_count' => $remaining]);

        $this->audits->log(
            action: 'academics.student_unit_removed',
            entityType: 'students',
            entityId: $student->id,
            oldValue: [
                'unit_code' => $row->unit_code,
                'unit_name' => $row->unit_name,
                'semester_label' => $row->semester_label,
            ],
            newValue: ['unit_count' => $remaining],
            reason: $reason,
            userId: $officer->id,
        );

        return [
            'unit_code' => (string) $row->unit_code,
            'unit_count' => $remaining,
        ];
    }

    /**
     * Pin the year and semester of study on the student record, overriding the admission arithmetic.
     *
     * The year decides which units the student is due for; the semester decides which one inside
     * that year they are sitting, so both are written together and the period is auditable.
     *
     * @return array{year: int, term: int, period: int, period_label: string, semester_id: int|null}
     */
    public function pinYear(
        User $officer,
        Student $student,
        AcademicProgram $program,
        CurriculumVersion $intake,
        int $year,
        int $term,
        string $reason,
    ): array {
        $years = $program->studyYears();
        $terms = max(1, (int) $program->termsPerYear());

        if ($year < 1 || $year > $years) {
            throw ValidationException::withMessages([
                'current_year' => 'Year of study must be between 1 and '.$years.' for '.($program->program_code ?: 'this programme').'.',
            ]);
        }

        if ($term < 1 || $term > $terms) {
            throw ValidationException::withMessages([
                'current_term' => $program->program_code.' runs '.$terms.' semester'.($terms === 1 ? '' : 's').' a year, so the semester must be between 1 and '.$terms.'.',
            ]);
        }

        $period = (($year - 1) * $terms) + $term;
        $semesterId = $this->schedule->resolveSemesterId($intake, $period, true);

        if (! $semesterId) {
            throw ValidationException::withMessages([
                'current_term' => 'This intake has no calendar semester for '.$program->periodLabel($period).'. Add it on the Semester units tab first.',
            ]);
        }

        $before = ['current_year' => $student->current_year, 'current_semester_id' => $student->current_semester_id];

        $student->forceFill([
            'current_year' => $year,
            'current_semester_id' => $semesterId,
        ])->save();

        $this->audits->log(
            action: 'academics.student_year_of_study_pinned',
            entityType: 'students',
            entityId: $student->id,
            oldValue: $before,
            newValue: ['current_year' => $year, 'current_semester_id' => $semesterId, 'teaching_period' => $period],
            reason: $reason,
            userId: $officer->id,
        );

        return [
            'year' => $year,
            'term' => $term,
            'period' => $period,
            'period_label' => $program->periodLabel($period),
            'semester_id' => (int) $semesterId,
        ];
    }

    /**
     * Drop the year pinned on a student so their admission date decides the year again. A pin is for
     * a repeating class, so it has to be liftable: while it stands the student sits that year's
     * semesters for the rest of their time on the programme.
     */
    public function releaseYearPin(User $officer, Student $student, string $reason): void
    {
        $before = ['current_year' => $student->current_year];

        $student->forceFill(['current_year' => null])->save();

        $this->audits->log(
            action: 'academics.student_year_of_study_released',
            entityType: 'students',
            entityId: $student->id,
            oldValue: $before,
            newValue: ['current_year' => null],
            reason: $reason,
            userId: $officer->id,
        );
    }

    /**
     * Lecturers already allocated to the period's units, so Academics can see who a
     * student will actually be reporting to.
     *
     * @return Collection<int, string>
     */
    private function lecturersByUnit(Collection $units, CurriculumVersion $intake): Collection
    {
        $unitIds = $units->pluck('unit_id')->filter()->unique()->all();

        if ($unitIds === []) {
            return collect();
        }

        return DB::table('unit_allocations as ua')
            ->join('staff as s', 's.id', '=', 'ua.staff_id')
            ->whereIn('ua.unit_id', $unitIds)
            ->where('ua.is_active', 1)
            ->where('ua.status', 'approved')
            ->orderBy('s.surname')
            ->get(['ua.unit_id', 's.first_name', 's.surname'])
            ->groupBy('unit_id')
            ->map(fn (Collection $rows) => $rows
                ->map(fn ($row) => trim($row->first_name.' '.$row->surname))
                ->implode(', '));
    }

    /**
     * @return Collection<int, Student>
     */
    private function candidates(Request $request, AcademicProgram $program, CurriculumVersion $intake): Collection
    {
        $query = Student::query()
            ->with(['applicant', 'campus'])
            ->where('program_id', $program->id)
            ->orderBy('registration_number');

        $status = $request->string('enrollment_status')->toString();
        if ($status !== '') {
            $query->where('enrollment_status', $status);
        }

        $pathway = $request->string('entry_pathway')->toString();
        if ($pathway !== '') {
            $query->where('entry_pathway', $pathway);
        }

        $year = $request->integer('admission_year');
        if ($year > 0) {
            $query->where(function (Builder $q) use ($year) {
                $q->whereYear('date_of_admission', $year)
                    ->orWhere('cohort_intake', 'like', $year.'%')
                    ->orWhereHas('applicant', fn (Builder $a) => $a->where('intake_year', $year));
            });
        }

        $search = trim($request->string('student_search')->toString());
        if ($search !== '') {
            $query->where(function (Builder $q) use ($search) {
                $q->where('registration_number', 'like', '%'.$search.'%')
                    ->orWhere('first_name', 'like', '%'.$search.'%')
                    ->orWhere('surname', 'like', '%'.$search.'%');
            });
        }

        return $query->get()
            ->sortByDesc(fn (Student $student) => IntakeIdentity::studentMatchesIntake($student, $intake) ? 1 : 0)
            ->values();
    }

    /**
     * @return Collection<string, array{key: string, label: string, semester: int, block_id: int|null}>
     */
    private function periodOptions(AcademicProgram $program): Collection
    {
        $numbers = range(1, max(1, $program->scheduledPeriods()));

        if ($program->usesBlocks()) {
            $blocks = NursingBlock::query()
                ->where('program_id', $program->id)
                ->orderBy('block_order')
                ->get();

            if ($blocks->isNotEmpty()) {
                return $blocks->mapWithKeys(fn (NursingBlock $block) => [
                    'block-'.$block->id => [
                        'key' => 'block-'.$block->id,
                        'label' => $block->block_label,
                        'semester' => (int) $block->block_order,
                        'block_id' => (int) $block->id,
                    ],
                ]);
            }
        }

        return collect($numbers)->mapWithKeys(fn (int $number) => [
            'sem-'.$number => [
                'key' => 'sem-'.$number,
                'label' => $program->periodLabel($number),
                'semester' => $number,
                'block_id' => null,
            ],
        ]);
    }

    /**
     * Open the desk on a period that actually carries units.
     *
     * The cohort's own year comes first, but the desk used to stop there and show an empty
     * list whenever the intake had not been mapped onto that period yet - which read as
     * "only Year 1 units ever load". Falling back to the first mapped period keeps the
     * screen usable, and the panel says which periods hold units either way.
     *
     * @param  Collection<string, array{semester: int, block_id: int|null}>  $periodOptions
     * @param  Collection<int, array<string, mixed>|null>  $yearsByStudent
     * @param  Collection<string, int>  $unitsByPeriod
     */
    private function resolvePeriodKey(Request $request, Collection $periodOptions, Collection $yearsByStudent, Collection $unitsByPeriod): string
    {
        $requested = $request->string('registration_period')->toString();

        if ($requested !== '' && $periodOptions->has($requested)) {
            return $requested;
        }

        $own = $yearsByStudent
            ->filter()
            ->map(fn (array $year) => $year['period_key'])
            ->countBy()
            ->sortDesc()
            ->keys()
            ->first();

        if ($own !== null && $periodOptions->has($own) && (int) ($unitsByPeriod[$own] ?? 0) > 0) {
            return (string) $own;
        }

        $populated = $unitsByPeriod->search(fn (int $count) => $count > 0);

        if ($populated !== false) {
            return (string) $populated;
        }

        return (string) ($own ?? $periodOptions->keys()->first() ?? 'sem-1');
    }

    /**
     * The year the student started the programme, from the first field that carries it.
     */
    private function admissionYear(Student $student, ?CurriculumVersion $intake): ?int
    {
        $admitted = $student->date_of_admission ? (int) substr((string) $student->date_of_admission, 0, 4) : null;

        if ($admitted && $admitted > 1990) {
            return $admitted;
        }

        if (preg_match('/(19|20)\d{2}/', (string) $student->cohort_intake, $cohort)) {
            return (int) $cohort[0];
        }

        $applicantYear = $student->applicant?->intake_year;

        if ($applicantYear) {
            return (int) $applicantYear;
        }

        return $intake?->intake_year ? (int) $intake->intake_year : null;
    }

    /**
     * The academic year now running, taken from its start date so a Sep start is read as
     * the year it opens in rather than the calendar year it ends in.
     */
    private function currentAcademicYearStart(): ?int
    {
        $year = AcademicYear::query()
            ->where('is_current', 1)
            ->orderByDesc('start_date')
            ->first();

        if ($year) {
            if ($year->start_date) {
                return (int) $year->start_date->format('Y');
            }

            if (preg_match('/(19|20)\d{2}/', (string) $year->year_label, $label)) {
                return (int) $label[0];
            }
        }

        $fallback = (int) now()->format('Y');

        return $fallback > 1990 ? $fallback : null;
    }

    /**
     * @return Collection<int, CurriculumVersionUnit>
     */
    private function mappingsForPeriod(Collection $mappings, int $semester, ?int $blockId): Collection
    {
        return $mappings->filter(function (CurriculumVersionUnit $mapping) use ($semester, $blockId) {
            if ($blockId) {
                return (int) $mapping->block_id === $blockId;
            }

            return (int) $mapping->semester === $semester && ! $mapping->block_id;
        })->values();
    }

    /**
     * @param  list<int>  $studentIds
     * @param  list<int>  $unitIds
     * @return Collection<int, Collection<int, int>>
     */
    private function registeredUnitIds(array $studentIds, array $unitIds): Collection
    {
        if ($studentIds === [] || $unitIds === []) {
            return collect();
        }

        return DB::table('registered_units as ru')
            ->join('student_semester_registrations as ssr', 'ssr.id', '=', 'ru.semester_registration_id')
            ->whereIn('ssr.student_id', $studentIds)
            ->whereIn('ru.unit_id', $unitIds)
            ->get(['ssr.student_id', 'ru.unit_id'])
            ->groupBy('student_id')
            ->map(fn (Collection $rows) => $rows->pluck('unit_id')->map(fn ($id) => (int) $id));
    }

    /**
     * @return list<string>
     */
    private function distinctValues(AcademicProgram $program, string $column): array
    {
        return Student::query()
            ->where('program_id', $program->id)
            ->whereNotNull($column)
            ->distinct()
            ->orderBy($column)
            ->pluck($column)
            ->all();
    }

    /**
     * @return list<int>
     */
    private function admissionYears(AcademicProgram $program): array
    {
        $years = Student::query()
            ->where('program_id', $program->id)
            ->pluck('date_of_admission')
            ->filter()
            ->map(fn ($date) => (int) substr((string) $date, 0, 4));

        $intakeYears = DB::table('applicants')
            ->whereIn('id', function ($query) use ($program) {
                $query->select('application_id')
                    ->from('students')
                    ->where('program_id', $program->id)
                    ->whereNotNull('application_id');
            })
            ->pluck('intake_year')
            ->filter()
            ->map(fn ($year) => (int) $year);

        return $years->merge($intakeYears)->filter()->unique()->sortDesc()->values()->all();
    }
}
