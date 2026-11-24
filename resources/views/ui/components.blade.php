@php($title = 'Toán Lão Sư · UI kit')

<x-layouts.app :title="$title">
    <div class="space-y-10">
        <header id="foundations" class="max-w-3xl">
            <x-ui.badge variant="brand">UI-002 · Design system</x-ui.badge>
            <h1 class="mt-4 text-3xl font-semibold tracking-tight text-slate-950 sm:text-4xl">Một bộ giao diện rõ ràng cho việc học khó.</h1>
            <p class="mt-4 text-base leading-7 text-slate-600">Trang showcase này là hợp đồng trực quan cho token, primitive và trạng thái dùng chung trước khi xây màn hình nghiệp vụ.</p>
        </header>

        <x-ui.card>
            <div class="flex flex-wrap items-end justify-between gap-4">
                <div><p class="text-sm font-semibold text-brand-600">Foundations</p><h2 class="mt-1 text-xl font-semibold">Actions & status</h2></div>
                <p class="text-sm text-slate-500">Touch target tối thiểu 44px · focus ring rõ</p>
            </div>
            <div class="mt-6 flex flex-wrap items-center gap-3">
                <x-ui.button>Tiếp tục học <span aria-hidden="true">→</span></x-ui.button>
                <x-ui.button variant="secondary">Xem chi tiết</x-ui.button>
                <x-ui.button variant="quiet" size="sm">Hủy</x-ui.button>
                <x-ui.button variant="danger" size="sm">Xóa bản nháp</x-ui.button>
            </div>
            <div class="mt-5 flex flex-wrap gap-2"><x-ui.badge>Draft</x-ui.badge><x-ui.badge variant="brand">In review</x-ui.badge><x-ui.badge variant="success">Published</x-ui.badge><x-ui.badge variant="warning">Saving</x-ui.badge><x-ui.badge variant="danger">Error</x-ui.badge></div>
        </x-ui.card>

        <section class="grid gap-6 lg:grid-cols-2">
            <x-ui.card>
                <h2 class="text-xl font-semibold">Form primitives</h2>
                <p class="mt-2 text-sm text-slate-600">Label, hint, validation và focus phải nhất quán ở mobile lẫn desktop.</p>
                <div class="mt-6 space-y-4"><x-ui.input label="Tên bài học" value="Máy bay đi vào vùng radar" hint="Tên hiển thị cho học sinh."/><x-ui.input label="Slug" value="radar-may-bay" error="Slug này đã được sử dụng."/></div>
            </x-ui.card>
            <x-ui.card>
                <h2 class="text-xl font-semibold">Progress & feedback</h2>
                <div class="mt-6 space-y-5"><x-ui.progress label="Tiến độ bài học" :value="58"/><x-ui.progress label="Mô hình 2D" :value="82"/><x-ui.alert title="Đã lưu" variant="success">Thay đổi của bạn đã được đồng bộ với máy chủ.</x-ui.alert><x-ui.alert title="Mất kết nối" variant="warning">Đáp án đang chờ đồng bộ; đừng đóng trang.</x-ui.alert></div>
            </x-ui.card>
        </section>

        <section id="states" class="grid gap-6 lg:grid-cols-3">
            <x-ui.card><div class="flex items-center justify-between"><h2 class="font-semibold">Loading</h2><x-ui.badge variant="neutral">Skeleton</x-ui.badge></div><div class="mt-5 space-y-3"><x-ui.skeleton class="h-5 w-2/3"/><x-ui.skeleton class="h-4 w-full"/><x-ui.skeleton class="h-4 w-5/6"/></div></x-ui.card>
            <x-ui.card><h2 class="font-semibold">Empty</h2><div class="mt-5 rounded-2xl border border-dashed border-slate-300 p-6 text-center"><div class="mx-auto flex size-12 items-center justify-center rounded-2xl bg-brand-50 text-xl text-brand-600">◎</div><p class="mt-3 font-semibold">Chưa có bài ôn tập</p><p class="mt-1 text-sm text-slate-500">Hoàn thành một bài để tạo danh sách ôn lại.</p><x-ui.button size="sm" variant="secondary" class="mt-4">Khám phá bài học</x-ui.button></div></x-ui.card>
            <x-ui.card><h2 class="font-semibold">Error & retry</h2><div class="mt-5"><x-ui.alert title="Không tải được dữ liệu" variant="danger">Hãy thử lại. Tiến độ cục bộ vẫn được giữ an toàn.</x-ui.alert><x-ui.button size="sm" class="mt-4">Thử lại</x-ui.button></div></x-ui.card>
        </section>

        <footer class="flex flex-wrap items-center justify-between gap-3 border-t border-slate-200 pt-6 text-sm text-slate-500"><span>Light mode · reduced motion · WCAG baseline</span><a href="{{ route('ui.prototype') }}" class="font-semibold text-brand-600">Xem prototype shell →</a></footer>
    </div>
</x-layouts.app>
