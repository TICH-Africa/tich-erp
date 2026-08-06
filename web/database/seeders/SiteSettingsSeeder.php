<?php

namespace Database\Seeders;

use App\Models\Site\SiteSetting;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;

class SiteSettingsSeeder extends Seeder
{
    public function run(): void
    {
        if (! Schema::hasTable('site_settings')) {
            return;
        }

        if (SiteSetting::query()->exists()) {
            return;
        }

        $site = config('tich-navigation.site', []);
        $now = now();

        $defaults = [
            [
                'setting_key' => 'site.institution_name',
                'setting_value' => $site['institution_name'] ?? 'Tropical Institute of Community Health and Development in Africa',
                'group_name' => 'identity',
                'label' => 'Institution name',
            ],
            [
                'setting_key' => 'site.short_name',
                'setting_value' => $site['short_name'] ?? 'TICH in Africa',
                'group_name' => 'identity',
                'label' => 'Short name',
            ],
            [
                'setting_key' => 'site.brand_name',
                'setting_value' => $site['short_name'] ?? 'TICH ERP',
                'group_name' => 'identity',
                'label' => 'Navbar brand name',
            ],
            [
                'setting_key' => 'site.brand_tagline',
                'setting_value' => $site['tagline'] ?? 'Community health platform',
                'group_name' => 'identity',
                'label' => 'Navbar tagline',
            ],
            [
                'setting_key' => 'site.tagline',
                'setting_value' => $site['tagline'] ?? 'Community health education for Africa',
                'group_name' => 'identity',
                'label' => 'Site tagline',
            ],
            [
                'setting_key' => 'site.copyright',
                'setting_value' => $site['copyright'] ?? ($site['institution_name'] ?? 'TICH in Africa'),
                'group_name' => 'identity',
                'label' => 'Copyright',
            ],
            [
                'setting_key' => 'site.website',
                'setting_value' => $site['website'] ?? 'tich.africa',
                'group_name' => 'identity',
                'label' => 'Website',
            ],
        ];

        foreach ($defaults as $row) {
            SiteSetting::query()->create([
                ...$row,
                'value_type' => 'string',
                'is_public' => 1,
                'is_active' => 1,
                'updated_at' => $now,
            ]);
        }
    }
}
