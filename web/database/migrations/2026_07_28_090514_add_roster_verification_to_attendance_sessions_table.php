<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('attendance_sessions', function (Blueprint $table) {
            $table->unsignedBigInteger('roster_verified_by')->nullable()->after('registrar_verified_at');
            $table->dateTime('roster_verified_at')->nullable()->after('roster_verified_by');
            $table->unsignedBigInteger('exam_eligibility_checked_by')->nullable()->after('roster_verified_at');
            $table->dateTime('exam_eligibility_checked_at')->nullable()->after('exam_eligibility_checked_by');
        });
    }

    public function down(): void
    {
        Schema::table('attendance_sessions', function (Blueprint $table) {
            $table->dropColumn([
                'roster_verified_by',
                'roster_verified_at',
                'exam_eligibility_checked_by',
                'exam_eligibility_checked_at',
            ]);
        });
    }
};
