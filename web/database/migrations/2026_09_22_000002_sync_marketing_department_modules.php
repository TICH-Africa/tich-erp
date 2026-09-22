<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (! DB::getSchemaBuilder()->hasTable('department_modules') || ! DB::getSchemaBuilder()->hasTable('departments')) {
            return;
        }

        $marketing = DB::table('departments')->where('dept_code', 'MKT')->where('is_active', 1)->first();

        if (! $marketing) {
            return;
        }

        $allowed = ['portal', 'site_settings'];

        DB::table('department_modules')
            ->where('department_id', $marketing->id)
            ->whereNotIn('module_key', $allowed)
            ->delete();

        foreach ($allowed as $moduleKey) {
            DB::table('department_modules')->updateOrInsert(
                [
                    'department_id' => $marketing->id,
                    'module_key' => $moduleKey,
                ],
                [
                    'assigned_at' => now(),
                    'assigned_by' => null,
                ]
            );
        }
    }

    public function down(): void
    {
        if (! DB::getSchemaBuilder()->hasTable('department_modules') || ! DB::getSchemaBuilder()->hasTable('departments')) {
            return;
        }

        $marketing = DB::table('departments')->where('dept_code', 'MKT')->where('is_active', 1)->first();

        if ($marketing) {
            DB::table('department_modules')
                ->where('department_id', $marketing->id)
                ->whereIn('module_key', ['portal', 'site_settings'])
                ->delete();
        }
    }
};
