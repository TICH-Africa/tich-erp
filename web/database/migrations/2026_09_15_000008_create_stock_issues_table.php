<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_issues', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inventory_item_id')->constrained('inventory_items')->cascadeOnDelete();
            $table->foreignId('department_id')->constrained('departments')->cascadeOnDelete();
            $table->foreignId('requested_by')->constrained('staff')->cascadeOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('staff')->nullOnDelete();
            $table->decimal('quantity', 12, 2)->default(0.00);
            $table->decimal('unit_cost', 12, 2)->default(0.00);
            $table->decimal('total_cost', 12, 2)->default(0.00);
            $table->text('reason');
            $table->string('approval_status', 50)->default('pending');
            $table->string('status', 50)->default('pending');
            $table->date('approved_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_issues');
    }
};
