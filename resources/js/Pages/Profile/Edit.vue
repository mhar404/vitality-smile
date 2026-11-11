<script setup>
import { computed, ref } from "vue";
import { Head, Link, usePage } from "@inertiajs/vue3";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import UpdatePatientProfileInformationForm from "./Partials/UpdatePatientProfileInformationForm.vue";
import PatientMedicalSnapshot from "./Partials/PatientMedicalSnapshot.vue";
import UpdateProfileInformationForm from "./Partials/UpdateProfileInformationForm.vue";
import UpdatePasswordForm from "./Partials/UpdatePasswordForm.vue";
import { useTheme } from "@/Composables/useTheme";

const { isDark, setTheme } = useTheme();

const props = defineProps({
    mustVerifyEmail: {
        type: Boolean,
        default: false,
    },
    status: {
        type: String,
        default: "",
    },
    patient: {
        type: Object,
        default: null,
    },
    medicalHistory: {
        type: Object,
        default: null,
    },
    stats: {
        type: Object,
        default: () => ({
            appointments_count: 0,
            treatments_count: 0,
            critical_alerts_count: 0,
            outstanding_balance: 0,
        }),
    },
    nextAppointment: {
        type: Object,
        default: null,
    },
});

const user = usePage().props.auth.user;
const isPatient = computed(() => user.role === "patient" && !!props.patient);

// Active Tab navigation
const activeTab = ref("personal"); // 'personal' | 'medical' | 'security'

// Copy Patient ID to clipboard
const copiedPatientId = ref(false);
const copyPatientId = () => {
    if (!props.patient?.patient_id) return;
    navigator.clipboard.writeText(props.patient.patient_id);
    copiedPatientId.value = true;
    setTimeout(() => {
        copiedPatientId.value = false;
    }, 2000);
};

// Format currency
const formatCurrency = (amount) => {
    return new Intl.NumberFormat("en-PH", {
        style: "currency",
        currency: "PHP",
        minimumFractionDigits: 0,
    }).format(amount || 0);
};

// Member since calculation
const memberSince = computed(() => {
    if (!user.created_at) return "Active Member";
    const d = new Date(user.created_at);
    return `Patient since ${d.toLocaleDateString("en-US", { month: "short", year: "numeric" })}`;
});
</script>

<template>
    <Head :title="isPatient ? 'Patient Profile - Vitality Smile' : 'Profile Settings - Vitality Smile'" />

    <AuthenticatedLayout>
        <!-- Page Header -->
        <template #header>
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <div class="flex items-center gap-2">
                        <span class="inline-flex items-center gap-1 rounded-md bg-teal-100 dark:bg-teal-900/60 px-2 py-0.5 text-xs font-bold text-teal-800 dark:text-teal-300">
                            <i class="pi pi-shield text-[10px]"></i>
                            {{ isPatient ? 'Clinical Patient Portal' : 'Clinic Staff Profile' }}
                        </span>
                    </div>
                    <h2 class="text-xl font-extrabold text-gray-900 dark:text-white mt-1">
                        {{ isPatient ? 'My Dental Patient Profile' : 'Staff Profile & Security' }}
                    </h2>
                    <p class="text-xs text-gray-500 dark:text-slate-400 mt-0.5">
                        {{ isPatient ? 'Manage your personal records, clinical medical overview, and account credentials.' : 'Manage your staff identity, clinic credentials, and access security.' }}
                    </p>
                </div>

                <div class="flex items-center gap-2">
                    <Link
                        :href="route('dashboard')"
                        class="inline-flex items-center gap-1.5 rounded-xl border border-gray-200 dark:border-slate-700 bg-white dark:bg-slate-800 px-3.5 py-2 text-xs font-semibold text-gray-700 dark:text-slate-200 shadow-xs hover:bg-gray-50 dark:hover:bg-slate-700 transition"
                    >
                        <i class="pi pi-arrow-left text-xs"></i>
                        Dashboard
                    </Link>

                    <Link
                        v-if="isPatient"
                        :href="route('appointments.create')"
                        class="inline-flex items-center gap-1.5 rounded-xl bg-teal-700 hover:bg-teal-800 dark:bg-teal-600 dark:hover:bg-teal-500 px-4 py-2 text-xs font-bold text-white shadow-sm transition"
                    >
                        <i class="pi pi-calendar-plus text-xs"></i>
                        Book Visit
                    </Link>
                </div>
            </div>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 space-y-6">

                <!-- 1. PATIENT HERO IDENTITY BANNER (For Patients) -->
                <div
                    v-if="isPatient"
                    class="relative overflow-hidden rounded-3xl bg-gradient-to-br from-teal-800 via-teal-700 to-cyan-900 p-6 sm:p-8 text-white shadow-lg"
                >
                    <!-- Background ambient dental ornament -->
                    <div class="pointer-events-none absolute -right-10 -bottom-10 opacity-10">
                        <svg class="h-64 w-64 text-white" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M12 2C7.5 2 4 4.5 4 8c0 2.5 1.5 5 2.5 8 .8 2.4 1.5 6 3.5 6 1.5 0 2-3 2-4.5 0 1.5.5 4.5 2 4.5 2 0 2.7-3.6 3.5-6 1-3 2.5-5.5 2.5-8 0-3.5-3.5-6-8-6z" />
                        </svg>
                    </div>

                    <div class="relative z-10 flex flex-col md:flex-row md:items-center md:justify-between gap-6">
                        <!-- Left Avatar & Patient Identification -->
                        <div class="flex items-start sm:items-center gap-4 sm:gap-5">
                            <div class="relative shrink-0">
                                <div class="flex h-20 w-20 items-center justify-center rounded-2xl bg-white/15 text-3xl font-extrabold text-white shadow-inner ring-4 ring-white/20 backdrop-blur-md">
                                    {{ (patient.first_name || user.name || 'P').charAt(0).toUpperCase() }}
                                </div>
                                <span
                                    class="absolute -bottom-1 -right-1 flex h-6 w-6 items-center justify-center rounded-full bg-emerald-500 text-white text-[10px] ring-2 ring-teal-900 shadow-xs"
                                    title="Active Patient"
                                >
                                    <i class="pi pi-check text-[10px]"></i>
                                </span>
                            </div>

                            <div class="space-y-1">
                                <div class="flex flex-wrap items-center gap-2">
                                    <h1 class="text-2xl font-black tracking-tight text-white sm:text-3xl">
                                        {{ patient.full_name || user.name }}
                                    </h1>
                                    <span class="rounded-full bg-teal-500/30 px-2.5 py-0.5 text-[11px] font-semibold text-teal-100 border border-teal-300/30">
                                        {{ patient.gender || 'Patient' }}
                                        <span v-if="patient.age">&bull; {{ patient.age }} yrs</span>
                                    </span>
                                </div>

                                <div class="flex flex-wrap items-center gap-3 text-xs text-teal-100/90">
                                    <!-- Patient ID Pill with Copy button -->
                                    <button
                                        type="button"
                                        @click="copyPatientId"
                                        class="inline-flex items-center gap-1.5 rounded-lg bg-black/20 hover:bg-black/30 px-2.5 py-1 text-xs font-mono font-bold text-white transition border border-white/10"
                                        title="Click to copy ID"
                                    >
                                        <i class="pi pi-id-card text-teal-300 text-xs"></i>
                                        <span>{{ patient.patient_id }}</span>
                                        <i :class="['pi text-[10px]', copiedPatientId ? 'pi-check text-emerald-300 font-bold' : 'pi-copy text-teal-200']"></i>
                                    </button>

                                    <!-- Email -->
                                    <span class="inline-flex items-center gap-1">
                                        <i class="pi pi-envelope text-[11px] text-teal-300"></i>
                                        {{ user.email }}
                                    </span>

                                    <!-- Member Since -->
                                    <span class="hidden sm:inline-flex items-center gap-1 text-teal-200/80">
                                        <i class="pi pi-calendar text-[11px]"></i>
                                        {{ memberSince }}
                                    </span>
                                </div>
                            </div>
                        </div>

                        <!-- Right Quick Action Shortcuts -->
                        <div class="flex flex-wrap items-center gap-2 shrink-0">
                            <Link
                                :href="route('medical-histories.my')"
                                class="inline-flex items-center gap-1.5 rounded-xl bg-white/15 hover:bg-white/25 px-4 py-2.5 text-xs font-bold text-white transition backdrop-blur-sm border border-white/20"
                            >
                                <i class="pi pi-heart text-rose-300 text-xs"></i>
                                Medical History
                            </Link>

                            <Link
                                :href="route('treatments.index')"
                                class="inline-flex items-center gap-1.5 rounded-xl bg-white/15 hover:bg-white/25 px-4 py-2.5 text-xs font-bold text-white transition backdrop-blur-sm border border-white/20"
                            >
                                <i class="pi pi-file text-cyan-200 text-xs"></i>
                                Dental Records
                            </Link>

                            <Link
                                :href="route('billing.index')"
                                class="inline-flex items-center gap-1.5 rounded-xl bg-white text-teal-900 hover:bg-teal-50 px-4 py-2.5 text-xs font-bold transition shadow-sm"
                            >
                                <i class="pi pi-wallet text-teal-700 text-xs"></i>
                                My Billing
                            </Link>
                        </div>
                    </div>
                </div>

                <!-- 2. CLINICAL METRICS QUICK STRIP (For Patients) -->
                <div v-if="isPatient" class="grid grid-cols-2 gap-3 sm:grid-cols-4">
                    <!-- Total Appointments -->
                    <div class="rounded-2xl border border-gray-200 bg-white p-4 shadow-xs transition hover:shadow-sm">
                        <div class="flex items-center justify-between text-gray-500">
                            <span class="text-xs font-semibold uppercase tracking-wider">Appointments</span>
                            <span class="flex h-8 w-8 items-center justify-center rounded-xl bg-teal-50 text-teal-700">
                                <i class="pi pi-calendar text-xs"></i>
                            </span>
                        </div>
                        <div class="mt-2 text-2xl font-black text-gray-900">
                            {{ stats.appointments_count }}
                        </div>
                        <div class="mt-0.5 text-[11px] text-gray-400">
                            Total scheduled visits
                        </div>
                    </div>

                    <!-- Completed Procedures -->
                    <div class="rounded-2xl border border-gray-200 bg-white p-4 shadow-xs transition hover:shadow-sm">
                        <div class="flex items-center justify-between text-gray-500">
                            <span class="text-xs font-semibold uppercase tracking-wider">Treatments</span>
                            <span class="flex h-8 w-8 items-center justify-center rounded-xl bg-cyan-50 text-cyan-700">
                                <i class="pi pi-sparkles text-xs"></i>
                            </span>
                        </div>
                        <div class="mt-2 text-2xl font-black text-gray-900">
                            {{ stats.treatments_count }}
                        </div>
                        <div class="mt-0.5 text-[11px] text-gray-400">
                            Completed procedures on file
                        </div>
                    </div>

                    <!-- Medical Alerts Status -->
                    <div class="rounded-2xl border border-gray-200 bg-white p-4 shadow-xs transition hover:shadow-sm">
                        <div class="flex items-center justify-between text-gray-500">
                            <span class="text-xs font-semibold uppercase tracking-wider">Medical Alerts</span>
                            <span
                                :class="[
                                    'flex h-8 w-8 items-center justify-center rounded-xl',
                                    stats.critical_alerts_count > 0 ? 'bg-red-50 text-red-600' : 'bg-emerald-50 text-emerald-600'
                                ]"
                            >
                                <i :class="['pi text-xs', stats.critical_alerts_count > 0 ? 'pi-exclamation-triangle' : 'pi-shield']"></i>
                            </span>
                        </div>
                        <div class="mt-2 text-2xl font-black text-gray-900">
                            {{ stats.critical_alerts_count }}
                        </div>
                        <div class="mt-0.5 text-[11px] font-medium" :class="stats.critical_alerts_count > 0 ? 'text-red-600' : 'text-emerald-600'">
                            {{ stats.critical_alerts_count > 0 ? 'Precaution required' : 'No clinical alerts' }}
                        </div>
                    </div>

                    <!-- Outstanding Balance -->
                    <div class="rounded-2xl border border-gray-200 bg-white p-4 shadow-xs transition hover:shadow-sm">
                        <div class="flex items-center justify-between text-gray-500">
                            <span class="text-xs font-semibold uppercase tracking-wider">Account Balance</span>
                            <span class="flex h-8 w-8 items-center justify-center rounded-xl bg-amber-50 text-amber-700">
                                <i class="pi pi-wallet text-xs"></i>
                            </span>
                        </div>
                        <div class="mt-2 text-xl font-black text-gray-900">
                            {{ formatCurrency(stats.outstanding_balance) }}
                        </div>
                        <div class="mt-0.5 text-[11px] text-gray-400">
                            {{ stats.outstanding_balance > 0 ? 'Unpaid invoices pending' : 'Zero balance / Paid up' }}
                        </div>
                    </div>
                </div>

                <!-- 3. TAB NAVIGATION (For Patients) -->
                <div v-if="isPatient" class="border-b border-gray-200">
                    <nav class="-mb-px flex gap-2 sm:gap-6" aria-label="Tabs">
                        <button
                            type="button"
                            @click="activeTab = 'personal'"
                            :class="[
                                'inline-flex items-center gap-2 border-b-2 py-3 px-3 text-xs sm:text-sm font-bold transition',
                                activeTab === 'personal'
                                    ? 'border-teal-600 text-teal-800'
                                    : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700'
                            ]"
                        >
                            <i class="pi pi-user text-xs"></i>
                            <span>Personal & Clinical Info</span>
                        </button>

                        <button
                            type="button"
                            @click="activeTab = 'medical'"
                            :class="[
                                'inline-flex items-center gap-2 border-b-2 py-3 px-3 text-xs sm:text-sm font-bold transition',
                                activeTab === 'medical'
                                    ? 'border-teal-600 text-teal-800'
                                    : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700'
                            ]"
                        >
                            <i class="pi pi-heart text-xs"></i>
                            <span>Health & Emergency Snapshot</span>
                            <span
                                v-if="stats.critical_alerts_count > 0"
                                class="rounded-full bg-red-100 px-1.5 py-0.2 text-[10px] font-bold text-red-700"
                            >
                                {{ stats.critical_alerts_count }}
                            </span>
                        </button>

                        <button
                            type="button"
                            @click="activeTab = 'security'"
                            :class="[
                                'inline-flex items-center gap-2 border-b-2 py-3 px-3 text-xs sm:text-sm font-bold transition',
                                activeTab === 'security'
                                    ? 'border-teal-600 text-teal-800'
                                    : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700'
                            ]"
                        >
                            <i class="pi pi-lock text-xs"></i>
                            <span>Account Security & Login</span>
                        </button>
                    </nav>
                </div>

                <!-- 4. PATIENT TAB CONTENTS -->
                <div v-if="isPatient" class="space-y-6">
                    <!-- Tab 1: Personal & Clinical Info -->
                    <div v-show="activeTab === 'personal'" class="rounded-3xl border border-gray-200 bg-white p-6 sm:p-8 shadow-sm">
                        <UpdatePatientProfileInformationForm
                            :patient="patient"
                            :must-verify-email="mustVerifyEmail"
                            :status="status"
                        />
                    </div>

                    <!-- Tab 2: Health & Emergency Snapshot -->
                    <div v-show="activeTab === 'medical'">
                        <PatientMedicalSnapshot
                            :patient="patient"
                            :medical-history="medicalHistory"
                        />
                    </div>

                    <!-- Tab 3: Account & Security -->
                    <div v-show="activeTab === 'security'" class="space-y-6">
                        <!-- Display Appearance / Theme Settings -->
                        <div class="rounded-3xl border border-gray-200 bg-white p-6 sm:p-8 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                                <div>
                                    <h3 class="text-base font-bold text-gray-900 dark:text-slate-100 flex items-center gap-2">
                                        <i :class="isDark ? 'pi pi-moon text-teal-400' : 'pi pi-sun text-amber-500'"></i>
                                        <span>Display Theme & Appearance</span>
                                    </h3>
                                    <p class="mt-1 text-xs text-gray-500 dark:text-slate-400 max-w-xl">
                                        Customize your viewing preference between Light Mode and Dark Mode. Your theme choice is preserved across visits.
                                    </p>
                                </div>
                                <div class="flex items-center gap-2 shrink-0">
                                    <button
                                        type="button"
                                        @click="setTheme('light')"
                                        :class="[
                                            'inline-flex items-center gap-2 rounded-xl px-4 py-2.5 text-xs font-bold transition border',
                                            !isDark
                                                ? 'border-teal-600 bg-teal-50 text-teal-800 shadow-xs dark:bg-teal-950/60 dark:text-teal-400'
                                                : 'border-gray-200 bg-white text-gray-700 hover:bg-gray-50 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-300 dark:hover:bg-slate-700'
                                        ]"
                                    >
                                        <i class="pi pi-sun text-amber-500"></i>
                                        <span>Light</span>
                                    </button>
                                    <button
                                        type="button"
                                        @click="setTheme('dark')"
                                        :class="[
                                            'inline-flex items-center gap-2 rounded-xl px-4 py-2.5 text-xs font-bold transition border',
                                            isDark
                                                ? 'border-teal-500 bg-slate-800 text-teal-400 shadow-xs'
                                                : 'border-gray-200 bg-white text-gray-700 hover:bg-gray-50 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-300 dark:hover:bg-slate-700'
                                        ]"
                                    >
                                        <i class="pi pi-moon text-slate-300"></i>
                                        <span>Dark</span>
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Password Update -->
                        <div class="rounded-3xl border border-gray-200 bg-white p-6 sm:p-8 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                            <UpdatePasswordForm />
                        </div>
                    </div>
                </div>

                <!-- 5. STAFF / PROVIDER PROFILE VIEW (If Logged-in User is Staff, Dentist, or Admin) -->
                <div v-else class="space-y-6">
                    <!-- Staff Role Header Card -->
                    <div class="rounded-3xl bg-gradient-to-r from-teal-800 to-teal-900 p-6 sm:p-8 text-white shadow-md flex items-center justify-between">
                        <div class="flex items-center gap-4">
                            <div class="flex h-16 w-16 items-center justify-center rounded-2xl bg-white/20 text-2xl font-bold text-white shadow-inner">
                                {{ user.name.charAt(0).toUpperCase() }}
                            </div>
                            <div>
                                <div class="flex items-center gap-2">
                                    <h1 class="text-2xl font-extrabold text-white">{{ user.name }}</h1>
                                    <span class="rounded-md bg-teal-500/40 px-2 py-0.5 text-xs font-bold uppercase tracking-wider text-teal-100 border border-teal-400/30">
                                        {{ user.role }}
                                    </span>
                                </div>
                                <p class="text-xs text-teal-200 mt-0.5">{{ user.email }} &bull; Clinic Team Member</p>
                            </div>
                        </div>
                    </div>

                    <!-- Display Appearance / Theme Settings for Staff -->
                    <div class="rounded-3xl border border-gray-200 bg-white p-6 sm:p-8 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                            <div>
                                <h3 class="text-base font-bold text-gray-900 dark:text-slate-100 flex items-center gap-2">
                                    <i :class="isDark ? 'pi pi-moon text-teal-400' : 'pi pi-sun text-amber-500'"></i>
                                    <span>Display Theme & Appearance</span>
                                </h3>
                                <p class="mt-1 text-xs text-gray-500 dark:text-slate-400 max-w-xl">
                                    Toggle between Light Mode and Dark Mode for clinical workstation display.
                                </p>
                            </div>
                            <div class="flex items-center gap-2 shrink-0">
                                <button
                                    type="button"
                                    @click="setTheme('light')"
                                    :class="[
                                        'inline-flex items-center gap-2 rounded-xl px-4 py-2.5 text-xs font-bold transition border',
                                        !isDark
                                            ? 'border-teal-600 bg-teal-50 text-teal-800 shadow-xs dark:bg-teal-950/60 dark:text-teal-400'
                                            : 'border-gray-200 bg-white text-gray-700 hover:bg-gray-50 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-300 dark:hover:bg-slate-700'
                                    ]"
                                >
                                    <i class="pi pi-sun text-amber-500"></i>
                                    <span>Light</span>
                                </button>
                                <button
                                    type="button"
                                    @click="setTheme('dark')"
                                    :class="[
                                        'inline-flex items-center gap-2 rounded-xl px-4 py-2.5 text-xs font-bold transition border',
                                        isDark
                                            ? 'border-teal-500 bg-slate-800 text-teal-400 shadow-xs'
                                            : 'border-gray-200 bg-white text-gray-700 hover:bg-gray-50 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-300 dark:hover:bg-slate-700'
                                    ]"
                                >
                                    <i class="pi pi-moon text-slate-300"></i>
                                    <span>Dark</span>
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="rounded-3xl border border-gray-200 bg-white p-6 sm:p-8 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                        <UpdateProfileInformationForm
                            :must-verify-email="mustVerifyEmail"
                            :status="status"
                        />
                    </div>

                    <div class="rounded-3xl border border-gray-200 bg-white p-6 sm:p-8 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                        <UpdatePasswordForm />
                    </div>
                </div>

            </div>
        </div>
    </AuthenticatedLayout>
</template>
