<script setup>
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import { Head, Link, router } from "@inertiajs/vue3";
import { ref, watch, computed } from "vue";

const props = defineProps({
    payments: Object,
    filters: {
        type: Object,
        default: () => ({}),
    },
    metrics: {
        type: Object,
        default: () => ({
            total_collected: 0,
            today_collected: 0,
            month_collected: 0,
            total_transactions: 0,
        }),
    },
    patient: Object,
    isPatient: Boolean,
});

// Patient view search & filter state
const patientSearch = ref("");
const patientMethodTab = ref("all");

const patientFilteredPayments = computed(() => {
    let list = props.payments?.data || [];
    if (patientMethodTab.value !== "all") {
        list = list.filter((p) => (p.payment_method || "").toLowerCase() === patientMethodTab.value.toLowerCase());
    }
    if (patientSearch.value.trim()) {
        const q = patientSearch.value.toLowerCase();
        list = list.filter((p) => {
            const num = p.payment_number?.toLowerCase().includes(q);
            const refNum = p.reference_number?.toLowerCase().includes(q);
            const method = p.payment_method?.toLowerCase().includes(q);
            const inv = p.invoice?.invoice_number?.toLowerCase().includes(q);
            const amt = p.amount?.toString().includes(q);
            return num || refNum || method || inv || amt;
        });
    }
    return list;
});

const patientMetrics = computed(() => {
    const list = props.payments?.data || [];
    const total = list.reduce((sum, p) => sum + (parseFloat(p.amount) || 0), 0);
    return {
        total_amount: total,
        receipts_count: list.length,
        latest_date: list[0]?.payment_date || null,
    };
});

// Search and filter states
const search = ref(props.filters.search || "");
const paymentMethod = ref(props.filters.payment_method || "");
const dateFrom = ref(props.filters.date_from || "");
const dateTo = ref(props.filters.date_to || "");

let searchTimeout = null;
watch([search, paymentMethod, dateFrom, dateTo], ([newSearch, newMethod, newFrom, newTo]) => {
    if (props.isPatient) return;
    if (searchTimeout) clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        router.get(
            route("payments.index"),
            {
                search: newSearch,
                payment_method: newMethod,
                date_from: newFrom,
                date_to: newTo,
            },
            { preserveState: true, replace: true }
        );
    }, 300);
});

const resetFilters = () => {
    search.value = "";
    paymentMethod.value = "";
    dateFrom.value = "";
    dateTo.value = "";
};

const deletePayment = (id) => {
    if (confirm("Are you sure you want to void/delete this payment receipt? The associated invoice balance will be updated.")) {
        router.delete(route("payments.destroy", id), {
            preserveScroll: true,
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
    return "₱" + num.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
};
</script>

<template>
    <Head title="Payment Receipts & Ledger" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <div class="flex items-center gap-2">
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full bg-teal-50 border border-teal-200 text-teal-800 text-[11px] font-bold uppercase tracking-wider">
                            <i class="pi pi-receipt text-[10px]"></i>
                            <span>{{ isPatient ? "Patient Receipts" : "Collections Ledger" }}</span>
                        </span>
                        <span class="text-xs text-gray-400">•</span>
                        <span class="text-xs font-medium text-gray-500">Vitality Smile Clinic</span>
                    </div>
                    <h2 class="mt-1 text-2xl font-extrabold text-gray-900 tracking-tight">
                        {{ isPatient ? "My Payment Receipts" : "Official Receipts & Collections" }}
                    </h2>
                    <p class="mt-0.5 text-xs sm:text-sm text-gray-500">
                        {{
                            isPatient
                                ? "Review official payment receipts issued for your dental consultations and procedures."
                                : "Track daily cash flow, digital payments, and transaction history."
                        }}
                    </p>
                </div>

                <div class="flex items-center gap-3">
                    <Link
                        v-if="!isPatient"
                        :href="route('billing.index')"
                        class="inline-flex items-center justify-center rounded-lg border border-gray-300 bg-white px-3.5 py-2 text-sm font-semibold text-gray-700 shadow-sm hover:bg-gray-50 transition"
                    >
                        <i class="pi pi-file mr-2 text-xs"></i>
                        Invoices Ledger
                    </Link>
                    <Link
                        v-if="!isPatient && ['admin', 'secretary', 'dentist'].includes($page.props.auth.user.role)"
                        :href="route('billing.create')"
                        class="inline-flex items-center justify-center rounded-lg bg-teal-700 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-teal-800"
                    >
                        <i class="pi pi-plus mr-2 text-xs"></i>
                        Create Invoice
                    </Link>
                </div>
            </div>
        </template>

        <div class="py-6 sm:py-8">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 space-y-6">

                <!-- Patient Shared Cross-Navigation Bar -->
                <div v-if="isPatient" class="flex items-center gap-2 overflow-x-auto pb-2 scrollbar-none border-b border-gray-200">
                    <Link
                        :href="route('appointments.index')"
                        class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl text-xs font-bold transition whitespace-nowrap bg-white text-gray-600 hover:bg-gray-100 border border-gray-200/80"
                    >
                        <i class="pi pi-calendar text-xs"></i>
                        <span>My Appointments</span>
                    </Link>
                    <Link
                        :href="route('treatments.index')"
                        class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl text-xs font-bold transition whitespace-nowrap bg-white text-gray-600 hover:bg-gray-100 border border-gray-200/80"
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
                        class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl text-xs font-bold transition whitespace-nowrap bg-teal-700 text-white shadow-sm"
                    >
                        <i class="pi pi-receipt text-xs"></i>
                        <span>My Receipts</span>
                    </Link>
                </div>

                <!-- 1. PATIENT VIEW -->
                <div v-if="isPatient" class="space-y-6">
                    <!-- Patient Summary Cards -->
                    <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4">
                        <div class="rounded-2xl border border-gray-200/80 bg-white p-4 sm:p-5 shadow-xs">
                            <span class="text-xs font-semibold uppercase tracking-wider text-emerald-700">Total Settled</span>
                            <p class="mt-2 text-2xl font-extrabold text-emerald-700">{{ formatCurrency(patientMetrics.total_amount) }}</p>
                            <p class="mt-0.5 text-[11px] text-gray-400">Total payments made</p>
                        </div>

                        <div class="rounded-2xl border border-gray-200/80 bg-white p-4 sm:p-5 shadow-xs">
                            <span class="text-xs font-semibold uppercase tracking-wider text-gray-500">Receipts Count</span>
                            <p class="mt-2 text-2xl font-extrabold text-gray-900">{{ patientMetrics.receipts_count }}</p>
                            <p class="mt-0.5 text-[11px] text-gray-400">Official vouchers issued</p>
                        </div>

                        <div class="rounded-2xl border border-gray-200/80 bg-white p-4 sm:p-5 shadow-xs">
                            <span class="text-xs font-semibold uppercase tracking-wider text-gray-500">Latest Payment</span>
                            <p class="mt-2 text-lg sm:text-xl font-bold text-gray-900 truncate">{{ formatDate(patientMetrics.latest_date) }}</p>
                            <p class="mt-0.5 text-[11px] text-gray-400">Most recent transaction</p>
                        </div>

                        <div class="rounded-2xl border border-gray-200/80 bg-white p-4 sm:p-5 shadow-xs">
                            <span class="text-xs font-semibold uppercase tracking-wider text-teal-700">Audit Status</span>
                            <p class="mt-2 text-lg sm:text-xl font-extrabold text-teal-800 flex items-center gap-1.5">
                                <i class="pi pi-verified text-teal-600 text-base"></i> Verified
                            </p>
                            <p class="mt-0.5 text-[11px] text-gray-400">100% Tax & Clinic Cleared</p>
                        </div>
                    </div>

                    <!-- Receipts Filter Toolbar & List Card -->
                    <div class="rounded-3xl border border-gray-200/90 bg-white shadow-sm overflow-hidden">
                        <div class="p-4 sm:p-5 border-b border-gray-100 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                            <!-- Method Tabs -->
                            <div class="flex items-center gap-1.5 overflow-x-auto pb-1 md:pb-0 scrollbar-none">
                                <button
                                    type="button"
                                    @click="patientMethodTab = 'all'"
                                    :class="[
                                        'px-3.5 py-1.5 text-xs font-bold rounded-xl transition cursor-pointer whitespace-nowrap',
                                        patientMethodTab === 'all'
                                            ? 'bg-teal-700 text-white shadow-xs'
                                            : 'bg-gray-100 text-gray-600 hover:bg-gray-200/80'
                                    ]"
                                >
                                    All Receipts ({{ patientMetrics.receipts_count }})
                                </button>
                                <button
                                    type="button"
                                    @click="patientMethodTab = 'cash'"
                                    :class="[
                                        'px-3.5 py-1.5 text-xs font-bold rounded-xl transition cursor-pointer whitespace-nowrap',
                                        patientMethodTab === 'cash'
                                            ? 'bg-teal-700 text-white shadow-xs'
                                            : 'bg-gray-100 text-gray-600 hover:bg-gray-200/80'
                                    ]"
                                >
                                    Cash
                                </button>
                                <button
                                    type="button"
                                    @click="patientMethodTab = 'gcash'"
                                    :class="[
                                        'px-3.5 py-1.5 text-xs font-bold rounded-xl transition cursor-pointer whitespace-nowrap',
                                        patientMethodTab === 'gcash'
                                            ? 'bg-teal-700 text-white shadow-xs'
                                            : 'bg-gray-100 text-gray-600 hover:bg-gray-200/80'
                                    ]"
                                >
                                    GCash
                                </button>
                            </div>

                            <!-- Search -->
                            <div class="relative w-full md:w-72">
                                <i class="pi pi-search absolute left-3.5 top-1/2 -translate-y-1/2 text-gray-400 text-xs"></i>
                                <input
                                    v-model="patientSearch"
                                    type="text"
                                    placeholder="Search receipt #, ref, amount..."
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

                        <!-- Receipts Feed -->
                        <div v-if="patientFilteredPayments.length > 0" class="divide-y divide-gray-100 p-4 sm:p-6 space-y-4">
                            <div
                                v-for="p in patientFilteredPayments"
                                :key="p.id"
                                class="rounded-2xl border border-gray-200/80 bg-white p-5 sm:p-6 transition hover:shadow-md hover:border-teal-300 flex flex-col sm:flex-row sm:items-center justify-between gap-4"
                            >
                                <div class="space-y-1.5">
                                    <div class="flex items-center gap-3">
                                        <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-emerald-50 text-emerald-700 border border-emerald-100">
                                            <i class="pi pi-receipt text-sm"></i>
                                        </div>
                                        <div>
                                            <div class="flex items-center gap-2">
                                                <span class="font-mono font-extrabold text-teal-900 text-base">{{ p.payment_number }}</span>
                                                <span class="px-2.5 py-0.5 rounded-full text-xs font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                                    {{ p.payment_method }}
                                                </span>
                                            </div>
                                            <p class="text-xs text-gray-500 mt-0.5">
                                                Settlement Date: <strong>{{ formatDate(p.payment_date) }}</strong>
                                                <span v-if="p.reference_number" class="ml-1 text-gray-400">• Ref: {{ p.reference_number }}</span>
                                            </p>
                                        </div>
                                    </div>

                                    <p v-if="p.invoice?.treatment?.procedure_name || p.invoice?.appointment?.service || p.invoice?.items?.[0]?.description" class="text-xs font-semibold text-teal-800 dark:text-teal-300 pl-13 flex items-center gap-1.5">
                                        <i class="pi pi-heart text-teal-600 text-[11px]"></i>
                                        <span>Service: {{ p.invoice?.treatment?.procedure_name || p.invoice?.appointment?.service || p.invoice?.items?.[0]?.description }}</span>
                                    </p>

                                    <p v-if="p.invoice" class="text-xs text-gray-600 pl-13">
                                        Applied to Official Statement:
                                        <Link :href="route('billing.show', p.invoice.id)" class="font-bold text-teal-700 hover:underline">
                                            {{ p.invoice.invoice_number }}
                                        </Link>
                                    </p>
                                </div>

                                <div class="flex items-center gap-5 self-end sm:self-auto">
                                    <div class="text-right">
                                        <span class="text-xs text-gray-400 font-medium block">Amount Settled</span>
                                        <span class="text-xl sm:text-2xl font-extrabold text-emerald-700">{{ formatCurrency(p.amount) }}</span>
                                        <div v-if="p.amount_tendered && Number(p.amount_tendered) > Number(p.amount)" class="text-[11px] text-gray-500 font-medium mt-0.5">
                                            Given: {{ formatCurrency(p.amount_tendered) }} • Change: {{ formatCurrency(p.change_amount || 0) }}
                                        </div>
                                    </div>

                                    <div class="flex items-center gap-2">
                                        <Link
                                            :href="route('payments.receipt', p.id)"
                                            target="_blank"
                                            class="inline-flex items-center gap-1.5 px-3 py-2 rounded-xl bg-teal-700 text-white text-xs font-bold hover:bg-teal-800 transition shadow-2xs"
                                            title="Print Official Payment Receipt"
                                        >
                                            <i class="pi pi-print text-xs"></i>
                                            <span>Print Receipt</span>
                                        </Link>
                                        <Link
                                            v-if="p.invoice"
                                            :href="route('billing.show', p.invoice.id)"
                                            class="inline-flex items-center gap-1.5 px-3 py-2 rounded-xl bg-teal-50 border border-teal-200 text-teal-800 text-xs font-bold hover:bg-teal-100 transition shadow-2xs"
                                            title="View Statement"
                                        >
                                            <i class="pi pi-file-pdf text-xs"></i>
                                            <span>Statement</span>
                                        </Link>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Filter Empty State -->
                        <div v-else-if="payments?.data && payments.data.length > 0" class="p-12 text-center text-gray-500">
                            <i class="pi pi-filter-slash text-3xl text-gray-300 mb-2"></i>
                            <h4 class="text-base font-bold text-gray-900">No matching payment receipts</h4>
                            <p class="text-xs text-gray-400 mt-1">Try resetting your filter or search query.</p>
                            <button
                                type="button"
                                @click="patientSearch = ''; patientMethodTab = 'all'"
                                class="mt-3 inline-flex items-center gap-1.5 rounded-xl bg-teal-50 px-3.5 py-1.5 text-xs font-bold text-teal-800 hover:bg-teal-100 transition"
                            >
                                <i class="pi pi-refresh text-xs"></i>
                                <span>Clear Filters</span>
                            </button>
                        </div>

                        <!-- Zero Receipts Empty state -->
                        <div v-else class="p-16 text-center text-gray-500">
                            <div class="h-16 w-16 mx-auto mb-4 bg-teal-50 text-teal-700 rounded-3xl flex items-center justify-center border border-teal-100">
                                <i class="pi pi-receipt text-2xl"></i>
                            </div>
                            <h4 class="text-base font-bold text-gray-900">No Payment Receipts Found</h4>
                            <p class="text-xs text-gray-500 mt-1 max-w-sm mx-auto leading-relaxed">
                                Official receipts issued upon settling treatment invoices at the clinic counter will be securely archived here for your records.
                            </p>
                        </div>
                    </div>

                    <!-- Medical Insurance & Tax Guarantee Card -->
                    <div class="rounded-2xl bg-teal-50/60 dark:bg-teal-950/40 border border-teal-100 dark:border-teal-800/60 p-4 sm:p-5 flex items-start gap-3.5 text-xs text-teal-950 dark:text-slate-200">
                        <i class="pi pi-shield text-teal-700 dark:text-teal-400 text-base mt-0.5 shrink-0"></i>
                        <div class="leading-relaxed">
                            <strong class="font-bold text-teal-900 dark:text-teal-300">Insurance & Tax Compliance:</strong>
                            All issued payment receipts are official and verifiable records suitable for HMO reimbursements, dental health plan claims, and personal financial documentation.
                        </div>
                    </div>
                </div>


                <!-- 2. STAFF / ADMIN VIEW -->
                <div v-else class="space-y-6">
                    <!-- KPI Cards -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                        <div class="bg-white rounded-xl border border-gray-200 p-5 shadow-sm">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-xs font-semibold uppercase tracking-wider text-green-600">Total Collected</p>
                                    <p class="mt-1 text-2xl font-bold text-green-700">{{ formatCurrency(metrics.total_collected) }}</p>
                                </div>
                                <div class="h-11 w-11 rounded-lg bg-green-50 text-green-700 flex items-center justify-center text-xl">
                                    <i class="pi pi-dollar text-xl"></i>
                                </div>
                            </div>
                        </div>

                        <div class="bg-white rounded-xl border border-gray-200 p-5 shadow-sm">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-xs font-semibold uppercase tracking-wider text-teal-600">Today's Collections</p>
                                    <p class="mt-1 text-2xl font-bold text-teal-700">{{ formatCurrency(metrics.today_collected) }}</p>
                                </div>
                                <div class="h-11 w-11 rounded-lg bg-teal-50 text-teal-700 flex items-center justify-center text-xl">
                                    <i class="pi pi-calendar-plus text-xl"></i>
                                </div>
                            </div>
                        </div>

                        <div class="bg-white rounded-xl border border-gray-200 p-5 shadow-sm">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-xs font-semibold uppercase tracking-wider text-blue-600">This Month</p>
                                    <p class="mt-1 text-2xl font-bold text-blue-700">{{ formatCurrency(metrics.month_collected) }}</p>
                                </div>
                                <div class="h-11 w-11 rounded-lg bg-blue-50 text-blue-700 flex items-center justify-center text-xl">
                                    <i class="pi pi-chart-line text-xl"></i>
                                </div>
                            </div>
                        </div>

                        <div class="bg-white rounded-xl border border-gray-200 p-5 shadow-sm">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-xs font-semibold uppercase tracking-wider text-gray-500">Transactions</p>
                                    <p class="mt-1 text-2xl font-bold text-gray-900">{{ metrics.total_transactions }}</p>
                                </div>
                                <div class="h-11 w-11 rounded-lg bg-gray-100 text-gray-700 flex items-center justify-center text-xl">
                                    <i class="pi pi-receipt text-xl"></i>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Search & Filter Controls -->
                    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4 space-y-3">
                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
                            <div class="relative lg:col-span-2">
                                <input
                                    v-model="search"
                                    type="text"
                                    placeholder="Search receipt #, reference, invoice #, patient..."
                                    class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-teal-500 focus:border-teal-500"
                                />
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <i class="pi pi-search text-gray-400 text-sm"></i>
                                </div>
                            </div>

                            <div>
                                <select
                                    v-model="paymentMethod"
                                    class="w-full border-gray-300 rounded-lg text-sm shadow-sm focus:ring-teal-500 focus:border-teal-500 font-medium"
                                >
                                    <option value="">All Payment Methods</option>
                                    <option value="Cash">Cash</option>
                                    <option value="GCash">GCash</option>
                                </select>
                            </div>

                            <div>
                                <button
                                    type="button"
                                    @click="resetFilters"
                                    class="w-full py-2 px-3 border border-gray-300 text-gray-600 hover:text-gray-900 hover:bg-gray-50 rounded-lg text-sm font-medium transition flex items-center justify-center gap-1.5"
                                >
                                    <i class="pi pi-filter-slash text-xs"></i> Reset Filters
                                </button>
                            </div>
                        </div>

                        <!-- Date range -->
                        <div class="flex flex-wrap items-center gap-3 pt-2 border-t border-gray-100 text-xs text-gray-600">
                            <span class="font-semibold text-gray-700">Date Range:</span>
                            <div class="flex items-center gap-2">
                                <label>From:</label>
                                <input v-model="dateFrom" type="date" class="border border-gray-300 rounded-md text-xs py-1 px-2 focus:ring-teal-500" />
                            </div>
                            <div class="flex items-center gap-2">
                                <label>To:</label>
                                <input v-model="dateTo" type="date" class="border border-gray-300 rounded-md text-xs py-1 px-2 focus:ring-teal-500" />
                            </div>
                        </div>
                    </div>

                    <!-- Payments Table -->
                    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th scope="col" class="px-6 py-3.5 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                            Receipt #
                                        </th>
                                        <th scope="col" class="px-6 py-3.5 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                            Date
                                        </th>
                                        <th scope="col" class="px-6 py-3.5 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                            Patient
                                        </th>
                                        <th scope="col" class="px-6 py-3.5 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                            Invoice
                                        </th>
                                        <th scope="col" class="px-6 py-3.5 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                            Method
                                        </th>
                                        <th scope="col" class="px-6 py-3.5 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                            Reference
                                        </th>
                                        <th scope="col" class="px-6 py-3.5 text-right text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                            Amount Paid
                                        </th>
                                        <th scope="col" class="px-6 py-3.5 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                            Cashier
                                        </th>
                                        <th scope="col" class="px-6 py-3.5 text-right text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                            Actions
                                        </th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200 bg-white">
                                    <tr
                                        v-for="p in payments?.data"
                                        :key="p.id"
                                        class="hover:bg-gray-50/80 transition"
                                    >
                                        <td class="px-6 py-4 whitespace-nowrap font-mono font-bold text-teal-800">
                                            {{ p.payment_number }}
                                        </td>

                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                                            {{ formatDate(p.payment_date) }}
                                        </td>

                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div v-if="p.patient">
                                                <Link
                                                    :href="route('patients.show', p.patient.id)"
                                                    class="font-semibold text-gray-900 hover:text-teal-700 transition"
                                                >
                                                    {{ p.patient.first_name }} {{ p.patient.last_name }}
                                                </Link>
                                                <div class="text-xs text-gray-500 font-mono">{{ p.patient.patient_id }}</div>
                                            </div>
                                            <span v-else class="text-xs text-gray-400">N/A</span>
                                        </td>

                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <Link
                                                v-if="p.invoice"
                                                :href="route('billing.show', p.invoice.id)"
                                                class="font-mono text-xs font-semibold text-teal-700 hover:text-teal-900 underline block"
                                            >
                                                {{ p.invoice.invoice_number }}
                                            </Link>
                                            <div
                                                v-if="p.invoice?.treatment?.procedure_name || p.invoice?.appointment?.service || p.invoice?.items?.[0]?.description"
                                                class="text-[11px] text-gray-500 truncate max-w-[170px]"
                                                :title="p.invoice?.treatment?.procedure_name || p.invoice?.appointment?.service || p.invoice?.items?.[0]?.description"
                                            >
                                                {{ p.invoice?.treatment?.procedure_name || p.invoice?.appointment?.service || p.invoice?.items?.[0]?.description }}
                                            </div>
                                            <span v-else-if="!p.invoice" class="text-xs text-gray-400">None</span>
                                        </td>

                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span class="px-2.5 py-0.5 rounded-full text-xs font-bold bg-teal-50 text-teal-700 border border-teal-100">
                                                {{ p.payment_method }}
                                            </span>
                                        </td>

                                        <td class="px-6 py-4 whitespace-nowrap text-xs font-mono text-gray-500">
                                            {{ p.reference_number || '-' }}
                                        </td>

                                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-extrabold text-green-700">
                                            <div>{{ formatCurrency(p.amount) }}</div>
                                            <div v-if="p.amount_tendered && Number(p.amount_tendered) > Number(p.amount)" class="text-[11px] text-gray-500 font-normal">
                                                Given: {{ formatCurrency(p.amount_tendered) }} • Change: {{ formatCurrency(p.change_amount || 0) }}
                                            </div>
                                        </td>

                                        <td class="px-6 py-4 whitespace-nowrap text-xs text-gray-600">
                                            {{ p.receiver?.name || 'Staff' }}
                                        </td>

                                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium space-x-1.5">
                                            <Link
                                                :href="route('payments.receipt', p.id)"
                                                target="_blank"
                                                class="text-teal-700 hover:text-teal-900 bg-teal-50 hover:bg-teal-100 px-2.5 py-1 rounded-lg inline-flex items-center gap-1 text-xs font-semibold border border-teal-200 transition"
                                                title="Print Official Payment Receipt"
                                            >
                                                <i class="pi pi-print text-xs"></i>
                                                <span class="hidden sm:inline">Print</span>
                                            </Link>

                                            <Link
                                                v-if="p.invoice"
                                                :href="route('billing.show', p.invoice.id)"
                                                class="text-gray-500 hover:text-gray-800 bg-gray-50 hover:bg-gray-100 p-1.5 rounded-lg inline-flex items-center text-xs border border-gray-200 transition"
                                                title="View Statement"
                                            >
                                                <i class="pi pi-file-pdf text-xs"></i>
                                            </Link>

                                            <button
                                                v-if="['admin', 'dentist'].includes($page.props.auth.user.role)"
                                                type="button"
                                                @click="deletePayment(p.id)"
                                                class="text-red-500 hover:text-red-700 p-1.5 rounded-lg hover:bg-red-50 transition"
                                                title="Void Payment"
                                            >
                                                <i class="pi pi-trash text-xs"></i>
                                            </button>
                                        </td>
                                    </tr>

                                    <tr v-if="!payments?.data || payments.data.length === 0">
                                        <td colspan="9" class="px-6 py-12 text-center text-gray-500">
                                            <i class="pi pi-receipt text-3xl text-gray-300 mb-2"></i>
                                            <p class="text-sm font-medium text-gray-700">No payment receipts found matching criteria.</p>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <!-- Pagination -->
                        <div v-if="payments?.links && payments.links.length > 3" class="px-6 py-4 border-t border-gray-200 flex items-center justify-between">
                            <div class="text-xs text-gray-500">
                                Showing {{ payments.from || 0 }} to {{ payments.to || 0 }} of {{ payments.total }} receipts
                            </div>
                            <div class="flex gap-1">
                                <Link
                                    v-for="(link, idx) in payments.links"
                                    :key="idx"
                                    :href="link.url || '#'"
                                    :class="[
                                        'px-3 py-1.5 text-xs rounded-md border transition',
                                        link.active ? 'bg-teal-700 text-white border-teal-700' : 'bg-white text-gray-700 border-gray-300 hover:bg-gray-50',
                                        !link.url ? 'opacity-40 cursor-not-allowed pointer-events-none' : ''
                                    ]"
                                    v-html="link.label"
                                />
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </AuthenticatedLayout>
</template>
