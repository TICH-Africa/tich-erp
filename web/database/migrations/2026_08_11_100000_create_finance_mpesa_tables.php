<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('finance_mpesa_settings', function (Blueprint $table) {
            $table->id();
            $table->boolean('is_enabled')->default(false);
            $table->string('environment', 20)->default('sandbox');
            $table->string('shortcode', 20)->nullable();
            $table->text('passkey')->nullable();
            $table->string('consumer_key')->nullable();
            $table->text('consumer_secret')->nullable();
            $table->string('transaction_type', 50)->default('CustomerPayBillOnline');
            $table->string('account_reference_prefix', 30)->default('TICH');
            $table->string('callback_url_override', 500)->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
        });

        Schema::create('mpesa_stk_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_id')->constrained()->cascadeOnDelete();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->decimal('amount', 12, 2);
            $table->string('phone', 20);
            $table->string('account_reference', 50);
            $table->string('merchant_request_id')->nullable()->index();
            $table->string('checkout_request_id')->nullable()->unique();
            $table->string('status', 20)->default('pending');
            $table->integer('result_code')->nullable();
            $table->string('result_desc', 500)->nullable();
            $table->string('mpesa_receipt_number', 50)->nullable()->index();
            $table->foreignId('payment_id')->nullable()->constrained()->nullOnDelete();
            $table->json('callback_payload')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index(['invoice_id', 'status']);
            $table->index(['status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mpesa_stk_requests');
        Schema::dropIfExists('finance_mpesa_settings');
    }
};
