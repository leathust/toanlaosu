<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import TeacherLayout from '../../Layouts/TeacherLayout.vue';

interface Scene { id: number; public_id: string; name: string; engine: string; scene_key: string; status: string; }
interface SceneVersion { id: number; version: number; schema_version: string; config: unknown[] | Record<string, unknown>; steps: unknown[]; allowed_commands: string[]; status: string; published_at: string | null; }
interface SceneForm { version: number; name: string; scene_key: string; config: Record<string, unknown>; steps: unknown[]; allowed_commands: string[]; }

const props = defineProps<{ scene: Scene; version: SceneVersion }>();
const form = useForm<any>({
    version: props.version.version,
    name: props.scene.name,
    scene_key: props.scene.scene_key,
    config: (Array.isArray(props.version.config) ? {} : props.version.config) as Record<string, unknown>,
    steps: props.version.steps,
    allowed_commands: props.version.allowed_commands,
});
const configText = ref(JSON.stringify(form.config, null, 2));
const stepsText = ref(JSON.stringify(form.steps, null, 2));
const commandsText = ref(form.allowed_commands.join(', '));

function save(): void {
    try {
        form.config = configText.value.trim() ? JSON.parse(configText.value) : {};
        form.steps = stepsText.value.trim() ? JSON.parse(stepsText.value) : [];
        form.allowed_commands = commandsText.value.split(',').map((command: string) => command.trim()).filter(Boolean);
    } catch {
        form.setError('config', 'Scene config và steps phải là JSON hợp lệ.');
        return;
    }
    form.patch(`/teacher/scenes/${props.scene.id}/versions/${props.version.version}`);
}
</script>

<template>
    <TeacherLayout>
        <div class="grid gap-6">
            <header class="flex flex-wrap items-start justify-between gap-4"><div class="grid gap-2"><a href="/teacher" class="text-sm font-semibold text-brand-700">← Teacher workspace</a><p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Scene editor · {{ scene.public_id }} · draft v{{ version.version }}</p><h1 class="text-3xl font-semibold tracking-tight text-slate-950">Chỉnh sửa mô hình 2D</h1></div><span class="rounded-full bg-amber-50 px-3 py-1 text-sm font-semibold text-amber-700">{{ version.status }}</span></header>
            <form class="grid gap-5 rounded-3xl border border-slate-200 bg-white p-6 shadow-sm" @submit.prevent="save">
                <div class="grid gap-5 md:grid-cols-2"><label class="grid gap-2 text-sm font-semibold text-slate-700">Tên scene<input v-model="form.name" class="min-h-11 rounded-xl border border-slate-300 px-3 font-normal text-slate-950" maxlength="255" /></label><label class="grid gap-2 text-sm font-semibold text-slate-700">Scene key<input v-model="form.scene_key" class="min-h-11 rounded-xl border border-slate-300 px-3 font-normal text-slate-950" maxlength="100" /></label></div>
                <label class="grid gap-2 text-sm font-semibold text-slate-700">Config (JSON)<textarea v-model="configText" class="min-h-48 rounded-xl border border-slate-300 px-3 py-2 font-mono text-xs font-normal text-slate-950" /></label><label class="grid gap-2 text-sm font-semibold text-slate-700">Steps (JSON)<textarea v-model="stepsText" class="min-h-40 rounded-xl border border-slate-300 px-3 py-2 font-mono text-xs font-normal text-slate-950" /></label><label class="grid gap-2 text-sm font-semibold text-slate-700">Allowed commands<input v-model="commandsText" class="min-h-11 rounded-xl border border-slate-300 px-3 font-normal text-slate-950" /></label>
                <p v-if="form.errors.config" class="text-sm text-danger-600">{{ form.errors.config }}</p><div class="flex flex-wrap items-center justify-between gap-3 border-t border-slate-100 pt-4"><p class="text-sm text-slate-500" aria-live="polite">{{ form.processing ? 'Đang lưu…' : 'Đang sửa đúng draft version này; bản publish là bất biến.' }}</p><button type="submit" class="min-h-11 rounded-xl bg-brand-600 px-5 text-sm font-semibold text-white disabled:opacity-50" :disabled="form.processing">{{ form.processing ? 'Đang lưu…' : 'Lưu draft' }}</button></div>
            </form>
        </div>
    </TeacherLayout>
</template>
