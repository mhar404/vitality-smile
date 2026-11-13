<script setup>
import { ref, watch, computed } from "vue";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import Modal from "@/Components/Modal.vue";
import { Head, Link, router, useForm, usePage } from "@inertiajs/vue3";

const props = defineProps({
    users: {
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
            total_users: 0,
            staff_count: 0,
            active_count: 0,
            suspended_count: 0,
            patient_count: 0,
        }),
    },
    roleDefinitions: {
        type: Object,
        default: () => ({}),
    },
});

const page = usePage();
const authUser = computed(() => page.props.auth.user);

// Search & filter states
const search = ref(props.filters.search || "");
const roleFilter = ref(props.filters.role || "");
const statusFilter = ref(props.filters.status || "");
const verifiedFilter = ref(props.filters.verified || "");

let searchTimeout = null;

const applyFilters = () => {
    router.get(
        route("users.index"),
        {
            search: search.value || undefined,
            role: roleFilter.value || undefined,
            status: statusFilter.value || undefined,
            verified: verifiedFilter.value || undefined,
        },
        {
            preserveState: true,
            preserveScroll: true,
            replace: true,
        }
    );
};

watch([search, roleFilter, statusFilter, verifiedFilter], () => {
    if (searchTimeout) clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        applyFilters();
    }, 300);
});

const resetFilters = () => {
    search.value = "";
    roleFilter.value = "";
    statusFilter.value = "";
    verifiedFilter.value = "";
};

const hasActiveFilters = computed(() => {
    return !!(
        search.value ||
        roleFilter.value ||
        statusFilter.value ||
        verifiedFilter.value
    );
});

// Modals State
const showCreateModal = ref(false);
const showEditModal = ref(false);
const showPasswordModal = ref(false);
const showGuideModal = ref(false);
const activeUser = ref(null);

// Create User Form
const createForm = useForm({
    name: "",
    email: "",
    phone_number: "",
    role: "dentist",
    password: "",
    email_verified: true,
});

const openCreateModal = () => {
    createForm.reset();
    createForm.clearErrors();
    createForm.role = "dentist";
    createForm.email_verified = true;
    showCreateModal.value = true;
};

const generatePassword = (formTarget) => {
    const chars = "abcdefghjkmnpqrstuvwxyzABCDEFGHJKLMNPQRSTUVWXYZ23456789!@#$%&*";
    let pass = "";
    for (let i = 0; i < 12; i++) {
        pass += chars.charAt(Math.floor(Math.random() * chars.length));
    }
    if (formTarget === "create") {
        createForm.password = pass;
    } else if (formTarget === "reset") {
        passwordForm.password = pass;
        passwordForm.password_confirmation = pass;
    }
};

const submitCreate = () => {
    createForm.post(route("users.store"), {
        preserveScroll: true,
        onSuccess: () => {
            showCreateModal.value = false;
            createForm.reset();
        },
    });
};

// Edit User Form
const editForm = useForm({
    name: "",
    email: "",
    phone_number: "",
    role: "staff",
    is_active: true,
});

const openEditModal = (user) => {
    activeUser.value = user;
    editForm.clearErrors();
    editForm.name = user.name;
    editForm.email = user.email;
    editForm.phone_number = user.phone_number || "";
    editForm.role = user.role;
    editForm.is_active = !!user.is_active;
    showEditModal.value = true;
};

const submitEdit = () => {
    if (!activeUser.value) return;
    editForm.put(route("users.update", activeUser.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            showEditModal.value = false;
        },
    });
};

// Reset Password Form
const passwordForm = useForm({
    password: "",
    password_confirmation: "",
});

const openPasswordModal = (user) => {
    activeUser.value = user;
    passwordForm.reset();
    passwordForm.clearErrors();
    showPasswordModal.value = true;
};

const submitPasswordReset = () => {
    if (!activeUser.value) return;
    passwordForm.post(route("users.reset-password", activeUser.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            showPasswordModal.value = false;
            passwordForm.reset();
        },
    });
};

// Toggle User Status
const toggleStatus = (user) => {
    const action = user.is_active ? "deactivate / suspend" : "activate";
    if (confirm(`Are you sure you want to ${action} login access for ${user.name}?`)) {
        router.patch(
            route("users.toggle-status", user.id),
            {},
            { preserveScroll: true }
        );
    }
};

// Delete User
const deleteUser = (user) => {
    if (
        confirm(
            `Are you sure you want to permanently delete user '${user.name}' (${user.email})? This action cannot be undone.`
        )
    ) {
        router.delete(route("users.destroy", user.id), {
            preserveScroll: true,
        });
    }
};

// Formatting utilities
const formatDateTime = (dateString) => {
    if (!dateString) return "Never";
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

const getRoleInitial = (name) => {
    if (!name) return "U";
    return name.trim().charAt(0).toUpperCase();
};

const getRoleBadge = (role) => {
    const r = (role || "").toLowerCase();
    switch (r) {
        case "admin":
            return {
                label: "Administrator",
                class: "bg-purple-100 text-purple-800 border-purple-200",
                icon: "pi pi-shield",
            };
        case "dentist":
            return {
                label: "Dentist (Admin)",
                class: "bg-teal-100 text-teal-800 border-teal-200",
                icon: "pi pi-heart",
            };
        case "staff":
            return {
                label: "Clinic Staff",
                class: "bg-sky-100 text-sky-800 border-sky-200",
                icon: "pi pi-id-card",
            };
        case "secretary":
            return {
                label: "Secretary",
                class: "bg-blue-100 text-blue-800 border-blue-200",
                icon: "pi pi-calendar",
            };
        case "patient":
            return {
                label: "Patient",
                class: "bg-emerald-100 text-emerald-800 border-emerald-200",
                icon: "pi pi-user",
            };
        default:
            return {
                label: role || "User",
                class: "bg-gray-100 text-gray-700 border-gray-200",
                icon: "pi pi-user",
            };
    }
};
</script>

<template>
    <Head title="Users & Access Control - Vitality Smile Dental Clinic" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <div class="flex items-center gap-2">
                        <span class="inline-flex items-center gap-1.5 rounded-full border border-teal-200 bg-teal-50 px-2.5 py-0.5 text-xs font-semibold text-teal-800">
                            <i class="pi pi-lock text-[10px]"></i>
                            Access Control & Security
                        </span>
                        <span class="inline-flex items-center gap-1 rounded-full border border-gray-200 bg-white px-2 py-0.5 text-[11px] font-medium text-gray-500">
                            Role-Based Privileges (RBAC)
                        </span>
                    </div>
                    <h2 class="mt-1 text-2xl font-bold tracking-tight text-gray-900">
                        User Credentials & Access Control
                    </h2>
                    <p class="text-xs text-gray-500 sm:text-sm">
                        Manage authorized clinic personnel, secure login accounts, role permissions, and access status.
                    </p>
                </div>

                <!-- Action Controls -->
                <div class="flex flex-wrap items-center gap-2">
                    <button
                        type="button"
                        @click="showGuideModal = true"
                        class="inline-flex items-center gap-2 rounded-xl border border-gray-300 bg-white px-3.5 py-2 text-xs font-semibold text-gray-700 shadow-sm transition hover:bg-gray-50 hover:text-teal-700"
                    >
                        <i class="pi pi-shield text-teal-600"></i>
                        <span>Permissions Guide</span>
                    </button>

                    <button
                        type="button"
                        @click="openCreateModal"
                        class="inline-flex items-center gap-2 rounded-xl bg-teal-700 px-4 py-2 text-xs font-semibold text-white shadow-sm transition hover:bg-teal-800"
                    >
                        <i class="pi pi-user-plus text-xs"></i>
                        <span>Add Authorized User</span>
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
                    <!-- Total Users -->
                    <div class="rounded-2xl border border-gray-100 bg-white p-5 shadow-sm">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-semibold uppercase tracking-wider text-gray-500">
                                Total Accounts
                            </span>
                            <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-teal-50 text-teal-600">
                                <i class="pi pi-users text-sm"></i>
                            </div>
                        </div>
                        <p class="mt-2 text-2xl font-black text-gray-900">
                            {{ Number(metrics.total_users || 0).toLocaleString() }}
                        </p>
                        <p class="mt-1 text-xs text-gray-500">
                            All portal and staff users
                        </p>
                    </div>

                    <!-- Authorized Personnel -->
                    <div class="rounded-2xl border border-gray-100 bg-white p-5 shadow-sm">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-semibold uppercase tracking-wider text-gray-500">
                                Authorized Personnel
                            </span>
                            <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-purple-50 text-purple-600">
                                <i class="pi pi-id-card text-sm"></i>
                            </div>
                        </div>
                        <div class="mt-2 flex items-baseline gap-2">
                            <p class="text-2xl font-black text-gray-900">
                                {{ Number(metrics.staff_count || 0).toLocaleString() }}
                            </p>
                            <span class="text-xs font-medium text-purple-700">staff / clinical</span>
                        </div>
                        <p class="mt-1 text-xs text-gray-500">
                            Doctors, secretaries & admin
                        </p>
                    </div>

                    <!-- Active Accounts -->
                    <div class="rounded-2xl border border-gray-100 bg-white p-5 shadow-sm">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-semibold uppercase tracking-wider text-gray-500">
                                Active Accounts
                            </span>
                            <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-emerald-50 text-emerald-600">
                                <i class="pi pi-check-circle text-sm"></i>
                            </div>
                        </div>
                        <p class="mt-2 text-2xl font-black text-gray-900">
                            {{ Number(metrics.active_count || 0).toLocaleString() }}
                        </p>
                        <p class="mt-1 text-xs text-gray-500">
                            Permitted login access
                        </p>
                    </div>

                    <!-- Suspended Accounts -->
                    <div class="rounded-2xl border border-gray-100 bg-white p-5 shadow-sm">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-semibold uppercase tracking-wider text-gray-500">
                                Suspended / Inactive
                            </span>
                            <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-rose-50 text-rose-600">
                                <i class="pi pi-ban text-sm"></i>
                            </div>
                        </div>
                        <p class="mt-2 text-2xl font-black text-gray-900">
                            {{ Number(metrics.suspended_count || 0).toLocaleString() }}
                        </p>
                        <p class="mt-1 text-xs text-gray-500">
                            Access revoked / locked
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
                                placeholder="Search by name, email, phone number, or role..."
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
                    <div class="grid grid-cols-1 gap-3 sm:grid-cols-3">
                        <!-- Role Filter -->
                        <div>
                            <label class="block text-[11px] font-semibold text-gray-500 uppercase tracking-wider mb-1">
                                System Role
                            </label>
                            <select
                                v-model="roleFilter"
                                class="w-full rounded-xl border-gray-300 text-xs py-2 focus:border-teal-500 focus:ring-teal-500"
                            >
                                <option value="">All Roles</option>
                                <option value="admin">Administrator</option>
                                <option value="dentist">Dentist (Admin)</option>
                                <option value="staff">Clinic Staff</option>
                                <option value="secretary">Secretary</option>
                                <option value="patient">Patient Portal</option>
                            </select>
                        </div>

                        <!-- Status Filter -->
                        <div>
                            <label class="block text-[11px] font-semibold text-gray-500 uppercase tracking-wider mb-1">
                                Account Status
                            </label>
                            <select
                                v-model="statusFilter"
                                class="w-full rounded-xl border-gray-300 text-xs py-2 focus:border-teal-500 focus:ring-teal-500"
                            >
                                <option value="">All Statuses</option>
                                <option value="active">Active (Access Granted)</option>
                                <option value="suspended">Suspended / Inactive</option>
                            </select>
                        </div>

                        <!-- Email Verification Filter -->
                        <div>
                            <label class="block text-[11px] font-semibold text-gray-500 uppercase tracking-wider mb-1">
                                Verification
                            </label>
                            <select
                                v-model="verifiedFilter"
                                class="w-full rounded-xl border-gray-300 text-xs py-2 focus:border-teal-500 focus:ring-teal-500"
                            >
                                <option value="">All Accounts</option>
                                <option value="verified">Verified Emails</option>
                                <option value="unverified">Unverified</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Main Users Data Table -->
                <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 text-left text-xs">
                            <thead class="bg-gray-50/75">
                                <tr>
                                    <th scope="col" class="px-5 py-3.5 font-bold uppercase tracking-wider text-gray-600">
                                        User / Personnel
                                    </th>
                                    <th scope="col" class="px-5 py-3.5 font-bold uppercase tracking-wider text-gray-600">
                                        Role & Permissions
                                    </th>
                                    <th scope="col" class="px-5 py-3.5 font-bold uppercase tracking-wider text-gray-600">
                                        Status
                                    </th>
                                    <th scope="col" class="px-5 py-3.5 font-bold uppercase tracking-wider text-gray-600">
                                        Email Verification
                                    </th>
                                    <th scope="col" class="px-5 py-3.5 font-bold uppercase tracking-wider text-gray-600">
                                        Last Login
                                    </th>
                                    <th scope="col" class="px-5 py-3.5 text-right font-bold uppercase tracking-wider text-gray-600">
                                        Actions
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 bg-white">
                                <tr
                                    v-for="u in users.data"
                                    :key="u.id"
                                    class="transition hover:bg-gray-50/60"
                                >
                                    <!-- User / Personnel -->
                                    <td class="px-5 py-3.5 whitespace-nowrap">
                                        <div class="flex items-center gap-3">
                                            <div
                                                v-if="u.avatar"
                                                class="h-9 w-9 rounded-full overflow-hidden shrink-0 border border-gray-200"
                                            >
                                                <img :src="u.avatar" alt="Avatar" class="h-full w-full object-cover" />
                                            </div>
                                            <div
                                                v-else
                                                class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-teal-100 text-xs font-bold text-teal-800"
                                            >
                                                {{ getRoleInitial(u.name) }}
                                            </div>

                                            <div>
                                                <div class="flex items-center gap-1.5 font-semibold text-gray-900">
                                                    <span>{{ u.name }}</span>
                                                    <span
                                                        v-if="u.id === authUser.id"
                                                        class="rounded-full bg-teal-50 px-1.5 py-0.2 text-[10px] font-bold text-teal-700 border border-teal-200"
                                                    >
                                                        You
                                                    </span>
                                                </div>
                                                <div class="text-xs text-gray-500">
                                                    {{ u.email }}
                                                </div>
                                                <div v-if="u.phone_number" class="text-[11px] text-gray-400">
                                                    {{ u.phone_number }}
                                                </div>
                                            </div>
                                        </div>
                                    </td>

                                    <!-- Role & Permissions -->
                                    <td class="px-5 py-3.5 whitespace-nowrap">
                                        <span
                                            :class="[
                                                'inline-flex items-center gap-1.5 rounded-lg border px-2.5 py-1 text-xs font-semibold capitalize',
                                                getRoleBadge(u.role).class
                                            ]"
                                        >
                                            <i :class="getRoleBadge(u.role).icon" class="text-xs"></i>
                                            <span>{{ getRoleBadge(u.role).label }}</span>
                                        </span>
                                    </td>

                                    <!-- Status -->
                                    <td class="px-5 py-3.5 whitespace-nowrap">
                                        <span
                                            v-if="u.is_active"
                                            class="inline-flex items-center gap-1.5 rounded-full border border-emerald-200 bg-emerald-50 px-2.5 py-0.5 text-xs font-semibold text-emerald-800"
                                        >
                                            <span class="h-1.5 w-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                                            Active
                                        </span>
                                        <span
                                            v-else
                                            class="inline-flex items-center gap-1.5 rounded-full border border-rose-200 bg-rose-50 px-2.5 py-0.5 text-xs font-semibold text-rose-800"
                                        >
                                            <i class="pi pi-lock text-[10px]"></i>
                                            Suspended
                                        </span>
                                    </td>

                                    <!-- Email Verification -->
                                    <td class="px-5 py-3.5 whitespace-nowrap">
                                        <span
                                            v-if="u.email_verified_at"
                                            class="inline-flex items-center gap-1 text-xs font-medium text-emerald-700"
                                        >
                                            <i class="pi pi-check-circle text-emerald-500 text-xs"></i>
                                            Verified
                                        </span>
                                        <span
                                            v-else
                                            class="inline-flex items-center gap-1 text-xs font-medium text-amber-700"
                                        >
                                            <i class="pi pi-clock text-amber-500 text-xs"></i>
                                            Unverified
                                        </span>
                                    </td>

                                    <!-- Last Login -->
                                    <td class="px-5 py-3.5 whitespace-nowrap">
                                        <div class="text-xs text-gray-800 font-medium">
                                            {{ formatDateTime(u.last_login_at) }}
                                        </div>
                                        <div v-if="u.last_login_ip" class="text-[10px] font-mono text-gray-400">
                                            IP: {{ u.last_login_ip }}
                                        </div>
                                    </td>

                                    <!-- Actions Menu -->
                                    <td class="px-5 py-3.5 whitespace-nowrap text-right space-x-1.5">
                                        <!-- Quick Status Toggle -->
                                        <button
                                            v-if="u.id !== authUser.id"
                                            type="button"
                                            @click="toggleStatus(u)"
                                            :class="[
                                                'inline-flex items-center gap-1 rounded-lg border px-2.5 py-1 text-xs font-semibold shadow-xs transition',
                                                u.is_active
                                                    ? 'border-rose-200 bg-rose-50 text-rose-700 hover:bg-rose-100'
                                                    : 'border-emerald-200 bg-emerald-50 text-emerald-700 hover:bg-emerald-100'
                                            ]"
                                            :title="u.is_active ? 'Suspend account access' : 'Activate account access'"
                                        >
                                            <i :class="u.is_active ? 'pi pi-ban' : 'pi pi-check'" class="text-[10px]"></i>
                                            <span>{{ u.is_active ? "Suspend" : "Activate" }}</span>
                                        </button>

                                        <!-- Edit Details Button -->
                                        <button
                                            type="button"
                                            @click="openEditModal(u)"
                                            class="inline-flex items-center gap-1 rounded-lg border border-gray-200 bg-white px-2.5 py-1 text-xs font-semibold text-gray-700 shadow-xs transition hover:bg-teal-50 hover:text-teal-700 hover:border-teal-300"
                                            title="Edit user details and role"
                                        >
                                            <i class="pi pi-pencil text-[10px]"></i>
                                            <span>Edit</span>
                                        </button>

                                        <!-- Reset Password Button -->
                                        <button
                                            type="button"
                                            @click="openPasswordModal(u)"
                                            class="inline-flex items-center gap-1 rounded-lg border border-gray-200 bg-white px-2.5 py-1 text-xs font-semibold text-gray-700 shadow-xs transition hover:bg-amber-50 hover:text-amber-700 hover:border-amber-300"
                                            title="Reset user login password"
                                        >
                                            <i class="pi pi-key text-[10px]"></i>
                                            <span>Reset Key</span>
                                        </button>

                                        <!-- Delete Button -->
                                        <button
                                            v-if="u.id !== authUser.id"
                                            type="button"
                                            @click="deleteUser(u)"
                                            class="inline-flex items-center justify-center rounded-lg border border-transparent p-1.5 text-xs text-rose-500 hover:bg-rose-50 hover:text-rose-700 transition"
                                            title="Delete account"
                                        >
                                            <i class="pi pi-trash text-xs"></i>
                                        </button>
                                    </td>
                                </tr>

                                <!-- Empty State -->
                                <tr v-if="!users.data || users.data.length === 0">
                                    <td colspan="6" class="px-6 py-16 text-center">
                                        <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-teal-50 text-teal-600">
                                            <i class="pi pi-user-minus text-2xl"></i>
                                        </div>
                                        <h3 class="mt-3 text-sm font-bold text-gray-900">
                                            No user accounts found
                                        </h3>
                                        <p class="mt-1 text-xs text-gray-500 max-w-sm mx-auto">
                                            No users matched your current filter criteria. Try clearing your filters or adding a new authorized personnel.
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
                        v-if="users.links && users.links.length > 3"
                        class="flex flex-col sm:flex-row items-center justify-between gap-4 border-t border-gray-200 bg-gray-50/50 px-5 py-4"
                    >
                        <div class="text-xs text-gray-600">
                            Showing <span class="font-semibold text-gray-900">{{ users.from || 0 }}</span> to
                            <span class="font-semibold text-gray-900">{{ users.to || 0 }}</span> of
                            <span class="font-semibold text-gray-900">{{ users.total }}</span> users
                        </div>

                        <div class="flex flex-wrap items-center gap-1">
                            <Link
                                v-for="(link, idx) in users.links"
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

        <!-- 1. Create Authorized User Modal -->
        <Modal :show="showCreateModal" @close="showCreateModal = false" max-width="lg">
            <div class="p-6 space-y-5">
                <div class="flex items-center justify-between border-b border-gray-100 pb-3">
                    <div class="flex items-center gap-2.5">
                        <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-teal-50 text-teal-700">
                            <i class="pi pi-user-plus text-base"></i>
                        </div>
                        <div>
                            <h3 class="text-base font-bold text-gray-900">
                                Create Authorized User Account
                            </h3>
                            <p class="text-xs text-gray-500">
                                Provision credentials and assign role permissions.
                            </p>
                        </div>
                    </div>
                    <button
                        type="button"
                        @click="showCreateModal = false"
                        class="rounded-lg p-1 text-gray-400 hover:text-gray-600"
                    >
                        <i class="pi pi-times"></i>
                    </button>
                </div>

                <form @submit.prevent="submitCreate" class="space-y-4">
                    <!-- Full Name -->
                    <div>
                        <label class="block text-xs font-semibold text-gray-700 mb-1">
                            Full Name <span class="text-rose-500">*</span>
                        </label>
                        <input
                            v-model="createForm.name"
                            type="text"
                            required
                            placeholder="e.g. Maria Santos, RN"
                            class="w-full rounded-xl border-gray-300 text-xs py-2.5 focus:border-teal-500 focus:ring-teal-500"
                        />
                        <p v-if="createForm.errors.name" class="mt-1 text-xs text-rose-600">
                            {{ createForm.errors.name }}
                        </p>
                    </div>

                    <!-- Email Address -->
                    <div>
                        <label class="block text-xs font-semibold text-gray-700 mb-1">
                            Email Address <span class="text-rose-500">*</span>
                        </label>
                        <input
                            v-model="createForm.email"
                            type="email"
                            required
                            placeholder="e.g. staff.maria@vitalitysmile.com"
                            class="w-full rounded-xl border-gray-300 text-xs py-2.5 focus:border-teal-500 focus:ring-teal-500"
                        />
                        <p v-if="createForm.errors.email" class="mt-1 text-xs text-rose-600">
                            {{ createForm.errors.email }}
                        </p>
                    </div>

                    <!-- Phone Number -->
                    <div>
                        <label class="block text-xs font-semibold text-gray-700 mb-1">
                            Contact Phone Number
                        </label>
                        <input
                            v-model="createForm.phone_number"
                            type="text"
                            placeholder="e.g. 0917 123 4567"
                            class="w-full rounded-xl border-gray-300 text-xs py-2.5 focus:border-teal-500 focus:ring-teal-500"
                        />
                        <p v-if="createForm.errors.phone_number" class="mt-1 text-xs text-rose-600">
                            {{ createForm.errors.phone_number }}
                        </p>
                    </div>

                    <!-- Role Select -->
                    <div>
                        <label class="block text-xs font-semibold text-gray-700 mb-1">
                            System Role & Access Level <span class="text-rose-500">*</span>
                        </label>
                        <select
                            v-model="createForm.role"
                            required
                            class="w-full rounded-xl border-gray-300 text-xs py-2.5 focus:border-teal-500 focus:ring-teal-500"
                        >
                            <option value="dentist">Dentist (Admin - Clinical Charting, Treatments, Billing, Payments)</option>
                            <option value="staff">Clinic Staff (Patients; View-Only: Appointments, Treatments, Services, Billing, Payments)</option>
                            <option value="secretary">Clinic Secretary (Appointment Scheduling & Invoicing)</option>
                        </select>
                        <p v-if="createForm.errors.role" class="mt-1 text-xs text-rose-600">
                            {{ createForm.errors.role }}
                        </p>
                    </div>

                    <!-- Password with Generator -->
                    <div>
                        <div class="flex items-center justify-between mb-1">
                            <label class="block text-xs font-semibold text-gray-700">
                                Temporary Password <span class="text-rose-500">*</span>
                            </label>
                            <button
                                type="button"
                                @click="generatePassword('create')"
                                class="text-[11px] font-semibold text-teal-700 hover:text-teal-900 inline-flex items-center gap-1"
                            >
                                <i class="pi pi-bolt text-[10px]"></i> Auto-Generate Strong Password
                            </button>
                        </div>
                        <input
                            v-model="createForm.password"
                            type="text"
                            required
                            placeholder="Minimum 8 characters"
                            class="w-full rounded-xl border-gray-300 font-mono text-xs py-2.5 focus:border-teal-500 focus:ring-teal-500"
                        />
                        <p v-if="createForm.errors.password" class="mt-1 text-xs text-rose-600">
                            {{ createForm.errors.password }}
                        </p>
                    </div>

                    <!-- Email Verified Checkbox -->
                    <div class="flex items-center gap-2 pt-1">
                        <input
                            v-model="createForm.email_verified"
                            type="checkbox"
                            id="create_email_verified"
                            class="rounded border-gray-300 text-teal-600 focus:ring-teal-500 h-4 w-4"
                        />
                        <label for="create_email_verified" class="text-xs text-gray-700 font-medium">
                            Mark email as pre-verified (allows immediate login without email confirmation)
                        </label>
                    </div>

                    <!-- Modal Actions -->
                    <div class="mt-6 flex justify-end gap-2.5 border-t border-gray-100 pt-4">
                        <button
                            type="button"
                            @click="showCreateModal = false"
                            class="rounded-xl border border-gray-300 bg-white px-4 py-2 text-xs font-semibold text-gray-700 hover:bg-gray-50"
                        >
                            Cancel
                        </button>
                        <button
                            type="submit"
                            :disabled="createForm.processing"
                            class="inline-flex items-center gap-1.5 rounded-xl bg-teal-700 px-4 py-2 text-xs font-semibold text-white shadow-sm hover:bg-teal-800 disabled:opacity-50"
                        >
                            <i class="pi pi-check text-xs"></i>
                            <span>{{ createForm.processing ? "Creating..." : "Save User Account" }}</span>
                        </button>
                    </div>
                </form>
            </div>
        </Modal>

        <!-- 2. Edit User Modal -->
        <Modal :show="showEditModal" @close="showEditModal = false" max-width="lg">
            <div v-if="activeUser" class="p-6 space-y-5">
                <div class="flex items-center justify-between border-b border-gray-100 pb-3">
                    <div class="flex items-center gap-2.5">
                        <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-teal-50 text-teal-700">
                            <i class="pi pi-pencil text-base"></i>
                        </div>
                        <div>
                            <h3 class="text-base font-bold text-gray-900">
                                Edit User Account & Access Role
                            </h3>
                            <p class="text-xs text-gray-500">
                                Modifying account for {{ activeUser.name }}
                            </p>
                        </div>
                    </div>
                    <button
                        type="button"
                        @click="showEditModal = false"
                        class="rounded-lg p-1 text-gray-400 hover:text-gray-600"
                    >
                        <i class="pi pi-times"></i>
                    </button>
                </div>

                <form @submit.prevent="submitEdit" class="space-y-4">
                    <!-- Full Name -->
                    <div>
                        <label class="block text-xs font-semibold text-gray-700 mb-1">
                            Full Name <span class="text-rose-500">*</span>
                        </label>
                        <input
                            v-model="editForm.name"
                            type="text"
                            required
                            class="w-full rounded-xl border-gray-300 text-xs py-2.5 focus:border-teal-500 focus:ring-teal-500"
                        />
                        <p v-if="editForm.errors.name" class="mt-1 text-xs text-rose-600">
                            {{ editForm.errors.name }}
                        </p>
                    </div>

                    <!-- Email Address -->
                    <div>
                        <label class="block text-xs font-semibold text-gray-700 mb-1">
                            Email Address <span class="text-rose-500">*</span>
                        </label>
                        <input
                            v-model="editForm.email"
                            type="email"
                            required
                            class="w-full rounded-xl border-gray-300 text-xs py-2.5 focus:border-teal-500 focus:ring-teal-500"
                        />
                        <p v-if="editForm.errors.email" class="mt-1 text-xs text-rose-600">
                            {{ editForm.errors.email }}
                        </p>
                    </div>

                    <!-- Phone Number -->
                    <div>
                        <label class="block text-xs font-semibold text-gray-700 mb-1">
                            Contact Phone Number
                        </label>
                        <input
                            v-model="editForm.phone_number"
                            type="text"
                            placeholder="e.g. 0917 123 4567"
                            class="w-full rounded-xl border-gray-300 text-xs py-2.5 focus:border-teal-500 focus:ring-teal-500"
                        />
                    </div>

                    <!-- Role Select -->
                    <div>
                        <label class="block text-xs font-semibold text-gray-700 mb-1">
                            Role Assignment <span class="text-rose-500">*</span>
                        </label>
                        <select
                            v-model="editForm.role"
                            :disabled="activeUser.id === authUser.id"
                            required
                            class="w-full rounded-xl border-gray-300 text-xs py-2.5 focus:border-teal-500 focus:ring-teal-500 disabled:bg-gray-100 disabled:cursor-not-allowed"
                        >
                            <option v-if="editForm.role === 'admin'" value="admin">Administrator</option>
                            <option value="dentist">Dentist (Admin - Clinical Authority, Billing & Payments)</option>
                            <option value="staff">Clinic Staff (Patients; View-Only: Appointments, Treatments, Services, Billing, Payments)</option>
                            <option value="secretary">Secretary (Appointment Scheduling & Invoicing)</option>
                            <option v-if="editForm.role === 'patient'" value="patient">Patient Portal User</option>
                        </select>
                        <p v-if="activeUser.id === authUser.id" class="mt-1 text-[11px] text-gray-500">
                            You cannot change your own administrator role to prevent accidental lockout.
                        </p>
                    </div>

                    <!-- Active Status Toggle -->
                    <div class="rounded-xl border border-gray-200 bg-gray-50 p-3.5 flex items-center justify-between">
                        <div>
                            <span class="block text-xs font-bold text-gray-900">
                                Login Access Permitted
                            </span>
                            <span class="block text-[11px] text-gray-500">
                                Disabling this immediately prevents this user from logging into Vitality Smile.
                            </span>
                        </div>
                        <input
                            v-model="editForm.is_active"
                            :disabled="activeUser.id === authUser.id"
                            type="checkbox"
                            class="h-5 w-5 rounded border-gray-300 text-teal-600 focus:ring-teal-500 disabled:opacity-50"
                        />
                    </div>

                    <!-- Modal Actions -->
                    <div class="mt-6 flex justify-end gap-2.5 border-t border-gray-100 pt-4">
                        <button
                            type="button"
                            @click="showEditModal = false"
                            class="rounded-xl border border-gray-300 bg-white px-4 py-2 text-xs font-semibold text-gray-700 hover:bg-gray-50"
                        >
                            Cancel
                        </button>
                        <button
                            type="submit"
                            :disabled="editForm.processing"
                            class="inline-flex items-center gap-1.5 rounded-xl bg-teal-700 px-4 py-2 text-xs font-semibold text-white shadow-sm hover:bg-teal-800 disabled:opacity-50"
                        >
                            <i class="pi pi-check text-xs"></i>
                            <span>{{ editForm.processing ? "Saving..." : "Update User" }}</span>
                        </button>
                    </div>
                </form>
            </div>
        </Modal>

        <!-- 3. Reset Password Modal -->
        <Modal :show="showPasswordModal" @close="showPasswordModal = false" max-width="md">
            <div v-if="activeUser" class="p-6 space-y-4">
                <div class="flex items-center justify-between border-b border-gray-100 pb-3">
                    <div class="flex items-center gap-2.5">
                        <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-amber-50 text-amber-700">
                            <i class="pi pi-key text-base"></i>
                        </div>
                        <div>
                            <h3 class="text-base font-bold text-gray-900">
                                Reset Password Credentials
                            </h3>
                            <p class="text-xs text-gray-500">
                                For user {{ activeUser.name }} ({{ activeUser.email }})
                            </p>
                        </div>
                    </div>
                    <button
                        type="button"
                        @click="showPasswordModal = false"
                        class="rounded-lg p-1 text-gray-400 hover:text-gray-600"
                    >
                        <i class="pi pi-times"></i>
                    </button>
                </div>

                <form @submit.prevent="submitPasswordReset" class="space-y-4">
                    <div class="flex items-center justify-between">
                        <label class="block text-xs font-semibold text-gray-700">
                            New Password <span class="text-rose-500">*</span>
                        </label>
                        <button
                            type="button"
                            @click="generatePassword('reset')"
                            class="text-[11px] font-semibold text-teal-700 hover:text-teal-900 inline-flex items-center gap-1"
                        >
                            <i class="pi pi-bolt text-[10px]"></i> Auto-Generate
                        </button>
                    </div>
                    <input
                        v-model="passwordForm.password"
                        type="text"
                        required
                        placeholder="New password (min 8 characters)"
                        class="w-full rounded-xl border-gray-300 font-mono text-xs py-2.5 focus:border-amber-500 focus:ring-amber-500"
                    />
                    <p v-if="passwordForm.errors.password" class="text-xs text-rose-600">
                        {{ passwordForm.errors.password }}
                    </p>

                    <div>
                        <label class="block text-xs font-semibold text-gray-700 mb-1">
                            Confirm New Password <span class="text-rose-500">*</span>
                        </label>
                        <input
                            v-model="passwordForm.password_confirmation"
                            type="text"
                            required
                            placeholder="Repeat new password"
                            class="w-full rounded-xl border-gray-300 font-mono text-xs py-2.5 focus:border-amber-500 focus:ring-amber-500"
                        />
                    </div>

                    <div class="rounded-xl border border-amber-200 bg-amber-50 p-3 text-xs text-amber-900">
                        <i class="pi pi-info-circle text-amber-700 mr-1"></i>
                        The user will immediately need this new password to access their account.
                    </div>

                    <div class="mt-6 flex justify-end gap-2.5 border-t border-gray-100 pt-4">
                        <button
                            type="button"
                            @click="showPasswordModal = false"
                            class="rounded-xl border border-gray-300 bg-white px-4 py-2 text-xs font-semibold text-gray-700 hover:bg-gray-50"
                        >
                            Cancel
                        </button>
                        <button
                            type="submit"
                            :disabled="passwordForm.processing"
                            class="inline-flex items-center gap-1.5 rounded-xl bg-amber-600 px-4 py-2 text-xs font-semibold text-white shadow-sm hover:bg-amber-700 disabled:opacity-50"
                        >
                            <i class="pi pi-key text-xs"></i>
                            <span>{{ passwordForm.processing ? "Updating..." : "Update Password" }}</span>
                        </button>
                    </div>
                </form>
            </div>
        </Modal>

        <!-- 4. Permissions Guide & Access Matrix Modal -->
        <Modal :show="showGuideModal" @close="showGuideModal = false" max-width="3xl">
            <div class="p-6 space-y-6">
                <div class="flex items-center justify-between border-b border-gray-100 pb-3">
                    <div class="flex items-center gap-2.5">
                        <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-teal-50 text-teal-700">
                            <i class="pi pi-shield text-base"></i>
                        </div>
                        <div>
                            <h3 class="text-base font-bold text-gray-900">
                                Role-Based Access Control (RBAC) Reference
                            </h3>
                            <p class="text-xs text-gray-500">
                                Security boundaries and module permissions defined in Vitality Smile Dental Clinic.
                            </p>
                        </div>
                    </div>
                    <button
                        type="button"
                        @click="showGuideModal = false"
                        class="rounded-lg p-1 text-gray-400 hover:text-gray-600"
                    >
                        <i class="pi pi-times"></i>
                    </button>
                </div>

                <div class="space-y-4 max-h-[60vh] overflow-y-auto pr-1">
                    <div
                        v-for="(def, key) in roleDefinitions"
                        :key="key"
                        class="rounded-2xl border border-gray-200 bg-white p-4 shadow-2xs space-y-2.5"
                    >
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-2">
                                <span
                                    :class="[
                                        'rounded-lg border px-2.5 py-0.5 text-xs font-bold capitalize',
                                        def.badge
                                    ]"
                                >
                                    {{ def.name }}
                                </span>
                                <span class="font-mono text-[11px] text-gray-400">({{ key }})</span>
                            </div>
                        </div>

                        <p class="text-xs text-gray-600 font-medium leading-relaxed">
                            {{ def.description }}
                        </p>

                        <div class="pt-2 border-t border-gray-100">
                            <span class="text-[11px] font-bold uppercase tracking-wider text-gray-500 block mb-1.5">
                                Permitted Capabilities:
                            </span>
                            <ul class="grid grid-cols-1 sm:grid-cols-2 gap-1.5 text-xs text-gray-700">
                                <li
                                    v-for="(perm, pIdx) in def.permissions"
                                    :key="pIdx"
                                    class="flex items-center gap-1.5"
                                >
                                    <i class="pi pi-check text-[10px] text-teal-600 shrink-0"></i>
                                    <span>{{ perm }}</span>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>

                <div class="flex justify-end pt-3 border-t border-gray-100">
                    <button
                        type="button"
                        @click="showGuideModal = false"
                        class="rounded-xl border border-gray-300 bg-white px-4 py-2 text-xs font-semibold text-gray-700 hover:bg-gray-50"
                    >
                        Close Guide
                    </button>
                </div>
            </div>
        </Modal>
    </AuthenticatedLayout>
</template>

