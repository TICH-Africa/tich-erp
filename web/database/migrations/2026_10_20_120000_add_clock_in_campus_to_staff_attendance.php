<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('staff_attendance', function (Blueprint $table) {
            if (! Schema::hasColumn('staff_attendance', 'clock_in_campus_id')) {
                $table->unsignedBigInteger('clock_in_campus_id')->nullable()->after('field_project_name');
                $table->string('clock_in_campus_other', 300)->nullable()->after('clock_in_campus_id');
                $table->foreign('clock_in_campus_id')
                    ->references('id')
                    ->on('campuses')
                    ->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('staff_attendance', function (Blueprint $table) {
            if (Schema::hasColumn('staff_attendance', 'clock_in_campus_id')) {
                $table->dropForeign(['clock_in_campus_id']);
                $table->dropColumn(['clock_in_campus_id', 'clock_in_campus_other']);
            }
        });
    }
};
