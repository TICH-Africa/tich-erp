<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('procurement_invoices', function (Blueprint $table) {
            $table->id();
            $table->string('invoice_number', 50)->unique();
            $table->unsignedBigInteger('supplier_id');
            $table->unsignedBigInteger('purchase_order_id');
            $table->unsignedBigInteger('rfq_id')->nullable();
            $table->unsignedBigInteger('requisition_id')->nullable();
            $table->date('invoice_date');
            $table->date('due_date')->nullable();
            $table->decimal('subtotal', 12, 2)->default(0.00);
            $table->decimal('tax_amount', 12, 2)->default(0.00);
            $table->decimal('total_amount', 12, 2)->default(0.00);
            $table->decimal('amount_paid', 12, 2)->default(0.00);
            $table->decimal('balance', 12, 2)->default(0.00);
            $table->string('status', 50)->default('draft');
            $table->decimal('retention_percent', 5, 2)->default(0.00);
            $table->decimal('retention_amount', 12, 2)->default(0.00);
            $table->decimal('released_amount', 12, 2)->default(0.00);
            $table->text('payment_certificate')->nullable();
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('matched_by')->nullable();
            $table->dateTime('matched_at')->nullable();
            $table->dateTime('paid_at')->nullable();
            $table->timestamps();

            $table->foreign('supplier_id')->references('id')->on('suppliers')->restrictOnDelete();
            $table->foreign('purchase_order_id')->references('id')->on('purchase_orders')->restrictOnDelete();
            $table->foreign('rfq_id')->references('id')->on('rfqs')->nullOnDelete();
            $table->foreign('requisition_id')->references('id')->on('procurement_requisitions')->nullOnDelete();
            $table->foreign('created_by')->references('id')->on('staff')->nullOnDelete();
            $table->foreign('matched_by')->references('id')->on('staff')->nullOnDelete();

            $table->index(['supplier_id', 'status']);
            $table->index(['status', 'due_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('procurement_invoices');
    }
};
