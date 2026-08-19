<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('finance_budget_cycles')) {
            Schema::create('finance_budget_cycles', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('budget_id');
                $table->string('cycle_type', 50); // annual, quarterly, monthly, weekly
                $table->string('label', 200);
                $table->date('period_start');
                $table->date('period_end');
                $table->decimal('allocated_amount', 14, 2)->default(0);
                $table->decimal('spent_amount', 14, 2)->default(0);
                $table->decimal('committed_amount', 14, 2)->default(0);
                $table->string('status', 50)->default('active');
                $table->text('notes')->nullable();
                $table->timestamps();

                $table->foreign('budget_id')->references('id')->on('finance_budgets')->cascadeOnDelete();
                $table->index(['budget_id', 'cycle_type']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('finance_budget_cycles');
    }
};
