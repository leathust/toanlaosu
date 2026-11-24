@props(['value' => 0, 'label' => null])

@php($safeValue = min(100, max(0, (int) $value)))

<div {{ $attributes }}>
    @if ($label)
        <div class="mb-2 flex items-center justify-between gap-3 text-sm"><span class="text-slate-600">{{ $label }}</span><span class="font-semibold text-slate-900">{{ $safeValue }}%</span></div>
    @endif
    <div class="h-2 overflow-hidden rounded-full bg-slate-100" role="progressbar" aria-valuenow="{{ $safeValue }}" aria-valuemin="0" aria-valuemax="100" @if ($label) aria-label="{{ $label }}" @endif><div class="h-full rounded-full bg-brand-600 transition-[width]" style="width: {{ $safeValue }}%"></div></div>
</div>
