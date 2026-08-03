<?php

use App\Models\PayrollDeductionType;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE payroll_deduction_types MODIFY value_type ENUM('band_percent', 'global_fixed', 'withholding_percent') NOT NULL DEFAULT 'band_percent'");

        PayrollDeductionType::query()->updateOrCreate(
            ['code' => 'withholding_tax'],
            [
                'label' => 'Withholding tax (WHT)',
                'value_type' => 'withholding_percent',
                'fixed_amount' => config('tich-payroll.default_withholding_rate', 5),
                'display_order' => 99,
                'is_active' => 1,
            ]
        );
    }

    public function down(): void
    {
        PayrollDeductionType::query()->where('code', 'withholding_tax')->delete();
    }
};
