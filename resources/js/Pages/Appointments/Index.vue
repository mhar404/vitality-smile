<script setup>
import { ref, computed } from "vue";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import Modal from "@/Components/Modal.vue";
import Pagination from "@/Components/Pagination.vue";
import { Head, Link, router } from "@inertiajs/vue3";

const props = defineProps({
    appointments: Object,
    dentist: Object,
    medicalClearance: {
        type: Object,
        default: null,
    },
});

const sendingReminders = ref(false);
const triggerReminders = () => {
    sendingReminders.value = true;
    router.post(route("appointments.send-reminders"), {}, {
        preserveScroll: true,
        onFinish: () => {
            sendingReminders.value = false;
        },
    });
};

// Formatters
const formatTime = (timeStr) => {
    if (!timeStr) return "";
    try {
        return new Date("1970-01-01T" + timeStr).toLocaleTimeString([], {
            hour: "2-digit",
            minute: "2-digit",
        });
    } catch (e) {
        return timeStr;
    }
};

const formatDate = (dateStr) => {
    if (!dateStr) return "";
    const d = new Date(dateStr + "T00:00:00");
    return d.toLocaleDateString("en-US", {
        month: "short",
        day: "numeric",
        year: "numeric",
    });
};

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

const formatBookingDate = (dateStr) => {
    if (!dateStr) return "";
    try {
        const d = new Date(dateStr);
        return d.toLocaleDateString("en-US", {
            month: "short",
            day: "numeric",
            year: "numeric",
            hour: "2-digit",
            minute: "2-digit",
        });
    } catch (e) {
        return dateStr;
    }
};

const isUpcoming = (appointment) => {
    if (!appointment || !appointment.appointment_date) return false;
    const today = new Date().toISOString().split("T")[0];
    const isFutureOrToday = appointment.appointment_date >= today;
    const isActiveStatus =
        appointment.status === "confirmed" || appointment.status === "pending";
    return isFutureOrToday && isActiveStatus;
};

const getStatusBadge = (status) => {
    switch (status) {
        case "confirmed":
            return {
                bg: "bg-emerald-50 text-emerald-700 border-emerald-200/90",
                dot: "bg-emerald-500",
                label: "Confirmed",
                icon: "pi-check-circle",
            };
        case "pending":
            return {
                bg: "bg-amber-50 text-amber-700 border-amber-200/90",
                dot: "bg-amber-500",
                label: "Pending Review",
                icon: "pi-clock",
            };
        case "completed":
            return {
                bg: "bg-blue-50 text-blue-700 border-blue-200/90",
                dot: "bg-blue-500",
                label: "Completed",
                icon: "pi-verified",
            };
        case "cancelled":
            return {
                bg: "bg-rose-50 text-rose-700 border-rose-200/90",
                dot: "bg-rose-500",
                label: "Cancelled",
                icon: "pi-times-circle",
            };
        default:
            return {
                bg: "bg-gray-50 text-gray-700 border-gray-200",
                dot: "bg-gray-400",
                label: status || "Unknown",
                icon: "pi-circle",
            };
    }
};

const getServiceIcon = (serviceName) => {
    if (!serviceName) return "pi-calendar";
    const s = serviceName.toLowerCase();
    if (s.includes("clean") || s.includes("prophylaxis")) return "pi-shield";
    if (
        s.includes("restoration") ||
        s.includes("filling") ||
        s.includes("composite")
    )
        return "pi-wrench";
    if (s.includes("root canal")) return "pi-bolt";
    if (s.includes("brace") || s.includes("orthodontic"))
        return "pi-align-justify";
    if (s.includes("whiten")) return "pi-sparkles";
    if (s.includes("extract") || s.includes("surgery")) return "pi-heart";
    return "pi-calendar";
};

// State
const selectedTab = ref("all"); // 'all', 'upcoming', 'confirmed', 'pending', 'completed', 'cancelled'
const searchQuery = ref("");

// Modals
const showCancelModal = ref(false);
const selectedAppointment = ref(null);
const showDetailsModal = ref(false);
const viewingAppointment = ref(null);

const allAppointments = computed(() => {
    return props.appointments?.data || [];
});

// Summary Metrics
const metrics = computed(() => {
    const list = allAppointments.value;
    return {
        total: props.appointments?.total || list.length,
        upcoming: list.filter((a) => isUpcoming(a)).length,
        confirmed: list.filter((a) => a.status === "confirmed").length,
        pending: list.filter((a) => a.status === "pending").length,
        completed: list.filter((a) => a.status === "completed").length,
        cancelled: list.filter((a) => a.status === "cancelled").length,
    };
});

// Spotlight Next Appointment
const nextAppointment = computed(() => {
    const list = allAppointments.value.filter((a) => isUpcoming(a));
    if (list.length === 0) return null;
    return [...list].sort((a, b) => {
        const dateA =
            a.appointment_date + "T" + (a.appointment_time || "00:00:00");
        const dateB =
            b.appointment_date + "T" + (b.appointment_time || "00:00:00");
        return dateA.localeCompare(dateB);
    })[0];
});

// Filtered List
const filteredAppointments = computed(() => {
    let list = allAppointments.value;

    if (selectedTab.value === "upcoming") {
        list = list.filter((a) => isUpcoming(a));
    } else if (selectedTab.value !== "all") {
        list = list.filter((a) => a.status === selectedTab.value);
    }

    if (searchQuery.value.trim()) {
        const query = searchQuery.value.toLowerCase();
        list = list.filter((a) => {
            const serviceMatch = a.service?.toLowerCase().includes(query);
            const notesMatch = a.notes?.toLowerCase().includes(query);
            const dentistMatch = (
                a.dentist?.name ||
                props.dentist?.name ||
                "Dr. Valdez"
            )
                .toLowerCase()
                .includes(query);
            const dateMatch = a.appointment_date?.includes(query);
            return serviceMatch || notesMatch || dentistMatch || dateMatch;
        });
    }

    return list;
});

// Actions
const openDetailsModal = (appointment) => {
    viewingAppointment.value = appointment;
    showDetailsModal.value = true;
};

const confirmCancel = (appointment) => {
    selectedAppointment.value = appointment;
    showCancelModal.value = true;
};

const executeCancel = () => {
    if (!selectedAppointment.value) return;
    router.patch(
        route("appointments.status.update", selectedAppointment.value.id),
        {
            status: "cancelled",
        },
        {
            preserveScroll: true,
            onSuccess: () => {
                showCancelModal.value = false;
                selectedAppointment.value = null;
                if (
                    viewingAppointment.value?.id ===
                    selectedAppointment.value?.id
                ) {
                    showDetailsModal.value = false;
                }
            },
        },
    );
};

const generateGoogleCalendarUrl = (appointment) => {
    if (!appointment) return "#";
    const title = encodeURIComponent(
        `Dental Visit: ${appointment.service} - Vitality Smile`,
    );
    const details = encodeURIComponent(
        `Appointment with ${appointment.dentist?.name || props.dentist?.name || "Dr. Valdez"} at Vitality Smile Dental Clinic.\nNotes: ${appointment.notes || "None"}`,
    );
    const location = encodeURIComponent(
        "Vitality Smile Dental Clinic, Main Avenue, Metro Manila",
    );

    const dateStr = appointment.appointment_date.replace(/-/g, "");
    const startTimeStr =
        (appointment.appointment_time || "09:00:00")
            .replace(/:/g, "")
            .slice(0, 4) + "00";
    const endTimeStr =
        (
            appointment.end_time ||
            appointment.calculated_end_time ||
            "10:00:00"
        )
            .replace(/:/g, "")
            .slice(0, 4) + "00";

    return `https://calendar.google.com/calendar/render?action=TEMPLATE&text=${title}&dates=${dateStr}T${startTimeStr}/${dateStr}T${endTimeStr}&details=${details}&location=${location}`;
};
</script>

<template>
    <Head title="My Appointments - Vitality Smile" />

    <AuthenticatedLayout>
        <!-- Header -->
        <template #header>
            <div
                class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4"
            >
                <div>
                    <div class="flex items-center gap-2">
                        <span
                            class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full bg-teal-50 border border-teal-200 text-teal-800 text-[11px] font-bold uppercase tracking-wider"
                        >
                            <i class="pi pi-calendar text-[10px]"></i>
                            <span>Patient Schedule</span>
                        </span>
                        <span class="text-xs text-gray-400">•</span>
                        <span class="text-xs font-medium text-gray-500"
                            >Attending: Dr. Valdez</span
                        >
                    </div>
                    <h2
                        class="mt-1 text-2xl font-extrabold text-gray-900 tracking-tight"
                    >
                        My Appointments
                    </h2>
                    <p class="text-xs sm:text-sm text-gray-500 mt-0.5">
                        Track upcoming clinical visits, review past treatments,
                        and manage your dental schedule
                    </p>
                </div>

                <div class="flex flex-wrap items-center gap-2.5">
                    <button
                        v-if="['admin', 'secretary', 'dentist', 'staff'].includes($page.props.auth.user.role)"
                        @click="triggerReminders"
                        :disabled="sendingReminders"
                        class="inline-flex items-center justify-center gap-2 rounded-xl bg-white border border-gray-200 px-4 py-2.5 text-xs sm:text-sm font-bold text-slate-700 shadow-2xs hover:bg-slate-50 hover:border-slate-300 transition-all disabled:opacity-60"
                        title="Scan upcoming appointments and dispatch reminders to staff, dentists, and patients"
                    >
                        <i :class="sendingReminders ? 'pi pi-spin pi-spinner text-teal-600' : 'pi pi-bell text-amber-500'" class="text-xs"></i>
                        <span>{{ sendingReminders ? 'Checking...' : 'Dispatch Reminders' }}</span>
                    </button>

                    <Link
                        :href="route('appointments.create')"
                        class="inline-flex items-center justify-center gap-2 rounded-xl bg-teal-700 px-5 py-2.5 text-xs sm:text-sm font-bold text-white shadow-md shadow-teal-700/20 hover:bg-teal-800 hover:shadow-lg transition-all focus:outline-none focus:ring-2 focus:ring-teal-500 focus:ring-offset-2"
                    >
                        <i class="pi pi-plus text-xs"></i>
                        <span>Book New Appointment</span>
                    </Link>
                </div>
            </div>
        </template>

        <div class="py-6 sm:py-8">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
                <!-- Patient Shared Cross-Navigation Bar -->
                <div class="flex items-center gap-2 overflow-x-auto pb-2 scrollbar-none border-b border-gray-200">
                    <Link
                        :href="route('appointments.index')"
                        class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl text-xs font-bold transition whitespace-nowrap bg-teal-700 text-white shadow-sm"
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
                        class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl text-xs font-bold transition whitespace-nowrap bg-white text-gray-600 hover:bg-gray-100 border border-gray-200/80"
                    >
                        <i class="pi pi-receipt text-xs"></i>
                        <span>My Receipts</span>
                    </Link>
                </div>

                <!-- Flash Alert -->
                <div
                    v-if="$page.props.flash && $page.props.flash.success"
                    class="flex items-center justify-between gap-3 bg-emerald-50 border border-emerald-300 text-emerald-900 px-4 py-3 rounded-2xl shadow-xs"
                >
                    <div class="flex items-center gap-2.5">
                        <i
                            class="pi pi-check-circle text-emerald-600 text-lg"
                        ></i>
                        <span class="text-sm font-semibold">{{
                            $page.props.flash.success
                        }}</span>
                    </div>
                </div>

                <!-- Medical Clearance Safety Alert Banner (For Patients with recorded conditions) -->
                <div
                    v-if="medicalClearance?.has_conditions && !medicalClearance?.clearance_presented"
                    class="rounded-2xl border-2 border-amber-300 dark:border-amber-700/60 bg-amber-50/90 dark:bg-amber-950/40 p-4 sm:p-5 shadow-xs flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4"
                >
                    <div class="flex items-start gap-3.5">
                        <div class="h-10 w-10 rounded-xl bg-amber-500 text-white flex items-center justify-center shrink-0 shadow-sm">
                            <i class="pi pi-exclamation-triangle text-lg"></i>
                        </div>
                        <div>
                            <div class="flex items-center gap-2 flex-wrap">
                                <h4 class="text-sm font-black text-amber-950 dark:text-amber-200 uppercase tracking-tight">
                                    Action Required: Physician Medical Clearance Mandatory
                                </h4>
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-black uppercase tracking-wider bg-rose-600 text-white">
                                    Clearance Required
                                </span>
                            </div>
                            <p class="text-xs text-amber-900 dark:text-amber-300 mt-1 leading-relaxed">
                                Because you have documented medical conditions (<strong>{{ medicalClearance.conditions.join(', ') }}</strong>), clinic safety protocols require a signed <strong>Physician Medical Clearance</strong> before dental treatment can be performed. Please bring your clearance letter to your visit.
                            </p>
                        </div>
                    </div>
                    <Link
                        :href="route('medical-histories.my-history')"
                        class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl bg-amber-600 hover:bg-amber-700 text-white text-xs font-bold transition shrink-0 shadow-xs"
                    >
                        <i class="pi pi-file text-xs"></i>
                        <span>Medical Profile</span>
                    </Link>
                </div>

                <!-- 4-Card Overview Metrics -->
                <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4">
                    <!-- Total Appointments -->
                    <div
                        @click="selectedTab = 'all'"
                        class="cursor-pointer rounded-2xl border bg-white p-4 sm:p-5 shadow-xs transition hover:shadow-md hover:border-teal-300"
                        :class="
                            selectedTab === 'all'
                                ? 'border-teal-500 ring-2 ring-teal-500/10'
                                : 'border-gray-200/80'
                        "
                    >
                        <div class="flex items-center justify-between">
                            <span
                                class="text-xs font-semibold uppercase tracking-wider text-gray-500"
                            >
                                Total Booked
                            </span>
                            <div
                                class="flex h-9 w-9 items-center justify-center rounded-xl bg-teal-50 text-teal-700"
                            >
                                <i class="pi pi-calendar text-sm"></i>
                            </div>
                        </div>
                        <p class="mt-2 text-2xl font-extrabold text-gray-900">
                            {{ metrics.total }}
                        </p>
                        <p class="mt-0.5 text-[11px] text-gray-400">
                            All scheduled records
                        </p>
                    </div>

                    <!-- Upcoming Visits -->
                    <div
                        @click="selectedTab = 'upcoming'"
                        class="cursor-pointer rounded-2xl border bg-white p-4 sm:p-5 shadow-xs transition hover:shadow-md hover:border-emerald-300"
                        :class="
                            selectedTab === 'upcoming'
                                ? 'border-emerald-500 ring-2 ring-emerald-500/10'
                                : 'border-gray-200/80'
                        "
                    >
                        <div class="flex items-center justify-between">
                            <span
                                class="text-xs font-semibold uppercase tracking-wider text-gray-500"
                            >
                                Upcoming
                            </span>
                            <div
                                class="flex h-9 w-9 items-center justify-center rounded-xl bg-emerald-50 text-emerald-700 relative"
                            >
                                <i class="pi pi-clock text-sm"></i>
                                <span
                                    v-if="metrics.upcoming > 0"
                                    class="absolute -top-1 -right-1 flex h-3 w-3"
                                >
                                    <span
                                        class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"
                                    ></span>
                                    <span
                                        class="relative inline-flex rounded-full h-3 w-3 bg-emerald-500"
                                    ></span>
                                </span>
                            </div>
                        </div>
                        <p class="mt-2 text-2xl font-extrabold text-emerald-700">
                            {{ metrics.upcoming }}
                        </p>
                        <p class="mt-0.5 text-[11px] text-gray-400">
                            Scheduled visits ahead
                        </p>
                    </div>

                    <!-- Pending Approval -->
                    <div
                        @click="selectedTab = 'pending'"
                        class="cursor-pointer rounded-2xl border bg-white p-4 sm:p-5 shadow-xs transition hover:shadow-md hover:border-amber-300"
                        :class="
                            selectedTab === 'pending'
                                ? 'border-amber-500 ring-2 ring-amber-500/10'
                                : 'border-gray-200/80'
                        "
                    >
                        <div class="flex items-center justify-between">
                            <span
                                class="text-xs font-semibold uppercase tracking-wider text-gray-500"
                            >
                                Pending Review
                            </span>
                            <div
                                class="flex h-9 w-9 items-center justify-center rounded-xl bg-amber-50 text-amber-700"
                            >
                                <i class="pi pi-hourglass text-sm"></i>
                            </div>
                        </div>
                        <p class="mt-2 text-2xl font-extrabold text-amber-700">
                            {{ metrics.pending }}
                        </p>
                        <p class="mt-0.5 text-[11px] text-gray-400">
                            Awaiting clinic confirmation
                        </p>
                    </div>

                    <!-- Completed -->
                    <div
                        @click="selectedTab = 'completed'"
                        class="cursor-pointer rounded-2xl border bg-white p-4 sm:p-5 shadow-xs transition hover:shadow-md hover:border-blue-300"
                        :class="
                            selectedTab === 'completed'
                                ? 'border-blue-500 ring-2 ring-blue-500/10'
                                : 'border-gray-200/80'
                        "
                    >
                        <div class="flex items-center justify-between">
                            <span
                                class="text-xs font-semibold uppercase tracking-wider text-gray-500"
                            >
                                Completed
                            </span>
                            <div
                                class="flex h-9 w-9 items-center justify-center rounded-xl bg-blue-50 text-blue-700"
                            >
                                <i class="pi pi-check-circle text-sm"></i>
                            </div>
                        </div>
                        <p class="mt-2 text-2xl font-extrabold text-blue-700">
                            {{ metrics.completed }}
                        </p>
                        <p class="mt-0.5 text-[11px] text-gray-400">
                            Past clinical treatments
                        </p>
                    </div>
                </div>

                <!-- Spotlight: Next Upcoming Appointment (If exists) -->
                <div
                    v-if="nextAppointment"
                    class="relative overflow-hidden rounded-3xl bg-gradient-to-r from-teal-900 via-teal-800 to-slate-900 text-white p-6 sm:p-7 shadow-xl shadow-teal-950/15"
                >
                    <div
                        class="pointer-events-none absolute -right-10 -bottom-10 h-64 w-64 rounded-full bg-teal-500/15 blur-2xl"
                        aria-hidden="true"
                    ></div>

                    <div
                        class="relative flex flex-col md:flex-row md:items-center md:justify-between gap-6"
                    >
                        <div class="space-y-3">
                            <div class="flex flex-wrap items-center gap-2">
                                <span
                                    class="inline-flex items-center gap-1.5 rounded-full bg-emerald-500/20 border border-emerald-400/30 px-3 py-1 text-xs font-bold text-emerald-300"
                                >
                                    <span
                                        class="h-2 w-2 rounded-full bg-emerald-400 animate-pulse"
                                    ></span>
                                    <span>Next Scheduled Visit</span>
                                </span>
                                <span
                                    class="rounded-full bg-white/10 px-2.5 py-0.5 text-xs text-teal-200"
                                >
                                    {{
                                        nextAppointment.duration_minutes || 60
                                    }}
                                    minutes
                                </span>
                            </div>

                            <div>
                                <h3
                                    class="text-xl sm:text-2xl font-extrabold tracking-tight text-white"
                                >
                                    {{ nextAppointment.service }}
                                </h3>
                                <p class="text-xs sm:text-sm text-teal-100/80 mt-1">
                                    Attending Clinician:
                                    <strong class="text-white">{{
                                        nextAppointment.dentist?.name ||
                                        props.dentist?.name ||
                                        "Dr. Valdez"
                                    }}</strong>
                                    • Vitality Smile Dental Operatory
                                </p>
                            </div>

                            <!-- Date / Time Highlights -->
                            <div
                                class="inline-flex flex-wrap items-center gap-3 pt-1"
                            >
                                <div
                                    class="flex items-center gap-2 rounded-xl bg-white/10 backdrop-blur-md px-3 py-1.5 text-xs font-semibold text-white border border-white/15"
                                >
                                    <i
                                        class="pi pi-calendar text-teal-300"
                                    ></i>
                                    <span>{{
                                        formatFullDate(
                                            nextAppointment.appointment_date,
                                        )
                                    }}</span>
                                </div>
                                <div
                                    class="flex items-center gap-2 rounded-xl bg-white/10 backdrop-blur-md px-3 py-1.5 text-xs font-semibold text-white border border-white/15"
                                >
                                    <i class="pi pi-clock text-teal-300"></i>
                                    <span>
                                        {{
                                            formatTime(
                                                nextAppointment.appointment_time,
                                            )
                                        }}
                                        –
                                        {{
                                            formatTime(
                                                nextAppointment.end_time ||
                                                    nextAppointment.calculated_end_time,
                                            )
                                        }}
                                    </span>
                                </div>
                            </div>
                        </div>

                        <!-- Action Buttons on Spotlight -->
                        <div
                            class="flex flex-col sm:flex-row items-stretch sm:items-center gap-2.5 shrink-0"
                        >
                            <button
                                type="button"
                                @click="openDetailsModal(nextAppointment)"
                                class="inline-flex items-center justify-center gap-2 rounded-xl bg-white text-teal-900 hover:bg-teal-50 px-4 py-2.5 text-xs font-bold shadow-sm transition"
                            >
                                <i class="pi pi-eye text-xs"></i>
                                <span>View Details</span>
                            </button>
                            <a
                                :href="
                                    generateGoogleCalendarUrl(nextAppointment)
                                "
                                target="_blank"
                                rel="noopener noreferrer"
                                class="inline-flex items-center justify-center gap-2 rounded-xl bg-teal-800/80 hover:bg-teal-800 text-teal-100 hover:text-white px-4 py-2.5 text-xs font-semibold border border-teal-700 transition"
                            >
                                <i class="pi pi-calendar text-xs"></i>
                                <span>Add to Calendar</span>
                            </a>
                            <button
                                v-if="
                                    nextAppointment.status === 'pending' ||
                                    nextAppointment.status === 'confirmed'
                                "
                                type="button"
                                @click="confirmCancel(nextAppointment)"
                                class="inline-flex items-center justify-center gap-1.5 rounded-xl text-rose-300 hover:text-rose-100 hover:bg-rose-950/40 px-3 py-2 text-xs font-semibold transition"
                            >
                                <span>Cancel</span>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Main Card: Filter Tabs + Search + Table -->
                <div
                    class="rounded-3xl border border-gray-200/90 bg-white shadow-sm overflow-hidden"
                >
                    <!-- Filter Toolbar -->
                    <div
                        class="p-4 sm:p-5 border-b border-gray-100 flex flex-col md:flex-row md:items-center md:justify-between gap-4"
                    >
                        <!-- Status Tabs -->
                        <div
                            class="flex items-center gap-1.5 overflow-x-auto pb-1 md:pb-0 scrollbar-none"
                        >
                            <button
                                type="button"
                                @click="selectedTab = 'all'"
                                :class="[
                                    'px-3.5 py-1.5 text-xs font-bold rounded-xl transition cursor-pointer whitespace-nowrap',
                                    selectedTab === 'all'
                                        ? 'bg-teal-700 text-white shadow-xs'
                                        : 'bg-gray-100 text-gray-600 hover:bg-gray-200/80',
                                ]"
                            >
                                All ({{ metrics.total }})
                            </button>
                            <button
                                type="button"
                                @click="selectedTab = 'upcoming'"
                                :class="[
                                    'px-3.5 py-1.5 text-xs font-bold rounded-xl transition cursor-pointer whitespace-nowrap',
                                    selectedTab === 'upcoming'
                                        ? 'bg-emerald-600 text-white shadow-xs'
                                        : 'bg-gray-100 text-gray-600 hover:bg-gray-200/80',
                                ]"
                            >
                                Upcoming ({{ metrics.upcoming }})
                            </button>
                            <button
                                type="button"
                                @click="selectedTab = 'confirmed'"
                                :class="[
                                    'px-3.5 py-1.5 text-xs font-bold rounded-xl transition cursor-pointer whitespace-nowrap',
                                    selectedTab === 'confirmed'
                                        ? 'bg-teal-700 text-white shadow-xs'
                                        : 'bg-gray-100 text-gray-600 hover:bg-gray-200/80',
                                ]"
                            >
                                Confirmed ({{ metrics.confirmed }})
                            </button>
                            <button
                                type="button"
                                @click="selectedTab = 'pending'"
                                :class="[
                                    'px-3.5 py-1.5 text-xs font-bold rounded-xl transition cursor-pointer whitespace-nowrap',
                                    selectedTab === 'pending'
                                        ? 'bg-amber-600 text-white shadow-xs'
                                        : 'bg-gray-100 text-gray-600 hover:bg-gray-200/80',
                                ]"
                            >
                                Pending ({{ metrics.pending }})
                            </button>
                            <button
                                type="button"
                                @click="selectedTab = 'completed'"
                                :class="[
                                    'px-3.5 py-1.5 text-xs font-bold rounded-xl transition cursor-pointer whitespace-nowrap',
                                    selectedTab === 'completed'
                                        ? 'bg-blue-600 text-white shadow-xs'
                                        : 'bg-gray-100 text-gray-600 hover:bg-gray-200/80',
                                ]"
                            >
                                Completed ({{ metrics.completed }})
                            </button>
                            <button
                                type="button"
                                @click="selectedTab = 'cancelled'"
                                :class="[
                                    'px-3.5 py-1.5 text-xs font-bold rounded-xl transition cursor-pointer whitespace-nowrap',
                                    selectedTab === 'cancelled'
                                        ? 'bg-rose-600 text-white shadow-xs'
                                        : 'bg-gray-100 text-gray-600 hover:bg-gray-200/80',
                                ]"
                            >
                                Cancelled ({{ metrics.cancelled }})
                            </button>
                        </div>

                        <!-- Search Field -->
                        <div class="relative w-full md:w-72">
                            <i
                                class="pi pi-search absolute left-3.5 top-1/2 -translate-y-1/2 text-gray-400 text-xs"
                            ></i>
                            <input
                                v-model="searchQuery"
                                type="text"
                                placeholder="Search service, notes, date..."
                                class="w-full rounded-xl border border-gray-200 bg-gray-50/70 py-2 pl-9 pr-8 text-xs text-gray-900 placeholder-gray-400 focus:border-teal-500 focus:bg-white focus:outline-none focus:ring-1 focus:ring-teal-500 transition dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100 dark:placeholder-slate-400 dark:focus:bg-slate-800"
                            />
                            <button
                                v-if="searchQuery"
                                @click="searchQuery = ''"
                                type="button"
                                class="absolute right-2.5 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600"
                            >
                                <i class="pi pi-times text-xs"></i>
                            </button>
                        </div>
                    </div>

                    <!-- Desktop Table View (hidden on small screens) -->
                    <div class="hidden md:block overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-100">
                            <thead class="bg-gray-50/80">
                                <tr>
                                    <th
                                        scope="col"
                                        class="px-6 py-3.5 text-left text-[11px] font-bold text-gray-500 uppercase tracking-wider"
                                    >
                                        Procedure / Service
                                    </th>
                                    <th
                                        scope="col"
                                        class="px-6 py-3.5 text-left text-[11px] font-bold text-gray-500 uppercase tracking-wider"
                                    >
                                        Date & Time
                                    </th>
                                    <th
                                        scope="col"
                                        class="px-6 py-3.5 text-left text-[11px] font-bold text-gray-500 uppercase tracking-wider"
                                    >
                                        Attending Dentist
                                    </th>
                                    <th
                                        scope="col"
                                        class="px-6 py-3.5 text-left text-[11px] font-bold text-gray-500 uppercase tracking-wider"
                                    >
                                        Status
                                    </th>
                                    <th
                                        scope="col"
                                        class="px-6 py-3.5 text-left text-[11px] font-bold text-gray-500 uppercase tracking-wider"
                                    >
                                        Patient Notes
                                    </th>
                                    <th
                                        scope="col"
                                        class="px-6 py-3.5 text-right text-[11px] font-bold text-gray-500 uppercase tracking-wider"
                                    >
                                        Actions
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 bg-white">
                                <tr
                                    v-for="appointment in filteredAppointments"
                                    :key="appointment.id"
                                    class="hover:bg-teal-50/30 transition-colors group"
                                >
                                    <!-- Service -->
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center gap-3">
                                            <div
                                                class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-teal-50 text-teal-700 border border-teal-100 group-hover:bg-teal-600 group-hover:text-white transition-colors"
                                            >
                                                <i
                                                    :class="[
                                                        'pi',
                                                        getServiceIcon(
                                                            appointment.service,
                                                        ),
                                                        'text-sm',
                                                    ]"
                                                ></i>
                                            </div>
                                            <div>
                                                <div
                                                    class="font-bold text-gray-900 text-sm"
                                                >
                                                    {{ appointment.service }}
                                                </div>
                                                <div
                                                    class="text-[11px] text-gray-400 mt-0.5"
                                                >
                                                    Ref #APT-{{ appointment.id }}
                                                </div>
                                            </div>
                                        </div>
                                    </td>

                                    <!-- Date & Time -->
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div
                                            class="font-semibold text-gray-900 text-sm flex items-center gap-1.5"
                                        >
                                            <i
                                                class="pi pi-calendar text-xs text-teal-600"
                                            ></i>
                                            <span>{{
                                                formatDate(
                                                    appointment.appointment_date,
                                                )
                                            }}</span>
                                        </div>
                                        <div
                                            class="text-xs text-gray-600 font-medium flex items-center gap-2 mt-1"
                                        >
                                            <span>
                                                {{
                                                    formatTime(
                                                        appointment.appointment_time,
                                                    )
                                                }}
                                                –
                                                {{
                                                    formatTime(
                                                        appointment.end_time ||
                                                            appointment.calculated_end_time,
                                                    )
                                                }}
                                            </span>
                                            <span
                                                class="rounded-md bg-teal-50 border border-teal-200/80 px-1.5 py-0.2 text-[10px] text-teal-800 font-semibold"
                                            >
                                                {{
                                                    appointment.duration_minutes ||
                                                    60
                                                }}m
                                            </span>
                                        </div>
                                        <div
                                            v-if="appointment.created_at"
                                            class="text-[11px] text-gray-500 mt-1 flex items-center gap-1"
                                            :title="'Booking placed on ' + formatBookingDate(appointment.created_at)"
                                        >
                                            <i class="pi pi-calendar-plus text-[10px] text-teal-600"></i>
                                            <span>Booked: {{ formatBookingDate(appointment.created_at) }}</span>
                                        </div>
                                    </td>

                                    <!-- Dentist -->
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center gap-2">
                                            <div
                                                class="h-7 w-7 rounded-full bg-slate-100 border border-slate-200 flex items-center justify-center text-xs font-bold text-slate-700"
                                            >
                                                DV
                                            </div>
                                            <div>
                                                <p
                                                    class="text-sm font-semibold text-gray-900"
                                                >
                                                    {{
                                                        appointment.dentist
                                                            ?.name ||
                                                        props.dentist?.name ||
                                                        "Dr. Valdez"
                                                    }}
                                                </p>
                                                <p
                                                    class="text-[10px] text-gray-400"
                                                >
                                                    Attending Dentist
                                                </p>
                                            </div>
                                        </div>
                                    </td>

                                    <!-- Status -->
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span
                                            :class="[
                                                'inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold border',
                                                getStatusBadge(
                                                    appointment.status,
                                                ).bg,
                                            ]"
                                        >
                                            <span
                                                :class="[
                                                    'h-1.5 w-1.5 rounded-full',
                                                    getStatusBadge(
                                                        appointment.status,
                                                    ).dot,
                                                ]"
                                            ></span>
                                            <span>{{
                                                getStatusBadge(
                                                    appointment.status,
                                                ).label
                                            }}</span>
                                        </span>
                                    </td>

                                    <!-- Notes -->
                                    <td class="px-6 py-4 text-xs text-gray-500 max-w-xs">
                                        <p
                                            class="truncate"
                                            :title="appointment.notes"
                                        >
                                            {{ appointment.notes || "—" }}
                                        </p>
                                    </td>

                                    <!-- Actions -->
                                    <td
                                        class="px-6 py-4 whitespace-nowrap text-right text-xs"
                                    >
                                        <div
                                            class="flex items-center justify-end gap-2"
                                        >
                                            <button
                                                type="button"
                                                @click="
                                                    openDetailsModal(
                                                        appointment,
                                                    )
                                                "
                                                class="inline-flex items-center gap-1 rounded-lg border border-gray-200 bg-white px-2.5 py-1.5 font-semibold text-gray-700 shadow-2xs hover:bg-gray-50 hover:text-teal-700 transition"
                                                title="View appointment details"
                                            >
                                                <i
                                                    class="pi pi-eye text-[11px]"
                                                ></i>
                                                <span>Details</span>
                                            </button>

                                            <button
                                                v-if="
                                                    appointment.status ===
                                                        'pending' ||
                                                    appointment.status ===
                                                        'confirmed'
                                                "
                                                type="button"
                                                @click="
                                                    confirmCancel(appointment)
                                                "
                                                class="inline-flex items-center gap-1 rounded-lg border border-rose-200 bg-rose-50 px-2.5 py-1.5 font-semibold text-rose-700 hover:bg-rose-100 transition"
                                                title="Cancel appointment"
                                            >
                                                <i
                                                    class="pi pi-times text-[10px]"
                                                ></i>
                                                <span>Cancel</span>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Mobile Cards View (displayed on phones & small tablets) -->
                    <div
                        class="md:hidden divide-y divide-gray-100 bg-white p-3 space-y-3"
                    >
                        <div
                            v-for="appointment in filteredAppointments"
                            :key="'mobile-' + appointment.id"
                            class="rounded-2xl border border-gray-100 bg-white p-4 shadow-2xs space-y-3 hover:border-teal-200 transition"
                        >
                            <!-- Header with Service & Status -->
                            <div class="flex items-start justify-between gap-2">
                                <div class="flex items-center gap-2.5">
                                    <div
                                        class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-teal-50 text-teal-700 border border-teal-100"
                                    >
                                        <i
                                            :class="[
                                                'pi',
                                                getServiceIcon(
                                                    appointment.service,
                                                ),
                                                'text-xs',
                                            ]"
                                        ></i>
                                    </div>
                                    <div>
                                        <h4
                                            class="font-bold text-gray-900 text-sm"
                                        >
                                            {{ appointment.service }}
                                        </h4>
                                        <p class="text-[10px] text-gray-400">
                                            Ref #APT-{{ appointment.id }}
                                        </p>
                                    </div>
                                </div>

                                <span
                                    :class="[
                                        'inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[11px] font-bold border shrink-0',
                                        getStatusBadge(appointment.status).bg,
                                    ]"
                                >
                                    <span
                                        :class="[
                                            'h-1.5 w-1.5 rounded-full',
                                            getStatusBadge(appointment.status)
                                                .dot,
                                        ]"
                                    ></span>
                                    <span>{{
                                        getStatusBadge(appointment.status).label
                                    }}</span>
                                </span>
                            </div>

                            <!-- Date, Time & Doctor Info -->
                            <div
                                class="rounded-xl bg-gray-50/80 p-3 space-y-1.5 text-xs"
                            >
                                <div
                                    class="flex items-center justify-between font-semibold text-gray-800"
                                >
                                    <span class="flex items-center gap-1.5">
                                        <i
                                            class="pi pi-calendar text-teal-600 text-xs"
                                        ></i>
                                        <span>{{
                                            formatDate(
                                                appointment.appointment_date,
                                            )
                                        }}</span>
                                    </span>
                                    <span
                                        class="rounded-md bg-teal-50 border border-teal-200 px-1.5 py-0.5 text-[10px] text-teal-800"
                                    >
                                        {{
                                            appointment.duration_minutes || 60
                                        }}
                                        mins
                                    </span>
                                </div>
                                <div
                                    class="flex items-center justify-between text-gray-600"
                                >
                                    <span class="flex items-center gap-1.5">
                                        <i
                                            class="pi pi-clock text-teal-600 text-xs"
                                        ></i>
                                        <span>
                                            {{
                                                formatTime(
                                                    appointment.appointment_time,
                                                )
                                            }}
                                            –
                                            {{
                                                formatTime(
                                                    appointment.end_time ||
                                                        appointment.calculated_end_time,
                                                )
                                            }}
                                        </span>
                                    </span>
                                    <span class="text-gray-500 font-medium">
                                        Dr. Valdez
                                    </span>
                                </div>
                                <div
                                    v-if="appointment.created_at"
                                    class="pt-1 text-[11px] text-gray-500 flex items-center gap-1.5"
                                >
                                    <i class="pi pi-calendar-plus text-teal-600 text-[10px]"></i>
                                    <span>Booked: {{ formatBookingDate(appointment.created_at) }}</span>
                                </div>
                            </div>

                            <!-- Notes if available -->
                            <div
                                v-if="appointment.notes"
                                class="text-xs text-gray-500 line-clamp-2"
                            >
                                <span class="font-semibold text-gray-700"
                                    >Note:</span
                                >
                                {{ appointment.notes }}
                            </div>

                            <!-- Mobile Action Buttons -->
                            <div
                                class="pt-2 border-t border-gray-100 flex items-center justify-between gap-2"
                            >
                                <button
                                    type="button"
                                    @click="openDetailsModal(appointment)"
                                    class="flex-1 inline-flex items-center justify-center gap-1.5 rounded-xl border border-gray-200 bg-white py-2 text-xs font-bold text-gray-700 hover:bg-gray-50 transition"
                                >
                                    <i class="pi pi-eye text-xs"></i>
                                    <span>Details</span>
                                </button>
                                <button
                                    v-if="
                                        appointment.status === 'pending' ||
                                        appointment.status === 'confirmed'
                                    "
                                    type="button"
                                    @click="confirmCancel(appointment)"
                                    class="flex-1 inline-flex items-center justify-center gap-1.5 rounded-xl border border-rose-200 bg-rose-50 py-2 text-xs font-bold text-rose-700 hover:bg-rose-100 transition"
                                >
                                    <i class="pi pi-times text-xs"></i>
                                    <span>Cancel</span>
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Filter Empty State (Search / Tab yielded no results) -->
                    <div
                        v-if="
                            filteredAppointments.length === 0 &&
                            allAppointments.length > 0
                        "
                        class="px-6 py-12 text-center text-gray-500"
                    >
                        <div
                            class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-gray-100 text-gray-400 mb-3"
                        >
                            <i class="pi pi-filter-slash text-2xl"></i>
                        </div>
                        <p class="text-base font-bold text-gray-900">
                            No appointments found
                        </p>
                        <p class="mt-1 text-xs text-gray-500">
                            No records matched your selected filter or search
                            criteria.
                        </p>
                        <div class="mt-4">
                            <button
                                type="button"
                                @click="
                                    selectedTab = 'all';
                                    searchQuery = '';
                                "
                                class="inline-flex items-center gap-1.5 rounded-xl bg-teal-50 px-4 py-2 text-xs font-bold text-teal-800 hover:bg-teal-100 transition"
                            >
                                <i class="pi pi-refresh text-xs"></i>
                                <span>Clear Filters</span>
                            </button>
                        </div>
                    </div>

                    <!-- Zero Bookings State (Patient has no appointments yet) -->
                    <div
                        v-if="allAppointments.length === 0"
                        class="px-6 py-16 text-center"
                    >
                        <div
                            class="mx-auto flex h-16 w-16 items-center justify-center rounded-3xl bg-teal-50 border border-teal-100 text-teal-700 shadow-sm mb-4"
                        >
                            <i class="pi pi-calendar-plus text-3xl"></i>
                        </div>
                        <h3 class="text-lg font-extrabold text-gray-900">
                            No Appointments Yet
                        </h3>
                        <p class="mt-1.5 text-xs sm:text-sm text-gray-500 max-w-sm mx-auto leading-relaxed">
                            You haven't scheduled any dental clinic visits. Book
                            your consultation with Dr. Valdez in just 2 minutes!
                        </p>
                        <div class="mt-6">
                            <Link
                                :href="route('appointments.create')"
                                class="inline-flex items-center justify-center gap-2 rounded-xl bg-teal-700 px-6 py-3 text-sm font-bold text-white shadow-md shadow-teal-700/20 hover:bg-teal-800 transition"
                            >
                                <i class="pi pi-plus text-xs"></i>
                                <span>Book Your First Visit</span>
                            </Link>
                        </div>
                    </div>

                    <!-- Pagination -->
                    <Pagination
                        v-if="appointments"
                        :paginator="appointments"
                        label="appointments"
                    />
                </div>
            </div>
        </div>

        <!-- Appointment Details Modal -->
        <Modal
            :show="showDetailsModal"
            @close="showDetailsModal = false"
            maxWidth="lg"
        >
            <div class="p-6 sm:p-7 space-y-6">
                <!-- Modal Header -->
                <div class="flex items-start justify-between gap-4 border-b border-gray-100 pb-4">
                    <div class="flex items-center gap-3">
                        <div
                            class="flex h-12 w-12 items-center justify-center rounded-2xl bg-teal-50 border border-teal-200/80 text-teal-700"
                        >
                            <i
                                :class="[
                                    'pi',
                                    getServiceIcon(
                                        viewingAppointment?.service,
                                    ),
                                    'text-lg',
                                ]"
                            ></i>
                        </div>
                        <div>
                            <span
                                class="text-[10px] font-bold uppercase tracking-wider text-teal-700"
                            >
                                Appointment Details
                            </span>
                            <h3 class="text-lg font-bold text-gray-900">
                                {{ viewingAppointment?.service }}
                            </h3>
                            <p class="text-xs text-gray-400">
                                Reference #APT-{{ viewingAppointment?.id }}
                            </p>
                        </div>
                    </div>

                    <button
                        type="button"
                        @click="showDetailsModal = false"
                        class="rounded-xl p-2 text-gray-400 hover:bg-gray-100 hover:text-gray-600 transition"
                        aria-label="Close"
                    >
                        <i class="pi pi-times text-sm"></i>
                    </button>
                </div>

                <!-- Schedule Card in Modal -->
                <div
                    class="rounded-2xl bg-teal-50/60 border border-teal-100 p-4 space-y-3"
                >
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2 text-xs font-bold text-teal-950">
                            <i class="pi pi-calendar text-teal-700"></i>
                            <span>{{
                                formatFullDate(
                                    viewingAppointment?.appointment_date,
                                )
                            }}</span>
                        </div>
                        <span
                            :class="[
                                'inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[11px] font-bold border',
                                getStatusBadge(viewingAppointment?.status).bg,
                            ]"
                        >
                            <span
                                :class="[
                                    'h-1.5 w-1.5 rounded-full',
                                    getStatusBadge(viewingAppointment?.status)
                                        .dot,
                                ]"
                            ></span>
                            <span>{{
                                getStatusBadge(viewingAppointment?.status).label
                            }}</span>
                        </span>
                    </div>

                    <div
                        class="flex items-center justify-between text-xs text-gray-700 border-t border-teal-100/70 pt-2"
                    >
                        <span class="flex items-center gap-1.5">
                            <i class="pi pi-clock text-teal-600"></i>
                            <span>
                                {{
                                    formatTime(
                                        viewingAppointment?.appointment_time,
                                    )
                                }}
                                –
                                {{
                                    formatTime(
                                        viewingAppointment?.end_time ||
                                            viewingAppointment?.calculated_end_time,
                                    )
                                }}
                            </span>
                        </span>
                        <span class="text-teal-800 font-semibold">
                            Duration:
                            {{
                                viewingAppointment?.duration_minutes || 60
                            }}
                            minutes
                        </span>
                    </div>
                </div>

                <!-- Details Grid -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-xs">
                    <div
                        class="rounded-xl border border-gray-100 p-3 bg-white space-y-1"
                    >
                        <p class="text-gray-400 font-medium uppercase tracking-wider text-[10px]">
                            Attending Dentist
                        </p>
                        <p class="font-bold text-gray-900 text-sm">
                            {{
                                viewingAppointment?.dentist?.name ||
                                props.dentist?.name ||
                                "Dr. Valdez"
                            }}
                        </p>
                        <p class="text-teal-700 text-[11px]">
                            Licensed Dental Healthcare Clinician
                        </p>
                    </div>

                    <div
                        class="rounded-xl border border-gray-100 p-3 bg-white space-y-1"
                    >
                        <p class="text-gray-400 font-medium uppercase tracking-wider text-[10px]">
                            Clinic Location
                        </p>
                        <p class="font-bold text-gray-900 text-sm">
                            Vitality Smile Operatory
                        </p>
                        <p class="text-gray-500 text-[11px]">
                            Ground Floor, Healthcare Suite, Main Avenue
                        </p>
                    </div>
                </div>

                <!-- Patient Notes -->
                <div
                    class="rounded-xl border border-gray-100 p-3 bg-gray-50/70 text-xs space-y-1"
                >
                    <p class="text-gray-400 font-medium uppercase tracking-wider text-[10px]">
                        Your Notes / Special Requests
                    </p>
                    <p class="text-gray-800 leading-relaxed">
                        {{
                            viewingAppointment?.notes ||
                            "No special notes provided for this appointment."
                        }}
                    </p>
                </div>

                <!-- Helpful Patient Reminder -->
                <div
                    class="flex items-start gap-2.5 rounded-xl bg-amber-50/70 border border-amber-200/80 p-3 text-xs text-amber-900"
                >
                    <i class="pi pi-info-circle text-amber-600 mt-0.5"></i>
                    <p class="leading-relaxed">
                        Please arrive <strong>10 minutes prior</strong> to your
                        scheduled time. If you need to reschedule or have
                        questions, you may cancel or call our hotline.
                    </p>
                </div>

                <!-- Modal Actions -->
                <div
                    class="flex flex-col sm:flex-row items-center justify-between gap-3 border-t border-gray-100 pt-4"
                >
                    <a
                        :href="generateGoogleCalendarUrl(viewingAppointment)"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="w-full sm:w-auto inline-flex items-center justify-center gap-2 rounded-xl border border-gray-300 bg-white px-4 py-2 text-xs font-bold text-gray-700 hover:bg-gray-50 transition"
                    >
                        <i class="pi pi-calendar text-xs text-teal-700"></i>
                        <span>Add to Google Calendar</span>
                    </a>

                    <div class="flex items-center gap-2 w-full sm:w-auto">
                        <button
                            v-if="
                                viewingAppointment?.status === 'pending' ||
                                viewingAppointment?.status === 'confirmed'
                            "
                            type="button"
                            @click="
                                showDetailsModal = false;
                                confirmCancel(viewingAppointment);
                            "
                            class="flex-1 sm:flex-none rounded-xl border border-rose-200 bg-rose-50 px-4 py-2 text-xs font-bold text-rose-700 hover:bg-rose-100 transition"
                        >
                            Cancel Visit
                        </button>

                        <button
                            type="button"
                            @click="showDetailsModal = false"
                            class="flex-1 sm:flex-none rounded-xl bg-gray-900 px-5 py-2 text-xs font-bold text-white hover:bg-gray-800 transition"
                        >
                            Done
                        </button>
                    </div>
                </div>
            </div>
        </Modal>

        <!-- Cancel Confirmation Modal -->
        <Modal
            :show="showCancelModal"
            @close="showCancelModal = false"
            maxWidth="md"
        >
            <div class="p-6 sm:p-7 space-y-4">
                <div class="flex items-start gap-3.5">
                    <div
                        class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-rose-100 text-rose-600 border border-rose-200"
                    >
                        <i class="pi pi-exclamation-triangle text-xl"></i>
                    </div>
                    <div>
                        <h3 class="text-lg font-bold text-gray-900">
                            Cancel Dental Appointment
                        </h3>
                        <p class="text-xs sm:text-sm text-gray-500 mt-1 leading-relaxed">
                            Are you sure you want to cancel your visit for
                            <strong class="text-gray-900">{{
                                selectedAppointment?.service
                            }}</strong>
                            on
                            <strong class="text-gray-900">{{
                                formatDate(
                                    selectedAppointment?.appointment_date,
                                )
                            }}</strong>
                            at
                            <strong class="text-gray-900">{{
                                formatTime(
                                    selectedAppointment?.appointment_time,
                                )
                            }}</strong
                            >?
                        </p>
                    </div>
                </div>

                <div
                    class="rounded-xl bg-rose-50/70 border border-rose-200/80 p-3 text-xs text-rose-800"
                >
                    <p>
                        This scheduled slot will be released back to the clinic's
                        open calendar. You can book another appointment anytime.
                    </p>
                </div>

                <div
                    class="mt-6 flex flex-col-reverse sm:flex-row justify-end gap-2.5 border-t border-gray-100 pt-4"
                >
                    <button
                        type="button"
                        @click="showCancelModal = false"
                        class="rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-xs font-bold text-gray-700 shadow-2xs hover:bg-gray-50 transition"
                    >
                        Keep Appointment
                    </button>
                    <button
                        type="button"
                        @click="executeCancel"
                        class="rounded-xl bg-rose-600 px-5 py-2.5 text-xs font-bold text-white shadow-sm hover:bg-rose-700 transition"
                    >
                        Yes, Cancel Appointment
                    </button>
                </div>
            </div>
        </Modal>
    </AuthenticatedLayout>
</template>
