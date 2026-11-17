<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('research_partners')) {
            return;
        }

        $column = collect(DB::select('SHOW COLUMNS FROM research_partners'))
            ->keyBy('Field')
            ->get('name');

        // A partner can be posted as a logo only, so the name column has to accept NULL.
        if ($column && strtoupper($column->Null ?? '') === 'NO') {
            DB::statement('ALTER TABLE research_partners MODIFY name VARCHAR(300) NULL');
        }
    }

    public function down(): void
    {
        // Requiring the name again would drop the logo-only rows.
    }
};
