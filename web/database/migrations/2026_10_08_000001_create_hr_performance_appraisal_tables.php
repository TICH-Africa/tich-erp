<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('hr_appraisal_cycles')) {
            Schema::create('hr_appraisal_cycles', function (Blueprint $table) {
                $table->id();
                $table->string('name', 200);
                $table->unsignedSmallInteger('fiscal_year');
                $table->unsignedTinyInteger('quarter'); // 1-4
                $table->date('period_start');
                $table->date('period_end');
                $table->string('status', 40)->default('draft'); // draft, open, calibration, closed
                $table->unsignedBigInteger('initiated_by')->nullable();
                $table->text('instructions')->nullable();
                $table->text('calibration_notes')->nullable();
                $table->dateTime('opened_at')->nullable();
                $table->dateTime('calibration_started_at')->nullable();
                $table->dateTime('closed_at')->nullable();
                $table->timestamps();

                $table->foreign('initiated_by')->references('id')->on('staff')->nullOnDelete();
                $table->unique(['fiscal_year', 'quarter']);
                $table->index('status');
            });
        }

        if (! Schema::hasTable('hr_corporate_goals')) {
            Schema::create('hr_corporate_goals', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('cycle_id')->nullable();
                $table->unsignedBigInteger('parent_id')->nullable();
                $table->string('code', 50)->nullable();
                $table->string('title', 300);
                $table->text('description')->nullable();
                $table->string('role_scope', 40)->default('all'); // all, department, job_title
                $table->json('scope_values')->nullable(); // dept ids or job title strings
                $table->decimal('weight_hint', 5, 2)->nullable();
                $table->boolean('is_active')->default(true);
                $table->unsignedBigInteger('created_by')->nullable();
                $table->timestamps();

                $table->foreign('cycle_id')->references('id')->on('hr_appraisal_cycles')->nullOnDelete();
                $table->foreign('parent_id')->references('id')->on('hr_corporate_goals')->nullOnDelete();
                $table->foreign('created_by')->references('id')->on('staff')->nullOnDelete();
                $table->index(['is_active', 'role_scope']);
            });
        }

        if (! Schema::hasTable('hr_appraisals')) {
            Schema::create('hr_appraisals', function (Blueprint $table) {
                $table->id();
                $table->string('appraisal_number', 50)->unique();
                $table->unsignedBigInteger('cycle_id');
                $table->unsignedBigInteger('staff_id');
                $table->unsignedBigInteger('line_manager_id')->nullable(); // snapshotted; resignations do not clear
                $table->unsignedBigInteger('department_id')->nullable();
                $table->string('job_title_snapshot', 200)->nullable();
                $table->text('job_description_snapshot')->nullable();
                $table->string('status', 40)->default('draft_goals');
                $table->decimal('objectives_score', 4, 2)->nullable();
                $table->decimal('competencies_score', 4, 2)->nullable();
                $table->decimal('overall_score', 4, 2)->nullable();
                $table->decimal('calibrated_score', 4, 2)->nullable();
                $table->string('overall_rating', 50)->nullable();
                $table->text('strengths')->nullable();
                $table->text('development_areas')->nullable();
                $table->text('training_recommendations')->nullable();
                $table->text('employee_self_comments')->nullable();
                $table->text('manager_objectives_comments')->nullable();
                $table->text('manager_competencies_comments')->nullable();
                $table->text('hr_comments')->nullable();
                $table->text('calibration_reason')->nullable();
                $table->boolean('staff_agrees')->default(false);
                $table->dateTime('goals_submitted_at')->nullable();
                $table->dateTime('goals_approved_at')->nullable();
                $table->unsignedBigInteger('goals_approved_by')->nullable();
                $table->dateTime('self_submitted_at')->nullable();
                $table->dateTime('manager_submitted_at')->nullable();
                $table->unsignedBigInteger('manager_reviewed_by')->nullable();
                $table->dateTime('calibrated_at')->nullable();
                $table->unsignedBigInteger('calibrated_by')->nullable();
                $table->dateTime('hr_signed_at')->nullable();
                $table->unsignedBigInteger('hr_signed_by')->nullable();
                $table->dateTime('completed_at')->nullable();
                $table->timestamps();

                $table->foreign('cycle_id')->references('id')->on('hr_appraisal_cycles')->restrictOnDelete();
                $table->foreign('staff_id')->references('id')->on('staff')->restrictOnDelete();
                $table->foreign('line_manager_id')->references('id')->on('staff')->nullOnDelete();
                $table->foreign('department_id')->references('id')->on('departments')->nullOnDelete();
                $table->foreign('goals_approved_by')->references('id')->on('staff')->nullOnDelete();
                $table->foreign('manager_reviewed_by')->references('id')->on('staff')->nullOnDelete();
                $table->foreign('calibrated_by')->references('id')->on('staff')->nullOnDelete();
                $table->foreign('hr_signed_by')->references('id')->on('staff')->nullOnDelete();
                $table->unique(['cycle_id', 'staff_id']);
                $table->index(['status', 'line_manager_id']);
                $table->index('staff_id');
            });
        }

        if (! Schema::hasTable('hr_appraisal_goals')) {
            Schema::create('hr_appraisal_goals', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('appraisal_id');
                $table->unsignedTinyInteger('sort_order')->default(1);
                $table->string('goal_type', 40)->default('personal'); // jd_duties, personal, cascaded
                $table->unsignedBigInteger('corporate_goal_id')->nullable();
                $table->string('title', 300);
                $table->text('description')->nullable();
                $table->text('smart_specific')->nullable();
                $table->text('smart_measurable')->nullable();
                $table->text('smart_achievable')->nullable();
                $table->text('smart_relevant')->nullable();
                $table->text('smart_timebound')->nullable();
                $table->date('target_date')->nullable();
                $table->decimal('weight', 5, 2)->default(0);
                $table->text('employee_achievement')->nullable();
                $table->unsignedTinyInteger('self_rating')->nullable();
                $table->unsignedTinyInteger('manager_rating')->nullable();
                $table->text('manager_comments')->nullable();
                $table->string('status', 40)->default('draft'); // draft, pending_supervisor, approved, locked
                $table->timestamps();

                $table->foreign('appraisal_id')->references('id')->on('hr_appraisals')->cascadeOnDelete();
                $table->foreign('corporate_goal_id')->references('id')->on('hr_corporate_goals')->nullOnDelete();
                $table->index(['appraisal_id', 'sort_order']);
            });
        }

        if (! Schema::hasTable('hr_appraisal_competencies')) {
            Schema::create('hr_appraisal_competencies', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('appraisal_id');
                $table->string('category', 40); // core, managerial, functional
                $table->string('competency_key', 80);
                $table->string('competency_label', 200);
                $table->boolean('is_applicable')->default(true);
                $table->unsignedTinyInteger('self_rating')->nullable();
                $table->unsignedTinyInteger('manager_rating')->nullable();
                $table->text('comments')->nullable();
                $table->timestamps();

                $table->foreign('appraisal_id')->references('id')->on('hr_appraisals')->cascadeOnDelete();
                $table->unique(['appraisal_id', 'competency_key']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('hr_appraisal_competencies');
        Schema::dropIfExists('hr_appraisal_goals');
        Schema::dropIfExists('hr_appraisals');
        Schema::dropIfExists('hr_corporate_goals');
        Schema::dropIfExists('hr_appraisal_cycles');
    }
};
