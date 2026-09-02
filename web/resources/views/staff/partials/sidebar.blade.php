@php
    $staffBadgeKeys = app(\App\Services\Sidebar\StaffSidebarNotificationService::class);
@endphp

<aside class="tich-admin-sidebar" id="staff-admin-sidebar">
    @include('partials.navigation.sidebar-user')
    <p class="tich-admin-sidebar__title">Staff workspace</p>
    <p class="tich-caption">{{ $staff->job_title ?? 'Teaching staff' }} · {{ $staff->department?->dept_name }}</p>
    @if (! empty($portalData['teaching_context']['summary']))
        <p class="tich-caption" style="color: var(--tich-blue, #1669a6);">{{ $portalData['teaching_context']['summary'] }}</p>
    @endif

    <nav class="tich-admin-sidebar__nav">
        @foreach ($sidebarNavigation as $item)
            @if ($item['type'] === 'heading')
                <p class="tich-admin-sidebar__section">{{ $item['label'] }}</p>
            @elseif ($item['type'] === 'dropdown')
                @php
                    $isHodSubSection = in_array($section, ['hod-management', 'hod-lesson-plans', 'hod-unit-allocations', 'hod-attendance', 'hod-leave', 'hod-performance']);
                @endphp
                <div data-sidebar-group class="tich-sidebar-group">
                    <button type="button" data-sidebar-group-toggle class="tich-admin-sidebar__group-toggle" aria-expanded="{{ $isHodSubSection ? 'true' : 'false' }}">
                        @include('partials.navigation.sidebar-icon', ['name' => $item['icon'] ?? 'circle'])
                        <span class="tich-admin-sidebar__label">{{ $item['label'] }}</span>
                        <span class="tich-admin-sidebar__chevron">▼</span>
                    </button>
                    <div data-sidebar-group-panel {{ $isHodSubSection ? '' : 'hidden' }} class="tich-sidebar-group__panel">
                        @foreach ($item['children'] as $child)
                            @include('partials.navigation.sidebar-link', [
                                'href' => route('staff.dashboard', ['section' => $child['section']]),
                                'label' => $child['label'],
                                'icon' => $child['icon'] ?? \App\Support\SidebarIcon::forSection($child['section'] ?? null),
                                'active' => $section === ($child['section'] ?? ''),
                                'badgeKey' => $staffBadgeKeys->badgeKeyForSection($child['section'] ?? ''),
                                'sub' => true,
                            ])
                        @endforeach
                    </div>
                </div>
            @else
                @include('partials.navigation.sidebar-link', [
                    'href' => route('staff.dashboard', ['section' => $item['section']]),
                    'label' => $item['label'],
                    'icon' => $item['icon'] ?? \App\Support\SidebarIcon::forSection($item['section'] ?? null),
                    'active' => $section === ($item['section'] ?? ''),
                    'badgeKey' => $staffBadgeKeys->badgeKeyForSection($item['section'] ?? ''),
                ])
            @endif
        @endforeach
    </nav>

    <style>
        .tich-sidebar-group__panel { margin-left: 1rem; }
        .tich-admin-sidebar__group-toggle {
            width: 100%; text-align: left; padding: var(--space-sm) var(--space-md);
            background: var(--tich-white); border: none; cursor: pointer;
            display: flex; align-items: center; gap: 0.5rem; font-family: inherit;
            font-weight: 600; color: var(--tich-text, #1e293b);
        }
        .tich-admin-sidebar__group-toggle:hover { background: var(--tich-surface-muted, #f1f5f9); }
        .tich-admin-sidebar__chevron { margin-left: auto; transition: transform 0.2s ease; font-size: 0.75rem; }
        [data-sidebar-group].is-open .tich-admin-sidebar__chevron { transform: rotate(-180deg); }
        .tich-admin-sidebar__link--sub { padding-left: var(--space-xl); font-size: 0.875rem; }
    </style>
</aside>
