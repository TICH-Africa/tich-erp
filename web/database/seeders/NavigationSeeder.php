<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class NavigationSeeder extends Seeder
{
    public function run(): void
    {
        if (! Schema::hasTable('navigation_menus') || DB::table('navigation_menus')->exists()) {
            return;
        }

        $headerId = DB::table('navigation_menus')->insertGetId([
            'menu_name' => 'primary_header',
            'display_label' => 'Primary Header',
            'location' => 'header',
            'is_active' => 1,
            'display_order' => 1,
            'created_at' => now(),
        ]);

        $footerId = DB::table('navigation_menus')->insertGetId([
            'menu_name' => 'footer_primary',
            'display_label' => 'Footer Primary',
            'location' => 'footer_primary',
            'is_active' => 1,
            'display_order' => 1,
            'created_at' => now(),
        ]);

        $quickId = DB::table('navigation_menus')->insertGetId([
            'menu_name' => 'footer_quick_links',
            'display_label' => 'Footer Quick Links',
            'location' => 'footer_quick_links',
            'is_active' => 1,
            'display_order' => 2,
            'created_at' => now(),
        ]);

        $headerItems = config('tich-navigation.header', []);
        foreach ($headerItems as $order => $item) {
            DB::table('navigation_menu_items')->insert([
                'menu_id' => $headerId,
                'label' => $item['label'],
                'url_or_route' => $item['url'] ?? '/',
                'display_order' => $order + 1,
                'is_active' => 1,
                'created_at' => now(),
            ]);
        }

        foreach (config('tich-navigation.footer_primary', []) as $order => $item) {
            DB::table('navigation_menu_items')->insert([
                'menu_id' => $footerId,
                'label' => $item['label'],
                'url_or_route' => $item['url'] ?? '/',
                'display_order' => $order + 1,
                'is_active' => 1,
                'created_at' => now(),
            ]);
        }

        foreach (config('tich-navigation.footer_quick_links', []) as $order => $item) {
            DB::table('navigation_menu_items')->insert([
                'menu_id' => $quickId,
                'label' => $item['label'],
                'url_or_route' => $item['url'] ?? 'route:login',
                'display_order' => $order + 1,
                'is_active' => 1,
                'created_at' => now(),
            ]);
        }

        if (Schema::hasTable('social_links') && ! DB::table('social_links')->exists()) {
            foreach (config('tich-navigation.social', []) as $order => $social) {
                DB::table('social_links')->insert([
                    'platform' => $social['platform'],
                    'display_name' => $social['display_name'],
                    'url' => $social['url'],
                    'icon_name' => $social['icon_name'] ?? $social['platform'],
                    'display_order' => $order + 1,
                    'is_active' => 1,
                    'created_at' => now(),
                ]);
            }
        }

        if (Schema::hasTable('contact_channels') && ! DB::table('contact_channels')->exists()) {
            foreach (config('tich-navigation.contact', []) as $order => $contact) {
                DB::table('contact_channels')->insert([
                    'channel_type' => $contact['channel_type'],
                    'label' => $contact['label'],
                    'value' => $contact['value'] ?? $contact['display_value'],
                    'display_value' => $contact['display_value'],
                    'is_primary' => $order === 0 ? 1 : 0,
                    'display_order' => $order + 1,
                    'is_active' => 1,
                    'created_at' => now(),
                ]);
            }
        }
    }
}
