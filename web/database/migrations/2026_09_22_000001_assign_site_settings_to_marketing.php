<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (! DB::getSchemaBuilder()->hasTable('department_modules')) {
            return;
        }

        $marketing = DB::table('departments')->where('dept_code', 'MKT')->where('is_active', 1)->first();

        if (! $marketing) {
            return;
        }

        DB::table('department_modules')->updateOrInsert(
            [
                'department_id' => $marketing->id,
                'module_key' => 'site_settings',
            ],
            [
                'assigned_at' => now(),
                'assigned_by' => null,
            ]
        );
    }

    public function down(): void
    {
        if (! DB::getSchemaBuilder()->hasTable('department_modules')) {
            return;
        }

        $marketing = DB::table('departments')->where('dept_code', 'MKT')->where('is_active', 1)->first();

        if ($marketing) {
            DB::table('department_modules')
                ->where('department_id', $marketing->id)
                ->where('module_key', 'site_settings')
                ->delete();
        }
    }
};
