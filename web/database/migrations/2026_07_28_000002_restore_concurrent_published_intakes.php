<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (! \Illuminate\Support\Facades\Schema::hasTable('curriculum_versions')) {
            return;
        }

        // Intakes were incorrectly superseded when another cohort was published.
        // Restore any that had been published so cohorts can run concurrently.
        DB::table('curriculum_versions')
            ->where('status', 'superseded')
            ->whereNotNull('published_at')
            ->update([
                'status' => 'published',
                'updated_at' => now(),
            ]);
    }

    public function down(): void
    {
        // Irreversible data fix.
    }
};
