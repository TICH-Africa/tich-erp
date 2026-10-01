<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Imports\ChartOfAccountsImport;
use App\Models\ChartOfAccount;
use App\Services\Finance\LedgerService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class ChartOfAccountController extends Controller
{
    private const TYPES = ['asset', 'liability', 'equity', 'revenue', 'expense'];

    private const DEFAULT_CURRENCY = 'KES';

    public function index(Request $request): RedirectResponse
    {
        return redirect()->route('finance.gl.index');
    }

    public function create(): View
    {
        return view('finance.chart-of-accounts.create', [
            'parentAccounts' => $this->parentOptions(),
            'types' => self::TYPES,
            'currencies' => $this->currencies(),
            'defaultCurrency' => self::DEFAULT_CURRENCY,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate($this->rules());

        if ($error = $this->parentTypeError($validated['parent_account_code'] ?? null, $validated['account_type'])) {
            return back()->withErrors($error)->withInput();
        }

        $validated['currency'] = strtoupper((string) ($validated['currency'] ?? '')) ?: self::DEFAULT_CURRENCY;
        $validated['is_active'] = true;
        $validated['is_system_account'] = false;

        ChartOfAccount::query()->create($validated);

        return redirect()
            ->route('finance.gl.index')
            ->with('status', 'Account created successfully.');
    }

    public function show(ChartOfAccount $chartOfAccount): View
    {
        $chartOfAccount->load(['parent', 'children']);
        $balances = app(LedgerService::class)->accountBalanceMap([$chartOfAccount]);
        $balance = $balances[$chartOfAccount->account_code] ?? ['debit' => 0.0, 'credit' => 0.0, 'net' => 0.0];

        return view('finance.chart-of-accounts.show', [
            'chartOfAccount' => $chartOfAccount,
            'debitTotal' => $balance['debit'],
            'creditTotal' => $balance['credit'],
            'balance' => $balance['net'],
            'entryCount' => $chartOfAccount->ledgerEntries()->count(),
        ]);
    }

    public function edit(ChartOfAccount $chartOfAccount): View
    {
        return view('finance.chart-of-accounts.edit', [
            'chartOfAccount' => $chartOfAccount,
            'parentAccounts' => $this->parentOptions($chartOfAccount),
            'types' => self::TYPES,
            'currencies' => $this->currencies(),
        ]);
    }

    public function update(Request $request, ChartOfAccount $chartOfAccount): RedirectResponse
    {
        $validated = $request->validate($this->rules($chartOfAccount));

        if ($chartOfAccount->account_type !== $validated['account_type']) {
            $hasChildren = $chartOfAccount->children()->exists();
            $hasEntries = $chartOfAccount->hasLedgerEntries();

            if ($hasChildren || $hasEntries) {
                return back()->withErrors([
                    'account_type' => 'The account type cannot change once the account has child accounts or ledger entries.',
                ])->withInput();
            }
        }

        $parentCode = $validated['parent_account_code'] ?? null;

        if ($parentCode !== null) {
            if ($parentCode === $chartOfAccount->account_code) {
                return back()->withErrors([
                    'parent_account_code' => 'An account cannot be its own parent.',
                ])->withInput();
            }

            if ($this->descendantCodes($chartOfAccount)->contains($parentCode)) {
                return back()->withErrors([
                    'parent_account_code' => 'An account cannot be moved under one of its own child accounts.',
                ])->withInput();
            }
        }

        if ($error = $this->parentTypeError($parentCode, $validated['account_type'], $chartOfAccount)) {
            return back()->withErrors($error)->withInput();
        }

        $validated['is_system_account'] = $chartOfAccount->is_system_account;
        $validated['currency'] = strtoupper((string) ($validated['currency'] ?? '')) ?: $chartOfAccount->currency;
        $validated['is_active'] = $chartOfAccount->is_active;

        $chartOfAccount->update($validated);

        return redirect()
            ->route('finance.gl.index')
            ->with('status', 'Account updated successfully.');
    }

    public function destroy(ChartOfAccount $chartOfAccount): RedirectResponse
    {
        if ($chartOfAccount->is_system_account) {
            return back()->withErrors(['account' => 'System accounts cannot be deleted.']);
        }

        if ($chartOfAccount->children()->exists()) {
            return back()->withErrors(['account' => 'Accounts with child accounts cannot be deleted.']);
        }

        if ($chartOfAccount->hasLedgerEntries()) {
            return back()->withErrors(['account' => 'Accounts with ledger entries cannot be deleted.']);
        }

        $chartOfAccount->delete();

        return redirect()
            ->route('finance.gl.index')
            ->with('status', 'Account deleted successfully.');
    }

    public function import(Request $request): RedirectResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx,xls,csv', 'max:10240'],
        ]);

        $import = new ChartOfAccountsImport(self::DEFAULT_CURRENCY, app(LedgerService::class));

        try {
            $result = $import->import($request->file('file')->getRealPath());
        } catch (\Throwable $exception) {
            report($exception);

            return back()->withErrors([
                'file' => 'The file could not be read. Save it as .xlsx or .csv and try again.',
            ]);
        }

        if ($result['skipped'] > 0 && $result['imported'] === 0 && $result['updated'] === 0) {
            return back()
                ->withErrors(['file' => 'No accounts were imported. ' . implode(' ', array_slice($result['errors'], 0, 5))])
                ->withInput();
        }

        $message = sprintf(
            '%d account(s) imported, %d updated, %d skipped.',
            $result['imported'],
            $result['updated'],
            $result['skipped']
        );

        if ($result['balances'] > 0) {
            $message .= ' ' . $result['balances'] . ' opening balance(s) posted.';
        }

        return redirect()
            ->route('finance.gl.index')
            ->with('status', $message)
            ->with('import_errors', $result['errors']);
    }

    public function template(): \Symfony\Component\HttpFoundation\BinaryFileResponse
    {
        $headers = ['account_code', 'account_name', 'account_type', 'currency', 'balance', 'is_active'];

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->fromArray($headers, null, 'A1');

        $sample = [
            ['1114', 'Bank - Savings Account', 'asset', 'KES', '2500000', '1'],
            ['1115', 'Bank - Project Account', 'asset', 'KES', '750000', '1'],
            ['1100', 'Accounts Receivable - Students', 'asset', 'KES', '0', '1'],
            ['2010', 'Accrued Expenses', 'liability', 'KES', '0', '1'],
            ['4100', 'Hostel Revenue', 'revenue', 'KES', '0', '1'],
            ['5100', 'Staff Training', 'expense', 'KES', '0', '1'],
        ];
        $sheet->fromArray($sample, null, 'A2');

        foreach (range('A', 'F') as $column) {
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }

        $name = 'chart_of_accounts_template.xlsx';
        $path = tempnam(sys_get_temp_dir(), 'coa_');
        $writer = new Xlsx($spreadsheet);
        $writer->save($path);
        $spreadsheet->disconnectWorksheets();

        return response()->download($path, $name, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend(true);
    }

    /**
     * @return array<string, array<int, string>>
     */
    private function rules(?ChartOfAccount $account = null): array
    {
        return [
            'account_code' => [
                'required',
                'string',
                'max:30',
                'regex:/^[0-9A-Za-z\-\.]+$/',
                'unique:chart_of_accounts,account_code' . ($account ? ',' . $account->id : ''),
            ],
            'account_name' => ['required', 'string', 'max:200'],
            'account_type' => ['required', 'in:' . implode(',', self::TYPES)],
            'currency' => ['nullable', 'string', 'size:3'],
            'parent_account_code' => ['nullable', 'string', 'max:30', 'exists:chart_of_accounts,account_code'],
            'is_system_account' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    private function parentTypeError(?string $parentCode, string $type, ?ChartOfAccount $account = null): array
    {
        if ($parentCode === null || $parentCode === '') {
            return [];
        }

        $parent = ChartOfAccount::query()->where('account_code', $parentCode)->first();

        if ($parent === null) {
            return ['parent_account_code' => 'The selected parent account does not exist.'];
        }

        if ($account !== null && $parent->id === $account->id) {
            return ['parent_account_code' => 'An account cannot be its own parent.'];
        }

        if ($parent->account_type !== $type) {
            return ['parent_account_code' => "The parent account is type '{$parent->account_type}', so it must match the account type."];
        }

        return [];
    }

    /**
     * @return \Illuminate\Support\Collection<int, string>
     */
    private function descendantCodes(ChartOfAccount $account)
    {
        $codes = collect();
        $queue = $account->children()->pluck('account_code');
        $seen = [];

        while ($queue->isNotEmpty()) {
            $code = $queue->shift();
            if (isset($seen[$code])) {
                continue;
            }

            $seen[$code] = true;
            $codes->push($code);
            $queue = $queue->merge(ChartOfAccount::query()->where('parent_account_code', $code)->pluck('account_code'));
        }

        return $codes;
    }

    /**
     * @return \Illuminate\Database\Eloquent\Collection<int, ChartOfAccount>
     */
    private function parentOptions(?ChartOfAccount $account = null)
    {
        $excluded = $account ? $this->descendantCodes($account)->push($account->account_code)->all() : [];

        return ChartOfAccount::query()
            ->where('is_active', 1)
            ->when($excluded !== [], fn ($q) => $q->whereNotIn('account_code', $excluded))
            ->orderBy('account_code')
            ->get(['id', 'account_code', 'account_name', 'account_type']);
    }

    /**
     * @return list<string>
     */
    private function currencies(): array
    {
        $stored = ChartOfAccount::query()
            ->whereNotNull('currency')
            ->distinct()
            ->orderBy('currency')
            ->pluck('currency')
            ->filter()
            ->map(static fn ($currency) => strtoupper((string) $currency))
            ->values()
            ->all();

        return array_values(array_unique(array_merge([self::DEFAULT_CURRENCY, 'UGX', 'USD', 'EUR', 'GBP'], $stored)));
    }
}
