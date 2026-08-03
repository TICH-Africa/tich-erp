<?php

namespace App\Services;

use App\Models\PayrollStatutoryRate;
use App\Models\PayrollTaxBand;
use Illuminate\Support\Collection;

class KenyaPayrollTaxService
{
    /**
     * @param  array{allowances?: float, other_deductions?: float, employee_name?: string, employee_number?: string}  $options
     * @return array<string, mixed>
     */
    public function calculateFromGross(float $gross, array $options = []): array
    {
        $allowances = max(0, (float) ($options['allowances'] ?? 0));
        $otherDeductions = max(0, (float) ($options['other_deductions'] ?? 0));
        $grossTotal = round($gross + $allowances, 2);

        $bands = PayrollTaxBand::activeOrdered()->get();
        $rates = PayrollStatutoryRate::activeOrdered()->get()->keyBy('code');

        $nssf = $this->calculateNssf($grossTotal, $rates);
        $sha = $this->calculateStatutoryLine($grossTotal, $rates->get('sha'));
        $ahl = $this->calculateStatutoryLine($grossTotal, $rates->get('ahl'));
        $pension = $this->calculateStatutoryLine($grossTotal, $rates->get('pension_employee'));

        $taxableIncome = max(0, $grossTotal - $nssf['employee_total'] - $pension['employee']);
        $bandBreakdown = $this->calculatePayeBandBreakdown($taxableIncome, $bands);
        $payeBeforeRelief = $bandBreakdown['total'];
        $personalRelief = (float) ($rates->get('personal_relief')?->fixed_amount ?? 2400);
        $paye = max(0, round($payeBeforeRelief - $personalRelief, 2));

        $employeeDeductions = collect([
            $this->deductionRow('paye', 'PAYE (KRA)', $paye, $payeBeforeRelief, $personalRelief),
            $this->deductionRow('nssf_tier1', 'NSSF Tier I', $nssf['tier1']['employee'], $nssf['tier1']['base'], $nssf['tier1']['rate']),
            $this->deductionRow('nssf_tier2', 'NSSF Tier II', $nssf['tier2']['employee'], $nssf['tier2']['base'], $nssf['tier2']['rate']),
            $this->deductionRow('sha', 'SHA / SHIF', $sha['employee'], $sha['base'], $sha['rate']),
            $this->deductionRow('ahl', 'Affordable Housing Levy', $ahl['employee'], $ahl['base'], $ahl['rate']),
            $this->deductionRow('pension_employee', 'Pension (employee)', $pension['employee'], $pension['base'], $pension['rate']),
        ])->filter(fn (array $row) => $row['amount'] > 0 || in_array($row['code'], ['paye', 'nssf_tier1', 'sha', 'ahl'], true));

        if ($otherDeductions > 0) {
            $employeeDeductions->push([
                'code' => 'other',
                'label' => 'Other deductions',
                'amount' => round($otherDeductions, 2),
                'base' => null,
                'rate' => null,
            ]);
        }

        $totalDeductions = round($employeeDeductions->sum('amount'), 2);
        $netSalary = round($grossTotal - $totalDeductions, 2);

        $employerContributions = collect([
            ['code' => 'nssf_tier1', 'label' => 'NSSF Tier I (employer)', 'amount' => $nssf['tier1']['employer']],
            ['code' => 'nssf_tier2', 'label' => 'NSSF Tier II (employer)', 'amount' => $nssf['tier2']['employer']],
            ['code' => 'ahl', 'label' => 'AHL (employer)', 'amount' => $ahl['employer']],
            ['code' => 'pension_employer', 'label' => 'Pension (employer)', 'amount' => $pension['employer']],
        ])->filter(fn (array $row) => $row['amount'] > 0)->values();

        return [
            'mode' => 'gross',
            'employee_name' => $options['employee_name'] ?? null,
            'employee_number' => $options['employee_number'] ?? null,
            'basic_salary' => round($gross, 2),
            'allowances' => $allowances,
            'gross_salary' => $grossTotal,
            'taxable_income' => round($taxableIncome, 2),
            'paye_before_relief' => round($payeBeforeRelief, 2),
            'personal_relief' => round($personalRelief, 2),
            'paye' => $paye,
            'net_salary' => $netSalary,
            'total_deductions' => $totalDeductions,
            'total_employer_cost' => round($grossTotal + $employerContributions->sum('amount'), 2),
            'deductions' => $employeeDeductions->values()->all(),
            'employer_contributions' => $employerContributions->all(),
            'band_breakdown' => $bandBreakdown['lines'],
            'bands' => $bands->map(fn (PayrollTaxBand $band) => [
                'label' => $band->label,
                'min_amount' => (float) $band->min_amount,
                'max_amount' => $band->max_amount !== null ? (float) $band->max_amount : null,
                'rate_percent' => (float) $band->rate_percent,
            ])->all(),
            'statutory_rates' => $rates->map(fn (PayrollStatutoryRate $rate) => [
                'code' => $rate->code,
                'label' => $rate->label,
                'rate_percent' => $rate->rate_percent !== null ? (float) $rate->rate_percent : null,
                'employer_rate_percent' => $rate->employer_rate_percent !== null ? (float) $rate->employer_rate_percent : null,
                'fixed_amount' => $rate->fixed_amount !== null ? (float) $rate->fixed_amount : null,
                'ceiling_amount' => $rate->ceiling_amount !== null ? (float) $rate->ceiling_amount : null,
            ])->values()->all(),
        ];
    }

    /**
     * @param  array{allowances?: float, other_deductions?: float, employee_name?: string, employee_number?: string}  $options
     * @return array<string, mixed>
     */
    public function calculateFromNet(float $targetNet, array $options = []): array
    {
        $targetNet = max(0, $targetNet);
        $low = $targetNet;
        $high = max($targetNet * 2.5, $targetNet + 50000);
        $best = null;

        for ($iteration = 0; $iteration < 80; $iteration++) {
            $mid = ($low + $high) / 2;
            $result = $this->calculateFromGross($mid, $options);
            $diff = $result['net_salary'] - $targetNet;

            if ($best === null || abs($diff) < abs($best['net_salary'] - $targetNet)) {
                $best = $result;
            }

            if (abs($diff) < 0.01) {
                break;
            }

            if ($diff < 0) {
                $low = $mid;
            } else {
                $high = $mid;
            }
        }

        $best['mode'] = 'net';
        $best['target_net'] = round($targetNet, 2);
        $best['computed_gross'] = $best['basic_salary'];

        return $best;
    }

    /**
     * @return array{tier1: array{employee: float, employer: float, base: float, rate: float}, tier2: array{employee: float, employer: float, base: float, rate: float}, employee_total: float, employer_total: float}
     */
    private function calculateNssf(float $gross, Collection $rates): array
    {
        $tier1 = $this->calculateTierStatutory($gross, $rates->get('nssf_tier1'));
        $tier2 = $this->calculateTierStatutory($gross, $rates->get('nssf_tier2'));

        return [
            'tier1' => $tier1,
            'tier2' => $tier2,
            'employee_total' => round($tier1['employee'] + $tier2['employee'], 2),
            'employer_total' => round($tier1['employer'] + $tier2['employer'], 2),
        ];
    }

    /**
     * @return array{employee: float, employer: float, base: float, rate: float}
     */
    private function calculateTierStatutory(float $gross, ?PayrollStatutoryRate $rate): array
    {
        if (! $rate || ! $rate->rate_percent) {
            return ['employee' => 0, 'employer' => 0, 'base' => 0, 'rate' => 0];
        }

        $base = $this->resolveBaseAmount($gross, $rate);
        $employeeRate = (float) $rate->rate_percent;
        $employerRate = (float) ($rate->employer_rate_percent ?? $rate->rate_percent);

        return [
            'employee' => round($base * $employeeRate / 100, 2),
            'employer' => round($base * $employerRate / 100, 2),
            'base' => round($base, 2),
            'rate' => $employeeRate,
        ];
    }

    /**
     * @return array{employee: float, employer: float, base: float, rate: float}
     */
    private function calculateStatutoryLine(float $gross, ?PayrollStatutoryRate $rate): array
    {
        if (! $rate) {
            return ['employee' => 0, 'employer' => 0, 'base' => 0, 'rate' => 0];
        }

        if ($rate->fixed_amount !== null) {
            return [
                'employee' => round((float) $rate->fixed_amount, 2),
                'employer' => 0,
                'base' => round($gross, 2),
                'rate' => 0,
            ];
        }

        return $this->calculateTierStatutory($gross, $rate);
    }

    private function resolveBaseAmount(float $gross, PayrollStatutoryRate $rate): float
    {
        $base = $gross;

        if ($rate->floor_amount !== null) {
            $base = max(0, $base - (float) $rate->floor_amount);
        }

        if ($rate->ceiling_amount !== null) {
            $cap = (float) $rate->ceiling_amount;

            if ($rate->floor_amount !== null) {
                $cap = max(0, $cap - (float) $rate->floor_amount);
            }

            $base = min($base, $cap);
        }

        return max(0, $base);
    }

    /**
     * @return array{total: float, lines: list<array<string, mixed>>}
     */
    private function calculatePayeBandBreakdown(float $taxableIncome, Collection $bands): array
    {
        $remaining = $taxableIncome;
        $total = 0;
        $lines = [];

        foreach ($bands as $band) {
            if ($remaining <= 0) {
                break;
            }

            $bandMin = (float) $band->min_amount;
            $bandMax = $band->max_amount !== null ? (float) $band->max_amount : null;
            $bandWidth = $bandMax !== null ? max(0, $bandMax - $bandMin) : $remaining;
            $taxableInBand = $bandMax !== null ? min($remaining, $bandWidth) : $remaining;

            if ($taxableInBand <= 0) {
                continue;
            }

            $tax = round($taxableInBand * ((float) $band->rate_percent / 100), 2);
            $total += $tax;
            $remaining -= $taxableInBand;

            $lines[] = [
                'label' => $band->label,
                'taxable_amount' => round($taxableInBand, 2),
                'rate_percent' => (float) $band->rate_percent,
                'tax' => $tax,
            ];
        }

        return ['total' => round($total, 2), 'lines' => $lines];
    }

    /**
     * @return array{code: string, label: string, amount: float, base: ?float, rate: ?float, relief?: float}
     */
    private function deductionRow(string $code, string $label, float $amount, ?float $base = null, ?float $rate = null, ?float $relief = null): array
    {
        $row = [
            'code' => $code,
            'label' => $label,
            'amount' => round($amount, 2),
            'base' => $base !== null ? round($base, 2) : null,
            'rate' => $rate,
        ];

        if ($relief !== null) {
            $row['relief'] = round($relief, 2);
        }

        return $row;
    }
}
