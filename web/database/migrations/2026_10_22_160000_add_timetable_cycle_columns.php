<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * A year of study can run more than one timetable in the same semester - the week 1 classes,
     * the week 2 classes, a month's block, or the whole semester at once. The pair says which one
     * a timetable is, so each posting keeps its own row instead of overwriting the last one.
     */
    public function up(): void
    {
        Schema::table('program_timetables', function (Blueprint $table) {
            $table->string('cycle_type', 20)->nullable()->after('timetable_kind');
            $table->unsignedSmallInteger('cycle_no')->nullable()->after('cycle_type');
        });
    }

    public function down(): void
    {
        Schema::table('program_timetables', function (Blueprint $table) {
            $table->dropColumn(['cycle_type', 'cycle_no']);
        });
    }
};
