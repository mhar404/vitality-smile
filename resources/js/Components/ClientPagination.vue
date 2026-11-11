<script setup>
import { computed } from "vue";

/**
 * Client-side pagination controls for lists that are already loaded in memory.
 * Usage: <ClientPagination v-model:page="page" :total="items.length" :per-page="5" />
 */
const props = defineProps({
    page: { type: Number, default: 1 },
    total: { type: Number, default: 0 },
    perPage: { type: Number, default: 5 },
    label: { type: String, default: "records" },
});
const emit = defineEmits(["update:page"]);

const lastPage = computed(() => Math.max(1, Math.ceil(props.total / props.perPage)));
const from = computed(() => (props.total === 0 ? 0 : (props.page - 1) * props.perPage + 1));
const to = computed(() => Math.min(props.page * props.perPage, props.total));

const go = (p) => emit("update:page", Math.min(Math.max(1, p), lastPage.value));
</script>

<template>
    <div
        v-if="total > perPage"
        class="flex flex-col items-center justify-between gap-2 border-t border-gray-100 dark:border-slate-800 px-1 pt-3 sm:flex-row"
    >
        <div class="text-xs text-gray-500 dark:text-slate-400">
            Showing <span class="font-semibold text-gray-800 dark:text-slate-200">{{ from }}</span>
            to <span class="font-semibold text-gray-800 dark:text-slate-200">{{ to }}</span>
            of <span class="font-semibold text-gray-800 dark:text-slate-200">{{ total }}</span> {{ label }}
        </div>
        <div class="flex items-center gap-1">
            <button
                type="button"
                :disabled="page <= 1"
                @click="go(page - 1)"
                class="rounded-lg border border-gray-300 bg-white px-3 py-1.5 text-xs font-medium text-gray-700 hover:bg-gray-50 disabled:cursor-not-allowed disabled:opacity-40 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-300 dark:hover:bg-slate-700"
            >
                Previous
            </button>
            <span class="px-2 text-xs text-gray-600 dark:text-slate-400">Page {{ page }} of {{ lastPage }}</span>
            <button
                type="button"
                :disabled="page >= lastPage"
                @click="go(page + 1)"
                class="rounded-lg border border-gray-300 bg-white px-3 py-1.5 text-xs font-medium text-gray-700 hover:bg-gray-50 disabled:cursor-not-allowed disabled:opacity-40 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-300 dark:hover:bg-slate-700"
            >
                Next
            </button>
        </div>
    </div>
</template>

