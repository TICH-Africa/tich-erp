@php
    $modalId = $modalId ?? 'leave-request-modal';
    $editing = $editRequest !== null;
    $formAction = $editing
        ? route('employee.leave.update', $editRequest)
        : route('employee.leave.store');
    $openModal = $openModal ?? ($editing || $errors->any());
@endphp

<div id="{{ $modalId }}" class="tich-modal{{ $openModal ? ' is-open' : '' }}" aria-hidden="{{ $openModal ? 'false' : 'true' }}" role="dialog" aria-modal="true" aria-labelledby="{{ $modalId }}-title">
    <div class="tich-modal__backdrop" data-close-modal="{{ $modalId }}"></div>
    <div class="tich-modal__dialog tich-modal__dialog--leave">
        <header class="tich-modal__header">
            <h2 class="tich-h3" id="{{ $modalId }}-title">{{ $editing ? 'Update leave request' : 'New leave request' }}</h2>
            <button type="button" class="tich-modal__close" data-close-modal="{{ $modalId }}" aria-label="Close">&times;</button>
        </header>

        <form method="POST" action="{{ $formAction }}" enctype="multipart/form-data" class="tich-modal__body">
            @csrf
            @if ($editing)
                @method('PUT')
            @endif

            @if ($errors->any())
                <div class="tich-modal__errors">
                    <p class="tich-text"><strong>Please fix the following:</strong></p>
                    <ul class="tich-mt-2" style="margin-bottom:0; padding-left:1.25rem;">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @if ($editing && $editRequest->hr_review_notes)
                <div class="tich-alert tich-alert--warning tich-mb-4">
                    <strong>HR feedback:</strong> {{ $editRequest->hr_review_notes }}
                </div>
            @endif

            <div class="tich-form-stack">
                <div>
                    <label for="leave_type_id" class="tich-label">Leave type</label>
                    <select id="leave_type_id" name="leave_type_id" class="tich-input" required>
                        <option value="">Select leave type</option>
                        @foreach ($leaveTypes as $type)
                            <option
                                value="{{ $type->id }}"
                                @selected(old('leave_type_id', $editRequest?->leave_type_id) == $type->id)
                                data-calculation-type="{{ $type->calculation_type }}"
                                data-accrual-type="{{ $type->accrual_type }}"
                                data-accrual-rate="{{ $type->accrual_rate }}"
                                data-notice-period="{{ $type->notice_period_days }}"
                                data-max-consecutive="{{ $type->max_consecutive_days }}"
                                data-requires-certificate="{{ $type->requires_certificate }}"
                            >
                                {{ $type->leave_name }}
                                @if ($type->accrual_type === 'monthly')
                                    (accrues monthly)
                                @endif
                            </option>
                        @endforeach
                    </select>
                    <p class="tich-caption tich-mt-1" id="leave-type-hint"></p>
                </div>

                <div class="tich-grid tich-grid--2">
                    <div>
                        <label for="start_date" class="tich-label">Start date</label>
                        <input type="date" id="start_date" name="start_date" class="tich-input" required
                            value="{{ old('start_date', $editRequest?->start_date?->format('Y-m-d')) }}">
                    </div>
                    <div>
                        <label for="end_date" class="tich-label">End date</label>
                        <input type="date" id="end_date" name="end_date" class="tich-input" required
                            value="{{ old('end_date', $editRequest?->end_date?->format('Y-m-d')) }}">
                        <p class="tich-caption tich-mt-1" id="days-preview"></p>
                    </div>
                </div>

                <div id="certificate-field">
                    <label for="medical_certificate" class="tich-label">Medical certificate (if required)</label>
                    <input type="file" id="medical_certificate" name="medical_certificate" class="tich-input" accept=".pdf,.jpg,.jpeg,.png">
                </div>

                <div>
                    <label for="reason" class="tich-label">Reason</label>
                    <textarea id="reason" name="reason" class="tich-input" rows="4" required placeholder="Brief reason for your leave request">{{ old('reason', $editRequest?->reason) }}</textarea>
                </div>

                <div>
                    <label for="handover_notes" class="tich-label">Handover notes</label>
                    <textarea id="handover_notes" name="handover_notes" class="tich-input" rows="3" placeholder="Who covers your duties, key handover points…">{{ old('handover_notes', $editRequest?->handover_notes) }}</textarea>
                </div>

                <div>
                    <label class="tich-checkbox">
                        <input type="checkbox" name="is_emergency" value="1" @checked(old('is_emergency', $editRequest?->is_emergency))>
                        Emergency leave
                    </label>
                </div>
            </div>

            <script>
                (function() {
                    const leaveTypeSelect = document.getElementById('leave_type_id');
                    const hintEl = document.getElementById('leave-type-hint');
                    const daysPreview = document.getElementById('days-preview');
                    const startInput = document.getElementById('start_date');
                    const endInput = document.getElementById('end_date');
                    const certificateField = document.getElementById('certificate-field');

                    function updateHint() {
                        const option = leaveTypeSelect.options[leaveTypeSelect.selectedIndex];
                        if (!option || !option.value) {
                            hintEl.textContent = '';
                            daysPreview.textContent = '';
                            certificateField.style.display = 'none';
                            return;
                        }

                        const calc = option.dataset.calculationType || 'calendar_days';
                        const accrual = option.dataset.accrualType || 'none';
                        const accrualRate = option.dataset.accrualRate;
                        const notice = option.dataset.noticePeriod;
                        const maxConsecutive = option.dataset.maxConsecutive;
                        const requiresCertificate = option.dataset.requiresCertificate === '1';

                        const parts = [];
                        parts.push(calc === 'working_days' ? 'Counts working days only (excludes weekends/holidays).' : 'Counts calendar days including weekends/holidays.');
                        if (accrual === 'monthly' && accrualRate) {
                            parts.push('Accrues at ' + accrualRate + ' days/month.');
                        }
                        if (notice && parseInt(notice) > 0) {
                            parts.push('Notice period: ' + notice + ' days.');
                        }
                        if (maxConsecutive && parseInt(maxConsecutive) > 0) {
                            parts.push('Max consecutive: ' + maxConsecutive + ' days.');
                        }

                        hintEl.textContent = parts.join(' ');

                        certificateField.style.display = requiresCertificate ? 'block' : 'none';
                    }

                    function updateDaysPreview() {
                        if (!startInput.value || !endInput.value) {
                            daysPreview.textContent = '';
                            return;
                        }

                        const start = new Date(startInput.value + 'T00:00:00');
                        const end = new Date(endInput.value + 'T00:00:00');
                        if (isNaN(start.getTime()) || isNaN(end.getTime()) || end < start) {
                            daysPreview.textContent = '';
                            return;
                        }

                        const option = leaveTypeSelect.options[leaveTypeSelect.selectedIndex];
                        const calc = option && option.value ? (option.dataset.calculationType || 'calendar_days') : 'calendar_days';

                        let days = 0;
                        if (calc === 'working_days') {
                            const holidays = [];
                            for (let d = new Date(start); d <= end; d.setDate(d.getDate() + 1)) {
                                const day = d.getDay();
                                if (day !== 0 && day !== 6) {
                                    days++;
                                }
                            }
                        } else {
                            days = Math.round((end - start) / (1000 * 60 * 60 * 24)) + 1;
                        }

                        daysPreview.textContent = 'Requested: ' + days + ' day(s)';
                    }

                    leaveTypeSelect.addEventListener('change', function() {
                        updateHint();
                        updateDaysPreview();
                    });

                    startInput.addEventListener('change', updateDaysPreview);
                    endInput.addEventListener('change', updateDaysPreview);

                    updateHint();
                })();
            </script>

            <footer class="tich-modal__footer">
                <button type="button" class="tich-btn tich-btn-secondary" data-close-modal="{{ $modalId }}">Cancel</button>
                <button type="submit" class="tich-btn tich-btn-primary">
                    {{ $editing ? 'Resubmit to HR' : 'Submit to HR' }}
                </button>
            </footer>
        </form>
    </div>
</div>

@include('admin.partials.tich-modal-assets')
