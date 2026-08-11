<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('payroll_runs')) {
            Schema::create('payroll_runs', function (Blueprint $table) {
                $table->id();
                $table->string('run_number', 50)->unique();
                $table->unsignedSmallInteger('pay_period_year');
                $table->unsignedTinyInteger('pay_period_month');
                $table->string('status', 30)->default('draft');
                $table->unsignedInteger('staff_count')->default(0);
                $table->decimal('total_gross', 14, 2)->default(0);
                $table->decimal('total_deductions', 14, 2)->default(0);
                $table->decimal('total_net', 14, 2)->default(0);
                $table->decimal('total_paye', 14, 2)->default(0);
                $table->decimal('total_nssf', 14, 2)->default(0);
                $table->decimal('total_sha', 14, 2)->default(0);
                $table->decimal('total_ahl', 14, 2)->default(0);
                $table->decimal('total_employer_cost', 14, 2)->default(0);
                $table->text('notes')->nullable();
                $table->unsignedBigInteger('created_by')->nullable();
                $table->unsignedBigInteger('approved_by')->nullable();
                $table->dateTime('approved_at')->nullable();
                $table->unsignedBigInteger('posted_by')->nullable();
                $table->dateTime('posted_at')->nullable();
                $table->string('gl_reference', 100)->nullable();
                $table->dateTime('created_at')->useCurrent();
                $table->dateTime('updated_at')->nullable();
                $table->foreign('created_by')->references('id')->on('staff')->nullOnDelete();
                $table->foreign('approved_by')->references('id')->on('staff')->nullOnDelete();
                $table->foreign('posted_by')->references('id')->on('staff')->nullOnDelete();
            });
        }

        if (Schema::hasTable('payroll_items') && ! Schema::hasColumn('payroll_items', 'payroll_run_id')) {
            Schema::table('payroll_items', function (Blueprint $table) {
                $table->unsignedBigInteger('payroll_run_id')->nullable()->after('id');
                $table->json('calculation_snapshot')->nullable()->after('net_salary');
                $table->foreign('payroll_run_id')->references('id')->on('payroll_runs')->cascadeOnDelete();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('payroll_items') && Schema::hasColumn('payroll_items', 'payroll_run_id')) {
            Schema::table('payroll_items', function (Blueprint $table) {
                $table->dropForeign(['payroll_run_id']);
                $table->dropColumn(['payroll_run_id', 'calculation_snapshot']);
            });
        }

        Schema::dropIfExists('payroll_runs');
    }
};
