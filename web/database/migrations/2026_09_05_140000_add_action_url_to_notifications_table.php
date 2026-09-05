<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('notifications')) {
            return;
        }

        Schema::table('notifications', function (Blueprint $table) {
            if (! Schema::hasColumn('notifications', 'action_url')) {
                $table->string('action_url', 500)->nullable()->after('related_entity_id');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('notifications') || ! Schema::hasColumn('notifications', 'action_url')) {
            return;
        }

        Schema::table('notifications', function (Blueprint $table) {
            $table->dropColumn('action_url');
        });
    }
};
