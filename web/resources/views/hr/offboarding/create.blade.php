@extends('layouts.hr')

@section('title', 'New Offboarding')

@section('hr-content')
    <x-page-toolbar title="Initiate Offboarding" />

    <article class="tich-card">
        <form method="POST" action="{{ route('hr.offboarding.store') }}">
            @csrf

            <div class="tich-grid tich-grid--2 tich-mb-6">
                <div>
                    <label for="staff_id" class="tich-label">Staff Member *</label>
                    <select id="staff_id" name="staff_id" required class="tich-input">
                        <option value="">Select staff</option>
                        @foreach ($staff as $s)
                            <option value="{{ $s->id }}" {{ old('staff_id') == $s->id ? 'selected' : '' }}>
                                {{ $s->fullName() }} ({{ $s->employee_number }})
                            </option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="exit_type" class="tich-label">Exit Type *</label>
                    <select id="exit_type" name="exit_type" required class="tich-input">
                        <option value="">Select type</option>
                        <option value="resignation" {{ old('exit_type') == 'resignation' ? 'selected' : '' }}>Resignation</option>
                        <option value="retirement" {{ old('exit_type') == 'retirement' ? 'selected' : '' }}>Retirement</option>
                        <option value="non_renewal" {{ old('exit_type') == 'non_renewal' ? 'selected' : '' }}>Non-Renewal</option>
                        <option value="termination" {{ old('exit_type') == 'termination' ? 'selected' : '' }}>Termination</option>
                        <option value="redundancy" {{ old('exit_type') == 'redundancy' ? 'selected' : '' }}>Redundancy</option>
                        <option value="death" {{ old('exit_type') == 'death' ? 'selected' : '' }}>Death</option>
                    </select>
                </div>
                <div>
                    <label for="exit_date" class="tich-label">Exit Date *</label>
                    <input type="date" id="exit_date" name="exit_date" value="{{ old('exit_date') }}" required class="tich-input">
                </div>
                <div>
                    <label for="notice_period_days" class="tich-label">Notice Period (Days)</label>
                    <input type="number" id="notice_period_days" name="notice_period_days" value="{{ old('notice_period_days') }}" class="tich-input">
                </div>
                <div class="tich-grid--span-2">
                    <label for="reason" class="tich-label">Reason</label>
                    <textarea id="reason" name="reason" rows="3" class="tich-input">{{ old('reason') }}</textarea>
                </div>
                <div class="tich-grid--span-2">
                    <label for="termination_reason" class="tich-label">Termination Reason (if applicable)</label>
                    <textarea id="termination_reason" name="termination_reason" rows="3" class="tich-input">{{ old('termination_reason') }}</textarea>
                </div>
                <div class="tich-grid--span-2">
                    <label for="notes" class="tich-label">Additional Notes</label>
                    <textarea id="notes" name="notes" rows="3" class="tich-input">{{ old('notes') }}</textarea>
                </div>
            </div>

            <div class="tich-mt-6">
                <button type="submit" class="tich-btn tich-btn-primary">Initiate Offboarding</button>
                <a href="{{ route('hr.offboarding.index') }}" class="tich-btn tich-btn-ghost">Cancel</a>
            </div>
        </form>
    </article>
@endsection
