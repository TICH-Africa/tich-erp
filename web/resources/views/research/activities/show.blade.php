@extends('layouts.research')

@section('title', $activity->title)

@section('research-content')
    <x-page-toolbar title="{{ $activity->title }}" meta="{{ $activity->statusLabel() }} · {{ ucfirst($activity->visibility) }}">
        <x-slot:actions>
            @if ($activity->isPublished() && $activity->slug)
                <a href="{{ route('research.show', $activity->slug) }}" class="tich-btn tich-btn-secondary" target="_blank" rel="noopener">Public page</a>
            @endif
            <a href="{{ route('research.activities.edit', $activity) }}" class="tich-btn tich-btn-primary">Edit</a>
            <a href="{{ route('research.activities.index') }}" class="tich-btn tich-btn-ghost">All activities</a>
        </x-slot:actions>
    </x-page-toolbar>

    @if (session('status'))
        <div class="tich-alert tich-alert--success tich-mt-4">{{ session('status') }}</div>
    @endif

    <div class="tich-grid tich-grid--2 tich-mt-6 research-activity-admin" style="gap:1.5rem;align-items:start;">
        <section>
            @if ($activity->coverUrl())
                <img src="{{ $activity->coverUrl() }}" alt="" style="width:100%;max-height:16rem;object-fit:cover;border-radius:4px;">
            @endif
            <p class="tich-text tich-mt-4">{{ $activity->summary }}</p>
            @if ($activity->body)
                <div class="tich-prose-article tich-mt-6">{!! $activity->body !!}</div>
            @endif
        </section>
        <aside>
            <dl class="tich-text" style="display:grid;gap:0.75rem;">
                <div><dt class="tich-caption">Start</dt><dd>{{ $activity->start_date?->format('d M Y') ?? '—' }}</dd></div>
                <div><dt class="tich-caption">Duration</dt><dd>@if($activity->duration_value){{ $activity->duration_value }} {{ $activity->duration_unit }}@else — @endif</dd></div>
                <div><dt class="tich-caption">Expected completion</dt><dd>{{ $activity->end_date?->format('d M Y') ?? '—' }}</dd></div>
                <div><dt class="tich-caption">Status</dt><dd>{{ $activity->statusLabel() }}@if($activity->status_locked) <span class="tich-caption">(manual)</span>@endif</dd></div>
                <div><dt class="tich-caption">Visibility</dt><dd>{{ ucfirst($activity->visibility) }}</dd></div>
            </dl>

            <h2 class="tich-h3 tich-mt-6">Documents</h2>
            @forelse ($activity->documents as $doc)
                <p class="tich-mt-2"><span class="tich-text">{{ $doc->title }}</span></p>
            @empty
                <p class="tich-caption tich-mt-2">No documents attached.</p>
            @endforelse

            <form method="POST" action="{{ route('research.activities.destroy', $activity) }}" class="tich-mt-8" onsubmit="return confirm('Delete this research activity and its documents?');">
                @csrf
                @method('DELETE')
                <button type="submit" class="tich-btn tich-btn-ghost">Delete activity</button>
            </form>
        </aside>
    </div>
@endsection
