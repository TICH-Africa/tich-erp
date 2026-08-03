<?php

namespace App\Http\Controllers\HR;

use App\Http\Controllers\Controller;
use App\Models\PayrollStatutoryRate;
use App\Models\PayrollTaxBand;
use App\Models\Staff;
use App\Services\KenyaPayrollTaxService;
use App\Services\PrintDocumentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Str;
use Illuminate\View\View;

class PayrollTaxController extends Controller
{
    public function __construct(
        protected KenyaPayrollTaxService $taxService,
        protected PrintDocumentService $printDocuments,
    ) {}

    public function index(Request $request): View
    {
        $staff = Staff::query()
            ->where('employment_status', 'active')
            ->orderBy('surname')
            ->orderBy('first_name')
            ->get(['id', 'employee_number', 'first_name', 'surname', 'gross_monthly_salary']);

        $input = [
            'mode' => old('mode', $request->query('mode', 'net')),
            'amount' => old('amount', $request->query('amount')),
            'allowances' => old('allowances', $request->query('allowances', 0)),
            'other_deductions' => old('other_deductions', $request->query('other_deductions', 0)),
            'staff_id' => old('staff_id', $request->query('staff_id')),
            'employee_name' => old('employee_name'),
        ];

        $breakdown = null;

        if ($request->filled('amount')) {
            $breakdown = $this->runCalculation($request->merge($input));
        }

        return view('hr.payroll.tax.index', [
            'staff' => $staff,
            'input' => $input,
            'breakdown' => $breakdown,
        ]);
    }

    public function calculate(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'mode' => 'required|in:gross,net',
            'amount' => 'required|numeric|min:0',
            'allowances' => 'nullable|numeric|min:0',
            'other_deductions' => 'nullable|numeric|min:0',
            'staff_id' => 'nullable|exists:staff,id',
            'employee_name' => 'nullable|string|max:255',
        ]);

        return redirect()->route('hr.payroll.tax.index', array_filter([
            'mode' => $validated['mode'],
            'amount' => $validated['amount'],
            'allowances' => $validated['allowances'] ?? 0,
            'other_deductions' => $validated['other_deductions'] ?? 0,
            'staff_id' => $validated['staff_id'] ?? null,
            'employee_name' => $validated['employee_name'] ?? null,
        ]));
    }

    public function settings(): View
    {
        return view('hr.payroll.tax.settings', [
            'bands' => PayrollTaxBand::query()->orderBy('display_order')->orderBy('min_amount')->get(),
            'rates' => PayrollStatutoryRate::query()->orderBy('display_order')->orderBy('code')->get(),
        ]);
    }

    public function updateSettings(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'bands' => 'required|array|min:1',
            'bands.*.id' => 'nullable|integer|exists:payroll_tax_bands,id',
            'bands.*.label' => 'required|string|max:120',
            'bands.*.min_amount' => 'required|numeric|min:0',
            'bands.*.max_amount' => 'nullable|numeric|min:0',
            'bands.*.rate_percent' => 'required|numeric|min:0|max:100',
            'bands.*.display_order' => 'nullable|integer|min:0',
            'bands.*.is_active' => 'nullable|boolean',
            'rates' => 'required|array|min:1',
            'rates.*.id' => 'required|integer|exists:payroll_statutory_rates,id',
            'rates.*.label' => 'required|string|max:120',
            'rates.*.rate_percent' => 'nullable|numeric|min:0|max:100',
            'rates.*.employer_rate_percent' => 'nullable|numeric|min:0|max:100',
            'rates.*.fixed_amount' => 'nullable|numeric|min:0',
            'rates.*.floor_amount' => 'nullable|numeric|min:0',
            'rates.*.ceiling_amount' => 'nullable|numeric|min:0',
            'rates.*.notes' => 'nullable|string|max:500',
            'rates.*.is_active' => 'nullable|boolean',
        ]);

        $keptBandIds = [];

        foreach ($validated['bands'] as $index => $bandData) {
            $payload = [
                'label' => $bandData['label'],
                'min_amount' => $bandData['min_amount'],
                'max_amount' => $bandData['max_amount'] ?: null,
                'rate_percent' => $bandData['rate_percent'],
                'display_order' => $bandData['display_order'] ?? $index,
                'is_active' => ! empty($bandData['is_active']),
            ];

            if (! empty($bandData['id'])) {
                PayrollTaxBand::query()->whereKey($bandData['id'])->update($payload);
                $keptBandIds[] = (int) $bandData['id'];
            } else {
                $keptBandIds[] = PayrollTaxBand::query()->create($payload)->id;
            }
        }

        PayrollTaxBand::query()->whereNotIn('id', $keptBandIds)->delete();

        foreach ($validated['rates'] as $rateData) {
            PayrollStatutoryRate::query()->whereKey($rateData['id'])->update([
                'label' => $rateData['label'],
                'rate_percent' => $rateData['rate_percent'] ?: null,
                'employer_rate_percent' => $rateData['employer_rate_percent'] ?: null,
                'fixed_amount' => $rateData['fixed_amount'] ?: null,
                'floor_amount' => $rateData['floor_amount'] ?: null,
                'ceiling_amount' => $rateData['ceiling_amount'] ?: null,
                'notes' => $rateData['notes'] ?? null,
                'is_active' => ! empty($rateData['is_active']),
            ]);
        }

        return redirect()->route('hr.payroll.tax.settings')->with('success', 'KRA tax bands and statutory rates updated.');
    }

    public function report(Request $request): View
    {
        $breakdown = $this->runCalculation($request);

        abort_unless($breakdown, 404);

        return $this->printDocuments->render('hr.payroll.tax.print', $this->documentData($breakdown));
    }

    public function reportPdf(Request $request): Response
    {
        $breakdown = $this->runCalculation($request);

        abort_unless($breakdown, 404);

        $slug = Str::slug($breakdown['employee_name'] ?? 'payroll-tax');

        return $this->printDocuments->downloadPdf(
            'hr.payroll.tax.print',
            $this->documentData($breakdown, includeActions: false),
            'kra-payroll-'.$slug.'.pdf',
        );
    }

    /**
     * @return array<string, mixed>|null
     */
    private function runCalculation(Request $request): ?array
    {
        if (! $request->filled('amount')) {
            return null;
        }

        $options = [
            'allowances' => (float) $request->input('allowances', 0),
            'other_deductions' => (float) $request->input('other_deductions', 0),
        ];

        if ($request->filled('staff_id')) {
            $staff = Staff::query()->find($request->input('staff_id'));

            if ($staff) {
                $options['employee_name'] = trim($staff->first_name.' '.$staff->surname);
                $options['employee_number'] = $staff->employee_number;
            }
        } elseif ($request->filled('employee_name')) {
            $options['employee_name'] = $request->input('employee_name');
        }

        $amount = (float) $request->input('amount');

        return $request->input('mode', 'net') === 'gross'
            ? $this->taxService->calculateFromGross($amount, $options)
            : $this->taxService->calculateFromNet($amount, $options);
    }

    /**
     * @param  array<string, mixed>  $breakdown
     * @return array<string, mixed>
     */
    private function documentData(array $breakdown, bool $includeActions = true): array
    {
        $metaRows = [
            ['label' => 'Calculation mode', 'value' => $breakdown['mode'] === 'net' ? 'Net to gross (reverse)' : 'Gross to net'],
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
            'documentTitle' => 'KRA Payroll Tax Breakdown',
            'documentSubtitle' => 'Monthly statutory deductions and PAYE per configured KRA bands',
            'documentRef' => $this->printDocuments->documentRef('TAX', $breakdown['employee_number'] ?? 'GENERAL'),
            'metaRows' => $metaRows,
            'breakdown' => $breakdown,
            'hideActions' => ! $includeActions,
            'pdfUrl' => route('hr.payroll.tax.report.pdf', request()->query()),
        ];
    }
}
