<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('work_from_home_requests')) {
            return;
        }

        Schema::create('work_from_home_requests', function (Blueprint $table) {
            $table->id();
            $table->string('request_code', 40)->unique();
            $table->unsignedBigInteger('staff_id');
            $table->unsignedBigInteger('supervisor_staff_id')->nullable();
            $table->string('supervisor_name', 200)->nullable();
            $table->string('department_name', 200)->nullable();
            $table->string('job_title', 200)->nullable();
            $table->string('arrangement_type', 40)->default('work_from_home');
            $table->date('work_date');
            $table->unsignedSmallInteger('work_year');
            $table->unsignedTinyInteger('work_month');
            $table->unsignedTinyInteger('week_of_month');
            $table->time('start_time')->nullable();
            $table->time('end_time')->nullable();
            $table->decimal('total_hours', 5, 2)->nullable();
            $table->date('period_start')->nullable();
            $table->date('period_end')->nullable();
            $table->json('remote_tasks')->nullable();
            $table->json('considerations')->nullable();
            $table->string('status', 30)->default('pending_hr');
            $table->timestamp('submitted_at')->nullable();
            $table->unsignedBigInteger('hr_reviewed_by_staff_id')->nullable();
            $table->timestamp('hr_reviewed_at')->nullable();
            $table->text('hr_notes')->nullable();
            $table->timestamps();

            $table->index(['staff_id', 'work_year', 'work_month'], 'wfh_staff_month_idx');
            $table->index(['status', 'submitted_at'], 'wfh_status_submitted_idx');
            $table->unique(['staff_id', 'work_date'], 'wfh_staff_date_unique');
            $table->foreign('staff_id')->references('id')->on('staff')->cascadeOnDelete();
            $table->foreign('supervisor_staff_id')->references('id')->on('staff')->nullOnDelete();
            $table->foreign('hr_reviewed_by_staff_id')->references('id')->on('staff')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('work_from_home_requests');
    }
};
