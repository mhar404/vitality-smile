<script setup>
import { ref, computed } from "vue";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import Pagination from "@/Components/Pagination.vue";
import PatientReportTable from "@/Components/Reports/PatientReportTable.vue";
import AppointmentReportTable from "@/Components/Reports/AppointmentReportTable.vue";
import TreatmentReportTable from "@/Components/Reports/TreatmentReportTable.vue";
import RevenueReportTable from "@/Components/Reports/RevenueReportTable.vue";
import { Head, router } from "@inertiajs/vue3";

const props = defineProps({
    summary: {
        type: Object,
        required: true,
    },
    patientAnalytics: {
        type: Object,
        required: true,
    },
    treatmentAnalytics: {
        type: Object,
        required: true,
    },
    billingAnalytics: {
        type: Object,
        required: true,
    },
    patientReport: {
        type: Object,
        default: () => ({ records: [], grouped_by_date: [], grouped_by_week: [], grand_total: {} }),
    },
    appointmentReport: {
        type: Object,
        default: () => ({ records: [], grouped_by_date: [], grouped_by_week: [], grand_total: {} }),
    },
    treatmentReport: {
        type: Object,
        default: () => ({ records: [], grouped_by_date: [], grouped_by_week: [], grand_total: {} }),
    },
    revenueReport: {
        type: Object,
        default: () => ({ records: [], grouped_by_date: [], grouped_by_week: [], grand_total: {} }),
    },
    insights: {
        type: Array,
        default: () => [],
    },
    filters: {
        type: Object,
        default: () => ({}),
    },
    clinicInfo: {
        type: Object,
        default: () => ({
            name: "Vitality Smile Dental Clinic",
            tagline: "General Dentistry & Orthodontics",
            address:
                "Unit 1 Threo Commercial Building B2 L3A Casan Residencia Subdivision Barangay Malitlit, Sta Rosa Laguna 4026",
            phone: "0977-328-1243",
            email: "vitalitysmile2025@gmail.com",
            dentist: "Dr. Valdez",
        }),
    },
});

// Tab state: 'overview' | 'patients' | 'appointments' | 'treatments' | 'revenue' | 'billing'
const activeTab = ref(props.filters.tab || "overview");
const selectedPreset = ref(props.filters.date_preset || "this_month");
const customDateFrom = ref(props.filters.date_from || "");
const customDateTo = ref(props.filters.date_to || "");
const searchQuery = ref(props.filters.search || "");
const showExportDropdown = ref(false);

// Grouping mode: 'daily' (Monday to Sunday) | 'weekly' | 'flat'
const groupingMode = ref(
    selectedPreset.value === "this_week" || selectedPreset.value === "last_week"
        ? "daily"
        : "daily"
);

const datePresets = [
    { key: "today", label: "Today" },
    { key: "this_week", label: "This Week" },
    { key: "last_week", label: "Last Week" },
    { key: "this_month", label: "This Month" },
    { key: "last_month", label: "Last Month" },
    { key: "this_quarter", label: "This Quarter" },
    { key: "this_year", label: "This Year" },
    { key: "all_time", label: "All Time" },
    { key: "custom", label: "Custom Range" },
];

const changePreset = (presetKey) => {
    selectedPreset.value = presetKey;
    if (presetKey === "this_week" || presetKey === "last_week") {
        groupingMode.value = "daily";
    }
    if (presetKey !== "custom") {
        applyFilters();
    }
};

const applyFilters = () => {
    router.get(
        route("reports.index"),
        {
            date_preset: selectedPreset.value,
            date_from:
                selectedPreset.value === "custom"
                    ? customDateFrom.value
                    : undefined,
            date_to:
                selectedPreset.value === "custom"
                    ? customDateTo.value
                    : undefined,
            tab: activeTab.value,
            search: searchQuery.value || undefined,
        },
        {
            preserveState: true,
            preserveScroll: true,
            replace: true,
        },
    );
};

const switchTab = (tabKey) => {
    activeTab.value = tabKey;
    applyFilters();
};

const triggerPrint = () => {
    window.print();
};

const exportCsv = (type) => {
    showExportDropdown.value = false;
    const url = route("reports.export", {
        type,
        date_preset: selectedPreset.value,
        date_from:
            selectedPreset.value === "custom"
                ? customDateFrom.value
                : undefined,
        date_to:
            selectedPreset.value === "custom" ? customDateTo.value : undefined,
    });
    window.location.href = url;
};

// Formatting utilities
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

const formatNumber = (val) => {
    const num = parseInt(val) || 0;
    return num.toLocaleString();
};

const formatDateRangeLabel = computed(() => {
    if (selectedPreset.value === "today")
        return "Today (" + (props.filters.formatted_from || props.filters.date_from) + ")";
    if (selectedPreset.value === "this_week")
        return "This Week (" + (props.filters.formatted_from || props.filters.date_from) + " – " + (props.filters.formatted_to || props.filters.date_to) + ")";
    if (selectedPreset.value === "last_week")
        return "Last Week (" + (props.filters.formatted_from || props.filters.date_from) + " – " + (props.filters.formatted_to || props.filters.date_to) + ")";
    if (selectedPreset.value === "this_month")
        return "This Month (" + (props.filters.formatted_from || props.filters.date_from) + " – " + (props.filters.formatted_to || props.filters.date_to) + ")";
    if (selectedPreset.value === "last_month")
        return "Last Month (" + (props.filters.formatted_from || props.filters.date_from) + " – " + (props.filters.formatted_to || props.filters.date_to) + ")";
    if (selectedPreset.value === "this_quarter") return "This Quarter";
    if (selectedPreset.value === "this_year")
        return "This Year (" + new Date().getFullYear() + ")";
    if (selectedPreset.value === "all_time") return "All Time Cumulative";
    return `${props.filters.formatted_from || props.filters.date_from} to ${props.filters.formatted_to || props.filters.date_to}`;
});

const activeReportTitle = computed(() => {
    switch (activeTab.value) {
        case "patients":
            return "Official Patient Registration Report";
        case "appointments":
            return "Official Appointment Schedule Report";
        case "treatments":
            return "Official Clinical Treatments & Procedures Report";
        case "revenue":
            return "Official Revenue & Financial Collections Report";
        case "billing":
            return "Official Invoicing & Accounts Receivable Report";
        case "overview":
        default:
            return "Executive Clinical & Financial Report";
    }
});
</script>

<template>
    <Head title="Clinic Analytics & Automated Reports - Vitality Smile" />

    <AuthenticatedLayout>
        <!-- Print Letterhead Header (Only displayed in print mode) -->
        <div class="hidden print:block mb-6 pb-4 border-b-2 border-teal-800">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-black tracking-tight text-teal-900">
                        VITALITY SMILE DENTAL CLINIC
                    </h1>
                    <p class="text-xs font-semibold text-teal-800 uppercase tracking-wider">
                        General Dentistry & Orthodontics
                    </p>
                    <p class="text-xs text-gray-600 mt-1">
                        {{ clinicInfo.address }}
                    </p>
                    <p class="text-xs text-gray-600">
                        Contact: {{ clinicInfo.phone }} • Email: {{ clinicInfo.email }}
                    </p>
                </div>
                <div class="text-right">
                    <span class="inline-block bg-teal-100 text-teal-950 text-xs font-black uppercase tracking-wider px-3 py-1 rounded border border-teal-300">
                        {{ activeReportTitle }}
                    </span>
                    <p class="text-xs text-gray-700 mt-1.5 font-medium">
                        Generated: {{ clinicInfo.generated_at }}
                    </p>
                    <p class="text-xs text-gray-700">
                        Prepared by: {{ clinicInfo.generated_by }}
                    </p>
                </div>
            </div>
            <div class="mt-3 pt-2.5 border-t border-gray-300 flex justify-between text-xs font-bold text-gray-800">
                <span>Reporting Scope: {{ formatDateRangeLabel }}</span>
                <span>Attending Dentist: {{ clinicInfo.dentist }} (Clinical Director)</span>
            </div>
        </div>

        <template #header>
            <div
                class="print:hidden flex flex-col md:flex-row md:items-center md:justify-between gap-4"
            >
                <div>
                    <div class="flex items-center gap-2">
                        <span
                            class="rounded-lg bg-teal-100 p-1.5 text-teal-700"
                        >
                            <i class="pi pi-chart-bar text-lg"></i>
                        </span>
                        <h2
                            class="text-xl font-bold leading-tight text-gray-900 sm:text-2xl"
                        >
                            Clinic Reports & Analytics
                        </h2>
                    </div>
                    <p class="text-xs text-gray-500 mt-1 sm:text-sm">
                        Automated reporting for patient records, clinical
                        procedures, and billing operations.
                    </p>
                </div>

                <!-- Action Controls: CSV Export -->
                <div class="flex items-center gap-2.5 flex-wrap">
                    <!-- Export Dropdown -->
                    <div class="relative">
                        <button
                            type="button"
                            @click="showExportDropdown = !showExportDropdown"
                            class="inline-flex items-center gap-2 rounded-xl bg-teal-700 px-4 py-2 text-xs font-semibold text-white shadow-sm hover:bg-teal-800 transition focus:outline-none"
                        >
                            <i class="pi pi-download text-xs"></i>
                            Export CSV
                            <i
                                class="pi pi-chevron-down text-[10px] ml-0.5"
                            ></i>
                        </button>

                        <div
                            v-if="showExportDropdown"
                            class="absolute right-0 mt-2 w-60 rounded-xl bg-white dark:bg-slate-800 shadow-lg border border-gray-100 dark:border-slate-700 py-1.5 z-50 text-xs"
                        >
                            <div
                                class="px-3 py-1.5 font-bold uppercase tracking-wider text-gray-400 dark:text-slate-400 text-[10px]"
                            >
                                Download Formal CSV Reports
                            </div>
                            <button
                                @click="exportCsv('summary')"
                                class="w-full text-left px-3.5 py-2 hover:bg-teal-50 dark:hover:bg-slate-700 hover:text-teal-800 dark:hover:text-teal-300 flex items-center gap-2 text-gray-700 dark:text-slate-200 transition"
                            >
                                <i
                                    class="pi pi-file text-teal-600 dark:text-teal-400"
                                ></i>
                                Executive Summary CSV
                            </button>
                            <button
                                @click="exportCsv('patients')"
                                class="w-full text-left px-3.5 py-2 hover:bg-teal-50 dark:hover:bg-slate-700 hover:text-teal-800 dark:hover:text-teal-300 flex items-center gap-2 text-gray-700 dark:text-slate-200 transition"
                            >
                                <i
                                    class="pi pi-users text-teal-600 dark:text-teal-400"
                                ></i>
                                Patient Report CSV
                            </button>
                            <button
                                @click="exportCsv('appointments')"
                                class="w-full text-left px-3.5 py-2 hover:bg-teal-50 dark:hover:bg-slate-700 hover:text-teal-800 dark:hover:text-teal-300 flex items-center gap-2 text-gray-700 dark:text-slate-200 transition"
                            >
                                <i
                                    class="pi pi-calendar text-teal-600 dark:text-teal-400"
                                ></i>
                                Appointment Report CSV
                            </button>
                            <button
                                @click="exportCsv('treatments')"
                                class="w-full text-left px-3.5 py-2 hover:bg-teal-50 dark:hover:bg-slate-700 hover:text-teal-800 dark:hover:text-teal-300 flex items-center gap-2 text-gray-700 dark:text-slate-200 transition"
                            >
                                <i
                                    class="pi pi-heart text-teal-600 dark:text-teal-400"
                                ></i>
                                Treatment Report CSV
                            </button>
                            <button
                                @click="exportCsv('revenue')"
                                class="w-full text-left px-3.5 py-2 hover:bg-teal-50 dark:hover:bg-slate-700 hover:text-teal-800 dark:hover:text-teal-300 flex items-center gap-2 text-gray-700 dark:text-slate-200 transition"
                            >
                                <i
                                    class="pi pi-wallet text-teal-600 dark:text-teal-400"
                                ></i>
                                Revenue Report CSV
                            </button>
                            <button
                                @click="exportCsv('billing')"
                                class="w-full text-left px-3.5 py-2 hover:bg-teal-50 dark:hover:bg-slate-700 hover:text-teal-800 dark:hover:text-teal-300 flex items-center gap-2 text-gray-700 dark:text-slate-200 transition"
                            >
                                <i
                                    class="pi pi-receipt text-teal-600 dark:text-teal-400"
                                ></i>
                                Billing & Invoices CSV
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </template>

        <div
            class="py-6 sm:py-8 space-y-6 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8"
        >
            <!-- 1. DATE PRESET & FILTER CONTROLS BAR (Hidden in print) -->
            <div
                class="print:hidden bg-white dark:bg-slate-900 rounded-2xl border border-gray-200 dark:border-slate-800 p-4 shadow-sm space-y-3"
            >
                <div
                    class="flex flex-col lg:flex-row lg:items-center justify-between gap-3"
                >
                    <!-- Preset Pills -->
                    <div class="flex items-center gap-1.5 flex-wrap">
                        <span
                            class="text-xs font-bold text-gray-500 dark:text-slate-400 uppercase tracking-wider mr-1"
                            >Period:</span
                        >
                        <button
                            v-for="preset in datePresets"
                            :key="preset.key"
                            type="button"
                            @click="changePreset(preset.key)"
                            :class="[
                                selectedPreset === preset.key
                                    ? 'bg-teal-700 text-white font-semibold shadow-sm'
                                    : 'bg-gray-100 dark:bg-slate-800 text-gray-700 dark:text-slate-300 hover:bg-gray-200 dark:hover:bg-slate-700 font-medium',
                                'px-3 py-1.5 rounded-lg text-xs transition',
                            ]"
                        >
                            {{ preset.label }}
                        </button>
                    </div>

                    <!-- Period Info Badge -->
                    <div
                        class="text-xs text-gray-500 dark:text-slate-400 font-medium flex items-center gap-1.5 bg-gray-50 dark:bg-slate-800 px-3 py-1.5 rounded-lg border border-gray-100 dark:border-slate-700 shrink-0"
                    >
                        <i
                            class="pi pi-calendar text-teal-600 dark:text-teal-400 text-xs"
                        ></i>
                        <span
                            >Showing:
                            <strong class="text-gray-900 dark:text-white">{{
                                formatDateRangeLabel
                            }}</strong></span
                        >
                    </div>
                </div>

                <!-- Custom Date Inputs (if 'custom' selected) -->
                <div
                    v-if="selectedPreset === 'custom'"
                    class="pt-3 border-t border-gray-100 dark:border-slate-800 flex flex-wrap items-center gap-3"
                >
                    <div class="flex items-center gap-2">
                        <label
                            class="text-xs font-semibold text-gray-700 dark:text-slate-300"
                            >From:</label
                        >
                        <input
                            type="date"
                            v-model="customDateFrom"
                            class="text-xs rounded-lg border-gray-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-gray-900 dark:text-slate-100 py-1.5 px-2.5 focus:border-teal-500 focus:ring-teal-500"
                        />
                    </div>
                    <div class="flex items-center gap-2">
                        <label
                            class="text-xs font-semibold text-gray-700 dark:text-slate-300"
                            >To:</label
                        >
                        <input
                            type="date"
                            v-model="customDateTo"
                            class="text-xs rounded-lg border-gray-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-gray-900 dark:text-slate-100 py-1.5 px-2.5 focus:border-teal-500 focus:ring-teal-500"
                        />
                    </div>
                    <button
                        type="button"
                        @click="applyFilters"
                        class="rounded-lg bg-teal-700 text-white text-xs font-semibold px-3 py-1.5 hover:bg-teal-800 transition shadow-sm"
                    >
                        Apply Range
                    </button>
                </div>

                <!-- Grouping & Search Controls Bar -->
                <div class="pt-3 border-t border-gray-100 dark:border-slate-800 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                    <div class="flex items-center gap-2 flex-wrap">
                        <span class="text-xs font-bold text-gray-500 dark:text-slate-400 uppercase tracking-wider">Group Records:</span>
                        <div class="inline-flex items-center p-1 rounded-xl bg-gray-100 dark:bg-slate-800">
                            <button
                                type="button"
                                @click="groupingMode = 'daily'"
                                :class="[
                                    groupingMode === 'daily'
                                        ? 'bg-teal-700 text-white font-bold shadow-xs'
                                        : 'text-gray-600 dark:text-slate-300 hover:text-gray-900 dark:hover:text-white',
                                    'px-3 py-1 rounded-lg text-xs transition flex items-center gap-1.5'
                                ]"
                            >
                                <i class="pi pi-calendar text-[10px]"></i>
                                Daily (Mon–Sun)
                            </button>
                            <button
                                type="button"
                                @click="groupingMode = 'weekly'"
                                :class="[
                                    groupingMode === 'weekly'
                                        ? 'bg-teal-700 text-white font-bold shadow-xs'
                                        : 'text-gray-600 dark:text-slate-300 hover:text-gray-900 dark:hover:text-white',
                                    'px-3 py-1 rounded-lg text-xs transition flex items-center gap-1.5'
                                ]"
                            >
                                <i class="pi pi-bars text-[10px]"></i>
                                Weekly
                            </button>
                            <button
                                type="button"
                                @click="groupingMode = 'flat'"
                                :class="[
                                    groupingMode === 'flat'
                                        ? 'bg-teal-700 text-white font-bold shadow-xs'
                                        : 'text-gray-600 dark:text-slate-300 hover:text-gray-900 dark:hover:text-white',
                                    'px-3 py-1 rounded-lg text-xs transition flex items-center gap-1.5'
                                ]"
                            >
                                <i class="pi pi-list text-[10px]"></i>
                                All Records
                            </button>
                        </div>
                    </div>

                    <!-- Search Input -->
                    <div class="w-full sm:w-72">
                        <div class="relative">
                            <i class="pi pi-search absolute left-3 top-2.5 text-gray-400 text-xs"></i>
                            <input
                                type="text"
                                v-model="searchQuery"
                                @keyup.enter="applyFilters"
                                placeholder="Search report records..."
                                class="w-full pl-8 pr-3 py-1.5 text-xs rounded-xl border-gray-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-gray-900 dark:text-slate-100 placeholder-gray-400 dark:placeholder-slate-500 focus:border-teal-500 focus:ring-teal-500"
                            />
                        </div>
                    </div>
                </div>
            </div>

            <!-- 2. AUTOMATED OPERATIONAL INSIGHTS BANNER -->
            <div
                v-if="insights && insights.length > 0"
                class="print:border-none grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-3"
            >
                <div
                    v-for="(item, idx) in insights"
                    :key="idx"
                    :class="[
                        item.type === 'success'
                            ? 'bg-emerald-50 dark:bg-emerald-950/50 border-emerald-200 dark:border-emerald-800/80 text-emerald-950 dark:text-emerald-200'
                            : item.type === 'primary'
                              ? 'bg-teal-50 dark:bg-teal-950/50 border-teal-200 dark:border-teal-800/80 text-teal-950 dark:text-teal-200'
                              : item.type === 'warning'
                                ? 'bg-amber-50 dark:bg-amber-950/50 border-amber-200 dark:border-amber-800/80 text-amber-950 dark:text-amber-200'
                                : item.type === 'danger'
                                  ? 'bg-rose-50 dark:bg-rose-950/50 border-rose-200 dark:border-rose-800/80 text-rose-950 dark:text-rose-200'
                                  : 'bg-blue-50 dark:bg-blue-950/50 border-blue-200 dark:border-blue-800/80 text-blue-950 dark:text-blue-200',
                        'p-4 rounded-xl border flex items-start gap-3 shadow-xs transition-colors',
                    ]"
                >
                    <div
                        :class="[
                            item.type === 'success'
                                ? 'bg-emerald-200/60 dark:bg-emerald-900/60 text-emerald-800 dark:text-emerald-300'
                                : item.type === 'primary'
                                  ? 'bg-teal-200/60 dark:bg-teal-900/60 text-teal-800 dark:text-teal-300'
                                  : item.type === 'warning'
                                    ? 'bg-amber-200/60 dark:bg-amber-900/60 text-amber-800 dark:text-amber-300'
                                    : item.type === 'danger'
                                      ? 'bg-rose-200/60 dark:bg-rose-900/60 text-rose-800 dark:text-rose-300'
                                      : 'bg-blue-200/60 dark:bg-blue-900/60 text-blue-800 dark:text-blue-300',
                            'h-8 w-8 rounded-lg flex items-center justify-center shrink-0 mt-0.5',
                        ]"
                    >
                        <i :class="['pi', item.icon, 'text-sm']"></i>
                    </div>
                    <div class="space-y-0.5 min-w-0">
                        <h4
                            class="text-xs font-bold uppercase tracking-wider text-gray-900 dark:text-white"
                        >
                            {{ item.title }}
                        </h4>
                        <p
                            class="text-xs text-gray-700 dark:text-slate-300 leading-relaxed mt-0.5"
                        >
                            {{ item.message }}
                        </p>
                    </div>
                </div>
            </div>

            <!-- 3. REPORT MODULE NAVIGATION TABS (Hidden in print) -->
            <div
                class="print:hidden border-b border-gray-200 dark:border-slate-800"
            >
                <nav
                    class="-mb-px flex space-x-6 overflow-x-auto"
                    aria-label="Report Modules"
                >
                    <button
                        type="button"
                        @click="switchTab('overview')"
                        :class="[
                            activeTab === 'overview'
                                ? 'border-teal-600 dark:border-teal-400 text-teal-700 dark:text-teal-400 font-bold'
                                : 'border-transparent text-gray-500 dark:text-slate-400 hover:text-gray-700 dark:hover:text-slate-200 hover:border-gray-300 dark:hover:border-slate-700 font-medium',
                            'whitespace-nowrap py-3 px-1 border-b-2 text-sm flex items-center gap-2 transition',
                        ]"
                    >
                        <i class="pi pi-th-large text-sm"></i>
                        Executive Overview
                    </button>

                    <button
                        type="button"
                        @click="switchTab('patients')"
                        :class="[
                            activeTab === 'patients'
                                ? 'border-teal-600 dark:border-teal-400 text-teal-700 dark:text-teal-400 font-bold'
                                : 'border-transparent text-gray-500 dark:text-slate-400 hover:text-gray-700 dark:hover:text-slate-200 hover:border-gray-300 dark:hover:border-slate-700 font-medium',
                            'whitespace-nowrap py-3 px-1 border-b-2 text-sm flex items-center gap-2 transition',
                        ]"
                    >
                        <i class="pi pi-users text-sm"></i>
                        Patient Report
                    </button>

                    <button
                        type="button"
                        @click="switchTab('appointments')"
                        :class="[
                            activeTab === 'appointments'
                                ? 'border-teal-600 dark:border-teal-400 text-teal-700 dark:text-teal-400 font-bold'
                                : 'border-transparent text-gray-500 dark:text-slate-400 hover:text-gray-700 dark:hover:text-slate-200 hover:border-gray-300 dark:hover:border-slate-700 font-medium',
                            'whitespace-nowrap py-3 px-1 border-b-2 text-sm flex items-center gap-2 transition',
                        ]"
                    >
                        <i class="pi pi-calendar text-sm"></i>
                        Appointment Report
                    </button>

                    <button
                        type="button"
                        @click="switchTab('treatments')"
                        :class="[
                            activeTab === 'treatments'
                                ? 'border-teal-600 dark:border-teal-400 text-teal-700 dark:text-teal-400 font-bold'
                                : 'border-transparent text-gray-500 dark:text-slate-400 hover:text-gray-700 dark:hover:text-slate-200 hover:border-gray-300 dark:hover:border-slate-700 font-medium',
                            'whitespace-nowrap py-3 px-1 border-b-2 text-sm flex items-center gap-2 transition',
                        ]"
                    >
                        <i class="pi pi-heart text-sm"></i>
                        Treatment Report
                    </button>

                    <button
                        type="button"
                        @click="switchTab('revenue')"
                        :class="[
                            activeTab === 'revenue'
                                ? 'border-teal-600 dark:border-teal-400 text-teal-700 dark:text-teal-400 font-bold'
                                : 'border-transparent text-gray-500 dark:text-slate-400 hover:text-gray-700 dark:hover:text-slate-200 hover:border-gray-300 dark:hover:border-slate-700 font-medium',
                            'whitespace-nowrap py-3 px-1 border-b-2 text-sm flex items-center gap-2 transition',
                        ]"
                    >
                        <i class="pi pi-wallet text-sm"></i>
                        Revenue Report
                    </button>

                    <button
                        type="button"
                        @click="switchTab('billing')"
                        :class="[
                            activeTab === 'billing'
                                ? 'border-teal-600 dark:border-teal-400 text-teal-700 dark:text-teal-400 font-bold'
                                : 'border-transparent text-gray-500 dark:text-slate-400 hover:text-gray-700 dark:hover:text-slate-200 hover:border-gray-300 dark:hover:border-slate-700 font-medium',
                            'whitespace-nowrap py-3 px-1 border-b-2 text-sm flex items-center gap-2 transition',
                        ]"
                    >
                        <i class="pi pi-receipt text-sm"></i>
                        Invoicing & Ledger
                    </button>
                </nav>
            </div>

            <!-- ========================================================================= -->
            <!-- TAB 1: EXECUTIVE OVERVIEW                                                  -->
            <!-- ========================================================================= -->
            <div v-show="activeTab === 'overview'" class="space-y-6">
                <!-- 6 Major KPI Metrics Cards -->
                <div
                    class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6 gap-4"
                >
                    <!-- Total Patients -->
                    <div
                        class="bg-white dark:bg-slate-900 rounded-2xl border border-gray-200 dark:border-slate-800 p-4 shadow-xs"
                    >
                        <div
                            class="flex items-center justify-between text-gray-500 dark:text-slate-400"
                        >
                            <span
                                class="text-[11px] font-bold uppercase tracking-wider"
                                >Active Patients</span
                            >
                            <span
                                class="h-8 w-8 rounded-lg bg-teal-50 dark:bg-slate-800 text-teal-700 dark:text-teal-400 flex items-center justify-center"
                            >
                                <i class="pi pi-users text-sm"></i>
                            </span>
                        </div>
                        <p
                            class="text-2xl font-black text-gray-900 dark:text-white mt-2"
                        >
                            {{ formatNumber(summary.total_patients) }}
                        </p>
                        <p
                            class="text-[11px] text-teal-700 dark:text-teal-400 font-semibold mt-1"
                        >
                            +{{ summary.new_patients }} registered in period
                        </p>
                    </div>

                    <!-- Procedures Documented -->
                    <div
                        class="bg-white dark:bg-slate-900 rounded-2xl border border-gray-200 dark:border-slate-800 p-4 shadow-xs"
                    >
                        <div
                            class="flex items-center justify-between text-gray-500 dark:text-slate-400"
                        >
                            <span
                                class="text-[11px] font-bold uppercase tracking-wider"
                                >Procedures</span
                            >
                            <span
                                class="h-8 w-8 rounded-lg bg-blue-50 dark:bg-slate-800 text-blue-700 dark:text-blue-400 flex items-center justify-center"
                            >
                                <i class="pi pi-heart text-sm"></i>
                            </span>
                        </div>
                        <p
                            class="text-2xl font-black text-gray-900 dark:text-white mt-2"
                        >
                            {{ formatNumber(summary.total_treatments) }}
                        </p>
                        <p
                            class="text-[11px] text-gray-500 dark:text-slate-400 font-medium mt-1"
                        >
                            {{ summary.completed_treatments }} completed
                            procedures
                        </p>
                    </div>

                    <!-- Gross Billed -->
                    <div
                        class="bg-white dark:bg-slate-900 rounded-2xl border border-gray-200 dark:border-slate-800 p-4 shadow-xs"
                    >
                        <div
                            class="flex items-center justify-between text-gray-500 dark:text-slate-400"
                        >
                            <span
                                class="text-[11px] font-bold uppercase tracking-wider"
                                >Gross Billed</span
                            >
                            <span
                                class="h-8 w-8 rounded-lg bg-purple-50 dark:bg-slate-800 text-purple-700 dark:text-purple-400 flex items-center justify-center"
                            >
                                <i class="pi pi-receipt text-sm"></i>
                            </span>
                        </div>
                        <p
                            class="text-2xl font-black text-gray-900 dark:text-white mt-2"
                        >
                            {{ formatCurrency(summary.gross_billed) }}
                        </p>
                        <p
                            class="text-[11px] text-gray-500 dark:text-slate-400 font-medium mt-1"
                        >
                            {{
                                formatCurrency(summary.discounts_given)
                            }}
                            discounts
                        </p>
                    </div>

                    <!-- Net Billed -->
                    <div
                        class="bg-white dark:bg-slate-900 rounded-2xl border border-gray-200 dark:border-slate-800 p-4 shadow-xs"
                    >
                        <div
                            class="flex items-center justify-between text-gray-500 dark:text-slate-400"
                        >
                            <span
                                class="text-[11px] font-bold uppercase tracking-wider"
                                >Net Invoiced</span
                            >
                            <span
                                class="h-8 w-8 rounded-lg bg-teal-50 dark:bg-slate-800 text-teal-700 dark:text-teal-400 flex items-center justify-center"
                            >
                                <i class="pi pi-file-check text-sm"></i>
                            </span>
                        </div>
                        <p
                            class="text-2xl font-black text-teal-800 dark:text-teal-400 mt-2"
                        >
                            {{ formatCurrency(summary.net_billed) }}
                        </p>
                        <p
                            class="text-[11px] text-gray-500 dark:text-slate-400 font-medium mt-1"
                        >
                            Total revenue due
                        </p>
                    </div>

                    <!-- Total Collected -->
                    <div
                        class="bg-white dark:bg-slate-900 rounded-2xl border border-emerald-200 dark:border-emerald-800/80 bg-emerald-50/20 p-4 shadow-xs"
                    >
                        <div
                            class="flex items-center justify-between text-gray-500 dark:text-slate-400"
                        >
                            <span
                                class="text-[11px] font-bold uppercase tracking-wider text-emerald-800 dark:text-emerald-400"
                                >Collections</span
                            >
                            <span
                                class="h-8 w-8 rounded-lg bg-emerald-100 dark:bg-slate-800 text-emerald-800 dark:text-emerald-400 flex items-center justify-center"
                            >
                                <i class="pi pi-credit-card text-sm"></i>
                            </span>
                        </div>
                        <p
                            class="text-2xl font-black text-emerald-800 dark:text-emerald-400 mt-2"
                        >
                            {{ formatCurrency(summary.total_collected) }}
                        </p>
                        <p
                            class="text-[11px] text-emerald-700 dark:text-emerald-400 font-semibold mt-1"
                        >
                            Received & deposited
                        </p>
                    </div>

                    <!-- Collection Efficiency -->
                    <div
                        class="bg-white dark:bg-slate-900 rounded-2xl border border-gray-200 dark:border-slate-800 p-4 shadow-xs"
                    >
                        <div
                            class="flex items-center justify-between text-gray-500 dark:text-slate-400"
                        >
                            <span
                                class="text-[11px] font-bold uppercase tracking-wider"
                                >Efficiency</span
                            >
                            <span
                                class="h-8 w-8 rounded-lg bg-indigo-50 dark:bg-slate-800 text-indigo-700 dark:text-indigo-400 flex items-center justify-center"
                            >
                                <i class="pi pi-percentage text-sm"></i>
                            </span>
                        </div>
                        <p
                            class="text-2xl font-black text-indigo-900 dark:text-indigo-300 mt-2"
                        >
                            {{ summary.collection_rate }}%
                        </p>
                        <p
                            class="text-[11px] text-amber-700 dark:text-amber-400 font-semibold mt-1"
                        >
                            {{
                                formatCurrency(summary.total_outstanding)
                            }}
                            balance
                        </p>
                    </div>
                </div>

                <!-- 2 High-Level Visual Cards (Top Procedures & Demographics Snapshot) -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <!-- Top Clinical Procedures by Volume -->
                    <div
                        class="bg-white dark:bg-slate-900 rounded-2xl border border-gray-200 dark:border-slate-800 p-6 shadow-sm"
                    >
                        <div class="flex items-center justify-between mb-4">
                            <div>
                                <h3
                                    class="text-sm font-bold text-gray-900 dark:text-white uppercase tracking-wider"
                                >
                                    Top Dental Procedures
                                </h3>
                                <p
                                    class="text-xs text-gray-500 dark:text-slate-400"
                                >
                                    Frequency and generated revenue share
                                </p>
                            </div>
                            <button
                                @click="switchTab('treatments')"
                                class="text-xs font-semibold text-teal-700 dark:text-teal-400 hover:text-teal-900 dark:hover:text-teal-300 flex items-center gap-1"
                            >
                                Full Report
                                <i class="pi pi-arrow-right text-[10px]"></i>
                            </button>
                        </div>

                        <div
                            v-if="
                                treatmentAnalytics.top_procedures &&
                                treatmentAnalytics.top_procedures.length > 0
                            "
                            class="space-y-4"
                        >
                            <div
                                v-for="(
                                    proc, i
                                ) in treatmentAnalytics.top_procedures.slice(
                                    0,
                                    5,
                                )"
                                :key="i"
                                class="space-y-1.5"
                            >
                                <div
                                    class="flex justify-between items-center text-xs"
                                >
                                    <span
                                        class="font-bold text-gray-800 dark:text-slate-200"
                                        >{{ proc.procedure_name }}</span
                                    >
                                    <span
                                        class="text-gray-500 dark:text-slate-400 font-mono"
                                        >{{ proc.count }} sessions •
                                        {{
                                            formatCurrency(proc.total_revenue)
                                        }}</span
                                    >
                                </div>
                                <div
                                    class="w-full bg-gray-100 dark:bg-slate-800 rounded-full h-2 overflow-hidden"
                                >
                                    <div
                                        class="bg-teal-600 dark:bg-teal-500 h-2 rounded-full transition-all duration-500"
                                        :style="{
                                            width: `${proc.percentage}%`,
                                        }"
                                    ></div>
                                </div>
                            </div>
                        </div>
                        <div
                            v-else
                            class="py-8 text-center text-xs text-gray-400 dark:text-slate-500"
                        >
                            No treatment procedures documented in this period.
                        </div>
                    </div>

                    <!-- Payment Methods & Settlement Snapshot -->
                    <div
                        class="bg-white dark:bg-slate-900 rounded-2xl border border-gray-200 dark:border-slate-800 p-6 shadow-sm"
                    >
                        <div class="flex items-center justify-between mb-4">
                            <div>
                                <h3
                                    class="text-sm font-bold text-gray-900 dark:text-white uppercase tracking-wider"
                                >
                                    Payment Channels
                                </h3>
                                <p
                                    class="text-xs text-gray-500 dark:text-slate-400"
                                >
                                    Collected funds breakdown by gateway
                                </p>
                            </div>
                            <button
                                @click="switchTab('billing')"
                                class="text-xs font-semibold text-teal-700 dark:text-teal-400 hover:text-teal-900 dark:hover:text-teal-300 flex items-center gap-1"
                            >
                                Financial Ledger
                                <i class="pi pi-arrow-right text-[10px]"></i>
                            </button>
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                            <div
                                v-for="(
                                    m, name
                                ) in billingAnalytics.payment_methods"
                                :key="name"
                                class="p-3.5 rounded-xl border border-gray-200/80 dark:border-slate-800 bg-gray-50/70 dark:bg-slate-800/90 shadow-2xs transition-colors"
                            >
                                <span
                                    class="text-xs font-bold text-gray-700 dark:text-slate-300 block"
                                    >{{ name }}</span
                                >
                                <p
                                    class="text-base font-black text-teal-800 dark:text-teal-400 mt-1"
                                >
                                    {{ formatCurrency(m.amount) }}
                                </p>
                                <span
                                    class="text-[11px] text-gray-500 dark:text-slate-400"
                                    >{{ m.count }} transaction(s)</span
                                >
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ========================================================================= -->
            <!-- TAB 2: PATIENT REPORT                                                      -->
            <!-- ========================================================================= -->
            <div v-show="activeTab === 'patients'" class="space-y-6">
                <!-- Professional Printable Patient Report Table -->
                <PatientReportTable
                    :report="patientReport"
                    :grouping-mode="groupingMode"
                    :period-label="formatDateRangeLabel"
                />

                <!-- Demographics & Alerts Grid (Screen Only) -->
                <div class="print:hidden grid grid-cols-1 lg:grid-cols-3 gap-6">
                    <!-- Age Distribution -->
                    <div
                        class="bg-white dark:bg-slate-900 rounded-2xl border border-gray-200 dark:border-slate-800 p-6 shadow-sm space-y-4"
                    >
                        <h3
                            class="text-sm font-bold text-gray-900 dark:text-white uppercase tracking-wider flex items-center gap-2"
                        >
                            <i
                                class="pi pi-users text-teal-700 dark:text-teal-400"
                            ></i>
                            Age Demographics
                        </h3>

                        <div class="space-y-3">
                            <div
                                class="flex justify-between items-center text-xs"
                            >
                                <span
                                    class="text-gray-700 dark:text-slate-300 font-medium"
                                    >Pediatric (0 - 12 yrs)</span
                                >
                                <span
                                    class="font-bold text-gray-900 dark:text-white"
                                    >{{
                                        patientAnalytics.age_distribution
                                            .pediatric
                                    }}</span
                                >
                            </div>
                            <div
                                class="flex justify-between items-center text-xs"
                            >
                                <span
                                    class="text-gray-700 dark:text-slate-300 font-medium"
                                    >Adolescent / Teens (13 - 19 yrs)</span
                                >
                                <span
                                    class="font-bold text-gray-900 dark:text-white"
                                    >{{
                                        patientAnalytics.age_distribution.teens
                                    }}</span
                                >
                            </div>
                            <div
                                class="flex justify-between items-center text-xs"
                            >
                                <span
                                    class="text-gray-700 dark:text-slate-300 font-medium"
                                    >Adults (20 - 59 yrs)</span
                                >
                                <span
                                    class="font-bold text-gray-900 dark:text-white"
                                    >{{
                                        patientAnalytics.age_distribution.adults
                                    }}</span
                                >
                            </div>
                            <div
                                class="flex justify-between items-center text-xs"
                            >
                                <span
                                    class="text-gray-700 dark:text-slate-300 font-medium"
                                    >Seniors (60+ yrs)</span
                                >
                                <span
                                    class="font-bold text-gray-900 dark:text-white"
                                    >{{
                                        patientAnalytics.age_distribution
                                            .seniors
                                    }}</span
                                >
                            </div>
                        </div>
                    </div>

                    <!-- Gender Breakdown -->
                    <div
                        class="bg-white dark:bg-slate-900 rounded-2xl border border-gray-200 dark:border-slate-800 p-6 shadow-sm space-y-4"
                    >
                        <h3
                            class="text-sm font-bold text-gray-900 dark:text-white uppercase tracking-wider flex items-center gap-2"
                        >
                            <i
                                class="pi pi-id-card text-teal-700 dark:text-teal-400"
                            ></i>
                            Gender Distribution
                        </h3>

                        <div class="space-y-3">
                            <div
                                class="flex justify-between items-center text-xs"
                            >
                                <span
                                    class="text-gray-700 dark:text-slate-300 font-medium"
                                    >Female Patients</span
                                >
                                <span
                                    class="font-bold text-teal-800 dark:text-teal-400"
                                    >{{
                                        patientAnalytics.gender_distribution
                                            .female
                                    }}</span
                                >
                            </div>
                            <div
                                class="flex justify-between items-center text-xs"
                            >
                                <span
                                    class="text-gray-700 dark:text-slate-300 font-medium"
                                    >Male Patients</span
                                >
                                <span
                                    class="font-bold text-blue-800 dark:text-blue-400"
                                    >{{
                                        patientAnalytics.gender_distribution
                                            .male
                                    }}</span
                                >
                            </div>
                            <div
                                class="flex justify-between items-center text-xs"
                            >
                                <span
                                    class="text-gray-700 dark:text-slate-300 font-medium"
                                    >Other / Unspecified</span
                                >
                                <span
                                    class="font-bold text-gray-600 dark:text-slate-400"
                                    >{{
                                        patientAnalytics.gender_distribution
                                            .other
                                    }}</span
                                >
                            </div>
                        </div>
                    </div>

                    <!-- Medical Alerts Breakdown -->
                    <div
                        class="bg-white dark:bg-slate-900 rounded-2xl border border-gray-200 dark:border-slate-800 p-6 shadow-sm space-y-4"
                    >
                        <h3
                            class="text-sm font-bold text-gray-900 dark:text-white uppercase tracking-wider flex items-center gap-2"
                        >
                            <i
                                class="pi pi-shield text-rose-600 dark:text-rose-400"
                            ></i>
                            Medical Alerts Prevalence
                        </h3>

                        <div class="grid grid-cols-2 gap-2 text-xs">
                            <div
                                class="p-2 rounded-lg bg-rose-50 dark:bg-rose-950/40 border border-rose-100 dark:border-rose-900/50 flex justify-between items-center"
                            >
                                <span
                                    class="text-rose-900 dark:text-rose-200 font-semibold"
                                    >Allergies</span
                                >
                                <span
                                    class="font-black text-rose-800 dark:text-rose-300"
                                    >{{
                                        patientAnalytics.medical_alerts
                                            .allergies
                                    }}</span
                                >
                            </div>
                            <div
                                class="p-2 rounded-lg bg-amber-50 dark:bg-amber-950/40 border border-amber-100 dark:border-amber-900/50 flex justify-between items-center"
                            >
                                <span
                                    class="text-amber-900 dark:text-amber-200 font-semibold"
                                    >Hypertension</span
                                >
                                <span
                                    class="font-black text-amber-800 dark:text-amber-300"
                                    >{{
                                        patientAnalytics.medical_alerts
                                            .hypertension
                                    }}</span
                                >
                            </div>
                            <div
                                class="p-2 rounded-lg bg-amber-50 dark:bg-amber-950/40 border border-amber-100 dark:border-amber-900/50 flex justify-between items-center"
                            >
                                <span
                                    class="text-amber-900 dark:text-amber-200 font-semibold"
                                    >Diabetes</span
                                >
                                <span
                                    class="font-black text-amber-800 dark:text-amber-300"
                                    >{{
                                        patientAnalytics.medical_alerts.diabetes
                                    }}</span
                                >
                            </div>
                            <div
                                class="p-2 rounded-lg bg-rose-50 dark:bg-rose-950/40 border border-rose-100 dark:border-rose-900/50 flex justify-between items-center"
                            >
                                <span
                                    class="text-rose-900 dark:text-rose-200 font-semibold"
                                    >Cardiac</span
                                >
                                <span
                                    class="font-black text-rose-800 dark:text-rose-300"
                                    >{{
                                        patientAnalytics.medical_alerts
                                            .heart_disease
                                    }}</span
                                >
                            </div>
                            <div
                                class="p-2 rounded-lg bg-blue-50 dark:bg-blue-950/40 border border-blue-100 dark:border-blue-900/50 flex justify-between items-center"
                            >
                                <span
                                    class="text-blue-900 dark:text-blue-200 font-semibold"
                                    >Asthma</span
                                >
                                <span
                                    class="font-black text-blue-800 dark:text-blue-300"
                                    >{{
                                        patientAnalytics.medical_alerts.asthma
                                    }}</span
                                >
                            </div>
                            <div
                                class="p-2 rounded-lg bg-rose-50 dark:bg-rose-950/40 border border-rose-100 dark:border-rose-900/50 flex justify-between items-center"
                            >
                                <span
                                    class="text-rose-900 dark:text-rose-200 font-semibold"
                                    >Bleeding Risk</span
                                >
                                <span
                                    class="font-black text-rose-800 dark:text-rose-300"
                                    >{{
                                        patientAnalytics.medical_alerts
                                            .bleeding_disorders
                                    }}</span
                                >
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Patient Records Paginated Ledger (Screen Only) -->
                <div
                    class="print:hidden bg-white dark:bg-slate-900 rounded-2xl border border-gray-200 dark:border-slate-800 shadow-sm overflow-hidden"
                >
                    <div
                        class="p-5 border-b border-gray-200 dark:border-slate-800 flex flex-col sm:flex-row sm:items-center justify-between gap-3"
                    >
                        <div>
                            <h3
                                class="text-sm font-bold text-gray-900 dark:text-white uppercase tracking-wider"
                            >
                                Patient Master Records
                            </h3>
                            <p
                                class="text-xs text-gray-500 dark:text-slate-400"
                            >
                                Comprehensive registry with clinical visits and
                                medical alert tags
                            </p>
                        </div>
                        <div class="w-full sm:w-64">
                            <input
                                type="text"
                                v-model="searchQuery"
                                @keyup.enter="applyFilters"
                                placeholder="Search by name, ID, contact..."
                                class="w-full text-xs rounded-xl border-gray-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-gray-900 dark:text-slate-100 placeholder-gray-400 dark:placeholder-slate-500 py-2 px-3 focus:border-teal-500 focus:ring-teal-500"
                            />
                        </div>
                    </div>

                    <div class="overflow-x-auto">
                        <table
                            class="min-w-full divide-y divide-gray-200 dark:divide-slate-800 text-xs text-left"
                        >
                            <thead
                                class="bg-gray-50 dark:bg-slate-800 text-gray-500 dark:text-slate-400 font-semibold uppercase tracking-wider"
                            >
                                <tr>
                                    <th scope="col" class="px-5 py-3.5">
                                        Patient ID
                                    </th>
                                    <th scope="col" class="px-5 py-3.5">
                                        Full Name
                                    </th>
                                    <th scope="col" class="px-5 py-3.5">
                                        Age / Gender
                                    </th>
                                    <th scope="col" class="px-5 py-3.5">
                                        Contact Number
                                    </th>
                                    <th
                                        scope="col"
                                        class="px-5 py-3.5 text-center"
                                    >
                                        Visits
                                    </th>
                                    <th
                                        scope="col"
                                        class="px-5 py-3.5 text-center"
                                    >
                                        Treatments
                                    </th>
                                    <th scope="col" class="px-5 py-3.5">
                                        Medical Alerts
                                    </th>
                                    <th
                                        scope="col"
                                        class="px-5 py-3.5 text-right"
                                    >
                                        Registered
                                    </th>
                                </tr>
                            </thead>
                            <tbody
                                class="divide-y divide-gray-100 dark:divide-slate-800 bg-white dark:bg-slate-900"
                            >
                                <tr
                                    v-for="p in patientAnalytics.records.data"
                                    :key="p.id"
                                    class="hover:bg-gray-50/80 dark:hover:bg-slate-800/50 transition"
                                >
                                    <td
                                        class="px-5 py-3.5 font-mono font-bold text-teal-800 dark:text-teal-400"
                                    >
                                        {{ p.patient_id }}
                                    </td>
                                    <td
                                        class="px-5 py-3.5 font-bold text-gray-900 dark:text-white"
                                    >
                                        {{ p.full_name }}
                                    </td>
                                    <td
                                        class="px-5 py-3.5 text-gray-600 dark:text-slate-400"
                                    >
                                        {{ p.age ? `${p.age} yrs` : "N/A" }} •
                                        {{ p.gender || "N/A" }}
                                    </td>
                                    <td
                                        class="px-5 py-3.5 text-gray-600 dark:text-slate-400 font-mono"
                                    >
                                        {{ p.contact_number || "N/A" }}
                                    </td>
                                    <td
                                        class="px-5 py-3.5 text-center font-bold text-gray-800 dark:text-slate-200"
                                    >
                                        {{ p.appointments_count }}
                                    </td>
                                    <td
                                        class="px-5 py-3.5 text-center font-bold text-teal-800 dark:text-teal-400"
                                    >
                                        {{ p.treatments_count }}
                                    </td>
                                    <td class="px-5 py-3.5">
                                        <div
                                            v-if="
                                                p.critical_alerts &&
                                                p.critical_alerts.length > 0
                                            "
                                            class="flex flex-wrap gap-1"
                                        >
                                            <span
                                                v-for="(
                                                    alert, aIdx
                                                ) in p.critical_alerts"
                                                :key="aIdx"
                                                :class="[
                                                    alert.type === 'danger'
                                                        ? 'bg-rose-50 dark:bg-rose-950/60 text-rose-700 dark:text-rose-300 border-rose-200 dark:border-rose-800'
                                                        : 'bg-amber-50 dark:bg-amber-950/60 text-amber-800 dark:text-amber-300 border-amber-200 dark:border-amber-800',
                                                    'px-2 py-0.5 rounded text-[10px] font-semibold border',
                                                ]"
                                            >
                                                {{ alert.label }}
                                            </span>
                                        </div>
                                        <span
                                            v-else
                                            class="text-gray-400 dark:text-slate-500 text-[11px]"
                                            >None flagged</span
                                        >
                                    </td>
                                    <td
                                        class="px-5 py-3.5 text-right text-gray-500 dark:text-slate-400"
                                    >
                                        {{ p.registered_date }}
                                    </td>
                                </tr>
                                <tr
                                    v-if="
                                        !patientAnalytics.records?.data ||
                                        patientAnalytics.records.data.length ===
                                            0
                                    "
                                >
                                    <td
                                        colspan="8"
                                        class="px-6 py-8 text-center text-gray-400 dark:text-slate-500"
                                    >
                                        No patient records found matching query.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <div class="print:hidden">
                        <Pagination
                            :paginator="patientAnalytics.records"
                            label="patients"
                        />
                    </div>
                </div>
            </div>

            <!-- ========================================================================= -->
            <!-- TAB 3: APPOINTMENT REPORT                                                 -->
            <!-- ========================================================================= -->
            <div v-show="activeTab === 'appointments'" class="space-y-6">
                <!-- Professional Printable Appointment Report Table -->
                <AppointmentReportTable
                    :report="appointmentReport"
                    :grouping-mode="groupingMode"
                    :period-label="formatDateRangeLabel"
                />
            </div>

            <!-- ========================================================================= -->
            <!-- TAB 4: TREATMENT REPORT                                                   -->
            <!-- ========================================================================= -->
            <div v-show="activeTab === 'treatments'" class="space-y-6">
                <!-- Professional Printable Treatment Report Table -->
                <TreatmentReportTable
                    :report="treatmentReport"
                    :grouping-mode="groupingMode"
                    :period-label="formatDateRangeLabel"
                    :format-currency="formatCurrency"
                />

                <!-- Top Procedures & Dentist Performance (Screen Only) -->
                <div class="print:hidden grid grid-cols-1 lg:grid-cols-3 gap-6">
                    <!-- Top Procedures Leaderboard -->
                    <div
                        class="lg:col-span-2 bg-white dark:bg-slate-900 rounded-2xl border border-gray-200 dark:border-slate-800 p-6 shadow-sm space-y-4"
                    >
                        <div class="flex items-center justify-between">
                            <div>
                                <h3
                                    class="text-sm font-bold text-gray-900 dark:text-white uppercase tracking-wider"
                                >
                                    Clinical Procedures Ranking
                                </h3>
                                <p
                                    class="text-xs text-gray-500 dark:text-slate-400"
                                >
                                    Volume, generated revenue, and average
                                    procedure cost
                                </p>
                            </div>
                        </div>

                        <div class="overflow-x-auto">
                            <table
                                class="min-w-full divide-y divide-gray-100 dark:divide-slate-800 text-xs"
                            >
                                <thead>
                                    <tr
                                        class="text-gray-400 dark:text-slate-400 uppercase tracking-wider font-semibold text-left border-b border-gray-100 dark:border-slate-800"
                                    >
                                        <th class="pb-3">Procedure Name</th>
                                        <th class="pb-3 text-center">Volume</th>
                                        <th class="pb-3 text-center">Share</th>
                                        <th class="pb-3 text-right">Avg Fee</th>
                                        <th class="pb-3 text-right">
                                            Total Revenue
                                        </th>
                                    </tr>
                                </thead>
                                <tbody
                                    class="divide-y divide-gray-100 dark:divide-slate-800"
                                >
                                    <tr
                                        v-for="(
                                            proc, pIdx
                                        ) in treatmentAnalytics.top_procedures"
                                        :key="pIdx"
                                    >
                                        <td
                                            class="py-3 font-bold text-gray-900 dark:text-white"
                                        >
                                            {{ proc.procedure_name }}
                                        </td>
                                        <td
                                            class="py-3 text-center font-bold text-teal-800 dark:text-teal-400"
                                        >
                                            {{ proc.count }}
                                        </td>
                                        <td
                                            class="py-3 text-center font-mono text-gray-600 dark:text-slate-400"
                                        >
                                            {{ proc.percentage }}%
                                        </td>
                                        <td
                                            class="py-3 text-right text-gray-600 dark:text-slate-400"
                                        >
                                            {{ formatCurrency(proc.avg_cost) }}
                                        </td>
                                        <td
                                            class="py-3 text-right font-black text-teal-800 dark:text-teal-400"
                                        >
                                            {{
                                                formatCurrency(
                                                    proc.total_revenue,
                                                )
                                            }}
                                        </td>
                                    </tr>
                                    <tr
                                        v-if="
                                            !treatmentAnalytics.top_procedures ||
                                            treatmentAnalytics.top_procedures
                                                .length === 0
                                        "
                                    >
                                        <td
                                            colspan="5"
                                            class="py-6 text-center text-gray-400 dark:text-slate-500"
                                        >
                                            No procedures logged in period.
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Dentist Performance Card -->
                    <div
                        class="bg-white dark:bg-slate-900 rounded-2xl border border-gray-200 dark:border-slate-800 p-6 shadow-sm space-y-4"
                    >
                        <h3
                            class="text-sm font-bold text-gray-900 dark:text-white uppercase tracking-wider flex items-center gap-2"
                        >
                            <i
                                class="pi pi-user text-teal-700 dark:text-teal-400"
                            ></i>
                            Attending Dentist
                        </h3>

                        <div
                            v-for="(
                                d, dIdx
                            ) in treatmentAnalytics.dentist_performance"
                            :key="dIdx"
                            class="p-4 rounded-xl border border-teal-100 dark:border-teal-900/60 bg-teal-50/50 dark:bg-teal-950/40 space-y-2 transition-colors"
                        >
                            <h4
                                class="font-extrabold text-sm text-teal-950 dark:text-teal-200"
                            >
                                {{ d.name }}
                            </h4>
                            <div
                                class="flex justify-between text-xs text-teal-900 dark:text-teal-300"
                            >
                                <span>Procedures Performed:</span>
                                <strong class="font-bold">{{
                                    d.total_procedures
                                }}</strong>
                            </div>
                            <div
                                class="flex justify-between text-xs text-teal-900 dark:text-teal-300"
                            >
                                <span>Completed Operations:</span>
                                <strong class="font-bold">{{
                                    d.completed
                                }}</strong>
                            </div>
                            <div
                                class="flex justify-between text-xs text-teal-900 dark:text-teal-300 border-t border-teal-200/60 dark:border-teal-800/60 pt-2"
                            >
                                <span>Procedure Billings:</span>
                                <strong
                                    class="font-black text-teal-800 dark:text-teal-300 text-sm"
                                    >{{ formatCurrency(d.revenue) }}</strong
                                >
                            </div>
                        </div>

                        <!-- Top Treated Teeth -->
                        <div
                            class="pt-3 border-t border-gray-100 dark:border-slate-800"
                        >
                            <h4
                                class="text-xs font-bold text-gray-500 dark:text-slate-400 uppercase tracking-wider mb-2"
                            >
                                Frequently Treated Teeth
                            </h4>
                            <div class="flex flex-wrap gap-1.5">
                                <span
                                    v-for="tooth in treatmentAnalytics.tooth_distribution"
                                    :key="tooth.tooth_number"
                                    class="px-2.5 py-1 rounded-md bg-gray-100 dark:bg-slate-800 text-gray-700 dark:text-slate-300 font-mono text-xs font-bold border border-gray-200 dark:border-slate-700"
                                >
                                    Tooth #{{ tooth.tooth_number }} ({{
                                        tooth.count
                                    }}x)
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Detailed Treatments Ledger (Screen Only) -->
                <div
                    class="print:hidden bg-white dark:bg-slate-900 rounded-2xl border border-gray-200 dark:border-slate-800 shadow-sm overflow-hidden"
                >
                    <div
                        class="p-5 border-b border-gray-200 dark:border-slate-800 flex flex-col sm:flex-row sm:items-center justify-between gap-3"
                    >
                        <div>
                            <h3
                                class="text-sm font-bold text-gray-900 dark:text-white uppercase tracking-wider"
                            >
                                Clinical Procedures Ledger
                            </h3>
                            <p
                                class="text-xs text-gray-500 dark:text-slate-400"
                            >
                                Chronological treatment records and diagnostic
                                findings
                            </p>
                        </div>
                        <div class="w-full sm:w-64">
                            <input
                                type="text"
                                v-model="searchQuery"
                                @keyup.enter="applyFilters"
                                placeholder="Search procedure, patient, diagnosis..."
                                class="w-full text-xs rounded-xl border-gray-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-gray-900 dark:text-slate-100 placeholder-gray-400 dark:placeholder-slate-500 py-2 px-3 focus:border-teal-500 focus:ring-teal-500"
                            />
                        </div>
                    </div>

                    <div class="overflow-x-auto">
                        <table
                            class="min-w-full divide-y divide-gray-200 dark:divide-slate-800 text-xs text-left"
                        >
                            <thead
                                class="bg-gray-50 dark:bg-slate-800 text-gray-500 dark:text-slate-400 font-semibold uppercase tracking-wider"
                            >
                                <tr>
                                    <th scope="col" class="px-5 py-3.5">
                                        Date
                                    </th>
                                    <th scope="col" class="px-5 py-3.5">
                                        Patient Name
                                    </th>
                                    <th scope="col" class="px-5 py-3.5">
                                        Procedure
                                    </th>
                                    <th
                                        scope="col"
                                        class="px-5 py-3.5 text-center"
                                    >
                                        Tooth #
                                    </th>
                                    <th scope="col" class="px-5 py-3.5">
                                        Attending Dentist
                                    </th>
                                    <th scope="col" class="px-5 py-3.5">
                                        Diagnosis
                                    </th>
                                    <th
                                        scope="col"
                                        class="px-5 py-3.5 text-center"
                                    >
                                        Status
                                    </th>
                                    <th
                                        scope="col"
                                        class="px-5 py-3.5 text-right"
                                    >
                                        Fee
                                    </th>
                                </tr>
                            </thead>
                            <tbody
                                class="divide-y divide-gray-100 dark:divide-slate-800 bg-white dark:bg-slate-900"
                            >
                                <tr
                                    v-for="t in treatmentAnalytics.records.data"
                                    :key="t.id"
                                    class="hover:bg-gray-50/80 dark:hover:bg-slate-800/50 transition"
                                >
                                    <td
                                        class="px-5 py-3.5 text-gray-500 dark:text-slate-400 whitespace-nowrap"
                                    >
                                        {{ t.treatment_date }}
                                    </td>
                                    <td
                                        class="px-5 py-3.5 font-bold text-gray-900 dark:text-white"
                                    >
                                        {{ t.patient_name }}
                                    </td>
                                    <td
                                        class="px-5 py-3.5 font-semibold text-teal-800 dark:text-teal-400"
                                    >
                                        {{ t.procedure_name }}
                                    </td>
                                    <td
                                        class="px-5 py-3.5 text-center font-mono font-bold text-gray-700 dark:text-slate-300"
                                    >
                                        {{ t.tooth_number || "—" }}
                                    </td>
                                    <td
                                        class="px-5 py-3.5 text-gray-700 dark:text-slate-300"
                                    >
                                        {{ t.dentist_name }}
                                    </td>
                                    <td
                                        class="px-5 py-3.5 text-gray-600 dark:text-slate-400 max-w-xs truncate"
                                        :title="t.diagnosis"
                                    >
                                        {{ t.diagnosis || "None noted" }}
                                    </td>
                                    <td class="px-5 py-3.5 text-center">
                                        <span
                                            :class="[
                                                t.status === 'completed'
                                                    ? 'bg-emerald-50 dark:bg-emerald-950/60 text-emerald-700 dark:text-emerald-300 border-emerald-200 dark:border-emerald-800'
                                                    : 'bg-blue-50 dark:bg-blue-950/60 text-blue-700 dark:text-blue-300 border-blue-200 dark:border-blue-800',
                                                'px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wider border',
                                            ]"
                                        >
                                            {{ t.status }}
                                        </span>
                                    </td>
                                    <td
                                        class="px-5 py-3.5 text-right font-black text-gray-900 dark:text-white"
                                    >
                                        {{ formatCurrency(t.cost) }}
                                    </td>
                                </tr>
                                <tr
                                    v-if="
                                        !treatmentAnalytics.records?.data ||
                                        treatmentAnalytics.records.data
                                            .length === 0
                                    "
                                >
                                    <td
                                        colspan="8"
                                        class="px-6 py-8 text-center text-gray-400 dark:text-slate-500"
                                    >
                                        No clinical treatment records found
                                        matching criteria.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <div class="print:hidden">
                        <Pagination
                            :paginator="treatmentAnalytics.records"
                            label="treatments"
                        />
                    </div>
                </div>
            </div>

            <!-- ========================================================================= -->
            <!-- TAB 5: REVENUE REPORT                                                     -->
            <!-- ========================================================================= -->
            <div v-show="activeTab === 'revenue'" class="space-y-6">
                <!-- Professional Printable Revenue Report Table -->
                <RevenueReportTable
                    :report="revenueReport"
                    :grouping-mode="groupingMode"
                    :period-label="formatDateRangeLabel"
                    :format-currency="formatCurrency"
                />
            </div>

            <!-- ========================================================================= -->
            <!-- TAB 6: INVOICING & ACCOUNTS RECEIVABLE LEDGER                             -->
            <!-- ========================================================================= -->
            <div v-show="activeTab === 'billing'" class="space-y-6">
                <!-- Financial Overview Cards -->
                <div
                    class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4"
                >
                    <div
                        class="bg-white dark:bg-slate-900 rounded-2xl border border-gray-200 dark:border-slate-800 p-4 shadow-xs"
                    >
                        <span
                            class="text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-slate-400"
                            >Gross Invoiced</span
                        >
                        <p
                            class="text-2xl font-black text-gray-900 dark:text-white mt-1"
                        >
                            {{ formatCurrency(billingAnalytics.gross_amount) }}
                        </p>
                        <p
                            class="text-[11px] text-gray-500 dark:text-slate-400 mt-1"
                        >
                            {{ billingAnalytics.total_invoices }} invoices
                            generated
                        </p>
                    </div>

                    <div
                        class="bg-white dark:bg-slate-900 rounded-2xl border border-gray-200 dark:border-slate-800 p-4 shadow-xs"
                    >
                        <span
                            class="text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-slate-400"
                            >Discounts & Deductions</span
                        >
                        <p
                            class="text-2xl font-black text-rose-700 dark:text-rose-400 mt-1"
                        >
                            -{{
                                formatCurrency(billingAnalytics.discount_amount)
                            }}
                        </p>
                        <p
                            class="text-[11px] text-gray-500 dark:text-slate-400 mt-1"
                        >
                            Special courtesies & promos
                        </p>
                    </div>

                    <div
                        class="bg-white rounded-2xl border border-emerald-200 dark:border-emerald-800/80 bg-emerald-50/30 dark:bg-slate-900 p-4 shadow-xs"
                    >
                        <span
                            class="text-xs font-bold uppercase tracking-wider text-emerald-800 dark:text-emerald-400"
                            >Actual Collections</span
                        >
                        <p
                            class="text-2xl font-black text-emerald-800 dark:text-emerald-400 mt-1"
                        >
                            {{
                                formatCurrency(billingAnalytics.total_collected)
                            }}
                        </p>
                        <p
                            class="text-[11px] text-emerald-700 dark:text-emerald-400 font-semibold mt-1"
                        >
                            Confirmed payments received
                        </p>
                    </div>

                    <div
                        class="bg-white rounded-2xl border border-amber-200 dark:border-amber-800/80 bg-amber-50/30 dark:bg-slate-900 p-4 shadow-xs"
                    >
                        <span
                            class="text-xs font-bold uppercase tracking-wider text-amber-800 dark:text-amber-400"
                            >Outstanding Balance</span
                        >
                        <p
                            class="text-2xl font-black text-amber-900 dark:text-amber-300 mt-1"
                        >
                            {{
                                formatCurrency(billingAnalytics.balance_amount)
                            }}
                        </p>
                        <p
                            class="text-[11px] text-amber-700 dark:text-amber-400 font-semibold mt-1"
                        >
                            Accounts receivable
                        </p>
                    </div>
                </div>

                <!-- Aging of Receivables & Invoice Status Breakdown -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <!-- Aging of Accounts Receivable -->
                    <div
                        class="bg-white dark:bg-slate-900 rounded-2xl border border-gray-200 dark:border-slate-800 p-6 shadow-sm space-y-4"
                    >
                        <h3
                            class="text-sm font-bold text-gray-900 dark:text-white uppercase tracking-wider flex items-center gap-2"
                        >
                            <i
                                class="pi pi-clock text-teal-700 dark:text-teal-400"
                            ></i>
                            Receivables Aging Analysis
                        </h3>

                        <div class="grid grid-cols-3 gap-3">
                            <div
                                class="p-3.5 rounded-xl border border-gray-100 dark:border-slate-800 bg-gray-50 dark:bg-slate-800/90 text-center transition-colors"
                            >
                                <span
                                    class="text-[11px] font-bold text-gray-500 dark:text-slate-400 uppercase"
                                    >Current (0-30 Days)</span
                                >
                                <p
                                    class="text-base font-black text-gray-900 dark:text-white mt-1"
                                >
                                    {{
                                        formatCurrency(
                                            billingAnalytics.aging.current,
                                        )
                                    }}
                                </p>
                            </div>
                            <div
                                class="p-3.5 rounded-xl border border-amber-100 dark:border-amber-900/50 bg-amber-50 dark:bg-amber-950/40 text-center transition-colors"
                            >
                                <span
                                    class="text-[11px] font-bold text-amber-700 dark:text-amber-400 uppercase"
                                    >31 - 60 Days Due</span
                                >
                                <p
                                    class="text-base font-black text-amber-900 dark:text-amber-300 mt-1"
                                >
                                    {{
                                        formatCurrency(
                                            billingAnalytics.aging.overdue_30,
                                        )
                                    }}
                                </p>
                            </div>
                            <div
                                class="p-3.5 rounded-xl border border-rose-100 dark:border-rose-900/50 bg-rose-50 dark:bg-rose-950/40 text-center transition-colors"
                            >
                                <span
                                    class="text-[11px] font-bold text-rose-700 dark:text-rose-400 uppercase"
                                    >>60 Days Due</span
                                >
                                <p
                                    class="text-base font-black text-rose-900 dark:text-rose-300 mt-1"
                                >
                                    {{
                                        formatCurrency(
                                            billingAnalytics.aging.overdue_60,
                                        )
                                    }}
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Invoice Status Breakdown -->
                    <div
                        class="bg-white dark:bg-slate-900 rounded-2xl border border-gray-200 dark:border-slate-800 p-6 shadow-sm space-y-4"
                    >
                        <h3
                            class="text-sm font-bold text-gray-900 dark:text-white uppercase tracking-wider flex items-center gap-2"
                        >
                            <i
                                class="pi pi-receipt text-teal-700 dark:text-teal-400"
                            ></i>
                            Invoicing Status Audit
                        </h3>

                        <div class="grid grid-cols-2 gap-3 text-xs">
                            <div
                                class="p-3 rounded-xl border border-emerald-100 dark:border-emerald-900/50 bg-emerald-50 dark:bg-emerald-950/40 flex justify-between items-center transition-colors"
                            >
                                <div>
                                    <span
                                        class="font-bold text-emerald-900 dark:text-emerald-200"
                                        >Fully Paid</span
                                    >
                                    <p
                                        class="text-[11px] text-emerald-700 dark:text-emerald-400"
                                    >
                                        {{
                                            billingAnalytics.status_breakdown
                                                .paid.count
                                        }}
                                        invoices
                                    </p>
                                </div>
                                <span
                                    class="font-black text-emerald-800 dark:text-emerald-300"
                                    >{{
                                        formatCurrency(
                                            billingAnalytics.status_breakdown
                                                .paid.amount,
                                        )
                                    }}</span
                                >
                            </div>

                            <div
                                class="p-3 rounded-xl border border-amber-100 dark:border-amber-900/50 bg-amber-50 dark:bg-amber-950/40 flex justify-between items-center transition-colors"
                            >
                                <div>
                                    <span
                                        class="font-bold text-amber-900 dark:text-amber-200"
                                        >Partially Paid</span
                                    >
                                    <p
                                        class="text-[11px] text-amber-700 dark:text-amber-400"
                                    >
                                        {{
                                            billingAnalytics.status_breakdown
                                                .partially_paid.count
                                        }}
                                        invoices
                                    </p>
                                </div>
                                <span
                                    class="font-black text-amber-800 dark:text-amber-300"
                                    >{{
                                        formatCurrency(
                                            billingAnalytics.status_breakdown
                                                .partially_paid.amount,
                                        )
                                    }}</span
                                >
                            </div>

                            <div
                                class="p-3 rounded-xl border border-rose-100 dark:border-rose-900/50 bg-rose-50 dark:bg-rose-950/40 flex justify-between items-center transition-colors"
                            >
                                <div>
                                    <span
                                        class="font-bold text-rose-900 dark:text-rose-200"
                                        >Unpaid</span
                                    >
                                    <p
                                        class="text-[11px] text-rose-700 dark:text-rose-400"
                                    >
                                        {{
                                            billingAnalytics.status_breakdown
                                                .unpaid.count
                                        }}
                                        invoices
                                    </p>
                                </div>
                                <span
                                    class="font-black text-rose-800 dark:text-rose-300"
                                    >{{
                                        formatCurrency(
                                            billingAnalytics.status_breakdown
                                                .unpaid.amount,
                                        )
                                    }}</span
                                >
                            </div>

                            <div
                                class="p-3 rounded-xl border border-gray-100 dark:border-slate-800 bg-gray-50 dark:bg-slate-800/80 flex justify-between items-center transition-colors"
                            >
                                <div>
                                    <span
                                        class="font-bold text-gray-700 dark:text-slate-300"
                                        >Cancelled</span
                                    >
                                    <p
                                        class="text-[11px] text-gray-500 dark:text-slate-400"
                                    >
                                        {{
                                            billingAnalytics.status_breakdown
                                                .cancelled.count
                                        }}
                                        invoices
                                    </p>
                                </div>
                                <span
                                    class="font-black text-gray-700 dark:text-slate-300"
                                    >{{
                                        formatCurrency(
                                            billingAnalytics.status_breakdown
                                                .cancelled.amount,
                                        )
                                    }}</span
                                >
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Detailed Invoices Ledger -->
                <div
                    class="bg-white dark:bg-slate-900 rounded-2xl border border-gray-200 dark:border-slate-800 shadow-sm overflow-hidden"
                >
                    <div
                        class="p-5 border-b border-gray-200 dark:border-slate-800 flex flex-col sm:flex-row sm:items-center justify-between gap-3"
                    >
                        <div>
                            <h3
                                class="text-sm font-bold text-gray-900 dark:text-white uppercase tracking-wider"
                            >
                                Billing & Invoice Ledger
                            </h3>
                            <p
                                class="text-xs text-gray-500 dark:text-slate-400"
                            >
                                Official statement records and accounts
                                settlement details
                            </p>
                        </div>
                        <div class="w-full sm:w-64">
                            <input
                                type="text"
                                v-model="searchQuery"
                                @keyup.enter="applyFilters"
                                placeholder="Search invoice #, patient..."
                                class="w-full text-xs rounded-xl border-gray-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-gray-900 dark:text-slate-100 placeholder-gray-400 dark:placeholder-slate-500 py-2 px-3 focus:border-teal-500 focus:ring-teal-500"
                            />
                        </div>
                    </div>

                    <div class="overflow-x-auto">
                        <table
                            class="min-w-full divide-y divide-gray-200 dark:divide-slate-800 text-xs text-left"
                        >
                            <thead
                                class="bg-gray-50 dark:bg-slate-800 text-gray-500 dark:text-slate-400 font-semibold uppercase tracking-wider"
                            >
                                <tr>
                                    <th scope="col" class="px-5 py-3.5">
                                        Invoice #
                                    </th>
                                    <th scope="col" class="px-5 py-3.5">
                                        Date Issued
                                    </th>
                                    <th scope="col" class="px-5 py-3.5">
                                        Patient Name
                                    </th>
                                    <th
                                        scope="col"
                                        class="px-5 py-3.5 text-right"
                                    >
                                        Gross
                                    </th>
                                    <th
                                        scope="col"
                                        class="px-5 py-3.5 text-right"
                                    >
                                        Discount
                                    </th>
                                    <th
                                        scope="col"
                                        class="px-5 py-3.5 text-right"
                                    >
                                        Net Total
                                    </th>
                                    <th
                                        scope="col"
                                        class="px-5 py-3.5 text-right"
                                    >
                                        Paid
                                    </th>
                                    <th
                                        scope="col"
                                        class="px-5 py-3.5 text-right"
                                    >
                                        Balance
                                    </th>
                                    <th
                                        scope="col"
                                        class="px-5 py-3.5 text-center"
                                    >
                                        Status
                                    </th>
                                </tr>
                            </thead>
                            <tbody
                                class="divide-y divide-gray-100 dark:divide-slate-800 bg-white dark:bg-slate-900"
                            >
                                <tr
                                    v-for="inv in billingAnalytics.records.data"
                                    :key="inv.id"
                                    class="hover:bg-gray-50/80 dark:hover:bg-slate-800/50 transition"
                                >
                                    <td
                                        class="px-5 py-3.5 font-mono font-bold text-teal-800 dark:text-teal-400"
                                    >
                                        {{ inv.invoice_number }}
                                    </td>
                                    <td
                                        class="px-5 py-3.5 text-gray-500 dark:text-slate-400 whitespace-nowrap"
                                    >
                                        {{ inv.invoice_date }}
                                    </td>
                                    <td
                                        class="px-5 py-3.5 font-bold text-gray-900 dark:text-white"
                                    >
                                        {{ inv.patient_name }}
                                    </td>
                                    <td
                                        class="px-5 py-3.5 text-right text-gray-600 dark:text-slate-400"
                                    >
                                        {{ formatCurrency(inv.total_amount) }}
                                    </td>
                                    <td
                                        class="px-5 py-3.5 text-right text-rose-600 dark:text-rose-400 font-semibold"
                                    >
                                        {{
                                            inv.discount > 0
                                                ? `-${formatCurrency(inv.discount)}`
                                                : "₱0.00"
                                        }}
                                    </td>
                                    <td
                                        class="px-5 py-3.5 text-right font-black text-gray-900 dark:text-white"
                                    >
                                        {{ formatCurrency(inv.net_amount) }}
                                    </td>
                                    <td
                                        class="px-5 py-3.5 text-right font-bold text-emerald-800 dark:text-emerald-400"
                                    >
                                        {{ formatCurrency(inv.paid_amount) }}
                                    </td>
                                    <td
                                        class="px-5 py-3.5 text-right font-bold"
                                        :class="
                                            inv.balance > 0
                                                ? 'text-amber-800 dark:text-amber-400'
                                                : 'text-gray-400 dark:text-slate-500'
                                        "
                                    >
                                        {{ formatCurrency(inv.balance) }}
                                    </td>
                                    <td class="px-5 py-3.5 text-center">
                                        <span
                                            :class="[
                                                inv.status === 'paid'
                                                    ? 'bg-emerald-50 dark:bg-emerald-950/60 text-emerald-700 dark:text-emerald-300 border-emerald-200 dark:border-emerald-800'
                                                    : inv.status ===
                                                        'partially_paid'
                                                      ? 'bg-amber-50 dark:bg-amber-950/60 text-amber-700 dark:text-amber-300 border-amber-200 dark:border-amber-800'
                                                      : inv.status ===
                                                          'cancelled'
                                                        ? 'bg-gray-100 dark:bg-slate-800 text-gray-700 dark:text-slate-300 border-gray-200 dark:border-slate-700'
                                                        : 'bg-rose-50 dark:bg-rose-950/60 text-rose-700 dark:text-rose-300 border-rose-200 dark:border-rose-800',
                                                'px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wider border',
                                            ]"
                                        >
                                            {{ inv.status.replace("_", " ") }}
                                        </span>
                                    </td>
                                </tr>
                                <tr
                                    v-if="
                                        !billingAnalytics.records?.data ||
                                        billingAnalytics.records.data.length ===
                                            0
                                    "
                                >
                                    <td
                                        colspan="9"
                                        class="px-6 py-8 text-center text-gray-400 dark:text-slate-500"
                                    >
                                        No billing records found matching
                                        criteria.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <div class="print:hidden">
                        <Pagination
                            :paginator="billingAnalytics.records"
                            label="invoices"
                        />
                    </div>
                </div>
            </div>

            <!-- Print Footer Signature Area (Only visible on printed paper) -->
            <div class="hidden print:block mt-8 pt-6 border-t-2 border-gray-400 print-avoid-break">
                <div class="grid grid-cols-2 gap-12 text-xs">
                    <div>
                        <p class="font-bold text-gray-700">Prepared by:</p>
                        <div class="mt-8 border-b border-gray-400 w-48"></div>
                        <p class="mt-1 font-semibold text-gray-800">
                            {{ clinicInfo.generated_by }}
                        </p>
                        <p class="text-gray-500">Clinic Administration</p>
                    </div>
                    <div class="text-right">
                        <p class="font-bold text-gray-700">
                            Certified by Attending Dentist:
                        </p>
                        <div
                            class="mt-8 border-b border-gray-400 w-48 ml-auto"
                        ></div>
                        <p class="mt-1 font-semibold text-gray-800">
                            {{ clinicInfo.dentist }}
                        </p>
                        <p class="text-gray-500">
                            Lead Dental Clinician / Clinic Director
                        </p>
                    </div>
                </div>
                <p class="text-[10px] text-gray-400 text-center mt-6">
                    Vitality Smile Dental Clinic Automated Management Information System • Official Operational & Financial Document
                </p>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<style scoped>
@media print {
    @page {
        size: A4 portrait;
        margin: 12mm 15mm 15mm 15mm;
    }

    body {
        background-color: white !important;
        color: #111827 !important;
        font-size: 10pt !important;
        -webkit-print-color-adjust: exact !important;
        print-color-adjust: exact !important;
    }

    table {
        width: 100% !important;
        border-collapse: collapse !important;
        page-break-inside: auto;
    }

    thead {
        display: table-header-group !important;
    }

    tfoot {
        display: table-footer-group !important;
    }

    tr {
        page-break-inside: avoid !important;
        break-inside: avoid !important;
    }

    .print-avoid-break {
        page-break-inside: avoid !important;
        break-inside: avoid !important;
    }
}
</style>
