<aside class="tich-admin-sidebar">
    <p class="tich-admin-sidebar__title">{{ $department->dept_name }}</p>
    <p class="tich-caption" style="margin: -0.5rem 0 1rem;">{{ $categoryLabel($department) }} · {{ $department->dept_code }} · /departments/{{ $department->getRouteKey() }}</p>

    <nav class="tich-admin-sidebar__nav">
        @foreach ($sidebarNavigation as $item)
            @if ($item['type'] === 'heading')
                <p class="tich-admin-sidebar__section">{{ $item['label'] }}</p>
            @else
                @php
                    $isActive = false;

                    if (($item['route'] ?? '') === 'departments.show') {
                        $currentSection = request()->query('section', 'overview');
                        $itemSection = $item['section'] ?? 'overview';
                        $targetId = $item['target_id'] ?? ($item['params']['department'] ?? null);
                        $isActive = request()->routeIs('departments.show')
                            && (int) request()->route('department')?->id === (int) $targetId
                            && $currentSection === $itemSection;
                    } elseif (($item['route'] ?? '') === 'dashboard') {
                        $isActive = request()->routeIs('dashboard');
                    } elseif (! empty($item['route'])) {
                        $routeBase = preg_replace('/\.(index|dashboard)$/', '', $item['route']) ?? $item['route'];
                        $isActive = request()->routeIs($item['route']) || request()->routeIs($routeBase.'.*');
                    }
                @endphp

                @if (! empty($item['coming_soon']))
                    <span class="tich-admin-sidebar__disabled">{{ $item['label'] }} <small>(soon)</small></span>
                @else
                    <a href="{{ route($item['route'], $item['params'] ?? []) }}" @class(['is-active' => $isActive])>
                        <span>{{ $item['label'] }}</span>
                        @if (! empty($item['badge']))
                            <span class="tich-notification-badge" aria-label="{{ $item['badge'] }} pending applications">{{ $item['badge'] }}</span>
                        @endif
                    </a>
                @endif
            @endif
        @endforeach
    </nav>
</aside>
