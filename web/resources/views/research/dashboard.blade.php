@extends('layouts.research')

@section('title', 'Research Dashboard')

@section('research-content')
<div class="tich-mod-dash">
    <header class="tich-mod-dash__hero">
        <div class="tich-mod-dash__hero-copy">
            <p class="tich-mod-dash__eyebrow">Knowledge &amp; discovery</p>
            <h1 class="tich-mod-dash__title">Research command center</h1>
            <p class="tich-mod-dash__lede">Post research activities, manage timelines and documents, and publish to the public Research portal.</p>
        </div>
    </header>

    @include('qa.partials.assigned-tasks-panel')

    <div class="tich-mod-dash__metrics">
        <article class="tich-mod-dash__metric">
            <p class="tich-mod-dash__metric-label">Activities</p>
            <p class="tich-mod-dash__metric-value">{{ $stats['total'] }}</p>
        </article>
        <article class="tich-mod-dash__metric">
            <p class="tich-mod-dash__metric-label">Ongoing</p>
            <p class="tich-mod-dash__metric-value">{{ $stats['ongoing'] }}</p>
        </article>
        <article class="tich-mod-dash__metric">
            <p class="tich-mod-dash__metric-label">Upcoming</p>
            <p class="tich-mod-dash__metric-value">{{ $stats['upcoming'] }}</p>
        </article>
        <article class="tich-mod-dash__metric">
            <p class="tich-mod-dash__metric-label">Published</p>
            <p class="tich-mod-dash__metric-value">{{ $stats['published'] }}</p>
        </article>
    </div>

    <article class="tich-mod-dash__panel">
        <div class="tich-mod-dash__panel-head">
            <div>
                <p class="tich-mod-dash__panel-eyebrow">Workspace</p>
                <h2 class="tich-mod-dash__panel-title">Research activities</h2>
                <p class="tich-mod-dash__panel-meta">Create activities with start dates, durations, CMS descriptions, and read-only public documents.</p>
            </div>
            <a href="{{ route('research.activities.create') }}" class="tich-btn tich-btn-primary">Post activity</a>
        </div>

        <div class="tich-table-wrap tich-mt-4">
            <table class="tich-admin-table">
                <thead>
                    <tr>
                        <th>Title</th>
                        <th>Status</th>
                        <th>Visibility</th>
                        <th>Expected end</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($recent as $item)
                        <tr>
                            <td>{{ $item->title }}</td>
                            <td><x-status-badge :status="$item->status" :label="$item->statusLabel()" /></td>
                            <td>{{ ucfirst($item->visibility) }}</td>
                            <td>{{ $item->end_date?->format('d M Y') ?? '—' }}</td>
                            <td><a href="{{ route('research.activities.show', $item) }}" class="tich-link">Open</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="5">No activities yet. <a href="{{ route('research.activities.create') }}" class="tich-link">Post one</a>.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <p class="tich-mt-4"><a href="{{ route('research.activities.index') }}" class="tich-link">View all activities</a></p>
    </article>
</div>
@endsection
