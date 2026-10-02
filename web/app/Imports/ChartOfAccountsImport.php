<?php

namespace App\Imports;

use App\Models\ChartOfAccount;
use App\Services\Finance\ChartOfAccountService;
use App\Services\Finance\LedgerService;
use App\Support\SpreadsheetTabularReader;
use Illuminate\Support\Facades\DB;

class ChartOfAccountsImport
{
    public const HEADERS = [
        'account_code',
        'account_name',
        'account_type',
        'currency',
        'parent_account_code',
        'balance',
        'balance_side',
        'is_active',
        'is_system_account',
    ];

    public const ACCOUNT_TYPES = ChartOfAccountService::TYPES;

    /** Columns that must be present; everything else falls back to a default. */
    public const REQUIRED_FIELDS = ['account_code', 'account_name', 'account_type'];

    /**
     * Accepted header spellings per field.
     *
     * @var array<string, list<string>>
     */
    private const HEADER_ALIASES = [
        'account_code' => ['account_code', 'code', 'account_no', 'account_number', 'acct_code'],
        'account_name' => ['account_name', 'name', 'account', 'description', 'account_title'],
        'account_type' => ['account_type', 'type', 'class', 'category'],
        'currency' => ['currency', 'curr', 'currency_code'],
        'parent_account_code' => ['parent_account_code', 'parent', 'parent_code', 'parent_account'],
        'is_active' => ['is_active', 'active', 'is_enabled'],
        'is_system_account' => ['is_system_account', 'system', 'is_system', 'protected'],
        'balance' => ['balance', 'opening_balance', 'opening balance', 'amount', 'closing_balance'],
        'balance_side' => ['balance_side', 'side', 'dr_cr', 'debit_credit', 'dc'],
    ];

    private int $balancesPosted = 0;

    public function __construct(
        private readonly string $defaultCurrency = 'KES',
        private readonly ?LedgerService $ledger = null,
    ) {
    }

    private int $imported = 0;

    private int $updated = 0;

    private int $skipped = 0;

    /** @var list<string> */
    private array $errors = [];

    /**
     * @return array{imported: int, updated: int, skipped: int, balances: int, errors: list<string>}
     */
    public function import(string $path, ?string $extension = null): array
    {
        $this->imported = 0;
        $this->updated = 0;
        $this->skipped = 0;
        $this->balancesPosted = 0;
        $this->errors = [];

        $extension = strtolower((string) ($extension ?: pathinfo($path, PATHINFO_EXTENSION)));

        $rows = (new SpreadsheetTabularReader())->rows($path, $extension);
        $rows = array_values(array_filter($rows, static fn ($row) => is_array($row) && $row !== []));
        if ($rows === []) {
            $this->errors[] = 'The uploaded file is empty.';

            return $this->summary();
        }

        $headerIndex = $this->detectHeaderRow($rows);

        if ($headerIndex === null) {
            $this->errors[] = 'The header row could not be found. Row 1 must contain at least account_code and account_name.';

            return $this->summary();
        }

        $columns = $this->mapColumns($rows[$headerIndex]);
        $dataRows = array_slice($rows, $headerIndex + 1);

        foreach (self::REQUIRED_FIELDS as $field) {
            if (! isset($columns[$field])) {
                $this->errors[] = "Missing required column: {$field}.";
            }
        }

        if ($this->errors !== []) {
            return $this->summary();
        }

        $touched = [];
        $balances = [];
        $seenInFile = [];

        foreach (array_values($dataRows) as $index => $row) {
            $record = [];

            foreach ($columns as $field => $position) {
                $record[$field] = trim((string) ($row[$position] ?? ''));
            }

            if ($this->isEmptyRow($record)) {
                continue;
            }

            $rowNumber = $headerIndex + $index + 2;

            // A sheet edited by hand can repeat a code; the later row wins instead of
            // failing the whole upload on the unique index.
            if (isset($seenInFile[$record['account_code']])) {
                unset($touched[$record['account_code']], $balances[$record['account_code']]);
            }

            $seenInFile[$record['account_code']] = true;

            try {
                $saved = $this->persist($record, $rowNumber, isset($columns['parent_account_code']));
            } catch (\Throwable $exception) {
                $this->fail($rowNumber, 'could not be saved: '.$this->shortMessage($exception));
                $saved = false;
            }

            if ($saved) {
                $touched[$record['account_code']] = $rowNumber;

                if (isset($columns['balance']) && $record['balance'] !== '') {
                    $balances[$record['account_code']] = [
                        'value' => $record['balance'],
                        'row' => $rowNumber,
                        'side' => $this->normaliseSide($record['balance_side'] ?? '') ?? '',
                    ];
                }
            }
        }

        if ($touched === []) {
            $this->errors[] = 'No account rows were found below the header row.';

            return $this->summary();
        }

        $this->inferParentsFromCodes(array_keys($touched));
        $this->validateParents($touched);
        $this->postOpeningBalances($balances);

        return $this->summary();
    }

    /**
     * Child account codes are the parent code plus decimal segments, so 1114.01 belongs
     * to 1114. When the sheet has no parent_account_code column the parent is worked out
     * from the code, so a sheet that mixes main and child accounts imports correctly.
     *
     * @param  array<int, string>  $codes
     */
    private function inferParentsFromCodes(array $codes): void
    {
        $accounts = ChartOfAccount::query()
            ->whereIn('account_code', $codes)
            ->get(['account_code', 'parent_account_code']);

        $parents = ChartOfAccount::query()->pluck('account_code')->all();

        $service = app(ChartOfAccountService::class);

        foreach ($accounts as $account) {
            if ($account->parent_account_code !== null && $account->parent_account_code !== '') {
                continue;
            }

            $parentCode = $service->parentCodeFor($account->account_code, $parents);

            if ($parentCode === null) {
                continue;
            }

            $account->parent_account_code = $parentCode;
            $account->save();
        }
    }

    /**
     * Post supplied opening balances as ledger entries against the opening balance account.
     *
     * @param  array<string, array{value: string, row: int, side: string}>  $balances
     */
    private function postOpeningBalances(array $balances): void
    {
        if ($balances === []) {
            return;
        }

        $counterpartCode = (string) config('finance.accounts.opening_balance_equity', '3000');
        $counterpart = ChartOfAccount::query()->where('account_code', $counterpartCode)->first();

        $ledger = $this->ledger ?? app(LedgerService::class);

        foreach ($balances as $code => $entry) {
            $amount = $this->parseAmount($entry['value']);

            if ($amount === null) {
                $this->errors[] = "Row {$entry['row']}: Balance '{$entry['value']}' for account '{$code}' is not a valid number.";

                continue;
            }

            if ($amount == 0.0) {
                continue;
            }

            if ($code === $counterpartCode) {
                $this->errors[] = "Row {$entry['row']}: Account '{$code}' is the opening balance account, its balance must be left at 0.";

                continue;
            }

            if ($counterpart === null) {
                $this->errors[] = "Opening balances cannot be posted: account '{$counterpartCode}' does not exist.";

                return;
            }

            $account = ChartOfAccount::query()->where('account_code', $code)->first();

            if ($account === null) {
                continue;
            }

            if ($account->ledgerEntries()->exists()) {
                $this->errors[] = "Row {$entry['row']}: Account '{$code}' already has ledger entries, its opening balance was not posted.";

                continue;
            }

            $debitNormal = in_array($account->account_type, ['asset', 'expense'], true);
            $side = $this->normaliseSide($entry['side'] ?? '');

            // A supplied Dr/Cr side wins; otherwise a negative amount means the
            // opposite side of the account normal balance.
            if ($side === 'dr') {
                $debitCode = $code;
            } elseif ($side === 'cr') {
                $debitCode = $counterpartCode;
            } else {
                $debitCode = $amount > 0 ? ($debitNormal ? $code : $counterpartCode) : ($debitNormal ? $counterpartCode : $code);
            }

            $creditCode = $debitCode === $code ? $counterpartCode : $code;

            $ledger->postEntry(
                'opening_balance',
                $debitCode,
                $creditCode,
                abs($amount),
                "Opening balance for {$account->account_code} {$account->account_name}",
                'chart_of_accounts_import',
                'chart_of_accounts',
                (string) $account->id,
            );

            $this->balancesPosted++;
        }
    }

    private function parseAmount(string $value): ?float
    {
        $value = str_replace([',', ' ', "\u{00A0}"], '', trim($value));

        if ($value === '') {
            return null;
        }

        if (! preg_match('/^-?\d+(\.\d+)?$/', $value)) {
            return null;
        }

        return round((float) $value, 2);
    }

    /**
     * Read a Dr/Cr balance side from a sheet cell.
     */
    private function normaliseSide(string $value): ?string
    {
        $value = strtolower(trim($value));

        if ($value === '') {
            return null;
        }

        return match ($value) {
            'dr', 'debit', 'd' => 'dr',
            'cr', 'credit', 'c' => 'cr',
            default => null,
        };
    }

    /**
     * Find the header row, allowing a few leading title rows and BOM-prefixed cells.
     *
     * @param  list<array<int, mixed>>  $rows
     */
    private function detectHeaderRow(array $rows): ?int
    {
        foreach (array_slice($rows, 0, 10, true) as $index => $row) {
            $cells = array_filter(array_map(
                fn ($cell) => $this->normaliseHeader((string) $cell),
                array_values((array) $row)
            ));

            if (in_array('account_code', $cells, true) && in_array('account_name', $cells, true)) {
                return (int) $index;
            }
        }

        return null;
    }

    /**
     * Map each supported field to its column position.
     *
     * @param  array<int, mixed>  $row
     * @return array<string, int>
     */
    private function mapColumns(array $row): array
    {
        $cells = array_map(fn ($cell) => $this->normaliseHeader((string) $cell), array_values((array) $row));
        $columns = [];

        foreach (self::HEADER_ALIASES as $field => $aliases) {
            foreach ($aliases as $alias) {
                $position = array_search($alias, $cells, true);

                if ($position !== false) {
                    $columns[$field] = $position;
                    break;
                }
            }
        }

        return $columns;
    }

    private function normaliseHeader(string $value): string
    {
        $value = preg_replace('/^\xEF\xBB\xBF/', '', trim($value));

        return strtolower(trim((string) preg_replace('/[^A-Za-z0-9]+/', '_', $value), '_'));
    }

    /**
     * @return array{imported: int, updated: int, skipped: int, balances: int, errors: list<string>}
     */
    public function summary(): array
    {
        return [
            'imported' => $this->imported,
            'updated' => $this->updated,
            'skipped' => $this->skipped,
            'balances' => $this->balancesPosted,
            'errors' => $this->errors,
        ];
    }

    /**
     * Save one sheet row. Existing accounts are updated, so a sheet downloaded, edited
     * and uploaded again keeps the same account codes.
     */
    private function persist(array $record, int $row, bool $hasParentColumn = true): bool
    {
        $code = $record['account_code'];
        $name = $record['account_name'];
        $rawType = strtolower($record['account_type']);

        if ($code === '' || $name === '' || $rawType === '') {
            $this->fail($row, 'account_code, account_name and account_type are required.');

            return false;
        }

        if (strlen($code) > 30 || ! preg_match('/^[0-9A-Za-z\-\.]+$/', $code)) {
            $this->fail($row, "Account code '{$code}' is invalid. Use letters, numbers, dashes or dots (max 30 characters).");

            return false;
        }

        // Sheets carry labels such as "Accounts Receivable" or "Other Current
        // Liabilities"; these are mapped onto the five ledger account types.
        $service = app(ChartOfAccountService::class);
        $type = $service->normaliseType($rawType);

        if ($type === null) {
            $this->fail($row, "Account type '{$record['account_type']}' is not recognised. Use one of: "
                .implode(', ', $service->acceptedTypeLabels()).'.');

            return false;
        }

        $currency = strtoupper($record['currency'] ?? '');
        if ($currency === '') {
            $currency = $this->defaultCurrency;
        }

        if (! preg_match('/^[A-Z]{3}$/', $currency)) {
            $this->fail($row, "Currency '{$currency}' is invalid. Use a 3 letter ISO code such as KES or USD.");

            return false;
        }

        $parentCode = $record['parent_account_code'] ?? '';
        if ($parentCode === $code) {
            $this->fail($row, "Account '{$code}' cannot be its own parent.");

            return false;
        }

        $existing = ChartOfAccount::query()->where('account_code', $code)->first();
        $systemFlag = $this->parseBooleanOrNull($record['is_system_account'] ?? '');

        // A sheet edited in Excel may leave the parent cell empty for an account that is
        // already a child, so keep the stored parent instead of orphaning it.
        if ($parentCode === ''
            && ! $hasParentColumn
            && $existing !== null
            && $existing->parent_account_code !== null
            && $existing->parent_account_code !== '') {
            $parentCode = $existing->parent_account_code;
        }

        if ($existing !== null && $existing->is_system_account && $systemFlag === false) {
            $this->fail($row, "Account '{$code}' is a system account and cannot be imported as a user account.");

            return false;
        }

        $attributes = [
            'account_code' => $code,
            'account_name' => $name,
            'account_type' => $type,
            'currency' => $currency,
            'parent_account_code' => $parentCode !== '' ? $parentCode : null,
            'is_active' => $this->parseBoolean($record['is_active'] ?? '', true),
            'is_system_account' => $existing !== null
                ? ((bool) $existing->is_system_account || $systemFlag === true)
                : $systemFlag === true,
        ];

        DB::transaction(function () use ($existing, $attributes): void {
            if ($existing === null) {
                ChartOfAccount::query()->create($attributes);
                $this->imported++;

                return;
            }

            $existing->fill($attributes);

            if ($existing->isDirty()) {
                $existing->save();
                $this->updated++;
            }
        });

        return true;
    }

    /**
     * Parents may be listed after their children, so parent links are validated once all rows exist.
     *
     * @param  array<string, int>  $touched
     */
    private function validateParents(array $touched): void
    {
        $accounts = ChartOfAccount::query()
            ->whereIn('account_code', array_keys($touched))
            ->get(['id', 'account_code', 'account_name', 'account_type', 'parent_account_code']);

        $parents = ChartOfAccount::query()
            ->whereIn('account_code', array_values(array_filter(array_map(
                static fn ($account) => $account->parent_account_code,
                $accounts->all()
            ))))
            ->get(['account_code', 'account_type'])
            ->keyBy('account_code');

        foreach ($accounts as $account) {
            $parentCode = $account->parent_account_code;

            if ($parentCode === null || $parentCode === '') {
                continue;
            }

            $row = (int) ($touched[$account->account_code] ?? 0);
            $parent = $parents->get($parentCode);

            if ($parent === null) {
                $reason = "Parent account '{$parentCode}' for account '{$account->account_code}' was not found.";
            } elseif ($parent->account_type !== $account->account_type) {
                $reason = "Parent account '{$parentCode}' is type '{$parent->account_type}' and must match account '{$account->account_code}' type '{$account->account_type}'.";
            } else {
                continue;
            }

            $account->parent_account_code = null;
            $account->save();

            $this->unlink($row, $reason);
        }
    }

    /**
     * Record a parent link problem without counting the row twice.
     */
    private function unlink(int $row, string $message): void
    {
        $this->errors[] = $row > 0 ? "Row {$row}: {$message}" : $message;
    }

    private function fail(int $row, string $message): void
    {
        $this->skipped++;
        $this->errors[] = $row > 0 ? "Row {$row}: {$message}" : $message;
    }

    private function isEmptyRow(array $record): bool
    {
        foreach ($record as $key => $value) {
            if ($key === '_row') {
                continue;
            }

            if (trim((string) $value) !== '') {
                return false;
            }
        }

        return true;
    }

    private function parseBooleanOrNull(string $value): ?bool
    {
        $value = strtolower(trim($value));

        if ($value === '') {
            return null;
        }

        if (in_array($value, ['1', 'true', 'yes', 'y', 'on'], true)) {
            return true;
        }

        if (in_array($value, ['0', 'false', 'no', 'n', 'off'], true)) {
            return false;
        }

        return null;
    }

    private function parseBoolean(string $value, bool $default): bool
    {
        return $this->parseBooleanOrNull($value) ?? $default;
    }

    /**
     * A short, path free reason for a row that could not be saved.
     */
    private function shortMessage(\Throwable $exception): string
    {
        $message = trim(preg_replace('/\s+/', ' ', $exception->getMessage()) ?? '');
        $message = preg_replace('#(/[^\s:]{8,})#', '[path]', $message) ?? $message;

        if ($message === '') {
            return 'unknown error.';
        }

        return strlen($message) > 160 ? substr($message, 0, 157).'...' : $message;
    }
}
