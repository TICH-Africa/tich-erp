<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('public_holidays')) {
            return;
        }

        Schema::table('public_holidays', function (Blueprint $table) {
            if (! Schema::hasColumn('public_holidays', 'source')) {
                $table->string('source', 32)->nullable()->after('holiday_type');
                $table->index('source');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('public_holidays')) {
            return;
        }

        Schema::table('public_holidays', function (Blueprint $table) {
            if (Schema::hasColumn('public_holidays', 'source')) {
                $table->dropIndex(['source']);
                $table->dropColumn('source');
            }
        });
    }
};
