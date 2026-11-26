<script setup lang="ts">
import { router, useForm } from '@inertiajs/vue3';
import { computed, reactive, ref } from 'vue';
import AdminLayout from '../../../Layouts/AdminLayout.vue';
import type { AdminAiOperationsProps } from '../../../types/admin';

const props = defineProps<AdminAiOperationsProps>();
const refreshing = ref(false);
const saved = ref(false);
const filterState = reactive({ ...props.filters });
const budgetForm = useForm({ value: props.budget.daily });
const currency = computed(() => props.summary.currency || props.budget.currency);

function applyFilters(): void {
    refreshing.value = true;
    router.get('/admin/ai-operations', { ...filterState }, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
        onFinish: () => { refreshing.value = false; },
    });
}

function saveBudget(): void {
    saved.value = false;
    budgetForm.put('/admin/settings/ai.daily_budget', {
        preserveScroll: true,
        onSuccess: () => { saved.value = true; },
    });
}

function money(value: number): string {
    return new Intl.NumberFormat('en-US', { style: 'currency', currency: currency.value, minimumFractionDigits: 2, maximumFractionDigits: 6 }).format(value);
}

function number(value: number | null): string {
    return value === null ? '—' : new Intl.NumberFormat('en-US').format(value);
}

function stateLabel(state: AdminAiOperationsProps['dataState']): string {
    return { ready: 'Có dữ liệu', degraded: 'Suy giảm', no_traffic: 'Chưa có traffic', outage: 'Có dấu hiệu outage' }[state];
}
</script>

<template>
    <AdminLayout :breadcrumb="[{ label: 'Admin', href: '/admin' }, { label: 'AI operations' }]">
        <div class="grid gap-6">
            <p class="sr-only" id="ai-breakdown-description">AI usage grouped by provider, model and operation</p>
            <section class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm sm:p-7">
                <div class="flex flex-col justify-between gap-4 lg:flex-row lg:items-end">
                    <div>
                        <p class="text-xs font-bold uppercase tracking-[0.2em] text-brand-600">AI control plane</p>
                        <h1 class="mt-2 text-2xl font-bold tracking-tight text-slate-950">Usage, quality và provider health</h1>
                        <p class="mt-2 max-w-2xl text-sm text-slate-500">Dữ liệu đã tổng hợp phía server. Prompt, credential và nội dung chat không xuất hiện trong workspace vận hành.</p>
                    </div>
                    <span class="inline-flex w-fit items-center gap-2 rounded-full px-3 py-1.5 text-xs font-semibold" :class="props.dataState === 'ready' ? 'bg-emerald-50 text-emerald-700' : 'bg-amber-50 text-amber-700'">
                        <span class="size-2 rounded-full bg-current" />{{ stateLabel(props.dataState) }}
                    </span>
                </div>
                <div class="mt-6 grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                    <label class="grid gap-1 text-xs font-semibold text-slate-600">Khoảng thời gian<select v-model="filterState.range" class="mt-1 min-h-10 rounded-xl border-slate-200 text-sm font-normal"><option value="7d">7 ngày</option><option value="30d">30 ngày</option><option value="90d">90 ngày</option></select></label>
                    <label class="grid gap-1 text-xs font-semibold text-slate-600">So sánh<select v-model="filterState.compare" class="mt-1 min-h-10 rounded-xl border-slate-200 text-sm font-normal"><option value="previous">Kỳ trước</option><option value="none">Không so sánh</option></select></label>
                    <label class="grid gap-1 text-xs font-semibold text-slate-600">Course<select v-model="filterState.course" class="mt-1 min-h-10 rounded-xl border-slate-200 text-sm font-normal"><option :value="null">Tất cả course</option><option v-for="course in props.filterOptions.courses" :key="course.id" :value="course.id">{{ course.title }}</option></select></label>
                    <label class="grid gap-1 text-xs font-semibold text-slate-600">Timezone<select v-model="filterState.timezone" class="mt-1 min-h-10 rounded-xl border-slate-200 text-sm font-normal"><option value="Asia/Ho_Chi_Minh">Asia/Ho_Chi_Minh</option><option value="Asia/Bangkok">Asia/Bangkok</option><option value="UTC">UTC</option></select></label>
                    <label class="grid gap-1 text-xs font-semibold text-slate-600">Provider<select v-model="filterState.provider" class="mt-1 min-h-10 rounded-xl border-slate-200 text-sm font-normal"><option value="">Tất cả provider</option><option v-for="provider in props.filterOptions.providers" :key="provider" :value="provider">{{ provider }}</option></select></label>
                    <label class="grid gap-1 text-xs font-semibold text-slate-600">Model<select v-model="filterState.model" class="mt-1 min-h-10 rounded-xl border-slate-200 text-sm font-normal"><option value="">Tất cả model</option><option v-for="model in props.filterOptions.models" :key="model" :value="model">{{ model }}</option></select></label>
                    <label class="grid gap-1 text-xs font-semibold text-slate-600">Operation<select v-model="filterState.operation" class="mt-1 min-h-10 rounded-xl border-slate-200 text-sm font-normal"><option value="">Tất cả operation</option><option v-for="operation in props.filterOptions.operations" :key="operation" :value="operation">{{ operation }}</option></select></label>
                    <button type="button" class="min-h-10 self-end rounded-xl bg-brand-600 px-4 text-sm font-semibold text-white hover:bg-brand-700 disabled:opacity-60" :disabled="refreshing" @click="applyFilters">{{ refreshing ? 'Đang tải…' : 'Áp dụng bộ lọc' }}</button>
                </div>
            </section>

            <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-6">
                <article v-for="card in [
                    { label: 'Requests', value: number(props.summary.requests), tone: 'text-slate-950' },
                    { label: 'Tokens', value: number(props.summary.tokens), tone: 'text-slate-950' },
                    { label: 'Cost', value: money(props.summary.cost), tone: 'text-brand-700' },
                    { label: 'Success rate', value: `${props.summary.successRate}%`, tone: 'text-emerald-700' },
                    { label: 'Latency p50', value: `${number(props.summary.latencyP50)} ms`, tone: 'text-slate-950' },
                    { label: 'Latency p95', value: `${number(props.summary.latencyP95)} ms`, tone: 'text-amber-700' },
                ]" :key="card.label" class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">{{ card.label }}</p><p class="mt-3 text-2xl font-bold" :class="card.tone">{{ card.value }}</p>
                </article>
            </section>

            <section class="grid gap-6 xl:grid-cols-[1.6fr_1fr]">
                <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
                    <div class="flex items-center justify-between"><div><h2 class="font-bold text-slate-950">Trend theo ngày</h2><p class="mt-1 text-xs text-slate-500">Requests, tokens và cost được tính ở server.</p></div><span class="text-xs text-slate-400">{{ props.summary.window.from.slice(0, 10) }} → {{ props.summary.window.to.slice(0, 10) }}</span></div>
                    <div class="mt-5 grid gap-2" role="img" aria-label="AI usage trend">
                        <div v-for="day in props.trend" :key="day.label" class="grid grid-cols-[5rem_1fr_5rem] items-center gap-3 text-xs"><span class="text-slate-500">{{ day.label.slice(5) }}</span><div class="h-2 overflow-hidden rounded-full bg-slate-100"><div class="h-full rounded-full bg-brand-500" :style="{ width: `${Math.min(100, (day.requests / Math.max(1, props.summary.requests)) * 100 * props.trend.length)}%` }" /></div><span class="text-right font-semibold text-slate-700">{{ day.requests }}</span></div>
                    </div>
                </article>
                <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6"><div class="flex items-start justify-between gap-3"><div><h2 class="font-bold text-slate-950">Provider health</h2><p class="mt-1 text-xs text-slate-500">{{ props.providerHealth.provider }}</p></div><span class="rounded-full px-2.5 py-1 text-xs font-bold" :class="props.providerHealth.status === 'healthy' ? 'bg-emerald-50 text-emerald-700' : 'bg-amber-50 text-amber-700'">{{ props.providerHealth.status }}</span></div><div class="mt-6 grid grid-cols-2 gap-3 text-sm"><div class="rounded-xl bg-slate-50 p-3"><span class="block text-xs text-slate-500">Failures</span><strong>{{ props.providerHealth.failures }}</strong></div><div class="rounded-xl bg-slate-50 p-3"><span class="block text-xs text-slate-500">Threshold</span><strong>{{ props.providerHealth.threshold }}</strong></div></div><a :href="props.providerHealth.actionHref" class="mt-5 inline-flex text-sm font-semibold text-brand-700 hover:underline">Mở Action Center →</a></article>
            </section>

            <section class="rounded-2xl border border-slate-200 bg-white shadow-sm"><div class="border-b border-slate-100 p-5 sm:p-6"><h2 class="font-bold text-slate-950">Provider / model / operation</h2><p class="mt-1 text-xs text-slate-500">Không tính cost từ formatted string ở client.</p></div><div class="overflow-x-auto"><table class="min-w-full text-left text-sm"><thead class="bg-slate-50 text-xs uppercase tracking-wide text-slate-500"><tr><th class="px-5 py-3">Provider</th><th class="px-5 py-3">Model</th><th class="px-5 py-3">Operation</th><th class="px-5 py-3">Requests</th><th class="px-5 py-3">Success</th><th class="px-5 py-3">Helpfulness</th><th class="px-5 py-3">Tokens</th><th class="px-5 py-3">Cost</th></tr></thead><tbody class="divide-y divide-slate-100"><tr v-for="row in props.breakdown" :key="`${row.provider}-${row.model}-${row.operation}`"><td class="px-5 py-3 font-semibold">{{ row.provider }}</td><td class="px-5 py-3 text-slate-600">{{ row.model }}</td><td class="px-5 py-3 text-slate-600">{{ row.operation }}</td><td class="px-5 py-3">{{ row.requests }}</td><td class="px-5 py-3">{{ row.successRate }}%</td><td class="px-5 py-3">{{ row.helpfulness.averageRating ?? '—' }} <span class="text-xs text-slate-400">(n={{ row.helpfulness.sampleSize }})</span></td><td class="px-5 py-3">{{ number(row.tokens) }}</td><td class="px-5 py-3 font-semibold">{{ money(row.cost) }}</td></tr><tr v-if="props.breakdown.length === 0"><td colspan="8" class="px-5 py-10 text-center text-sm text-slate-500">Chưa có dữ liệu trong khoảng đã chọn.</td></tr></tbody></table></div></section>

            <section class="grid gap-6 lg:grid-cols-2">
                <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6"><div class="flex items-center justify-between"><div><h2 class="font-bold text-slate-950">Feedback & citation quality</h2><p class="mt-1 text-xs text-slate-500">Luôn hiển thị sample size để tránh diễn giải quá mức.</p></div><a :href="props.quality.href" class="text-sm font-semibold text-brand-700 hover:underline">Mở mẫu an toàn →</a></div><dl class="mt-5 grid grid-cols-2 gap-3"><div class="rounded-xl bg-slate-50 p-3"><dt class="text-xs text-slate-500">Feedback sample</dt><dd class="mt-1 text-xl font-bold">{{ props.quality.sampleSize }}</dd></div><div class="rounded-xl bg-slate-50 p-3"><dt class="text-xs text-slate-500">Average rating</dt><dd class="mt-1 text-xl font-bold">{{ props.quality.averageRating ?? '—' }}</dd></div><div class="rounded-xl bg-slate-50 p-3"><dt class="text-xs text-slate-500">Positive rate</dt><dd class="mt-1 text-xl font-bold">{{ props.quality.positiveRate === null ? '—' : `${props.quality.positiveRate}%` }}</dd></div><div class="rounded-xl bg-slate-50 p-3"><dt class="text-xs text-slate-500">Citation coverage</dt><dd class="mt-1 text-xl font-bold">{{ props.quality.citationCoverage === null ? '—' : `${props.quality.citationCoverage}%` }}</dd></div></dl></article>
                <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6"><div class="flex items-start justify-between gap-3"><div><h2 class="font-bold text-slate-950">Budget guard</h2><p class="mt-1 text-xs text-slate-500">Thay đổi cần password confirmation và audit log.</p></div><span class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-bold text-slate-700">{{ props.budget.enabled ? 'Enabled' : 'Disabled' }}</span></div><form class="mt-5 flex items-end gap-3" @submit.prevent="saveBudget"><label class="grid flex-1 gap-1 text-xs font-semibold text-slate-600">Daily budget ({{ props.budget.currency }})<input v-model.number="budgetForm.value" type="number" min="0" step="1" class="mt-1 min-h-11 rounded-xl border-slate-200" /></label><button type="submit" class="min-h-11 rounded-xl bg-slate-900 px-4 text-sm font-semibold text-white hover:bg-slate-700 disabled:opacity-60" :disabled="budgetForm.processing">{{ budgetForm.processing ? 'Đang lưu…' : 'Lưu' }}</button></form><p v-if="saved" class="mt-3 text-xs font-semibold text-emerald-700">Đã ghi nhận thay đổi.</p><p v-if="budgetForm.errors.value" class="mt-3 text-xs font-semibold text-red-700">{{ budgetForm.errors.value }}</p></article>
            </section>

            <section class="rounded-2xl border border-slate-200 bg-white shadow-sm"><div class="flex items-center justify-between border-b border-slate-100 p-5 sm:p-6"><div><h2 class="font-bold text-slate-950">Failure explorer</h2><p class="mt-1 text-xs text-slate-500">Chỉ hiển thị error code và thông báo đã redacted.</p></div><a href="/admin/action-center?source=ai_provider" class="text-sm font-semibold text-brand-700 hover:underline">Action Center →</a></div><div class="divide-y divide-slate-100"><div v-for="failure in props.failures" :key="failure.id" class="flex flex-col gap-3 p-5 sm:flex-row sm:items-center sm:justify-between"><div><p class="font-semibold text-slate-900">{{ failure.message }}</p><p class="mt-1 text-xs text-slate-500">{{ failure.errorCode }} · {{ failure.provider }}/{{ failure.model }} · {{ failure.operation }}</p></div><a :href="failure.actionHref" class="text-sm font-semibold text-brand-700 hover:underline">Xem sự cố</a></div><p v-if="props.failures.length === 0" class="p-8 text-center text-sm text-slate-500">Không có failure trong khoảng đã chọn.</p></div></section>
        </div>
    </AdminLayout>
</template>
