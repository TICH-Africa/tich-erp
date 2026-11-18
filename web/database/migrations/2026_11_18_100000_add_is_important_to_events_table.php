<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('events') || Schema::hasColumn('events', 'is_important')) {
            return;
        }

        Schema::table('events', function (Blueprint $table) {
            $table->tinyInteger('is_important')->default(0)->after('is_featured');
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('events') || ! Schema::hasColumn('events', 'is_important')) {
            return;
        }

        Schema::table('events', function (Blueprint $table) {
            $table->dropColumn('is_important');
        });
    }
};
