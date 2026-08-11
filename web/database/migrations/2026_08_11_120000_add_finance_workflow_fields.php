<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('fee_structures', function (Blueprint $table) {
            $table->string('status', 50)->default('draft')->after('is_active');
            $table->string('version', 20)->nullable()->after('status');
            $table->text('notes')->nullable()->after('version');
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->string('status', 50)->default('INITIATED')->after('payment_reference');
            $table->string('mpesa_receipt_number', 50)->nullable()->unique()->after('status');
            $table->string('transaction_channel_ref', 100)->nullable()->after('mpesa_receipt_number');
        });

        Schema::table('receipts', function (Blueprint $table) {
            $table->string('receipt_number', 50)->unique()->after('id');
        });

        Schema::table('refunds', function (Blueprint $table) {
            $table->string('status', 50)->default('pending')->after('reason');
            $table->unsignedBigInteger('approved_by')->nullable()->after('requested_by');
            $table->dateTime('approved_at')->nullable()->after('approved_by');
            $table->unsignedBigInteger('processed_by')->nullable()->after('approved_at');
            $table->dateTime('processed_at')->nullable()->after('processed_by');
        });
    }

    public function down(): void
    {
        Schema::table('fee_structures', function (Blueprint $table) {
            $table->dropColumn(['notes', 'version', 'status']);
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->dropColumn(['transaction_channel_ref', 'mpesa_receipt_number', 'status']);
        });

        Schema::table('receipts', function (Blueprint $table) {
            $table->dropColumn(['receipt_number']);
        });

        Schema::table('refunds', function (Blueprint $table) {
            $table->dropColumn(['processed_at', 'processed_by', 'approved_at', 'approved_by', 'status']);
        });
    }
};
