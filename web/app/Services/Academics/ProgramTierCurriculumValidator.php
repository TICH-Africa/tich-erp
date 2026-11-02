<?php

namespace App\Services\Academics;

use App\Models\AcademicProgram;
use App\Models\CurriculumVersion;
use App\Models\ProgramUnit;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class ProgramTierCurriculumValidator
{
    public function __construct(protected ProgramTierPlannerService $planner) {}

    /**
     * @throws ValidationException
     */
    public function assertProgramTemplateReady(AcademicProgram $program): void
    {
        if (! $program->isTieredProgram()) {
            return;
        }

        $program->loadMissing('tiers');
        $maxSemester = $this->planner->maxSemester($program);
        if ($maxSemester < 1) {
            throw ValidationException::withMessages([
                'curriculum' => 'Configure tier durations and semester length before publishing curriculum.',
            ]);
        }

        $units = ProgramUnit::query()
            ->where('program_id', $program->id)
            ->where('is_active', 1)
            ->get(['unit_id', 'semester', 'program_tier']);

        $this->assertUnitsComplete($program, $maxSemester, $units);
    }

    /**
     * @throws ValidationException
     */
    public function assertIntakeVersionReady(CurriculumVersion $version): void
    {
        $version->loadMissing(['program.tiers', 'items']);
        $program = $version->program;
        if (! $program || ! $program->isTieredProgram()) {
            return;
        }

        $maxSemester = $this->planner->maxSemester($program);
        $items = $version->items->map(fn ($item) => (object) [
            'unit_id' => $item->unit_id,
            'semester' => $item->semester,
            'program_tier' => $this->planner->tierForSemester($program, (int) $item->semester),
        ]);

        $this->assertUnitsComplete($program, $maxSemester, $items);
    }

    /**
     * @param  Collection<int, object{unit_id: int, semester: int, program_tier: ?string}>  $units
     *
     * @throws ValidationException
     */
    private function assertUnitsComplete(AcademicProgram $program, int $maxSemester, Collection $units): void
    {
        $seenUnits = [];
        $semestersWithUnits = [];

        foreach ($units as $row) {
            $unitId = (int) $row->unit_id;
            if (isset($seenUnits[$unitId])) {
                throw ValidationException::withMessages([
                    'curriculum' => 'Each unit may only appear once on a tiered programme.',
                ]);
            }
            $seenUnits[$unitId] = true;

            $semester = (int) $row->semester;
            if ($semester < 1 || $semester > $maxSemester) {
                throw ValidationException::withMessages([
                    'curriculum' => "Unit mapping uses semester {$semester}, but the Advanced Diploma tier only spans semesters 1–{$maxSemester}.",
                ]);
            }

            $expectedTier = $this->planner->tierForSemester($program, $semester);
            $actualTier = $row->program_tier ?? $expectedTier;
            if ($expectedTier && $actualTier && $expectedTier !== $actualTier) {
                throw ValidationException::withMessages([
                    'curriculum' => "Semester {$semester} belongs to {$expectedTier}, not {$actualTier}.",
                ]);
            }

            $semestersWithUnits[$semester] = true;
        }

        for ($sem = 1; $sem <= $maxSemester; $sem++) {
            if (empty($semestersWithUnits[$sem])) {
                throw ValidationException::withMessages([
                    'curriculum' => "Publish blocked: assign at least one unit to semester {$sem} for every tier before publishing.",
                ]);
            }
        }
    }
}
