<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('me_policies', function (Blueprint $table) {
            $table->id();
            $table->string('fiscal_year', 20);
            $table->string('title', 300);
            $table->string('version', 50)->nullable();
            $table->string('file_path', 500);
            $table->text('description')->nullable();
            $table->date('effective_date')->nullable();
            $table->string('status', 50)->default('draft'); // draft, published, archived
            $table->unsignedBigInteger('uploaded_by')->nullable();
            $table->dateTime('uploaded_at')->useCurrent();
            $table->dateTime('published_at')->nullable();
            $table->dateTime('created_at')->useCurrent();
            $table->dateTime('updated_at')->nullable()->useCurrentOnUpdate();
            $table->foreign('uploaded_by')->references('id')->on('staff')->nullOnDelete();
            $table->index(['fiscal_year', 'status']);
        });

        Schema::create('me_policy_signoffs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('policy_id');
            $table->unsignedBigInteger('department_id');
            $table->unsignedBigInteger('staff_id');
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('signed_name', 200);
            $table->string('employee_number', 100)->nullable();
            $table->text('signature')->nullable();
            $table->string('ip_address', 64)->nullable();
            $table->dateTime('signed_at')->useCurrent();
            $table->foreign('policy_id')->references('id')->on('me_policies')->restrictOnDelete();
            $table->foreign('department_id')->references('id')->on('departments')->restrictOnDelete();
            $table->foreign('staff_id')->references('id')->on('staff')->restrictOnDelete();
            $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();
            $table->unique(['policy_id', 'department_id', 'staff_id'], 'me_policy_signoffs_unique');
        });

        Schema::create('me_technical_plans', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('budget_request_id')->nullable();
            $table->unsignedBigInteger('planning_cycle_id')->nullable();
            $table->unsignedBigInteger('department_id');
            $table->string('title', 300);
            $table->string('fiscal_year', 20)->nullable();
            $table->string('status', 50)->default('draft');
            // draft, me_review, me_approved, baseline_locked, closed, returned
            $table->text('summary')->nullable();
            $table->unsignedBigInteger('submitted_by')->nullable();
            $table->dateTime('submitted_at')->nullable();
            $table->unsignedBigInteger('me_reviewed_by')->nullable();
            $table->dateTime('me_reviewed_at')->nullable();
            $table->text('me_notes')->nullable();
            $table->unsignedBigInteger('baseline_locked_by')->nullable();
            $table->dateTime('baseline_locked_at')->nullable();
            $table->dateTime('created_at')->useCurrent();
            $table->dateTime('updated_at')->nullable()->useCurrentOnUpdate();
            $table->foreign('budget_request_id')->references('id')->on('admin_budget_requests')->nullOnDelete();
            $table->foreign('planning_cycle_id')->references('id')->on('admin_planning_cycles')->nullOnDelete();
            $table->foreign('department_id')->references('id')->on('departments')->restrictOnDelete();
            $table->foreign('submitted_by')->references('id')->on('users')->nullOnDelete();
            $table->foreign('me_reviewed_by')->references('id')->on('staff')->nullOnDelete();
            $table->foreign('baseline_locked_by')->references('id')->on('staff')->nullOnDelete();
            $table->index(['department_id', 'status']);
        });

        Schema::create('me_plan_outputs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('technical_plan_id');
            $table->text('output');
            $table->text('activity');
            $table->string('costable_item', 500)->nullable();
            $table->decimal('planned', 14, 2)->default(0);
            $table->string('planned_unit', 50)->nullable();
            $table->integer('display_order')->default(0);
            $table->dateTime('created_at')->useCurrent();
            $table->foreign('technical_plan_id')->references('id')->on('me_technical_plans')->cascadeOnDelete();
        });

        Schema::create('me_quarters', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('technical_plan_id');
            $table->unsignedTinyInteger('quarter_number'); // 1-4
            $table->date('period_start');
            $table->date('period_end');
            $table->string('status', 50)->default('open'); // open, reporting, closed
            $table->dateTime('created_at')->useCurrent();
            $table->unique(['technical_plan_id', 'quarter_number']);
            $table->foreign('technical_plan_id')->references('id')->on('me_technical_plans')->cascadeOnDelete();
        });

        Schema::create('me_quarterly_reports', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('technical_plan_id');
            $table->unsignedBigInteger('quarter_id');
            $table->unsignedBigInteger('department_id');
            $table->string('status', 50)->default('draft');
            // draft, submitted, me_verified, ceo_delivered, returned
            $table->unsignedBigInteger('submitted_by')->nullable();
            $table->dateTime('submitted_at')->nullable();
            $table->unsignedBigInteger('me_verified_by')->nullable();
            $table->dateTime('me_verified_at')->nullable();
            $table->text('me_notes')->nullable();
            $table->dateTime('ceo_delivered_at')->nullable();
            $table->unsignedBigInteger('ceo_reviewed_by')->nullable();
            $table->dateTime('ceo_reviewed_at')->nullable();
            $table->string('ceo_signature', 300)->nullable();
            $table->text('ceo_notes')->nullable();
            $table->dateTime('created_at')->useCurrent();
            $table->dateTime('updated_at')->nullable()->useCurrentOnUpdate();
            $table->unique(['quarter_id', 'department_id']);
            $table->foreign('technical_plan_id')->references('id')->on('me_technical_plans')->restrictOnDelete();
            $table->foreign('quarter_id')->references('id')->on('me_quarters')->restrictOnDelete();
            $table->foreign('department_id')->references('id')->on('departments')->restrictOnDelete();
            $table->foreign('submitted_by')->references('id')->on('users')->nullOnDelete();
            $table->foreign('me_verified_by')->references('id')->on('staff')->nullOnDelete();
            $table->foreign('ceo_reviewed_by')->references('id')->on('users')->nullOnDelete();
        });

        Schema::create('me_quarterly_report_lines', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('quarterly_report_id');
            $table->unsignedBigInteger('plan_output_id')->nullable();
            $table->text('output');
            $table->text('activity');
            $table->string('costable_item', 500)->nullable();
            $table->decimal('planned', 14, 2)->default(0);
            $table->decimal('achieved', 14, 2)->default(0);
            $table->decimal('deviation', 14, 2)->default(0);
            $table->integer('display_order')->default(0);
            $table->foreign('quarterly_report_id')->references('id')->on('me_quarterly_reports')->cascadeOnDelete();
            $table->foreign('plan_output_id')->references('id')->on('me_plan_outputs')->nullOnDelete();
        });

        Schema::create('me_department_health_scores', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('department_id');
            $table->string('fiscal_year', 20)->nullable();
            $table->unsignedBigInteger('planning_cycle_id')->nullable();
            $table->decimal('qa_compliance_avg', 5, 2)->nullable();
            $table->decimal('me_achievement_avg', 5, 2)->nullable();
            $table->decimal('health_score', 5, 2)->nullable();
            $table->string('health_rating', 50)->nullable(); // excellent, good, watch, critical
            $table->dateTime('calculated_at');
            $table->dateTime('created_at')->useCurrent();
            $table->unique(['department_id', 'fiscal_year'], 'me_health_dept_year_unique');
            $table->foreign('department_id')->references('id')->on('departments')->restrictOnDelete();
            $table->foreign('planning_cycle_id')->references('id')->on('admin_planning_cycles')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('me_department_health_scores');
        Schema::dropIfExists('me_quarterly_report_lines');
        Schema::dropIfExists('me_quarterly_reports');
        Schema::dropIfExists('me_quarters');
        Schema::dropIfExists('me_plan_outputs');
        Schema::dropIfExists('me_technical_plans');
        Schema::dropIfExists('me_policy_signoffs');
        Schema::dropIfExists('me_policies');
    }
};
