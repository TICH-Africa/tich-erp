<?php

namespace App\Services\Holidays;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class NagerDateHolidayProvider
{
    /**
     * @return list<array{date: string, name: string, source: string, type: string}>
     */
    public function holidaysForYear(int $year, string $countryCode = 'KE'): array
    {
        if (! config('tich-kenya-public-holidays.nager.enabled', true)) {
            return [];
        }

        $baseUrl = rtrim((string) config('tich-kenya-public-holidays.nager.base_url', 'https://date.nager.at/api/v3'), '/');
        $ttlHours = (int) config('tich-kenya-public-holidays.cache_ttl_hours', 24);
        $cacheKey = "public_holidays.nager.{$countryCode}.{$year}";

        $payload = Cache::remember($cacheKey, now()->addHours(max(1, $ttlHours)), function () use ($baseUrl, $countryCode, $year) {
            $response = Http::timeout(15)
                ->retry(2, 500)
                ->get("{$baseUrl}/PublicHolidays/{$year}/{$countryCode}");

            if (! $response->successful()) {
                Log::warning('Nager.Date holiday fetch failed', [
                    'year' => $year,
                    'country' => $countryCode,
                    'status' => $response->status(),
                ]);

                return [];
            }

            return $response->json();
        });

        if (! is_array($payload)) {
            return [];
        }

        $holidays = [];
        foreach ($payload as $row) {
            if (! is_array($row)) {
                continue;
            }
            $date = (string) ($row['date'] ?? '');
            $name = trim((string) ($row['localName'] ?? $row['name'] ?? ''));
            if ($date === '' || $name === '') {
                continue;
            }

            $holidays[] = [
                'date' => $date,
                'name' => $name,
                'source' => 'nager',
                'type' => 'national',
            ];
        }

        return $holidays;
    }
}
