@extends('layouts.research')

@section('title', 'Research activities')

@section('research-content')
    <x-page-toolbar title="Research activities" meta="Post, schedule, and publish institutional research">
        <x-slot:actions>
            <a href="{{ route('research.activities.create') }}" class="tich-btn tich-btn-primary">Post activity</a>
        </x-slot:actions>
    </x-page-toolbar>

    @if (session('status'))
        <div class="tich-alert tich-alert--success tich-mt-4">{{ session('status') }}</div>
    @endif

    <form method="GET" action="{{ route('research.activities.index') }}" class="tich-page-toolbar__filters-form tich-mt-4" style="display:flex;flex-wrap:wrap;gap:0.75rem;align-items:end;">
        <div>
            <label class="tich-label" for="q">Search</label>
            <input type="search" id="q" name="q" value="{{ $filters['q'] }}" class="tich-input" placeholder="Title or summary">
        </div>
        <div>
            <label class="tich-label" for="status">Lifecycle</label>
            <select id="status" name="status" class="tich-input">
                <option value="">All</option>
                @foreach (['upcoming','ongoing','completed','paused'] as $st)
                    <option value="{{ $st }}" @selected($filters['status'] === $st)>{{ ucfirst($st) }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="tich-label" for="visibility">Visibility</label>
            <select id="visibility" name="visibility" class="tich-input">
                <option value="">All</option>
                @foreach (['draft','published','archived'] as $vis)
                    <option value="{{ $vis }}" @selected($filters['visibility'] === $vis)>{{ ucfirst($vis) }}</option>
                @endforeach
            </select>
        </div>
        <button type="submit" class="tich-btn tich-btn-secondary">Filter</button>
    </form>

    <div class="tich-table-panel tich-mt-6">
        <div class="tich-table-wrap">
            <table class="tich-admin-table">
                <thead>
                    <tr>
                        <th>Title</th>
                        <th>Start</th>
                        <th>Expected end</th>
                        <th>Status</th>
                        <th>Visibility</th>
                        <th>Docs</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($activities as $item)
                        <tr>
                            <td>
                                <strong>{{ $item->title }}</strong>
                                @if ($item->is_featured)
                                    <span class="tich-caption"> · Featured</span>
                                @endif
                            </td>
                            <td>{{ $item->start_date?->format('d M Y') ?? '—' }}</td>
                            <td>{{ $item->end_date?->format('d M Y') ?? '—' }}</td>
                            <td><x-status-badge :status="$item->status" :label="$item->statusLabel()" /></td>
                            <td><x-status-badge :status="$item->visibility" /></td>
                            <td>{{ $item->documents_count }}</td>
                            <td><a href="{{ route('research.activities.show', $item) }}" class="tich-link">Open</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="7">No research activities yet. <a href="{{ route('research.activities.create') }}" class="tich-link">Post the first one</a>.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="tich-mt-4">{{ $activities->links() }}</div>
    </div>
@endsection
