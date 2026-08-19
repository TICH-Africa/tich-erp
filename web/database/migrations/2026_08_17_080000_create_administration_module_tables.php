<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('admin_planning_cycles')) {
            Schema::create('admin_planning_cycles', function (Blueprint $table) {
                $table->id();
                $table->string('cycle_code', 50)->unique();
                $table->string('title', 300);
                $table->string('plan_tier', 30); // annual, quarterly, monthly, weekly
                $table->unsignedSmallInteger('fiscal_year');
                $table->date('period_start');
                $table->date('period_end');
                $table->dateTime('requisition_deadline');
                $table->string('status', 50)->default('open'); // open, locked, closed
                $table->text('notes')->nullable();
                $table->unsignedBigInteger('created_by')->nullable();
                $table->timestamps();

                $table->index(['plan_tier', 'fiscal_year', 'status']);
            });
        }

        if (! Schema::hasTable('admin_budget_requests')) {
            Schema::create('admin_budget_requests', function (Blueprint $table) {
                $table->id();
                $table->string('request_code', 50)->unique();
                $table->unsignedBigInteger('planning_cycle_id')->nullable();
                $table->unsignedBigInteger('department_id');
                $table->string('title', 300);
                $table->string('framework', 50)->default('standard'); // standard, cbe
                $table->decimal('requested_amount', 14, 2)->default(0);
                $table->decimal('verified_amount', 14, 2)->nullable();
                $table->decimal('approved_amount', 14, 2)->nullable();
                $table->string('status', 50)->default('draft');
                // draft -> submitted -> finance_review -> executive_review -> approved|rejected
                $table->text('justification')->nullable();
                $table->unsignedBigInteger('submitted_by')->nullable();
                $table->dateTime('submitted_at')->nullable();
                $table->unsignedBigInteger('finance_verified_by')->nullable();
                $table->dateTime('finance_verified_at')->nullable();
                $table->unsignedBigInteger('executive_approved_by')->nullable();
                $table->dateTime('executive_approved_at')->nullable();
                $table->text('workflow_notes')->nullable();
                $table->timestamps();

                $table->foreign('planning_cycle_id')->references('id')->on('admin_planning_cycles')->nullOnDelete();
                $table->foreign('department_id')->references('id')->on('departments')->cascadeOnDelete();
                $table->index(['status', 'department_id']);
            });
        }

        if (! Schema::hasTable('admin_fund_allocations')) {
            Schema::create('admin_fund_allocations', function (Blueprint $table) {
                $table->id();
                $table->string('allocation_code', 50)->unique();
                $table->unsignedBigInteger('budget_request_id')->nullable();
                $table->unsignedBigInteger('department_id');
                $table->unsignedSmallInteger('fiscal_year');
                $table->unsignedTinyInteger('month')->nullable();
                $table->decimal('amount', 14, 2)->default(0);
                $table->string('status', 50)->default('pending'); // pending, released, revoked
                $table->unsignedBigInteger('released_by')->nullable();
                $table->dateTime('released_at')->nullable();
                $table->text('notes')->nullable();
                $table->timestamps();

                $table->foreign('budget_request_id')->references('id')->on('admin_budget_requests')->nullOnDelete();
                $table->foreign('department_id')->references('id')->on('departments')->cascadeOnDelete();
                $table->index(['fiscal_year', 'month', 'status']);
            });
        }

        if (! Schema::hasTable('admin_statutory_certifications')) {
            Schema::create('admin_statutory_certifications', function (Blueprint $table) {
                $table->id();
                $table->string('certificate_code', 50)->unique();
                $table->string('title', 300);
                $table->string('authority', 50); // KRA, TVETA, MoE, other
                $table->string('certificate_number', 150)->nullable();
                $table->date('issued_on')->nullable();
                $table->date('expires_on')->nullable();
                $table->string('status', 50)->default('active'); // active, expiring, expired, pending_renewal
                $table->string('document_path', 500)->nullable();
                $table->text('alignment_notes')->nullable();
                $table->unsignedBigInteger('updated_by')->nullable();
                $table->timestamps();

                $table->index(['authority', 'status', 'expires_on']);
            });
        }

        if (! Schema::hasTable('admin_inspection_checks')) {
            Schema::create('admin_inspection_checks', function (Blueprint $table) {
                $table->id();
                $table->string('check_code', 50)->unique();
                $table->string('area', 100);
                $table->string('requirement', 500);
                $table->string('regulator', 50)->nullable();
                $table->string('status', 50)->default('pending'); // pending, ready, gap, waived
                $table->string('evidence_path', 500)->nullable();
                $table->text('notes')->nullable();
                $table->unsignedBigInteger('reviewed_by')->nullable();
                $table->dateTime('reviewed_at')->nullable();
                $table->timestamps();

                $table->index(['status', 'regulator']);
            });
        }

        if (! Schema::hasTable('admin_quickbooks_sync_logs')) {
            Schema::create('admin_quickbooks_sync_logs', function (Blueprint $table) {
                $table->id();
                $table->string('sync_batch', 50);
                $table->string('source_type', 50); // payment, ap_invoice, receipt
                $table->unsignedBigInteger('source_id')->nullable();
                $table->string('external_ref', 150)->nullable();
                $table->string('status', 50)->default('pending'); // pending, synced, failed
                $table->text('payload')->nullable();
                $table->text('error_message')->nullable();
                $table->unsignedBigInteger('triggered_by')->nullable();
                $table->dateTime('synced_at')->nullable();
                $table->timestamps();

                $table->index(['status', 'source_type']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('admin_quickbooks_sync_logs');
        Schema::dropIfExists('admin_inspection_checks');
        Schema::dropIfExists('admin_statutory_certifications');
        Schema::dropIfExists('admin_fund_allocations');
        Schema::dropIfExists('admin_budget_requests');
        Schema::dropIfExists('admin_planning_cycles');
    }
};
