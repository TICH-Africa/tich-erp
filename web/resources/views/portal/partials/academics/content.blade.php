@php
    $unitContent = $portalData['academics']['unit_content'] ?? collect();
    // Posts reach the student newest first; a unit is read from the first lesson to the latest one.
    $byUnit = $unitContent->groupBy('unit_id')->map(fn ($items) => $items->sortBy('id'));
    $openUnitId = (int) request('unit');
    $openUnit = $byUnit->get($openUnitId);
@endphp

@if ($unitContent->isEmpty())
    @include('partials.states.empty', [
        'title' => 'No learning content yet',
        'description' => 'Your lecturer has not published any notes or materials for your units yet.',
        'icon' => 'inbox',
    ])
@elseif ($openUnit)
    @php $openUnitModel = $openUnit->first()->unit; @endphp
    <section class="tich-portal-panel tich-mt-6">
        <div class="tich-portal-panel__head">
            <div>
                <h2 class="tich-h3">{{ $openUnitModel ? $openUnitModel->unit_code.' - '.$openUnitModel->unit_name : 'Unit' }}</h2>
                <p class="tich-caption tich-mt-1">{{ $openUnit->count() }} posted, first lesson to the latest.</p>
            </div>
            <a href="{{ route('portal.dashboard', ['section' => 'academics', 'tab' => 'content']) }}" class="tich-btn tich-btn-ghost">All units</a>
        </div>

        <div class="tich-lesson-list tich-mt-4">
            @foreach ($openUnit as $item)
                @include('portal.partials.academics.content-item', ['item' => $item, 'number' => $loop->iteration])
            @endforeach
        </div>
    </section>
@else
    <div class="tich-portal-panel tich-mt-6">
        <div class="tich-portal-panel__head">
            <div>
                <h2 class="tich-h3">Learning content</h2>
                <p class="tich-caption tich-mt-1">Choose a unit to read everything its lecturer posted, from lesson 1.</p>
            </div>
        </div>

        <div class="tich-grid tich-grid--3 tich-mt-4">
            @foreach ($byUnit as $unitId => $items)
                @php
                    $unit = $items->first()->unit;
                    $latest = $items->sortByDesc('id')->first();
                @endphp
                <a href="{{ route('portal.dashboard', ['section' => 'academics', 'tab' => 'content', 'unit' => $unitId]) }}"
                   class="tich-card" style="display:block; text-decoration:none;">
                    <h3 class="tich-h4">{{ $unit?->unit_code ?? 'Unit' }}</h3>
                    <p class="tich-text tich-mt-2">{{ $unit?->unit_name ?? 'Learning content' }}</p>
                    <p class="tich-caption tich-mt-2">
                        {{ $items->count() }} posted · last {{ ($latest->published_at ?? $latest->created_at)?->format('d M Y') }}
                    </p>
                    <span class="tich-btn tich-btn-secondary tich-mt-4">Open</span>
                </a>
            @endforeach
        </div>
    </div>
@endif
