<script setup>
import { ref, computed } from "vue";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import Modal from "@/Components/Modal.vue";
import Pagination from "@/Components/Pagination.vue";
import { Head, Link, router, useForm, usePage } from "@inertiajs/vue3";

const props = defineProps({
    stats: {
        type: Object,
        required: true,
    },
    backups: {
        type: Object,
        default: () => ({ data: [], total: 0 }),
    },
});

const page = usePage();

// Modals state
const showCreateModal = ref(false);
const showRestoreModal = ref(false);
const showUploadModal = ref(false);
const selectedBackup = ref(null);

// Create Backup Form
const createForm = useForm({
    type: "database", // 'database' | 'full'
    note: "",
});

const openCreateModal = () => {
    createForm.reset();
    createForm.type = "database";
    createForm.note = "";
    showCreateModal.value = true;
};

const submitCreate = () => {
    createForm.post(route("backup.store"), {
        preserveScroll: true,
        onSuccess: () => {
            showCreateModal.value = false;
            createForm.reset();
        },
    });
};

// Restore Backup Form
const restoreForm = useForm({
    filename: "",
    confirmation: "",
});

const openRestoreModal = (backup) => {
    selectedBackup.value = backup;
    restoreForm.reset();
    restoreForm.filename = backup.filename;
    restoreForm.confirmation = "";
    showRestoreModal.value = true;
};

const submitRestore = () => {
    restoreForm.post(route("backup.restore"), {
        preserveScroll: true,
        onSuccess: () => {
            showRestoreModal.value = false;
            restoreForm.reset();
        },
    });
};

// Upload & Restore Form
const uploadForm = useForm({
    backup_file: null,
    confirmation: "",
});

const handleFileUpload = (event) => {
    uploadForm.backup_file = event.target.files[0] || null;
};

const submitUploadRestore = () => {
    uploadForm.post(route("backup.restore-upload"), {
        preserveScroll: true,
        onSuccess: () => {
            showUploadModal.value = false;
            uploadForm.reset();
        },
    });
};

// Delete Backup
const deleteBackup = (backup) => {
    if (
        confirm(
            `Are you sure you want to delete backup file '${backup.filename}'? This action cannot be reversed.`
        )
    ) {
        router.delete(route("backup.destroy", backup.filename), {
            preserveScroll: true,
        });
    }
};

// Formatting helpers
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
</script>

<template>
    <Head title="System Backup & Recovery - Vitality Smile Dental Clinic" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <div class="flex items-center gap-2">
                        <span class="inline-flex items-center gap-1.5 rounded-full border border-teal-200 bg-teal-50 px-2.5 py-0.5 text-xs font-semibold text-teal-800">
                            <i class="pi pi-database text-[10px]"></i>
                            Disaster Recovery & Continuity
                        </span>
                        <span class="inline-flex items-center gap-1 rounded-full border border-gray-200 bg-white px-2 py-0.5 text-[11px] font-medium text-gray-500">
                            SHA-256 Checksum Verified
                        </span>
                    </div>
                    <h2 class="mt-1 text-2xl font-bold tracking-tight text-gray-900">
                        System Backup & Data Recovery
                    </h2>
                    <p class="text-xs text-gray-500 sm:text-sm">
                        Create automated archives of clinic databases, restore snapshots safely, and protect patient health records against system failure.
                    </p>
                </div>

                <!-- Action Controls -->
                <div class="flex flex-wrap items-center gap-2">
                    <button
                        type="button"
                        @click="showUploadModal = true"
                        class="inline-flex items-center gap-2 rounded-xl border border-gray-300 bg-white px-3.5 py-2 text-xs font-semibold text-gray-700 shadow-sm transition hover:bg-gray-50 hover:text-teal-700"
                    >
                        <i class="pi pi-upload text-teal-600"></i>
                        <span>Upload & Restore</span>
                    </button>

                    <button
                        type="button"
                        @click="openCreateModal"
                        class="inline-flex items-center gap-2 rounded-xl bg-teal-700 px-4 py-2 text-xs font-semibold text-white shadow-sm transition hover:bg-teal-800"
                    >
                        <i class="pi pi-plus text-xs"></i>
                        <span>Create Backup Snapshot</span>
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

                <!-- 4 Top Health & Storage Metrics -->
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    <!-- Active Database File Size -->
                    <div class="rounded-2xl border border-gray-100 bg-white p-5 shadow-sm">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-semibold uppercase tracking-wider text-gray-500">
                                Active Database File
                            </span>
                            <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-teal-50 text-teal-600">
                                <i class="pi pi-server text-sm"></i>
                            </div>
                        </div>
                        <div class="mt-2 flex items-baseline gap-2">
                            <p class="text-2xl font-black text-gray-900">
                                {{ stats.database_size_formatted }}
                            </p>
                            <span class="inline-flex items-center gap-1 text-[11px] font-medium text-emerald-600">
                                <span class="h-1.5 w-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                                Online
                            </span>
                        </div>
                        <p class="mt-1 text-xs font-mono text-gray-400">
                            {{ stats.database_file }}
                        </p>
                    </div>

                    <!-- Total Records Secured -->
                    <div class="rounded-2xl border border-gray-100 bg-white p-5 shadow-sm">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-semibold uppercase tracking-wider text-gray-500">
                                Total Records Secured
                            </span>
                            <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-indigo-50 text-indigo-600">
                                <i class="pi pi-list text-sm"></i>
                            </div>
                        </div>
                        <p class="mt-2 text-2xl font-black text-gray-900">
                            {{ Number(stats.total_records || 0).toLocaleString() }}
                        </p>
                        <p class="mt-1 text-xs text-gray-500">
                            Across all healthcare clinical tables
                        </p>
                    </div>

                    <!-- Archived Backups -->
                    <div class="rounded-2xl border border-gray-100 bg-white p-5 shadow-sm">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-semibold uppercase tracking-wider text-gray-500">
                                Stored Backup Archives
                            </span>
                            <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-purple-50 text-purple-600">
                                <i class="pi pi-box text-sm"></i>
                            </div>
                        </div>
                        <div class="mt-2 flex items-baseline gap-2">
                            <p class="text-2xl font-black text-gray-900">
                                {{ stats.total_backups }}
                            </p>
                            <span class="text-xs font-medium text-purple-700">({{ stats.total_backup_size_formatted }})</span>
                        </div>
                        <p class="mt-1 text-xs text-gray-500">
                            Preserved in secure local storage
                        </p>
                    </div>

                    <!-- Last Backup Created -->
                    <div class="rounded-2xl border border-gray-100 bg-white p-5 shadow-sm">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-semibold uppercase tracking-wider text-gray-500">
                                Latest Snapshot
                            </span>
                            <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-emerald-50 text-emerald-600">
                                <i class="pi pi-history text-sm"></i>
                            </div>
                        </div>
                        <p class="mt-2 text-sm font-bold text-gray-900 truncate">
                            {{ stats.last_backup_date ? formatDateTime(stats.last_backup_date) : "No backups yet" }}
                        </p>
                        <p class="mt-1 text-xs text-gray-500">
                            {{ stats.last_backup_date ? formatTimeAgo(stats.last_backup_date) : "Ready for first snapshot" }}
                        </p>
                    </div>
                </div>

                <!-- Live Clinic Data Statistics Chips -->
                <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm space-y-3">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold uppercase tracking-wider text-gray-700 flex items-center gap-1.5">
                            <i class="pi pi-chart-pie text-teal-600"></i>
                            Active Database Entity Counts (Snapshot Breakdown)
                        </span>
                        <span class="text-[11px] text-gray-400">
                            Synchronized with active SQLite database
                        </span>
                    </div>

                    <div class="flex flex-wrap items-center gap-2 text-xs">
                        <div class="flex items-center gap-1.5 rounded-xl border border-gray-200 bg-gray-50 px-3 py-1.5 font-medium text-gray-700">
                            <i class="pi pi-users text-teal-600 text-xs"></i>
                            <span>Patients:</span>
                            <span class="font-bold text-gray-900">{{ stats.table_counts?.patients || 0 }}</span>
                        </div>

                        <div class="flex items-center gap-1.5 rounded-xl border border-gray-200 bg-gray-50 px-3 py-1.5 font-medium text-gray-700">
                            <i class="pi pi-calendar text-teal-600 text-xs"></i>
                            <span>Appointments:</span>
                            <span class="font-bold text-gray-900">{{ stats.table_counts?.appointments || 0 }}</span>
                        </div>

                        <div class="flex items-center gap-1.5 rounded-xl border border-gray-200 bg-gray-50 px-3 py-1.5 font-medium text-gray-700">
                            <i class="pi pi-file-edit text-indigo-600 text-xs"></i>
                            <span>Treatments:</span>
                            <span class="font-bold text-gray-900">{{ stats.table_counts?.treatments || 0 }}</span>
                        </div>

                        <div class="flex items-center gap-1.5 rounded-xl border border-gray-200 bg-gray-50 px-3 py-1.5 font-medium text-gray-700">
                            <i class="pi pi-receipt text-amber-600 text-xs"></i>
                            <span>Invoices:</span>
                            <span class="font-bold text-gray-900">{{ stats.table_counts?.invoices || 0 }}</span>
                        </div>

                        <div class="flex items-center gap-1.5 rounded-xl border border-gray-200 bg-gray-50 px-3 py-1.5 font-medium text-gray-700">
                            <i class="pi pi-credit-card text-emerald-600 text-xs"></i>
                            <span>Payments:</span>
                            <span class="font-bold text-gray-900">{{ stats.table_counts?.payments || 0 }}</span>
                        </div>

                        <div class="flex items-center gap-1.5 rounded-xl border border-gray-200 bg-gray-50 px-3 py-1.5 font-medium text-gray-700">
                            <i class="pi pi-shield text-purple-600 text-xs"></i>
                            <span>Audit Logs:</span>
                            <span class="font-bold text-gray-900">{{ stats.table_counts?.audit_logs || 0 }}</span>
                        </div>

                        <div class="flex items-center gap-1.5 rounded-xl border border-gray-200 bg-gray-50 px-3 py-1.5 font-medium text-gray-700">
                            <i class="pi pi-id-card text-sky-600 text-xs"></i>
                            <span>Users:</span>
                            <span class="font-bold text-gray-900">{{ stats.table_counts?.users || 0 }}</span>
                        </div>
                    </div>
                </div>

                <!-- Backup Archives List -->
                <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm">
                    <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
                        <div>
                            <h3 class="text-sm font-bold text-gray-900">
                                Available Backup Archives
                            </h3>
                            <p class="text-xs text-gray-500">
                                Historical point-in-time snapshots ready for download or one-click recovery.
                            </p>
                        </div>
                        <span class="rounded-full bg-gray-100 px-2.5 py-0.5 text-xs font-semibold text-gray-600">
                            {{ backups.total }} archives
                        </span>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 text-left text-xs">
                            <thead class="bg-gray-50/75">
                                <tr>
                                    <th scope="col" class="px-5 py-3.5 font-bold uppercase tracking-wider text-gray-600">
                                        Archive File & Type
                                    </th>
                                    <th scope="col" class="px-5 py-3.5 font-bold uppercase tracking-wider text-gray-600">
                                        Timestamp (PHT)
                                    </th>
                                    <th scope="col" class="px-5 py-3.5 font-bold uppercase tracking-wider text-gray-600">
                                        Size
                                    </th>
                                    <th scope="col" class="px-5 py-3.5 font-bold uppercase tracking-wider text-gray-600">
                                        Data Scope / Notes
                                    </th>
                                    <th scope="col" class="px-5 py-3.5 font-bold uppercase tracking-wider text-gray-600">
                                        Checksum (SHA-256)
                                    </th>
                                    <th scope="col" class="px-5 py-3.5 text-right font-bold uppercase tracking-wider text-gray-600">
                                        Actions
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 bg-white">
                                <tr
                                    v-for="b in backups.data"
                                    :key="b.filename"
                                    :class="[
                                        'transition',
                                        b.is_rollback ? 'bg-amber-50/30 hover:bg-amber-50/50' : 'hover:bg-gray-50/60'
                                    ]"
                                >
                                    <!-- Archive File & Type -->
                                    <td class="px-5 py-3.5 whitespace-nowrap">
                                        <div class="flex items-center gap-3">
                                            <div
                                                :class="[
                                                    'flex h-9 w-9 shrink-0 items-center justify-center rounded-xl',
                                                    b.is_rollback ? 'bg-amber-100 text-amber-700' : 'bg-teal-50 text-teal-700'
                                                ]"
                                            >
                                                <i :class="b.is_rollback ? 'pi pi-history' : 'pi pi-file-zip'" class="text-base"></i>
                                            </div>
                                            <div>
                                                <div class="font-mono font-semibold text-gray-900 text-xs">
                                                    {{ b.filename }}
                                                </div>
                                                <div class="flex items-center gap-1.5 mt-0.5">
                                                    <span
                                                        v-if="b.is_rollback"
                                                        class="inline-block rounded-full bg-amber-100 text-amber-800 border border-amber-200 px-2 py-0.2 text-[10px] font-bold"
                                                    >
                                                        Emergency Pre-Restore Rollback
                                                    </span>
                                                    <span
                                                        v-else
                                                        class="inline-block rounded-full bg-teal-50 text-teal-700 border border-teal-200 px-2 py-0.2 text-[10px] font-semibold"
                                                    >
                                                        {{ b.type }}
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                    </td>

                                    <!-- Timestamp -->
                                    <td class="px-5 py-3.5 whitespace-nowrap">
                                        <div class="font-semibold text-gray-900">
                                            {{ formatDateTime(b.created_at) }}
                                        </div>
                                        <div class="text-[11px] text-gray-400">
                                            {{ formatTimeAgo(b.created_at) }}
                                        </div>
                                    </td>

                                    <!-- Size -->
                                    <td class="px-5 py-3.5 whitespace-nowrap">
                                        <span class="font-bold text-gray-900">
                                            {{ b.size_formatted }}
                                        </span>
                                    </td>

                                    <!-- Data Scope / Notes -->
                                    <td class="px-5 py-3.5 max-w-xs">
                                        <div v-if="b.manifest?.note" class="text-xs text-gray-800 font-medium">
                                            {{ b.manifest.note }}
                                        </div>
                                        <div v-if="b.manifest?.total_records" class="text-[11px] text-gray-500">
                                            {{ Number(b.manifest.total_records).toLocaleString() }} database records archived
                                        </div>
                                        <div v-if="b.manifest?.created_by" class="text-[10px] text-gray-400">
                                            Created by: {{ b.manifest.created_by }}
                                        </div>
                                        <span v-if="!b.manifest" class="text-xs text-gray-400 italic">
                                            Standard archive snapshot
                                        </span>
                                    </td>

                                    <!-- Checksum -->
                                    <td class="px-5 py-3.5 whitespace-nowrap font-mono text-[11px] text-gray-500">
                                        <span :title="b.sha256">
                                            {{ b.sha256 ? b.sha256.substring(0, 14) + '...' : 'Verified' }}
                                        </span>
                                    </td>

                                    <!-- Actions -->
                                    <td class="px-5 py-3.5 whitespace-nowrap text-right space-x-1.5">
                                        <!-- Download Button -->
                                        <a
                                            :href="route('backup.download', b.filename)"
                                            class="inline-flex items-center gap-1 rounded-lg border border-gray-200 bg-white px-2.5 py-1 text-xs font-semibold text-gray-700 shadow-xs transition hover:bg-teal-50 hover:text-teal-700 hover:border-teal-300"
                                            title="Download archive file to local storage"
                                        >
                                            <i class="pi pi-download text-[10px]"></i>
                                            <span>Download</span>
                                        </a>

                                        <!-- Restore Button -->
                                        <button
                                            type="button"
                                            @click="openRestoreModal(b)"
                                            class="inline-flex items-center gap-1 rounded-lg border border-amber-200 bg-amber-50 px-2.5 py-1 text-xs font-semibold text-amber-800 shadow-xs transition hover:bg-amber-100"
                                            title="Restore clinic database to this snapshot point"
                                        >
                                            <i class="pi pi-refresh text-[10px]"></i>
                                            <span>Restore</span>
                                        </button>

                                        <!-- Delete Button -->
                                        <button
                                            type="button"
                                            @click="deleteBackup(b)"
                                            class="inline-flex items-center justify-center rounded-lg border border-transparent p-1.5 text-xs text-rose-500 hover:bg-rose-50 hover:text-rose-700 transition"
                                            title="Delete backup file"
                                        >
                                            <i class="pi pi-trash text-xs"></i>
                                        </button>
                                    </td>
                                </tr>

                                <!-- Empty State -->
                                <tr v-if="!backups.data || backups.data.length === 0">
                                    <td colspan="6" class="px-6 py-16 text-center">
                                        <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-teal-50 text-teal-600">
                                            <i class="pi pi-database text-2xl"></i>
                                        </div>
                                        <h3 class="mt-3 text-sm font-bold text-gray-900">
                                            No backup snapshots found
                                        </h3>
                                        <p class="mt-1 text-xs text-gray-500 max-w-sm mx-auto">
                                            No system backups have been generated yet. Create your first snapshot to secure your clinic records against data loss.
                                        </p>
                                        <button
                                            type="button"
                                            @click="openCreateModal"
                                            class="mt-4 inline-flex items-center gap-1.5 rounded-xl bg-teal-700 px-4 py-2 text-xs font-semibold text-white shadow-sm hover:bg-teal-800"
                                        >
                                            <i class="pi pi-plus text-xs"></i>
                                            <span>Create First Backup Now</span>
                                        </button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <Pagination :paginator="backups" label="archives" />
                </div>
            </div>
        </div>

        <!-- 1. Create Backup Modal -->
        <Modal :show="showCreateModal" @close="showCreateModal = false" max-width="lg">
            <div class="p-6 space-y-5">
                <div class="flex items-center justify-between border-b border-gray-100 pb-3">
                    <div class="flex items-center gap-2.5">
                        <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-teal-50 text-teal-700">
                            <i class="pi pi-database text-base"></i>
                        </div>
                        <div>
                            <h3 class="text-base font-bold text-gray-900">
                                Create System Backup Snapshot
                            </h3>
                            <p class="text-xs text-gray-500">
                                Take a secure point-in-time snapshot of Vitality Smile database records.
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
                    <!-- Backup Type -->
                    <div>
                        <label class="block text-xs font-semibold text-gray-700 mb-2">
                            Select Backup Scope:
                        </label>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <label
                                :class="[
                                    'cursor-pointer rounded-xl border p-3.5 flex flex-col justify-between transition',
                                    createForm.type === 'database'
                                        ? 'border-teal-500 bg-teal-50/40 ring-1 ring-teal-500'
                                        : 'border-gray-200 hover:border-gray-300'
                                ]"
                            >
                                <div class="flex items-center justify-between mb-2">
                                    <span class="text-xs font-bold text-gray-900">Database Snapshot</span>
                                    <input
                                        type="radio"
                                        v-model="createForm.type"
                                        value="database"
                                        class="text-teal-600 focus:ring-teal-500 h-4 w-4"
                                    />
                                </div>
                                <p class="text-[11px] text-gray-500 leading-relaxed">
                                    Archives all patients, appointments, treatments, invoices, payments, and audit logs. Fast and compact.
                                </p>
                            </label>

                            <label
                                :class="[
                                    'cursor-pointer rounded-xl border p-3.5 flex flex-col justify-between transition',
                                    createForm.type === 'full'
                                        ? 'border-teal-500 bg-teal-50/40 ring-1 ring-teal-500'
                                        : 'border-gray-200 hover:border-gray-300'
                                ]"
                            >
                                <div class="flex items-center justify-between mb-2">
                                    <span class="text-xs font-bold text-gray-900">Full System Backup</span>
                                    <input
                                        type="radio"
                                        v-model="createForm.type"
                                        value="full"
                                        class="text-teal-600 focus:ring-teal-500 h-4 w-4"
                                    />
                                </div>
                                <p class="text-[11px] text-gray-500 leading-relaxed">
                                    Includes complete database plus uploaded media assets and user profile avatars in public storage.
                                </p>
                            </label>
                        </div>
                    </div>

                    <!-- Backup Note -->
                    <div>
                        <label class="block text-xs font-semibold text-gray-700 mb-1">
                            Snapshot Label / Remark (Optional)
                        </label>
                        <input
                            v-model="createForm.note"
                            type="text"
                            placeholder="e.g. Monthly maintenance backup, pre-update backup"
                            class="w-full rounded-xl border-gray-300 text-xs py-2.5 focus:border-teal-500 focus:ring-teal-500"
                        />
                    </div>

                    <div class="rounded-xl border border-teal-200 bg-teal-50/60 p-3 text-xs text-teal-900 flex items-start gap-2">
                        <i class="pi pi-info-circle text-teal-600 mt-0.5"></i>
                        <p class="text-[11px] leading-relaxed text-teal-800">
                            Backups are compressed with ZIP, verified with SHA-256 integrity checksums, and recorded in the audit trail.
                        </p>
                    </div>

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
                            <i class="pi pi-database text-xs"></i>
                            <span>{{ createForm.processing ? "Creating Snapshot..." : "Generate Backup" }}</span>
                        </button>
                    </div>
                </form>
            </div>
        </Modal>

        <!-- 2. Restore Confirmation Modal -->
        <Modal :show="showRestoreModal" @close="showRestoreModal = false" max-width="md">
            <div v-if="selectedBackup" class="p-6 space-y-4">
                <div class="flex items-center gap-3">
                    <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-amber-50 text-amber-700">
                        <i class="pi pi-exclamation-triangle text-lg"></i>
                    </div>
                    <div>
                        <h3 class="text-base font-bold text-gray-900">
                            Confirm System Restoration
                        </h3>
                        <p class="text-xs text-gray-500">
                            Restore database records to snapshot point.
                        </p>
                    </div>
                </div>

                <div class="rounded-xl border border-amber-200 bg-amber-50 p-4 space-y-2 text-xs text-amber-900">
                    <p class="font-bold flex items-center gap-1.5">
                        <i class="pi pi-shield text-amber-700"></i>
                        Automatic Pre-Restore Rollback Protection:
                    </p>
                    <p class="text-[11px] leading-relaxed text-amber-800">
                        Before applying this restoration, the system will automatically create an emergency snapshot of your current database. If anything is wrong, you can immediately roll back.
                    </p>
                </div>

                <div class="rounded-xl border border-gray-200 bg-gray-50 p-3 text-xs space-y-1">
                    <div class="flex justify-between">
                        <span class="text-gray-500">Restoring From:</span>
                        <span class="font-mono font-bold text-gray-900">{{ selectedBackup.filename }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-500">Archive Date:</span>
                        <span class="font-medium text-gray-700">{{ formatDateTime(selectedBackup.created_at) }}</span>
                    </div>
                </div>

                <form @submit.prevent="submitRestore" class="space-y-4 pt-2">
                    <div>
                        <label class="block text-xs font-semibold text-gray-700 mb-1">
                            Type <span class="font-mono text-rose-600 font-bold">RESTORE</span> to confirm:
                        </label>
                        <input
                            v-model="restoreForm.confirmation"
                            type="text"
                            required
                            placeholder="RESTORE"
                            class="w-full rounded-xl border-gray-300 font-mono text-xs py-2.5 focus:border-rose-500 focus:ring-rose-500"
                        />
                        <p v-if="restoreForm.errors.confirmation" class="text-xs text-rose-600 mt-1">
                            {{ restoreForm.errors.confirmation }}
                        </p>
                    </div>

                    <div class="mt-6 flex justify-end gap-2.5 border-t border-gray-100 pt-4">
                        <button
                            type="button"
                            @click="showRestoreModal = false"
                            class="rounded-xl border border-gray-300 bg-white px-4 py-2 text-xs font-semibold text-gray-700 hover:bg-gray-50"
                        >
                            Cancel
                        </button>
                        <button
                            type="submit"
                            :disabled="restoreForm.processing || restoreForm.confirmation !== 'RESTORE'"
                            class="inline-flex items-center gap-1.5 rounded-xl bg-amber-600 px-4 py-2 text-xs font-semibold text-white shadow-sm hover:bg-amber-700 disabled:opacity-40"
                        >
                            <i class="pi pi-refresh text-xs"></i>
                            <span>{{ restoreForm.processing ? "Restoring System..." : "Confirm & Restore Data" }}</span>
                        </button>
                    </div>
                </form>
            </div>
        </Modal>

        <!-- 3. Upload & Restore Modal -->
        <Modal :show="showUploadModal" @close="showUploadModal = false" max-width="md">
            <div class="p-6 space-y-4">
                <div class="flex items-center gap-3">
                    <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-teal-50 text-teal-700">
                        <i class="pi pi-upload text-lg"></i>
                    </div>
                    <div>
                        <h3 class="text-base font-bold text-gray-900">
                            Upload External Backup Archive
                        </h3>
                        <p class="text-xs text-gray-500">
                            Restore clinic records from an off-site .zip or .sqlite backup file.
                        </p>
                    </div>
                </div>

                <form @submit.prevent="submitUploadRestore" class="space-y-4 pt-2">
                    <div>
                        <label class="block text-xs font-semibold text-gray-700 mb-1">
                            Choose Backup File (.zip or .sqlite)
                        </label>
                        <input
                            type="file"
                            @change="handleFileUpload"
                            accept=".zip,.sqlite"
                            required
                            class="w-full text-xs text-gray-700 file:mr-3 file:py-2 file:px-3 file:rounded-xl file:border-0 file:text-xs file:font-semibold file:bg-teal-50 file:text-teal-700 hover:file:bg-teal-100"
                        />
                        <p v-if="uploadForm.errors.backup_file" class="text-xs text-rose-600 mt-1">
                            {{ uploadForm.errors.backup_file }}
                        </p>
                    </div>

                    <div class="rounded-xl border border-amber-200 bg-amber-50 p-3 text-xs text-amber-900">
                        <i class="pi pi-shield text-amber-700 mr-1"></i>
                        The system will verify the SQLite file header and automatically create a pre-restore emergency rollback.
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-gray-700 mb-1">
                            Type <span class="font-mono text-rose-600 font-bold">RESTORE</span> to confirm:
                        </label>
                        <input
                            v-model="uploadForm.confirmation"
                            type="text"
                            required
                            placeholder="RESTORE"
                            class="w-full rounded-xl border-gray-300 font-mono text-xs py-2.5 focus:border-rose-500 focus:ring-rose-500"
                        />
                        <p v-if="uploadForm.errors.confirmation" class="text-xs text-rose-600 mt-1">
                            {{ uploadForm.errors.confirmation }}
                        </p>
                    </div>

                    <div class="mt-6 flex justify-end gap-2.5 border-t border-gray-100 pt-4">
                        <button
                            type="button"
                            @click="showUploadModal = false"
                            class="rounded-xl border border-gray-300 bg-white px-4 py-2 text-xs font-semibold text-gray-700 hover:bg-gray-50"
                        >
                            Cancel
                        </button>
                        <button
                            type="submit"
                            :disabled="uploadForm.processing || uploadForm.confirmation !== 'RESTORE' || !uploadForm.backup_file"
                            class="inline-flex items-center gap-1.5 rounded-xl bg-teal-700 px-4 py-2 text-xs font-semibold text-white shadow-sm hover:bg-teal-800 disabled:opacity-40"
                        >
                            <i class="pi pi-upload text-xs"></i>
                            <span>{{ uploadForm.processing ? "Uploading & Restoring..." : "Upload & Restore" }}</span>
                        </button>
                    </div>
                </form>
            </div>
        </Modal>
    </AuthenticatedLayout>
</template>

