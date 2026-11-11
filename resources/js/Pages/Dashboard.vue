<script setup>
import { computed } from "vue";
import { Head, Link, usePage } from "@inertiajs/vue3";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";

const page = usePage();

const props = defineProps({
    stats: {
        type: Object,
        default: () => ({
            total_patients: 0,
            today_appointments_count: 0,
            pending_count: 0,
            confirmed_today_count: 0,
            total_receivables: 0,
            total_collected: 0,
            today_collected: 0,
            unpaid_invoices_count: 0,
            total_treatments: 0,
            upcoming_week_count: 0,
            dentist_today_appointments: 0,
            dentist_confirmed_today: 0,
            dentist_treatments_count: 0,
            dentist_pending_count: 0,
        }),
    },
    todayAppointments: {
        type: Array,
        default: () => [],
    },
    recentPending: {
        type: Array,
        default: () => [],
    },
    recentPatients: {
        type: Array,
        default: () => [],
    },
    recentActivities: {
        type: Array,
        default: () => [],
    },
});

const userRole = computed(() => page.props.auth?.user?.role || "staff");
const userName = computed(() => page.props.auth?.user?.name || "User");

const getGreeting = () => {
    const hour = new Date().getHours();
    if (hour < 12) return "Good morning";
    if (hour < 18) return "Good afternoon";
    return "Good evening";
};

const currentDateFormatted = computed(() => {
    return new Date().toLocaleDateString("en-PH", {
        weekday: "long",
        year: "numeric",
        month: "short",
        day: "numeric",
    });
});

const roleMeta = computed(() => {
    switch (userRole.value) {
        case "dentist":
            return {
                badge: "Attending Dentist Operatory",
                badgeIcon: "pi pi-heart",
                badgeClass: "bg-teal-50 text-teal-800 border-teal-200",
                subtitle:
                    "Clinical operatory hub • consultations, treatment records, tooth charting & care plans",
            };
        case "secretary":
            return {
                badge: "Appointment Desk & Coordination",
                badgeIcon: "pi pi-calendar",
                badgeClass: "bg-blue-50 text-blue-800 border-blue-200",
                subtitle:
                    "Patient intake coordination • triage booking requests, schedule doctor visits & reception",
            };
        case "staff":
            return {
                badge: "Front Desk & Billing Hub",
                badgeIcon: "pi pi-wallet",
                badgeClass: "bg-emerald-50 text-emerald-800 border-emerald-200",
                subtitle:
                    "Front desk operations • patient registration, billing statements, payments & cash register",
            };
        case "admin":
        default:
            return {
                badge: "Executive Clinic Administration",
                badgeIcon: "pi pi-shield",
                badgeClass: "bg-slate-50 text-slate-800 border-slate-200",
                subtitle:
                    "Clinic administration • comprehensive oversight, financial performance & system security",
            };
    }
});

const kpis = computed(() => {
    if (userRole.value === "dentist") {
        const myToday =
            props.stats.dentist_today_appointments ??
            props.stats.today_appointments_count;
        const myConfirmed =
            props.stats.dentist_confirmed_today ??
            props.stats.confirmed_today_count;
        const myTreatments =
            props.stats.dentist_treatments_count ??
            props.stats.total_treatments;
        const myPending =
            props.stats.dentist_pending_count ?? props.stats.pending_count;

        return [
            {
                title: "Today's Clinical Visits",
                value: myToday,
                subtext: `${myConfirmed} confirmed for today`,
                icon: "pi pi-calendar-clock",
                iconColor: "text-teal-600 bg-teal-50 border-teal-100 dark:text-teal-400 dark:bg-teal-950/50 dark:border-teal-800/60",
                href: route("appointments.index"),
                accent: "border-teal-500",
            },
            {
                title: "Procedures Recorded",
                value: myTreatments,
                subtext: "Clinical treatments & notes",
                icon: "pi pi-heart",
                iconColor: "text-blue-600 bg-blue-50 border-blue-100 dark:text-blue-400 dark:bg-blue-950/50 dark:border-blue-800/60",
                href: route("treatments.index"),
                accent: "border-blue-500",
            },
            {
                title: "Pending Consultations",
                value: myPending,
                subtext: "Awaiting clinic confirmation",
                icon: "pi pi-clock",
                iconColor: "text-amber-600 bg-amber-50 border-amber-100 dark:text-amber-400 dark:bg-amber-950/50 dark:border-amber-800/60",
                href: route("appointments.index"),
                accent: "border-amber-500",
            },
            {
                title: "Active Patient Records",
                value: props.stats.total_patients,
                subtext: "Clinical medical profiles",
                icon: "pi pi-users",
                iconColor: "text-purple-600 bg-purple-50 border-purple-100 dark:text-purple-400 dark:bg-purple-950/50 dark:border-purple-800/60",
                href: route("patients.index"),
                accent: "border-purple-500",
            },
        ];
    }

    if (userRole.value === "secretary") {
        return [
            {
                title: "Pending Requests",
                value: props.stats.pending_count,
                subtext:
                    props.stats.pending_count > 0
                        ? "Requires coordinator review"
                        : "All requests triaged",
                icon: "pi pi-bell",
                iconColor: "text-amber-600 bg-amber-50 border-amber-100",
                href: route("appointments.index"),
                accent: "border-amber-500",
                alert: props.stats.pending_count > 0,
            },
            {
                title: "Scheduled Visits Today",
                value: props.stats.today_appointments_count,
                subtext: `${props.stats.confirmed_today_count} confirmed visits`,
                icon: "pi pi-calendar",
                iconColor: "text-teal-600 bg-teal-50 border-teal-100",
                href: route("appointments.index"),
                accent: "border-teal-500",
            },
            {
                title: "Upcoming 7-Day Horizon",
                value:
                    props.stats.upcoming_week_count ??
                    props.stats.today_appointments_count,
                subtext: "Visits scheduled this week",
                icon: "pi pi-clock",
                iconColor: "text-blue-600 bg-blue-50 border-blue-100",
                href: route("appointments.index"),
                accent: "border-blue-500",
            },
            {
                title: "Registered Patients",
                value: props.stats.total_patients,
                subtext: "Active patient directory",
                icon: "pi pi-users",
                iconColor: "text-purple-600 bg-purple-50 border-purple-100",
                href: route("patients.index"),
                accent: "border-purple-500",
            },
        ];
    }

    if (userRole.value === "staff") {
        return [
            {
                title: "Today's Collections",
                value: formatCurrency(props.stats.today_collected),
                subtext: "Front desk settlements today",
                icon: "pi pi-wallet",
                iconColor: "text-emerald-600 bg-emerald-50 border-emerald-100",
                href: route("payments.index"),
                accent: "border-emerald-500",
            },
            {
                title: "Receivables Balance",
                value: formatCurrency(props.stats.total_receivables),
                subtext: `${props.stats.unpaid_invoices_count || 0} unpaid or partial balance(s)`,
                icon: "pi pi-file-edit",
                iconColor: "text-amber-600 bg-amber-50 border-amber-100",
                href: route("billing.index"),
                accent: "border-amber-500",
            },
            {
                title: "Today's Patient Visits",
                value: props.stats.today_appointments_count,
                subtext: `${props.stats.confirmed_today_count} confirmed check-in(s)`,
                icon: "pi pi-calendar-clock",
                iconColor: "text-teal-600 bg-teal-50 border-teal-100 dark:text-teal-400 dark:bg-teal-950/50 dark:border-teal-800/60",
                href: route("appointments.index"),
                accent: "border-teal-500",
            },
            {
                title: "Registered Patients",
                value: props.stats.total_patients,
                subtext: "Total active files",
                icon: "pi pi-users",
                iconColor: "text-purple-600 bg-purple-50 border-purple-100",
                href: route("patients.index"),
                accent: "border-purple-500",
            },
        ];
    }

    // Admin default
    return [
        {
            title: "Total Clinic Patients",
            value: props.stats.total_patients,
            subtext: "Active dental records",
            icon: "pi pi-users",
            iconColor: "text-teal-600 bg-teal-50 border-teal-100",
            href: route("patients.index"),
            accent: "border-teal-500",
        },
        {
            title: "Today's Appointments",
            value: props.stats.today_appointments_count,
            subtext: `${props.stats.confirmed_today_count} confirmed • ${props.stats.pending_count} pending`,
            icon: "pi pi-calendar",
            iconColor: "text-blue-600 bg-blue-50 border-blue-100",
            href: route("appointments.index"),
            accent: "border-blue-500",
        },
        {
            title: "Total Payments Collected",
            value: formatCurrency(props.stats.total_collected),
            subtext: `${formatCurrency(props.stats.today_collected)} collected today`,
            icon: "pi pi-credit-card",
            iconColor: "text-emerald-600 bg-emerald-50 border-emerald-100",
            href: route("payments.index"),
            accent: "border-emerald-500",
        },
        {
            title: "Outstanding Receivables",
            value: formatCurrency(props.stats.total_receivables),
            subtext: `${props.stats.unpaid_invoices_count || 0} unpaid / partial invoice(s)`,
            icon: "pi pi-wallet",
            iconColor: "text-amber-600 bg-amber-50 border-amber-100",
            href: route("billing.index"),
            accent: "border-amber-500",
        },
    ];
});

const quickShortcuts = computed(() => {
    if (userRole.value === "dentist") {
        return [
            {
                title: "Record Treatment",
                desc: "Document clinical procedure, tooth # & notes",
                icon: "pi pi-heart",
                iconClass: "bg-teal-50 text-teal-700",
                href: route("treatments.create"),
                badge: "Primary Action",
            },
            {
                title: "Dental Records Archive",
                desc: "Search patient clinical charts & histories",
                icon: "pi pi-file",
                iconClass: "bg-blue-50 text-blue-700",
                href: route("treatments.index"),
            },
            {
                title: "Master Schedule",
                desc: "Review daily and weekly chair availability",
                icon: "pi pi-calendar",
                iconClass: "bg-purple-50 text-purple-700",
                href: route("appointments.index"),
            },
            {
                title: "Invoices & Billing",
                desc: "View patient treatment invoices & balance",
                icon: "pi pi-wallet",
                iconClass: "bg-emerald-50 text-emerald-700",
                href: route("billing.index"),
            },
        ];
    }

    if (userRole.value === "secretary") {
        return [
            {
                title: "Book New Appointment",
                desc: "Schedule patient visit with doctor & operatory",
                icon: "pi pi-calendar-plus",
                iconClass: "bg-teal-50 text-teal-700",
                href: route("appointments.create"),
                badge: "New Visit",
            },
            {
                title: "Triage Booking Requests",
                desc: "Review & confirm pending online reservations",
                icon: "pi pi-bell",
                iconClass: "bg-amber-50 text-amber-700",
                href: route("appointments.index"),
                badge:
                    props.stats.pending_count > 0
                        ? `${props.stats.pending_count} Pending`
                        : null,
                badgeAlert: props.stats.pending_count > 0,
            },
            {
                title: "Patient Intake Registration",
                desc: "Register incoming walk-in or new patient profile",
                icon: "pi pi-user-plus",
                iconClass: "bg-blue-50 text-blue-700",
                href: route("patients.create"),
            },
            {
                title: "Service Catalog & Pricing",
                desc: "Browse dental treatments & fee guidelines",
                icon: "pi pi-list",
                iconClass: "bg-purple-50 text-purple-700",
                href: route("services.index"),
            },
        ];
    }

    if (userRole.value === "staff") {
        return [
            {
                title: "New Patient Registration",
                desc: "Complete patient intake & medical history",
                icon: "pi pi-user-plus",
                iconClass: "bg-teal-50 text-teal-700",
                href: route("patients.create"),
                badge: "Intake",
            },
            {
                title: "Create Billing Invoice",
                desc: "Issue itemized dental bill & payment terms",
                icon: "pi pi-file-edit",
                iconClass: "bg-emerald-50 text-emerald-700",
                href: route("billing.create"),
            },
            {
                title: "Cashier & Receipts",
                desc: "Process GCash, Cash, Card & print vouchers",
                icon: "pi pi-credit-card",
                iconClass: "bg-blue-50 text-blue-700",
                href: route("payments.index"),
            },
            {
                title: "Today's Appointments",
                desc: "Check-in patients arriving for consultations",
                icon: "pi pi-calendar-clock",
                iconClass: "bg-purple-50 text-purple-700",
                href: route("appointments.index"),
            },
        ];
    }

    // Admin
    return [
        {
            title: "Financial & Clinic Reports",
            desc: "Comprehensive analytics & revenue exports",
            icon: "pi pi-chart-bar",
            iconClass: "bg-teal-50 text-teal-700",
            href: route("reports.index"),
        },
        {
            title: "Staff & User Access",
            desc: "Manage doctors, secretary, staff & permissions",
            icon: "pi pi-users",
            iconClass: "bg-blue-50 text-blue-700",
            href: route("users.index"),
        },
        {
            title: "Security & Audit Trail",
            desc: "Inspect system actions, logins & operations",
            icon: "pi pi-shield",
            iconClass: "bg-amber-50 text-amber-700",
            href: route("audit-logs.index"),
        },
        {
            title: "Database Backup",
            desc: "Automated snapshots & disaster recovery tools",
            icon: "pi pi-database",
            iconClass: "bg-purple-50 text-purple-700",
            href: route("backup.index"),
        },
    ];
});

const getActivityColorClasses = (color) => {
    switch (color) {
        case "teal":
            return "bg-teal-50 text-teal-600 border border-teal-100";
        case "blue":
            return "bg-blue-50 text-blue-600 border border-blue-100";
        case "green":
            return "bg-emerald-50 text-emerald-600 border border-emerald-100";
        case "amber":
            return "bg-amber-50 text-amber-600 border border-amber-100";
        case "purple":
            return "bg-purple-50 text-purple-600 border border-purple-100";
        default:
            return "bg-slate-50 text-slate-600 border border-slate-200";
    }
};

const getAvatarClass = (index) => {
    const colors = [
        "bg-teal-100 text-teal-800 border-teal-200",
        "bg-blue-100 text-blue-800 border-blue-200",
        "bg-purple-100 text-purple-800 border-purple-200",
        "bg-emerald-100 text-emerald-800 border-emerald-200",
        "bg-indigo-100 text-indigo-800 border-indigo-200",
        "bg-amber-100 text-amber-800 border-amber-200",
    ];
    return colors[index % colors.length];
};

const formatTime = (timeStr) => {
    if (!timeStr) return "";
    try {
        const [hours, minutes] = timeStr.split(":");
        const d = new Date();
        d.setHours(parseInt(hours, 10));
        d.setMinutes(parseInt(minutes, 10));
        return d.toLocaleTimeString([], {
            hour: "numeric",
            minute: "2-digit",
            hour12: true,
        });
    } catch (e) {
        return timeStr;
    }
};

const formatDate = (dateStr) => {
    if (!dateStr) return "";
    try {
        return new Date(dateStr).toLocaleDateString("en-PH", {
            month: "short",
            day: "numeric",
        });
    } catch (e) {
        return dateStr;
    }
};

const formatCurrency = (val) => {
    const num = parseFloat(val) || 0;
    return (
        "₱" +
        num.toLocaleString("en-PH", {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2,
        })
    );
};
</script>

<template>
    <Head title="Clinical Dashboard - Vitality Smile" />

    <AuthenticatedLayout>
        <!-- Header Banner -->
        <template #header>
            <div
                class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between"
            >
                <div>
                    <!-- Role Pill & Date Strip -->
                    <div class="flex flex-wrap items-center gap-2">
                        <span
                            :class="[
                                'inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold border tracking-wide uppercase',
                                roleMeta.badgeClass,
                            ]"
                        >
                            <i :class="roleMeta.badgeIcon" class="text-xs"></i>
                            {{ roleMeta.badge }}
                        </span>

                        <span
                            class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-medium text-slate-600 bg-white border border-slate-200 shadow-2xs"
                        >
                            <i class="pi pi-calendar text-xs text-teal-600"></i>
                            {{ currentDateFormatted }}
                        </span>
                    </div>

                    <!-- Welcome Heading -->
                    <h1
                        class="mt-2 text-2xl font-black text-slate-900 tracking-tight sm:text-3xl"
                    >
                        {{ getGreeting() }}, {{ userName }}
                    </h1>
                    <p
                        class="mt-1 text-sm text-slate-500 max-w-2xl leading-relaxed"
                    >
                        {{ roleMeta.subtitle }}
                    </p>
                </div>

                <!-- Role-Tailored Primary Quick Actions -->
                <div class="flex flex-wrap items-center gap-2.5">
                    <!-- Dentist Actions -->
                    <template v-if="userRole === 'dentist'">
                        <Link
                            :href="route('treatments.create')"
                            class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl font-bold text-sm text-white bg-gradient-to-r from-teal-600 to-emerald-600 hover:from-teal-700 hover:to-emerald-700 shadow-sm transition hover:shadow-md hover:-translate-y-0.5"
                        >
                            <i class="pi pi-plus text-xs font-bold"></i>
                            <span>Record Treatment</span>
                        </Link>
                        <Link
                            :href="route('appointments.index')"
                            class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl font-bold text-sm text-slate-700 bg-white hover:bg-slate-50 border border-slate-200 shadow-2xs transition hover:border-slate-300"
                        >
                            <i class="pi pi-calendar text-xs text-teal-600"></i>
                            <span>Master Schedule</span>
                        </Link>
                        <Link
                            :href="route('patients.create')"
                            class="inline-flex items-center gap-2 px-3 py-2.5 rounded-xl font-semibold text-sm text-slate-700 bg-white hover:bg-slate-50 border border-slate-200 shadow-2xs transition"
                            title="Add Patient"
                        >
                            <i
                                class="pi pi-user-plus text-xs text-teal-600"
                            ></i>
                            <span class="hidden sm:inline">Add Patient</span>
                        </Link>
                    </template>

                    <!-- Secretary Actions -->
                    <template v-else-if="userRole === 'secretary'">
                        <Link
                            :href="route('appointments.create')"
                            class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl font-bold text-sm text-white bg-gradient-to-r from-teal-600 to-cyan-600 hover:from-teal-700 hover:to-cyan-700 shadow-sm transition hover:shadow-md hover:-translate-y-0.5"
                        >
                            <i
                                class="pi pi-calendar-plus text-xs font-bold"
                            ></i>
                            <span>Book Appointment</span>
                        </Link>
                        <Link
                            :href="route('appointments.index')"
                            class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl font-bold text-sm text-slate-700 bg-white hover:bg-slate-50 border border-slate-200 shadow-2xs transition hover:border-slate-300"
                        >
                            <i class="pi pi-inbox text-xs text-amber-600"></i>
                            <span>Triage Requests</span>
                            <span
                                v-if="stats.pending_count > 0"
                                class="inline-flex items-center justify-center px-2 py-0.5 rounded-full text-xs font-extrabold bg-amber-500 text-white"
                            >
                                {{ stats.pending_count }}
                            </span>
                        </Link>
                        <Link
                            :href="route('patients.create')"
                            class="inline-flex items-center gap-2 px-3.5 py-2.5 rounded-xl font-semibold text-sm text-slate-700 bg-white hover:bg-slate-50 border border-slate-200 shadow-2xs transition"
                        >
                            <i
                                class="pi pi-user-plus text-xs text-teal-600"
                            ></i>
                            <span>Intake</span>
                        </Link>
                    </template>

                    <!-- Staff Actions -->
                    <template v-else-if="userRole === 'staff'">
                        <Link
                            :href="route('patients.create')"
                            class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl font-bold text-sm text-white bg-gradient-to-r from-teal-600 to-emerald-600 hover:from-teal-700 hover:to-emerald-700 shadow-sm transition hover:shadow-md hover:-translate-y-0.5"
                        >
                            <i class="pi pi-user-plus text-xs font-bold"></i>
                            <span>New Patient Intake</span>
                        </Link>
                        <Link
                            :href="route('billing.create')"
                            class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl font-bold text-sm text-slate-700 bg-white hover:bg-slate-50 border border-slate-200 shadow-2xs transition hover:border-slate-300"
                        >
                            <i
                                class="pi pi-file-edit text-xs text-emerald-600"
                            ></i>
                            <span>Issue Invoice</span>
                        </Link>
                        <Link
                            :href="route('payments.index')"
                            class="inline-flex items-center gap-2 px-3.5 py-2.5 rounded-xl font-semibold text-sm text-slate-700 bg-white hover:bg-slate-50 border border-slate-200 shadow-2xs transition"
                        >
                            <i
                                class="pi pi-credit-card text-xs text-blue-600"
                            ></i>
                            <span>Cashier</span>
                        </Link>
                    </template>

                    <!-- Admin Actions -->
                    <template v-else>
                        <Link
                            :href="route('reports.index')"
                            class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl font-bold text-sm text-white bg-gradient-to-r from-teal-600 to-blue-600 hover:from-teal-700 hover:to-blue-700 shadow-sm transition hover:shadow-md hover:-translate-y-0.5"
                        >
                            <i class="pi pi-chart-bar text-xs font-bold"></i>
                            <span>Clinic Reports</span>
                        </Link>
                        <Link
                            :href="route('users.index')"
                            class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl font-bold text-sm text-slate-700 bg-white hover:bg-slate-50 border border-slate-200 shadow-2xs transition hover:border-slate-300"
                        >
                            <i class="pi pi-users text-xs text-blue-600"></i>
                            <span>User Access</span>
                        </Link>
                        <Link
                            :href="route('audit-logs.index')"
                            class="inline-flex items-center gap-2 px-3.5 py-2.5 rounded-xl font-semibold text-sm text-slate-700 bg-white hover:bg-slate-50 border border-slate-200 shadow-2xs transition"
                        >
                            <i class="pi pi-shield text-xs text-purple-600"></i>
                            <span>Audit Log</span>
                        </Link>
                    </template>
                </div>
            </div>
        </template>

        <!-- Main Body -->
        <div class="py-6 sm:py-8 space-y-6">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 space-y-6">
                <!-- High Priority Alert: Pending Appointment Requests (Visible if pending count > 0) -->
                <div
                    v-if="stats.pending_count > 0"
                    class="relative overflow-hidden rounded-2xl border border-amber-300 bg-gradient-to-r from-amber-50 via-orange-50 to-amber-50 p-4 sm:p-5 shadow-xs"
                >
                    <div
                        class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4"
                    >
                        <div class="flex items-start sm:items-center gap-3.5">
                            <div
                                class="relative flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-amber-500 text-white shadow-xs"
                            >
                                <i class="pi pi-bell text-lg"></i>
                                <span
                                    class="absolute -top-1 -right-1 flex h-3.5 w-3.5"
                                >
                                    <span
                                        class="animate-ping absolute inline-flex h-full w-full rounded-full bg-amber-400 opacity-75"
                                    ></span>
                                    <span
                                        class="relative inline-flex rounded-full h-3.5 w-3.5 bg-amber-600"
                                    ></span>
                                </span>
                            </div>
                            <div>
                                <div class="flex items-center gap-2">
                                    <h2
                                        class="text-sm font-extrabold text-amber-950 uppercase tracking-wide"
                                    >
                                        Pending Appointments Requiring Action
                                    </h2>
                                    <span
                                        class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-bold bg-amber-200/80 text-amber-900 border border-amber-300"
                                    >
                                        {{ stats.pending_count }} waiting
                                    </span>
                                </div>
                                <p class="text-xs text-amber-800/90 mt-0.5">
                                    Patients have submitted booking requests
                                    that need verification, doctor assignment,
                                    or confirmation.
                                </p>
                            </div>
                        </div>

                        <div class="flex items-center gap-2 shrink-0">
                            <Link
                                :href="route('appointments.index')"
                                class="inline-flex items-center gap-2 px-4 py-2 rounded-xl text-xs font-extrabold text-white bg-amber-600 hover:bg-amber-700 shadow-xs transition hover:shadow-md"
                            >
                                <span>Triage in Appointments Hub</span>
                                <i class="pi pi-arrow-right text-[10px]"></i>
                            </Link>
                        </div>
                    </div>

                    <!-- Mini Preview Chips of Recent Pending Requests -->
                    <div
                        v-if="recentPending.length > 0"
                        class="mt-3.5 pt-3.5 border-t border-amber-200/70 flex flex-wrap items-center gap-2"
                    >
                        <span
                            class="text-[11px] font-bold text-amber-900 uppercase tracking-wider mr-1"
                        >
                            Latest Requests:
                        </span>
                        <div
                            v-for="pnd in recentPending.slice(0, 3)"
                            :key="pnd.id"
                            class="inline-flex items-center gap-2 px-2.5 py-1 rounded-lg bg-white/80 border border-amber-200 text-xs text-slate-800 shadow-2xs"
                        >
                            <span class="font-bold text-slate-900">
                                {{
                                    pnd.patient
                                        ? `${pnd.patient.first_name} ${pnd.patient.last_name}`
                                        : pnd.user
                                          ? pnd.user.name
                                          : "Patient"
                                }}
                            </span>
                            <span class="text-amber-800 font-medium">
                                {{ pnd.service }}
                            </span>
                            <span class="text-slate-400 text-[10px]">
                                • {{ formatDate(pnd.appointment_date) }}
                                {{ formatTime(pnd.appointment_time) }}
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Role-Tailored KPI Metric Cards (Grid of 4) -->
                <div
                    class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4"
                >
                    <Link
                        v-for="(kpi, idx) in kpis"
                        :key="idx"
                        :href="kpi.href"
                        class="group relative overflow-hidden rounded-2xl border border-slate-200/90 bg-white p-5 shadow-2xs transition-all duration-200 hover:-translate-y-1 hover:border-teal-300 hover:shadow-md"
                    >
                        <!-- Top Accent Line -->
                        <div
                            :class="[
                                'absolute top-0 inset-x-0 h-1 bg-gradient-to-r opacity-90 transition-opacity',
                                kpi.accent === 'border-teal-500'
                                    ? 'from-teal-500 to-cyan-500'
                                    : '',
                                kpi.accent === 'border-emerald-500'
                                    ? 'from-emerald-500 to-teal-500'
                                    : '',
                                kpi.accent === 'border-blue-500'
                                    ? 'from-blue-500 to-indigo-500'
                                    : '',
                                kpi.accent === 'border-amber-500'
                                    ? 'from-amber-500 to-orange-500'
                                    : '',
                                kpi.accent === 'border-purple-500'
                                    ? 'from-purple-500 to-pink-500'
                                    : '',
                            ]"
                        ></div>

                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <p
                                    class="text-xs font-bold uppercase tracking-wider text-slate-500"
                                >
                                    {{ kpi.title }}
                                </p>
                                <p
                                    class="mt-2 text-2xl font-black text-slate-900 sm:text-3xl tracking-tight truncate"
                                >
                                    {{ kpi.value }}
                                </p>
                                <p
                                    class="mt-1 text-xs text-slate-500 font-medium flex items-center gap-1.5 truncate"
                                >
                                    <span
                                        v-if="kpi.alert"
                                        class="h-2 w-2 rounded-full bg-amber-500 shrink-0 animate-ping"
                                    ></span>
                                    {{ kpi.subtext }}
                                </p>
                            </div>

                            <div
                                :class="[
                                    'flex h-12 w-12 shrink-0 items-center justify-center rounded-xl border text-xl transition group-hover:scale-105 shadow-2xs',
                                    kpi.iconColor,
                                ]"
                            >
                                <i :class="kpi.icon"></i>
                            </div>
                        </div>

                        <div
                            class="mt-4 flex items-center justify-between border-t border-slate-100 pt-3 text-xs font-bold text-teal-700 group-hover:text-teal-800"
                        >
                            <span>Manage & View</span>
                            <i
                                class="pi pi-arrow-right text-[10px] transition-transform group-hover:translate-x-1"
                            ></i>
                        </div>
                    </Link>
                </div>

                <!-- Primary Operational Grid: Two Columns -->
                <div class="grid grid-cols-1 gap-6 lg:grid-cols-12">
                    <!-- Left Column: Today's Clinical Schedule (7 Cols) -->
                    <div
                        class="lg:col-span-7 flex flex-col rounded-2xl border border-slate-200/90 bg-white shadow-2xs overflow-hidden"
                    >
                        <!-- Card Header -->
                        <div
                            class="flex items-center justify-between border-b border-slate-100 px-5 py-4 sm:px-6 bg-slate-50/50"
                        >
                            <div class="flex items-center gap-3">
                                <div
                                    class="flex h-9 w-9 items-center justify-center rounded-xl bg-teal-50 text-teal-700 border border-teal-100"
                                >
                                    <i class="pi pi-calendar text-base"></i>
                                </div>
                                <div>
                                    <h2
                                        class="text-base font-bold text-slate-900 flex items-center gap-2"
                                    >
                                        <span>Today's Clinical Schedule</span>
                                        <span
                                            class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-bold bg-teal-50 text-teal-700 border border-teal-200"
                                        >
                                            {{ todayAppointments.length }}
                                        </span>
                                    </h2>
                                    <p class="text-xs text-slate-500">
                                        Chair appointments booked for
                                        {{ currentDateFormatted }}
                                    </p>
                                </div>
                            </div>

                            <Link
                                :href="route('appointments.index')"
                                class="inline-flex items-center gap-1.5 text-xs font-bold text-teal-700 hover:text-teal-800 bg-teal-50/80 hover:bg-teal-100/80 px-3 py-1.5 rounded-lg border border-teal-200 transition"
                            >
                                <span>Master View</span>
                                <i class="pi pi-arrow-right text-[10px]"></i>
                            </Link>
                        </div>

                        <!-- Schedule Items List -->
                        <div class="divide-y divide-slate-100 flex-1">
                            <template v-if="todayAppointments.length > 0">
                                <div
                                    v-for="apt in todayAppointments"
                                    :key="apt.id"
                                    class="group flex flex-col sm:flex-row sm:items-center justify-between gap-3 p-4 sm:px-6 hover:bg-slate-50/80 transition"
                                >
                                    <div
                                        class="flex items-start sm:items-center gap-3.5 min-w-0"
                                    >
                                        <!-- Time Box -->
                                        <div
                                            class="flex flex-col items-center justify-center h-12 w-14 shrink-0 rounded-xl bg-teal-50 border border-teal-200/80 text-teal-800"
                                        >
                                            <i
                                                class="pi pi-clock text-[10px] text-teal-600 mb-0.5"
                                            ></i>
                                            <span
                                                class="text-[11px] font-black tracking-tight leading-none"
                                            >
                                                {{
                                                    formatTime(
                                                        apt.appointment_time,
                                                    )
                                                }}
                                            </span>
                                        </div>

                                        <!-- Patient & Procedure Detail -->
                                        <div class="min-w-0">
                                            <div
                                                class="flex items-center gap-2"
                                            >
                                                <p
                                                    class="font-bold text-slate-900 text-sm truncate group-hover:text-teal-700 transition"
                                                >
                                                    {{
                                                        apt.patient
                                                            ? `${apt.patient.first_name} ${apt.patient.last_name}`
                                                            : apt.user
                                                              ? apt.user.name
                                                              : "Walk-in Client"
                                                    }}
                                                </p>
                                                <span
                                                    v-if="
                                                        apt.patient?.patient_id
                                                    "
                                                    class="hidden sm:inline-block text-[10px] font-mono font-medium text-slate-500 bg-slate-100 px-1.5 py-0.5 rounded border border-slate-200"
                                                >
                                                    {{ apt.patient.patient_id }}
                                                </span>
                                            </div>

                                            <div
                                                class="mt-0.5 flex flex-wrap items-center gap-2 text-xs text-slate-500"
                                            >
                                                <span
                                                    class="font-medium text-slate-700"
                                                >
                                                    {{ apt.service }}
                                                </span>
                                                <span
                                                    v-if="apt.dentist"
                                                    class="inline-flex items-center gap-1 text-[11px] font-medium text-teal-700 bg-teal-50 px-2 py-0.5 rounded border border-teal-100"
                                                >
                                                    <i
                                                        class="pi pi-user text-[9px]"
                                                    ></i>
                                                    {{
                                                        apt.dentist.name.startsWith(
                                                            "Dr.",
                                                        )
                                                            ? apt.dentist.name
                                                            : "Dr. " +
                                                              apt.dentist.name
                                                    }}
                                                </span>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Status Pill & Quick Action -->
                                    <div
                                        class="flex items-center justify-between sm:justify-end gap-2.5 shrink-0 pl-17 sm:pl-0"
                                    >
                                        <span
                                            :class="[
                                                apt.status === 'confirmed'
                                                    ? 'bg-emerald-50 text-emerald-800 border-emerald-200'
                                                    : '',
                                                apt.status === 'pending'
                                                    ? 'bg-amber-50 text-amber-800 border-amber-200'
                                                    : '',
                                                apt.status === 'completed'
                                                    ? 'bg-blue-50 text-blue-800 border-blue-200'
                                                    : '',
                                                apt.status === 'cancelled'
                                                    ? 'bg-rose-50 text-rose-800 border-rose-200'
                                                    : '',
                                                'inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11px] font-bold uppercase tracking-wider border',
                                            ]"
                                        >
                                            <span
                                                :class="[
                                                    'h-1.5 w-1.5 rounded-full',
                                                    apt.status === 'confirmed'
                                                        ? 'bg-emerald-600 animate-pulse'
                                                        : '',
                                                    apt.status === 'pending'
                                                        ? 'bg-amber-600'
                                                        : '',
                                                    apt.status === 'completed'
                                                        ? 'bg-blue-600'
                                                        : '',
                                                    apt.status === 'cancelled'
                                                        ? 'bg-rose-600'
                                                        : '',
                                                ]"
                                            ></span>
                                            {{ apt.status }}
                                        </span>

                                        <Link
                                            :href="route('appointments.index')"
                                            class="inline-flex items-center justify-center h-8 w-8 rounded-lg text-slate-400 hover:text-teal-700 hover:bg-slate-100 transition"
                                            title="View in Appointments Hub"
                                        >
                                            <i
                                                class="pi pi-chevron-right text-xs"
                                            ></i>
                                        </Link>
                                    </div>
                                </div>
                            </template>

                            <!-- Empty Schedule -->
                            <div v-else class="p-8 text-center">
                                <div
                                    class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-teal-50 text-teal-600 border border-teal-100 mb-3"
                                >
                                    <i class="pi pi-calendar text-2xl"></i>
                                </div>
                                <h3 class="text-sm font-bold text-slate-900">
                                    No Appointments Scheduled for Today
                                </h3>
                                <p
                                    class="text-xs text-slate-500 mt-1 max-w-sm mx-auto"
                                >
                                    The clinical chair schedule is open today.
                                    You can book an appointment or triage
                                    pending requests.
                                </p>
                                <div
                                    class="mt-4 flex items-center justify-center gap-2"
                                >
                                    <Link
                                        :href="route('appointments.create')"
                                        class="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-lg text-xs font-bold text-white bg-teal-600 hover:bg-teal-700 shadow-2xs transition"
                                    >
                                        <i class="pi pi-plus text-[10px]"></i>
                                        <span>Book Appointment</span>
                                    </Link>
                                    <Link
                                        :href="route('appointments.index')"
                                        class="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-lg text-xs font-bold text-slate-700 bg-slate-100 hover:bg-slate-200 transition"
                                    >
                                        <span>View Calendar</span>
                                    </Link>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Right Column: Recent Patients Registry (5 Cols) -->
                    <div
                        class="lg:col-span-5 flex flex-col rounded-2xl border border-slate-200/90 bg-white shadow-2xs overflow-hidden"
                    >
                        <!-- Card Header -->
                        <div
                            class="flex items-center justify-between border-b border-slate-100 px-5 py-4 sm:px-6 bg-slate-50/50"
                        >
                            <div class="flex items-center gap-3">
                                <div
                                    class="flex h-9 w-9 items-center justify-center rounded-xl bg-purple-50 text-purple-700 border border-purple-100"
                                >
                                    <i class="pi pi-users text-base"></i>
                                </div>
                                <div>
                                    <h2
                                        class="text-base font-bold text-slate-900 flex items-center gap-2"
                                    >
                                        <span>Recent Patients</span>
                                        <span
                                            class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-bold bg-purple-50 text-purple-700 border border-purple-200"
                                        >
                                            {{ recentPatients.length }}
                                        </span>
                                    </h2>
                                    <p class="text-xs text-slate-500">
                                        Quick medical chart profiles
                                    </p>
                                </div>
                            </div>

                            <div class="flex items-center gap-2">
                                <Link
                                    v-if="
                                        [
                                            'admin',
                                            'secretary',
                                            'dentist',
                                            'staff',
                                        ].includes(userRole)
                                    "
                                    :href="route('patients.create')"
                                    class="inline-flex items-center gap-1 text-xs font-bold text-purple-700 hover:text-purple-800 bg-purple-50/80 hover:bg-purple-100 px-2.5 py-1 rounded-lg border border-purple-200 transition"
                                    title="Add New Patient"
                                >
                                    <i class="pi pi-plus text-[9px]"></i>
                                    <span>New</span>
                                </Link>
                                <Link
                                    :href="route('patients.index')"
                                    class="inline-flex items-center gap-1 text-xs font-bold text-slate-600 hover:text-slate-900 px-2 py-1 transition"
                                >
                                    <span>All</span>
                                    <i
                                        class="pi pi-chevron-right text-[9px]"
                                    ></i>
                                </Link>
                            </div>
                        </div>

                        <!-- Patient List -->
                        <div class="divide-y divide-slate-100 flex-1">
                            <template v-if="recentPatients.length > 0">
                                <Link
                                    v-for="(patient, index) in recentPatients"
                                    :key="patient.id"
                                    :href="route('patients.show', patient.id)"
                                    class="group flex items-center justify-between gap-3 p-3.5 sm:px-5 hover:bg-slate-50/80 transition"
                                >
                                    <div
                                        class="flex items-center gap-3 min-w-0"
                                    >
                                        <!-- Avatar with Initials -->
                                        <div
                                            :class="[
                                                'flex h-10 w-10 shrink-0 items-center justify-center rounded-xl text-xs font-black border transition group-hover:scale-105 shadow-2xs',
                                                getAvatarClass(index),
                                            ]"
                                        >
                                            {{ patient.initials }}
                                        </div>

                                        <div class="min-w-0">
                                            <div
                                                class="flex items-center gap-2"
                                            >
                                                <p
                                                    class="truncate font-bold text-slate-900 text-sm group-hover:text-teal-700 transition"
                                                >
                                                    {{ patient.name }}
                                                </p>
                                                <span
                                                    class="text-[10px] font-mono font-bold text-slate-500 bg-slate-100 px-1.5 py-0.5 rounded border border-slate-200 shrink-0"
                                                >
                                                    {{ patient.patient_id }}
                                                </span>
                                            </div>

                                            <p
                                                class="truncate text-xs text-slate-500 mt-0.5"
                                            >
                                                <span
                                                    class="font-medium text-slate-700"
                                                    >{{
                                                        patient.subtitle
                                                    }}</span
                                                >
                                                <span
                                                    v-if="patient.age"
                                                    class="text-slate-400 font-normal"
                                                    >•
                                                    {{ patient.age }} yrs</span
                                                >
                                                <span
                                                    v-if="patient.gender"
                                                    class="text-slate-400 font-normal"
                                                    >•
                                                    {{ patient.gender }}</span
                                                >
                                            </p>
                                        </div>
                                    </div>

                                    <div
                                        class="flex items-center gap-2 shrink-0"
                                    >
                                        <span
                                            v-if="patient.badge"
                                            :class="[
                                                patient.badge === 'Treated'
                                                    ? 'bg-emerald-50 text-emerald-800 border-emerald-200'
                                                    : '',
                                                patient.badge === 'Confirmed'
                                                    ? 'bg-blue-50 text-blue-800 border-blue-200'
                                                    : '',
                                                patient.badge === 'Pending'
                                                    ? 'bg-amber-50 text-amber-800 border-amber-200'
                                                    : '',
                                                patient.badge === 'New'
                                                    ? 'bg-teal-50 text-teal-800 border-teal-200'
                                                    : '',
                                                'hidden sm:inline-block px-2 py-0.5 rounded-md text-[10px] font-bold uppercase tracking-wider border',
                                            ]"
                                        >
                                            {{ patient.badge }}
                                        </span>

                                        <i
                                            class="pi pi-chevron-right text-xs text-slate-300 group-hover:text-teal-600 group-hover:translate-x-0.5 transition-all"
                                        ></i>
                                    </div>
                                </Link>
                            </template>

                            <div v-else class="p-8 text-center">
                                <div
                                    class="mx-auto flex h-12 w-12 items-center justify-center rounded-2xl bg-purple-50 text-purple-600 border border-purple-100 mb-2"
                                >
                                    <i class="pi pi-users text-xl"></i>
                                </div>
                                <h3 class="text-sm font-bold text-slate-900">
                                    No Patient Records Yet
                                </h3>
                                <p class="text-xs text-slate-500 mt-1">
                                    Start by registering your first dental
                                    patient.
                                </p>
                                <Link
                                    :href="route('patients.create')"
                                    class="mt-3 inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-bold text-white bg-teal-600 hover:bg-teal-700 transition"
                                >
                                    <i class="pi pi-plus text-[10px]"></i>
                                    <span>Register Patient</span>
                                </Link>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Operational Hub / Role-Specific Quick Shortcuts Strip -->
                <div
                    class="rounded-2xl border border-slate-200/90 bg-white p-5 sm:p-6 shadow-2xs"
                >
                    <div class="flex items-center justify-between mb-4">
                        <div class="flex items-center gap-2.5">
                            <div
                                class="flex h-8 w-8 items-center justify-center rounded-lg bg-teal-50 text-teal-700 border border-teal-100"
                            >
                                <i class="pi pi-th-large text-sm"></i>
                            </div>
                            <div>
                                <h2
                                    class="text-sm font-black uppercase tracking-wider text-slate-900"
                                >
                                    Operational Shortcuts & Tools
                                </h2>
                                <p class="text-xs text-slate-500">
                                    Quick tools tailored for your role:
                                    {{ roleMeta.badge }}
                                </p>
                            </div>
                        </div>
                    </div>

                    <div
                        class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4"
                    >
                        <Link
                            v-for="(tool, index) in quickShortcuts"
                            :key="index"
                            :href="tool.href"
                            class="group relative flex flex-col justify-between rounded-xl border border-slate-200/80 bg-slate-50/50 p-4 transition-all duration-200 hover:-translate-y-0.5 hover:bg-white hover:border-teal-300 hover:shadow-sm"
                        >
                            <div>
                                <div
                                    class="flex items-center justify-between mb-3"
                                >
                                    <div
                                        :class="[
                                            'flex h-10 w-10 items-center justify-center rounded-xl text-lg transition-transform group-hover:scale-105 shadow-2xs border border-white',
                                            tool.iconClass,
                                        ]"
                                    >
                                        <i :class="tool.icon"></i>
                                    </div>

                                    <span
                                        v-if="tool.badge"
                                        :class="[
                                            'px-2 py-0.5 rounded-full text-[10px] font-black uppercase tracking-wider',
                                            tool.badgeAlert
                                                ? 'bg-amber-100 text-amber-800 border border-amber-300'
                                                : 'bg-teal-100 text-teal-800 border border-teal-200',
                                        ]"
                                    >
                                        {{ tool.badge }}
                                    </span>
                                </div>

                                <h3
                                    class="font-bold text-sm text-slate-900 group-hover:text-teal-700 transition"
                                >
                                    {{ tool.title }}
                                </h3>
                                <p
                                    class="text-xs text-slate-500 mt-1 line-clamp-2 leading-relaxed"
                                >
                                    {{ tool.desc }}
                                </p>
                            </div>

                            <div
                                class="mt-4 flex items-center justify-end text-xs font-bold text-teal-700 pt-2 border-t border-slate-100"
                            >
                                <span class="group-hover:mr-1 transition-all"
                                    >Launch</span
                                >
                                <i class="pi pi-chevron-right text-[10px]"></i>
                            </div>
                        </Link>
                    </div>
                </div>

                <!-- Recent Clinical Activity Stream (Live Clinic Feed) -->
                <div
                    class="rounded-2xl border border-slate-200/90 bg-white shadow-2xs overflow-hidden"
                >
                    <div
                        class="flex items-center justify-between border-b border-slate-100 px-5 py-4 sm:px-6 bg-slate-50/50"
                    >
                        <div class="flex items-center gap-3">
                            <div
                                class="flex h-9 w-9 items-center justify-center rounded-xl bg-blue-50 text-blue-700 border border-blue-100"
                            >
                                <i class="pi pi-history text-base"></i>
                            </div>
                            <div>
                                <h2 class="text-base font-bold text-slate-900">
                                    Recent Clinic Activity Feed
                                </h2>
                                <p class="text-xs text-slate-500">
                                    Aggregated clinical stream • patient intake,
                                    procedures, billing & appointments
                                </p>
                            </div>
                        </div>

                        <div class="flex items-center gap-2">
                            <span
                                class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium text-emerald-800 bg-emerald-50 border border-emerald-200"
                            >
                                <span
                                    class="h-1.5 w-1.5 rounded-full bg-emerald-600 animate-pulse"
                                ></span>
                                Live Sync
                            </span>
                        </div>
                    </div>

                    <div class="divide-y divide-slate-100">
                        <template
                            v-if="
                                recentActivities && recentActivities.length > 0
                            "
                        >
                            <Link
                                v-for="activity in recentActivities"
                                :key="activity.id"
                                :href="activity.link || '#'"
                                class="group flex items-start gap-3.5 px-5 py-3.5 transition-colors hover:bg-slate-50 sm:items-center sm:gap-4 sm:px-6"
                            >
                                <div
                                    :class="[
                                        'flex h-10 w-10 shrink-0 items-center justify-center rounded-xl transition-transform group-hover:scale-105 shadow-2xs',
                                        getActivityColorClasses(activity.color),
                                    ]"
                                >
                                    <i
                                        :class="[activity.icon, 'text-base']"
                                    ></i>
                                </div>

                                <div class="min-w-0 flex-1">
                                    <p class="text-sm text-slate-800">
                                        <span
                                            class="font-extrabold text-slate-900"
                                        >
                                            {{ activity.actor }}
                                        </span>
                                        {{ " " + activity.action }}
                                    </p>

                                    <p
                                        class="mt-0.5 text-xs text-slate-500 truncate font-medium"
                                    >
                                        {{ activity.detail }}
                                    </p>
                                </div>

                                <div class="flex items-center gap-3 shrink-0">
                                    <span
                                        class="text-xs text-slate-400 font-medium whitespace-nowrap"
                                    >
                                        {{ activity.time_ago }}
                                    </span>
                                    <i
                                        class="pi pi-chevron-right text-xs text-slate-300 group-hover:text-teal-600 group-hover:translate-x-0.5 transition-all"
                                    ></i>
                                </div>
                            </Link>
                        </template>

                        <div v-else class="p-8 text-center text-slate-500">
                            <div
                                class="mx-auto flex h-12 w-12 items-center justify-center rounded-2xl bg-slate-100 text-slate-400 mb-2"
                            >
                                <i class="pi pi-history text-xl"></i>
                            </div>
                            <p class="text-sm font-medium text-slate-700">
                                No recent activities recorded yet.
                            </p>
                            <p class="text-xs text-slate-400 mt-0.5">
                                As operations are performed in the clinic, live
                                activity records will appear here.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
