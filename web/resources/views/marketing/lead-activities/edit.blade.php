@extends('layouts.marketing')

@section('title', 'Edit Activity')

@section('department-content')
    <x-page-toolbar title="Edit Activity" meta="{{ $activity->lead->name }}" />

    <form method="POST" action="{{ route('marketing.lead-activities.update', $activity) }}" class="tich-blog-compose" style="padding: 1rem;">
        @csrf
        @method('PUT')
        <div class="tich-form-grid tich-form-grid--2" style="gap: 1rem;">
            <div class="tich-form-group">
                <label class="tich-label" for="lead_id">Lead</label>
                <select id="lead_id" name="lead_id" class="tich-input" required>
                    @forelse ($leads as $lead)
                        <option value="{{ $lead->id }}" {{ old('lead_id', $activity->lead_id) == $lead->id ? 'selected' : '' }}>{{ $lead->name }}</option>
                    @empty
                        <option value="" disabled>No leads available</option>
                    @endforelse
                </select>
            </div>
            <div class="tich-form-group">
                <label class="tich-label" for="activity_type">Type</label>
                <select id="activity_type" name="activity_type" class="tich-input" required>
                    @foreach ($activityTypes as $type)
                        <option value="{{ $type }}" {{ old('activity_type', $activity->activity_type) === $type ? 'selected' : '' }}>{{ ucfirst($type) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="tich-form-group">
                <label class="tich-label" for="scheduled_date">Scheduled Date</label>
                <input id="scheduled_date" type="date" name="scheduled_date" class="tich-input" value="{{ old('scheduled_date', $activity->scheduled_date) }}" required>
            </div>
            <div class="tich-form-group">
                <label class="tich-label" for="scheduled_time">Time</label>
                <input id="scheduled_time" type="time" name="scheduled_time" class="tich-input" value="{{ old('scheduled_time', $activity->scheduled_time) }}">
            </div>
            <div class="tich-form-group" style="grid-column: 1 / -1;">
                <label class="tich-label" for="description">Description</label>
                <textarea id="description" name="description" class="tich-input" rows="3">{{ old('description', $activity->description) }}</textarea>
            </div>
            <div class="tich-form-group" style="grid-column: 1 / -1;">
                <label class="tich-label" for="notes">Notes</label>
                <textarea id="notes" name="notes" class="tich-input" rows="2">{{ old('notes', $activity->notes) }}</textarea>
            </div>
            <div class="tich-form-group">
                <label class="tich-label" for="completed">Status</label>
                <select id="completed" name="completed" class="tich-input">
                    <option value="0" {{ old('completed', $activity->completed) === 0 ? 'selected' : '' }}>Pending</option>
                    <option value="1" {{ old('completed', $activity->completed) === 1 ? 'selected' : '' }}>Completed</option>
                </select>
            </div>
        </div>
        <div class="tich-mt-4 tich-blog-compose__footer">
            <a href="{{ route('marketing.lead-activities.index') }}" class="tich-btn tich-btn-secondary">Cancel</a>
            <button type="submit" class="tich-btn tich-btn-primary">Update Activity</button>
        </div>
    </form>
@endsection
