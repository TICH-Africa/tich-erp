<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('procurement_requisitions', function (Blueprint $table) {
            if (! Schema::hasColumn('procurement_requisitions', 'line_items')) {
                $table->json('line_items')->nullable()->after('requested_item');
            }
        });
    }

    public function down(): void
    {
        Schema::table('procurement_requisitions', function (Blueprint $table) {
            if (Schema::hasColumn('procurement_requisitions', 'line_items')) {
                $table->dropColumn('line_items');
            }
        });
    }
};
