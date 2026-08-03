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

        $bandOne = PayrollTaxBand::query()->create([
            'label' => 'First KES 24,000',
            'min_amount' => 0,
            'max_amount' => 24000,
            'rate_percent' => 10,
            'display_order' => 1,
            'is_active' => 1,
        ]);

        $bandTwo = PayrollTaxBand::query()->create([
            'label' => 'Next KES 41,000',
            'min_amount' => 24000,
            'max_amount' => 65000,
            'rate_percent' => 25,
            'display_order' => 2,
            'is_active' => 1,
        ]);

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
            'label' => 'SHA',
            'value_type' => 'band_percent',
            'display_order' => 3,
            'is_active' => 1,
        ]);

        $ahl = PayrollDeductionType::query()->create([
            'code' => 'ahl',
            'label' => 'AHL',
            'value_type' => 'band_percent',
            'employer_rate_percent' => 1.5,
            'display_order' => 4,
            'is_active' => 1,
        ]);

        foreach ([$bandOne, $bandTwo] as $band) {
            foreach ([[$nssf, 6], [$sha, 2.75], [$ahl, 1.5]] as [$type, $rate]) {
                PayrollBandDeductionRate::query()->create([
                    'payroll_tax_band_id' => $band->id,
                    'payroll_deduction_type_id' => $type->id,
                    'rate_percent' => $rate,
                ]);
            }
        }
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
