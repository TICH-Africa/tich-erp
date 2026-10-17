<?php

/**
 * Kenyan public holidays — synced from external APIs into public_holidays (cache for leave & calendars).
 *
 * Nager.Date: fixed + Christian movable (Good Friday, Easter Monday) for KE.
 * Aladhan: Idd-ul-Fitr & Idd-ul-Azha (calculation method configurable; confirm against gazette if needed).
 */
return [
    /** api = fetch providers; config = legacy fixed month/day only; hybrid = API + config fallback if API empty */
    'provider' => env('TICH_HOLIDAY_PROVIDER', 'api'),

    'nager' => [
        'enabled' => true,
        'base_url' => 'https://date.nager.at/api/v3',
        'country_code' => 'KE',
    ],

    'aladhan' => [
        'enabled' => true,
        'base_url' => 'https://api.aladhan.com/v1',
        /** @see https://aladhan.com/calculation-methods (2 = Muslim World League / common default) */
        'method' => (int) env('TICH_ALADHAN_METHOD', 2),
    ],

    'cache_ttl_hours' => 24,

    'fallback_to_config' => true,

    /** Legacy fixed-date fallback when APIs are unreachable. */
    'holidays' => [
        ['month' => 1, 'day' => 1, 'name' => "New Year's Day"],
        ['month' => 5, 'day' => 1, 'name' => 'Labour Day'],
        ['month' => 6, 'day' => 1, 'name' => 'Madaraka Day'],
        ['month' => 10, 'day' => 10, 'name' => 'Mazingira Day'],
        ['month' => 10, 'day' => 20, 'name' => 'Mashujaa Day'],
        ['month' => 12, 'day' => 12, 'name' => 'Jamhuri Day'],
        ['month' => 12, 'day' => 25, 'name' => 'Christmas Day'],
        ['month' => 12, 'day' => 26, 'name' => 'Boxing Day'],
    ],

    'sync_years_behind' => 2,

    'sync_years_ahead' => 5,

    /** HR/academics calendar year picker (independent of automatic sync window). */
    'calendar_years_behind' => (int) env('TICH_CALENDAR_YEARS_BEHIND', 2),

    'calendar_years_ahead' => (int) env('TICH_CALENDAR_YEARS_AHEAD', 30),
];
