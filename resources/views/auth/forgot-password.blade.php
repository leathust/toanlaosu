@php($title = 'Khôi phục mật khẩu · Toán Lão Sư')

<x-layouts.auth :title="$title">
    <div class="mx-auto max-w-md">
        <a href="{{ route('login') }}" class="text-sm font-semibold text-brand-600 focus-visible:outline focus-visible:outline-2 focus-visible:outline-brand-500">← Quay lại đăng nhập</a>
        <div class="mt-10">
            <x-ui.badge variant="brand">Khôi phục tài khoản</x-ui.badge>
            <h1 class="mt-4 text-3xl font-semibold tracking-tight">Bạn quên mật khẩu?</h1>
            <p class="mt-3 text-sm leading-6 text-slate-600">Nhập email đã đăng ký. Nếu tài khoản tồn tại, chúng tôi sẽ gửi một liên kết đặt lại có thời hạn.</p>
        </div>

        @if (session('status'))
            <x-ui.alert variant="success" title="Đã tiếp nhận yêu cầu" class="mt-6" role="status" aria-live="polite">{{ session('status') }}</x-ui.alert>
        @endif
        @if ($errors->any())
            <x-ui.alert variant="danger" title="Không thể gửi yêu cầu" class="mt-6" role="alert">{{ $errors->first() }}</x-ui.alert>
        @endif

        <form method="POST" action="{{ route('password.email') }}" class="mt-6 space-y-5">
            @csrf
            <x-ui.input label="Email tài khoản" type="email" name="email" autocomplete="email" placeholder="ban@example.com" :value="old('email')" :error="$errors->first('email')" required />
            <x-ui.button type="submit" class="w-full">Gửi hướng dẫn</x-ui.button>
        </form>
    </div>
</x-layouts.auth>
