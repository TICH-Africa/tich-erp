@props([
    'type' => 'primary',
    'size' => 'md',
    'rounded' => true,
    'shadow' => true,
])

@php
$baseClasses = 'inline-flex items-center justify-center gap-2 font-semibold transition-all focus:ring-2 focus:ring-offset-2';

$sizeClasses = [
    'sm' => 'px-2.5 py-1.5 text-xs',
    'md' => 'px-4 py-2 text-sm',
    'lg' => 'px-5 py-2.5 text-sm',
];

$typeClasses = [
    'primary' => 'bg-green-700 text-white hover:bg-green-800 focus:ring-green-500',
    'secondary' => 'bg-gray-100 text-gray-800 hover:bg-gray-200 focus:ring-gray-500',
    'success' => 'bg-green-600 text-white hover:bg-green-700 focus:ring-green-500',
    'danger' => 'bg-red-600 text-white hover:bg-red-700 focus:ring-red-500',
    'outline' => 'border border-gray-300 text-gray-700 hover:bg-gray-50 focus:ring-gray-500',
    'ghost' => 'text-gray-700 hover:bg-gray-100 focus:ring-gray-500',
];

$roundedClass = $rounded ? 'rounded-lg' : 'rounded';
$shadowClass = $shadow ? 'shadow-sm' : '';

$classes = implode(' ', array_filter([
    $baseClasses,
    $sizeClasses[$size] ?? $sizeClasses['md'],
    $typeClasses[$type] ?? $typeClasses['primary'],
    $roundedClass,
    $shadowClass,
]));
@endphp

<button {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</button>
