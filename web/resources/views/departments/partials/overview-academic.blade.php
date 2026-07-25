<header class="tich-dept-header">
    <p class="tich-caption">{{ $overviewStats['category'] }}</p>
    <h1 class="tich-h1 tich-dept-header__title">{{ $department->dept_name }}</h1>
    <p class="tich-text tich-dept-header__meta">
        Academic programmes, student applications, and department workflows.
    </p>
</header>

<div class="tich-grid tich-grid--2 tich-dept-stats">
    <article class="tich-card tich-stat">
        <p class="tich-caption">Available tools</p>
        <p class="tich-stat__value">{{ $overviewStats['tool_count'] }}</p>
    </article>
    <article class="tich-card tich-stat">
        <p class="tich-caption">Campus</p>
        <p class="tich-stat__value" style="font-size: 1.125rem;">{{ $department->campus?->campus_name ?? '-' }}</p>
    </article>
</div>

<section class="tich-dept-panel tich-mt-8">
    <div class="tich-dept-panel__head">
        <h2 class="tich-h2 tich-dept-panel__title">Academic workspace</h2>
        <p class="tich-text">Review applications and manage records for programmes offered by this department.</p>
    </div>

    <div class="tich-grid tich-grid--2 tich-dept-cards">
        @foreach ($modules as $module)
            <article class="tich-card tich-dept-card tich-dept-card--accent">
                <h3 class="tich-h3">{{ $module['label'] }}</h3>
                <p class="tich-text tich-mt-2">{{ $module['description'] }}</p>
                @if (! empty($module['coming_soon']))
                    <p class="tich-caption tich-mt-4">Coming soon</p>
                @else
                    <a href="{{ route($module['route'], $module['params'] ?? []) }}" class="tich-btn tich-btn-primary tich-mt-4">Open</a>
                @endif
            </article>
        @endforeach
    </div>
</section>
