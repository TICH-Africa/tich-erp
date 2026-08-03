<?php

namespace Database\Seeders;

use App\Models\PayrollStatutoryRate;
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

        foreach ($bands as $band) {
            PayrollTaxBand::query()->create($band + ['is_active' => 1]);
        }

        $rates = [
            [
                'code' => 'personal_relief',
                'label' => 'Personal relief (PAYE)',
                'fixed_amount' => 2400,
                'applies_to' => 'taxable',
                'notes' => 'Monthly personal relief deducted from PAYE',
                'display_order' => 1,
            ],
            [
                'code' => 'nssf_tier1',
                'label' => 'NSSF Tier I',
                'rate_percent' => 6,
                'employer_rate_percent' => 6,
                'ceiling_amount' => 7000,
                'applies_to' => 'pensionable',
                'notes' => '6% on first KES 7,000 pensionable earnings',
                'display_order' => 2,
            ],
            [
                'code' => 'nssf_tier2',
                'label' => 'NSSF Tier II',
                'rate_percent' => 6,
                'employer_rate_percent' => 6,
                'floor_amount' => 7000,
                'ceiling_amount' => 36000,
                'applies_to' => 'pensionable',
                'notes' => '6% on pensionable earnings between KES 7,001 and 36,000',
                'display_order' => 3,
            ],
            [
                'code' => 'sha',
                'label' => 'SHA / SHIF',
                'rate_percent' => 2.75,
                'applies_to' => 'gross',
                'notes' => 'Social Health Authority contribution on gross salary',
                'display_order' => 4,
            ],
            [
                'code' => 'ahl',
                'label' => 'Affordable Housing Levy (AHL)',
                'rate_percent' => 1.5,
                'employer_rate_percent' => 1.5,
                'applies_to' => 'gross',
                'notes' => 'Housing levy on gross salary (employee and employer)',
                'display_order' => 5,
            ],
            [
                'code' => 'pension_employee',
                'label' => 'Pension (optional)',
                'rate_percent' => 0,
                'employer_rate_percent' => 0,
                'applies_to' => 'gross',
                'notes' => 'Set rate if staff pension scheme applies; reduces taxable pay',
                'display_order' => 6,
                'is_active' => 0,
            ],
        ];

        foreach ($rates as $rate) {
            PayrollStatutoryRate::query()->create($rate + ['is_active' => $rate['is_active'] ?? 1]);
        }
    }
}
