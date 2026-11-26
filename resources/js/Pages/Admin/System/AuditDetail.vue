<script setup lang="ts">
import AdminLayout from '../../../Layouts/AdminLayout.vue';
import type { AdminAuditDetailProps } from '../../../types/admin';

const props = defineProps<AdminAuditDetailProps>();
</script>

<template>
    <AdminLayout :breadcrumb="[{ label: 'Admin', href: '/admin' }, { label: 'Audit & system', href: '/admin/operations' }, { label: 'Audit detail' }]">
        <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-7"><div class="flex flex-col justify-between gap-3 sm:flex-row sm:items-start"><div><p class="text-xs font-bold uppercase tracking-wide text-brand-600">Audit detail #{{ props.audit.id }}</p><h1 class="mt-2 text-2xl font-bold text-slate-950">{{ props.audit.event }}</h1><p class="mt-2 text-sm text-slate-500">{{ props.audit.actor }} · {{ props.audit.targetType || 'System' }}{{ props.audit.targetId ? ` #${props.audit.targetId}` : '' }}</p></div><span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-bold">{{ props.audit.severity }}</span></div><dl class="mt-7 grid gap-4 sm:grid-cols-2"><div><dt class="text-xs font-semibold uppercase tracking-wide text-slate-400">Occurred</dt><dd class="mt-1 text-sm">{{ props.audit.occurredAt || '—' }}</dd></div><div><dt class="text-xs font-semibold uppercase tracking-wide text-slate-400">Retention expires</dt><dd class="mt-1 text-sm">{{ props.audit.retentionExpiresAt || '—' }}</dd></div></dl><div class="mt-7 grid gap-5 lg:grid-cols-2"><div><h2 class="text-sm font-bold text-slate-900">Old values (redacted)</h2><pre class="mt-2 max-h-96 overflow-auto rounded-xl bg-slate-950 p-4 text-xs text-slate-100">{{ JSON.stringify(props.audit.oldValues || {}, null, 2) }}</pre></div><div><h2 class="text-sm font-bold text-slate-900">New values (redacted)</h2><pre class="mt-2 max-h-96 overflow-auto rounded-xl bg-slate-950 p-4 text-xs text-slate-100">{{ JSON.stringify(props.audit.newValues || {}, null, 2) }}</pre></div></div></section>
    </AdminLayout>
</template>
