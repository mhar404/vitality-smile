<script setup>
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import ServiceIcon from "@/Components/ServiceIcon.vue";
import { Link, useForm } from "@inertiajs/vue3";
import { computed, onMounted, ref, watch } from "vue";

const props = defineProps({
    services: Array,
    detailed_services: {
        type: Array,
        default: () => [],
    },
    patients: {
        type: Array,
        default: () => [],
    },
    dentist: {
        type: Object,
        default: null,
    },
    preselected_patient_id: {
        type: [String, Number],
        default: null,
    },
    preselected_service: {
        type: String,
        default: "",
    },
    isClinicStaff: {
        type: Boolean,
        default: false,
    },
    medicalClearance: {
        type: Object,
        default: null,
    },
});

const activeMedicalClearance = computed(() => {
    if (!props.isClinicStaff) {
        return props.medicalClearance;
    }
    if (!form.patient_id) return null;
    const selected = (props.patients || []).find((p) => String(p.id) === String(form.patient_id));
    const mh = selected?.medical_history;
    if (!mh) return null;
    const hasConds = !!(
        mh.hypertension ||
        mh.diabetes ||
        mh.heart_disease ||
        mh.bleeding_disorders ||
        mh.asthma ||
        mh.kidney_disease ||
        mh.liver_disease ||
        mh.hepatitis ||
        mh.epilepsy ||
        mh.is_pregnant ||
        mh.has_allergies ||
        (mh.allergies && mh.allergies.trim().length > 0)
    );
    if (!hasConds) return null;
    const conds = [];
    if (mh.hypertension) conds.push("Hypertension");
    if (mh.diabetes) conds.push("Diabetes");
    if (mh.heart_disease) conds.push("Heart Disease");
    if (mh.bleeding_disorders) conds.push("Bleeding Disorders");
    if (mh.asthma) conds.push("Asthma");
    if (mh.kidney_disease) conds.push("Kidney Disease");
    if (mh.liver_disease) conds.push("Liver Disease");
    if (mh.hepatitis) conds.push("Hepatitis");
    if (mh.epilepsy) conds.push("Epilepsy");
    if (mh.is_pregnant) conds.push("Pregnancy");
    if (mh.has_allergies || (mh.allergies && mh.allergies.trim())) conds.push("Drug Allergies");

    return {
        has_conditions: true,
        clearance_presented: !!mh.medical_clearance_presented,
        clearing_doctor: mh.medical_clearance_doctor,
        conditions: conds,
    };
});

const resolveInitialService = (target) => {
    if (!target) return "";
    const raw = String(target).trim();
    if (props.services && props.services.includes(raw)) return raw;
    const model = (props.detailed_services || []).find(
        (s) => s.name === raw || s.title === raw
    );
    if (model) return model.name || model.title;
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
    if (fuzzyModel) return fuzzyModel.name || fuzzyModel.title;
    const fuzzy = (props.services || []).find((s) => {
        const sLower = s.toLowerCase();
        return sLower.includes(rawLower) || rawLower.includes(sLower);
    });
    return fuzzy || raw;
};

const form = useForm({
    patient_id: props.preselected_patient_id ? String(props.preselected_patient_id) : "",
    service: resolveInitialService(props.preselected_service),
    appointment_date: "",
    appointment_time: "",
    status: props.isClinicStaff ? "confirmed" : "pending",
    notes: "",
});

const availableServices = computed(() => {
    const list = [...(props.services || [])];
    (props.detailed_services || []).forEach((s) => {
        if (s.name && !list.includes(s.name)) list.push(s.name);
        if (s.title && !list.includes(s.title)) list.push(s.title);
    });
    if (form.service && !list.includes(form.service)) {
        list.unshift(form.service);
    }
    return list;
});

// Clinic operating schedule constants
const CLINIC_START_TIME = "09:00";
const CLINIC_END_TIME = "18:00";

const todayString = computed(() => {
    const today = new Date();
    const yyyy = today.getFullYear();
    const mm = String(today.getMonth() + 1).padStart(2, "0");
    const dd = String(today.getDate()).padStart(2, "0");
    return `${yyyy}-${mm}-${dd}`;
});

// Match selected service from detailed list
const selectedServiceObj = computed(() => {
    if (!form.service) return null;
    return (
        props.detailed_services.find(
            (s) => s.name === form.service || s.title === form.service
        ) || null
    );
});

// Calculate duration in minutes from database record or fallback
const selectedDurationMinutes = computed(() => {
    if (selectedServiceObj.value?.duration_minutes) {
        return Number(selectedServiceObj.value.duration_minutes);
    }
    const name = (form.service || "").toLowerCase();
    if (name.includes("orthodontic") || name.includes("adjustment")) return 30;
    if (name.includes("restoration") || name.includes("filling") || name.includes("extraction")) return 45;
    if (name.includes("odontectomy") || name.includes("root canal")) return 90;
    return 60;
});

// Automatically calculate end time using selected start time + duration
const calculatedEndTime = computed(() => {
    if (!form.appointment_time || !selectedDurationMinutes.value) return "";
    try {
        const parts = form.appointment_time.slice(0, 5).split(":");
        const startH = parseInt(parts[0], 10);
        const startM = parseInt(parts[1], 10);
        if (isNaN(startH) || isNaN(startM)) return "";

        const totalMinutes = startH * 60 + startM + selectedDurationMinutes.value;
        const endH = Math.floor(totalMinutes / 60);
        const endM = totalMinutes % 60;

        return `${String(endH).padStart(2, "0")}:${String(endM).padStart(2, "0")}`;
    } catch {
        return "";
    }
});

// Helper to format 24-hr time to 12-hr display (e.g. "09:00" -> "9:00 AM")
const format12Hour = (time24) => {
    if (!time24) return "";
    try {
        const parts = time24.slice(0, 5).split(":");
        const h = parseInt(parts[0], 10);
        const m = parseInt(parts[1], 10);
        const ampm = h >= 12 ? "PM" : "AM";
        const hour12 = h % 12 || 12;
        return `${hour12}:${String(m).padStart(2, "0")} ${ampm}`;
    } catch {
        return time24;
    }
};

// Date & Time Validation
const dateError = ref("");
const timeError = ref("");

const validateDate = () => {
    dateError.value = "";
    if (!form.appointment_date) return true;

    const parts = form.appointment_date.split("-");
    if (parts.length === 3) {
        const year = parseInt(parts[0], 10);
        const month = parseInt(parts[1], 10) - 1;
        const day = parseInt(parts[2], 10);
        const selected = new Date(year, month, day);

        const dayOfWeek = selected.getDay(); // 0 = Sun, 6 = Sat
        if (dayOfWeek === 0 || dayOfWeek === 6) {
            dateError.value =
                "Appointments are only available from Monday to Friday. The clinic is closed on weekends.";
            return false;
        }

        if (!props.isClinicStaff) {
            const today = new Date();
            today.setHours(0, 0, 0, 0);
            if (selected < today) {
                dateError.value = "Appointment date cannot be in the past.";
                return false;
            }
        }
    }
    return true;
};

const validateTime = () => {
    timeError.value = "";
    if (!form.appointment_time) return true;

    const time = form.appointment_time.slice(0, 5);
    if (time < CLINIC_START_TIME || time > CLINIC_END_TIME) {
        timeError.value = "Appointment starting time must be between 9:00 AM and 6:00 PM.";
        return false;
    }

    if (calculatedEndTime.value && calculatedEndTime.value > "18:00") {
        timeError.value = `This ${selectedDurationMinutes.value}-minute appointment starting at ${format12Hour(time)} would end at ${format12Hour(calculatedEndTime.value)}, exceeding our 6:00 PM closing time.`;
        return false;
    }

    // Check if the selected slot overlaps a confirmed appointment from live availability
    if (availabilityData.value?.slots) {
        const matchingSlot = availabilityData.value.slots.find(
            (s) => s.time === time
        );
        if (matchingSlot && !matchingSlot.available) {
            timeError.value = matchingSlot.reason || "This time slot is unavailable.";
            return false;
        }
    }

    return true;
};

// Live Server Availability State
const isCheckingAvailability = ref(false);
const availabilityData = ref(null);
const availabilityError = ref("");

const fetchAvailability = async () => {
    if (!form.appointment_date) {
        availabilityData.value = null;
        return;
    }
    isCheckingAvailability.value = true;
    availabilityError.value = "";

    try {
        const params = new URLSearchParams({
            date: form.appointment_date,
            duration_minutes: String(selectedDurationMinutes.value),
            service: form.service || "",
        });

        const response = await fetch(
            route("appointments.availability") + "?" + params.toString(),
            {
                headers: {
                    Accept: "application/json",
                    "X-Requested-With": "XMLHttpRequest",
                },
            }
        );

        if (!response.ok) {
            throw new Error(`HTTP ${response.status}`);
        }

        const data = await response.json();
        availabilityData.value = data;

        // Revalidate currently chosen time against fresh availability data
        if (form.appointment_time) {
            validateTime();
        }
    } catch (e) {
        console.error("Failed to load availability", e);
        availabilityError.value = "Unable to fetch live clinic availability.";
    } finally {
        isCheckingAvailability.value = false;
    }
};

watch(() => form.appointment_date, () => {
    if (validateDate()) {
        fetchAvailability();
    }
});

watch(selectedDurationMinutes, () => {
    if (form.appointment_date) {
        fetchAvailability();
    }
    if (form.appointment_time) {
        validateTime();
    }
});

watch(() => form.appointment_time, () => {
    validateTime();
});

// Slot Click Handler
const selectSlot = (slot) => {
    if (!slot.available) return;
    form.appointment_time = slot.time;
    validateTime();
};

// Form submission
const submit = () => {
    const isDateOk = validateDate();
    const isTimeOk = validateTime();
    if (!isDateOk || !isTimeOk) return;

    form.post(route("appointments.store"));
};

// Format selected date nicely
const formattedSelectedDate = computed(() => {
    if (!form.appointment_date) return "";
    try {
        const [y, m, d] = form.appointment_date.split("-");
        const dt = new Date(parseInt(y, 10), parseInt(m, 10) - 1, parseInt(d, 10));
        return dt.toLocaleDateString("en-US", {
            weekday: "long",
            month: "long",
            day: "numeric",
            year: "numeric",
        });
    } catch {
        return form.appointment_date;
    }
});

onMounted(() => {
    if (form.service && !form.appointment_date) {
        // Pre-fill tomorrow if weekday or today
        const nextDate = new Date();
        if (nextDate.getDay() === 5) {
            // Friday -> next Monday
            nextDate.setDate(nextDate.getDate() + 3);
        } else if (nextDate.getDay() === 6) {
            // Saturday -> next Monday
            nextDate.setDate(nextDate.getDate() + 2);
        } else {
            nextDate.setDate(nextDate.getDate() + 1);
        }
        form.appointment_date = nextDate.toISOString().slice(0, 10);
        fetchAvailability();
    }
});
</script>

<template>
    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <div>
                    <div class="flex items-center gap-2">
                        <span class="inline-flex items-center gap-1 rounded-md bg-teal-100 px-2 py-0.5 text-xs font-bold text-teal-800">
                            <i class="pi pi-calendar text-[10px]"></i>
                            {{ isClinicStaff ? 'Staff Scheduling' : 'Patient Booking' }}
                        </span>
                    </div>
                    <h2 class="font-bold text-xl text-gray-900 leading-tight mt-1">
                        {{ isClinicStaff ? 'Schedule Clinic Appointment' : 'Book a Dental Appointment' }}
                    </h2>
                    <p class="text-xs text-gray-500 mt-0.5">
                        {{ isClinicStaff ? 'Assign a patient, select procedure duration, and lock in an open dentist slot.' : 'Choose your desired service, date, and available time slot.' }}
                    </p>
                </div>
                <Link
                    :href="route('appointments.index')"
                    class="inline-flex items-center gap-1.5 rounded-xl border border-gray-200 bg-white px-3.5 py-2 text-xs font-semibold text-gray-700 shadow-xs hover:bg-gray-50 transition"
                >
                    <i class="pi pi-arrow-left text-xs"></i>
                    Back to Appointments
                </Link>
            </div>
        </template>

        <div class="py-8">
            <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

                <!-- Clinic Operating Notice Banner -->
                <div class="rounded-2xl bg-teal-50/90 border border-teal-200 p-4 sm:p-5 flex items-start gap-3.5 shadow-xs">
                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-teal-700 text-white shadow-xs">
                        <i class="pi pi-clock text-base"></i>
                    </div>
                    <div class="flex-1 text-xs sm:text-sm">
                        <div class="flex flex-wrap items-center gap-2">
                            <h4 class="font-bold text-teal-950">
                                Clinic Operating Hours & Schedule Policy
                            </h4>
                            <span class="inline-flex items-center rounded-full bg-teal-200/80 px-2.5 py-0.5 text-[11px] font-bold text-teal-900">
                                Monday – Friday Only
                            </span>
                        </div>
                        <p class="text-teal-800 mt-1 leading-relaxed">
                            Appointments are scheduled from <strong class="text-teal-950">9:00 AM to 6:00 PM</strong> on weekdays. End times are automatically calculated based on procedure duration. Confirmed appointments reserved with Dr. Valdez prevent overlapping bookings.
                        </p>
                    </div>
                </div>

                <!-- Main Form Card -->
                <div class="bg-white rounded-3xl border border-gray-200 shadow-sm overflow-hidden">
                    <div class="p-6 sm:p-8">
                        <form @submit.prevent="submit" class="space-y-6">

                            <!-- Staff Only: Patient Selector -->
                            <div v-if="isClinicStaff">
                                <label for="patient_id" class="block text-xs font-semibold text-gray-700 uppercase tracking-wider mb-1">
                                    Patient Record <span class="text-red-500">*</span>
                                </label>
                                <select
                                    id="patient_id"
                                    v-model="form.patient_id"
                                    required
                                    class="w-full rounded-xl border-gray-300 text-sm focus:border-teal-500 focus:ring-teal-500"
                                >
                                    <option value="" disabled>Select patient from records...</option>
                                    <option
                                        v-for="patient in patients"
                                        :key="patient.id"
                                        :value="String(patient.id)"
                                    >
                                        {{ patient.last_name }}, {{ patient.first_name }} (ID: {{ patient.patient_id }})
                                    </option>
                                </select>
                                <p v-if="form.errors.patient_id" class="text-red-500 text-xs mt-1">
                                    {{ form.errors.patient_id }}
                                </p>
                            </div>

                            <!-- Medical Clearance Safety Gate Notification -->
                            <div
                                v-if="activeMedicalClearance?.has_conditions && !activeMedicalClearance?.clearance_presented"
                                class="rounded-2xl border-2 border-amber-300 dark:border-amber-700/60 bg-amber-50/90 dark:bg-amber-950/40 p-4 sm:p-5 space-y-2 shadow-xs"
                            >
                                <div class="flex items-start gap-3">
                                    <div class="h-9 w-9 rounded-xl bg-amber-500 text-white flex items-center justify-center shrink-0 shadow-xs">
                                        <i class="pi pi-exclamation-triangle text-base"></i>
                                    </div>
                                    <div class="space-y-1">
                                        <div class="flex items-center gap-2 flex-wrap">
                                            <h4 class="text-xs sm:text-sm font-black text-amber-950 dark:text-amber-200 uppercase tracking-tight">
                                                Clinical Safety Notice: Physician Medical Clearance Mandatory
                                            </h4>
                                            <span class="px-2 py-0.5 rounded-full text-[10px] font-black uppercase tracking-wider bg-rose-600 text-white">
                                                Clearance Required
                                            </span>
                                        </div>
                                        <p class="text-xs text-amber-900 dark:text-amber-300 leading-relaxed">
                                            {{ isClinicStaff ? 'This patient has' : 'You have' }} documented medical conditions (<strong>{{ activeMedicalClearance.conditions.join(', ') }}</strong>). In accordance with Vitality Smile clinical safety protocols, a signed <strong>Physician Medical Clearance</strong> must be presented and verified before any dental treatment or procedure can be administered.
                                        </p>
                                        <p class="text-[11px] font-semibold text-amber-800 dark:text-amber-400">
                                            📌 Please ensure {{ isClinicStaff ? 'the patient presents' : 'you bring' }} this written clearance to this appointment.
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <!-- Verified Clearance Badge -->
                            <div
                                v-else-if="activeMedicalClearance?.has_conditions && activeMedicalClearance?.clearance_presented"
                                class="rounded-xl border border-emerald-300 bg-emerald-50/80 p-3 flex items-center justify-between text-xs text-emerald-900"
                            >
                                <div class="flex items-center gap-2">
                                    <i class="pi pi-check-circle text-emerald-600 text-base"></i>
                                    <span>Physician Medical Clearance on file (Verified by attending dentist: Dr. {{ activeMedicalClearance.clearing_doctor || 'Physician' }}).</span>
                                </div>
                                <span class="font-bold text-[10px] uppercase tracking-wider text-emerald-700 bg-white px-2 py-0.5 rounded-full border border-emerald-200">
                                    Cleared for Treatment
                                </span>
                            </div>

                            <!-- 1. Service Selection & Live Duration Badge (Requirement 1) -->
                            <div>
                                <label for="service" class="block text-xs font-semibold text-gray-700 uppercase tracking-wider mb-1">
                                    Dental Procedure / Service <span class="text-red-500">*</span>
                                </label>
                                <select
                                    id="service"
                                    v-model="form.service"
                                    required
                                    class="w-full rounded-xl border-gray-300 text-sm focus:border-teal-500 focus:ring-teal-500"
                                >
                                    <option value="" disabled>Choose a dental service...</option>
                                    <option
                                        v-for="service in availableServices"
                                        :key="service"
                                        :value="service"
                                    >
                                        {{ service }}
                                    </option>
                                </select>
                                <p v-if="form.errors.service" class="text-red-500 text-xs mt-1">
                                    {{ form.errors.service }}
                                </p>

                                <!-- Live Estimated Duration Badge -->
                                <div
                                    v-if="form.service"
                                    class="mt-3 rounded-2xl border border-teal-200 bg-gradient-to-r from-teal-50 to-cyan-50 p-4 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3"
                                >
                                    <div class="flex items-center gap-3">
                                        <ServiceIcon
                                            :service="selectedServiceObj"
                                            :title="form.service"
                                            size="sm"
                                        />
                                        <div>
                                            <div class="text-xs font-bold text-teal-950">
                                                {{ selectedServiceObj?.title || form.service }}
                                            </div>
                                            <div class="text-[11px] text-teal-700">
                                                {{ selectedServiceObj?.formatted_price || 'Standard Clinic Fee' }}
                                            </div>
                                        </div>
                                    </div>

                                    <div class="flex items-center gap-2 self-start sm:self-auto">
                                        <span class="inline-flex items-center gap-1.5 rounded-xl bg-teal-700 px-3.5 py-1.5 text-xs font-bold text-white shadow-xs">
                                            <i class="pi pi-clock text-xs"></i>
                                            Estimated Duration: {{ selectedDurationMinutes }} minutes
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <!-- 2. Date Selection (Requirement 2) -->
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label for="appointment_date" class="block text-xs font-semibold text-gray-700 uppercase tracking-wider mb-1">
                                        Appointment Date <span class="text-red-500">*</span>
                                    </label>
                                    <input
                                        id="appointment_date"
                                        v-model="form.appointment_date"
                                        type="date"
                                        :min="todayString"
                                        required
                                        :class="[
                                            'w-full rounded-xl border-gray-300 text-sm focus:border-teal-500 focus:ring-teal-500',
                                            dateError || form.errors.appointment_date ? 'border-red-400 ring-1 ring-red-400' : ''
                                        ]"
                                    />
                                    <p class="text-[11px] text-gray-500 mt-1">
                                        Clinic open Monday to Friday
                                    </p>
                                    <p v-if="dateError" class="text-red-600 text-xs mt-1 font-medium flex items-center gap-1">
                                        <i class="pi pi-exclamation-circle text-xs"></i>
                                        {{ dateError }}
                                    </p>
                                    <p v-if="form.errors.appointment_date" class="text-red-600 text-xs mt-1">
                                        {{ form.errors.appointment_date }}
                                    </p>
                                </div>

                                <!-- Manual Start Time Fallback Input -->
                                <div>
                                    <label for="appointment_time" class="block text-xs font-semibold text-gray-700 uppercase tracking-wider mb-1">
                                        Starting Time <span class="text-red-500">*</span>
                                    </label>
                                    <input
                                        id="appointment_time"
                                        v-model="form.appointment_time"
                                        type="time"
                                        min="09:00"
                                        max="18:00"
                                        step="900"
                                        required
                                        :class="[
                                            'w-full rounded-xl border-gray-300 text-sm focus:border-teal-500 focus:ring-teal-500',
                                            timeError || form.errors.appointment_time ? 'border-red-400 ring-1 ring-red-400' : ''
                                        ]"
                                    />
                                    <p class="text-[11px] text-gray-500 mt-1">
                                        Between 9:00 AM and 6:00 PM (or pick slot below)
                                    </p>
                                    <p v-if="timeError" class="text-red-600 text-xs mt-1 font-medium flex items-center gap-1">
                                        <i class="pi pi-exclamation-circle text-xs"></i>
                                        {{ timeError }}
                                    </p>
                                    <p v-if="form.errors.appointment_time" class="text-red-600 text-xs mt-1">
                                        {{ form.errors.appointment_time }}
                                    </p>
                                </div>
                            </div>

                            <!-- 3. Dynamic Live Time Slots & Availability Display (Requirements 2, 5, 6, 8, 9) -->
                            <div class="rounded-2xl border border-gray-200 bg-gray-50/50 p-5 space-y-3">
                                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 border-b border-gray-200 pb-3">
                                    <div>
                                        <h3 class="text-xs font-bold uppercase tracking-wider text-gray-800 flex items-center gap-2">
                                            <span>Available Starting Time Slots</span>
                                            <span v-if="isCheckingAvailability" class="text-teal-600 flex items-center gap-1 text-[11px] normal-case font-normal">
                                                <i class="pi pi-spin pi-spinner text-xs"></i>
                                                Checking dentist schedule...
                                            </span>
                                        </h3>
                                        <p class="text-[11px] text-gray-500 mt-0.5">
                                            Slots automatically check Dr. Valdez's schedule and procedure duration ({{ selectedDurationMinutes }} mins).
                                        </p>
                                    </div>

                                    <!-- Legend -->
                                    <div class="flex items-center gap-3 text-[11px] text-gray-600 flex-wrap">
                                        <span class="inline-flex items-center gap-1">
                                            <span class="h-2.5 w-2.5 rounded-full bg-teal-600"></span> Available
                                        </span>
                                        <span class="inline-flex items-center gap-1">
                                            <span class="h-2.5 w-2.5 rounded-full bg-amber-400"></span> Pending Request
                                        </span>
                                        <span class="inline-flex items-center gap-1">
                                            <span class="h-2.5 w-2.5 rounded-full bg-gray-300"></span> Booked / Closed
                                        </span>
                                    </div>
                                </div>

                                <!-- Slot Grid -->
                                <div v-if="availabilityData?.slots && availabilityData.slots.length > 0" class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-2.5 pt-2">
                                    <button
                                        v-for="slot in availabilityData.slots"
                                        :key="slot.time"
                                        type="button"
                                        :disabled="!slot.available"
                                        @click="selectSlot(slot)"
                                        :title="slot.reason || (slot.has_pending ? 'Slot has pending requests' : 'Available for booking')"
                                        :class="[
                                            'relative rounded-xl p-2.5 text-left border transition text-xs flex flex-col justify-between min-h-[58px]',
                                            form.appointment_time && form.appointment_time.slice(0, 5) === slot.time
                                                ? 'border-teal-700 bg-teal-700 text-white font-bold ring-2 ring-teal-400 shadow-sm'
                                                : slot.available
                                                    ? 'border-gray-200 bg-white hover:border-teal-500 hover:bg-teal-50/50 text-gray-800'
                                                    : 'border-gray-200 bg-gray-100/80 text-gray-400 cursor-not-allowed opacity-60'
                                        ]"
                                    >
                                        <div class="flex items-center justify-between">
                                            <span class="font-bold text-xs">{{ slot.label }}</span>
                                            <span
                                                v-if="slot.has_pending && slot.available && form.appointment_time?.slice(0, 5) !== slot.time"
                                                class="h-2 w-2 rounded-full bg-amber-500"
                                                title="Slot has pending requests"
                                            ></span>
                                        </div>

                                        <div class="text-[10px] mt-1 truncate">
                                            <span v-if="slot.available">
                                                Ends: {{ slot.end_label }}
                                            </span>
                                            <span v-else class="text-red-500 font-medium">
                                                {{ slot.reason || 'Booked' }}
                                            </span>
                                        </div>
                                    </button>
                                </div>

                                <div v-else-if="!form.appointment_date" class="py-6 text-center text-xs text-gray-500">
                                    <i class="pi pi-calendar text-gray-400 text-lg mb-1 block"></i>
                                    Please select an appointment date above to view real-time dentist availability.
                                </div>

                                <div v-else-if="availabilityData && !availabilityData.is_operating_day" class="py-6 text-center text-xs text-amber-700 bg-amber-50 rounded-xl p-4">
                                    <i class="pi pi-info-circle text-amber-600 text-lg mb-1 block"></i>
                                    {{ availabilityData.message }}
                                </div>
                            </div>

                            <!-- 4. Estimated Window Preview Card (Requirements 3 & 4) -->
                            <div
                                v-if="form.service && form.appointment_date && form.appointment_time"
                                class="rounded-2xl border border-teal-300 bg-teal-50/60 p-5 shadow-xs"
                            >
                                <div class="flex items-center gap-2 text-xs font-bold uppercase tracking-wider text-teal-900 border-b border-teal-200/80 pb-3">
                                    <i class="pi pi-check-circle text-teal-700 text-sm"></i>
                                    Appointment Schedule Confirmation Preview
                                </div>

                                <div class="mt-3 grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-4 text-xs">
                                    <!-- Procedure & Duration -->
                                    <div>
                                        <span class="text-gray-500 block">Procedure & Duration</span>
                                        <span class="font-bold text-gray-900 text-sm">
                                            {{ selectedServiceObj?.title || form.service }}
                                        </span>
                                        <span class="text-teal-700 block font-semibold text-xs mt-0.5">
                                            {{ selectedDurationMinutes }} Minutes Scheduled
                                        </span>
                                    </div>

                                    <!-- Date -->
                                    <div>
                                        <span class="text-gray-500 block">Scheduled Date</span>
                                        <span class="font-bold text-gray-900 text-sm">
                                            {{ formattedSelectedDate }}
                                        </span>
                                        <span class="text-gray-500 block text-xs mt-0.5">
                                            Regular Clinic Hours
                                        </span>
                                    </div>

                                    <!-- Time Window -->
                                    <div>
                                        <span class="text-gray-500 block">Estimated Time Window</span>
                                        <span class="font-extrabold text-teal-900 text-base">
                                            {{ format12Hour(form.appointment_time) }} – {{ format12Hour(calculatedEndTime) }}
                                        </span>
                                        <span class="text-gray-500 block text-xs mt-0.5">
                                            Attending: Dr. Valdez
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <!-- Staff Only: Status Selector -->
                            <div v-if="isClinicStaff" class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label for="status" class="block text-xs font-semibold text-gray-700 uppercase tracking-wider mb-1">
                                        Initial Status <span class="text-red-500">*</span>
                                    </label>
                                    <select
                                        id="status"
                                        v-model="form.status"
                                        required
                                        class="w-full rounded-xl border-gray-300 text-sm focus:border-teal-500 focus:ring-teal-500"
                                    >
                                        <option value="confirmed">Confirmed (Reserve & Lock Slot)</option>
                                        <option value="pending">Pending (Review Queue)</option>
                                    </select>
                                </div>

                                <div>
                                    <label class="block text-xs font-semibold text-gray-700 uppercase tracking-wider mb-1">
                                        Attending Dentist
                                    </label>
                                    <div class="rounded-xl border border-gray-200 bg-gray-50 p-2.5 text-xs text-gray-800 font-semibold flex items-center justify-between">
                                        <span>Dr. Valdez</span>
                                        <span class="text-[11px] text-teal-700 font-normal">Resident Dentist</span>
                                    </div>
                                </div>
                            </div>

                            <!-- Notes -->
                            <div>
                                <label for="notes" class="block text-xs font-semibold text-gray-700 uppercase tracking-wider mb-1">
                                    {{ isClinicStaff ? 'Scheduling Remarks / Clinical Notes' : 'Patient Notes / Special Requests (Optional)' }}
                                </label>
                                <textarea
                                    id="notes"
                                    v-model="form.notes"
                                    rows="3"
                                    class="w-full rounded-xl border-gray-300 text-sm focus:border-teal-500 focus:ring-teal-500"
                                    :placeholder="isClinicStaff ? 'Pre-procedure instructions, medical alerts, or notes...' : 'Any toothache symptoms, tooth numbers, or requests for the dentist?'"
                                ></textarea>
                                <p v-if="form.errors.notes" class="text-red-500 text-xs mt-1">
                                    {{ form.errors.notes }}
                                </p>
                            </div>

                            <!-- Form Actions -->
                            <div class="flex items-center justify-end gap-3 border-t border-gray-100 pt-5">
                                <Link
                                    :href="route('appointments.index')"
                                    class="rounded-xl border border-gray-300 bg-white px-5 py-2.5 text-xs font-semibold text-gray-700 hover:bg-gray-50 transition"
                                >
                                    Cancel
                                </Link>

                                <button
                                    type="submit"
                                    :disabled="form.processing || !!dateError || !!timeError"
                                    class="inline-flex items-center gap-2 rounded-xl bg-teal-700 px-6 py-2.5 text-xs font-bold text-white shadow-sm hover:bg-teal-800 focus:outline-none focus:ring-2 focus:ring-teal-500 focus:ring-offset-2 disabled:opacity-50 transition"
                                >
                                    <i v-if="form.processing" class="pi pi-spin pi-spinner text-xs"></i>
                                    <i v-else class="pi pi-calendar-plus text-xs"></i>
                                    {{ isClinicStaff ? 'Confirm & Schedule Appointment' : 'Submit Appointment Request' }}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

            </div>
        </div>
    </AuthenticatedLayout>
</template>
