<x-page-toolbar
    title="{{ $department->dept_name }}"
    :meta="$department->dept_code . ($department->group ? ' · ' . $department->group->group_name : '') . ($department->campus ? ' · ' . $department->campus->campus_name : '')"
/>

<div class="tich-grid tich-grid--3 tich-dept-stats">
    <article class="tich-card tich-stat">
        <p class="tich-caption">Sub-departments</p>
        <p class="tich-stat__value">{{ $overviewStats['child_count'] }}</p>
    </article>
    <article class="tich-card tich-stat">
        <p class="tich-caption">Available tools</p>
        <p class="tich-stat__value">{{ $overviewStats['tool_count'] }}</p>
    </article>
    <article class="tich-card tich-stat">
        <p class="tich-caption">Department type</p>
        <p class="tich-stat__value" style="font-size: 1.25rem;">Hub</p>
    </article>
</div>

@if ($modules !== [])
    <section class="tich-dept-panel tich-mt-8">
        <div class="tich-dept-panel__head">
            <h2 class="tich-h2 tich-dept-panel__title">Tools</h2>
            <p class="tich-text">Modules managed from this hub.</p>
        </div>

        <div class="tich-grid tich-grid--2 tich-dept-cards">
            @foreach ($modules as $module)
                <article class="tich-card tich-dept-card">
                    <h3 class="tich-h3">{{ $module['label'] }}</h3>
                    <p class="tich-text tich-mt-2">{{ $module['description'] }}</p>
                    @if (! empty($module['coming_soon']))
                        <p class="tich-caption tich-mt-4">Coming soon</p>
                    @else
                        <a href="{{ route($module['route'], $module['params'] ?? []) }}" class="tich-btn tich-btn-primary tich-mt-4">Open tool</a>
                    @endif
                </article>
            @endforeach
        </div>
    </section>
@endif
