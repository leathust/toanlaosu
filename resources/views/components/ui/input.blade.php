@props(['label' => null, 'error' => null, 'hint' => null])

@php($inputId = $attributes->get('id', 'field-' . uniqid()))

<div class="space-y-2">
    @if ($label)<label for="{{ $inputId }}" class="block text-sm font-semibold text-slate-800">{{ $label }}</label>@endif
    <input id="{{ $inputId }}" {{ $attributes->except(['id'])->merge(['class' => 'min-h-11 w-full rounded-xl border border-slate-300 bg-white px-3.5 text-sm text-slate-900 shadow-sm outline-none placeholder:text-slate-400 focus:border-brand-500 focus:ring-4 focus:ring-brand-100']) }} @if ($error) aria-invalid="true" aria-describedby="{{ $inputId }}-error" @endif>
    @if ($hint && ! $error)<p class="text-xs text-slate-500">{{ $hint }}</p>@endif
    @if ($error)<p id="{{ $inputId }}-error" class="text-xs font-medium text-danger-600">{{ $error }}</p>@endif
</div>
