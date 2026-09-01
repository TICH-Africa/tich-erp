@extends('layouts.hr')

@section('title', $contract->contract_number . ' - Contract')

@section('hr-content')
    @include('partials.financial-privacy')

    @php
        $statusBadge = match ($contract->renewal_status) {
            'renewed' => 'success',
            'pending' => 'warning',
            'expired' => 'danger',
            default => 'info',
        };
    @endphp

    <x-page-toolbar :title="$contract->contract_number" :meta="$contract->staff->fullName() ?? 'Staff contract'">
        <x-slot:actions>
            <a href="{{ route('hr.contracts.index') }}" class="tich-btn tich-btn-ghost">All contracts</a>
            @if ($contract->staff)
                <a href="{{ route('hr.staff.show', $contract->staff) }}" class="tich-btn tich-btn-secondary">View staff profile</a>
            @endif
        </x-slot:actions>
    </x-page-toolbar>

    <div class="tich-hr-profile-header">
        <div class="tich-hr-profile-header__main">
            <h2 class="tich-hr-profile-header__name">{{ $contract->job_title }}</h2>
            <div class="tich-hr-profile-header__meta">
                <span>{{ $contract->staff->fullName() ?? '—' }}</span>
                <span>{{ $contract->department->dept_name ?? '—' }}</span>
                <span class="tich-badge tich-badge--info">{{ ucfirst($contract->contract_type) }}</span>
                <span class="tich-badge tich-badge--{{ $contract->is_signed ? 'success' : 'warning' }}">
                    {{ $contract->is_signed ? 'Signed' : 'Unsigned' }}
                </span>
                <span class="tich-badge tich-badge--{{ $statusBadge }}">{{ ucfirst($contract->renewal_status) }}</span>
            </div>
        </div>
        <div class="tich-hr-profile-header__actions">
            <x-financial-value :value="number_format($contract->gross_salary, 2)" prefix="Gross KES " />
        </div>
    </div>

    <div class="tich-detail-grid tich-detail-grid--2 tich-mb-8">
        <section class="tich-detail-card">
            <h3 class="tich-detail-card__title">Contract details</h3>
            <dl class="tich-dl">
                <div class="tich-dl__row"><dt class="tich-dl__label">Contract No.</dt><dd class="tich-dl__value">{{ $contract->contract_number }}</dd></div>
                <div class="tich-dl__row"><dt class="tich-dl__label">Staff</dt><dd class="tich-dl__value">{{ $contract->staff->fullName() ?? '—' }}</dd></div>
                <div class="tich-dl__row"><dt class="tich-dl__label">Job title</dt><dd class="tich-dl__value">{{ $contract->job_title }}</dd></div>
                <div class="tich-dl__row"><dt class="tich-dl__label">Department</dt><dd class="tich-dl__value">{{ $contract->department->dept_name ?? '—' }}</dd></div>
                <div class="tich-dl__row"><dt class="tich-dl__label">Type</dt><dd class="tich-dl__value">{{ ucfirst($contract->contract_type) }}</dd></div>
                <div class="tich-dl__row">
                    <dt class="tich-dl__label">Gross salary</dt>
                    <dd class="tich-dl__value"><x-financial-value :value="number_format($contract->gross_salary, 2)" /></dd>
                </div>
                <div class="tich-dl__row"><dt class="tich-dl__label">Start date</dt><dd class="tich-dl__value">{{ $contract->start_date?->format('d M Y') ?? '—' }}</dd></div>
                <div class="tich-dl__row"><dt class="tich-dl__label">End date</dt><dd class="tich-dl__value">{{ $contract->end_date?->format('d M Y') ?? 'Ongoing' }}</dd></div>
                <div class="tich-dl__row"><dt class="tich-dl__label">Probation end</dt><dd class="tich-dl__value">{{ $contract->probation_end_date?->format('d M Y') ?? '—' }}</dd></div>
                <div class="tich-dl__row"><dt class="tich-dl__label">Renewable</dt><dd class="tich-dl__value">{{ $contract->is_renewable ? 'Yes' : 'No' }}</dd></div>
                <div class="tich-dl__row"><dt class="tich-dl__label">Signed</dt><dd class="tich-dl__value">{{ $contract->is_signed ? 'Yes (' . $contract->signed_date?->format('d M Y') . ')' : 'No' }}</dd></div>
            </dl>
        </section>

        <section class="tich-detail-card">
            <h3 class="tich-detail-card__title">Actions</h3>

            <form method="POST" action="{{ route('hr.contracts.renew', $contract) }}" class="tich-mb-6" style="padding-bottom:1rem; border-bottom:1px solid var(--tich-neutral-border);">
                @csrf
                <p class="tich-text tich-mb-3">Renew this contract for another term.</p>
                <div class="tich-grid tich-grid--2 tich-mb-4">
                    <div>
                        <label for="renew_start_date" class="tich-label">Start date *</label>
                        <input type="date" id="renew_start_date" name="start_date" value="{{ old('start_date', $contract->start_date?->format('Y-m-d')) }}" required class="tich-input">
                    </div>
                    <div>
                        <label for="renew_duration" class="tich-label">Duration</label>
                        <input type="text" id="renew_duration" name="duration" value="{{ old('duration') }}" class="tich-input" placeholder="e.g. 6 months, 1 year">
                    </div>
                    <div>
                        <label for="renew_end_date" class="tich-label">End date</label>
                        <input type="date" id="renew_end_date" name="end_date" value="{{ old('end_date', $contract->end_date?->format('Y-m-d')) }}" class="tich-input" readonly>
                    </div>
                    <div>
                        <label for="renew_gross_salary" class="tich-label">Gross salary *</label>
                        <input type="number" step="0.01" id="renew_gross_salary" name="gross_salary" value="{{ old('gross_salary', $contract->gross_salary) }}" required class="tich-input">
                    </div>
                    <div>
                        <label for="renew_contract_type" class="tich-label">Contract type</label>
                        <select id="renew_contract_type" name="contract_type" class="tich-input">
                            @foreach (['permanent' => 'Permanent', 'contract' => 'Contract', 'intern' => 'Intern', 'visiting' => 'Visiting', 'casual' => 'Casual', 'probation' => 'Probation', 'consultancy' => 'Consultancy'] as $value => $label)
                                <option value="{{ $value }}" {{ old('contract_type', $contract->contract_type) == $value ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="tich-grid--span-2">
                        <label for="renew_job_title" class="tich-label">Job title</label>
                        <input type="text" id="renew_job_title" name="job_title" value="{{ old('job_title', $contract->job_title) }}" class="tich-input">
                    </div>
                </div>
                <button type="submit" class="tich-btn tich-btn-primary">Renew contract</button>
            </form>

            <form method="POST" action="{{ route('hr.contracts.convert-permanent', $contract) }}" class="tich-mb-4">
                @csrf
                <p class="tich-text tich-mb-2">Convert this contract to permanent employment.</p>
                <button type="submit" class="tich-btn tich-btn-secondary">Convert to permanent</button>
            </form>

            <form method="POST" action="{{ route('hr.contracts.sign', $contract) }}">
                @csrf
                <p class="tich-text tich-mb-2">Mark this contract as signed.</p>
                <button type="submit" class="tich-btn tich-btn-secondary">Mark as signed</button>
            </form>
        </section>
    </div>

    @if ($contract->newContract)
        <section class="tich-detail-card tich-mb-8">
            <h3 class="tich-detail-card__title">Successor contract</h3>
            <p class="tich-mt-2">
                <a href="{{ route('hr.contracts.show', $contract->newContract) }}">{{ $contract->newContract->contract_number }}</a>
            </p>
        </section>
    @endif

    <script>
        (function () {
            var startInput = document.getElementById('renew_start_date');
            var durationInput = document.getElementById('renew_duration');
            var endInput = document.getElementById('renew_end_date');
            if (!startInput || !durationInput || !endInput) return;

            function calculateEnd() {
                var start = startInput.value;
                var duration = durationInput.value.trim();
                if (!start || !duration) {
                    endInput.value = '';
                    return;
                }

                var lower = duration.toLowerCase();
                var months = 0;
                var yearMatch = lower.match(/(\d+)\s*y/);
                var monthMatch = lower.match(/(\d+)\s*m/);

                if (yearMatch) months += parseInt(yearMatch[1], 10) * 12;
                if (monthMatch) months += parseInt(monthMatch[1], 10);
                if (!yearMatch && !monthMatch && /^\d+$/.test(lower)) months = parseInt(lower, 10);

                if (months <= 0) {
                    endInput.value = '';
                    return;
                }

                var date = new Date(start);
                date.setMonth(date.getMonth() + months);
                endInput.value = date.getFullYear() + '-' + String(date.getMonth() + 1).padStart(2, '0') + '-' + String(date.getDate()).padStart(2, '0');
            }

            startInput.addEventListener('change', calculateEnd);
            durationInput.addEventListener('input', calculateEnd);
        })();
    </script>
@endsection
