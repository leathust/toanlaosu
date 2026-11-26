<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { onBeforeUnmount, onMounted, ref } from 'vue';
import AdminLayout from '../../../Layouts/AdminLayout.vue';
import type { AdminExamOperationsProps } from '../../../types/admin';

const props = defineProps<AdminExamOperationsProps>();
const localFilters = ref({ ...props.filters });
const refreshing = ref(false);
const polling = ref(true);
let timer: ReturnType<typeof setTimeout> | null = null;
let pollDelay = 5000;

function activeFilters(page?: number): Record<string, string | number> {
    const filters: Record<string, string | number> = {};
    if (localFilters.value.status) filters.status = localFilters.value.status;
    if (localFilters.value.issue) filters.issue = localFilters.value.issue;
    if (page) filters.page = page;
    return filters;
}

function applyFilters(): void {
    router.get('/admin/exams', activeFilters(), { preserveState: true, preserveScroll: true, replace: true });
}

function refresh(): void {
    if (document.hidden || !polling.value) return;
    refreshing.value = true;
    router.reload({ only: ['serverNow', 'examStatuses', 'attemptStatuses', 'issues', 'attempts', 'exportJobs'], onSuccess: () => { pollDelay = 5000; }, onError: () => { pollDelay = Math.min(pollDelay * 2, 30000); }, onFinish: () => { refreshing.value = false; schedulePoll(); } });
}

function schedulePoll(): void {
    if (timer) clearTimeout(timer);
    if (!polling.value || document.hidden) return;
    timer = setTimeout(refresh, pollDelay);
}

function handleVisibility(): void {
    if (document.hidden) {
        if (timer) clearTimeout(timer);
        timer = null;
    } else {
        pollDelay = 5000;
        schedulePoll();
    }
}

function formatDate(value: string | null): string {
    return value ? new Date(value).toLocaleString('vi-VN', { dateStyle: 'short', timeStyle: 'short' }) : '—';
}

function issueLabel(issue: string | null): string {
    return issue === 'overdue' ? 'Quá deadline' : issue === 'stuck' ? 'Stuck' : 'Bình thường';
}

onMounted(() => { document.addEventListener('visibilitychange', handleVisibility); schedulePoll(); });
onBeforeUnmount(() => { polling.value = false; if (timer) clearTimeout(timer); document.removeEventListener('visibilitychange', handleVisibility); });
</script>

<template>
    <AdminLayout :breadcrumb="[{ label: 'Admin', href: '/admin' }, { label: 'Exams' }]">
        <div class="grid gap-6">
            <header class="flex flex-col justify-between gap-4 sm:flex-row sm:items-end"><div><p class="text-sm font-semibold text-brand-700">Exam operations</p><h1 class="mt-1 text-3xl font-semibold tracking-tight">Live exam health</h1><p class="mt-2 max-w-2xl text-sm text-slate-500">Server time: {{ formatDate(props.serverNow) }} · polling tự dừng khi tab hidden.</p></div><button type="button" class="min-h-11 rounded-xl border border-slate-200 bg-white px-4 text-sm font-semibold text-slate-700 shadow-sm disabled:opacity-50" :disabled="refreshing" @click="refresh">{{ refreshing ? 'Đang tải…' : 'Làm mới' }}</button></header>
            <section class="grid gap-3 sm:grid-cols-3" aria-label="Exam status overview"><article v-for="(value, key) in props.examStatuses" :key="key" class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm"><p class="text-sm text-slate-500">{{ key }}</p><p class="mt-2 text-3xl font-semibold text-slate-950">{{ value }}</p></article></section>
            <section class="grid gap-3 sm:grid-cols-2 xl:grid-cols-5" aria-label="Exam incident signals"><article v-for="(value, key) in props.issues" :key="key" class="rounded-2xl border p-4 shadow-sm" :class="value ? 'border-amber-200 bg-amber-50' : 'border-slate-200 bg-white'"><p class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ key.replaceAll('_', ' ') }}</p><p class="mt-2 text-2xl font-semibold text-slate-950">{{ value }}</p></article></section>
            <section class="flex flex-wrap items-center gap-3 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm"><select v-model="localFilters.status" class="min-h-11 rounded-xl border-slate-200 text-sm"><option value="">Tất cả attempt status</option><option v-for="status in props.filterOptions.statuses" :key="status" :value="status">{{ status }}</option></select><select v-model="localFilters.issue" class="min-h-11 rounded-xl border-slate-200 text-sm"><option value="">Tất cả incident</option><option v-for="issue in props.filterOptions.issues" :key="issue" :value="issue">{{ issue }}</option></select><button type="button" class="min-h-11 rounded-xl bg-brand-600 px-4 text-sm font-semibold text-white" @click="applyFilters">Áp dụng</button></section>
            <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm"><div class="flex items-center justify-between gap-3 border-b border-slate-100 p-5"><div><h2 class="text-lg font-semibold">Attempts</h2><p class="text-sm text-slate-500">{{ props.attempts.meta.total }} attempt · không hiển thị answer hoặc IP/user-agent.</p></div><span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold">Page {{ props.attempts.meta.currentPage }}/{{ props.attempts.meta.lastPage }}</span></div><div class="divide-y divide-slate-100"><article v-for="attempt in props.attempts.data" :key="attempt.id" class="flex flex-col justify-between gap-4 p-5 lg:flex-row lg:items-center"><div class="grid gap-1"><div class="flex flex-wrap items-center gap-2"><a :href="attempt.href" class="font-semibold text-brand-700 hover:text-brand-800">{{ attempt.exam.title }}</a><span class="rounded-full bg-slate-100 px-2 py-1 text-[11px] font-semibold">{{ attempt.status }}</span><span v-if="attempt.issue" class="rounded-full bg-danger-50 px-2 py-1 text-[11px] font-semibold text-danger-700">{{ issueLabel(attempt.issue) }}</span></div><p class="text-sm text-slate-600">{{ attempt.learner.name }} · lần {{ attempt.attemptNumber }} · save v{{ attempt.saveVersion }}</p><p class="text-xs text-slate-400">Deadline {{ formatDate(attempt.deadlineAt) }} · event cuối {{ formatDate(attempt.lastEventAt) }}</p></div><a :href="attempt.href" class="rounded-xl border border-slate-200 px-3 py-2 text-sm font-semibold text-slate-700 hover:border-brand-300 hover:text-brand-700">Mở timeline</a></article><p v-if="!props.attempts.data.length" class="p-10 text-center text-sm text-slate-500">Không có attempt phù hợp.</p></div><div v-if="props.attempts.meta.lastPage > 1" class="flex justify-between border-t border-slate-100 p-4"><button type="button" class="rounded-lg border border-slate-200 px-3 py-2 text-sm disabled:opacity-40" :disabled="props.attempts.meta.currentPage <= 1" @click="router.get('/admin/exams', activeFilters(props.attempts.meta.currentPage - 1), { preserveState: true, preserveScroll: true })">Trang trước</button><button type="button" class="rounded-lg border border-slate-200 px-3 py-2 text-sm disabled:opacity-40" :disabled="props.attempts.meta.currentPage >= props.attempts.meta.lastPage" @click="router.get('/admin/exams', activeFilters(props.attempts.meta.currentPage + 1), { preserveState: true, preserveScroll: true })">Trang sau</button></div></section>
            <section class="grid gap-4 lg:grid-cols-[1fr_20rem]"><article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm"><h2 class="text-lg font-semibold">Export/report jobs</h2><div class="mt-4 grid gap-3"><div v-for="job in props.exportJobs" :key="job.id" class="flex flex-wrap items-center justify-between gap-3 rounded-xl border border-slate-100 p-3 text-sm"><div><p class="font-semibold">{{ job.examTitle }} · {{ job.mode }}</p><p class="text-xs text-slate-500">{{ formatDate(job.requestedAt) }}</p></div><a v-if="job.failureHref" :href="job.failureHref" class="text-sm font-semibold text-danger-700">Mở Action Center</a><span v-else class="rounded-full bg-slate-100 px-2 py-1 text-xs">{{ job.status }}</span></div><p v-if="!props.exportJobs.length" class="text-sm text-slate-500">Chưa có export job.</p></div></article><article class="rounded-2xl border border-sky-200 bg-sky-50 p-5 shadow-sm"><p class="text-xs font-semibold uppercase tracking-wide text-sky-700">Exam policy</p><h2 class="mt-2 font-semibold text-slate-950">{{ props.aiPolicy.label }}</h2><p class="mt-2 text-sm text-slate-700">{{ props.aiPolicy.reason }}</p><span class="mt-4 inline-flex rounded-full bg-white px-3 py-1 text-xs font-semibold text-sky-700">{{ props.aiPolicy.state }}</span></article></section>
        </div>
    </AdminLayout>
</template>
