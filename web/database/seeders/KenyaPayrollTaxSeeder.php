<?php

namespace Database\Seeders;

use App\Models\PayrollBandDeductionRate;
use App\Models\PayrollDeductionType;
use App\Models\PayrollTaxBand;
use Illuminate\Database\Seeder;

class KenyaPayrollTaxSeeder extends Seeder
{
    public function run(): void
    {
        if (PayrollTaxBand::query()->exists()) {
            return;
        }

        $bands = [
            ['label' => 'First KES 24,000', 'min_amount' => 0, 'max_amount' => 24000, 'rate_percent' => 10, 'display_order' => 1],
            ['label' => 'Next KES 8,333', 'min_amount' => 24000, 'max_amount' => 32333, 'rate_percent' => 25, 'display_order' => 2],
            ['label' => 'Next KES 467,667', 'min_amount' => 32333, 'max_amount' => 500000, 'rate_percent' => 30, 'display_order' => 3],
            ['label' => 'Next KES 300,000', 'min_amount' => 500000, 'max_amount' => 800000, 'rate_percent' => 32.5, 'display_order' => 4],
            ['label' => 'Above KES 800,000', 'min_amount' => 800000, 'max_amount' => null, 'rate_percent' => 35, 'display_order' => 5],
        ];

        $bandModels = [];

        foreach ($bands as $band) {
            $bandModels[] = PayrollTaxBand::query()->create($band + ['is_active' => 1]);
        }

        $personalRelief = PayrollDeductionType::query()->create([
            'code' => 'personal_relief',
            'label' => 'Personal relief (PAYE)',
            'value_type' => 'global_fixed',
            'fixed_amount' => 2400,
            'display_order' => 1,
            'is_active' => 1,
        ]);

        $deductionColumns = [
            ['code' => 'nssf', 'label' => 'NSSF', 'employer_rate_percent' => 6, 'reduces_taxable' => 1, 'display_order' => 2, 'rate' => 6],
            ['code' => 'sha', 'label' => 'SHA / SHIF', 'display_order' => 3, 'rate' => 2.75],
            ['code' => 'ahl', 'label' => 'Affordable Housing Levy (AHL)', 'employer_rate_percent' => 1.5, 'display_order' => 4, 'rate' => 1.5],
        ];

        foreach ($deductionColumns as $column) {
            $type = PayrollDeductionType::query()->create([
                'code' => $column['code'],
                'label' => $column['label'],
                'value_type' => 'band_percent',
                'employer_rate_percent' => $column['employer_rate_percent'] ?? null,
                'reduces_taxable' => $column['reduces_taxable'] ?? 0,
                'display_order' => $column['display_order'],
                'is_active' => 1,
            ]);

            foreach ($bandModels as $band) {
                PayrollBandDeductionRate::query()->create([
                    'payroll_tax_band_id' => $band->id,
                    'payroll_deduction_type_id' => $type->id,
                    'rate_percent' => $column['rate'],
                ]);
            }
        }

        unset($personalRelief);
    }
}
