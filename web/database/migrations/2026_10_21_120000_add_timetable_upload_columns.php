<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A timetable that Academics uploaded is a record of what was actually issued, not something the
 * scheduler invented, so it needs to carry where it came from and, for the rows, the dated week
 * they belong to. Everything is nullable: generated timetables keep working unchanged.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('program_timetables') && ! Schema::hasColumn('program_timetables', 'source')) {
            Schema::table('program_timetables', function (Blueprint $table) {
                $table->string('source', 20)->default('generated')->after('status');
                $table->string('source_file')->nullable()->after('source');
                $table->string('source_name')->nullable()->after('source_file');
                $table->dateTime('source_uploaded_at')->nullable()->after('source_name');
                $table->unsignedBigInteger('source_uploaded_by')->nullable()->after('source_uploaded_at');
            });
        }

        if (Schema::hasTable('program_timetable_sessions') && ! Schema::hasColumn('program_timetable_sessions', 'session_date')) {
            Schema::table('program_timetable_sessions', function (Blueprint $table) {
                $table->date('session_date')->nullable()->after('end_time');
                $table->unsignedTinyInteger('week_no')->nullable()->after('session_date');
                $table->string('source', 20)->default('generated')->after('week_no');
                $table->string('external_ref', 120)->nullable()->after('source');
            });
        }
        if (Schema::hasTable('program_timetable_sessions') && ! Schema::hasColumn('program_timetable_sessions', 'delivery_mode')) {
            Schema::table('program_timetable_sessions', function (Blueprint $table) {
                $table->string('delivery_mode', 20)->default('physical')->after('session_type');
            });
        }

        if (Schema::hasTable('program_timetable_sessions') && ! Schema::hasColumn('program_timetable_sessions', 'recurrence')) {
            Schema::table('program_timetable_sessions', function (Blueprint $table) {
                $table->string('recurrence', 20)->default('weekly')->after('delivery_mode');
                $table->decimal('contact_hours', 6, 2)->nullable()->after('recurrence');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('program_timetable_sessions') && Schema::hasColumn('program_timetable_sessions', 'recurrence')) {
            Schema::table('program_timetable_sessions', function (Blueprint $table) {
                $table->dropColumn(['recurrence', 'contact_hours']);
            });
        }

        if (Schema::hasTable('program_timetable_sessions') && Schema::hasColumn('program_timetable_sessions', 'delivery_mode')) {
            Schema::table('program_timetable_sessions', function (Blueprint $table) {
                $table->dropColumn('delivery_mode');
            });
        }
        if (Schema::hasTable('program_timetable_sessions') && Schema::hasColumn('program_timetable_sessions', 'session_date')) {
            Schema::table('program_timetable_sessions', function (Blueprint $table) {
                $table->dropColumn(['session_date', 'week_no', 'source', 'external_ref']);
            });
        }

        if (Schema::hasTable('program_timetables') && Schema::hasColumn('program_timetables', 'source')) {
            Schema::table('program_timetables', function (Blueprint $table) {
                $table->dropColumn(['source', 'source_file', 'source_name', 'source_uploaded_at', 'source_uploaded_by']);
            });
        }
    }
};
