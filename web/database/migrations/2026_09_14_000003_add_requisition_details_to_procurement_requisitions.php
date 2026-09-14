<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('procurement_requisitions', function (Blueprint $table) {
            $table->string('requested_item', 300)->nullable()->after('justification');
            $table->json('attachments')->nullable()->after('requested_item');
            $table->string('budget_line', 100)->nullable()->after('budget_code');
            $table->decimal('estimated_unit_cost', 12, 2)->nullable()->after('estimated_cost');
            $table->decimal('quantity', 12, 2)->nullable()->after('estimated_unit_cost');
            $table->string('delivery_location', 500)->nullable()->after('delivery_required_by');
            $table->text('audit_trail')->nullable()->after('ceo_approved_at');
        });
    }

    public function down(): void
    {
        Schema::table('procurement_requisitions', function (Blueprint $table) {
            $table->dropColumn([
                'requested_item',
                'attachments',
                'budget_line',
                'estimated_unit_cost',
                'quantity',
                'delivery_location',
                'audit_trail',
            ]);
        });
    }
};
