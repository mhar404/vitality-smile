<script setup>
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import Modal from "@/Components/Modal.vue";
import { Head, Link, router } from "@inertiajs/vue3";
import { ref, watch, computed } from "vue";

const props = defineProps({
    treatments: Object,
    filters: {
        type: Object,
        default: () => ({}),
    },
    metrics: {
        type: Object,
        default: () => ({
            total_treatments: 0,
            completed: 0,
            in_progress: 0,
            total_revenue: 0,
        }),
    },
    dentists: {
        type: Array,
        default: () => [],
    },
    patients: {
        type: Array,
        default: () => [],
    },
    isPatient: Boolean,
});

// Patient view search & filter state
const patientSearch = ref("");
const patientStatusTab = ref("all");

const patientMetrics = computed(() => {
    const list = props.treatments?.data || [];
    return {
        total: list.length,
        completed: list.filter((t) => t.status === "completed").length,
        in_progress: list.filter((t) => t.status === "in_progress").length,
        with_prescription: list.filter((t) => !!t.prescription).length,
    };
});

const patientFilteredTreatments = computed(() => {
    let list = props.treatments?.data || [];
    if (patientStatusTab.value !== "all") {
        list = list.filter((t) => t.status === patientStatusTab.value);
    }
    if (patientSearch.value.trim()) {
        const q = patientSearch.value.toLowerCase();
        list = list.filter((t) => {
            const proc = t.procedure_name?.toLowerCase().includes(q);
            const diag = t.diagnosis?.toLowerCase().includes(q);
            const notes = t.treatment_notes?.toLowerCase().includes(q);
            const rx = t.prescription?.toLowerCase().includes(q);
            const tooth = t.tooth_number?.toString().includes(q);
            const dentist = t.dentist?.name?.toLowerCase().includes(q);
            return proc || diag || notes || rx || tooth || dentist;
        });
    }
    return list;
});

// Filters for staff view
const search = ref(props.filters.search || "");
const status = ref(props.filters.status || "");
const dentistId = ref(props.filters.dentist_id || "");
const dateFrom = ref(props.filters.date_from || "");
const dateTo = ref(props.filters.date_to || "");

let searchTimeout = null;
watch(
    [search, status, dentistId, dateFrom, dateTo],
    ([newSearch, newStatus, newDentist, newFrom, newTo]) => {
        if (props.isPatient) return;
        if (searchTimeout) clearTimeout(searchTimeout);
        searchTimeout = setTimeout(() => {
            router.get(
                route("treatments.index"),
                {
                    search: newSearch,
                    status: newStatus,
                    dentist_id: newDentist,
                    date_from: newFrom,
                    date_to: newTo,
                },
                { preserveState: true, replace: true },
            );
        }, 300);
    },
);

const resetFilters = () => {
    search.value = "";
    status.value = "";
    dentistId.value = "";
    dateFrom.value = "";
    dateTo.value = "";
};

// Selected Treatment for View Modal
const selectedTreatment = ref(null);
const showDetailModal = ref(false);

const openDetailModal = (treatment) => {
    selectedTreatment.value = treatment;
    showDetailModal.value = true;
};

const deleteTreatment = (id) => {
    if (confirm("Are you sure you want to delete this treatment record?")) {
        router.delete(route("treatments.destroy", id), {
            preserveScroll: true,
            onSuccess: () => {
                showDetailModal.value = false;
            },
        });
    }
};

const formatDate = (dateStr) => {
    if (!dateStr) return "N/A";
    return new Date(dateStr).toLocaleDateString("en-US", {
        year: "numeric",
        month: "short",
        day: "numeric",
    });
};

const formatCurrency = (val) => {
    const num = parseFloat(val) || 0;
    return (
        "₱" +
        num.toLocaleString(undefined, {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2,
        })
    );
};
</script>

<template>
    <Head title="Dental Records & Treatments" />

    <AuthenticatedLayout>
        <template #header>
            <div
                class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4"
            >
                <div>
                    <div class="flex items-center gap-2">
                        <span
                            class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full bg-teal-50 border border-teal-200 text-teal-800 text-[11px] font-bold uppercase tracking-wider"
                        >
                            <i class="pi pi-shield text-[10px]"></i>
                            <span>{{
                                isPatient
                                    ? "Clinical Chart"
                                    : "Clinical Management"
                            }}</span>
                        </span>
                        <span class="text-xs text-gray-400">•</span>
                        <span class="text-xs font-medium text-gray-500"
                            >Vitality Smile Clinic</span
                        >
                    </div>
                    <h2
                        class="mt-1 text-2xl font-extrabold text-gray-900 tracking-tight"
                    >
                        {{
                            isPatient
                                ? "My Dental Records"
                                : "Clinical Treatments & Records"
                        }}
                    </h2>
                    <p class="mt-0.5 text-xs sm:text-sm text-gray-500">
                        {{
                            isPatient
                                ? "View your complete clinical history, procedures performed, tooth charts, and prescriptions."
                                : "Manage patient clinical records, procedures, tooth charts, and notes."
                        }}
                    </p>
                </div>

                <div class="flex items-center gap-3">
                    <Link
                        v-if="
                            !isPatient &&
                            ['admin', 'dentist'].includes(
                                $page.props.auth.user.role,
                            )
                        "
                        :href="route('treatments.create')"
                        class="inline-flex items-center justify-center gap-2 rounded-xl bg-teal-700 px-5 py-3 text-sm font-semibold text-white shadow-md shadow-teal-700/20 hover:bg-teal-800 transition focus:outline-none focus:ring-2 focus:ring-teal-500 focus:ring-offset-2"
                    >
                        <i class="pi pi-plus text-xs"></i>
                        <span>Record Treatment</span>
                    </Link>
                    <Link
                        v-if="isPatient"
                        :href="route('appointments.create')"
                        class="inline-flex items-center justify-center gap-2 rounded-xl bg-teal-700 px-5 py-3 text-sm font-semibold text-white shadow-md shadow-teal-700/20 hover:bg-teal-800 transition focus:outline-none focus:ring-2 focus:ring-teal-500 focus:ring-offset-2"
                    >
                        <i class="pi pi-calendar-plus text-xs"></i>
                        <span>Book Appointment</span>
                    </Link>
                </div>
            </div>
        </template>

        <div class="py-6 sm:py-8">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 space-y-6">
                <!-- Patient Shared Cross-Navigation Bar -->
                <div
                    v-if="isPatient"
                    class="flex items-center gap-2 overflow-x-auto pb-2 scrollbar-none border-b border-gray-200"
                >
                    <Link
                        :href="route('appointments.index')"
                        class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl text-xs font-bold transition whitespace-nowrap bg-white text-gray-600 hover:bg-gray-100 border border-gray-200/80"
                    >
                        <i class="pi pi-calendar text-xs"></i>
                        <span>My Appointments</span>
                    </Link>
                    <Link
                        :href="route('treatments.index')"
                        class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl text-xs font-bold transition whitespace-nowrap bg-teal-700 text-white shadow-sm"
                    >
                        <i class="pi pi-file text-xs"></i>
                        <span>Dental Records</span>
                    </Link>
                    <Link
                        :href="route('billing.index')"
                        class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl text-xs font-bold transition whitespace-nowrap bg-white text-gray-600 hover:bg-gray-100 border border-gray-200/80"
                    >
                        <i class="pi pi-wallet text-xs"></i>
                        <span>Billing & Invoices</span>
                    </Link>
                    <Link
                        :href="route('payments.index')"
                        class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl text-xs font-bold transition whitespace-nowrap bg-white text-gray-600 hover:bg-gray-100 border border-gray-200/80"
                    >
                        <i class="pi pi-receipt text-xs"></i>
                        <span>My Receipts</span>
                    </Link>
                </div>

                <!-- 1. PATIENT VIEW -->
                <div v-if="isPatient" class="space-y-6">
                    <!-- Patient Intro Card -->
                    <div
                        class="relative overflow-hidden rounded-3xl bg-gradient-to-r from-teal-900 via-teal-800 to-slate-900 p-6 sm:p-8 text-white shadow-xl shadow-teal-950/15"
                    >
                        <div
                            class="pointer-events-none absolute -right-10 -bottom-10 h-64 w-64 rounded-full bg-teal-500/15 blur-2xl"
                            aria-hidden="true"
                        ></div>

                        <div
                            class="relative flex flex-col md:flex-row md:items-center justify-between gap-6"
                        >
                            <div>
                                <span
                                    class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-white/15 text-teal-200 backdrop-blur-md mb-3 border border-white/10"
                                >
                                    <i class="pi pi-shield text-[10px]"></i>
                                    Official Clinical History
                                </span>
                                <h3
                                    class="text-2xl sm:text-3xl font-extrabold tracking-tight text-white"
                                >
                                    Your Dental Treatment Timeline
                                </h3>
                                <p
                                    class="text-teal-100/90 text-xs sm:text-sm mt-1.5 max-w-xl leading-relaxed"
                                >
                                    Certified electronic health record
                                    documenting performed dental procedures,
                                    tooth charting, clinical diagnoses, and
                                    prescriptions by Dr. Valdez.
                                </p>
                            </div>
                            <div
                                class="bg-white/10 backdrop-blur-md rounded-2xl px-6 py-4 text-center border border-white/20 shrink-0"
                            >
                                <p
                                    class="text-[11px] text-teal-200 uppercase font-bold tracking-wider"
                                >
                                    Completed Procedures
                                </p>
                                <p
                                    class="text-3xl font-extrabold text-white mt-1"
                                >
                                    {{ patientMetrics.completed }}
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- 4-Card Overview Metrics -->
                    <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4">
                        <div
                            @click="patientStatusTab = 'all'"
                            class="cursor-pointer rounded-2xl border bg-white p-4 sm:p-5 shadow-xs transition hover:shadow-md"
                            :class="
                                patientStatusTab === 'all'
                                    ? 'border-teal-500 ring-2 ring-teal-500/10'
                                    : 'border-gray-200/80'
                            "
                        >
                            <div class="flex items-center justify-between">
                                <span
                                    class="text-xs font-semibold uppercase tracking-wider text-gray-500"
                                    >Total Records</span
                                >
                                <div
                                    class="flex h-9 w-9 items-center justify-center rounded-xl bg-teal-50 text-teal-700"
                                >
                                    <i class="pi pi-file text-sm"></i>
                                </div>
                            </div>
                            <p
                                class="mt-2 text-2xl font-extrabold text-gray-900"
                            >
                                {{ patientMetrics.total }}
                            </p>
                            <p class="mt-0.5 text-[11px] text-gray-400">
                                All recorded procedures
                            </p>
                        </div>

                        <div
                            @click="patientStatusTab = 'completed'"
                            class="cursor-pointer rounded-2xl border bg-white p-4 sm:p-5 shadow-xs transition hover:shadow-md"
                            :class="
                                patientStatusTab === 'completed'
                                    ? 'border-emerald-500 ring-2 ring-emerald-500/10'
                                    : 'border-gray-200/80'
                            "
                        >
                            <div class="flex items-center justify-between">
                                <span
                                    class="text-xs font-semibold uppercase tracking-wider text-gray-500"
                                    >Completed</span
                                >
                                <div
                                    class="flex h-9 w-9 items-center justify-center rounded-xl bg-emerald-50 text-emerald-700"
                                >
                                    <i class="pi pi-check-circle text-sm"></i>
                                </div>
                            </div>
                            <p
                                class="mt-2 text-2xl font-extrabold text-emerald-700"
                            >
                                {{ patientMetrics.completed }}
                            </p>
                            <p class="mt-0.5 text-[11px] text-gray-400">
                                Successfully completed
                            </p>
                        </div>

                        <div
                            @click="patientStatusTab = 'in_progress'"
                            class="cursor-pointer rounded-2xl border bg-white p-4 sm:p-5 shadow-xs transition hover:shadow-md"
                            :class="
                                patientStatusTab === 'in_progress'
                                    ? 'border-amber-500 ring-2 ring-amber-500/10'
                                    : 'border-gray-200/80'
                            "
                        >
                            <div class="flex items-center justify-between">
                                <span
                                    class="text-xs font-semibold uppercase tracking-wider text-gray-500"
                                    >In Progress</span
                                >
                                <div
                                    class="flex h-9 w-9 items-center justify-center rounded-xl bg-amber-50 text-amber-700"
                                >
                                    <i class="pi pi-clock text-sm"></i>
                                </div>
                            </div>
                            <p
                                class="mt-2 text-2xl font-extrabold text-amber-700"
                            >
                                {{ patientMetrics.in_progress }}
                            </p>
                            <p class="mt-0.5 text-[11px] text-gray-400">
                                Ongoing dental care
                            </p>
                        </div>

                        <div
                            class="rounded-2xl border border-gray-200/80 bg-white p-4 sm:p-5 shadow-xs"
                        >
                            <div class="flex items-center justify-between">
                                <span
                                    class="text-xs font-semibold uppercase tracking-wider text-gray-500"
                                    >Prescriptions</span
                                >
                                <div
                                    class="flex h-9 w-9 items-center justify-center rounded-xl bg-blue-50 text-blue-700"
                                >
                                    <i class="pi pi-file-edit text-sm"></i>
                                </div>
                            </div>
                            <p
                                class="mt-2 text-2xl font-extrabold text-blue-700"
                            >
                                {{ patientMetrics.with_prescription }}
                            </p>
                            <p class="mt-0.5 text-[11px] text-gray-400">
                                Medical instructions
                            </p>
                        </div>
                    </div>

                    <!-- Filter Toolbar -->
                    <div
                        class="rounded-3xl border border-gray-200/90 bg-white shadow-sm overflow-hidden"
                    >
                        <div
                            class="p-4 sm:p-5 border-b border-gray-100 flex flex-col md:flex-row md:items-center md:justify-between gap-4"
                        >
                            <!-- Status Tabs -->
                            <div
                                class="flex items-center gap-1.5 overflow-x-auto pb-1 md:pb-0 scrollbar-none"
                            >
                                <button
                                    type="button"
                                    @click="patientStatusTab = 'all'"
                                    :class="[
                                        'px-3.5 py-1.5 text-xs font-bold rounded-xl transition cursor-pointer whitespace-nowrap',
                                        patientStatusTab === 'all'
                                            ? 'bg-teal-700 text-white shadow-xs'
                                            : 'bg-gray-100 text-gray-600 hover:bg-gray-200/80',
                                    ]"
                                >
                                    All Procedures ({{ patientMetrics.total }})
                                </button>
                                <button
                                    type="button"
                                    @click="patientStatusTab = 'completed'"
                                    :class="[
                                        'px-3.5 py-1.5 text-xs font-bold rounded-xl transition cursor-pointer whitespace-nowrap',
                                        patientStatusTab === 'completed'
                                            ? 'bg-emerald-600 text-white shadow-xs'
                                            : 'bg-gray-100 text-gray-600 hover:bg-gray-200/80',
                                    ]"
                                >
                                    Completed ({{ patientMetrics.completed }})
                                </button>
                                <button
                                    type="button"
                                    @click="patientStatusTab = 'in_progress'"
                                    :class="[
                                        'px-3.5 py-1.5 text-xs font-bold rounded-xl transition cursor-pointer whitespace-nowrap',
                                        patientStatusTab === 'in_progress'
                                            ? 'bg-amber-600 text-white shadow-xs'
                                            : 'bg-gray-100 text-gray-600 hover:bg-gray-200/80',
                                    ]"
                                >
                                    In Progress ({{
                                        patientMetrics.in_progress
                                    }})
                                </button>
                            </div>

                            <!-- Search -->
                            <div class="relative w-full md:w-72">
                                <i
                                    class="pi pi-search absolute left-3.5 top-1/2 -translate-y-1/2 text-gray-400 text-xs"
                                ></i>
                                <input
                                    v-model="patientSearch"
                                    type="text"
                                    placeholder="Search procedure, diagnosis, tooth #..."
                                    class="w-full rounded-xl border border-gray-200 bg-gray-50/70 py-2 pl-9 pr-8 text-xs text-gray-900 placeholder-gray-400 focus:border-teal-500 focus:bg-white focus:outline-none focus:ring-1 focus:ring-teal-500 transition dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100 dark:placeholder-slate-400 dark:focus:bg-slate-800"
                                />
                                <button
                                    v-if="patientSearch"
                                    @click="patientSearch = ''"
                                    type="button"
                                    class="absolute right-2.5 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600"
                                >
                                    <i class="pi pi-times text-xs"></i>
                                </button>
                            </div>
                        </div>

                        <!-- Patient Treatments Timeline/List -->
                        <div
                            v-if="patientFilteredTreatments.length > 0"
                            class="divide-y divide-gray-100 p-4 sm:p-6 space-y-4"
                        >
                            <div
                                v-for="item in patientFilteredTreatments"
                                :key="item.id"
                                class="rounded-2xl border border-gray-200/80 bg-white p-5 sm:p-6 transition hover:shadow-md hover:border-teal-300 space-y-4"
                            >
                                <div
                                    class="flex flex-col sm:flex-row sm:items-start justify-between gap-4"
                                >
                                    <div class="flex items-start gap-3.5">
                                        <div
                                            class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-teal-50 border border-teal-100 text-teal-700"
                                        >
                                            <i class="pi pi-file text-lg"></i>
                                        </div>

                                        <div>
                                            <div
                                                class="flex flex-wrap items-center gap-2"
                                            >
                                                <h4
                                                    class="text-base sm:text-lg font-bold text-gray-900"
                                                >
                                                    {{ item.procedure_name }}
                                                </h4>
                                                <span
                                                    v-if="item.tooth_number"
                                                    class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-bold bg-blue-50 text-blue-700 border border-blue-200"
                                                >
                                                    <i
                                                        class="pi pi-tag text-[10px]"
                                                    ></i>
                                                    Tooth #{{
                                                        item.tooth_number
                                                    }}
                                                </span>
                                                <span
                                                    :class="[
                                                        item.status ===
                                                        'completed'
                                                            ? 'bg-emerald-50 text-emerald-700 border-emerald-200'
                                                            : item.status ===
                                                                'in_progress'
                                                              ? 'bg-amber-50 text-amber-700 border-amber-200'
                                                              : 'bg-purple-50 text-purple-700 border-purple-200',
                                                        'px-2.5 py-0.5 inline-flex text-xs font-bold rounded-full capitalize border',
                                                    ]"
                                                >
                                                    {{
                                                        (
                                                            item.status ||
                                                            "in_progress"
                                                        ).replace("_", " ")
                                                    }}
                                                </span>
                                            </div>

                                            <div
                                                class="mt-2 flex flex-wrap items-center gap-4 text-xs text-gray-500"
                                            >
                                                <span
                                                    class="flex items-center gap-1 font-medium text-gray-700"
                                                >
                                                    <i
                                                        class="pi pi-calendar text-teal-600"
                                                    ></i>
                                                    {{
                                                        formatDate(
                                                            item.treatment_date,
                                                        )
                                                    }}
                                                </span>
                                                <span
                                                    class="flex items-center gap-1"
                                                >
                                                    <i
                                                        class="pi pi-user text-teal-600"
                                                    ></i>
                                                    {{
                                                        item.dentist
                                                            ? "Dr. " +
                                                              item.dentist.name
                                                            : "Dr. Valdez"
                                                    }}
                                                </span>
                                                <span
                                                    class="font-bold text-teal-800 bg-teal-50/80 px-2 py-0.5 rounded-md border border-teal-100"
                                                >
                                                    Fee:
                                                    {{
                                                        formatCurrency(
                                                            item.cost,
                                                        )
                                                    }}
                                                </span>
                                                <span
                                                    v-if="item.medical_clearance_presented"
                                                    class="inline-flex items-center gap-1 font-bold text-teal-900 bg-teal-50 px-2 py-0.5 rounded-md border border-teal-200 text-[11px]"
                                                    :title="'Medical Clearance verified: ' + (item.medical_clearance_doctor || 'Attending Physician')"
                                                >
                                                    <i class="pi pi-shield text-[10px] text-teal-600"></i>
                                                    Clearance Presented
                                                </span>
                                            </div>
                                        </div>
                                    </div>

                                    <div
                                        class="flex items-center gap-2 self-end sm:self-auto shrink-0"
                                    >
                                        <button
                                            type="button"
                                            @click="openDetailModal(item)"
                                            class="inline-flex items-center gap-1.5 text-xs font-bold text-teal-800 hover:text-teal-900 bg-teal-50 hover:bg-teal-100 border border-teal-200/80 px-3.5 py-2 rounded-xl transition"
                                        >
                                            <i class="pi pi-eye text-xs"></i>
                                            <span>Full Clinical Record</span>
                                        </button>
                                        <Link
                                            v-if="item.invoice"
                                            :href="
                                                route(
                                                    'billing.show',
                                                    item.invoice.id,
                                                )
                                            "
                                            class="inline-flex items-center gap-1.5 text-xs font-bold text-gray-700 hover:text-teal-800 bg-gray-50 hover:bg-gray-100 border border-gray-200 px-3 py-2 rounded-xl transition"
                                            title="View official billing statement"
                                        >
                                            <i
                                                class="pi pi-file-pdf text-xs"
                                            ></i>
                                            <span>Invoice</span>
                                        </Link>
                                    </div>
                                </div>

                                <!-- Diagnosis snippet -->
                                <div
                                    v-if="item.diagnosis"
                                    class="rounded-xl bg-gray-50 p-3.5 border border-gray-100 text-xs sm:text-sm text-gray-700"
                                >
                                    <span class="font-bold text-gray-900"
                                        >Clinical Diagnosis:</span
                                    >
                                    {{ item.diagnosis }}
                                </div>

                                <!-- Prescription preview if present -->
                                <div
                                    v-if="item.prescription"
                                    class="rounded-xl bg-teal-50/70 border border-teal-200/80 p-3.5 text-xs text-teal-950 flex items-start gap-2.5"
                                >
                                    <i
                                        class="pi pi-file-edit text-teal-700 text-sm mt-0.5 shrink-0"
                                    ></i>
                                    <div>
                                        <span class="font-bold text-teal-900"
                                            >Prescription & Medication
                                            Instructions:</span
                                        >
                                        <p
                                            class="mt-0.5 leading-relaxed whitespace-pre-line"
                                        >
                                            {{ item.prescription }}
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Filter Empty State -->
                        <div
                            v-else-if="
                                treatments?.data && treatments.data.length > 0
                            "
                            class="p-12 text-center text-gray-500"
                        >
                            <i
                                class="pi pi-filter-slash text-3xl text-gray-300 mb-2"
                            ></i>
                            <h4 class="text-base font-bold text-gray-900">
                                No matching dental records
                            </h4>
                            <p class="text-xs text-gray-400 mt-1">
                                Try clearing your search or filter criteria.
                            </p>
                            <button
                                type="button"
                                @click="
                                    patientSearch = '';
                                    patientStatusTab = 'all';
                                "
                                class="mt-3 inline-flex items-center gap-1.5 rounded-xl bg-teal-50 px-3.5 py-1.5 text-xs font-bold text-teal-800 hover:bg-teal-100 transition"
                            >
                                <i class="pi pi-refresh text-xs"></i>
                                <span>Clear Filters</span>
                            </button>
                        </div>

                        <!-- Zero Treatments Empty State -->
                        <div v-else class="p-16 text-center text-gray-500">
                            <div
                                class="h-16 w-16 mx-auto mb-4 bg-teal-50 text-teal-700 rounded-3xl flex items-center justify-center border border-teal-100"
                            >
                                <i class="pi pi-heart text-2xl"></i>
                            </div>
                            <h4 class="text-base font-bold text-gray-900">
                                No Dental Records Yet
                            </h4>
                            <p
                                class="text-xs text-gray-500 mt-1 max-w-sm mx-auto leading-relaxed"
                            >
                                Once Dr. Valdez completes a clinical
                                consultation or treatment, your diagnoses, tooth
                                charts, and prescription regimens will appear
                                here.
                            </p>
                            <div class="mt-5">
                                <Link
                                    :href="route('appointments.create')"
                                    class="inline-flex items-center gap-2 rounded-xl bg-teal-700 px-5 py-2.5 text-xs font-bold text-white shadow-sm hover:bg-teal-800 transition"
                                >
                                    <i class="pi pi-calendar-plus text-xs"></i>
                                    <span>Schedule a Dental Consultation</span>
                                </Link>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 2. STAFF / DENTIST / ADMIN VIEW -->
                <div v-else class="space-y-6">
                    <!-- KPI Metric Cards -->
                    <div
                        class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4"
                    >
                        <div
                            class="bg-white rounded-xl border border-gray-200 p-5 shadow-sm"
                        >
                            <div class="flex items-center justify-between">
                                <div>
                                    <p
                                        class="text-xs font-semibold uppercase tracking-wider text-gray-500"
                                    >
                                        Total Procedures
                                    </p>
                                    <p
                                        class="mt-1 text-2xl font-bold text-gray-900"
                                    >
                                        {{ metrics.total_treatments }}
                                    </p>
                                </div>
                                <div
                                    class="h-11 w-11 rounded-lg bg-teal-50 text-teal-700 flex items-center justify-center text-xl"
                                >
                                    <i class="pi pi-list"></i>
                                </div>
                            </div>
                        </div>

                        <div
                            class="bg-white rounded-xl border border-gray-200 p-5 shadow-sm"
                        >
                            <div class="flex items-center justify-between">
                                <div>
                                    <p
                                        class="text-xs font-semibold uppercase tracking-wider text-gray-500"
                                    >
                                        Completed
                                    </p>
                                    <p
                                        class="mt-1 text-2xl font-bold text-green-700"
                                    >
                                        {{ metrics.completed }}
                                    </p>
                                </div>
                                <div
                                    class="h-11 w-11 rounded-lg bg-green-50 text-green-700 flex items-center justify-center text-xl"
                                >
                                    <i class="pi pi-check-circle"></i>
                                </div>
                            </div>
                        </div>

                        <div
                            class="bg-white rounded-xl border border-gray-200 p-5 shadow-sm"
                        >
                            <div class="flex items-center justify-between">
                                <div>
                                    <p
                                        class="text-xs font-semibold uppercase tracking-wider text-gray-500"
                                    >
                                        In Progress
                                    </p>
                                    <p
                                        class="mt-1 text-2xl font-bold text-yellow-700"
                                    >
                                        {{ metrics.in_progress }}
                                    </p>
                                </div>
                                <div
                                    class="h-11 w-11 rounded-lg bg-yellow-50 text-yellow-700 flex items-center justify-center text-xl"
                                >
                                    <i class="pi pi-clock"></i>
                                </div>
                            </div>
                        </div>

                        <div
                            class="bg-white rounded-xl border border-gray-200 p-5 shadow-sm"
                        >
                            <div class="flex items-center justify-between">
                                <div>
                                    <p
                                        class="text-xs font-semibold uppercase tracking-wider text-gray-500"
                                    >
                                        Procedure Value
                                    </p>
                                    <p
                                        class="mt-1 text-2xl font-bold text-teal-900"
                                    >
                                        {{
                                            formatCurrency(
                                                metrics.total_revenue,
                                            )
                                        }}
                                    </p>
                                </div>
                                <div
                                    class="h-11 w-11 rounded-lg bg-teal-50 text-teal-700 flex items-center justify-center text-xl"
                                >
                                    <i class="pi pi-wallet"></i>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Search & Filter Controls -->
                    <div
                        class="bg-white rounded-xl shadow-sm border border-gray-200 p-4 space-y-4"
                    >
                        <div
                            class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3"
                        >
                            <!-- Search -->
                            <div class="relative lg:col-span-2">
                                <input
                                    v-model="search"
                                    type="text"
                                    placeholder="Search by procedure, tooth #, notes, or patient..."
                                    class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-teal-500 focus:border-teal-500"
                                />
                                <div
                                    class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none"
                                >
                                    <i
                                        class="pi pi-search text-gray-400 text-sm"
                                    ></i>
                                </div>
                            </div>

                            <!-- Status Filter -->
                            <div>
                                <select
                                    v-model="status"
                                    class="w-full border-gray-300 rounded-lg text-sm shadow-sm focus:ring-teal-500 focus:border-teal-500"
                                >
                                    <option value="">All Statuses</option>
                                    <option value="completed">Completed</option>
                                    <option value="in_progress">
                                        In Progress
                                    </option>
                                    <option value="referred">Referred</option>
                                </select>
                            </div>

                            <!-- Dentist Filter -->
                            <div>
                                <select
                                    v-model="dentistId"
                                    class="w-full border-gray-300 rounded-lg text-sm shadow-sm focus:ring-teal-500 focus:border-teal-500"
                                >
                                    <option value="">
                                        All Attending Dentists
                                    </option>
                                    <option
                                        v-for="d in dentists"
                                        :key="d.id"
                                        :value="d.id"
                                    >
                                        Dr. {{ d.name }}
                                    </option>
                                </select>
                            </div>

                            <!-- Reset Button -->
                            <div>
                                <button
                                    type="button"
                                    @click="resetFilters"
                                    class="w-full py-2 px-3 border border-gray-300 text-gray-600 hover:text-gray-900 hover:bg-gray-50 rounded-lg text-sm font-medium transition flex items-center justify-center gap-1.5"
                                >
                                    <i class="pi pi-filter-slash text-xs"></i>
                                    Reset Filters
                                </button>
                            </div>
                        </div>

                        <!-- Date range filter row -->
                        <div
                            class="flex flex-wrap items-center gap-3 pt-2 border-t border-gray-100 text-xs text-gray-600"
                        >
                            <span class="font-semibold text-gray-700"
                                >Date Range:</span
                            >
                            <div class="flex items-center gap-2">
                                <label>From:</label>
                                <input
                                    v-model="dateFrom"
                                    type="date"
                                    class="border border-gray-300 rounded-md text-xs py-1 px-2 focus:ring-teal-500 focus:border-teal-500"
                                />
                            </div>
                            <div class="flex items-center gap-2">
                                <label>To:</label>
                                <input
                                    v-model="dateTo"
                                    type="date"
                                    class="border border-gray-300 rounded-md text-xs py-1 px-2 focus:ring-teal-500 focus:border-teal-500"
                                />
                            </div>
                        </div>
                    </div>

                    <!-- Treatments Ledger Table -->
                    <div
                        class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden"
                    >
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th
                                            scope="col"
                                            class="px-6 py-3.5 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider"
                                        >
                                            Patient
                                        </th>
                                        <th
                                            scope="col"
                                            class="px-6 py-3.5 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider"
                                        >
                                            Procedure & Tooth
                                        </th>
                                        <th
                                            scope="col"
                                            class="px-6 py-3.5 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider"
                                        >
                                            Attending Dentist
                                        </th>
                                        <th
                                            scope="col"
                                            class="px-6 py-3.5 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider"
                                        >
                                            Date
                                        </th>
                                        <th
                                            scope="col"
                                            class="px-6 py-3.5 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider"
                                        >
                                            Cost
                                        </th>
                                        <th
                                            scope="col"
                                            class="px-6 py-3.5 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider"
                                        >
                                            Status
                                        </th>
                                        <th
                                            scope="col"
                                            class="px-6 py-3.5 text-right text-xs font-semibold text-gray-600 uppercase tracking-wider"
                                        >
                                            Actions
                                        </th>
                                    </tr>
                                </thead>
                                <tbody
                                    class="divide-y divide-gray-200 bg-white"
                                >
                                    <tr
                                        v-for="item in treatments?.data"
                                        :key="item.id"
                                        class="hover:bg-gray-50/80 transition"
                                    >
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div v-if="item.patient">
                                                <Link
                                                    :href="
                                                        route(
                                                            'patients.show',
                                                            item.patient.id,
                                                        )
                                                    "
                                                    class="font-bold text-gray-900 hover:text-teal-700 transition"
                                                >
                                                    {{
                                                        item.patient.first_name
                                                    }}
                                                    {{ item.patient.last_name }}
                                                </Link>
                                                <div
                                                    class="text-xs text-gray-500 font-mono"
                                                >
                                                    {{
                                                        item.patient.patient_id
                                                    }}
                                                </div>
                                            </div>
                                            <span
                                                v-else
                                                class="text-xs text-gray-400"
                                                >Unassigned</span
                                            >
                                        </td>

                                        <td class="px-6 py-4">
                                            <div
                                                class="font-semibold text-gray-900 text-sm"
                                            >
                                                {{ item.procedure_name }}
                                            </div>
                                            <div
                                                class="flex items-center gap-2 mt-0.5"
                                            >
                                                <span
                                                    v-if="item.tooth_number"
                                                    class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-medium bg-blue-50 text-blue-700 border border-blue-200"
                                                >
                                                    Tooth
                                                    {{ item.tooth_number }}
                                                </span>
                                                <span
                                                    v-if="item.medical_clearance_presented"
                                                    class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[11px] font-bold bg-amber-50 text-amber-800 border border-amber-200"
                                                    :title="`Medical Clearance Verified: Dr. ${item.medical_clearance_doctor || 'Physician'}`"
                                                >
                                                    <i class="pi pi-shield text-[10px] text-amber-600"></i>
                                                    Clearance
                                                </span>
                                                <span
                                                    v-if="item.prescription"
                                                    class="text-[11px] text-teal-600 flex items-center gap-0.5"
                                                    title="Has prescription"
                                                >
                                                    <i
                                                        class="pi pi-file-edit text-[10px]"
                                                    ></i>
                                                    Rx
                                                </span>
                                            </div>
                                        </td>

                                        <td
                                            class="px-6 py-4 whitespace-nowrap text-sm text-gray-600"
                                        >
                                            <span
                                                v-if="item.dentist"
                                                class="flex items-center gap-1.5 font-medium text-gray-800"
                                            >
                                                <i
                                                    class="pi pi-user text-teal-600 text-xs"
                                                ></i>
                                                Dr. {{ item.dentist.name }}
                                            </span>
                                            <span
                                                v-else
                                                class="text-xs text-gray-400"
                                                >None assigned</span
                                            >
                                        </td>

                                        <td
                                            class="px-6 py-4 whitespace-nowrap text-sm text-gray-600"
                                        >
                                            {{
                                                formatDate(item.treatment_date)
                                            }}
                                        </td>

                                        <td
                                            class="px-6 py-4 whitespace-nowrap text-sm font-bold text-gray-900"
                                        >
                                            {{ formatCurrency(item.cost) }}
                                        </td>

                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span
                                                :class="[
                                                    item.status === 'completed'
                                                        ? 'bg-green-100 text-green-800 border-green-200'
                                                        : item.status ===
                                                            'in_progress'
                                                          ? 'bg-yellow-100 text-yellow-800 border-yellow-200'
                                                          : 'bg-purple-100 text-purple-800 border-purple-200',
                                                    'px-2.5 py-1 inline-flex text-xs leading-4 font-semibold rounded-full border capitalize',
                                                ]"
                                            >
                                                {{
                                                    (
                                                        item.status ||
                                                        "in_progress"
                                                    ).replace("_", " ")
                                                }}
                                            </span>
                                        </td>

                                        <td
                                            class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium space-x-2"
                                        >
                                            <button
                                                type="button"
                                                @click="openDetailModal(item)"
                                                class="text-teal-600 hover:text-teal-900 p-1"
                                                title="View Details"
                                            >
                                                <i class="pi pi-eye"></i>
                                            </button>

                                            <!-- Invoice Action: If already billed, show link to invoice. If unbilled, show Bill button -->
                                            <Link
                                                v-if="
                                                    [
                                                        'admin',
                                                        'dentist',
                                                        'staff',
                                                        'secretary',
                                                    ].includes(
                                                        $page.props.auth.user
                                                            .role,
                                                    ) && item.invoice
                                                "
                                                :href="
                                                    route(
                                                        'billing.show',
                                                        item.invoice.id,
                                                    )
                                                "
                                                class="inline-flex items-center gap-1 px-2.5 py-1 text-xs font-semibold rounded-lg bg-teal-50 text-teal-800 hover:bg-teal-100 border border-teal-200 transition"
                                                title="View Official Invoice"
                                            >
                                                <i
                                                    class="pi pi-receipt text-[10px]"
                                                ></i>
                                                {{
                                                    item.invoice.invoice_number
                                                }}
                                            </Link>
                                            <Link
                                                v-else-if="
                                                    [
                                                        'admin',
                                                        'secretary',
                                                        'dentist',
                                                    ].includes(
                                                        $page.props.auth.user
                                                            .role,
                                                    )
                                                "
                                                :href="
                                                    route('billing.create', {
                                                        treatment_id: item.id,
                                                        patient_id:
                                                            item.patient_id,
                                                    })
                                                "
                                                class="inline-flex items-center gap-1 px-2.5 py-1 text-xs font-semibold rounded-lg bg-teal-700 text-white hover:bg-teal-800 shadow-sm transition"
                                                title="Generate Invoice / Bill"
                                            >
                                                <i
                                                    class="pi pi-receipt text-[10px]"
                                                ></i>
                                                Bill
                                            </Link>

                                            <Link
                                                v-if="
                                                    [
                                                        'admin',
                                                        'dentist',
                                                    ].includes(
                                                        $page.props.auth.user
                                                            .role,
                                                    )
                                                "
                                                :href="
                                                    route(
                                                        'treatments.edit',
                                                        item.id,
                                                    )
                                                "
                                                class="text-blue-600 hover:text-blue-900 p-1"
                                                title="Edit Treatment"
                                            >
                                                <i class="pi pi-pencil"></i>
                                            </Link>

                                            <button
                                                v-if="
                                                    [
                                                        'admin',
                                                        'dentist',
                                                    ].includes(
                                                        $page.props.auth.user
                                                            .role,
                                                    )
                                                "
                                                type="button"
                                                @click="
                                                    deleteTreatment(item.id)
                                                "
                                                class="text-red-500 hover:text-red-700 p-1"
                                                title="Delete Record"
                                            >
                                                <i class="pi pi-trash"></i>
                                            </button>
                                        </td>
                                    </tr>

                                    <tr
                                        v-if="
                                            !treatments?.data ||
                                            treatments.data.length === 0
                                        "
                                    >
                                        <td
                                            colspan="7"
                                            class="px-6 py-12 text-center text-gray-500"
                                        >
                                            <i
                                                class="pi pi-folder-open text-3xl text-gray-300 mb-2"
                                            ></i>
                                            <p
                                                class="text-sm font-medium text-gray-700"
                                            >
                                                No treatment records found
                                                matching criteria.
                                            </p>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <!-- Pagination Links -->
                        <div
                            v-if="
                                treatments?.links && treatments.links.length > 3
                            "
                            class="px-6 py-4 border-t border-gray-200 flex items-center justify-between"
                        >
                            <div class="text-xs text-gray-500">
                                Showing {{ treatments.from || 0 }} to
                                {{ treatments.to || 0 }} of
                                {{ treatments.total }} records
                            </div>
                            <div class="flex gap-1">
                                <Link
                                    v-for="(link, idx) in treatments.links"
                                    :key="idx"
                                    :href="link.url || '#'"
                                    :class="[
                                        'px-3 py-1.5 text-xs rounded-md border transition',
                                        link.active
                                            ? 'bg-teal-700 text-white border-teal-700'
                                            : 'bg-white text-gray-700 border-gray-300 hover:bg-gray-50',
                                        !link.url
                                            ? 'opacity-40 cursor-not-allowed pointer-events-none'
                                            : '',
                                    ]"
                                    v-html="link.label"
                                />
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Treatment Detail Modal -->
        <Modal
            :show="showDetailModal"
            @close="showDetailModal = false"
            maxWidth="lg"
        >
            <div v-if="selectedTreatment" class="p-6">
                <div
                    class="flex items-center justify-between border-b border-gray-100 pb-4"
                >
                    <div>
                        <span
                            class="text-xs font-semibold uppercase tracking-wider text-teal-700"
                            >Clinical Record</span
                        >
                        <h3 class="text-xl font-bold text-gray-900 mt-0.5">
                            {{ selectedTreatment.procedure_name }}
                        </h3>
                    </div>
                    <button
                        type="button"
                        @click="showDetailModal = false"
                        class="text-gray-400 hover:text-gray-600 p-1"
                    >
                        <i class="pi pi-times text-lg"></i>
                    </button>
                </div>

                <div class="mt-4 space-y-4 text-sm">
                    <!-- Key Information Grid -->
                    <div
                        class="grid grid-cols-2 gap-4 bg-gray-50 p-4 rounded-xl border border-gray-100"
                    >
                        <div>
                            <span class="text-xs font-semibold text-gray-500"
                                >Patient:</span
                            >
                            <p class="font-bold text-gray-900 mt-0.5">
                                {{ selectedTreatment.patient?.first_name }}
                                {{ selectedTreatment.patient?.last_name }}
                            </p>
                            <p class="text-xs text-gray-500 font-mono">
                                {{ selectedTreatment.patient?.patient_id }}
                            </p>
                        </div>
                        <div>
                            <span class="text-xs font-semibold text-gray-500"
                                >Attending Dentist:</span
                            >
                            <p class="font-bold text-gray-900 mt-0.5">
                                {{
                                    selectedTreatment.dentist
                                        ? "Dr. " +
                                          selectedTreatment.dentist.name
                                        : "Unassigned"
                                }}
                            </p>
                        </div>
                        <div>
                            <span class="text-xs font-semibold text-gray-500"
                                >Treatment Date:</span
                            >
                            <p class="font-medium text-gray-800 mt-0.5">
                                {{
                                    formatDate(selectedTreatment.treatment_date)
                                }}
                            </p>
                        </div>
                        <div>
                            <span class="text-xs font-semibold text-gray-500"
                                >Tooth / Area:</span
                            >
                            <p class="font-medium text-blue-700 mt-0.5">
                                {{
                                    selectedTreatment.tooth_number
                                        ? "Tooth #" +
                                          selectedTreatment.tooth_number
                                        : "General / Whole Mouth"
                                }}
                            </p>
                        </div>
                        <div>
                            <span class="text-xs font-semibold text-gray-500"
                                >Cost:</span
                            >
                            <p class="font-bold text-teal-800 mt-0.5">
                                {{ formatCurrency(selectedTreatment.cost) }}
                            </p>
                        </div>
                        <div>
                            <span class="text-xs font-semibold text-gray-500"
                                >Status:</span
                            >
                            <p class="mt-0.5">
                                <span
                                    class="capitalize px-2 py-0.5 rounded text-xs font-bold"
                                    :class="
                                        selectedTreatment.status === 'completed'
                                            ? 'bg-green-100 text-green-800'
                                            : 'bg-yellow-100 text-yellow-800'
                                    "
                                >
                                    {{
                                        (
                                            selectedTreatment.status ||
                                            "in_progress"
                                        ).replace("_", " ")
                                    }}
                                </span>
                            </p>
                        </div>
                    </div>

                    <!-- Physician Medical Clearance Verification -->
                    <div
                        v-if="selectedTreatment.medical_clearance_presented"
                        class="rounded-xl border border-teal-200 bg-teal-50/70 p-3.5 space-y-1.5"
                    >
                        <div class="flex items-center justify-between">
                            <span
                                class="font-bold text-xs uppercase tracking-wider text-teal-900 flex items-center gap-1.5"
                            >
                                <i class="pi pi-verified text-teal-600"></i>
                                Physician Medical Clearance Verified
                            </span>
                            <span
                                v-if="selectedTreatment.medical_clearance_date"
                                class="text-xs font-semibold text-teal-800"
                            >
                                Date: {{ formatDate(selectedTreatment.medical_clearance_date) }}
                            </span>
                        </div>
                        <p class="text-xs text-teal-950 font-medium">
                            Clearing Physician / Specialist: <strong class="underline">{{ selectedTreatment.medical_clearance_doctor || 'Attending Physician' }}</strong>
                        </p>
                        <p
                            v-if="selectedTreatment.medical_clearance_notes"
                            class="text-xs text-gray-700 italic bg-white/80 p-2 rounded border border-teal-100 mt-1"
                        >
                            "{{ selectedTreatment.medical_clearance_notes }}"
                        </p>
                    </div>

                    <!-- Clinical Diagnosis -->
                    <div v-if="selectedTreatment.diagnosis">
                        <span class="font-semibold text-gray-700 block mb-1"
                            >Clinical Diagnosis</span
                        >
                        <p
                            class="text-gray-800 bg-white border border-gray-200 p-3 rounded-lg leading-relaxed"
                        >
                            {{ selectedTreatment.diagnosis }}
                        </p>
                    </div>

                    <!-- Treatment & Procedure Notes -->
                    <div v-if="selectedTreatment.treatment_notes">
                        <span class="font-semibold text-gray-700 block mb-1"
                            >Procedure Notes & Observations</span
                        >
                        <p
                            class="text-gray-800 bg-white border border-gray-200 p-3 rounded-lg leading-relaxed whitespace-pre-line"
                        >
                            {{ selectedTreatment.treatment_notes }}
                        </p>
                    </div>

                    <!-- Prescription & Post-op Care -->
                    <div v-if="selectedTreatment.prescription">
                        <span
                            class="font-semibold text-teal-800 flex items-center gap-1.5 mb-1"
                        >
                            <i class="pi pi-file-edit"></i> Prescription &
                            Medication Instructions
                        </span>
                        <p
                            class="text-teal-950 bg-teal-50 border border-teal-200 p-3 rounded-lg leading-relaxed whitespace-pre-line"
                        >
                            {{ selectedTreatment.prescription }}
                        </p>
                    </div>
                </div>

                <div
                    class="mt-6 flex justify-end gap-3 border-t border-gray-100 pt-4"
                >
                    <button
                        type="button"
                        @click="showDetailModal = false"
                        class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 shadow-sm hover:bg-gray-50"
                    >
                        Close
                    </button>
                    <Link
                        v-if="
                            !isPatient &&
                            ['admin', 'dentist', 'staff', 'secretary'].includes(
                                $page.props.auth.user.role,
                            ) &&
                            selectedTreatment.invoice
                        "
                        :href="
                            route('billing.show', selectedTreatment.invoice.id)
                        "
                        class="rounded-lg bg-teal-50 border border-teal-200 text-teal-800 px-4 py-2 text-sm font-semibold shadow-sm hover:bg-teal-100 transition inline-flex items-center gap-1.5"
                    >
                        <i class="pi pi-receipt text-xs"></i>
                        View Invoice ({{
                            selectedTreatment.invoice.invoice_number
                        }})
                    </Link>
                    <Link
                        v-else-if="
                            !isPatient &&
                            ['admin', 'secretary', 'dentist'].includes(
                                $page.props.auth.user.role,
                            )
                        "
                        :href="
                            route('billing.create', {
                                treatment_id: selectedTreatment.id,
                                patient_id: selectedTreatment.patient_id,
                            })
                        "
                        class="rounded-lg bg-teal-700 text-white px-4 py-2 text-sm font-semibold shadow-sm hover:bg-teal-800 transition inline-flex items-center gap-1.5"
                    >
                        <i class="pi pi-receipt text-xs"></i>
                        Generate Invoice
                    </Link>
                    <Link
                        v-if="
                            !isPatient &&
                            ['admin', 'dentist'].includes(
                                $page.props.auth.user.role,
                            )
                        "
                        :href="route('treatments.edit', selectedTreatment.id)"
                        class="rounded-lg bg-teal-700 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-teal-800"
                    >
                        Edit Record
                    </Link>
                </div>
            </div>
        </Modal>
    </AuthenticatedLayout>
</template>
