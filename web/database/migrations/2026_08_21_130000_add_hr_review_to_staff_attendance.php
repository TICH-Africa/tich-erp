<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('staff_attendance', function (Blueprint $table) {
            $table->string('hr_review_status', 20)->default('pending')->after('location_verification_status');
            $table->unsignedBigInteger('hr_reviewed_by_staff_id')->nullable()->after('hr_review_status');
            $table->timestamp('hr_reviewed_at')->nullable()->after('hr_reviewed_by_staff_id');
            $table->string('hr_review_notes', 2000)->nullable()->after('hr_reviewed_at');
            $table->string('hr_rejection_reason', 1000)->nullable()->after('hr_review_notes');
        });
    }

    public function down(): void
    {
        Schema::table('staff_attendance', function (Blueprint $table) {
            $table->dropColumn([
                'hr_review_status',
                'hr_reviewed_by_staff_id',
                'hr_reviewed_at',
                'hr_review_notes',
                'hr_rejection_reason',
            ]);
        });
    }
};
