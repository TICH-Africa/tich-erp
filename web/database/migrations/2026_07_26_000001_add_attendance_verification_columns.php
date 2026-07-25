<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('attendance_sessions', function (Blueprint $table) {
            $table->string('verification_status', 50)->default('draft')->after('is_locked');
            $table->dateTime('submitted_at')->nullable()->after('verification_status');
            $table->unsignedBigInteger('hod_verified_by')->nullable()->after('submitted_at');
            $table->dateTime('hod_verified_at')->nullable()->after('hod_verified_by');
            $table->unsignedBigInteger('registrar_verified_by')->nullable()->after('hod_verified_at');
            $table->dateTime('registrar_verified_at')->nullable()->after('registrar_verified_by');
            $table->string('sheet_image_hash', 64)->nullable()->after('signed_sheet_image_path');

            $table->foreign('hod_verified_by')->references('id')->on('staff')->nullOnDelete();
            $table->foreign('registrar_verified_by')->references('id')->on('staff')->nullOnDelete();
        });

        Schema::table('attendance_summaries', function (Blueprint $table) {
            $table->dateTime('amber_alert_sent_at')->nullable()->after('last_calculated_at');
            $table->dateTime('red_alert_sent_at')->nullable()->after('amber_alert_sent_at');
            $table->tinyInteger('exam_eligibility_blocked')->default(0)->after('red_alert_sent_at');
        });
    }

    public function down(): void
    {
        Schema::table('attendance_summaries', function (Blueprint $table) {
            $table->dropColumn(['amber_alert_sent_at', 'red_alert_sent_at', 'exam_eligibility_blocked']);
        });

        Schema::table('attendance_sessions', function (Blueprint $table) {
            $table->dropForeign(['hod_verified_by']);
            $table->dropForeign(['registrar_verified_by']);
            $table->dropColumn([
                'verification_status',
                'submitted_at',
                'hod_verified_by',
                'hod_verified_at',
                'registrar_verified_by',
                'registrar_verified_at',
                'sheet_image_hash',
            ]);
        });
    }
};
