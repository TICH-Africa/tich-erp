<?php

namespace App\Services\Academics;

use App\Models\AcademicProgram;
use App\Models\FeeStructure;
use App\Models\Student;
use InvalidArgumentException;

class ProgramTierFeeService
{
    /**
     * @return list<string>
     */
    public function tierKeysInOrder(): array
    {
        return config('tich-program-tiers.tier_keys', []);
    }

    public function tierOrderIndex(string $tierKey): int
    {
        $keys = $this->tierKeysInOrder();
        $index = array_search($tierKey, $keys, true);

        return $index === false ? -1 : (int) $index;
    }

    public function feeForTier(AcademicProgram $program, string $tierKey, ?int $academicYearId = null): ?FeeStructure
    {
        $query = FeeStructure::query()
            ->where('program_id', $program->id)
            ->where('program_tier', $tierKey)
            ->where('is_active', 1)
            ->orderByDesc('is_approved')
            ->orderByDesc('effective_from');

        if ($academicYearId) {
            $query->where('academic_year_id', $academicYearId);
        }

        return $query->first();
    }

    /**
     * Amount due when upgrading from one paid tier to another (no skipping).
     */
    public function topUpAmount(AcademicProgram $program, string $fromTierKey, string $toTierKey): float
    {
        $keys = $this->tierKeysInOrder();
        $from = array_search($fromTierKey, $keys, true);
        $to = array_search($toTierKey, $keys, true);

        if ($from === false || $to === false || $to !== $from + 1) {
            throw new InvalidArgumentException('Upgrades must be to the next tier only (Certificate → Diploma → Advanced Diploma).');
        }

        $fromFee = (float) ($this->feeForTier($program, $fromTierKey)?->total_semester_fee ?? 0);
        $toFee = (float) ($this->feeForTier($program, $toTierKey)?->total_semester_fee ?? 0);

        if ($toFee <= 0) {
            throw new InvalidArgumentException('Fee structure for the target tier is not configured.');
        }

        return max(0, round($toFee - $fromFee, 2));
    }

    /**
     * @return list<string>
     */
    public function paidTierKeys(Student $student): array
    {
        $paid = $student->paid_program_tiers ?? [];

        return is_array($paid) ? array_values(array_filter($paid, 'is_string')) : [];
    }

    public function highestPaidTierKey(Student $student): ?string
    {
        $paid = $this->paidTierKeys($student);
        if ($paid === []) {
            return null;
        }

        $best = null;
        $bestIndex = -1;
        foreach ($paid as $key) {
            $idx = $this->tierOrderIndex($key);
            if ($idx > $bestIndex) {
                $bestIndex = $idx;
                $best = $key;
            }
        }

        return $best;
    }

    public function canEnrollUnitsForSemester(Student $student, AcademicProgram $program, int $semester): bool
    {
        if (! $program->isTieredProgram()) {
            return true;
        }

        $student->loadMissing('program');
        $currentTier = $student->program_tier;
        if (! $currentTier) {
            return false;
        }

        $planner = app(ProgramTierPlannerService::class);
        $range = $planner->semesterRangeForTier($program, $currentTier);
        if (! $range) {
            return false;
        }

        return $semester >= $range['semester_from'] && $semester <= $range['semester_to'];
    }
}
