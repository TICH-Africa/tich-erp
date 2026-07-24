<aside class="tich-admin-sidebar">
    <p class="tich-admin-sidebar__title">{{ $program->program_name }}</p>
    <p class="tich-caption" style="margin: -0.5rem 0 1rem;">{{ $program->program_code }} · {{ $program->department?->dept_name }}</p>

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
                    } elseif (($item['route'] ?? '') === 'dashboard') {
                        $isActive = request()->routeIs('dashboard');
                    }
                @endphp

                <a href="{{ route($item['route'], $item['params'] ?? []) }}" @class(['is-active' => $isActive])>{{ $item['label'] }}</a>
            @endif
        @endforeach
    </nav>
</aside>
