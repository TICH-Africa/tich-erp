<aside class="tich-admin-sidebar">
    <p class="tich-admin-sidebar__title">{{ $staff->fullName() }}</p>
    <p class="tich-caption" style="margin: -0.5rem 0 1rem;">{{ $staff->job_title ?? 'Teaching staff' }} · {{ $staff->department?->dept_name }}</p>

    <nav class="tich-admin-sidebar__nav">
        @foreach ($sidebarNavigation as $item)
            @if ($item['type'] === 'heading')
                <p class="tich-admin-sidebar__section">{{ $item['label'] }}</p>
            @else
                <a href="{{ route('staff.dashboard', ['section' => $item['section']]) }}"
                   @class(['is-active' => $section === ($item['section'] ?? '')])>{{ $item['label'] }}</a>
            @endif
        @endforeach
    </nav>
</aside>
