@php
    $unitContent = $portalData['academics']['unit_content'] ?? collect();
    // A unit is read the way the lecturer numbered it: section by section, lesson 1 first.
    $byUnit = $unitContent
        ->groupBy('unit_id')
        ->map(fn ($items) => App\Models\UnitContent::arrange($items)
            ->groupBy(fn ($item) => $item->sectionLabel()));
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
    @php $openUnitModel = $openUnit->flatten()->first()->unit; @endphp
    <section class="tich-portal-panel tich-mt-6">
        <div class="tich-portal-panel__head">
            <div>
                <h2 class="tich-h3">{{ $openUnitModel ? $openUnitModel->unit_code.' - '.$openUnitModel->unit_name : 'Unit' }}</h2>
                <p class="tich-caption tich-mt-1">{{ $openUnit->sum(fn ($items) => $items->count()) }} lessons, from lesson 1.</p>
            </div>
            <a href="{{ route('portal.dashboard', ['section' => 'academics', 'tab' => 'content']) }}" class="tich-btn tich-btn-ghost">All units</a>
        </div>

        <div class="tich-mt-4">
            @include('partials.learning-lessons', [
                'sections' => $openUnit,
                'rowMode' => 'link',
                'openRoute' => 'portal.learning-content.read',
                'downloadRoute' => 'portal.learning-content.download',
            ])
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
            @foreach ($byUnit as $unitId => $sections)
                @php
                    $unitPosts = $sections->flatten();
                    $unit = $unitPosts->first()->unit;
                    $latest = $unitPosts->sortByDesc('id')->first();
                @endphp
                <a href="{{ route('portal.dashboard', ['section' => 'academics', 'tab' => 'content', 'unit' => $unitId]) }}"
                   class="tich-card" style="display:block; text-decoration:none;">
                    <h3 class="tich-h4">{{ $unit?->unit_code ?? 'Unit' }}</h3>
                    <p class="tich-text tich-mt-2">{{ $unit?->unit_name ?? 'Learning content' }}</p>
                    <p class="tich-caption tich-mt-2">
                        {{ $unitPosts->count() }} lessons in {{ $sections->count() }} {{ str('section')->plural($sections->count()) }}
                        · last {{ ($latest->published_at ?? $latest->created_at)?->format('d M Y') }}
                    </p>
                    <span class="tich-btn tich-btn-secondary tich-mt-4">Open</span>
                </a>
            @endforeach
        </div>
    </div>
@endif
