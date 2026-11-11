<script setup>
import { Link } from '@inertiajs/vue3';

defineProps({
    href: {
        type: String,
        required: true,
    },
});
</script>

<template>
    <Link
        :href="href"
        class="block w-full px-4 py-2 text-start text-sm leading-5 text-gray-700 dark:text-slate-200 transition duration-150 ease-in-out hover:bg-gray-100 dark:hover:bg-slate-800/80 focus:bg-gray-100 dark:focus:bg-slate-800/80 focus:outline-none"
    >
        <slot />
    </Link>
</template>
