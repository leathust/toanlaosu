@php($title = 'Đăng nhập · Toán Lão Sư')

<x-layouts.auth :title="$title">
    <div class="mx-auto max-w-md">
        <a href="{{ route('ui.prototype') }}" class="text-sm font-semibold text-brand-600 lg:hidden">← Toán Lão Sư</a>
        <div class="mt-8 lg:mt-0"><x-ui.badge variant="brand">Chào mừng trở lại</x-ui.badge><h1 class="mt-4 text-3xl font-semibold tracking-tight">Đăng nhập để học tiếp</h1><p class="mt-3 text-sm leading-6 text-slate-600">Sử dụng tài khoản đã được cấp để tiếp tục tiến độ và bài ôn của bạn.</p></div>
        <x-ui.alert variant="info" title="Prototype UI" class="mt-6">Form đang ở chế độ xem trước; kết nối session/auth thật sẽ được tích hợp ở lớp backend.</x-ui.alert>
        <form method="POST" action="{{ route('login.store') }}" class="mt-6 space-y-5">
            @csrf
            <x-ui.input label="Email" type="email" name="email" autocomplete="email" placeholder="ban@example.com" required />
            <div class="space-y-2"><div class="flex items-center justify-between gap-3"><label for="password" class="text-sm font-semibold text-slate-800">Mật khẩu</label><a href="{{ route('password.request') }}" class="text-xs font-semibold text-brand-600">Quên mật khẩu?</a></div><input id="password" type="password" name="password" autocomplete="current-password" class="min-h-11 w-full rounded-xl border border-slate-300 bg-white px-3.5 text-sm shadow-sm outline-none placeholder:text-slate-400 focus:border-brand-500 focus:ring-4 focus:ring-brand-100" placeholder="••••••••" required></div>
            <label class="flex items-start gap-3 text-sm text-slate-600"><input type="checkbox" name="remember" class="mt-1 size-4 rounded border-slate-300 text-brand-600 focus:ring-brand-500"><span>Ghi nhớ thiết bị này <span class="block text-xs text-slate-400">Không dùng trên máy tính công cộng.</span></span></label>
            <x-ui.button type="submit" class="w-full">Đăng nhập <span aria-hidden="true">→</span></x-ui.button>
        </form>
        <p class="mt-6 text-center text-sm text-slate-500">Chưa có quyền truy cập? <a href="mailto:support@example.com" class="font-semibold text-brand-600">Liên hệ hỗ trợ</a></p>
    </div>
</x-layouts.auth>
