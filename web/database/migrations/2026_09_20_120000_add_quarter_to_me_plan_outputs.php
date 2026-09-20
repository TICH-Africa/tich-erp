<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('me_plan_outputs')) {
            return;
        }

        Schema::table('me_plan_outputs', function (Blueprint $table) {
            if (! Schema::hasColumn('me_plan_outputs', 'quarter')) {
                $table->unsignedTinyInteger('quarter')->nullable()->after('planned_unit');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('me_plan_outputs') || ! Schema::hasColumn('me_plan_outputs', 'quarter')) {
            return;
        }

        Schema::table('me_plan_outputs', function (Blueprint $table) {
            $table->dropColumn('quarter');
        });
    }
};
