<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payroll_statutory_brackets', function (Blueprint $table) {
            $table->id();
            $table->string('scheme', 16);
            $table->string('label', 120);
            $table->decimal('min_amount', 12, 2)->default(0);
            $table->decimal('max_amount', 12, 2)->nullable();
            $table->string('calculation', 24)->default('percent');
            $table->decimal('rate_percent', 7, 4)->nullable();
            $table->decimal('fixed_amount', 12, 2)->nullable();
            $table->decimal('cap_amount', 12, 2)->nullable();
            $table->unsignedSmallInteger('display_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['scheme', 'display_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payroll_statutory_brackets');
    }
};