<?php

namespace App\Services\Academics;

use App\Models\AcademicProgram;
use InvalidArgumentException;

/**
 * Maps cumulative tier durations to semester indices for semester-format programmes.
 */
class ProgramTierPlannerService
{
    /**
     * @param  list<array{ tier_key: string, tier_order: int, cumulative_months: int }>  $tiers
     * @return list<array{
     *     tier_key: string,
     *     tier_order: int,
     *     cumulative_months: int,
     *     incremental_months: int,
     *     semester_from: int,
     *     semester_to: int,
     *     label: string
     * }>
     */
    public function buildPlan(AcademicProgram $program, array $tiers): array
    {
        $semesterMonths = (int) ($program->tier_semester_months ?? 0);
        if ($semesterMonths <= 0) {
            throw new InvalidArgumentException('Set semester length (months) for tiered programmes.');
        }

        usort($tiers, fn ($a, $b) => ($a['tier_order'] ?? 0) <=> ($b['tier_order'] ?? 0));

        $plan = [];
        $previousCumulative = 0;
        $semesterCursor = 1;

        foreach ($tiers as $tier) {
            $cumulative = (int) $tier['cumulative_months'];
            if ($cumulative <= $previousCumulative) {
                throw new InvalidArgumentException('Each tier must have a longer cumulative duration than the previous tier.');
            }

            $incremental = $cumulative - $previousCumulative;
            $semestersInTier = (int) max(1, (int) ceil($incremental / $semesterMonths));
            $semesterFrom = $semesterCursor;
            $semesterTo = $semesterCursor + $semestersInTier - 1;
            $semesterCursor = $semesterTo + 1;

            $key = (string) $tier['tier_key'];
            $plan[] = [
                'tier_key' => $key,
                'tier_order' => (int) $tier['tier_order'],
                'cumulative_months' => $cumulative,
                'incremental_months' => $incremental,
                'semester_from' => $semesterFrom,
                'semester_to' => $semesterTo,
                'label' => config('tich-program-tiers.tier_labels.'.$key, $key),
            ];

            $previousCumulative = $cumulative;
        }

        return $plan;
    }

    /**
     * @return array{semester_from: int, semester_to: int}|null
     */
    public function semesterRangeForTier(AcademicProgram $program, string $tierKey): ?array
    {
        if (! $program->is_tiered) {
            return null;
        }

        $tiers = $program->relationLoaded('tiers')
            ? $program->tiers
            : $program->tiers()->orderBy('tier_order')->get();

        if ($tiers->isEmpty()) {
            return null;
        }

        $plan = $this->buildPlan($program, $tiers->map(fn ($t) => [
            'tier_key' => $t->tier_key,
            'tier_order' => $t->tier_order,
            'cumulative_months' => $t->cumulative_months,
        ])->all());

        foreach ($plan as $row) {
            if ($row['tier_key'] === $tierKey) {
                return [
                    'semester_from' => $row['semester_from'],
                    'semester_to' => $row['semester_to'],
                ];
            }
        }

        return null;
    }

    public function tierForSemester(AcademicProgram $program, int $semester): ?string
    {
        if (! $program->is_tiered) {
            return null;
        }

        $tiers = $program->tiers()->orderBy('tier_order')->get();
        if ($tiers->isEmpty()) {
            return null;
        }

        $plan = $this->buildPlan($program, $tiers->map(fn ($t) => [
            'tier_key' => $t->tier_key,
            'tier_order' => $t->tier_order,
            'cumulative_months' => $t->cumulative_months,
        ])->all());

        foreach ($plan as $row) {
            if ($semester >= $row['semester_from'] && $semester <= $row['semester_to']) {
                return $row['tier_key'];
            }
        }

        return null;
    }

    public function maxSemester(AcademicProgram $program): int
    {
        if (! $program->is_tiered) {
            return $program->scheduledPeriods();
        }

        $top = $program->tiers()->orderByDesc('tier_order')->first();
        if (! $top) {
            return 0;
        }

        $range = $this->semesterRangeForTier($program, $top->tier_key);

        return $range['semester_to'] ?? 0;
    }
}
