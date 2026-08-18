<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('admin_budget_requests', function (Blueprint $table) {
            $table->json('standard_line_items')->nullable()->after('framework');
            $table->json('cbe_details')->nullable()->after('standard_line_items');
        });
    }

    public function down(): void
    {
        Schema::table('admin_budget_requests', function (Blueprint $table) {
            $table->dropColumn(['standard_line_items', 'cbe_details']);
        });
    }
};