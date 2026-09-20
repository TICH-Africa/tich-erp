@extends('layouts.research')

@section('title', 'Partnership inquiries')

@section('research-content')
    <x-page-toolbar title="Partnership inquiries" meta="Submissions from the public Research partner form">
        <x-slot:actions>
            <a href="{{ route('research.dashboard') }}" class="tich-btn tich-btn-ghost">Dashboard</a>
        </x-slot:actions>
    </x-page-toolbar>

    @if (session('status'))
        <div class="tich-alert tich-alert--success tich-mt-4">{{ session('status') }}</div>
    @endif

    <form method="GET" class="tich-mt-4" style="display:flex;flex-wrap:wrap;gap:0.75rem;align-items:end;">
        <div>
            <label class="tich-label" for="status">Status</label>
            <select id="status" name="status" class="tich-input">
                <option value="">All</option>
                @foreach (['pending_review','under_evaluation','approved','declined'] as $st)
                    <option value="{{ $st }}" @selected(($filters['status'] ?? '') === $st)>{{ str_replace('_', ' ', ucfirst($st)) }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="tich-label" for="applicant_type">Type</label>
            <select id="applicant_type" name="applicant_type" class="tich-input">
                <option value="">All</option>
                <option value="organisation" @selected(($filters['applicant_type'] ?? '') === 'organisation')>Organisation</option>
                <option value="individual" @selected(($filters['applicant_type'] ?? '') === 'individual')>Individual</option>
            </select>
        </div>
        <button type="submit" class="tich-btn tich-btn-secondary">Filter</button>
    </form>

    <div class="tich-table-panel tich-mt-6">
        <div class="tich-table-wrap">
            <table class="tich-admin-table">
                <thead>
                    <tr>
                        <th>Ref</th>
                        <th>Applicant</th>
                        <th>Type</th>
                        <th>Email</th>
                        <th>Area</th>
                        <th>Status</th>
                        <th>Received</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($requests as $item)
                        <tr>
                            <td>{{ $item->request_number }}</td>
                            <td>{{ $item->displayName() }}</td>
                            <td>{{ ucfirst($item->applicant_type) }}</td>
                            <td>{{ $item->email }}</td>
                            <td>{{ $item->research_area ?: '—' }}</td>
                            <td><x-status-badge :status="$item->status" /></td>
                            <td>{{ $item->created_at?->format('d M Y H:i') }}</td>
                            <td><a href="{{ route('research.partnerships.show', $item) }}" class="tich-link">Open</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="8">No partnership inquiries yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="tich-mt-4">{{ $requests->links() }}</div>
    </div>
@endsection
