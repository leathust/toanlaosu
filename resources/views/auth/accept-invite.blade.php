@php($title = 'Kích hoạt tài khoản · Toán Lão Sư')

<x-layouts.auth :title="$title">
    <div class="mx-auto max-w-md">
        <x-ui.badge variant="brand">Lời mời tài khoản</x-ui.badge>
        <h1 class="mt-4 text-3xl font-semibold tracking-tight">Thiết lập mật khẩu của bạn</h1>
        <p class="mt-3 text-sm leading-6 text-slate-600">Lời mời này dành cho <span class="font-semibold text-slate-800">{{ $invitation->email }}</span> với vai trò <span class="font-semibold text-slate-800">{{ $invitation->role->display_name }}</span>.</p>

        @if ($errors->any())
            <x-ui.alert variant="danger" title="Không thể kích hoạt tài khoản" class="mt-6" role="alert">{{ $errors->first() }}</x-ui.alert>
        @endif

        <form method="POST" action="{{ route('invite.store', ['token' => $token]) }}" class="mt-6 space-y-5">
            @csrf
            <x-ui.input label="Mật khẩu" type="password" name="password" autocomplete="new-password" hint="Tối thiểu 12 ký tự." required />
            <x-ui.input label="Nhập lại mật khẩu" type="password" name="password_confirmation" autocomplete="new-password" required />
            <x-ui.button type="submit" class="w-full">Kích hoạt tài khoản</x-ui.button>
        </form>
    </div>
</x-layouts.auth>
