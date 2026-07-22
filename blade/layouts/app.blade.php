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
    <nav class="sticky top-0 z-50 bg-white border-b border-gray-100 shadow-sm">
        <div class="max-w-7xl mx-auto px-6 flex items-center justify-between h-16">
            <a href="{{ route('home') }}" class="flex items-center gap-3">
                <img src="{{ asset('images/logo.png') }}" alt="TICH Logo" class="h-10 w-10 object-contain">
                <div>
                    <p class="text-sm font-extrabold leading-tight text-green-800">TICH</p>
                    <p class="text-xs text-gray-500 leading-tight">International College of Hospitality</p>
                </div>
            </a>

            <div class="hidden md:flex items-center gap-7">
                @foreach(['About' => '#about', 'Programs' => '#programs', 'Admissions' => '#admissions', 'News' => '#news', 'Contact' => '#contact'] as $label => $href)
                    <a href="{{ $href }}" class="text-sm text-gray-600 hover:text-green-700 font-medium transition-colors">{{ $label }}</a>
                @endforeach
            </div>

            <div class="flex items-center gap-3">
                <a href="{{ route('login') }}" class="text-sm text-green-700 font-semibold hover:underline">Staff Login</a>
                <a href="{{ route('sacco.login') }}" class="text-sm text-amber-700 font-semibold hover:underline">SACCO</a>
                <a href="{{ route('procurement.login') }}" class="text-sm text-blue-700 font-semibold hover:underline">Procurement</a>
                <a href="{{ route('apply') }}" class="bg-green-700 text-white text-sm font-semibold px-4 py-2 rounded-lg hover:bg-green-800 transition-colors">Apply Now</a>
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
