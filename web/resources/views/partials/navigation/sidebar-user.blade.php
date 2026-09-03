@php
    $sidebarUser = \App\Support\SidebarUserProfile::fromUser(auth()->user());
@endphp

@if ($sidebarUser)
    <div class="tich-admin-sidebar__user">
        <div class="tich-admin-sidebar__user-photo" aria-hidden="true">
            @if ($sidebarUser->photoUrl)
                <img src="{{ $sidebarUser->photoUrl }}" alt="">
            @else
                <span class="tich-admin-sidebar__user-initials">{{ $sidebarUser->initials }}</span>
            @endif
        </div>
        <div class="tich-admin-sidebar__user-meta">
            <p class="tich-admin-sidebar__user-name">{{ $sidebarUser->name }}</p>
            <p class="tich-admin-sidebar__user-email">{{ $sidebarUser->email }}</p>
            @if ($sidebarUser->roles !== [])
                <p class="tich-admin-sidebar__user-roles" title="{{ implode(' · ', $sidebarUser->roles) }}">
                    {{ implode(' · ', $sidebarUser->roles) }}
                </p>
            @endif
        </div>
    </div>
@endif
