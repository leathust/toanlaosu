@props(['variant' => 'info', 'title' => null])

@php
    $variants = [
        'info' => 'border-sky-200 bg-sky-50 text-sky-900',
        'success' => 'border-emerald-200 bg-emerald-50 text-emerald-900',
        'warning' => 'border-amber-200 bg-amber-50 text-amber-900',
        'danger' => 'border-rose-200 bg-rose-50 text-rose-900',
    ];
@endphp

<div role="status" {{ $attributes->merge(['class' => 'rounded-2xl border p-4 text-sm ' . ($variants[$variant] ?? $variants['info'])]) }}>
    @if ($title)<p class="font-semibold">{{ $title }}</p>@endif
    <div class="{{ $title ? 'mt-1' : '' }}">{{ $slot }}</div>
</div>
