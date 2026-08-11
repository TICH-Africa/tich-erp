<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('invoice_items')) {
            Schema::create('invoice_items', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('invoice_id');
                $table->string('fee_item', 100);
                $table->string('description', 300);
                $table->decimal('amount', 12, 2);
                $table->decimal('scholarship_adjustment', 12, 2)->default(0.00);
                $table->decimal('bursary_adjustment', 12, 2)->default(0.00);
                $table->decimal('waiver_adjustment', 12, 2)->default(0.00);
                $table->decimal('net_amount', 12, 2);
                $table->dateTime('created_at')->useCurrent();
                $table->foreign('invoice_id')->references('id')->on('invoices')->restrictOnDelete();
            });
        }

        if (! Schema::hasTable('payment_allocations')) {
            Schema::create('payment_allocations', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('payment_id');
                $table->unsignedBigInteger('invoice_id');
                $table->decimal('allocated_amount', 12, 2);
                $table->dateTime('allocated_at')->useCurrent();
                $table->foreign('payment_id')->references('id')->on('payments')->restrictOnDelete();
                $table->foreign('invoice_id')->references('id')->on('invoices')->restrictOnDelete();
            });
        }

        if (! Schema::hasTable('receipts')) {
            Schema::create('receipts', function (Blueprint $table) {
                $table->id();
                $table->string('receipt_number', 50)->unique();
                $table->unsignedBigInteger('payment_id');
                $table->unsignedBigInteger('invoice_id');
                $table->unsignedBigInteger('student_account_id');
                $table->unsignedBigInteger('student_id');
                $table->decimal('amount', 12, 2);
                $table->string('payment_method', 50);
                $table->string('payment_reference', 100)->nullable();
                $table->dateTime('issued_at')->useCurrent();
                $table->unsignedBigInteger('issued_by');
                $table->foreign('payment_id')->references('id')->on('payments')->restrictOnDelete();
                $table->foreign('invoice_id')->references('id')->on('invoices')->restrictOnDelete();
                $table->foreign('student_account_id')->references('id')->on('student_accounts')->restrictOnDelete();
                $table->foreign('student_id')->references('id')->on('students')->restrictOnDelete();
                $table->foreign('issued_by')->references('id')->on('staff')->restrictOnDelete();
            });
        }

        if (! Schema::hasTable('financial_adjustments')) {
            Schema::create('financial_adjustments', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('student_account_id');
                $table->unsignedBigInteger('student_id');
                $table->unsignedBigInteger('invoice_id')->nullable();
                $table->unsignedBigInteger('invoice_item_id')->nullable();
                $table->string('adjustment_type', 50);
                $table->string('reason', 500);
                $table->decimal('amount', 12, 2);
                $table->string('status', 50)->default('pending');
                $table->unsignedBigInteger('requested_by');
                $table->unsignedBigInteger('approved_by')->nullable();
                $table->dateTime('approved_at')->nullable();
                $table->dateTime('created_at')->useCurrent();
                $table->foreign('student_account_id')->references('id')->on('student_accounts')->restrictOnDelete();
                $table->foreign('student_id')->references('id')->on('students')->restrictOnDelete();
                $table->foreign('invoice_id')->references('id')->on('invoices')->nullOnDelete();
                if (Schema::hasTable('invoice_items')) {
                    $table->foreign('invoice_item_id')->references('id')->on('invoice_items')->nullOnDelete();
                }
                $table->foreign('requested_by')->references('id')->on('staff')->restrictOnDelete();
                $table->foreign('approved_by')->references('id')->on('staff')->nullOnDelete();
            });
        }

        if (! Schema::hasTable('installment_plans')) {
            Schema::create('installment_plans', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('student_account_id');
                $table->unsignedBigInteger('student_id');
                $table->unsignedBigInteger('invoice_id');
                $table->string('plan_number', 50)->unique();
                $table->decimal('total_amount', 12, 2);
                $table->decimal('paid_amount', 12, 2)->default(0.00);
                $table->decimal('remaining_amount', 12, 2);
                $table->string('status', 50)->default('active');
                $table->dateTime('created_at')->useCurrent();
                $table->foreign('student_account_id')->references('id')->on('student_accounts')->restrictOnDelete();
                $table->foreign('student_id')->references('id')->on('students')->restrictOnDelete();
                $table->foreign('invoice_id')->references('id')->on('invoices')->restrictOnDelete();
            });
        }

        if (! Schema::hasTable('installment_plan_items')) {
            Schema::create('installment_plan_items', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('installment_plan_id');
                $table->integer('installment_number');
                $table->decimal('amount', 12, 2);
                $table->date('due_date');
                $table->string('status', 50)->default('pending');
                $table->decimal('paid_amount', 12, 2)->default(0.00);
                $table->dateTime('paid_at')->nullable();
                $table->foreign('installment_plan_id')->references('id')->on('installment_plans')->restrictOnDelete();
            });
        }

        if (! Schema::hasTable('refunds')) {
            Schema::create('refunds', function (Blueprint $table) {
                $table->id();
                $table->string('refund_number', 50)->unique();
                $table->unsignedBigInteger('payment_id');
                $table->unsignedBigInteger('invoice_id');
                $table->unsignedBigInteger('student_account_id');
                $table->unsignedBigInteger('student_id');
                $table->decimal('amount', 12, 2);
                $table->string('reason', 500);
                $table->string('status', 50)->default('pending');
                $table->unsignedBigInteger('requested_by');
                $table->unsignedBigInteger('approved_by')->nullable();
                $table->dateTime('approved_at')->nullable();
                $table->unsignedBigInteger('processed_by')->nullable();
                $table->dateTime('processed_at')->nullable();
                $table->dateTime('created_at')->useCurrent();
                $table->foreign('payment_id')->references('id')->on('payments')->restrictOnDelete();
                $table->foreign('invoice_id')->references('id')->on('invoices')->restrictOnDelete();
                $table->foreign('student_account_id')->references('id')->on('student_accounts')->restrictOnDelete();
                $table->foreign('student_id')->references('id')->on('students')->restrictOnDelete();
                $table->foreign('requested_by')->references('id')->on('staff')->restrictOnDelete();
                $table->foreign('approved_by')->references('id')->on('staff')->nullOnDelete();
                $table->foreign('processed_by')->references('id')->on('staff')->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('refunds');
        Schema::dropIfExists('installment_plan_items');
        Schema::dropIfExists('installment_plans');
        Schema::dropIfExists('financial_adjustments');
        Schema::dropIfExists('receipts');
        Schema::dropIfExists('payment_allocations');
        Schema::dropIfExists('invoice_items');
    }
};
