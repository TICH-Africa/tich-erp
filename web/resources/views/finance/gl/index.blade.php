@extends('layouts.finance')

@section('title', 'General Ledger')

@section('finance-content')
    <x-page-toolbar title="Chart of Accounts / GL" meta="Accounts, journal entries, debits, credits, balances, Trial Balance, P&amp;L, Balance Sheet and Cash Flow">
        <x-slot:actions>
            <a href="{{ route('finance.reports.index', ['report' => 'trial_balance']) }}" class="tich-btn tich-btn-secondary">Financial reports</a>
            @can('finance.chart_of_accounts.manage')
                <a href="{{ route('finance.chart-of-accounts.export') }}" class="tich-btn tich-btn-ghost">Export current chart</a>
                <a href="{{ route('finance.chart-of-accounts.template') }}" class="tich-btn tich-btn-ghost">Template</a>
                <a href="{{ route('finance.chart-of-accounts.create') }}" class="tich-btn tich-btn-primary">+ Add Main Account</a>
                <button type="button" class="tich-btn tich-btn--danger" data-open-modal="delete-all-modal">Delete all</button>
            @endcan
            <a href="{{ route('finance.gl.journal.create') }}" class="tich-btn tich-btn-secondary">+ New journal entry</a>
        </x-slot:actions>
    </x-page-toolbar>

    @if (session('status'))
        <div class="tich-alert tich-alert--success tich-mt-4">{{ session('status') }}</div>
    @endif
    @if (session('import_errors'))
        <div class="tich-alert tich-alert--warning tich-mt-4">
            <strong>Rows that were not imported:</strong>
            <ul style="margin: 0.5rem 0 0; padding-left: 1.25rem;">
                @foreach (array_slice(session('import_errors'), 0, 50) as $importError)
                    <li>{{ $importError }}</li>
                @endforeach
            </ul>
        </div>
    @endif
    @if ($errors->any())
        <div class="tich-alert tich-alert--error tich-mt-4">
            <ul style="margin:0; padding-left:1.25rem;">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="tich-grid tich-grid--3 tich-mt-6">
        <article class="tich-card tich-stat">
            <p class="tich-caption">Treasury account ({{ $mainAccount }})</p>
            <p class="tich-stat__value">KES {{ number_format($mainAccountBalance, 2) }}</p>
            <p class="tich-caption" style="margin-top: 0.35rem;">Including child accounts</p>
        </article>
        <article class="tich-card tich-stat">
            <p class="tich-caption">Active accounts</p>
            <p class="tich-stat__value">{{ $accounts->count() }}</p>
        </article>
        <article class="tich-card tich-stat">
            <p class="tich-caption">Trial balance</p>
            <p class="tich-stat__value" style="font-size:1rem;">
                Dr {{ number_format($trialBalance['total_debit'], 2) }} /
                Cr {{ number_format($trialBalance['total_credit'], 2) }}
            </p>
        </article>
    </div>

    <!-- Accounts: search -->
    <div class="tich-card tich-mt-4">
        <div class="tich-card__body" style="padding: 0.75rem 1rem;">
            <form method="GET" action="{{ route('finance.gl.index') }}" class="tich-flex tich-flex--wrap tich-gap-2 tich-flex--middle">
                <input type="text" id="search" name="search" class="uf-input" value="{{ request('search') }}" placeholder="Search code or name..." style="width: 220px; height: 34px; padding: 0.25rem 0.5rem; font-size: 0.8125rem;">
                <select id="filter_account_type" name="account_type" class="uf-input" style="width: 150px; height: 34px; padding: 0.25rem 0.5rem; font-size: 0.8125rem;">
                    <option value="">All Types</option>
                    @foreach($chartTypes as $type)
                        <option value="{{ $type }}" @selected(request('account_type') === $type)>{{ ucfirst($type) }}</option>
                    @endforeach
                </select>
                <select id="filter_is_active" name="is_active" class="uf-input" style="width: 130px; height: 34px; padding: 0.25rem 0.5rem; font-size: 0.8125rem;">
                    <option value="">All Status</option>
                    <option value="1" @selected(request('is_active') === '1')>Active</option>
                    <option value="0" @selected(request('is_active') === '0')>Inactive</option>
                </select>
                <button type="submit" class="tich-btn tich-btn-primary tich-btn--sm">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="margin-right: 0.35rem;">
                        <circle cx="11" cy="11" r="8"/>
                        <line x1="21" y1="21" x2="16.65" y2="16.65"/>
                    </svg>
                    Search
                </button>
                <a href="{{ route('finance.gl.index') }}" class="tich-btn tich-btn-ghost tich-btn--sm">Clear</a>
                <div style="flex: 1;"></div>
                @can('finance.chart_of_accounts.manage')
                    <button type="button" class="tich-btn tich-btn-secondary tich-btn--sm" data-open-modal="import-modal">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="margin-right: 0.35rem;">
                            <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
                            <polyline points="17 8 12 3 7 8"/>
                            <line x1="12" y1="3" x2="12" y2="15"/>
                        </svg>
                        Import
                    </button>
                @endcan
            </form>
        </div>
    </div>

    <!-- Accounts -->
    <section class="tich-mt-4">
        <div class="tich-card tich-table-panel">
            @if ($chartAccounts->isNotEmpty())
                <div class="tich-table-wrap">
                    <table class="tich-admin-table">
                        <thead>
                            <tr>
                                <th>Code</th>
                                <th>Account name</th>
                                <th>Type</th>
                                <th>Currency</th>
                                <th>Balance</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($chartAccounts as $row)
                                @php
                                    $account = $row['account'];
                                    $balance = $row['balance'];
                                    $depth = (int) $row['depth'];
                                    $childCount = (int) $row['childCount'];
                                @endphp
                                <tr>
                                    <td>
                                        <span style="display:inline-flex; align-items:center; gap:0.35rem; padding-left: {{ $depth * 1.25 }}rem;">
                                            @if ($depth > 0)
                                                <span class="tich-text--muted" aria-hidden="true">&#8627;</span>
                                            @elseif ($childCount > 0)
                                                <span class="tich-text--muted" aria-hidden="true">&#9662;</span>
                                            @endif
                                            <code>{{ $account->account_code }}</code>
                                        </span>
                                    </td>
                                    <td>
                                        {{ $account->account_name }}
                                        @if ($childCount > 0)
                                            <span class="tich-badge bg-blue-100 text-blue-800">{{ $childCount }} child account{{ $childCount === 1 ? '' : 's' }}</span>
                                        @endif
                                    </td>
                                    <td>{{ ucfirst($account->account_type) }}</td>
                                    <td>{{ $account->currency ?? 'KES' }}</td>
                                    <td>
                                        <span class="tich-text--sm">Dr {{ number_format($balance['debit'], 2) }} / Cr {{ number_format($balance['credit'], 2) }}</span>
                                        <br><strong>{{ number_format(abs($balance['net']), 2) }} {{ $balance['net'] >= 0 ? 'Dr' : 'Cr' }}</strong>
                                        @if ($childCount > 0)
                                            <br><span class="tich-text--sm tich-text--muted">
                                                Own {{ number_format(abs($balance['ownNet']), 2) }} + children {{ number_format(abs($balance['childNet']), 2) }}
                                            </span>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="tich-flex" style="gap:0.35rem; align-items:center; flex-wrap:wrap;">
                                            <a href="{{ route('finance.chart-of-accounts.show', $account) }}" class="tich-btn tich-btn-ghost tich-btn--sm">View</a>
                                            @can('finance.chart_of_accounts.manage')
                                                <a href="{{ route('finance.chart-of-accounts.create', ['parent' => $account->account_code]) }}" class="tich-btn tich-btn-ghost tich-btn--sm">+ Child</a>
                                                <a href="{{ route('finance.chart-of-accounts.edit', $account) }}" class="tich-btn tich-btn-ghost tich-btn--sm">Edit</a>
                                                <form method="POST" action="{{ route('finance.chart-of-accounts.destroy', $account) }}" style="display:inline;"
                                                      onsubmit="return confirm('Delete account {{ $account->account_code }} - {{ $account->account_name }}? This cannot be undone.');">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="tich-btn tich-btn--danger tich-btn--sm">Delete</button>
                                                </form>
                                            @endcan
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                {{ $chartAccounts->links() }}
            @else
                <div class="tich-card__body" style="text-align: center; padding: 3rem;">
                    <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" style="margin: 0 auto 1rem; color: #9ca3af;">
                        <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
                    </svg>
                    <h3 class="tich-h3">No accounts found</h3>
                    <p class="tich-text tich-text--muted tich-mt-2">Create your first main account, then add child accounts under it. You can also import from Excel.</p>
                    @can('finance.chart_of_accounts.manage')
                        <a href="{{ route('finance.chart-of-accounts.create') }}" class="tich-btn tich-btn-primary tich-mt-4">Add First Account</a>
                    @endcan
                </div>
            @endif
        </div>
    </section>

    <!-- Recent journal entries -->
    <section class="tich-mt-6">
        <h2 class="tich-h3 tich-mb-4">Recent journal entries</h2>
        <div class="tich-card tich-table-panel">
            <table class="tich-admin-table">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Type</th>
                        <th>Debit</th>
                        <th>Credit</th>
                        <th>Amount</th>
                        <th>Narration</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($entries as $entry)
                        <tr>
                            <td>{{ $entry->ledger_date?->format('d M Y') }}</td>
                            <td>{{ str_replace('_', ' ', $entry->transaction_type) }}</td>
                            <td>{{ $entry->debit_account_code }}</td>
                            <td>{{ $entry->credit_account_code }}</td>
                            <td>KES {{ number_format(max((float) $entry->debit_amount, (float) $entry->credit_amount), 2) }}</td>
                            <td>{{ $entry->narration }}</td>
                            <td>
                                <a href="{{ route('finance.gl.show', [$entry]) }}" class="tich-link">View</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="tich-table-empty">No journal entries yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    <!-- Import Modal -->
    @can('finance.chart_of_accounts.manage')
    <div id="import-modal" class="tich-modal" aria-hidden="true" role="dialog" aria-modal="true">
        <div class="tich-modal__backdrop" data-close-modal="import-modal"></div>
        <div class="tich-modal__dialog" style="max-width: 420px;">
            <header class="tich-modal__header">
                <h2 class="tich-h3" style="margin: 0;">Import Accounts</h2>
                <button type="button" class="tich-modal__close" data-close-modal="import-modal" aria-label="Close">&times;</button>
            </header>
            <form method="POST" action="{{ route('finance.chart-of-accounts.import') }}" enctype="multipart/form-data" id="import-form" class="tich-modal__body">
                @csrf
                <div style="border: 1.5px dashed #d1d5db; border-radius: 10px; background: #f9fafb; padding: 1.5rem 1rem; text-align: center;">
                    <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="#9ca3af" stroke-width="1.5" style="margin: 0 auto 0.5rem;">
                        <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
                        <polyline points="17 8 12 3 7 8"/>
                        <line x1="12" y1="3" x2="12" y2="15"/>
                    </svg>
                    <p class="tich-text tich-text--sm" style="margin: 0 0 0.75rem; color: #4b5563;">Select a spreadsheet to import</p>
                    <input type="file" id="file" name="file" class="uf-input" accept=".xlsx,.xls,.csv" required
                           style="font-size: 0.8125rem; padding: 0.4rem; background: #fff;">
                </div>
                <p class="tich-text tich-text--sm tich-text--muted" style="margin: 0.6rem 0 0;">.xlsx, .xls or .csv &middot; up to 10MB</p>
                <p class="tich-text tich-text--sm tich-text--muted" style="margin: 0.35rem 0 0;">
                    Main and child accounts can be imported together. Put the parent code in
                    <code>parent_account_code</code>, or use a decimal child code such as
                    <code>1114.01</code> and it is matched to <code>1114</code> automatically.
                </p>
                <p class="tich-text tich-text--sm tich-text--muted" style="margin: 0.35rem 0 0;">
                    Accepted account types: {{ implode(', ', $acceptedAccountTypes) }}.
                    Columns: <code>account_code</code>, <code>account_name</code>, <code>account_type</code>,
                    <code>currency</code>, <code>parent_account_code</code>, <code>debit</code>, <code>credit</code>,
                    <code>balance</code>, <code>balance_side</code> (Dr/Cr), <code>is_active</code>.
                </p>
                <p class="tich-text tich-text--sm tich-text--muted" style="margin: 0.35rem 0 0;">
                    Export the current chart, edit in Excel, then import: existing codes are updated,
                    new codes are created, and balance differences post as ledger adjustments.
                </p>

                @unless (\App\Support\PhpSpreadsheetAvailability::isAvailable())
                    <div class="tich-alert tich-alert--warning" style="margin-top: 0.75rem; padding: 0.5rem 0.75rem;">
                        <strong>Excel (.xlsx/.xls) export is unavailable</strong>
                        <p class="tich-text tich-text--sm" style="margin:0.35rem 0 0;">
                            {{ \App\Support\PhpSpreadsheetAvailability::missingMessage('Chart of accounts Excel') }}
                            CSV import may still work.
                        </p>
                    </div>
                @endunless
                @unless (class_exists(\ZipArchive::class) && class_exists(\DOMDocument::class))
                    <div class="tich-alert tich-alert--warning" style="margin-top: 0.75rem; padding: 0.5rem 0.75rem;">
                        <strong>.xlsx cannot be read on this server</strong>
                        (need PHP zip + xml). ICT: <a href="{{ route('ict.php-runtime') }}">PHP runtime check</a>.
                    </div>
                @endunless

                @error('file')
                    <div class="tich-alert tich-alert--error" style="margin-top: 0.75rem; padding: 0.5rem 0.75rem;">{{ $message }}</div>
                @enderror

                <footer class="tich-modal__footer" style="margin-top: 1rem;">
                    <a href="{{ route('finance.chart-of-accounts.export') }}" class="tich-btn tich-btn-ghost tich-btn--sm">
                        Export current chart
                    </a>
                    <a href="{{ route('finance.chart-of-accounts.template') }}" class="tich-btn tich-btn-ghost tich-btn--sm">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="margin-right: 0.35rem;">
                            <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
                            <polyline points="17 8 12 3 7 8"/>
                            <line x1="12" y1="3" x2="12" y2="15"/>
                        </svg>
                        Template
                    </a>
                    <div style="flex: 1;"></div>
                    <button type="button" class="tich-btn tich-btn-secondary tich-btn--sm" data-close-modal="import-modal">Cancel</button>
                    <button type="submit" class="tich-btn tich-btn-primary tich-btn--sm">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="margin-right: 0.35rem;">
                            <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
                            <polyline points="17 8 12 3 7 8"/>
                            <line x1="12" y1="3" x2="12" y2="15"/>
                        </svg>
                        Import
                    </button>
                </footer>
            </form>
        </div>
    </div>

    <!-- Delete All Modal -->
    <div id="delete-all-modal" class="tich-modal" aria-hidden="true" role="dialog" aria-modal="true">
        <div class="tich-modal__backdrop" data-close-modal="delete-all-modal"></div>
        <div class="tich-modal__dialog" style="max-width: 440px;">
            <header class="tich-modal__header">
                <h2 class="tich-h3" style="margin: 0;">Delete All Accounts</h2>
                <button type="button" class="tich-modal__close" data-close-modal="delete-all-modal" aria-label="Close">&times;</button>
            </header>
            <form method="POST" action="{{ route('finance.chart-of-accounts.destroy-all') }}" id="delete-all-form" class="tich-modal__body">
                @csrf
                @method('DELETE')
                <p class="tich-text" style="margin: 0;">
                    This removes every chart of accounts entry so you can import a fresh chart.
                    Accounts that already have journal entries are kept, so the ledger stays intact.
                </p>
                <p class="tich-text tich-text--sm tich-text--muted" style="margin: 0.6rem 0 0;">
                    Type <strong>DELETE ALL</strong> below to confirm.
                </p>
                <input type="text" id="delete-all-confirmation" name="confirmation" class="uf-input" placeholder="DELETE ALL" required
                       style="width: 100%; margin-top: 0.5rem;" autocomplete="off">
                @error('confirmation')
                    <div class="tich-alert tich-alert--error" style="margin-top: 0.75rem; padding: 0.5rem 0.75rem;">{{ $message }}</div>
                @enderror
                <footer class="tich-modal__footer" style="margin-top: 1rem;">
                    <div style="flex: 1;"></div>
                    <button type="button" class="tich-btn tich-btn-secondary tich-btn--sm" data-close-modal="delete-all-modal">Cancel</button>
                    <button type="submit" class="tich-btn tich-btn--danger tich-btn--sm">Delete all accounts</button>
                </footer>
            </form>
        </div>
    </div>
    @endcan
@endsection

@section('scripts')
    @parent
    @include('admin.partials.tich-modal-assets')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const form = document.getElementById('import-form');

            if (form) {
                form.addEventListener('submit', function (e) {
                    const fileInput = form.querySelector('input[name="file"]');
                    if (!fileInput.files.length) {
                        e.preventDefault();
                        alert('Please select a file to upload.');
                    }
                });
            }

            const deleteAllForm = document.getElementById('delete-all-form');

            if (deleteAllForm) {
                deleteAllForm.addEventListener('submit', function (e) {
                    const input = document.getElementById('delete-all-confirmation');
                    if (input.value !== 'DELETE ALL') {
                        e.preventDefault();
                        alert('Type DELETE ALL exactly to confirm.');
                        input.focus();
                    }
                });
            }
        });
    </script>
    @if ($errors->has('confirmation'))
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                if (window.tichOpenModal) {
                    window.tichOpenModal('delete-all-modal');
                }
            });
        </script>
    @endif
@endsection
