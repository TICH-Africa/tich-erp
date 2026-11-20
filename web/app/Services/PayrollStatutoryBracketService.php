<?php

namespace App\Services;

use App\Models\PayrollKenyaStatutorySetting;
use App\Models\PayrollStatutoryBracket;
use Illuminate\Support\Facades\Schema;

class PayrollStatutoryBracketService
{
    public function ensureConfigured(): void
    {
        if (! Schema::hasTable('payroll_statutory_brackets')) {
            return;
        }

        $this->migrateFromLegacySettingsIfEmpty();
        $this->ensureDefaults();
    }

    public function migrateFromLegacySettingsIfEmpty(): void
    {
        if (! Schema::hasTable('payroll_statutory_brackets')) {
            return;
        }

        if (PayrollStatutoryBracket::query()->exists()) {
            return;
        }

        if (! Schema::hasTable('payroll_kenya_statutory_settings')) {
            return;
        }

        $row = PayrollKenyaStatutorySetting::current();
        $rate = (float) $row->nssf_rate_percent;

        PayrollStatutoryBracket::query()->create([
            'scheme' => PayrollStatutoryBracket::SCHEME_NSSF,
            'label' => 'Tier I (pensionable up to KES '.number_format((float) $row->nssf_tier1_ceiling, 0).')',
            'min_amount' => 0,
            'max_amount' => $row->nssf_tier1_ceiling,
            'calculation' => 'percent_capped',
            'rate_percent' => $rate,
            'cap_amount' => $row->nssf_tier1_max_employee,
            'display_order' => 1,
            'is_active' => true,
        ]);

        PayrollStatutoryBracket::query()->create([
            'scheme' => PayrollStatutoryBracket::SCHEME_NSSF,
            'label' => 'Tier II (up to KES '.number_format((float) $row->nssf_tier2_ceiling, 0).' pensionable)',
            'min_amount' => (float) $row->nssf_tier1_ceiling + 0.01,
            'max_amount' => $row->nssf_tier2_ceiling,
            'calculation' => 'percent_capped',
            'rate_percent' => $rate,
            'cap_amount' => $row->nssf_tier2_max_employee,
            'display_order' => 2,
            'is_active' => true,
        ]);

        PayrollStatutoryBracket::query()->create([
            'scheme' => PayrollStatutoryBracket::SCHEME_SHIF,
            'label' => 'Minimum contribution (low gross)',
            'min_amount' => 0,
            'max_amount' => $row->shif_floor_applies_up_to_gross,
            'calculation' => 'fixed',
            'fixed_amount' => $row->shif_monthly_floor,
            'display_order' => 1,
            'is_active' => true,
        ]);

        PayrollStatutoryBracket::query()->create([
            'scheme' => PayrollStatutoryBracket::SCHEME_SHIF,
            'label' => 'Percentage of gross',
            'min_amount' => (float) $row->shif_floor_applies_up_to_gross + 0.01,
            'max_amount' => null,
            'calculation' => 'percent_floor',
            'rate_percent' => $row->shif_rate_percent,
            'fixed_amount' => $row->shif_monthly_floor,
            'display_order' => 2,
            'is_active' => true,
        ]);

        PayrollStatutoryBracket::query()->create([
            'scheme' => PayrollStatutoryBracket::SCHEME_AHL,
            'label' => 'All gross cash pay',
            'min_amount' => 0,
            'max_amount' => null,
            'calculation' => 'percent',
            'rate_percent' => $row->ahl_rate_percent,
            'display_order' => 1,
            'is_active' => true,
        ]);
    }

    public function ensureDefaults(): void
    {
        if (! Schema::hasTable('payroll_statutory_brackets')) {
            return;
        }

        foreach ([
            PayrollStatutoryBracket::SCHEME_NSSF,
            PayrollStatutoryBracket::SCHEME_SHIF,
            PayrollStatutoryBracket::SCHEME_AHL,
        ] as $scheme) {
            if (PayrollStatutoryBracket::query()->where('scheme', $scheme)->exists()) {
                continue;
            }

            foreach (PayrollStatutoryBracket::defaultRowsForScheme($scheme) as $row) {
                PayrollStatutoryBracket::query()->create([
                    ...$row,
                    'scheme' => $scheme,
                    'is_active' => true,
                ]);
            }
        }
    }

    /**
     * @param  array<string, array<int, array<string, mixed>>>  $grouped
     */
    public function saveGrouped(array $grouped): void
    {
        if (! Schema::hasTable('payroll_statutory_brackets')) {
            return;
        }

        foreach ([
            PayrollStatutoryBracket::SCHEME_NSSF,
            PayrollStatutoryBracket::SCHEME_SHIF,
            PayrollStatutoryBracket::SCHEME_AHL,
        ] as $scheme) {
            $rows = $grouped[$scheme] ?? [];
            $keptIds = [];

            foreach (array_values($rows) as $index => $row) {
                if (empty($row['label'])) {
                    continue;
                }

                $payload = [
                    'scheme' => $scheme,
                    'label' => $row['label'],
                    'min_amount' => round((float) ($row['min_amount'] ?? 0), 2),
                    'max_amount' => ($row['max_amount'] ?? '') === '' || $row['max_amount'] === null
                        ? null
                        : round((float) $row['max_amount'], 2),
                    'calculation' => $row['calculation'] ?? 'percent',
                    'rate_percent' => ($row['rate_percent'] ?? '') === '' || $row['rate_percent'] === null
                        ? null
                        : round((float) $row['rate_percent'], 4),
                    'fixed_amount' => ($row['fixed_amount'] ?? '') === '' || $row['fixed_amount'] === null
                        ? null
                        : round((float) $row['fixed_amount'], 2),
                    'cap_amount' => ($row['cap_amount'] ?? '') === '' || $row['cap_amount'] === null
                        ? null
                        : round((float) $row['cap_amount'], 2),
                    'display_order' => $index,
                    'is_active' => in_array($row['is_active'] ?? false, [true, 1, '1', 'on'], true),
                ];

                if (! empty($row['id'])) {
                    PayrollStatutoryBracket::query()->whereKey($row['id'])->update($payload);
                    $keptIds[] = (int) $row['id'];
                } else {
                    $keptIds[] = PayrollStatutoryBracket::query()->create($payload)->id;
                }
            }

            if ($keptIds !== []) {
                PayrollStatutoryBracket::query()
                    ->where('scheme', $scheme)
                    ->whereNotIn('id', $keptIds)
                    ->delete();
            }
        }
    }
}