@props([
    'variant' => 'primary',
    'size' => 'md',
    'type' => 'button',
])

@php
    $variants = [
        'primary' => 'bg-brand-600 text-white hover:bg-brand-700',
        'secondary' => 'border border-slate-200 bg-white text-slate-700 hover:bg-slate-50',
        'quiet' => 'text-slate-600 hover:bg-slate-100',
        'danger' => 'bg-danger-600 text-white hover:bg-red-700',
    ];
    $sizes = [
        'sm' => 'min-h-9 px-3 text-xs',
        'md' => 'min-h-11 px-4 text-sm',
        'lg' => 'min-h-12 px-5 text-base',
    ];
@endphp

<button type="{{ $type }}" {{ $attributes->merge(['class' => 'inline-flex items-center justify-center gap-2 rounded-xl font-semibold transition-colors focus-visible:outline-brand-500 ' . ($variants[$variant] ?? $variants['primary']) . ' ' . ($sizes[$size] ?? $sizes['md'])]) }}>
    {{ $slot }}
</button>
