<script setup>
import { computed } from "vue";
import { Head, Link, router } from "@inertiajs/vue3";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";

const props = defineProps({
    notifications: {
        type: Object,
        default: () => ({ data: [] }),
    },
    filters: {
        type: Object,
        default: () => ({ status: "all", category: "all" }),
    },
    metrics: {
        type: Object,
        default: () => ({
            total: 0,
            unread: 0,
            read: 0,
            appointments: 0,
            treatments: 0,
            billing: 0,
        }),
    },
});

const currentStatus = computed(() => props.filters?.status || "all");
const currentCategory = computed(() => props.filters?.category || "all");

const filterNotifications = (newStatus, newCategory) => {
    router.get(
        route("notifications.index"),
        {
            status: newStatus !== undefined ? newStatus : currentStatus.value,
            category: newCategory !== undefined ? newCategory : currentCategory.value,
        },
        { preserveState: true, preserveScroll: true }
    );
};

const markAsRead = (id) => {
    router.patch(
        route("notifications.read", id),
        {},
        { preserveScroll: true }
    );
};

const markAllAsRead = () => {
    router.post(
        route("notifications.read-all"),
        {},
        { preserveScroll: true }
    );
};

const deleteNotification = (id) => {
    router.delete(
        route("notifications.destroy", id),
        { preserveScroll: true }
    );
};

const clearRead = () => {
    if (confirm("Are you sure you want to remove all read notifications?")) {
        router.delete(
            route("notifications.clear-read"),
            { preserveScroll: true }
        );
    }
};

// Category config helper
const getCategoryMeta = (category) => {
    switch (category) {
        case "appointment":
            return {
                icon: "pi pi-calendar",
                badgeClass: "bg-teal-50 dark:bg-teal-950/60 text-teal-700 dark:text-teal-300 border-teal-200 dark:border-teal-800",
                iconContainer: "bg-teal-50 dark:bg-teal-950/60 text-teal-600 dark:text-teal-400 border border-teal-100 dark:border-teal-800",
                label: "Appointment",
            };
        case "treatment":
            return {
                icon: "pi pi-heart",
                badgeClass: "bg-blue-50 dark:bg-blue-950/60 text-blue-700 dark:text-blue-300 border-blue-200 dark:border-blue-800",
                iconContainer: "bg-blue-50 dark:bg-blue-950/60 text-blue-600 dark:text-blue-400 border border-blue-100 dark:border-blue-800",
                label: "Clinical Record",
            };
        case "billing":
            return {
                icon: "pi pi-wallet",
                badgeClass: "bg-purple-50 dark:bg-purple-950/60 text-purple-700 dark:text-purple-300 border-purple-200 dark:border-purple-800",
                iconContainer: "bg-purple-50 dark:bg-purple-950/60 text-purple-600 dark:text-purple-400 border border-purple-100 dark:border-purple-800",
                label: "Billing Statement",
            };
        case "payment":
            return {
                icon: "pi pi-receipt",
                badgeClass: "bg-emerald-50 dark:bg-emerald-950/60 text-emerald-700 dark:text-emerald-300 border-emerald-200 dark:border-emerald-800",
                iconContainer: "bg-emerald-50 dark:bg-emerald-950/60 text-emerald-600 dark:text-emerald-400 border border-emerald-100 dark:border-emerald-800",
                label: "Official Receipt",
            };
        case "medical":
        case "clearance":
            return {
                icon: "pi pi-shield",
                badgeClass: "bg-amber-50 dark:bg-amber-950/60 text-amber-800 dark:text-amber-300 border-amber-200 dark:border-amber-800",
                iconContainer: "bg-amber-50 dark:bg-amber-950/60 text-amber-600 dark:text-amber-400 border border-amber-200 dark:border-amber-800",
                label: "Medical Clearance Requirement",
            };
        default:
            return {
                icon: "pi pi-bell",
                badgeClass: "bg-gray-50 dark:bg-slate-800 text-gray-700 dark:text-slate-300 border-gray-200 dark:border-slate-700",
                iconContainer: "bg-gray-50 dark:bg-slate-800 text-gray-600 dark:text-slate-400 border border-gray-100 dark:border-slate-700",
                label: "General Notice",
            };
    }
};

// Format human relative date
const formatNotificationDate = (dateStr) => {
    if (!dateStr) return "";
    const date = new Date(dateStr);
    const now = new Date();
    const diffMs = now - date;
    const diffSec = Math.floor(diffMs / 1000);
    const diffMin = Math.floor(diffSec / 60);
    const diffHrs = Math.floor(diffMin / 60);
    const diffDays = Math.floor(diffHrs / 24);

    if (diffMin < 1) return "Just now";
    if (diffMin < 60) return `${diffMin}m ago`;
    if (diffHrs < 24) return `${diffHrs}h ago`;
    if (diffDays === 1) return "Yesterday";
    if (diffDays < 7) return `${diffDays}d ago`;

    return date.toLocaleDateString("en-US", {
        month: "short",
        day: "numeric",
        year: "numeric",
        hour: "numeric",
        minute: "2-digit",
    });
};
</script>

<template>
    <Head title="Notifications & Alerts" />

    <AuthenticatedLayout>
        <div class="py-8 bg-gray-50/50 dark:bg-slate-950 min-h-[calc(100vh-4rem)]">
            <div class="mx-auto max-w-5xl px-4 sm:px-6 lg:px-8 space-y-6">

                <!-- Header Banner -->
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 border-b border-gray-200 dark:border-slate-800 pb-5">
                    <div>
                        <div class="flex items-center gap-3">
                            <div class="flex h-11 w-11 items-center justify-center rounded-2xl bg-teal-50 dark:bg-slate-800 text-teal-700 dark:text-teal-400 border border-teal-100 dark:border-slate-700">
                                <i class="pi pi-bell text-xl"></i>
                            </div>
                            <div>
                                <h1 class="text-2xl font-extrabold text-gray-900 dark:text-white tracking-tight">
                                    Notifications & Alerts
                                </h1>
                                <p class="text-xs sm:text-sm text-gray-500 dark:text-slate-400 mt-0.5">
                                    Real-time clinical updates, appointment schedules, and billing notices sent to you and your registered email.
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Top Actions -->
                    <div class="flex items-center gap-2 self-start sm:self-auto shrink-0">
                        <button
                            v-if="metrics.unread > 0"
                            type="button"
                            @click="markAllAsRead"
                            class="inline-flex items-center gap-1.5 rounded-xl border border-teal-200 dark:border-teal-800 bg-white dark:bg-slate-800 px-3.5 py-2 text-xs font-semibold text-teal-800 dark:text-teal-300 shadow-2xs hover:bg-teal-50 dark:hover:bg-slate-700 transition"
                        >
                            <i class="pi pi-check text-xs"></i>
                            <span>Mark all as read</span>
                        </button>

                        <button
                            v-if="metrics.read > 0"
                            type="button"
                            @click="clearRead"
                            class="inline-flex items-center gap-1.5 rounded-xl border border-gray-200 dark:border-slate-700 bg-white dark:bg-slate-800 px-3 py-2 text-xs font-semibold text-gray-600 dark:text-slate-300 shadow-2xs hover:bg-gray-50 dark:hover:bg-slate-700 hover:text-red-600 dark:hover:text-rose-400 transition"
                        >
                            <i class="pi pi-trash text-xs"></i>
                            <span>Clear read</span>
                        </button>
                    </div>
                </div>

                <!-- Category Filters Row -->
                <div class="flex flex-wrap items-center justify-between gap-3 bg-white dark:bg-slate-900 p-3.5 rounded-2xl border border-gray-200/90 dark:border-slate-800 shadow-2xs">
                    <!-- Category Tabs -->
                    <div class="flex flex-wrap items-center gap-1.5 text-xs">
                        <button
                            type="button"
                            @click="filterNotifications(undefined, 'all')"
                            :class="[
                                'rounded-xl px-3 py-1.5 font-semibold transition',
                                currentCategory === 'all'
                                    ? 'bg-teal-700 text-white shadow-2xs'
                                    : 'text-gray-600 dark:text-slate-300 hover:bg-gray-100 dark:hover:bg-slate-800 hover:text-gray-900 dark:hover:text-white',
                            ]"
                        >
                            All ({{ metrics.total }})
                        </button>

                        <button
                            type="button"
                            @click="filterNotifications(undefined, 'appointment')"
                            :class="[
                                'rounded-xl px-3 py-1.5 font-semibold transition inline-flex items-center gap-1.5',
                                currentCategory === 'appointment'
                                    ? 'bg-teal-700 text-white shadow-2xs'
                                    : 'text-gray-600 dark:text-slate-300 hover:bg-gray-100 dark:hover:bg-slate-800 hover:text-gray-900 dark:hover:text-white',
                            ]"
                        >
                            <i class="pi pi-calendar text-[11px]"></i>
                            <span>Appointments</span>
                        </button>

                        <button
                            type="button"
                            @click="filterNotifications(undefined, 'treatment')"
                            :class="[
                                'rounded-xl px-3 py-1.5 font-semibold transition inline-flex items-center gap-1.5',
                                currentCategory === 'treatment'
                                    ? 'bg-teal-700 text-white shadow-2xs'
                                    : 'text-gray-600 dark:text-slate-300 hover:bg-gray-100 dark:hover:bg-slate-800 hover:text-gray-900 dark:hover:text-white',
                            ]"
                        >
                            <i class="pi pi-heart text-[11px]"></i>
                            <span>Dental Records</span>
                        </button>

                        <button
                            type="button"
                            @click="filterNotifications(undefined, 'billing')"
                            :class="[
                                'rounded-xl px-3 py-1.5 font-semibold transition inline-flex items-center gap-1.5',
                                currentCategory === 'billing'
                                    ? 'bg-teal-700 text-white shadow-2xs'
                                    : 'text-gray-600 dark:text-slate-300 hover:bg-gray-100 dark:hover:bg-slate-800 hover:text-gray-900 dark:hover:text-white',
                            ]"
                        >
                            <i class="pi pi-wallet text-[11px]"></i>
                            <span>Billing & Payments</span>
                        </button>

                        <button
                            type="button"
                            @click="filterNotifications(undefined, 'medical')"
                            :class="[
                                'rounded-xl px-3 py-1.5 font-semibold transition inline-flex items-center gap-1.5',
                                currentCategory === 'medical'
                                    ? 'bg-amber-600 text-white shadow-2xs'
                                    : 'text-gray-600 dark:text-slate-300 hover:bg-gray-100 dark:hover:bg-slate-800 hover:text-gray-900 dark:hover:text-white',
                            ]"
                        >
                            <i class="pi pi-shield text-[11px]"></i>
                            <span>Medical & Clearance</span>
                        </button>
                    </div>

                    <!-- Status Tabs: All vs Unread -->
                    <div class="flex items-center gap-1 bg-gray-100 dark:bg-slate-800 p-1 rounded-xl text-xs">
                        <button
                            type="button"
                            @click="filterNotifications('all', undefined)"
                            :class="[
                                'rounded-lg px-2.5 py-1 font-semibold transition',
                                currentStatus === 'all'
                                    ? 'bg-white dark:bg-slate-700 text-gray-900 dark:text-white shadow-2xs'
                                    : 'text-gray-500 dark:text-slate-400 hover:text-gray-800 dark:hover:text-slate-200',
                            ]"
                        >
                            All
                        </button>

                        <button
                            type="button"
                            @click="filterNotifications('unread', undefined)"
                            :class="[
                                'rounded-lg px-2.5 py-1 font-semibold transition inline-flex items-center gap-1',
                                currentStatus === 'unread'
                                    ? 'bg-white dark:bg-slate-700 text-teal-800 dark:text-teal-300 shadow-2xs'
                                    : 'text-gray-500 dark:text-slate-400 hover:text-gray-800 dark:hover:text-slate-200',
                            ]"
                        >
                            <span>Unread</span>
                            <span
                                v-if="metrics.unread > 0"
                                class="rounded-full bg-teal-600 px-1.5 py-0.2 text-[10px] font-bold text-white"
                            >
                                {{ metrics.unread }}
                            </span>
                        </button>
                    </div>
                </div>

                <!-- Notifications List -->
                <div v-if="notifications.data && notifications.data.length > 0" class="space-y-3.5">
                    <div
                        v-for="item in notifications.data"
                        :key="item.id"
                        :class="[
                            'group relative rounded-2xl border p-5 transition duration-200 hover:shadow-md',
                            item.read_at
                                ? 'border-gray-200/90 dark:border-slate-800 bg-white dark:bg-slate-900 text-gray-800 dark:text-slate-200'
                                : 'border-teal-300 dark:border-teal-700 bg-teal-50/25 dark:bg-slate-900 shadow-2xs ring-1 ring-teal-200/50 dark:ring-teal-800/60',
                        ]"
                    >
                        <div class="flex items-start gap-4">
                            <!-- Category Vector Badge -->
                            <div
                                :class="[
                                    'flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl text-base',
                                    getCategoryMeta(item.data?.category).iconContainer,
                                ]"
                            >
                                <i :class="getCategoryMeta(item.data?.category).icon"></i>
                            </div>

                            <!-- Content Body -->
                            <div class="flex-1 min-w-0">
                                <div class="flex flex-wrap items-center justify-between gap-2 mb-1">
                                    <div class="flex items-center gap-2">
                                        <!-- Unread Dot -->
                                        <span
                                            v-if="!item.read_at"
                                            class="h-2 w-2 rounded-full bg-teal-600 dark:bg-teal-400 animate-pulse"
                                            title="Unread notification"
                                        ></span>

                                        <h3
                                            :class="[
                                                'text-sm font-bold',
                                                item.read_at ? 'text-gray-900 dark:text-white' : 'text-teal-950 dark:text-teal-200 font-extrabold',
                                            ]"
                                        >
                                            {{ item.data?.title || "Clinical Notification" }}
                                        </h3>

                                        <span
                                            :class="[
                                                'text-[10px] font-semibold uppercase tracking-wider px-2 py-0.5 rounded-full border',
                                                getCategoryMeta(item.data?.category).badgeClass,
                                            ]"
                                        >
                                            {{ getCategoryMeta(item.data?.category).label }}
                                        </span>
                                    </div>

                                    <!-- Time and Read Status -->
                                    <span class="text-xs text-gray-400 dark:text-slate-400 font-medium">
                                        {{ formatNotificationDate(item.created_at) }}
                                    </span>
                                </div>

                                <!-- Notification Message -->
                                <p class="text-xs sm:text-sm text-gray-600 dark:text-slate-300 leading-relaxed mt-1">
                                    {{ item.data?.message }}
                                </p>

                                <!-- Details Grid (Procedure, Date, Dentist, Amount) -->
                                <div
                                    v-if="item.data?.details && Object.keys(item.data.details).length > 0"
                                    class="mt-3 grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-2.5 rounded-xl bg-gray-50/90 dark:bg-slate-800/90 p-3.5 border border-gray-200/80 dark:border-slate-700 text-xs shadow-2xs"
                                >
                                    <div
                                        v-for="(val, key) in item.data.details"
                                        :key="key"
                                        class="flex flex-col leading-tight"
                                    >
                                        <span class="text-[10px] font-bold uppercase tracking-wider text-gray-500 dark:text-slate-400">
                                            {{ key }}
                                        </span>
                                        <span class="font-semibold text-gray-900 dark:text-slate-100 mt-1 break-words">
                                            {{ val }}
                                        </span>
                                    </div>
                                </div>

                                <!-- Action Buttons -->
                                <div class="mt-4 flex flex-wrap items-center justify-between gap-2 border-t border-gray-100 dark:border-slate-800 pt-3">
                                    <div class="flex items-center gap-2">
                                        <Link
                                            v-if="item.data?.action_url"
                                            :href="item.data.action_url"
                                            class="inline-flex items-center gap-1.5 rounded-lg bg-teal-700 hover:bg-teal-800 text-white px-3.5 py-1.5 text-xs font-semibold shadow-2xs transition"
                                        >
                                            <span>{{ item.data.action_text || "View Details" }}</span>
                                            <i class="pi pi-arrow-right text-[10px]"></i>
                                        </Link>
                                    </div>

                                    <div class="flex items-center gap-2">
                                        <button
                                            v-if="!item.read_at"
                                            type="button"
                                            @click="markAsRead(item.id)"
                                            class="inline-flex items-center gap-1 text-xs font-semibold text-teal-700 dark:text-teal-400 hover:text-teal-900 dark:hover:text-teal-300 transition px-2 py-1"
                                        >
                                            <i class="pi pi-check text-[10px]"></i>
                                            <span>Mark as read</span>
                                        </button>

                                        <button
                                            type="button"
                                            @click="deleteNotification(item.id)"
                                            class="inline-flex items-center gap-1 text-xs text-gray-400 dark:text-slate-400 hover:text-red-600 dark:hover:text-rose-400 transition px-2 py-1 rounded-lg"
                                            title="Delete notification"
                                        >
                                            <i class="pi pi-trash text-[11px]"></i>
                                            <span class="sr-only">Delete</span>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Pagination -->
                    <div
                        v-if="notifications.links && notifications.links.length > 3"
                        class="flex items-center justify-center gap-1.5 pt-4"
                    >
                        <Link
                            v-for="link in notifications.links"
                            :key="link.label"
                            :href="link.url || '#'"
                            :class="[
                                'rounded-xl px-3 py-1.5 text-xs font-semibold transition',
                                link.active
                                    ? 'bg-teal-700 text-white'
                                    : link.url
                                    ? 'bg-white dark:bg-slate-800 border border-gray-200 dark:border-slate-700 text-gray-700 dark:text-slate-200 hover:bg-gray-50 dark:hover:bg-slate-700'
                                    : 'bg-gray-100 dark:bg-slate-800/50 text-gray-400 dark:text-slate-500 cursor-not-allowed',
                            ]"
                            v-html="link.label"
                        />
                    </div>
                </div>

                <!-- Empty State -->
                <div v-else class="rounded-3xl border border-gray-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-12 text-center shadow-2xs">
                    <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-teal-50 dark:bg-slate-800 text-teal-600 dark:text-teal-400 border border-teal-100 dark:border-slate-700 mb-3.5">
                        <i class="pi pi-bell-slash text-2xl"></i>
                    </div>
                    <h3 class="text-base font-bold text-gray-900 dark:text-white">
                        No Notifications Found
                    </h3>
                    <p class="text-xs sm:text-sm text-gray-500 dark:text-slate-400 mt-1 max-w-sm mx-auto">
                        {{ currentStatus === 'unread' ? "You're all caught up! There are no unread notifications right now." : "You don't have any notifications under this category yet." }}
                    </p>
                    <div class="mt-5">
                        <Link
                            :href="route('dashboard')"
                            class="inline-flex items-center gap-1.5 rounded-xl border border-gray-300 dark:border-slate-700 bg-white dark:bg-slate-800 px-4 py-2 text-xs font-semibold text-gray-700 dark:text-slate-200 hover:bg-gray-50 dark:hover:bg-slate-700 transition"
                        >
                            <i class="pi pi-home text-xs"></i>
                            <span>Return to Dashboard</span>
                        </Link>
                    </div>
                </div>

            </div>
        </div>
    </AuthenticatedLayout>
</template>

