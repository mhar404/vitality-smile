<script setup>
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';

const props = defineProps({
    href: {
        type: String,
        required: true,
    },
    active: {
        type: Boolean,
    },
});

const classes = computed(() =>
    props.active
        ? 'block w-full ps-3 pe-4 py-2 border-l-4 border-teal-500 dark:border-teal-400 text-start text-base font-medium text-teal-700 dark:text-teal-300 bg-teal-50 dark:bg-teal-950/40 focus:outline-none focus:text-teal-800 dark:focus:text-teal-200 focus:bg-teal-100 dark:focus:bg-teal-900/50 focus:border-teal-700 transition duration-150 ease-in-out'
        : 'block w-full ps-3 pe-4 py-2 border-l-4 border-transparent text-start text-base font-medium text-gray-600 dark:text-slate-400 hover:text-gray-800 dark:hover:text-slate-200 hover:bg-gray-50 dark:hover:bg-slate-800 hover:border-gray-300 dark:hover:border-slate-700 focus:outline-none focus:text-gray-800 dark:focus:text-slate-200 focus:bg-gray-50 dark:focus:bg-slate-800 focus:border-gray-300 transition duration-150 ease-in-out',
);
</script>

<template>
    <Link :href="href" :class="classes">
        <slot />
    </Link>
</template>
