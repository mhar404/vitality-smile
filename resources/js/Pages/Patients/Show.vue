<script setup>
import { computed, ref } from "vue";
import { Head, Link, usePage } from "@inertiajs/vue3";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import ClientPagination from "@/Components/ClientPagination.vue";

const page = usePage();

const props = defineProps({
    patient: {
        type: Object,
        required: true,
    },
});

const userRole = computed(() => page.props.auth?.user?.role || "staff");

const perPage = 5;
const aptPage = ref(1);
const trtPage = ref(1);
const invPage = ref(1);

const slicePage = (list, pageNum) => (list || []).slice((pageNum - 1) * perPage, pageNum * perPage);

const pagedAppointments = computed(() => slicePage(props.patient?.appointments, aptPage.value));
const pagedTreatments = computed(() => slicePage(props.patient?.treatments, trtPage.value));
const pagedInvoices = computed(() => slicePage(props.patient?.invoices, invPage.value));

const totalBilled = computed(() => {
    if (!props.patient?.invoices) return 0;
    return props.patient.invoices
        .filter((i) => i.status !== "cancelled")
        .reduce((sum, i) => sum + (parseFloat(i.net_amount) || 0), 0);
});

const totalPaid = computed(() => {
    if (!props.patient?.invoices) return 0;
    return props.patient.invoices
        .filter((i) => i.status !== "cancelled")
        .reduce((sum, i) => sum + (parseFloat(i.paid_amount) || 0), 0);
});

const totalBalance = computed(() => {
    if (!props.patient?.invoices) return 0;
    return props.patient.invoices
        .filter((i) => ["unpaid", "partially_paid"].includes(i.status))
        .reduce((sum, i) => sum + (parseFloat(i.balance) || 0), 0);
});

const calculateAge = (dob) => {
    if (!dob) return "N/A";
    try {
        const birthday = new Date(dob);
        const ageDifMs = Date.now() - birthday.getTime();
        const ageDate = new Date(ageDifMs);
        return Math.abs(ageDate.getUTCFullYear() - 1970);
    } catch (e) {
        return "N/A";
    }
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

const formatCurrency = (val) => {
    const num = parseFloat(val) || 0;
    return "₱" + num.toLocaleString("en-PH", { minimumFractionDigits: 2, maximumFractionDigits: 2 });
};

// Copy feedback
const copiedPhone = ref(false);
const copyPhone = () => {
    if (!props.patient.contact_number) return;
    navigator.clipboard.writeText(props.patient.contact_number);
    copiedPhone.value = true;
    setTimeout(() => {
        copiedPhone.value = false;
    }, 2000);
};
</script>

<template>
    <Head :title="`${patient.first_name} ${patient.last_name} - Patient Dossier`" />

    <AuthenticatedLayout>
        <!-- Header Banner -->
        <template #header>
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <!-- Breadcrumbs Strip -->
                    <div class="flex items-center gap-2">
                        <Link
                            :href="route('patients.index')"
                            class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-white text-slate-600 hover:text-teal-700 border border-slate-200 shadow-2xs transition"
                        >
                            <i class="pi pi-arrow-left text-[10px]"></i>
                            <span>Patient Directory</span>
                        </Link>
                        <span class="text-xs text-slate-400 font-medium">• Dossier</span>
                    </div>

                    <div class="mt-2 flex flex-wrap items-center gap-3">
                        <h1 class="text-2xl font-black text-slate-900 tracking-tight sm:text-3xl">
                            {{ patient.first_name }} {{ patient.middle_name ? patient.middle_name + ' ' : '' }}{{ patient.last_name }} {{ patient.suffix || '' }}
                        </h1>
                        <span class="text-xs font-mono font-bold text-slate-600 bg-slate-100 px-2.5 py-1 rounded-lg border border-slate-200">
                            {{ patient.patient_id }}
                        </span>
                        <span
                            :class="[
                                patient.status === 'Active' ? 'bg-emerald-50 text-emerald-800 border-emerald-200' :
                                patient.status === 'Inactive' ? 'bg-amber-50 text-amber-800 border-amber-200' : 'bg-slate-100 text-slate-700 border-slate-200',
                                'inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-bold border',
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
                    </div>
                </div>

                <!-- Action Toolbar -->
                <div class="flex flex-wrap items-center gap-2">
                    <Link
                        v-if="['admin', 'secretary', 'dentist', 'staff'].includes(userRole)"
                        :href="route('patients.edit', patient.id)"
                        class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl text-xs font-bold text-slate-700 bg-white hover:bg-slate-50 border border-slate-200 shadow-2xs transition hover:border-slate-300"
                    >
                        <i class="pi pi-pencil text-xs text-teal-600"></i>
                        <span>Edit Profile</span>
                    </Link>

                    <Link
                        v-if="['admin', 'secretary', 'dentist'].includes(userRole)"
                        :href="route('appointments.create', { patient_id: patient.id })"
                        class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl text-xs font-bold text-white bg-gradient-to-r from-teal-600 to-cyan-600 hover:from-teal-700 hover:to-cyan-700 shadow-2xs transition hover:shadow-sm"
                    >
                        <i class="pi pi-calendar-plus text-xs"></i>
                        <span>Book Visit</span>
                    </Link>

                    <Link
                        v-if="['admin', 'dentist'].includes(userRole)"
                        :href="route('treatments.create', { patient_id: patient.id })"
                        class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl text-xs font-bold text-white bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-700 hover:to-teal-700 shadow-2xs transition hover:shadow-sm"
                    >
                        <i class="pi pi-heart text-xs"></i>
                        <span>Record Treatment</span>
                    </Link>
                </div>
            </div>
        </template>

        <!-- Main Body -->
        <div class="py-6 sm:py-8 space-y-6">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 space-y-6">

                <!-- Patient Master Overview Card -->
                <div class="relative overflow-hidden rounded-3xl border border-slate-200/90 bg-white p-6 sm:p-8 shadow-2xs">
                    <!-- Top Gradient Stripe -->
                    <div class="absolute top-0 inset-x-0 h-1.5 bg-gradient-to-r from-teal-500 via-cyan-500 to-emerald-500"></div>

                    <div class="flex flex-col md:flex-row md:items-center justify-between gap-6">
                        <div class="flex flex-col sm:flex-row sm:items-center gap-5">
                            <!-- Avatar -->
                            <div class="flex h-20 w-20 shrink-0 items-center justify-center rounded-2xl bg-gradient-to-br from-teal-500 to-emerald-600 text-white font-black text-2xl shadow-md border-2 border-white ring-4 ring-teal-50">
                                {{ (patient.first_name || 'P').charAt(0) }}{{ (patient.last_name || 'U').charAt(0) }}
                            </div>

                            <!-- Name & Key Indicators -->
                            <div class="space-y-1.5">
                                <div class="flex flex-wrap items-center gap-2">
                                    <h2 class="text-xl sm:text-2xl font-black text-slate-900 tracking-tight">
                                        {{ patient.first_name }} {{ patient.middle_name ? patient.middle_name + ' ' : '' }}{{ patient.last_name }} {{ patient.suffix || '' }}
                                    </h2>
                                </div>

                                <div class="flex flex-wrap items-center gap-2.5 text-xs text-slate-500">
                                    <span class="inline-flex items-center gap-1 font-semibold text-slate-700">
                                        <i class="pi pi-user text-[11px] text-teal-600"></i>
                                        {{ patient.gender }}
                                    </span>
                                    <span>•</span>
                                    <span class="font-semibold text-slate-700">
                                        {{ patient.age || calculateAge(patient.date_of_birth) }} yrs old
                                    </span>
                                    <span>•</span>
                                    <span>DOB: {{ formatDate(patient.date_of_birth) }}</span>
                                    <span v-if="patient.guardian" class="inline-flex items-center gap-1 text-amber-800 bg-amber-50 px-2 py-0.5 rounded border border-amber-200 font-medium">
                                        <i class="pi pi-shield text-[10px]"></i>
                                        Guardian: {{ patient.guardian }}
                                    </span>
                                </div>

                                <!-- Contact Pills -->
                                <div class="flex flex-wrap items-center gap-2 pt-1">
                                    <button
                                        @click="copyPhone"
                                        class="inline-flex items-center gap-1.5 px-3 py-1 rounded-lg text-xs font-mono font-semibold text-slate-700 bg-slate-50 hover:bg-slate-100 border border-slate-200 transition"
                                        title="Click to copy phone number"
                                    >
                                        <i :class="copiedPhone ? 'pi pi-check text-emerald-600' : 'pi pi-phone text-teal-600'" class="text-[10px]"></i>
                                        <span>{{ patient.contact_number || 'No Phone' }}</span>
                                        <span v-if="copiedPhone" class="text-[10px] text-emerald-700 font-bold ml-1">Copied!</span>
                                    </button>

                                    <a
                                        v-if="patient.email"
                                        :href="'mailto:' + patient.email"
                                        class="inline-flex items-center gap-1.5 px-3 py-1 rounded-lg text-xs font-semibold text-slate-700 bg-slate-50 hover:bg-slate-100 border border-slate-200 transition"
                                    >
                                        <i class="pi pi-envelope text-[10px] text-teal-600"></i>
                                        <span>{{ patient.email }}</span>
                                    </a>

                                    <span
                                        v-if="patient.medical_history?.blood_type"
                                        class="inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-black bg-teal-50 text-teal-800 border border-teal-200"
                                    >
                                        Blood Group: {{ patient.medical_history.blood_type }}
                                    </span>
                                </div>
                            </div>
                        </div>

                        <!-- Mini Fast Action Panel -->
                        <div class="flex flex-wrap md:flex-col items-center md:items-end justify-between md:justify-center gap-2 shrink-0 border-t md:border-t-0 md:border-l border-slate-100 pt-4 md:pt-0 md:pl-6 text-xs text-slate-500">
                            <div class="text-left md:text-right">
                                <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400 block">Record Started</span>
                                <span class="font-bold text-slate-900">{{ formatDate(patient.created_at) }}</span>
                            </div>
                            <div class="text-left md:text-right">
                                <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400 block">Total Invoiced</span>
                                <span class="font-extrabold text-teal-700">{{ formatCurrency(totalBilled) }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Critical Medical Alert Banner (if applicable) -->
                    <div
                        v-if="patient.medical_history?.has_allergies || patient.medical_history?.allergies || (patient.medical_history?.critical_alerts && patient.medical_history.critical_alerts.length > 0)"
                        class="mt-6 pt-5 border-t border-slate-100"
                    >
                        <div class="rounded-2xl border border-rose-300 bg-gradient-to-r from-rose-50 via-red-50 to-amber-50 p-4 flex items-start gap-3.5 shadow-2xs">
                            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-rose-600 text-white shadow-xs">
                                <i class="pi pi-exclamation-triangle text-base"></i>
                            </div>
                            <div class="min-w-0 flex-1">
                                <div class="flex items-center gap-2">
                                    <h3 class="text-xs font-black uppercase tracking-wider text-rose-950">
                                        Clinical Medical Alerts & Allergies Precaution
                                    </h3>
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-black uppercase bg-rose-200 text-rose-900">
                                        Doctor Warning
                                    </span>
                                </div>
                                <p class="text-xs font-bold text-rose-900 mt-0.5">
                                    {{ patient.medical_history.allergies || 'Patient has documented allergies or medical contraindications.' }}
                                </p>
                                <div v-if="patient.medical_history?.critical_alerts" class="mt-2 flex flex-wrap items-center gap-1.5">
                                    <span
                                        v-for="(alert, idx) in patient.medical_history.critical_alerts"
                                        :key="idx"
                                        class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-white text-rose-800 border border-rose-300 shadow-2xs"
                                    >
                                        <i class="pi pi-exclamation-circle text-[9px] text-rose-600"></i>
                                        {{ alert.label }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Two-Column Operational Detail Grid -->
                <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">

                    <!-- Left Column: Personal Info & Medical History (4 Cols) -->
                    <div class="lg:col-span-4 space-y-6">

                        <!-- Personal & Demographic Profile -->
                        <div class="rounded-2xl border border-slate-200/90 bg-white p-5 shadow-2xs space-y-4">
                            <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                                <h3 class="text-sm font-black uppercase tracking-wider text-slate-900 flex items-center gap-2">
                                    <i class="pi pi-id-card text-teal-600 text-base"></i>
                                    <span>Personal Information</span>
                                </h3>
                                <Link
                                    v-if="['admin', 'secretary', 'dentist', 'staff'].includes(userRole)"
                                    :href="route('patients.edit', patient.id)"
                                    class="text-xs font-bold text-teal-700 hover:text-teal-800"
                                >
                                    Edit
                                </Link>
                            </div>

                            <dl class="space-y-3 text-xs">
                                <div class="flex justify-between py-1 border-b border-slate-50">
                                    <dt class="font-medium text-slate-500">Gender</dt>
                                    <dd class="font-bold text-slate-900">{{ patient.gender }}</dd>
                                </div>
                                <div class="flex justify-between py-1 border-b border-slate-50">
                                    <dt class="font-medium text-slate-500">Date of Birth</dt>
                                    <dd class="font-bold text-slate-900">{{ formatDate(patient.date_of_birth) }}</dd>
                                </div>
                                <div class="flex justify-between py-1 border-b border-slate-50">
                                    <dt class="font-medium text-slate-500">Age</dt>
                                    <dd class="font-bold text-slate-900">{{ patient.age || calculateAge(patient.date_of_birth) }} years</dd>
                                </div>
                                <div v-if="patient.guardian" class="flex justify-between py-1 border-b border-slate-50">
                                    <dt class="font-medium text-slate-500">Parent / Guardian</dt>
                                    <dd class="font-bold text-amber-800">{{ patient.guardian }}</dd>
                                </div>
                                <div class="flex justify-between py-1 border-b border-slate-50">
                                    <dt class="font-medium text-slate-500">Phone</dt>
                                    <dd class="font-mono font-bold text-slate-900">{{ patient.contact_number || 'N/A' }}</dd>
                                </div>
                                <div class="flex justify-between py-1 border-b border-slate-50">
                                    <dt class="font-medium text-slate-500">Email</dt>
                                    <dd class="font-bold text-slate-900 truncate max-w-[160px]">{{ patient.email || 'N/A' }}</dd>
                                </div>
                                <div class="py-1">
                                    <dt class="font-medium text-slate-500 block mb-1">Residential Address</dt>
                                    <dd class="font-medium text-slate-800 bg-slate-50 p-2.5 rounded-xl border border-slate-200/80 leading-relaxed">
                                        {{ patient.address || 'No residential address on record.' }}
                                    </dd>
                                </div>
                            </dl>
                        </div>

                        <!-- Medical History & Systemic Conditions Card -->
                        <div class="rounded-2xl border border-slate-200/90 bg-white p-5 shadow-2xs space-y-4">
                            <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                                <div>
                                    <h3 class="text-sm font-black uppercase tracking-wider text-slate-900 flex items-center gap-2">
                                        <i class="pi pi-shield text-teal-600 text-base"></i>
                                        <span>Medical Health History</span>
                                    </h3>
                                </div>
                                <Link
                                    v-if="['admin', 'secretary', 'dentist', 'staff'].includes(userRole)"
                                    :href="route('medical-histories.edit', patient.id)"
                                    class="text-xs font-bold text-teal-700 hover:text-teal-800"
                                >
                                    {{ patient.medical_history ? 'Update' : 'Intake' }}
                                </Link>
                            </div>

                            <div v-if="patient.medical_history" class="space-y-3.5 text-xs">
                                <!-- Blood Type & Allergy Flag -->
                                <div class="flex items-center justify-between p-2.5 rounded-xl bg-slate-50 border border-slate-200">
                                    <span class="font-bold text-slate-700">Blood Group</span>
                                    <span class="font-black text-teal-800 bg-white px-2 py-0.5 rounded border border-slate-200">
                                        {{ patient.medical_history.blood_type ? 'Type ' + patient.medical_history.blood_type : 'Not Stated' }}
                                    </span>
                                </div>

                                <!-- Systemic Conditions Chips -->
                                <div>
                                    <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400 block mb-1.5">
                                        Systemic Conditions
                                    </span>
                                    <div class="flex flex-wrap gap-1.5">
                                        <span v-if="patient.medical_history.hypertension" class="px-2 py-0.5 rounded text-[11px] font-bold bg-amber-50 text-amber-800 border border-amber-200">
                                            Hypertension
                                        </span>
                                        <span v-if="patient.medical_history.diabetes" class="px-2 py-0.5 rounded text-[11px] font-bold bg-amber-50 text-amber-800 border border-amber-200">
                                            Diabetes
                                        </span>
                                        <span v-if="patient.medical_history.heart_disease" class="px-2 py-0.5 rounded text-[11px] font-bold bg-rose-50 text-rose-800 border border-rose-200">
                                            Heart Disease
                                        </span>
                                        <span v-if="patient.medical_history.bleeding_disorders" class="px-2 py-0.5 rounded text-[11px] font-bold bg-rose-50 text-rose-800 border border-rose-200">
                                            Bleeding Tendency
                                        </span>
                                        <span v-if="patient.medical_history.asthma" class="px-2 py-0.5 rounded text-[11px] font-bold bg-amber-50 text-amber-800 border border-amber-200">
                                            Asthma
                                        </span>
                                        <span v-if="patient.medical_history.kidney_disease" class="px-2 py-0.5 rounded text-[11px] font-bold bg-amber-50 text-amber-800 border border-amber-200">
                                            Kidney Disease
                                        </span>
                                        <span v-if="patient.medical_history.is_pregnant" class="px-2 py-0.5 rounded text-[11px] font-bold bg-blue-50 text-blue-800 border border-blue-200">
                                            Pregnant / Nursing
                                        </span>
                                        <span
                                            v-if="!patient.medical_history.hypertension && !patient.medical_history.diabetes && !patient.medical_history.heart_disease && !patient.medical_history.bleeding_disorders && !patient.medical_history.asthma && !patient.medical_history.is_pregnant"
                                            class="text-xs text-emerald-700 font-medium flex items-center gap-1"
                                        >
                                            <i class="pi pi-check text-[10px]"></i> No chronic conditions reported
                                        </span>
                                    </div>
                                </div>

                                <!-- Current Medications -->
                                <div v-if="patient.medical_history.current_medications">
                                    <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400 block mb-1">
                                        Maintenance Medications
                                    </span>
                                    <p class="bg-slate-50 p-2.5 rounded-xl border border-slate-200 text-slate-800 leading-relaxed">
                                        {{ patient.medical_history.current_medications }}
                                    </p>
                                </div>

                                <!-- Emergency Contact -->
                                <div v-if="patient.medical_history.emergency_contact_name" class="p-3 rounded-xl bg-slate-50 border border-slate-200">
                                    <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400 block mb-0.5">Emergency Contact</span>
                                    <p class="font-bold text-slate-900">
                                        {{ patient.medical_history.emergency_contact_name }}
                                        <span v-if="patient.medical_history.emergency_contact_relation" class="text-slate-500 font-normal">
                                            ({{ patient.medical_history.emergency_contact_relation }})
                                        </span>
                                    </p>
                                    <p class="font-mono text-slate-600 mt-0.5">{{ patient.medical_history.emergency_contact_number || 'No contact phone' }}</p>
                                </div>
                            </div>

                            <!-- Empty Medical State -->
                            <div v-else class="text-center py-6 text-slate-500">
                                <i class="pi pi-file-medical text-2xl text-slate-300 mb-2"></i>
                                <p class="text-xs font-bold text-slate-800">No Medical Intake Completed</p>
                                <p class="text-[11px] text-slate-400 mt-0.5">Please record health conditions & allergies for clinical safety.</p>
                                <Link
                                    v-if="['admin', 'secretary', 'dentist', 'staff'].includes(userRole)"
                                    :href="route('medical-histories.edit', patient.id)"
                                    class="mt-3 inline-flex items-center gap-1 px-3 py-1.5 rounded-lg text-xs font-bold text-teal-700 bg-teal-50 hover:bg-teal-100 transition"
                                >
                                    <i class="pi pi-plus text-[10px]"></i>
                                    <span>Complete Intake</span>
                                </Link>
                            </div>
                        </div>

                    </div>

                    <!-- Right Column: Operational Sections (8 Cols) -->
                    <div class="lg:col-span-8 space-y-6">

                        <!-- Section 1: Appointments History & Visits -->
                        <div class="rounded-2xl border border-slate-200/90 bg-white shadow-2xs overflow-hidden">
                            <div class="flex items-center justify-between border-b border-slate-100 px-5 py-4 bg-slate-50/50">
                                <div class="flex items-center gap-3">
                                    <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-teal-50 text-teal-700 border border-teal-100">
                                        <i class="pi pi-calendar text-base"></i>
                                    </div>
                                    <div>
                                        <h3 class="text-base font-bold text-slate-900 flex items-center gap-2">
                                            <span>Appointments & Consultations</span>
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-bold bg-teal-50 text-teal-700 border border-teal-200">
                                                {{ patient.appointments?.length || 0 }}
                                            </span>
                                        </h3>
                                        <p class="text-xs text-slate-500">
                                            Past visits and upcoming chair bookings
                                        </p>
                                    </div>
                                </div>

                                <Link
                                    v-if="['admin', 'secretary', 'dentist'].includes(userRole)"
                                    :href="route('appointments.create', { patient_id: patient.id })"
                                    class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-bold text-teal-700 bg-teal-50 hover:bg-teal-100 border border-teal-200 transition"
                                >
                                    <i class="pi pi-plus text-[10px]"></i>
                                    <span>Book Visit</span>
                                </Link>
                            </div>

                            <div class="p-5">
                                <div v-if="patient.appointments && patient.appointments.length > 0" class="space-y-3">
                                    <div
                                        v-for="apt in pagedAppointments"
                                        :key="apt.id"
                                        class="rounded-xl border border-slate-200/80 p-4 hover:bg-slate-50/70 transition"
                                    >
                                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                                            <div>
                                                <div class="flex items-center gap-2">
                                                    <span class="font-bold text-slate-900 text-sm">{{ apt.service }}</span>
                                                    <span
                                                        :class="[
                                                            apt.status === 'confirmed' ? 'bg-emerald-50 text-emerald-800 border-emerald-200' :
                                                            apt.status === 'pending' ? 'bg-amber-50 text-amber-800 border-amber-200' :
                                                            apt.status === 'completed' ? 'bg-blue-50 text-blue-800 border-blue-200' : 'bg-rose-50 text-rose-800 border-rose-200',
                                                            'px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wider border',
                                                        ]"
                                                    >
                                                        {{ apt.status }}
                                                    </span>
                                                </div>

                                                <div class="mt-1 flex flex-wrap items-center gap-3 text-xs text-slate-500">
                                                    <span class="inline-flex items-center gap-1">
                                                        <i class="pi pi-calendar text-[10px] text-teal-600"></i>
                                                        {{ formatDate(apt.appointment_date) }}
                                                    </span>
                                                    <span class="inline-flex items-center gap-1">
                                                        <i class="pi pi-clock text-[10px] text-teal-600"></i>
                                                        {{ formatTime(apt.appointment_time) }}
                                                    </span>
                                                    <span v-if="apt.dentist" class="inline-flex items-center gap-1 font-semibold text-teal-800">
                                                        🩺 {{ apt.dentist.name.startsWith('Dr.') ? apt.dentist.name : 'Dr. ' + apt.dentist.name }}
                                                    </span>
                                                </div>

                                                <p v-if="apt.notes" class="mt-2 text-xs text-slate-600 bg-slate-50 p-2 rounded-lg border border-slate-100">
                                                    <strong class="text-slate-800">Notes:</strong> {{ apt.notes }}
                                                </p>
                                            </div>

                                            <div
                                                v-if="['admin', 'dentist'].includes(userRole) && ['confirmed', 'completed'].includes(apt.status)"
                                                class="shrink-0"
                                            >
                                                <Link
                                                    :href="route('treatments.create', { appointment_id: apt.id, patient_id: patient.id })"
                                                    class="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg text-xs font-bold text-teal-700 bg-teal-50 hover:bg-teal-100 border border-teal-200 transition"
                                                >
                                                    <i class="pi pi-heart text-[10px]"></i>
                                                    <span>Document Treatment</span>
                                                </Link>
                                            </div>
                                        </div>
                                    </div>

                                    <ClientPagination v-model:page="aptPage" :total="patient.appointments.length" :per-page="perPage" label="appointments" />
                                </div>

                                <div v-else class="text-center py-8 text-slate-500">
                                    <i class="pi pi-calendar-times text-2xl text-slate-300 mb-2"></i>
                                    <p class="text-xs font-bold text-slate-800">No Appointments Recorded</p>
                                    <p class="text-[11px] text-slate-400 mt-0.5">This patient has no booked chair appointments yet.</p>
                                    <Link
                                        v-if="['admin', 'secretary', 'dentist'].includes(userRole)"
                                        :href="route('appointments.create', { patient_id: patient.id })"
                                        class="mt-3 inline-flex items-center gap-1 px-3 py-1.5 rounded-lg text-xs font-bold text-teal-700 bg-teal-50 hover:bg-teal-100 transition"
                                    >
                                        <i class="pi pi-plus text-[10px]"></i>
                                        <span>Schedule First Visit</span>
                                    </Link>
                                </div>
                            </div>
                        </div>

                        <!-- Section 2: Clinical Treatments & Records -->
                        <div class="rounded-2xl border border-slate-200/90 bg-white shadow-2xs overflow-hidden">
                            <div class="flex items-center justify-between border-b border-slate-100 px-5 py-4 bg-slate-50/50">
                                <div class="flex items-center gap-3">
                                    <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-blue-50 text-blue-700 border border-blue-100">
                                        <i class="pi pi-heart text-base"></i>
                                    </div>
                                    <div>
                                        <h3 class="text-base font-bold text-slate-900 flex items-center gap-2">
                                            <span>Clinical Treatments & Dental Records</span>
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-bold bg-blue-50 text-blue-700 border border-blue-200">
                                                {{ patient.treatments?.length || 0 }}
                                            </span>
                                        </h3>
                                        <p class="text-xs text-slate-500">
                                            Procedures performed, tooth charting & prescriptions
                                        </p>
                                    </div>
                                </div>

                                <Link
                                    v-if="['admin', 'dentist'].includes(userRole)"
                                    :href="route('treatments.create', { patient_id: patient.id })"
                                    class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-bold text-blue-700 bg-blue-50 hover:bg-blue-100 border border-blue-200 transition"
                                >
                                    <i class="pi pi-plus text-[10px]"></i>
                                    <span>Record Treatment</span>
                                </Link>
                            </div>

                            <div class="p-5">
                                <div v-if="patient.treatments && patient.treatments.length > 0" class="space-y-3">
                                    <div
                                        v-for="t in pagedTreatments"
                                        :key="t.id"
                                        class="rounded-xl border border-slate-200/80 p-4 hover:bg-slate-50/70 transition"
                                    >
                                        <div class="flex flex-col sm:flex-row sm:items-start justify-between gap-3">
                                            <div class="space-y-1">
                                                <div class="flex items-center gap-2">
                                                    <span class="font-bold text-slate-900 text-sm">{{ t.procedure_name }}</span>
                                                    <span
                                                        v-if="t.tooth_number"
                                                        class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold bg-blue-50 text-blue-800 border border-blue-200"
                                                    >
                                                        Tooth #{{ t.tooth_number }}
                                                    </span>
                                                    <span class="text-xs font-bold text-slate-800">
                                                        Fee: {{ formatCurrency(t.cost) }}
                                                    </span>
                                                </div>

                                                <div class="flex flex-wrap items-center gap-3 text-xs text-slate-500">
                                                    <span><i class="pi pi-calendar text-[10px] text-teal-600 mr-1"></i> {{ formatDate(t.treatment_date) }}</span>
                                                    <span v-if="t.dentist" class="font-semibold text-teal-800">
                                                        🩺 {{ t.dentist.name.startsWith('Dr.') ? t.dentist.name : 'Dr. ' + t.dentist.name }}
                                                    </span>
                                                </div>

                                                <div v-if="t.diagnosis" class="text-xs text-slate-700 pt-1">
                                                    <strong class="text-slate-900">Diagnosis:</strong> {{ t.diagnosis }}
                                                </div>

                                                <p v-if="t.treatment_notes" class="text-xs text-slate-600 bg-slate-50 p-2.5 rounded-lg border border-slate-100">
                                                    <strong class="text-slate-800">Clinical Notes:</strong> {{ t.treatment_notes }}
                                                </p>

                                                <div v-if="t.prescription" class="text-xs text-teal-900 bg-teal-50/70 p-2.5 rounded-lg border border-teal-100 flex items-start gap-1.5">
                                                    <i class="pi pi-file-edit text-teal-600 text-xs mt-0.5"></i>
                                                    <div>
                                                        <strong class="text-teal-950">Prescription:</strong> {{ t.prescription }}
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="flex sm:flex-col items-end gap-2 shrink-0">
                                                <span
                                                    :class="[
                                                        t.status === 'completed' ? 'bg-emerald-50 text-emerald-800 border-emerald-200' :
                                                        t.status === 'in_progress' ? 'bg-amber-50 text-amber-800 border-amber-200' : 'bg-purple-50 text-purple-800 border-purple-200',
                                                        'px-2.5 py-1 rounded-full text-[10px] font-bold uppercase tracking-wider border',
                                                    ]"
                                                >
                                                    {{ (t.status || 'completed').replace('_', ' ') }}
                                                </span>

                                                <Link
                                                    v-if="['admin', 'secretary', 'dentist', 'staff'].includes(userRole)"
                                                    :href="route('treatments.edit', t.id)"
                                                    class="inline-flex items-center gap-1 text-xs font-bold text-blue-700 hover:text-blue-900"
                                                >
                                                    <i class="pi pi-pencil text-[10px]"></i>
                                                    <span>Edit</span>
                                                </Link>
                                            </div>
                                        </div>
                                    </div>

                                    <ClientPagination v-model:page="trtPage" :total="patient.treatments.length" :per-page="perPage" label="treatments" />
                                </div>

                                <div v-else class="text-center py-8 text-slate-500">
                                    <i class="pi pi-heart text-2xl text-slate-300 mb-2"></i>
                                    <p class="text-xs font-bold text-slate-800">No Treatment Records Yet</p>
                                    <p class="text-[11px] text-slate-400 mt-0.5">Clinical procedures recorded by attending dentists will appear here.</p>
                                    <Link
                                        v-if="['admin', 'dentist'].includes(userRole)"
                                        :href="route('treatments.create', { patient_id: patient.id })"
                                        class="mt-3 inline-flex items-center gap-1 px-3 py-1.5 rounded-lg text-xs font-bold text-blue-700 bg-blue-50 hover:bg-blue-100 transition"
                                    >
                                        <i class="pi pi-plus text-[10px]"></i>
                                        <span>Document First Treatment</span>
                                    </Link>
                                </div>
                            </div>
                        </div>

                        <!-- Section 3: Billing & Financial Ledger -->
                        <div class="rounded-2xl border border-slate-200/90 bg-white shadow-2xs overflow-hidden">
                            <div class="flex items-center justify-between border-b border-slate-100 px-5 py-4 bg-slate-50/50">
                                <div class="flex items-center gap-3">
                                    <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-emerald-50 text-emerald-700 border border-emerald-100">
                                        <i class="pi pi-wallet text-base"></i>
                                    </div>
                                    <div>
                                        <h3 class="text-base font-bold text-slate-900 flex items-center gap-2">
                                            <span>Billing Statements & Ledger</span>
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                                {{ patient.invoices?.length || 0 }}
                                            </span>
                                        </h3>
                                        <p class="text-xs text-slate-500">
                                            Invoices issued, payments collected & unsettled balances
                                        </p>
                                    </div>
                                </div>

                                <Link
                                    v-if="['admin', 'secretary', 'dentist', 'staff'].includes(userRole)"
                                    :href="route('billing.create', { patient_id: patient.id })"
                                    class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-bold text-emerald-700 bg-emerald-50 hover:bg-emerald-100 border border-emerald-200 transition"
                                >
                                    <i class="pi pi-plus text-[10px]"></i>
                                    <span>Create Invoice</span>
                                </Link>
                            </div>

                            <!-- Financial Summary Tiles -->
                            <div class="grid grid-cols-3 divide-x divide-slate-100 border-b border-slate-100 bg-slate-50/40 p-4 text-center">
                                <div>
                                    <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400 block">Total Billed</span>
                                    <p class="mt-1 font-black text-slate-900 text-sm sm:text-base">{{ formatCurrency(totalBilled) }}</p>
                                </div>
                                <div>
                                    <span class="text-[10px] font-bold uppercase tracking-wider text-emerald-600 block">Total Collected</span>
                                    <p class="mt-1 font-black text-emerald-700 text-sm sm:text-base">{{ formatCurrency(totalPaid) }}</p>
                                </div>
                                <div>
                                    <span class="text-[10px] font-bold uppercase tracking-wider text-rose-600 block">Balance Due</span>
                                    <p class="mt-1 font-black text-sm sm:text-base" :class="totalBalance > 0 ? 'text-rose-600' : 'text-slate-600'">
                                        {{ formatCurrency(totalBalance) }}
                                    </p>
                                </div>
                            </div>

                            <div class="p-5">
                                <div v-if="patient.invoices && patient.invoices.length > 0" class="overflow-x-auto">
                                    <table class="min-w-full divide-y divide-slate-100 text-xs">
                                        <thead class="text-slate-400 font-bold uppercase tracking-wider text-left">
                                            <tr>
                                                <th scope="col" class="pb-3">Invoice #</th>
                                                <th scope="col" class="pb-3">Date</th>
                                                <th scope="col" class="pb-3 text-right">Net Total</th>
                                                <th scope="col" class="pb-3 text-right">Balance</th>
                                                <th scope="col" class="pb-3 text-center">Status</th>
                                                <th scope="col" class="pb-3 text-right">Action</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-slate-100">
                                            <tr v-for="inv in pagedInvoices" :key="inv.id" class="hover:bg-slate-50/70 transition">
                                                <td class="py-3 font-mono font-bold text-teal-800">
                                                    <Link :href="route('billing.show', inv.id)" class="hover:underline">
                                                        {{ inv.invoice_number }}
                                                    </Link>
                                                </td>
                                                <td class="py-3 text-slate-600">{{ formatDate(inv.invoice_date) }}</td>
                                                <td class="py-3 text-right font-black text-slate-900">{{ formatCurrency(inv.net_amount) }}</td>
                                                <td class="py-3 text-right font-bold" :class="inv.balance > 0 ? 'text-rose-600' : 'text-slate-500'">
                                                    {{ formatCurrency(inv.balance) }}
                                                </td>
                                                <td class="py-3 text-center">
                                                    <span
                                                        :class="[
                                                            inv.status === 'paid' ? 'bg-emerald-50 text-emerald-800 border-emerald-200' :
                                                            inv.status === 'partially_paid' ? 'bg-amber-50 text-amber-800 border-amber-200' :
                                                            inv.status === 'cancelled' ? 'bg-slate-100 text-slate-700 border-slate-200' : 'bg-rose-50 text-rose-800 border-rose-200',
                                                            'px-2 py-0.5 rounded-full text-[10px] font-bold border capitalize',
                                                        ]"
                                                    >
                                                        {{ (inv.status || 'unpaid').replace('_', ' ') }}
                                                    </span>
                                                </td>
                                                <td class="py-3 text-right">
                                                    <Link
                                                        :href="route('billing.show', inv.id)"
                                                        class="inline-flex items-center gap-1 font-bold text-teal-700 hover:text-teal-900"
                                                    >
                                                        <i class="pi pi-file-pdf text-[10px]"></i>
                                                        <span>View</span>
                                                    </Link>
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                    <ClientPagination v-model:page="invPage" :total="patient.invoices.length" :per-page="perPage" label="invoices" />
                                </div>

                                <div v-else class="text-center py-8 text-slate-500">
                                    <i class="pi pi-receipt text-2xl text-slate-300 mb-2"></i>
                                    <p class="text-xs font-bold text-slate-800">No Invoices Issued</p>
                                    <p class="text-[11px] text-slate-400 mt-0.5">Billing invoices and payment vouchers will appear here once created.</p>
                                    <Link
                                        v-if="['admin', 'secretary', 'dentist', 'staff'].includes(userRole)"
                                        :href="route('billing.create', { patient_id: patient.id })"
                                        class="mt-3 inline-flex items-center gap-1 px-3 py-1.5 rounded-lg text-xs font-bold text-emerald-700 bg-emerald-50 hover:bg-emerald-100 transition"
                                    >
                                        <i class="pi pi-plus text-[10px]"></i>
                                        <span>Issue First Invoice</span>
                                    </Link>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>

            </div>
        </div>
    </AuthenticatedLayout>
</template>
