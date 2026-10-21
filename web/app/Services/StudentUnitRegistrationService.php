<?php

namespace App\Services;

use App\Models\AcademicProgram;
use App\Models\AcademicYear;
use App\Models\CurriculumVersion;
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
        $students = $this->candidates($request, $program, $intake);
        $selectedKey = $this->resolvePeriodKey($request, $program, $periodOptions, $students);
        $selected = $periodOptions->get($selectedKey);

        $units = $selected
            ? $this->mappingsForPeriod($mappings, $selected['semester'], $selected['block_id'])
            : collect();

        return [
            'periodOptions' => $periodOptions,
            'selectedPeriodKey' => $selectedKey,
            'selectedPeriod' => $selected,
            'semesterId' => $selected ? $this->schedule->resolveSemesterId($intake, $selected['semester']) : null,
            'periodUnits' => $units,
            'lecturersByUnit' => $this->lecturersByUnit($units, $intake),
            'students' => $students,
            'yearsByStudent' => $students->mapWithKeys(fn (Student $student) => [
                $student->id => $this->yearOfStudy($student, $program, $intake),
            ]),
            'pathways' => $this->distinctValues($program, 'entry_pathway'),
            'admissionYears' => $this->admissionYears($program),
            'pathwayFilter' => $request->string('entry_pathway')->toString() ?: null,
            'yearFilter' => $request->integer('admission_year') ?: null,
            'search' => trim($request->string('student_search')->toString()),
            'registeredUnitIds' => $this->registeredUnitIds(
                $students->pluck('id')->all(),
                $units->pluck('unit_id')->filter()->all(),
            ),
        ];
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
     * running gives the year; the year gives the period whose units they are due for.
     * An officer can pin the year on the student record (students.current_year) and that
     * wins, because re-admits, repeats and transfers do not follow the arithmetic.
     *
     * @return array{year: int, period: int, period_key: string, period_label: string, basis: string, overridden: bool}|null
     */
    public function yearOfStudy(Student $student, AcademicProgram $program, ?CurriculumVersion $intake = null): ?array
    {
        if ($program->usesBlocks()) {
            return null;
        }

        $terms = max(1, (int) $program->termsPerYear());
        $periods = max(1, (int) $program->totalTeachingPeriods());
        $years = max(1, intdiv($periods, $terms));

        $pinned = $student->current_year ? (int) $student->current_year : null;

        if ($pinned !== null && $pinned >= 1 && $pinned <= $years) {
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
        $last = min($first + $terms - 1, $periods);
        $period = $first;

        // A student already sitting a semester inside their own year stays on it.
        if ($student->current_semester_id) {
            $sitting = Semester::query()->whereKey($student->current_semester_id)->value('semester_number');

            if ($sitting && (int) $sitting >= $first && (int) $sitting <= $last) {
                $period = (int) $sitting;
            }
        }

        return [
            'year' => $year,
            'period' => $period,
            'period_key' => 'sem-'.$period,
            'period_label' => $program->periodLabel($period),
            'basis' => $basis,
            'overridden' => $pinned !== null,
        ];
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

            $semesterId = $this->schedule->resolveSemesterId($intake, $year['period']);

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
            'maxYear' => max(1, intdiv(max(1, (int) $program->totalTeachingPeriods()), max(1, (int) $program->termsPerYear()))),
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
     * Pin the year of study on the student record, overriding the admission arithmetic.
     */
    public function pinYear(User $officer, Student $student, AcademicProgram $program, ?int $year, string $reason): int
    {
        $years = max(1, intdiv(max(1, (int) $program->totalTeachingPeriods()), max(1, (int) $program->termsPerYear())));

        if ($year === null || $year < 1 || $year > $years) {
            throw ValidationException::withMessages([
                'current_year' => 'Year of study must be between 1 and '.$years.' for '.($program->program_code ?: 'this programme').'.',
            ]);
        }

        $before = $student->current_year;

        $student->forceFill(['current_year' => $year])->save();

        $this->audits->log(
            action: 'academics.student_year_of_study_pinned',
            entityType: 'students',
            entityId: $student->id,
            oldValue: ['current_year' => $before],
            newValue: ['current_year' => $year],
            reason: $reason,
            userId: $officer->id,
        );

        return $year;
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
        $numbers = range(1, max(1, $program->totalTeachingPeriods()));

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
     * @param  Collection<string, array{semester: int, block_id: int|null}>  $periodOptions
     * @param  Collection<int, Student>  $students
     */
    private function resolvePeriodKey(Request $request, AcademicProgram $program, Collection $periodOptions, Collection $students): string
    {
        $requested = $request->string('registration_period')->toString();

        if ($requested !== '' && $periodOptions->has($requested)) {
            return $requested;
        }

        return $this->cohortPeriodKey($program, $periodOptions, $students);
    }

    /**
     * Open the desk on the semester the listed students are actually sitting, so the
     * units they are due for are the ones already on screen.
     *
     * @param  Collection<string, array{semester: int, block_id: int|null}>  $periodOptions
     * @param  Collection<int, Student>  $students
     */
    private function cohortPeriodKey(AcademicProgram $program, Collection $periodOptions, Collection $students): string
    {
        $fallback = $periodOptions->keys()->first() ?? 'sem-1';

        if ($students->isEmpty()) {
            return $fallback;
        }

        $semesterNumbers = Semester::query()
            ->whereIn('id', $students->pluck('current_semester_id')->filter()->unique()->all())
            ->pluck('semester_number', 'id');

        $keys = $students
            ->map(fn (Student $student) => $this->studentPeriodKey($program, $student, $semesterNumbers))
            ->filter()
            ->values();

        if ($keys->isEmpty()) {
            return $fallback;
        }

        $key = $keys->countBy()->sortDesc()->keys()->first();

        return $periodOptions->has($key) ? $key : $fallback;
    }

    /**
     * @param  Collection<int, int>  $semesterNumbers
     */
    private function studentPeriodKey(AcademicProgram $program, Student $student, Collection $semesterNumbers): ?string
    {
        if ($program->usesBlocks()) {
            return $student->current_nursing_block_id ? 'block-'.$student->current_nursing_block_id : null;
        }

        $period = $student->current_semester_id ? $semesterNumbers[$student->current_semester_id] ?? null : null;

        if (! $period) {
            // Nothing is recorded yet, so the admission year decides where they sit.
            $year = $this->yearOfStudy($student, $program);

            return $year ? $year['period_key'] : null;
        }

        return 'sem-'.(int) $period;
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
