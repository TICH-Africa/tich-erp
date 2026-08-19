<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('admin_budget_requests', function (Blueprint $table) {
            if (! Schema::hasColumn('admin_budget_requests', 'budget_type')) {
                $table->string('budget_type', 50)->nullable()->after('framework');
            }
            if (! Schema::hasColumn('admin_budget_requests', 'allocated_amount')) {
                $table->decimal('allocated_amount', 14, 2)->nullable()->after('approved_amount');
            }
            if (! Schema::hasColumn('admin_budget_requests', 'group_allocations')) {
                $table->json('group_allocations')->nullable()->after('allocated_amount');
            }
        });
    }

    public function down(): void
    {
        Schema::table('admin_budget_requests', function (Blueprint $table) {
            if (Schema::hasColumn('admin_budget_requests', 'group_allocations')) {
                $table->dropColumn(['group_allocations', 'allocated_amount', 'budget_type']);
            }
        });
    }
};
