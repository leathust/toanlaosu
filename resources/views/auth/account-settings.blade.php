@php($title = 'Cài đặt tài khoản · Toán Lão Sư')

<x-layouts.auth :title="$title">
    <div class="mx-auto max-w-2xl space-y-8">
        <form method="POST" action="{{ route('logout') }}" class="flex justify-end">@csrf<button type="submit" class="text-sm font-semibold text-slate-600">Đăng xuất</button></form>
        <div>
            <a href="{{ route('workspace') }}" class="text-sm font-semibold text-brand-600 focus-visible:outline focus-visible:outline-2 focus-visible:outline-brand-500">← Về workspace</a>
            <x-ui.badge variant="brand" class="mt-8">Tài khoản</x-ui.badge>
            <h1 class="mt-4 text-3xl font-semibold tracking-tight">Cài đặt bảo mật</h1>
            <p class="mt-3 text-sm leading-6 text-slate-600">Cập nhật email hoặc mật khẩu. Các thao tác nhạy cảm đều yêu cầu mật khẩu hiện tại.</p>
        </div>

        @if (session('status'))
            <x-ui.alert variant="success" title="Đã cập nhật" role="status">{{ session('status') }}</x-ui.alert>
        @endif
        @if ($errors->any())
            <x-ui.alert variant="danger" title="Không thể cập nhật" role="alert">{{ $errors->first() }}</x-ui.alert>
        @endif

        <section class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6" aria-labelledby="email-heading">
            <div class="flex items-start justify-between gap-4"><div><h2 id="email-heading" class="text-lg font-semibold">Địa chỉ email</h2><p class="mt-1 text-sm text-slate-500">Email hiện tại: {{ auth()->user()->email }}</p></div><span class="rounded-full px-2.5 py-1 text-xs font-semibold {{ auth()->user()->hasVerifiedEmail() ? 'bg-emerald-50 text-emerald-700' : 'bg-amber-50 text-amber-700' }}">{{ auth()->user()->hasVerifiedEmail() ? 'Đã xác minh' : 'Chưa xác minh' }}</span></div>
            <form method="POST" action="{{ route('account.email.update') }}" class="mt-5 grid gap-4 sm:grid-cols-2">
                @csrf @method('PUT')
                <x-ui.input label="Email mới" type="email" name="email" autocomplete="email" :value="old('email', auth()->user()->email)" required />
                <x-ui.input label="Mật khẩu hiện tại" type="password" name="current_password" autocomplete="current-password" required />
                <div class="sm:col-span-2"><x-ui.button type="submit">Cập nhật email</x-ui.button></div>
            </form>
        </section>

        <section class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6" aria-labelledby="password-heading">
            <div><h2 id="password-heading" class="text-lg font-semibold">Mật khẩu</h2><p class="mt-1 text-sm text-slate-500">Sau khi đổi, các phiên đăng nhập khác sẽ bị thu hồi.</p></div>
            <form method="POST" action="{{ route('account.password.update') }}" class="mt-5 grid gap-4 sm:grid-cols-2">
                @csrf @method('PUT')
                <x-ui.input label="Mật khẩu hiện tại" type="password" name="current_password" autocomplete="current-password" required />
                <x-ui.input label="Mật khẩu mới" type="password" name="password" autocomplete="new-password" hint="Tối thiểu 12 ký tự." required />
                <x-ui.input label="Nhập lại mật khẩu mới" type="password" name="password_confirmation" autocomplete="new-password" />
                <div class="sm:col-span-2"><x-ui.button type="submit">Đổi mật khẩu</x-ui.button></div>
            </form>
        </section>

        <section class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6" aria-labelledby="sessions-heading">
            <div class="flex items-start justify-between gap-4">
                <div><h2 id="sessions-heading" class="text-lg font-semibold">Phiên đăng nhập</h2><p class="mt-1 text-sm text-slate-500">Chỉ hiển thị thiết bị, IP rút gọn và thời điểm hoạt động gần nhất.</p></div>
                <form method="POST" action="{{ route('account.sessions.revoke-others') }}">@csrf @method('DELETE')<x-ui.button type="submit" variant="secondary">Thu hồi phiên khác</x-ui.button></form>
            </div>
            <div class="mt-5 divide-y divide-slate-100">
                @forelse ($sessions as $session)
                    <div class="flex flex-wrap items-center justify-between gap-3 py-4 first:pt-0 last:pb-0">
                        <div><p class="font-medium text-slate-900">{{ $session['device'] }} @if ($session['is_current'])<span class="ml-2 text-xs font-semibold text-emerald-700">Phiên hiện tại</span>@endif</p><p class="text-sm text-slate-500">IP {{ $session['ip_address'] }} · {{ \Carbon\Carbon::createFromTimestamp($session['last_activity'])->diffForHumans() }}</p></div>
                        @unless ($session['is_current'])
                            <form method="POST" action="{{ route('account.sessions.revoke', $session['key']) }}">@csrf @method('DELETE')<x-ui.button type="submit" variant="secondary">Thu hồi</x-ui.button></form>
                        @endunless
                    </div>
                @empty
                    <p class="py-4 text-sm text-slate-500">Chưa có phiên đăng nhập được lưu.</p>
                @endforelse
            </div>
        </section>
    </div>
</x-layouts.auth>
