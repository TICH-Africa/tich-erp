<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('roles') || ! Schema::hasColumn('roles', 'role_category')) {
            return;
        }

        if (DB::getDriverName() === 'sqlite') {
            Schema::table('roles', function (Blueprint $table) {
                $table->string('role_category', 50)->nullable(false)->change();
            });
        } else {
            DB::statement('ALTER TABLE roles MODIFY role_category VARCHAR(50) NOT NULL');
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('roles') || ! Schema::hasColumn('roles', 'role_category')) {
            return;
        }

        if (DB::getDriverName() === 'sqlite') {
            Schema::table('roles', function (Blueprint $table) {
                $table->string('role_category', 50)->nullable(true)->change();
            });
        } else {
            DB::statement("ALTER TABLE roles MODIFY role_category ENUM('executive', 'academic', 'administrative', 'teaching', 'student') NOT NULL");
        }
    }
};
