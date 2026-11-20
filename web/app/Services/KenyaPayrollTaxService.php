<?php

namespace App\Services;

use App\Models\PayrollDeductionType;
use App\Models\PayrollTaxBand;
use Illuminate\Support\Collection;

class KenyaPayrollTaxService
{
    public function __construct(
        protected KenyaStatutoryPayrollCalculator $statutoryCalculator,
    ) {}
    /**
     * @param  array{allowances?: float, other_deductions?: float, employee_name?: string, employee_number?: string, payroll_scheme?: string}  $options
     * @return array<string, mixed>
     */
    public function calculateForStaff(\App\Models\Staff $staff, ?float $grossOverride = null, array $options = []): array
    {
        $gross = $grossOverride ?? (float) $staff->gross_monthly_salary;

        $options = array_merge([
            'employee_name' => $staff->fullName(),
            'employee_number' => $staff->employee_number,
            'payroll_scheme' => $staff->resolvedPayrollScheme(),
        ], $options);

        if (($options['payroll_scheme'] ?? 'employee') === 'unpaid') {
            return $this->calculateUnpaidVolunteer($options);
        }

        if (($options['payroll_scheme'] ?? 'employee') === 'stipend') {
            return $this->calculateStipendFromGross($gross, $options);
        }

        if (($options['payroll_scheme'] ?? 'employee') === 'withholding') {
            return $this->calculateWithholdingFromGross($gross, $options);
        }

        return $this->calculateFromGross($gross, $options);
    }

    /**
     * @param  array{allowances?: float, other_deductions?: float, employee_name?: string, employee_number?: string}  $options
     * @return array<string, mixed>
     */
    /**
     * @param  array{employee_name?: string, employee_number?: string}  $options
     * @return array<string, mixed>
     */
    public function calculateUnpaidVolunteer(array $options = []): array
    {
        return [
            'mode' => 'unpaid',
            'payroll_scheme' => 'unpaid',
            'employee_name' => $options['employee_name'] ?? null,
            'employee_number' => $options['employee_number'] ?? null,
            'basic_salary' => 0.0,
            'allowances' => 0.0,
            'gross_salary' => 0.0,
            'taxable_income' => 0.0,
            'paye_before_relief' => 0.0,
            'personal_relief' => 0.0,
            'paye' => 0.0,
            'net_salary' => 0.0,
            'total_deductions' => 0.0,
            'total_employer_cost' => 0.0,
            'deductions' => [],
            'employer_contributions' => [],
            'band_breakdown' => [],
            'bands' => [],
            'deduction_types' => [],
        ];
    }

    /**
     * Intern / stipend: gross pay with no PAYE or statutory deductions.
     *
     * @param  array{allowances?: float, other_deductions?: float, employee_name?: string, employee_number?: string}  $options
     * @return array<string, mixed>
     */
    public function calculateStipendFromGross(float $gross, array $options = []): array
    {
        $allowances = max(0, (float) ($options['allowances'] ?? 0));
        $otherDeductions = max(0, (float) ($options['other_deductions'] ?? 0));
        $grossTotal = round($gross + $allowances, 2);

        $employeeDeductions = collect();

        if ($otherDeductions > 0) {
            $employeeDeductions->push([
                'code' => 'other',
                'label' => (string) ($options['other_deductions_label'] ?? 'Other deductions'),
                'amount' => round($otherDeductions, 2),
                'base' => null,
                'rate' => null,
            ]);
        }

        $totalDeductions = round($employeeDeductions->sum('amount'), 2);
        $netSalary = round($grossTotal - $totalDeductions, 2);

        return [
            'mode' => 'stipend',
            'payroll_scheme' => 'stipend',
            'employee_name' => $options['employee_name'] ?? null,
            'employee_number' => $options['employee_number'] ?? null,
            'basic_salary' => round($gross, 2),
            'allowances' => $allowances,
            'gross_salary' => $grossTotal,
            'taxable_income' => 0.0,
            'paye_before_relief' => 0.0,
            'personal_relief' => 0.0,
            'paye' => 0.0,
            'net_salary' => $netSalary,
            'total_deductions' => $totalDeductions,
            'total_employer_cost' => $grossTotal,
            'deductions' => $employeeDeductions->values()->all(),
            'employer_contributions' => [],
            'band_breakdown' => [],
            'bands' => [],
            'deduction_types' => [],
        ];
    }

    public function calculateWithholdingFromGross(float $gross, array $options = []): array
    {
        $allowances = max(0, (float) ($options['allowances'] ?? 0));
        $otherDeductions = max(0, (float) ($options['other_deductions'] ?? 0));
        $grossTotal = round($gross + $allowances, 2);
        $rate = $this->withholdingTaxRate();
        $withholdingTax = round($grossTotal * $rate / 100, 2);

        $employeeDeductions = collect([
            $this->deductionRow('withholding_tax', 'Withholding tax (WHT)', $withholdingTax, $grossTotal, $rate),
        ]);

        if ($otherDeductions > 0) {
            $employeeDeductions->push([
                'code' => 'other',
                'label' => (string) ($options['other_deductions_label'] ?? 'Other deductions'),
                'amount' => round($otherDeductions, 2),
                'base' => null,
                'rate' => null,
            ]);
        }

        $totalDeductions = round($employeeDeductions->sum('amount'), 2);
        $netSalary = round($grossTotal - $totalDeductions, 2);

        return [
            'mode' => 'withholding',
            'payroll_scheme' => 'withholding',
            'employee_name' => $options['employee_name'] ?? null,
            'employee_number' => $options['employee_number'] ?? null,
            'basic_salary' => round($gross, 2),
            'allowances' => $allowances,
            'gross_salary' => $grossTotal,
            'taxable_income' => $grossTotal,
            'withholding_rate' => $rate,
            'withholding_tax' => $withholdingTax,
            'paye_before_relief' => 0.0,
            'personal_relief' => 0.0,
            'paye' => 0.0,
            'net_salary' => $netSalary,
            'total_deductions' => $totalDeductions,
            'total_employer_cost' => $grossTotal,
            'deductions' => $employeeDeductions->values()->all(),
            'employer_contributions' => [],
            'band_breakdown' => [],
            'bands' => [],
            'deduction_types' => [],
        ];
    }

    public function withholdingTaxRate(): float
    {
        $configured = PayrollDeductionType::query()
            ->where('code', 'withholding_tax')
            ->where('is_active', 1)
            ->value('fixed_amount');

        if ($configured !== null) {
            return max(0, (float) $configured);
        }

        return (float) config('tich-payroll.default_withholding_rate', 5);
    }

    /**
     * @param  array{allowances?: float, other_deductions?: float, employee_name?: string, employee_number?: string, payroll_scheme?: string}  $options
     * @return array<string, mixed>
     */
    public function calculateFromGross(float $gross, array $options = []): array
    {
        if (($options['payroll_scheme'] ?? 'employee') === 'stipend') {
            return $this->calculateStipendFromGross($gross, $options);
        }

        if (($options['payroll_scheme'] ?? 'employee') === 'withholding') {
            return $this->calculateWithholdingFromGross($gross, $options);
        }

        $allowances = max(0, (float) ($options['allowances'] ?? 0));
        $otherDeductions = max(0, (float) ($options['other_deductions'] ?? 0));
        $grossTotal = round($gross + $allowances, 2);

        $bands = PayrollTaxBand::activeOrdered()
            ->with(['deductionRates.deductionType'])
            ->get();

        $deductionTypes = PayrollDeductionType::activeOrdered()->get();
        $globalFixedTypes = $deductionTypes->filter(fn (PayrollDeductionType $type) => $type->isGlobalFixed());

        $pensionable = max(0, (float) ($options['pensionable'] ?? $grossTotal));
        $statutory = $this->statutoryCalculator->employeeStatutoryBundle($grossTotal, [
            'pensionable' => $pensionable,
        ]);

        $typeLabels = $deductionTypes->keyBy('code');
        $statutoryTotals = [
            'nssf' => [
                'label' => $typeLabels->get('nssf')?->label ?? 'NSSF',
                'amount' => $statutory['nssf']['amount'],
                'employer' => 0.0,
                'detail' => $statutory['nssf'],
            ],
            'sha' => [
                'label' => $typeLabels->get('sha')?->label ?? 'SHA / SHIF',
                'amount' => $statutory['shif']['amount'],
                'employer' => 0.0,
                'detail' => $statutory['shif'],
            ],
            'ahl' => [
                'label' => $typeLabels->get('ahl')?->label ?? 'Affordable Housing Levy (AHL)',
                'amount' => $statutory['ahl']['amount'],
                'employer' => 0.0,
                'detail' => $statutory['ahl'],
            ],
        ];

        $taxableIncome = max(0, round($grossTotal - $statutory['total_reduces_taxable'], 2));
        $bandBreakdown = $this->calculatePayeBandBreakdown($taxableIncome, $bands);

        $payeBeforeRelief = $bandBreakdown['total'];
        $personalRelief = (float) ($globalFixedTypes->firstWhere('code', 'personal_relief')?->fixed_amount ?? 2400);
        $paye = max(0, round($payeBeforeRelief - $personalRelief, 2));

        $employeeDeductions = collect();

        foreach (['nssf', 'sha', 'ahl'] as $code) {
            $totals = $statutoryTotals[$code];
            $base = $code === 'nssf'
                ? (float) ($totals['detail']['pensionable'] ?? $grossTotal)
                : $grossTotal;
            $rate = (float) ($totals['detail']['rate_percent'] ?? 0);

            $employeeDeductions->push($this->deductionRow(
                $code,
                $totals['label'],
                $totals['amount'],
                $base,
                $rate > 0 ? $rate : $this->effectiveRate($totals['amount'], $base),
            ));
        }

        $employeeDeductions->push(
            $this->deductionRow('paye', 'PAYE (KRA)', $paye, $taxableIncome, null, $personalRelief),
        );

        if ($otherDeductions > 0) {
            $employeeDeductions->push([
                'code' => 'other',
                'label' => (string) ($options['other_deductions_label'] ?? 'Other deductions'),
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
            'payroll_scheme' => 'employee',
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
            'statutory_breakdown' => [
                'nssf' => $statutory['nssf'],
                'shif' => $statutory['shif'],
                'ahl' => $statutory['ahl'],
            ],
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
        if (($options['payroll_scheme'] ?? 'employee') === 'stipend') {
            $result = $this->calculateStipendFromGross($targetNet, $options);
            $result['mode'] = 'net';
            $result['target_net'] = round($targetNet, 2);
            $result['computed_gross'] = $result['basic_salary'];

            return $result;
        }

        if (($options['payroll_scheme'] ?? 'employee') === 'withholding') {
            $rate = $this->withholdingTaxRate();
            $factor = max(0.01, 1 - ($rate / 100));
            $gross = round($targetNet / $factor, 2);
            $result = $this->calculateWithholdingFromGross($gross, $options);
            $result['mode'] = 'net';
            $result['target_net'] = round($targetNet, 2);
            $result['computed_gross'] = $result['basic_salary'];

            return $result;
        }

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
