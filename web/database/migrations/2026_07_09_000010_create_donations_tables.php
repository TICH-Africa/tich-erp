<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('donation_campaigns', function (Blueprint $table) {
            $table->id();
            $table->string('campaign_code', 50)->unique();
            $table->string('title', 300);
            $table->text('description')->nullable();
            $table->string('fund_allocation', 50); // student_bursaries, facility_construction, work_study_tools, research, general
            $table->decimal('target_amount', 14, 2);
            $table->decimal('raised_amount', 14, 2)->default(0.00);
            $table->string('currency', 10)->default('KES');
            $table->string('cover_image_path', 500)->nullable();
            $table->string('mpesa_paybill_number', 50)->nullable();
            $table->string('mpesa_account_name', 200)->nullable();
            $table->string('bank_account_name', 200)->nullable();
            $table->string('bank_account_number', 50)->nullable();
            $table->string('bank_name', 200)->nullable();
            $table->string('swift_code', 50)->nullable();
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->tinyInteger('is_public')->default(1);
            $table->tinyInteger('is_featured')->default(0);
            $table->string('status', 50)->default('active'); // draft, active, closed, cancelled
            $table->dateTime('created_at')->useCurrent();
            $table->dateTime('updated_at')->nullable()->useCurrentOnUpdate();
        });

        Schema::create('donations', function (Blueprint $table) {
            $table->id();
            $table->string('donation_number', 50)->unique();
            $table->unsignedBigInteger('campaign_id')->nullable();
            $table->string('donor_name', 300)->nullable();
            $table->string('donor_email', 255)->nullable();
            $table->string('donor_phone', 30)->nullable();
            $table->string('donor_type', 50)->default('anonymous'); // individual, corporate, foundation, government, anonymous
            $table->decimal('amount', 14, 2);
            $table->string('currency', 10)->default('KES');
            $table->decimal('amount_KES', 14, 2);
            $table->decimal('exchange_rate_used', 12, 4)->default(1.0000);
            $table->string('payment_method', 50); // mpesa, bank_transfer, card, international_wire, cash
            $table->string('payment_reference', 100)->nullable();
            $table->tinyInteger('is_anonymous')->default(0);
            $table->tinyInteger('deductible_for_tax')->default(0);
            $table->tinyInteger('receipt_sent')->default(0);
            $table->string('receipt_reference', 100)->nullable();
            $table->tinyInteger('is_reconciled')->default(0);
            $table->unsignedBigInteger('reconciled_by')->nullable();
            $table->dateTime('reconciled_at')->nullable();
            $table->date('donation_date');
            $table->text('notes')->nullable();
            $table->dateTime('created_at')->useCurrent();
            $table->foreign('campaign_id')->references('id')->on('donation_campaigns')->nullOnDelete();
            $table->foreign('reconciled_by')->references('id')->on('staff')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('donations');
        Schema::dropIfExists('donation_campaigns');
    }
};
