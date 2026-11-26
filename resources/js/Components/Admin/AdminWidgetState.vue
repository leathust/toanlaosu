<script setup lang="ts">
import type { WidgetStatus } from '../../types/admin';

defineProps<{
    status: WidgetStatus;
    emptyMessage?: string;
    errorMessage?: string;
}>();

const emit = defineEmits<{
    retry: [];
}>();
</script>

<template>
    <div v-if="status === 'loading'" class="grid gap-3" aria-label="Đang tải dữ liệu" aria-busy="true" aria-live="polite">
        <div class="h-4 w-1/3 animate-pulse rounded bg-slate-200" />
        <div class="h-24 animate-pulse rounded-2xl bg-slate-100" />
    </div>
    <div v-else-if="status === 'empty'" class="rounded-2xl border border-dashed border-slate-300 bg-slate-50 p-6 text-sm text-slate-600" role="status" aria-live="polite">
        {{ emptyMessage || 'Chưa có dữ liệu trong khoảng thời gian này.' }}
    </div>
    <div v-else-if="status === 'error'" class="rounded-2xl border border-danger-100 bg-danger-50 p-6 text-sm text-danger-700" role="alert">
        <p>{{ errorMessage || 'Không thể tải widget này.' }}</p>
        <button type="button" class="mt-3 rounded-lg bg-white px-3 py-2 font-semibold text-danger-700 shadow-sm ring-1 ring-danger-100" @click="emit('retry')">Thử lại</button>
    </div>
    <div v-else-if="status === 'stale' || status === 'partial'" class="rounded-2xl border border-warning-100 bg-warning-50 p-4 text-sm text-warning-700" role="status">
        {{ status === 'stale' ? 'Dữ liệu đang cũ; hãy thử tải lại.' : 'Một phần dữ liệu chưa sẵn sàng.' }}
        <button type="button" class="ml-2 font-semibold underline" @click="emit('retry')">Tải lại</button>
    </div>
    <slot v-else />
</template>
