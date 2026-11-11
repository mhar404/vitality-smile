<script setup>
import { ref, watch, computed } from "vue";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import Modal from "@/Components/Modal.vue";
import { Head, Link, router, useForm, usePage } from "@inertiajs/vue3";

const props = defineProps({
    logs: {
        type: Object,
        required: true,
    },
    filters: {
        type: Object,
        default: () => ({}),
    },
    metrics: {
        type: Object,
        default: () => ({
            total_logs: 0,
            today_logs: 0,
            clinical_operations: 0,
            financial_operations: 0,
        }),
    },
    availableModules: {
        type: Array,
        default: () => [],
    },
    availableActions: {
        type: Array,
        default: () => [],
    },
});

const page = usePage();

// Filter states
const search = ref(props.filters.search || "");
const moduleFilter = ref(props.filters.module || "");
const actionFilter = ref(props.filters.action || "");
const roleFilter = ref(props.filters.role || "");
const dateFrom = ref(props.filters.date_from || "");
const dateTo = ref(props.filters.date_to || "");

let searchTimeout = null;

const applyFilters = () => {
    router.get(
        route("audit-logs.index"),
        {
            search: search.value || undefined,
            module: moduleFilter.value || undefined,
            action: actionFilter.value || undefined,
            role: roleFilter.value || undefined,
            date_from: dateFrom.value || undefined,
            date_to: dateTo.value || undefined,
        },
        {
            preserveState: true,
            preserveScroll: true,
            replace: true,
        }
    );
};

watch([search, moduleFilter, actionFilter, roleFilter, dateFrom, dateTo], () => {
    if (searchTimeout) clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        applyFilters();
    }, 300);
});

const resetFilters = () => {
    search.value = "";
    moduleFilter.value = "";
    actionFilter.value = "";
    roleFilter.value = "";
    dateFrom.value = "";
    dateTo.value = "";
};

const hasActiveFilters = computed(() => {
    return !!(
        search.value ||
        moduleFilter.value ||
        actionFilter.value ||
        roleFilter.value ||
        dateFrom.value ||
        dateTo.value
    );
});

// CSV Export URL
const exportUrl = computed(() => {
    return route("audit-logs.export", {
        search: search.value || undefined,
        module: moduleFilter.value || undefined,
        action: actionFilter.value || undefined,
        role: roleFilter.value || undefined,
        date_from: dateFrom.value || undefined,
        date_to: dateTo.value || undefined,
    });
});

// Diff / Details Inspector Modal
const showDiffModal = ref(false);
const activeLog = ref(null);
const viewMode = ref("formatted"); // 'formatted' | 'raw'

const inspectLog = (log) => {
    activeLog.value = log;
    viewMode.value = "formatted";
    showDiffModal.value = true;
};

// Purge / Retention Modal
const showPurgeModal = ref(false);
const purgeForm = useForm({
    days: "90",
});

const openPurgeModal = () => {
    purgeForm.days = "90";
    showPurgeModal.value = true;
};

const submitPurge = () => {
    if (
        confirm(
            `Are you sure you want to permanently delete all audit logs older than ${purgeForm.days} days? This action cannot be reversed.`
        )
    ) {
        purgeForm.delete(route("audit-logs.purge"), {
            preserveScroll: true,
            onSuccess: () => {
                showPurgeModal.value = false;
            },
        });
    }
};

// Formatting helpers
const formatDateTime = (dateString) => {
    if (!dateString) return "N/A";
    const date = new Date(dateString);
    return new Intl.DateTimeFormat("en-PH", {
        month: "short",
        day: "numeric",
        year: "numeric",
        hour: "numeric",
        minute: "2-digit",
        hour12: true,
        timeZone: "Asia/Manila",
    }).format(date);
};

const formatTimeAgo = (dateString) => {
    if (!dateString) return "";
    const date = new Date(dateString);
    const now = new Date();
    const diffSeconds = Math.floor((now - date) / 1000);

    if (diffSeconds < 60) return "just now";
    const diffMinutes = Math.floor(diffSeconds / 60);
    if (diffMinutes < 60) return `${diffMinutes}m ago`;
    const diffHours = Math.floor(diffMinutes / 60);
    if (diffHours < 24) return `${diffHours}h ago`;
    const diffDays = Math.floor(diffHours / 24);
    if (diffDays < 30) return `${diffDays}d ago`;
    return "";
};

// Visual badge mappings
const getModuleBadge = (mod) => {
    const key = (mod || "").toLowerCase();
    switch (key) {
        case "appointments":
            return {
                label: "Appointments",
                icon: "pi pi-calendar",
                class: "bg-teal-50 text-teal-700 border-teal-200",
            };
        case "patients":
            return {
                label: "Patients",
                icon: "pi pi-users",
                class: "bg-emerald-50 text-emerald-700 border-emerald-200",
            };
        case "treatments":
            return {
                label: "Treatments",
                icon: "pi pi-file-edit",
                class: "bg-indigo-50 text-indigo-700 border-indigo-200",
            };
        case "billing":
            return {
                label: "Billing",
                icon: "pi pi-receipt",
                class: "bg-amber-50 text-amber-700 border-amber-200",
            };
        case "payments":
            return {
                label: "Payments",
                icon: "pi pi-credit-card",
                class: "bg-cyan-50 text-cyan-700 border-cyan-200",
            };
        case "services":
            return {
                label: "Services",
                icon: "pi pi-sparkles",
                class: "bg-violet-50 text-violet-700 border-violet-200",
            };
        case "audit_log":
            return {
                label: "Audit System",
                icon: "pi pi-shield",
                class: "bg-purple-50 text-purple-700 border-purple-200",
            };
        case "auth":
            return {
                label: "Security / Auth",
                icon: "pi pi-lock",
                class: "bg-rose-50 text-rose-700 border-rose-200",
            };
        default:
            return {
                label: mod || "System",
                icon: "pi pi-cog",
                class: "bg-gray-50 text-gray-700 border-gray-200",
            };
    }
};

const getActionPill = (action) => {
    const act = (action || "").toLowerCase();
    if (act.includes("created") || act.includes("documented")) {
        return "bg-emerald-100 text-emerald-800 border-emerald-200";
    }
    if (act.includes("confirmed") || act.includes("completed")) {
        return "bg-teal-100 text-teal-800 border-teal-200";
    }
    if (act.includes("updated") || act.includes("rescheduled") || act.includes("status_changed")) {
        return "bg-amber-100 text-amber-800 border-amber-200";
    }
    if (act.includes("deleted") || act.includes("cancelled") || act.includes("archived") || act.includes("purged")) {
        return "bg-rose-100 text-rose-800 border-rose-200";
    }
    if (act.includes("exported")) {
        return "bg-purple-100 text-purple-800 border-purple-200";
    }
    return "bg-gray-100 text-gray-800 border-gray-200";
};

const getRoleBadge = (role) => {
    const r = (role || "").toLowerCase();
    switch (r) {
        case "admin":
            return "bg-purple-100 text-purple-800 border-purple-200";
        case "dentist":
            return "bg-teal-100 text-teal-800 border-teal-200";
        case "staff":
        case "secretary":
            return "bg-sky-100 text-sky-800 border-sky-200";
        case "patient":
            return "bg-emerald-100 text-emerald-800 border-emerald-200";
        default:
            return "bg-gray-100 text-gray-700 border-gray-200";
    }
};

const getRoleInitial = (name) => {
    if (!name) return "S";
    return name.trim().charAt(0).toUpperCase();
};
</script>

<template>
    <Head title="Audit Log - Vitality Smile Dental Clinic" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <div class="flex items-center gap-2">
                        <span class="inline-flex items-center gap-1.5 rounded-full border border-teal-200 bg-teal-50 px-2.5 py-0.5 text-xs font-semibold text-teal-800">
                            <i class="pi pi-shield text-[10px]"></i>
                            Admin Security Console
                        </span>
                        <span class="inline-flex items-center gap-1 rounded-full border border-gray-200 bg-white px-2 py-0.5 text-[11px] font-medium text-gray-500">
                            <span class="h-1.5 w-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                            Live Auditing Active
                        </span>
                    </div>
                    <h2 class="mt-1 text-2xl font-bold tracking-tight text-gray-900">
                        System Audit & Compliance Log
                    </h2>
                    <p class="text-xs text-gray-500 sm:text-sm">
                        Comprehensive trail of user actions, clinical modifications, billing events, and security access.
                    </p>
                </div>

                <!-- Action Controls -->
                <div class="flex flex-wrap items-center gap-2">
                    <a
                        :href="exportUrl"
                        class="inline-flex items-center gap-2 rounded-xl border border-gray-300 bg-white px-3.5 py-2 text-xs font-semibold text-gray-700 shadow-sm transition hover:bg-gray-50 hover:text-teal-700"
                        title="Export current filtered view to CSV"
                    >
                        <i class="pi pi-download text-teal-600"></i>
                        <span>Export CSV</span>
                    </a>

                    <button
                        type="button"
                        @click="openPurgeModal"
                        class="inline-flex items-center gap-2 rounded-xl border border-rose-200 bg-rose-50 px-3.5 py-2 text-xs font-semibold text-rose-700 shadow-sm transition hover:bg-rose-100"
                        title="Configure log retention and purge older entries"
                    >
                        <i class="pi pi-trash text-rose-600"></i>
                        <span>Retention & Purge</span>
                    </button>
                </div>
            </div>
        </template>

        <div class="py-6 sm:py-8">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 space-y-6">
                <!-- Flash Notification Banner -->
                <div
                    v-if="page.props.flash?.success"
                    class="flex items-center justify-between rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-emerald-900 shadow-sm"
                >
                    <div class="flex items-center gap-3">
                        <i class="pi pi-check-circle text-lg text-emerald-600"></i>
                        <span class="text-xs font-semibold sm:text-sm">{{ page.props.flash.success }}</span>
                    </div>
                    <button
                        @click="page.props.flash.success = null"
                        class="text-emerald-700 hover:text-emerald-900"
                    >
                        <i class="pi pi-times text-xs"></i>
                    </button>
                </div>

                <div
                    v-if="page.props.flash?.error"
                    class="flex items-center justify-between rounded-xl border border-rose-200 bg-rose-50 p-4 text-rose-900 shadow-sm"
                >
                    <div class="flex items-center gap-3">
                        <i class="pi pi-exclamation-circle text-lg text-rose-600"></i>
                        <span class="text-xs font-semibold sm:text-sm">{{ page.props.flash.error }}</span>
                    </div>
                    <button
                        @click="page.props.flash.error = null"
                        class="text-rose-700 hover:text-rose-900"
                    >
                        <i class="pi pi-times text-xs"></i>
                    </button>
                </div>

                <!-- 4 Top Metric Cards -->
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    <!-- Total Logs -->
                    <div class="rounded-2xl border border-gray-100 bg-white p-5 shadow-sm">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-semibold uppercase tracking-wider text-gray-500">
                                Total Logged Events
                            </span>
                            <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-teal-50 text-teal-600">
                                <i class="pi pi-database text-sm"></i>
                            </div>
                        </div>
                        <p class="mt-2 text-2xl font-black text-gray-900">
                            {{ Number(metrics.total_logs || 0).toLocaleString() }}
                        </p>
                        <p class="mt-1 text-xs text-gray-500">
                            Permanent audit records preserved
                        </p>
                    </div>

                    <!-- Today's Logs -->
                    <div class="rounded-2xl border border-gray-100 bg-white p-5 shadow-sm">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-semibold uppercase tracking-wider text-gray-500">
                                Today's Activity
                            </span>
                            <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-emerald-50 text-emerald-600">
                                <i class="pi pi-calendar text-sm"></i>
                            </div>
                        </div>
                        <div class="mt-2 flex items-baseline gap-2">
                            <p class="text-2xl font-black text-gray-900">
                                {{ Number(metrics.today_logs || 0).toLocaleString() }}
                            </p>
                            <span class="text-xs font-medium text-emerald-600">events</span>
                        </div>
                        <p class="mt-1 text-xs text-gray-500">
                            Logged since 12:00 AM PHT
                        </p>
                    </div>

                    <!-- Clinical Operations -->
                    <div class="rounded-2xl border border-gray-100 bg-white p-5 shadow-sm">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-semibold uppercase tracking-wider text-gray-500">
                                Clinical Operations
                            </span>
                            <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-indigo-50 text-indigo-600">
                                <i class="pi pi-heart text-sm"></i>
                            </div>
                        </div>
                        <p class="mt-2 text-2xl font-black text-gray-900">
                            {{ Number(metrics.clinical_operations || 0).toLocaleString() }}
                        </p>
                        <p class="mt-1 text-xs text-gray-500">
                            Appointments & treatment charts
                        </p>
                    </div>

                    <!-- Financial Operations -->
                    <div class="rounded-2xl border border-gray-100 bg-white p-5 shadow-sm">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-semibold uppercase tracking-wider text-gray-500">
                                Financial Ledger
                            </span>
                            <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-amber-50 text-amber-600">
                                <i class="pi pi-receipt text-sm"></i>
                            </div>
                        </div>
                        <p class="mt-2 text-2xl font-black text-gray-900">
                            {{ Number(metrics.financial_operations || 0).toLocaleString() }}
                        </p>
                        <p class="mt-1 text-xs text-gray-500">
                            Invoices, payments & receipts
                        </p>
                    </div>
                </div>

                <!-- Filter & Search Toolbar -->
                <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm space-y-4">
                    <div class="flex flex-col lg:flex-row items-stretch lg:items-center justify-between gap-3">
                        <!-- Search Box -->
                        <div class="relative flex-1">
                            <i class="pi pi-search absolute left-3.5 top-1/2 -translate-y-1/2 text-gray-400"></i>
                            <input
                                v-model="search"
                                type="text"
                                placeholder="Search by description, actor name, action, IP address..."
                                class="w-full rounded-xl border-gray-300 pl-10 pr-4 py-2.5 text-xs text-gray-900 placeholder-gray-400 focus:border-teal-500 focus:ring-teal-500 sm:text-sm"
                            />
                            <button
                                v-if="search"
                                @click="search = ''"
                                class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600"
                            >
                                <i class="pi pi-times-circle text-xs"></i>
                            </button>
                        </div>

                        <!-- Reset Button -->
                        <button
                            v-if="hasActiveFilters"
                            type="button"
                            @click="resetFilters"
                            class="inline-flex items-center justify-center gap-1.5 rounded-xl border border-gray-300 bg-white px-3.5 py-2.5 text-xs font-medium text-gray-700 shadow-sm hover:bg-gray-50"
                        >
                            <i class="pi pi-filter-slash text-gray-400"></i>
                            <span>Reset Filters</span>
                        </button>
                    </div>

                    <!-- Multi-dropdown filters -->
                    <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 md:grid-cols-4 lg:grid-cols-5">
                        <!-- Module Filter -->
                        <div>
                            <label class="block text-[11px] font-semibold text-gray-500 uppercase tracking-wider mb-1">
                                Module
                            </label>
                            <select
                                v-model="moduleFilter"
                                class="w-full rounded-xl border-gray-300 text-xs py-2 focus:border-teal-500 focus:ring-teal-500"
                            >
                                <option value="">All Modules</option>
                                <option value="appointments">Appointments</option>
                                <option value="patients">Patients</option>
                                <option value="treatments">Treatments</option>
                                <option value="billing">Billing / Invoices</option>
                                <option value="payments">Payments & Receipts</option>
                                <option value="services">Services Catalog</option>
                                <option value="audit_log">Audit System</option>
                                <option value="auth">Auth & Security</option>
                            </select>
                        </div>

                        <!-- Action Filter -->
                        <div>
                            <label class="block text-[11px] font-semibold text-gray-500 uppercase tracking-wider mb-1">
                                Action
                            </label>
                            <select
                                v-model="actionFilter"
                                class="w-full rounded-xl border-gray-300 text-xs py-2 focus:border-teal-500 focus:ring-teal-500"
                            >
                                <option value="">All Actions</option>
                                <option value="created">Created</option>
                                <option value="updated">Updated</option>
                                <option value="confirmed">Confirmed</option>
                                <option value="rescheduled">Rescheduled</option>
                                <option value="completed">Completed</option>
                                <option value="cancelled">Cancelled</option>
                                <option value="deleted">Deleted</option>
                                <option value="archived">Archived</option>
                                <option value="exported">Exported</option>
                                <option value="purged">Purged</option>
                            </select>
                        </div>

                        <!-- Actor Role -->
                        <div>
                            <label class="block text-[11px] font-semibold text-gray-500 uppercase tracking-wider mb-1">
                                Actor Role
                            </label>
                            <select
                                v-model="roleFilter"
                                class="w-full rounded-xl border-gray-300 text-xs py-2 focus:border-teal-500 focus:ring-teal-500"
                            >
                                <option value="">All Roles</option>
                                <option value="admin">Admin</option>
                                <option value="dentist">Dentist</option>
                                <option value="staff">Staff</option>
                                <option value="secretary">Secretary</option>
                                <option value="patient">Patient</option>
                                <option value="system">System</option>
                            </select>
                        </div>

                        <!-- Date From -->
                        <div>
                            <label class="block text-[11px] font-semibold text-gray-500 uppercase tracking-wider mb-1">
                                Date From
                            </label>
                            <input
                                v-model="dateFrom"
                                type="date"
                                class="w-full rounded-xl border-gray-300 text-xs py-2 focus:border-teal-500 focus:ring-teal-500"
                            />
                        </div>

                        <!-- Date To -->
                        <div>
                            <label class="block text-[11px] font-semibold text-gray-500 uppercase tracking-wider mb-1">
                                Date To
                            </label>
                            <input
                                v-model="dateTo"
                                type="date"
                                class="w-full rounded-xl border-gray-300 text-xs py-2 focus:border-teal-500 focus:ring-teal-500"
                            />
                        </div>
                    </div>
                </div>

                <!-- Main Audit Log Data Table -->
                <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 text-left text-xs">
                            <thead class="bg-gray-50/75">
                                <tr>
                                    <th scope="col" class="px-5 py-3.5 font-bold uppercase tracking-wider text-gray-600">
                                        Date & Time (PHT)
                                    </th>
                                    <th scope="col" class="px-5 py-3.5 font-bold uppercase tracking-wider text-gray-600">
                                        Actor
                                    </th>
                                    <th scope="col" class="px-5 py-3.5 font-bold uppercase tracking-wider text-gray-600">
                                        Module
                                    </th>
                                    <th scope="col" class="px-5 py-3.5 font-bold uppercase tracking-wider text-gray-600">
                                        Action
                                    </th>
                                    <th scope="col" class="px-5 py-3.5 font-bold uppercase tracking-wider text-gray-600">
                                        Description
                                    </th>
                                    <th scope="col" class="px-5 py-3.5 font-bold uppercase tracking-wider text-gray-600">
                                        Network & IP
                                    </th>
                                    <th scope="col" class="px-5 py-3.5 text-right font-bold uppercase tracking-wider text-gray-600">
                                        Details
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 bg-white">
                                <tr
                                    v-for="log in logs.data"
                                    :key="log.id"
                                    class="transition hover:bg-gray-50/60"
                                >
                                    <!-- Date & Time -->
                                    <td class="px-5 py-3.5 whitespace-nowrap">
                                        <div class="font-semibold text-gray-900">
                                            {{ formatDateTime(log.created_at) }}
                                        </div>
                                        <div class="text-[11px] text-gray-400">
                                            {{ formatTimeAgo(log.created_at) }}
                                        </div>
                                    </td>

                                    <!-- Actor -->
                                    <td class="px-5 py-3.5 whitespace-nowrap">
                                        <div class="flex items-center gap-2.5">
                                            <div class="flex h-8 w-8 items-center justify-center rounded-full bg-teal-100 text-xs font-bold text-teal-800">
                                                {{ getRoleInitial(log.user_name || log.user?.name) }}
                                            </div>
                                            <div>
                                                <div class="font-semibold text-gray-900">
                                                    {{ log.user_name || log.user?.name || "System" }}
                                                </div>
                                                <span
                                                    :class="[
                                                        'inline-block rounded-full border px-2 py-0.2 text-[10px] font-semibold uppercase tracking-wider',
                                                        getRoleBadge(log.user_role || log.user?.role)
                                                    ]"
                                                >
                                                    {{ log.user_role || log.user?.role || "system" }}
                                                </span>
                                            </div>
                                        </div>
                                    </td>

                                    <!-- Module -->
                                    <td class="px-5 py-3.5 whitespace-nowrap">
                                        <span
                                            :class="[
                                                'inline-flex items-center gap-1.5 rounded-lg border px-2.5 py-1 text-xs font-semibold capitalize',
                                                getModuleBadge(log.module).class
                                            ]"
                                        >
                                            <i :class="getModuleBadge(log.module).icon" class="text-xs"></i>
                                            <span>{{ getModuleBadge(log.module).label }}</span>
                                        </span>
                                    </td>

                                    <!-- Action -->
                                    <td class="px-5 py-3.5 whitespace-nowrap">
                                        <span
                                            :class="[
                                                'inline-block rounded-full border px-2.5 py-0.5 text-xs font-semibold capitalize tracking-wide',
                                                getActionPill(log.action)
                                            ]"
                                        >
                                            {{ (log.action || "").replace('_', ' ') }}
                                        </span>
                                    </td>

                                    <!-- Description -->
                                    <td class="px-5 py-3.5 max-w-md">
                                        <p class="text-xs text-gray-800 font-medium leading-relaxed line-clamp-2" :title="log.description">
                                            {{ log.description }}
                                        </p>
                                        <div v-if="log.auditable_type" class="mt-0.5 text-[10px] font-mono text-gray-400">
                                            Ref: {{ log.auditable_type.split('\\').pop() }} #{{ log.auditable_id }}
                                        </div>
                                    </td>

                                    <!-- Network & IP -->
                                    <td class="px-5 py-3.5 whitespace-nowrap">
                                        <div class="flex items-center gap-1.5">
                                            <i class="pi pi-desktop text-[11px] text-gray-400"></i>
                                            <span class="font-mono text-xs text-gray-600">
                                                {{ log.ip_address || "127.0.0.1" }}
                                            </span>
                                        </div>
                                    </td>

                                    <!-- Details Button -->
                                    <td class="px-5 py-3.5 whitespace-nowrap text-right">
                                        <button
                                            type="button"
                                            @click="inspectLog(log)"
                                            class="inline-flex items-center gap-1.5 rounded-lg border border-gray-200 bg-white px-2.5 py-1.5 text-xs font-semibold text-gray-700 shadow-sm transition hover:border-teal-300 hover:bg-teal-50 hover:text-teal-700"
                                            title="View log metadata and value diff"
                                        >
                                            <i class="pi pi-search-plus text-xs"></i>
                                            <span>Inspect</span>
                                        </button>
                                    </td>
                                </tr>

                                <!-- Empty State -->
                                <tr v-if="!logs.data || logs.data.length === 0">
                                    <td colspan="7" class="px-6 py-16 text-center">
                                        <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-teal-50 text-teal-600">
                                            <i class="pi pi-history text-2xl"></i>
                                        </div>
                                        <h3 class="mt-3 text-sm font-bold text-gray-900">
                                            No audit log entries found
                                        </h3>
                                        <p class="mt-1 text-xs text-gray-500 max-w-sm mx-auto">
                                            No events match your current filter parameters. Try clearing your filters or selecting a different date range.
                                        </p>
                                        <button
                                            v-if="hasActiveFilters"
                                            type="button"
                                            @click="resetFilters"
                                            class="mt-4 inline-flex items-center gap-1.5 rounded-xl bg-teal-700 px-4 py-2 text-xs font-semibold text-white shadow-sm hover:bg-teal-800"
                                        >
                                            <i class="pi pi-refresh text-xs"></i>
                                            <span>Reset All Filters</span>
                                        </button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <div
                        v-if="logs.links && logs.links.length > 3"
                        class="flex flex-col sm:flex-row items-center justify-between gap-4 border-t border-gray-200 bg-gray-50/50 px-5 py-4"
                    >
                        <div class="text-xs text-gray-600">
                            Showing <span class="font-semibold text-gray-900">{{ logs.from || 0 }}</span> to
                            <span class="font-semibold text-gray-900">{{ logs.to || 0 }}</span> of
                            <span class="font-semibold text-gray-900">{{ logs.total }}</span> entries
                        </div>

                        <div class="flex flex-wrap items-center gap-1">
                            <Link
                                v-for="(link, idx) in logs.links"
                                :key="idx"
                                :href="link.url || '#'"
                                v-html="link.label"
                                :class="[
                                    'px-3 py-1.5 text-xs font-medium rounded-lg border transition',
                                    link.active
                                        ? 'bg-teal-700 text-white border-teal-700 shadow-sm'
                                        : 'bg-white text-gray-700 border-gray-300 hover:bg-gray-50',
                                    !link.url ? 'opacity-40 cursor-not-allowed pointer-events-none' : ''
                                ]"
                            />
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- 1. Log Inspector & Diff Modal -->
        <Modal :show="showDiffModal" @close="showDiffModal = false" max-width="2xl">
            <div v-if="activeLog" class="p-6 space-y-6">
                <!-- Modal Header -->
                <div class="flex items-start justify-between border-b border-gray-100 pb-4">
                    <div class="flex items-center gap-3">
                        <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-teal-50 text-teal-700">
                            <i :class="getModuleBadge(activeLog.module).icon" class="text-lg"></i>
                        </div>
                        <div>
                            <div class="flex items-center gap-2">
                                <h3 class="text-base font-bold text-gray-900">
                                    Audit Entry #{{ activeLog.id }}
                                </h3>
                                <span
                                    :class="[
                                        'rounded-full border px-2 py-0.5 text-[10px] font-bold uppercase',
                                        getActionPill(activeLog.action)
                                    ]"
                                >
                                    {{ activeLog.action }}
                                </span>
                            </div>
                            <p class="text-xs text-gray-500">
                                Recorded on {{ formatDateTime(activeLog.created_at) }}
                            </p>
                        </div>
                    </div>

                    <button
                        type="button"
                        @click="showDiffModal = false"
                        class="rounded-lg p-1.5 text-gray-400 hover:bg-gray-100 hover:text-gray-600"
                    >
                        <i class="pi pi-times"></i>
                    </button>
                </div>

                <!-- Event Summary Card -->
                <div class="rounded-xl border border-gray-200 bg-gray-50/70 p-4 space-y-3">
                    <div>
                        <span class="text-[11px] font-semibold text-gray-500 uppercase tracking-wider">
                            Description
                        </span>
                        <p class="mt-0.5 text-sm font-semibold text-gray-900">
                            {{ activeLog.description }}
                        </p>
                    </div>

                    <div class="grid grid-cols-2 gap-3 pt-2 border-t border-gray-200/60 text-xs">
                        <div>
                            <span class="text-gray-500">Actor:</span>
                            <span class="ml-1 font-semibold text-gray-900">
                                {{ activeLog.user_name || activeLog.user?.name || "System" }}
                            </span>
                            <span class="ml-1 text-[10px] text-gray-500">({{ activeLog.user_role }})</span>
                        </div>
                        <div>
                            <span class="text-gray-500">Client IP:</span>
                            <span class="ml-1 font-mono font-medium text-gray-900">
                                {{ activeLog.ip_address || "127.0.0.1" }}
                            </span>
                        </div>
                        <div v-if="activeLog.auditable_type" class="col-span-2">
                            <span class="text-gray-500">Target Object:</span>
                            <span class="ml-1 font-mono text-gray-800">
                                {{ activeLog.auditable_type }} (ID: {{ activeLog.auditable_id }})
                            </span>
                        </div>
                        <div v-if="activeLog.user_agent" class="col-span-2 text-[11px] text-gray-500 truncate" :title="activeLog.user_agent">
                            <span>User Agent:</span>
                            <span class="ml-1 text-gray-700">{{ activeLog.user_agent }}</span>
                        </div>
                    </div>
                </div>

                <!-- View Mode Toggle -->
                <div class="flex items-center justify-between">
                    <span class="text-xs font-bold uppercase tracking-wider text-gray-700">
                        Recorded State Changes (Diff)
                    </span>
                    <div class="inline-flex rounded-lg border border-gray-200 p-0.5 bg-gray-50 text-xs">
                        <button
                            type="button"
                            @click="viewMode = 'formatted'"
                            :class="[
                                'px-2.5 py-1 rounded-md font-semibold transition',
                                viewMode === 'formatted'
                                    ? 'bg-white text-teal-700 shadow-sm'
                                    : 'text-gray-500 hover:text-gray-900'
                            ]"
                        >
                            Readable View
                        </button>
                        <button
                            type="button"
                            @click="viewMode = 'raw'"
                            :class="[
                                'px-2.5 py-1 rounded-md font-semibold transition',
                                viewMode === 'raw'
                                    ? 'bg-white text-teal-700 shadow-sm'
                                    : 'text-gray-500 hover:text-gray-900'
                            ]"
                        >
                            Raw JSON
                        </button>
                    </div>
                </div>

                <!-- Diffs Comparison -->
                <div v-if="viewMode === 'formatted'" class="space-y-4">
                    <div
                        v-if="!activeLog.old_values && !activeLog.new_values"
                        class="rounded-xl border border-gray-200 bg-gray-50 p-6 text-center text-xs text-gray-500"
                    >
                        <i class="pi pi-info-circle text-lg text-gray-400 mb-1"></i>
                        <p>No specific field values or JSON snapshots were recorded for this action.</p>
                    </div>

                    <div v-else class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <!-- Old Values -->
                        <div class="rounded-xl border border-rose-200 bg-rose-50/40 p-3.5 space-y-2">
                            <div class="flex items-center gap-1.5 text-xs font-bold text-rose-800 border-b border-rose-200/60 pb-2">
                                <i class="pi pi-minus-circle text-rose-600"></i>
                                <span>Previous Values (Before)</span>
                            </div>

                            <div v-if="activeLog.old_values && Object.keys(activeLog.old_values).length > 0" class="space-y-1.5 text-xs">
                                <div
                                    v-for="(val, key) in activeLog.old_values"
                                    :key="key"
                                    class="flex items-start justify-between py-1 border-b border-rose-100 last:border-0"
                                >
                                    <span class="font-mono text-[11px] text-gray-600">{{ key }}:</span>
                                    <span class="font-semibold text-rose-900 break-all text-right ml-2">
                                        {{ typeof val === 'object' ? JSON.stringify(val) : (val ?? 'null') }}
                                    </span>
                                </div>
                            </div>
                            <p v-else class="text-[11px] text-gray-500 italic py-2 text-center">
                                No prior values (New Creation)
                            </p>
                        </div>

                        <!-- New Values -->
                        <div class="rounded-xl border border-emerald-200 bg-emerald-50/40 p-3.5 space-y-2">
                            <div class="flex items-center gap-1.5 text-xs font-bold text-emerald-800 border-b border-emerald-200/60 pb-2">
                                <i class="pi pi-plus-circle text-emerald-600"></i>
                                <span>Updated Values (After)</span>
                            </div>

                            <div v-if="activeLog.new_values && Object.keys(activeLog.new_values).length > 0" class="space-y-1.5 text-xs">
                                <div
                                    v-for="(val, key) in activeLog.new_values"
                                    :key="key"
                                    class="flex items-start justify-between py-1 border-b border-emerald-100 last:border-0"
                                >
                                    <span class="font-mono text-[11px] text-gray-600">{{ key }}:</span>
                                    <span class="font-semibold text-emerald-900 break-all text-right ml-2">
                                        {{ typeof val === 'object' ? JSON.stringify(val) : (val ?? 'null') }}
                                    </span>
                                </div>
                            </div>
                            <p v-else class="text-[11px] text-gray-500 italic py-2 text-center">
                                No new values (Deletion)
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Raw JSON View -->
                <div v-else class="rounded-xl border border-gray-800 bg-gray-900 p-4 text-xs font-mono text-emerald-400 overflow-x-auto max-h-72">
                    <pre>{{ JSON.stringify({ old_values: activeLog.old_values, new_values: activeLog.new_values }, null, 2) }}</pre>
                </div>

                <!-- Modal Footer -->
                <div class="flex justify-end pt-3 border-t border-gray-100">
                    <button
                        type="button"
                        @click="showDiffModal = false"
                        class="rounded-xl border border-gray-300 bg-white px-4 py-2 text-xs font-semibold text-gray-700 shadow-sm hover:bg-gray-50"
                    >
                        Close Inspector
                    </button>
                </div>
            </div>
        </Modal>

        <!-- 2. Purge & Retention Management Modal -->
        <Modal :show="showPurgeModal" @close="showPurgeModal = false" max-width="md">
            <div class="p-6 space-y-4">
                <div class="flex items-center gap-3">
                    <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-rose-50 text-rose-600">
                        <i class="pi pi-exclamation-triangle text-lg"></i>
                    </div>
                    <div>
                        <h3 class="text-base font-bold text-gray-900">
                            Audit Log Retention & Cleanup
                        </h3>
                        <p class="text-xs text-gray-500">
                            Purge outdated logs to optimize storage and comply with data retention policies.
                        </p>
                    </div>
                </div>

                <form @submit.prevent="submitPurge" class="space-y-4 pt-2">
                    <div>
                        <label class="block text-xs font-semibold text-gray-700 mb-1.5">
                            Purge entries older than:
                        </label>
                        <select
                            v-model="purgeForm.days"
                            class="w-full rounded-xl border-gray-300 text-xs py-2.5 focus:border-rose-500 focus:ring-rose-500"
                        >
                            <option value="30">30 days (1 month)</option>
                            <option value="60">60 days (2 months)</option>
                            <option value="90">90 days (Quarter)</option>
                            <option value="180">180 days (6 months)</option>
                            <option value="365">365 days (1 year)</option>
                        </select>
                    </div>

                    <div class="rounded-xl border border-amber-200 bg-amber-50 p-3 text-xs text-amber-900 space-y-1">
                        <p class="font-bold flex items-center gap-1.5">
                            <i class="pi pi-info-circle text-amber-700"></i>
                            Compliance Notice:
                        </p>
                        <p class="text-[11px] leading-relaxed text-amber-800">
                            Permanent deletion will be performed for records prior to the selected timeframe. The purge action itself will be recorded in the audit trail.
                        </p>
                    </div>

                    <div class="mt-6 flex justify-end gap-2.5 border-t border-gray-100 pt-4">
                        <button
                            type="button"
                            @click="showPurgeModal = false"
                            class="rounded-xl border border-gray-300 bg-white px-4 py-2 text-xs font-semibold text-gray-700 hover:bg-gray-50"
                        >
                            Cancel
                        </button>
                        <button
                            type="submit"
                            :disabled="purgeForm.processing"
                            class="inline-flex items-center gap-1.5 rounded-xl bg-rose-600 px-4 py-2 text-xs font-semibold text-white shadow-sm hover:bg-rose-700 disabled:opacity-50"
                        >
                            <i class="pi pi-trash text-xs"></i>
                            <span>{{ purgeForm.processing ? "Purging..." : "Confirm Purge" }}</span>
                        </button>
                    </div>
                </form>
            </div>
        </Modal>
    </AuthenticatedLayout>
</template>

