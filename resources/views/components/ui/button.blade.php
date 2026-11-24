@props([
    'size' => 'md',
    'variant' => 'primary',
    'startIcon' => null,
    'endIcon' => null,
    'className' => '',
    'disabled' => false,
])

@php
    $base = 'inline-flex min-h-[44px] items-center justify-center font-medium gap-2 rounded-lg transition focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-500';

    $sizeMap = [
        'sm' => 'px-4 py-2.5 text-sm',
        'md' => 'px-5 py-3 text-sm',
    ];
    $sizeClass = $sizeMap[$size] ?? $sizeMap['md'];

    $variantMap = [
        'primary' => 'bg-brand-600 text-white shadow-theme-xs hover:bg-brand-700 disabled:bg-brand-300',
        'outline' => 'bg-white text-gray-700 ring-1 ring-inset ring-gray-300 hover:bg-gray-50 dark:bg-gray-800 dark:text-gray-200 dark:ring-gray-700 dark:hover:bg-white/[0.03] dark:hover:text-white',
    ];
    $variantClass = $variantMap[$variant] ?? $variantMap['primary'];

    $disabledClass = $disabled ? 'cursor-not-allowed opacity-50' : '';

    $classes = trim("{$base} {$sizeClass} {$variantClass} {$className} {$disabledClass}");
@endphp

<button
    {{ $attributes->merge(['class' => $classes, 'type' => $attributes->get('type', 'button')]) }}
    @if($disabled) disabled @endif
>
    @hasSection('startIcon')
        <span class="flex items-center">
            @yield('startIcon')
        </span>
    @elseif($startIcon)
        <span class="flex items-center">{!! $startIcon !!}</span>
    @endif

    {{ $slot }}

    @hasSection('endIcon')
        <span class="flex items-center">
            @yield('endIcon')
        </span>
    @elseif($endIcon)
        <span class="flex items-center">{!! $endIcon !!}</span>
    @endif
</button>
