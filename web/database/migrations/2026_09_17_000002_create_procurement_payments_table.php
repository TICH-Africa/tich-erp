<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('procurement_payments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('invoice_id');
            $table->string('payment_number', 50)->unique();
            $table->unsignedBigInteger('supplier_id');
            $table->decimal('amount', 12, 2);
            $table->decimal('retention_amount', 12, 2)->default(0.00);
            $table->decimal('released_amount', 12, 2)->default(0.00);
            $table->string('payment_method', 50);
            $table->string('payment_reference', 100)->nullable();
            $table->string('transaction_channel_ref', 100)->nullable();
            $table->string('status', 50)->default('pending');
            $table->unsignedBigInteger('mpesa_stk_request_id')->nullable();
            $table->unsignedBigInteger('recorded_by');
            $table->date('payment_date')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->foreign('invoice_id')->references('id')->on('procurement_invoices')->restrictOnDelete();
            $table->foreign('supplier_id')->references('id')->on('suppliers')->restrictOnDelete();
            $table->foreign('recorded_by')->references('id')->on('staff')->restrictOnDelete();
            $table->foreign('mpesa_stk_request_id')->references('id')->on('mpesa_stk_requests')->nullOnDelete();

            $table->index(['invoice_id', 'status']);
            $table->index(['supplier_id', 'payment_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('procurement_payments');
    }
};
