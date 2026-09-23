<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('three_way_matches')) {
            return;
        }

        Schema::create('three_way_matches', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('invoice_id');
            $table->unsignedBigInteger('purchase_order_id');
            $table->unsignedBigInteger('rfq_quotation_id')->nullable();
            $table->string('status', 50)->default('pending');
            $table->decimal('quantity_po', 12, 2)->default(0.00);
            $table->decimal('quantity_quotation', 12, 2)->default(0.00);
            $table->decimal('quantity_invoiced', 12, 2)->default(0.00);
            $table->decimal('unit_price_po', 12, 2)->default(0.00);
            $table->decimal('unit_price_quotation', 12, 2)->default(0.00);
            $table->decimal('unit_price_invoiced', 12, 2)->default(0.00);
            $table->decimal('quantity_tolerance_percent', 5, 2)->default(5.00);
            $table->boolean('price_match')->default(false);
            $table->boolean('description_match')->default(false);
            $table->boolean('quantity_match')->default(false);
            $table->boolean('arithmetic_match')->default(false);
            $table->boolean('delivery_match')->default(false);
            $table->decimal('total_calculated', 12, 2)->default(0.00);
            $table->decimal('total_invoiced', 12, 2)->default(0.00);
            $table->decimal('deviation_amount', 12, 2)->default(0.00);
            $table->integer('discrepancy_count')->default(0);
            $table->unsignedBigInteger('matched_by')->nullable();
            $table->dateTime('matched_at')->nullable();
            $table->text('matching_certificate')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->foreign('invoice_id')->references('id')->on('procurement_invoices')->restrictOnDelete();
            $table->foreign('purchase_order_id')->references('id')->on('purchase_orders')->restrictOnDelete();
            $table->foreign('rfq_quotation_id')->references('id')->on('rfq_quotations')->nullOnDelete();
            $table->foreign('matched_by')->references('id')->on('staff')->nullOnDelete();

            $table->index(['invoice_id', 'status']);
            $table->index(['status', 'deviation_amount']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('three_way_matches');
    }
};
