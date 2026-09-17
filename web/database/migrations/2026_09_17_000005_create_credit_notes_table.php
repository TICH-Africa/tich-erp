<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('credit_notes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('discrepancy_id');
            $table->unsignedBigInteger('invoice_id');
            $table->unsignedBigInteger('supplier_id');
            $table->string('credit_note_number', 50)->unique();
            $table->decimal('amount', 12, 2);
            $table->text('reason');
            $table->string('status', 50)->default('pending');
            $table->unsignedBigInteger('applied_to_invoice_id')->nullable();
            $table->unsignedBigInteger('created_by');
            $table->dateTime('applied_at')->nullable();
            $table->timestamps();

            $table->foreign('discrepancy_id')->references('id')->on('discrepancies')->restrictOnDelete();
            $table->foreign('invoice_id')->references('id')->on('procurement_invoices')->restrictOnDelete();
            $table->foreign('supplier_id')->references('id')->on('suppliers')->restrictOnDelete();
            $table->foreign('applied_to_invoice_id')->references('id')->on('procurement_invoices')->nullOnDelete();
            $table->foreign('created_by')->references('id')->on('staff')->restrictOnDelete();

            $table->index(['supplier_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('credit_notes');
    }
};
