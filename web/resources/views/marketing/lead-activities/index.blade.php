@extends('layouts.marketing')

@section('title', 'Lead Activities')

@section('department-content')
    <x-page-toolbar title="Lead Activities" meta="Schedule and record marketing activities">
        <x-slot:actions>
            <a href="{{ route('marketing.lead-activities.create') }}" class="tich-btn tich-btn-primary">Add Activity</a>
        </x-slot:actions>
    </x-page-toolbar>

    <div class="tich-card tich-mt-4 tich-mb-4" style="padding: 1rem;">
        <form method="GET" class="tich-form-grid tich-form-grid--3" style="gap: 1rem;">
            <div class="tich-form-group">
                <label class="tich-label" for="lead_id">Lead</label>
                <select id="lead_id" name="lead_id" class="tich-input">
                    <option value="">All Leads</option>
                    @foreach ($leads as $lead)
                        <option value="{{ $lead->id }}" {{ ($filters['lead_id'] ?? '') == $lead->id ? 'selected' : '' }}>{{ $lead->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="tich-form-group">
                <label class="tich-label" for="completed">Status</label>
                <select id="completed" name="completed" class="tich-input">
                    <option value="">All</option>
                    <option value="1" {{ ($filters['completed'] ?? '') === '1' ? 'selected' : '' }}>Completed</option>
                    <option value="0" {{ ($filters['completed'] ?? '') === '0' ? 'selected' : '' }}>Pending</option>
                </select>
            </div>
            <div class="tich-form-group">
                <label class="tich-label">&nbsp;</label>
                <button type="submit" class="tich-btn tich-btn-primary">Filter</button>
            </div>
        </form>
    </div>

    <div class="tich-card tich-table-panel">
        <table class="tich-admin-table">
            <thead>
                <tr>
                    <th>Lead</th>
                    <th>Type</th>
                    <th>Scheduled</th>
                    <th>Description</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($activities as $activity)
                    <tr>
                        <td><a href="{{ route('marketing.leads.edit', $activity->lead) }}" class="tich-link">{{ $activity->lead->name }}</a></td>
                        <td>{{ ucfirst($activity->activity_type) }}</td>
                        <td>{{ $activity->scheduled_date->format('d M Y') }}</td>
                        <td>{{ Str::limit($activity->description, 50) }}</td>
                        <td>{{ $activity->completed ? 'Completed' : 'Pending' }}</td>
                        <td>
                            <a href="{{ route('marketing.lead-activities.edit', $activity) }}" class="tich-squircle-btn" title="Edit">✎</a>
                            <form method="POST" action="{{ route('marketing.lead-activities.destroy', $activity) }}" onsubmit="return confirm('Delete this activity?');" style="display:inline;">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="tich-squircle-btn" title="Delete">×</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6">No activities found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{ $activities->links() }}
@endsection
