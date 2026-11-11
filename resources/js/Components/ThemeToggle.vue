<script setup>
import { useTheme } from "@/Composables/useTheme";

const props = defineProps({
    showLabel: {
        type: Boolean,
        default: false,
    },
    buttonClass: {
        type: String,
        default: "",
    },
});

const { isDark, toggleTheme } = useTheme();
</script>

<template>
    <button
        type="button"
        @click="toggleTheme"
        :class="[
            'relative flex items-center justify-center transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-teal-500 focus:ring-offset-2 dark:focus:ring-offset-slate-900',
            buttonClass ||
                'h-9 w-9 rounded-xl border border-gray-200 bg-white text-gray-600 hover:bg-gray-50 hover:text-teal-700 shadow-2xs dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200 dark:hover:bg-slate-700 dark:hover:text-amber-300',
            showLabel ? 'gap-2 px-3 w-auto' : '',
        ]"
        :aria-label="isDark ? 'Switch to Light Mode' : 'Switch to Dark Mode'"
        :title="isDark ? 'Switch to Light Mode' : 'Switch to Dark Mode'"
    >
        <!-- Icon: PrimeVue / PrimeIcons sun & moon -->
        <i
            v-if="isDark"
            class="pi pi-sun text-base text-amber-400 transition-transform duration-300 hover:rotate-45"
        ></i>
        <i
            v-else
            class="pi pi-moon text-base text-slate-600 transition-transform duration-300 hover:-rotate-12"
        ></i>

        <span
            v-if="showLabel"
            class="text-xs font-semibold select-none text-gray-700 dark:text-slate-200"
        >
            {{ isDark ? "Light Mode" : "Dark Mode" }}
        </span>
    </button>
</template>

