<?php

namespace Tests\Unit\HR;

use App\Models\PayrollBandDeductionRate;
use App\Models\PayrollDeductionType;
use App\Models\PayrollTaxBand;
use App\Services\KenyaPayrollTaxService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class KenyaPayrollTaxServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        PayrollBandDeductionRate::query()->delete();
        PayrollDeductionType::query()->delete();
        PayrollTaxBand::query()->delete();

        $bands = [
            ['label' => 'First KES 24,000', 'min_amount' => 0, 'max_amount' => 24000, 'rate_percent' => 10, 'display_order' => 1],
            ['label' => 'KES 24,001 – 32,333', 'min_amount' => 24000, 'max_amount' => 32333, 'rate_percent' => 25, 'display_order' => 2],
            ['label' => 'KES 32,334 – 500,000', 'min_amount' => 32333, 'max_amount' => 500000, 'rate_percent' => 30, 'display_order' => 3],
            ['label' => 'KES 500,001 – 800,000', 'min_amount' => 500000, 'max_amount' => 800000, 'rate_percent' => 32.5, 'display_order' => 4],
            ['label' => 'Above KES 800,000', 'min_amount' => 800000, 'max_amount' => null, 'rate_percent' => 35, 'display_order' => 5],
        ];

        $bandModels = [];

        foreach ($bands as $band) {
            $bandModels[] = PayrollTaxBand::query()->create($band + ['is_active' => 1]);
        }

        PayrollDeductionType::query()->create([
            'code' => 'personal_relief',
            'label' => 'Personal relief',
            'value_type' => 'global_fixed',
            'fixed_amount' => 2400,
            'display_order' => 1,
            'is_active' => 1,
        ]);

        $nssf = PayrollDeductionType::query()->create([
            'code' => 'nssf',
            'label' => 'NSSF',
            'value_type' => 'band_percent',
            'employer_rate_percent' => 6,
            'reduces_taxable' => 1,
            'display_order' => 2,
            'is_active' => 1,
        ]);

        $sha = PayrollDeductionType::query()->create([
            'code' => 'sha',
            'label' => 'SHA / SHIF',
            'value_type' => 'band_percent',
            'reduces_taxable' => 1,
            'display_order' => 3,
            'is_active' => 1,
        ]);

        $ahl = PayrollDeductionType::query()->create([
            'code' => 'ahl',
            'label' => 'Affordable Housing Levy (AHL)',
            'value_type' => 'band_percent',
            'reduces_taxable' => 1,
            'display_order' => 4,
            'is_active' => 1,
        ]);

        foreach ($bandModels as $band) {
            foreach ([$nssf, $sha, $ahl] as $type) {
                PayrollBandDeductionRate::query()->create([
                    'payroll_tax_band_id' => $band->id,
                    'payroll_deduction_type_id' => $type->id,
                    'rate_percent' => null,
                ]);
            }
        }
    }

    public function test_calculate_matches_kra_worked_example_at_100k_gross(): void
    {
        $result = app(KenyaPayrollTaxService::class)->calculateFromGross(100000);

        $this->assertSame(100000.0, $result['gross_salary']);
        $this->assertSame(89750.0, $result['taxable_income']);
        $this->assertEqualsWithDelta(21708.35, $result['paye_before_relief'], 0.02);
        $this->assertEqualsWithDelta(19308.35, $result['paye'], 0.02);
        $this->assertEqualsWithDelta(70441.65, $result['net_salary'], 0.02);

        $byCode = collect($result['deductions'])->keyBy('code');

        $this->assertEqualsWithDelta(6000.0, $byCode->get('nssf')['amount'], 0.01);
        $this->assertEqualsWithDelta(2750.0, $byCode->get('sha')['amount'], 0.01);
        $this->assertEqualsWithDelta(1500.0, $byCode->get('ahl')['amount'], 0.01);
    }

    public function test_calculate_from_gross_returns_net_and_deductions(): void
    {
        $result = app(KenyaPayrollTaxService::class)->calculateFromGross(65000);

        $this->assertSame(65000.0, $result['gross_salary']);
        $this->assertGreaterThan(0, $result['paye']);
        $this->assertLessThan($result['gross_salary'], $result['net_salary']);
        $this->assertNotEmpty($result['deductions']);
    }

    public function test_calculate_from_net_resolves_matching_gross(): void
    {
        $service = app(KenyaPayrollTaxService::class);
        $forward = $service->calculateFromGross(65000);
        $reverse = $service->calculateFromNet($forward['net_salary']);

        $this->assertEqualsWithDelta($forward['net_salary'], $reverse['net_salary'], 0.05);
        $this->assertGreaterThan(60000, $reverse['gross_salary']);
    }

    public function test_withholding_payroll_deducts_configured_rate_only(): void
    {
        PayrollDeductionType::query()->updateOrCreate(
            ['code' => 'withholding_tax'],
            [
                'label' => 'Withholding tax (WHT)',
                'value_type' => 'withholding_percent',
                'fixed_amount' => 5,
                'display_order' => 99,
                'is_active' => 1,
            ]
        );

        $result = app(KenyaPayrollTaxService::class)->calculateWithholdingFromGross(100000, [
            'employee_name' => 'Jane Consultant',
            'employee_number' => 'CON-001',
        ]);

        $this->assertSame('withholding', $result['payroll_scheme']);
        $this->assertSame(5000.0, $result['withholding_tax']);
        $this->assertSame(95000.0, $result['net_salary']);
        $this->assertSame(5000.0, $result['total_deductions']);
        $this->assertSame(0.0, $result['paye']);
        $this->assertCount(1, $result['deductions']);
        $this->assertSame('withholding_tax', $result['deductions'][0]['code']);
    }
}
