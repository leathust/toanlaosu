<script setup lang="ts">
import type { Workspace } from '../types';

defineProps<{
    workspace: Workspace;
}>();
</script>

<template>
    <div class="min-h-screen bg-slate-50 text-slate-950">
        <header class="border-b border-slate-200 bg-white">
            <div class="mx-auto flex min-h-16 max-w-6xl items-center justify-between gap-4 px-4 sm:px-6">
                <a href="/ui/inertia-pilot" class="flex items-center gap-3 font-semibold tracking-tight">
                    <span class="flex size-9 items-center justify-center rounded-xl bg-brand-600 text-sm font-bold text-white">TL</span>
                    <span>Toán Lão Sư <span class="font-normal text-slate-400">/ {{ workspace }}</span></span>
                </a>
                <nav aria-label="Workspace navigation" class="flex items-center gap-1 text-sm">
                    <a href="/ui/prototype" class="rounded-lg px-3 py-2 text-slate-600 hover:bg-slate-50">Prototype</a>
                    <a href="/ui/components" class="rounded-lg px-3 py-2 text-slate-600 hover:bg-slate-50">UI kit</a>
                </nav>
            </div>
        </header>
        <main class="mx-auto max-w-6xl px-4 py-8 sm:px-6 lg:py-12">
            <slot />
        </main>
    </div>
</template>
