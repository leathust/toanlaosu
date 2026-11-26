@php($title = 'Review content · Toán Lão Sư')
<x-layouts.app :title="$title">
    <div class="mx-auto max-w-4xl space-y-6">
        <header>
            <a href="{{ route('admin.content.review') }}" class="text-sm font-semibold text-brand-600">← Review queue</a>
            <h1 class="mt-3 text-3xl font-semibold tracking-tight">Review {{ $type }}</h1>
            <p class="mt-1 text-sm text-slate-500">{{ $content->title ?? $content->name ?? $content->public_id }} · status {{ $content->status }}</p>
        </header>
        <section class="rounded-3xl border border-slate-200 bg-white p-6">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <h2 class="font-semibold">Version preview</h2>
                @if($type === 'course' || $version)
                    <a href="{{ $type === 'question' ? route('teacher.questions.edit', ['question' => $content->id, 'version' => $version->version]) : ($type === 'scene' ? route('teacher.scenes.edit', ['visualScene' => $content->id, 'version' => $version->version]) : route('teacher.course', $content->id)) }}" class="rounded-xl border border-brand-200 px-3 py-2 text-sm font-semibold text-brand-700">Open Teacher editor</a>
                @endif
            </div>
            @if($version)
                <div class="mt-4 space-y-3">
                    <p class="whitespace-pre-wrap text-slate-800">{{ $version->stem ?? json_encode($version->config) }}</p>
                    @if($version->solution)
                        <div class="rounded-2xl bg-slate-50 p-4"><p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Solution</p><p class="mt-2 whitespace-pre-wrap text-sm">{{ $version->solution }}</p></div>
                    @endif
                </div>
            @else
                <p class="mt-3 text-sm text-slate-500">Course metadata preview.</p>
            @endif
        </section>
        <form class="grid gap-4 rounded-3xl border border-slate-200 bg-white p-6" method="post" action="{{ route('admin.content.review.decision', [$type, $content->id]) }}">
            @csrf @method('PATCH')
            @if($version)<input type="hidden" name="expected_version" value="{{ $version->version }}">@endif
            <label class="grid gap-2 text-sm font-semibold">Decision<select name="decision" class="rounded-xl border-slate-200"><option value="approve">Approve / publish</option><option value="request_changes">Request changes</option><option value="reject">Reject</option></select></label>
            <label class="grid gap-2 text-sm font-semibold">Reason<textarea name="reason" required minlength="3" maxlength="500" class="min-h-28 rounded-xl border-slate-200"></textarea></label>
            <button class="rounded-xl bg-brand-600 px-4 py-3 text-sm font-semibold text-white">Save review decision</button>
        </form>
    </div>
</x-layouts.app>
