<?php

namespace App\Services;

use App\Models\PayrollKenyaStatutorySetting;
use App\Models\PayrollStatutoryBracket;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;

/**
 * Resolves Kenya employee statutory rules (bracket tables, then legacy settings row, then config).
 */
class KenyaPayrollStatutoryConfig
{
    /**
     * @return Collection<int, PayrollStatutoryBracket>
     */
    public function activeBrackets(string $scheme): Collection
    {
        if (! Schema::hasTable('payroll_statutory_brackets')) {
            return collect();
        }

        return PayrollStatutoryBracket::query()
            ->where('scheme', $scheme)
            ->where('is_active', true)
            ->orderBy('display_order')
            ->orderBy('min_amount')
            ->get();
    }

    public function usesBracketTables(): bool
    {
        if (! Schema::hasTable('payroll_statutory_brackets')) {
            return false;
        }

        return PayrollStatutoryBracket::query()->exists();
    }

    /**
     * @return array{nssf: array<string, mixed>, shif: array<string, mixed>, ahl: array<string, mixed>}
     */
    public function resolve(): array
    {
        if ($this->usesBracketTables()) {
            return $this->legacyShapeFromBrackets();
        }

        if (Schema::hasTable('payroll_kenya_statutory_settings')) {
            return PayrollKenyaStatutorySetting::current()->toCalculatorConfig();
        }

        return config('tich-payroll.kenya_statutory', []);
    }

    /**
     * Flat map for older callers/tests — calculation prefers [activeBrackets].
     *
     * @return array{nssf: array<string, mixed>, shif: array<string, mixed>, ahl: array<string, mixed>}
     */
    protected function legacyShapeFromBrackets(): array
    {
        $nssf = $this->activeBrackets(PayrollStatutoryBracket::SCHEME_NSSF);
        $shif = $this->activeBrackets(PayrollStatutoryBracket::SCHEME_SHIF);
        $ahl = $this->activeBrackets(PayrollStatutoryBracket::SCHEME_AHL);

        $tier1 = $nssf->first();
        $tier2 = $nssf->skip(1)->first();
        $shifFixed = $shif->firstWhere('calculation', 'fixed') ?? $shif->first();
        $shifRate = $shif->firstWhere('calculation', 'percent_floor') ?? $shif->skip(1)->first();
        $ahlRow = $ahl->first();

        return [
            'nssf' => [
                'rate_percent' => (float) ($tier1?->rate_percent ?? 6),
                'tier1_ceiling' => (float) ($tier1?->max_amount ?? 9000),
                'tier1_max_employee' => (float) ($tier1?->cap_amount ?? 540),
                'tier2_ceiling' => (float) ($tier2?->max_amount ?? 108000),
                'tier2_max_employee' => (float) ($tier2?->cap_amount ?? 5940),
                'max_employee' => (float) (($tier1?->cap_amount ?? 540) + ($tier2?->cap_amount ?? 5940)),
            ],
            'shif' => [
                'rate_percent' => (float) ($shifRate?->rate_percent ?? 2.75),
                'monthly_floor' => (float) ($shifFixed?->fixed_amount ?? $shifRate?->fixed_amount ?? 300),
                'floor_applies_up_to_gross' => (float) ($shifFixed?->max_amount ?? 10909),
            ],
            'ahl' => [
                'rate_percent' => (float) ($ahlRow?->rate_percent ?? 1.5),
            ],
        ];
    }
}