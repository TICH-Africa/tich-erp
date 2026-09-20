@extends($moduleContext['layout'])

@section('title', $department->dept_name.' · '.$pageTitle)

@section($moduleContext['content_section'])
    <x-page-toolbar
        :title="$pageTitle"
        meta="Independent of budgeting · Q1–Q4 outputs · Routes to M&E on submit"
    >
        <x-slot:actions>
            <a href="{{ route($indexRoute) }}" class="tich-btn tich-btn-ghost">Back to technical plans</a>
        </x-slot:actions>
    </x-page-toolbar>

    @if ($plan?->status === 'returned' && $plan->me_notes)
        <div class="tich-alert tich-alert--info tich-mt-4">
            <strong>Returned by M&amp;E.</strong> Update the plan and resubmit.
            <pre class="tich-pre tich-mt-2" style="white-space:pre-wrap; margin:0;">{{ $plan->me_notes }}</pre>
        </div>
    @endif

    @error('plan')
        <div class="tich-alert tich-alert--error tich-mt-4">{{ $message }}</div>
    @enderror
    @error('quarters')
        <div class="tich-alert tich-alert--error tich-mt-4">{{ $message }}</div>
    @enderror

    <div class="uf-form">
        <form method="POST" action="{{ $formAction }}" data-uf="ready" id="dept-tech-plan-form" data-tich-autosave="1">
            @csrf
            @if ($plan)
                @method('PUT')
            @endif

            <div class="uf-amount-bar">
                <div>
                    <div class="uf-amount-bar__ref">TP · {{ $department->dept_code }}</div>
                    <div class="uf-amount-bar__sum">{{ $department->dept_name }}</div>
                </div>
                <span class="uf-badge">{{ $plan ? 'Edit' : 'Draft' }}</span>
            </div>

            <div class="uf-form-section">
                <div class="uf-section-head">Plan details</div>
                <div class="uf-section-body">
                    <div class="uf-form-grid-2">
                        <div class="uf-field">
                            <label for="title">Title <span class="uf-req">*</span></label>
                            <input
                                type="text"
                                id="title"
                                name="title"
                                value="{{ old('title', $plan?->title) }}"
                                required
                                maxlength="300"
                                placeholder="e.g. FY2026 departmental technical plan"
                                class="{{ $errors->has('title') ? 'is-invalid' : '' }}"
                            >
                            @error('title')
                                <span class="uf-error">{{ $message }}</span>
                            @enderror
                        </div>
                        <div class="uf-field">
                            <label for="fiscal_year">Fiscal year</label>
                            <input
                                type="text"
                                id="fiscal_year"
                                name="fiscal_year"
                                value="{{ old('fiscal_year', $plan?->fiscal_year ?? now()->year) }}"
                                maxlength="20"
                                placeholder="e.g. 2026"
                            >
                        </div>
                    </div>
                    <div class="uf-field">
                        <label for="summary">Plan summary</label>
                        <textarea
                            id="summary"
                            name="summary"
                            rows="3"
                            maxlength="5000"
                            placeholder="Optional narrative for M&E"
                            class="{{ $errors->has('summary') ? 'is-invalid' : '' }}"
                        >{{ old('summary', $plan?->summary) }}</textarea>
                    </div>
                </div>
            </div>

            @foreach ($quarters as $qKey => $qLabel)
                @php
                    $outputs = $quarterOutputs[$qKey]['outputs'] ?? [['output'=>'','activity'=>'','costable_item'=>'','planned'=>'','planned_unit'=>'']];
                @endphp
                <div class="uf-form-section js-tp-quarter" data-quarter="{{ $qKey }}">
                    <div class="uf-section-head">{{ $qLabel }}</div>
                    <div class="uf-section-body">
                        <div class="tich-flex-wrap" style="justify-content:flex-end;margin-bottom:1rem;">
                            <button type="button" class="uf-btn uf-btn-secondary js-tp-add-output" data-quarter="{{ $qKey }}">+ Add output</button>
                        </div>
                        <div class="tich-table-wrap">
                            <table class="tich-admin-table">
                                <thead>
                                    <tr>
                                        <th style="min-width:12rem;">Output</th>
                                        <th style="min-width:12rem;">Activity</th>
                                        <th style="min-width:10rem;">Costable item</th>
                                        <th style="min-width:7rem;">Planned</th>
                                        <th style="min-width:6rem;">Unit</th>
                                        <th style="width:3rem;"></th>
                                    </tr>
                                </thead>
                                <tbody class="js-tp-outputs-body" data-quarter="{{ $qKey }}">
                                    @foreach ($outputs as $oi => $oRow)
                                        <tr class="js-tp-output-row">
                                            <td><input type="text" name="quarters[{{ $qKey }}][outputs][{{ $oi }}][output]" class="tich-input" value="{{ $oRow['output'] ?? '' }}" maxlength="2000" placeholder="Strategic target"></td>
                                            <td><input type="text" name="quarters[{{ $qKey }}][outputs][{{ $oi }}][activity]" class="tich-input" value="{{ $oRow['activity'] ?? '' }}" maxlength="2000" placeholder="Implementation task"></td>
                                            <td><input type="text" name="quarters[{{ $qKey }}][outputs][{{ $oi }}][costable_item]" class="tich-input" value="{{ $oRow['costable_item'] ?? '' }}" maxlength="500" placeholder="Budget code / resource"></td>
                                            <td><input type="number" name="quarters[{{ $qKey }}][outputs][{{ $oi }}][planned]" class="tich-input" value="{{ $oRow['planned'] ?? '' }}" min="0" step="0.01" placeholder="0"></td>
                                            <td><input type="text" name="quarters[{{ $qKey }}][outputs][{{ $oi }}][planned_unit]" class="tich-input" value="{{ $oRow['planned_unit'] ?? '' }}" maxlength="50" placeholder="e.g. students"></td>
                                            <td><button type="button" class="tich-btn tich-btn-ghost js-tp-remove-output" title="Remove" aria-label="Remove">&times;</button></td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            @endforeach

            <div class="uf-form-section">
                <div class="uf-section-head">Submit</div>
                <div class="uf-section-body">
                    <div class="uf-form-actions">
                        <a href="{{ route($indexRoute) }}" class="uf-btn uf-btn-secondary">Cancel</a>
                        <button type="submit" class="uf-btn uf-btn-primary">{{ $submitLabel }}</button>
                    </div>
                </div>
            </div>

            <p class="uf-form-footnote"><span class="uf-req">*</span> Required field · At least one complete output (output + activity) across the year</p>
        </form>
    </div>

    <script>
    (function () {
        document.querySelectorAll('.js-tp-quarter').forEach(function (block) {
            var qKey = block.getAttribute('data-quarter');
            var body = block.querySelector('.js-tp-outputs-body');
            var addBtn = block.querySelector('.js-tp-add-output');
            if (!body || !addBtn) return;

            function reindex() {
                body.querySelectorAll('.js-tp-output-row').forEach(function (row, index) {
                    row.querySelectorAll('input[name*="[outputs]"]').forEach(function (input) {
                        input.name = input.name.replace(
                            /quarters\[\d+\]\[outputs\]\[\d+\]/,
                            'quarters[' + qKey + '][outputs][' + index + ']'
                        );
                    });
                });
            }

            function bindRow(row) {
                var removeBtn = row.querySelector('.js-tp-remove-output');
                if (!removeBtn) return;
                removeBtn.addEventListener('click', function () {
                    if (body.querySelectorAll('.js-tp-output-row').length <= 1) {
                        row.querySelectorAll('input').forEach(function (input) { input.value = ''; });
                        return;
                    }
                    row.remove();
                    reindex();
                });
            }

            addBtn.addEventListener('click', function () {
                var index = body.querySelectorAll('.js-tp-output-row').length;
                var row = document.createElement('tr');
                row.className = 'js-tp-output-row';
                row.innerHTML =
                    '<td><input type="text" name="quarters[' + qKey + '][outputs][' + index + '][output]" class="tich-input" maxlength="2000" placeholder="Strategic target"></td>' +
                    '<td><input type="text" name="quarters[' + qKey + '][outputs][' + index + '][activity]" class="tich-input" maxlength="2000" placeholder="Implementation task"></td>' +
                    '<td><input type="text" name="quarters[' + qKey + '][outputs][' + index + '][costable_item]" class="tich-input" maxlength="500" placeholder="Budget code / resource"></td>' +
                    '<td><input type="number" name="quarters[' + qKey + '][outputs][' + index + '][planned]" class="tich-input" min="0" step="0.01" placeholder="0"></td>' +
                    '<td><input type="text" name="quarters[' + qKey + '][outputs][' + index + '][planned_unit]" class="tich-input" maxlength="50" placeholder="e.g. students"></td>' +
                    '<td><button type="button" class="tich-btn tich-btn-ghost js-tp-remove-output" title="Remove" aria-label="Remove">&times;</button></td>';
                body.appendChild(row);
                bindRow(row);
            });

            body.querySelectorAll('.js-tp-output-row').forEach(bindRow);
        });
    })();
    </script>
@endsection
