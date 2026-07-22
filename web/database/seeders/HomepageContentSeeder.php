<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class HomepageContentSeeder extends Seeder
{
    public function run(): void
    {
        if (Schema::hasTable('homepage_carousel_slides') && ! DB::table('homepage_carousel_slides')->exists()) {
            foreach (config('tich-homepage.carousel', []) as $order => $slide) {
                DB::table('homepage_carousel_slides')->insert([
                    'title' => $slide['title'],
                    'subtitle' => $slide['subtitle'] ?? null,
                    'image_path' => $slide['image_path'] ?? null,
                    'cta_label' => $slide['cta_label'] ?? null,
                    'cta_url' => $slide['cta_url'] ?? null,
                    'display_order' => $order + 1,
                    'is_active' => 1,
                    'created_at' => now(),
                ]);
            }
        }

        if (Schema::hasTable('events') && ! DB::table('events')->exists()) {
            foreach (config('tich-homepage.events', []) as $event) {
                DB::table('events')->insert([
                    'title' => $event['title'],
                    'subtitle' => $event['subtitle'] ?? null,
                    'event_type' => $event['event_type'],
                    'description' => $event['subtitle'] ?? null,
                    'start_datetime' => $event['start_datetime'],
                    'end_datetime' => $event['start_datetime'],
                    'venue' => $event['venue'] ?? null,
                    'registration_url_or_form' => $event['registration_url_or_form'] ?? null,
                    'is_public' => 1,
                    'is_featured' => 1,
                    'created_at' => now(),
                ]);
            }
        }

        if (Schema::hasTable('blog_posts') && ! DB::table('blog_posts')->exists()) {
            foreach (config('tich-homepage.blog_posts', []) as $post) {
                DB::table('blog_posts')->insert([
                    'title' => $post['title'],
                    'slug' => $post['slug'],
                    'excerpt' => $post['excerpt'] ?? null,
                    'body' => '<p>'.($post['excerpt'] ?? '').'</p>',
                    'featured_image_path' => $post['featured_image_path'] ?? null,
                    'status' => 'published',
                    'published_at' => $post['published_at'],
                    'reading_time_minutes' => $post['reading_time_minutes'] ?? 3,
                    'created_at' => now(),
                ]);
            }
        }

        if (Schema::hasTable('research_projects') && ! DB::table('research_projects')->exists()) {
            $research = config('tich-homepage.research');
            if ($research) {
                DB::table('research_projects')->insert([
                    'title' => $research['title'],
                    'status' => $research['status'] ?? 'ongoing',
                    'summary' => $research['summary'],
                    'is_featured' => 1,
                    'created_at' => now(),
                ]);
            }
        }
    }
}
