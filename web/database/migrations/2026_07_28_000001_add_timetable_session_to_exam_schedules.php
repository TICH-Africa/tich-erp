<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('exam_schedules')) {
            return;
        }

        Schema::table('exam_schedules', function (Blueprint $table) {
            if (! Schema::hasColumn('exam_schedules', 'program_timetable_session_id')) {
                $table->unsignedBigInteger('program_timetable_session_id')->nullable()->after('semester_id');
                $table->foreign('program_timetable_session_id', 'exam_schedules_pts_fk')
                    ->references('id')
                    ->on('program_timetable_sessions')
                    ->nullOnDelete();
                $table->unique('program_timetable_session_id', 'exam_schedules_pts_unique');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('exam_schedules')) {
            return;
        }

        Schema::table('exam_schedules', function (Blueprint $table) {
            if (Schema::hasColumn('exam_schedules', 'program_timetable_session_id')) {
                $table->dropForeign('exam_schedules_pts_fk');
                $table->dropUnique('exam_schedules_pts_unique');
                $table->dropColumn('program_timetable_session_id');
            }
        });
    }
};
