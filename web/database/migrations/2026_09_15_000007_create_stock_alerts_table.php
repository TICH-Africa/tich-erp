<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_alerts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inventory_item_id')->constrained('inventory_items')->cascadeOnDelete();
            $table->string('alert_type', 50)->default('low_stock');
            $table->integer('current_stock')->default(0);
            $table->integer('reorder_level')->default(0);
            $table->integer('recommended_quantity')->default(0);
            $table->timestamp('triggered_at')->useCurrent();
            $table->timestamp('closed_at')->nullable();
            $table->json('channels')->nullable();
            $table->json('sent_to')->nullable();
            $table->foreignId('requisition_id')->nullable()->constrained('procurement_requisitions')->nullOnDelete();
            $table->string('status', 50)->default('active');
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_alerts');
    }
};
