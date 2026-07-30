<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('roles') || ! Schema::hasColumn('roles', 'role_category')) {
            return;
        }

        DB::statement('ALTER TABLE roles MODIFY role_category VARCHAR(50) NOT NULL');
    }

    public function down(): void
    {
        if (! Schema::hasTable('roles') || ! Schema::hasColumn('roles', 'role_category')) {
            return;
        }

        DB::statement("ALTER TABLE roles MODIFY role_category ENUM('executive', 'academic', 'administrative', 'teaching', 'student') NOT NULL");
    }
};
