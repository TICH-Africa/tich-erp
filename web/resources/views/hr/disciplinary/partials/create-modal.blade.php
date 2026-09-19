<div
    id="disciplinary-create-modal"
    class="tich-modal{{ ($openCreateModal ?? false) ? ' is-open' : '' }}"
    aria-hidden="{{ ($openCreateModal ?? false) ? 'false' : 'true' }}"
    role="dialog"
    aria-modal="true"
    aria-labelledby="disciplinary-create-modal-title"
>
    <div class="tich-modal__backdrop" data-close-modal="disciplinary-create-modal"></div>
    <div class="tich-modal__dialog" style="max-width: 40rem;">
        <header class="tich-modal__header">
            <h2 id="disciplinary-create-modal-title" class="tich-h3" style="margin: 0;">New disciplinary case</h2>
            <button type="button" class="tich-modal__close" data-close-modal="disciplinary-create-modal" aria-label="Close">&times;</button>
        </header>
        <form method="POST" action="{{ route('hr.employee-relations.disciplinary.store') }}" class="tich-modal__body" data-uf="skip">
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

            <div class="uf-form-grid-2">
                <div class="uf-field">
                    <label for="disciplinary-staff_id">Employee <span class="uf-req">*</span></label>
                    <select id="disciplinary-staff_id" name="staff_id" required>
                        <option value="">Select employee</option>
                        @foreach ($staffList as $staff)
                            <option value="{{ $staff->id }}" @selected(old('staff_id') == $staff->id)>
                                {{ $staff->fullName() }} ({{ $staff->employee_number }})
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="uf-field">
                    <label for="disciplinary-assigned_to">Assign to</label>
                    <select id="disciplinary-assigned_to" name="assigned_to">
                        <option value="">Unassigned</option>
                        @foreach ($staffList as $staff)
                            <option value="{{ $staff->id }}" @selected(old('assigned_to') == $staff->id)>
                                {{ $staff->fullName() }} ({{ $staff->employee_number }})
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="uf-field">
                    <label for="disciplinary-incident_date">Incident date <span class="uf-req">*</span></label>
                    <input type="date" id="disciplinary-incident_date" name="incident_date" value="{{ old('incident_date') }}" required>
                </div>
                <div class="uf-field">
                    <label for="disciplinary-hearing_date">Hearing date</label>
                    <input type="date" id="disciplinary-hearing_date" name="hearing_date" value="{{ old('hearing_date') }}">
                </div>
            </div>
            <div class="uf-field">
                <label for="disciplinary-incident_description">Incident description <span class="uf-req">*</span></label>
                <textarea id="disciplinary-incident_description" name="incident_description" rows="4" required placeholder="Describe the incident...">{{ old('incident_description') }}</textarea>
            </div>
            <div class="uf-field">
                <label for="disciplinary-investigation_notes">Investigation notes</label>
                <textarea id="disciplinary-investigation_notes" name="investigation_notes" rows="3" placeholder="Investigation findings...">{{ old('investigation_notes') }}</textarea>
            </div>
            <div class="uf-field">
                <label for="disciplinary-witness_information">Witness information</label>
                <textarea id="disciplinary-witness_information" name="witness_information" rows="2" placeholder="Witness names, contacts, statements...">{{ old('witness_information') }}</textarea>
            </div>
            <div class="uf-field">
                <label for="disciplinary-committee_members">Committee members</label>
                <textarea id="disciplinary-committee_members" name="committee_members" rows="2" placeholder="Names of committee members...">{{ old('committee_members') }}</textarea>
            </div>

            <footer class="tich-modal__footer">
                <button type="button" class="tich-btn tich-btn-secondary" data-close-modal="disciplinary-create-modal">Cancel</button>
                <button type="submit" class="tich-btn tich-btn-primary">Create case</button>
            </footer>
        </form>
    </div>
</div>
