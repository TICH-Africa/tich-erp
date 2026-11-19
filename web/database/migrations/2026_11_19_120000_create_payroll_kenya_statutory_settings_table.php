<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payroll_kenya_statutory_settings', function (Blueprint $table) {
            $table->id();
            $table->decimal('nssf_rate_percent', 7, 4)->default(6);
            $table->decimal('nssf_tier1_ceiling', 12, 2)->default(9000);
            $table->decimal('nssf_tier1_max_employee', 12, 2)->default(540);
            $table->decimal('nssf_tier2_ceiling', 12, 2)->default(108000);
            $table->decimal('nssf_tier2_max_employee', 12, 2)->default(5940);
            $table->decimal('nssf_max_employee', 12, 2)->default(6480);
            $table->decimal('shif_rate_percent', 7, 4)->default(2.75);
            $table->decimal('shif_monthly_floor', 12, 2)->default(300);
            $table->decimal('shif_floor_applies_up_to_gross', 12, 2)->default(10909);
            $table->decimal('ahl_rate_percent', 7, 4)->default(1.5);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payroll_kenya_statutory_settings');
    }
};