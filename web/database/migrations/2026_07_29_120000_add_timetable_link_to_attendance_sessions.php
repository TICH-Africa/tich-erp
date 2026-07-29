<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('attendance_sessions', function (Blueprint $table) {
            if (! Schema::hasColumn('attendance_sessions', 'program_timetable_session_id')) {
                $table->unsignedBigInteger('program_timetable_session_id')->nullable()->after('unit_allocation_id');
                $table->foreign('program_timetable_session_id', 'att_sess_timetable_slot_fk')
                    ->references('id')
                    ->on('program_timetable_sessions')
                    ->nullOnDelete();
                $table->unique(
                    ['program_timetable_session_id', 'session_date'],
                    'att_sess_timetable_slot_date_unique'
                );
            }

            if (! Schema::hasColumn('attendance_sessions', 'class_photo_image_path')) {
                $table->string('class_photo_image_path', 500)->nullable()->after('sheet_image_hash');
            }

            if (! Schema::hasColumn('attendance_sessions', 'class_photo_image_hash')) {
                $table->string('class_photo_image_hash', 64)->nullable()->after('class_photo_image_path');
            }
        });
    }

    public function down(): void
    {
        Schema::table('attendance_sessions', function (Blueprint $table) {
            if (Schema::hasColumn('attendance_sessions', 'program_timetable_session_id')) {
                $table->dropForeign('att_sess_timetable_slot_fk');
                $table->dropUnique('att_sess_timetable_slot_date_unique');
                $table->dropColumn('program_timetable_session_id');
            }

            if (Schema::hasColumn('attendance_sessions', 'class_photo_image_hash')) {
                $table->dropColumn('class_photo_image_hash');
            }

            if (Schema::hasColumn('attendance_sessions', 'class_photo_image_path')) {
                $table->dropColumn('class_photo_image_path');
            }
        });
    }
};
