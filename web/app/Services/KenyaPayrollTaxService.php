<?php

namespace App\Services;

use App\Models\PayrollDeductionType;
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

        $bands = PayrollTaxBand::activeOrdered()
            ->with(['deductionRates.deductionType'])
            ->get();

        $deductionTypes = PayrollDeductionType::activeOrdered()->get();
        $bandPercentTypes = $deductionTypes->filter(fn (PayrollDeductionType $type) => $type->isBandPercent());
        $globalFixedTypes = $deductionTypes->filter(fn (PayrollDeductionType $type) => $type->isGlobalFixed());

        $grossSlices = $this->sliceAmountAcrossBands($grossTotal, $bands);
        $statutoryTotals = [];
        $employerTotals = [];

        foreach ($bandPercentTypes as $type) {
            $statutoryTotals[$type->code] = ['label' => $type->label, 'amount' => 0.0, 'employer' => 0.0];
        }

        foreach ($bands as $band) {
            $slice = $grossSlices[$band->id] ?? 0;

            if ($slice <= 0) {
                continue;
            }

            foreach ($band->deductionRates as $bandRate) {
                $type = $bandRate->deductionType;

                if (! $type || ! $type->is_active || ! $type->isBandPercent()) {
                    continue;
                }

                $rate = (float) ($bandRate->rate_percent ?? 0);

                if ($rate <= 0) {
                    continue;
                }

                $employeeAmount = round($slice * $rate / 100, 2);
                $employerRate = (float) ($type->employer_rate_percent ?? 0);
                $employerAmount = $employerRate > 0 ? round($slice * $employerRate / 100, 2) : 0;

                $statutoryTotals[$type->code]['amount'] += $employeeAmount;
                $statutoryTotals[$type->code]['employer'] += $employerAmount;
            }
        }

        $taxableReduction = 0;

        foreach ($bandPercentTypes as $type) {
            if ($type->reduces_taxable) {
                $taxableReduction += $statutoryTotals[$type->code]['amount'] ?? 0;
            }
        }

        $taxableIncome = max(0, round($grossTotal - $taxableReduction, 2));
        $bandBreakdown = $this->calculatePayeBandBreakdown($taxableIncome, $bands);

        $payeBeforeRelief = $bandBreakdown['total'];
        $personalRelief = (float) ($globalFixedTypes->firstWhere('code', 'personal_relief')?->fixed_amount ?? 2400);
        $paye = max(0, round($payeBeforeRelief - $personalRelief, 2));

        $employeeDeductions = collect([
            $this->deductionRow('paye', 'PAYE (KRA)', $paye, $taxableIncome, null, $personalRelief),
        ]);

        foreach ($statutoryTotals as $code => $totals) {
            if ($totals['amount'] <= 0 && ! in_array($code, ['nssf', 'sha', 'ahl'], true)) {
                continue;
            }

            $employeeDeductions->push($this->deductionRow(
                $code,
                $totals['label'],
                $totals['amount'],
                $grossTotal,
                $this->effectiveRate($totals['amount'], $grossTotal),
            ));
        }

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

        $employerContributions = collect($statutoryTotals)
            ->filter(fn (array $totals) => $totals['employer'] > 0)
            ->map(fn (array $totals, string $code) => [
                'code' => $code,
                'label' => $totals['label'].' (employer)',
                'amount' => round($totals['employer'], 2),
            ])
            ->values();

        return [
            'mode' => 'gross',
            'employee_name' => $options['employee_name'] ?? null,
            'employee_number' => $options['employee_number'] ?? null,
            'basic_salary' => round($gross, 2),
            'allowances' => $allowances,
            'gross_salary' => $grossTotal,
            'taxable_income' => $taxableIncome,
            'paye_before_relief' => round($payeBeforeRelief, 2),
            'personal_relief' => round($personalRelief, 2),
            'paye' => $paye,
            'net_salary' => $netSalary,
            'total_deductions' => $totalDeductions,
            'total_employer_cost' => round($grossTotal + $employerContributions->sum('amount'), 2),
            'deductions' => $employeeDeductions->values()->all(),
            'employer_contributions' => $employerContributions->all(),
            'band_breakdown' => $bandBreakdown['lines'],
            'bands' => $this->formatBandsForOutput($bands),
            'deduction_types' => $deductionTypes->map(fn (PayrollDeductionType $type) => [
                'code' => $type->code,
                'label' => $type->label,
                'value_type' => $type->value_type,
                'fixed_amount' => $type->fixed_amount !== null ? (float) $type->fixed_amount : null,
                'employer_rate_percent' => $type->employer_rate_percent !== null ? (float) $type->employer_rate_percent : null,
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
     * @return array<int, float> band_id => slice amount
     */
    private function sliceAmountAcrossBands(float $amount, Collection $bands): array
    {
        $slices = [];
        $remaining = $amount;

        foreach ($bands as $band) {
            if ($remaining <= 0) {
                $slices[$band->id] = 0;

                continue;
            }

            $bandMin = (float) $band->min_amount;
            $bandMax = $band->max_amount !== null ? (float) $band->max_amount : null;
            $bandWidth = $bandMax !== null ? max(0, $bandMax - $bandMin) : $remaining;
            $slice = $bandMax !== null ? min($remaining, $bandWidth) : $remaining;

            $slices[$band->id] = round(max(0, $slice), 2);
            $remaining -= $slice;
        }

        return $slices;
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
     * @return list<array<string, mixed>>
     */
    private function formatBandsForOutput(Collection $bands): array
    {
        return $bands->map(function (PayrollTaxBand $band) {
            $deductions = [];

            foreach ($band->deductionRates as $rate) {
                if ($rate->deductionType) {
                    $deductions[$rate->deductionType->code] = $rate->rate_percent !== null
                        ? (float) $rate->rate_percent
                        : null;
                }
            }

            return [
                'label' => $band->label,
                'min_amount' => (float) $band->min_amount,
                'max_amount' => $band->max_amount !== null ? (float) $band->max_amount : null,
                'rate_percent' => (float) $band->rate_percent,
                'deductions' => $deductions,
            ];
        })->all();
    }

    private function effectiveRate(float $amount, float $base): ?float
    {
        if ($base <= 0) {
            return null;
        }

        return round($amount / $base * 100, 2);
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
