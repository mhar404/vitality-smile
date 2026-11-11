<script setup>
import { ref, onMounted, onUnmounted } from 'vue';

const phDate = ref('');
const phTime = ref('');
let timer = null;

const updatePhilippineTime = () => {
    const now = new Date();

    phDate.value = new Intl.DateTimeFormat('en-PH', {
        timeZone: 'Asia/Manila',
        weekday: 'short',
        month: 'short',
        day: 'numeric',
        year: 'numeric',
    }).format(now);

    phTime.value = new Intl.DateTimeFormat('en-PH', {
        timeZone: 'Asia/Manila',
        hour: '2-digit',
        minute: '2-digit',
        second: '2-digit',
        hour12: true,
    }).format(now);
};

onMounted(() => {
    updatePhilippineTime();
    timer = setInterval(updatePhilippineTime, 1000);
});

onUnmounted(() => {
    if (timer) clearInterval(timer);
});
</script>

<template>
    <div
        class="hidden items-center gap-2 rounded-full border border-teal-100 bg-teal-50/60 dark:border-teal-900/60 dark:bg-teal-950/40 px-3.5 py-1.5 text-xs font-semibold text-teal-900 dark:text-teal-200 shadow-sm sm:flex"
        title="Philippine Standard Time (UTC+8)"
    >
        <span class="relative flex h-2 w-2">
            <span
                class="absolute inline-flex h-full w-full animate-ping rounded-full bg-teal-400 opacity-75"
            ></span>
            <span
                class="relative inline-flex h-2 w-2 rounded-full bg-teal-500"
            ></span>
        </span>
        <span class="text-xs font-medium text-gray-600 dark:text-slate-400">{{
            phDate
        }}</span>
        <span class="text-teal-200 dark:text-teal-700">|</span>
        <span class="font-mono text-xs font-bold text-teal-900 dark:text-teal-300">{{
            phTime
        }}</span>
    </div>
</template>
