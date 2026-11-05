@extends('layouts.hr')

@section('title', 'Apply leave for employee')

@section('hr-content')
    <x-page-toolbar title="Apply leave for employee" meta="Record leave on behalf of a staff member">
        <x-slot:actions>
            <a href="{{ route('hr.leave.index') }}" class="tich-btn tich-btn-ghost">Leave requests</a>
            <a href="{{ route('hr.leave.employees') }}" class="tich-btn tich-btn-ghost">All employees</a>
        </x-slot:actions>
    </x-page-toolbar>

    <div class="tich-card tich-mb-4">
        <form method="GET" action="{{ route('hr.leave.apply.create') }}" class="tich-page-toolbar__filters-form">
            <label for="staff_id" class="tich-caption">Employee</label>
            <select id="staff_id" name="staff_id" class="tich-input tich-input--compact" onchange="this.form.submit()">
                <option value="">Select employee…</option>
                @foreach ($employees as $employee)
                    <option value="{{ $employee->id }}" @selected($selectedStaff?->id === $employee->id)>
                        {{ $employee->fullName() }} ({{ $employee->employee_number }})
                    </option>
                @endforeach
            </select>
        </form>
    </div>

    @if ($errors->has('leave'))
        <div class="tich-alert tich-alert--error tich-mb-4">{{ $errors->first('leave') }}</div>
    @endif

    @if (! $selectedStaff)
        <div class="tich-card">
            <p class="tich-text tich-text--secondary">Choose an employee above to open the leave application form.</p>
        </div>
    @else
        <div class="tich-card">
            <p class="tich-caption tich-mb-4">
                Applying for <strong>{{ $selectedStaff->fullName() }}</strong>
                · {{ $selectedStaff->employee_number }}
                · {{ $selectedStaff->department?->dept_name ?? 'No department' }}
            </p>

            <form method="POST" action="{{ route('hr.leave.apply.store') }}" enctype="multipart/form-data" class="tich-leave-app-form" data-uf="skip">
                @csrf
                <input type="hidden" name="staff_id" value="{{ $selectedStaff->id }}">

                <div class="uf-field">
                    <label for="leave_type_id">Leave type <span class="uf-req">*</span></label>
                    <select id="leave_type_id" name="leave_type_id" required class="tich-input">
                        <option value="">Select leave type</option>
                        @foreach ($leaveTypes as $type)
                            @php $meta = $leaveTypeMeta[$type->id] ?? []; @endphp
                            <option value="{{ $type->id }}" @selected(old('leave_type_id') == $type->id)>
                                {{ $type->leave_name }}
                                @if (! empty($meta['available_balance']))
                                    ({{ $meta['available_balance'] }} days left)
                                @endif
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="uf-form-grid-2">
                    <div class="uf-field">
                        <label for="start_date">Start date <span class="uf-req">*</span></label>
                        <input type="date" id="start_date" name="start_date" class="tich-input" required value="{{ old('start_date') }}">
                    </div>
                    <div class="uf-field">
                        <label for="end_date">End date (inclusive) <span class="uf-req">*</span></label>
                        <input type="date" id="end_date" name="end_date" class="tich-input" required value="{{ old('end_date') }}">
                    </div>
                </div>

                <div class="uf-field">
                    <label for="return_date">Return to work on</label>
                    <input type="date" id="return_date" name="return_date" class="tich-input" value="{{ old('return_date') }}">
                </div>

                <div class="uf-field">
                    <label for="reason">Purpose / remarks <span class="uf-req">*</span></label>
                    <textarea id="reason" name="reason" rows="3" class="tich-input" required>{{ old('reason') }}</textarea>
                </div>

                <div class="uf-field">
                    <label for="handover_notes">Handover notes</label>
                    <textarea id="handover_notes" name="handover_notes" rows="2" class="tich-input">{{ old('handover_notes') }}</textarea>
                </div>

                <div class="uf-field">
                    <label for="medical_certificate">Supporting document (if required)</label>
                    <input type="file" id="medical_certificate" name="medical_certificate" accept=".pdf,.jpg,.jpeg,.png">
                </div>

                <div class="uf-field">
                    <label class="tich-checkbox">
                        <input type="hidden" name="approve_immediately" value="0">
                        <input type="checkbox" name="approve_immediately" value="1" @checked(old('approve_immediately', '1') !== '0' && old('approve_immediately', '1') !== 0)>
                        Approve immediately (deduct leave balance now)
                    </label>
                    <span class="uf-hint">Uncheck to leave the request in the HR inbox for a separate approval step.</span>
                </div>

                <div class="tich-mt-4">
                    <button type="submit" class="tich-btn tich-btn-primary">Save leave application</button>
                </div>
            </form>
        </div>
    @endif
@endsection
