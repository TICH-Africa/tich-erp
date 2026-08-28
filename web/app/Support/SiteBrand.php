<?php

namespace App\Support;

use App\Services\SiteSettingsService;

class SiteBrand
{
    public static function shortName(): string
    {
        return (string) (app(SiteSettingsService::class)->siteMeta()['short_name'] ?? 'TICH in Africa');
    }

    public static function institutionName(): string
    {
        return (string) (app(SiteSettingsService::class)->siteMeta()['institution_name'] ?? self::shortName());
    }

    public static function mailFromName(): string
    {
        return self::shortName();
    }
}
