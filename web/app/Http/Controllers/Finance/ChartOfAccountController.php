<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Imports\ChartOfAccountsImport;
use App\Models\ChartOfAccount;
use App\Services\Finance\ChartOfAccountService;
use App\Services\Finance\LedgerService;
use App\Support\PhpSpreadsheetAvailability;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class ChartOfAccountController extends Controller
{
    private const TYPES = ChartOfAccountService::TYPES;

    private const DEFAULT_CURRENCY = ChartOfAccountService::DEFAULT_CURRENCY;

    /**
     * Column order matching the GL / CoA on-screen table (plus parent / active for re-import).
     */
    private const HEADERS = [
        'account_code',
        'account_name',
        'account_type',
        'currency',
        'parent_account_code',
        'debit',
        'credit',
        'balance',
        'balance_side',
        'is_active',
    ];

    public function index(Request $request): RedirectResponse
    {
        return redirect()->route('finance.gl.index');
    }

    /**
     * Create a main account, or a child of the account given by ?parent=CODE.
     */
    public function create(Request $request): View
    {
        $parent = null;
        $code = trim((string) $request->query('parent'));

        if ($code !== '') {
            $parent = ChartOfAccount::query()->where('account_code', $code)->first();

            if ($parent === null) {
                return redirect()
                    ->route('finance.chart-of-accounts.create')
                    ->withErrors(['parent_account_code' => "Parent account '{$code}' does not exist."]);
            }
        }

        return view('finance.chart-of-accounts.create', [
            'parentAccounts' => $this->service()->parentOptions(),
            'parentAccount' => $parent,
            'suggestedCode' => $parent ? $this->service()->suggestChildCode($parent) : null,
            'typeGroups' => $this->service()->typeGroups(),
            'selectedTypeLabel' => $this->service()->typeLabelFor($parent?->account_type ?? 'asset'),
            'currencies' => $this->service()->currencies(),
            'defaultCurrency' => ($parent->currency ?? null) ?: self::DEFAULT_CURRENCY,
            'openingBalanceAccount' => $this->service()->openingBalanceAccountCode(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate($this->rules());

        $service = $this->service();
        $type = $service->normaliseType((string) $validated['account_type']);

        if ($type === null) {
            return back()->withErrors([
                'account_type' => 'Select an account type from the list.',
            ])->withInput();
        }

        $parentCode = $validated['parent_account_code'] ?? null;
        $inferredParent = null;

        // No parent was picked, so the code itself decides, e.g. 612000 filed under the
        // 61000 summary account. The choice is only applied when it agrees with the
        // account type, and it is reported back to the user.
        if ($parentCode === null || $parentCode === '') {
            $resolution = $service->resolveParentCode(
                (string) $validated['account_code'],
                ChartOfAccount::query()->pluck('account_code')->all()
            );

            $candidate = $resolution['parent'] === null
                ? null
                : ChartOfAccount::query()->where('account_code', $resolution['parent'])->first();

            if ($candidate !== null && $candidate->account_type === $type) {
                $parentCode = $candidate->account_code;
                $inferredParent = $parentCode;
            }
        }

        if ($error = $this->parentTypeError($parentCode, $type)) {
            return back()->withErrors($error)->withInput();
        }

        if ($error = $this->childCodeError($validated['account_code'], $parentCode)) {
            return back()->withErrors($error)->withInput();
        }

        $balance = round((float) ($validated['opening_balance'] ?? 0), 2);
        $side = strtolower((string) ($validated['balance_side'] ?? 'dr')) === 'cr' ? 'cr' : 'dr';

        if ($balance > 0.0) {
            $counterpartCode = $service->openingBalanceAccountCode();

            if ($validated['account_code'] === $counterpartCode) {
                return back()->withErrors([
                    'opening_balance' => "Account {$counterpartCode} is the opening balance account, its balance must be left at 0.",
                ])->withInput();
            }

            if (! ChartOfAccount::query()->where('account_code', $counterpartCode)->exists()) {
                return back()->withErrors([
                    'opening_balance' => 'The balance cannot be posted yet: opening balance account '
                        .$counterpartCode.' is missing. Create it first or leave the balance at 0.',
                ])->withInput();
            }
        }

        $validated['account_type'] = $type;
        $validated['currency'] = strtoupper((string) ($validated['currency'] ?? '')) ?: self::DEFAULT_CURRENCY;
        $validated['is_active'] = true;
        $validated['is_system_account'] = false;

        unset($validated['opening_balance'], $validated['balance_side']);

        $account = ChartOfAccount::query()->create($validated);

        $message = $inferredParent !== null
            ? "Child account {$account->account_code} added under {$inferredParent}, taken from the account code."
            : ($parentCode !== null
                ? "Child account {$account->account_code} added under {$parentCode}."
                : 'Account created successfully.');

        if ($balance > 0.0) {
            try {
                $service->postOpeningBalance(
                    $account,
                    $balance,
                    $side,
                    (int) ($request->user()->staff_id ?? \App\Models\Staff::query()->value('id') ?? 1),
                );

                $message .= sprintf(
                    ' Opening balance of %s %s posted on the %s side.',
                    number_format($balance, 2),
                    $account->currency,
                    strtoupper($side)
                );
            } catch (\RuntimeException $exception) {
                return redirect()
                    ->route('finance.chart-of-accounts.show', $account)
                    ->with('status', $account->account_code.' was created, but the balance was not posted.')
                    ->withErrors(['opening_balance' => $exception->getMessage()]);
            }
        }

        return redirect()
            ->route('finance.chart-of-accounts.show', $account)
            ->with('status', $message);
    }

    public function show(ChartOfAccount $chartOfAccount): View
    {
        $chartOfAccount->load(['parent']);
        $service = $this->service();
        $balances = $service->balanceMap();
        $balance = $balances[$chartOfAccount->account_code] ?? ['debit' => 0.0, 'credit' => 0.0, 'net' => 0.0, 'ownNet' => 0.0, 'childNet' => 0.0];

        $descendants = $service->descendants($chartOfAccount);

        return view('finance.chart-of-accounts.show', [
            'chartOfAccount' => $chartOfAccount,
            'ancestors' => $chartOfAccount->ancestors(),
            'descendants' => $descendants,
            'descendantBalances' => collect($descendants->pluck('account.account_code')->all())
                ->mapWithKeys(fn (string $code) => [$code => $balances[$code] ?? ['debit' => 0.0, 'credit' => 0.0, 'net' => 0.0, 'ownNet' => 0.0, 'childNet' => 0.0]])
                ->all(),
            'debitTotal' => $balance['debit'],
            'creditTotal' => $balance['credit'],
            'balance' => $balance['net'],
            'ownBalance' => $balance['ownNet'],
            'childBalance' => $balance['childNet'],
            'entryCount' => $chartOfAccount->ledgerEntries()->count(),
        ]);
    }

    public function edit(ChartOfAccount $chartOfAccount): View
    {
        return view('finance.chart-of-accounts.edit', [
            'chartOfAccount' => $chartOfAccount,
            'parentAccounts' => $this->service()->parentOptions($chartOfAccount),
            'typeGroups' => $this->service()->typeGroups(),
            'selectedTypeLabel' => $this->service()->typeLabelFor($chartOfAccount->account_type),
            'currencies' => $this->service()->currencies(),
        ]);
    }

    public function update(Request $request, ChartOfAccount $chartOfAccount): RedirectResponse
    {
        $validated = $request->validate($this->rules($chartOfAccount));

        $service = $this->service();
        $type = $service->normaliseType((string) $validated['account_type']);

        if ($type === null) {
            return back()->withErrors([
                'account_type' => 'Select an account type from the list.',
            ])->withInput();
        }

        if ($chartOfAccount->account_type !== $type) {
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

            if ($chartOfAccount->descendantCodes()->contains($parentCode)) {
                return back()->withErrors([
                    'parent_account_code' => 'An account cannot be moved under one of its own child accounts.',
                ])->withInput();
            }
        }

        if ($error = $this->parentTypeError($parentCode, $type, $chartOfAccount)) {
            return back()->withErrors($error)->withInput();
        }

        if ($parentCode !== $chartOfAccount->parent_account_code
            && ($error = $this->childCodeError($validated['account_code'], $parentCode))) {
            return back()->withErrors($error)->withInput();
        }

        $validated['account_type'] = $type;
        $validated['is_system_account'] = $chartOfAccount->is_system_account;
        $validated['currency'] = strtoupper((string) ($validated['currency'] ?? '')) ?: $chartOfAccount->currency;
        $validated['is_active'] = $chartOfAccount->is_active;

        unset($validated['opening_balance'], $validated['balance_side']);

        $chartOfAccount->update($validated);

        return redirect()
            ->route('finance.chart-of-accounts.show', $chartOfAccount)
            ->with('status', 'Account updated successfully.');
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

        if ($result['skipped'] > 0
            && $result['imported'] === 0
            && $result['updated'] === 0
            && $result['balances'] === 0) {
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

        if ($result['imported'] === 0 && $result['updated'] === 0 && $result['skipped'] === 0) {
            $message = 'Chart of accounts is already up to date. No changes were needed.';
        }

        if ($result['balances'] > 0) {
            $message .= ' '.$result['balances'].' balance adjustment(s) posted.';
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

    /**
     * Download the live chart matching the on-screen CoA/GL table.
     * Edit locally and re-upload to update fields and post balance adjustments.
     */
    public function export(): \Symfony\Component\HttpFoundation\BinaryFileResponse|RedirectResponse
    {
        if (! PhpSpreadsheetAvailability::isAvailable()) {
            return redirect()
                ->route('finance.gl.index')
                ->withErrors(['file' => PhpSpreadsheetAvailability::missingMessage('Chart of accounts Excel export')]);
        }

        $service = $this->service();
        $accounts = $service->tree(ChartOfAccount::query()->orderBy('account_code')->get());
        $direct = app(LedgerService::class)->accountBalanceMap(
            $accounts->map(fn (array $row) => $row['account'])
        );

        $rows = $accounts->map(function (array $row) use ($direct): array {
            $account = $row['account'];
            $own = $direct[$account->account_code] ?? ['debit' => 0.0, 'credit' => 0.0, 'net' => 0.0];
            $ownNet = (float) $own['net'];

            return [
                $account->account_code,
                $account->account_name,
                ucfirst((string) $account->account_type),
                $account->currency ?: self::DEFAULT_CURRENCY,
                $account->isTopLevel() ? '' : $account->parent_account_code,
                number_format((float) $own['debit'], 2, '.', ''),
                number_format((float) $own['credit'], 2, '.', ''),
                number_format(abs($ownNet), 2, '.', ''),
                $ownNet >= 0 ? 'Dr' : 'Cr',
                (int) $account->is_active,
            ];
        })->values()->all();

        try {
            $spreadsheet = new Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();
            $sheet->fromArray(self::HEADERS, null, 'A1');
            if ($rows !== []) {
                $sheet->fromArray($rows, null, 'A2');
            }
            foreach (range('A', 'J') as $column) {
                $sheet->getColumnDimension($column)->setAutoSize(true);
            }
            $name = 'chart_of_accounts_'.now()->format('Ymd_His').'.xlsx';
            $path = tempnam(sys_get_temp_dir(), 'coa_');
            (new Xlsx($spreadsheet))->save($path);
            $spreadsheet->disconnectWorksheets();
        } catch (\Throwable $exception) {
            report($exception);

            return redirect()
                ->route('finance.gl.index')
                ->withErrors([
                    'file' => 'Excel export failed: '.$exception->getMessage()
                        .'. If this mentions a missing PhpOffice file, redeploy so composer install restores vendor/phpoffice/phpspreadsheet.',
                ]);
        }

        return response()->download($path, $name, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend(true);
    }

    public function template(): \Symfony\Component\HttpFoundation\BinaryFileResponse|RedirectResponse
    {
        if (! PhpSpreadsheetAvailability::isAvailable()) {
            return redirect()
                ->route('finance.gl.index')
                ->withErrors(['file' => PhpSpreadsheetAvailability::missingMessage('Chart of accounts Excel template')]);
        }

        try {
            $spreadsheet = new Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();
            $sheet->fromArray(self::HEADERS, null, 'A1');
            $sheet->fromArray([
                ['1114', 'Bank Accounts', 'Asset', 'KES', '', '0.00', '0.00', '0.00', 'Dr', '1'],
                ['1114.01', 'Bank - Savings Account', 'Asset', 'KES', '1114', '0.00', '0.00', '0.00', 'Dr', '1'],
                ['4000', 'Tuition Revenue', 'Revenue', 'KES', '', '0.00', '0.00', '0.00', 'Cr', '1'],
                ['5100', 'Staff Training', 'Expense', 'KES', '', '0.00', '0.00', '0.00', 'Dr', '1'],
            ], null, 'A2');
            foreach (range('A', 'J') as $column) {
                $sheet->getColumnDimension($column)->setAutoSize(true);
            }
            $name = 'chart_of_accounts_template.xlsx';
            $path = tempnam(sys_get_temp_dir(), 'coa_');
            (new Xlsx($spreadsheet))->save($path);
            $spreadsheet->disconnectWorksheets();
        } catch (\Throwable $exception) {
            report($exception);

            return redirect()
                ->route('finance.gl.index')
                ->withErrors(['file' => 'Could not build the Excel template: '.$exception->getMessage()]);
        }

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
            'account_type' => ['required', 'string', 'max:50'],
            'currency' => ['nullable', 'string', 'size:3'],
            'parent_account_code' => ['nullable', 'string', 'max:30', 'exists:chart_of_accounts,account_code'],
            'opening_balance' => ['nullable', 'numeric', 'min:0', 'max:999999999999'],
            'balance_side' => ['nullable', 'in:dr,cr'],
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
     * A child account code has to belong to the parent it is filed under. The shape of
     * a child code is worked out by the chart of accounts service, so every numbering
     * style an import accepts is accepted here too: parent plus a separator, e.g.
     * 1114.01 or 1114-01, a longer code that starts with the parent, e.g. 111400, and a
     * code in the parent's own digit block, e.g. 61000 over 612000.
     *
     * Accounts with no parent are free form. Existing legacy children keep working
     * until their parent is changed.
     *
     * @return array<string, string>
     */
    private function childCodeError(string $code, ?string $parentCode): array
    {
        if ($parentCode === null || $parentCode === '') {
            return [];
        }

        $known = ChartOfAccount::query()->pluck('account_code')->all();
        $resolved = $this->service()->resolveParentCode($code, $known);

        if ($resolved['parent'] === $parentCode) {
            return [];
        }

        if ($resolved['parent'] !== null) {
            return [
                'account_code' => "Account code {$code} belongs under {$resolved['parent']} by its numbering, not under {$parentCode}.",
            ];
        }

        $separators = (array) config('finance.hierarchy.separators', ['.', '-', '/', '_', ':', ' ']);
        $examples = implode(', ', array_map(
            static fn ($separator) => $parentCode.$separator.'01',
            array_slice($separators, 0, 3)
        ));

        return [
            'account_code' => "A child account code must follow its parent's numbering, for example {$examples}, or sit in the parent's own digit block such as {$parentCode}00.",
        ];
    }

    private function service(): ChartOfAccountService
    {
        return app(ChartOfAccountService::class);
    }
}
