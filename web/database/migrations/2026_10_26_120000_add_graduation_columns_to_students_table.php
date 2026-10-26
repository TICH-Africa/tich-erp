<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * students.enrollment_status has always allowed 'graduated' and 'alumni', and the CEO dashboard
 * already counts them, but nothing could record when a person finished or what they were awarded,
 * so no screen could ever set those statuses. Admissions now registers past-year graduates, so the
 * graduation year, date and award class live on the student beside the status.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('students')) {
            return;
        }

        Schema::table('students', function (Blueprint $table) {
            if (! Schema::hasColumn('students', 'graduation_year')) {
                $table->unsignedSmallInteger('graduation_year')->nullable()->after('hold_cleared_period');
            }
            if (! Schema::hasColumn('students', 'graduation_date')) {
                $table->date('graduation_date')->nullable()->after('graduation_year');
            }
            if (! Schema::hasColumn('students', 'graduation_award')) {
                $table->string('graduation_award', 120)->nullable()->after('graduation_date');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('students')) {
            return;
        }

        Schema::table('students', function (Blueprint $table) {
            foreach (['graduation_award', 'graduation_date', 'graduation_year'] as $column) {
                if (Schema::hasColumn('students', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
