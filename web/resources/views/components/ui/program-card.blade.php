@props(['program'])

@php
$color = $program['color'] ?? 'green';
$bgClass = 'bg-' . $color . '-100';
$textClass = 'text-' . $color . '-700';
@endphp

<article class="bg-white border border-gray-100 rounded-xl p-5 hover:shadow-md transition-shadow">
    <div class="flex items-start justify-between mb-3">
        <div class="{{ $bgClass }} rounded-lg flex items-center justify-center p-2">
            @if(!empty($program['image']))
                <img src="{{ $program['image'] }}" alt="{{ $program['name'] }}" class="w-8 h-8 object-contain" />
            @else
                <svg class="w-6 h-6 {{ $textClass }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
            @endif
        </div>
        <span class="text-xs {{ $bgClass }} {{ $textClass }} px-2 py-0.5 rounded-full font-medium">
            {{ $program['duration'] ?? '' }}
        </span>
    </div>
    
    <h3 class="text-lg font-bold text-gray-900 mb-1">{{ $program['name'] }}</h3>
    <p class="text-xs text-gray-500 mb-1">{{ $program['level'] ?? '' }}</p>
    <p class="text-xs text-gray-500 mb-3">{{ $program['department'] ?? '' }}</p>
    
    <div class="text-sm">
        <span class="font-semibold text-green-700">KES {{ number_format($program['fee'] ?? 0) }}</span>
        @if(!empty($program['feeNote']))
        <span class="text-gray-400"> ({{ $program['feeNote'] }})</span>
        @endif
    </div>
    
    <p class="text-xs text-gray-400 mt-2">{{ $program['qualification'] ?? '' }}</p>
    
    <div class="mt-4 flex gap-2">
        <button class="flex-1 flex items-center justify-center gap-1 border border-green-200 text-green-700 rounded-lg px-3 py-1.5 text-xs font-semibold hover:bg-green-50 transition-colors">
            <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.25 12h19.5M12 2v19.5" />
            </svg>
            View Details
        </button>
        <button class="flex-1 flex items-center justify-center gap-1 bg-green-700 text-white rounded-lg px-3 py-1.5 text-xs font-semibold hover:bg-green-800 transition-colors">
            <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M1 5v9m0 9V5m0 9h9" />
            </svg>
            Apply Now
        </button>
    </div>
</article>
