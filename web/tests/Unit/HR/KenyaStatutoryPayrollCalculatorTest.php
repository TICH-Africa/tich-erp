<?php

namespace Tests\Unit\HR;

use App\Services\KenyaStatutoryPayrollCalculator;
use Tests\TestCase;

class KenyaStatutoryPayrollCalculatorTest extends TestCase
{
    public function test_nssf_at_100k_matches_tier_caps(): void
    {
        $calc = app(KenyaStatutoryPayrollCalculator::class);
        $nssf = $calc->employeeNssf(100000);

        $this->assertSame(540.0, $nssf['tier1']);
        $this->assertSame(5460.0, $nssf['tier2']);
        $this->assertSame(6000.0, $nssf['amount']);
    }

    public function test_shif_uses_floor_below_threshold(): void
    {
        $calc = app(KenyaStatutoryPayrollCalculator::class);

        $this->assertSame(300.0, $calc->employeeShif(8000)['amount']);
        $this->assertTrue($calc->employeeShif(8000)['used_floor']);
    }

    public function test_shif_at_100k(): void
    {
        $calc = app(KenyaStatutoryPayrollCalculator::class);

        $this->assertSame(2750.0, $calc->employeeShif(100000)['amount']);
    }

    public function test_ahl_at_100k(): void
    {
        $calc = app(KenyaStatutoryPayrollCalculator::class);

        $this->assertSame(1500.0, $calc->employeeAhl(100000)['amount']);
    }
}