<header id="site-header" class="tich-header{{ request()->routeIs('home') ? ' tich-header--over-hero' : '' }}">
    <div class="tich-container tich-header__inner">
        @include('partials.brand-logo', ['variant' => request()->routeIs('home') ? 'light' : 'default'])

        <button type="button" class="tich-nav-toggle" aria-label="Open menu" data-nav-toggle>
            <span></span><span></span><span></span>
        </button>

        <nav class="tich-nav tich-nav--desktop" aria-label="Primary navigation">
            @foreach ($headerMenu as $item)
                @include('partials.navigation.menu-item', ['item' => $item])
            @endforeach

            @include('partials.theme-toggle')

            @auth
                <a href="{{ route('dashboard') }}" class="tich-nav__link">Dashboard</a>
                <form method="POST" action="{{ route('logout') }}" class="inline">
                    @csrf
                    <button type="submit" class="tich-btn tich-btn-ghost">Sign out</button>
                </form>
            @else
                <a href="{{ route('login') }}" class="tich-btn tich-btn-blue">Sign in</a>
                <a href="{{ route('register') }}" class="tich-btn tich-btn-primary">Apply / Register</a>
            @endauth
        </nav>
    </div>

    <div class="tich-nav-drawer" data-nav-drawer hidden>
        <nav class="tich-container tich-nav-drawer__inner" aria-label="Mobile navigation">
            @foreach ($headerMenu as $item)
                @include('partials.navigation.menu-item', ['item' => $item, 'mobile' => true])
            @endforeach
            <div class="tich-nav-drawer__actions">
                <div class="tich-nav-drawer__theme">
                    @include('partials.theme-toggle')
                    <span class="tich-caption">Appearance</span>
                </div>
                @auth
                    <a href="{{ route('dashboard') }}" class="tich-btn tich-btn-secondary tich-btn-block">Dashboard</a>
                @else
                    <a href="{{ route('login') }}" class="tich-btn tich-btn-blue tich-btn-block">Sign in</a>
                    <a href="{{ route('register') }}" class="tich-btn tich-btn-primary tich-btn-block">Apply / Register</a>
                @endauth
            </div>
        </nav>
    </div>
</header>
