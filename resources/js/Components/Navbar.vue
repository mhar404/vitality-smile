<script setup>
import { Link } from "@inertiajs/vue3";
import { ref, onMounted, onUnmounted } from "vue";
import ThemeToggle from "@/Components/ThemeToggle.vue";

const isOpen = ref(false);

const phDate = ref("");
const phTime = ref("");
let timer = null;

const updatePhDateTime = () => {
    const now = new Date();

    // Formats day and date (e.g., "Mon, Oct 28, 2026")
    phDate.value = new Intl.DateTimeFormat("en-PH", {
        timeZone: "Asia/Manila",
        weekday: "short",
        month: "short",
        day: "numeric",
        year: "numeric",
    }).format(now);

    // Formats time (e.g., "09:45:12 PM")
    phTime.value = new Intl.DateTimeFormat("en-PH", {
        timeZone: "Asia/Manila",
        hour: "2-digit",
        minute: "2-digit",
        second: "2-digit",
        hour12: true,
    }).format(now);
};

onMounted(() => {
    updatePhDateTime();
    timer = setInterval(updatePhDateTime, 1000);
});

onUnmounted(() => {
    if (timer) clearInterval(timer);
});
</script>

<template>
    <nav class="border-b border-gray-200 bg-white dark:border-slate-800 dark:bg-slate-900 transition-colors duration-150">
        <div
            class="mx-auto flex h-16 max-w-7xl items-center justify-between px-4 sm:px-6 lg:px-8"
        >
            <!-- Logo -->
            <Link href="/" class="flex items-center gap-2.5 focus:outline-none rounded-2xl p-1 transition focus:ring-2 focus:ring-teal-500 focus:ring-offset-2 dark:focus:ring-offset-slate-900">
                <img
                    src="/images/brand/vitality-logo.png"
                    alt="Vitality Smile Logo"
                    class="h-8 w-auto object-contain shrink-0"
                />
                <div class="flex flex-col justify-center text-left">
                    <span
                        class="text-lg font-extrabold tracking-tight text-gray-900 dark:text-white leading-none"
                    >
                        VITALITY <span class="text-teal-600 dark:text-teal-400">SMILE</span>
                    </span>
                    <span
                        class="mt-0.5 text-[10px] font-semibold tracking-wider text-teal-700/80 dark:text-teal-400/90 uppercase leading-none whitespace-nowrap"
                    >
                        GENERAL DENTISTRY & ORTHODONTICS
                    </span>
                </div>
            </Link>

            <!-- Redesigned PH Date & Time Badge -->
            <div
                class="hidden items-center gap-2 rounded-full border border-teal-100 bg-teal-50/60 dark:border-teal-900/60 dark:bg-teal-950/40 px-3.5 py-1.5 text-xs font-semibold text-teal-900 dark:text-teal-200 shadow-sm sm:flex"
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

            <!-- Desktop Navigation -->
            <div class="hidden items-center gap-6 md:flex">
                <Link
                    href="/"
                    class="text-sm font-medium text-gray-700 dark:text-slate-300 transition hover:text-teal-700 dark:hover:text-teal-400"
                    :class="$page.url === '/' ? 'font-semibold text-teal-700 dark:text-teal-400' : ''"
                >
                    Home
                </Link>

                <Link
                    href="/services"
                    class="text-sm font-medium transition hover:text-teal-700 dark:hover:text-teal-400"
                    :class="
                        $page.url === '/services'
                            ? 'font-semibold text-teal-700 dark:text-teal-400'
                            : 'text-gray-700 dark:text-slate-300'
                    "
                >
                    Services
                </Link>

                <Link
                    href="/#about"
                    class="text-sm font-medium text-gray-700 dark:text-slate-300 transition hover:text-teal-700 dark:hover:text-teal-400"
                >
                    About
                </Link>

                <Link
                    href="/#contact"
                    class="text-sm font-medium text-gray-700 dark:text-slate-300 transition hover:text-teal-700 dark:hover:text-teal-400"
                >
                    Contact
                </Link>

                <!-- Theme Toggle Button -->
                <ThemeToggle />

                <Link
                    href="/login"
                    class="rounded-lg border border-teal-700 px-4 py-2 text-sm font-semibold text-teal-700 transition hover:bg-teal-50 dark:border-teal-500 dark:text-teal-400 dark:hover:bg-slate-800"
                >
                    Login
                </Link>

                <Link
                    href="/register"
                    class="rounded-lg bg-teal-700 px-4 py-2 text-sm font-semibold text-white transition hover:bg-teal-800 shadow-xs dark:bg-teal-600 dark:hover:bg-teal-500"
                >
                    Register
                </Link>
            </div>

            <!-- Mobile Menu Button -->
            <div class="flex items-center gap-2 md:hidden">
                <ThemeToggle />

                <button
                    @click="isOpen = !isOpen"
                    class="rounded-lg p-2 text-gray-700 hover:bg-gray-100 dark:text-slate-300 dark:hover:bg-slate-800"
                    aria-label="Toggle navigation menu"
                >
                    <svg
                        v-if="!isOpen"
                        xmlns="http://www.w3.org/2000/svg"
                        class="h-6 w-6"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke="currentColor"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M4 6h16M4 12h16M4 18h16"
                        />
                    </svg>

                    <svg
                        v-else
                        xmlns="http://www.w3.org/2000/svg"
                        class="h-6 w-6"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke="currentColor"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M6 18L18 6M6 6l12 12"
                        />
                    </svg>
                </button>
            </div>
        </div>

        <!-- Mobile Navigation Drawer -->
        <div v-if="isOpen" class="border-t border-gray-200 bg-white dark:border-slate-800 dark:bg-slate-900 md:hidden">
            <div class="space-y-1 px-4 py-4">
                <!-- Mobile Date & Time Badge -->
                <div class="flex items-center justify-center gap-2 rounded-full border border-teal-100 bg-teal-50/60 dark:border-teal-900/60 dark:bg-teal-950/40 px-3.5 py-1.5 text-xs font-semibold text-teal-900 dark:text-teal-200 shadow-sm mb-3">
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

                <Link
                    href="/"
                    @click="isOpen = false"
                    class="block rounded-lg px-3 py-2 text-gray-700 dark:text-slate-300 hover:bg-teal-50 dark:hover:bg-slate-800 hover:text-teal-700 dark:hover:text-teal-400"
                >
                    Home
                </Link>

                <Link
                    href="/services"
                    @click="isOpen = false"
                    class="block rounded-lg px-3 py-2 text-gray-700 dark:text-slate-300 hover:bg-teal-50 dark:hover:bg-slate-800 hover:text-teal-700 dark:hover:text-teal-400"
                >
                    Services
                </Link>

                <Link
                    href="/#about"
                    @click="isOpen = false"
                    class="block rounded-lg px-3 py-2 text-gray-700 dark:text-slate-300 hover:bg-teal-50 dark:hover:bg-slate-800 hover:text-teal-700 dark:hover:text-teal-400"
                >
                    About
                </Link>

                <Link
                    href="/#contact"
                    @click="isOpen = false"
                    class="block rounded-lg px-3 py-2 text-gray-700 dark:text-slate-300 hover:bg-teal-50 dark:hover:bg-slate-800 hover:text-teal-700 dark:hover:text-teal-400"
                >
                    Contact
                </Link>

                <div class="flex gap-2 pt-3">
                    <Link
                        href="/login"
                        class="flex-1 rounded-lg border border-teal-700 px-4 py-2 text-center text-sm font-semibold text-teal-700 hover:bg-teal-50 dark:border-teal-500 dark:text-teal-400 dark:hover:bg-slate-800"
                    >
                        Login
                    </Link>

                    <Link
                        href="/register"
                        class="flex-1 rounded-lg bg-teal-700 px-4 py-2 text-center text-sm font-semibold text-white hover:bg-teal-800 dark:bg-teal-600 dark:hover:bg-teal-500"
                    >
                        Register
                    </Link>
                </div>
            </div>
        </div>
    </nav>
</template>
