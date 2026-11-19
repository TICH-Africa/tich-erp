<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PayrollKenyaStatutorySetting extends Model
{
    protected $fillable = [
        'nssf_rate_percent',
        'nssf_tier1_ceiling',
        'nssf_tier1_max_employee',
        'nssf_tier2_ceiling',
        'nssf_tier2_max_employee',
        'nssf_max_employee',
        'shif_rate_percent',
        'shif_monthly_floor',
        'shif_floor_applies_up_to_gross',
        'ahl_rate_percent',
    ];

    protected function casts(): array
    {
        return [
            'nssf_rate_percent' => 'decimal:4',
            'nssf_tier1_ceiling' => 'decimal:2',
            'nssf_tier1_max_employee' => 'decimal:2',
            'nssf_tier2_ceiling' => 'decimal:2',
            'nssf_tier2_max_employee' => 'decimal:2',
            'nssf_max_employee' => 'decimal:2',
            'shif_rate_percent' => 'decimal:4',
            'shif_monthly_floor' => 'decimal:2',
            'shif_floor_applies_up_to_gross' => 'decimal:2',
            'ahl_rate_percent' => 'decimal:4',
        ];
    }

    public static function current(): self
    {
        $row = static::query()->first();

        if ($row !== null) {
            return $row;
        }

        return static::query()->create(static::defaultsFromConfig());
    }

    /**
     * @return array<string, mixed>
     */
    public static function defaultsFromConfig(): array
    {
        $cfg = config('tich-payroll.kenya_statutory', []);

        return [
            'nssf_rate_percent' => $cfg['nssf']['rate_percent'] ?? 6,
            'nssf_tier1_ceiling' => $cfg['nssf']['tier1_ceiling'] ?? 9000,
            'nssf_tier1_max_employee' => $cfg['nssf']['tier1_max_employee'] ?? 540,
            'nssf_tier2_ceiling' => $cfg['nssf']['tier2_ceiling'] ?? 108000,
            'nssf_tier2_max_employee' => $cfg['nssf']['tier2_max_employee'] ?? 5940,
            'nssf_max_employee' => $cfg['nssf']['max_employee'] ?? 6480,
            'shif_rate_percent' => $cfg['shif']['rate_percent'] ?? 2.75,
            'shif_monthly_floor' => $cfg['shif']['monthly_floor'] ?? 300,
            'shif_floor_applies_up_to_gross' => $cfg['shif']['floor_applies_up_to_gross'] ?? 10909,
            'ahl_rate_percent' => $cfg['ahl']['rate_percent'] ?? 1.5,
        ];
    }

    /**
     * Shape expected by KenyaStatutoryPayrollCalculator.
     *
     * @return array{nssf: array<string, float>, shif: array<string, float>, ahl: array<string, float>}
     */
    public function toCalculatorConfig(): array
    {
        return [
            'nssf' => [
                'rate_percent' => (float) $this->nssf_rate_percent,
                'tier1_ceiling' => (float) $this->nssf_tier1_ceiling,
                'tier1_max_employee' => (float) $this->nssf_tier1_max_employee,
                'tier2_ceiling' => (float) $this->nssf_tier2_ceiling,
                'tier2_max_employee' => (float) $this->nssf_tier2_max_employee,
                'max_employee' => (float) $this->nssf_max_employee,
            ],
            'shif' => [
                'rate_percent' => (float) $this->shif_rate_percent,
                'monthly_floor' => (float) $this->shif_monthly_floor,
                'floor_applies_up_to_gross' => (float) $this->shif_floor_applies_up_to_gross,
            ],
            'ahl' => [
                'rate_percent' => (float) $this->ahl_rate_percent,
            ],
        ];
    }
}