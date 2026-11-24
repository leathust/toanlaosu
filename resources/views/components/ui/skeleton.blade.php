@props(['class' => 'h-4 w-full'])

<span aria-hidden="true" {{ $attributes->merge(['class' => 'block animate-pulse rounded-lg bg-slate-200 ' . $class]) }}></span>
