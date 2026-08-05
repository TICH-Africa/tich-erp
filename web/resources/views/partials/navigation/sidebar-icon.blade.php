@php
    $name = $name ?? 'circle';
@endphp
<span class="tich-icon tich-admin-sidebar__icon-svg" aria-hidden="true">
@switch($name)
    @case('dashboard')
        <svg width="18" height="18" viewBox="0 0 24 24"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/></svg>
        @break
    @case('home')
        <svg width="18" height="18" viewBox="0 0 24 24"><path d="M3 10.5 12 3l9 7.5"/><path d="M5 9.5V20a1 1 0 0 0 1 1h4v-6h4v6h4a1 1 0 0 0 1-1V9.5"/></svg>
        @break
    @case('users')
        <svg width="18" height="18" viewBox="0 0 24 24"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
        @break
    @case('user')
        <svg width="18" height="18" viewBox="0 0 24 24"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
        @break
    @case('user-plus')
        <svg width="18" height="18" viewBox="0 0 24 24"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><line x1="19" y1="8" x2="19" y2="14"/><line x1="22" y1="11" x2="16" y2="11"/></svg>
        @break
    @case('user-search')
        <svg width="18" height="18" viewBox="0 0 24 24"><circle cx="10" cy="8" r="4"/><path d="M10.3 14a7 7 0 1 0 0 0"/><path d="m21 21-4.3-4.3"/></svg>
        @break
    @case('users-cog')
        <svg width="18" height="18" viewBox="0 0 24 24"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><circle cx="19" cy="11" r="2"/><path d="M19 8v1"/><path d="M19 13v1"/><path d="m21.7 9.7-.7.7"/><path d="m16.7 14.7-.7.7"/><path d="m16 11h1"/><path d="m21 11h1"/><path d="m16.7 9.7.7.7"/><path d="m21.7 14.7-.7.7"/></svg>
        @break
    @case('file-text')
        <svg width="18" height="18" viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8Z"/><path d="M14 2v6h6"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>
        @break
    @case('briefcase')
        <svg width="18" height="18" viewBox="0 0 24 24"><rect x="2" y="7" width="20" height="14" rx="2"/><path d="M16 7V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v2"/><line x1="12" y1="12" x2="12" y2="16"/><line x1="10" y1="14" x2="14" y2="14"/></svg>
        @break
    @case('calendar-off')
        <svg width="18" height="18" viewBox="0 0 24 24"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/><line x1="4" y1="4" x2="20" y2="20"/></svg>
        @break
    @case('calendar')
        <svg width="18" height="18" viewBox="0 0 24 24"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
        @break
    @case('wallet')
        <svg width="18" height="18" viewBox="0 0 24 24"><path d="M19 7V5a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-2"/><path d="M3 7h18"/><path d="M16 14h.01"/></svg>
        @break
    @case('shield')
        <svg width="18" height="18" viewBox="0 0 24 24"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10"/></svg>
        @break
    @case('shield-check')
        <svg width="18" height="18" viewBox="0 0 24 24"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10"/><path d="m9 12 2 2 4-4"/></svg>
        @break
    @case('folder')
        <svg width="18" height="18" viewBox="0 0 24 24"><path d="M4 20h16a2 2 0 0 0 2-2V8a2 2 0 0 0-2-2h-7.93a2 2 0 0 1-1.66-.9l-.82-1.2A2 2 0 0 0 7.93 3H4a2 2 0 0 0-2 2v13a2 2 0 0 0 2 2Z"/></svg>
        @break
    @case('files')
        <svg width="18" height="18" viewBox="0 0 24 24"><path d="M15 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7Z"/><path d="M14 2v4a2 2 0 0 0 2 2h4"/><path d="M10 9H8"/><path d="M16 13H8"/><path d="M16 17H8"/></svg>
        @break
    @case('log-out')
        <svg width="18" height="18" viewBox="0 0 24 24"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
        @break
    @case('presentation')
        <svg width="18" height="18" viewBox="0 0 24 24"><path d="M2 3h20"/><path d="M21 3v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V3"/><path d="M12 16v6"/><path d="m8 22 4-2 4 2"/></svg>
        @break
    @case('arrow-left')
        <svg width="18" height="18" viewBox="0 0 24 24"><line x1="19" y1="12" x2="5" y2="12"/><polyline points="12 19 5 12 12 5"/></svg>
        @break
    @case('chevron-down')
        <svg width="16" height="16" viewBox="0 0 24 24"><polyline points="6 9 12 15 18 9"/></svg>
        @break
    @case('book-open')
        <svg width="18" height="18" viewBox="0 0 24 24"><path d="M12 7v14"/><path d="M3 18a1 1 0 0 1-1-1V4a1 1 0 0 1 1-1h5a4 4 0 0 1 4 4 4 4 0 0 1 4-4h5a1 1 0 0 1 1 1v13a1 1 0 0 1-1 1h-6a3 3 0 0 0-3 3 3 3 0 0 0-3-3Z"/></svg>
        @break
    @case('clipboard-check')
        <svg width="18" height="18" viewBox="0 0 24 24"><rect x="8" y="2" width="8" height="4" rx="1"/><path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"/><path d="m9 14 2 2 4-4"/></svg>
        @break
    @case('clipboard-list')
        <svg width="18" height="18" viewBox="0 0 24 24"><rect x="8" y="2" width="8" height="4" rx="1"/><path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"/><line x1="12" y1="11" x2="12" y2="17"/><line x1="9" y1="14" x2="15" y2="14"/></svg>
        @break
    @case('award')
        <svg width="18" height="18" viewBox="0 0 24 24"><circle cx="12" cy="8" r="6"/><path d="M8.21 13.89 7 22l5-3 5 3-1.21-8.11"/></svg>
        @break
    @case('notebook')
        <svg width="18" height="18" viewBox="0 0 24 24"><path d="M2 6h4"/><path d="M2 10h4"/><path d="M2 14h4"/><path d="M2 18h4"/><rect x="6" y="2" width="16" height="20" rx="2"/><path d="M10 6h8"/><path d="M10 10h8"/><path d="M10 14h8"/></svg>
        @break
    @case('layers')
        <svg width="18" height="18" viewBox="0 0 24 24"><polygon points="12 2 2 7 12 12 22 7 12 2"/><polyline points="2 17 12 22 22 17"/><polyline points="2 12 12 17 22 12"/></svg>
        @break
    @case('clock')
        <svg width="18" height="18" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
        @break
    @case('building')
        <svg width="18" height="18" viewBox="0 0 24 24"><rect x="4" y="2" width="16" height="20" rx="2"/><path d="M9 22v-4h6v4"/><path d="M8 6h.01"/><path d="M16 6h.01"/><path d="M12 6h.01"/><path d="M12 10h.01"/><path d="M12 14h.01"/><path d="M16 10h.01"/><path d="M16 14h.01"/><path d="M8 10h.01"/><path d="M8 14h.01"/></svg>
        @break
    @case('building-2')
        <svg width="18" height="18" viewBox="0 0 24 24"><path d="M6 22V4a2 2 0 0 1 2-2h8a2 2 0 0 1 2 2v18Z"/><path d="M6 12H4a2 2 0 0 0-2 2v6a2 2 0 0 0 2 2h2"/><path d="M18 9h2a2 2 0 0 1 2 2v9a2 2 0 0 1-2 2h-2"/><path d="M10 6h4"/><path d="M10 10h4"/><path d="M10 14h4"/><path d="M10 18h4"/></svg>
        @break
    @case('graduation-cap')
        <svg width="18" height="18" viewBox="0 0 24 24"><path d="M22 10v6M2 10l10-5 10 5-10 5z"/><path d="M6 12v5c0 1.7 2.7 3 6 3s6-1.3 6-3v-5"/></svg>
        @break
    @case('layout-grid')
        <svg width="18" height="18" viewBox="0 0 24 24"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>
        @break
    @case('library')
        <svg width="18" height="18" viewBox="0 0 24 24"><path d="m16 6 4 14"/><path d="M12 6v14"/><path d="M8 8v12"/><path d="M4 4v16"/></svg>
        @break
    @case('search')
        <svg width="18" height="18" viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
        @break
    @case('bar-chart')
        <svg width="18" height="18" viewBox="0 0 24 24"><line x1="12" y1="20" x2="12" y2="10"/><line x1="18" y1="20" x2="18" y2="4"/><line x1="6" y1="20" x2="6" y2="16"/></svg>
        @break
    @case('scroll')
        <svg width="18" height="18" viewBox="0 0 24 24"><path d="M8 21h12a2 2 0 0 0 2-2v-2H10v2a2 2 0 1 1-4 0V5a2 2 0 1 0-4 0v3h4"/><path d="M19 17V5a2 2 0 0 0-2-2H4"/></svg>
        @break
    @case('badge-check')
        <svg width="18" height="18" viewBox="0 0 24 24"><path d="M3.85 8.62a4 4 0 0 1 .78-4.35 4 4 0 0 1 5.28-.53 4 4 0 0 1 4.12.53 4 4 0 0 1 .78 4.35 4 4 0 0 1 .53 5.28 4 4 0 0 1-4.35.78 4 4 0 0 1-5.28.53 4 4 0 0 1-4.12-.53 4 4 0 0 1-.78-4.35 4 4 0 0 1-.53-5.28 4 4 0 0 1 4.35-.78Z"/><path d="m9 12 2 2 4-4"/></svg>
        @break
    @case('lock')
        <svg width="18" height="18" viewBox="0 0 24 24"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
        @break
    @default
        <svg width="18" height="18" viewBox="0 0 24 24"><circle cx="12" cy="12" r="4"/></svg>
@endswitch
</span>
