<?php

namespace App\Http\Controllers\HR;

use App\Http\Controllers\Controller;
use App\Models\PayrollBandDeductionRate;
use App\Models\PayrollDeductionType;
use App\Models\PayrollTaxBand;
use App\Models\Staff;
use App\Services\KenyaPayrollTaxService;
use App\Services\PrintDocumentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Str;
use Illuminate\View\View;

class PayrollController extends Controller
{
    public function __construct(
        protected KenyaPayrollTaxService $taxService,
        protected PrintDocumentService $printDocuments,
    ) {}

    public function index(Request $request): View
    {
        $staff = Staff::query()
            ->with('department')
            ->when($request->filled('search'), function ($query) use ($request) {
                $term = '%'.$request->string('search').'%';
                $query->where(function ($inner) use ($term) {
                    $inner->where('first_name', 'like', $term)
                        ->orWhere('surname', 'like', $term)
                        ->orWhere('employee_number', 'like', $term)
                        ->orWhere('organisation_email', 'like', $term);
                });
            })
            ->when($request->filled('status'), fn ($query) => $query->where('employment_status', $request->string('status')))
            ->orderBy('surname')
            ->orderBy('first_name')
            ->get();

        $rows = $staff->map(function (Staff $member) {
            $gross = (float) $member->gross_monthly_salary;

            if ($gross <= 0) {
                return [
                    'staff' => $member,
                    'breakdown' => null,
                ];
            }

            return [
                'staff' => $member,
                'breakdown' => $this->taxService->calculateFromGross($gross, [
                    'employee_name' => $member->fullName(),
                    'employee_number' => $member->employee_number,
                ]),
            ];
        });

        $totals = [
            'gross_salary' => 0.0,
            'paye' => 0.0,
            'nssf' => 0.0,
            'sha' => 0.0,
            'ahl' => 0.0,
            'total_deductions' => 0.0,
            'net_salary' => 0.0,
            'employer_cost' => 0.0,
        ];

        foreach ($rows as $row) {
            if (! $row['breakdown']) {
                continue;
            }

            $breakdown = $row['breakdown'];
            $totals['gross_salary'] += $breakdown['gross_salary'];
            $totals['paye'] += $this->deductionAmount($breakdown, 'paye') ?? 0;
            $totals['nssf'] += $this->deductionAmount($breakdown, 'nssf') ?? 0;
            $totals['sha'] += $this->deductionAmount($breakdown, 'sha') ?? 0;
            $totals['ahl'] += $this->deductionAmount($breakdown, 'ahl') ?? 0;
            $totals['total_deductions'] += $breakdown['total_deductions'];
            $totals['net_salary'] += $breakdown['net_salary'];
            $totals['employer_cost'] += $breakdown['total_employer_cost'];
        }

        return view('hr.payroll.index', [
            'rows' => $rows,
            'totals' => $totals,
        ]);
    }

    public function settings(): View
    {
        $deductionTypes = PayrollDeductionType::query()->orderBy('display_order')->orderBy('label')->get();
        $bands = PayrollTaxBand::query()
            ->with(['deductionRates'])
            ->orderBy('display_order')
            ->orderBy('min_amount')
            ->get();

        return view('hr.payroll.settings', [
            'bands' => $bands,
            'deductionTypes' => $deductionTypes,
        ]);
    }

    public function updateSettings(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'deduction_types' => 'nullable|array',
            'deduction_types.*.id' => 'nullable|integer|exists:payroll_deduction_types,id',
            'deduction_types.*.code' => 'nullable|string|max:50',
            'deduction_types.*.label' => 'required|string|max:120',
            'deduction_types.*.value_type' => 'required|in:band_percent,global_fixed',
            'deduction_types.*.fixed_amount' => 'nullable|numeric|min:0',
            'deduction_types.*.employer_rate_percent' => 'nullable|numeric|min:0|max:100',
            'deduction_types.*.reduces_taxable' => 'nullable|boolean',
            'deduction_types.*.display_order' => 'nullable|integer|min:0',
            'deduction_types.*.is_active' => 'nullable|boolean',
            'bands' => 'required|array|min:1',
            'bands.*.id' => 'nullable|integer|exists:payroll_tax_bands,id',
            'bands.*.label' => 'required|string|max:120',
            'bands.*.min_amount' => 'required|numeric|min:0',
            'bands.*.max_amount' => 'nullable|numeric|min:0',
            'bands.*.rate_percent' => 'required|numeric|min:0|max:100',
            'bands.*.display_order' => 'nullable|integer|min:0',
            'bands.*.is_active' => 'nullable|boolean',
            'bands.*.deductions' => 'nullable|array',
            'bands.*.deductions.*' => 'nullable|numeric|min:0|max:100',
        ]);

        $orderedBands = collect($validated['bands'])
            ->sortBy(fn ($band, $index) => $band['display_order'] ?? $index)
            ->values();

        $previousMin = null;

        foreach ($orderedBands as $bandData) {
            $minAmount = (float) $bandData['min_amount'];

            if ($previousMin !== null && $minAmount < $previousMin) {
                return redirect()
                    ->route('hr.payroll.settings')
                    ->withErrors(['bands' => 'PAYE bands must be ordered from lowest to highest income bracket (min amount).'])
                    ->withInput();
            }

            $previousMin = $minAmount;
        }

        $keptTypeIds = [];
        $typeIndexToId = [];

        foreach ($validated['deduction_types'] ?? [] as $index => $typeData) {
            $payload = [
                'label' => $typeData['label'],
                'value_type' => $typeData['value_type'],
                'fixed_amount' => ($typeData['fixed_amount'] ?? null) !== null && $typeData['fixed_amount'] !== ''
                    ? round((float) $typeData['fixed_amount'], 2)
                    : null,
                'employer_rate_percent' => ($typeData['employer_rate_percent'] ?? null) !== null && $typeData['employer_rate_percent'] !== ''
                    ? round((float) $typeData['employer_rate_percent'], 2)
                    : null,
                'reduces_taxable' => ! empty($typeData['reduces_taxable']),
                'display_order' => $typeData['display_order'] ?? $index,
                'is_active' => ! empty($typeData['is_active']),
            ];

            if (! empty($typeData['id'])) {
                $type = PayrollDeductionType::query()->find($typeData['id']);
                $payload['code'] = $type?->code ?? Str::slug($typeData['label'], '_');
                $type?->update($payload);
                $typeId = (int) $typeData['id'];
            } else {
                $code = $typeData['code'] ?? Str::slug($typeData['label'], '_');
                $typeId = PayrollDeductionType::query()->create([
                    ...$payload,
                    'code' => $this->uniqueDeductionCode($code),
                ])->id;
            }

            $keptTypeIds[] = $typeId;
            $typeIndexToId[$index] = $typeId;
        }

        if ($keptTypeIds !== []) {
            PayrollDeductionType::query()->whereNotIn('id', $keptTypeIds)->delete();
        }

        $activeBandPercentTypeIds = PayrollDeductionType::query()
            ->whereIn('id', $keptTypeIds)
            ->where('value_type', 'band_percent')
            ->pluck('id')
            ->all();

        $keptBandIds = [];

        foreach ($validated['bands'] as $index => $bandData) {
            $payload = [
                'label' => $bandData['label'],
                'min_amount' => round((float) $bandData['min_amount'], 2),
                'max_amount' => $bandData['max_amount'] !== null && $bandData['max_amount'] !== ''
                    ? round((float) $bandData['max_amount'], 2)
                    : null,
                'rate_percent' => round((float) $bandData['rate_percent'], 2),
                'display_order' => $bandData['display_order'] ?? $index,
                'is_active' => ! empty($bandData['is_active']),
            ];

            if (! empty($bandData['id'])) {
                PayrollTaxBand::query()->whereKey($bandData['id'])->update($payload);
                $bandId = (int) $bandData['id'];
            } else {
                $bandId = PayrollTaxBand::query()->create($payload)->id;
            }

            $keptBandIds[] = $bandId;

            $submittedTypeIds = [];

            foreach ($bandData['deductions'] ?? [] as $key => $rate) {
                $typeId = $this->resolveDeductionTypeId($key, $typeIndexToId);

                if (! $typeId || ! in_array($typeId, $activeBandPercentTypeIds, true)) {
                    continue;
                }

                if ($rate === null || $rate === '') {
                    continue;
                }

                $submittedTypeIds[] = $typeId;

                PayrollBandDeductionRate::query()->updateOrCreate(
                    [
                        'payroll_tax_band_id' => $bandId,
                        'payroll_deduction_type_id' => $typeId,
                    ],
                    ['rate_percent' => round((float) $rate, 2)]
                );
            }

            PayrollBandDeductionRate::query()
                ->where('payroll_tax_band_id', $bandId)
                ->whereIn('payroll_deduction_type_id', $activeBandPercentTypeIds)
                ->whereNotIn('payroll_deduction_type_id', $submittedTypeIds)
                ->delete();
        }

        PayrollTaxBand::query()->whereNotIn('id', $keptBandIds)->delete();

        return redirect()->route('hr.payroll.settings')->with('success', 'KRA tax bands and deductions updated.');
    }

    public function report(Request $request): View
    {
        $breakdown = $this->runCalculation($request);

        abort_unless($breakdown, 404);

        return $this->printDocuments->render('hr.payroll.print', $this->documentData($breakdown));
    }

    public function reportPdf(Request $request): Response
    {
        $breakdown = $this->runCalculation($request);

        abort_unless($breakdown, 404);

        $slug = Str::slug($breakdown['employee_name'] ?? 'payroll');

        return $this->printDocuments->downloadPdf(
            'hr.payroll.print',
            $this->documentData($breakdown, includeActions: false),
            'payroll-'.$slug.'.pdf',
        );
    }

    /**
     * @param  array<string, mixed>  $breakdown
     */
    private function deductionAmount(array $breakdown, string $code): ?float
    {
        foreach ($breakdown['deductions'] as $row) {
            if (($row['code'] ?? '') === $code) {
                return (float) $row['amount'];
            }
        }

        return null;
    }

    /**
     * @param  array<int, int>  $typeIndexToId
     */
    private function resolveDeductionTypeId(string|int $key, array $typeIndexToId): ?int
    {
        if (is_numeric($key)) {
            return (int) $key;
        }

        if (str_starts_with((string) $key, 'new_')) {
            $index = (int) substr((string) $key, 4);

            return $typeIndexToId[$index] ?? null;
        }

        return null;
    }

    private function uniqueDeductionCode(string $base): string
    {
        $code = Str::slug($base, '_') ?: 'deduction';
        $original = $code;
        $counter = 1;

        while (PayrollDeductionType::query()->where('code', $code)->exists()) {
            $code = $original.'_'.$counter;
            $counter++;
        }

        return $code;
    }

    /**
     * @return array<string, mixed>|null
     */
    private function runCalculation(Request $request): ?array
    {
        if ($request->filled('staff_id')) {
            $staff = Staff::query()->find($request->input('staff_id'));
            $gross = (float) ($request->input('amount') ?: $staff?->gross_monthly_salary);

            if (! $staff || $gross <= 0) {
                return null;
            }

            return $this->taxService->calculateFromGross($gross, [
                'employee_name' => $staff->fullName(),
                'employee_number' => $staff->employee_number,
                'allowances' => (float) $request->input('allowances', 0),
                'other_deductions' => (float) $request->input('other_deductions', 0),
            ]);
        }

        if (! $request->filled('amount')) {
            return null;
        }

        $options = [
            'allowances' => (float) $request->input('allowances', 0),
            'other_deductions' => (float) $request->input('other_deductions', 0),
        ];

        if ($request->filled('employee_name')) {
            $options['employee_name'] = $request->input('employee_name');
        }

        if ($request->filled('employee_number')) {
            $options['employee_number'] = $request->input('employee_number');
        }

        $amount = (float) $request->input('amount');

        return $request->input('mode', 'gross') === 'net'
            ? $this->taxService->calculateFromNet($amount, $options)
            : $this->taxService->calculateFromGross($amount, $options);
    }

    /**
     * @param  array<string, mixed>  $breakdown
     * @return array<string, mixed>
     */
    private function documentData(array $breakdown, bool $includeActions = true): array
    {
        $metaRows = [
            ['label' => 'Gross salary', 'value' => 'KES '.number_format($breakdown['gross_salary'], 2)],
            ['label' => 'Net salary', 'value' => 'KES '.number_format($breakdown['net_salary'], 2)],
        ];

        if (! empty($breakdown['employee_name'])) {
            array_unshift($metaRows, ['label' => 'Employee', 'value' => $breakdown['employee_name']]);
        }

        if (! empty($breakdown['employee_number'])) {
            array_unshift($metaRows, ['label' => 'Employee no.', 'value' => $breakdown['employee_number']]);
        }

        return [
            'documentTitle' => 'Payroll Breakdown',
            'documentSubtitle' => 'Monthly statutory deductions and PAYE per configured KRA bands',
            'documentRef' => $this->printDocuments->documentRef('PAY', $breakdown['employee_number'] ?? 'GENERAL'),
            'metaRows' => $metaRows,
            'breakdown' => $breakdown,
            'hideActions' => ! $includeActions,
            'pdfUrl' => route('hr.payroll.report.pdf', request()->query()),
        ];
    }
}
