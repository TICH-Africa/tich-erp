<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * The colour an officer picks for a study paints that study's pages; it starts from the cover's own edge colour.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('research_projects') && ! Schema::hasColumn('research_projects', 'bg_color')) {
            DB::statement('ALTER TABLE research_projects ADD COLUMN bg_color CHAR(7) NULL AFTER cover_image_path');
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('research_projects') && Schema::hasColumn('research_projects', 'bg_color')) {
            DB::statement('ALTER TABLE research_projects DROP COLUMN bg_color');
        }
    }
};
