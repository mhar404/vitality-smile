<script setup>
import { Link } from "@inertiajs/vue3";
import ThemeToggle from "@/Components/ThemeToggle.vue";

defineProps({
    maxWidth: {
        type: String,
        default: "max-w-md",
    },
});
</script>

<template>
    <div
        class="relative min-h-screen flex flex-col items-center justify-center bg-gradient-to-br from-teal-50/80 via-slate-50 to-emerald-50/60 py-10 px-4 sm:px-6 lg:px-8 overflow-hidden font-sans dark:from-slate-950 dark:via-slate-900 dark:to-teal-950/40 text-gray-900 dark:text-slate-100 transition-colors duration-150"
    >
        <!-- Floating Theme Toggle for Guests -->
        <div class="absolute top-4 right-4 z-30">
            <ThemeToggle />
        </div>

        <!-- Ambient Decorative Background Glows -->
        <div
            class="pointer-events-none absolute -top-24 -left-24 w-96 h-96 bg-teal-300/25 dark:bg-teal-500/10 rounded-full blur-3xl"
        ></div>
        <div
            class="pointer-events-none absolute -bottom-24 -right-24 w-96 h-96 bg-emerald-300/25 dark:bg-emerald-500/10 rounded-full blur-3xl"
        ></div>
        <div
            class="pointer-events-none absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[600px] h-[600px] bg-cyan-200/15 dark:bg-cyan-500/5 rounded-full blur-3xl"
        ></div>

        <!-- Clinic Brand Header -->
        <div class="relative z-10 mb-6 text-center">
            <Link
                href="/"
                class="inline-flex items-center gap-3 group focus:outline-none focus:ring-2 focus:ring-teal-500 focus:ring-offset-2 dark:focus:ring-offset-slate-900 rounded-2xl p-2 transition"
            >
                <!-- Tooth / Medical Shield Badge -->
                <div
                    class="h-12 w-12 shrink-0 rounded-2xl flex items-center justify-center group-hover:scale-105 transition-transform duration-200"
                >
                    <img
                        src="/images/brand/vitality-logo.png"
                        alt="Vitality Smile Logo"
                        class="h-10 w-auto object-contain"
                    />
                </div>

                <!-- Text Block (Centered vertically with image) -->
                <div
                    class="flex flex-col justify-center text-left leading-tight"
                >
                    <div
                        class="text-xl font-extrabold tracking-tight text-gray-900 dark:text-white leading-none"
                    >
                        VITALITY <span class="text-teal-600 dark:text-teal-400">SMILE</span>
                    </div>
                    <div
                        class="mt-1 text-[10px] font-semibold tracking-wider text-teal-700/80 dark:text-teal-400/90 uppercase leading-none"
                    >
                        GENERAL DENTISTRY & ORTHODONTICS
                    </div>
                </div>
            </Link>
        </div>

        <!-- Main Card Container -->
        <div
            :class="[
                'relative z-10 w-full bg-white/95 backdrop-blur-xl shadow-xl shadow-slate-200/50 rounded-2xl sm:rounded-3xl border border-teal-100/80 p-6 sm:p-8 md:p-10 transition-all duration-300 dark:bg-slate-900/95 dark:border-slate-800 dark:shadow-2xl dark:shadow-black/60',
                maxWidth,
            ]"
        >
            <slot />
        </div>

        <!-- Footer -->
        <div class="relative z-10 mt-8 text-center text-xs text-gray-500 dark:text-slate-400">
            <p>
                © {{ new Date().getFullYear() }} Vitality Smile Dental Clinic.
                All rights reserved.
            </p>
            <p class="mt-1">
                <Link
                    href="/"
                    class="text-teal-600 hover:text-teal-700 dark:text-teal-400 dark:hover:text-teal-300 hover:underline"
                    >Return to Home</Link
                >
                <span class="mx-2">•</span>
                <Link
                    :href="route('services.index')"
                    class="text-teal-600 hover:text-teal-700 dark:text-teal-400 dark:hover:text-teal-300 hover:underline"
                    >Clinic Services</Link
                >
            </p>
        </div>
    </div>
</template>
