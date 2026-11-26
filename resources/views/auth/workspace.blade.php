@php($title = 'Workspace · Toán Lão Sư')

<x-layouts.auth :title="$title">
    <div class="mx-auto max-w-3xl">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <x-ui.badge variant="brand">Workspace của bạn</x-ui.badge>
                <h1 class="mt-4 text-3xl font-semibold tracking-tight">Chọn không gian làm việc</h1>
                <p class="mt-3 text-sm leading-6 text-slate-600">Bạn chỉ nhìn thấy những workspace được cấp trực tiếp cho tài khoản này.</p>
            </div>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="min-h-10 rounded-xl border border-slate-200 px-3 text-sm font-semibold text-slate-700 transition hover:border-slate-300 hover:bg-slate-50 focus-visible:outline focus-visible:outline-2 focus-visible:outline-brand-500">Đăng xuất</button>
            </form>
        </div>

        @if (count($workspaces) > 1)
            <x-ui.alert variant="info" title="Bạn có nhiều quyền truy cập" class="mt-6">Chọn đúng workspace cho công việc bạn muốn làm. Mọi quyền vẫn được kiểm tra lại ở server.</x-ui.alert>
        @endif

        @if (count($workspaces) > 0)
            <div class="mt-6 grid gap-4 {{ count($workspaces) === 1 ? 'sm:grid-cols-1' : (count($workspaces) === 2 ? 'sm:grid-cols-2' : 'sm:grid-cols-3') }}" aria-label="Workspace được cấp quyền">
                @foreach ($workspaces as $workspace)
                    <a href="{{ route($workspace['route']) }}" class="group rounded-3xl border p-5 transition hover:-translate-y-0.5 hover:shadow-lg focus-visible:outline focus-visible:outline-2 focus-visible:outline-brand-500 {{ $workspace['classes'] }}">
                        <span class="flex size-11 items-center justify-center rounded-2xl bg-white text-xl shadow-sm" aria-hidden="true">{{ $workspace['icon'] }}</span>
                        <h2 class="mt-5 text-lg font-semibold">{{ $workspace['label'] }}</h2>
                        <p class="mt-2 text-sm leading-6 opacity-80">{{ $workspace['description'] }}</p>
                        <span class="mt-5 block text-sm font-semibold">Mở workspace <span aria-hidden="true">→</span></span>
                    </a>
                @endforeach
            </div>
        @else
            <x-ui.alert variant="warning" title="Tài khoản chưa được cấp workspace" class="mt-6" role="status">
                Tài khoản của bạn đang hoạt động nhưng chưa có role phù hợp. Vui lòng liên hệ Admin hoặc bộ phận hỗ trợ để được cấp quyền.
            </x-ui.alert>
        @endif

        <p class="mt-6 text-xs leading-5 text-slate-500">Đang đăng nhập với <span class="font-semibold text-slate-700">{{ auth()->user()->email }}</span>. Nếu bạn cho rằng quyền truy cập chưa đúng, hãy liên hệ Admin.</p>
    </div>
</x-layouts.auth>
