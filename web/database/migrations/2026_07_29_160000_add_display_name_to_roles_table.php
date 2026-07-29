<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('roles', 'display_name')) {
            Schema::table('roles', function (Blueprint $table) {
                $table->string('display_name', 200)->nullable()->after('role_name');
            });

            DB::table('roles')->update([
                'display_name' => DB::raw('role_name'),
            ]);
        }

        if (! Schema::hasColumn('roles', 'updated_at')) {
            Schema::table('roles', function (Blueprint $table) {
                $table->dateTime('updated_at')->nullable()->useCurrentOnUpdate();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('roles', 'display_name')) {
            Schema::table('roles', function (Blueprint $table) {
                $table->dropColumn('display_name');
            });
        }

        if (Schema::hasColumn('roles', 'updated_at')) {
            Schema::table('roles', function (Blueprint $table) {
                $table->dropColumn('updated_at');
            });
        }
    }
};
