@props([
    'title',
    'value',
    'subtitle' => null,
    'color' => 'blue',
    'icon' => null,
])

@php
    $colorClasses = [
        'blue' => 'border-blue-500 bg-blue-100 text-blue-600',
        'green' => 'border-green-500 bg-green-100 text-green-600',
        'purple' => 'border-purple-500 bg-purple-100 text-purple-600',
        'orange' => 'border-orange-500 bg-orange-100 text-orange-600',
        'red' => 'border-red-500 bg-red-100 text-red-600',
        'indigo' => 'border-indigo-500 bg-indigo-100 text-indigo-600',
        'yellow' => 'border-yellow-500 bg-yellow-100 text-yellow-600',
    ];
    $borderColor = Str::before($colorClasses[$color] ?? $colorClasses['blue'], ' ');
    $bgColor = Str::beforeLast(Str::after($colorClasses[$color] ?? $colorClasses['blue'], ' '), ' ');
    $textColor = Str::afterLast($colorClasses[$color] ?? $colorClasses['blue'], ' ');
@endphp

<div class="bg-white rounded-xl shadow-sm p-6 border-l-4 {{ $borderColor }}">
    <div class="flex items-center justify-between">
        <div>
            <p class="text-sm text-gray-500">{{ $title }}</p>
            <p class="text-3xl font-bold text-gray-800">{{ $value }}</p>
            @if($subtitle)
                <p class="text-xs text-gray-500 mt-1">{{ $subtitle }}</p>
            @endif
        </div>
        @if($icon)
            <div class="w-12 h-12 {{ $bgColor }} rounded-full flex items-center justify-center">
                <span class="{{ $textColor }}">{!! $icon !!}</span>
            </div>
        @endif
    </div>
</div>
