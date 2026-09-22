@extends('layouts.marketing')

@section('title', 'Marketing Leads')

@section('department-content')
    <x-page-toolbar title="Marketing Leads" meta="Prospects and sales pipeline">
        <x-slot:actions>
            <a href="{{ route('marketing.leads.create') }}" class="tich-btn tich-btn-primary">Add Lead</a>
        </x-slot:actions>
    </x-page-toolbar>

    <div class="tich-card tich-mt-4 tich-mb-4" style="padding: 1rem;">
        <form method="GET" class="tich-form-grid tich-form-grid--4" style="gap: 1rem;">
            <div class="tich-form-group">
                <label class="tich-label" for="search">Search</label>
                <input id="search" type="text" name="search" class="tich-input" value="{{ $filters['search'] ?? '' }}" placeholder="Name, email, phone">
            </div>
            <div class="tich-form-group">
                <label class="tich-label" for="stage">Stage</label>
                <select id="stage" name="stage" class="tich-input">
                    <option value="">All Stages</option>
                    @foreach ($stages as $stage)
                        <option value="{{ $stage }}" {{ ($filters['stage'] ?? '') === $stage ? 'selected' : '' }}>{{ ucfirst($stage) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="tich-form-group">
                <label class="tich-label" for="source">Source</label>
                <select id="source" name="source" class="tich-input">
                    <option value="">All Sources</option>
                    @foreach ($sources as $source)
                        <option value="{{ $source }}" {{ ($filters['source'] ?? '') === $source ? 'selected' : '' }}>{{ ucfirst(str_replace('_', ' ', $source)) }}</option>
                    @endforeach
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
                    <th>Name</th>
                    <th>Email</th>
                    <th>Phone</th>
                    <th>Source</th>
                    <th>Stage</th>
                    <th>Intake</th>
                    <th>Next Followup</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($leads as $lead)
                    <tr>
                        <td><a href="{{ route('marketing.leads.edit', $lead) }}" class="tich-link">{{ $lead->name }}</a></td>
                        <td>{{ $lead->email ?? '-' }}</td>
                        <td>{{ $lead->phone ?? '-' }}</td>
                        <td>{{ ucfirst(str_replace('_', ' ', $lead->source)) }}</td>
                        <td>{{ ucfirst($lead->stage) }}</td>
                        <td>{{ $lead->intake ?? '-' }}</td>
                        <td>{{ $lead->next_followup?->format('d M Y') }}</td>
                        <td>
                            <a href="{{ route('marketing.leads.edit', $lead) }}" class="tich-squircle-btn" title="Edit">✎</a>
                            <form method="POST" action="{{ route('marketing.leads.destroy', $lead) }}" onsubmit="return confirm('Delete this lead?');" style="display:inline;">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="tich-squircle-btn" title="Delete">×</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="8">No leads found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{ $leads->links() }}
@endsection
