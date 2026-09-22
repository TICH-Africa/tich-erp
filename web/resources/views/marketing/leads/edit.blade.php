@extends('layouts.marketing')

@section('title', 'Edit Lead')

@section('department-content')
    <x-page-toolbar title="Edit Lead" meta="{{ $lead->name }}" />

    <form method="POST" action="{{ route('marketing.leads.update', $lead) }}" enctype="multipart/form-data" class="tich-blog-compose" data-blog-compose style="padding: 1rem;">
        @csrf
        @method('PUT')
        <div class="tich-form-grid tich-form-grid--2" style="gap: 1rem;">
            <div class="tich-form-group">
                <label class="tich-label" for="name">Name</label>
                <input id="name" type="text" name="name" class="tich-input" value="{{ old('name', $lead->name) }}" required maxlength="300">
            </div>
            <div class="tich-form-group">
                <label class="tich-label" for="email">Email</label>
                <input id="email" type="email" name="email" class="tich-input" value="{{ old('email', $lead->email) }}" maxlength="255">
            </div>
            <div class="tich-form-group">
                <label class="tich-label" for="phone">Phone</label>
                <input id="phone" type="text" name="phone" class="tich-input" value="{{ old('phone', $lead->phone) }}" maxlength="50">
            </div>
            <div class="tich-form-group">
                <label class="tich-label" for="source">Source</label>
                <select id="source" name="source" class="tich-input">
                    <option value="referral" {{ old('source', $lead->source) === 'referral' ? 'selected' : '' }}>Referral</option>
                    <option value="social_media" {{ old('source', $lead->source) === 'social_media' ? 'selected' : '' }}>Social Media</option>
                    <option value="website" {{ old('source', $lead->source) === 'website' ? 'selected' : '' }}>Website</option>
                    <option value="event" {{ old('source', $lead->source) === 'event' ? 'selected' : '' }}>Event</option>
                    <option value="direct" {{ old('source', $lead->source) === 'direct' ? 'selected' : '' }}>Direct</option>
                    <option value="other" {{ old('source', $lead->source) === 'other' ? 'selected' : '' }}>Other</option>
                </select>
            </div>
            <div class="tich-form-group">
                <label class="tich-label" for="stage">Stage</label>
                <select id="stage" name="stage" class="tich-input" required>
                    @foreach ($stages as $stage)
                        <option value="{{ $stage }}" {{ old('stage', $lead->stage) === $stage ? 'selected' : '' }}>{{ ucfirst($stage) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="tich-form-group">
                <label class="tich-label" for="program_id">Programme</label>
                <select id="program_id" name="program_id" class="tich-input">
                    <option value="">All Programmes</option>
                    @foreach ($programs as $program)
                        <option value="{{ $program->id }}" {{ old('program_id', $lead->program_id) == $program->id ? 'selected' : '' }}>{{ $program->program_name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="tich-form-group">
                <label class="tich-label" for="intake">Intake</label>
                <input id="intake" type="text" name="intake" class="tich-input" value="{{ old('intake', $lead->intake) }}" maxlength="100">
            </div>
            <div class="tich-form-group">
                <label class="tich-label" for="next_followup">Next Followup</label>
                <input id="next_followup" type="date" name="next_followup" class="tich-input" value="{{ old('next_followup', $lead->next_followup) }}">
            </div>
            <div class="tich-form-group" style="grid-column: 1 / -1;">
                <label class="tich-label" for="notes">Notes</label>
                <textarea id="notes" name="notes" class="tich-input" rows="3">{{ old('notes', $lead->notes) }}</textarea>
            </div>
        </div>
        <div class="tich-mt-4 tich-blog-compose__footer">
            <a href="{{ route('marketing.leads.edit', $lead) }}" class="tich-btn tich-btn-secondary">Cancel</a>
            <button type="submit" class="tich-btn tich-btn-primary">Update Lead</button>
        </div>
    </form>
@endsection
