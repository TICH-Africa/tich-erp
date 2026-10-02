<?php

namespace App\Services\Finance;

use App\Models\ChartOfAccount;
use Illuminate\Contracts\Pagination\LengthAwarePaginator as LengthAwarePaginatorContract;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

/**
 * Builds the parent/child shape of the chart of accounts.
 *
 * Parent accounts are the main accounts. Children hang under them and carry the
 * real postings, while the parent balance is the sum of its own postings and
 * every posting on its descendants.
 */
class ChartOfAccountService
{
    public const TYPES = ['asset', 'liability', 'equity', 'revenue', 'expense'];

    public const DEFAULT_CURRENCY = 'KES';

    /**
     * Spreadsheets and hand written charts carry finer grained labels than the five
     * ledger types. These are mapped onto the ledger type so reports, trial balance
     * and the parent roll-up keep working.
     *
     * @var array<string, string>
     */
    public const TYPE_ALIASES = [
        // assets
        'account receivable' => 'asset',
        'trade receivable' => 'asset',
        'receivable' => 'asset',
        'debtor' => 'asset',
        'fixed asset' => 'asset',
        'non current asset' => 'asset',
        'current asset' => 'asset',
        'other current asset' => 'asset',
        'inventory' => 'asset',
        'prepayment' => 'asset',
        'other asset' => 'asset',
        'cash' => 'asset',
        'bank' => 'asset',
        'vehicle' => 'asset',
        'equipment' => 'asset',
        'furniture' => 'asset',
        'building' => 'asset',
        'land' => 'asset',
        'asset' => 'asset',
        // liabilities
        'current liability' => 'liability',
        'other current liability' => 'liability',
        'non current liability' => 'liability',
        'long term liability' => 'liability',
        'account payable' => 'liability',
        'trade payable' => 'liability',
        'creditor' => 'liability',
        'payable' => 'liability',
        'accrual' => 'liability',
        'accrued expense' => 'liability',
        'accrued cost' => 'liability',
        'tax payable' => 'liability',
        'deferred income' => 'liability',
        'loan' => 'liability',
        'overdraft' => 'liability',
        'liability' => 'liability',
        // equity
        'equity' => 'equity',
        'capital' => 'equity',
        'retained earning' => 'equity',
        'reserve' => 'equity',
        'net asset' => 'equity',
        // revenue
        'revenue' => 'revenue',
        'income' => 'revenue',
        'sale' => 'revenue',
        'fee' => 'revenue',
        'donation' => 'revenue',
        'grant' => 'revenue',
        'other income' => 'revenue',
        // expenses
        'expense' => 'expense',
        'operating expense' => 'expense',
        'cost of sale' => 'expense',
        'other expense' => 'expense',
        'direct cost' => 'expense',
        'cost' => 'expense',
        'staff' => 'expense',
        'administrative' => 'expense',
        'finance cost' => 'expense',
        'salary' => 'expense',
        'wage' => 'expense',
        'rent' => 'expense',
        'utility' => 'expense',
        'electricity' => 'expense',
        'transport' => 'expense',
        'maintenance' => 'expense',
        'depreciation' => 'expense',
        'insurance' => 'expense',
        'bank charge' => 'expense',
        'printing' => 'expense',
        'stationery' => 'expense',
    ];

    public function __construct(private readonly LedgerService $ledger) {}

    /**
     * Map a spreadsheet or form account type onto a ledger account type.
     *
     * Accepts the five ledger types plus labels such as "Accounts Receivable",
     * "Fixed Assets", "Other Current Assets", "Current Liabilities" and
     * "Other Current Liabilities", in any capitalisation, singular or plural.
     */
    public function normaliseType(string $value): ?string
    {
        $key = $this->typeKey($value);

        if ($key === '') {
            return null;
        }

        $candidates = [$key, $this->singular($key)];

        if (str_starts_with($key, 'other ')) {
            $stripped = trim(substr($key, 6));
            $candidates[] = $stripped;
            $candidates[] = $this->singular($stripped);
        }

        foreach ($candidates as $candidate) {
            if (in_array($candidate, self::TYPES, true)) {
                return $candidate;
            }

            if (isset(self::TYPE_ALIASES[$candidate])) {
                return self::TYPE_ALIASES[$candidate];
            }
        }

        return $this->typeFromDescription($key);
    }

    /**
     * Last resort for descriptive labels such as "Accounts Receivable - Students":
     * match the longest known type phrase contained in the text.
     */
    private function typeFromDescription(string $key): ?string
    {
        $match = null;
        $matchedLength = 0;

        foreach (self::TYPE_ALIASES as $alias => $type) {
            foreach ([$alias, $this->singular($alias)] as $needle) {
                $length = strlen($needle);

                if ($length <= $matchedLength || ! str_contains($key, $needle)) {
                    continue;
                }

                $match = $type;
                $matchedLength = $length;
            }
        }

        return $match;
    }

    /**
     * Account type choices grouped the way a chart of accounts is normally laid out.
     * Every option resolves back to one of the five ledger types.
     *
     * @return array<string, list<array{type: string, label: string}>>
     */
    public function typeGroups(): array
    {
        return [
            'Assets' => [
                ['type' => 'asset', 'label' => 'Assets (general)'],
                ['type' => 'asset', 'label' => 'Current Assets'],
                ['type' => 'asset', 'label' => 'Other Current Assets'],
                ['type' => 'asset', 'label' => 'Accounts Receivable'],
                ['type' => 'asset', 'label' => 'Fixed Assets'],
                ['type' => 'asset', 'label' => 'Inventories & Prepayments'],
                ['type' => 'asset', 'label' => 'Cash & Bank'],
            ],
            'Liabilities' => [
                ['type' => 'liability', 'label' => 'Liabilities (general)'],
                ['type' => 'liability', 'label' => 'Current Liabilities'],
                ['type' => 'liability', 'label' => 'Other Current Liabilities'],
                ['type' => 'liability', 'label' => 'Accounts Payable'],
                ['type' => 'liability', 'label' => 'Accruals & Tax Payable'],
                ['type' => 'liability', 'label' => 'Loans & Overdrafts'],
            ],
            'Equity' => [
                ['type' => 'equity', 'label' => 'Equity (general)'],
                ['type' => 'equity', 'label' => 'Capital'],
                ['type' => 'equity', 'label' => 'Reserves'],
                ['type' => 'equity', 'label' => 'Retained Earnings'],
            ],
            'Revenue' => [
                ['type' => 'revenue', 'label' => 'Revenue (general)'],
                ['type' => 'revenue', 'label' => 'Fee Income'],
                ['type' => 'revenue', 'label' => 'Sales'],
                ['type' => 'revenue', 'label' => 'Donations & Grants'],
                ['type' => 'revenue', 'label' => 'Other Income'],
            ],
            'Expenses' => [
                ['type' => 'expense', 'label' => 'Expenses (general)'],
                ['type' => 'expense', 'label' => 'Operating Expenses'],
                ['type' => 'expense', 'label' => 'Cost of Sales'],
                ['type' => 'expense', 'label' => 'Staff Costs'],
                ['type' => 'expense', 'label' => 'Administrative Costs'],
                ['type' => 'expense', 'label' => 'Finance Costs'],
                ['type' => 'expense', 'label' => 'Other Expenses'],
            ],
        ];
    }

    /**
     * The dropdown label that represents a stored ledger type.
     */
    public function typeLabelFor(string $type): string
    {
        $type = $this->normaliseType($type) ?? $type;

        foreach ($this->typeGroups() as $options) {
            foreach ($options as $option) {
                if ($option['type'] === $type) {
                    return $option['label'];
                }
            }
        }

        return ucfirst($type);
    }

    /**
     * Every account type label the chart of accounts accepts, for help text.
     *
     * @return list<string>
     */
    public function acceptedTypeLabels(): array
    {
        $labels = [];

        foreach ($this->typeGroups() as $options) {
            foreach ($options as $option) {
                $labels[] = $option['label'];
            }
        }

        return array_values(array_unique($labels));
    }

    private function typeKey(string $value): string
    {
        $value = strtolower(trim($value));
        $value = preg_replace('/[^a-z0-9 ]+/', ' ', $value) ?? '';
        $value = preg_replace('/\s+/', ' ', $value) ?? '';

        return trim($value);
    }

    private function singular(string $key): string
    {
        if (str_ends_with($key, 'ies')) {
            return substr($key, 0, -3).'y';
        }

        if (str_ends_with($key, 'sses') || str_ends_with($key, 'shes') || str_ends_with($key, 'ches')) {
            return substr($key, 0, -2);
        }

        if (str_ends_with($key, 's') && ! str_ends_with($key, 'ss')) {
            return substr($key, 0, -1);
        }

        return $key;
    }

    /**
     * Every account as a parent/child tree, ordered so children follow their parent.
     *
     * @param  iterable<int, ChartOfAccount>  $accounts
     * @return Collection<int, array{account: ChartOfAccount, depth: int, childCount: int, hasChildren: bool}>
     */
    public function tree(iterable $accounts): Collection
    {
        $accounts = collect($accounts);
        $known = array_flip($accounts->pluck('account_code')->all());
        $children = $this->groupChildren($accounts);

        $rows = collect();
        $visited = [];

        foreach ($accounts as $account) {
            $parentCode = $account->parent_account_code;

            if ($parentCode === null || $parentCode === '' || ! isset($known[$parentCode])) {
                $this->appendBranch($rows, $account, 0, $children, $visited);
            }
        }

        // Accounts caught in a parent cycle never reach a root, so list them last.
        foreach ($accounts as $account) {
            $this->appendBranch($rows, $account, 0, $children, $visited);
        }

        return $rows->values();
    }

    /**
     * Tree rows filtered by the chart of accounts search form. Parents of a match are
     * kept so the hierarchy stays readable while searching.
     *
     * @param  Collection<int, array{account: ChartOfAccount, depth: int, childCount: int, hasChildren: bool}>  $rows
     * @param  array<string, mixed>  $filters
     * @return Collection<int, array{account: ChartOfAccount, depth: int, childCount: int, hasChildren: bool}>
     */
    public function filterRows(Collection $rows, array $filters): Collection
    {
        $search = trim((string) ($filters['search'] ?? ''));
        $type = trim((string) ($filters['account_type'] ?? ''));
        $activeFilter = $filters['is_active'] ?? null;
        $isActive = ($activeFilter === null || $activeFilter === '') ? null : (int) (bool) $activeFilter;

        if ($search === '' && $type === '' && $isActive === null) {
            return $rows;
        }

        $needle = mb_strtolower($search);

        $matched = $rows->filter(function (array $row) use ($needle, $type, $isActive): bool {
            $account = $row['account'];

            if ($type !== '' && $account->account_type !== $type) {
                return false;
            }

            if ($isActive !== null && (int) $account->is_active !== $isActive) {
                return false;
            }

            if ($needle === '') {
                return true;
            }

            return str_contains(mb_strtolower((string) $account->account_code), $needle)
                || str_contains(mb_strtolower((string) $account->account_name), $needle);
        });

        if ($matched->isEmpty()) {
            return $matched;
        }

        $byCode = $rows->keyBy(fn (array $row) => $row['account']->account_code);
        $keep = [];

        foreach ($matched as $row) {
            $current = $row['account'];
            $guard = 0;

            while ($current !== null && $guard < 50) {
                if (isset($keep[$current->account_code])) {
                    break;
                }

                $keep[$current->account_code] = true;
                $current = $this->parentOf($current, $byCode);
                $guard++;
            }
        }

        return $rows->filter(static fn (array $row) => isset($keep[$row['account']->account_code]))->values();
    }

    /**
     * A page of tree rows with balances, ready for the chart of accounts table.
     *
     * @param  array<string, mixed>  $filters
     * @param  array<string, array<string, float>>  $balances
     * @param  array<string, mixed>  $query
     * @return LengthAwarePaginatorContract<int, array<string, mixed>>
     */
    public function paginateRows(Collection $rows, array $balances, array $filters = [], int $perPage = 25, array $query = []): LengthAwarePaginatorContract
    {
        $filtered = $this->filterRows($rows, $filters);
        $page = max(1, (int) request()->input('page', 1));

        $items = $filtered->forPage($page, $perPage)->values()->map(function (array $row) use ($balances): array {
            $code = $row['account']->account_code;
            $balance = $balances[$code] ?? ['debit' => 0.0, 'credit' => 0.0, 'net' => 0.0, 'ownNet' => 0.0, 'childNet' => 0.0];

            return array_merge($row, ['balance' => $balance]);
        });

        return new LengthAwarePaginator(
            $items,
            $filtered->count(),
            $perPage,
            $page,
            ['path' => request()->url(), 'query' => $query],
        );
    }

    /**
     * Balances for every account, with parents rolled up from their descendants.
     *
     * @return array<string, array{debit: float, credit: float, net: float, ownNet: float, childNet: float}>
     */
    public function balanceMap(): array
    {
        $accounts = ChartOfAccount::query()->orderBy('account_code')->get();
        $children = $this->groupChildren($accounts);
        $direct = $this->ledger->accountBalanceMap($accounts);

        $balances = [];

        foreach ($accounts as $account) {
            $code = $account->account_code;
            $own = $direct[$code] ?? ['debit' => 0.0, 'credit' => 0.0, 'net' => 0.0];
            $debit = $own['debit'];
            $credit = $own['credit'];
            $childNet = 0.0;

            foreach ($this->descendantsOf($code, $children) as $childCode) {
                $child = $direct[$childCode] ?? ['debit' => 0.0, 'credit' => 0.0, 'net' => 0.0];
                $debit += $child['debit'];
                $credit += $child['credit'];
                $childNet += $child['net'];
            }

            $balances[$code] = [
                'debit' => round($debit, 2),
                'credit' => round($credit, 2),
                'net' => round($own['net'] + $childNet, 2),
                'ownNet' => $own['net'],
                'childNet' => round($childNet, 2),
            ];
        }

        return $balances;
    }

    /**
     * Descendants of an account with their depth, used on the account detail page.
     *
     * @return Collection<int, array{account: ChartOfAccount, depth: int}>
     */
    public function descendants(ChartOfAccount $account): Collection
    {
        $all = ChartOfAccount::query()->get();
        $children = $this->groupChildren($all);

        $rows = collect();
        $queue = [['code' => $account->account_code, 'depth' => 1]];
        $seen = [$account->account_code => true];

        while ($queue !== []) {
            $node = array_shift($queue);

            foreach ($children[$node['code']] ?? [] as $child) {
                if (isset($seen[$child->account_code])) {
                    continue;
                }

                $seen[$child->account_code] = true;
                $rows->push(['account' => $child, 'depth' => $node['depth']]);
                $queue[] = ['code' => $child->account_code, 'depth' => $node['depth'] + 1];
            }
        }

        return $rows;
    }

    /**
     * Accounts that may be picked as a parent.
     *
     * @return Collection<int, ChartOfAccount>
     */
    public function parentOptions(?ChartOfAccount $account = null): Collection
    {
        $excluded = $account ? $account->descendantCodes()->push($account->account_code)->all() : [];

        return ChartOfAccount::query()
            ->where('is_active', 1)
            ->when($excluded !== [], fn ($query) => $query->whereNotIn('account_code', $excluded))
            ->orderBy('account_code')
            ->get(['id', 'account_code', 'account_name', 'account_type']);
    }

    /**
     * @return list<string>
     */
    public function currencies(): array
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

    /**
     * The next free child code for a parent, e.g. 1114 becomes 1114.03 when
     * 1114.01 and 1114.02 already exist.
     */
    public function suggestChildCode(ChartOfAccount $parent): string
    {
        $prefix = $parent->account_code.'.';
        $used = [];
        $highest = 0;

        foreach ($parent->children()->pluck('account_code')->all() as $code) {
            $code = (string) $code;

            if (! str_starts_with($code, $prefix)) {
                continue;
            }

            $segment = substr($code, strlen($prefix));
            $used[] = $segment;

            if (is_numeric($segment)) {
                $highest = max($highest, (int) $segment);
            }
        }

        for ($index = $highest + 1; $index <= $highest + 100; $index++) {
            $segment = str_pad((string) $index, 2, '0', STR_PAD_LEFT);

            if (! in_array($segment, $used, true)) {
                return $prefix.$segment;
            }
        }

        return $prefix.str_pad((string) ($highest + 1), 2, '0', STR_PAD_LEFT);
    }

    /**
     * The ledger account used as the counterpart when an opening balance is posted.
     */
    public function openingBalanceAccountCode(): string
    {
        return (string) config('finance.accounts.opening_balance_equity', '3000');
    }

    /**
     * Post an opening balance to a newly created account.
     *
     * The amount is the real money value in the account currency. Finance chooses the
     * side: Dr increases a debit normal account, Cr decreases it or increases a credit
     * normal one. The counterpart is the opening balance equity account, so trial
     * balance stays balanced and the parent roll-up picks the amount up automatically.
     *
     * @throws \RuntimeException when the counterpart account is missing
     */
    public function postOpeningBalance(
        ChartOfAccount $account,
        float $amount,
        string $side,
        ?int $recordedByStaffId = null,
        ?string $ledgerDate = null,
    ): void {
        $amount = round(abs($amount), 2);

        if ($amount <= 0.0) {
            return;
        }

        $counterpartCode = $this->openingBalanceAccountCode();

        if ($counterpartCode === $account->account_code) {
            throw new \RuntimeException(
                "Account {$account->account_code} is the opening balance account, its balance must be left at 0."
            );
        }

        $counterpartExists = ChartOfAccount::query()->where('account_code', $counterpartCode)->exists();

        if (! $counterpartExists) {
            throw new \RuntimeException(
                "Opening balance cannot be posted because account {$counterpartCode} does not exist."
            );
        }

        $debitCode = strtolower($side) === 'cr' ? $counterpartCode : $account->account_code;
        $creditCode = $debitCode === $account->account_code ? $counterpartCode : $account->account_code;

        $this->ledger->postEntry(
            'opening_balance',
            $debitCode,
            $creditCode,
            $amount,
            "Opening balance for {$account->account_code} {$account->account_name}",
            'chart_of_accounts',
            'chart_of_accounts',
            (string) $account->id,
            $recordedByStaffId,
            $ledgerDate,
        );
    }

    /**
     * Work out the parent code implied by a dotted child code.
     *
     * Child codes are the parent code plus decimal segments, so 1100.01 sits under
     * 1100 and 1100.01.02 sits under 1100.01. Returns null when no prefix matches a
     * known account, in which case the account stays top level.
     *
     * @param  array<int, string>  $knownCodes
     */
    public function parentCodeFor(string $code, array $knownCodes): ?string
    {
        $segments = explode('.', $code);

        if (count($segments) < 2) {
            return null;
        }

        array_pop($segments);
        $known = array_flip($knownCodes);

        while ($segments !== []) {
            $candidate = implode('.', $segments);

            if (isset($known[$candidate])) {
                return $candidate;
            }

            array_pop($segments);
        }

        return null;
    }

    /**
     * @param  iterable<int, ChartOfAccount>  $accounts
     * @return array<string, Collection<int, ChartOfAccount>>
     */
    private function groupChildren(iterable $accounts): array
    {
        $children = [];

        foreach ($accounts as $account) {
            $parent = $account->parent_account_code;

            if ($parent === null || $parent === '') {
                continue;
            }

            $children[$parent] = ($children[$parent] ?? collect())->push($account);
        }

        foreach ($children as $parentCode => $group) {
            $children[$parentCode] = $group->sortBy(
                static fn (ChartOfAccount $account) => $account->account_code
            )->values();
        }

        return $children;
    }

    /**
     * @param  Collection<int, array{account: ChartOfAccount, depth: int, childCount: int, hasChildren: bool}>  $rows
     * @param  array<string, Collection<int, ChartOfAccount>>  $children
     * @param  array<string, bool>  $visited
     */
    private function appendBranch(Collection $rows, ChartOfAccount $account, int $depth, array $children, array &$visited): void
    {
        $code = $account->account_code;

        if (isset($visited[$code])) {
            return;
        }

        $visited[$code] = true;
        $kids = $children[$code] ?? collect();

        $rows->push([
            'account' => $account,
            'depth' => $depth,
            'childCount' => $kids->count(),
            'hasChildren' => $kids->isNotEmpty(),
        ]);

        foreach ($kids as $child) {
            $this->appendBranch($rows, $child, $depth + 1, $children, $visited);
        }
    }

    /**
     * @param  Collection<string, array{account: ChartOfAccount, depth: int, childCount: int, hasChildren: bool}>  $byCode
     */
    private function parentOf(ChartOfAccount $account, Collection $byCode): ?ChartOfAccount
    {
        $code = $account->parent_account_code;

        if ($code === null || $code === '') {
            return null;
        }

        $row = $byCode->get($code);

        return $row['account'] ?? null;
    }

    /**
     * @param  array<string, Collection<int, ChartOfAccount>>  $children
     * @return list<string>
     */
    private function descendantsOf(string $code, array $children): array
    {
        $codes = [];
        $queue = $children[$code] ?? collect();
        $seen = [];

        while ($queue->isNotEmpty()) {
            $node = $queue->shift();

            if (isset($seen[$node->account_code])) {
                continue;
            }

            $seen[$node->account_code] = true;
            $codes[] = $node->account_code;
            $queue = $queue->merge($children[$node->account_code] ?? collect());
        }

        return $codes;
    }
}