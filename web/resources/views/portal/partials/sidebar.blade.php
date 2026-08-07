@php
    $studentBadgeKeys = app(\App\Services\Sidebar\StudentSidebarNotificationService::class);
@endphp

<aside class="tich-admin-sidebar" id="student-admin-sidebar">
    <p class="tich-admin-sidebar__title">Student portal</p>
    <p class="tich-caption">
        {{ $student->registration_number }}<br>
        {{ $biodata['academic']['program'] ?? '-' }}
    </p>

    <nav class="tich-admin-sidebar__nav">
        @php
            $currentSection = $section ?? request()->query('section', 'overview');
            $currentTab = $tab ?? request()->query('tab');
        @endphp

        @foreach ($sidebarNavigation as $item)
            @if ($item['type'] === 'heading')
                <p class="tich-admin-sidebar__section">{{ $item['label'] }}</p>
            @elseif ($item['type'] === 'group')
                @php
                    $groupSection = $item['section'] ?? null;
                    $groupActive = $groupSection && $currentSection === $groupSection;
                    $groupItems = collect($item['items'] ?? [])->map(function (array $child) use ($groupSection, $currentSection, $currentTab) {
                        $childTab = $child['tab'] ?? null;

                        return [
                            'href' => route('portal.dashboard', array_filter([
                                'section' => $groupSection,
                                'tab' => $childTab,
                            ])),
                            'label' => $child['label'],
                            'icon' => $child['icon'] ?? 'circle',
                            'active' => $groupSection && $currentSection === $groupSection && $childTab === ($currentTab ?: ($groupSection === 'academics' ? 'units' : 'lesson')),
                            'badgeKey' => $child['badgeKey'] ?? null,
                        ];
                    })->all();
                @endphp

                @include('partials.navigation.sidebar-group', [
                    'label' => $item['label'],
                    'icon' => $item['icon'] ?? \App\Support\SidebarIcon::forSection($groupSection),
                    'open' => $groupActive,
                    'active' => $groupActive,
                    'items' => $groupItems,
                ])
            @elseif (! empty($item['coming_soon']))
                <span class="tich-admin-sidebar__disabled">
                    <span class="tich-admin-sidebar__icon">
                        @include('partials.navigation.sidebar-icon', ['name' => \App\Support\SidebarIcon::forSection($item['section'] ?? null)])
                    </span>
                    <span>{{ $item['label'] }} <small>(soon)</small></span>
                </span>
            @elseif (! empty($item['section']))
                @include('partials.navigation.sidebar-link', [
                    'href' => route('portal.dashboard', ['section' => $item['section']]),
                    'label' => $item['label'],
                    'icon' => \App\Support\SidebarIcon::forSection($item['section']),
                    'active' => $currentSection === $item['section'],
                    'badgeKey' => $studentBadgeKeys->badgeKeyForSection($item['section']),
                ])
            @elseif (! empty($item['route']))
                @include('partials.navigation.sidebar-link', [
                    'href' => route($item['route'], $item['params'] ?? []),
                    'label' => $item['label'],
                    'icon' => \App\Support\SidebarIcon::forRoute($item['route']),
                ])
            @endif
        @endforeach
    </nav>
</aside>
