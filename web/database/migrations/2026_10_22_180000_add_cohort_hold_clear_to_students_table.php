<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A cohort roll holds a student back while they owe a repeat unit, and the hold lifts on its own
 * when the supplementary is passed. Academics also need to be able to lift it for one teaching
 * period without touching the student's marks, so the cleared period is recorded on the student.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('students') && ! Schema::hasColumn('students', 'hold_cleared_period')) {
            Schema::table('students', function (Blueprint $table) {
                $table->unsignedTinyInteger('hold_cleared_period')->nullable()->after('current_year');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('students') && Schema::hasColumn('students', 'hold_cleared_period')) {
            Schema::table('students', function (Blueprint $table) {
                $table->dropColumn('hold_cleared_period');
            });
        }
    }
};
