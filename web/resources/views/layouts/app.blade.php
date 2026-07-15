<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Home') — {{ config('app.name', 'TICH ERP') }}</title>
    @include('partials.head-assets')
</head>
<body class="tich-body">
    <header class="tich-header">
        <div class="tich-container tich-header__inner">
            @include('partials.brand-logo')

            <nav class="tich-nav">
                <a href="{{ route('home') }}" class="tich-nav__link hidden sm:inline">Home</a>

                @auth
                    <span class="tich-nav__user hidden sm:inline">{{ auth()->user()->username }}</span>
                    <form method="POST" action="{{ route('logout') }}" class="inline">
                        @csrf
                        <button type="submit" class="tich-btn tich-btn-ghost">Sign out</button>
                    </form>
                @else
                    <a href="{{ route('login') }}" class="tich-btn tich-btn-blue">Sign in</a>
                    <a href="{{ route('register') }}" class="tich-btn tich-btn-primary">Create account</a>
                @endauth
            </nav>
        </div>
    </header>

    <main>
        @include('partials.alerts')
        @yield('content')
    </main>

    <footer class="tich-footer">
        <div class="tich-container tich-footer__inner">
            <p>&copy; {{ date('Y') }} Tropical Institute of Community Health and Development in Africa</p>
            <p class="tich-caption">ERP platform under active development</p>
        </div>
    </footer>
</body>
</html>
