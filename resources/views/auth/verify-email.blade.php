@php($title = 'Xác minh email · Toán Lão Sư')

<x-layouts.auth :title="$title">
    <div class="mx-auto max-w-md"><x-ui.badge variant="warning">Cần xác minh</x-ui.badge><h1 class="mt-4 text-3xl font-semibold tracking-tight">Kiểm tra hộp thư của bạn</h1><p class="mt-3 text-sm leading-6 text-slate-600">Tài khoản cần xác minh email trước khi truy cập bài học và ca thi.</p><div class="mt-8 flex size-16 items-center justify-center rounded-2xl bg-amber-50 text-2xl text-amber-700">✉</div><x-ui.alert variant="info" title="Chưa nhận được email?" class="mt-6">Kiểm tra thư rác hoặc yêu cầu gửi lại sau khi backend auth được kết nối.</x-ui.alert><div class="mt-6 flex flex-wrap gap-3"><x-ui.button type="button">Gửi lại email</x-ui.button><a href="{{ route('login') }}" class="inline-flex min-h-11 items-center rounded-xl border border-slate-200 px-4 text-sm font-semibold text-slate-700">Đổi tài khoản</a></div></div>
</x-layouts.auth>
