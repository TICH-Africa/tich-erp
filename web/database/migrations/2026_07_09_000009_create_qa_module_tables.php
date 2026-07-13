<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('qa_plans', function (Blueprint $table) {
            $table->id();
            $table->string('plan_name', 300);
            $table->date('period_start');
            $table->date('period_end');
            $table->string('scope_type', 50); // institution_wide, department_specific
            $table->json('department_ids')->nullable();
            $table->unsignedBigInteger('deployed_by');
            $table->dateTime('deployed_at');
            $table->string('status', 50)->default('active'); // active, closed, cancelled
            $table->dateTime('created_at')->useCurrent();
            $table->dateTime('updated_at')->nullable()->useCurrentOnUpdate();
            $table->foreign('deployed_by')->references('id')->on('staff')->restrictOnDelete();
        });

        Schema::create('qa_audit_checklists', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('qa_plan_id');
            $table->text('checklist_item_text');
            $table->string('item_category', 100)->nullable();
            $table->decimal('weight', 5, 2)->default(1.00);
            $table->decimal('max_score', 5, 2)->default(100.00);
            $table->unsignedBigInteger('applies_to_department_id')->nullable();
            $table->tinyInteger('requires_evidence')->default(1);
            $table->integer('display_order')->default(0);
            $table->tinyInteger('is_active')->default(1);
            $table->dateTime('created_at')->useCurrent();
            $table->foreign('qa_plan_id')->references('id')->on('qa_plans')->restrictOnDelete();
            $table->foreign('applies_to_department_id')->references('id')->on('departments')->nullOnDelete();
        });

        Schema::create('qa_department_submissions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('qa_plan_id');
            $table->unsignedBigInteger('checklist_item_id');
            $table->unsignedBigInteger('department_id');
            $table->unsignedBigInteger('submitted_by');
            $table->text('submission_text')->nullable();
            $table->decimal('score', 5, 2)->nullable();
            $table->string('submission_status', 50)->default('pending'); // pending, submitted, verified, approved, rejected
            $table->unsignedBigInteger('verified_by')->nullable();
            $table->dateTime('verified_at')->nullable();
            $table->text('verified_notes')->nullable();
            $table->dateTime('submitted_at')->useCurrent();
            $table->unique(['checklist_item_id', 'department_id', 'qa_plan_id'], 'qa_dept_submissions_unique');
            $table->foreign('qa_plan_id')->references('id')->on('qa_plans')->restrictOnDelete();
            $table->foreign('checklist_item_id')->references('id')->on('qa_audit_checklists')->restrictOnDelete();
            $table->foreign('department_id')->references('id')->on('departments')->restrictOnDelete();
            $table->foreign('submitted_by')->references('id')->on('staff')->restrictOnDelete();
            $table->foreign('verified_by')->references('id')->on('staff')->nullOnDelete();
        });

        Schema::create('qa_corrective_actions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('qa_plan_id');
            $table->unsignedBigInteger('department_id');
            $table->unsignedBigInteger('checklist_item_id')->nullable();
            $table->text('flagged_reason');
            $table->decimal('compliance_score_at_flag', 5, 2)->nullable();
            $table->date('resolution_deadline');
            $table->text('resolution_plan')->nullable();
            $table->unsignedBigInteger('responsible_officer_id')->nullable();
            $table->string('status', 50)->default('open'); // open, in_progress, resolved, overdue, closed
            $table->dateTime('resolved_at')->nullable();
            $table->unsignedBigInteger('resolved_by')->nullable();
            $table->text('resolution_notes')->nullable();
            $table->tinyInteger('is_module_lock_active')->default(0);
            $table->dateTime('created_at')->useCurrent();
            $table->dateTime('updated_at')->nullable()->useCurrentOnUpdate();
            $table->foreign('qa_plan_id')->references('id')->on('qa_plans')->restrictOnDelete();
            $table->foreign('department_id')->references('id')->on('departments')->restrictOnDelete();
            $table->foreign('checklist_item_id')->references('id')->on('qa_audit_checklists')->nullOnDelete();
            $table->foreign('responsible_officer_id')->references('id')->on('staff')->nullOnDelete();
            $table->foreign('resolved_by')->references('id')->on('staff')->nullOnDelete();
        });

        Schema::create('qa_compliance_scores', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('qa_plan_id');
            $table->unsignedBigInteger('department_id');
            $table->integer('total_items')->default(0);
            $table->integer('items_submitted')->default(0);
            $table->decimal('weighted_score', 5, 2)->default(0.00);
            $table->string('pass_fail_status', 50)->default('pending'); // pending, pass, fail
            $table->tinyInteger('is_below_threshold')->default(0);
            $table->dateTime('threshold_met_at')->nullable();
            $table->dateTime('calculated_at');
            $table->dateTime('created_at')->useCurrent();
            $table->unique(['qa_plan_id', 'department_id'], 'qa_compliance_scores_unique');
            $table->foreign('qa_plan_id')->references('id')->on('qa_plans')->restrictOnDelete();
            $table->foreign('department_id')->references('id')->on('departments')->restrictOnDelete();
        });

        Schema::create('qa_evidence_attachments', function (Blueprint $table) {
            $table->id();
            $table->string('evidence_type', 50); // checklist_submission, corrective_action, attendance_sheet, exam_draft, lesson_plan, compliance_doc
            $table->unsignedBigInteger('linked_id');
            $table->string('file_path', 500);
            $table->string('file_type', 50); // image, pdf, document, video
            $table->string('description', 500)->nullable();
            $table->unsignedBigInteger('uploaded_by');
            $table->dateTime('uploaded_at')->useCurrent();
            $table->foreign('uploaded_by')->references('id')->on('staff')->restrictOnDelete();
            $table->index(['evidence_type', 'linked_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('qa_evidence_attachments');
        Schema::dropIfExists('qa_compliance_scores');
        Schema::dropIfExists('qa_corrective_actions');
        Schema::dropIfExists('qa_department_submissions');
        Schema::dropIfExists('qa_audit_checklists');
        Schema::dropIfExists('qa_plans');
    }
};
