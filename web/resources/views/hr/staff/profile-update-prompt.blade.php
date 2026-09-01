@extends('layouts.hr')

@section('title', 'Request profile update - ' . $staff->fullName())

@section('hr-content')
    @php
        use App\Services\EmployeeProfileCompletenessService;

        $fieldLabels = EmployeeProfileCompletenessService::requestableFieldLabels();
        $grouped = [
            'Identity' => [
                'description' => 'Name, photo, and personal details',
                'fields' => ['photo', 'first_name', 'middle_name', 'surname', 'date_of_birth', 'gender', 'marital_status'],
            ],
            'Contact' => [
                'description' => 'Email, phone, and address information',
                'fields' => ['primary_email', 'phone_number', 'alt_phone_number', 'physical_address', 'postal_address', 'postal_code', 'home_county'],
            ],
            'Emergency' => [
                'description' => 'Emergency contact person',
                'fields' => ['emergency_contact_name', 'emergency_contact_phone', 'emergency_contact_relationship'],
            ],
            'Qualifications' => [
                'description' => 'Academic and professional certificates',
                'fields' => ['qualification'],
            ],
        ];
        $selectedFields = old('fields', []);
        $notifyEmail = $staff->organisation_email ?: $staff->primary_email;
    @endphp

    <x-page-toolbar title="Request profile update">
        <x-slot:actions>
            <a href="{{ route('hr.staff.show', $staff) }}" class="tich-btn tich-btn-ghost">Back to profile</a>
        </x-slot:actions>
    </x-page-toolbar>

    <div class="tich-prompt-page">
    <div class="tich-prompt-page-header">
        <div class="tich-prompt-page-header__photo">
            @if ($staff->photoUrl())
                <img src="{{ $staff->photoUrl() }}" alt="{{ $staff->fullName() }}">
            @else
                <span class="tich-prompt-page-header__initials">{{ $staff->initials() }}</span>
            @endif
        </div>
        <div>
            <p class="tich-prompt-page-header__eyebrow">Employee</p>
            <h2 class="tich-prompt-page-header__name">{{ $staff->fullName() }}</h2>
            <p class="tich-prompt-page-header__meta">
                {{ $staff->employee_number }}
                @if ($staff->job_title)
                    · {{ $staff->job_title }}
                @endif
                @if ($staff->department)
                    · {{ $staff->department->dept_name }}
                @endif
            </p>
        </div>
    </div>

    <div class="tich-prompt-callout">
        <p>Select the profile items the employee should update. They will receive an email at <strong>{{ $notifyEmail ?: 'their registered email' }}</strong> with a link to <strong>My Employee Portal</strong>, where the selected fields will be highlighted.</p>
    </div>

    <form method="POST" action="{{ route('hr.staff.profile-update-prompt.store', $staff) }}" class="tich-prompt-form" id="profile-update-prompt-form">
        @csrf

        @error('fields')
            <div class="tich-prompt-error">{{ $message }}</div>
        @enderror

        <div class="tich-prompt-layout">
            <div class="tich-prompt-layout__main">
                @foreach ($grouped as $groupLabel => $group)
                    <section class="tich-prompt-section" data-prompt-section>
                        <div class="tich-prompt-section__head">
                            <div>
                                <h3 class="tich-prompt-section__title">{{ $groupLabel }}</h3>
                                <p class="tich-prompt-section__desc">{{ $group['description'] }}</p>
                            </div>
                            <div class="tich-prompt-section__actions">
                                <button type="button" class="tich-btn tich-btn-ghost tich-btn--sm" data-prompt-select-all>Select all</button>
                                <button type="button" class="tich-btn tich-btn-ghost tich-btn--sm" data-prompt-clear-all>Clear</button>
                            </div>
                        </div>

                        <div class="tich-prompt-fields">
                            @foreach ($group['fields'] as $key)
                                @if (! isset($fieldLabels[$key]))
                                    @continue
                                @endif
                                @php
                                    $isSelected = in_array($key, $selectedFields, true);
                                    $current = $currentValues[$key] ?? '—';
                                    $isEmpty = $current === '—' || $current === 'No photo uploaded' || $current === 'No qualifications on file';
                                @endphp
                                <label class="tich-prompt-field{{ $isSelected ? ' is-selected' : '' }}">
                                    <input
                                        type="checkbox"
                                        name="fields[]"
                                        value="{{ $key }}"
                                        class="tich-prompt-field__input"
                                        @checked($isSelected)
                                    >
                                    <span class="tich-prompt-field__card">
                                        <span class="tich-prompt-field__top">
                                            <span class="tich-prompt-field__checkbox" aria-hidden="true"></span>
                                            <span class="tich-prompt-field__label">{{ $fieldLabels[$key] }}</span>
                                        </span>
                                        <span class="tich-prompt-field__current">
                                            <span class="tich-prompt-field__current-label">Current value</span>
                                            @if ($key === 'photo' && $staff->photoUrl())
                                                <span class="tich-prompt-field__photo-preview">
                                                    <img src="{{ $staff->photoUrl() }}" alt="Current photo">
                                                    <span>Photo on file</span>
                                                </span>
                                            @else
                                                <span class="tich-prompt-field__value{{ $isEmpty ? ' is-empty' : '' }}">{{ $current }}</span>
                                            @endif
                                        </span>
                                    </span>
                                </label>
                            @endforeach
                        </div>
                    </section>
                @endforeach
            </div>

            <aside class="tich-prompt-sidebar">
                <div class="tich-prompt-sidebar__card">
                    <h3 class="tich-prompt-sidebar__title">Request summary</h3>
                    <p class="tich-prompt-sidebar__count">
                        <span id="prompt-selected-count">{{ count($selectedFields) }}</span>
                        <span id="prompt-selected-label">{{ count($selectedFields) === 1 ? 'item' : 'items' }} selected</span>
                    </p>

                    <div id="prompt-selected-list" class="tich-prompt-sidebar__list">
                        @foreach ($selectedFields as $key)
                            @if (isset($fieldLabels[$key]))
                                <span class="tich-prompt-sidebar__chip">{{ $fieldLabels[$key] }}</span>
                            @endif
                        @endforeach
                    </div>

                    <div class="tich-form-group tich-mt-4">
                        <label for="profile_prompt_notes" class="tich-label">Message for employee</label>
                        <textarea
                            id="profile_prompt_notes"
                            name="notes"
                            rows="4"
                            class="tich-input"
                            maxlength="2000"
                            placeholder="Optional note, e.g. Please upload a recent passport-style photo and confirm your emergency contact."
                        >{{ old('notes') }}</textarea>
                    </div>

                    <button type="submit" class="tich-btn tich-btn-primary tich-btn--block tich-mt-4" id="prompt-submit-btn">
                        Send profile update request
                    </button>
                    <a href="{{ route('hr.staff.show', $staff) }}" class="tich-btn tich-btn-ghost tich-btn--block tich-mt-2">Cancel</a>
                </div>
            </aside>
        </div>
    </form>
    </div>

    <style>
        .tich-prompt-page {
            --prompt-callout-bg: var(--tich-blue-light);
            --prompt-callout-border: color-mix(in srgb, var(--tich-blue) 28%, var(--tich-neutral-border));
            --prompt-callout-text: var(--tich-blue-dark);
            --prompt-field-bg: var(--tich-surface-muted, #f8fafc);
            --prompt-field-inner-bg: var(--tich-surface, var(--tich-white));
            --prompt-field-hover-border: color-mix(in srgb, var(--tich-blue) 45%, var(--tich-neutral-border));
            --prompt-field-selected-bg: var(--tich-blue-light);
            --prompt-field-selected-shadow: color-mix(in srgb, var(--tich-blue) 18%, transparent);
            --prompt-chip-bg: var(--tich-blue-light);
            --prompt-chip-text: var(--tich-blue-dark);
            --prompt-chip-border: color-mix(in srgb, var(--tich-blue) 32%, var(--tich-neutral-border));
            font-size: 0.8rem;
        }

        [data-theme="dark"] .tich-prompt-page {
            --prompt-callout-text: var(--tich-blue);
            --prompt-field-inner-bg: var(--tich-neutral, #0f1419);
            --prompt-chip-text: var(--tich-blue);
        }

        .tich-prompt-page-header {
            display: flex;
            align-items: center;
            gap: 0.8rem;
            padding: 0.8rem 1rem;
            margin-bottom: 0.8rem;
            background: var(--tich-surface, var(--tich-white));
            border: 1px solid var(--tich-neutral-border);
            border-radius: var(--radius-md);
        }

        .tich-prompt-page-header__photo {
            width: 3.6rem;
            height: 3.6rem;
            border-radius: 50%;
            overflow: hidden;
            flex-shrink: 0;
            border: 2px solid var(--tich-neutral-border);
            background: var(--tich-surface-muted, #f1f5f9);
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .tich-prompt-page-header__photo img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .tich-prompt-page-header__initials {
            font-family: var(--font-heading);
            font-size: 0.88rem;
            font-weight: 700;
            color: var(--tich-blue);
        }

        .tich-prompt-page-header__eyebrow {
            margin: 0;
            font-size: 0.6rem;
            font-weight: 600;
            letter-spacing: 0.04em;
            text-transform: uppercase;
            color: var(--tich-neutral-muted, #64748b);
        }

        .tich-prompt-page-header__name {
            margin: 0.12rem 0 0;
            font-family: var(--font-heading);
            font-size: 1rem;
            font-weight: 700;
            color: var(--tich-text, var(--tich-grey));
        }

        .tich-prompt-page-header__meta {
            margin: 0.2rem 0 0;
            font-size: 0.7rem;
            color: var(--tich-neutral-muted, #64748b);
        }

        .tich-prompt-callout {
            padding: 0.72rem 0.88rem;
            margin-bottom: 1rem;
            background: var(--prompt-callout-bg);
            border: 1px solid var(--prompt-callout-border);
            border-radius: var(--radius-md);
            color: var(--prompt-callout-text);
            font-size: 0.72rem;
            line-height: 1.5;
        }

        .tich-prompt-callout p { margin: 0; }

        .tich-prompt-callout strong {
            color: var(--tich-text, var(--tich-grey));
        }

        .tich-prompt-error {
            margin-bottom: 0.8rem;
            padding: 0.6rem 0.8rem;
            border-radius: var(--radius-md);
            background: var(--tich-error-bg);
            border: 1px solid var(--tich-error-border);
            color: var(--tich-error-text);
            font-size: 0.7rem;
        }

        .tich-prompt-layout {
            display: grid;
            gap: 1rem;
            align-items: start;
        }

        @media (min-width: 1024px) {
            .tich-prompt-layout {
                grid-template-columns: minmax(0, 1fr) 16rem;
            }
        }

        .tich-prompt-section {
            background: var(--tich-surface, var(--tich-white));
            border: 1px solid var(--tich-neutral-border);
            border-radius: var(--radius-md);
            padding: 0.88rem 1rem;
            margin-bottom: 0.8rem;
        }

        .tich-prompt-section__head {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 0.8rem;
            margin-bottom: 0.8rem;
            padding-bottom: 0.68rem;
            border-bottom: 1px solid var(--tich-neutral-border);
        }

        .tich-prompt-section__title {
            margin: 0;
            font-size: 0.8rem;
            font-weight: 700;
            color: var(--tich-text, var(--tich-grey));
        }

        .tich-prompt-section__desc {
            margin: 0.16rem 0 0;
            font-size: 0.65rem;
            color: var(--tich-neutral-muted, #64748b);
        }

        .tich-prompt-section__actions {
            display: flex;
            gap: 0.28rem;
            flex-shrink: 0;
        }

        .tich-prompt-fields {
            display: grid;
            gap: 0.6rem;
        }

        @media (min-width: 768px) {
            .tich-prompt-fields {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        .tich-prompt-field {
            display: block;
            cursor: pointer;
        }

        .tich-prompt-field__input {
            position: absolute;
            opacity: 0;
            pointer-events: none;
        }

        .tich-prompt-field__card {
            display: block;
            height: 100%;
            padding: 0.68rem 0.76rem;
            border: 1px solid var(--tich-neutral-border);
            border-radius: var(--radius-md);
            background: var(--prompt-field-bg);
            transition: border-color 0.15s ease, background 0.15s ease, box-shadow 0.15s ease;
        }

        .tich-prompt-field:hover .tich-prompt-field__card {
            border-color: var(--prompt-field-hover-border);
        }

        .tich-prompt-field.is-selected .tich-prompt-field__card {
            border-color: var(--tich-blue);
            background: var(--prompt-field-selected-bg);
            box-shadow: 0 0 0 1px var(--prompt-field-selected-shadow);
        }

        .tich-prompt-field__top {
            display: flex;
            align-items: center;
            gap: 0.44rem;
            margin-bottom: 0.44rem;
        }

        .tich-prompt-field__checkbox {
            width: 0.88rem;
            height: 0.88rem;
            border: 2px solid var(--tich-neutral-border);
            border-radius: 0.2rem;
            background: var(--tich-surface, var(--tich-white));
            flex-shrink: 0;
            position: relative;
        }

        .tich-prompt-field.is-selected .tich-prompt-field__checkbox {
            border-color: var(--tich-blue);
            background: var(--tich-blue);
        }

        .tich-prompt-field.is-selected .tich-prompt-field__checkbox::after {
            content: '';
            position: absolute;
            left: 0.16rem;
            top: 0.01rem;
            width: 0.24rem;
            height: 0.44rem;
            border: solid var(--tich-on-brand, #fff);
            border-width: 0 2px 2px 0;
            transform: rotate(45deg);
        }

        .tich-prompt-field__label {
            font-size: 0.72rem;
            font-weight: 600;
            color: var(--tich-text, var(--tich-grey));
        }

        .tich-prompt-field__current {
            display: block;
            padding: 0.44rem 0.52rem;
            border-radius: calc(var(--radius-md) - 2px);
            background: var(--prompt-field-inner-bg);
            border: 1px solid var(--tich-neutral-border);
        }

        .tich-prompt-field__current-label {
            display: block;
            font-size: 0.55rem;
            font-weight: 700;
            letter-spacing: 0.04em;
            text-transform: uppercase;
            color: var(--tich-neutral-muted, #64748b);
            margin-bottom: 0.16rem;
        }

        .tich-prompt-field__value {
            display: block;
            font-size: 0.7rem;
            line-height: 1.4;
            word-break: break-word;
            color: var(--tich-text, var(--tich-grey));
        }

        .tich-prompt-field__value.is-empty {
            color: var(--tich-neutral-muted, #94a3b8);
            font-style: italic;
        }

        .tich-prompt-field__photo-preview {
            display: flex;
            align-items: center;
            gap: 0.52rem;
        }

        .tich-prompt-field__photo-preview img {
            width: 2rem;
            height: 2rem;
            border-radius: 50%;
            object-fit: cover;
            border: 1px solid var(--tich-neutral-border);
        }

        .tich-prompt-field__photo-preview span {
            font-size: 0.7rem;
            color: var(--tich-text, var(--tich-grey));
        }

        .tich-prompt-sidebar__card {
            position: sticky;
            top: 0.8rem;
            padding: 0.88rem 1rem;
            background: var(--tich-surface, var(--tich-white));
            border: 1px solid var(--tich-neutral-border);
            border-radius: var(--radius-md);
        }

        .tich-prompt-sidebar__title {
            margin: 0;
            font-size: 0.75rem;
            font-weight: 700;
            color: var(--tich-text, var(--tich-grey));
        }

        .tich-prompt-sidebar__count {
            margin: 0.6rem 0 0;
            font-size: 1.2rem;
            font-weight: 700;
            color: var(--tich-blue);
            line-height: 1.2;
        }

        .tich-prompt-sidebar__count span:last-child {
            display: block;
            font-size: 0.65rem;
            font-weight: 500;
            color: var(--tich-neutral-muted, #64748b);
            margin-top: 0.12rem;
        }

        .tich-prompt-sidebar__list {
            display: flex;
            flex-wrap: wrap;
            gap: 0.28rem;
            margin-top: 0.6rem;
            min-height: 1.2rem;
        }

        .tich-prompt-sidebar__chip {
            display: inline-block;
            padding: 0.16rem 0.44rem;
            font-size: 0.6rem;
            border-radius: 999px;
            background: var(--prompt-chip-bg);
            color: var(--prompt-chip-text);
            border: 1px solid var(--prompt-chip-border);
        }

        .tich-prompt-page .tich-btn--block {
            display: block;
            width: 100%;
            text-align: center;
        }

        .tich-prompt-page #prompt-submit-btn:disabled {
            opacity: 0.55;
            cursor: not-allowed;
        }
    </style>

    <script>
        (function () {
            var form = document.getElementById('profile-update-prompt-form');
            if (!form) return;

            var fieldLabels = @json($fieldLabels);
            var countEl = document.getElementById('prompt-selected-count');
            var labelEl = document.getElementById('prompt-selected-label');
            var listEl = document.getElementById('prompt-selected-list');
            var submitBtn = document.getElementById('prompt-submit-btn');

            function selectedInputs() {
                return Array.from(form.querySelectorAll('.tich-prompt-field__input:checked'));
            }

            function syncUI() {
                form.querySelectorAll('.tich-prompt-field').forEach(function (label) {
                    var input = label.querySelector('.tich-prompt-field__input');
                    label.classList.toggle('is-selected', input && input.checked);
                });

                var selected = selectedInputs();
                var count = selected.length;

                if (countEl) countEl.textContent = String(count);
                if (labelEl) labelEl.textContent = count === 1 ? 'item selected' : 'items selected';
                if (submitBtn) submitBtn.disabled = count === 0;

                if (listEl) {
                    listEl.innerHTML = '';
                    selected.forEach(function (input) {
                        var chip = document.createElement('span');
                        chip.className = 'tich-prompt-sidebar__chip';
                        chip.textContent = fieldLabels[input.value] || input.value;
                        listEl.appendChild(chip);
                    });
                }
            }

            form.addEventListener('change', function (event) {
                if (event.target.matches('.tich-prompt-field__input')) {
                    syncUI();
                }
            });

            form.querySelectorAll('[data-prompt-section]').forEach(function (section) {
                var selectAll = section.querySelector('[data-prompt-select-all]');
                var clearAll = section.querySelector('[data-prompt-clear-all]');

                if (selectAll) {
                    selectAll.addEventListener('click', function () {
                        section.querySelectorAll('.tich-prompt-field__input').forEach(function (input) {
                            input.checked = true;
                        });
                        syncUI();
                    });
                }

                if (clearAll) {
                    clearAll.addEventListener('click', function () {
                        section.querySelectorAll('.tich-prompt-field__input').forEach(function (input) {
                            input.checked = false;
                        });
                        syncUI();
                    });
                }
            });

            syncUI();
        })();
    </script>
@endsection
