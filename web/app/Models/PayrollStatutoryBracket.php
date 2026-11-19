<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PayrollStatutoryBracket extends Model
{
    public const SCHEME_NSSF = 'nssf';

    public const SCHEME_SHIF = 'shif';

    public const SCHEME_AHL = 'ahl';

    protected $fillable = [
        'scheme',
        'label',
        'min_amount',
        'max_amount',
        'calculation',
        'rate_percent',
        'fixed_amount',
        'cap_amount',
        'display_order',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'min_amount' => 'decimal:2',
            'max_amount' => 'decimal:2',
            'rate_percent' => 'decimal:4',
            'fixed_amount' => 'decimal:2',
            'cap_amount' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    public function scopeForScheme($query, string $scheme)
    {
        return $query->where('scheme', $scheme)->orderBy('display_order')->orderBy('min_amount');
    }

    /**
     * @return list<array<string, mixed>>
     */
    public static function defaultRowsForScheme(string $scheme): array
    {
        return match ($scheme) {
            self::SCHEME_NSSF => [
                [
                    'label' => 'Tier I (pensionable up to KES 9,000)',
                    'min_amount' => 0,
                    'max_amount' => 9000,
                    'calculation' => 'percent_capped',
                    'rate_percent' => 6,
                    'fixed_amount' => null,
                    'cap_amount' => 540,
                    'display_order' => 1,
                ],
                [
                    'label' => 'Tier II (KES 9,001 – 108,000)',
                    'min_amount' => 9001,
                    'max_amount' => 108000,
                    'calculation' => 'percent_capped',
                    'rate_percent' => 6,
                    'fixed_amount' => null,
                    'cap_amount' => 5940,
                    'display_order' => 2,
                ],
            ],
            self::SCHEME_SHIF => [
                [
                    'label' => 'Minimum contribution (low gross)',
                    'min_amount' => 0,
                    'max_amount' => 10909,
                    'calculation' => 'fixed',
                    'rate_percent' => null,
                    'fixed_amount' => 300,
                    'cap_amount' => null,
                    'display_order' => 1,
                ],
                [
                    'label' => 'Percentage of gross',
                    'min_amount' => 10910,
                    'max_amount' => null,
                    'calculation' => 'percent_floor',
                    'rate_percent' => 2.75,
                    'fixed_amount' => 300,
                    'cap_amount' => null,
                    'display_order' => 2,
                ],
            ],
            self::SCHEME_AHL => [
                [
                    'label' => 'All gross cash pay',
                    'min_amount' => 0,
                    'max_amount' => null,
                    'calculation' => 'percent',
                    'rate_percent' => 1.5,
                    'fixed_amount' => null,
                    'cap_amount' => null,
                    'display_order' => 1,
                ],
            ],
            default => [],
        };
    }
}