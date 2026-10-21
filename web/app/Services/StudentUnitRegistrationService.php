<?php

namespace App\Services;

use App\Models\AcademicProgram;
use App\Models\CurriculumVersion;
use App\Models\CurriculumVersionUnit;
use App\Models\NursingBlock;
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
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function desk(Request $request, AcademicProgram $program, CurriculumVersion $intake): array
    {
        $mappings = $this->versions->mappedUnits($intake);
        $periodOptions = $this->periodOptions($program);
        $selectedKey = $this->resolvePeriodKey($request, $periodOptions);
        $selected = $periodOptions->get($selectedKey);

        $units = $selected
            ? $this->mappingsForPeriod($mappings, $selected['semester'], $selected['block_id'])
            : collect();

        $students = $this->candidates($request, $program, $intake);

        return [
            'periodOptions' => $periodOptions,
            'selectedPeriodKey' => $selectedKey,
            'selectedPeriod' => $selected,
            'semesterId' => $selected ? $this->schedule->resolveSemesterId($intake, $selected['semester']) : null,
            'periodUnits' => $units,
            'lecturersByUnit' => $this->lecturersByUnit($units, $intake),
            'students' => $students,
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

                    DB::table('registered_units')->insert([
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
     */
    private function resolvePeriodKey(Request $request, Collection $periodOptions): string
    {
        $requested = $request->string('registration_period')->toString();

        if ($requested !== '' && $periodOptions->has($requested)) {
            return $requested;
        }

        return $periodOptions->keys()->first() ?? 'sem-1';
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
