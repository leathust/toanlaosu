@php($title = 'Khôi phục mật khẩu · Toán Lão Sư')

<x-layouts.auth :title="$title">
    <div class="mx-auto max-w-md"><a href="{{ route('login') }}" class="text-sm font-semibold text-brand-600">← Quay lại đăng nhập</a><div class="mt-10"><x-ui.badge variant="brand">Khôi phục tài khoản</x-ui.badge><h1 class="mt-4 text-3xl font-semibold tracking-tight">Bạn quên mật khẩu?</h1><p class="mt-3 text-sm leading-6 text-slate-600">Nhập email đã đăng ký. Khi backend auth được kết nối, chúng tôi sẽ gửi liên kết đặt lại có thời hạn.</p></div><x-ui.alert variant="warning" title="Prototype UI" class="mt-6">Chưa gửi email thật trong bản prototype này.</x-ui.alert><form method="GET" action="{{ route('password.request') }}" class="mt-6 space-y-5"><x-ui.input label="Email tài khoản" type="email" name="email" autocomplete="email" placeholder="ban@example.com" required /><x-ui.button type="submit" class="w-full">Gửi hướng dẫn</x-ui.button></form></div>
</x-layouts.auth>
