@php($title = 'Đặt lại mật khẩu · Toán Lão Sư')

<x-layouts.auth :title="$title">
    <div class="mx-auto max-w-md">
        <a href="{{ route('login') }}" class="text-sm font-semibold text-brand-600 focus-visible:outline focus-visible:outline-2 focus-visible:outline-brand-500">← Quay lại đăng nhập</a>
        <div class="mt-10">
            <x-ui.badge variant="brand">Bảo mật tài khoản</x-ui.badge>
            <h1 class="mt-4 text-3xl font-semibold tracking-tight">Tạo mật khẩu mới</h1>
            <p class="mt-3 text-sm leading-6 text-slate-600">Mật khẩu mới cần có ít nhất 12 ký tự và phải được nhập lại chính xác.</p>
        </div>

        @if ($errors->any())
            <x-ui.alert variant="danger" title="Liên kết không hợp lệ" class="mt-6" role="alert">{{ $errors->first() }}</x-ui.alert>
        @endif

        <form method="POST" action="{{ route('password.update') }}" class="mt-6 space-y-5">
            @csrf
            <input type="hidden" name="token" value="{{ $token }}">
            <x-ui.input label="Email tài khoản" type="email" name="email" autocomplete="email" :value="old('email', $email)" required />
            <x-ui.input label="Mật khẩu mới" type="password" name="password" autocomplete="new-password" hint="Tối thiểu 12 ký tự." required />
            <x-ui.input label="Nhập lại mật khẩu mới" type="password" name="password_confirmation" autocomplete="new-password" required />
            <x-ui.button type="submit" class="w-full">Cập nhật mật khẩu</x-ui.button>
        </form>
    </div>
</x-layouts.auth>
