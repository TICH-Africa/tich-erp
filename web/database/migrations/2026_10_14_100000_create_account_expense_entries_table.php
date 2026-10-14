<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('account_expense_entries')) {
            return;
        }

        Schema::create('account_expense_entries', function (Blueprint $table) {
            $table->id();
            $table->string('account_code', 30);
            $table->string('reference_no', 30);
            $table->date('expense_date');
            $table->string('payee_name', 200)->nullable();
            $table->string('address', 500)->nullable();
            $table->string('memo', 500)->nullable();
            $table->string('class', 100)->nullable();
            $table->string('currency', 3)->default('KES');
            $table->decimal('exchange_rate', 18, 6)->default(1);
            $table->decimal('amount', 18, 2)->default(0);
            $table->decimal('vat_amount', 18, 2)->default(0);
            $table->string('supplier_vat_code', 50)->nullable();
            $table->string('credit_account_code', 30)->nullable();
            $table->unsignedBigInteger('recorded_by')->nullable();
            $table->timestamps();

            $table->index(['account_code', 'expense_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('account_expense_entries');
    }
};
