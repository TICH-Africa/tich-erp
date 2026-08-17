<aside class="tich-admin-sidebar">
    @include('partials.navigation.sidebar-user')
    @php
        $sidebarWorkingIntake = ! empty($program)
            ? app(\App\Services\WorkingIntakeService::class)->resolve($program, request())
            : null;
    @endphp
    <p class="tich-admin-sidebar__title">{{ $program->program_name }}</p>
    <p class="tich-caption">{{ $program->program_code }} · {{ $program->department?->dept_name }}</p>
    @if ($sidebarWorkingIntake)
        <p class="tich-caption" style="color: var(--tich-blue);">Working intake: {{ $sidebarWorkingIntake->intakeLabel() }}</p>
    @endif

    <nav class="tich-admin-sidebar__nav">
        @foreach ($curriculumSidebarNavigation as $item)
            @if ($item['type'] === 'heading')
                <p class="tich-admin-sidebar__section">{{ $item['label'] }}</p>
            @else
                @php
                    $isActive = false;

                    if (($item['route'] ?? '') === 'departments.academics.programs.curriculum') {
                        $isActive = request()->routeIs('departments.academics.programs.curriculum')
                            && (int) request()->route('program')?->id === (int) ($item['params']['program'] ?? 0)
                            && request()->query('section', 'structure') === ($item['section'] ?? 'structure');
                    } elseif (($item['route'] ?? '') === 'departments.academics.programs.index') {
                        $isActive = request()->routeIs('departments.academics.programs.index');
                    } elseif (($item['route'] ?? '') === 'departments.show') {
                        $isActive = request()->routeIs('departments.show')
                            && (int) request()->route('department')?->id === (int) ($item['target_id'] ?? 0);
                    } elseif (! empty($item['route'])) {
                        $routeBase = preg_replace('/\.(index|dashboard)$/', '', $item['route']) ?? $item['route'];
                        $isActive = request()->routeIs($item['route']) || request()->routeIs($routeBase.'.*');
                    }

                    $icon = $item['icon'] ?? \App\Support\SidebarIcon::forRoute($item['route'] ?? null);
                @endphp

                @include('partials.navigation.sidebar-link', [
                    'href' => route($item['route'], $item['params'] ?? []),
                    'label' => $item['label'],
                    'icon' => $icon,
                    'active' => $isActive,
                ])
            @endif
        @endforeach
    </nav>
</aside>
