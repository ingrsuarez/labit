@props([
    'label',
    'value',
    'sublabel' => null,
    'color' => 'gray',
    'highlight' => false,
])

@php
    $colorClasses = [
        'blue'   => 'bg-blue-50 border-blue-200',
        'green'  => 'bg-green-50 border-green-200',
        'orange' => 'bg-orange-50 border-orange-200',
        'red'    => 'bg-red-50 border-red-200',
        'gray'   => 'bg-gray-50 border-gray-200',
    ];
    $base = $colorClasses[$color] ?? $colorClasses['gray'];
    $ringClass = $highlight ? 'border-2 ring-2 ring-red-300' : 'border';
@endphp

<div {{ $attributes->merge(['class' => $base . ' rounded-lg p-4 ' . $ringClass]) }}>
    <div class="text-xs uppercase tracking-wide text-gray-500 font-medium">{{ $label }}</div>
    <div class="text-3xl font-bold mt-1 text-gray-800">{{ number_format($value) }}</div>
    @if ($sublabel)
        <div class="text-xs text-gray-500 mt-1">{{ $sublabel }}</div>
    @endif
</div>
