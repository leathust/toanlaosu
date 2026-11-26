<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { ref } from 'vue';
import StudentLayout from '../../Layouts/StudentLayout.vue';
import SceneRuntime from './SceneRuntime.vue';
import type { SceneCommand } from '../../Scenes/sceneSchema';

interface Option { key: string; content: string; }
interface Question { id: number; stem: string; type: string; options: Option[]; }
interface Scene { public_id: string; name: string; scene_key: string; schema_version: string; config: { type: 'radar' | 'ant' | 'graph' | 'surface'; parameters: Record<string, unknown> }; steps: Array<Record<string, unknown>>; allowed_commands: SceneCommand[]; fallback: { disk: string | null; path: string | null } | null; }
interface Block { id: number; type: string; body: string | null; settings: Record<string, unknown> | null; completed: boolean; question: Question | null; scene: Scene | null; }

const props = defineProps<{ course: { slug: string; title: string }; lesson: { slug: string; title: string; summary: string | null; blocks: Block[] } }>();
const selected = ref<Record<number, string>>({});
const busy = ref<number | null>(null);

function complete(block: Block): void {
    busy.value = block.id;
    router.post(`/student/lessons/blocks/${block.id}/complete`, {}, { preserveScroll: true, onFinish: () => { busy.value = null; block.completed = true; } });
}

function submitQuestion(block: Block): void {
    if (!block.question || !selected.value[block.id]) return;
    router.post('/student/practice/attempts', { question_version_id: block.question.id, lesson_block_id: block.id, answer: selected.value[block.id] }, { preserveScroll: true });
}
</script>

<template>
    <StudentLayout>
        <div class="grid gap-6">
            <header class="grid gap-3">
                <a :href="`/student/learn`" class="text-sm font-semibold text-brand-700">← {{ course.title }}</a>
                <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Bài học thật · {{ lesson.slug }}</p>
                <h1 class="text-3xl font-semibold tracking-tight text-slate-950">{{ lesson.title }}</h1>
                <p v-if="lesson.summary" class="max-w-3xl text-sm leading-6 text-slate-600">{{ lesson.summary }}</p>
            </header>
            <main class="grid gap-5">
                <article v-for="block in lesson.blocks" :key="block.id" class="grid gap-4 rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                    <div class="flex items-center justify-between gap-4">
                        <span class="text-xs font-semibold uppercase tracking-wide text-brand-700">{{ block.type }}</span>
                        <span v-if="block.completed" class="text-xs font-semibold text-emerald-600">Đã hoàn thành</span>
                    </div>
                    <div v-if="block.type === 'note' || block.type === 'text'" class="prose prose-slate max-w-none text-sm leading-7" v-html="block.body || ''" />
                    <SceneRuntime v-else-if="block.type === 'visual' && block.scene" :scene="block.scene" />
                    <div v-else-if="block.type === 'question' && block.question" class="grid gap-4">
                        <h2 class="text-lg font-semibold text-slate-950">{{ block.question.stem }}</h2>
                        <label v-for="option in block.question.options" :key="option.key" class="flex cursor-pointer gap-3 rounded-2xl border border-slate-200 p-4 hover:border-brand-300">
                            <input v-model="selected[block.id]" type="radio" :name="`question-${block.id}`" :value="option.key" class="mt-1 text-brand-600" />
                            <span class="text-sm text-slate-700">{{ option.content }}</span>
                        </label>
                        <button type="button" class="min-h-11 w-fit rounded-xl bg-brand-600 px-4 text-sm font-semibold text-white disabled:opacity-50" :disabled="!selected[block.id]" @click="submitQuestion(block)">Nộp câu trả lời</button>
                    </div>
                    <div v-else class="rounded-2xl bg-slate-50 p-5 text-sm leading-6 text-slate-600">Block {{ block.type }} đã được tải từ dữ liệu bài học.</div>
                    <button v-if="block.type !== 'question' && !block.completed" type="button" class="min-h-11 w-fit rounded-xl border border-brand-200 px-4 text-sm font-semibold text-brand-700 disabled:opacity-50" :disabled="busy === block.id" @click="complete(block)">{{ busy === block.id ? 'Đang lưu…' : 'Đánh dấu hoàn thành' }}</button>
                </article>
            </main>
        </div>
    </StudentLayout>
</template>
