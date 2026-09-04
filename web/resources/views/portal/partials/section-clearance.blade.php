@php
    $clearanceItems = $clearanceItems ?? collect();
@endphp

<x-page-toolbar title="Clearance" meta="Department checklist for exams and graduation">
    <x-slot:actions>
        <form method="POST" action="{{ route('portal.clearance.ensure') }}">
            @csrf
            <button type="submit" class="tich-btn tich-btn-secondary">Refresh status</button>
        </form>
    </x-slot:actions>
</x-page-toolbar>

<div class="tich-grid tich-grid--2 tich-mt-8" style="gap:1rem;">
    @forelse ($clearanceItems as $item)
        <article class="tich-card">
            <p class="tich-caption">{{ $item->label }}</p>
            <p class="tich-h3 tich-mt-2">{{ ucfirst(str_replace('_', ' ', $item->status)) }}</p>
            @if ($item->notes)
                <p class="tich-caption tich-mt-2">{{ $item->notes }}</p>
            @endif
            @if ($item->cleared_at)
                <p class="tich-caption tich-mt-2">Cleared {{ $item->cleared_at->format('d M Y') }}</p>
            @endif
        </article>
    @empty
        <div style="grid-column:1 / -1;">
            @include('partials.states.empty', [
                'title' => 'Clearance checklist not ready',
                'description' => 'Click refresh to create your default clearance items.',
                'icon' => 'inbox',
            ])
        </div>
    @endforelse
</div>
