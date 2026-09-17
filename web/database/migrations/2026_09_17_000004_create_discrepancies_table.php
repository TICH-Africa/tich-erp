<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('discrepancies', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('invoice_id');
            $table->unsignedBigInteger('three_way_match_id');
            $table->unsignedBigInteger('supplier_id');
            $table->string('discrepancy_type', 50);
            $table->string('field_name', 100)->nullable();
            $table->text('po_value')->nullable();
            $table->text('quotation_value')->nullable();
            $table->text('invoice_value')->nullable();
            $table->decimal('deviation_amount', 12, 2)->default(0.00);
            $table->string('status', 50)->default('open');
            $table->unsignedBigInteger('raised_by');
            $table->unsignedBigInteger('resolved_by')->nullable();
            $table->text('resolution_note')->nullable();
            $table->string('resolution_type', 50)->nullable();
            $table->unsignedBigInteger('credit_note_id')->nullable();
            $table->dateTime('supplier_response_at')->nullable();
            $table->dateTime('resolved_at')->nullable();
            $table->dateTime('escalated_at')->nullable();
            $table->text('supplier_response')->nullable();
            $table->boolean('is_escalated')->default(false);
            $table->boolean('is_fraud_suspected')->default(false);
            $table->text('escalation_note')->nullable();
            $table->timestamps();

            $table->foreign('invoice_id')->references('id')->on('procurement_invoices')->restrictOnDelete();
            $table->foreign('three_way_match_id')->references('id')->on('three_way_matches')->restrictOnDelete();
            $table->foreign('supplier_id')->references('id')->on('suppliers')->restrictOnDelete();
            $table->foreign('raised_by')->references('id')->on('staff')->restrictOnDelete();
            $table->foreign('resolved_by')->references('id')->on('staff')->nullOnDelete();

            $table->index(['invoice_id', 'status']);
            $table->index(['supplier_id', 'status']);
            $table->index(['status', 'is_escalated']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('discrepancies');
    }
};
