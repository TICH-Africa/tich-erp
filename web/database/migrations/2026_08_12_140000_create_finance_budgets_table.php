<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('finance_budgets')) {
            return;
        }

        Schema::create('finance_budgets', function (Blueprint $table) {
            $table->id();
            $table->string('budget_code', 50)->unique();
            $table->string('budget_name', 300);
            $table->string('budget_type', 50)->default('departmental');
            $table->unsignedBigInteger('department_id')->nullable();
            $table->unsignedSmallInteger('fiscal_year');
            $table->date('period_start');
            $table->date('period_end');
            $table->decimal('allocated_amount', 14, 2)->default(0);
            $table->decimal('spent_amount', 14, 2)->default(0);
            $table->decimal('committed_amount', 14, 2)->default(0);
            $table->string('status', 50)->default('draft');
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->dateTime('approved_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->foreign('department_id')->references('id')->on('departments')->nullOnDelete();
            $table->foreign('approved_by')->references('id')->on('staff')->nullOnDelete();
            $table->index(['fiscal_year', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('finance_budgets');
    }
};
