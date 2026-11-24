<!DOCTYPE html>
<html lang="vi">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <title>{{ $title ?? 'Toán Lão Sư' }}</title>
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="min-h-screen bg-slate-50 text-slate-950 antialiased">
        <header class="border-b border-slate-200 bg-white">
            <div class="mx-auto flex min-h-16 max-w-6xl items-center justify-between gap-4 px-4 sm:px-6">
                <a href="{{ route('ui.components') }}" class="flex items-center gap-3 font-semibold tracking-tight">
                    <span class="flex size-9 items-center justify-center rounded-xl bg-brand-600 text-sm font-bold text-white">TL</span>
                    <span>Toán Lão Sư <span class="font-normal text-slate-400">/ UI kit</span></span>
                </a>
                <nav aria-label="UI kit navigation" class="flex items-center gap-1 text-sm">
                    <a href="#foundations" class="rounded-lg px-3 py-2 text-slate-600 hover:bg-slate-50">Foundations</a>
                    <a href="#states" class="rounded-lg px-3 py-2 text-slate-600 hover:bg-slate-50">States</a>
                    <a href="{{ route('ui.prototype') }}" class="rounded-lg bg-brand-50 px-3 py-2 font-semibold text-brand-700">Prototype</a>
                </nav>
            </div>
        </header>
        <main class="mx-auto max-w-6xl px-4 py-8 sm:px-6 lg:py-12">{{ $slot }}</main>
    </body>
</html>
