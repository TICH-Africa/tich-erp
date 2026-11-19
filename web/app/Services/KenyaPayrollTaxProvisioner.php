<?php

namespace App\Services;

use App\Models\PayrollBandDeductionRate;
use App\Models\PayrollDeductionType;
use App\Models\PayrollTaxBand;
use Illuminate\Support\Collection;

/**
 * Ensures PAYE bands and employee statutory deduction rows exist before HR enters rates.
 */
class KenyaPayrollTaxProvisioner
{
    public function ensureConfigured(): void
    {
        $bands = $this->ensurePayeBands();
        $this->ensurePersonalRelief();
        $statutoryTypes = $this->ensureStatutoryDeductionTypes();
        $this->ensureBandDeductionMatrix($bands, $statutoryTypes);
    }

    /**
     * @return Collection<int, PayrollTaxBand>
     */
    protected function ensurePayeBands(): Collection
    {
        $existing = PayrollTaxBand::query()->orderBy('display_order')->orderBy('min_amount')->get();

        if ($existing->isNotEmpty()) {
            return $existing;
        }

        $defs = config('tich-payroll.kenya_defaults.paye_bands', []);

        if ($defs === []) {
            return $existing;
        }

        $bands = collect();

        foreach ($defs as $def) {
            $bands->push(
                PayrollTaxBand::query()->create([
                    'label' => $def['label'],
                    'min_amount' => $def['min_amount'],
                    'max_amount' => $def['max_amount'],
                    'rate_percent' => $def['rate_percent'],
                    'display_order' => $def['display_order'],
                    'is_active' => 1,
                ])
            );
        }

        return $bands;
    }

    protected function ensurePersonalRelief(): void
    {
        $amount = (float) config('tich-payroll.kenya_defaults.personal_relief', 2400);

        PayrollDeductionType::query()->updateOrCreate(
            ['code' => 'personal_relief'],
            [
                'label' => 'Personal relief (PAYE)',
                'value_type' => 'global_fixed',
                'fixed_amount' => $amount,
                'employer_rate_percent' => null,
                'reduces_taxable' => 0,
                'display_order' => 1,
                'is_active' => 1,
            ]
        );
    }

    /**
     * @return Collection<int, PayrollDeductionType>
     */
    protected function ensureStatutoryDeductionTypes(): Collection
    {
        $defs = config('tich-payroll.kenya_defaults.statutory_deductions', []);
        $types = collect();

        foreach ($defs as $def) {
            $types->push(
                PayrollDeductionType::query()->updateOrCreate(
                    ['code' => $def['code']],
                    [
                        'label' => $def['label'],
                        'value_type' => $def['value_type'],
                        'fixed_amount' => null,
                        'employer_rate_percent' => null,
                        'reduces_taxable' => ! empty($def['reduces_taxable']),
                        'display_order' => $def['display_order'],
                        'is_active' => 1,
                    ]
                )
            );
        }

        return $types;
    }

    /**
     * @param  Collection<int, PayrollTaxBand>  $bands
     * @param  Collection<int, PayrollDeductionType>  $statutoryTypes
     */
    protected function ensureBandDeductionMatrix(Collection $bands, Collection $statutoryTypes): void
    {
        foreach ($bands as $band) {
            foreach ($statutoryTypes as $type) {
                if (! $type->isBandPercent()) {
                    continue;
                }

                PayrollBandDeductionRate::query()->firstOrCreate(
                    [
                        'payroll_tax_band_id' => $band->id,
                        'payroll_deduction_type_id' => $type->id,
                    ],
                    [
                        'rate_percent' => null,
                    ]
                );
            }
        }
    }

    /**
     * @return list<string>
     */
    public function lockedDeductionCodes(): array
    {
        return config('tich-payroll.kenya_defaults.locked_deduction_codes', [
            'personal_relief',
            'nssf',
            'sha',
            'ahl',
            'withholding_tax',
        ]);
    }
}