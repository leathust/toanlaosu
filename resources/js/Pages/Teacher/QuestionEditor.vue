<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import TeacherLayout from '../../Layouts/TeacherLayout.vue';

interface Question { id: number; public_id: string; question_type: string; difficulty: string; status: string; }
interface QuestionVersion { id: number; version: number; stem: string; solution: string | null; solution_blocks: unknown[] | null; status: string; published_at: string | null; }
interface QuestionForm { version: number; stem: string; solution: string; solution_blocks: unknown[]; }

const props = defineProps<{ question: Question; version: QuestionVersion }>();
const form = useForm<any>({
    version: props.version.version,
    stem: props.version.stem,
    solution: props.version.solution ?? '',
    solution_blocks: props.version.solution_blocks ?? [],
});
const blocksText = ref(JSON.stringify(form.solution_blocks, null, 2));

function save(): void {
    try {
        form.solution_blocks = blocksText.value.trim() ? JSON.parse(blocksText.value) : [];
    } catch {
        form.setError('solution_blocks', 'Solution blocks phải là JSON hợp lệ.');
        return;
    }
    form.patch(`/teacher/questions/${props.question.id}/versions/${props.version.version}`);
}
</script>

<template>
    <TeacherLayout>
        <div class="grid gap-6">
            <header class="flex flex-wrap items-start justify-between gap-4">
                <div class="grid gap-2">
                    <a href="/teacher" class="text-sm font-semibold text-brand-700">← Teacher workspace</a>
                    <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Question editor · {{ question.public_id }} · draft v{{ version.version }}</p>
                    <h1 class="text-3xl font-semibold tracking-tight text-slate-950">Chỉnh sửa câu hỏi</h1>
                </div>
                <span class="rounded-full bg-amber-50 px-3 py-1 text-sm font-semibold text-amber-700">{{ version.status }}</span>
            </header>
            <form class="grid gap-5 rounded-3xl border border-slate-200 bg-white p-6 shadow-sm" @submit.prevent="save">
                <label class="grid gap-2 text-sm font-semibold text-slate-700">Nội dung câu hỏi<textarea v-model="form.stem" class="min-h-36 rounded-xl border border-slate-300 px-3 py-2 font-normal text-slate-950" maxlength="10000" /></label>
                <label class="grid gap-2 text-sm font-semibold text-slate-700">Lời giải<textarea v-model="form.solution" class="min-h-36 rounded-xl border border-slate-300 px-3 py-2 font-normal text-slate-950" maxlength="30000" /></label>
                <label class="grid gap-2 text-sm font-semibold text-slate-700">Solution blocks (JSON)<textarea v-model="blocksText" class="min-h-48 rounded-xl border border-slate-300 px-3 py-2 font-mono text-xs font-normal text-slate-950" /></label>
                <p v-if="form.errors.solution_blocks" class="text-sm text-danger-600">{{ form.errors.solution_blocks }}</p>
                <div class="flex flex-wrap items-center justify-between gap-3 border-t border-slate-100 pt-4"><p class="text-sm text-slate-500" aria-live="polite">{{ form.processing ? 'Đang lưu…' : 'Đang sửa đúng draft version này; bản publish là bất biến.' }}</p><button type="submit" class="min-h-11 rounded-xl bg-brand-600 px-5 text-sm font-semibold text-white disabled:opacity-50" :disabled="form.processing">{{ form.processing ? 'Đang lưu…' : 'Lưu draft' }}</button></div>
            </form>
        </div>
    </TeacherLayout>
</template>
