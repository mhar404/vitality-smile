<script setup>
import { ref, computed } from "vue";
import { Head, Link, router, usePage } from "@inertiajs/vue3";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import Modal from "@/Components/Modal.vue";
import ServiceIcon from "@/Components/ServiceIcon.vue";

const page = usePage();

const props = defineProps({
    nextAppointment: {
        type: Object,
        default: null,
    },
    appointmentsCount: {
        type: Number,
        default: 0,
    },
    outstandingBalance: {
        type: Number,
        default: 0,
    },
    recentTreatment: {
        type: Object,
        default: null,
    },
    notifications: {
        type: Array,
        default: () => [],
    },
    unreadNotificationsCount: {
        type: Number,
        default: 0,
    },
    medicalClearance: {
        type: Object,
        default: null,
    },
});

const userFirstName = computed(() => {
    const fullName = page.props.auth?.user?.name || "Patient";
    return fullName.split(" ")[0];
});

// Helper: Format full date (e.g. "Thursday, Oct 29, 2026")
const formatFullDate = (dateStr) => {
    if (!dateStr) return "";
    const d = new Date(dateStr + "T00:00:00");
    return d.toLocaleDateString("en-US", {
        weekday: "short",
        month: "short",
        day: "numeric",
        year: "numeric",
    });
};

// Helper: Format Time Window (e.g. "09:00 AM – 10:00 AM")
const formatTimeWindow = (apt) => {
    if (!apt || !apt.appointment_time) return "";
    const start = new Date(
        "1970-01-01T" + apt.appointment_time,
    ).toLocaleTimeString("en-US", {
        hour: "numeric",
        minute: "2-digit",
    });

    const endTime = apt.end_time || apt.calculated_end_time;
    if (endTime) {
        const end = new Date("1970-01-01T" + endTime).toLocaleTimeString(
            "en-US",
            {
                hour: "numeric",
                minute: "2-digit",
            },
        );
        return `${start} – ${end}`;
    }
    return start;
};

// Helper: Calculate relative day countdown
const relativeDayBadge = computed(() => {
    if (!props.nextAppointment?.appointment_date) return null;
    const today = new Date();
    today.setHours(0, 0, 0, 0);

    const aptDate = new Date(
        props.nextAppointment.appointment_date + "T00:00:00",
    );
    aptDate.setHours(0, 0, 0, 0);

    const diffDays = Math.round((aptDate - today) / (1000 * 60 * 60 * 24));
    if (diffDays === 0)
        return {
            label: "Today",
            class: "bg-emerald-100 text-emerald-800 border-emerald-300 font-bold",
        };
    if (diffDays === 1)
        return {
            label: "Tomorrow",
            class: "bg-blue-100 text-blue-800 border-blue-300 font-semibold",
        };
    if (diffDays > 1)
        return {
            label: `In ${diffDays} days`,
            class: "bg-teal-50 text-teal-700 border-teal-200 font-semibold",
        };
    return {
        label: "Scheduled",
        class: "bg-gray-100 text-gray-700 border-gray-200",
    };
});

// Format Currency
const formatCurrency = (amount) => {
    return Number(amount || 0).toLocaleString("en-PH", {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    });
};

// Cancel Modal State & Handler
const showCancelModal = ref(false);
const isCancelling = ref(false);

const cancelAppointment = () => {
    if (!props.nextAppointment) return;
    isCancelling.value = true;
    router.patch(
        route("appointments.status.update", props.nextAppointment.id),
        { status: "cancelled" },
        {
            preserveScroll: true,
            onFinish: () => {
                isCancelling.value = false;
                showCancelModal.value = false;
            },
        },
    );
};

// Notification helpers
const getNotificationCategoryMeta = (category) => {
    switch (category) {
        case "appointment":
            return {
                icon: "pi pi-calendar",
                badgeClass: "bg-teal-50 text-teal-700 border-teal-200",
                iconContainer: "bg-teal-50 text-teal-600 border border-teal-100",
                label: "Appointment",
            };
        case "treatment":
            return {
                icon: "pi pi-heart",
                badgeClass: "bg-blue-50 text-blue-700 border-blue-200",
                iconContainer: "bg-blue-50 text-blue-600 border border-blue-100",
                label: "Dental Record",
            };
        case "billing":
            return {
                icon: "pi pi-wallet",
                badgeClass: "bg-purple-50 text-purple-700 border-purple-200",
                iconContainer: "bg-purple-50 text-purple-600 border border-purple-100",
                label: "Billing",
            };
        case "payment":
            return {
                icon: "pi pi-receipt",
                badgeClass: "bg-emerald-50 text-emerald-700 border-emerald-200",
                iconContainer: "bg-emerald-50 text-emerald-600 border border-emerald-100",
                label: "Receipt",
            };
        case "medical":
        case "clearance":
            return {
                icon: "pi pi-shield",
                badgeClass: "bg-amber-50 text-amber-800 border-amber-200",
                iconContainer: "bg-amber-50 text-amber-600 border border-amber-200",
                label: "Medical Requirement",
            };
        default:
            return {
                icon: "pi pi-bell",
                badgeClass: "bg-gray-50 text-gray-700 border-gray-200",
                iconContainer: "bg-gray-50 text-gray-600 border border-gray-100",
                label: "Notice",
            };
    }
};

const formatNotificationTime = (dateStr) => {
    if (!dateStr) return "";
    const date = new Date(dateStr);
    const now = new Date();
    const diffMs = now - date;
    const diffSec = Math.floor(diffMs / 1000);
    const diffMin = Math.floor(diffSec / 60);
    const diffHrs = Math.floor(diffMin / 60);
    const diffDays = Math.floor(diffHrs / 24);

    if (diffMin < 1) return "Just now";
    if (diffMin < 60) return `${diffMin}m ago`;
    if (diffHrs < 24) return `${diffHrs}h ago`;
    if (diffDays === 1) return "Yesterday";
    return date.toLocaleDateString("en-US", { month: "short", day: "numeric" });
};

const markNotificationRead = (id) => {
    router.patch(route("notifications.read", id), {}, { preserveScroll: true });
};
</script>

<template>
    <Head title="Patient Dashboard" />

    <AuthenticatedLayout>
        <div class="py-8 bg-gray-50/50 min-h-[calc(100vh-4rem)]">
            <div class="mx-auto max-w-6xl px-4 sm:px-6 lg:px-8 space-y-7">
                <!-- 1. Welcome Banner Header -->
                <div
                    class="relative overflow-hidden rounded-3xl bg-gradient-to-r from-teal-800 via-teal-700 to-emerald-800 p-6 sm:p-8 text-white shadow-lg"
                >
                    <!-- Subtle ambient geometric decoration -->
                    <div
                        class="pointer-events-none absolute -right-12 -top-12 h-64 w-64 rounded-full bg-white/10 blur-2xl"
                    ></div>
                    <div
                        class="pointer-events-none absolute -bottom-10 right-1/3 h-44 w-44 rounded-full bg-emerald-400/15 blur-xl"
                    ></div>

                    <div
                        class="relative z-10 flex flex-col md:flex-row md:items-center md:justify-between gap-6"
                    >
                        <div class="max-w-2xl">
                            <div
                                class="inline-flex items-center gap-2 rounded-full bg-white/15 px-3 py-1 text-xs font-semibold uppercase tracking-wider text-teal-100 backdrop-blur-xs mb-3"
                            >
                                Vitality Smile Patient Portal
                            </div>



                            <h1
                                class="text-2xl sm:text-3xl lg:text-4xl font-extrabold tracking-tight"
                            >
                                Welcome back, {{ userFirstName }}!
                            </h1>

                            <p
                                class="mt-2 text-sm sm:text-base text-teal-100/90 leading-relaxed"
                            >
                                Manage your dental appointments, view upcoming
                                procedures, and track your clinical dental
                                history in one place.
                            </p>
                        </div>



                        <div class="flex flex-wrap items-center gap-3 shrink-0">
                            <Link
                                :href="route('appointments.create')"
                                class="inline-flex items-center gap-2 rounded-xl bg-white px-5 py-3 text-sm font-bold text-teal-800 shadow-sm transition-all duration-200 hover:bg-teal-50 hover:shadow-md focus:outline-none focus:ring-2 focus:ring-white focus:ring-offset-2 focus:ring-offset-teal-800"
                            >
                                <i
                                    class="pi pi-calendar-plus text-teal-700 text-sm"
                                ></i>
                                <span>Book Appointment</span>
                            </Link>

                            <Link
                                :href="route('treatments.index')"
                                class="inline-flex items-center gap-2 rounded-xl border border-white/30 bg-white/10 px-4 py-3 text-sm font-semibold text-white backdrop-blur-xs transition hover:bg-white/20"
                            >
                                <i class="pi pi-file text-sm"></i>
                                <span>Dental Records</span>
                            </Link>
                        </div>


                    </div>


                </div>



                <!-- Unread Notification Notice Banner -->
                <div
                    v-if="unreadNotificationsCount > 0"
                    class="rounded-2xl border border-teal-200 bg-gradient-to-r from-teal-50 to-emerald-50/60 p-4 shadow-2xs flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 dark:border-teal-800/60 dark:from-teal-950/40 dark:to-emerald-950/30"
                >
                    <div class="flex items-center gap-3">
                        <span class="relative flex h-3 w-3 shrink-0">
                            <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-teal-400 opacity-75"></span>
                            <span class="relative inline-flex h-3 w-3 rounded-full bg-teal-600"></span>
                        </span>
                        <div class="text-xs sm:text-sm text-teal-950 font-medium">
                            You have <strong class="font-extrabold text-teal-800">{{ unreadNotificationsCount }} unread clinic notice{{ unreadNotificationsCount > 1 ? 's' : '' }}</strong> regarding your appointment schedule, clinical treatments, or billing.
                        </div>


                    </div>



                    <Link
                        :href="route('notifications.index')"
                        class="inline-flex items-center gap-1.5 rounded-xl bg-teal-700 px-4 py-2 text-xs font-semibold text-white shadow-2xs hover:bg-teal-800 transition shrink-0 self-start sm:self-auto"
                    >
                        <i class="pi pi-bell text-xs"></i>
                        <span>View Notifications</span>
                        <i class="pi pi-arrow-right text-[10px]"></i>
                    </Link>
                </div>

                <!-- Medical Clearance Safety Alert Banner: When patient has medical conditions and clearance is required -->
                <div
                    v-if="medicalClearance?.has_conditions && !medicalClearance?.clearance_presented"
                    class="rounded-3xl border-2 border-amber-300 dark:border-amber-700/60 bg-gradient-to-br from-amber-50 via-white to-amber-50/80 dark:from-amber-950/40 dark:via-slate-900 dark:to-amber-950/30 p-6 sm:p-7 shadow-md space-y-5"
                >
                    <div class="flex flex-col sm:flex-row sm:items-start justify-between gap-4">
                        <div class="flex items-start gap-4">
                            <div
                                class="h-12 w-12 rounded-2xl bg-gradient-to-br from-amber-500 to-rose-500 text-white flex items-center justify-center shrink-0 shadow-md"
                            >
                                <i class="pi pi-exclamation-triangle text-xl"></i>
                            </div>
                            <div class="space-y-1">
                                <div class="flex flex-wrap items-center gap-2">
                                    <h2 class="text-base sm:text-lg font-black text-amber-950 dark:text-amber-200 uppercase tracking-tight">
                                        Action Required: Physician Medical Clearance Mandatory
                                    </h2>
                                    <span
                                        class="px-2.5 py-0.5 rounded-full text-[11px] font-black uppercase tracking-wider bg-rose-600 text-white shadow-xs"
                                    >
                                        Clearance Required
                                    </span>
                                </div>
                                <p class="text-xs sm:text-sm text-amber-900/90 dark:text-amber-300 leading-relaxed max-w-3xl">
                                    Our clinical records indicate that you have documented medical conditions. In accordance with clinical safety protocols, your dentist <strong>cannot administer dental treatments or procedures</strong> until a valid <strong>Physician Medical Clearance</strong> has been presented and verified.
                                </p>
                            </div>
                        </div>

                        <Link
                            :href="route('medical-histories.my')"
                            class="inline-flex items-center gap-2 rounded-xl bg-amber-600 hover:bg-amber-700 px-4 py-2.5 text-xs font-bold text-white shadow-xs transition shrink-0 self-start sm:self-auto"
                        >
                            <i class="pi pi-file text-xs"></i>
                            <span>View Medical Requirements</span>
                            <i class="pi pi-arrow-right text-[10px]"></i>
                        </Link>
                    </div>

                    <!-- Clinical Alert Highlights & Specific Conditions -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 pt-2">
                        <!-- Flagged Conditions -->
                        <div class="rounded-2xl border border-amber-200/90 dark:border-amber-800/60 bg-white/80 dark:bg-slate-900/80 p-4 space-y-2">
                            <div class="text-xs font-bold uppercase tracking-wider text-amber-800 dark:text-amber-300 flex items-center gap-1.5">
                                <i class="pi pi-heart text-rose-500 text-xs"></i>
                                Flagged Medical Conditions
                            </div>
                            <div class="flex flex-wrap gap-1.5 pt-1">
                                <span
                                    v-for="(condition, idx) in (medicalClearance.conditions || [])"
                                    :key="idx"
                                    class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-xs font-bold bg-amber-100 dark:bg-amber-950/70 text-amber-900 dark:text-amber-200 border border-amber-300 dark:border-amber-800"
                                >
                                    <i class="pi pi-circle-fill text-[8px] text-amber-600"></i>
                                    {{ condition }}
                                </span>
                            </div>
                            <p class="text-[11px] text-gray-500 dark:text-slate-400 mt-1">
                                Dental extractions, anesthesia, and invasive procedures require formal physician clearance for these conditions.
                            </p>
                        </div>

                        <!-- What You Need to Bring -->
                        <div class="rounded-2xl border border-teal-200/90 dark:border-teal-800/60 bg-white/80 dark:bg-slate-900/80 p-4 space-y-2">
                            <div class="text-xs font-bold uppercase tracking-wider text-teal-800 dark:text-teal-300 flex items-center gap-1.5">
                                <i class="pi pi-check-circle text-teal-600 text-xs"></i>
                                What You Need to Bring
                            </div>
                            <ul class="text-xs text-gray-700 dark:text-slate-300 space-y-1.5 pt-1">
                                <li class="flex items-start gap-1.5">
                                    <i class="pi pi-arrow-right text-[10px] text-teal-600 mt-1 shrink-0"></i>
                                    <span>Written clearance from your attending physician / specialist authorizing dental care.</span>
                                </li>
                                <li class="flex items-start gap-1.5">
                                    <i class="pi pi-arrow-right text-[10px] text-teal-600 mt-1 shrink-0"></i>
                                    <span>Physician's license number, contact details, and any medication precautions.</span>
                                </li>
                                <li class="flex items-start gap-1.5">
                                    <i class="pi pi-arrow-right text-[10px] text-teal-600 mt-1 shrink-0"></i>
                                    <span>Present this letter at the reception counter upon arrival for your appointment.</span>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>

                <!-- Verified Medical Clearance Status (If already cleared by dentist) -->
                <div
                    v-else-if="medicalClearance?.has_conditions && medicalClearance?.clearance_presented"
                    class="rounded-2xl border border-emerald-300 dark:border-emerald-700/60 bg-emerald-50/70 dark:bg-emerald-950/30 p-4 sm:p-5 flex flex-col sm:flex-row sm:items-center justify-between gap-3"
                >
                    <div class="flex items-center gap-3">
                        <div class="h-10 w-10 rounded-xl bg-emerald-600 text-white flex items-center justify-center shrink-0 shadow-xs">
                            <i class="pi pi-verified text-lg"></i>
                        </div>
                        <div>
                            <div class="flex items-center gap-2">
                                <h3 class="text-sm font-bold text-emerald-950 dark:text-emerald-200">
                                    Physician Medical Clearance Verified & On File
                                </h3>
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-black uppercase tracking-wider bg-emerald-600 text-white">
                                    Cleared for Treatment
                                </span>
                            </div>
                            <p class="text-xs text-emerald-800 dark:text-emerald-300 mt-0.5">
                                Verified by attending dentist: Cleared by <strong>{{ medicalClearance.clearing_doctor || 'Attending Physician' }}</strong>{{ medicalClearance.clearance_date ? ` on ${medicalClearance.clearance_date}` : '' }}.
                            </p>
                        </div>
                    </div>
                    <Link
                        :href="route('medical-histories.my')"
                        class="text-xs font-semibold text-emerald-700 dark:text-emerald-300 hover:underline flex items-center gap-1 shrink-0"
                    >
                        <span>View Health Record</span>
                        <i class="pi pi-arrow-right text-[10px]"></i>
                    </Link>
                </div>

                <!-- 2. Top Metric Tiles -->
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-5">
                    <!-- Next Appointment Summary Tile -->
                    <div
                        class="group relative rounded-2xl border border-gray-200/90 bg-white p-5 sm:p-6 shadow-xs transition hover:shadow-md hover:border-teal-300"
                    >
                        <div class="flex items-center justify-between">
                            <span
                                class="text-xs font-bold uppercase tracking-wider text-gray-400"
                                >Next Scheduled Visit</span
                            >
                            <div
                                class="flex h-10 w-10 items-center justify-center rounded-xl bg-teal-50 text-teal-700 transition group-hover:scale-110"
                            >
                                <i class="pi pi-calendar text-lg"></i>
                            </div>


                        </div>



                        <div v-if="nextAppointment" class="mt-4">
                            <div class="flex items-baseline gap-2">
                                <span
                                    class="text-xl sm:text-2xl font-extrabold text-gray-900"
                                >
                                    {{
                                        new Date(
                                            nextAppointment.appointment_date +
                                                "T00:00:00",
                                        ).toLocaleDateString("en-US", {
                                            month: "short",
                                            day: "numeric",
                                        })
                                    }}
                                </span>
                                <span
                                    v-if="relativeDayBadge"
                                    :class="[
                                        'text-[11px] px-2 py-0.5 rounded-full border',
                                        relativeDayBadge.class,
                                    ]"
                                >
                                    {{ relativeDayBadge.label }}
                                </span>
                            </div>



                            <p class="text-sm font-semibold text-teal-700 mt-1">
                                {{ formatTimeWindow(nextAppointment) }}
                            </p>

                            <div
                                class="mt-3 flex items-center gap-1.5 text-xs text-gray-500 border-t border-gray-100 pt-2.5"
                            >
                                <i
                                    class="pi pi-user text-teal-600 text-[11px]"
                                ></i>
                                <span class="truncate">{{
                                    nextAppointment.dentist?.name ||
                                    "Dr. Valdez"
                                }}</span>
                            </div>


                        </div>



                        <div v-else class="mt-4">
                            <p class="text-lg font-bold text-gray-900">
                                None Scheduled
                            </p>
                            <p class="text-xs text-gray-500 mt-0.5">
                                Keep your smile healthy and clean.
                            </p>
                            <div class="mt-3 border-t border-gray-100 pt-2.5">
                                <Link
                                    :href="route('appointments.create')"
                                    class="text-xs font-bold text-teal-700 hover:text-teal-800 inline-flex items-center gap-1 group/link"
                                >
                                    <span>Schedule a visit</span>
                                    <i
                                        class="pi pi-arrow-right text-[10px] transition-transform group-hover/link:translate-x-0.5"
                                    ></i>
                                </Link>
                            </div>


                        </div>


                    </div>



                    <!-- Total Appointments Count Tile -->
                    <div
                        class="group relative rounded-2xl border border-gray-200/90 bg-white p-5 sm:p-6 shadow-xs transition hover:shadow-md hover:border-blue-300"
                    >
                        <div class="flex items-center justify-between">
                            <span
                                class="text-xs font-bold uppercase tracking-wider text-gray-400"
                                >Total Visits</span
                            >
                            <div
                                class="flex h-10 w-10 items-center justify-center rounded-xl bg-blue-50 text-blue-700 transition group-hover:scale-110"
                            >
                                <i class="pi pi-history text-lg"></i>
                            </div>


                        </div>



                        <div class="mt-4">
                            <div class="flex items-baseline gap-2">
                                <span
                                    class="text-2xl sm:text-3xl font-extrabold text-gray-900"
                                >
                                    {{ appointmentsCount }}
                                </span>
                                <span class="text-xs font-medium text-gray-500"
                                    >recorded</span
                                >
                            </div>



                            <p class="text-xs text-gray-500 mt-1">
                                Complete dental visit history on file
                            </p>

                            <div class="mt-3 border-t border-gray-100 pt-2.5">
                                <Link
                                    :href="route('appointments.index')"
                                    class="text-xs font-bold text-blue-700 hover:text-blue-800 inline-flex items-center gap-1 group/link"
                                >
                                    <span>View appointment list</span>
                                    <i
                                        class="pi pi-arrow-right text-[10px] transition-transform group-hover/link:translate-x-0.5"
                                    ></i>
                                </Link>
                            </div>


                        </div>


                    </div>



                    <!-- Outstanding Balance Tile -->
                    <div
                        class="group relative rounded-2xl border border-gray-200/90 bg-white p-5 sm:p-6 shadow-xs transition hover:shadow-md"
                        :class="
                            outstandingBalance > 0
                                ? 'hover:border-amber-300'
                                : 'hover:border-emerald-300'
                        "
                    >
                        <div class="flex items-center justify-between">
                            <span
                                class="text-xs font-bold uppercase tracking-wider text-gray-400"
                                >Account Balance</span
                            >
                            <div
                                :class="[
                                    'flex h-10 w-10 items-center justify-center rounded-xl transition group-hover:scale-110',
                                    outstandingBalance > 0
                                        ? 'bg-amber-50 text-amber-700'
                                        : 'bg-emerald-50 text-emerald-700',
                                ]"
                            >
                                <i class="pi pi-wallet text-lg"></i>
                            </div>


                        </div>



                        <div class="mt-4">
                            <div class="flex items-baseline gap-2">
                                <span
                                    class="text-xl sm:text-2xl font-extrabold text-gray-900"
                                >
                                    ₱{{ formatCurrency(outstandingBalance) }}
                                </span>
                                <span
                                    v-if="outstandingBalance > 0"
                                    class="text-[10px] font-bold uppercase tracking-wider px-2 py-0.5 rounded-full bg-amber-100 text-amber-800 border border-amber-200"
                                >
                                    Pending
                                </span>
                                <span
                                    v-else
                                    class="text-[10px] font-bold uppercase tracking-wider px-2 py-0.5 rounded-full bg-emerald-100 text-emerald-800 border border-emerald-200"
                                >
                                    Settled
                                </span>
                            </div>



                            <p class="text-xs text-gray-500 mt-1">
                                {{
                                    outstandingBalance > 0
                                        ? "Outstanding invoice balance due"
                                        : "All previous invoices settled in full"
                                }}
                            </p>

                            <div class="mt-3 border-t border-gray-100 pt-2.5">
                                <Link
                                    :href="route('billing.index')"
                                    class="text-xs font-bold text-teal-700 hover:text-teal-800 inline-flex items-center gap-1 group/link"
                                >
                                    <span>{{
                                        outstandingBalance > 0
                                            ? "Review & pay bills"
                                            : "View payment receipts"
                                    }}</span>
                                    <i
                                        class="pi pi-arrow-right text-[10px] transition-transform group-hover/link:translate-x-0.5"
                                    ></i>
                                </Link>
                            </div>


                        </div>


                    </div>


                </div>



                <!-- 3. Upcoming Appointment Spotlight Card -->
                <div
                    class="rounded-3xl border border-gray-200/90 bg-white shadow-xs overflow-hidden"
                >
                    <div
                        class="border-b border-gray-100 bg-gray-50/80 px-6 py-4 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2"
                    >
                        <div class="flex items-center gap-2.5">
                            <span
                                class="flex h-2.5 w-2.5 rounded-full bg-teal-600"
                            ></span>
                            <h2 class="text-base font-bold text-gray-900">
                                Upcoming Dental Care
                            </h2>
                        </div>


                        <span class="text-xs text-gray-500"
                            >Live booking status and schedule details</span
                        >
                    </div>



                    <!-- Case A: Appointment Exists -->
                    <div v-if="nextAppointment" class="p-6 lg:p-8">
                        <div
                            class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-6"
                        >
                            <!-- Left: Procedure & Doctor Details -->
                            <div class="flex items-start gap-4 sm:gap-5">
                                <ServiceIcon
                                    :title="nextAppointment.service"
                                    size="lg"
                                />

                                <div>
                                    <div
                                        class="flex flex-wrap items-center gap-2 mb-1.5"
                                    >
                                        <h3
                                            class="text-xl font-extrabold text-gray-900"
                                        >
                                            {{ nextAppointment.service }}
                                        </h3>

                                        <!-- Status Badge -->
                                        <span
                                            v-if="
                                                nextAppointment.status ===
                                                'confirmed'
                                            "
                                            class="inline-flex items-center gap-1.5 rounded-full bg-emerald-50 border border-emerald-200 px-3 py-0.5 text-xs font-bold text-emerald-800 uppercase tracking-wider"
                                        >
                                            <span
                                                class="h-2 w-2 rounded-full bg-emerald-500 animate-pulse"
                                            ></span>
                                            Confirmed
                                        </span>
                                        <span
                                            v-else-if="
                                                nextAppointment.status ===
                                                'pending'
                                            "
                                            class="inline-flex items-center gap-1.5 rounded-full bg-amber-50 border border-amber-200 px-3 py-0.5 text-xs font-bold text-amber-800 uppercase tracking-wider"
                                        >
                                            <span
                                                class="h-2 w-2 rounded-full bg-amber-500"
                                            ></span>
                                            Pending Review
                                        </span>
                                        <span
                                            v-else
                                            class="inline-flex items-center gap-1 rounded-full bg-gray-100 border border-gray-200 px-2.5 py-0.5 text-xs font-semibold text-gray-700 capitalize"
                                        >
                                            {{ nextAppointment.status }}
                                        </span>
                                    </div>



                                    <!-- Attending Dentist -->
                                    <div
                                        class="flex items-center gap-2 text-sm text-gray-600 mb-3"
                                    >
                                        <div
                                            class="flex h-5 w-5 items-center justify-center rounded-full bg-teal-100 text-teal-800 text-[10px] font-bold"
                                        >
                                            Dr
                                        </div>


                                        <span
                                            >Attending:
                                            <strong
                                                class="text-gray-900 font-semibold"
                                                >{{
                                                    nextAppointment.dentist
                                                        ?.name || "Dr. Valdez"
                                                }}</strong
                                            ></span
                                        >
                                    </div>



                                    <!-- Date, Time Window & Duration Pill -->
                                    <div
                                        class="flex flex-wrap items-center gap-2.5 text-sm"
                                    >
                                        <div
                                            class="inline-flex items-center gap-1.5 rounded-lg bg-teal-50 px-3 py-1 font-semibold text-teal-900 border border-teal-100"
                                        >
                                            <i
                                                class="pi pi-calendar text-teal-700 text-xs"
                                            ></i>
                                            <span>{{
                                                formatFullDate(
                                                    nextAppointment.appointment_date,
                                                )
                                            }}</span>
                                        </div>



                                        <div
                                            class="inline-flex items-center gap-1.5 rounded-lg bg-teal-50 px-3 py-1 font-bold text-teal-900 border border-teal-100"
                                        >
                                            <i
                                                class="pi pi-clock text-teal-700 text-xs"
                                            ></i>
                                            <span>{{
                                                formatTimeWindow(
                                                    nextAppointment,
                                                )
                                            }}</span>
                                        </div>



                                        <span
                                            class="rounded-lg bg-gray-100 px-2.5 py-1 text-xs font-bold text-gray-700 border border-gray-200"
                                        >
                                            {{
                                                nextAppointment.duration_minutes ||
                                                60
                                            }}
                                            mins
                                        </span>
                                    </div>


                                </div>


                            </div>



                            <!-- Right: Actions -->
                            <div
                                class="flex flex-col sm:flex-row lg:flex-col xl:flex-row items-stretch sm:items-center gap-2.5 shrink-0 pt-4 lg:pt-0 border-t lg:border-t-0 border-gray-100"
                            >
                                <Link
                                    :href="route('appointments.index')"
                                    class="inline-flex items-center justify-center gap-2 rounded-xl bg-teal-700 px-5 py-2.5 text-sm font-semibold text-white shadow-xs transition hover:bg-teal-800 focus:outline-none focus:ring-2 focus:ring-teal-500"
                                >
                                    <i class="pi pi-calendar text-xs"></i>
                                    <span>View My Schedule</span>
                                </Link>

                                <button
                                    v-if="
                                        ['pending', 'confirmed'].includes(
                                            nextAppointment.status,
                                        )
                                    "
                                    type="button"
                                    @click="showCancelModal = true"
                                    class="inline-flex items-center justify-center gap-2 rounded-xl border border-red-200 bg-red-50/60 px-4 py-2.5 text-sm font-semibold text-red-600 transition hover:bg-red-100/70 hover:text-red-700 focus:outline-none"
                                >
                                    <i class="pi pi-times-circle text-xs"></i>
                                    <span>Cancel Booking</span>
                                </button>
                            </div>


                        </div>



                        <!-- Clinic Appointment Day Notice -->
                        <div
                            class="mt-6 rounded-2xl bg-teal-50/50 border border-teal-100/80 p-4 flex items-start gap-3 text-xs text-teal-900"
                        >
                            <i
                                class="pi pi-info-circle text-teal-600 text-base shrink-0 mt-0.5"
                            ></i>
                            <div class="leading-relaxed">
                                <span class="font-bold">Clinic Reminder:</span>
                                Please arrive 10 minutes before your reserved
                                time slot for check-in and blood pressure
                                screening. If you need to reschedule, you can
                                contact the clinic or cancel above.
                            </div>


                        </div>


                    </div>



                    <!-- Case B: Empty State (No Upcoming Appointment) -->
                    <div v-else class="p-8 text-center">
                        <div
                            class="mx-auto flex h-16 w-16 items-center justify-center rounded-2xl bg-teal-50 text-teal-600 mb-4 border border-teal-100"
                        >
                            <i class="pi pi-calendar-plus text-2xl"></i>
                        </div>



                        <h3 class="text-lg font-bold text-gray-900">
                            No Upcoming Appointments
                        </h3>

                        <p
                            class="mx-auto mt-1 max-w-md text-sm text-gray-500 leading-relaxed"
                        >
                            Routine cleanings and regular oral examinations help
                            maintain dental health, prevent cavities, and keep
                            your smile shining.
                        </p>

                        <div class="mt-6 flex justify-center">
                            <Link
                                :href="route('appointments.create')"
                                class="inline-flex items-center gap-2 rounded-xl bg-teal-700 px-6 py-3 text-sm font-bold text-white shadow-xs transition hover:bg-teal-800"
                            >
                                <i class="pi pi-calendar-plus text-xs"></i>
                                <span>Schedule an Appointment</span>
                            </Link>
                        </div>


                    </div>


                </div>



                <!-- 4. Quick Actions Hub -->
                <div>
                    <div class="flex items-center justify-between mb-4">
                        <h2 class="text-lg font-bold text-gray-900">
                            Patient Care Actions
                        </h2>
                        <span class="text-xs text-gray-400"
                            >Quick shortcuts to your dental services</span
                        >
                    </div>



                    <div
                        class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4"
                    >
                        <!-- 1. Book Appointment -->
                        <Link
                            :href="route('appointments.create')"
                            class="group relative rounded-2xl border border-gray-200/90 bg-white p-5 shadow-xs transition duration-200 hover:-translate-y-1 hover:border-teal-400 hover:shadow-md flex flex-col justify-between"
                        >
                            <div>
                                <div
                                    class="flex h-12 w-12 items-center justify-center rounded-xl bg-teal-50 text-teal-700 group-hover:bg-teal-700 group-hover:text-white transition duration-200 mb-3.5"
                                >
                                    <i class="pi pi-calendar-plus text-xl"></i>
                                </div>


                                <h3
                                    class="font-bold text-gray-900 group-hover:text-teal-700 transition"
                                >
                                    Book Appointment
                                </h3>
                                <p
                                    class="text-xs text-gray-500 mt-1 leading-relaxed"
                                >
                                    Choose procedure & available time slot
                                </p>
                            </div>


                            <div
                                class="mt-4 flex items-center text-xs font-semibold text-teal-700"
                            >
                                <span>Book now</span>
                                <i
                                    class="pi pi-arrow-right ml-1 text-[10px] transition group-hover:translate-x-1"
                                ></i>
                            </div>


                        </Link>

                        <!-- 2. My Appointments -->
                        <Link
                            :href="route('appointments.index')"
                            class="group relative rounded-2xl border border-gray-200/90 bg-white p-5 shadow-xs transition duration-200 hover:-translate-y-1 hover:border-teal-400 hover:shadow-md flex flex-col justify-between"
                        >
                            <div>
                                <div
                                    class="flex h-12 w-12 items-center justify-center rounded-xl bg-blue-50 text-blue-700 group-hover:bg-blue-600 group-hover:text-white transition duration-200 mb-3.5"
                                >
                                    <i class="pi pi-calendar text-xl"></i>
                                </div>


                                <h3
                                    class="font-bold text-gray-900 group-hover:text-teal-700 transition"
                                >
                                    My Appointments
                                </h3>
                                <p
                                    class="text-xs text-gray-500 mt-1 leading-relaxed"
                                >
                                    Track scheduled dates & cancellation history
                                </p>
                            </div>


                            <div
                                class="mt-4 flex items-center text-xs font-semibold text-blue-700"
                            >
                                <span>View visits</span>
                                <i
                                    class="pi pi-arrow-right ml-1 text-[10px] transition group-hover:translate-x-1"
                                ></i>
                            </div>


                        </Link>

                        <!-- 3. Dental Records -->
                        <Link
                            :href="route('treatments.index')"
                            class="group relative rounded-2xl border border-gray-200/90 bg-white p-5 shadow-xs transition duration-200 hover:-translate-y-1 hover:border-teal-400 hover:shadow-md flex flex-col justify-between"
                        >
                            <div>
                                <div
                                    class="flex h-12 w-12 items-center justify-center rounded-xl bg-emerald-50 text-emerald-700 group-hover:bg-emerald-600 group-hover:text-white transition duration-200 mb-3.5"
                                >
                                    <i class="pi pi-heart text-xl"></i>
                                </div>


                                <h3
                                    class="font-bold text-gray-900 group-hover:text-teal-700 transition"
                                >
                                    Dental Records
                                </h3>
                                <p
                                    class="text-xs text-gray-500 mt-1 leading-relaxed"
                                >
                                    Completed procedures & clinical diagnosis
                                </p>
                            </div>


                            <div
                                class="mt-4 flex items-center text-xs font-semibold text-emerald-700"
                            >
                                <span>Open chart</span>
                                <i
                                    class="pi pi-arrow-right ml-1 text-[10px] transition group-hover:translate-x-1"
                                ></i>
                            </div>


                        </Link>

                        <!-- 4. Billing & Receipts -->
                        <Link
                            :href="route('billing.index')"
                            class="group relative rounded-2xl border border-gray-200/90 bg-white p-5 shadow-xs transition duration-200 hover:-translate-y-1 hover:border-teal-400 hover:shadow-md flex flex-col justify-between"
                        >
                            <div>
                                <div
                                    class="flex h-12 w-12 items-center justify-center rounded-xl bg-purple-50 text-purple-700 group-hover:bg-purple-600 group-hover:text-white transition duration-200 mb-3.5"
                                >
                                    <i class="pi pi-receipt text-xl"></i>
                                </div>


                                <h3
                                    class="font-bold text-gray-900 group-hover:text-teal-700 transition"
                                >
                                    Billing & Receipts
                                </h3>
                                <p
                                    class="text-xs text-gray-500 mt-1 leading-relaxed"
                                >
                                    Official invoices, balances & receipts
                                </p>
                            </div>


                            <div
                                class="mt-4 flex items-center text-xs font-semibold text-purple-700"
                            >
                                <span>Check invoices</span>
                                <i
                                    class="pi pi-arrow-right ml-1 text-[10px] transition group-hover:translate-x-1"
                                ></i>
                            </div>


                        </Link>
                    </div>


                </div>



                <!-- 5. Recent Treatment & Oral Health Spotlight (Two Column Grid) -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <!-- Recent Dental Treatment Card -->
                    <div
                        class="rounded-3xl border border-gray-200/90 bg-white p-6 shadow-xs flex flex-col justify-between"
                    >
                        <div>
                            <div
                                class="flex items-center justify-between pb-4 border-b border-gray-100"
                            >
                                <div class="flex items-center gap-2">
                                    <i
                                        class="pi pi-heart text-teal-600 text-sm"
                                    ></i>
                                    <h3
                                        class="font-bold text-gray-900 text-base"
                                    >
                                        Latest Clinical Treatment
                                    </h3>
                                </div>


                                <Link
                                    :href="route('treatments.index')"
                                    class="text-xs font-semibold text-teal-700 hover:underline"
                                >
                                    Full history
                                </Link>
                            </div>



                            <div v-if="recentTreatment" class="mt-5 space-y-3">
                                <div
                                    class="flex items-start justify-between gap-3"
                                >
                                    <div>
                                        <p
                                            class="font-extrabold text-gray-900 text-base leading-snug"
                                        >
                                            {{ recentTreatment.service }}
                                        </p>
                                        <p class="text-xs text-gray-500 mt-0.5">
                                            Treated by:
                                            <span
                                                class="font-semibold text-gray-700"
                                                >{{
                                                    recentTreatment.dentist
                                                }}</span
                                            >
                                        </p>
                                    </div>



                                    <span
                                        v-if="recentTreatment.tooth_number"
                                        class="rounded-lg bg-blue-50 border border-blue-200 px-2.5 py-1 text-xs font-bold text-blue-700 shrink-0"
                                    >
                                        Tooth #{{
                                            recentTreatment.tooth_number
                                        }}
                                    </span>
                                </div>



                                <div
                                    class="flex items-center gap-4 text-xs pt-2"
                                >
                                    <span class="text-gray-500">
                                        <i
                                            class="pi pi-calendar mr-1 text-gray-400"
                                        ></i
                                        >{{ recentTreatment.date }}
                                    </span>
                                    <span
                                        class="inline-flex items-center gap-1 font-semibold text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded border border-emerald-200"
                                    >
                                        <i class="pi pi-check text-[10px]"></i>
                                        {{ recentTreatment.status }}
                                    </span>
                                </div>


                            </div>



                            <div v-else class="mt-6 text-center py-4">
                                <i
                                    class="pi pi-check-circle text-gray-300 text-3xl mb-2"
                                ></i>
                                <p class="text-sm font-semibold text-gray-800">
                                    No Prior Treatments Recorded
                                </p>
                                <p class="text-xs text-gray-400 mt-1">
                                    Completed dental procedures and tooth charts
                                    will show here.
                                </p>
                            </div>


                        </div>



                        <div
                            class="mt-6 border-t border-gray-100 pt-4 flex justify-end"
                        >
                            <Link
                                :href="route('treatments.index')"
                                class="text-xs font-bold text-teal-700 hover:text-teal-800 inline-flex items-center gap-1"
                            >
                                <span>View Dental Chart</span>
                                <i class="pi pi-arrow-right text-[10px]"></i>
                            </Link>
                        </div>


                    </div>



                    <!-- Preventive Oral Wellness Tip Card -->
                    <div
                        class="rounded-3xl border border-teal-100 bg-gradient-to-br from-teal-50/70 to-emerald-50/40 p-6 shadow-xs flex flex-col justify-between"
                    >
                        <div>
                            <div
                                class="flex items-center gap-2 pb-4 border-b border-teal-100/80"
                            >
                                <i
                                    class="pi pi-shield text-teal-700 text-sm"
                                ></i>
                                <h3 class="font-bold text-teal-950 text-base">
                                    Preventive Care Guidelines
                                </h3>
                            </div>



                            <div
                                class="mt-4 space-y-3 text-xs text-teal-900/90 leading-relaxed"
                            >
                                <div class="flex items-start gap-2.5">
                                    <div
                                        class="h-5 w-5 rounded-full bg-teal-100 text-teal-800 flex items-center justify-center shrink-0 mt-0.5 font-bold"
                                    >
                                        ✓
                                    </div>


                                    <p>
                                        <strong>Biannual Cleaning:</strong>
                                        Schedule an Oral Prophylaxis every 6
                                        months to remove calculus and prevent
                                        gingivitis.
                                    </p>
                                </div>



                                <div class="flex items-start gap-2.5">
                                    <div
                                        class="h-5 w-5 rounded-full bg-teal-100 text-teal-800 flex items-center justify-center shrink-0 mt-0.5 font-bold"
                                    >
                                        ✓
                                    </div>


                                    <p>
                                        <strong
                                            >Daily Flossing & Brushing:</strong
                                        >
                                        Use soft-bristled toothbrushes twice
                                        daily with fluoridated toothpaste.
                                    </p>
                                </div>



                                <div class="flex items-start gap-2.5">
                                    <div
                                        class="h-5 w-5 rounded-full bg-teal-100 text-teal-800 flex items-center justify-center shrink-0 mt-0.5 font-bold"
                                    >
                                        ✓
                                    </div>


                                    <p>
                                        <strong>Early Detection:</strong>
                                        Address small tooth sensitivities early
                                        to prevent deep root infections.
                                    </p>
                                </div>


                            </div>


                        </div>



                        <div
                            class="mt-6 border-t border-teal-100/80 pt-4 flex items-center justify-between"
                        >
                            <span
                                class="text-[11px] font-medium text-teal-800/80"
                                >Clinic Hours: Mon – Fri (9am – 6pm)</span
                            >
                            <Link
                                :href="route('appointments.create')"
                                class="text-xs font-bold text-teal-800 hover:underline"
                            >
                                Schedule Checkup &rarr;
                            </Link>
                        </div>


                    </div>


                </div>



                <!-- 6. Recent Clinic Notifications & Updates Card -->
                <div class="rounded-3xl border border-gray-200/90 bg-white p-6 shadow-xs">
                    <div class="flex items-center justify-between pb-4 border-b border-gray-100">
                        <div class="flex items-center gap-2.5">
                            <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-teal-50 text-teal-700 border border-teal-100">
                                <i class="pi pi-bell text-sm"></i>
                            </div>


                            <div>
                                <h2 class="text-base font-bold text-gray-900">Recent Notifications & Updates</h2>
                                <p class="text-xs text-gray-400">Important notices sent to your portal and registered email</p>
                            </div>


                        </div>



                        <Link
                            :href="route('notifications.index')"
                            class="inline-flex items-center gap-1 text-xs font-bold text-teal-700 hover:text-teal-900 transition"
                        >
                            <span>View All Notifications</span>
                            <i class="pi pi-arrow-right text-[10px]"></i>
                        </Link>
                    </div>



                    <!-- Notification Items List -->
                    <div v-if="notifications && notifications.length > 0" class="mt-4 divide-y divide-gray-100">
                        <div
                            v-for="item in notifications"
                            :key="item.id"
                            :class="[
                                'flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 py-3.5 first:pt-1 last:pb-1 transition rounded-xl px-2 hover:bg-gray-50/70',
                                !item.read_at ? 'bg-teal-50/20' : '',
                            ]"
                        >
                            <div class="flex items-start gap-3 min-w-0">
                                <div
                                    :class="[
                                        'flex h-9 w-9 shrink-0 items-center justify-center rounded-xl text-xs',
                                        getNotificationCategoryMeta(item.data?.category).iconContainer,
                                    ]"
                                >
                                    <i :class="getNotificationCategoryMeta(item.data?.category).icon"></i>
                                </div>



                                <div class="min-w-0">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <span
                                            v-if="!item.read_at"
                                            class="h-1.5 w-1.5 rounded-full bg-teal-600 shrink-0"
                                            title="Unread"
                                        ></span>
                                        <h3 class="text-xs sm:text-sm font-bold text-gray-900 truncate">
                                            {{ item.data?.title }}
                                        </h3>
                                        <span
                                            :class="[
                                                'text-[10px] font-semibold uppercase tracking-wider px-2 py-0.5 rounded-full border',
                                                getNotificationCategoryMeta(item.data?.category).badgeClass,
                                            ]"
                                        >
                                            {{ getNotificationCategoryMeta(item.data?.category).label }}
                                        </span>
                                        <span class="text-[10px] text-gray-400 font-medium">
                                            {{ formatNotificationTime(item.created_at) }}
                                        </span>
                                    </div>


                                    <p class="text-xs text-gray-600 mt-0.5 line-clamp-1">
                                        {{ item.data?.message }}
                                    </p>
                                </div>


                            </div>



                            <div class="flex items-center gap-2 shrink-0 self-end sm:self-auto">
                                <Link
                                    v-if="item.data?.action_url"
                                    :href="item.data.action_url"
                                    class="inline-flex items-center gap-1 rounded-lg border border-teal-200 bg-white px-2.5 py-1 text-[11px] font-semibold text-teal-800 hover:bg-teal-50 transition shadow-2xs"
                                >
                                    <span>{{ item.data.action_text || 'View' }}</span>
                                    <i class="pi pi-arrow-right text-[9px]"></i>
                                </Link>

                                <button
                                    v-if="!item.read_at"
                                    type="button"
                                    @click="markNotificationRead(item.id)"
                                    class="text-gray-400 hover:text-teal-700 p-1"
                                    title="Mark as read"
                                >
                                    <i class="pi pi-check text-xs"></i>
                                </button>
                            </div>


                        </div>


                    </div>



                    <!-- Empty State -->
                    <div v-else class="mt-4 text-center py-6 text-gray-400">
                        <i class="pi pi-bell-slash text-2xl mb-1.5 text-gray-300"></i>
                        <p class="text-xs font-semibold text-gray-700">No Notifications</p>
                        <p class="text-[11px] text-gray-400 mt-0.5">You're all caught up! New clinical updates and receipts will show here.</p>
                    </div>


                </div>


            </div>
        </div>

        <!-- 6. Cancel Confirmation Modal -->
        <Modal
            :show="showCancelModal"
            @close="showCancelModal = false"
            maxWidth="md"
        >
            <div class="p-6">
                <div class="flex items-center gap-3.5">
                    <div
                        class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-red-100 text-red-600"
                    >
                        <i class="pi pi-exclamation-triangle text-xl"></i>
                    </div>


                    <div>
                        <h3 class="text-lg font-bold text-gray-900">
                            Cancel Appointment
                        </h3>
                        <p class="text-xs text-gray-500 mt-1 leading-relaxed">
                            Are you sure you want to cancel your upcoming
                            booking for
                            <strong class="text-gray-900 font-semibold">{{
                                nextAppointment?.service
                            }}</strong
                            >?
                        </p>
                    </div>


                </div>



                <p
                    class="mt-4 text-xs text-gray-500 bg-gray-50 p-3 rounded-xl border border-gray-100"
                >
                    Cancelling this booking will free up your reserved time slot
                    for other patients. You can re-book another slot at any
                    time.
                </p>

                <div
                    class="mt-6 flex justify-end gap-3 border-t border-gray-100 pt-4"
                >
                    <button
                        type="button"
                        :disabled="isCancelling"
                        @click="showCancelModal = false"
                        class="rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-xs font-semibold text-gray-700 shadow-xs hover:bg-gray-50 transition"
                    >
                        Keep Appointment
                    </button>
                    <button
                        type="button"
                        :disabled="isCancelling"
                        @click="cancelAppointment"
                        class="inline-flex items-center gap-1.5 rounded-xl bg-red-600 px-4 py-2.5 text-xs font-semibold text-white shadow-xs hover:bg-red-700 transition disabled:opacity-50"
                    >
                        <i
                            v-if="isCancelling"
                            class="pi pi-spin pi-spinner text-xs"
                        ></i>
                        <span>Yes, Cancel Booking</span>
                    </button>
                </div>


            </div>
        </Modal>
    </AuthenticatedLayout>
</template>
