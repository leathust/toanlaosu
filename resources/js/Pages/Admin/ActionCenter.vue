<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import AdminLayout from '../../Layouts/AdminLayout.vue';
import type { AdminActionAlert, AdminActionCenterProps } from '../../types/admin';

const props = defineProps<AdminActionCenterProps>();
const busy = ref<string | null>(null);
const localFilters = ref({ ...props.filters });

const filterEntries = computed(() => Object.fromEntries(Object.entries(localFilters.value).filter(([, value]) => value)));

function applyFilters(): void {
    router.get('/admin/action-center', filterEntries.value, { preserveState: true, replace: true });
}

function updateState(alert: AdminActionAlert, status: 'acknowledge' | 'resolve' | 'retry'): void {
    if (!window.confirm(`Xác nhận thao tác ${status} cho alert này?`)) {
        return;
    }
    const reason = window.prompt('Nhập reason để audit:');
    if (!reason?.trim()) {
        return;
    }
    busy.value = alert.id;
    router.post(`/admin/operations/health/${alert.id}`, { action: status, reason: reason.trim(), source: alert.source, source_id: alert.sourceId, idempotency_key: `${alert.id}:${status}` }, {
        preserveScroll: true,
        onFinish: () => { busy.value = null; },
    });
}

function severityClass(severity: AdminActionAlert['severity']): string {
    return { critical: 'border-danger-200 bg-danger-50', warning: 'border-amber-200 bg-amber-50', info: 'border-sky-200 bg-sky-50' }[severity];
}
</script>

<template>
    <AdminLayout :breadcrumb="[{ label: 'Admin', href: '/admin' }, { label: 'Action Center' }]">
        <div class="grid gap-6">
            <section class="flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
                <div><p class="text-sm font-semibold text-brand-700">Operations</p><h1 class="mt-1 text-3xl font-bold tracking-tight">Action Center</h1><p class="mt-2 max-w-2xl text-sm text-slate-500">Một hàng đợi cho sự cố vận hành, review và security signal. Nội dung nhạy cảm được loại khỏi contract.</p></div>
                <div class="flex gap-2 text-xs font-semibold"><span v-for="(value, key) in props.counts" :key="key" class="rounded-full bg-white px-3 py-2 text-slate-600 shadow-sm ring-1 ring-slate-200">{{ key }}: {{ value }}</span></div>
            </section>
            <section class="grid gap-3 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:grid-cols-4">
                <select v-model="localFilters.severity" class="min-h-11 rounded-xl border-slate-200 text-sm"><option value="">Tất cả severity</option><option v-for="item in props.filterOptions.severities" :key="item" :value="item">{{ item }}</option></select>
                <select v-model="localFilters.source" class="min-h-11 rounded-xl border-slate-200 text-sm"><option value="">Tất cả source</option><option v-for="item in props.filterOptions.sources" :key="item" :value="item">{{ item }}</option></select>
                <select v-model="localFilters.status" class="min-h-11 rounded-xl border-slate-200 text-sm"><option value="">Tất cả trạng thái</option><option v-for="item in props.filterOptions.statuses" :key="item" :value="item">{{ item }}</option></select>
                <button type="button" class="min-h-11 rounded-xl bg-brand-600 px-4 text-sm font-semibold text-white hover:bg-brand-700" @click="applyFilters">Áp dụng bộ lọc</button>
            </section>
            <section v-if="props.alerts.length" class="grid gap-3" aria-label="Operational alerts">
                <article v-for="alert in props.alerts" :key="alert.id" class="rounded-2xl border p-5 shadow-sm" :class="severityClass(alert.severity)">
                    <div class="flex flex-col justify-between gap-4 md:flex-row"><div><div class="flex flex-wrap items-center gap-2"><span class="rounded-full bg-white/80 px-2 py-1 text-[11px] font-bold uppercase tracking-wide">{{ alert.severity }}</span><span class="text-xs font-semibold text-slate-500">{{ alert.source }}</span><span class="text-xs text-slate-500">{{ alert.status }}</span></div><h2 class="mt-3 text-lg font-bold text-slate-900">{{ alert.title }}</h2><p class="mt-1 text-sm text-slate-700">{{ alert.summary }}</p><p class="mt-2 text-xs text-slate-500">Owner: {{ alert.owner }} · {{ alert.action }}</p></div><div class="flex shrink-0 flex-wrap items-start gap-2"><a :href="alert.href" class="rounded-xl bg-white px-3 py-2 text-sm font-semibold text-brand-700 ring-1 ring-slate-200 hover:bg-slate-50">Mở</a><button v-if="alert.source === 'webhook' && alert.status !== 'resolved'" type="button" class="rounded-xl bg-amber-600 px-3 py-2 text-sm font-semibold text-white disabled:opacity-50" :disabled="busy === alert.id" @click="updateState(alert, 'retry')">Retry</button><button v-if="alert.status === 'open'" type="button" class="rounded-xl bg-slate-900 px-3 py-2 text-sm font-semibold text-white disabled:opacity-50" :disabled="busy === alert.id" @click="updateState(alert, 'acknowledge')">Acknowledge</button><button v-if="alert.status !== 'resolved'" type="button" class="rounded-xl bg-white px-3 py-2 text-sm font-semibold text-slate-800 ring-1 ring-slate-200 disabled:opacity-50" :disabled="busy === alert.id" @click="updateState(alert, 'resolve')">Resolve</button></div></div>
                </article>
            </section>
            <section v-else class="rounded-2xl border border-dashed border-slate-300 bg-white p-12 text-center"><p class="text-lg font-semibold text-slate-900">Không có alert phù hợp</p><p class="mt-2 text-sm text-slate-500">Hàng đợi đang trống hoặc bộ lọc không trả về kết quả.</p></section>
        </div>
    </AdminLayout>
</template>
