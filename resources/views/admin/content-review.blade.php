@php($title = 'Content review · Toán Lão Sư')
<x-layouts.app :title="$title">
    <div class="space-y-6">
        <header class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <a href="{{ route('admin.dashboard') }}" class="text-sm font-semibold text-brand-600">← Operations</a>
                <h1 class="mt-3 text-3xl font-semibold tracking-tight">Content review queue</h1>
                <p class="mt-1 text-sm text-slate-500">Course, Question và Scene được review theo version; bản đã publish không bị ghi đè.</p>
            </div>
        </header>

        <form class="flex flex-wrap gap-3 rounded-2xl border border-slate-200 bg-white p-4" method="get">
            <select name="type" class="rounded-xl border-slate-200">
                <option value="">All content types</option>
                @foreach(['course', 'question', 'scene'] as $type)
                    <option value="{{ $type }}" @selected($filters['type'] === $type)>{{ $type }}</option>
                @endforeach
            </select>
            <select name="reviewer" class="rounded-xl border-slate-200">
                <option value="">All assigned reviewers</option>
                @foreach($reviewers as $reviewer)
                    <option value="{{ $reviewer->id }}" @selected($filters['reviewer'] === $reviewer->id)>{{ $reviewer->name }}</option>
                @endforeach
            </select>
            <button class="rounded-xl bg-brand-600 px-4 py-2 text-sm font-semibold text-white">Apply</button>
        </form>

        <section class="overflow-hidden rounded-3xl border border-slate-200 bg-white">
            <div class="divide-y divide-slate-100">
                @forelse($items as $item)
                    <div class="flex flex-wrap items-center justify-between gap-4 p-5 hover:bg-slate-50">
                        <a href="{{ $item['href'] }}">
                            <p class="font-semibold">{{ $item['title'] }}</p>
                            <p class="text-sm text-slate-500">{{ $item['type'] }} · owner {{ $item['owner'] }} · reviewer {{ $item['assignedReviewer'] ?? 'Unassigned' }}</p>
                        </a>
                        <div class="flex items-center gap-3">
                            <x-ui.badge>{{ $item['status'] }}</x-ui.badge>
                            <span class="text-xs text-slate-400">v{{ $item['version'] ?: '—' }}</span>
                            @if($item['teacherHref'])
                                <a href="{{ $item['teacherHref'] }}" class="text-xs font-semibold text-brand-700">Open Teacher editor</a>
                            @endif
                        </div>
                    </div>
                @empty
                    <p class="p-10 text-center text-sm text-slate-500">Không có content đang chờ review.</p>
                @endforelse
            </div>
        </section>

        <p class="text-xs text-slate-500">Batch review không hiển thị mặc định; chỉ được gọi bằng policy-approved request với content type được allow-list.</p>
    </div>
</x-layouts.app>
