<script setup>
import { Link } from "@inertiajs/vue3";

/**
 * Server-side pagination footer for Laravel (Inertia) paginator objects.
 * Usage: <Pagination :paginator="items" label="records" />
 */
defineProps({
    paginator: { type: Object, default: null },
    label: { type: String, default: "records" },
});
</script>

<template>
    <div
        v-if="paginator && paginator.links && paginator.last_page > 1"
        class="flex flex-col items-center justify-between gap-3 border-t border-gray-200 bg-gray-50/50 px-5 py-4 sm:flex-row dark:border-slate-800 dark:bg-slate-900/60"
    >
        <div class="text-xs text-gray-600 dark:text-slate-400">
            Showing
            <span class="font-semibold text-gray-900 dark:text-slate-200">{{ paginator.from || 0 }}</span>
            to
            <span class="font-semibold text-gray-900 dark:text-slate-200">{{ paginator.to || 0 }}</span>
            of
            <span class="font-semibold text-gray-900 dark:text-slate-200">{{ paginator.total }}</span>
            {{ label }}
        </div>
        <div class="flex flex-wrap items-center gap-1">
            <Link
                v-for="(link, idx) in paginator.links"
                :key="idx"
                :href="link.url || '#'"
                preserve-scroll
                v-html="link.label"
                :class="[
                    'rounded-lg border px-3 py-1.5 text-xs font-medium transition',
                    link.active
                        ? 'border-teal-700 bg-teal-700 text-white shadow-sm dark:border-teal-600 dark:bg-teal-600'
                        : 'border-gray-300 bg-white text-gray-700 hover:bg-gray-50 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-300 dark:hover:bg-slate-700',
                    !link.url ? 'pointer-events-none cursor-not-allowed opacity-40' : '',
                ]"
            />
        </div>
    </div>
</template>

