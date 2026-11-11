<script setup>
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import Modal from "@/Components/Modal.vue";
import { Head, Link, router, useForm } from "@inertiajs/vue3";
import { computed, ref, watch } from "vue";

const props = defineProps({
    appointments: Object,
    filters: Object,
    metrics: Object,
    dentist: Object,
    patients: Array,
    services: Array,
    detailed_services: {
        type: Array,
        default: () => [],
    },
    currentRole: {
        type: String,
        default: "",
    },
});

// Filter state
const search = ref(props.filters?.search || "");
const status = ref(props.filters?.status || "all");
const dateFilter = ref(props.filters?.date_filter || "all");
const specificDate = ref(props.filters?.specific_date || "");
const sortBy = ref(props.filters?.sort_by || "booking_asc");

let searchDebounceTimeout = null;

const applyFilters = () => {
    router.get(
        route("appointments.index"),
        {
            search: search.value,
            status: status.value,
            date_filter: dateFilter.value,
            specific_date: specificDate.value,
            sort_by: sortBy.value,
        },
        {
            preserveState: true,
            preserveScroll: true,
            replace: true,
        },
    );
};

watch(search, () => {
    clearTimeout(searchDebounceTimeout);
    searchDebounceTimeout = setTimeout(() => {
        applyFilters();
    }, 350);
});

watch([status, dateFilter, specificDate, sortBy], () => {
    applyFilters();
});

const resetFilters = () => {
    search.value = "";
    status.value = "all";
    dateFilter.value = "all";
    specificDate.value = "";
    sortBy.value = "booking_asc";
    applyFilters();
};

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

const isToday = (dateStr) => {
    const today = new Date().toISOString().slice(0, 10);
    return dateStr === today;
};

const getLinkedInvoice = (apt) => {
    return apt?.invoice || apt?.treatment?.invoice || null;
};

const formatStatus = (status) => {
    if (!status) return "";
    return String(status).replace(/_/g, " ");
};

// Modals State
const showEditModal = ref(false);
const showCancelModal = ref(false);
const activeAppointment = ref(null);

const editDateError = ref("");
const editTimeError = ref("");

const editForm = useForm({
    service: "",
    appointment_date: "",
    appointment_time: "",
    status: "",
    notes: "",
});

const editDurationMinutes = computed(() => {
    if (!editForm.service) return 60;
    const srv = (props.detailed_services || []).find(
        (s) => s.name === editForm.service || s.title === editForm.service
    );
    if (srv?.duration_minutes) return Number(srv.duration_minutes);
    const lower = editForm.service.toLowerCase();
    if (lower.includes("orthodontic") || lower.includes("adjustment")) return 30;
    if (lower.includes("restoration") || lower.includes("filling") || lower.includes("extraction")) return 45;
    if (lower.includes("odontectomy") || lower.includes("root canal")) return 90;
    return 60;
});

const editCalculatedEndTime = computed(() => {
    if (!editForm.appointment_time || !editDurationMinutes.value) return "";
    try {
        const parts = editForm.appointment_time.slice(0, 5).split(":");
        const startH = parseInt(parts[0], 10);
        const startM = parseInt(parts[1], 10);
        if (isNaN(startH) || isNaN(startM)) return "";

        const totalMinutes = startH * 60 + startM + editDurationMinutes.value;
        const endH = Math.floor(totalMinutes / 60);
        const endM = totalMinutes % 60;

        return `${String(endH).padStart(2, "0")}:${String(endM).padStart(2, "0")}`;
    } catch {
        return "";
    }
});

const resolveAppointmentService = (serviceName) => {
    if (!serviceName) return "";
    const raw = String(serviceName).trim();
    if (!raw) return "";

    // 1. Direct match in props.services
    if (props.services && props.services.includes(raw)) {
        return raw;
    }

    // 2. Direct match in detailed_services name or title
    const exactModel = (props.detailed_services || []).find(
        (s) => s.name === raw || s.title === raw
    );
    if (exactModel) {
        return exactModel.name || exactModel.title;
    }

    // 3. Substring / Case-insensitive match in detailed_services
    const rawLower = raw.toLowerCase();
    const fuzzyModel = (props.detailed_services || []).find((s) => {
        const sName = (s.name || "").toLowerCase();
        const sTitle = (s.title || "").toLowerCase();
        return (
            sName.includes(rawLower) ||
            rawLower.includes(sName) ||
            sTitle.includes(rawLower) ||
            rawLower.includes(sTitle)
        );
    });
    if (fuzzyModel) {
        return fuzzyModel.name || fuzzyModel.title;
    }

    // 4. Substring / Case-insensitive match in props.services array
    const fuzzyString = (props.services || []).find((srv) => {
        const srvLower = srv.toLowerCase();
        return srvLower.includes(rawLower) || rawLower.includes(srvLower);
    });
    if (fuzzyString) {
        return fuzzyString;
    }

    // 5. Fallback: preserve original string
    return raw;
};

const availableEditServices = computed(() => {
    const list = [...(props.services || [])];

    (props.detailed_services || []).forEach((s) => {
        if (s.name && !list.includes(s.name)) list.push(s.name);
        if (s.title && !list.includes(s.title)) list.push(s.title);
    });

    if (editForm.service && !list.includes(editForm.service)) {
        list.unshift(editForm.service);
    }
    if (activeAppointment.value?.service && !list.includes(activeAppointment.value.service)) {
        list.unshift(activeAppointment.value.service);
    }

    return list;
});

const openEditModal = (apt) => {
    activeAppointment.value = apt;
    editDateError.value = "";
    editTimeError.value = "";
    editForm.service = resolveAppointmentService(apt.service);
    editForm.appointment_date = apt.appointment_date;
    editForm.appointment_time = apt.appointment_time
        ? apt.appointment_time.slice(0, 5)
        : "";
    editForm.status = apt.status;
    editForm.notes = apt.notes || "";
    showEditModal.value = true;
};

const validateEditSchedule = () => {
    editDateError.value = "";
    editTimeError.value = "";
    let valid = true;

    if (editForm.appointment_date) {
        const parts = editForm.appointment_date.split("-");
        if (parts.length === 3) {
            const year = parseInt(parts[0], 10);
            const month = parseInt(parts[1], 10) - 1;
            const day = parseInt(parts[2], 10);
            const selected = new Date(year, month, day);
            const dayOfWeek = selected.getDay();
            if (dayOfWeek === 0 || dayOfWeek === 6) {
                editDateError.value =
                    "Appointments are only available from Monday to Friday. Clinic is closed on weekends.";
                valid = false;
            }
        }
    }

    if (editForm.appointment_time) {
        const time = editForm.appointment_time.slice(0, 5);
        if (time < "09:00" || time > "18:00") {
            editTimeError.value =
                "Appointment time must be between 9:00 AM and 6:00 PM.";
            valid = false;
        }

        if (editCalculatedEndTime.value && editCalculatedEndTime.value > "18:00") {
            editTimeError.value = `This ${editDurationMinutes.value}-minute appointment starting at ${time} would end at ${editCalculatedEndTime.value}, exceeding our 6:00 PM closing time.`;
            valid = false;
        }
    }

    return valid;
};

const submitEdit = () => {
    if (!activeAppointment.value) return;
    if (!validateEditSchedule()) return;

    editForm.put(route("appointments.update", activeAppointment.value.id), {
        onSuccess: () => {
            showEditModal.value = false;
            activeAppointment.value = null;
        },
    });
};

const quickStatusChange = (apt, newStatus) => {
    router.patch(
        route("appointments.status.update", apt.id),
        {
            status: newStatus,
        },
        {
            preserveScroll: true,
        },
    );
};

const openCancelModal = (apt) => {
    activeAppointment.value = apt;
    showCancelModal.value = true;
};

const submitCancel = () => {
    if (!activeAppointment.value) return;
    router.delete(route("appointments.destroy", activeAppointment.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            showCancelModal.value = false;
            activeAppointment.value = null;
        },
    });
};
</script>

<template>
    <Head title="Appointments Management" />

    <AuthenticatedLayout>
        <template #header>
            <div
                class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between"
            >
                <div>
                    <div class="flex items-center gap-2">
                        <span
                            :class="[
                                'inline-flex items-center gap-1 rounded-md px-2 py-0.5 text-xs font-bold',
                                $page.props.auth.user.role === 'dentist'
                                    ? 'bg-blue-100 text-blue-800'
                                    : 'bg-teal-100 text-teal-800'
                            ]"
                        >
                            <i :class="['pi text-[10px]', $page.props.auth.user.role === 'dentist' ? 'pi-user' : ($page.props.auth.user.role === 'staff' ? 'pi-eye' : 'pi-inbox')]"></i>
                            {{ $page.props.auth.user.role === 'dentist' ? 'Dentist Chairside Desk' : ($page.props.auth.user.role === 'staff' ? 'Staff View Desk' : 'Secretary Appointment Desk') }}
                        </span>
                    </div>
                    <h2 class="text-xl font-bold text-gray-900 sm:text-2xl mt-1">
                        {{ $page.props.auth.user.role === 'dentist' ? 'My Daily Schedule & Chairside Appointments' : 'Clinic Appointments & Booking Requests' }}
                    </h2>
                    <p class="mt-0.5 text-sm text-gray-500">
                        {{ $page.props.auth.user.role === 'dentist' ? 'Track your confirmed patient treatments, allocated procedure windows, and medical alerts.' : 'Review incoming patient requests, verify dentist availability, and manage schedule bookings.' }}
                    </p>
                </div>

                <div v-if="$page.props.auth.user.role !== 'staff'" class="flex items-center gap-3">
                    <Link
                        :href="route('appointments.create')"
                        class="inline-flex items-center justify-center rounded-lg bg-teal-700 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-teal-800 focus:outline-none focus:ring-2 focus:ring-teal-500 focus:ring-offset-2"
                    >
                        <i class="pi pi-plus -ml-1 mr-2 text-sm"></i>
                        New Appointment
                    </Link>
                </div>
            </div>
        </template>

        <div class="py-6 sm:py-8">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 space-y-6">
                <!-- Flash Message -->
                <div
                    v-if="$page.props.flash && $page.props.flash.success"
                    class="flex items-center gap-3 rounded-lg border border-green-200 bg-green-50 p-4 text-green-800 shadow-sm"
                >
                    <i class="pi pi-check-circle text-xl text-green-600"></i>
                    <p class="text-sm font-medium">
                        {{ $page.props.flash.success }}
                    </p>
                </div>

                <div
                    v-if="$page.props.flash && $page.props.flash.error"
                    class="flex items-center gap-3 rounded-2xl border border-red-200 bg-red-50 p-4 text-red-800 shadow-sm"
                >
                    <i class="pi pi-exclamation-triangle text-xl text-red-600"></i>
                    <p class="text-sm font-medium">
                        {{ $page.props.flash.error }}
                    </p>
                </div>

                <!-- Metrics Overview -->
                <div
                    class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4"
                >
                    <!-- Today -->
                    <div
                        @click="dateFilter = 'today'"
                        class="cursor-pointer rounded-xl border border-gray-200 bg-white p-5 shadow-sm transition hover:border-teal-500 hover:shadow-md"
                        :class="
                            dateFilter === 'today' ? 'ring-2 ring-teal-500' : ''
                        "
                    >
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-500">
                                    Today's Appointments
                                </p>
                                <p
                                    class="mt-2 text-3xl font-bold text-gray-900"
                                >
                                    {{ metrics.total_today }}
                                </p>
                            </div>
                            <div
                                class="flex h-12 w-12 items-center justify-center rounded-lg bg-teal-50 text-teal-700"
                            >
                                <i class="pi pi-calendar text-2xl"></i>
                            </div>
                        </div>
                    </div>

                    <!-- Pending Approval -->
                    <div
                        @click="status = 'pending'"
                        class="cursor-pointer rounded-xl border border-gray-200 bg-white p-5 shadow-sm transition hover:border-yellow-500 hover:shadow-md"
                        :class="
                            status === 'pending' ? 'ring-2 ring-yellow-500' : ''
                        "
                    >
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-500">
                                    Pending Requests
                                </p>
                                <p
                                    class="mt-2 text-3xl font-bold text-yellow-600"
                                >
                                    {{ metrics.pending }}
                                </p>
                            </div>
                            <div
                                class="flex h-12 w-12 items-center justify-center rounded-lg bg-yellow-50 text-yellow-600"
                            >
                                <i class="pi pi-clock text-2xl"></i>
                            </div>
                        </div>
                    </div>

                    <!-- Confirmed -->
                    <div
                        @click="status = 'confirmed'"
                        class="cursor-pointer rounded-xl border border-gray-200 bg-white p-5 shadow-sm transition hover:border-blue-500 hover:shadow-md"
                        :class="
                            status === 'confirmed' ? 'ring-2 ring-blue-500' : ''
                        "
                    >
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-500">
                                    Confirmed
                                </p>
                                <p
                                    class="mt-2 text-3xl font-bold text-blue-600"
                                >
                                    {{ metrics.confirmed }}
                                </p>
                            </div>
                            <div
                                class="flex h-12 w-12 items-center justify-center rounded-lg bg-blue-50 text-blue-600"
                            >
                                <i class="pi pi-check text-2xl"></i>
                            </div>
                        </div>
                    </div>

                    <!-- Completed -->
                    <div
                        @click="status = 'completed'"
                        class="cursor-pointer rounded-xl border border-gray-200 bg-white p-5 shadow-sm transition hover:border-green-500 hover:shadow-md"
                        :class="
                            status === 'completed'
                                ? 'ring-2 ring-green-500'
                                : ''
                        "
                    >
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-500">
                                    Completed
                                </p>
                                <p
                                    class="mt-2 text-3xl font-bold text-green-600"
                                >
                                    {{ metrics.completed }}
                                </p>
                            </div>
                            <div
                                class="flex h-12 w-12 items-center justify-center rounded-lg bg-green-50 text-green-600"
                            >
                                <i class="pi pi-check-circle text-2xl"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Filters & Search Toolbar -->
                <div
                    class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm sm:p-5"
                >
                    <div
                        class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-5"
                    >
                        <!-- Search -->
                        <div class="lg:col-span-2 relative">
                            <i
                                class="pi pi-search absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"
                            ></i>
                            <input
                                v-model="search"
                                type="text"
                                placeholder="Search patient, service, or notes..."
                                class="w-full rounded-lg border-gray-300 pl-9 pr-4 text-sm shadow-sm focus:border-teal-500 focus:ring-teal-500"
                            />
                        </div>

                        <!-- Status Filter -->
                        <div>
                            <select
                                v-model="status"
                                class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-teal-500 focus:ring-teal-500"
                            >
                                <option value="all">All Statuses</option>
                                <option value="pending">Pending</option>
                                <option value="confirmed">Confirmed</option>
                                <option value="completed">Completed</option>
                                <option value="cancelled">Cancelled</option>
                            </select>
                        </div>

                        <!-- Date Filter -->
                        <div>
                            <select
                                v-model="dateFilter"
                                class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-teal-500 focus:ring-teal-500"
                            >
                                <option value="all">All Dates</option>
                                <option value="today">Today</option>
                                <option value="tomorrow">Tomorrow</option>
                                <option value="this_week">This Week</option>
                                <option value="upcoming">All Upcoming</option>
                                <option value="past">Past Appointments</option>
                                <option value="custom">
                                    Pick Specific Date
                                </option>
                            </select>
                        </div>

                        <!-- Sort Order -->
                        <div>
                            <select
                                v-model="sortBy"
                                class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-teal-500 focus:ring-teal-500 font-medium text-teal-900 bg-teal-50/40"
                                title="Appointment list sorting order"
                            >
                                <option value="booking_asc">First Booked Above (Queue)</option>
                                <option value="schedule_asc">Schedule: Earliest Date</option>
                                <option value="schedule_desc">Schedule: Latest Date</option>
                                <option value="booking_desc">Recent Booked Above</option>
                            </select>
                        </div>
                    </div>

                    <!-- Custom Date Picker if custom selected -->
                    <div
                        v-if="dateFilter === 'custom'"
                        class="mt-3 flex items-center gap-3"
                    >
                        <label class="text-xs font-medium text-gray-600"
                            >Select Date:</label
                        >
                        <input
                            v-model="specificDate"
                            type="date"
                            class="rounded-lg border-gray-300 text-sm shadow-sm focus:border-teal-500 focus:ring-teal-500"
                        />
                    </div>

                    <!-- Active Filter Clearer -->
                    <div
                        v-if="
                            search || status !== 'all' || dateFilter !== 'all' || sortBy !== 'booking_asc'
                        "
                        class="mt-3 flex items-center justify-between border-t border-gray-100 pt-3"
                    >
                        <span class="text-xs text-gray-500">
                            Active filters applied
                        </span>
                        <button
                            @click="resetFilters"
                            class="inline-flex items-center gap-1 text-xs font-semibold text-teal-700 hover:text-teal-900"
                        >
                            <i class="pi pi-filter-slash text-xs"></i> Reset
                            Filters
                        </button>
                    </div>
                </div>

                <!-- Appointments Table -->
                <div
                    class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm"
                >
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th
                                        scope="col"
                                        class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider text-gray-500"
                                    >
                                        Patient Details
                                    </th>
                                    <th
                                        scope="col"
                                        class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider text-gray-500"
                                    >
                                        Service
                                    </th>
                                    <th
                                        scope="col"
                                        class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider text-gray-500"
                                    >
                                        Schedule & Booking
                                    </th>
                                    <th
                                        scope="col"
                                        class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider text-gray-500"
                                    >
                                        Assigned Dentist
                                    </th>
                                    <th
                                        scope="col"
                                        class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider text-gray-500"
                                    >
                                        Status & Workflow
                                    </th>
                                    <th
                                        scope="col"
                                        class="px-6 py-4 text-right text-xs font-semibold uppercase tracking-wider text-gray-500"
                                    >
                                        Actions
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 bg-white">
                                <tr
                                    v-for="appointment in appointments.data"
                                    :key="appointment.id"
                                    class="hover:bg-gray-50 transition-colors"
                                >
                                    <!-- Patient Details -->
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center">
                                            <div
                                                class="h-10 w-10 shrink-0 rounded-full bg-teal-100 text-teal-700 flex items-center justify-center font-bold text-sm"
                                            >
                                                <template
                                                    v-if="appointment.patient"
                                                >
                                                    {{
                                                        appointment.patient.first_name?.charAt(
                                                            0,
                                                        )
                                                    }}{{
                                                        appointment.patient.last_name?.charAt(
                                                            0,
                                                        )
                                                    }}
                                                </template>
                                                <template
                                                    v-else-if="appointment.user"
                                                >
                                                    {{
                                                        appointment.user.name?.charAt(
                                                            0,
                                                        )
                                                    }}
                                                </template>
                                                <template v-else>?</template>
                                            </div>
                                            <div class="ml-3">
                                                <div
                                                    class="text-sm font-semibold text-gray-900"
                                                >
                                                    <Link
                                                        v-if="
                                                            appointment.patient
                                                        "
                                                        :href="
                                                            route(
                                                                'patients.show',
                                                                appointment
                                                                    .patient.id,
                                                            )
                                                        "
                                                        class="hover:text-teal-700 hover:underline"
                                                    >
                                                        {{
                                                            appointment.patient
                                                                .first_name
                                                        }}
                                                        {{
                                                            appointment.patient
                                                                .last_name
                                                        }}
                                                    </Link>
                                                    <span
                                                        v-else-if="
                                                            appointment.user
                                                        "
                                                    >
                                                        {{
                                                            appointment.user
                                                                .name
                                                        }}
                                                    </span>
                                                    <span
                                                        v-else
                                                        class="text-gray-400"
                                                        >Walk-in Client</span
                                                    >
                                                </div>
                                                <div
                                                    class="text-xs text-gray-500 flex flex-wrap items-center gap-2 mt-0.5"
                                                >
                                                    <span
                                                        v-if="
                                                            appointment.patient
                                                        "
                                                    >
                                                        ID:
                                                        {{
                                                            appointment.patient
                                                                .patient_id
                                                        }}
                                                    </span>
                                                    <span
                                                        v-if="
                                                            appointment.patient
                                                                ?.medical_history
                                                                ?.critical_alerts &&
                                                            appointment.patient
                                                                .medical_history
                                                                .critical_alerts
                                                                .length > 0
                                                        "
                                                        class="inline-flex items-center gap-1 rounded bg-red-50 px-1.5 py-0.5 text-[10px] font-bold text-red-700 border border-red-200"
                                                        :title="
                                                            appointment.patient.medical_history.critical_alerts
                                                                .map(
                                                                    (a) =>
                                                                        a.label,
                                                                )
                                                                .join(', ')
                                                        "
                                                    >
                                                        <i
                                                            class="pi pi-exclamation-triangle text-[9px]"
                                                        ></i>
                                                        Alert
                                                    </span>
                                                    <span
                                                        v-if="
                                                            appointment.patient
                                                                ?.contact_number
                                                        "
                                                    >
                                                        •
                                                        {{
                                                            appointment.patient
                                                                .contact_number
                                                        }}
                                                    </span>
                                                    <span
                                                        v-else-if="
                                                            appointment.user
                                                                ?.email
                                                        "
                                                    >
                                                        •
                                                        {{
                                                            appointment.user
                                                                .email
                                                        }}
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                    </td>

                                    <!-- Service & Notes -->
                                    <td class="px-6 py-4">
                                        <div
                                            class="text-sm font-medium text-gray-900 max-w-xs truncate"
                                            :title="appointment.service"
                                        >
                                            {{ appointment.service }}
                                        </div>
                                        <div
                                            v-if="appointment.notes"
                                            class="text-xs text-gray-500 mt-1 max-w-xs truncate"
                                            :title="appointment.notes"
                                        >
                                            <i
                                                class="pi pi-comment mr-1 text-gray-400"
                                            ></i
                                            >{{ appointment.notes }}
                                        </div>
                                    </td>

                                    <!-- Schedule & Booking -->
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center gap-2">
                                            <span
                                                class="text-sm font-medium text-gray-900"
                                            >
                                                {{
                                                    formatDate(
                                                        appointment.appointment_date,
                                                    )
                                                }}
                                            </span>
                                            <span
                                                v-if="
                                                    isToday(
                                                        appointment.appointment_date,
                                                    )
                                                "
                                                class="rounded-full bg-teal-100 px-2 py-0.5 text-[10px] font-bold text-teal-800"
                                            >
                                                Today
                                            </span>
                                        </div>
                                        <div
                                            class="text-xs text-teal-800 font-semibold mt-1 flex items-center gap-1.5"
                                        >
                                            <i
                                                class="pi pi-clock text-[10px] text-teal-600"
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
                                            <span
                                                class="rounded-md bg-teal-50 border border-teal-200 px-1.5 py-0.5 text-[10px] text-teal-700 font-normal"
                                            >
                                                {{ appointment.duration_minutes || 60 }}m
                                            </span>
                                        </div>
                                        <div
                                            v-if="appointment.created_at"
                                            class="text-[11px] text-gray-500 mt-1 flex items-center gap-1.5"
                                            :title="'Patient booked on ' + formatBookingDate(appointment.created_at)"
                                        >
                                            <i class="pi pi-calendar-plus text-[10px] text-teal-600"></i>
                                            <span>Booked: {{ formatBookingDate(appointment.created_at) }}</span>
                                        </div>
                                    </td>

                                    <!-- Dentist -->
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center gap-2">
                                            <span
                                                class="text-sm font-medium text-gray-900"
                                                >{{
                                                    appointment.dentist?.name ||
                                                    "Dr. Valdez"
                                                }}</span
                                            >
                                        </div>
                                    </td>

                                    <!-- Status & Workflow -->
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="space-y-1.5">
                                            <!-- Main Appointment Status Badge -->
                                            <div>
                                                <span
                                                    :class="[
                                                        appointment.status === 'pending'
                                                            ? 'bg-yellow-100 text-yellow-800'
                                                            : '',
                                                        appointment.status ===
                                                        'confirmed'
                                                            ? 'bg-blue-100 text-blue-800'
                                                            : '',
                                                        appointment.status ===
                                                        'completed'
                                                            ? 'bg-green-100 text-green-800'
                                                            : '',
                                                        appointment.status ===
                                                        'cancelled'
                                                            ? 'bg-red-100 text-red-800'
                                                            : '',
                                                        'inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-semibold capitalize',
                                                    ]"
                                                >
                                                    <span
                                                        :class="[
                                                            appointment.status ===
                                                            'pending'
                                                                ? 'bg-yellow-500'
                                                                : '',
                                                            appointment.status ===
                                                            'confirmed'
                                                                ? 'bg-blue-500'
                                                                : '',
                                                            appointment.status ===
                                                            'completed'
                                                                ? 'bg-green-500'
                                                                : '',
                                                            appointment.status ===
                                                            'cancelled'
                                                                ? 'bg-red-500'
                                                                : '',
                                                            'h-1.5 w-1.5 rounded-full',
                                                        ]"
                                                    ></span>
                                                    {{ appointment.status }}
                                                </span>
                                            </div>

                                            <!-- Cross-Process Tracking Badges -->
                                            <div class="flex flex-col gap-1 items-start">
                                                <!-- Clinical Treatment Status -->
                                                <div v-if="appointment.treatment">
                                                    <Link
                                                        :href="route('treatments.index', { search: appointment.patient?.patient_id || appointment.patient?.last_name })"
                                                        class="inline-flex items-center gap-1 rounded bg-teal-50 px-2 py-0.5 text-[11px] font-medium text-teal-700 hover:bg-teal-100 border border-teal-200 transition"
                                                        title="View clinical treatment record"
                                                    >
                                                        <i class="pi pi-heart text-[9px] text-teal-600"></i>
                                                        Treated: {{ (appointment.treatment.status || 'completed').replace('_', ' ') }}
                                                    </Link>
                                                </div>
                                                <div v-else-if="appointment.status === 'confirmed'">
                                                    <span class="inline-flex items-center gap-1 rounded bg-blue-50 px-2 py-0.5 text-[11px] font-medium text-blue-700 border border-blue-200">
                                                        <i class="pi pi-clock text-[9px]"></i>
                                                        Awaiting Treatment
                                                    </span>
                                                </div>

                                                <!-- Billing / Invoice Status -->
                                                <div v-if="getLinkedInvoice(appointment)">
                                                    <Link
                                                        :href="route('billing.show', getLinkedInvoice(appointment).id)"
                                                        class="inline-flex items-center gap-1 rounded bg-emerald-50 px-2 py-0.5 text-[11px] font-semibold text-emerald-800 hover:bg-emerald-100 border border-emerald-200 transition"
                                                        title="View official billing statement"
                                                    >
                                                        <i class="pi pi-receipt text-[9px] text-emerald-600"></i>
                                                        {{ getLinkedInvoice(appointment).invoice_number }}
                                                        <span
                                                            class="capitalize text-[10px] px-1 py-0.2 rounded"
                                                            :class="getLinkedInvoice(appointment).status === 'paid' ? 'bg-emerald-200/60 text-emerald-900 font-bold' : 'text-amber-800 font-medium'"
                                                        >
                                                            ({{ formatStatus(getLinkedInvoice(appointment).status) }})
                                                        </span>
                                                    </Link>
                                                </div>
                                                <div v-else-if="appointment.treatment || appointment.status === 'completed'">
                                                    <span class="inline-flex items-center gap-1 rounded bg-amber-50 px-2 py-0.5 text-[11px] font-semibold text-amber-800 border border-amber-200">
                                                        <i class="pi pi-exclamation-circle text-[9px] text-amber-600"></i>
                                                        Unbilled
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                    </td>

                                    <!-- Actions -->
                                    <td
                                        class="px-6 py-4 whitespace-nowrap text-right text-sm"
                                    >
                                        <div
                                            class="flex items-center justify-end gap-1.5 flex-wrap"
                                        >
                                            <!-- Quick Confirm (Pending) -->
                                            <button
                                                v-if="
                                                    appointment.status ===
                                                    'pending' &&
                                                    $page.props.auth.user.role !== 'staff'
                                                "
                                                @click="
                                                    quickStatusChange(
                                                        appointment,
                                                        'confirmed',
                                                    )
                                                "
                                                class="rounded-lg bg-teal-50 px-2.5 py-1.5 text-xs font-semibold text-teal-700 hover:bg-teal-100 transition inline-flex items-center gap-1"
                                                title="Confirm appointment"
                                            >
                                                <i class="pi pi-check text-[10px]"></i>
                                                Confirm
                                            </button>

                                            <!-- Clinical Treatment Action (Confirmed or Completed without treatment) -->
                                            <Link
                                                v-if="
                                                    (appointment.status === 'confirmed' || appointment.status === 'completed') &&
                                                    !appointment.treatment &&
                                                    ['admin', 'dentist'].includes($page.props.auth.user.role)
                                                "
                                                :href="
                                                    route('treatments.create', {
                                                        appointment_id:
                                                            appointment.id,
                                                        patient_id:
                                                            appointment.patient_id,
                                                        procedure:
                                                            appointment.service,
                                                    })
                                                "
                                                class="rounded-lg bg-teal-50 px-2.5 py-1.5 text-xs font-semibold text-teal-700 hover:bg-teal-100 transition inline-flex items-center gap-1 border border-teal-200"
                                                title="Record Clinical Treatment"
                                            >
                                                <i
                                                    class="pi pi-heart text-[10px]"
                                                ></i>
                                                Treatment
                                            </Link>

                                            <!-- Billing Action: If unbilled and (treated or confirmed/completed), allow generating bill -->
                                            <Link
                                                v-if="!getLinkedInvoice(appointment) && (appointment.treatment || appointment.status === 'completed') && ['admin', 'secretary', 'dentist'].includes($page.props.auth.user.role)"
                                                :href="
                                                    route('billing.create', {
                                                        appointment_id:
                                                            appointment.id,
                                                        treatment_id:
                                                            appointment.treatment?.id,
                                                        patient_id:
                                                            appointment.patient_id,
                                                    })
                                                "
                                                class="rounded-lg bg-teal-700 px-2.5 py-1.5 text-xs font-semibold text-white hover:bg-teal-800 transition inline-flex items-center gap-1 shadow-sm"
                                                title="Generate Invoice / Bill"
                                            >
                                                <i class="pi pi-receipt text-[10px]"></i>
                                                Bill
                                            </Link>

                                            <!-- Invoice Action: If already billed, quick link to Invoice -->
                                            <Link
                                                v-if="getLinkedInvoice(appointment)"
                                                :href="route('billing.show', getLinkedInvoice(appointment).id)"
                                                class="rounded-lg bg-emerald-50 px-2.5 py-1.5 text-xs font-semibold text-emerald-800 hover:bg-emerald-100 border border-emerald-200 transition inline-flex items-center gap-1"
                                                title="View Official Invoice"
                                            >
                                                <i class="pi pi-receipt text-[10px]"></i>
                                                Invoice
                                            </Link>

                                            <!-- Edit/Reschedule -->
                                            <button
                                                v-if="$page.props.auth.user.role !== 'staff'"
                                                @click="
                                                    openEditModal(appointment)
                                                "
                                                class="rounded-lg p-1.5 text-gray-500 hover:bg-gray-100 hover:text-teal-700 transition"
                                                title="Edit / Reschedule"
                                            >
                                                <i class="pi pi-pencil"></i>
                                            </button>

                                            <!-- Cancel (if not already cancelled/completed) -->
                                            <button
                                                v-if="
                                                    appointment.status !==
                                                        'cancelled' &&
                                                    appointment.status !==
                                                        'completed' &&
                                                    $page.props.auth.user.role !== 'staff'
                                                "
                                                @click="
                                                    openCancelModal(appointment)
                                                "
                                                class="rounded-lg p-1.5 text-gray-500 hover:bg-red-50 hover:text-red-600 transition"
                                                title="Cancel appointment"
                                            >
                                                <i class="pi pi-times"></i>
                                            </button>

                                            <!-- View only indicator for staff if no other link is present -->
                                            <span
                                                v-if="$page.props.auth.user.role === 'staff' && !getLinkedInvoice(appointment)"
                                                class="text-xs text-gray-400 italic px-2 py-1"
                                            >
                                                View only
                                            </span>
                                        </div>
                                    </td>
                                </tr>

                                <!-- Empty State -->
                                <tr v-if="appointments.data.length === 0">
                                    <td
                                        colspan="6"
                                        class="px-6 py-12 text-center"
                                    >
                                        <div
                                            class="mx-auto flex h-14 w-14 items-center justify-center rounded-full bg-gray-100 text-gray-400"
                                        >
                                            <i
                                                class="pi pi-calendar text-2xl"
                                            ></i>
                                        </div>
                                        <h3
                                            class="mt-3 text-base font-semibold text-gray-900"
                                        >
                                            No appointments found
                                        </h3>
                                        <p class="mt-1 text-sm text-gray-500">
                                            Try adjusting your search criteria
                                            or date filters.
                                        </p>
                                        <div class="mt-4">
                                            <button
                                                @click="resetFilters"
                                                class="inline-flex items-center rounded-lg border border-gray-300 bg-white px-3 py-1.5 text-xs font-semibold text-gray-700 shadow-sm hover:bg-gray-50"
                                            >
                                                Clear all filters
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <div
                        v-if="
                            appointments.links && appointments.data.length > 0
                        "
                        class="flex items-center justify-between border-t border-gray-200 bg-white px-4 py-3 sm:px-6"
                    >
                        <div
                            class="hidden sm:flex sm:flex-1 sm:items-center sm:justify-between"
                        >
                            <div>
                                <p class="text-sm text-gray-700">
                                    Showing
                                    <span class="font-medium">{{
                                        appointments.from
                                    }}</span>
                                    to
                                    <span class="font-medium">{{
                                        appointments.to
                                    }}</span>
                                    of
                                    <span class="font-medium">{{
                                        appointments.total
                                    }}</span>
                                    results
                                </p>
                            </div>
                            <div>
                                <nav
                                    class="relative z-0 inline-flex -space-x-px rounded-md shadow-sm"
                                    aria-label="Pagination"
                                >
                                    <template
                                        v-for="(
                                            link, key
                                        ) in appointments.links"
                                        :key="key"
                                    >
                                        <Link
                                            v-if="link.url"
                                            :href="link.url"
                                            v-html="link.label"
                                            class="relative inline-flex items-center border px-4 py-2 text-sm font-medium"
                                            :class="[
                                                link.active
                                                    ? 'z-10 bg-teal-50 border-teal-500 text-teal-600'
                                                    : 'bg-white border-gray-300 text-gray-500 hover:bg-gray-50',
                                            ]"
                                        />
                                        <span
                                            v-else
                                            v-html="link.label"
                                            class="relative inline-flex items-center border border-gray-300 bg-gray-50 px-4 py-2 text-sm font-medium text-gray-500"
                                        ></span>
                                    </template>
                                </nav>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Edit / Reschedule Modal -->
        <Modal
            :show="showEditModal"
            @close="showEditModal = false"
            maxWidth="lg"
        >
            <div class="p-6">
                <div
                    class="flex items-center justify-between border-b border-gray-100 pb-4"
                >
                    <h3 class="text-lg font-bold text-gray-900">
                        Edit / Reschedule Appointment
                    </h3>
                    <button
                        @click="showEditModal = false"
                        class="text-gray-400 hover:text-gray-600"
                    >
                        <i class="pi pi-times"></i>
                    </button>
                </div>

                <form @submit.prevent="submitEdit" class="mt-5 space-y-4">
                    <!-- Service -->
                    <div>
                        <label
                            class="block text-xs font-semibold uppercase tracking-wider text-gray-700"
                            >Dental Service <span class="text-red-500">*</span></label
                        >
                        <select
                            v-model="editForm.service"
                            required
                            class="mt-1 block w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-teal-500 focus:ring-teal-500"
                        >
                            <option value="" disabled>Choose a dental service...</option>
                            <option
                                v-for="srv in availableEditServices"
                                :key="srv"
                                :value="srv"
                            >
                                {{ srv }}
                            </option>
                        </select>
                        <p v-if="editForm.errors.service" class="text-red-500 text-xs mt-1">
                            {{ editForm.errors.service }}
                        </p>
                    </div>

                    <!-- Date & Time -->
                    <div
                        class="rounded-lg bg-teal-50 border border-teal-200 p-3 text-xs text-teal-800"
                    >
                        <span class="font-bold">Clinic Hours:</span> Monday –
                        Friday, 9:00 AM – 6:00 PM (Closed on Weekends)
                    </div>

                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div>
                            <label
                                class="block text-xs font-semibold uppercase tracking-wider text-gray-700"
                                >Date <span class="text-red-500">*</span></label
                            >
                            <input
                                v-model="editForm.appointment_date"
                                type="date"
                                required
                                :class="[
                                    'mt-1 block w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-teal-500 focus:ring-teal-500',
                                    editDateError ||
                                    editForm.errors.appointment_date
                                        ? 'border-red-400 ring-1 ring-red-400'
                                        : '',
                                ]"
                            />
                            <p class="text-[11px] text-gray-500 mt-1">
                                Monday – Friday only
                            </p>
                            <div
                                v-if="editDateError"
                                class="text-red-600 text-xs mt-1 font-medium flex items-center gap-1"
                            >
                                <i class="pi pi-exclamation-circle text-xs"></i>
                                <span>{{ editDateError }}</span>
                            </div>
                            <div
                                v-if="editForm.errors.appointment_date"
                                class="text-red-600 text-xs mt-1 font-medium"
                            >
                                {{ editForm.errors.appointment_date }}
                            </div>
                        </div>
                        <div>
                            <label
                                class="block text-xs font-semibold uppercase tracking-wider text-gray-700"
                                >Time <span class="text-red-500">*</span></label
                            >
                            <input
                                v-model="editForm.appointment_time"
                                type="time"
                                min="09:00"
                                max="18:00"
                                required
                                :class="[
                                    'mt-1 block w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-teal-500 focus:ring-teal-500',
                                    editTimeError ||
                                    editForm.errors.appointment_time
                                        ? 'border-red-400 ring-1 ring-red-400'
                                        : '',
                                ]"
                            />
                            <p class="text-[11px] text-gray-500 mt-1">
                                9:00 AM – 6:00 PM only
                            </p>
                            <div
                                v-if="editTimeError"
                                class="text-red-600 text-xs mt-1 font-medium flex items-center gap-1"
                            >
                                <i class="pi pi-exclamation-circle text-xs"></i>
                                <span>{{ editTimeError }}</span>
                            </div>
                            <div
                                v-if="editForm.errors.appointment_time"
                                class="text-red-600 text-xs mt-1 font-medium"
                            >
                                {{ editForm.errors.appointment_time }}
                            </div>
                        </div>
                    </div>

                    <!-- Live Estimated Duration & Window Preview in Edit Modal -->
                    <div
                        v-if="editForm.service && editForm.appointment_time"
                        class="rounded-xl border border-teal-200 bg-teal-50/80 p-3 text-xs flex flex-col sm:flex-row sm:items-center sm:justify-between gap-1.5"
                    >
                        <div class="flex items-center gap-2">
                            <i class="pi pi-clock text-teal-700"></i>
                            <span class="font-semibold text-teal-950">
                                Estimated Duration: {{ editDurationMinutes }} minutes
                            </span>
                        </div>
                        <div class="font-extrabold text-teal-900">
                            Window: {{ formatTime(editForm.appointment_time) }} – {{ formatTime(editCalculatedEndTime) }}
                        </div>
                    </div>

                    <!-- Attending Dentist -->
                    <div>
                        <label
                            class="block text-xs font-semibold uppercase tracking-wider text-gray-700"
                            >Attending Dentist</label
                        >
                        <div
                            class="mt-1 flex items-center justify-between rounded-lg border border-gray-200 bg-gray-50 px-3 py-2 text-sm text-gray-800"
                        >
                            <span class="flex items-center gap-2 font-medium">
                                <span>👨‍⚕️</span> Dr. Valdez
                            </span>
                            <span
                                class="rounded-full bg-teal-100 px-2 py-0.5 text-[10px] font-bold text-teal-800"
                            >
                                Resident Dentist
                            </span>
                        </div>
                    </div>

                    <!-- Status -->
                    <div>
                        <label
                            class="block text-xs font-semibold uppercase tracking-wider text-gray-700"
                            >Status</label
                        >
                        <select
                            v-model="editForm.status"
                            required
                            class="mt-1 block w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-teal-500 focus:ring-teal-500"
                        >
                            <option value="pending">Pending</option>
                            <option value="confirmed">Confirmed</option>
                            <option value="completed">Completed</option>
                            <option value="cancelled">Cancelled</option>
                        </select>
                    </div>

                    <!-- Notes -->
                    <div>
                        <label
                            class="block text-xs font-semibold uppercase tracking-wider text-gray-700"
                            >Notes / Remarks</label
                        >
                        <textarea
                            v-model="editForm.notes"
                            rows="3"
                            class="mt-1 block w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-teal-500 focus:ring-teal-500"
                            placeholder="Add clinical or scheduling notes..."
                        ></textarea>
                    </div>

                    <!-- Actions -->
                    <div
                        class="mt-6 flex justify-end gap-3 border-t border-gray-100 pt-4"
                    >
                        <button
                            type="button"
                            @click="showEditModal = false"
                            class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 shadow-sm hover:bg-gray-50"
                        >
                            Cancel
                        </button>
                        <button
                            type="submit"
                            :disabled="editForm.processing"
                            class="rounded-lg bg-teal-700 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-teal-800 disabled:opacity-50"
                        >
                            Save Changes
                        </button>
                    </div>
                </form>
            </div>
        </Modal>

        <!-- Cancel Confirmation Modal -->
        <Modal
            :show="showCancelModal"
            @close="showCancelModal = false"
            maxWidth="md"
        >
            <div class="p-6">
                <div class="flex items-center gap-3">
                    <div
                        class="flex h-12 w-12 shrink-0 items-center justify-center rounded-full bg-red-100 text-red-600"
                    >
                        <i class="pi pi-exclamation-triangle text-xl"></i>
                    </div>
                    <div>
                        <h3 class="text-lg font-bold text-gray-900">
                            Cancel Appointment
                        </h3>
                        <p class="text-sm text-gray-500">
                            Are you sure you want to cancel this appointment for
                            <span class="font-semibold text-gray-900">
                                {{
                                    activeAppointment?.patient?.first_name ||
                                    activeAppointment?.user?.name ||
                                    "this patient"
                                }} </span
                            >?
                        </p>
                    </div>
                </div>

                <div
                    class="mt-6 flex justify-end gap-3 border-t border-gray-100 pt-4"
                >
                    <button
                        type="button"
                        @click="showCancelModal = false"
                        class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 shadow-sm hover:bg-gray-50"
                    >
                        Go Back
                    </button>
                    <button
                        @click="submitCancel"
                        class="rounded-lg bg-red-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-red-700"
                    >
                        Yes, Cancel Appointment
                    </button>
                </div>
            </div>
        </Modal>
    </AuthenticatedLayout>
</template>
