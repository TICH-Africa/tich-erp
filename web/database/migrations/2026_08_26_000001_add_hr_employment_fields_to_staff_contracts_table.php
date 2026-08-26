<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('staff_contracts', function (Blueprint $table) {
            $table->unsignedBigInteger('campus_id')->nullable()->after('department_id');
            $table->string('job_grade', 50)->nullable()->after('job_title');
            $table->string('payroll_scheme', 50)->nullable()->after('job_grade');
            $table->decimal('salary_scale', 12, 2)->nullable()->after('payroll_scheme');
            $table->unsignedBigInteger('line_manager_id')->nullable()->after('salary_scale');
            $table->string('organisation_email', 255)->nullable()->after('line_manager_id');

            $table->foreign('campus_id')->references('id')->on('campuses')->nullOnDelete();
            $table->foreign('line_manager_id')->references('id')->on('staff')->nullOnDelete();
            $table->index('campus_id');
            $table->index('line_manager_id');
        });
    }

    public function down(): void
    {
        Schema::table('staff_contracts', function (Blueprint $table) {
            $table->dropForeign(['line_manager_id']);
            $table->dropForeign(['campus_id']);
            $table->dropColumn(['campus_id', 'job_grade', 'payroll_scheme', 'salary_scale', 'line_manager_id', 'organisation_email']);
        });
    }
};
