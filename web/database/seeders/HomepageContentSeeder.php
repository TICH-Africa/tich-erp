<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;

/**
 * Homepage content is managed via CMS / academic records.
 * This seeder no longer inserts sample events, blog posts, or research.
 */
class HomepageContentSeeder extends Seeder
{
    public function run(): void
    {
        if (Schema::hasTable('academic_programs')) {
            app(\App\Services\ProgramCarouselSyncService::class)->syncAllFeatured();
        }
    }
}
