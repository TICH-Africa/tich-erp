<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('staff_weekly_time_logs')) {
            Schema::create('staff_weekly_time_logs', function (Blueprint $table) {
                $table->id();
                $table->string('log_code', 40)->unique();
                $table->unsignedBigInteger('staff_id');
                $table->unsignedSmallInteger('log_year');
                $table->unsignedTinyInteger('log_month');
                $table->unsignedTinyInteger('week_number');
                $table->string('week_ref', 30);
                $table->date('period_start');
                $table->date('period_end');
                $table->string('status', 30)->default('draft');
                $table->decimal('total_hours', 8, 2)->default(0);
                $table->decimal('total_units', 10, 2)->nullable();
                $table->string('employee_signed_name', 200)->nullable();
                $table->timestamp('employee_signed_at')->nullable();
                $table->unsignedBigInteger('manager_staff_id')->nullable();
                $table->string('manager_signed_name', 200)->nullable();
                $table->string('manager_signature', 300)->nullable();
                $table->timestamp('manager_signed_at')->nullable();
                $table->boolean('manager_self_endorsed')->default(false);
                $table->unsignedBigInteger('hr_reviewed_by_staff_id')->nullable();
                $table->timestamp('hr_reviewed_at')->nullable();
                $table->text('hr_notes')->nullable();
                $table->timestamps();

                $table->unique(['staff_id', 'log_year', 'log_month', 'week_number'], 'swtl_staff_week_unique');
                $table->index(['status', 'employee_signed_at'], 'swtl_status_submitted_idx');
                $table->index(['log_year', 'log_month', 'week_number'], 'swtl_period_idx');
                $table->foreign('staff_id')->references('id')->on('staff')->cascadeOnDelete();
                $table->foreign('manager_staff_id')->references('id')->on('staff')->nullOnDelete();
                $table->foreign('hr_reviewed_by_staff_id')->references('id')->on('staff')->nullOnDelete();
            });
        }

        if (! Schema::hasTable('staff_weekly_time_log_days')) {
            Schema::create('staff_weekly_time_log_days', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('weekly_time_log_id');
                $table->date('work_date');
                $table->string('day_label', 10);
                $table->boolean('in_month')->default(true);
                $table->time('time_in')->nullable();
                $table->time('time_out')->nullable();
                $table->text('tasks_accomplished')->nullable();
                $table->string('initials', 20)->nullable();
                $table->json('department_ids')->nullable();
                $table->string('approval_sign', 120)->nullable();
                $table->decimal('total_hours', 6, 2)->nullable();
                $table->decimal('total_units', 8, 2)->nullable();
                $table->unsignedTinyInteger('display_order')->default(0);
                $table->timestamps();

                $table->unique(['weekly_time_log_id', 'work_date'], 'swtld_log_date_unique');
                $table->foreign('weekly_time_log_id', 'swtld_log_fk')
                    ->references('id')->on('staff_weekly_time_logs')->cascadeOnDelete();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('staff_weekly_time_log_days');
        Schema::dropIfExists('staff_weekly_time_logs');
    }
};
