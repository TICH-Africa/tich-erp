<div
    id="grievance-create-modal"
    class="tich-modal{{ ($openCreateModal ?? false) ? ' is-open' : '' }}"
    aria-hidden="{{ ($openCreateModal ?? false) ? 'false' : 'true' }}"
    role="dialog"
    aria-modal="true"
    aria-labelledby="grievance-create-modal-title"
>
    <div class="tich-modal__backdrop" data-close-modal="grievance-create-modal"></div>
    <div class="tich-modal__dialog" style="max-width: 40rem;">
        <header class="tich-modal__header">
            <h2 id="grievance-create-modal-title" class="tich-h3" style="margin: 0;">New grievance</h2>
            <button type="button" class="tich-modal__close" data-close-modal="grievance-create-modal" aria-label="Close">&times;</button>
        </header>
        <form method="POST" action="{{ route('hr.employee-relations.grievances.store') }}" class="tich-modal__body">
            @csrf

            @if ($errors->any())
                <div class="tich-modal__errors tich-mb-4">
                    <ul style="margin: 0; padding-left: 1.25rem;">
                        @foreach ($errors->all() as $error)
                            <li class="tich-text">{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div style="display: grid; gap: 1rem;">
                <div class="tich-form-group" style="margin: 0;">
                    <label for="grievance-staff_id" class="tich-label">Employee *</label>
                    <select id="grievance-staff_id" name="staff_id" required class="tich-input">
                        <option value="">Select employee</option>
                        @foreach ($staffList as $staff)
                            <option value="{{ $staff->id }}" {{ old('staff_id') == $staff->id ? 'selected' : '' }}>
                                {{ $staff->fullName() }} ({{ $staff->employee_number }})
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="tich-form-group" style="margin: 0;">
                    <label for="grievance-assigned_to" class="tich-label">Assign to</label>
                    <select id="grievance-assigned_to" name="assigned_to" class="tich-input">
                        <option value="">Unassigned</option>
                        @foreach ($staffList as $staff)
                            <option value="{{ $staff->id }}" {{ old('assigned_to') == $staff->id ? 'selected' : '' }}>
                                {{ $staff->fullName() }} ({{ $staff->employee_number }})
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="tich-form-group" style="margin: 0;">
                    <label for="grievance-grievance_type" class="tich-label">Grievance type</label>
                    <input type="text" id="grievance-grievance_type" name="grievance_type" value="{{ old('grievance_type') }}" class="tich-input" placeholder="e.g. workplace, compensation, management">
                </div>
                <div class="tich-form-group" style="margin: 0;">
                    <label for="grievance-incident_date" class="tich-label">Incident date</label>
                    <input type="date" id="grievance-incident_date" name="incident_date" value="{{ old('incident_date') }}" class="tich-input">
                </div>
                <div class="tich-form-group" style="margin: 0;">
                    <label for="grievance-description" class="tich-label">Description *</label>
                    <textarea id="grievance-description" name="description" rows="5" required class="tich-input" placeholder="Describe the grievance...">{{ old('description') }}</textarea>
                </div>
                <div class="tich-form-group" style="margin: 0;">
                    <label for="grievance-resolution_notes" class="tich-label">Resolution notes</label>
                    <textarea id="grievance-resolution_notes" name="resolution_notes" rows="3" class="tich-input" placeholder="How was this resolved...">{{ old('resolution_notes') }}</textarea>
                </div>
            </div>

            <footer class="tich-modal__footer">
                <button type="button" class="tich-btn tich-btn-secondary" data-close-modal="grievance-create-modal">Cancel</button>
                <button type="submit" class="tich-btn tich-btn-primary">Create grievance</button>
            </footer>
        </form>
    </div>
</div>
