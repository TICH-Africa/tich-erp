<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('curriculum_version_periods')) {
            Schema::table('curriculum_version_periods', function (Blueprint $table) {
                if (! Schema::hasColumn('curriculum_version_periods', 'learning_start_date')) {
                    $table->date('learning_start_date')->nullable()->after('end_date');
                    $table->date('learning_end_date')->nullable()->after('learning_start_date');
                    $table->date('exam_start_date')->nullable()->after('learning_end_date');
                    $table->date('exam_end_date')->nullable()->after('exam_start_date');
                }
            });
        }

        if (Schema::hasTable('program_timetables')) {
            Schema::table('program_timetables', function (Blueprint $table) {
                if (! Schema::hasColumn('program_timetables', 'title')) {
                    $table->string('title', 200)->nullable()->after('teaching_period');
                }
                if (! Schema::hasColumn('program_timetables', 'timetable_kind')) {
                    $table->string('timetable_kind', 50)->default('lesson')->after('title');
                }
            });
        }

        if (Schema::hasTable('program_timetable_segments')) {
            DB::table('program_timetable_segments')
                ->where('label', 'Lessons start')
                ->delete();
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('curriculum_version_periods')) {
            Schema::table('curriculum_version_periods', function (Blueprint $table) {
                $table->dropColumn([
                    'learning_start_date',
                    'learning_end_date',
                    'exam_start_date',
                    'exam_end_date',
                ]);
            });
        }

        if (Schema::hasTable('program_timetables')) {
            Schema::table('program_timetables', function (Blueprint $table) {
                $table->dropColumn(['title', 'timetable_kind']);
            });
        }
    }
};
