<script setup lang="ts">
import axios from 'axios';
import { onBeforeUnmount, ref, watch } from 'vue';
import TeacherLayout from '../../Layouts/TeacherLayout.vue';

interface Lesson { id: number; slug: string; title: string; summary: string | null; status: string; lock_version: number; published_at: string | null; }
interface Course { id: number; slug: string; title: string; }
interface Revision { id: number; version: number; snapshot: Record<string, unknown>; change_summary: string | null; created_at: string; }

const props = defineProps<{ course: Course; lesson: Lesson; revisions: Revision[] }>();
const form = ref({ title: props.lesson.title, summary: props.lesson.summary ?? '' });
const lockVersion = ref(props.lesson.lock_version);
const saveState = ref<'saved' | 'dirty' | 'saving' | 'conflict' | 'error'>('saved');
const serverDraft = ref<{ title: string; summary: string | null; lock_version: number } | null>(null);
const preview = ref(false);
const publishing = ref(false);
let saveTimer: ReturnType<typeof setTimeout> | undefined;

function scheduleSave(): void {
    saveState.value = 'dirty';
    if (saveTimer) clearTimeout(saveTimer);
    saveTimer = setTimeout(() => { void saveDraft(); }, 600);
}

async function saveDraft(): Promise<void> {
    saveState.value = 'saving';
    try {
        const response = await axios.patch(`/teacher/lessons/${props.lesson.id}`, { ...form.value, lock_version: lockVersion.value });
        lockVersion.value = response.data.data.lock_version;
        saveState.value = 'saved';
    } catch (error: unknown) {
        if (axios.isAxiosError(error) && error.response?.status === 409) {
            serverDraft.value = error.response.data.data ?? null;
            saveState.value = 'conflict';
        }
        else saveState.value = 'error';
    }
}

async function publish(): Promise<void> {
    publishing.value = true;
    try {
        await axios.post(`/teacher/lessons/${props.lesson.id}/publish`);
        window.location.reload();
    } finally {
        publishing.value = false;
    }
}

function reloadLatest(): void { window.location.reload(); }

async function duplicateDraft(): Promise<void> {
    const response = await axios.post(`/teacher/lessons/${props.lesson.id}/duplicate`);
    window.location.href = `/teacher/lessons/${response.data.data.id}/edit`;
}

watch(form, scheduleSave, { deep: true });
onBeforeUnmount(() => { if (saveTimer) clearTimeout(saveTimer); });
</script>

<template>
    <TeacherLayout>
        <div class="grid gap-6">
            <header class="flex flex-wrap items-start justify-between gap-4">
                <div class="grid gap-2">
                    <a :href="`/teacher/courses/${course.id}`" class="text-sm font-semibold text-brand-700">← {{ course.title }}</a>
                    <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Teacher editor · {{ lesson.slug }}</p>
                    <h1 class="text-3xl font-semibold tracking-tight text-slate-950">Chỉnh sửa bài học</h1>
                </div>
                <div class="flex items-center gap-3">
                    <span class="text-sm text-slate-500" aria-live="polite">
                        {{ saveState === 'saved' ? 'Đã lưu' : saveState === 'saving' ? 'Đang lưu…' : saveState === 'dirty' ? 'Chờ lưu…' : saveState === 'conflict' ? 'Có xung đột' : 'Lỗi lưu' }}
                    </span>
                    <button type="button" class="min-h-11 rounded-xl bg-brand-600 px-4 text-sm font-semibold text-white disabled:opacity-50" :disabled="publishing || saveState === 'conflict'" @click="publish">{{ publishing ? 'Đang xuất bản…' : 'Xuất bản' }}</button>
                </div>
            </header>

            <div v-if="saveState === 'conflict'" role="alert" class="grid gap-4 rounded-2xl border border-danger-600/30 bg-danger-50 p-4 text-sm text-danger-600">
                <div class="flex flex-wrap items-center justify-between gap-3"><span>Bản nháp đã thay đổi ở nơi khác. Không ghi đè im lặng.</span><div class="flex flex-wrap gap-2"><button type="button" class="rounded-lg border border-danger-600/40 px-3 py-2 font-semibold" @click="reloadLatest">Tải lại</button><button type="button" class="rounded-lg bg-danger-600 px-3 py-2 font-semibold text-white" @click="duplicateDraft">Tạo draft copy</button></div></div>
                <div v-if="serverDraft" class="grid gap-3 rounded-xl border border-danger-600/20 bg-white/70 p-3 text-slate-700 sm:grid-cols-2"><div><p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Bản đang sửa</p><p class="mt-1 font-medium">{{ form.title }}</p><p class="text-xs">{{ form.summary || 'Chưa có tóm tắt.' }}</p></div><div><p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Bản trên server · v{{ serverDraft.lock_version }}</p><p class="mt-1 font-medium">{{ serverDraft.title }}</p><p class="text-xs">{{ serverDraft.summary || 'Chưa có tóm tắt.' }}</p></div></div>
            </div>

            <main class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_20rem]">
                <section class="grid gap-5 rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                    <label class="grid gap-2 text-sm font-semibold text-slate-700">Tiêu đề<input v-model="form.title" class="min-h-11 rounded-xl border border-slate-300 px-3 font-normal text-slate-950" maxlength="255" /></label>
                    <label class="grid gap-2 text-sm font-semibold text-slate-700">Tóm tắt<textarea v-model="form.summary" class="min-h-32 rounded-xl border border-slate-300 px-3 py-2 font-normal text-slate-950" maxlength="2000" /></label>
                    <div class="flex items-center justify-between gap-3 border-t border-slate-100 pt-4">
                        <span class="text-xs text-slate-500">Lock version {{ lockVersion }}</span>
                        <button type="button" class="rounded-lg border border-slate-300 px-3 py-2 text-sm font-semibold" @click="preview = !preview">{{ preview ? 'Ẩn preview' : 'Xem preview' }}</button>
                    </div>
                    <article v-if="preview" class="grid gap-2 rounded-2xl bg-slate-50 p-5">
                        <h2 class="text-xl font-semibold text-slate-950">{{ form.title }}</h2>
                        <p class="text-sm leading-6 text-slate-600">{{ form.summary || 'Chưa có tóm tắt.' }}</p>
                    </article>
                </section>
                <aside class="grid content-start gap-3 rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
                    <h2 class="font-semibold text-slate-950">Lịch sử phiên bản</h2>
                    <p v-if="!revisions.length" class="text-sm text-slate-500">Chưa có revision.</p>
                    <ol v-else class="grid gap-3">
                        <li v-for="revision in revisions" :key="revision.id" class="rounded-xl bg-slate-50 p-3 text-sm"><strong>v{{ revision.version }}</strong><span class="block text-slate-500">{{ revision.change_summary || 'Cập nhật nội dung' }}</span></li>
                    </ol>
                </aside>
            </main>
        </div>
    </TeacherLayout>
</template>
