<header class="tich-dept-header">
    <p class="tich-caption">{{ $overviewStats['category'] }}</p>
    <h1 class="tich-h1 tich-dept-header__title">{{ $department->dept_name }}</h1>
    <p class="tich-text tich-dept-header__meta">
        {{ $department->dept_code }}
        @if ($department->group)
            · {{ $department->group->group_name }}
        @endif
        @if ($department->campus)
            · {{ $department->campus->campus_name }}
        @endif
    </p>
</header>

<div class="tich-grid tich-grid--3 tich-dept-stats">
    <article class="tich-card tich-stat">
        <p class="tich-caption">Tools available</p>
        <p class="tich-stat__value">{{ $overviewStats['tool_count'] }}</p>
    </article>
    <article class="tich-card tich-stat">
        <p class="tich-caption">Department code</p>
        <p class="tich-stat__value" style="font-size: 1.25rem;">{{ $department->dept_code }}</p>
    </article>
    <article class="tich-card tich-stat">
        <p class="tich-caption">Group</p>
        <p class="tich-stat__value" style="font-size: 1.125rem;">{{ $department->group?->group_code ?? '—' }}</p>
    </article>
</div>

<section class="tich-dept-panel tich-mt-8">
    <div class="tich-dept-panel__head">
        <h2 class="tich-h2 tich-dept-panel__title">Department tools</h2>
        <p class="tich-text">Open a module from the menu or use the cards below.</p>
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
