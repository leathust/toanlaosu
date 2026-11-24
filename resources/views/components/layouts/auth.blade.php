<!DOCTYPE html>
<html lang="vi">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>{{ $title ?? 'Đăng nhập · Toán Lão Sư' }}</title>
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="min-h-screen bg-slate-100 text-slate-950 antialiased">
        <main class="flex min-h-screen items-center justify-center px-4 py-8 sm:px-6">
            <div class="grid w-full max-w-5xl overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-xl shadow-slate-200/60 lg:grid-cols-[0.9fr_1.1fr]">
                <aside class="hidden bg-slate-950 p-10 text-white lg:flex lg:flex-col lg:justify-between">
                    <div><a href="{{ route('ui.prototype') }}" class="flex items-center gap-3 font-semibold"><span class="flex size-10 items-center justify-center rounded-2xl bg-brand-500">TL</span> Toán Lão Sư</a><p class="mt-16 max-w-xs text-3xl font-semibold leading-tight">Học bằng cách nhìn thấy điều đang thay đổi.</p><p class="mt-5 max-w-sm text-sm leading-6 text-slate-300">Một không gian học tập nơi công thức, câu hỏi và mô hình 2D cùng đưa bạn đi tiếp.</p></div>
                    <p class="text-xs text-slate-400">UI-003 · Authentication prototype</p>
                </aside>
                <section class="p-6 sm:p-10">{{ $slot }}</section>
            </div>
        </main>
    </body>
</html>
