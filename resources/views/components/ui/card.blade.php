@props(['padding' => 'md'])

@php
    $paddings = ['none' => '', 'sm' => 'p-4', 'md' => 'p-5 sm:p-6', 'lg' => 'p-6 sm:p-8'];
@endphp

<section {{ $attributes->merge(['class' => 'rounded-3xl border border-slate-200 bg-white shadow-sm ' . ($paddings[$padding] ?? $paddings['md'])]) }}>{{ $slot }}</section>
