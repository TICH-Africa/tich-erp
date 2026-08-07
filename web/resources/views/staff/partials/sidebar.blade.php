@php
    $staffBadgeKeys = app(\App\Services\Sidebar\StaffSidebarNotificationService::class);
@endphp

<aside class="tich-admin-sidebar" id="staff-admin-sidebar">
    <p class="tich-admin-sidebar__title">{{ $staff->fullName() }}</p>
    <p class="tich-caption">{{ $staff->job_title ?? 'Teaching staff' }} · {{ $staff->department?->dept_name }}</p>
    @if (! empty($portalData['teaching_context']['summary']))
        <p class="tich-caption" style="color: var(--tich-blue, #1669a6);">{{ $portalData['teaching_context']['summary'] }}</p>
    @endif

    <nav class="tich-admin-sidebar__nav">
        @foreach ($sidebarNavigation as $item)
            @if ($item['type'] === 'heading')
                <p class="tich-admin-sidebar__section">{{ $item['label'] }}</p>
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
