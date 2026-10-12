<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('hr_policies', function (Blueprint $table) {
            if (! Schema::hasColumn('hr_policies', 'target_type')) {
                $table->enum('target_type', ['all', 'department', 'staff', 'role'])
                    ->default('all')
                    ->after('tags');
            }
            if (! Schema::hasColumn('hr_policies', 'target_departments')) {
                $table->json('target_departments')->nullable()->after('target_type');
            }
            if (! Schema::hasColumn('hr_policies', 'target_staff_ids')) {
                $table->json('target_staff_ids')->nullable()->after('target_departments');
            }
            if (! Schema::hasColumn('hr_policies', 'target_roles')) {
                $table->json('target_roles')->nullable()->after('target_staff_ids');
            }
        });
    }

    public function down(): void
    {
        Schema::table('hr_policies', function (Blueprint $table) {
            $columns = ['target_type', 'target_departments', 'target_staff_ids', 'target_roles'];
            foreach ($columns as $col) {
                if (Schema::hasColumn('hr_policies', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};