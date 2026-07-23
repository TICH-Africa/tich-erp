{{-- blade/layouts/app.blade.php
    Public-facing layout for the TICH landing page.
    Requires: routes/web.php, public/images/logo.png
--}}
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>@yield('title', 'TICH – The International College of Hospitality')</title>
    <meta name="description" content="@yield('meta_description', 'Kenya's premier institution for hospitality, tourism, and culinary education.')">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('styles')
</head>
<body class="bg-white font-body text-gray-900 antialiased">

    {{-- NAVBAR --}}
    <nav class="sticky top-0 z-50 bg-white/80 backdrop-blur-md border-b border-gray-100">
        <div class="max-w-7xl mx-auto px-6 flex items-center justify-between h-16">
            <a href="{{ route('home') }}" class="flex items-center gap-3">
                <img src="{{ asset('images/logo.png') }}" alt="TICH Logo" class="h-10 w-10 object-contain">
                <div>
                    <p class="text-sm font-extrabold leading-tight text-gray-900">TICH</p>
                    <p class="text-[10px] text-gray-500 leading-tight">Tropical Institute of Community Health and Development</p>
                </div>
            </a>

            <div class="hidden lg:flex items-center gap-1">
                {{-- News & Events --}}
                <div class="relative group">
                    <button class="flex items-center gap-1 px-3 py-2 text-xs font-semibold text-gray-600 hover:text-green-700 rounded-lg hover:bg-green-50 transition-all">
                        News & Events
                        <svg class="w-4 h-4 transition-transform group-hover:rotate-180" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                    </button>
                    <div class="absolute top-full left-1/2 -translate-x-1/2 mt-2 w-52 bg-white border border-gray-100 rounded-xl z-50 overflow-hidden opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all">
                        <div class="p-1.5">
                            <a href="#conference" class="block px-3 py-2 text-xs font-medium text-gray-600 hover:text-green-700 hover:bg-green-50 rounded-lg transition-colors">Conference</a>
                            <a href="#events" class="block px-3 py-2 text-xs font-medium text-gray-600 hover:text-green-700 hover:bg-green-50 rounded-lg transition-colors">Events</a>
                            <a href="#gallery" class="block px-3 py-2 text-xs font-medium text-gray-600 hover:text-green-700 hover:bg-green-50 rounded-lg transition-colors">Gallery</a>
                            <a href="#blog" class="block px-3 py-2 text-xs font-medium text-gray-600 hover:text-green-700 hover:bg-green-50 rounded-lg transition-colors">Blog</a>
                        </div>
                    </div>
                </div>

                {{-- Admissions --}}
                <div class="relative group">
                    <button class="flex items-center gap-1 px-3 py-2 text-xs font-semibold text-gray-600 hover:text-green-700 rounded-lg hover:bg-green-50 transition-all">
                        Admissions
                        <svg class="w-4 h-4 transition-transform group-hover:rotate-180" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                    </button>
                    <div class="absolute top-full left-1/2 -translate-x-1/2 mt-2 w-52 bg-white border border-gray-100 rounded-xl z-50 overflow-hidden opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all">
                        <div class="p-1.5">
                            <a href="#hef" class="block px-3 py-2 text-xs font-medium text-gray-600 hover:text-green-700 hover:bg-green-50 rounded-lg transition-colors">HEF Application</a>
                            <a href="#financial-aid" class="block px-3 py-2 text-xs font-medium text-gray-600 hover:text-green-700 hover:bg-green-50 rounded-lg transition-colors">Financial Aid</a>
                            <a href="#tveta" class="block px-3 py-2 text-xs font-medium text-gray-600 hover:text-green-700 hover:bg-green-50 rounded-lg transition-colors">TVETA Application</a>
                            <a href="#kuccps" class="block px-3 py-2 text-xs font-medium text-gray-600 hover:text-green-700 hover:bg-green-50 rounded-lg transition-colors">KUCCPS Application</a>
                        </div>
                    </div>
                </div>

                {{-- About Us --}}
                <div class="relative group">
                    <button class="flex items-center gap-1 px-3 py-2 text-xs font-semibold text-gray-600 hover:text-green-700 rounded-lg hover:bg-green-50 transition-all">
                        About Us
                        <svg class="w-4 h-4 transition-transform group-hover:rotate-180" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                    </button>
                    <div class="absolute top-full left-1/2 -translate-x-1/2 mt-2 w-52 bg-white border border-gray-100 rounded-xl z-50 overflow-hidden opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all">
                        <div class="p-1.5">
                            <a href="#about" class="block px-3 py-2 text-xs font-medium text-gray-600 hover:text-green-700 hover:bg-green-50 rounded-lg transition-colors">About</a>
                            <a href="#mission" class="block px-3 py-2 text-xs font-medium text-gray-600 hover:text-green-700 hover:bg-green-50 rounded-lg transition-colors">Mission & Vision</a>
                            <a href="#history" class="block px-3 py-2 text-xs font-medium text-gray-600 hover:text-green-700 hover:bg-green-50 rounded-lg transition-colors">History</a>
                        </div>
                    </div>
                </div>

                {{-- Careers --}}
                <div class="relative group">
                    <button class="flex items-center gap-1 px-3 py-2 text-xs font-semibold text-gray-600 hover:text-green-700 rounded-lg hover:bg-green-50 transition-all">
                        Careers
                        <svg class="w-4 h-4 transition-transform group-hover:rotate-180" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                    </button>
                    <div class="absolute top-full left-1/2 -translate-x-1/2 mt-2 w-52 bg-white border border-gray-100 rounded-xl z-50 overflow-hidden opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all">
                        <div class="p-1.5">
                            <a href="#talent-pool" class="block px-3 py-2 text-xs font-medium text-gray-600 hover:text-green-700 hover:bg-green-50 rounded-lg transition-colors">Talent Pool</a>
                            <a href="#careers" class="block px-3 py-2 text-xs font-medium text-gray-600 hover:text-green-700 hover:bg-green-50 rounded-lg transition-colors">Careers</a>
                        </div>
                    </div>
                </div>

                {{-- Standalone --}}
                <a href="#research" class="px-3 py-2 text-xs font-semibold text-gray-600 hover:text-green-700 rounded-lg hover:bg-green-50 transition-all">Research</a>
                <a href="#programs" class="px-3 py-2 text-xs font-semibold text-gray-600 hover:text-green-700 rounded-lg hover:bg-green-50 transition-all">Programs</a>
                <a href="#contact" class="px-3 py-2 text-xs font-semibold text-gray-600 hover:text-green-700 rounded-lg hover:bg-green-50 transition-all">Contact</a>
            </div>

            <div class="hidden md:flex items-center gap-2">
                <div class="relative group">
                    <button class="px-3 py-1.5 text-xs font-semibold text-gray-600 hover:text-green-700 rounded-lg hover:bg-gray-50 transition-all flex items-center gap-1">
                        Login
                        <svg class="w-4 h-4 transition-transform group-hover:rotate-180" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                    </button>
                    <div class="absolute top-full right-0 mt-2 w-40 bg-white border border-gray-100 rounded-xl shadow-lg z-50 overflow-hidden opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all">
                        <div class="p-1.5">
                            <a href="{{ route('login') }}" class="block px-3 py-2 text-xs font-medium text-gray-600 hover:text-green-700 hover:bg-green-50 rounded-lg transition-colors">Login as Staff</a>
                            <a href="{{ route('login') }}" class="block px-3 py-2 text-xs font-medium text-gray-600 hover:text-green-700 hover:bg-green-50 rounded-lg transition-colors">Login as Student</a>
                        </div>
                    </div>
                </div>
                <a href="{{ route('programs') }}" class="px-5 py-2 text-xs font-bold text-white bg-green-700 hover:bg-green-800 rounded-xl shadow-xl shadow-green-700/30 hover:shadow-2xl hover:shadow-green-700/40 hover:-translate-y-0.5 transition-all">Apply Now</a>
            </div>
        </div>
    </nav>

    {{-- PAGE CONTENT --}}
    <main>
        @yield('content')
    </main>

    {{-- FOOTER --}}
    <footer class="bg-green-950 text-white py-10">
        <div class="max-w-7xl mx-auto px-6">
            <div class="grid md:grid-cols-4 gap-8 mb-8">
                <div>
                    <div class="flex items-center gap-2 mb-3">
                        <img src="{{ asset('images/logo.png') }}" alt="TICH" class="h-10 w-10 object-contain">
                        <div>
                            <p class="font-bold text-sm">TICH</p>
                            <p class="text-green-400 text-xs">The International College of Hospitality</p>
                        </div>
                    </div>
                    <p class="text-xs text-green-300 leading-relaxed">Shaping hospitality professionals since 2004.</p>
                </div>
                @foreach([
                    'Programs' => ['Certificate Programs', 'Diploma Programs', 'Degree Programs', 'Postgraduate'],
                    'Admissions' => ['How to Apply', 'Entry Requirements', 'Scholarships', 'Fee Structure'],
                    'Campus Life' => ['Student Services', 'Accommodation', 'Sports & Clubs', 'Alumni'],
                ] as $title => $links)
                    <div>
                        <h4 class="font-bold text-sm mb-3 text-green-200">{{ $title }}</h4>
                        <ul class="space-y-2">
                            @foreach($links as $link)
                                <li><a href="#" class="text-xs text-green-400 hover:text-white transition-colors">{{ $link }}</a></li>
                            @endforeach
                        </ul>
                    </div>
                @endforeach
            </div>
            <div class="border-t border-green-800 pt-6 flex flex-col sm:flex-row items-center justify-between gap-2 text-xs text-green-500">
                <p>© {{ date('Y') }} The International College of Hospitality (TICH). All rights reserved.</p>
                <div class="flex gap-4">
                    <a href="{{ route('privacy') }}" class="hover:text-white transition-colors">Privacy Policy</a>
                    <a href="{{ route('terms') }}" class="hover:text-white transition-colors">Terms of Use</a>
                    <a href="{{ route('login') }}" class="hover:text-white transition-colors">Staff Portal</a>
                </div>
            </div>
        </div>
    </footer>

    @stack('scripts')
</body>
</html>
