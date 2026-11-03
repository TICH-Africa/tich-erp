<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * An online class is posted for a run of days rather than one week number, so a manual class needs
 * the last day it sits on beside the first one. Nullable: a class that repeats with no fixed end is
 * stored exactly as before.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('program_timetable_sessions') && ! Schema::hasColumn('program_timetable_sessions', 'session_date_to')) {
            Schema::table('program_timetable_sessions', function (Blueprint $table) {
                $table->date('session_date_to')->nullable()->after('session_date');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('program_timetable_sessions') && Schema::hasColumn('program_timetable_sessions', 'session_date_to')) {
            Schema::table('program_timetable_sessions', function (Blueprint $table) {
                $table->dropColumn('session_date_to');
            });
        }
    }
};
