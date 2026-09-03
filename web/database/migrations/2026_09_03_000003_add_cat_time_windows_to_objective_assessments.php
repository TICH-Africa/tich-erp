<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('objective_assessments', function (Blueprint $table) {
            $table->integer('time_limit_minutes')->nullable()->after('max_score');
            $table->dateTime('available_from')->nullable()->after('time_limit_minutes');
            $table->dateTime('available_until')->nullable()->after('available_from');
            $table->boolean('show_results_immediately')->default(true)->after('available_until');
            $table->boolean('allow_multiple_attempts')->default(false)->after('show_results_immediately');
            $table->unsignedSmallInteger('max_attempts')->nullable()->after('allow_multiple_attempts');
            $table->dateTime('student_started_at')->nullable()->after('max_attempts');
            $table->dateTime('student_submitted_at')->nullable()->after('student_started_at');
            $table->integer('time_taken_seconds')->nullable()->after('student_submitted_at');
        });
    }

    public function down(): void
    {
        Schema::table('objective_assessments', function (Blueprint $table) {
            $table->dropColumn([
                'time_limit_minutes',
                'available_from',
                'available_until',
                'show_results_immediately',
                'allow_multiple_attempts',
                'max_attempts',
                'student_started_at',
                'student_submitted_at',
                'time_taken_seconds',
            ]);
        });
    }
};
