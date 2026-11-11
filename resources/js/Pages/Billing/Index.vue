<script setup>
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import Modal from "@/Components/Modal.vue";
import { Head, Link, router, useForm } from "@inertiajs/vue3";
import { ref, watch, computed } from "vue";

const props = defineProps({
    invoices: Object,
    filters: {
        type: Object,
        default: () => ({}),
    },
    metrics: {
        type: Object,
        default: () => ({
            total_billed: 0,
            total_collected: 0,
            total_outstanding: 0,
            unpaid_count: 0,
        }),
    },
    patients: {
        type: Array,
        default: () => [],
    },
    isPatient: Boolean,
});

// Patient view filter state
const patientSearch = ref("");
const patientStatusTab = ref("all");

const patientFilteredInvoices = computed(() => {
    let list = props.invoices?.data || [];
    if (patientStatusTab.value === "unpaid") {
        list = list.filter((inv) => inv.status === "unpaid" || (inv.balance && inv.balance > 0));
    } else if (patientStatusTab.value !== "all") {
        list = list.filter((inv) => inv.status === patientStatusTab.value);
    }
    if (patientSearch.value.trim()) {
        const q = patientSearch.value.toLowerCase();
        list = list.filter((inv) => {
            const num = inv.invoice_number?.toLowerCase().includes(q);
            const date = inv.invoice_date?.includes(q);
            const amount = inv.net_amount?.toString().includes(q);
            return num || date || amount;
        });
    }
    return list;
});

const patientMetrics = computed(() => {
    const list = props.invoices?.data || [];
    return {
        total_count: list.length,
        unpaid_count: list.filter((inv) => inv.status === "unpaid" || (inv.balance && inv.balance > 0)).length,
        paid_count: list.filter((inv) => inv.status === "paid" || inv.balance === 0).length,
    };
});

// Filter state
const search = ref(props.filters.search || "");
const status = ref(props.filters.status || "");
const patientId = ref(props.filters.patient_id || "");
const dateFrom = ref(props.filters.date_from || "");
const dateTo = ref(props.filters.date_to || "");

let searchTimeout = null;
watch([search, status, patientId, dateFrom, dateTo], ([newSearch, newStatus, newPatient, newFrom, newTo]) => {
    if (props.isPatient) return;
    if (searchTimeout) clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        router.get(
            route("billing.index"),
            {
                search: newSearch,
                status: newStatus,
                patient_id: newPatient,
                date_from: newFrom,
                date_to: newTo,
            },
            { preserveState: true, replace: true }
        );
    }, 300);
});

const resetFilters = () => {
    search.value = "";
    status.value = "";
    patientId.value = "";
    dateFrom.value = "";
    dateTo.value = "";
};

// Payment recording modal
const showPaymentModal = ref(false);
const activeInvoice = ref(null);

const paymentForm = useForm({
    amount: "",
    amount_tendered: "",
    change_amount: 0,
    payment_date: new Date().toISOString().split("T")[0],
    payment_method: "Cash",
    reference_number: "",
    notes: "",
});

const calculatedChange = computed(() => {
    const tendered = parseFloat(paymentForm.amount_tendered) || 0;
    const applied = parseFloat(paymentForm.amount) || 0;
    return Math.max(0, Math.round((tendered - applied) * 100) / 100);
});

const calculatedRemainingBalance = computed(() => {
    if (!activeInvoice.value) return 0;
    const currentBalance = parseFloat(activeInvoice.value.balance) || 0;
    const applied = parseFloat(paymentForm.amount) || 0;
    return Math.max(0, Math.round((currentBalance - applied) * 100) / 100);
});

const isInsufficientPayment = computed(() => {
    if (!activeInvoice.value) return false;
    const currentBalance = parseFloat(activeInvoice.value.balance) || 0;
    const applied = parseFloat(paymentForm.amount) || 0;
    return applied < currentBalance;
});

const onTenderedInput = () => {
    if (!activeInvoice.value) return;
    const currentBalance = parseFloat(activeInvoice.value.balance) || 0;
    const tendered = parseFloat(paymentForm.amount_tendered);

    if (isNaN(tendered) || tendered <= 0) {
        paymentForm.amount = "";
        paymentForm.change_amount = 0;
        return;
    }

    if (tendered >= currentBalance) {
        // Excess or exact money given: settle full balance, excess is change!
        paymentForm.amount = currentBalance;
        paymentForm.change_amount = Math.round((tendered - currentBalance) * 100) / 100;
    } else {
        // Insufficient money given: settle only tendered amount, remaining stays in balance!
        paymentForm.amount = tendered;
        paymentForm.change_amount = 0;
    }
};

const setExactTendered = () => {
    if (!activeInvoice.value) return;
    paymentForm.amount_tendered = activeInvoice.value.balance;
    onTenderedInput();
};

const addTenderedAmount = (addVal) => {
    const current = parseFloat(paymentForm.amount_tendered) || 0;
    paymentForm.amount_tendered = (current + addVal).toFixed(2);
    onTenderedInput();
};

const openPaymentModal = (invoice) => {
    activeInvoice.value = invoice;
    paymentForm.amount = invoice.balance;
    paymentForm.amount_tendered = invoice.balance;
    paymentForm.change_amount = 0;
    paymentForm.payment_date = new Date().toISOString().split("T")[0];
    paymentForm.payment_method = "Cash";
    paymentForm.reference_number = "";
    paymentForm.notes = "";
    showPaymentModal.value = true;
};

const submitPayment = () => {
    if (!activeInvoice.value) return;
    paymentForm.change_amount = calculatedChange.value;
    paymentForm.post(route("payments.store", activeInvoice.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            showPaymentModal.value = false;
        },
    });
};

const deleteInvoice = (id) => {
    if (confirm("Are you sure you want to delete this invoice and all associated items/payments?")) {
        router.delete(route("billing.destroy", id), {
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
    <Head title="Billing & Invoicing" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <div class="flex items-center gap-2">
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full bg-teal-50 border border-teal-200 text-teal-800 text-[11px] font-bold uppercase tracking-wider">
                            <i class="pi pi-wallet text-[10px]"></i>
                            <span>{{ isPatient ? "Patient Financials" : "Billing Management" }}</span>
                        </span>
                        <span class="text-xs text-gray-400">•</span>
                        <span class="text-xs font-medium text-gray-500">Vitality Smile Clinic</span>
                    </div>
                    <h2 class="mt-1 text-2xl font-extrabold text-gray-900 tracking-tight">
                        {{ isPatient ? "My Billing & Statements" : "Billing & Invoicing" }}
                    </h2>
                    <p class="mt-0.5 text-xs sm:text-sm text-gray-500">
                        {{
                            isPatient
                                ? "Review official dental clinic statements, track payments, and inspect invoice balances."
                                : "Manage patient invoices, collect payments, and track receivables."
                        }}
                    </p>
                </div>

                <div v-if="!isPatient" class="flex items-center gap-3">
                    <Link
                        :href="route('payments.index')"
                        class="inline-flex items-center justify-center rounded-lg border border-gray-300 bg-white px-3.5 py-2 text-sm font-semibold text-gray-700 shadow-sm hover:bg-gray-50 transition"
                    >
                        <i class="pi pi-receipt -ml-1 mr-2 text-xs"></i>
                        Payment Receipts
                    </Link>
                    <Link
                        v-if="['admin', 'secretary', 'dentist'].includes($page.props.auth.user.role)"
                        :href="route('billing.create')"
                        class="inline-flex items-center justify-center rounded-lg bg-teal-700 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-teal-800 focus:outline-none focus:ring-2 focus:ring-teal-500 focus:ring-offset-2"
                    >
                        <i class="pi pi-plus -ml-1 mr-2 text-xs"></i>
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
                        class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl text-xs font-bold transition whitespace-nowrap bg-teal-700 text-white shadow-sm"
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
                    <!-- Patient Financial Overview KPI Cards -->
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                        <!-- Total Invoiced -->
                        <div class="rounded-2xl border border-gray-200/80 bg-white p-5 shadow-xs">
                            <div class="flex items-center justify-between">
                                <span class="text-xs font-semibold uppercase tracking-wider text-gray-500">Total Invoiced</span>
                                <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-teal-50 text-teal-700">
                                    <i class="pi pi-file text-sm"></i>
                                </div>
                            </div>
                            <p class="mt-2 text-2xl font-extrabold text-gray-900">{{ formatCurrency(metrics.total_billed) }}</p>
                            <p class="mt-0.5 text-[11px] text-gray-400">Total fees billed to date</p>
                        </div>

                        <!-- Total Settled -->
                        <div class="rounded-2xl border border-gray-200/80 bg-white p-5 shadow-xs">
                            <div class="flex items-center justify-between">
                                <span class="text-xs font-semibold uppercase tracking-wider text-emerald-700">Settled Payments</span>
                                <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-emerald-50 text-emerald-700">
                                    <i class="pi pi-check-circle text-sm"></i>
                                </div>
                            </div>
                            <p class="mt-2 text-2xl font-extrabold text-emerald-700">{{ formatCurrency(metrics.total_paid) }}</p>
                            <p class="mt-0.5 text-[11px] text-gray-400">Successfully received & credited</p>
                        </div>

                        <!-- Outstanding Balance -->
                        <div
                            class="rounded-2xl border p-5 shadow-xs transition"
                            :class="(metrics.total_balance > 0) ? 'border-rose-200 bg-rose-50/40' : 'border-gray-200/80 bg-white'"
                        >
                            <div class="flex items-center justify-between">
                                <span
                                    class="text-xs font-bold uppercase tracking-wider"
                                    :class="(metrics.total_balance > 0) ? 'text-rose-700' : 'text-gray-500'"
                                >
                                    Remaining Balance
                                </span>
                                <div
                                    class="flex h-9 w-9 items-center justify-center rounded-xl"
                                    :class="(metrics.total_balance > 0) ? 'bg-rose-100 text-rose-700' : 'bg-gray-100 text-gray-600'"
                                >
                                    <i class="pi pi-wallet text-sm"></i>
                                </div>
                            </div>
                            <p
                                class="mt-2 text-2xl font-extrabold"
                                :class="(metrics.total_balance > 0) ? 'text-rose-700' : 'text-gray-900'"
                            >
                                {{ formatCurrency(metrics.total_balance) }}
                            </p>
                            <p class="mt-0.5 text-[11px]" :class="(metrics.total_balance > 0) ? 'text-rose-600 font-medium' : 'text-gray-400'">
                                {{ (metrics.total_balance > 0) ? 'Please settle at clinic front desk' : 'All accounts fully settled' }}
                            </p>
                        </div>
                    </div>

                    <!-- Filter Toolbar -->
                    <div class="rounded-3xl border border-gray-200/90 bg-white shadow-sm overflow-hidden">
                        <div class="p-4 sm:p-5 border-b border-gray-100 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                            <!-- Status Tabs -->
                            <div class="flex items-center gap-1.5 overflow-x-auto pb-1 md:pb-0 scrollbar-none">
                                <button
                                    type="button"
                                    @click="patientStatusTab = 'all'"
                                    :class="[
                                        'px-3.5 py-1.5 text-xs font-bold rounded-xl transition cursor-pointer whitespace-nowrap',
                                        patientStatusTab === 'all'
                                            ? 'bg-teal-700 text-white shadow-xs'
                                            : 'bg-gray-100 text-gray-600 hover:bg-gray-200/80'
                                    ]"
                                >
                                    All Statements ({{ patientMetrics.total_count }})
                                </button>
                                <button
                                    type="button"
                                    @click="patientStatusTab = 'unpaid'"
                                    :class="[
                                        'px-3.5 py-1.5 text-xs font-bold rounded-xl transition cursor-pointer whitespace-nowrap',
                                        patientStatusTab === 'unpaid'
                                            ? 'bg-rose-600 text-white shadow-xs'
                                            : 'bg-gray-100 text-gray-600 hover:bg-gray-200/80'
                                    ]"
                                >
                                    Unpaid / Due ({{ patientMetrics.unpaid_count }})
                                </button>
                                <button
                                    type="button"
                                    @click="patientStatusTab = 'paid'"
                                    :class="[
                                        'px-3.5 py-1.5 text-xs font-bold rounded-xl transition cursor-pointer whitespace-nowrap',
                                        patientStatusTab === 'paid'
                                            ? 'bg-emerald-600 text-white shadow-xs'
                                            : 'bg-gray-100 text-gray-600 hover:bg-gray-200/80'
                                    ]"
                                >
                                    Fully Paid ({{ patientMetrics.paid_count }})
                                </button>
                            </div>

                            <!-- Search -->
                            <div class="relative w-full md:w-72">
                                <i class="pi pi-search absolute left-3.5 top-1/2 -translate-y-1/2 text-gray-400 text-xs"></i>
                                <input
                                    v-model="patientSearch"
                                    type="text"
                                    placeholder="Search invoice #, date, amount..."
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

                        <!-- Invoices List -->
                        <div v-if="patientFilteredInvoices.length > 0" class="divide-y divide-gray-100 p-4 sm:p-6 space-y-4">
                            <div
                                v-for="inv in patientFilteredInvoices"
                                :key="inv.id"
                                class="rounded-2xl border border-gray-200/80 bg-white p-5 sm:p-6 transition hover:shadow-md hover:border-teal-300"
                            >
                                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                                    <div class="space-y-1.5">
                                        <div class="flex items-center gap-3">
                                            <span class="font-mono font-bold text-teal-900 text-base">{{ inv.invoice_number }}</span>
                                            <span
                                                :class="[
                                                    inv.status === 'paid' ? 'bg-emerald-50 text-emerald-700 border-emerald-200' :
                                                    inv.status === 'partially_paid' ? 'bg-amber-50 text-amber-700 border-amber-200' :
                                                    inv.status === 'cancelled' ? 'bg-gray-100 text-gray-700 border-gray-200' : 'bg-rose-50 text-rose-700 border-rose-200',
                                                    'px-2.5 py-0.5 rounded-full text-xs font-bold border capitalize'
                                                ]"
                                            >
                                                {{ (inv.status || 'unpaid').replace('_', ' ') }}
                                            </span>
                                        </div>

                                        <p class="text-xs text-gray-500 flex items-center gap-3">
                                            <span>Invoice Date: <strong>{{ formatDate(inv.invoice_date) }}</strong></span>
                                            <span>•</span>
                                            <span>Due Date: <strong>{{ formatDate(inv.due_date) }}</strong></span>
                                        </p>

                                        <p v-if="inv.treatment" class="text-xs text-gray-600">
                                            Procedure: <span class="font-semibold text-gray-800">{{ inv.treatment.procedure_name }}</span>
                                        </p>
                                    </div>

                                    <div class="flex items-center gap-6 self-end sm:self-auto">
                                        <div class="text-right">
                                            <p class="text-xs text-gray-400 font-medium">Billed Amount</p>
                                            <p class="text-lg sm:text-xl font-extrabold text-gray-900">{{ formatCurrency(inv.net_amount) }}</p>
                                            <p
                                                v-if="inv.balance > 0"
                                                class="text-xs text-rose-600 font-bold mt-0.5"
                                            >
                                                Balance Due: {{ formatCurrency(inv.balance) }}
                                            </p>
                                            <p
                                                v-else
                                                class="text-xs text-emerald-600 font-semibold mt-0.5 flex items-center justify-end gap-1"
                                            >
                                                <i class="pi pi-check text-[10px]"></i> Fully Paid
                                            </p>
                                        </div>

                                        <Link
                                            :href="route('billing.show', inv.id)"
                                            class="inline-flex items-center gap-1.5 px-4 py-2.5 rounded-xl bg-teal-50 text-teal-800 border border-teal-200 text-xs font-bold hover:bg-teal-100 transition shadow-2xs"
                                        >
                                            <i class="pi pi-file-pdf text-xs"></i>
                                            <span>View Statement</span>
                                        </Link>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Filter Empty State -->
                        <div v-else-if="invoices?.data && invoices.data.length > 0" class="p-12 text-center text-gray-500">
                            <i class="pi pi-filter-slash text-3xl text-gray-300 mb-2"></i>
                            <h4 class="text-base font-bold text-gray-900">No matching statements</h4>
                            <p class="text-xs text-gray-400 mt-1">Try adjusting your search or tab filter.</p>
                            <button
                                type="button"
                                @click="patientSearch = ''; patientStatusTab = 'all'"
                                class="mt-3 inline-flex items-center gap-1.5 rounded-xl bg-teal-50 px-3.5 py-1.5 text-xs font-bold text-teal-800 hover:bg-teal-100 transition"
                            >
                                <i class="pi pi-refresh text-xs"></i>
                                <span>Clear Filters</span>
                            </button>
                        </div>

                        <!-- Zero Invoices Empty state -->
                        <div v-else class="p-16 text-center text-gray-500">
                            <div class="h-16 w-16 mx-auto mb-4 bg-teal-50 text-teal-700 rounded-3xl flex items-center justify-center border border-teal-100">
                                <i class="pi pi-wallet text-2xl"></i>
                            </div>
                            <h4 class="text-base font-bold text-gray-900">No Invoices on File</h4>
                            <p class="text-xs text-gray-500 mt-1 max-w-sm mx-auto leading-relaxed">
                                Once dental procedures are recorded, itemized statements with breakdown of treatment fees will appear here.
                            </p>
                        </div>
                    </div>

                    <!-- Payment Settlement Reminder Card -->
                    <div class="rounded-2xl bg-teal-50/60 border border-teal-100 p-4 sm:p-5 flex items-start gap-3.5 text-xs text-teal-950">
                        <i class="pi pi-info-circle text-teal-700 text-base mt-0.5 shrink-0"></i>
                        <div class="leading-relaxed">
                            <strong class="font-bold text-teal-900">Clinic Payment Channels:</strong>
                            Vitality Smile accepts Cash and GCash at the clinic front desk. Official receipts are issued immediately upon payment settlement and can be reviewed under the <strong>My Receipts</strong> tab.
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
                                    <p class="text-xs font-semibold uppercase tracking-wider text-gray-500">Total Invoiced</p>
                                    <p class="mt-1 text-2xl font-bold text-gray-900">{{ formatCurrency(metrics.total_billed) }}</p>
                                </div>
                                <div class="h-11 w-11 rounded-lg bg-teal-50 text-teal-700 flex items-center justify-center text-xl">
                                    <i class="pi pi-file text-xl"></i>
                                </div>
                            </div>
                        </div>

                        <div class="bg-white rounded-xl border border-gray-200 p-5 shadow-sm">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-xs font-semibold uppercase tracking-wider text-green-600">Total Collected</p>
                                    <p class="mt-1 text-2xl font-bold text-green-700">{{ formatCurrency(metrics.total_collected) }}</p>
                                </div>
                                <div class="h-11 w-11 rounded-lg bg-green-50 text-green-700 flex items-center justify-center text-xl">
                                    <i class="pi pi-check-circle text-xl"></i>
                                </div>
                            </div>
                        </div>

                        <div class="bg-white rounded-xl border border-gray-200 p-5 shadow-sm">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-xs font-semibold uppercase tracking-wider text-red-500">Receivables (Balance)</p>
                                    <p class="mt-1 text-2xl font-bold text-red-600">{{ formatCurrency(metrics.total_outstanding) }}</p>
                                </div>
                                <div class="h-11 w-11 rounded-lg bg-red-50 text-red-600 flex items-center justify-center text-xl">
                                    <i class="pi pi-wallet text-xl"></i>
                                </div>
                            </div>
                        </div>

                        <div class="bg-white rounded-xl border border-gray-200 p-5 shadow-sm">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-xs font-semibold uppercase tracking-wider text-yellow-600">Unpaid Invoices</p>
                                    <p class="mt-1 text-2xl font-bold text-yellow-700">{{ metrics.unpaid_count }}</p>
                                </div>
                                <div class="h-11 w-11 rounded-lg bg-yellow-50 text-yellow-600 flex items-center justify-center text-xl">
                                    <i class="pi pi-clock text-xl"></i>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Search & Filters -->
                    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4 space-y-3">
                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3">
                            <div class="relative lg:col-span-2">
                                <input
                                    v-model="search"
                                    type="text"
                                    placeholder="Search by invoice #, patient name, or ID..."
                                    class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-teal-500 focus:border-teal-500"
                                />
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <i class="pi pi-search text-gray-400 text-sm"></i>
                                </div>
                            </div>

                            <div>
                                <select
                                    v-model="status"
                                    class="w-full border-gray-300 rounded-lg text-sm shadow-sm focus:ring-teal-500 focus:border-teal-500"
                                >
                                    <option value="">All Statuses</option>
                                    <option value="unpaid">Unpaid</option>
                                    <option value="partially_paid">Partially Paid</option>
                                    <option value="paid">Paid</option>
                                    <option value="cancelled">Cancelled</option>
                                </select>
                            </div>

                            <div>
                                <select
                                    v-model="patientId"
                                    class="w-full border-gray-300 rounded-lg text-sm shadow-sm focus:ring-teal-500 focus:border-teal-500"
                                >
                                    <option value="">All Patients</option>
                                    <option v-for="p in patients" :key="p.id" :value="p.id">
                                        {{ p.first_name }} {{ p.last_name }}
                                    </option>
                                </select>
                            </div>

                            <div>
                                <button
                                    type="button"
                                    @click="resetFilters"
                                    class="w-full py-2 px-3 border border-gray-300 text-gray-600 hover:text-gray-900 hover:bg-gray-50 rounded-lg text-sm font-medium transition flex items-center justify-center gap-1.5"
                                >
                                    <i class="pi pi-filter-slash text-xs"></i> Reset
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

                    <!-- Invoices Table -->
                    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th scope="col" class="px-6 py-3.5 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                            Invoice #
                                        </th>
                                        <th scope="col" class="px-6 py-3.5 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                            Patient
                                        </th>
                                        <th scope="col" class="px-6 py-3.5 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                            Date
                                        </th>
                                        <th scope="col" class="px-6 py-3.5 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                            Net Amount
                                        </th>
                                        <th scope="col" class="px-6 py-3.5 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                            Paid
                                        </th>
                                        <th scope="col" class="px-6 py-3.5 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                            Balance
                                        </th>
                                        <th scope="col" class="px-6 py-3.5 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                            Status
                                        </th>
                                        <th scope="col" class="px-6 py-3.5 text-right text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                            Actions
                                        </th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200 bg-white">
                                    <tr
                                        v-for="inv in invoices?.data"
                                        :key="inv.id"
                                        class="hover:bg-gray-50/80 transition"
                                    >
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <Link
                                                :href="route('billing.show', inv.id)"
                                                class="font-mono font-bold text-teal-700 hover:text-teal-900 hover:underline"
                                            >
                                                {{ inv.invoice_number }}
                                            </Link>
                                        </td>

                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div v-if="inv.patient">
                                                <Link
                                                    :href="route('patients.show', inv.patient.id)"
                                                    class="font-semibold text-gray-900 hover:text-teal-700 transition"
                                                >
                                                    {{ inv.patient.first_name }} {{ inv.patient.last_name }}
                                                </Link>
                                                <div class="text-xs text-gray-500 font-mono">{{ inv.patient.patient_id }}</div>
                                            </div>
                                            <span v-else class="text-xs text-gray-400">N/A</span>
                                        </td>

                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                                            {{ formatDate(inv.invoice_date) }}
                                        </td>

                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-bold text-gray-900">
                                            {{ formatCurrency(inv.net_amount) }}
                                        </td>

                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold text-green-700">
                                            {{ formatCurrency(inv.paid_amount) }}
                                        </td>

                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-bold" :class="inv.balance > 0 ? 'text-red-600' : 'text-gray-500'">
                                            {{ formatCurrency(inv.balance) }}
                                        </td>

                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span
                                                :class="[
                                                    inv.status === 'paid' ? 'bg-green-100 text-green-800 border-green-200' :
                                                    inv.status === 'partially_paid' ? 'bg-yellow-100 text-yellow-800 border-yellow-200' :
                                                    inv.status === 'cancelled' ? 'bg-gray-100 text-gray-800 border-gray-200' : 'bg-red-100 text-red-800 border-red-200',
                                                    'px-2.5 py-0.5 rounded-full text-xs font-bold border capitalize'
                                                ]"
                                            >
                                                {{ (inv.status || 'unpaid').replace('_', ' ') }}
                                            </span>
                                        </td>

                                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium space-x-1.5">
                                            <!-- Record Payment Button (if balance > 0) -->
                                            <button
                                                v-if="inv.balance > 0 && inv.status !== 'cancelled' && ['admin', 'secretary', 'dentist'].includes($page.props.auth.user.role)"
                                                type="button"
                                                @click="openPaymentModal(inv)"
                                                class="px-2.5 py-1 text-xs font-semibold rounded-lg bg-teal-50 text-teal-700 hover:bg-teal-100 transition inline-flex items-center gap-1"
                                                title="Accept Payment"
                                            >
                                                <i class="pi pi-credit-card text-[10px]"></i> Pay
                                            </button>

                                            <!-- View / Print Receipt -->
                                            <Link
                                                :href="route('billing.show', inv.id)"
                                                class="text-teal-600 hover:text-teal-900 p-1"
                                                title="View & Print"
                                            >
                                                <i class="pi pi-eye"></i>
                                            </Link>

                                            <button
                                                v-if="['admin', 'dentist'].includes($page.props.auth.user.role)"
                                                type="button"
                                                @click="deleteInvoice(inv.id)"
                                                class="text-red-500 hover:text-red-700 p-1"
                                                title="Delete Invoice"
                                            >
                                                <i class="pi pi-trash"></i>
                                            </button>
                                        </td>
                                    </tr>

                                    <tr v-if="!invoices?.data || invoices.data.length === 0">
                                        <td colspan="8" class="px-6 py-12 text-center text-gray-500">
                                            <i class="pi pi-folder-open text-3xl text-gray-300 mb-2"></i>
                                            <p class="text-sm font-medium text-gray-700">No invoices found matching criteria.</p>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <!-- Pagination -->
                        <div v-if="invoices?.links && invoices.links.length > 3" class="px-6 py-4 border-t border-gray-200 flex items-center justify-between">
                            <div class="text-xs text-gray-500">
                                Showing {{ invoices.from || 0 }} to {{ invoices.to || 0 }} of {{ invoices.total }} invoices
                            </div>
                            <div class="flex gap-1">
                                <Link
                                    v-for="(link, idx) in invoices.links"
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

        <!-- Quick Payment Modal -->
        <Modal :show="showPaymentModal" @close="showPaymentModal = false" maxWidth="lg">
            <div v-if="activeInvoice" class="p-6">
                <div class="flex items-center justify-between border-b border-gray-100 pb-3">
                    <div>
                        <span class="text-xs font-semibold text-teal-700 uppercase tracking-wider">Payment Receipt & Cashier Desk</span>
                        <h3 class="text-lg font-bold text-gray-900 mt-0.5">Record Payment for {{ activeInvoice.invoice_number }}</h3>
                    </div>
                    <button type="button" @click="showPaymentModal = false" class="text-gray-400 hover:text-gray-600">
                        <i class="pi pi-times"></i>
                    </button>
                </div>

                <form @submit.prevent="submitPayment" class="mt-4 space-y-4">
                    <!-- Balance Summary Bar -->
                    <div class="bg-slate-50 p-3.5 rounded-xl border border-gray-200 grid grid-cols-2 gap-3 text-xs">
                        <div>
                            <span class="text-gray-500 block">Total Statement Billed:</span>
                            <span class="font-bold text-gray-900 text-sm">{{ formatCurrency(activeInvoice.net_amount) }}</span>
                        </div>
                        <div class="text-right">
                            <span class="text-gray-500 block">Current Balance Due:</span>
                            <span class="font-extrabold text-red-600 text-sm">{{ formatCurrency(activeInvoice.balance) }}</span>
                        </div>
                    </div>

                    <!-- Money Given by Patient (Input for cash/money tendered) -->
                    <div class="rounded-xl border-2 border-teal-500/30 bg-teal-50/20 p-4">
                        <div class="flex items-center justify-between mb-1.5">
                            <label class="block text-xs font-bold text-teal-950 uppercase tracking-wide">
                                Money Given by Patient / Cash Received <span class="text-red-500">*</span>
                            </label>
                            <span class="text-[11px] text-teal-700 font-medium">Cash Tendered</span>
                        </div>
                        <div class="relative">
                            <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center text-teal-700 text-base font-extrabold">₱</span>
                            <input
                                v-model="paymentForm.amount_tendered"
                                @input="onTenderedInput"
                                type="number"
                                step="0.01"
                                min="0.01"
                                placeholder="0.00"
                                required
                                class="w-full pl-9 pr-4 py-2.5 rounded-xl border-teal-300 shadow-xs focus:border-teal-600 focus:ring-teal-600 text-base font-extrabold text-gray-900 bg-white"
                            />
                        </div>

                        <!-- Quick Cash Shortcuts -->
                        <div class="mt-2.5 flex flex-wrap items-center gap-1.5">
                            <span class="text-[10px] text-gray-400 font-medium mr-1">Quick Fill:</span>
                            <button
                                type="button"
                                @click="setExactTendered"
                                class="px-2 py-1 rounded-md text-[11px] font-semibold bg-white border border-teal-300 text-teal-800 hover:bg-teal-50 shadow-2xs"
                            >
                                Exact Balance (₱{{ Number(activeInvoice.balance).toLocaleString('en-US', { minimumFractionDigits: 2 }) }})
                            </button>
                            <button
                                type="button"
                                @click="addTenderedAmount(100)"
                                class="px-2 py-1 rounded-md text-[11px] font-medium bg-white border border-gray-200 text-gray-700 hover:bg-gray-50 shadow-2xs"
                            >
                                +₱100
                            </button>
                            <button
                                type="button"
                                @click="addTenderedAmount(500)"
                                class="px-2 py-1 rounded-md text-[11px] font-medium bg-white border border-gray-200 text-gray-700 hover:bg-gray-50 shadow-2xs"
                            >
                                +₱500
                            </button>
                            <button
                                type="button"
                                @click="addTenderedAmount(1000)"
                                class="px-2 py-1 rounded-md text-[11px] font-medium bg-white border border-gray-200 text-gray-700 hover:bg-gray-50 shadow-2xs"
                            >
                                +₱1,000
                            </button>
                        </div>
                    </div>

                    <!-- Computed Live Calculation Result (Change vs Remaining Balance) -->
                    <div class="rounded-xl border p-4 transition-all"
                         :class="[
                             isInsufficientPayment
                                 ? 'bg-amber-50/70 border-amber-200 text-amber-900'
                                 : 'bg-emerald-50/70 border-emerald-200 text-emerald-950'
                         ]">
                        <div class="grid grid-cols-2 gap-4">
                            <!-- Change Calculation (when excess) -->
                            <div class="border-r border-gray-200/60 pr-2">
                                <span class="text-[11px] font-bold uppercase tracking-wider block"
                                      :class="calculatedChange > 0 ? 'text-emerald-800' : 'text-gray-500'">
                                    Change to Return:
                                </span>
                                <div class="mt-1 flex items-baseline gap-1">
                                    <span class="text-xl sm:text-2xl font-black"
                                          :class="calculatedChange > 0 ? 'text-emerald-700' : 'text-gray-400'">
                                        ₱{{ calculatedChange.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) }}
                                    </span>
                                </div>
                                <p v-if="calculatedChange > 0" class="text-[10px] text-emerald-700 mt-0.5 font-medium flex items-center gap-1">
                                    <i class="pi pi-check-circle text-[10px]"></i> Excess money received from patient.
                                </p>
                                <p v-else class="text-[10px] text-gray-400 mt-0.5">
                                    No change required (Exact / partial payment).
                                </p>
                            </div>

                            <!-- Remaining Balance Calculation (when insufficient or fully settled) -->
                            <div class="pl-2">
                                <span class="text-[11px] font-bold uppercase tracking-wider block"
                                      :class="isInsufficientPayment ? 'text-amber-800' : 'text-emerald-800'">
                                    Remaining Invoice Balance:
                                </span>
                                <div class="mt-1 flex items-baseline gap-1">
                                    <span class="text-xl sm:text-2xl font-black"
                                          :class="isInsufficientPayment ? 'text-amber-700' : 'text-emerald-700'">
                                        ₱{{ calculatedRemainingBalance.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) }}
                                    </span>
                                    <span v-if="calculatedRemainingBalance === 0" class="ml-1 text-[10px] font-bold px-1.5 py-0.5 rounded-full bg-emerald-100 text-emerald-800 uppercase">
                                        Fully Paid
                                    </span>
                                    <span v-else class="ml-1 text-[10px] font-bold px-1.5 py-0.5 rounded-full bg-amber-100 text-amber-800 uppercase">
                                        Partial
                                    </span>
                                </div>
                                <p v-if="isInsufficientPayment" class="text-[10px] text-amber-700 mt-0.5 font-medium flex items-center gap-1">
                                    <i class="pi pi-exclamation-triangle text-[10px]"></i> Insufficient payment. ₱{{ calculatedRemainingBalance.toLocaleString('en-US', { minimumFractionDigits: 2 }) }} added to remaining balance.
                                </p>
                                <p v-else class="text-[10px] text-emerald-700 mt-0.5 font-medium flex items-center gap-1">
                                    <i class="pi pi-check-circle text-[10px]"></i> Total account settled in full.
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Payment Amount Applied to Invoice -->
                    <div>
                        <div class="flex items-center justify-between mb-1">
                            <label class="block text-xs font-semibold text-gray-700">
                                Amount Applied to Invoice (₱) <span class="text-red-500">*</span>
                            </label>
                            <span class="text-[11px] text-gray-400">Actual statement payment</span>
                        </div>
                        <div class="relative">
                            <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-500 text-sm font-bold">₱</span>
                            <input
                                v-model="paymentForm.amount"
                                type="number"
                                step="0.01"
                                min="0.01"
                                :max="activeInvoice.balance"
                                required
                                class="w-full pl-8 rounded-lg border-gray-300 shadow-sm focus:border-teal-500 focus:ring-teal-500 text-sm font-bold text-gray-900 bg-gray-50/50"
                            />
                        </div>
                        <p v-if="paymentForm.errors.amount" class="text-xs text-red-600 mt-1">{{ paymentForm.errors.amount }}</p>
                    </div>

                    <!-- Payment Method & Date -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-semibold text-gray-700 mb-1">
                                Payment Method <span class="text-red-500">*</span>
                            </label>
                            <select
                                v-model="paymentForm.payment_method"
                                required
                                class="w-full rounded-lg border-gray-300 shadow-sm focus:border-teal-500 focus:ring-teal-500 text-sm font-medium"
                            >
                                <option value="Cash">Cash</option>
                                <option value="GCash">GCash</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-gray-700 mb-1">
                                Payment Date <span class="text-red-500">*</span>
                            </label>
                            <input
                                v-model="paymentForm.payment_date"
                                type="date"
                                required
                                class="w-full rounded-lg border-gray-300 shadow-sm focus:border-teal-500 focus:ring-teal-500 text-sm"
                            />
                        </div>
                    </div>

                    <!-- Reference Number (GCash) -->
                    <div v-if="paymentForm.payment_method === 'GCash'">
                        <label class="block text-xs font-semibold text-gray-700 mb-1">
                            GCash Reference / Transaction Number <span class="text-red-500">*</span>
                        </label>
                        <input
                            v-model="paymentForm.reference_number"
                            type="text"
                            required
                            placeholder="e.g. GCash Ref # 100234912"
                            class="w-full rounded-lg border-gray-300 shadow-sm focus:border-teal-500 focus:ring-teal-500 text-sm"
                        />
                    </div>

                    <!-- Notes -->
                    <div>
                        <label class="block text-xs font-semibold text-gray-700 mb-1">Notes / Remarks</label>
                        <input
                            v-model="paymentForm.notes"
                            type="text"
                            placeholder="Optional cashier notes..."
                            class="w-full rounded-lg border-gray-300 shadow-sm focus:border-teal-500 focus:ring-teal-500 text-sm"
                        />
                    </div>

                    <div class="mt-6 flex justify-end gap-3 border-t border-gray-100 pt-4">
                        <button
                            type="button"
                            @click="showPaymentModal = false"
                            class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-xs font-semibold text-gray-700 hover:bg-gray-50"
                        >
                            Cancel
                        </button>
                        <button
                            type="submit"
                            :disabled="paymentForm.processing"
                            class="rounded-lg bg-teal-700 px-5 py-2 text-xs font-semibold text-white shadow-sm hover:bg-teal-800 disabled:opacity-50 inline-flex items-center gap-1.5"
                        >
                            <i class="pi pi-receipt text-xs"></i>
                            <span>Confirm Payment Receipt</span>
                        </button>
                    </div>
                </form>
            </div>
        </Modal>
    </AuthenticatedLayout>
</template>
