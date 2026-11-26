@php($title = 'Xác nhận mật khẩu · Toán Lão Sư')

<x-layouts.auth :title="$title">
    <div class="mx-auto max-w-lg">
        <x-ui.badge variant="brand">Bảo mật tài khoản</x-ui.badge>
        <h1 class="mt-4 text-3xl font-semibold tracking-tight">Xác nhận mật khẩu</h1>
        <p class="mt-3 text-sm leading-6 text-slate-600">Vì lý do bảo mật, hãy nhập mật khẩu hiện tại trước khi tiếp tục thao tác nhạy cảm.</p>
        @if ($errors->any())
            <x-ui.alert class="mt-6" variant="danger" title="Không thể xác nhận" role="alert">{{ $errors->first() }}</x-ui.alert>
        @endif
        <form method="POST" action="{{ route('password.confirm.store') }}" class="mt-8 space-y-5">
            @csrf
            <x-ui.input label="Mật khẩu hiện tại" type="password" name="password" autocomplete="current-password" required autofocus />
            <x-ui.button type="submit">Xác nhận và tiếp tục</x-ui.button>
        </form>
    </div>
</x-layouts.auth>
