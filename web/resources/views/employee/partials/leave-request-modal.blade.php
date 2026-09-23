@php
    $modalId = $modalId ?? 'leave-request-modal';
    $editing = $editRequest !== null;
    $formAction = $editing
        ? route('employee.leave.update', $editRequest)
        : route('employee.leave.store');
    $openModal = $openModal ?? ($editing || $errors->any());
    $familyRelations = $familyRelations ?? ['mother', 'father', 'child', 'spouse'];
    $coverageDepartments = $coverageDepartments ?? collect();
    $eligibleCoverStaff = $eligibleCoverStaff ?? collect();
    $leaveTypeMeta = $leaveTypeMeta ?? [];
    $staff = $staff ?? null;
    $existingCovers = $editing
        ? $editRequest->coverages->pluck('cover_staff_id', 'department_id')->all()
        : [];

    $defaultMobile = old('contact_mobile', $editRequest?->contact_mobile ?: ($staff?->phone_number ?? ''));
    $defaultEmail = old('contact_email', $editRequest?->contact_email ?: ($staff?->primary_email ?? $staff?->organisation_email ?? ''));
    $defaultPostal = old(
        'contact_postal_address',
        $editRequest?->contact_postal_address
            ?: trim(collect([$staff?->postal_address, $staff?->postal_code])->filter()->implode(', '))
    );
    $defaultReturn = old(
        'return_date',
        $editRequest?->return_date?->format('Y-m-d')
            ?? ($editRequest?->end_date ? $editRequest->end_date->copy()->addDay()->format('Y-m-d') : '')
    );
@endphp

<div id="{{ $modalId }}" class="tich-modal{{ $openModal ? ' is-open' : '' }}" aria-hidden="{{ $openModal ? 'false' : 'true' }}" role="dialog" aria-modal="true" aria-labelledby="{{ $modalId }}-title">
    <div class="tich-modal__backdrop" data-close-modal="{{ $modalId }}"></div>
    <div class="tich-modal__dialog tich-modal__dialog--leave">
        <header class="tich-modal__header">
            <h2 class="tich-h3" id="{{ $modalId }}-title">{{ $editing ? 'Update leave application' : 'Leave application form' }}</h2>
            <button type="button" class="tich-modal__close" data-close-modal="{{ $modalId }}" aria-label="Close">&times;</button>
        </header>

        <form method="POST" action="{{ $formAction }}" enctype="multipart/form-data" class="tich-modal__body tich-leave-app-form" data-uf="skip">
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

            <div class="tich-leave-app-form__letterhead">
                <strong>Tropical Institute of Community Health and Development</strong>
                <p>Leave application form — submitted to Human Resource for recording of leave days available.</p>
            </div>

            <section class="tich-leave-app-form__section">
                <h3 class="tich-leave-app-form__section-title">Applicant (auto-filled)</h3>
                <div class="tich-leave-app-form__readonly-grid">
                    <div>
                        <span class="tich-kv-grid__label">Applicant name</span>
                        <span class="tich-kv-grid__value">{{ $staff?->fullName() ?? '—' }}</span>
                    </div>
                    <div>
                        <span class="tich-kv-grid__label">Employee no.</span>
                        <span class="tich-kv-grid__value">{{ $staff?->employee_number ?? '—' }}</span>
                    </div>
                    <div>
                        <span class="tich-kv-grid__label">Position</span>
                        <span class="tich-kv-grid__value">{{ $staff?->job_title ?? '—' }}</span>
                    </div>
                    <div>
                        <span class="tich-kv-grid__label">Department</span>
                        <span class="tich-kv-grid__value">{{ $staff?->department?->dept_name ?? '—' }}</span>
                    </div>
                    <div>
                        <span class="tich-kv-grid__label">Application date</span>
                        <span class="tich-kv-grid__value">{{ now()->format('d/m/Y') }}</span>
                    </div>
                </div>
            </section>

            <section class="tich-leave-app-form__section">
                <h3 class="tich-leave-app-form__section-title">Leave details</h3>

                <div class="uf-field">
                    <label for="leave_type_id">Leave type <span class="uf-req">*</span></label>
                    <select id="leave_type_id" name="leave_type_id" required>
                        <option value="">Select leave type</option>
                        @foreach ($leaveTypes as $type)
                            @php $meta = $leaveTypeMeta[$type->id] ?? []; @endphp
                            <option
                                value="{{ $type->id }}"
                                @selected(old('leave_type_id', $editRequest?->leave_type_id) == $type->id)
                                data-leave-code="{{ $meta['code'] ?? $type->leave_code }}"
                                data-calculation-type="{{ $meta['calculation_type'] ?? $type->calculation_type }}"
                                data-accrual-type="{{ $meta['accrual_type'] ?? $type->accrual_type }}"
                                data-accrual-rate="{{ $meta['accrual_rate'] ?? $type->accrual_rate }}"
                                data-requires-document="{{ ! empty($meta['requires_document']) ? '1' : '0' }}"
                                data-document-label="{{ $meta['document_label'] ?? 'Supporting document' }}"
                                data-requires-family-relation="{{ ! empty($meta['requires_family_relation']) ? '1' : '0' }}"
                                data-days-allowed="{{ $meta['days_allowed'] ?? $type->days_allowed_per_year }}"
                                data-available-balance="{{ $meta['available_balance'] ?? 0 }}"
                                data-description="{{ e($meta['description'] ?? '') }}"
                            >
                                {{ $type->leave_name }}
                            </option>
                        @endforeach
                    </select>
                    <span class="uf-hint" id="leave-type-hint"></span>
                </div>

                <div class="uf-field" id="family-relation-field" style="display:none;">
                    <label for="family_relation">Family member <span class="uf-req">*</span></label>
                    <select id="family_relation" name="family_relation">
                        <option value="">Select relation</option>
                        @foreach ($familyRelations as $relation)
                            <option value="{{ $relation }}" @selected(old('family_relation', $editRequest?->family_relation) === $relation)>
                                {{ ucfirst($relation) }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="uf-form-grid-2">
                    <div class="uf-field">
                        <label for="start_date">With effect from (start) <span class="uf-req">*</span></label>
                        <input type="date" id="start_date" name="start_date" required
                            value="{{ old('start_date', $editRequest?->start_date?->format('Y-m-d')) }}">
                    </div>
                    <div class="uf-field">
                        <label for="end_date">To (inclusive) <span class="uf-req">*</span></label>
                        <input type="date" id="end_date" name="end_date" required
                            value="{{ old('end_date', $editRequest?->end_date?->format('Y-m-d')) }}">
                    </div>
                </div>

                <div class="uf-form-grid-2">
                    <div class="uf-field">
                        <label for="return_date">I will resume work on</label>
                        <input type="date" id="return_date" name="return_date"
                            value="{{ $defaultReturn }}">
                        <span class="uf-hint">Defaults to the day after the end date.</span>
                    </div>
                    <div class="uf-field">
                        <label>Days applied for</label>
                        <p class="tich-leave-app-form__metric" id="days-preview">—</p>
                    </div>
                </div>

                <div class="tich-leave-app-form__remarks">
                    <div>
                        <span class="tich-kv-grid__label">Number of days available</span>
                        <p class="tich-leave-app-form__metric" id="days-available">—</p>
                    </div>
                    <div>
                        <span class="tich-kv-grid__label">Number of days applied for</span>
                        <p class="tich-leave-app-form__metric" id="days-applied">—</p>
                    </div>
                </div>

                <div class="uf-field" id="certificate-field" style="display:none;">
                    <label for="medical_certificate" id="certificate-label">Supporting document</label>
                    <input type="file" id="medical_certificate" name="medical_certificate" accept=".pdf,.jpg,.jpeg,.png">
                    @if ($editing && ($editRequest->medical_certificate_path || $editRequest->supporting_document_path))
                        <span class="uf-hint">A document is already on file. Upload only to replace it.</span>
                    @endif
                </div>

                <div class="uf-field">
                    <label for="reason">Purpose / remarks <span class="uf-req">*</span></label>
                    <textarea id="reason" name="reason" rows="3" required placeholder="Brief purpose for this leave">{{ old('reason', $editRequest?->reason) }}</textarea>
                </div>

                <div class="uf-field">
                    <label class="tich-checkbox">
                        <input type="checkbox" name="is_emergency" value="1" @checked(old('is_emergency', $editRequest?->is_emergency))>
                        Emergency leave
                    </label>
                </div>
            </section>

            <section class="tich-leave-app-form__section">
                <h3 class="tich-leave-app-form__section-title">While on leave I can be reached on</h3>
                <div class="uf-form-grid-2">
                    <div class="uf-field">
                        <label for="contact_mobile">Mobile</label>
                        <input type="text" id="contact_mobile" name="contact_mobile" value="{{ $defaultMobile }}" placeholder="e.g. 0712…">
                    </div>
                    <div class="uf-field">
                        <label for="contact_email">E-mail address</label>
                        <input type="email" id="contact_email" name="contact_email" value="{{ $defaultEmail }}">
                    </div>
                </div>
                <div class="uf-field">
                    <label for="contact_postal_address">Postal address</label>
                    <input type="text" id="contact_postal_address" name="contact_postal_address" value="{{ $defaultPostal }}" placeholder="Optional">
                </div>
            </section>

            <section class="tich-leave-app-form__section">
                <h3 class="tich-leave-app-form__section-title">While I am away (stand-in / designee)</h3>
                @if ($coverageDepartments->isNotEmpty())
                    <span class="uf-hint" style="display:block;margin-bottom:0.75rem;">
                        Appoint who will stand in for you. Appointment is auto-accepted; department access is granted when HR approves.
                    </span>
                    @foreach ($coverageDepartments as $dept)
                        <div class="uf-field">
                            <label for="cover_staff_{{ $dept->id }}">{{ $dept->dept_name }} <span class="uf-req">*</span></label>
                            <select
                                id="cover_staff_{{ $dept->id }}"
                                name="cover_staff_id[{{ $dept->id }}]"
                                class="js-cover-staff"
                                data-dept-name="{{ $dept->dept_name }}"
                                required
                            >
                                <option value="">Select stand-in</option>
                                @foreach ($eligibleCoverStaff as $cover)
                                    <option
                                        value="{{ $cover->id }}"
                                        data-title="{{ $cover->job_title ?? '' }}"
                                        data-dept="{{ $cover->department?->dept_name ?? '' }}"
                                        @selected((string) old("cover_staff_id.{$dept->id}", $existingCovers[$dept->id] ?? '') === (string) $cover->id)
                                    >
                                        {{ $cover->fullName() }}
                                        @if ($cover->job_title) — {{ $cover->job_title }} @endif
                                        @if ($cover->employee_number) ({{ $cover->employee_number }}) @endif
                                    </option>
                                @endforeach
                            </select>
                            <span class="uf-hint js-cover-meta" id="cover_meta_{{ $dept->id }}"></span>
                        </div>
                    @endforeach
                @else
                    <p class="tich-caption">No department takeover is required for your profile. You may still note handover details below.</p>
                @endif

                <div class="uf-field">
                    <label for="handover_notes">Handover notes</label>
                    <textarea id="handover_notes" name="handover_notes" rows="3" placeholder="Key duties and handover points for your stand-in…">{{ old('handover_notes', $editRequest?->handover_notes) }}</textarea>
                </div>
            </section>

            <p class="tich-caption tich-leave-app-form__footnote">
                Submitted electronically to HR for approval and recording of leave days. Leave of officers heading a college unit may require Director confirmation as per policy.
            </p>

            <script>
                (function() {
                    const leaveTypeSelect = document.getElementById('leave_type_id');
                    const hintEl = document.getElementById('leave-type-hint');
                    const daysPreview = document.getElementById('days-preview');
                    const daysApplied = document.getElementById('days-applied');
                    const daysAvailable = document.getElementById('days-available');
                    const startInput = document.getElementById('start_date');
                    const endInput = document.getElementById('end_date');
                    const returnInput = document.getElementById('return_date');
                    const certificateField = document.getElementById('certificate-field');
                    const certificateLabel = document.getElementById('certificate-label');
                    const familyField = document.getElementById('family-relation-field');
                    const familySelect = document.getElementById('family_relation');
                    let returnTouched = {{ $defaultReturn !== '' && old('return_date') ? 'true' : 'false' }};

                    function selectedOption() {
                        return leaveTypeSelect.options[leaveTypeSelect.selectedIndex];
                    }

                    function updateHint() {
                        const option = selectedOption();
                        if (!option || !option.value) {
                            hintEl.textContent = '';
                            daysAvailable.textContent = '—';
                            certificateField.style.display = 'none';
                            familyField.style.display = 'none';
                            familySelect.required = false;
                            return;
                        }

                        const requiresDocument = option.dataset.requiresDocument === '1';
                        const documentLabel = option.dataset.documentLabel || 'Supporting document';
                        const requiresFamily = option.dataset.requiresFamilyRelation === '1';
                        const description = option.dataset.description || '';
                        const balance = option.dataset.availableBalance;

                        hintEl.textContent = description;
                        daysAvailable.textContent = (balance !== undefined && balance !== '')
                            ? (parseFloat(balance).toFixed(1).replace(/\.0$/, '') + ' day(s)')
                            : '—';
                        certificateField.style.display = requiresDocument ? 'block' : 'none';
                        certificateLabel.textContent = documentLabel;
                        familyField.style.display = requiresFamily ? 'block' : 'none';
                        familySelect.required = requiresFamily;
                        if (!requiresFamily) {
                            familySelect.value = '';
                        }
                    }

                    function calcDays() {
                        if (!startInput.value || !endInput.value) {
                            return null;
                        }
                        const start = new Date(startInput.value + 'T00:00:00');
                        const end = new Date(endInput.value + 'T00:00:00');
                        if (isNaN(start.getTime()) || isNaN(end.getTime()) || end < start) {
                            return null;
                        }

                        const option = selectedOption();
                        const calc = option && option.value ? (option.dataset.calculationType || 'calendar_days') : 'calendar_days';
                        let days = 0;
                        if (calc === 'working_days') {
                            for (let d = new Date(start); d <= end; d.setDate(d.getDate() + 1)) {
                                const day = d.getDay();
                                if (day !== 0 && day !== 6) {
                                    days++;
                                }
                            }
                        } else {
                            days = Math.round((end - start) / (1000 * 60 * 60 * 24)) + 1;
                        }
                        return days;
                    }

                    function syncReturnDate() {
                        if (returnTouched || !endInput.value) {
                            return;
                        }
                        const end = new Date(endInput.value + 'T00:00:00');
                        if (isNaN(end.getTime())) {
                            return;
                        }
                        end.setDate(end.getDate() + 1);
                        const yyyy = end.getFullYear();
                        const mm = String(end.getMonth() + 1).padStart(2, '0');
                        const dd = String(end.getDate()).padStart(2, '0');
                        returnInput.value = yyyy + '-' + mm + '-' + dd;
                    }

                    function updateDaysPreview() {
                        syncReturnDate();
                        const days = calcDays();
                        if (days === null) {
                            daysPreview.textContent = '—';
                            daysApplied.textContent = '—';
                            return;
                        }
                        const label = days + ' day(s)';
                        daysPreview.textContent = label;
                        daysApplied.textContent = label;
                    }

                    function updateCoverMeta(select) {
                        const meta = document.getElementById('cover_meta_' + select.id.replace('cover_staff_', ''));
                        if (!meta) return;
                        const option = select.options[select.selectedIndex];
                        if (!option || !option.value) {
                            meta.textContent = '';
                            return;
                        }
                        const title = option.dataset.title || '';
                        const dept = option.dataset.dept || select.dataset.deptName || '';
                        const parts = [];
                        if (title) parts.push('Title: ' + title);
                        if (dept) parts.push('Department: ' + dept);
                        meta.textContent = parts.join(' · ');
                    }

                    leaveTypeSelect.addEventListener('change', function() {
                        updateHint();
                        updateDaysPreview();
                    });
                    startInput.addEventListener('change', updateDaysPreview);
                    endInput.addEventListener('change', updateDaysPreview);
                    returnInput.addEventListener('change', function() {
                        returnTouched = !!returnInput.value;
                    });

                    document.querySelectorAll('.js-cover-staff').forEach(function(select) {
                        select.addEventListener('change', function() {
                            updateCoverMeta(select);
                        });
                        updateCoverMeta(select);
                    });

                    updateHint();
                    updateDaysPreview();
                })();
            </script>

            <footer class="tich-modal__footer tich-mt-4">
                <button type="button" class="tich-btn tich-btn-secondary" data-close-modal="{{ $modalId }}">Cancel</button>
                <button type="submit" class="tich-btn tich-btn-primary">
                    {{ $editing ? 'Resubmit to HR' : 'Submit to HR' }}
                </button>
            </footer>
        </form>
    </div>
</div>

@include('admin.partials.tich-modal-assets')
