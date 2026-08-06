@props([
    'logoSrc' => asset('images/logo.png'),
    'tagline' => 'Community health education for Africa',
    'navGroups' => [],
    'standaloneLinks' => [],
])

<header class="sticky top-0 z-50 bg-white/95 backdrop-blur-md border-b border-gray-200 transition-all duration-300" id="site-header">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex items-center justify-between h-14">
            <a href="{{ route('home') }}" class="flex items-center gap-2 group">
                <img src="{{ $logoSrc }}" alt="TICH Logo" class="h-9 w-auto object-contain transition-transform duration-300 group-hover:scale-105">
                <div class="hidden sm:block">
                    <p class="text-base font-bold text-gray-900 leading-tight">TICH in Africa</p>
                    <p class="text-[11px] text-green-700 leading-tight">{{ $tagline }}</p>
                </div>
            </a>

            <nav class="hidden lg:flex items-center gap-0.5 whitespace-nowrap overflow-x-auto" aria-label="Primary navigation">
                @foreach($navGroups as $group)
                    <x-ui.nav-group :label="$group['label']" :items="$group['items']" />
                @endforeach

                @foreach($standaloneLinks as $link)
                    <x-ui.nav-link :href="$link['href']" :label="$link['label']" :active="request()->routeIs($link['route'] ?? '')" />
                @endforeach
            </nav>

            <div class="flex items-center gap-3">
                @auth
                    <x-ui.user-panel :user="Auth::user()" :logoutUrl="route('logout')" />
                @else
                    <a href="{{ route('login') }}" class="hidden sm:inline-flex items-center px-3 py-1.5 text-xs font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50 transition-colors">
                        Sign in
                    </a>
                    <a href="{{ route('register') }}" class="inline-flex items-center px-3 py-1.5 text-xs font-medium text-white bg-green-700 rounded-md hover:bg-green-800 transition-colors shadow-sm">
                        Apply / Register
                    </a>
                @endauth

                <button type="button" class="lg:hidden p-2 text-gray-700 hover:bg-gray-100 rounded-lg transition-colors" data-nav-toggle aria-label="Open menu">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                    </svg>
                </button>
            </div>
        </div>
    </div>

    <div class="lg:hidden hidden" data-nav-drawer>
        <div class="px-4 py-3 space-y-1 bg-white border-t border-gray-100">
            @foreach($navGroups as $group)
                @foreach($group['items'] as $item)
                    <a href="{{ $item['href'] ?? '#' }}" class="block px-3 py-2 text-sm font-medium text-gray-700 rounded-lg hover:bg-gray-50">
                        {{ $item['label'] }}
                    </a>
                @endforeach
            @endforeach
            @foreach($standaloneLinks as $link)
                <a href="{{ $link['href'] }}" class="block px-3 py-2 text-sm font-medium text-gray-700 rounded-lg hover:bg-gray-50">
                    {{ $link['label'] }}
                </a>
            @endforeach
        </div>
    </div>
</header>
