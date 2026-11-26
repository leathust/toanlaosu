@php($title = 'Đăng nhập · Toán Lão Sư')

<x-layouts.auth :title="$title">
    <div class="mx-auto max-w-md">
        <a href="{{ route('ui.prototype') }}" class="text-sm font-semibold text-brand-600 lg:hidden">← Toán Lão Sư</a>
        <div class="mt-8 lg:mt-0"><x-ui.badge variant="brand">Chào mừng trở lại</x-ui.badge><h1 class="mt-4 text-3xl font-semibold tracking-tight">Đăng nhập để học tiếp</h1><p class="mt-3 text-sm leading-6 text-slate-600">Sử dụng tài khoản đã được cấp để tiếp tục tiến độ và bài ôn của bạn.</p></div>
        @if ($errors->any())
            <x-ui.alert variant="danger" title="Không thể đăng nhập" class="mt-6" role="alert" aria-live="polite">{{ $errors->first() }}</x-ui.alert>
        @endif
        <form method="POST" action="{{ route('login.store') }}" class="mt-6 space-y-5">
            @csrf
            <x-ui.input label="Email" type="email" name="email" autocomplete="email" placeholder="ban@example.com" :error="$errors->first('email')" value="{{ old('email') }}" required />
            <div class="space-y-2"><div class="flex items-center justify-between gap-3"><label for="password" class="text-sm font-semibold text-slate-800">Mật khẩu</label><a href="{{ route('password.request') }}" class="text-xs font-semibold text-brand-600">Quên mật khẩu?</a></div><div class="relative"><input id="password" type="password" name="password" autocomplete="current-password" class="min-h-11 w-full rounded-xl border border-slate-300 bg-white px-3.5 pr-20 text-sm shadow-sm outline-none placeholder:text-slate-400 focus:border-brand-500 focus:ring-4 focus:ring-brand-100" placeholder="••••••••" @if ($errors->has('password')) aria-invalid="true" aria-describedby="password-error" @endif required><button type="button" id="toggle-password" class="absolute inset-y-0 right-2 my-1 rounded-lg px-2 text-xs font-semibold text-slate-600 hover:bg-slate-100 focus-visible:outline focus-visible:outline-2 focus-visible:outline-brand-500" aria-controls="password" aria-pressed="false">Hiện</button></div>@if ($errors->has('password'))<p id="password-error" class="text-xs font-medium text-danger-600">{{ $errors->first('password') }}</p>@endif</div>
            <label class="flex items-start gap-3 text-sm text-slate-600"><input type="checkbox" name="remember" class="mt-1 size-4 rounded border-slate-300 text-brand-600 focus:ring-brand-500"><span>Ghi nhớ thiết bị này <span class="block text-xs text-slate-400">Không dùng trên máy tính công cộng.</span></span></label>
            <x-ui.button type="submit" class="w-full" id="login-submit"><span data-submit-label>Đăng nhập</span> <span aria-hidden="true">→</span></x-ui.button>
        </form>
        <p class="mt-6 text-center text-sm text-slate-500">Chưa có quyền truy cập? <a href="mailto:support@example.com" class="font-semibold text-brand-600">Liên hệ hỗ trợ</a></p>
    </div>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const form = document.querySelector('form[action="{{ route('login.store') }}"]');
            const password = document.getElementById('password');
            const toggle = document.getElementById('toggle-password');
            const submit = document.getElementById('login-submit');
            const label = submit?.querySelector('[data-submit-label]');

            toggle?.addEventListener('click', () => {
                const visible = password.type === 'text';
                password.type = visible ? 'password' : 'text';
                toggle.textContent = visible ? 'Hiện' : 'Ẩn';
                toggle.setAttribute('aria-pressed', String(!visible));
            });

            form?.addEventListener('submit', () => {
                submit?.setAttribute('disabled', 'disabled');
                submit?.setAttribute('aria-busy', 'true');
                if (label) label.textContent = 'Đang kiểm tra…';
            });
        });
    </script>
</x-layouts.auth>
