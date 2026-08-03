<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payroll_tax_bands', function (Blueprint $table) {
            $table->id();
            $table->string('label', 120);
            $table->decimal('min_amount', 12, 2)->default(0);
            $table->decimal('max_amount', 12, 2)->nullable();
            $table->decimal('rate_percent', 5, 2);
            $table->unsignedSmallInteger('display_order')->default(0);
            $table->tinyInteger('is_active')->default(1);
            $table->date('effective_from')->nullable();
            $table->timestamps();
        });

        Schema::create('payroll_statutory_rates', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50)->unique();
            $table->string('label', 120);
            $table->decimal('rate_percent', 7, 4)->nullable();
            $table->decimal('employer_rate_percent', 7, 4)->nullable();
            $table->decimal('fixed_amount', 12, 2)->nullable();
            $table->decimal('floor_amount', 12, 2)->nullable();
            $table->decimal('ceiling_amount', 12, 2)->nullable();
            $table->string('applies_to', 30)->default('gross');
            $table->text('notes')->nullable();
            $table->unsignedSmallInteger('display_order')->default(0);
            $table->tinyInteger('is_active')->default(1);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payroll_statutory_rates');
        Schema::dropIfExists('payroll_tax_bands');
    }
};
