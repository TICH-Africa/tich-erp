<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('navigation_menus') || ! Schema::hasTable('navigation_menu_items')) {
            return;
        }

        $now = now();

        $headerMenu = DB::table('navigation_menus')->where('location', 'header')->where('is_active', 1)->first();

        if ($headerMenu) {
            $hasCareers = DB::table('navigation_menu_items')
                ->where('menu_id', $headerMenu->id)
                ->where('label', 'Careers')
                ->exists();

            if (! $hasCareers) {
                $maxOrder = (int) DB::table('navigation_menu_items')
                    ->where('menu_id', $headerMenu->id)
                    ->max('display_order');

                DB::table('navigation_menu_items')->insert([
                    'menu_id' => $headerMenu->id,
                    'label' => 'Careers',
                    'url_or_route' => '/careers',
                    'display_order' => $maxOrder + 1,
                    'is_active' => 1,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            } else {
                DB::table('navigation_menu_items')
                    ->where('menu_id', $headerMenu->id)
                    ->where('label', 'Careers')
                    ->update([
                        'url_or_route' => '/careers',
                        'is_active' => 1,
                        'updated_at' => $now,
                    ]);
            }
        }

        $quickMenu = DB::table('navigation_menus')->where('location', 'footer_quick_links')->where('is_active', 1)->first();

        if ($quickMenu) {
            DB::table('navigation_menu_items')
                ->where('menu_id', $quickMenu->id)
                ->where('label', 'Careers')
                ->update([
                    'url_or_route' => '/careers',
                    'updated_at' => $now,
                ]);
        }
    }

    public function down(): void
    {
        // Navigation labels are shared site config; leave items in place on rollback.
    }
};
