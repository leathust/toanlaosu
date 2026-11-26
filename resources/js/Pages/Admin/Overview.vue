<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import AdminWidgetState from '../../Components/Admin/AdminWidgetState.vue';
import AdminLayout from '../../Layouts/AdminLayout.vue';
import type { AdminOverviewProps } from '../../types/admin';

const props = defineProps<AdminOverviewProps>();
const isRefreshing = ref(false);
const selectedFilters = ref({ ...props.filters });
const breadcrumb = computed(() => [{ label: 'Admin', href: '/admin' }, { label: 'Overview' }]);
const maxTrendValue = computed(() => Math.max(...props.trend.series.map((point) => point.activeLearners), 1));

function applyFilters(): void {
    isRefreshing.value = true;
    router.get(window.location.pathname, selectedFilters.value, {
        preserveState: true,
        preserveScroll: true,
        onFinish: () => {
            isRefreshing.value = false;
        },
    });
}

function retryOverview(): void {
    router.reload({
        only: ['stats', 'trend', 'alerts', 'recentAudits'],
        onStart: () => {
            isRefreshing.value = true;
        },
        onFinish: () => {
            isRefreshing.value = false;
        },
    });
}
</script>

<template>
    <AdminLayout :breadcrumb="breadcrumb" :filters="selectedFilters" :courses="props.filterOptions.courses" :is-refreshing="isRefreshing" @apply-filters="applyFilters">
        <section class="grid gap-8" aria-labelledby="admin-overview-title">
            <header class="flex flex-col justify-between gap-5 lg:flex-row lg:items-end">
                <div class="grid gap-2">
                    <p class="text-xs font-semibold uppercase tracking-[0.18em] text-brand-700">Operations Center</p>
                    <h1 id="admin-overview-title" class="text-3xl font-semibold tracking-tight text-slate-950 sm:text-4xl">Tổng quan hệ thống</h1>
                    <p class="max-w-2xl text-sm leading-6 text-slate-600">Một lớp quan sát để đội ngũ biết hệ thống đang hoạt động ra sao và nơi nào cần hành động.</p>
                </div>
                <button type="button" class="inline-flex min-h-11 items-center justify-center rounded-xl border border-slate-200 bg-white px-4 text-sm font-semibold text-slate-700 shadow-sm hover:border-brand-300 hover:text-brand-700 disabled:cursor-wait disabled:opacity-60" :disabled="isRefreshing" @click="retryOverview">
                    {{ isRefreshing ? 'Đang tải…' : 'Làm mới dữ liệu' }}
                </button>
            </header>

            <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4" aria-label="KPI overview">
                <article v-for="kpi in props.kpis.slice(0, 4)" :key="kpi.key" class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                    <p class="text-sm text-slate-500">{{ kpi.label }}</p>
                    <p class="mt-3 text-3xl font-semibold tracking-tight text-slate-950">{{ kpi.value.toLocaleString('vi-VN') }}<span v-if="kpi.unit === 'percent'" class="ml-1 text-lg">%</span></p>
                    <p class="mt-2 text-xs" :class="kpi.status === 'empty' ? 'text-slate-500' : 'text-success-600'">{{ kpi.status === 'empty' ? 'Chưa đủ dữ liệu' : kpi.comparison ? (kpi.comparison.delta > 0 ? '+' : '') + kpi.comparison.delta + ' so với kỳ trước' : 'Trong khoảng đã chọn' }}</p>
                </article>
            </section>

            <section class="grid gap-5 xl:grid-cols-[1.35fr_0.65fr]">
                <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <h2 class="text-lg font-semibold text-slate-950">Tiến độ analytics</h2>
                            <p class="mt-1 text-sm text-slate-500">Active learners và nhóm quay lại theo từng ngày trong window.</p>
                        </div>
                        <span class="rounded-full bg-brand-50 px-3 py-1 text-xs font-semibold text-brand-700">{{ props.filters.range }}</span>
                    </div>
                    <div class="mt-6">
                        <AdminWidgetState :status="props.trend.status" empty-message="Chưa có activity trong khoảng thời gian này." @retry="retryOverview">
                            <div class="grid gap-3" role="img" aria-label="Biểu đồ active learners theo ngày">
                                <div class="flex h-48 items-end gap-1 rounded-xl bg-slate-50 px-3 py-3 sm:gap-2">
                                    <div v-for="point in props.trend.series" :key="point.label" class="group relative flex min-w-0 flex-1 items-end justify-center gap-0.5" :title="point.label + ': ' + point.activeLearners + ' active, ' + point.returningLearners + ' quay lại'">
                                        <div class="w-full rounded-t-md bg-brand-500/80 transition group-hover:bg-brand-600" :style="{ height: Math.max((point.activeLearners / maxTrendValue) * 100, point.activeLearners ? 5 : 1) + '%' }" />
                                        <div class="absolute bottom-0 w-1/2 rounded-t-md bg-success-600/70" :style="{ height: Math.max((point.returningLearners / maxTrendValue) * 100, point.returningLearners ? 4 : 1) + '%' }" />
                                    </div>
                                </div>
                                <div class="flex justify-between text-[10px] text-slate-400"><span>{{ props.trend.series[0]?.label }}</span><span>{{ props.trend.series[props.trend.series.length - 1]?.label }}</span></div>
                                <div class="flex gap-4 text-xs text-slate-500"><span><i class="mr-1 inline-block size-2 rounded-full bg-brand-500" />Active learners</span><span><i class="mr-1 inline-block size-2 rounded-full bg-success-600" />Quay lại</span></div>
                            </div>
                        </AdminWidgetState>
                    </div>
                </article>

                <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <h2 class="text-lg font-semibold text-slate-950">Action Center</h2>
                            <p class="mt-1 text-sm text-slate-500">Các tín hiệu cần xử lý sẽ được hợp nhất ở ADM-004.</p>
                        </div>
                        <span class="rounded-full bg-success-50 px-3 py-1 text-xs font-semibold text-success-600">{{ props.alerts.length }} alert</span>
                    </div>
                    <AdminWidgetState :status="props.alerts.length ? 'ready' : 'empty'" empty-message="Hiện chưa có cảnh báo cần hành động." @retry="retryOverview">
                        <ul class="mt-5 grid gap-3">
                            <li v-for="alert in props.alerts" :key="alert.id" class="rounded-xl border border-slate-200 p-3 text-sm">{{ alert.title }}</li>
                        </ul>
                    </AdminWidgetState>
                </article>
            </section>

            <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <h2 class="text-lg font-semibold text-slate-950">Course health</h2>
                        <p class="mt-1 text-sm text-slate-500">Enrollment, completion, người học có nguy cơ và freshness.</p>
                    </div>
                    <span class="text-xs text-slate-400">Cập nhật {{ props.freshness }}</span>
                </div>
                <AdminWidgetState :status="props.courseHealth.length ? 'ready' : 'empty'" empty-message="Chưa có khóa học phù hợp với bộ lọc.">
                    <div class="mt-5 grid gap-3">
                        <article v-for="course in props.courseHealth" :key="course.courseId" class="grid gap-3 rounded-xl border border-slate-100 p-4 sm:grid-cols-[1.5fr_0.7fr_0.7fr_0.7fr] sm:items-center">
                            <div><h3 class="font-semibold text-slate-800">{{ course.title }}</h3><p class="text-xs text-slate-500">{{ course.enrollments }} enrollment · {{ course.learnersAtRisk }} at risk</p></div>
                            <div class="text-sm text-slate-600"><span class="block text-xs text-slate-400">Completion</span>{{ course.completion === null ? '—' : course.completion + '%' }}</div>
                            <div class="text-sm text-slate-600"><span class="block text-xs text-slate-400">Freshness</span>{{ course.freshness || '—' }}</div>
                            <a :href="'/admin?course=' + course.courseId" class="text-sm font-semibold text-brand-700 hover:text-brand-800">Drill-down</a>
                        </article>
                    </div>
                </AdminWidgetState>
            </section>

            <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
                <div class="flex items-center justify-between gap-4">
                    <div>
                        <h2 class="text-lg font-semibold text-slate-950">Audit gần đây</h2>
                        <p class="mt-1 text-sm text-slate-500">Dữ liệu đã rút gọn; xem đầy đủ tại Audit & system.</p>
                    </div>
                    <a href="/admin/audit" class="text-sm font-semibold text-brand-700 hover:text-brand-800">Mở audit</a>
                </div>
                <div v-if="props.recentAudits.length" class="mt-5 divide-y divide-slate-100">
                    <div v-for="audit in props.recentAudits" :key="audit.id" class="flex flex-col gap-1 py-3 text-sm sm:flex-row sm:items-center sm:justify-between sm:gap-4">
                        <span class="font-medium text-slate-800">{{ audit.event }}</span>
                        <span class="text-slate-500">{{ audit.actor }} · {{ audit.occurredAt || 'Không rõ thời điểm' }}</span>
                    </div>
                </div>
                <AdminWidgetState v-else status="empty" empty-message="Chưa có audit event." />
            </section>
        </section>
    </AdminLayout>
</template>
