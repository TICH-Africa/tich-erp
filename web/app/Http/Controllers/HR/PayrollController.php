<?php

namespace App\Http\Controllers\HR;

use App\Http\Controllers\Controller;
use App\Models\PayrollBandDeductionRate;
use App\Models\PayrollDeductionType;
use App\Models\PayrollKenyaStatutorySetting;
use App\Models\PayrollStatutoryBracket;
use App\Models\PayrollTaxBand;
use App\Models\Staff;
use App\Services\KenyaPayrollTaxProvisioner;
use App\Services\KenyaPayrollTaxService;
use App\Services\PayrollStatutoryBracketService;
use App\Services\PrintDocumentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Illuminate\Support\Str;
use Illuminate\View\View;

class PayrollController extends Controller
{
    public function __construct(
        protected KenyaPayrollTaxService $taxService,
        protected KenyaPayrollTaxProvisioner $payrollProvisioner,
        protected PayrollStatutoryBracketService $statutoryBracketService,
        protected PrintDocumentService $printDocuments,
    ) {}

    protected function viewPrefix(): string
    {
        return 'hr.payroll';
    }

    protected function routePrefix(): string
    {
        return 'hr.payroll';
    }

    protected function printView(): string
    {
        return $this->viewPrefix().'.print';
    }

    public function index(Request $request): View
    {
        $this->payrollProvisioner->ensureConfigured();

        $staff = Staff::query()
            ->excludePlatformOperators()
            ->with(['department', 'activeAllowances'])
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
            $basic = (float) $member->gross_monthly_salary;
            $allowances = (float) $member->activeAllowances->sum('amount');

            if ($basic <= 0 && $allowances <= 0) {
                return [
                    'staff' => $member,
                    'breakdown' => null,
                ];
            }

            return [
                'staff' => $member,
                'breakdown' => $this->taxService->calculateForStaff($member, $basic, [
                    'allowances' => $allowances,
                ]),
            ];
        });

        $totals = [
            'gross_salary' => 0.0,
            'paye' => 0.0,
            'wht' => 0.0,
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
            $totals['wht'] += $this->deductionAmount($breakdown, 'withholding_tax') ?? 0;
            $totals['nssf'] += $this->deductionAmount($breakdown, 'nssf') ?? 0;
            $totals['sha'] += $this->deductionAmount($breakdown, 'sha') ?? 0;
            $totals['ahl'] += $this->deductionAmount($breakdown, 'ahl') ?? 0;
            $totals['total_deductions'] += $breakdown['total_deductions'];
            $totals['net_salary'] += $breakdown['net_salary'];
            $totals['employer_cost'] += $breakdown['total_employer_cost'];
        }

        return view($this->viewPrefix().'.index', [
            'rows' => $rows,
            'totals' => $totals,
            'payslipPayload' => $this->buildPayslipPayload($rows),
            'withholdingRate' => $this->taxService->withholdingTaxRate(),
        ]);
    }

    public function settings(): View
    {
        $this->payrollProvisioner->ensureConfigured();

        $deductionTypes = PayrollDeductionType::query()->orderBy('display_order')->orderBy('label')->get();
        $bands = PayrollTaxBand::query()
            ->with(['deductionRates'])
            ->orderBy('display_order')
            ->orderBy('min_amount')
            ->get();

        return view($this->viewPrefix().'.settings', [
            'bands' => $bands,
            'deductionTypes' => $deductionTypes,
            'withholdingRate' => $this->taxService->withholdingTaxRate(),
            'statutorySettings' => $this->resolveStatutorySettingsForForm(),
            'statutoryBrackets' => $this->resolveStatutoryBracketsForForm(),
        ]);
    }

    /**
     * @return array<string, \Illuminate\Support\Collection<int, PayrollStatutoryBracket>>
     */
    protected function resolveStatutoryBracketsForForm(): array
    {
        if (! Schema::hasTable('payroll_statutory_brackets')) {
            return [
                PayrollStatutoryBracket::SCHEME_NSSF => collect(),
                PayrollStatutoryBracket::SCHEME_SHIF => collect(),
                PayrollStatutoryBracket::SCHEME_AHL => collect(),
            ];
        }

        return [
            PayrollStatutoryBracket::SCHEME_NSSF => PayrollStatutoryBracket::forScheme(PayrollStatutoryBracket::SCHEME_NSSF)->get(),
            PayrollStatutoryBracket::SCHEME_SHIF => PayrollStatutoryBracket::forScheme(PayrollStatutoryBracket::SCHEME_SHIF)->get(),
            PayrollStatutoryBracket::SCHEME_AHL => PayrollStatutoryBracket::forScheme(PayrollStatutoryBracket::SCHEME_AHL)->get(),
        ];
    }

    protected function resolveStatutorySettingsForForm(): PayrollKenyaStatutorySetting
    {
        if (! Schema::hasTable('payroll_kenya_statutory_settings')) {
            return new PayrollKenyaStatutorySetting(PayrollKenyaStatutorySetting::defaultsFromConfig());
        }

        return PayrollKenyaStatutorySetting::current();
    }

    public function updateSettings(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'withholding_tax_rate' => 'nullable|numeric|min:0|max:100',
            'deduction_types' => 'nullable|array',
            'deduction_types.*.id' => 'nullable|integer|exists:payroll_deduction_types,id',
            'deduction_types.*.code' => 'nullable|string|max:50',
            'deduction_types.*.label' => 'required|string|max:120',
            'deduction_types.*.value_type' => 'required|in:band_percent,global_fixed,withholding_percent',
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
            'kenya_statutory' => 'nullable|array',
            'kenya_statutory.nssf_rate_percent' => 'nullable|numeric|min:0|max:100',
            'kenya_statutory.nssf_tier1_ceiling' => 'nullable|numeric|min:0',
            'kenya_statutory.nssf_tier1_max_employee' => 'nullable|numeric|min:0',
            'kenya_statutory.nssf_tier2_ceiling' => 'nullable|numeric|min:0',
            'kenya_statutory.nssf_tier2_max_employee' => 'nullable|numeric|min:0',
            'kenya_statutory.nssf_max_employee' => 'nullable|numeric|min:0',
            'kenya_statutory.shif_rate_percent' => 'nullable|numeric|min:0|max:100',
            'kenya_statutory.shif_monthly_floor' => 'nullable|numeric|min:0',
            'kenya_statutory.shif_floor_applies_up_to_gross' => 'nullable|numeric|min:0',
            'kenya_statutory.ahl_rate_percent' => 'nullable|numeric|min:0|max:100',
            'statutory_brackets' => 'nullable|array',
            'statutory_brackets.nssf' => 'nullable|array',
            'statutory_brackets.shif' => 'nullable|array',
            'statutory_brackets.ahl' => 'nullable|array',
            'statutory_brackets.*.*.id' => 'nullable|integer|exists:payroll_statutory_brackets,id',
            'statutory_brackets.*.*.label' => 'required|string|max:120',
            'statutory_brackets.*.*.min_amount' => 'nullable|numeric|min:0',
            'statutory_brackets.*.*.max_amount' => 'nullable|numeric|min:0',
            'statutory_brackets.*.*.calculation' => 'required|in:percent,fixed,percent_capped,percent_floor',
            'statutory_brackets.*.*.rate_percent' => 'nullable|numeric|min:0|max:100',
            'statutory_brackets.*.*.fixed_amount' => 'nullable|numeric|min:0',
            'statutory_brackets.*.*.cap_amount' => 'nullable|numeric|min:0',
            'statutory_brackets.*.*.is_active' => 'nullable|boolean',
        ]);

        if (array_key_exists('withholding_tax_rate', $validated) && $validated['withholding_tax_rate'] !== null && $validated['withholding_tax_rate'] !== '') {
            PayrollDeductionType::query()->updateOrCreate(
                ['code' => 'withholding_tax'],
                [
                    'label' => 'Withholding tax (WHT)',
                    'value_type' => 'withholding_percent',
                    'fixed_amount' => round((float) $validated['withholding_tax_rate'], 2),
                    'display_order' => 99,
                    'is_active' => 1,
                ]
            );
        }

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
            PayrollDeductionType::query()
                ->whereNotIn('id', $keptTypeIds)
                ->whereNotIn('code', $this->payrollProvisioner->lockedDeductionCodes())
                ->delete();
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

        if (! empty($validated['kenya_statutory'])) {
            $this->saveKenyaStatutorySettings($validated['kenya_statutory']);
        }

        if (! empty($validated['statutory_brackets'])) {
            $this->statutoryBracketService->saveGrouped($validated['statutory_brackets']);
        }

        return redirect()->route($this->routePrefix().'.settings')->with('success', 'Payroll tax and statutory settings updated.');
    }

    /**
     * @param  array<string, mixed>  $input
     */
    protected function saveKenyaStatutorySettings(array $input): void
    {
        if (! Schema::hasTable('payroll_kenya_statutory_settings')) {
            return;
        }

        $row = PayrollKenyaStatutorySetting::current();

        $row->update([
            'nssf_rate_percent' => round((float) ($input['nssf_rate_percent'] ?? $row->nssf_rate_percent), 4),
            'nssf_tier1_ceiling' => round((float) ($input['nssf_tier1_ceiling'] ?? $row->nssf_tier1_ceiling), 2),
            'nssf_tier1_max_employee' => round((float) ($input['nssf_tier1_max_employee'] ?? $row->nssf_tier1_max_employee), 2),
            'nssf_tier2_ceiling' => round((float) ($input['nssf_tier2_ceiling'] ?? $row->nssf_tier2_ceiling), 2),
            'nssf_tier2_max_employee' => round((float) ($input['nssf_tier2_max_employee'] ?? $row->nssf_tier2_max_employee), 2),
            'nssf_max_employee' => round((float) ($input['nssf_max_employee'] ?? $row->nssf_max_employee), 2),
            'shif_rate_percent' => round((float) ($input['shif_rate_percent'] ?? $row->shif_rate_percent), 4),
            'shif_monthly_floor' => round((float) ($input['shif_monthly_floor'] ?? $row->shif_monthly_floor), 2),
            'shif_floor_applies_up_to_gross' => round((float) ($input['shif_floor_applies_up_to_gross'] ?? $row->shif_floor_applies_up_to_gross), 2),
            'ahl_rate_percent' => round((float) ($input['ahl_rate_percent'] ?? $row->ahl_rate_percent), 4),
        ]);
    }

    public function report(Request $request): View
    {
        $breakdown = $this->runCalculation($request);

        if (! $breakdown) {
            return view($this->viewPrefix().'.calculator', [
                'withholdingRate' => $this->taxService->withholdingTaxRate(),
            ]);
        }

        return $this->printDocuments->render($this->printView(), $this->documentData($breakdown));
    }

    public function reportPdf(Request $request): StreamedResponse
    {
        $breakdown = $this->runCalculation($request);

        abort_unless($breakdown, 404);

        $slug = Str::slug($breakdown['employee_name'] ?? 'payroll');

        return $this->printDocuments->downloadPdf(
            $this->printView(),
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
            $staff = Staff::query()
                ->excludePlatformOperators()
                ->with('activeAllowances')
                ->find($request->input('staff_id'));
            if ($staff?->isLinkedPlatformOperator()) {
                return null;
            }
            $basic = (float) ($request->input('amount') ?: $staff?->gross_monthly_salary);
            $allowances = (float) ($request->input('allowances') ?: $staff?->activeAllowances->sum('amount'));

            if (! $staff || ($basic <= 0 && $allowances <= 0)) {
                return null;
            }

            return $this->taxService->calculateForStaff($staff, $basic, [
                'allowances' => $allowances,
                'other_deductions' => (float) $request->input('other_deductions', 0),
            ]);
        }

        if ($request->filled('payroll_item_id')) {
            $item = \App\Models\PayrollItem::query()->find($request->input('payroll_item_id'));
            $snapshot = $item?->calculation_snapshot;

            return is_array($snapshot) && $snapshot !== [] ? $snapshot : null;
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

        if ($request->filled('payroll_scheme')) {
            $options['payroll_scheme'] = $request->input('payroll_scheme');
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
        $payPeriod = now()->format('F Y');
        $isWithholding = ($breakdown['payroll_scheme'] ?? 'employee') === 'withholding';

        return [
            'documentTitle' => $isWithholding
                ? \App\Support\EmploymentCategory::WITHHOLDING_PAYMENT_STATEMENT_TITLE
                : 'Monthly Payslip',
            'documentSubtitle' => ($breakdown['employee_name'] ?? 'Staff member').' · '.$payPeriod.($isWithholding ? ' · Withholding tax only' : ''),
            'documentRef' => $this->printDocuments->documentRef('PAY', $breakdown['employee_number'] ?? 'GENERAL'),
            'metaRows' => [],
            'breakdown' => $breakdown,
            'payPeriod' => $payPeriod,
            'hideActions' => ! $includeActions,
            'backUrl' => route($this->routePrefix().'.index'),
            'pdfUrl' => route($this->routePrefix().'.report.pdf', request()->query()),
            'bodyClass' => 'tich-payslip-page',
        ];
    }

    /**
     * @param  \Illuminate\Support\Collection<int, array{staff: Staff, breakdown: array<string, mixed>|null}>  $rows
     * @return list<array<string, mixed>>
     */
    private function buildPayslipPayload($rows): array
    {
        return $rows
            ->filter(fn (array $row) => $row['breakdown'] !== null)
            ->map(function (array $row) {
                $staff = $row['staff'];
                $query = ['staff_id' => $staff->id];

                return [
                    'id' => $staff->id,
                    'label' => $staff->fullName().' ('.$staff->employee_number.')',
                    'preview_url' => route($this->routePrefix().'.report', $query),
                    'download_url' => route($this->routePrefix().'.report.pdf', $query),
                    'external_url' => route($this->routePrefix().'.report', $query),
                ];
            })
            ->values()
            ->all();
    }
}
