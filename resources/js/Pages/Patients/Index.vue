<script setup>
import { ref, watch, computed } from "vue";
import { Head, Link, router, usePage } from "@inertiajs/vue3";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";

const page = usePage();

const props = defineProps({
    patients: {
        type: Object,
        default: () => ({ data: [], links: [] }),
    },
    filters: {
        type: Object,
        default: () => ({ search: "", status: "", gender: "" }),
    },
    stats: {
        type: Object,
        default: () => ({
            total: 0,
            active: 0,
            inactive: 0,
            archived: 0,
            new_this_month: 0,
            with_alerts: 0,
        }),
    },
});

const userRole = computed(() => page.props.auth?.user?.role || "staff");

// Filters
const search = ref(props.filters.search || "");
const status = ref(props.filters.status || "");
const gender = ref(props.filters.gender || "");

let debounceTimeout = null;
const applyFilters = () => {
    if (debounceTimeout) clearTimeout(debounceTimeout);
    debounceTimeout = setTimeout(() => {
        router.get(
            route("patients.index"),
            {
                search: search.value || undefined,
                status: status.value || undefined,
                gender: gender.value || undefined,
            },
            { preserveState: true, replace: true }
        );
    }, 300);
};

watch([search, status, gender], () => {
    applyFilters();
});

const clearFilters = () => {
    search.value = "";
    status.value = "";
    gender.value = "";
};

const hasActiveFilters = computed(() => {
    return !!(search.value || status.value || gender.value);
});

// Archive Confirmation Modal
const patientToArchive = ref(null);
const isArchiving = ref(false);

const confirmArchive = (patient) => {
    patientToArchive.value = patient;
};

const cancelArchive = () => {
    patientToArchive.value = null;
};

const executeArchive = () => {
    if (!patientToArchive.value) return;
    isArchiving.value = true;
    router.delete(route("patients.destroy", patientToArchive.value.id), {
        onFinish: () => {
            isArchiving.value = false;
            patientToArchive.value = null;
        },
    });
};

// Quick Preview Modal
const previewPatient = ref(null);
const openPreview = (patient) => {
    previewPatient.value = patient;
};
const closePreview = () => {
    previewPatient.value = null;
};

// Copy contact feedback
const copiedId = ref(null);
const copyToClipboard = (text, id) => {
    if (!text) return;
    navigator.clipboard.writeText(text);
    copiedId.value = id;
    setTimeout(() => {
        copiedId.value = null;
    }, 2000);
};

// Avatar colors
const getAvatarClass = (index) => {
    const colors = [
        "bg-teal-100 text-teal-800 border-teal-200",
        "bg-blue-100 text-blue-800 border-blue-200",
        "bg-purple-100 text-purple-800 border-purple-200",
        "bg-emerald-100 text-emerald-800 border-emerald-200",
        "bg-indigo-100 text-indigo-800 border-indigo-200",
        "bg-amber-100 text-amber-800 border-amber-200",
    ];
    return colors[index % colors.length];
};

const formatDate = (dateStr) => {
    if (!dateStr) return "N/A";
    try {
        return new Date(dateStr).toLocaleDateString("en-PH", {
            year: "numeric",
            month: "short",
            day: "numeric",
        });
    } catch (e) {
        return dateStr;
    }
};

const formatTime = (timeStr) => {
    if (!timeStr) return "";
    try {
        const [hours, minutes] = timeStr.split(":");
        const d = new Date();
        d.setHours(parseInt(hours, 10));
        d.setMinutes(parseInt(minutes, 10));
        return d.toLocaleTimeString([], { hour: "numeric", minute: "2-digit", hour12: true });
    } catch (e) {
        return timeStr;
    }
};
</script>

<template>
    <Head title="Patient Management - Vitality Smile" />

    <AuthenticatedLayout>
        <!-- Header Banner -->
        <template #header>
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <!-- Breadcrumb Pill -->
                    <div class="flex items-center gap-2">
                        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-teal-50 text-teal-800 border border-teal-200 tracking-wide uppercase">
                            <i class="pi pi-users text-xs"></i>
                            Clinical Directory
                        </span>
                        <span class="text-xs text-slate-400 font-medium">
                            • Patient Management & Health Records
                        </span>
                    </div>

                    <h1 class="mt-2 text-2xl font-black text-slate-900 tracking-tight sm:text-3xl">
                        Patient Management
                    </h1>
                    <p class="mt-1 text-sm text-slate-500 max-w-2xl leading-relaxed">
                        Comprehensive registry of patient profiles, contact details, medical histories, and clinical appointments.
                    </p>
                </div>

                <!-- Action Button -->
                <div
                    v-if="['admin', 'secretary', 'dentist', 'staff'].includes(userRole)"
                    class="flex items-center gap-2.5 shrink-0"
                >
                    <Link
                        :href="route('patients.create')"
                        class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl font-bold text-sm text-white bg-gradient-to-r from-teal-600 to-emerald-600 hover:from-teal-700 hover:to-emerald-700 shadow-sm transition hover:shadow-md hover:-translate-y-0.5"
                    >
                        <i class="pi pi-plus text-xs font-bold"></i>
                        <span>Register New Patient</span>
                    </Link>
                </div>
            </div>
        </template>

        <!-- Main Body Content -->
        <div class="py-6 sm:py-8 space-y-6">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 space-y-6">

                <!-- Clinical KPI Metric Cards (Grid of 4) -->
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    <!-- Total Patients -->
                    <div class="group relative overflow-hidden rounded-2xl border border-slate-200/90 bg-white p-5 shadow-2xs transition-all hover:border-teal-300 hover:shadow-sm">
                        <div class="absolute top-0 inset-x-0 h-1 bg-gradient-to-r from-teal-500 to-cyan-500"></div>
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <p class="text-xs font-bold uppercase tracking-wider text-slate-500">
                                    Total Registered
                                </p>
                                <p class="mt-2 text-2xl font-black text-slate-900 sm:text-3xl tracking-tight">
                                    {{ stats.total }}
                                </p>
                                <p class="mt-1 text-xs text-slate-500 font-medium">
                                    {{ stats.active }} active patient files
                                </p>
                            </div>
                            <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-teal-50 text-teal-600 border border-teal-100 shadow-2xs">
                                <i class="pi pi-users text-lg"></i>
                            </div>
                        </div>
                    </div>

                    <!-- New Patients This Month -->
                    <div class="group relative overflow-hidden rounded-2xl border border-slate-200/90 bg-white p-5 shadow-2xs transition-all hover:border-blue-300 hover:shadow-sm">
                        <div class="absolute top-0 inset-x-0 h-1 bg-gradient-to-r from-blue-500 to-indigo-500"></div>
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <p class="text-xs font-bold uppercase tracking-wider text-slate-500">
                                    New This Month
                                </p>
                                <p class="mt-2 text-2xl font-black text-blue-700 sm:text-3xl tracking-tight">
                                    {{ stats.new_this_month }}
                                </p>
                                <p class="mt-1 text-xs text-slate-500 font-medium">
                                    Recent clinic intake
                                </p>
                            </div>
                            <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-blue-50 text-blue-600 border border-blue-100 shadow-2xs">
                                <i class="pi pi-user-plus text-lg"></i>
                            </div>
                        </div>
                    </div>

                    <!-- Medical Precaution Alerts -->
                    <div class="group relative overflow-hidden rounded-2xl border border-slate-200/90 bg-white p-5 shadow-2xs transition-all hover:border-amber-300 hover:shadow-sm">
                        <div class="absolute top-0 inset-x-0 h-1 bg-gradient-to-r from-amber-500 to-rose-500"></div>
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <p class="text-xs font-bold uppercase tracking-wider text-slate-500">
                                    Medical Precaution Alerts
                                </p>
                                <p class="mt-2 text-2xl font-black text-amber-700 sm:text-3xl tracking-tight">
                                    {{ stats.with_alerts }}
                                </p>
                                <p class="mt-1 text-xs text-slate-500 font-medium">
                                    Allergies or systemic risks
                                </p>
                            </div>
                            <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-amber-50 text-amber-600 border border-amber-100 shadow-2xs">
                                <i class="pi pi-exclamation-triangle text-lg"></i>
                            </div>
                        </div>
                    </div>

                    <!-- Inactive / Archived Records -->
                    <div class="group relative overflow-hidden rounded-2xl border border-slate-200/90 bg-white p-5 shadow-2xs transition-all hover:border-slate-300 hover:shadow-sm">
                        <div class="absolute top-0 inset-x-0 h-1 bg-gradient-to-r from-slate-400 to-slate-600"></div>
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <p class="text-xs font-bold uppercase tracking-wider text-slate-500">
                                    Inactive / Archived
                                </p>
                                <p class="mt-2 text-2xl font-black text-slate-700 sm:text-3xl tracking-tight">
                                    {{ (stats.inactive || 0) + (stats.archived || 0) }}
                                </p>
                                <p class="mt-1 text-xs text-slate-500 font-medium">
                                    {{ stats.inactive }} inactive • {{ stats.archived }} archived
                                </p>
                            </div>
                            <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-slate-100 text-slate-600 border border-slate-200 shadow-2xs">
                                <i class="pi pi-inbox text-lg"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Smart Search & Filter Control Bar -->
                <div class="rounded-2xl border border-slate-200/90 bg-white p-4 sm:p-5 shadow-2xs">
                    <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
                        <!-- Search Box -->
                        <div class="relative flex-1">
                            <i class="pi pi-search absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 text-sm"></i>
                            <input
                                v-model="search"
                                type="text"
                                placeholder="Search by patient name, ID (e.g. PAT-2026), contact #, or email..."
                                class="w-full pl-10 pr-10 py-2.5 rounded-xl border border-slate-200 text-sm text-slate-900 placeholder:text-slate-400 focus:border-teal-500 focus:ring-2 focus:ring-teal-500/20 shadow-2xs transition dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100 dark:placeholder:text-slate-500"
                            />
                            <button
                                v-if="search"
                                @click="search = ''"
                                class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 p-1"
                                title="Clear search"
                            >
                                <i class="pi pi-times text-xs"></i>
                            </button>
                        </div>

                        <!-- Filters Strip -->
                        <div class="flex flex-wrap items-center gap-3">
                            <!-- Status Selector -->
                            <div class="relative">
                                <select
                                    v-model="status"
                                    class="appearance-none pl-3.5 pr-8 py-2.5 rounded-xl border border-slate-200 text-sm font-semibold text-slate-700 bg-white hover:border-slate-300 focus:border-teal-500 focus:ring-2 focus:ring-teal-500/20 shadow-2xs transition cursor-pointer dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200"
                                >
                                    <option value="">All Statuses</option>
                                    <option value="Active">Active</option>
                                    <option value="Inactive">Inactive</option>
                                    <option value="Archived">Archived</option>
                                </select>
                                <i class="pi pi-chevron-down absolute right-3 top-1/2 -translate-y-1/2 text-[10px] text-slate-400 pointer-events-none"></i>
                            </div>

                            <!-- Gender Selector -->
                            <div class="relative">
                                <select
                                    v-model="gender"
                                    class="appearance-none pl-3.5 pr-8 py-2.5 rounded-xl border border-slate-200 text-sm font-semibold text-slate-700 bg-white hover:border-slate-300 focus:border-teal-500 focus:ring-2 focus:ring-teal-500/20 shadow-2xs transition cursor-pointer dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200"
                                >
                                    <option value="">All Genders</option>
                                    <option value="Male">Male</option>
                                    <option value="Female">Female</option>
                                    <option value="Other">Other</option>
                                </select>
                                <i class="pi pi-chevron-down absolute right-3 top-1/2 -translate-y-1/2 text-[10px] text-slate-400 pointer-events-none"></i>
                            </div>

                            <!-- Reset Filters -->
                            <button
                                v-if="hasActiveFilters"
                                @click="clearFilters"
                                class="inline-flex items-center gap-1.5 px-3 py-2.5 rounded-xl text-xs font-bold text-rose-700 bg-rose-50 hover:bg-rose-100 border border-rose-200 transition"
                            >
                                <i class="pi pi-filter-slash text-xs"></i>
                                <span>Clear Filters</span>
                            </button>
                        </div>
                    </div>

                    <!-- Sub-bar with Record Count -->
                    <div class="mt-3 pt-3 border-t border-slate-100 flex flex-wrap items-center justify-between text-xs text-slate-500 gap-2">
                        <div class="flex items-center gap-2">
                            <span class="font-medium">
                                Showing <strong class="text-slate-900 font-bold">{{ patients.data?.length || 0 }}</strong> of
                                <strong class="text-slate-900 font-bold">{{ patients.total || 0 }}</strong> registered patients
                            </span>
                            <span v-if="hasActiveFilters" class="inline-flex items-center px-2 py-0.5 rounded-md text-[10px] font-bold bg-amber-50 text-amber-800 border border-amber-200">
                                Filter Active
                            </span>
                        </div>

                        <div class="flex items-center gap-3">
                            <span class="inline-flex items-center gap-1.5">
                                <span class="h-2 w-2 rounded-full bg-emerald-500"></span> Active
                            </span>
                            <span class="inline-flex items-center gap-1.5">
                                <span class="h-2 w-2 rounded-full bg-amber-500"></span> Inactive
                            </span>
                            <span class="inline-flex items-center gap-1.5">
                                <span class="h-2 w-2 rounded-full bg-slate-400"></span> Archived
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Patient Records Container -->
                <div class="rounded-2xl border border-slate-200/90 bg-white shadow-2xs overflow-hidden">
                    <!-- Desktop Table (lg and up) -->
                    <div class="hidden lg:block overflow-x-auto">
                        <table class="min-w-full divide-y divide-slate-100 text-left">
                            <thead class="bg-slate-50/70 text-slate-500 text-xs font-bold uppercase tracking-wider">
                                <tr>
                                    <th scope="col" class="py-3.5 pl-6 pr-4">Patient Profile</th>
                                    <th scope="col" class="py-3.5 px-4">Demographics</th>
                                    <th scope="col" class="py-3.5 px-4">Contact Info</th>
                                    <th scope="col" class="py-3.5 px-4">Medical Precaution</th>
                                    <th scope="col" class="py-3.5 px-4">Status & Activity</th>
                                    <th scope="col" class="py-3.5 pl-4 pr-6 text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                <template v-if="patients.data && patients.data.length > 0">
                                    <tr
                                        v-for="(patient, index) in patients.data"
                                        :key="patient.id"
                                        class="hover:bg-slate-50/80 transition-colors group"
                                    >
                                        <!-- Patient Profile -->
                                        <td class="py-4 pl-6 pr-4 whitespace-nowrap">
                                            <div class="flex items-center gap-3.5">
                                                <div
                                                    :class="[
                                                        'flex h-11 w-11 shrink-0 items-center justify-center rounded-xl text-xs font-black border transition group-hover:scale-105 shadow-2xs',
                                                        getAvatarClass(index),
                                                    ]"
                                                >
                                                    {{ (patient.first_name || 'P').charAt(0) }}{{ (patient.last_name || 'U').charAt(0) }}
                                                </div>

                                                <div class="min-w-0">
                                                    <div class="flex items-center gap-2">
                                                        <Link
                                                            :href="route('patients.show', patient.id)"
                                                            class="font-bold text-slate-900 text-sm group-hover:text-teal-700 transition hover:underline"
                                                        >
                                                            {{ patient.first_name }} {{ patient.middle_name ? patient.middle_name + ' ' : '' }}{{ patient.last_name }} {{ patient.suffix || '' }}
                                                        </Link>
                                                        <span class="text-[10px] font-mono font-bold text-slate-600 bg-slate-100 px-1.5 py-0.5 rounded border border-slate-200">
                                                            {{ patient.patient_id }}
                                                        </span>
                                                    </div>

                                                    <div class="mt-0.5 flex items-center gap-2 text-xs text-slate-500">
                                                        <span v-if="patient.guardian" class="inline-flex items-center gap-1 text-slate-600 font-medium">
                                                            <i class="pi pi-shield text-[10px] text-amber-600"></i>
                                                            Guardian: {{ patient.guardian }}
                                                        </span>
                                                        <span v-else class="text-slate-400">
                                                            Registered {{ formatDate(patient.created_at) }}
                                                        </span>
                                                    </div>
                                                </div>
                                            </div>
                                        </td>

                                        <!-- Demographics -->
                                        <td class="py-4 px-4 whitespace-nowrap">
                                            <div class="flex items-center gap-2">
                                                <span
                                                    :class="[
                                                        patient.gender === 'Female' ? 'bg-pink-50 text-pink-700 border-pink-200' : 'bg-blue-50 text-blue-700 border-blue-200',
                                                        'inline-flex items-center px-2 py-0.5 rounded-md text-xs font-bold border',
                                                    ]"
                                                >
                                                    {{ patient.gender }}
                                                </span>
                                                <span class="text-xs font-bold text-slate-700">
                                                    {{ patient.age ? `${patient.age} yrs` : 'N/A' }}
                                                </span>
                                            </div>
                                            <p class="text-[11px] text-slate-400 mt-1">
                                                DOB: {{ formatDate(patient.date_of_birth) }}
                                            </p>
                                        </td>

                                        <!-- Contact Info -->
                                        <td class="py-4 px-4 whitespace-nowrap">
                                            <div class="flex items-center gap-2">
                                                <a
                                                    :href="'tel:' + patient.contact_number"
                                                    class="font-mono font-semibold text-xs text-slate-900 hover:text-teal-700 hover:underline flex items-center gap-1"
                                                >
                                                    <i class="pi pi-phone text-[10px] text-teal-600"></i>
                                                    {{ patient.contact_number }}
                                                </a>
                                                <button
                                                    @click="copyToClipboard(patient.contact_number, patient.id)"
                                                    class="text-slate-400 hover:text-teal-700 text-[10px] p-0.5"
                                                    title="Copy phone number"
                                                >
                                                    <i :class="copiedId === patient.id ? 'pi pi-check text-emerald-600' : 'pi pi-copy'"></i>
                                                </button>
                                            </div>
                                            <p class="text-[11px] text-slate-500 mt-0.5 truncate max-w-[180px]">
                                                {{ patient.email || 'No email registered' }}
                                            </p>
                                        </td>

                                        <!-- Medical Precaution / Blood Type -->
                                        <td class="py-4 px-4 whitespace-nowrap">
                                            <div v-if="patient.medical_history">
                                                <div class="flex items-center gap-1.5 flex-wrap">
                                                    <span
                                                        v-if="patient.medical_history.blood_type"
                                                        class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-black bg-teal-50 text-teal-800 border border-teal-200"
                                                    >
                                                        Type {{ patient.medical_history.blood_type }}
                                                    </span>

                                                    <!-- Medical Alert Tag -->
                                                    <span
                                                        v-if="patient.medical_history.has_allergies || patient.medical_history.allergies"
                                                        class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[10px] font-bold bg-rose-50 text-rose-800 border border-rose-200"
                                                        title="Known allergies"
                                                    >
                                                        <i class="pi pi-exclamation-triangle text-[9px]"></i>
                                                        Allergy
                                                    </span>

                                                    <span
                                                        v-else-if="patient.medical_history.hypertension || patient.medical_history.diabetes || patient.medical_history.heart_disease || patient.medical_history.bleeding_disorders"
                                                        class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[10px] font-bold bg-amber-50 text-amber-800 border border-amber-200"
                                                        title="Systemic condition"
                                                    >
                                                        <i class="pi pi-info-circle text-[9px]"></i>
                                                        Condition
                                                    </span>

                                                    <span v-else class="text-[11px] text-emerald-600 font-medium flex items-center gap-1">
                                                        <i class="pi pi-check text-[9px]"></i> NKDA
                                                    </span>
                                                </div>
                                            </div>
                                            <div v-else class="text-xs text-slate-400 italic">
                                                No medical intake
                                            </div>
                                        </td>

                                        <!-- Status & Activity -->
                                        <td class="py-4 px-4 whitespace-nowrap">
                                            <div>
                                                <span
                                                    :class="[
                                                        patient.status === 'Active' ? 'bg-emerald-50 text-emerald-800 border-emerald-200' :
                                                        patient.status === 'Inactive' ? 'bg-amber-50 text-amber-800 border-amber-200' : 'bg-slate-100 text-slate-700 border-slate-200',
                                                        'inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-bold border',
                                                    ]"
                                                >
                                                    <span
                                                        :class="[
                                                            'h-1.5 w-1.5 rounded-full',
                                                            patient.status === 'Active' ? 'bg-emerald-600' :
                                                            patient.status === 'Inactive' ? 'bg-amber-600' : 'bg-slate-400',
                                                        ]"
                                                    ></span>
                                                    {{ patient.status }}
                                                </span>

                                                <!-- Latest Procedure / Appointment -->
                                                <div v-if="patient.treatments && patient.treatments.length > 0" class="mt-1 text-[11px] text-slate-500 truncate max-w-[150px]">
                                                    <i class="pi pi-heart text-[9px] text-teal-600 mr-1"></i>
                                                    {{ patient.treatments[0].procedure_name }}
                                                </div>
                                                <div v-else-if="patient.appointments && patient.appointments.length > 0" class="mt-1 text-[11px] text-slate-500 truncate max-w-[150px]">
                                                    <i class="pi pi-calendar text-[9px] text-blue-600 mr-1"></i>
                                                    {{ patient.appointments[0].service }}
                                                </div>
                                            </div>
                                        </td>

                                        <!-- Actions -->
                                        <td class="py-4 pl-4 pr-6 whitespace-nowrap text-right">
                                            <div class="flex items-center justify-end gap-1.5">
                                                <!-- Quick Preview -->
                                                <button
                                                    @click="openPreview(patient)"
                                                    class="inline-flex items-center justify-center h-8 w-8 rounded-lg text-slate-500 hover:text-teal-700 hover:bg-teal-50 transition"
                                                    title="Quick Preview"
                                                >
                                                    <i class="pi pi-search-plus text-xs"></i>
                                                </button>

                                                <!-- Full Profile Link -->
                                                <Link
                                                    :href="route('patients.show', patient.id)"
                                                    class="inline-flex items-center justify-center h-8 w-8 rounded-lg text-teal-600 hover:text-teal-800 hover:bg-teal-50 transition"
                                                    title="View Full Patient Dossier"
                                                >
                                                    <i class="pi pi-eye text-xs"></i>
                                                </Link>

                                                <!-- Edit Link -->
                                                <Link
                                                    v-if="['admin', 'secretary', 'dentist', 'staff'].includes(userRole)"
                                                    :href="route('patients.edit', patient.id)"
                                                    class="inline-flex items-center justify-center h-8 w-8 rounded-lg text-blue-600 hover:text-blue-800 hover:bg-blue-50 transition"
                                                    title="Edit Patient Information"
                                                >
                                                    <i class="pi pi-pencil text-xs"></i>
                                                </Link>

                                                <!-- Schedule Appointment -->
                                                <Link
                                                    v-if="['admin', 'secretary', 'dentist'].includes(userRole)"
                                                    :href="route('appointments.create', { patient_id: patient.id })"
                                                    class="inline-flex items-center justify-center h-8 w-8 rounded-lg text-amber-600 hover:text-amber-800 hover:bg-amber-50 transition"
                                                    title="Schedule Appointment"
                                                >
                                                    <i class="pi pi-calendar-plus text-xs"></i>
                                                </Link>

                                                <!-- Archive Button -->
                                                <button
                                                    v-if="['admin', 'secretary', 'dentist'].includes(userRole) && patient.status !== 'Archived'"
                                                    @click="confirmArchive(patient)"
                                                    class="inline-flex items-center justify-center h-8 w-8 rounded-lg text-slate-400 hover:text-rose-600 hover:bg-rose-50 transition"
                                                    title="Archive Record"
                                                >
                                                    <i class="pi pi-trash text-xs"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                </template>

                                <!-- Empty State -->
                                <tr v-else>
                                    <td colspan="6" class="py-12 px-6 text-center">
                                        <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-teal-50 text-teal-600 border border-teal-100 mb-3">
                                            <i class="pi pi-users text-2xl"></i>
                                        </div>
                                        <h3 class="text-base font-bold text-slate-900">No Patients Found</h3>
                                        <p class="text-xs text-slate-500 mt-1 max-w-sm mx-auto">
                                            We couldn't find any patient records matching your active search queries or status filters.
                                        </p>
                                        <div class="mt-4 flex items-center justify-center gap-2">
                                            <button
                                                v-if="hasActiveFilters"
                                                @click="clearFilters"
                                                class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl text-xs font-bold text-slate-700 bg-slate-100 hover:bg-slate-200 transition"
                                            >
                                                <i class="pi pi-filter-slash text-xs"></i>
                                                <span>Clear Filters</span>
                                            </button>
                                            <Link
                                                v-if="['admin', 'secretary', 'dentist', 'staff'].includes(userRole)"
                                                :href="route('patients.create')"
                                                class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl text-xs font-bold text-white bg-teal-600 hover:bg-teal-700 shadow-2xs transition"
                                            >
                                                <i class="pi pi-plus text-xs"></i>
                                                <span>Add New Patient</span>
                                            </Link>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Mobile / Tablet Card View (below lg) -->
                    <div class="lg:hidden divide-y divide-slate-100">
                        <template v-if="patients.data && patients.data.length > 0">
                            <div
                                v-for="(patient, index) in patients.data"
                                :key="patient.id"
                                class="p-4 sm:p-5 hover:bg-slate-50/80 transition space-y-3"
                            >
                                <div class="flex items-start justify-between gap-3">
                                    <div class="flex items-center gap-3">
                                        <div
                                            :class="[
                                                'flex h-11 w-11 shrink-0 items-center justify-center rounded-xl text-xs font-black border shadow-2xs',
                                                getAvatarClass(index),
                                            ]"
                                        >
                                            {{ (patient.first_name || 'P').charAt(0) }}{{ (patient.last_name || 'U').charAt(0) }}
                                        </div>
                                        <div>
                                            <div class="flex items-center gap-2">
                                                <Link
                                                    :href="route('patients.show', patient.id)"
                                                    class="font-bold text-slate-900 text-sm hover:text-teal-700 hover:underline"
                                                >
                                                    {{ patient.first_name }} {{ patient.middle_name ? patient.middle_name + ' ' : '' }}{{ patient.last_name }}
                                                </Link>
                                                <span class="text-[10px] font-mono font-bold text-slate-600 bg-slate-100 px-1.5 py-0.5 rounded border border-slate-200">
                                                    {{ patient.patient_id }}
                                                </span>
                                            </div>
                                            <p class="text-xs text-slate-500 mt-0.5">
                                                {{ patient.gender }} • {{ patient.age ? `${patient.age} yrs` : 'N/A' }} • DOB: {{ formatDate(patient.date_of_birth) }}
                                            </p>
                                        </div>
                                    </div>

                                    <span
                                        :class="[
                                            patient.status === 'Active' ? 'bg-emerald-50 text-emerald-800 border-emerald-200' :
                                            patient.status === 'Inactive' ? 'bg-amber-50 text-amber-800 border-amber-200' : 'bg-slate-100 text-slate-700 border-slate-200',
                                            'inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold border shrink-0',
                                        ]"
                                    >
                                        {{ patient.status }}
                                    </span>
                                </div>

                                <!-- Contact & Alert Chips -->
                                <div class="flex flex-wrap items-center gap-2 pt-1 border-t border-slate-100 text-xs">
                                    <a
                                        :href="'tel:' + patient.contact_number"
                                        class="inline-flex items-center gap-1 font-mono font-medium text-slate-700 bg-slate-50 hover:bg-slate-100 px-2.5 py-1 rounded-lg border border-slate-200"
                                    >
                                        <i class="pi pi-phone text-[10px] text-teal-600"></i>
                                        {{ patient.contact_number }}
                                    </a>

                                    <span
                                        v-if="patient.medical_history?.blood_type"
                                        class="inline-flex items-center px-2 py-1 rounded-lg text-[10px] font-black bg-teal-50 text-teal-800 border border-teal-200"
                                    >
                                        Blood: {{ patient.medical_history.blood_type }}
                                    </span>

                                    <span
                                        v-if="patient.medical_history?.has_allergies || patient.medical_history?.allergies"
                                        class="inline-flex items-center gap-1 px-2 py-1 rounded-lg text-[10px] font-bold bg-rose-50 text-rose-800 border border-rose-200"
                                    >
                                        <i class="pi pi-exclamation-triangle text-[9px]"></i>
                                        Allergy
                                    </span>
                                </div>

                                <!-- Actions Strip -->
                                <div class="flex items-center justify-between pt-2 border-t border-slate-100">
                                    <div class="flex items-center gap-2">
                                        <button
                                            @click="openPreview(patient)"
                                            class="inline-flex items-center gap-1 px-2.5 py-1.5 rounded-lg text-xs font-semibold text-slate-700 bg-slate-100 hover:bg-slate-200 transition"
                                        >
                                            <i class="pi pi-search text-[10px]"></i>
                                            <span>Quick View</span>
                                        </button>
                                        <Link
                                            :href="route('patients.show', patient.id)"
                                            class="inline-flex items-center gap-1 px-2.5 py-1.5 rounded-lg text-xs font-semibold text-teal-700 bg-teal-50 hover:bg-teal-100 transition"
                                        >
                                            <i class="pi pi-eye text-[10px]"></i>
                                            <span>Full Dossier</span>
                                        </Link>
                                    </div>

                                    <div class="flex items-center gap-1">
                                        <Link
                                            v-if="['admin', 'secretary', 'dentist', 'staff'].includes(userRole)"
                                            :href="route('patients.edit', patient.id)"
                                            class="p-2 text-blue-600 hover:bg-blue-50 rounded-lg transition"
                                            title="Edit"
                                        >
                                            <i class="pi pi-pencil text-xs"></i>
                                        </Link>
                                        <button
                                            v-if="['admin', 'secretary', 'dentist'].includes(userRole) && patient.status !== 'Archived'"
                                            @click="confirmArchive(patient)"
                                            class="p-2 text-rose-600 hover:bg-rose-50 rounded-lg transition"
                                            title="Archive"
                                        >
                                            <i class="pi pi-trash text-xs"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </template>

                        <div v-else class="p-8 text-center text-slate-500">
                            <i class="pi pi-users text-3xl text-slate-300 mb-2"></i>
                            <p class="text-sm font-bold text-slate-900">No patients found</p>
                            <p class="text-xs text-slate-400 mt-0.5">Try adjusting your filters or search terms.</p>
                        </div>
                    </div>

                    <!-- Pagination -->
                    <div
                        v-if="patients.links && patients.links.length > 3"
                        class="border-t border-slate-100 px-4 py-3.5 sm:px-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 bg-slate-50/50"
                    >
                        <div class="text-xs text-slate-500">
                            Showing <span class="font-bold text-slate-900">{{ patients.from || 0 }}</span> to
                            <span class="font-bold text-slate-900">{{ patients.to || 0 }}</span> of
                            <span class="font-bold text-slate-900">{{ patients.total || 0 }}</span> patients
                        </div>

                        <div class="flex flex-wrap items-center gap-1">
                            <template v-for="(link, key) in patients.links" :key="key">
                                <Link
                                    v-if="link.url"
                                    :href="link.url"
                                    v-html="link.label"
                                    :class="[
                                        'inline-flex items-center justify-center min-w-[32px] h-8 px-2.5 text-xs font-bold rounded-lg border transition',
                                        link.active
                                            ? 'bg-teal-600 border-teal-600 text-white shadow-2xs'
                                            : 'bg-white border-slate-200 text-slate-700 hover:bg-slate-100 hover:border-slate-300',
                                    ]"
                                />
                                <span
                                    v-else
                                    v-html="link.label"
                                    class="inline-flex items-center justify-center min-w-[32px] h-8 px-2.5 text-xs font-medium rounded-lg text-slate-400 bg-slate-50 border border-slate-200 cursor-not-allowed"
                                ></span>
                            </template>
                        </div>
                    </div>
                </div>

            </div>
        </div>

        <!-- Quick Patient Preview Modal -->
        <div
            v-if="previewPatient"
            class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs transition-opacity"
            @click.self="closePreview"
        >
            <div class="relative w-full max-w-xl overflow-hidden rounded-3xl bg-white shadow-2xl border border-slate-200 animate-in fade-in zoom-in-95 duration-150">
                <!-- Modal Header Banner -->
                <div class="bg-gradient-to-r from-teal-700 to-cyan-700 p-6 text-white relative">
                    <button
                        @click="closePreview"
                        class="absolute top-4 right-4 h-8 w-8 rounded-full bg-white/10 hover:bg-white/20 flex items-center justify-center text-white transition"
                    >
                        <i class="pi pi-times text-xs"></i>
                    </button>

                    <div class="flex items-center gap-4">
                        <div class="flex h-14 w-14 shrink-0 items-center justify-center rounded-2xl bg-white/20 text-white font-black text-xl border border-white/20 shadow-sm backdrop-blur-xs">
                            {{ (previewPatient.first_name || 'P').charAt(0) }}{{ (previewPatient.last_name || 'U').charAt(0) }}
                        </div>
                        <div>
                            <span class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider bg-white/20 text-white border border-white/30 mb-1">
                                Patient ID: {{ previewPatient.patient_id }}
                            </span>
                            <h3 class="text-xl font-black tracking-tight leading-tight">
                                {{ previewPatient.first_name }} {{ previewPatient.middle_name ? previewPatient.middle_name + ' ' : '' }}{{ previewPatient.last_name }} {{ previewPatient.suffix || '' }}
                            </h3>
                            <p class="text-xs text-teal-100 mt-0.5">
                                {{ previewPatient.gender }} • {{ previewPatient.age ? `${previewPatient.age} years old` : 'Age N/A' }} • Status: {{ previewPatient.status }}
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Modal Content -->
                <div class="p-6 space-y-4 max-h-[65vh] overflow-y-auto">
                    <!-- Critical Health Precaution -->
                    <div
                        v-if="previewPatient.medical_history?.has_allergies || previewPatient.medical_history?.allergies"
                        class="p-3.5 rounded-xl bg-rose-50 border border-rose-200 text-rose-900 flex items-start gap-3"
                    >
                        <i class="pi pi-exclamation-triangle text-rose-600 text-lg mt-0.5"></i>
                        <div>
                            <span class="text-xs font-extrabold uppercase tracking-wide block">Allergy Precaution:</span>
                            <p class="text-xs font-semibold text-rose-800 mt-0.5">
                                {{ previewPatient.medical_history.allergies || 'Reported drug or food allergy' }}
                            </p>
                        </div>
                    </div>

                    <!-- Contact & Demographics Grid -->
                    <div class="grid grid-cols-2 gap-3 text-xs">
                        <div class="p-3 rounded-xl bg-slate-50 border border-slate-200/80">
                            <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400 block">Contact Phone</span>
                            <p class="font-bold text-slate-900 mt-1 font-mono">{{ previewPatient.contact_number || 'N/A' }}</p>
                        </div>
                        <div class="p-3 rounded-xl bg-slate-50 border border-slate-200/80">
                            <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400 block">Email Address</span>
                            <p class="font-bold text-slate-900 mt-1 truncate">{{ previewPatient.email || 'N/A' }}</p>
                        </div>
                        <div class="p-3 rounded-xl bg-slate-50 border border-slate-200/80">
                            <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400 block">Date of Birth</span>
                            <p class="font-bold text-slate-900 mt-1">{{ formatDate(previewPatient.date_of_birth) }}</p>
                        </div>
                        <div class="p-3 rounded-xl bg-slate-50 border border-slate-200/80">
                            <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400 block">Blood Type</span>
                            <p class="font-bold text-teal-800 mt-1">
                                {{ previewPatient.medical_history?.blood_type ? 'Type ' + previewPatient.medical_history.blood_type : 'Not Recorded' }}
                            </p>
                        </div>
                    </div>

                    <!-- Address & Guardian -->
                    <div v-if="previewPatient.address || previewPatient.guardian" class="space-y-2 text-xs">
                        <div v-if="previewPatient.guardian" class="p-3 rounded-xl bg-amber-50/70 border border-amber-200 text-amber-900">
                            <span class="font-bold">Parent / Guardian:</span> {{ previewPatient.guardian }}
                        </div>
                        <div v-if="previewPatient.address" class="p-3 rounded-xl bg-slate-50 border border-slate-200 text-slate-700">
                            <span class="font-bold text-slate-900">Registered Address:</span> {{ previewPatient.address }}
                        </div>
                    </div>

                    <!-- Clinical Status & Notes -->
                    <div class="pt-2 border-t border-slate-100 flex items-center justify-between text-xs text-slate-500">
                        <span>Profile Created: {{ formatDate(previewPatient.created_at) }}</span>
                        <span class="font-bold text-teal-700">Vitality Smile Health Dossier</span>
                    </div>
                </div>

                <!-- Modal Actions Footer -->
                <div class="bg-slate-50 px-6 py-4 border-t border-slate-100 flex items-center justify-between gap-3">
                    <button
                        @click="closePreview"
                        class="px-4 py-2 rounded-xl text-xs font-bold text-slate-600 hover:text-slate-900 bg-white border border-slate-200 hover:bg-slate-100 transition"
                    >
                        Close Preview
                    </button>

                    <div class="flex items-center gap-2">
                        <Link
                            :href="route('patients.show', previewPatient.id)"
                            class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl text-xs font-bold text-white bg-teal-600 hover:bg-teal-700 shadow-sm transition"
                        >
                            <i class="pi pi-folder-open text-xs"></i>
                            <span>Open Full Dossier</span>
                        </Link>
                    </div>
                </div>
            </div>
        </div>

        <!-- Archive Confirmation Dialog -->
        <div
            v-if="patientToArchive"
            class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs transition-opacity"
            @click.self="cancelArchive"
        >
            <div class="relative w-full max-w-md overflow-hidden rounded-3xl bg-white shadow-2xl border border-slate-200 p-6 text-center animate-in fade-in zoom-in-95 duration-150">
                <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-rose-50 text-rose-600 border border-rose-200 mb-4">
                    <i class="pi pi-exclamation-triangle text-2xl"></i>
                </div>

                <h3 class="text-lg font-black text-slate-900">
                    Archive Patient Record?
                </h3>
                <p class="text-xs text-slate-500 mt-2 leading-relaxed">
                    Are you sure you want to archive
                    <strong class="text-slate-900">{{ patientToArchive.first_name }} {{ patientToArchive.last_name }}</strong>
                    ({{ patientToArchive.patient_id }})? The patient will be marked as Archived but historical treatments and billing records will remain intact.
                </p>

                <div class="mt-6 flex items-center justify-center gap-3">
                    <button
                        @click="cancelArchive"
                        :disabled="isArchiving"
                        class="w-full py-2.5 px-4 rounded-xl text-xs font-bold text-slate-700 bg-slate-100 hover:bg-slate-200 transition"
                    >
                        Cancel
                    </button>
                    <button
                        @click="executeArchive"
                        :disabled="isArchiving"
                        class="w-full py-2.5 px-4 rounded-xl text-xs font-bold text-white bg-rose-600 hover:bg-rose-700 shadow-sm transition disabled:opacity-50"
                    >
                        {{ isArchiving ? 'Archiving...' : 'Yes, Archive' }}
                    </button>
                </div>
            </div>
        </div>

    </AuthenticatedLayout>
</template>
