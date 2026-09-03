<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('objective_submissions', function (Blueprint $table) {
            $table->dateTime('student_started_at')->nullable()->after('updated_at');
            $table->dateTime('student_submitted_at')->nullable()->after('student_started_at');
            $table->integer('time_taken_seconds')->nullable()->after('student_submitted_at');
            $table->unsignedSmallInteger('attempt_number')->default(1)->after('time_taken_seconds');
        });
    }

    public function down(): void
    {
        Schema::table('objective_submissions', function (Blueprint $table) {
            $table->dropColumn([
                'student_started_at',
                'student_submitted_at',
                'time_taken_seconds',
                'attempt_number',
            ]);
        });
    }
};
