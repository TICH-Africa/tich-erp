@extends('layouts.hr')

@section('title', $contract->contract_number . ' - Contract')

@section('hr-content')
    <x-page-toolbar :title="$contract->contract_number" />

    <div class="tich-grid tich-grid--2 tich-mb-8">
        <article class="tich-card">
            <h3 class="tich-h3">Contract Details</h3>
            <div class="tich-mt-4">
                <p><strong>Contract No.:</strong> {{ $contract->contract_number }}</p>
                <p><strong>Staff:</strong> {{ $contract->staff->fullName() ?? '—' }}</p>
                <p><strong>Job Title:</strong> {{ $contract->job_title }}</p>
                <p><strong>Department:</strong> {{ $contract->department->dept_name ?? '—' }}</p>
                <p><strong>Type:</strong> {{ ucfirst($contract->contract_type) }}</p>
                <p><strong>Gross Salary:</strong> KES {{ number_format($contract->gross_salary, 2) }}</p>
                <p><strong>Start Date:</strong> {{ $contract->start_date?->format('Y-m-d') }}</p>
                <p><strong>End Date:</strong> {{ $contract->end_date?->format('Y-m-d') ?? 'Ongoing' }}</p>
                <p><strong>Probation End:</strong> {{ $contract->probation_end_date?->format('Y-m-d') ?? '—' }}</p>
                <p><strong>Renewable:</strong> {{ $contract->is_renewable ? 'Yes' : 'No' }}</p>
                <p><strong>Renewal Status:</strong> {{ ucfirst($contract->renewal_status) }}</p>
                <p><strong>Signed:</strong> {{ $contract->is_signed ? 'Yes (' . $contract->signed_date?->format('Y-m-d') . ')' : 'No' }}</p>
            </div>
        </article>

        <article class="tich-card">
            <h3 class="tich-h3">Actions</h3>
            <div class="tich-mt-4">
                <form method="POST" action="{{ route('hr.contracts.renew', $contract) }}" class="tich-mb-4">
                    @csrf
                    @method('POST')
                    <p class="tich-text tich-mb-2">Renew this contract for another term.</p>
                    <div class="tich-grid tich-grid--2 tich-mb-4">
                        <div>
                            <label for="renew_start_date" class="tich-label">Start Date *</label>
                            <input type="date" id="renew_start_date" name="start_date" value="{{ old('start_date', $contract->start_date?->format('Y-m-d')) }}" required class="tich-input">
                        </div>
                        <div>
                            <label for="renew_end_date" class="tich-label">End Date *</label>
                            <input type="date" id="renew_end_date" name="end_date" value="{{ old('end_date', $contract->end_date?->format('Y-m-d')) }}" required class="tich-input">
                        </div>
                        <div>
                            <label for="renew_gross_salary" class="tich-label">Gross Salary *</label>
                            <input type="number" step="0.01" id="renew_gross_salary" name="gross_salary" value="{{ old('gross_salary', $contract->gross_salary) }}" required class="tich-input">
                        </div>
                        <div>
                            <label for="renew_contract_type" class="tich-label">Contract Type</label>
                            <select id="renew_contract_type" name="contract_type" class="tich-input">
                                @foreach (['permanent' => 'Permanent', 'contract' => 'Contract', 'intern' => 'Intern', 'visiting' => 'Visiting', 'casual' => 'Casual', 'probation' => 'Probation', 'consultancy' => 'Consultancy'] as $value => $label)
                                    <option value="{{ $value }}" {{ old('contract_type', $contract->contract_type) == $value ? 'selected' : '' }}>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="tich-grid--span-2">
                            <label for="renew_job_title" class="tich-label">Job Title</label>
                            <input type="text" id="renew_job_title" name="job_title" value="{{ old('job_title', $contract->job_title) }}" class="tich-input">
                        </div>
                    </div>
                    <button type="submit" class="tich-btn tich-btn-primary">Renew Contract</button>
                </form>

                <form method="POST" action="{{ route('hr.contracts.convert-permanent', $contract) }}" class="tich-mb-4">
                    @csrf
                    @method('POST')
                    <p class="tich-text tich-mb-2">Convert this contract to permanent employment.</p>
                    <button type="submit" class="tich-btn tich-btn-secondary">Convert to Permanent</button>
                </form>

                <form method="POST" action="{{ route('hr.contracts.sign', $contract) }}" class="tich-mb-4">
                    @csrf
                    @method('POST')
                    <p class="tich-text tich-mb-2">Mark this contract as signed.</p>
                    <button type="submit" class="tich-btn tich-btn-secondary">Mark as Signed</button>
                </form>
            </div>
        </article>
    </div>

    @if ($contract->newContract)
        <article class="tich-card tich-mb-8">
            <h3 class="tich-h3">Successor Contract</h3>
            <p class="tich-mt-2">
                <a href="{{ route('hr.contracts.show', $contract->newContract) }}">{{ $contract->newContract->contract_number }}</a>
            </p>
        </article>
    @endif
@endsection
