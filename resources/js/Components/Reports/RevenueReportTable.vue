<script setup>
import { computed } from "vue";

const props = defineProps({
    report: {
        type: Object,
        required: true,
        default: () => ({ records: [], grouped_by_date: [], grouped_by_week: [], grand_total: {} }),
    },
    groupingMode: {
        type: String,
        default: "daily", // 'daily' | 'weekly' | 'flat'
    },
    periodLabel: {
        type: String,
        default: "",
    },
    formatCurrency: {
        type: Function,
        default: (val) => "₱" + (parseFloat(val) || 0).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 }),
    },
});

const recordsCount = computed(() => props.report.records?.length || 0);
const grandTotalTransactions = computed(() => props.report.grand_total?.total_transactions ?? recordsCount.value);
const grandTotalRevenue = computed(() => props.report.grand_total?.total_amount_paid ?? 0);
const cashTotal = computed(() => props.report.grand_total?.methods?.Cash ?? 0);
const gcashTotal = computed(() => props.report.grand_total?.methods?.GCash ?? 0);
</script>

<template>
    <div class="space-y-4">
        <!-- Table Container -->
        <div class="bg-white dark:bg-slate-900 rounded-2xl border border-gray-300 dark:border-slate-700 shadow-sm overflow-hidden print:border print:border-gray-400 print:shadow-none print:rounded-none">
            <!-- Table Header Banner (Screen Only) -->
            <div class="p-4 sm:p-5 border-b border-gray-200 dark:border-slate-800 flex flex-col sm:flex-row sm:items-center justify-between gap-3 print:hidden bg-gray-50/50 dark:bg-slate-800/40">
                <div>
                    <h3 class="text-sm font-bold text-gray-900 dark:text-white uppercase tracking-wider flex items-center gap-2">
                        <i class="pi pi-wallet text-teal-700 dark:text-teal-400"></i>
                        Revenue & Collections Report Table
                    </h3>
                    <p class="text-xs text-gray-500 dark:text-slate-400 mt-0.5">
                        Official payment receipt transactions with method breakdown, settled sums, and payment status.
                    </p>
                </div>
                <div class="flex items-center gap-2 text-xs flex-wrap">
                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-lg bg-teal-50 dark:bg-teal-950/50 text-teal-800 dark:text-teal-300 font-semibold border border-teal-200/80 dark:border-teal-800">
                        <i class="pi pi-receipt text-xs"></i>
                        Transactions: {{ grandTotalTransactions }}
                    </span>
                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-lg bg-emerald-50 dark:bg-emerald-950/50 text-emerald-800 dark:text-emerald-300 font-semibold border border-emerald-200/80 dark:border-emerald-800">
                        Total Revenue: {{ formatCurrency(grandTotalRevenue) }}
                    </span>
                    <span v-if="cashTotal > 0" class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg bg-gray-100 dark:bg-slate-800 text-gray-700 dark:text-slate-300 font-semibold border border-gray-200 dark:border-slate-700 text-[11px]">
                        Cash: {{ formatCurrency(cashTotal) }}
                    </span>
                    <span v-if="gcashTotal > 0" class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg bg-blue-50 dark:bg-blue-950/50 text-blue-800 dark:text-blue-300 font-semibold border border-blue-200/80 dark:border-blue-800 text-[11px]">
                        GCash: {{ formatCurrency(gcashTotal) }}
                    </span>
                </div>
            </div>

            <!-- Print-Only Table Title -->
            <div class="hidden print:block p-3 border-b border-gray-300 bg-gray-100 text-xs font-bold text-gray-900 uppercase tracking-wider">
                Table: Revenue & Collections Transaction Records ({{ periodLabel }})
            </div>

            <!-- Report Table -->
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-slate-700 text-xs text-left border-collapse print:text-[10pt] print:divide-gray-400">
                    <thead class="bg-gray-100 dark:bg-slate-800 text-gray-700 dark:text-slate-200 font-bold uppercase tracking-wider print:bg-gray-200 print:text-black">
                        <tr>
                            <th scope="col" class="px-4 py-3 border-b border-gray-300 dark:border-slate-700 w-36 text-left">
                                Transaction ID
                            </th>
                            <th scope="col" class="px-4 py-3 border-b border-gray-300 dark:border-slate-700 text-left">
                                Patient Name
                            </th>
                            <th scope="col" class="px-4 py-3 border-b border-gray-300 dark:border-slate-700 text-center w-36">
                                Payment Date
                            </th>
                            <th scope="col" class="px-4 py-3 border-b border-gray-300 dark:border-slate-700 text-center w-32">
                                Payment Method
                            </th>
                            <th scope="col" class="px-4 py-3 border-b border-gray-300 dark:border-slate-700 text-right w-36">
                                Amount Paid
                            </th>
                            <th scope="col" class="px-4 py-3 border-b border-gray-300 dark:border-slate-700 text-center w-32">
                                Payment Status
                            </th>
                        </tr>
                    </thead>

                    <!-- 1. GROUPED BY DATE (DAILY - Monday to Sunday) -->
                    <template v-if="groupingMode === 'daily' && report.grouped_by_date?.length > 0">
                        <tbody
                            v-for="(group, gIdx) in report.grouped_by_date"
                            :key="'date-' + gIdx"
                            class="divide-y divide-gray-100 dark:divide-slate-800 bg-white dark:bg-slate-900 print:bg-white"
                        >
                            <!-- Group Day Header -->
                            <tr class="bg-teal-50/60 dark:bg-slate-800/80 font-bold text-gray-800 dark:text-slate-200 border-t-2 border-b border-teal-200/60 dark:border-slate-700 print:bg-gray-100 print:text-black">
                                <td colspan="6" class="px-4 py-2 text-xs print:text-[9.5pt]">
                                    <div class="flex items-center justify-between">
                                        <span class="flex items-center gap-2">
                                            <i class="pi pi-calendar text-teal-700 dark:text-teal-400 print:hidden text-xs"></i>
                                            <strong>{{ group.formatted_date }}</strong>
                                        </span>
                                        <span class="text-xs font-semibold text-gray-600 dark:text-slate-400 print:text-black">
                                            {{ group.records.length }} transaction(s) • {{ formatCurrency(group.subtotal_amount) }}
                                        </span>
                                    </div>
                                </td>
                            </tr>

                            <!-- Records in Date Group -->
                            <tr
                                v-for="(pay, pIdx) in group.records"
                                :key="'pay-' + pay.id"
                                class="hover:bg-gray-50/80 dark:hover:bg-slate-800/50 transition print:hover:bg-transparent"
                            >
                                <td class="px-4 py-2.5 font-mono font-bold text-teal-800 dark:text-teal-400 print:text-black whitespace-nowrap">
                                    {{ pay.transaction_id }}
                                </td>
                                <td class="px-4 py-2.5 font-semibold text-gray-900 dark:text-white print:text-black">
                                    {{ pay.patient_name }}
                                </td>
                                <td class="px-4 py-2.5 text-center text-gray-700 dark:text-slate-300 print:text-black whitespace-nowrap">
                                    {{ pay.payment_date }}
                                </td>
                                <td class="px-4 py-2.5 text-center whitespace-nowrap">
                                    <span
                                        :class="[
                                            pay.payment_method === 'GCash'
                                                ? 'bg-blue-50 text-blue-800 border-blue-200 dark:bg-blue-950/60 dark:text-blue-300 dark:border-blue-800'
                                                : pay.payment_method === 'Cash'
                                                  ? 'bg-emerald-50 text-emerald-800 border-emerald-200 dark:bg-emerald-950/60 dark:text-emerald-300 dark:border-emerald-800'
                                                  : 'bg-purple-50 text-purple-800 border-purple-200 dark:bg-purple-950/60 dark:text-purple-300 dark:border-purple-800',
                                            'px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wider border print:border print:text-black print:bg-transparent'
                                        ]"
                                    >
                                        {{ pay.payment_method }}
                                    </span>
                                </td>
                                <td class="px-4 py-2.5 text-right font-mono font-black text-gray-900 dark:text-white print:text-black whitespace-nowrap">
                                    {{ formatCurrency(pay.amount_paid) }}
                                </td>
                                <td class="px-4 py-2.5 text-center whitespace-nowrap">
                                    <span
                                        :class="[
                                            pay.payment_status.toLowerCase() === 'paid'
                                                ? 'bg-emerald-50 text-emerald-800 border-emerald-200 dark:bg-emerald-950/60 dark:text-emerald-300 dark:border-emerald-800'
                                                : 'bg-amber-50 text-amber-800 border-amber-200 dark:bg-amber-950/60 dark:text-amber-300 dark:border-amber-800',
                                            'px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wider border print:border print:text-black print:bg-transparent'
                                        ]"
                                    >
                                        {{ pay.payment_status }}
                                    </span>
                                </td>
                            </tr>

                            <!-- Daily Subtotal Row -->
                            <tr class="bg-gray-50 dark:bg-slate-800/60 font-semibold text-xs border-b border-gray-200 dark:border-slate-700 print:bg-gray-50 print:text-black">
                                <td colspan="3" class="px-4 py-2 text-right uppercase tracking-wider text-gray-500 dark:text-slate-400 print:text-gray-700">
                                    Daily Subtotal ({{ group.day_name }}):
                                </td>
                                <td class="px-4 py-2 text-center font-bold text-teal-800 dark:text-teal-400 print:text-black">
                                    {{ group.subtotal_count }} Transaction(s)
                                </td>
                                <td class="px-4 py-2 text-right font-mono font-black text-teal-800 dark:text-teal-400 print:text-black">
                                    {{ formatCurrency(group.subtotal_amount) }}
                                </td>
                                <td class="px-4 py-2 text-center text-gray-400 dark:text-slate-500 print:text-transparent">
                                    —
                                </td>
                            </tr>
                        </tbody>
                    </template>

                    <!-- 2. GROUPED BY WEEK (WEEKLY) -->
                    <template v-else-if="groupingMode === 'weekly' && report.grouped_by_week?.length > 0">
                        <tbody
                            v-for="(group, wIdx) in report.grouped_by_week"
                            :key="'week-' + wIdx"
                            class="divide-y divide-gray-100 dark:divide-slate-800 bg-white dark:bg-slate-900 print:bg-white"
                        >
                            <!-- Group Week Header -->
                            <tr class="bg-teal-50/60 dark:bg-slate-800/80 font-bold text-gray-800 dark:text-slate-200 border-t-2 border-b border-teal-200/60 dark:border-slate-700 print:bg-gray-100 print:text-black">
                                <td colspan="6" class="px-4 py-2 text-xs print:text-[9.5pt]">
                                    <div class="flex items-center justify-between">
                                        <span class="flex items-center gap-2">
                                            <i class="pi pi-calendar text-teal-700 dark:text-teal-400 print:hidden text-xs"></i>
                                            <strong>{{ group.week_label }}</strong>
                                        </span>
                                        <span class="text-xs font-semibold text-gray-600 dark:text-slate-400 print:text-black">
                                            {{ group.records.length }} transaction(s) • {{ formatCurrency(group.subtotal_amount) }}
                                        </span>
                                    </div>
                                </td>
                            </tr>

                            <!-- Records in Week Group -->
                            <tr
                                v-for="(pay, pIdx) in group.records"
                                :key="'w-pay-' + pay.id"
                                class="hover:bg-gray-50/80 dark:hover:bg-slate-800/50 transition print:hover:bg-transparent"
                            >
                                <td class="px-4 py-2.5 font-mono font-bold text-teal-800 dark:text-teal-400 print:text-black whitespace-nowrap">
                                    {{ pay.transaction_id }}
                                </td>
                                <td class="px-4 py-2.5 font-semibold text-gray-900 dark:text-white print:text-black">
                                    {{ pay.patient_name }}
                                </td>
                                <td class="px-4 py-2.5 text-center text-gray-700 dark:text-slate-300 print:text-black whitespace-nowrap">
                                    {{ pay.payment_date }}
                                </td>
                                <td class="px-4 py-2.5 text-center whitespace-nowrap">
                                    <span
                                        :class="[
                                            pay.payment_method === 'GCash'
                                                ? 'bg-blue-50 text-blue-800 border-blue-200 dark:bg-blue-950/60 dark:text-blue-300 dark:border-blue-800'
                                                : pay.payment_method === 'Cash'
                                                  ? 'bg-emerald-50 text-emerald-800 border-emerald-200 dark:bg-emerald-950/60 dark:text-emerald-300 dark:border-emerald-800'
                                                  : 'bg-purple-50 text-purple-800 border-purple-200 dark:bg-purple-950/60 dark:text-purple-300 dark:border-purple-800',
                                            'px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wider border print:border print:text-black print:bg-transparent'
                                        ]"
                                    >
                                        {{ pay.payment_method }}
                                    </span>
                                </td>
                                <td class="px-4 py-2.5 text-right font-mono font-black text-gray-900 dark:text-white print:text-black whitespace-nowrap">
                                    {{ formatCurrency(pay.amount_paid) }}
                                </td>
                                <td class="px-4 py-2.5 text-center whitespace-nowrap">
                                    <span
                                        :class="[
                                            pay.payment_status.toLowerCase() === 'paid'
                                                ? 'bg-emerald-50 text-emerald-800 border-emerald-200 dark:bg-emerald-950/60 dark:text-emerald-300 dark:border-emerald-800'
                                                : 'bg-amber-50 text-amber-800 border-amber-200 dark:bg-amber-950/60 dark:text-amber-300 dark:border-amber-800',
                                            'px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wider border print:border print:text-black print:bg-transparent'
                                        ]"
                                    >
                                        {{ pay.payment_status }}
                                    </span>
                                </td>
                            </tr>

                            <!-- Weekly Subtotal Row -->
                            <tr class="bg-gray-50 dark:bg-slate-800/60 font-semibold text-xs border-b border-gray-200 dark:border-slate-700 print:bg-gray-50 print:text-black">
                                <td colspan="3" class="px-4 py-2 text-right uppercase tracking-wider text-gray-500 dark:text-slate-400 print:text-gray-700">
                                    Weekly Subtotal ({{ group.week_label }}):
                                </td>
                                <td class="px-4 py-2 text-center font-bold text-teal-800 dark:text-teal-400 print:text-black">
                                    {{ group.subtotal_count }} Transaction(s)
                                </td>
                                <td class="px-4 py-2 text-right font-mono font-black text-teal-800 dark:text-teal-400 print:text-black">
                                    {{ formatCurrency(group.subtotal_amount) }}
                                </td>
                                <td class="px-4 py-2 text-center text-gray-400 dark:text-slate-500 print:text-transparent">
                                    —
                                </td>
                            </tr>
                        </tbody>
                    </template>

                    <!-- 3. FLAT LIST / ALL RECORDS -->
                    <template v-else-if="report.records?.length > 0">
                        <tbody class="divide-y divide-gray-100 dark:divide-slate-800 bg-white dark:bg-slate-900 print:bg-white">
                            <tr
                                v-for="(pay, pIdx) in report.records"
                                :key="'flat-pay-' + pay.id"
                                class="hover:bg-gray-50/80 dark:hover:bg-slate-800/50 transition print:hover:bg-transparent"
                            >
                                <td class="px-4 py-2.5 font-mono font-bold text-teal-800 dark:text-teal-400 print:text-black whitespace-nowrap">
                                    {{ pay.transaction_id }}
                                </td>
                                <td class="px-4 py-2.5 font-semibold text-gray-900 dark:text-white print:text-black">
                                    {{ pay.patient_name }}
                                </td>
                                <td class="px-4 py-2.5 text-center text-gray-700 dark:text-slate-300 print:text-black whitespace-nowrap">
                                    {{ pay.payment_date }}
                                </td>
                                <td class="px-4 py-2.5 text-center whitespace-nowrap">
                                    <span
                                        :class="[
                                            pay.payment_method === 'GCash'
                                                ? 'bg-blue-50 text-blue-800 border-blue-200 dark:bg-blue-950/60 dark:text-blue-300 dark:border-blue-800'
                                                : pay.payment_method === 'Cash'
                                                  ? 'bg-emerald-50 text-emerald-800 border-emerald-200 dark:bg-emerald-950/60 dark:text-emerald-300 dark:border-emerald-800'
                                                  : 'bg-purple-50 text-purple-800 border-purple-200 dark:bg-purple-950/60 dark:text-purple-300 dark:border-purple-800',
                                            'px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wider border print:border print:text-black print:bg-transparent'
                                        ]"
                                    >
                                        {{ pay.payment_method }}
                                    </span>
                                </td>
                                <td class="px-4 py-2.5 text-right font-mono font-black text-gray-900 dark:text-white print:text-black whitespace-nowrap">
                                    {{ formatCurrency(pay.amount_paid) }}
                                </td>
                                <td class="px-4 py-2.5 text-center whitespace-nowrap">
                                    <span
                                        :class="[
                                            pay.payment_status.toLowerCase() === 'paid'
                                                ? 'bg-emerald-50 text-emerald-800 border-emerald-200 dark:bg-emerald-950/60 dark:text-emerald-300 dark:border-emerald-800'
                                                : 'bg-amber-50 text-amber-800 border-amber-200 dark:bg-amber-950/60 dark:text-amber-300 dark:border-amber-800',
                                            'px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wider border print:border print:text-black print:bg-transparent'
                                        ]"
                                    >
                                        {{ pay.payment_status }}
                                    </span>
                                </td>
                            </tr>
                        </tbody>
                    </template>

                    <!-- Empty State -->
                    <tbody v-if="!report.records || report.records.length === 0">
                        <tr>
                            <td colspan="6" class="px-4 py-10 text-center text-gray-400 dark:text-slate-500 text-xs">
                                No revenue transaction records found matching the selected period and filter criteria.
                            </td>
                        </tr>
                    </tbody>

                    <!-- Table Footer: Grand Total -->
                    <tfoot class="bg-gray-100 dark:bg-slate-800 border-t-2 border-b-2 border-gray-300 dark:border-slate-700 font-bold text-xs print:bg-gray-200 print:border-black print:text-black">
                        <tr>
                            <td colspan="3" class="px-4 py-3 text-right uppercase tracking-wider text-gray-700 dark:text-slate-200 print:text-black font-extrabold">
                                Grand Total Revenue:
                            </td>
                            <td class="px-4 py-3 text-center font-bold text-teal-800 dark:text-teal-300 print:text-black text-sm">
                                {{ grandTotalTransactions }} Transaction(s)
                            </td>
                            <td class="px-4 py-3 text-right font-mono font-black text-teal-800 dark:text-teal-300 print:text-black text-sm">
                                {{ formatCurrency(grandTotalRevenue) }}
                            </td>
                            <td class="px-4 py-3 text-center text-gray-400 dark:text-slate-500 print:text-transparent">
                                —
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
</template>

