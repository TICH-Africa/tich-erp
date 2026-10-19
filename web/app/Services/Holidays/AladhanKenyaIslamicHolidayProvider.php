<?php

namespace App\Services\Holidays;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AladhanKenyaIslamicHolidayProvider
{
    /**
     * Kenyan public holidays for Idd-ul-Fitr (1 Shawwāl) and Idd-ul-Azha (10 Dhū al-Ḥijjah).
     * Dates follow the configured calculation method; official gazette may differ by moon sighting.
     *
     * @return list<array{date: string, name: string, source: string, type: string}>
     */
    public function holidaysForYear(int $year): array
    {
        if (! config('tich-kenya-public-holidays.aladhan.enabled', true)) {
            return [];
        }

        $ttlHours = (int) config('tich-kenya-public-holidays.cache_ttl_hours', 24);
        $cacheKey = "public_holidays.aladhan.ke.{$year}";

        return Cache::remember($cacheKey, now()->addHours(max(1, $ttlHours)), function () use ($year) {
            return $this->fetchForYear($year);
        });
    }

    /**
     * @return list<array{date: string, name: string, source: string, type: string}>
     */
    private function fetchForYear(int $year): array
    {
        $baseUrl = rtrim((string) config('tich-kenya-public-holidays.aladhan.base_url', 'https://api.aladhan.com/v1'), '/');
        $method = (int) config('tich-kenya-public-holidays.aladhan.method', 2);
        $found = [];
        $fitrDate = null;
        $adhaDate = null;

        for ($month = 1; $month <= 12; $month++) {
            $response = Http::timeout(20)
                ->retry(2, 500)
                ->get("{$baseUrl}/gToHCalendar/{$month}/{$year}", ['method' => $method]);

            if (! $response->successful()) {
                Log::warning('Aladhan calendar fetch failed', [
                    'year' => $year,
                    'month' => $month,
                    'status' => $response->status(),
                ]);

                continue;
            }

            $days = $response->json('data');
            if (! is_array($days)) {
                continue;
            }

            foreach ($days as $day) {
                if (! is_array($day)) {
                    continue;
                }

                $hijri = is_array($day['hijri'] ?? null) ? $day['hijri'] : [];
                $gregorian = is_array($day['gregorian'] ?? null) ? $day['gregorian'] : [];
                $hMonth = (int) ($hijri['month']['number'] ?? 0);
                $hDay = (int) ($hijri['day'] ?? 0);
                $gYear = (int) ($gregorian['year'] ?? 0);
                $gMonth = (int) ($gregorian['month']['number'] ?? 0);
                $gDay = (int) ($gregorian['day'] ?? 0);

                if ($gYear !== $year || $gMonth < 1 || $gDay < 1) {
                    continue;
                }

                $date = sprintf('%04d-%02d-%02d', $gYear, $gMonth, $gDay);

                if ($hMonth === 10 && $hDay === 1 && $fitrDate === null) {
                    $fitrDate = $date;
                }

                if ($hMonth === 12 && $hDay === 10 && $adhaDate === null) {
                    $adhaDate = $date;
                }
            }
        }

        if ($fitrDate) {
            $found[] = [
                'date' => $fitrDate,
                'name' => 'Idd-ul-Fitr',
                'source' => 'aladhan',
                'type' => 'religious',
            ];
        }

        if ($adhaDate) {
            $found[] = [
                'date' => $adhaDate,
                'name' => 'Idd-ul-Azha',
                'source' => 'aladhan',
                'type' => 'religious',
            ];
        }

        return $found;
    }
}
