<?php

namespace App\Services;

use App\Services\Holidays\AladhanKenyaIslamicHolidayProvider;
use App\Services\Holidays\NagerDateHolidayProvider;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class KenyaPublicHolidayService
{
    public function __construct(
        protected NagerDateHolidayProvider $nager,
        protected AladhanKenyaIslamicHolidayProvider $aladhan,
    ) {}

    /**
     * @return list<array{month: int, day: int, name: string}>
     */
    public function fixedHolidayDefinitions(): array
    {
        return config('tich-kenya-public-holidays.holidays', []);
    }

    /**
     * @return list<array{date: string, name: string, source: string, type: string}>
     */
    public function resolveHolidaysForYear(int $year): array
    {
        $provider = (string) config('tich-kenya-public-holidays.provider', 'api');

        if ($provider === 'config') {
            return $this->holidaysFromConfigForYear($year);
        }

        $merged = [];
        $country = (string) config('tich-kenya-public-holidays.nager.country_code', 'KE');

        if (in_array($provider, ['api', 'hybrid'], true)) {
            $merged = array_merge(
                $merged,
                $this->nager->holidaysForYear($year, $country),
                $this->aladhan->holidaysForYear($year),
            );
        }

        if ($merged === [] && config('tich-kenya-public-holidays.fallback_to_config', true)) {
            $merged = $this->holidaysFromConfigForYear($year);
        }

        return $this->dedupeByDate($merged);
    }

    public function syncYear(int $year): int
    {
        if (! Schema::hasTable('public_holidays')) {
            return 0;
        }

        $rows = $this->resolveHolidaysForYear($year);
        $now = now();
        $dates = [];
        $synced = 0;

        foreach ($rows as $row) {
            $date = (string) ($row['date'] ?? '');
            $name = trim((string) ($row['name'] ?? ''));
            if ($date === '' || $name === '') {
                continue;
            }

            $dates[] = $date;
            $source = (string) ($row['source'] ?? 'config');
            $type = (string) ($row['type'] ?? 'national');

            $payload = [
                'holiday_name' => $name,
                'holiday_type' => $type,
                'description' => $this->descriptionForSource($source),
                'is_active' => 1,
                'updated_at' => $now,
            ];

            if (Schema::hasColumn('public_holidays', 'source')) {
                $payload['source'] = $source;
            }

            if (DB::table('public_holidays')->where('holiday_date', $date)->exists()) {
                DB::table('public_holidays')->where('holiday_date', $date)->update($payload);
            } else {
                DB::table('public_holidays')->insert(array_merge($payload, [
                    'holiday_date' => $date,
                    'created_at' => $now,
                ]));
            }

            $synced++;
        }

        $this->pruneStaleApiHolidaysForYear($year, $dates);

        return $synced;
    }

    public function syncRange(int $fromYear, int $toYear): int
    {
        $fromYear = min($fromYear, $toYear);
        $toYear = max($fromYear, $toYear);
        $total = 0;

        for ($year = $fromYear; $year <= $toYear; $year++) {
            $total += $this->syncYear($year);
        }

        return $total;
    }

    public function syncConfiguredWindow(): int
    {
        $current = (int) now()->year;
        $behind = (int) config('tich-kenya-public-holidays.sync_years_behind', 2);
        $ahead = (int) config('tich-kenya-public-holidays.sync_years_ahead', 5);

        return $this->syncRange($current - $behind, $current + $ahead);
    }

    /**
     * @return Collection<int, object{holiday_date: string, holiday_name: string, holiday_type: ?string}>
     */
    public function between(Carbon $from, Carbon $to): Collection
    {
        if (! Schema::hasTable('public_holidays')) {
            return collect();
        }

        return DB::table('public_holidays')
            ->where('is_active', 1)
            ->whereBetween('holiday_date', [$from->toDateString(), $to->toDateString()])
            ->orderBy('holiday_date')
            ->get(['holiday_date', 'holiday_name', 'holiday_type']);
    }

    /**
     * @return array<int, Collection<int, object>> Keys 1–12 (month number).
     */
    public function byMonthForYear(int $year): array
    {
        $this->syncYear($year);

        $grouped = [];
        for ($month = 1; $month <= 12; $month++) {
            $grouped[$month] = collect();
        }

        $from = Carbon::createFromDate($year, 1, 1)->startOfDay();
        $to = Carbon::createFromDate($year, 12, 31)->endOfDay();

        foreach ($this->between($from, $to) as $holiday) {
            $month = (int) Carbon::parse($holiday->holiday_date)->month;
            $grouped[$month]->push($holiday);
        }

        return $grouped;
    }

    /**
     * @return list<int>
     */
    public function selectableYears(): array
    {
        $current = (int) now()->year;
        $behind = (int) config('tich-kenya-public-holidays.sync_years_behind', 2);
        $ahead = (int) config('tich-kenya-public-holidays.sync_years_ahead', 5);

        return range($current - $behind, $current + $ahead);
    }

    /**
     * @return list<array{date: string, name: string, source: string, type: string}>
     */
    private function holidaysFromConfigForYear(int $year): array
    {
        $rows = [];

        foreach ($this->fixedHolidayDefinitions() as $def) {
            $month = (int) ($def['month'] ?? 0);
            $day = (int) ($def['day'] ?? 0);
            $name = trim((string) ($def['name'] ?? ''));

            if ($month < 1 || $month > 12 || $day < 1 || $day > 31 || $name === '') {
                continue;
            }

            try {
                $date = Carbon::createFromDate($year, $month, $day)->toDateString();
            } catch (\Throwable) {
                continue;
            }

            $rows[] = [
                'date' => $date,
                'name' => $name,
                'source' => 'config',
                'type' => 'national',
            ];
        }

        return $rows;
    }

    /**
     * @param  list<array{date: string, name: string, source: string, type: string}>  $rows
     * @return list<array{date: string, name: string, source: string, type: string}>
     */
    private function dedupeByDate(array $rows): array
    {
        $byDate = [];
        foreach ($rows as $row) {
            $byDate[$row['date']] = $row;
        }

        ksort($byDate);

        return array_values($byDate);
    }

    /**
     * @param  list<string>  $keepDates
     */
    private function pruneStaleApiHolidaysForYear(int $year, array $keepDates): void
    {
        if (! Schema::hasColumn('public_holidays', 'source')) {
            return;
        }

        $query = DB::table('public_holidays')
            ->whereIn('source', ['nager', 'aladhan'])
            ->whereYear('holiday_date', $year);

        if ($keepDates !== []) {
            $query->whereNotIn('holiday_date', $keepDates);
        }

        $query->delete();
    }

    private function descriptionForSource(string $source): string
    {
        return match ($source) {
            'nager' => 'Synced from Nager.Date (Kenya public holidays).',
            'aladhan' => 'Idd dates from Aladhan (calculation method in config; confirm official gazette if required).',
            'config' => 'Fixed-date fallback list in application config.',
            default => 'Kenyan public holiday (auto-maintained).',
        };
    }
}
