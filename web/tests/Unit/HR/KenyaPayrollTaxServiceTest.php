<?php

namespace Tests\Unit\HR;

use App\Models\PayrollStatutoryRate;
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

        PayrollTaxBand::query()->create([
            'label' => 'First KES 24,000',
            'min_amount' => 0,
            'max_amount' => 24000,
            'rate_percent' => 10,
            'display_order' => 1,
            'is_active' => 1,
        ]);

        PayrollTaxBand::query()->create([
            'label' => 'Next KES 41,000',
            'min_amount' => 24000,
            'max_amount' => 65000,
            'rate_percent' => 25,
            'display_order' => 2,
            'is_active' => 1,
        ]);

        PayrollStatutoryRate::query()->create([
            'code' => 'personal_relief',
            'label' => 'Personal relief',
            'fixed_amount' => 2400,
            'display_order' => 1,
            'is_active' => 1,
        ]);

        PayrollStatutoryRate::query()->create([
            'code' => 'nssf_tier1',
            'label' => 'NSSF Tier I',
            'rate_percent' => 6,
            'employer_rate_percent' => 6,
            'ceiling_amount' => 7000,
            'display_order' => 2,
            'is_active' => 1,
        ]);

        PayrollStatutoryRate::query()->create([
            'code' => 'sha',
            'label' => 'SHA',
            'rate_percent' => 2.75,
            'display_order' => 3,
            'is_active' => 1,
        ]);

        PayrollStatutoryRate::query()->create([
            'code' => 'ahl',
            'label' => 'AHL',
            'rate_percent' => 1.5,
            'employer_rate_percent' => 1.5,
            'display_order' => 4,
            'is_active' => 1,
        ]);
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
}
