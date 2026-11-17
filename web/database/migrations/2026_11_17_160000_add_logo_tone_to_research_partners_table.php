<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('research_partners') && ! Schema::hasColumn('research_partners', 'bg_color')) {
            DB::statement('ALTER TABLE research_partners ADD COLUMN bg_color CHAR(7) NULL AFTER logo_path');
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('research_partners') && Schema::hasColumn('research_partners', 'bg_color')) {
            DB::statement('ALTER TABLE research_partners DROP COLUMN bg_color');
        }
    }
};
