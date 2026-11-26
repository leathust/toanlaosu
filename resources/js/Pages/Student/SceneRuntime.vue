<script setup lang="ts">
import { computed, onBeforeUnmount, onMounted } from 'vue';
import { createSceneController } from '../../Scenes/sceneController';
import type { PersistedScene } from '../../Scenes/sceneSchema';

const props = defineProps<{ scene: PersistedScene }>();
const controller = createSceneController(props.scene.config.type);
const state = controller.state;
const progress = computed(() => Math.round(state.progress * 100));
let frame = 0;
let previous = 0;

function tick(timestamp: number): void {
    if (state.playing) {
        const elapsed = previous === 0 ? 0 : timestamp - previous;
        controller.seek(state.progress + elapsed / 10000);
    }
    previous = timestamp;
    frame = requestAnimationFrame(tick);
}

onMounted(() => { frame = requestAnimationFrame(tick); });
onBeforeUnmount(() => cancelAnimationFrame(frame));

function toggle(): void {
    if (state.playing) controller.pause(); else controller.play();
}

function seek(event: Event): void {
    controller.seek(Number((event.target as HTMLInputElement).value) / 100);
}
</script>

<template>
    <section class="grid gap-4 rounded-3xl border border-indigo-100 bg-white p-5 shadow-sm" :aria-label="scene.name">
        <div class="flex items-start justify-between gap-4">
            <div><p class="text-xs font-semibold uppercase tracking-[0.18em] text-indigo-600">Scene 2D · {{ scene.schema_version }}</p><h2 class="mt-2 text-xl font-semibold text-slate-950">{{ scene.name }}</h2></div>
            <span class="rounded-full bg-emerald-50 px-3 py-1 text-xs font-semibold text-emerald-700">{{ state.playing ? 'Đang chạy' : 'Tạm dừng' }}</span>
        </div>
        <div class="grid min-h-56 place-items-center overflow-hidden rounded-2xl bg-slate-950 p-4" role="img" :aria-label="`Mô hình ${scene.config.type}`">
            <svg viewBox="0 0 100 70" class="h-full max-h-64 w-full" aria-hidden="true">
                <defs><pattern id="scene-grid" width="10" height="10" patternUnits="userSpaceOnUse"><path d="M 10 0 L 0 0 0 10" fill="none" stroke="#64748b" stroke-width=".25" /></pattern></defs>
                <rect width="100" height="70" fill="url(#scene-grid)" opacity=".5" />
                <circle v-if="scene.config.type === 'radar'" cx="50" cy="35" r="20" fill="#6366f1" fill-opacity=".18" stroke="#a5b4fc" />
                <path v-else d="M15 58 H70 V18 H85" fill="none" stroke="#fbbf24" stroke-width="1.5" stroke-dasharray="3 2" />
                <circle :cx="12 + progress * 0.76" :cy="72 - Math.sin(state.progress * Math.PI) * 42" r="2.6" fill="#fb7185" stroke="white" stroke-width=".8" />
            </svg>
        </div>
        <div class="flex flex-wrap items-center gap-3">
            <button type="button" class="min-h-11 rounded-xl bg-indigo-600 px-4 text-sm font-semibold text-white" @click="toggle">{{ state.playing ? 'P⏸ Tạm dừng' : '▶ Chạy' }}</button>
            <button type="button" class="min-h-11 rounded-xl border border-slate-200 px-4 text-sm font-semibold text-slate-700" @click="controller.reset">↺ Đặt lại</button>
            <label class="flex min-h-11 min-w-48 flex-1 items-center gap-3 text-xs text-slate-500"><span class="sr-only">Tiến trình mô phỏng</span><input type="range" min="0" max="100" :value="progress" class="w-full accent-indigo-600" @input="seek" /><span>{{ progress }}%</span></label>
        </div>
        <p class="text-xs text-slate-500">Fallback: {{ scene.fallback ? 'có ảnh/text dự phòng' : 'mô tả SVG động' }}</p>
    </section>
</template>
