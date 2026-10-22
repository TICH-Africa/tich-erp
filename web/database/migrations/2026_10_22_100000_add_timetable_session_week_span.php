<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A class that only runs for part of the semester - a revision week, a placement month - has to say
 * which weeks or months it covers, not just the first one. week_no stays as the first posting, so
 * every timetable already posted keeps reading exactly as it did.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('program_timetable_sessions') && ! Schema::hasColumn('program_timetable_sessions', 'week_to')) {
            Schema::table('program_timetable_sessions', function (Blueprint $table) {
                $table->unsignedTinyInteger('week_to')->nullable()->after('week_no');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('program_timetable_sessions') && Schema::hasColumn('program_timetable_sessions', 'week_to')) {
            Schema::table('program_timetable_sessions', function (Blueprint $table) {
                $table->dropColumn('week_to');
            });
        }
    }
};
