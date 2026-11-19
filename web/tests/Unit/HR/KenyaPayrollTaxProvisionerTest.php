<?php

namespace Tests\Unit\HR;

use App\Models\PayrollDeductionType;
use App\Models\PayrollTaxBand;
use App\Services\KenyaPayrollTaxProvisioner;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class KenyaPayrollTaxProvisionerTest extends TestCase
{
    use RefreshDatabase;

    public function test_provisioner_creates_paye_bands_and_statutory_items(): void
    {
        app(KenyaPayrollTaxProvisioner::class)->ensureConfigured();

        $this->assertSame(5, PayrollTaxBand::query()->count());

        $codes = PayrollDeductionType::query()->orderBy('display_order')->pluck('code')->all();

        $this->assertSame(
            ['personal_relief', 'nssf', 'sha', 'ahl'],
            $codes
        );

        $nssf = PayrollDeductionType::query()->where('code', 'nssf')->first();
        $sha = PayrollDeductionType::query()->where('code', 'sha')->first();
        $ahl = PayrollDeductionType::query()->where('code', 'ahl')->first();

        $this->assertTrue($nssf->reduces_taxable);
        $this->assertTrue($sha->reduces_taxable);
        $this->assertTrue($ahl->reduces_taxable);
        $this->assertNull($nssf->employer_rate_percent);
    }
}