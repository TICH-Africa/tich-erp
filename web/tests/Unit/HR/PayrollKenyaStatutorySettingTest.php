<?php

namespace Tests\Unit\HR;

use App\Models\PayrollKenyaStatutorySetting;
use App\Services\KenyaStatutoryPayrollCalculator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PayrollKenyaStatutorySettingTest extends TestCase
{
    use RefreshDatabase;

    public function test_custom_shif_rate_from_database_applies(): void
    {
        $row = PayrollKenyaStatutorySetting::current();
        $row->update(['shif_rate_percent' => 3]);

        $amount = app(KenyaStatutoryPayrollCalculator::class)->employeeShif(100000)['amount'];

        $this->assertSame(3000.0, $amount);
    }
}