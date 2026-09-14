<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rfqs', function (Blueprint $table) {
            $table->id();
            $table->string('rfq_number', 50)->unique();
            $table->unsignedBigInteger('requisition_id');
            $table->unsignedBigInteger('created_by');
            $table->text('item_description');
            $table->decimal('quantity', 12, 2);
            $table->text('specifications')->nullable();
            $table->string('delivery_timeline', 100)->nullable();
            $table->string('delivery_location', 500)->nullable();
            $table->date('submission_deadline')->nullable();
            $table->json('minimum_categories')->nullable(); // ['goods', 'services', 'works']
            $table->json('preferred_categories')->nullable();
            $table->integer('minimum_suppliers')->default(3);
            $table->string('status', 50)->default('draft'); // draft, published, closed, awarded, cancelled
            $table->string('award_decision', 50)->nullable(); // pending, awarded, cancelled
            $table->unsignedBigInteger('awarded_supplier_id')->nullable();
            $table->decimal('awarded_amount', 12, 2)->nullable();
            $table->string('approval_level', 50)->nullable(); // procurement_officer, finance, ceo
            $table->string('approval_status', 50)->default('pending'); // pending, approved, rejected
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->dateTime('approved_at')->nullable();
            $table->text('approval_notes')->nullable();
            $table->text('award_letter_path')->nullable();
            $table->dateTime('published_at')->nullable();
            $table->dateTime('closed_at')->nullable();
            $table->dateTime('created_at')->useCurrent();
            $table->foreign('requisition_id')->references('id')->on('procurement_requisitions')->restrictOnDelete();
            $table->foreign('created_by')->references('id')->on('staff')->restrictOnDelete();
            $table->foreign('awarded_supplier_id')->references('id')->on('suppliers')->nullOnDelete();
            $table->foreign('approved_by')->references('id')->on('staff')->nullOnDelete();
        });

        Schema::create('rfq_suppliers', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('rfq_id');
            $table->unsignedBigInteger('supplier_id');
            $table->string('invitation_status', 50)->default('invited'); // invited, accepted, declined, no_response
            $table->text('decline_reason')->nullable();
            $table->dateTime('invited_at')->useCurrent();
            $table->dateTime('responded_at')->nullable();
            $table->foreign('rfq_id')->references('id')->on('rfqs')->cascadeOnDelete();
            $table->foreign('supplier_id')->references('id')->on('suppliers')->cascadeOnDelete();
            $table->unique(['rfq_id', 'supplier_id']);
        });

        Schema::create('rfq_quotations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('rfq_id');
            $table->unsignedBigInteger('supplier_id');
            $table->unsignedBigInteger('submitted_by')->nullable();
            $table->decimal('unit_price', 12, 2);
            $table->decimal('total_price', 12, 2);
            $table->string('delivery_period', 100)->nullable();
            $table->string('payment_terms', 500)->nullable();
            $table->string('warranty_terms', 500)->nullable();
            $table->string('validity_period', 100)->nullable();
            $table->text('technical_notes')->nullable();
            $table->json('attachments')->nullable();
            $table->string('status', 50)->default('submitted'); // submitted, revised, withdrawn, locked
            $table->dateTime('submitted_at')->useCurrent();
            $table->dateTime('revised_at')->nullable();
            $table->dateTime('locked_at')->nullable();
            $table->foreign('rfq_id')->references('id')->on('rfqs')->cascadeOnDelete();
            $table->foreign('supplier_id')->references('id')->on('suppliers')->cascadeOnDelete();
            $table->foreign('submitted_by')->references('id')->on('staff')->nullOnDelete();
            $table->unique(['rfq_id', 'supplier_id']);
        });

        Schema::create('rfq_evaluations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('rfq_id');
            $table->unsignedBigInteger('supplier_id');
            $table->unsignedBigInteger('evaluated_by');
            $table->decimal('price_score', 5, 2)->default(0); // 0-40
            $table->decimal('technical_score', 5, 2)->default(0); // 0-30
            $table->decimal('delivery_score', 5, 2)->default(0); // 0-15
            $table->decimal('payment_terms_score', 5, 2)->default(0); // 0-10
            $table->decimal('performance_score', 5, 2)->default(0); // 0-5
            $table->decimal('total_score', 5, 2)->default(0); // 0-100
            $table->integer('rank')->nullable();
            $table->text('comments')->nullable();
            $table->boolean('has_conflict_of_interest')->default(false);
            $table->text('conflict_of_interest_details')->nullable();
            $table->boolean('is_recused')->default(false);
            $table->dateTime('evaluated_at')->useCurrent();
            $table->foreign('rfq_id')->references('id')->on('rfqs')->cascadeOnDelete();
            $table->foreign('supplier_id')->references('id')->on('suppliers')->cascadeOnDelete();
            $table->foreign('evaluated_by')->references('id')->on('staff')->restrictOnDelete();
            $table->unique(['rfq_id', 'supplier_id', 'evaluated_by']);
        });

        Schema::create('rfq_clarifications', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('rfq_id');
            $table->unsignedBigInteger('supplier_id')->nullable();
            $table->unsignedBigInteger('asked_by')->nullable();
            $table->text('question');
            $table->text('answer')->nullable();
            $table->boolean('is_public')->default(true);
            $table->dateTime('asked_at')->useCurrent();
            $table->dateTime('answered_at')->nullable();
            $table->foreign('rfq_id')->references('id')->on('rfqs')->cascadeOnDelete();
            $table->foreign('supplier_id')->references('id')->on('suppliers')->nullOnDelete();
            $table->foreign('asked_by')->references('id')->on('staff')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rfq_clarifications');
        Schema::dropIfExists('rfq_evaluations');
        Schema::dropIfExists('rfq_quotations');
        Schema::dropIfExists('rfq_suppliers');
        Schema::dropIfExists('rfqs');
    }
};
