@extends('layouts.hr')

@section('title', 'Edit Training')

@section('hr-content')
    <x-page-toolbar title="Edit Training Record" />

    <article class="tich-card">
        <form method="POST" action="{{ route('hr.training.update', $training) }}">
            @csrf
            @method('PUT')

            <div class="tich-grid tich-grid--2 tich-mb-6">
                <div>
                    <label for="staff_ids" class="tich-label">Staff Members</label>
                    <select id="staff_ids" name="staff_ids[]" multiple class="tich-input" style="min-height: 10rem;">
                        <option value="">- Select staff -</option>
                        @foreach ($staff as $s)
                            <option value="{{ $s->id }}" {{ old('staff_ids', $training->assigned_staff_ids) && in_array($s->id, old('staff_ids', $training->assigned_staff_ids)) ? 'selected' : '' }}>
                                {{ $s->fullName() }} ({{ $s->employee_number }})
                            </option>
                        @endforeach
                    </select>
                    <p class="tich-caption tich-mt-2">Hold Ctrl/Cmd to select multiple staff. Leave empty to assign to all employees.</p>
                </div>
                <div>
                    <label class="tich-checkbox tich-mt-6">
                        <input type="checkbox" id="assign_all" name="assign_all" value="1" {{ $training->is_assigned_to_all ? 'checked' : '' }}>
                        <span><strong>Assign to all employees</strong></span>
                    </label>
                </div>
                <div>
                    <label for="activity_type" class="tich-label">Activity Type *</label>
                    <select id="activity_type" name="activity_type" required class="tich-input">
                        <option value="">Select type</option>
                        <option value="training" {{ old('activity_type', $training->activity_type) == 'training' ? 'selected' : '' }}>Training</option>
                        <option value="workshop" {{ old('activity_type', $training->activity_type) == 'workshop' ? 'selected' : '' }}>Workshop</option>
                        <option value="conference" {{ old('activity_type', $training->activity_type) == 'conference' ? 'selected' : '' }}>Conference</option>
                        <option value="seminar" {{ old('activity_type', $training->activity_type) == 'seminar' ? 'selected' : '' }}>Seminar</option>
                        <option value="cpd" {{ old('activity_type', $training->activity_type) == 'cpd' ? 'selected' : '' }}>CPD</option>
                        <option value="study_leave" {{ old('activity_type', $training->activity_type) == 'study_leave' ? 'selected' : '' }}>Study Leave</option>
                        <option value="attachment" {{ old('activity_type', $training->activity_type) == 'attachment' ? 'selected' : '' }}>Attachment</option>
                        <option value="mentorship" {{ old('activity_type', $training->activity_type) == 'mentorship' ? 'selected' : '' }}>Mentorship</option>
                    </select>
                </div>
                <div>
                    <label for="activity_name" class="tich-label">Activity Name *</label>
                    <input type="text" id="activity_name" name="activity_name" value="{{ old('activity_name', $training->activity_name) }}" required class="tich-input">
                </div>
                <div>
                    <label for="organizer" class="tich-label">Organizer</label>
                    <input type="text" id="organizer" name="organizer" value="{{ old('organizer', $training->organizer) }}" class="tich-input">
                </div>
                <div>
                    <label for="start_date" class="tich-label">Start Date *</label>
                    <input type="date" id="start_date" name="start_date" value="{{ old('start_date', $training->start_date?->format('Y-m-d')) }}" required class="tich-input">
                </div>
                <div>
                    <label for="end_date" class="tich-label">End Date</label>
                    <input type="date" id="end_date" name="end_date" value="{{ old('end_date', $training->end_date?->format('Y-m-d')) }}" class="tich-input">
                </div>
                <div>
                    <label for="hours_or_days" class="tich-label">Hours / Days</label>
                    <input type="number" step="0.01" id="hours_or_days" name="hours_or_days" value="{{ old('hours_or_days', $training->hours_or_days) }}" class="tich-input">
                </div>
                <div>
                    <label for="cpd_credits_earned" class="tich-label">CPD Credits</label>
                    <input type="number" step="0.01" id="cpd_credits_earned" name="cpd_credits_earned" value="{{ old('cpd_credits_earned', $training->cpd_credits_earned) }}" class="tich-input">
                </div>
                <div>
                    <label for="location" class="tich-label">Location</label>
                    <input type="text" id="location" name="location" value="{{ old('location', $training->location) }}" class="tich-input">
                </div>
                <div>
                    <label for="cost" class="tich-label">Cost (KES)</label>
                    <input type="number" step="0.01" id="cost" name="cost" value="{{ old('cost', $training->cost) }}" class="tich-input">
                </div>
                <div>
                    <label for="funded_by" class="tich-label">Funded By</label>
                    <select id="funded_by" name="funded_by" class="tich-input">
                        <option value="">Select</option>
                        <option value="institution" {{ old('funded_by', $training->funded_by) == 'institution' ? 'selected' : '' }}>Institution</option>
                        <option value="self" {{ old('funded_by', $training->funded_by) == 'self' ? 'selected' : '' }}>Self</option>
                        <option value="donor" {{ old('funded_by', $training->funded_by) == 'donor' ? 'selected' : '' }}>Donor</option>
                        <option value="sponsor" {{ old('funded_by', $training->funded_by) == 'sponsor' ? 'selected' : '' }}>Sponsor</option>
                    </select>
                </div>
                <div class="tich-flex--center">
                    <label class="tich-checkbox">
                        <input type="checkbox" id="is_external" name="is_external" value="1" {{ old('is_external', $training->is_external) ? 'checked' : '' }}>
                        <span>External</span>
                    </label>
                </div>
                <div class="tich-flex--center">
                    <label class="tich-checkbox">
                        <input type="checkbox" id="is_completed" name="is_completed" value="1" {{ old('is_completed', $training->is_completed) ? 'checked' : '' }}>
                        <span>Completed</span>
                    </label>
                </div>
                <div class="tich-grid--span-2">
                    <label for="appraisal_relevance" class="tich-label">Appraisal Relevance</label>
                    <textarea id="appraisal_relevance" name="appraisal_relevance" rows="2" class="tich-input">{{ old('appraisal_relevance', $training->appraisal_relevance) }}</textarea>
                </div>
            </div>

            <div class="tich-mt-6">
                <button type="submit" class="tich-btn tich-btn-primary">Update Training</button>
                <a href="{{ route('hr.training.index') }}" class="tich-btn tich-btn-ghost">Cancel</a>
            </div>
        </form>
    </article>
@endsection