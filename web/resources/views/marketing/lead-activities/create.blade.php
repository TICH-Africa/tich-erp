@extends('layouts.marketing')

@section('title', 'Add Activity')

@section('department-content')
    <x-page-toolbar title="Add Activity" meta="Schedule a marketing activity" />

    <form method="POST" action="{{ route('marketing.lead-activities.store') }}" class="tich-blog-compose" style="padding: 1rem;">
        @csrf
        <div class="tich-form-grid tich-form-grid--2" style="gap: 1rem;">
            <div class="tich-form-group">
                <label class="tich-label" for="lead_id">Lead</label>
                <select id="lead_id" name="lead_id" class="tich-input" required>
                    <option value="">Select Lead</option>
                    @forelse ($leads as $lead)
                        <option value="{{ $lead->id }}">{{ $lead->name }}</option>
                    @empty
                        <option value="" disabled>No leads available — create one first</option>
                    @endforelse
                </select>
            </div>
            <div class="tich-form-group">
                <label class="tich-label" for="activity_type">Type</label>
                <select id="activity_type" name="activity_type" class="tich-input" required>
                    @foreach ($activityTypes as $type)
                        <option value="{{ $type }}">{{ ucfirst($type) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="tich-form-group">
                <label class="tich-label" for="scheduled_date">Scheduled Date</label>
                <input id="scheduled_date" type="date" name="scheduled_date" class="tich-input" required>
            </div>
            <div class="tich-form-group">
                <label class="tich-label" for="scheduled_time">Time</label>
                <input id="scheduled_time" type="time" name="scheduled_time" class="tich-input">
            </div>
            <div class="tich-form-group" style="grid-column: 1 / -1;">
                <label class="tich-label" for="description">Description</label>
                <textarea id="description" name="description" class="tich-input" rows="3"></textarea>
            </div>
            <div class="tich-form-group" style="grid-column: 1 / -1;">
                <label class="tich-label" for="notes">Notes</label>
                <textarea id="notes" name="notes" class="tich-input" rows="2"></textarea>
            </div>
        </div>
        <div class="tich-mt-4 tich-blog-compose__footer">
            <a href="{{ route('marketing.lead-activities.index') }}" class="tich-btn tich-btn-secondary">Cancel</a>
            <button type="submit" class="tich-btn tich-btn-primary">Save Activity</button>
        </div>
    </form>
@endsection
