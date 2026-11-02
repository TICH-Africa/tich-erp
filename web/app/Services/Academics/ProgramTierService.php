<?php

namespace App\Services\Academics;

use App\Models\AcademicProgram;
use App\Models\ProgramTier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class ProgramTierService
{
    public function __construct(protected ProgramTierPlannerService $planner) {}

    /**
     * @return array<string, array<int, string|\Illuminate\Contracts\Validation\ValidationRule>>
     */
    public function validationRules(bool $isTiered): array
    {
        if (! $isTiered) {
            return [];
        }

        $keys = config('tich-program-tiers.tier_keys', []);

        return [
            'tier_semester_months' => ['required', 'integer', 'min:1', 'max:12'],
            'tiers' => ['required', 'array', 'size:'.count($keys)],
            'tiers.*.tier_key' => ['required', 'string', 'in:'.implode(',', $keys)],
            'tiers.*.cumulative_months' => ['required', 'integer', 'min:1', 'max:120'],
            'tiers.*.learning_outcomes' => ['nullable', 'string', 'max:50000'],
            'tiers.*.marketing_summary' => ['nullable', 'string', 'max:5000'],
        ];
    }

    /**
     * @param  array<string, mixed>  $validated
     * @return array<string, mixed>
     */
    public function tierPayloadFromValidated(array $validated, bool $isTiered): array
    {
        if (! $isTiered) {
            return [
                'is_tiered' => false,
                'tier_semester_months' => null,
                'tiers' => [],
            ];
        }

        $keys = config('tich-program-tiers.tier_keys', []);

        $ordered = [];
        foreach ($keys as $index => $key) {
            $row = collect($validated['tiers'])->firstWhere('tier_key', $key);
            if (! $row) {
                throw new InvalidArgumentException('Each programme tier (Certificate, Diploma, Advanced Diploma) must be configured.');
            }
            $ordered[] = [
                'tier_key' => $key,
                'tier_order' => $index + 1,
                'cumulative_months' => (int) $row['cumulative_months'],
                'learning_outcomes' => $row['learning_outcomes'] ?? null,
                'marketing_summary' => $row['marketing_summary'] ?? null,
            ];
        }

        return [
            'is_tiered' => true,
            'tier_semester_months' => (int) $validated['tier_semester_months'],
            'tiers' => $ordered,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function validateTierPayload(Request $request, bool $isTiered): array
    {
        if (! $isTiered) {
            return $this->tierPayloadFromValidated([], false);
        }

        $validated = $request->validate($this->validationRules(true));

        return $this->tierPayloadFromValidated($validated, true);
    }

    public function syncTiers(AcademicProgram $program, array $tierPayload): void
    {
        if (! ($tierPayload['is_tiered'] ?? false)) {
            $program->tiers()->delete();

            return;
        }

        if ($program->usesBlocks()) {
            throw new InvalidArgumentException('Tiered programmes are only supported for semester-based curriculum formats.');
        }

        $program->fill([
            'tier_semester_months' => $tierPayload['tier_semester_months'],
        ]);

        $this->planner->buildPlan($program, $tierPayload['tiers']);

        DB::transaction(function () use ($program, $tierPayload) {
            $program->tiers()->delete();
            foreach ($tierPayload['tiers'] as $row) {
                ProgramTier::create([
                    'program_id' => $program->id,
                    ...$row,
                ]);
            }

            $top = collect($tierPayload['tiers'])->sortByDesc('tier_order')->first();
            if ($top) {
                $program->duration_months = (int) $top['cumulative_months'];
                $program->save();
            }
        });
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function planForProgram(AcademicProgram $program): array
    {
        if (! $program->is_tiered) {
            return [];
        }

        $program->loadMissing('tiers');

        return $this->planner->buildPlan(
            $program,
            $program->tiers->map(fn (ProgramTier $t) => [
                'tier_key' => $t->tier_key,
                'tier_order' => $t->tier_order,
                'cumulative_months' => $t->cumulative_months,
            ])->all(),
        );
    }
}
