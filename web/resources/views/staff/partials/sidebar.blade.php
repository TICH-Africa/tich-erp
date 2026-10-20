@php
    $staffBadgeKeys = app(\App\Services\Sidebar\StaffSidebarNotificationService::class);
@endphp

<aside class="tich-admin-sidebar" id="staff-admin-sidebar">
    @include('partials.navigation.sidebar-user')
    <p class="tich-admin-sidebar__title">Staff workspace</p>
    <p class="tich-caption">{{ $staff->job_title ?? 'Teaching staff' }} · {{ $staff->department?->dept_name }}</p>
    @if (! empty($portalData['teaching_context']['summary']))
        <p class="tich-caption tich-admin-sidebar__teaching-context">{{ $portalData['teaching_context']['summary'] }}</p>
    @endif

    <nav class="tich-admin-sidebar__nav">
        @foreach ($sidebarNavigation as $item)
            @if ($item['type'] === 'heading')
                <p class="tich-admin-sidebar__section">{{ $item['label'] }}</p>
            @elseif (($item['type'] ?? '') === 'route' && ! empty($item['route']) && \Illuminate\Support\Facades\Route::has($item['route']))
                @php
                    $routePattern = $item['activeRoute'] ?? (preg_replace('/\.(index|create)$/', '.*', $item['route']) ?? $item['route']);
                @endphp
                @include('partials.navigation.sidebar-link', [
                    'href' => route($item['route']),
                    'label' => $item['label'],
                    'icon' => $item['icon'] ?? 'circle',
                    'active' => request()->routeIs($routePattern),
                ])
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
</aside>
