<aside class="tich-admin-sidebar">
    <p class="tich-admin-sidebar__title">Site settings</p>
    <p class="tich-caption">Public website content and branding</p>

    <nav class="tich-admin-sidebar__nav" aria-label="Site settings navigation">
        @foreach ([
            'general' => ['label' => 'Identity & logo', 'icon' => 'layers'],
            'hero' => ['label' => 'Hero slides', 'icon' => 'presentation'],
            'contact' => ['label' => 'Contact details', 'icon' => 'clipboard-list'],
            'social' => ['label' => 'Social links', 'icon' => 'files'],
        ] as $key => $item)
            @include('partials.navigation.sidebar-link', [
                'href' => route('site-settings.index', ['panel' => $key]),
                'label' => $item['label'],
                'icon' => $item['icon'],
                'active' => ($panel ?? 'general') === $key,
            ])
        @endforeach
    </nav>

    <div class="tich-admin-sidebar__footer">
        @include('partials.navigation.sidebar-link', [
            'href' => route('home'),
            'label' => 'View public site',
            'icon' => 'home',
            'muted' => true,
        ])
        @include('partials.navigation.sidebar-link', [
            'href' => route('dashboard'),
            'label' => 'Back to dashboard',
            'icon' => 'arrow-left',
            'muted' => true,
        ])
    </div>
</aside>
