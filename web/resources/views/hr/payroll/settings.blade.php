@extends('layouts.hr')

@section('title', 'KRA Tax Settings')

@section('hr-content')
    @php
        $settingsPayload = [
            'deductionTypes' => $deductionTypes->map(fn ($type) => [
                'id' => $type->id,
                'label' => $type->label,
                'value_type' => $type->value_type,
                'fixed_amount' => $type->fixed_amount,
                'employer_rate_percent' => $type->employer_rate_percent,
                'reduces_taxable' => (bool) $type->reduces_taxable,
                'is_active' => (bool) $type->is_active,
                'display_order' => $type->display_order,
            ])->values()->all(),
            'bands' => $bands->map(function ($band) {
                $deductions = [];

                foreach ($band->deductionRates as $rate) {
                    if ($rate->payroll_deduction_type_id) {
                        $deductions[(string) $rate->payroll_deduction_type_id] = $rate->rate_percent;
                    }
                }

                return [
                    'id' => $band->id,
                    'label' => $band->label,
                    'min_amount' => $band->min_amount,
                    'max_amount' => $band->max_amount,
                    'rate_percent' => $band->rate_percent,
                    'is_active' => (bool) $band->is_active,
                    'display_order' => $band->display_order,
                    'deductions' => $deductions,
                ];
            })->values()->all(),
        ];
    @endphp

    <div class="tich-mb-8">
        <div class="tich-flex tich-flex--between tich-flex--start">
            <div>
                <h1 class="tich-h1">KRA tax settings</h1>
                <p class="tich-text tich-mt-2">Manage PAYE bands and tax items (NSSF, SHA/SHIF, housing levy, personal relief, etc.). Use Edit to change values; drag rows to reorder.</p>
            </div>
            <a href="{{ route('hr.payroll.index') }}" class="tich-btn tich-btn-secondary">&larr; Back to payroll</a>
        </div>
    </div>

    <style>
        .tich-tax-sortable tbody tr[data-sortable-item],
        .tich-tax-sortable tbody tr[data-sortable-band] { cursor: default; }
        .tich-tax-sortable tbody tr.is-dragging { opacity: 0.45; cursor: grabbing; }
        .tich-drag-handle {
            color: var(--tich-muted, #64748b);
            user-select: none;
            width: 2rem;
            text-align: center;
            font-size: 1.1rem;
            line-height: 1;
            cursor: grab;
        }
        .tich-drag-handle:active { cursor: grabbing; }
    </style>

    <form method="POST" action="{{ route('hr.payroll.settings.update') }}" id="tax-settings-form">
        @csrf
        @method('PUT')
        <div id="tax-settings-hidden-fields"></div>

        <div class="tich-card tich-table-panel tich-mb-6">
            <div class="tich-flex tich-flex--between tich-mb-4" style="flex-wrap: wrap; gap: 0.75rem;">
                <div>
                    <h2 class="tich-h3">Tax items</h2>
                    <p class="tich-caption tich-mt-2">Statutory deductions and reliefs included in payroll tax calculations.</p>
                </div>
                <button type="button" class="tich-btn tich-btn-ghost" id="add-deduction-item-btn">+ Add tax item</button>
            </div>

            <p id="item-order-status" class="tich-caption tich-mb-4" aria-live="polite"></p>

            <div class="tich-table-wrap">
                <table class="tich-admin-table tich-tax-sortable">
                    <thead>
                        <tr>
                            <th style="width: 2.5rem;"></th>
                            <th>Label</th>
                            <th>Type</th>
                            <th>Details</th>
                            <th>Status</th>
                            <th style="width: 6rem;"></th>
                        </tr>
                    </thead>
                    <tbody id="deduction-items-tbody"></tbody>
                </table>
            </div>
        </div>

        <div class="tich-card tich-table-panel tich-mb-8">
            <div class="tich-flex tich-flex--between tich-mb-4" style="flex-wrap: wrap; gap: 0.75rem;">
                <div>
                    <h2 class="tich-h3">PAYE tax bands (KRA)</h2>
                    <p class="tich-caption tich-mt-2">Higher bands must stay after lower bands when reordering.</p>
                </div>
                <button type="button" class="tich-btn tich-btn-ghost" id="add-band-btn">+ Add band</button>
            </div>

            <p id="band-order-status" class="tich-caption tich-mb-4" aria-live="polite"></p>

            <div class="tich-table-wrap" style="overflow-x: auto;">
                <table class="tich-admin-table tich-tax-sortable" id="bands-table">
                    <thead>
                        <tr id="bands-header-row"></tr>
                    </thead>
                    <tbody id="bands-tbody"></tbody>
                    <tfoot>
                        <tr id="cumulative-footer-row"></tr>
                    </tfoot>
                </table>
            </div>
        </div>

        <button type="submit" class="tich-btn tich-btn-primary">Save tax settings</button>
    </form>

    {{-- Tax item modal --}}
    <div id="deduction-item-modal" class="tich-modal" aria-hidden="true" role="dialog" aria-modal="true">
        <div class="tich-modal__backdrop" data-close-modal="deduction-item-modal"></div>
        <div class="tich-modal__dialog">
            <header class="tich-modal__header">
                <h2 class="tich-h3" id="deduction-item-modal-title" style="margin: 0;">Edit tax item</h2>
                <button type="button" class="tich-modal__close" data-close-modal="deduction-item-modal" aria-label="Close">&times;</button>
            </header>
            <div class="tich-modal__body">
                <div class="tich-form-group">
                    <label class="tich-label" for="deduction-item-label">Label</label>
                    <input type="text" id="deduction-item-label" class="tich-input" required placeholder="e.g. NSSF, Personal relief">
                </div>
                <div class="tich-form-group">
                    <label class="tich-label" for="deduction-item-value-type">Calculation type</label>
                    <select id="deduction-item-value-type" class="tich-input">
                        <option value="band_percent">Band percentage (rate per PAYE bracket)</option>
                        <option value="global_fixed">Fixed amount (e.g. personal relief)</option>
                    </select>
                </div>
                <div class="tich-form-group" id="deduction-item-fixed-group" hidden>
                    <label class="tich-label" for="deduction-item-fixed-amount">Fixed amount (KES)</label>
                    <input type="number" id="deduction-item-fixed-amount" class="tich-input" min="0" step="0.01" placeholder="2400">
                </div>
                <div id="deduction-item-band-fields">
                    <div class="tich-form-group">
                        <label class="tich-label" for="deduction-item-employer-rate">Employer rate (%)</label>
                        <input type="number" id="deduction-item-employer-rate" class="tich-input" min="0" max="100" step="0.01" placeholder="Optional">
                    </div>
                    <div class="tich-form-group">
                        <label class="tich-label">
                            <input type="checkbox" id="deduction-item-reduces-taxable" value="1"> Reduces taxable income
                        </label>
                    </div>
                </div>
                <div class="tich-form-group">
                    <label class="tich-label">
                        <input type="checkbox" id="deduction-item-active" value="1" checked> Active
                    </label>
                </div>
            </div>
            <footer class="tich-modal__footer">
                <button type="button" class="tich-btn tich-btn-ghost" id="deduction-item-delete-btn" style="color: #c0392b; margin-right: auto;">Delete</button>
                <button type="button" class="tich-btn tich-btn-secondary" data-close-modal="deduction-item-modal">Cancel</button>
                <button type="button" class="tich-btn tich-btn-primary" id="deduction-item-save-btn">Save item</button>
            </footer>
        </div>
    </div>

    {{-- PAYE band modal --}}
    <div id="band-modal" class="tich-modal" aria-hidden="true" role="dialog" aria-modal="true">
        <div class="tich-modal__backdrop" data-close-modal="band-modal"></div>
        <div class="tich-modal__dialog tich-modal__dialog--wide">
            <header class="tich-modal__header">
                <h2 class="tich-h3" id="band-modal-title" style="margin: 0;">Edit PAYE band</h2>
                <button type="button" class="tich-modal__close" data-close-modal="band-modal" aria-label="Close">&times;</button>
            </header>
            <div class="tich-modal__body">
                <div class="tich-grid tich-grid--2">
                    <div class="tich-form-group">
                        <label class="tich-label" for="band-label">Label</label>
                        <input type="text" id="band-label" class="tich-input" required placeholder="First KES 24,000">
                    </div>
                    <div class="tich-form-group">
                        <label class="tich-label" for="band-rate-percent">PAYE rate (%)</label>
                        <input type="number" id="band-rate-percent" class="tich-input" min="0" max="100" step="0.01" required>
                    </div>
                    <div class="tich-form-group">
                        <label class="tich-label" for="band-min-amount">Min amount (KES)</label>
                        <input type="number" id="band-min-amount" class="tich-input" min="0" step="0.01" required>
                    </div>
                    <div class="tich-form-group">
                        <label class="tich-label" for="band-max-amount">Max amount (KES)</label>
                        <input type="number" id="band-max-amount" class="tich-input" min="0" step="0.01" placeholder="Leave empty for no limit">
                    </div>
                </div>
                <div class="tich-form-group">
                    <label class="tich-label">
                        <input type="checkbox" id="band-active" value="1" checked> Active
                    </label>
                </div>
                <h3 class="tich-h4 tich-mt-4 tich-mb-2">Deduction rates for this band</h3>
                <div class="tich-grid tich-grid--3" id="band-deduction-fields"></div>
            </div>
            <footer class="tich-modal__footer">
                <button type="button" class="tich-btn tich-btn-ghost" id="band-delete-btn" style="color: #c0392b; margin-right: auto;">Delete</button>
                <button type="button" class="tich-btn tich-btn-secondary" data-close-modal="band-modal">Cancel</button>
                <button type="button" class="tich-btn tich-btn-primary" id="band-save-btn">Save band</button>
            </footer>
        </div>
    </div>

    <script type="application/json" id="payroll-tax-settings-data">@json($settingsPayload)</script>
    @include('admin.partials.tich-modal-assets')
    <script src="{{ asset('js/tich-payroll-tax-settings.js') }}" defer></script>
@endsection
