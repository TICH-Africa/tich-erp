<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('grn_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('grn_id')->constrained('goods_received_notes')->cascadeOnDelete();
            $table->foreignId('purchase_order_id')->nullable()->constrained('purchase_orders')->nullOnDelete();
            $table->foreignId('supplier_id')->constrained('suppliers')->cascadeOnDelete();
            $table->string('item_name', 300);
            $table->string('item_description', 500)->nullable();
            $table->string('category', 50)->nullable();
            $table->string('classification', 50)->default('consumable');
            $table->decimal('quantity_ordered', 12, 2)->default(0.00);
            $table->decimal('quantity_received', 12, 2)->default(0.00);
            $table->decimal('unit_cost', 12, 2)->default(0.00);
            $table->decimal('total_cost', 12, 2)->default(0.00);
            $table->string('condition', 50)->default('good');
            $table->date('received_date')->nullable();
            $table->string('status', 50)->default('pending');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('grn_items');
    }
};
