<script setup>
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import Modal from "@/Components/Modal.vue";
import { Head, Link, router, useForm } from "@inertiajs/vue3";
import { ref, computed } from "vue";

const props = defineProps({
    invoice: {
        type: Object,
        required: true,
    },
});

// Payment recording modal
const showPaymentModal = ref(false);

const paymentForm = useForm({
    amount: props.invoice?.balance || 0,
    amount_tendered: props.invoice?.balance || 0,
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
    const currentBalance = parseFloat(props.invoice?.balance) || 0;
    const applied = parseFloat(paymentForm.amount) || 0;
    return Math.max(0, Math.round((currentBalance - applied) * 100) / 100);
});

const isInsufficientPayment = computed(() => {
    const currentBalance = parseFloat(props.invoice?.balance) || 0;
    const applied = parseFloat(paymentForm.amount) || 0;
    return applied < currentBalance;
});

const onTenderedInput = () => {
    const currentBalance = parseFloat(props.invoice?.balance) || 0;
    const tendered = parseFloat(paymentForm.amount_tendered);

    if (isNaN(tendered) || tendered <= 0) {
        paymentForm.amount = "";
        paymentForm.change_amount = 0;
        return;
    }

    if (tendered >= currentBalance) {
        paymentForm.amount = currentBalance;
        paymentForm.change_amount =
            Math.round((tendered - currentBalance) * 100) / 100;
    } else {
        paymentForm.amount = tendered;
        paymentForm.change_amount = 0;
    }
};

const setExactTendered = () => {
    paymentForm.amount_tendered = props.invoice?.balance || 0;
    onTenderedInput();
};

const addTenderedAmount = (addVal) => {
    const current = parseFloat(paymentForm.amount_tendered) || 0;
    paymentForm.amount_tendered = (current + addVal).toFixed(2);
    onTenderedInput();
};

const openPaymentModal = () => {
    paymentForm.amount = props.invoice?.balance || 0;
    paymentForm.amount_tendered = props.invoice?.balance || 0;
    paymentForm.change_amount = 0;
    paymentForm.payment_date = new Date().toISOString().split("T")[0];
    paymentForm.payment_method = "Cash";
    paymentForm.reference_number = "";
    paymentForm.notes = "";
    showPaymentModal.value = true;
};

const submitPayment = () => {
    if (!props.invoice?.id) return;
    paymentForm.change_amount = calculatedChange.value;
    paymentForm.post(route("payments.store", props.invoice.id), {
        preserveScroll: true,
        onSuccess: () => {
            showPaymentModal.value = false;
        },
    });
};

const deletePayment = (paymentId) => {
    if (
        confirm(
            "Are you sure you want to void/delete this payment receipt? The invoice balance will be recalculated.",
        )
    ) {
        router.delete(route("payments.destroy", paymentId), {
            preserveScroll: true,
        });
    }
};

const printInvoice = () => {
    window.print();
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

const formatStatus = (status) => {
    if (!status) return "Unpaid";
    return String(status).replace(/_/g, " ");
};
</script>

<template>
    <Head
        :title="
            invoice?.invoice_number
                ? `Invoice ${invoice.invoice_number} - Vitality Smile`
                : 'Vitality Smile Dental Invoice'
        "
    />

    <AuthenticatedLayout>
        <template #header>
            <div
                class="print:hidden flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4"
            >
                <div>
                    <div class="flex items-center gap-2">
                        <Link
                            :href="route('billing.index')"
                            class="text-xs font-semibold text-teal-700 hover:text-teal-900 inline-flex items-center gap-1"
                        >
                            <i class="pi pi-arrow-left text-[10px]"></i> Back to
                            Billing Ledger
                        </Link>
                    </div>
                    <div class="flex items-center gap-3 mt-1">
                        <h2
                            class="text-xl font-bold text-gray-900 sm:text-2xl font-mono"
                        >
                            {{ invoice?.invoice_number || "Invoice" }}
                        </h2>
                        <span
                            :class="[
                                invoice?.status === 'paid'
                                    ? 'bg-green-100 text-green-800 border-green-200'
                                    : invoice?.status === 'partially_paid'
                                      ? 'bg-yellow-100 text-yellow-800 border-yellow-200'
                                      : invoice?.status === 'cancelled'
                                        ? 'bg-gray-100 text-gray-800 border-gray-200'
                                        : 'bg-red-100 text-red-800 border-red-200',
                                'px-2.5 py-0.5 rounded-full text-xs font-bold border capitalize',
                            ]"
                        >
                            {{ formatStatus(invoice?.status) }}
                        </span>
                    </div>
                </div>

                <div class="flex items-center gap-2.5">
                    <!-- Print Button -->
                    <button
                        type="button"
                        @click="printInvoice"
                        class="inline-flex items-center justify-center rounded-lg border border-gray-300 bg-white px-3.5 py-2 text-xs font-semibold text-gray-700 shadow-sm hover:bg-gray-50 transition"
                    >
                        <i class="pi pi-print mr-1.5 text-xs text-gray-500"></i>
                        Print Statement
                    </button>

                    <!-- Record Payment Button (Staff / Admin) -->
                    <button
                        v-if="
                            (invoice?.balance || 0) > 0 &&
                            invoice?.status !== 'cancelled' &&
                            ['admin', 'secretary', 'dentist'].includes(
                                $page.props.auth.user?.role,
                            )
                        "
                        type="button"
                        @click="openPaymentModal"
                        class="inline-flex items-center justify-center rounded-lg bg-teal-700 px-4 py-2 text-xs font-semibold text-white shadow-sm hover:bg-teal-800 transition focus:outline-none"
                    >
                        <i class="pi pi-credit-card mr-1.5 text-xs"></i>
                        Record Payment
                    </button>
                </div>
            </div>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8 space-y-8">
                <!-- PRINTABLE INVOICE DOCUMENT -->
                <div
                    class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden p-8 sm:p-12 print:border-none print:shadow-none print:p-0"
                >
                    <!-- 1. Header / Clinic Branding -->
                    <div
                        class="flex flex-col sm:flex-row sm:items-start justify-between border-b border-gray-200 pb-8 gap-6"
                    >
                        <div class="space-y-1.5">
                            <div class="flex items-center gap-2">
                                <div
                                    class="h-9 w-9 rounded-lg bg-teal-700 text-white flex items-center justify-center font-bold text-lg"
                                >
                                    VS
                                </div>
                                <h1
                                    class="text-xl font-extrabold text-gray-900 tracking-tight"
                                >
                                    Vitality Smile Dental Clinic
                                </h1>
                            </div>
                            <p class="text-xs text-gray-500">
                                General Dentistry & Orthodontics
                            </p>
                            <p class="text-xs text-gray-600">
                                Unit1 Threo Commercial Building B2 L3A Casan
                                Residencia <br />
                                Subdivision Barangay Malitlit, Sta Rosa Laguna
                                4026
                            </p>
                            <p class="text-xs text-gray-600">
                                Cel: 0977-328-1243 • Email:
                                vitalitysmile2025@gmail.com
                            </p>
                        </div>

                        <div class="sm:text-right space-y-1">
                            <span
                                class="text-xs font-bold uppercase tracking-wider text-teal-800 bg-teal-50 px-2.5 py-1 rounded-md border border-teal-100"
                            >
                                Statement of Account
                            </span>
                            <h2
                                class="text-2xl font-black font-mono text-gray-900 mt-2"
                            >
                                {{ invoice.invoice_number }}
                            </h2>
                            <p class="text-xs text-gray-500">
                                <span class="font-semibold text-gray-700"
                                    >Date Issued:</span
                                >
                                {{ formatDate(invoice.invoice_date) }}
                            </p>
                            <p class="text-xs text-gray-500">
                                <span class="font-semibold text-gray-700"
                                    >Due Date:</span
                                >
                                {{ formatDate(invoice.due_date) }}
                            </p>
                        </div>
                    </div>

                    <!-- 2. Patient & Clinical Context -->
                    <div
                        class="grid grid-cols-1 sm:grid-cols-2 gap-6 py-6 border-b border-gray-200 text-xs"
                    >
                        <!-- Patient Details -->
                        <div>
                            <span
                                class="font-bold text-gray-400 uppercase tracking-wider"
                                >Bill To:</span
                            >
                            <div v-if="invoice.patient" class="mt-2 space-y-1">
                                <p class="text-base font-bold text-gray-900">
                                    {{ invoice.patient.first_name }}
                                    {{ invoice.patient.last_name }}
                                </p>
                                <p class="text-gray-600">
                                    <span class="font-semibold text-gray-700"
                                        >Patient ID:</span
                                    >
                                    {{ invoice.patient.patient_id }}
                                </p>
                                <p class="text-gray-600">
                                    <span class="font-semibold text-gray-700"
                                        >Contact:</span
                                    >
                                    {{
                                        invoice.patient.contact_number || "N/A"
                                    }}
                                </p>
                                <p class="text-gray-600">
                                    <span class="font-semibold text-gray-700"
                                        >Address:</span
                                    >
                                    {{
                                        invoice.patient.address ||
                                        "Metro Manila, PH"
                                    }}
                                </p>
                            </div>
                        </div>

                        <!-- Attending Clinic Records -->
                        <div class="sm:text-right">
                            <span
                                class="font-bold text-gray-400 uppercase tracking-wider"
                                >Clinical Details:</span
                            >
                            <div class="mt-2 space-y-1">
                                <p v-if="invoice.treatment">
                                    <span class="font-semibold text-gray-700"
                                        >Procedure:</span
                                    >
                                    <span
                                        class="font-bold text-gray-900 ml-1"
                                        >{{
                                            invoice.treatment.procedure_name
                                        }}</span
                                    >
                                    <span
                                        v-if="invoice.treatment.tooth_number"
                                        class="text-gray-500 ml-1"
                                        >(Tooth #{{
                                            invoice.treatment.tooth_number
                                        }})</span
                                    >
                                </p>
                                <p v-if="invoice.treatment?.dentist">
                                    <span class="font-semibold text-gray-700"
                                        >Dentist:</span
                                    >
                                    Dr. {{ invoice.treatment.dentist.name }}
                                </p>
                                <p v-else-if="invoice.appointment?.dentist">
                                    <span class="font-semibold text-gray-700"
                                        >Dentist:</span
                                    >
                                    Dr. {{ invoice.appointment.dentist.name }}
                                </p>
                                <p v-else>
                                    <span class="font-semibold text-gray-700"
                                        >Dentist:</span
                                    >
                                    Dr. Valdez
                                </p>
                                <p v-if="invoice.appointment">
                                    <span class="font-semibold text-gray-700"
                                        >Appointment:</span
                                    >
                                    {{
                                        formatDate(
                                            invoice.appointment
                                                .appointment_date,
                                        )
                                    }}
                                    ({{ invoice.appointment.appointment_time }})
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- 3. Line Items Table -->
                    <div class="py-6">
                        <table
                            class="min-w-full divide-y divide-gray-200 text-xs"
                        >
                            <thead>
                                <tr
                                    class="text-gray-500 uppercase tracking-wider font-semibold"
                                >
                                    <th scope="col" class="py-3 text-left w-12">
                                        #
                                    </th>
                                    <th scope="col" class="py-3 text-left">
                                        Service / Procedure Description
                                    </th>
                                    <th
                                        scope="col"
                                        class="py-3 text-center w-20"
                                    >
                                        Qty
                                    </th>
                                    <th
                                        scope="col"
                                        class="py-3 text-right w-32"
                                    >
                                        Unit Price
                                    </th>
                                    <th
                                        scope="col"
                                        class="py-3 text-right w-32"
                                    >
                                        Amount
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                <tr
                                    v-for="(item, idx) in invoice.items"
                                    :key="item.id"
                                >
                                    <td class="py-3 text-gray-400">
                                        {{ idx + 1 }}
                                    </td>
                                    <td
                                        class="py-3 font-semibold text-gray-900"
                                    >
                                        {{ item.description }}
                                    </td>
                                    <td class="py-3 text-center text-gray-600">
                                        {{ item.quantity }}
                                    </td>
                                    <td class="py-3 text-right text-gray-600">
                                        {{ formatCurrency(item.unit_price) }}
                                    </td>
                                    <td
                                        class="py-3 text-right font-bold text-gray-900"
                                    >
                                        {{ formatCurrency(item.total) }}
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- 4. Summary & Balances -->
                    <div
                        class="border-t border-gray-200 pt-6 flex flex-col sm:flex-row justify-between gap-6"
                    >
                        <!-- Notes / Terms -->
                        <div class="sm:max-w-xs space-y-2 text-xs">
                            <span class="font-bold text-gray-700"
                                >Remarks & Terms:</span
                            >
                            <p
                                class="text-gray-600 bg-gray-50 p-3 rounded-lg border border-gray-100 whitespace-pre-line leading-relaxed"
                            >
                                {{
                                    invoice.notes ||
                                    "Official receipt will be issued upon full settlement of account."
                                }}
                            </p>
                        </div>

                        <!-- Calculations Table -->
                        <div class="sm:w-72 space-y-2 text-xs">
                            <div
                                class="flex justify-between text-gray-600 py-1"
                            >
                                <span>Gross Subtotal:</span>
                                <span class="font-semibold text-gray-900">{{
                                    formatCurrency(invoice.total_amount)
                                }}</span>
                            </div>

                            <div
                                v-if="invoice.discount > 0"
                                class="flex justify-between text-green-700 py-1"
                            >
                                <span>Discount:</span>
                                <span
                                    >-{{
                                        formatCurrency(invoice.discount)
                                    }}</span
                                >
                            </div>

                            <div
                                v-if="invoice.tax > 0"
                                class="flex justify-between text-gray-600 py-1"
                            >
                                <span>Tax / VAT:</span>
                                <span>+{{ formatCurrency(invoice.tax) }}</span>
                            </div>

                            <div
                                class="border-t border-gray-200 pt-2 flex justify-between font-bold text-sm text-gray-900"
                            >
                                <span>Net Total:</span>
                                <span>{{
                                    formatCurrency(invoice.net_amount)
                                }}</span>
                            </div>

                            <div
                                class="flex justify-between text-green-700 font-semibold py-1"
                            >
                                <span>Total Paid:</span>
                                <span>{{
                                    formatCurrency(invoice.paid_amount)
                                }}</span>
                            </div>

                            <div
                                :class="[
                                    invoice.balance > 0
                                        ? 'bg-red-50 text-red-700 border-red-200'
                                        : 'bg-green-50 text-green-800 border-green-200',
                                    'p-3 rounded-xl border flex justify-between items-center text-sm font-extrabold mt-3',
                                ]"
                            >
                                <span>Remaining Due:</span>
                                <span class="text-base">{{
                                    formatCurrency(invoice.balance)
                                }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- 5. Payment Receipts History (if any) -->
                    <div
                        v-if="invoice.payments && invoice.payments.length > 0"
                        class="mt-8 pt-8 border-t border-gray-200"
                    >
                        <h4
                            class="text-xs font-bold uppercase tracking-wider text-gray-700 mb-3 flex items-center gap-1.5"
                        >
                            <i class="pi pi-receipt text-teal-700"></i> Payment
                            Receipts Applied
                        </h4>

                        <div
                            class="overflow-x-auto rounded-xl border border-gray-200"
                        >
                            <table
                                class="min-w-full divide-y divide-gray-200 text-xs"
                            >
                                <thead
                                    class="bg-gray-50 text-gray-500 font-semibold"
                                >
                                    <tr>
                                        <th
                                            scope="col"
                                            class="px-4 py-2.5 text-left"
                                        >
                                            Receipt #
                                        </th>
                                        <th
                                            scope="col"
                                            class="px-4 py-2.5 text-left"
                                        >
                                            Payment Date
                                        </th>
                                        <th
                                            scope="col"
                                            class="px-4 py-2.5 text-left"
                                        >
                                            Method
                                        </th>
                                        <th
                                            scope="col"
                                            class="px-4 py-2.5 text-left"
                                        >
                                            Reference #
                                        </th>
                                        <th
                                            scope="col"
                                            class="px-4 py-2.5 text-left"
                                        >
                                            Cashier / Staff
                                        </th>
                                        <th
                                            scope="col"
                                            class="px-4 py-2.5 text-right"
                                        >
                                            Amount Paid
                                        </th>
                                        <th
                                            scope="col"
                                            class="print:hidden px-4 py-2.5 text-right"
                                        >
                                            Receipt / Actions
                                        </th>
                                    </tr>
                                </thead>
                                <tbody
                                    class="divide-y divide-gray-100 bg-white"
                                >
                                    <tr
                                        v-for="pay in invoice.payments"
                                        :key="pay.id"
                                    >
                                        <td
                                            class="px-4 py-2.5 font-mono font-bold text-teal-800"
                                        >
                                            {{ pay.payment_number }}
                                        </td>
                                        <td class="px-4 py-2.5 text-gray-600">
                                            {{ formatDate(pay.payment_date) }}
                                        </td>
                                        <td class="px-4 py-2.5">
                                            <span
                                                class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-teal-50 text-teal-700 border border-teal-100"
                                            >
                                                {{ pay.payment_method }}
                                            </span>
                                        </td>
                                        <td
                                            class="px-4 py-2.5 text-gray-500 font-mono"
                                        >
                                            {{ pay.reference_number || "-" }}
                                        </td>
                                        <td class="px-4 py-2.5 text-gray-600">
                                            {{ pay.receiver?.name || "Staff" }}
                                        </td>
                                        <td
                                            class="px-4 py-2.5 text-right font-bold text-green-700"
                                        >
                                            <div>
                                                {{ formatCurrency(pay.amount) }}
                                            </div>
                                            <div
                                                v-if="
                                                    pay.amount_tendered &&
                                                    Number(
                                                        pay.amount_tendered,
                                                    ) > Number(pay.amount)
                                                "
                                                class="text-[10px] text-gray-500 font-normal"
                                            >
                                                Tendered:
                                                {{
                                                    formatCurrency(
                                                        pay.amount_tendered,
                                                    )
                                                }}
                                                • Change:
                                                {{
                                                    formatCurrency(
                                                        pay.change_amount || 0,
                                                    )
                                                }}
                                            </div>
                                        </td>
                                        <td
                                            class="print:hidden px-4 py-2.5 text-right space-x-1"
                                        >
                                            <Link
                                                :href="
                                                    route(
                                                        'payments.receipt',
                                                        pay.id,
                                                    )
                                                "
                                                target="_blank"
                                                class="text-teal-700 hover:text-teal-900 bg-teal-50 hover:bg-teal-100 px-2 py-0.5 rounded text-[11px] font-semibold border border-teal-200 inline-flex items-center gap-1 transition"
                                                title="Print Official Payment Receipt"
                                            >
                                                <i
                                                    class="pi pi-print text-[10px]"
                                                ></i>
                                                <span>Print Receipt</span>
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
                                                @click="deletePayment(pay.id)"
                                                class="text-red-500 hover:text-red-700 text-xs p-1"
                                                title="Void Payment"
                                            >
                                                <i class="pi pi-trash"></i>
                                            </button>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Clinic Stamp & Sign-off Signature line -->
                    <div
                        class="mt-12 pt-8 border-t border-gray-100 flex flex-col sm:flex-row justify-between items-end gap-6 text-xs text-gray-500"
                    >
                        <div>
                            <p class="italic">
                                This statement is computer-generated and serves
                                as an official clinical bill.
                            </p>
                            <p class="text-[10px] text-gray-400 mt-1">
                                Vitality Smile System • Generated:
                                {{ new Date().toLocaleString() }}
                            </p>
                        </div>
                        <div class="text-center w-56">
                            <div
                                class="border-b border-gray-300 pb-1 mb-1"
                            ></div>
                            <p class="font-semibold text-gray-700">
                                Authorized Signature / Cashier
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Quick Payment Modal -->
        <Modal
            :show="showPaymentModal"
            @close="showPaymentModal = false"
            maxWidth="lg"
        >
            <div class="p-6">
                <div
                    class="flex items-center justify-between border-b border-gray-100 pb-3"
                >
                    <div>
                        <span
                            class="text-xs font-semibold text-teal-700 uppercase tracking-wider"
                            >Payment Receipt & Cashier Desk</span
                        >
                        <h3 class="text-lg font-bold text-gray-900 mt-0.5">
                            Record Payment for {{ invoice.invoice_number }}
                        </h3>
                    </div>
                    <button
                        type="button"
                        @click="showPaymentModal = false"
                        class="text-gray-400 hover:text-gray-600"
                    >
                        <i class="pi pi-times"></i>
                    </button>
                </div>

                <form @submit.prevent="submitPayment" class="mt-4 space-y-4">
                    <!-- Balance Summary Bar -->
                    <div
                        class="bg-slate-50 p-3.5 rounded-xl border border-gray-200 grid grid-cols-2 gap-3 text-xs"
                    >
                        <div>
                            <span class="text-gray-500 block"
                                >Total Statement Billed:</span
                            >
                            <span class="font-bold text-gray-900 text-sm">{{
                                formatCurrency(invoice.net_amount)
                            }}</span>
                        </div>
                        <div class="text-right">
                            <span class="text-gray-500 block"
                                >Current Balance Due:</span
                            >
                            <span class="font-extrabold text-red-600 text-sm">{{
                                formatCurrency(invoice.balance)
                            }}</span>
                        </div>
                    </div>

                    <!-- Money Given by Patient (Input for cash/money tendered) -->
                    <div
                        class="rounded-xl border-2 border-teal-500/30 bg-teal-50/20 p-4"
                    >
                        <div class="flex items-center justify-between mb-1.5">
                            <label
                                class="block text-xs font-bold text-teal-950 uppercase tracking-wide"
                            >
                                Money Given by Patient / Cash Received
                                <span class="text-red-500">*</span>
                            </label>
                            <span class="text-[11px] text-teal-700 font-medium"
                                >Cash Tendered</span
                            >
                        </div>
                        <div class="relative">
                            <span
                                class="absolute inset-y-0 left-0 pl-3.5 flex items-center text-teal-700 text-base font-extrabold"
                                >₱</span
                            >
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
                            <span
                                class="text-[10px] text-gray-400 font-medium mr-1"
                                >Quick Fill:</span
                            >
                            <button
                                type="button"
                                @click="setExactTendered"
                                class="px-2 py-1 rounded-md text-[11px] font-semibold bg-white border border-teal-300 text-teal-800 hover:bg-teal-50 shadow-2xs"
                            >
                                Exact Balance (₱{{
                                    Number(invoice.balance).toLocaleString(
                                        "en-US",
                                        { minimumFractionDigits: 2 },
                                    )
                                }})
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
                    <div
                        class="rounded-xl border p-4 transition-all"
                        :class="[
                            isInsufficientPayment
                                ? 'bg-amber-50/70 border-amber-200 text-amber-900'
                                : 'bg-emerald-50/70 border-emerald-200 text-emerald-950',
                        ]"
                    >
                        <div class="grid grid-cols-2 gap-4">
                            <!-- Change Calculation (when excess) -->
                            <div class="border-r border-gray-200/60 pr-2">
                                <span
                                    class="text-[11px] font-bold uppercase tracking-wider block"
                                    :class="
                                        calculatedChange > 0
                                            ? 'text-emerald-800'
                                            : 'text-gray-500'
                                    "
                                >
                                    Change to Return:
                                </span>
                                <div class="mt-1 flex items-baseline gap-1">
                                    <span
                                        class="text-xl sm:text-2xl font-black"
                                        :class="
                                            calculatedChange > 0
                                                ? 'text-emerald-700'
                                                : 'text-gray-400'
                                        "
                                    >
                                        ₱{{
                                            calculatedChange.toLocaleString(
                                                "en-US",
                                                {
                                                    minimumFractionDigits: 2,
                                                    maximumFractionDigits: 2,
                                                },
                                            )
                                        }}
                                    </span>
                                </div>
                                <p
                                    v-if="calculatedChange > 0"
                                    class="text-[10px] text-emerald-700 mt-0.5 font-medium flex items-center gap-1"
                                >
                                    <i
                                        class="pi pi-check-circle text-[10px]"
                                    ></i>
                                    Excess money received from patient.
                                </p>
                                <p
                                    v-else
                                    class="text-[10px] text-gray-400 mt-0.5"
                                >
                                    No change required (Exact / partial
                                    payment).
                                </p>
                            </div>

                            <!-- Remaining Balance Calculation (when insufficient or fully settled) -->
                            <div class="pl-2">
                                <span
                                    class="text-[11px] font-bold uppercase tracking-wider block"
                                    :class="
                                        isInsufficientPayment
                                            ? 'text-amber-800'
                                            : 'text-emerald-800'
                                    "
                                >
                                    Remaining Invoice Balance:
                                </span>
                                <div class="mt-1 flex items-baseline gap-1">
                                    <span
                                        class="text-xl sm:text-2xl font-black"
                                        :class="
                                            isInsufficientPayment
                                                ? 'text-amber-700'
                                                : 'text-emerald-700'
                                        "
                                    >
                                        ₱{{
                                            calculatedRemainingBalance.toLocaleString(
                                                "en-US",
                                                {
                                                    minimumFractionDigits: 2,
                                                    maximumFractionDigits: 2,
                                                },
                                            )
                                        }}
                                    </span>
                                    <span
                                        v-if="calculatedRemainingBalance === 0"
                                        class="ml-1 text-[10px] font-bold px-1.5 py-0.5 rounded-full bg-emerald-100 text-emerald-800 uppercase"
                                    >
                                        Fully Paid
                                    </span>
                                    <span
                                        v-else
                                        class="ml-1 text-[10px] font-bold px-1.5 py-0.5 rounded-full bg-amber-100 text-amber-800 uppercase"
                                    >
                                        Partial
                                    </span>
                                </div>
                                <p
                                    v-if="isInsufficientPayment"
                                    class="text-[10px] text-amber-700 mt-0.5 font-medium flex items-center gap-1"
                                >
                                    <i
                                        class="pi pi-exclamation-triangle text-[10px]"
                                    ></i>
                                    Insufficient payment. ₱{{
                                        calculatedRemainingBalance.toLocaleString(
                                            "en-US",
                                            { minimumFractionDigits: 2 },
                                        )
                                    }}
                                    added to remaining balance.
                                </p>
                                <p
                                    v-else
                                    class="text-[10px] text-emerald-700 mt-0.5 font-medium flex items-center gap-1"
                                >
                                    <i
                                        class="pi pi-check-circle text-[10px]"
                                    ></i>
                                    Total account settled in full.
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Payment Amount Applied to Invoice -->
                    <div>
                        <div class="flex items-center justify-between mb-1">
                            <label
                                class="block text-xs font-semibold text-gray-700"
                            >
                                Amount Applied to Invoice (₱)
                                <span class="text-red-500">*</span>
                            </label>
                            <span class="text-[11px] text-gray-400"
                                >Actual statement payment</span
                            >
                        </div>
                        <div class="relative">
                            <span
                                class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-500 text-sm font-bold"
                                >₱</span
                            >
                            <input
                                v-model="paymentForm.amount"
                                type="number"
                                step="0.01"
                                min="0.01"
                                :max="invoice.balance"
                                required
                                class="w-full pl-8 rounded-lg border-gray-300 shadow-sm focus:border-teal-500 focus:ring-teal-500 text-sm font-bold text-gray-900 bg-gray-50/50"
                            />
                        </div>
                        <p
                            v-if="paymentForm.errors.amount"
                            class="text-xs text-red-600 mt-1"
                        >
                            {{ paymentForm.errors.amount }}
                        </p>
                    </div>

                    <!-- Payment Method & Date -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label
                                class="block text-xs font-semibold text-gray-700 mb-1"
                            >
                                Payment Method
                                <span class="text-red-500">*</span>
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
                            <label
                                class="block text-xs font-semibold text-gray-700 mb-1"
                            >
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
                        <label
                            class="block text-xs font-semibold text-gray-700 mb-1"
                        >
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
                        <label
                            class="block text-xs font-semibold text-gray-700 mb-1"
                            >Notes / Remarks</label
                        >
                        <input
                            v-model="paymentForm.notes"
                            type="text"
                            placeholder="Optional cashier notes..."
                            class="w-full rounded-lg border-gray-300 shadow-sm focus:border-teal-500 focus:ring-teal-500 text-sm"
                        />
                    </div>

                    <div
                        class="mt-6 flex justify-end gap-3 border-t border-gray-100 pt-4"
                    >
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
                            <span>Confirm Payment</span>
                        </button>
                    </div>
                </form>
            </div>
        </Modal>
    </AuthenticatedLayout>
</template>

<style>
@media print {
    body {
        background-color: white !important;
    }
    nav,
    header,
    footer,
    button,
    .print\:hidden {
        display: none !important;
    }
}
</style>
