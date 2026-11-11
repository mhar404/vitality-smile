<script setup>
import { Head, Link } from "@inertiajs/vue3";
import { computed } from "vue";
import ThemeToggle from "@/Components/ThemeToggle.vue";

const props = defineProps({
    payment: {
        type: Object,
        required: true,
    },
});

const attendingDentist = computed(() => {
    return (
        props.payment.invoice?.treatment?.dentist?.name ||
        props.payment.invoice?.appointment?.dentist?.name ||
        "Dr. Valdez"
    );
});

// Resolve all services/procedures availed by the patient
const availedServices = computed(() => {
    const list = [];
    const invoice = props.payment.invoice;

    // 1. Line items from invoice
    if (invoice?.items && invoice.items.length > 0) {
        for (const item of invoice.items) {
            const name =
                item.description ||
                item.item_name ||
                item.treatment?.procedure_name ||
                "Dental Service";
            const tooth =
                item.tooth_number ||
                item.treatment?.tooth_number ||
                null;
            const amount =
                item.total != null
                    ? Number(item.total)
                    : Number(item.quantity || 1) * Number(item.unit_price || 0);
            list.push({
                name,
                tooth,
                quantity: item.quantity || 1,
                unit_price:
                    item.unit_price != null ? Number(item.unit_price) : amount,
                total: amount,
            });
        }
    }

    // 2. Fallback to linked Treatment
    if (list.length === 0 && invoice?.treatment) {
        list.push({
            name: invoice.treatment.procedure_name || "Dental Treatment",
            tooth: invoice.treatment.tooth_number || null,
            quantity: 1,
            unit_price: Number(
                invoice.treatment.cost ||
                    invoice.total_amount ||
                    props.payment.amount,
            ),
            total: Number(
                invoice.treatment.cost ||
                    invoice.total_amount ||
                    props.payment.amount,
            ),
        });
    }

    // 3. Fallback to linked Appointment
    if (list.length === 0 && invoice?.appointment) {
        list.push({
            name:
                invoice.appointment.service ||
                "Dental Consultation / Appointment",
            tooth: null,
            quantity: 1,
            unit_price: Number(invoice.total_amount || props.payment.amount),
            total: Number(invoice.total_amount || props.payment.amount),
        });
    }

    // 4. Default fallback if invoice exists
    if (list.length === 0 && invoice) {
        list.push({
            name: invoice.notes
                ? `Dental Services (${invoice.notes})`
                : "General Dental Consultation & Treatment",
            tooth: null,
            quantity: 1,
            unit_price: Number(invoice.total_amount || props.payment.amount),
            total: Number(invoice.total_amount || props.payment.amount),
        });
    }

    return list;
});

// Primary service label for quick identification
const primaryServiceName = computed(() => {
    if (props.payment.invoice?.treatment?.procedure_name) {
        const proc = props.payment.invoice.treatment.procedure_name;
        const tooth = props.payment.invoice.treatment.tooth_number;
        return tooth ? `${proc} (Tooth #${tooth})` : proc;
    }
    if (props.payment.invoice?.appointment?.service) {
        return props.payment.invoice.appointment.service;
    }
    if (availedServices.value.length === 1) {
        const first = availedServices.value[0];
        return first.tooth ? `${first.name} (Tooth #${first.tooth})` : first.name;
    }
    if (availedServices.value.length > 1) {
        return `${availedServices.value[0].name} (+${availedServices.value.length - 1} more)`;
    }
    return "Dental Care & Clinical Treatment";
});

const formatCurrency = (val) => {
    return (
        "₱" +
        Number(val || 0).toLocaleString("en-US", {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2,
        })
    );
};

const formatDate = (dateStr) => {
    if (!dateStr) return "N/A";
    return new Date(dateStr).toLocaleDateString("en-US", {
        year: "numeric",
        month: "short",
        day: "numeric",
    });
};

const formatDateTime = (dateStr) => {
    if (!dateStr) return "N/A";
    return new Date(dateStr).toLocaleString("en-US", {
        year: "numeric",
        month: "short",
        day: "numeric",
        hour: "2-digit",
        minute: "2-digit",
    });
};

const printReceipt = () => {
    window.print();
};
</script>

<template>
    <Head
        :title="`Official Receipt #${payment.payment_number} - Vitality Smile`"
    />

    <div
        class="min-h-screen bg-slate-100 dark:bg-slate-950 py-8 px-4 sm:px-6 lg:px-8 print:bg-white print:p-0 transition-colors duration-150"
    >
        <!-- Floating Navigation / Print Actions Bar -->
        <div
            class="max-w-xl mx-auto mb-6 print:hidden flex flex-col sm:flex-row items-center justify-between gap-3 bg-white dark:bg-slate-900 p-4 rounded-2xl border border-gray-200 dark:border-slate-800 shadow-sm"
        >
            <div class="flex items-center gap-2">
                <Link
                    :href="route('payments.index')"
                    class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg border border-gray-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-xs font-semibold text-gray-700 dark:text-slate-200 hover:bg-gray-50 dark:hover:bg-slate-700 transition"
                >
                    <i class="pi pi-arrow-left text-xs"></i>
                    <span>All Receipts</span>
                </Link>
                <Link
                    v-if="payment.invoice"
                    :href="route('billing.show', payment.invoice.id)"
                    class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg border border-teal-200 dark:border-teal-800/50 bg-teal-50 dark:bg-teal-950/40 text-xs font-semibold text-teal-800 dark:text-teal-300 hover:bg-teal-100 dark:hover:bg-teal-900/50 transition"
                >
                    <i class="pi pi-file-pdf text-xs"></i>
                    <span>Statement #{{ payment.invoice.invoice_number }}</span>
                </Link>
            </div>

            <div class="flex items-center gap-2 w-full sm:w-auto">
                <ThemeToggle />

                <button
                    type="button"
                    @click="printReceipt"
                    class="flex-1 sm:flex-none inline-flex items-center justify-center gap-2 px-5 py-2.5 rounded-xl bg-teal-700 text-white text-xs font-bold shadow-sm hover:bg-teal-800 transition"
                >
                    <i class="pi pi-print text-sm"></i>
                    <span>Print Receipt</span>
                </button>
            </div>
        </div>

        <!-- PRINTABLE OFFICIAL PAYMENT RECEIPT -->
        <div
            class="max-w-xl mx-auto bg-white dark:bg-slate-900 rounded-3xl border border-gray-200 dark:border-slate-800 shadow-lg overflow-hidden p-6 sm:p-10 receipt-card print:border-none print:shadow-none print:p-0 print:m-0 print:bg-white print:text-black"
        >
            <!-- 1. Clinic Header Branding -->
            <div class="text-center border-b border-gray-200 pb-6">
                <div
                    class="inline-flex items-center justify-center h-12 w-12 rounded-2xl bg-teal-700 text-white mb-2.5 shadow-sm"
                >
                    <i class="pi pi-shield text-xl"></i>
                </div>
                <h1 class="text-xl font-black text-gray-900 tracking-tight">
                    Vitality Smile Dental Clinic
                </h1>
                <p class="text-xs text-teal-800 font-semibold tracking-wide">
                    General Dentistry & Orthodontics
                </p>
                <p class="text-xs text-gray-500 mt-1">
                    Unit 1 Threo Commercial Building B2 L3A Casan Residencia
                    Subdivision <br />
                    Barangay Malitlit, Sta Rosa Laguna 4026
                </p>
                <p class="text-xs text-gray-500">
                    Cel: 0977-328-1243 • Email: vitalitysmile2025@gmail.com
                </p>

                <div
                    class="mt-4 pt-3 border-t border-dashed border-gray-200 flex items-center justify-between text-xs"
                >
                    <span
                        class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[11px] font-extrabold uppercase tracking-wider bg-teal-50 text-teal-900 border border-teal-200"
                    >
                        <i class="pi pi-receipt text-[10px]"></i>
                        Official Payment Receipt
                    </span>
                    <span
                        class="font-mono font-extrabold text-teal-900 text-sm"
                    >
                        {{ payment.payment_number }}
                    </span>
                </div>
            </div>

            <!-- 2. Patient & Transaction Information -->
            <div class="py-5 border-b border-gray-200 text-xs space-y-2">
                <div class="flex justify-between items-start">
                    <span class="text-gray-500">Received From:</span>
                    <span class="font-bold text-gray-900 text-right">
                        {{
                            payment.patient
                                ? `${payment.patient.first_name} ${payment.patient.last_name}`
                                : "Patient Client"
                        }}
                    </span>
                </div>
                <div
                    v-if="payment.patient?.patient_id"
                    class="flex justify-between items-center"
                >
                    <span class="text-gray-500">Patient Record ID:</span>
                    <span class="font-mono text-gray-700">{{
                        payment.patient.patient_id
                    }}</span>
                </div>
                <div
                    v-if="payment.patient?.contact_number"
                    class="flex justify-between items-center"
                >
                    <span class="text-gray-500">Contact Number:</span>
                    <span class="text-gray-700">{{
                        payment.patient.contact_number
                    }}</span>
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-gray-500">Payment Date:</span>
                    <span class="font-medium text-gray-900">{{
                        formatDate(payment.payment_date)
                    }}</span>
                </div>
                <div
                    v-if="payment.created_at"
                    class="flex justify-between items-center"
                >
                    <span class="text-gray-500 dark:text-slate-400">Time Processed:</span>
                    <span class="text-gray-700 dark:text-slate-300">{{
                        formatDateTime(payment.created_at)
                    }}</span>
                </div>
                <div
                    v-if="payment.invoice"
                    class="flex justify-between items-center"
                >
                    <span class="text-gray-500 dark:text-slate-400">Applied Statement / Bill:</span>
                    <span class="font-mono font-semibold text-teal-800 dark:text-teal-400"
                        >#{{ payment.invoice.invoice_number }}</span
                    >
                </div>
                <div
                    v-if="attendingDentist"
                    class="flex justify-between items-center"
                >
                    <span class="text-gray-500 dark:text-slate-400">Attending Clinician:</span>
                    <span class="font-medium text-gray-900 dark:text-white">{{
                        attendingDentist
                    }}</span>
                </div>

                <!-- Primary Dental Service Availed -->
                <div class="flex justify-between items-start pt-2 border-t border-dashed border-gray-200 dark:border-slate-800">
                    <span class="text-gray-500 dark:text-slate-400 font-semibold">Service / Procedure Availed:</span>
                    <span class="font-black text-teal-900 dark:text-teal-300 text-right max-w-[65%] text-xs">
                        {{ primaryServiceName }}
                    </span>
                </div>
            </div>

            <!-- 3. Dental Services / Procedures Availed -->
            <div
                v-if="availedServices.length > 0"
                class="py-4 border-b border-gray-200 dark:border-slate-800 text-xs space-y-2.5"
            >
                <div class="flex items-center justify-between">
                    <p
                        class="font-bold uppercase tracking-wider text-teal-900 dark:text-teal-300 text-[11px] flex items-center gap-1.5"
                    >
                        <i class="pi pi-list text-[10px]"></i>
                        Dental Services / Procedures Availed:
                    </p>
                    <span class="text-[10px] text-gray-500 dark:text-slate-400 font-medium">
                        {{ availedServices.length }} {{ availedServices.length === 1 ? 'procedure' : 'procedures' }}
                    </span>
                </div>

                <div
                    class="space-y-2 bg-gray-50 dark:bg-slate-800/60 p-3.5 rounded-2xl border border-gray-100 dark:border-slate-700/80"
                >
                    <div
                        v-for="(item, idx) in availedServices"
                        :key="idx"
                        class="flex justify-between items-start text-xs pb-2 border-b border-dashed border-gray-200 dark:border-slate-700 last:border-b-0 last:pb-0"
                    >
                        <div class="pr-2 space-y-0.5">
                            <span class="text-gray-900 dark:text-white font-bold flex items-center gap-1.5 flex-wrap">
                                {{ item.name }}
                                <span
                                    v-if="item.tooth"
                                    class="inline-flex items-center px-1.5 py-0.2 rounded text-[10px] font-bold bg-blue-50 text-blue-700 dark:bg-blue-950/70 dark:text-blue-300 border border-blue-200 dark:border-blue-800"
                                >
                                    Tooth #{{ item.tooth }}
                                </span>
                            </span>
                            <span
                                v-if="item.quantity > 1"
                                class="text-[10px] text-gray-500 dark:text-slate-400 block"
                            >
                                Qty: {{ item.quantity }} × {{ formatCurrency(item.unit_price) }}
                            </span>
                        </div>
                        <span class="text-gray-900 dark:text-white font-extrabold font-mono whitespace-nowrap pt-0.5">
                            {{ formatCurrency(item.total) }}
                        </span>
                    </div>
                </div>

                <!-- Clinical Diagnosis / Note if attached to treatment -->
                <div
                    v-if="payment.invoice?.treatment?.diagnosis"
                    class="py-2 px-3 bg-teal-50/60 dark:bg-teal-950/40 rounded-xl border border-teal-100 dark:border-teal-900/40 text-[11px] text-teal-950 dark:text-teal-200 flex items-center gap-2"
                >
                    <i class="pi pi-info-circle text-teal-600 dark:text-teal-400 text-xs shrink-0"></i>
                    <span><strong>Clinical Diagnosis:</strong> {{ payment.invoice.treatment.diagnosis }}</span>
                </div>
            </div>

            <!-- 4. Payment Method & Financial Breakdown -->
            <div class="py-5 border-b border-gray-200 text-xs space-y-2.5">
                <div class="flex justify-between items-center">
                    <span class="text-gray-500">Payment Method:</span>
                    <span
                        class="font-bold text-gray-900 bg-gray-100 px-2 py-0.5 rounded-md"
                    >
                        {{ payment.payment_method }}
                    </span>
                </div>

                <div
                    v-if="payment.reference_number"
                    class="flex justify-between items-center"
                >
                    <span class="text-gray-500">Reference / Approval No:</span>
                    <span class="font-mono font-bold text-teal-900">{{
                        payment.reference_number
                    }}</span>
                </div>

                <div
                    v-if="payment.invoice"
                    class="flex justify-between items-center text-gray-600 pt-1"
                >
                    <span>Total Bill on Statement:</span>
                    <span>{{
                        formatCurrency(payment.invoice.net_amount)
                    }}</span>
                </div>

                <!-- AMOUNT PAID -->
                <div
                    class="flex justify-between items-center py-2.5 bg-emerald-50/70 dark:bg-emerald-950/50 border border-emerald-200/80 dark:border-emerald-700/60 px-3.5 rounded-xl font-bold print:bg-emerald-50 print:border-emerald-200"
                >
                    <span
                        class="text-emerald-950 dark:text-emerald-200 print:text-emerald-950 font-bold uppercase tracking-wide"
                        >Amount Paid / Credited:</span
                    >
                    <span class="text-lg font-black text-emerald-700 dark:text-emerald-400 print:text-emerald-700">{{
                        formatCurrency(payment.amount)
                    }}</span>
                </div>

                <!-- Money Given (Cash Tendered) -->
                <div
                    v-if="payment.amount_tendered"
                    class="flex justify-between items-center text-gray-700 dark:text-slate-300"
                >
                    <span>Money Given by Patient (Tendered):</span>
                    <span class="font-bold font-mono">{{
                        formatCurrency(payment.amount_tendered)
                    }}</span>
                </div>

                <!-- Change Returned -->
                <div class="flex justify-between items-center text-gray-700 dark:text-slate-300">
                    <span>Change Returned:</span>
                    <span
                        class="font-bold font-mono"
                        :class="
                            Number(payment.change_amount) > 0
                                ? 'text-emerald-700 dark:text-emerald-400 font-extrabold'
                                : 'text-gray-500 dark:text-slate-400'
                        "
                    >
                        {{ formatCurrency(payment.change_amount || 0) }}
                    </span>
                </div>

                <!-- Remaining Statement Balance -->
                <div
                    v-if="payment.invoice"
                    class="flex justify-between items-center pt-1 border-t border-dashed border-gray-200 dark:border-slate-700 font-semibold"
                >
                    <span class="text-gray-600 dark:text-slate-400"
                        >Remaining Balance on Account:</span
                    >
                    <span
                        :class="
                            Number(payment.invoice.balance) > 0
                                ? 'text-amber-700 dark:text-amber-400 font-bold'
                                : 'text-emerald-700 dark:text-emerald-400 font-bold'
                        "
                    >
                        {{ formatCurrency(payment.invoice.balance) }}
                        <span
                            v-if="Number(payment.invoice.balance) <= 0"
                            class="ml-1 text-[10px] font-bold text-emerald-800 dark:text-emerald-300 bg-emerald-100 dark:bg-emerald-950/60 border border-emerald-200 dark:border-emerald-800/60 px-1.5 py-0.2 rounded-full uppercase"
                        >
                            Fully Settled
                        </span>
                        <span
                            v-else
                            class="ml-1 text-[10px] font-bold text-amber-800 dark:text-amber-300 bg-amber-100 dark:bg-amber-950/60 border border-amber-200 dark:border-amber-800/60 px-1.5 py-0.2 rounded-full uppercase"
                        >
                            Partial Balance
                        </span>
                    </span>
                </div>
            </div>

            <!-- 5. Remarks / Notes -->
            <div
                v-if="payment.notes"
                class="py-3 border-b border-gray-200 text-xs"
            >
                <span
                    class="text-gray-400 block text-[10px] font-semibold uppercase"
                    >Notes / Remarks:</span
                >
                <p class="text-gray-600 mt-0.5 italic">{{ payment.notes }}</p>
            </div>

            <!-- 6. Cashier & Clinic Sign-Off -->
            <div
                class="mt-8 pt-4 flex flex-col sm:flex-row justify-between items-end gap-6 text-xs text-gray-500"
            >
                <div>
                    <p class="font-semibold text-gray-700">Received By:</p>
                    <p class="text-gray-900 font-medium">
                        {{
                            payment.receiver?.name ||
                            "Authorized Clinic Cashier"
                        }}
                    </p>
                    <p class="text-[10px] text-gray-400 mt-1">
                        Official Clinical Transaction Record
                    </p>
                </div>

                <div class="text-center w-48">
                    <div class="border-b border-gray-400 pb-1 mb-1"></div>
                    <p
                        class="font-bold text-gray-700 text-[11px] uppercase tracking-wider"
                    >
                        Authorized Signature
                    </p>
                    <p class="text-[10px] text-gray-400">
                        Vitality Smile Dental Clinic
                    </p>
                </div>
            </div>

            <div
                class="mt-6 pt-4 border-t border-gray-100 text-center text-[10px] text-gray-400"
            >
                <p>
                    Thank you for trusting Vitality Smile Dental Clinic! Please
                    keep this receipt for your records and tax / HMO
                    reimbursement claims.
                </p>
            </div>
        </div>
    </div>
</template>

<style>
@media print {
    body {
        background-color: white !important;
        color: black !important;
    }
    nav,
    header,
    footer,
    button,
    .print\:hidden {
        display: none !important;
    }
    .receipt-card {
        border: none !important;
        box-shadow: none !important;
        padding: 0 !important;
        max-width: 100% !important;
    }
}
</style>
