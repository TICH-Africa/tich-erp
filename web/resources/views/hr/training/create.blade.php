@extends('layouts.hr')

@section('title', 'Add Training')

@section('hr-content')
    <x-page-toolbar title="Add Training Record" />

    <div class="uf-form">
        <form method="POST" action="{{ route('hr.training.store') }}" data-uf="ready">
            @csrf

            <div class="uf-amount-bar">
                <div>
                    <div class="uf-amount-bar__ref">TRN · Training record</div>
                    <div class="uf-amount-bar__sum">Add training</div>
                </div>
                <span class="uf-badge">Draft</span>
            </div>

            <div class="uf-form-section">
                <div class="uf-section-head">Assignment</div>
                <div class="uf-section-body">
                    <div class="uf-form-grid-2">
                        <div class="uf-field">
                            <label for="staff_ids">Staff Members</label>
                            <select id="staff_ids" name="staff_ids[]" multiple style="min-height: 10rem;">
                                <option value="">- Select staff -</option>
                                @foreach ($staff as $s)
                                    <option value="{{ $s->id }}" @selected(old('staff_ids') && in_array($s->id, old('staff_ids')))>
                                        {{ $s->fullName() }} ({{ $s->employee_number }})
                                    </option>
                                @endforeach
                            </select>
                            <span class="uf-hint">Hold Ctrl/Cmd to select multiple staff. Leave empty to assign to all employees.</span>
                        </div>
                        <div class="uf-field">
                            <label class="tich-checkbox">
                                <input type="checkbox" id="assign_all" name="assign_all" value="1" @checked(old('assign_all'))>
                                <span><strong>Assign to all employees</strong></span>
                            </label>
                        </div>
                    </div>
                </div>
            </div>

            <div class="uf-form-section">
                <div class="uf-section-head">Activity Details</div>
                <div class="uf-section-body">
                    <div class="uf-form-grid-2">
                        <div class="uf-field">
                            <label for="activity_type">Activity Type <span class="uf-req">*</span></label>
                            <select id="activity_type" name="activity_type" required class="{{ $errors->has('activity_type') ? 'is-invalid' : '' }}">
                                <option value="">Select type</option>
                                <option value="training" @selected(old('activity_type') == 'training')>Training</option>
                                <option value="workshop" @selected(old('activity_type') == 'workshop')>Workshop</option>
                                <option value="conference" @selected(old('activity_type') == 'conference')>Conference</option>
                                <option value="seminar" @selected(old('activity_type') == 'seminar')>Seminar</option>
                                <option value="cpd" @selected(old('activity_type') == 'cpd')>CPD</option>
                                <option value="study_leave" @selected(old('activity_type') == 'study_leave')>Study Leave</option>
                                <option value="attachment" @selected(old('activity_type') == 'attachment')>Attachment</option>
                                <option value="mentorship" @selected(old('activity_type') == 'mentorship')>Mentorship</option>
                            </select>
                            @error('activity_type')
                                <span class="uf-error">{{ $message }}</span>
                            @enderror
                        </div>
                        <div class="uf-field">
                            <label for="activity_name">Activity Name <span class="uf-req">*</span></label>
                            <input type="text" id="activity_name" name="activity_name" value="{{ old('activity_name') }}" required class="{{ $errors->has('activity_name') ? 'is-invalid' : '' }}">
                            @error('activity_name')
                                <span class="uf-error">{{ $message }}</span>
                            @enderror
                        </div>
                        <div class="uf-field">
                            <label for="organizer">Organizer</label>
                            <input type="text" id="organizer" name="organizer" value="{{ old('organizer') }}">
                        </div>
                        <div class="uf-field">
                            <label for="start_date">Start Date <span class="uf-req">*</span></label>
                            <input type="date" id="start_date" name="start_date" value="{{ old('start_date') }}" required class="{{ $errors->has('start_date') ? 'is-invalid' : '' }}">
                            @error('start_date')
                                <span class="uf-error">{{ $message }}</span>
                            @enderror
                        </div>
                        <div class="uf-field">
                            <label for="end_date">End Date</label>
                            <input type="date" id="end_date" name="end_date" value="{{ old('end_date') }}">
                        </div>
                        <div class="uf-field">
                            <label for="hours_or_days">Hours / Days</label>
                            <input type="number" step="0.01" id="hours_or_days" name="hours_or_days" value="{{ old('hours_or_days') }}">
                        </div>
                        <div class="uf-field">
                            <label for="cpd_credits_earned">CPD Credits</label>
                            <input type="number" step="0.01" id="cpd_credits_earned" name="cpd_credits_earned" value="{{ old('cpd_credits_earned') }}">
                        </div>
                        <div class="uf-field">
                            <label for="location">Location</label>
                            <input type="text" id="location" name="location" value="{{ old('location') }}">
                        </div>
                        <div class="uf-field">
                            <label for="cost">Cost (KES)</label>
                            <input type="number" step="0.01" id="cost" name="cost" value="{{ old('cost') }}">
                        </div>
                        <div class="uf-field">
                            <label for="funded_by">Funded By</label>
                            <select id="funded_by" name="funded_by">
                                <option value="">Select</option>
                                <option value="institution" @selected(old('funded_by') == 'institution')>Institution</option>
                                <option value="self" @selected(old('funded_by') == 'self')>Self</option>
                                <option value="donor" @selected(old('funded_by') == 'donor')>Donor</option>
                                <option value="sponsor" @selected(old('funded_by') == 'sponsor')>Sponsor</option>
                            </select>
                        </div>
                        <div class="uf-field">
                            <label class="tich-checkbox">
                                <input type="checkbox" id="is_external" name="is_external" value="1" @checked(old('is_external'))>
                                <span>External</span>
                            </label>
                        </div>
                        <div class="uf-field">
                            <label class="tich-checkbox">
                                <input type="checkbox" id="is_completed" name="is_completed" value="1" @checked(old('is_completed'))>
                                <span>Completed</span>
                            </label>
                        </div>
                    </div>
                    <div class="uf-field">
                        <label for="appraisal_relevance">Appraisal Relevance</label>
                        <textarea id="appraisal_relevance" name="appraisal_relevance" rows="2">{{ old('appraisal_relevance') }}</textarea>
                    </div>
                </div>
            </div>

            <div class="uf-form-section">
                <div class="uf-section-head">Submit</div>
                <div class="uf-section-body">
                    <div class="uf-form-actions">
                        <button type="submit" class="uf-btn uf-btn-primary">Save Training</button>
                        <a href="{{ route('hr.training.index') }}" class="uf-btn uf-btn-secondary">Cancel</a>
                    </div>
                </div>
            </div>

            <p class="uf-form-footnote"><span class="uf-req">*</span> Required field</p>
        </form>
    </div>
@endsection
