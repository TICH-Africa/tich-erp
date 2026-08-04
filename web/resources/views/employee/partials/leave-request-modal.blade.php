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
                            <option value="{{ $type->id }}" @selected(old('leave_type_id', $editRequest?->leave_type_id) == $type->id)>
                                {{ $type->leave_name }}
                            </option>
                        @endforeach
                    </select>
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
                    </div>
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

                <div>
                    <label for="medical_certificate" class="tich-label">Medical certificate (if required)</label>
                    <input type="file" id="medical_certificate" name="medical_certificate" class="tich-input" accept=".pdf,.jpg,.jpeg,.png">
                </div>
            </div>

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
