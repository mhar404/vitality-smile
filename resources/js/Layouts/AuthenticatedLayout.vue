<script setup>
import { ref } from "vue";
import { Link } from "@inertiajs/vue3";

import ApplicationLogo from "@/Components/ApplicationLogo.vue";
import Dropdown from "@/Components/Dropdown.vue";
import DropdownLink from "@/Components/DropdownLink.vue";
import PhilippineTime from "@/Components/PhilippineTime.vue";
import ThemeToggle from "@/Components/ThemeToggle.vue";

const showingSidebar = ref(false);
</script>

<template>
    <div class="min-h-screen bg-gray-50 text-gray-900 dark:bg-slate-950 dark:text-slate-100 transition-colors duration-150">
        <!-- Mobile Overlay -->
        <div
            v-if="showingSidebar"
            @click="showingSidebar = false"
            class="fixed inset-0 z-40 bg-black/50 lg:hidden"
        ></div>

        <!-- Sidebar -->
        <aside
            :class="[
                'fixed inset-y-0 left-0 z-50 w-64 border-r border-gray-200 bg-white dark:border-slate-800 dark:bg-slate-900 transition-transform duration-300 print:hidden',
                showingSidebar
                    ? 'translate-x-0'
                    : '-translate-x-full lg:translate-x-0',
            ]"
        >
            <!-- Logo -->
            <div
                class="flex h-16 items-center justify-between border-b border-gray-200 dark:border-slate-800 px-5"
            >
                <Link
                    :href="route('dashboard')"
                    class="flex items-center gap-3"
                >
                    <img
                        src="/images/brand/vitality-logo.png"
                        alt="Vitality Smile Logo"
                        class="h-6 w-auto object-contain shrink-0"
                    />
                    <div
                        class="flex flex-col justify-center text-left leading-tight"
                    >
                        <span
                            class="text-md font-extrabold tracking-wide text-gray-900 dark:text-white leading-none"
                        >
                            VITALITY <span class="text-teal-600 dark:text-teal-400">SMILE</span>
                        </span>
                        <span
                            class="mt-1 text-[8.5px] font-semibold tracking-wider text-teal-700/80 dark:text-teal-400/90 uppercase leading-none"
                        >
                            GENERAL DENTISTRY & ORTHODONTICS
                        </span>
                    </div>
                </Link>

                <!-- Close Sidebar Mobile -->
                <button
                    @click="showingSidebar = false"
                    class="rounded-lg p-2 text-gray-500 hover:bg-gray-100 dark:text-slate-400 dark:hover:bg-slate-800 lg:hidden"
                    aria-label="Close sidebar"
                >
                    <i class="pi pi-times text-lg"></i>
                </button>
            </div>

            <!-- Navigation -->
            <div class="flex h-[calc(100vh-4rem)] flex-col">
                <nav class="flex-1 space-y-1 overflow-y-auto px-3 py-5">
                    <template v-if="$page.props.auth.user.role === 'patient'">
                        <!-- Dashboard -->
                        <Link
                            :href="route('dashboard')"
                            @click="showingSidebar = false"
                            :class="[
                                'flex items-center gap-3 rounded-lg px-4 py-3 text-sm font-medium transition',
                                route().current('dashboard')
                                    ? 'bg-teal-50 text-teal-700 dark:bg-teal-950/60 dark:text-teal-400 dark:border dark:border-teal-800/40'
                                    : 'text-gray-600 hover:bg-gray-50 hover:text-teal-700 dark:text-slate-300 dark:hover:bg-slate-800/70 dark:hover:text-teal-400',
                            ]"
                        >
                            <i class="pi pi-home text-lg"></i
                            ><span>Dashboard</span>
                        </Link>
                        <!-- My Appointments -->
                        <Link
                            :href="route('appointments.index')"
                            @click="showingSidebar = false"
                            :class="[
                                'flex items-center gap-3 rounded-lg px-4 py-3 text-sm font-medium transition',
                                route().current('appointments.*')
                                    ? 'bg-teal-50 text-teal-700 dark:bg-teal-950/60 dark:text-teal-400 dark:border dark:border-teal-800/40'
                                    : 'text-gray-600 hover:bg-gray-50 hover:text-teal-700 dark:text-slate-300 dark:hover:bg-slate-800/70 dark:hover:text-teal-400',
                            ]"
                        >
                            <i class="pi pi-calendar text-lg"></i
                            ><span>My Appointments</span>
                        </Link>
                        <!-- Services -->
                        <Link
                            :href="route('services.index')"
                            @click="showingSidebar = false"
                            :class="[
                                'flex items-center gap-3 rounded-lg px-4 py-3 text-sm font-medium transition',
                                route().current('services.*')
                                    ? 'bg-teal-50 text-teal-700 dark:bg-teal-950/60 dark:text-teal-400 dark:border dark:border-teal-800/40'
                                    : 'text-gray-600 hover:bg-gray-50 hover:text-teal-700 dark:text-slate-300 dark:hover:bg-slate-800/70 dark:hover:text-teal-400',
                            ]"
                        >
                            <i class="pi pi-list text-lg"></i
                            ><span>Services</span>
                        </Link>
                        <!-- Dental Records -->
                        <Link
                            :href="route('treatments.index')"
                            @click="showingSidebar = false"
                            :class="[
                                'flex items-center gap-3 rounded-lg px-4 py-3 text-sm font-medium transition',
                                route().current('treatments.*')
                                    ? 'bg-teal-50 text-teal-700 dark:bg-teal-950/60 dark:text-teal-400 dark:border dark:border-teal-800/40'
                                    : 'text-gray-600 hover:bg-gray-50 hover:text-teal-700 dark:text-slate-300 dark:hover:bg-slate-800/70 dark:hover:text-teal-400',
                            ]"
                        >
                            <i class="pi pi-file text-lg"></i
                            ><span>Dental Records</span>
                        </Link>
                        <!-- Billing -->
                        <Link
                            :href="route('billing.index')"
                            @click="showingSidebar = false"
                            :class="[
                                'flex items-center gap-3 rounded-lg px-4 py-3 text-sm font-medium transition',
                                route().current('billing.*')
                                    ? 'bg-teal-50 text-teal-700 dark:bg-teal-950/60 dark:text-teal-400 dark:border dark:border-teal-800/40'
                                    : 'text-gray-600 hover:bg-gray-50 hover:text-teal-700 dark:text-slate-300 dark:hover:bg-slate-800/70 dark:hover:text-teal-400',
                            ]"
                        >
                            <i class="pi pi-wallet text-lg"></i
                            ><span>Billing</span>
                        </Link>
                        <!-- My Receipts -->
                        <Link
                            :href="route('payments.index')"
                            @click="showingSidebar = false"
                            :class="[
                                'flex items-center gap-3 rounded-lg px-4 py-3 text-sm font-medium transition',
                                route().current('payments.*')
                                    ? 'bg-teal-50 text-teal-700 dark:bg-teal-950/60 dark:text-teal-400 dark:border dark:border-teal-800/40'
                                    : 'text-gray-600 hover:bg-gray-50 hover:text-teal-700 dark:text-slate-300 dark:hover:bg-slate-800/70 dark:hover:text-teal-400',
                            ]"
                        >
                            <i class="pi pi-receipt text-lg"></i
                            ><span>My Receipts</span>
                        </Link>
                        <!-- Notifications -->
                        <Link
                            :href="route('notifications.index')"
                            @click="showingSidebar = false"
                            :class="[
                                'flex items-center justify-between rounded-lg px-4 py-3 text-sm font-medium transition',
                                route().current('notifications.*')
                                    ? 'bg-teal-50 text-teal-700 font-semibold dark:bg-teal-950/60 dark:text-teal-400 dark:border dark:border-teal-800/40'
                                    : 'text-gray-600 hover:bg-gray-50 hover:text-teal-700 dark:text-slate-300 dark:hover:bg-slate-800/70 dark:hover:text-teal-400',
                            ]"
                        >
                            <div class="flex items-center gap-3">
                                <i class="pi pi-bell text-lg"></i>
                                <span>Notifications</span>
                            </div>
                            <span
                                v-if="$page.props.auth?.unread_notifications_count > 0"
                                class="inline-flex items-center justify-center rounded-full bg-teal-600 px-2 py-0.5 text-[11px] font-bold text-white shadow-xs"
                            >
                                {{ $page.props.auth.unread_notifications_count > 99 ? '99+' : $page.props.auth.unread_notifications_count }}
                            </span>
                        </Link>
                        <!-- My Profile -->
                        <Link
                            :href="route('profile.edit')"
                            @click="showingSidebar = false"
                            :class="[
                                'flex items-center gap-3 rounded-lg px-4 py-3 text-sm font-medium transition',
                                route().current('profile.*')
                                    ? 'bg-teal-50 text-teal-700 font-semibold dark:bg-teal-950/60 dark:text-teal-400 dark:border dark:border-teal-800/40'
                                    : 'text-gray-600 hover:bg-gray-50 hover:text-teal-700 dark:text-slate-300 dark:hover:bg-slate-800/70 dark:hover:text-teal-400',
                            ]"
                        >
                            <i class="pi pi-user text-lg"></i
                            ><span>My Profile</span>
                        </Link>
                    </template>

                    <template v-else>
                        <!-- Dashboard (All Roles) -->
                        <Link
                            :href="route('dashboard')"
                            @click="showingSidebar = false"
                            :class="[
                                'flex items-center gap-3 rounded-lg px-4 py-3 text-sm font-medium transition',
                                route().current('dashboard')
                                    ? 'bg-teal-50 text-teal-700 dark:bg-teal-950/60 dark:text-teal-400 dark:border dark:border-teal-800/40'
                                    : 'text-gray-600 hover:bg-gray-50 hover:text-teal-700 dark:text-slate-300 dark:hover:bg-slate-800/70 dark:hover:text-teal-400',
                            ]"
                        >
                            <i class="pi pi-home text-lg"></i
                            ><span>Dashboard</span>
                        </Link>

                        <!-- Patients (Staff, Dentist, Admin) -->
                        <Link
                            v-if="
                                [
                                    'staff',
                                    'dentist',
                                    'admin',
                                    'secretary',
                                ].includes($page.props.auth.user.role)
                            "
                            :href="route('patients.index')"
                            @click="showingSidebar = false"
                            :class="[
                                'flex items-center gap-3 rounded-lg px-4 py-3 text-sm font-medium transition',
                                route().current('patients.*')
                                    ? 'bg-teal-50 text-teal-700 dark:bg-teal-950/60 dark:text-teal-400 dark:border dark:border-teal-800/40'
                                    : 'text-gray-600 hover:bg-gray-50 hover:text-teal-700 dark:text-slate-300 dark:hover:bg-slate-800/70 dark:hover:text-teal-400',
                            ]"
                        >
                            <i class="pi pi-users text-lg"></i
                            ><span>Patients</span>
                        </Link>

                        <!-- Appointments (All Roles) -->
                        <Link
                            :href="route('appointments.index')"
                            @click="showingSidebar = false"
                            :class="[
                                'flex items-center gap-3 rounded-lg px-4 py-3 text-sm font-medium transition',
                                route().current('appointments.*')
                                    ? 'bg-teal-50 text-teal-700 dark:bg-teal-950/60 dark:text-teal-400 dark:border dark:border-teal-800/40'
                                    : 'text-gray-600 hover:bg-gray-50 hover:text-teal-700 dark:text-slate-300 dark:hover:bg-slate-800/70 dark:hover:text-teal-400',
                            ]"
                        >
                            <i class="pi pi-calendar text-lg"></i
                            ><span>Appointments</span>
                        </Link>

                        <!-- Treatments (Dentist, Staff, Admin) -->
                        <Link
                            v-if="
                                [
                                    'staff',
                                    'dentist',
                                    'admin',
                                    'secretary',
                                ].includes($page.props.auth.user.role)
                            "
                            :href="route('treatments.index')"
                            @click="showingSidebar = false"
                            :class="[
                                'flex items-center gap-3 rounded-lg px-4 py-3 text-sm font-medium transition',
                                route().current('treatments.*')
                                    ? 'bg-teal-50 text-teal-700 dark:bg-teal-950/60 dark:text-teal-400 dark:border dark:border-teal-800/40'
                                    : 'text-gray-600 hover:bg-gray-50 hover:text-teal-700 dark:text-slate-300 dark:hover:bg-slate-800/70 dark:hover:text-teal-400',
                            ]"
                        >
                            <i class="pi pi-heart text-lg"></i
                            ><span>Treatments</span>
                        </Link>

                        <!-- Billing (Staff, Dentist, Admin, Secretary) -->
                        <Link
                            v-if="
                                [
                                    'staff',
                                    'dentist',
                                    'admin',
                                    'secretary',
                                ].includes($page.props.auth.user.role)
                            "
                            :href="route('billing.index')"
                            @click="showingSidebar = false"
                            :class="[
                                'flex items-center gap-3 rounded-lg px-4 py-3 text-sm font-medium transition',
                                route().current('billing.*')
                                    ? 'bg-teal-50 text-teal-700 dark:bg-teal-950/60 dark:text-teal-400 dark:border dark:border-teal-800/40'
                                    : 'text-gray-600 hover:bg-gray-50 hover:text-teal-700 dark:text-slate-300 dark:hover:bg-slate-800/70 dark:hover:text-teal-400',
                            ]"
                        >
                            <i class="pi pi-wallet text-lg"></i
                            ><span>Billing</span>
                        </Link>

                        <!-- Payments (Staff, Dentist, Admin, Secretary) -->
                        <Link
                            v-if="
                                [
                                    'staff',
                                    'dentist',
                                    'admin',
                                    'secretary',
                                ].includes($page.props.auth.user.role)
                            "
                            :href="route('payments.index')"
                            @click="showingSidebar = false"
                            :class="[
                                'flex items-center gap-3 rounded-lg px-4 py-3 text-sm font-medium transition',
                                route().current('payments.*')
                                    ? 'bg-teal-50 text-teal-700 dark:bg-teal-950/60 dark:text-teal-400 dark:border dark:border-teal-800/40'
                                    : 'text-gray-600 hover:bg-gray-50 hover:text-teal-700 dark:text-slate-300 dark:hover:bg-slate-800/70 dark:hover:text-teal-400',
                            ]"
                        >
                            <i class="pi pi-credit-card text-lg"></i
                            ><span>Payments</span>
                        </Link>

                        <!-- Services (Staff, Dentist, Admin, Secretary) -->
                        <Link
                            v-if="
                                [
                                    'staff',
                                    'dentist',
                                    'admin',
                                    'secretary',
                                ].includes($page.props.auth.user.role)
                            "
                            :href="route('services.index')"
                            @click="showingSidebar = false"
                            :class="[
                                'flex items-center gap-3 rounded-lg px-4 py-3 text-sm font-medium transition',
                                route().current('services.*')
                                    ? 'bg-teal-50 text-teal-700 dark:bg-teal-950/60 dark:text-teal-400 dark:border dark:border-teal-800/40'
                                    : 'text-gray-600 hover:bg-gray-50 hover:text-teal-700 dark:text-slate-300 dark:hover:bg-slate-800/70 dark:hover:text-teal-400',
                            ]"
                        >
                            <i class="pi pi-list text-lg"></i
                            ><span>Services</span>
                        </Link>

                        <!-- Notifications (Staff, Dentist, Admin, Secretary) -->
                        <Link
                            v-if="
                                [
                                    'staff',
                                    'dentist',
                                    'admin',
                                    'secretary',
                                ].includes($page.props.auth.user.role)
                            "
                            :href="route('notifications.index')"
                            @click="showingSidebar = false"
                            :class="[
                                'flex items-center justify-between rounded-lg px-4 py-3 text-sm font-medium transition',
                                route().current('notifications.*')
                                    ? 'bg-teal-50 text-teal-700 font-semibold dark:bg-teal-950/60 dark:text-teal-400 dark:border dark:border-teal-800/40'
                                    : 'text-gray-600 hover:bg-gray-50 hover:text-teal-700 dark:text-slate-300 dark:hover:bg-slate-800/70 dark:hover:text-teal-400',
                            ]"
                        >
                            <div class="flex items-center gap-3">
                                <i class="pi pi-bell text-lg"></i>
                                <span>Notifications</span>
                            </div>
                            <span
                                v-if="$page.props.auth?.unread_notifications_count > 0"
                                class="inline-flex items-center justify-center rounded-full bg-teal-600 px-2 py-0.5 text-[11px] font-bold text-white shadow-xs"
                            >
                                {{ $page.props.auth.unread_notifications_count > 99 ? '99+' : $page.props.auth.unread_notifications_count }}
                            </span>
                        </Link>

                        <!-- Reports (Admin & Dentist) -->
                        <Link
                            v-if="['admin', 'dentist'].includes($page.props.auth.user.role)"
                            :href="route('reports.index')"
                            @click="showingSidebar = false"
                            :class="[
                                'flex items-center gap-3 rounded-lg px-4 py-3 text-sm font-medium transition',
                                route().current('reports.*')
                                    ? 'bg-teal-50 text-teal-700 dark:bg-teal-950/60 dark:text-teal-400 dark:border dark:border-teal-800/40'
                                    : 'text-gray-600 hover:bg-gray-50 hover:text-teal-700 dark:text-slate-300 dark:hover:bg-slate-800/70 dark:hover:text-teal-400',
                            ]"
                        >
                            <i class="pi pi-chart-bar text-lg"></i
                            ><span>Reports</span>
                        </Link>

                        <!-- Audit Log (Admin & Dentist) -->
                        <Link
                            v-if="['admin', 'dentist'].includes($page.props.auth.user.role)"
                            :href="route('audit-logs.index')"
                            @click="showingSidebar = false"
                            :class="[
                                'flex items-center gap-3 rounded-lg px-4 py-3 text-sm font-medium transition',
                                route().current('audit-logs.*')
                                    ? 'bg-teal-50 text-teal-700 dark:bg-teal-950/60 dark:text-teal-400 dark:border dark:border-teal-800/40'
                                    : 'text-gray-600 hover:bg-gray-50 hover:text-teal-700 dark:text-slate-300 dark:hover:bg-slate-800/70 dark:hover:text-teal-400',
                            ]"
                        >
                            <i class="pi pi-history text-lg"></i
                            ><span>Audit Log</span>
                        </Link>

                        <!-- Users (Admin & Dentist) -->
                        <Link
                            v-if="['admin', 'dentist'].includes($page.props.auth.user.role)"
                            :href="route('users.index')"
                            @click="showingSidebar = false"
                            :class="[
                                'flex items-center gap-3 rounded-lg px-4 py-3 text-sm font-medium transition',
                                route().current('users.*')
                                    ? 'bg-teal-50 text-teal-700 dark:bg-teal-950/60 dark:text-teal-400 dark:border dark:border-teal-800/40'
                                    : 'text-gray-600 hover:bg-gray-50 hover:text-teal-700 dark:text-slate-300 dark:hover:bg-slate-800/70 dark:hover:text-teal-400',
                            ]"
                        >
                            <i class="pi pi-users text-lg"></i><span>Users & Access</span>
                        </Link>

                        <!-- Backup & Disaster Recovery (Admin & Dentist) -->
                        <Link
                            v-if="['admin', 'dentist'].includes($page.props.auth.user.role)"
                            :href="route('backup.index')"
                            @click="showingSidebar = false"
                            :class="[
                                'flex items-center gap-3 rounded-lg px-4 py-3 text-sm font-medium transition',
                                route().current('backup.*')
                                    ? 'bg-teal-50 text-teal-700 dark:bg-teal-950/60 dark:text-teal-400 dark:border dark:border-teal-800/40'
                                    : 'text-gray-600 hover:bg-gray-50 hover:text-teal-700 dark:text-slate-300 dark:hover:bg-slate-800/70 dark:hover:text-teal-400',
                            ]"
                        >
                            <i class="pi pi-database text-lg"></i><span>Backup & Recovery</span>
                        </Link>
                    </template>
                </nav>

                <!-- Logout -->
                <div class="border-t border-gray-200 dark:border-slate-800 p-3">
                    <Link
                        :href="route('logout')"
                        method="post"
                        as="button"
                        class="flex w-full items-center gap-3 rounded-lg px-4 py-3 text-left text-sm font-medium text-gray-600 transition hover:bg-red-50 hover:text-red-600 dark:text-slate-400 dark:hover:bg-red-950/30 dark:hover:text-red-400"
                    >
                        <i class="pi pi-sign-out text-lg"></i>
                        <span>Logout</span>
                    </Link>
                </div>
            </div>
        </aside>

        <!-- Main Content -->
        <div class="min-h-screen lg:ml-64 print:ml-0 print:min-h-0 print:p-0">
            <!-- Top Navbar -->
            <header class="print:hidden sticky top-0 z-30 border-b border-gray-200 bg-white dark:border-slate-800 dark:bg-slate-900/95 dark:backdrop-blur-md">
                <div
                    class="flex h-16 items-center justify-between px-4 sm:px-6 lg:px-8"
                >
                    <!-- Mobile Menu -->
                    <button
                        @click="showingSidebar = true"
                        class="rounded-lg p-2 text-gray-500 hover:bg-gray-100 dark:text-slate-400 dark:hover:bg-slate-800 lg:hidden"
                        aria-label="Open sidebar"
                    >
                        <i class="pi pi-bars text-xl"></i>
                    </button>

                    <!-- Desktop Spacer -->
                    <div class="hidden lg:block"></div>

                    <!-- Right Controls: Philippine Time, Theme Toggle, Notifications & User Profile -->
                    <div class="flex items-center gap-2.5 sm:gap-3">
                        <!-- Philippine Time Badge -->
                        <PhilippineTime />

                        <!-- Theme Toggle Button -->
                        <ThemeToggle />

                        <!-- Notification Bell (Patient & Staff) -->
                        <Link
                            :href="route('notifications.index')"
                            class="relative flex h-9 w-9 items-center justify-center rounded-xl border border-gray-200 bg-white text-gray-600 transition hover:bg-gray-50 hover:text-teal-700 shadow-2xs dark:border-slate-700 dark:bg-slate-800 dark:text-slate-300 dark:hover:bg-slate-700 dark:hover:text-teal-400"
                            title="Notifications & Alerts"
                        >
                            <i class="pi pi-bell text-base"></i>
                            <span
                                v-if="$page.props.auth?.unread_notifications_count > 0"
                                class="absolute -top-1 -right-1 flex h-4 min-w-4 items-center justify-center rounded-full bg-teal-600 px-1 text-[10px] font-bold text-white shadow-xs"
                            >
                                {{ $page.props.auth.unread_notifications_count > 99 ? '99+' : $page.props.auth.unread_notifications_count }}
                            </span>
                        </Link>

                        <!-- User -->
                        <div class="relative">
                            <Dropdown align="right" width="48">
                                <template #trigger>
                                    <button
                                        class="flex items-center gap-2 rounded-lg px-2 py-2 text-sm font-medium text-gray-600 hover:bg-gray-50 sm:gap-3 sm:px-3 dark:text-slate-300 dark:hover:bg-slate-800"
                                    >
                                        <!-- Avatar -->
                                        <div
                                            class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-teal-100 font-semibold text-teal-700 dark:bg-teal-900/70 dark:text-teal-300"
                                        >
                                            {{
                                                $page.props.auth.user.name
                                                    .charAt(0)
                                                    .toUpperCase()
                                            }}
                                        </div>

                                        <!-- User Information -->
                                        <div class="hidden text-left sm:block">
                                            <div
                                                class="font-semibold text-gray-800 dark:text-slate-100"
                                            >
                                                {{ $page.props.auth.user.name }}
                                            </div>

                                            <div
                                                class="max-w-[150px] truncate text-xs text-gray-500 dark:text-slate-400"
                                            >
                                                {{
                                                    $page.props.auth.user.email
                                                }}
                                            </div>
                                        </div>

                                        <!-- Dropdown Icon -->
                                        <i
                                            class="pi pi-chevron-down hidden text-xs sm:block text-gray-400 dark:text-slate-500"
                                        ></i>
                                    </button>
                                </template>

                                <template #content>
                                    <DropdownLink :href="route('profile.edit')">
                                        <div class="flex items-center gap-2">
                                            <i class="pi pi-user"></i>
                                            <span>Profile</span>
                                        </div>
                                    </DropdownLink>

                                    <DropdownLink
                                        :href="route('logout')"
                                        method="post"
                                        as="button"
                                    >
                                        <div class="flex items-center gap-2">
                                            <i class="pi pi-sign-out"></i>
                                            <span>Log Out</span>
                                        </div>
                                    </DropdownLink>
                                </template>
                            </Dropdown>
                        </div>
                    </div>
                </div>
            </header>

            <!-- Page Heading -->
            <header
                v-if="$slots.header"
                class="print:hidden border-b border-gray-200 bg-white dark:border-slate-800 dark:bg-slate-900/90"
            >
                <div
                    class="mx-auto max-w-7xl px-4 py-5 sm:px-6 sm:py-6 lg:px-8"
                >
                    <slot name="header" />
                </div>
            </header>

            <!-- Page Content -->
            <main class="w-full">
                <slot />
            </main>
        </div>
    </div>
</template>
