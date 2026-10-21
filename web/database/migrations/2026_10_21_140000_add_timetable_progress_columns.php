<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A weekly or monthly timetable is issued many times across one semester, so the person building it
 * needs to say where they have got to. week_span counts how many weeks (or months) the timetable
 * covers, progress_note is the plain-English "working up to week 6" line the team reads.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('program_timetables') && ! Schema::hasColumn('program_timetables', 'progress_note')) {
            Schema::table('program_timetables', function (Blueprint $table) {
                $table->string('progress_note', 255)->nullable()->after('generation_notes');
                $table->unsignedTinyInteger('week_span')->nullable()->after('progress_note');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('program_timetables') && Schema::hasColumn('program_timetables', 'progress_note')) {
            Schema::table('program_timetables', function (Blueprint $table) {
                $table->dropColumn(['progress_note', 'week_span']);
            });
        }
    }
};
