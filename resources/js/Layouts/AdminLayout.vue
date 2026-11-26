<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import type { AdminCourseOption, AdminFilters } from '../types/admin';
import type { SharedPageProps } from '../types';

const props = withDefaults(defineProps<{
    breadcrumb?: Array<{ label: string; href?: string }>;
    filters?: AdminFilters;
    courses?: AdminCourseOption[];
    isRefreshing?: boolean;
}>(), {
    breadcrumb: () => [{ label: 'Admin' }],
    filters: undefined,
    courses: () => [],
    isRefreshing: false,
});

const emit = defineEmits<{
    applyFilters: [];
}>();

const page = usePage<SharedPageProps>();
const mobileOpen = ref(false);
const filterOpen = ref(false);
const navigation = computed(() => page.props.navigation?.admin ?? []);
const currentUrl = computed(() => page.url.split('?')[0].replace(/\/$/, '') || '/');

function isActive(href: string): boolean {
    return href.split('#')[0].replace(/\/$/, '') === currentUrl.value;
}
</script>

<template>
    <div class="min-h-screen overflow-x-hidden bg-slate-50 text-slate-950">
        <a href="#admin-main" class="sr-only fixed left-4 top-4 z-50 rounded-lg bg-white px-4 py-3 text-sm font-semibold text-brand-700 shadow-lg focus:not-sr-only">Bỏ qua đến nội dung</a>
        <div v-if="mobileOpen" class="fixed inset-0 z-40 bg-slate-950/40 lg:hidden" aria-hidden="true" @click="mobileOpen = false" />
        <aside class="fixed inset-y-0 left-0 z-50 flex w-72 -translate-x-full flex-col border-r border-slate-200 bg-white transition-transform lg:translate-x-0" :class="{ 'translate-x-0': mobileOpen }" aria-label="Admin navigation">
            <div class="flex min-h-20 items-center justify-between border-b border-slate-100 px-5">
                <Link href="/admin" class="flex items-center gap-3 font-semibold tracking-tight" @click="mobileOpen = false">
                    <span class="flex size-10 items-center justify-center rounded-xl bg-brand-600 text-sm font-bold text-white">TL</span>
                    <span>Toán Lão Sư <span class="block text-xs font-normal text-slate-400">Admin workspace</span></span>
                </Link>
                <button type="button" class="rounded-lg p-2 text-slate-500 hover:bg-slate-100 lg:hidden" aria-label="Đóng menu" @click="mobileOpen = false">×</button>
            </div>
            <nav class="grid gap-1 overflow-y-auto p-4" aria-label="Các khu vực quản trị">
                <Link v-for="item in navigation" :key="item.href" :href="item.href" class="group grid gap-1 rounded-xl px-3 py-3 text-sm transition" :class="isActive(item.href) ? 'bg-brand-50 text-brand-800 ring-1 ring-brand-100' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-950'" @click="mobileOpen = false">
                    <span class="flex items-center justify-between gap-2 font-semibold"><span>{{ item.label }}</span><span v-if="item.badge" class="rounded-full bg-danger-100 px-2 py-0.5 text-[11px] text-danger-700">{{ item.badge }}</span></span>
                    <span class="text-xs text-slate-400 group-hover:text-slate-500">{{ item.description }}</span>
                </Link>
            </nav>
            <div class="mt-auto border-t border-slate-100 p-4">
                <Link href="/workspace" class="block rounded-xl px-3 py-3 text-sm font-semibold text-slate-600 hover:bg-slate-50 hover:text-slate-950">Đổi workspace</Link>
                <Link href="/logout" method="post" as="button" class="mt-1 w-full rounded-xl px-3 py-3 text-left text-sm font-semibold text-danger-600 hover:bg-danger-50">Đăng xuất</Link>
            </div>
        </aside>

        <div class="min-w-0 lg:pl-72">
            <header class="sticky top-0 z-30 border-b border-slate-200/90 bg-white/95 backdrop-blur">
                <div class="flex min-h-16 items-center justify-between gap-4 px-4 sm:px-6 lg:px-8">
                    <button type="button" class="rounded-xl border border-slate-200 bg-white p-2 text-slate-600 shadow-sm lg:hidden" aria-label="Mở menu" :aria-expanded="mobileOpen" @click="mobileOpen = true">☰</button>
                    <div class="hidden min-w-0 items-center gap-2 text-sm text-slate-500 sm:flex" aria-label="Breadcrumb">
                        <template v-for="(item, index) in props.breadcrumb" :key="item.label">
                            <span v-if="index" aria-hidden="true">/</span>
                            <Link v-if="item.href" :href="item.href" class="truncate hover:text-brand-700">{{ item.label }}</Link>
                            <span v-else class="truncate font-semibold text-slate-800">{{ item.label }}</span>
                        </template>
                    </div>
                    <div class="flex items-center gap-3">
                        <span class="hidden text-right sm:block"><span class="block text-xs text-slate-400">Đang đăng nhập</span><span class="block text-sm font-semibold text-slate-800">{{ page.props.auth.user?.name }}</span></span>
                        <span class="flex size-9 items-center justify-center rounded-full bg-brand-100 text-sm font-bold text-brand-700" aria-hidden="true">{{ page.props.auth.user?.name?.slice(0, 1).toUpperCase() }}</span>
                    </div>
                </div>
            </header>

            <main id="admin-main" class="mx-auto min-w-0 max-w-[1440px] px-4 py-6 sm:px-6 lg:px-8 lg:py-8">
                <div v-if="props.filters" class="mb-6 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                    <div class="flex items-center justify-between gap-3">
                        <div>
                            <p class="text-sm font-semibold text-slate-950">Bộ lọc toàn cục</p>
                            <p class="text-xs text-slate-500">Được lưu trên URL để reload và chia sẻ đúng context.</p>
                        </div>
                        <button type="button" class="rounded-lg px-3 py-2 text-sm font-semibold text-brand-700 hover:bg-brand-50 sm:hidden" :aria-expanded="filterOpen" @click="filterOpen = !filterOpen">{{ filterOpen ? 'Thu gọn' : 'Mở bộ lọc' }}</button>
                    </div>
                    <div class="mt-4 grid gap-3 sm:grid-cols-2 lg:grid-cols-4" :class="{ 'hidden sm:grid': !filterOpen }">
                        <label class="grid gap-1 text-xs font-semibold text-slate-600">Khoảng thời gian<select v-model="props.filters.range" class="mt-1 min-h-11 rounded-xl border-slate-200 bg-white text-sm font-normal text-slate-800 focus:border-brand-500 focus:ring-brand-500"><option value="7d">7 ngày</option><option value="30d">30 ngày</option><option value="90d">90 ngày</option></select></label>
                        <label class="grid gap-1 text-xs font-semibold text-slate-600">So sánh<select v-model="props.filters.compare" class="mt-1 min-h-11 rounded-xl border-slate-200 bg-white text-sm font-normal text-slate-800 focus:border-brand-500 focus:ring-brand-500"><option value="previous">So với kỳ trước</option><option value="none">Không so sánh</option></select></label>
                        <label class="grid gap-1 text-xs font-semibold text-slate-600">Khóa học<select v-model="props.filters.course" class="mt-1 min-h-11 rounded-xl border-slate-200 bg-white text-sm font-normal text-slate-800 focus:border-brand-500 focus:ring-brand-500"><option :value="null">Tất cả khóa học</option><option v-for="course in props.courses" :key="course.id" :value="course.id">{{ course.title }}</option></select></label>
                        <label class="grid gap-1 text-xs font-semibold text-slate-600">Timezone<select v-model="props.filters.timezone" class="mt-1 min-h-11 rounded-xl border-slate-200 bg-white text-sm font-normal text-slate-800 focus:border-brand-500 focus:ring-brand-500"><option value="Asia/Ho_Chi_Minh">Asia/Ho_Chi_Minh</option><option value="Asia/Bangkok">Asia/Bangkok</option><option value="UTC">UTC</option></select></label>
                        <button type="button" class="min-h-11 rounded-xl bg-brand-600 px-4 text-sm font-semibold text-white shadow-sm hover:bg-brand-700 disabled:cursor-wait disabled:opacity-60 sm:col-span-2 lg:col-span-4 lg:justify-self-end" :disabled="props.isRefreshing" @click="emit('applyFilters')">{{ props.isRefreshing ? 'Đang áp dụng…' : 'Áp dụng bộ lọc' }}</button>
                    </div>
                </div>
                <slot />
            </main>
        </div>
    </div>
</template>
