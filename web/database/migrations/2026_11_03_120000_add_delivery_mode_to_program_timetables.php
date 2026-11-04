<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A year and semester can be taught to the campus class and to the online class on different hours,
 * so the posting itself has to say who it is for. Existing rows keep 'physical' and behave as before.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('program_timetables') && ! Schema::hasColumn('program_timetables', 'delivery_mode')) {
            Schema::table('program_timetables', function (Blueprint $table) {
                $table->string('delivery_mode', 20)->default('physical')->after('timetable_kind');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('program_timetables') && Schema::hasColumn('program_timetables', 'delivery_mode')) {
            Schema::table('program_timetables', function (Blueprint $table) {
                $table->dropColumn('delivery_mode');
            });
        }
    }
};
