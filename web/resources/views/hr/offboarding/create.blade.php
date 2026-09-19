@extends('layouts.hr')

@section('title', 'New Offboarding')

@section('hr-content')
    <x-page-toolbar title="Initiate Offboarding" />

    <div class="uf-form">
        <form method="POST" action="{{ route('hr.offboarding.store') }}" data-uf="ready">
            @csrf

            <div class="uf-amount-bar">
                <div>
                    <div class="uf-amount-bar__ref">OFB · Offboarding</div>
                    <div class="uf-amount-bar__sum">Initiate exit</div>
                </div>
                <span class="uf-badge">Draft</span>
            </div>

            <div class="uf-form-section">
                <div class="uf-section-head">Exit Details</div>
                <div class="uf-section-body">
                    <div class="uf-form-grid-2">
                        <div class="uf-field">
                            <label for="staff_id">Staff Member <span class="uf-req">*</span></label>
                            <select id="staff_id" name="staff_id" required class="{{ $errors->has('staff_id') ? 'is-invalid' : '' }}">
                                <option value="">Select staff</option>
                                @foreach ($staff as $s)
                                    <option value="{{ $s->id }}" @selected(old('staff_id') == $s->id)>
                                        {{ $s->fullName() }} ({{ $s->employee_number }})
                                    </option>
                                @endforeach
                            </select>
                            @error('staff_id')
                                <span class="uf-error">{{ $message }}</span>
                            @enderror
                        </div>
                        <div class="uf-field">
                            <label for="exit_type">Exit Type <span class="uf-req">*</span></label>
                            <select id="exit_type" name="exit_type" required class="{{ $errors->has('exit_type') ? 'is-invalid' : '' }}">
                                <option value="">Select type</option>
                                <option value="resignation" @selected(old('exit_type') == 'resignation')>Resignation</option>
                                <option value="retirement" @selected(old('exit_type') == 'retirement')>Retirement</option>
                                <option value="non_renewal" @selected(old('exit_type') == 'non_renewal')>Non-Renewal</option>
                                <option value="termination" @selected(old('exit_type') == 'termination')>Termination</option>
                                <option value="redundancy" @selected(old('exit_type') == 'redundancy')>Redundancy</option>
                                <option value="death" @selected(old('exit_type') == 'death')>Death</option>
                            </select>
                            @error('exit_type')
                                <span class="uf-error">{{ $message }}</span>
                            @enderror
                        </div>
                        <div class="uf-field">
                            <label for="exit_date">Exit Date <span class="uf-req">*</span></label>
                            <input type="date" id="exit_date" name="exit_date" value="{{ old('exit_date') }}" required class="{{ $errors->has('exit_date') ? 'is-invalid' : '' }}">
                            @error('exit_date')
                                <span class="uf-error">{{ $message }}</span>
                            @enderror
                        </div>
                        <div class="uf-field">
                            <label for="notice_period_days">Notice Period (Days)</label>
                            <input type="number" id="notice_period_days" name="notice_period_days" value="{{ old('notice_period_days') }}">
                        </div>
                    </div>
                    <div class="uf-field">
                        <label for="reason">Reason</label>
                        <textarea id="reason" name="reason" rows="3">{{ old('reason') }}</textarea>
                    </div>
                    <div class="uf-field">
                        <label for="termination_reason">Termination Reason (if applicable)</label>
                        <textarea id="termination_reason" name="termination_reason" rows="3">{{ old('termination_reason') }}</textarea>
                    </div>
                    <div class="uf-field">
                        <label for="notes">Additional Notes</label>
                        <textarea id="notes" name="notes" rows="3">{{ old('notes') }}</textarea>
                    </div>
                </div>
            </div>

            <div class="uf-form-section">
                <div class="uf-section-head">Submit</div>
                <div class="uf-section-body">
                    <div class="uf-form-actions">
                        <button type="submit" class="uf-btn uf-btn-primary">Initiate Offboarding</button>
                        <a href="{{ route('hr.offboarding.index') }}" class="uf-btn uf-btn-secondary">Cancel</a>
                    </div>
                </div>
            </div>

            <p class="uf-form-footnote"><span class="uf-req">*</span> Required field</p>
        </form>
    </div>
@endsection
