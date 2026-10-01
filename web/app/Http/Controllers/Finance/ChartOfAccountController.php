<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Imports\ChartOfAccountsImport;
use App\Models\ChartOfAccount;
use App\Services\Finance\LedgerService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
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
            // Use extension (filename), not MIME sniffing — Linux fileinfo often
            // reports .xlsx as application/zip, which fails mimes:xlsx.
            'file' => ['required', 'file', 'extensions:xlsx,xls,csv', 'max:10240'],
        ]);

        $upload = $request->file('file');
        $extension = strtolower((string) $upload->getClientOriginalExtension());
        if ($extension === '') {
            $extension = strtolower((string) pathinfo((string) $upload->getClientOriginalName(), PATHINFO_EXTENSION));
        }
        if (! in_array($extension, ['xlsx', 'xls', 'csv'], true)) {
            return back()->withErrors([
                'file' => 'Upload a .xlsx, .xls, or .csv file.',
            ]);
        }

        if (in_array($extension, ['xlsx', 'xlsm'], true) && ! class_exists(\ZipArchive::class)) {
            return back()->withErrors([
                'file' => 'This server cannot read .xlsx files because the PHP zip extension is missing. Ask ICT to enable php-zip, or save the sheet as .csv and upload that instead.',
            ]);
        }

        // Prefer app storage (always writable on cPanel) with a real extension.
        // Upload temp names have no extension; some readers and hosts need one.
        $storedRelative = $upload->storeAs(
            'imports/chart-of-accounts',
            'coa_'.now()->format('YmdHis').'_'.bin2hex(random_bytes(4)).'.'.$extension
        );

        if (! $storedRelative) {
            return back()->withErrors([
                'file' => 'The uploaded file could not be stored for import. Check that storage/app/private is writable.',
            ]);
        }

        $readablePath = Storage::disk('local')->path($storedRelative);
        $storedRelativeForCleanup = $storedRelative;

        // Also keep a /tmp copy when possible — some hosts restrict open_basedir on storage.
        $tmpPath = rtrim(sys_get_temp_dir(), DIRECTORY_SEPARATOR)
            .DIRECTORY_SEPARATOR
            .'coa_'.uniqid('', true).'.'.$extension;
        if (@copy($readablePath, $tmpPath) && is_file($tmpPath) && filesize($tmpPath) > 0) {
            $readablePath = $tmpPath;
        }

        if (! is_file($readablePath) || filesize($readablePath) < 1) {
            if (is_file($tmpPath)) {
                @unlink($tmpPath);
            }
            Storage::disk('local')->delete($storedRelativeForCleanup);

            return back()->withErrors([
                'file' => 'The uploaded file is empty or could not be prepared for import.',
            ]);
        }

        $import = new ChartOfAccountsImport(self::DEFAULT_CURRENCY, app(LedgerService::class));

        try {
            $result = $import->import($readablePath, $extension);
        } catch (\Illuminate\Database\QueryException $exception) {
            report($exception);

            return back()->withErrors([
                'file' => $this->importDatabaseFailureMessage($exception),
            ]);
        } catch (\Throwable $exception) {
            report($exception);

            return back()->withErrors([
                'file' => $this->importReadFailureMessage($exception, $extension),
            ]);
        } finally {
            if (is_file($tmpPath)) {
                @unlink($tmpPath);
            }
            Storage::disk('local')->delete($storedRelativeForCleanup);
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

    private function importDatabaseFailureMessage(\Illuminate\Database\QueryException $exception): string
    {
        $detail = strtolower($exception->getMessage());

        if (str_contains($detail, 'unknown column') && str_contains($detail, 'currency')) {
            return 'The spreadsheet was read, but the database is missing chart_of_accounts.currency. Run deploy/production-patches.sql section 44 (replace account_category with currency), then retry the upload.';
        }

        if (str_contains($detail, 'unknown column') && str_contains($detail, 'account_category')) {
            return 'The spreadsheet was read, but chart_of_accounts still expects account_category. Run deploy/production-patches.sql section 44 to switch to currency, then retry.';
        }

        return 'The spreadsheet was read, but saving accounts failed: '.$this->shortExceptionMessage($exception);
    }

    private function importReadFailureMessage(\Throwable $exception, string $extension): string
    {
        $detail = strtolower($exception->getMessage());
        $short = $this->shortExceptionMessage($exception);

        if (
            str_contains($detail, 'class "ziparchive" not found')
            || str_contains($detail, 'class ziparchive not found')
            || str_contains($detail, 'ziparchive is required')
        ) {
            return 'This server cannot read .xlsx files because the PHP zip extension is missing. Ask ICT to enable php-zip.';
        }

        if (str_contains($detail, 'domdocument') || str_contains($detail, 'php-xml') || str_contains($detail, 'php xml')) {
            return 'This server cannot read spreadsheet XML (PHP xml extension missing). Ask ICT to enable php-xml.';
        }

        if (str_contains($detail, 'phpspreadsheet is not installed') && in_array($extension, ['xls'], true)) {
            return 'This server cannot read .xls files because PhpSpreadsheet is missing. Upload .xlsx instead, or run composer install on the web host.';
        }

        return 'Could not read the spreadsheet ('.$short.'). Supported formats: .xlsx, .xls, .csv.';
    }

    private function shortExceptionMessage(\Throwable $exception): string
    {
        $message = trim(preg_replace('/\s+/', ' ', $exception->getMessage()) ?? '');
        $message = preg_replace('#(/[^\s:]{8,})#', '[path]', $message) ?? $message;

        if (strlen($message) > 180) {
            $message = substr($message, 0, 177).'...';
        }

        return $message !== '' ? $message : $exception::class;
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
