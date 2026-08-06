@php
    $slide = $carousel->first();
@endphp

<section class="relative overflow-hidden bg-gradient-to-br from-green-900 via-green-800 to-teal-900 text-white">
    <div class="absolute inset-0 opacity-10" style="background-image: radial-gradient(circle at 2px 2px, rgba(255,255,255,0.4) 1px, transparent 0); background-size: 32px 32px;"></div>
    <div class="absolute top-0 right-0 w-[600px] h-[600px] bg-green-500 opacity-10 rounded-full blur-3xl translate-x-1/3 -translate-y-1/3"></div>

    <div class="max-w-7xl mx-auto px-6 py-24 md:py-32 grid md:grid-cols-2 gap-12 items-center">
        <div>
            <span class="inline-block bg-green-500/20 text-green-300 text-xs font-semibold px-3 py-1 rounded-full mb-4 border border-green-500/30">
                Est. 2004 · Nairobi, Kenya
            </span>

            <h1 class="text-4xl md:text-5xl lg:text-6xl font-extrabold leading-tight mb-6" style="font-family: 'Plus Jakarta Sans', system-ui, sans-serif;">
                Shaping the Future of <span class="text-green-300">Community Health</span> Excellence
            </h1>

            <p class="text-lg text-green-100 leading-relaxed mb-8 max-w-lg">
                Kenya's premier institution for community health, development, and clinical training. Producing compassionate health professionals since 2004.
            </p>

            <div class="flex flex-wrap gap-3">
                <a href="{{ route('register') }}" class="inline-flex items-center justify-center bg-white text-green-800 font-bold px-6 py-3 rounded-lg hover:bg-green-50 transition-colors">
                    Apply for September 2026
                </a>
                <a href="{{ route('programs.index') }}" class="inline-flex items-center justify-center border border-white/40 text-white font-medium px-6 py-3 rounded-lg hover:bg-white/10 transition-colors">
                    Explore Programs
                </a>
            </div>

            <div class="mt-10 flex gap-8">
                @foreach([['1,640+', 'Students Enrolled'], ['9', 'Academic Programs'], ['200+', 'Industry Partners'], ['21', 'Years of Excellence']] as [$val, $label])
                <div>
                    <p class="text-2xl font-extrabold" style="font-family: 'Plus Jakarta Sans', system-ui, sans-serif;">{{ $val }}</p>
                    <p class="text-xs text-green-300 mt-0.5">{{ $label }}</p>
                </div>
                @endforeach
            </div>
        </div>

        <div class="hidden md:block">
            <div class="bg-white/10 backdrop-blur-sm border border-white/20 rounded-2xl p-8">
                <div class="flex items-center gap-3 mb-6">
                    <img src="{{ asset('images/logo.png') }}" alt="TICH Logo" class="h-14 w-14 object-contain" />
                    <div>
                        <p class="font-bold text-lg">Tropical Institute of</p>
                        <p class="text-green-300 text-sm">Community Health and Development (TICH)</p>
                    </div>
                </div>

                <div class="space-y-3">
                    @foreach(['Community-Linked Curriculum', 'Modern Clinical Labs', 'County Government Partnerships', 'Career Placement Support', 'Scholarship Opportunities'] as $feature)
                    <div class="flex items-center gap-2 text-sm text-green-100">
                        <div class="w-5 h-5 bg-green-500 rounded-full flex items-center justify-center flex-shrink-0">
                            <svg class="w-3 h-3 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" />
                            </svg>
                        </div>
                        {{ $feature }}
                    </div>
                    @endforeach
                </div>

                <div class="mt-6 pt-6 border-t border-white/20 grid grid-cols-3 gap-4 text-center">
                    <div>
                        <p class="text-xl font-bold">92%</p>
                        <p class="text-xs text-green-300">Graduate Employment</p>
                    </div>
                    <div>
                        <p class="text-xl font-bold">4.6★</p>
                        <p class="text-xs text-green-300">Student Rating</p>
                    </div>
                    <div>
                        <p class="text-xl font-bold">KRA</p>
                        <p class="text-xs text-green-300">Accredited</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
