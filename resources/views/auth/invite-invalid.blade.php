@php($title = 'Lời mời không hợp lệ · Toán Lão Sư')

<x-layouts.auth :title="$title">
    <div class="mx-auto max-w-md">
        <x-ui.badge variant="warning">Lời mời tài khoản</x-ui.badge>
        <h1 class="mt-4 text-3xl font-semibold tracking-tight">Liên kết không còn hiệu lực</h1>
        <p class="mt-3 text-sm leading-6 text-slate-600">Lời mời có thể đã hết hạn, đã được sử dụng hoặc bị thu hồi. Vui lòng liên hệ Admin để nhận lời mời mới.</p>
        <a href="{{ route('login') }}" class="mt-6 inline-flex min-h-11 items-center rounded-xl bg-brand-600 px-4 text-sm font-semibold text-white hover:bg-brand-700 focus-visible:outline focus-visible:outline-2 focus-visible:outline-brand-500">Quay lại đăng nhập</a>
    </div>
</x-layouts.auth>
