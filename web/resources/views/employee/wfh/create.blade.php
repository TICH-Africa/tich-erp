@extends('layouts.employee')

@section('employee-content')
    <x-page-toolbar title="Apply for work from home" meta="Separate from leave · Max 1 day per week · Unused days carry within the month only">
        <x-slot:actions>
            <a href="{{ route('employee.wfh.index') }}" class="tich-btn tich-btn-ghost">← Back</a>
        </x-slot:actions>
    </x-page-toolbar>

    <div class="uf-form">
        <form method="POST" action="{{ route('employee.wfh.store') }}" data-uf="ready" id="wfh-form">
            @csrf

            <div class="uf-amount-bar">
                <div>
                    <div class="uf-amount-bar__ref">WFH · Work from home</div>
                    <div class="uf-amount-bar__sum">{{ $entitlement['available'] }} day(s) available this week bank</div>
                </div>
                <span class="uf-badge">To HR</span>
            </div>

            <div class="uf-form-section">
                <div class="uf-section-head">1. Employee information</div>
                <div class="uf-section-body">
                    <div class="uf-form-grid-2">
                        <div class="uf-field">
                            <label>Employee name</label>
                            <input type="text" value="{{ $staff->fullName() }}" readonly>
                        </div>
                        <div class="uf-field">
                            <label>Employee ID</label>
                            <input type="text" value="{{ $staff->employee_number }}" readonly>
                        </div>
                        <div class="uf-field">
                            <label>Job title</label>
                            <input type="text" value="{{ $staff->job_title ?? '—' }}" readonly>
                        </div>
                    </div>
                </div>
            </div>

            <div class="uf-form-section">
                <div class="uf-section-head">2. Department information</div>
                <div class="uf-section-body">
                    <div class="uf-form-grid-2">
                        <div class="uf-field">
                            <label>Supervisor name</label>
                            <input type="text" value="{{ $staff->lineManager?->fullName() ?? 'Not assigned' }}" readonly>
                        </div>
                        <div class="uf-field">
                            <label>Department</label>
                            <input type="text" value="{{ $staff->department?->dept_name ?? '—' }}" readonly>
                        </div>
                    </div>
                </div>
            </div>

            <div class="uf-form-section">
                <div class="uf-section-head">3. Request details</div>
                <div class="uf-section-body">
                    <p class="uf-hint">Arrangement type is fixed as <strong>Work from home</strong>. Working days are Monday–Friday only.</p>
                    <div class="uf-form-grid-2">
                        <div class="uf-field">
                            <label for="work_date">Work from home date <span class="uf-req">*</span></label>
                            <input type="date" id="work_date" name="work_date" required
                                   value="{{ old('work_date') }}"
                                   min="{{ now()->toDateString() }}"
                                   class="{{ $errors->has('work_date') ? 'is-invalid' : '' }}">
                            @error('work_date')
                                <span class="uf-error">{{ $message }}</span>
                            @enderror
                        </div>
                        <div class="uf-field">
                            <label>Entitlement note</label>
                            <input type="text" readonly value="{{ $entitlement['used'] }} used · {{ $entitlement['earned'] }} earned · {{ $entitlement['available'] }} available in {{ \Carbon\Carbon::create($entitlement['year'], $entitlement['month'], 1)->format('F') }}">
                        </div>
                    </div>
                </div>
            </div>

            <div class="uf-form-section">
                <div class="uf-section-head">4. Proposed schedule (Mon–Fri)</div>
                <div class="uf-section-body">
                    <div class="uf-form-grid-2">
                        <div class="uf-field">
                            <label for="start_time">Start time <span class="uf-req">*</span></label>
                            <input type="time" id="start_time" name="start_time" required value="{{ old('start_time', '08:00') }}">
                            @error('start_time')
                                <span class="uf-error">{{ $message }}</span>
                            @enderror
                        </div>
                        <div class="uf-field">
                            <label for="end_time">End time <span class="uf-req">*</span></label>
                            <input type="time" id="end_time" name="end_time" required value="{{ old('end_time', '17:00') }}">
                            @error('end_time')
                                <span class="uf-error">{{ $message }}</span>
                            @enderror
                        </div>
                        <div class="uf-field">
                            <label for="total_hours">Total hours</label>
                            <input type="number" step="0.25" min="0.5" max="24" id="total_hours" name="total_hours" value="{{ old('total_hours', '8') }}">
                        </div>
                        <div class="uf-field">
                            <label for="period_start">Arrangement start date</label>
                            <input type="date" id="period_start" name="period_start" value="{{ old('period_start') }}">
                        </div>
                        <div class="uf-field">
                            <label for="period_end">Arrangement end date</label>
                            <input type="date" id="period_end" name="period_end" value="{{ old('period_end') }}">
                        </div>
                    </div>
                </div>
            </div>

            <div class="uf-form-section">
                <div class="uf-section-head">Tasks to be accomplished while working remotely</div>
                <div class="uf-section-body">
                    <p class="uf-hint">List up to 5 tasks. At least one is required.</p>
                    @error('remote_tasks')
                        <span class="uf-error">{{ $message }}</span>
                    @enderror
                    @for ($i = 0; $i < 5; $i++)
                        <div class="uf-field">
                            <label for="remote_tasks_{{ $i }}">{{ $i + 1 }}.</label>
                            <input type="text" id="remote_tasks_{{ $i }}" name="remote_tasks[]"
                                   value="{{ old('remote_tasks.'.$i) }}"
                                   maxlength="500"
                                   placeholder="Task or deliverable">
                        </div>
                    @endfor
                </div>
            </div>

            <div class="uf-form-section">
                <div class="uf-section-head">Submit</div>
                <div class="uf-section-body">
                    <p class="uf-hint">HR will review and complete the departmental consideration checklist before approval.</p>
                    <div class="uf-form-actions">
                        <button type="submit" class="uf-btn uf-btn-primary" @disabled($entitlement['available'] < 1)>Submit to HR</button>
                        <a href="{{ route('employee.wfh.index') }}" class="uf-btn uf-btn-secondary">Cancel</a>
                    </div>
                </div>
            </div>

            <p class="uf-form-footnote"><span class="uf-req">*</span> Required field</p>
        </form>
    </div>

    <script>
        (function () {
            const workDate = document.getElementById('work_date');
            if (!workDate) return;
            workDate.addEventListener('change', function () {
                const d = new Date(this.value + 'T12:00:00');
                if (Number.isNaN(d.getTime())) return;
                const day = d.getDay();
                if (day === 0 || day === 6) {
                    alert('Work from home is Monday to Friday only.');
                    this.value = '';
                }
            });
        })();
    </script>
@endsection
