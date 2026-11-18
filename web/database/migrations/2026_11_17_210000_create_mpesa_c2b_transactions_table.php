<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Every call Safaricom makes about a paybill payment, so a replayed or retried payload
 * can never book a second payment for the same transaction.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mpesa_c2b_transactions', function (Blueprint $table) {
            $table->id();
            $table->string('kind', 20);
            $table->string('transaction_id', 50)->nullable();
            $table->string('receipt_number', 50)->nullable();
            $table->string('local_trxn_id', 50)->nullable();
            $table->string('bill_ref_number', 60)->nullable();
            $table->string('invoice_number', 60)->nullable();
            $table->string('msisdn', 20)->nullable();
            $table->decimal('amount', 12, 2)->nullable();
            $table->string('status', 20)->default('received');
            $table->string('result_code', 20)->nullable();
            $table->string('result_desc', 500)->nullable();
            $table->foreignId('student_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('payment_id')->nullable()->constrained()->nullOnDelete();
            $table->json('payload')->nullable();
            $table->timestamps();

            $table->unique(['kind', 'receipt_number']);
            $table->unique(['kind', 'local_trxn_id']);
            $table->index(['kind', 'status']);
            $table->index(['student_id', 'created_at']);
            $table->index(['bill_ref_number', 'kind']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mpesa_c2b_transactions');
    }
};
