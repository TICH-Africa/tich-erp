<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('program_timetable_sessions')) {
            Schema::table('program_timetable_sessions', function (Blueprint $table) {
                if (! Schema::hasColumn('program_timetable_sessions', 'lesson_plan_cleared')) {
                    $table->boolean('lesson_plan_cleared')->default(false)->after('segment_id');
                }
                if (! Schema::hasColumn('program_timetable_sessions', 'lesson_plan_id')) {
                    $table->unsignedBigInteger('lesson_plan_id')->nullable()->after('lesson_plan_cleared');
                    $table->foreign('lesson_plan_id')->references('id')->on('lesson_plans')->nullOnDelete();
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('program_timetable_sessions')) {
            Schema::table('program_timetable_sessions', function (Blueprint $table) {
                if (Schema::hasColumn('program_timetable_sessions', 'lesson_plan_id')) {
                    $table->dropForeign(['lesson_plan_id']);
                    $table->dropColumn('lesson_plan_id');
                }
                if (Schema::hasColumn('program_timetable_sessions', 'lesson_plan_cleared')) {
                    $table->dropColumn('lesson_plan_cleared');
                }
            });
        }
    }
};
