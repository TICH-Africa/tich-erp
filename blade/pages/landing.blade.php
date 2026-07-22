{{-- blade/pages/landing.blade.php
    Route: GET /  →  HomeController@index
    Variables injected: $programs (collection), $news (collection), $stats (array)
--}}
@extends('layouts.app')

@section('title', 'TICH – The International College of Hospitality')

@section('content')

{{-- ── HERO ── --}}
<section class="relative overflow-hidden bg-gradient-to-br from-green-900 via-green-800 to-teal-900 text-white">
    <div class="absolute inset-0 opacity-10" style="background-image:radial-gradient(circle at 2px 2px,rgba(255,255,255,.4) 1px,transparent 0);background-size:32px 32px"></div>
    <div class="max-w-7xl mx-auto px-6 py-24 md:py-32 grid md:grid-cols-2 gap-12 items-center">
        <div>
            <span class="inline-block bg-green-500/20 text-green-300 text-xs font-semibold px-3 py-1 rounded-full mb-4 border border-green-500/30">
                Est. 2004 · Nairobi, Kenya
            </span>
            <h1 class="text-4xl md:text-5xl lg:text-6xl font-extrabold leading-tight mb-6">
                Shaping the Future of <span class="text-green-300">Hospitality</span> Excellence
            </h1>
            <p class="text-lg text-green-100 leading-relaxed mb-8 max-w-lg">
                    Kenya's premier institution for hospitality, tourism, and culinary education. Producing world-class professionals since 2004.
            </p>
            <div class="flex flex-wrap gap-3">
                <a href="{{ route('apply') }}" class="bg-white text-green-800 font-bold px-6 py-3 rounded-lg hover:bg-green-50 transition-colors">
                    Apply for September {{ date('Y') }}
                </a>
                <a href="#programs" class="border border-white/40 text-white px-6 py-3 rounded-lg hover:bg-white/10 transition-colors flex items-center gap-2">
                    Explore Programs →
                </a>
            </div>
            <div class="mt-10 flex gap-8">
                @foreach($stats ?? [['1,640+','Students Enrolled'],['9','Academic Programs'],['200+','Industry Partners'],['21','Years of Excellence']] as [$val,$label])
                    <div>
                        <p class="text-2xl font-extrabold text-white">{{ $val }}</p>
                        <p class="text-xs text-green-300 mt-0.5">{{ $label }}</p>
                    </div>
                @endforeach
            </div>
        </div>

        <div class="hidden md:block">
            <div class="bg-white/10 backdrop-blur-sm border border-white/20 rounded-2xl p-8">
                <div class="flex items-center gap-3 mb-6">
                    <img src="{{ asset('images/logo.png') }}" alt="TICH Logo" class="h-14 w-14 object-contain">
                    <div>
                        <p class="font-bold text-lg">The International College</p>
                        <p class="text-green-300 text-sm">of Hospitality (TICH)</p>
                    </div>
                </div>
                @foreach(['Industry-Linked Curriculum','Professional Kitchen Labs','International Exchange Programs','Career Placement Support','Scholarship Opportunities'] as $f)
                    <div class="flex items-center gap-2 text-sm text-green-100 mb-2">
                        <div class="w-5 h-5 bg-green-500 rounded-full flex items-center justify-center flex-shrink-0">
                            <svg class="w-3 h-3 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                        </div>
                        {{ $f }}
                    </div>
                @endforeach
                <div class="mt-6 pt-6 border-t border-white/20 grid grid-cols-3 gap-4 text-center">
                    <div><p class="text-xl font-bold">92%</p><p class="text-xs text-green-300">Graduate Employment</p></div>
                    <div><p class="text-xl font-bold">4.6★</p><p class="text-xs text-green-300">Student Rating</p></div>
                    <div><p class="text-xl font-bold">EAC</p><p class="text-xs text-green-300">Accredited</p></div>
                </div>
            </div>
        </div>
    </div>
</section>

{{-- ── ANNOUNCEMENT TICKER ── --}}
<div class="bg-green-700 text-white text-sm py-2 overflow-hidden">
    <div class="flex items-center gap-4 px-6">
        <span class="bg-white text-green-700 font-bold text-xs px-2 py-0.5 rounded flex-shrink-0">NEWS</span>
        <span class="text-green-100">Applications for {{ date('Y') }} intake are NOW OPEN &nbsp;·&nbsp; Scholarship applications close July 31 &nbsp;·&nbsp; Annual Culinary Showcase – August 15 at TICH Main Campus</span>
    </div>
</div>

{{-- ── PROGRAMS ── --}}
<section id="programs" class="py-20 bg-gray-50">
    <div class="max-w-7xl mx-auto px-6">
        <div class="text-center mb-12">
            <span class="text-green-600 font-semibold text-sm uppercase tracking-wider">Academic Offerings</span>
            <h2 class="text-3xl md:text-4xl font-extrabold mt-2 text-gray-900">Our Programs</h2>
            <p class="text-gray-500 mt-3 max-w-xl mx-auto">From foundational certificates to postgraduate research, TICH offers pathways for every career stage.</p>
        </div>
        <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-5">
            @foreach($programs ?? [] as $program)
                <div class="bg-white border border-gray-100 rounded-xl p-5 hover:shadow-md hover:border-green-200 transition-all group">
                    <div class="flex items-start justify-between mb-3">
                        <div class="w-10 h-10 bg-green-100 rounded-lg flex items-center justify-center text-green-700">
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/></svg>
                        </div>
                        <span class="text-xs bg-green-50 text-green-700 px-2 py-0.5 rounded-full font-medium">{{ $program->duration }}</span>
                    </div>
                    <h3 class="font-bold text-gray-900 mb-1 group-hover:text-green-700 transition-colors">{{ $program->name }}</h3>
                    <p class="text-xs text-gray-500 mb-3">{{ $program->faculty }}</p>
                    <div class="flex items-center justify-between text-sm">
                        <span class="text-gray-500">{{ $program->enrolled }} enrolled</span>
                        <span class="font-semibold text-green-700">KES {{ number_format($program->annual_fee) }}/yr</span>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</section>

{{-- ── NEWS ── --}}
<section id="news" class="py-20 bg-white">
    <div class="max-w-7xl mx-auto px-6">
        <div class="flex items-end justify-between mb-10">
            <div>
                <span class="text-green-600 font-semibold text-sm uppercase tracking-wider">Latest Updates</span>
                <h2 class="text-3xl font-extrabold mt-1 text-gray-900">News & Announcements</h2>
            </div>
            <a href="{{ route('news.index') }}" class="text-sm text-green-700 font-semibold hover:underline">View All →</a>
        </div>
        <div class="grid md:grid-cols-2 lg:grid-cols-4 gap-5">
            @foreach($news ?? [] as $item)
                <article class="border border-gray-100 rounded-xl overflow-hidden hover:shadow-md transition-shadow group">
                    <div class="bg-green-50 h-36 flex items-center justify-center">
                        @if($item->image)
                            <img src="{{ Storage::url($item->image) }}" alt="{{ $item->title }}" class="w-full h-full object-cover">
                        @else
                            <img src="{{ asset('images/logo.png') }}" alt="TICH" class="h-16 w-16 object-contain opacity-40">
                        @endif
                    </div>
                    <div class="p-4">
                        <div class="flex items-center gap-2 mb-2">
                            <span class="text-xs bg-green-100 text-green-700 px-2 py-0.5 rounded font-medium">{{ $item->category }}</span>
                            <span class="text-xs text-gray-400">{{ $item->published_at->format('d M Y') }}</span>
                        </div>
                        <h3 class="font-bold text-sm text-gray-900 mb-2 line-clamp-2 group-hover:text-green-700 transition-colors">{{ $item->title }}</h3>
                        <p class="text-xs text-gray-500 leading-relaxed line-clamp-3">{{ $item->excerpt }}</p>
                    </div>
                </article>
            @endforeach
        </div>
    </div>
</section>

{{-- ── ADMISSIONS CTA ── --}}
<section id="admissions" class="py-20 bg-gradient-to-r from-green-700 to-teal-700 text-white">
    <div class="max-w-4xl mx-auto px-6 text-center">
        <h2 class="text-3xl md:text-4xl font-extrabold mb-4">Ready to Begin Your Journey?</h2>
        <p class="text-green-100 text-lg mb-8">Applications for the {{ date('Y') }} intake are open. Scholarships available for qualified applicants.</p>
        <div class="flex flex-wrap justify-center gap-4">
            <a href="{{ route('apply') }}" class="bg-white text-green-800 font-bold px-8 py-3 rounded-lg hover:bg-green-50 transition-colors">Apply Online Now</a>
            <a href="{{ route('prospectus.download') }}" class="border border-white/50 text-white px-8 py-3 rounded-lg hover:bg-white/10 transition-colors">Download Prospectus</a>
        </div>
    </div>
</section>

{{-- ── CONTACT ── --}}
<section id="contact" class="py-20 bg-gray-50">
    <div class="max-w-7xl mx-auto px-6 grid md:grid-cols-2 gap-12">
        <div>
            <span class="text-green-600 font-semibold text-sm uppercase tracking-wider">Get in Touch</span>
            <h2 class="text-3xl font-extrabold mt-2 mb-5 text-gray-900">Contact Us</h2>
            @foreach([
                    ['Address', 'Plot 14, Hospitality Road, Nairobi, Kenya'],
                ['Phone', '+255 22 111 2222 / +255 744 333 444'],
                ['Email', 'admissions@tich.ac.tz'],
                ['Website', 'www.tich.ac.tz'],
            ] as [$label, $value])
                <div class="flex gap-3 mb-4">
                    <div class="w-9 h-9 bg-green-100 rounded-lg flex items-center justify-center text-green-700 flex-shrink-0">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                    </div>
                    <div>
                        <p class="text-xs text-gray-400 font-medium">{{ $label }}</p>
                        <p class="text-sm text-gray-800">{{ $value }}</p>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="bg-white border border-gray-100 rounded-xl p-6 shadow-sm">
            <h3 class="font-bold text-gray-900 mb-5">Send us a Message</h3>
            <form method="POST" action="{{ route('contact.send') }}">
                @csrf
                @foreach(['Full Name' => 'name', 'Email Address' => 'email', 'Phone Number' => 'phone'] as $label => $field)
                    <div class="mb-4">
                        <label class="block text-xs font-medium text-gray-600 mb-1">{{ $label }}</label>
                        <input type="text" name="{{ $field }}" value="{{ old($field) }}"
                            class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-green-500 focus:ring-1 focus:ring-green-200 @error($field) border-red-300 @enderror">
                        @error($field)<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
                    </div>
                @endforeach
                <div class="mb-4">
                    <label class="block text-xs font-medium text-gray-600 mb-1">Message</label>
                    <textarea name="message" rows="3" class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-green-500 focus:ring-1 focus:ring-green-200 resize-none"></textarea>
                </div>
                <button type="submit" class="w-full bg-green-700 text-white font-semibold py-2.5 rounded-lg hover:bg-green-800 transition-colors text-sm">Send Message</button>
            </form>
        </div>
    </div>
</section>

@endsection
