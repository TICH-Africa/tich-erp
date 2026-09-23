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
        <article class="tich-mod-dash__metric">
            <p class="tich-mod-dash__metric-label">Partnership pending</p>
            <p class="tich-mod-dash__metric-value">{{ $partnershipPending }}</p>
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

    <article class="tich-mod-dash__panel tich-mt-6">
        <div class="tich-mod-dash__panel-head">
            <div>
                <p class="tich-mod-dash__panel-eyebrow">Public portal</p>
                <h2 class="tich-mod-dash__panel-title">Partnership inquiries</h2>
                <p class="tich-mod-dash__panel-meta">Incoming requests from the “Be a research partner” form on the public Research page.</p>
            </div>
            <a href="{{ route('research.partnerships.index') }}" class="tich-btn tich-btn-secondary">Open inbox</a>
        </div>

        <div class="tich-table-wrap tich-mt-4">
            <table class="tich-admin-table">
                <thead>
                    <tr>
                        <th>Ref</th>
                        <th>Applicant</th>
                        <th>Type</th>
                        <th>Status</th>
                        <th>Received</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($partnerships as $item)
                        <tr>
                            <td>{{ $item->request_number }}</td>
                            <td>{{ $item->displayName() }}</td>
                            <td>{{ ucfirst($item->applicant_type) }}</td>
                            <td><x-status-badge :status="$item->status" /></td>
                            <td>{{ $item->created_at?->format('d M Y') }}</td>
                            <td><a href="{{ route('research.partnerships.show', $item) }}" class="tich-link">Open</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="6">No partnership inquiries yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </article>
</div>
@endsection
