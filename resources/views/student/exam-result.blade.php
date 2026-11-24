@php($title = $exam->title.' · Kết quả')

<x-layouts.app :title="$title">
    <div class="space-y-6">
        <header class="flex flex-wrap items-start justify-between gap-4"><div><p class="text-xs font-semibold uppercase tracking-[0.18em] text-brand-600">Exam review</p><h1 class="mt-2 text-3xl font-semibold tracking-tight">{{ $exam->title }}</h1><p class="mt-2 text-sm text-slate-600">Lần thi {{ $attempt->attempt_number }} · {{ $attempt->submitted_at?->format('d/m/Y H:i') ?? 'Chưa nộp' }}</p></div><a href="{{ route('student.exam.history', $exam) }}" class="min-h-11 rounded-xl border border-slate-200 px-4 py-3 text-sm font-semibold text-slate-700">Lịch sử bài thi</a></header>
        @if (! $resultReleased)
            <x-ui.alert variant="info" title="Kết quả chưa được công bố">Điểm sẽ hiển thị sau {{ $exam->result_release_at?->format('d/m/Y H:i') ?? 'khi giáo viên công bố' }}.</x-ui.alert>
        @else
            <section class="grid gap-4 sm:grid-cols-3"><x-ui.card><p class="text-sm text-slate-500">Điểm</p><p class="mt-2 text-3xl font-semibold">{{ $attempt->score ?? '—' }}</p></x-ui.card><x-ui.card><p class="text-sm text-slate-500">Tối đa</p><p class="mt-2 text-3xl font-semibold">{{ $attempt->max_score ?? '—' }}</p></x-ui.card><x-ui.card><p class="text-sm text-slate-500">Trạng thái</p><p class="mt-2 font-semibold text-emerald-700">{{ $attempt->status === 'submitted' ? 'Đã nộp' : 'Đang xử lý' }}</p></x-ui.card></section>
            <section class="space-y-4"><h2 class="text-xl font-semibold">Review câu hỏi</h2>@foreach ($attempt->questions as $index => $question)<article class="rounded-3xl border border-slate-200 bg-white p-5"><div class="flex items-start justify-between gap-4"><h3 class="font-semibold">Câu {{ $index + 1 }}</h3>@if ($answersReleased)<span class="text-sm {{ $question->answer?->is_correct ? 'text-emerald-700' : 'text-rose-700' }}">{{ $question->answer?->is_correct ? 'Đúng' : 'Chưa đúng' }}</span>@else<span class="text-sm text-slate-500">Đáp án đang khóa</span>@endif</div><p class="mt-3 leading-7 text-slate-700">{{ $question->snapshot['stem'] ?? '' }}</p>@if ($answersReleased && $question->answer)<p class="mt-3 rounded-xl bg-slate-50 p-3 text-sm text-slate-600">Trả lời: {{ $question->answer->answer['value'] ?? '—' }} · Điểm: {{ $question->answer->score ?? 0 }}</p>@endif</article>@endforeach</section>
        @endif
    </div>
</x-layouts.app>
