<?php

namespace App\Services;

/**
 * Kenya employee statutory deductions on monthly gross (NSSF tiers, SHA/SHIF, AHL).
 */
class KenyaStatutoryPayrollCalculator
{
    /**
     * @return array{amount: float, tier1: float, tier2: float, pensionable: float, rate_percent: float}
     */
    public function employeeNssf(float $pensionableMonthly): array
    {
        $pensionable = max(0, round($pensionableMonthly, 2));
        $cfg = config('tich-payroll.kenya_statutory.nssf', []);
        $rate = ((float) ($cfg['rate_percent'] ?? 6)) / 100;
        $tier1Ceiling = (float) ($cfg['tier1_ceiling'] ?? 9000);
        $tier2Ceiling = (float) ($cfg['tier2_ceiling'] ?? 108000);
        $tier1Max = (float) ($cfg['tier1_max_employee'] ?? 540);
        $tier2Max = (float) ($cfg['tier2_max_employee'] ?? 5940);
        $overallMax = (float) ($cfg['max_employee'] ?? 6480);

        $tier1Base = min($pensionable, $tier1Ceiling);
        $tier1 = min(round($tier1Base * $rate, 2), $tier1Max);

        if ($pensionable <= $tier1Ceiling) {
            return [
                'amount' => $tier1,
                'tier1' => $tier1,
                'tier2' => 0.0,
                'pensionable' => $pensionable,
                'rate_percent' => (float) ($cfg['rate_percent'] ?? 6),
            ];
        }

        $tier2Base = min($pensionable, $tier2Ceiling) - $tier1Ceiling;
        $tier2 = min(round(max(0, $tier2Base) * $rate, 2), $tier2Max);
        $amount = min(round($tier1 + $tier2, 2), $overallMax);

        return [
            'amount' => $amount,
            'tier1' => $tier1,
            'tier2' => $tier2,
            'pensionable' => $pensionable,
            'rate_percent' => (float) ($cfg['rate_percent'] ?? 6),
        ];
    }

    /**
     * @return array{amount: float, rate_percent: float, gross: float, used_floor: bool}
     */
    public function employeeShif(float $monthlyGross): array
    {
        $gross = max(0, round($monthlyGross, 2));
        $cfg = config('tich-payroll.kenya_statutory.shif', []);
        $rate = ((float) ($cfg['rate_percent'] ?? 2.75)) / 100;
        $floor = (float) ($cfg['monthly_floor'] ?? 300);
        $floorCapGross = (float) ($cfg['floor_applies_up_to_gross'] ?? 10909);

        if ($gross <= $floorCapGross) {
            return [
                'amount' => $floor,
                'rate_percent' => (float) ($cfg['rate_percent'] ?? 2.75),
                'gross' => $gross,
                'used_floor' => true,
            ];
        }

        $percentAmount = round($gross * $rate, 2);

        return [
            'amount' => max($floor, $percentAmount),
            'rate_percent' => (float) ($cfg['rate_percent'] ?? 2.75),
            'gross' => $gross,
            'used_floor' => $percentAmount < $floor,
        ];
    }

    /**
     * @return array{amount: float, rate_percent: float, gross: float}
     */
    public function employeeAhl(float $monthlyGrossCash): array
    {
        $gross = max(0, round($monthlyGrossCash, 2));
        $cfg = config('tich-payroll.kenya_statutory.ahl', []);
        $rate = ((float) ($cfg['rate_percent'] ?? 1.5)) / 100;

        return [
            'amount' => round($gross * $rate, 2),
            'rate_percent' => (float) ($cfg['rate_percent'] ?? 1.5),
            'gross' => $gross,
        ];
    }

    /**
     * @param  array{pensionable?: float}  $options
     * @return array{
     *     nssf: array<string, mixed>,
     *     shif: array<string, mixed>,
     *     ahl: array<string, mixed>,
     *     total_reduces_taxable: float
     * }
     */
    public function employeeStatutoryBundle(float $monthlyGross, array $options = []): array
    {
        $pensionable = max(0, (float) ($options['pensionable'] ?? $monthlyGross));

        $nssf = $this->employeeNssf($pensionable);
        $shif = $this->employeeShif($monthlyGross);
        $ahl = $this->employeeAhl($monthlyGross);

        return [
            'nssf' => $nssf,
            'shif' => $shif,
            'ahl' => $ahl,
            'total_reduces_taxable' => round($nssf['amount'] + $shif['amount'] + $ahl['amount'], 2),
        ];
    }
}