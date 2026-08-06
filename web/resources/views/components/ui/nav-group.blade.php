@props([
    'label' => '',
    'icon' => null,
    'items' => [],
])

<div class="relative group">
    <button type="button" class="inline-flex items-center gap-1 px-3 py-2 text-sm font-medium text-gray-700 rounded-lg hover:bg-gray-50 hover:text-green-700 transition-colors">
        @if($icon)
            {!! $icon !!}
        @endif
        {{ $label }}
        <svg class="w-4 h-4 transition-transform group-hover:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
        </svg>
    </button>
    <div class="absolute left-0 top-full mt-1 w-56 bg-white border border-gray-100 rounded-xl shadow-lg opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all duration-200 z-50">
        <div class="py-1">
            @foreach($items as $item)
                <a href="{{ $item['href'] ?? '#' }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-green-50 hover:text-green-700 transition-colors">
                    {{ $item['label'] }}
                </a>
            @endforeach
        </div>
    </div>
</div>
