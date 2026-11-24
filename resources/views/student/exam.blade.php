@php($title = $exam->title.' · Thi online')

<x-layouts.app :title="$title">
    <div data-exam-shell data-attempt-id="{{ $attempt->id }}" data-deadline="{{ $attempt->deadline_at->toIso8601String() }}" data-server-now="{{ $serverNow->toIso8601String() }}" class="space-y-6">
        <header class="flex flex-wrap items-start justify-between gap-4">
            <div><p class="text-xs font-semibold uppercase tracking-[0.18em] text-brand-600">Candidate exam</p><h1 class="mt-2 text-3xl font-semibold tracking-tight">{{ $exam->title }}</h1><p class="mt-2 text-sm text-slate-600">Mốc thời gian lấy từ server; reload không làm tăng thời gian.</p></div>
            <div class="rounded-2xl bg-amber-50 px-5 py-3 text-center"><p class="text-xs font-semibold uppercase tracking-wide text-amber-700">Còn lại</p><p data-exam-timer class="mt-1 text-2xl font-semibold tabular-nums text-amber-950">--:--</p><span data-exam-connection class="text-xs text-emerald-700">Đã kết nối</span></div>
        </header>

        <div class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_18rem]">
            <main class="space-y-4">
                @foreach ($attempt->questions as $index => $question)
                    <article id="question-{{ $question->id }}" class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm" data-exam-question data-question-id="{{ $question->id }}" data-save-version="{{ $attempt->save_version }}">
                        <div class="flex items-start justify-between gap-4"><h2 class="font-semibold">Câu {{ $index + 1 }}</h2><span data-answer-state class="text-xs text-slate-500">Chưa trả lời</span></div>
                        <p class="mt-4 leading-7 text-slate-700">{{ $question->snapshot['stem'] ?? 'Nội dung câu hỏi đang được chuẩn bị.' }}</p>
                        <textarea data-answer-input class="mt-4 min-h-24 w-full rounded-xl border border-slate-200 p-3 text-sm" placeholder="Nhập câu trả lời..."></textarea>
                        <button data-save-answer type="button" class="mt-3 min-h-10 rounded-xl border border-slate-200 px-3 text-sm font-semibold text-slate-700">Lưu đáp án</button>
                    </article>
                @endforeach
            </main>
            <aside class="h-fit space-y-4 lg:sticky lg:top-6"><section class="rounded-3xl border border-slate-200 bg-white p-5"><h2 class="font-semibold">Bảng câu hỏi</h2><div class="mt-4 grid grid-cols-5 gap-2">@foreach ($attempt->questions as $index => $question)<a href="#question-{{ $question->id }}" class="flex size-9 items-center justify-center rounded-xl bg-slate-100 text-sm font-semibold text-slate-600">{{ $index + 1 }}</a>@endforeach</div></section><button data-submit-exam type="button" class="min-h-11 w-full rounded-xl bg-brand-600 px-4 text-sm font-semibold text-white">Nộp bài</button><p data-exam-feedback class="text-sm text-slate-500" aria-live="polite"></p></aside>
        </div>
    </div>
    @vite('resources/js/exam.js')
</x-layouts.app>
