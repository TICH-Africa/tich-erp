@extends('layouts.monitoring-evaluation')

@section('title', 'Sign M&E Policy')

@section('monitoring-evaluation-content')
    <x-page-toolbar title="Mandatory M&E policy sign-off" :meta="$policy->title.' · '.$policy->fiscal_year" />

    @if (session('status'))
        <div class="tich-alert tich-alert--success tich-mt-4">{{ session('status') }}</div>
    @endif
    @if ($errors->any())
        <div class="tich-alert tich-alert--error tich-mt-4">
            @foreach ($errors->all() as $error)<p>{{ $error }}</p>@endforeach
        </div>
    @endif

    <article class="tich-card tich-mt-6">
        <p class="tich-text">Heads of Department must view and digitally sign this policy before submitting annual budgets and departmental plans.</p>
        <a href="{{ route('monitoring_evaluation.policies.download', $policy) }}" class="tich-btn tich-btn-secondary tich-mt-4" target="_blank">Download / view policy</a>
    </article>

    <article class="tich-card tich-mt-6">
        <h2 class="tich-h3">Digital sign-off</h2>
        <form method="POST" action="{{ route('monitoring_evaluation.policy.sign.store') }}" class="tich-form-stack tich-mt-4">
            @csrf
            <div class="tich-grid tich-grid--2">
                <div>
                    <label class="tich-label" for="signed_name">Full name *</label>
                    <input type="text" id="signed_name" name="signed_name" class="tich-input" required value="{{ old('signed_name', $staff?->fullName()) }}">
                </div>
                <div>
                    <label class="tich-label" for="employee_number">Employee number</label>
                    <input type="text" id="employee_number" name="employee_number" class="tich-input" value="{{ old('employee_number', $staff?->employee_number) }}">
                </div>
                <div class="tich-grid--span-2">
                    <label class="tich-label" for="signature">Digital signature</label>
                    <input type="text" id="signature" name="signature" class="tich-input" value="{{ old('signature') }}" placeholder="Type your full name as digital signature">
                </div>
            </div>
            <button type="submit" class="tich-btn tich-btn-primary">I have read and sign this M&amp;E policy</button>
        </form>
    </article>
@endsection
