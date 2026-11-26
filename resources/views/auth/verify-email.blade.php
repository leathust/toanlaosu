@php($title = 'Xác minh email · Toán Lão Sư')

<x-layouts.auth :title="$title">
    <div class="mx-auto max-w-md">
        <x-ui.badge variant="warning">Cần xác minh</x-ui.badge>
        <h1 class="mt-4 text-3xl font-semibold tracking-tight">Kiểm tra hộp thư của bạn</h1>
        <p class="mt-3 text-sm leading-6 text-slate-600">Xác minh <span class="font-semibold text-slate-800">{{ auth()->user()->email }}</span> để mở bài học, ca thi, AI và workspace vận hành.</p>
        <div class="mt-8 flex size-16 items-center justify-center rounded-2xl bg-amber-50 text-2xl text-amber-700" aria-hidden="true">✉</div>

        @if (session('status') === 'verification-link-sent')
            <x-ui.alert variant="success" title="Đã gửi lại email" class="mt-6" role="status">Liên kết xác minh mới đã được gửi. Hãy kiểm tra cả thư mục thư rác.</x-ui.alert>
        @elseif (session('status'))
            <x-ui.alert variant="success" title="Đã cập nhật" class="mt-6" role="status">{{ session('status') }}</x-ui.alert>
        @endif

        <form method="POST" action="{{ route('verification.send') }}" class="mt-6">
            @csrf
            <x-ui.button type="submit" class="w-full">Gửi lại email xác minh</x-ui.button>
        </form>
        <div class="mt-4 flex flex-wrap gap-3">
            <a href="{{ route('account.settings') }}" class="inline-flex min-h-11 flex-1 items-center justify-center rounded-xl border border-slate-200 px-4 text-sm font-semibold text-slate-700 focus-visible:outline focus-visible:outline-2 focus-visible:outline-brand-500">Cài đặt tài khoản</a>
            <form method="POST" action="{{ route('logout') }}" class="flex-1">
                @csrf
                <button type="submit" class="min-h-11 w-full rounded-xl border border-slate-200 px-4 text-sm font-semibold text-slate-700 focus-visible:outline focus-visible:outline-2 focus-visible:outline-brand-500">Đăng xuất</button>
            </form>
        </div>
    </div>
</x-layouts.auth>
