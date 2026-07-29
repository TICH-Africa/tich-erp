<aside class="tich-admin-sidebar">
    <p class="tich-admin-sidebar__title">{{ $staff->fullName() }}</p>
    <p class="tich-caption" style="margin: -0.5rem 0 1rem;">{{ $staff->job_title ?? 'Teaching staff' }} · {{ $staff->department?->dept_name }}</p>
    @if (! empty($portalData['teaching_context']['summary']))
        <p class="tich-caption" style="margin: -0.75rem 0 1rem; color: var(--tich-blue, #1669a6);">{{ $portalData['teaching_context']['summary'] }}</p>
    @endif

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
