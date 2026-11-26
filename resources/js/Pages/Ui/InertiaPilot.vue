<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import StudentLayout from '../../Layouts/StudentLayout.vue';
import type { SharedPageProps } from '../../types';

defineProps<{
    pilot: {
        title: string;
        status: string;
    };
}>();

const page = usePage<SharedPageProps>();
</script>

<template>
    <StudentLayout>
        <section class="space-y-8">
            <header class="max-w-3xl">
                <p class="text-xs font-semibold uppercase tracking-[0.18em] text-brand-600">APP-002 · Frontend foundation</p>
                <h1 class="mt-3 text-3xl font-semibold tracking-tight sm:text-4xl">{{ pilot.title }}</h1>
                <p class="mt-4 text-base leading-7 text-slate-600">
                    Đây là page pilot Inertia/Vue/TypeScript. Shared props và workspace layout đã được truyền từ Laravel.
                </p>
            </header>

            <div class="grid gap-4 md:grid-cols-3">
                <article class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
                    <p class="text-sm text-slate-500">App name</p>
                    <p class="mt-2 font-semibold">{{ page.props.app.name }}</p>
                </article>
                <article class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
                    <p class="text-sm text-slate-500">Workspace</p>
                    <p class="mt-2 font-semibold">Student</p>
                </article>
                <article class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
                    <p class="text-sm text-slate-500">Pilot status</p>
                    <p class="mt-2 font-semibold text-emerald-700">{{ pilot.status }}</p>
                </article>
            </div>

            <div class="rounded-3xl border border-brand-200 bg-brand-50 p-5 text-sm leading-6 text-brand-950">
                Inertia response thành công. Các màn hình nghiệp vụ hiện tại vẫn giữ Blade fallback; vertical slice thật sẽ bắt đầu sau APP-002.
            </div>
        </section>
    </StudentLayout>
</template>
