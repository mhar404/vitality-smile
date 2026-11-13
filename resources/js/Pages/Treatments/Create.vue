<script setup>
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import { Head, Link, useForm } from "@inertiajs/vue3";
import { ref, computed, onMounted } from "vue";

const props = defineProps({
    patients: Array,
    dentists: Array,
    services: Array,
    serviceCatalog: Array,
    preselected_patient_id: [String, Number],
    preselected_appointment: Object,
    preselected_procedure: String,
    today: String,
});

const isCustomProcedure = ref(false);

const initialProcedure =
    props.preselected_procedure ||
    props.preselected_appointment?.service ||
    (props.services && props.services.length > 0
        ? props.services[0]
        : "Oral Prophylaxis (₱800-₱1,000)");

const availableServices = computed(() => {
    const list = [...(props.services || [])];
    if (initialProcedure && !list.includes(initialProcedure)) {
        list.unshift(initialProcedure);
    }
    return list;
});

// Comprehensive catalog price matcher ensuring procedure fee strictly aligns with procedure catalog
const lookupProcedureFee = (procedureName) => {
    if (!procedureName) {
        return {
            min: 800,
            max: 1000,
            isFixed: false,
            defaultFee: 800,
            label: "₱800.00 – ₱1,000.00",
            presets: [800, 900, 1000],
            procedureTitle: "Oral Prophylaxis",
        };
    }

    // 1. Check in props.serviceCatalog if available
    const catalog = props.serviceCatalog || [];
    let matched = catalog.find(
        (s) => s.name === procedureName || s.title === procedureName,
    );

    if (!matched) {
        const lower = procedureName.toLowerCase();
        matched = catalog.find(
            (s) =>
                (s.name && s.name.toLowerCase() === lower) ||
                (s.title && s.title.toLowerCase() === lower) ||
                (s.name && lower.includes(s.name.toLowerCase())) ||
                (s.title && lower.includes(s.title.toLowerCase())),
        );
    }

    if (matched) {
        const min = parseFloat(matched.base_price) || 0;
        const max = matched.max_price ? parseFloat(matched.max_price) : min;
        const isFixed = min === max;
        const presets = [];
        if (isFixed) {
            presets.push(min);
        } else {
            presets.push(min);
            if (max - min >= 200) {
                const mid = Math.round((min + max) / 2 / 50) * 50;
                if (mid > min && mid < max) presets.push(mid);
            }
            presets.push(max);
        }
        return {
            min,
            max,
            isFixed,
            defaultFee: min,
            label:
                matched.price_display ||
                (isFixed
                    ? `₱${min.toLocaleString()}`
                    : `₱${min.toLocaleString()} – ₱${max.toLocaleString()}`),
            presets,
            procedureTitle: matched.title || matched.name,
        };
    }

    // 2. Parse from procedure string if contains price like (₱800-₱1,000) or ₱1,500
    const rangeMatch = procedureName.match(
        /₱\s*([0-9,]+)\s*[-–]\s*₱?\s*([0-9,]+)/,
    );
    if (rangeMatch) {
        const min = parseFloat(rangeMatch[1].replace(/,/g, ""));
        const max = parseFloat(rangeMatch[2].replace(/,/g, ""));
        const presets = [min];
        if (max - min >= 200) {
            presets.push(Math.round((min + max) / 2 / 50) * 50);
        }
        presets.push(max);
        return {
            min,
            max,
            isFixed: min === max,
            defaultFee: min,
            label: `₱${min.toLocaleString()} – ₱${max.toLocaleString()}`,
            presets,
            procedureTitle: procedureName,
        };
    }

    const singleMatch = procedureName.match(/₱\s*([0-9,]+)/);
    if (singleMatch) {
        const price = parseFloat(singleMatch[1].replace(/,/g, ""));
        return {
            min: price,
            max: price,
            isFixed: true,
            defaultFee: price,
            label: `₱${price.toLocaleString()} (Fixed)`,
            presets: [price],
            procedureTitle: procedureName,
        };
    }

    // 3. Fallback keywords based on standard Vitality Smile clinic catalog
    const proc = procedureName.toLowerCase();
    if (proc.includes("prophylaxis") || proc.includes("cleaning")) {
        return {
            min: 800,
            max: 1000,
            isFixed: false,
            defaultFee: 800,
            label: "₱800.00 – ₱1,000.00",
            presets: [800, 900, 1000],
            procedureTitle: "Oral Prophylaxis",
        };
    } else if (proc.includes("restoration") || proc.includes("filling")) {
        return {
            min: 800,
            max: 1000,
            isFixed: false,
            defaultFee: 800,
            label: "₱800.00 – ₱1,000.00",
            presets: [800, 900, 1000],
            procedureTitle: "Tooth Restoration",
        };
    } else if (
        proc.includes("extraction") &&
        !proc.includes("wisdom") &&
        !proc.includes("odontectomy")
    ) {
        return {
            min: 800,
            max: 1000,
            isFixed: false,
            defaultFee: 800,
            label: "₱800.00 – ₱1,000.00",
            presets: [800, 900, 1000],
            procedureTitle: "Tooth Extraction",
        };
    } else if (proc.includes("wisdom tooth") || proc.includes("erupted")) {
        return {
            min: 5000,
            max: 8000,
            isFixed: false,
            defaultFee: 5000,
            label: "₱5,000.00 – ₱8,000.00",
            presets: [5000, 6500, 8000],
            procedureTitle: "Wisdom Tooth Removal (Erupted)",
        };
    } else if (
        proc.includes("odontectomy") ||
        proc.includes("surgery") ||
        proc.includes("impacted")
    ) {
        return {
            min: 8000,
            max: 20000,
            isFixed: false,
            defaultFee: 8000,
            label: "₱8,000.00 – ₱20,000.00",
            presets: [8000, 12000, 20000],
            procedureTitle: "Odontectomy / Surgery",
        };
    } else if (proc.includes("root canal")) {
        return {
            min: 8000,
            max: 8000,
            isFixed: true,
            defaultFee: 8000,
            label: "₱8,000.00 per canal (Fixed)",
            presets: [8000],
            procedureTitle: "Root Canal Treatment",
        };
    } else if (proc.includes("whitening")) {
        return {
            min: 8000,
            max: 10000,
            isFixed: false,
            defaultFee: 8000,
            label: "₱8,000.00 – ₱10,000.00",
            presets: [8000, 9000, 10000],
            procedureTitle: "Teeth Whitening",
        };
    } else if (proc.includes("plastic jacket")) {
        return {
            min: 5000,
            max: 6000,
            isFixed: false,
            defaultFee: 5000,
            label: "₱5,000.00 – ₱6,000.00 per unit",
            presets: [5000, 5500, 6000],
            procedureTitle: "Plastic Jacket Crown",
        };
    } else if (
        proc.includes("porcelain") ||
        proc.includes("metal jacket") ||
        proc.includes("pfm")
    ) {
        return {
            min: 7000,
            max: 8000,
            isFixed: false,
            defaultFee: 7000,
            label: "₱7,000.00 – ₱8,000.00 per unit",
            presets: [7000, 7500, 8000],
            procedureTitle: "Porcelain Fused to Metal Crown",
        };
    } else if (proc.includes("bridge")) {
        return {
            min: 7000,
            max: 8000,
            isFixed: false,
            defaultFee: 7000,
            label: "₱7,000.00 – ₱8,000.00 per unit",
            presets: [7000, 7500, 8000],
            procedureTitle: "Fixed Bridge",
        };
    } else if (proc.includes("denture")) {
        return {
            min: 10000,
            max: 15000,
            isFixed: false,
            defaultFee: 10000,
            label: "₱10,000.00 – ₱15,000.00",
            presets: [10000, 12500, 15000],
            procedureTitle: "Ordinary Dentures",
        };
    } else if (
        proc.includes("orthodontic") ||
        proc.includes("adjustment") ||
        proc.includes("braces")
    ) {
        return {
            min: 1500,
            max: 1500,
            isFixed: true,
            defaultFee: 1500,
            label: "₱1,500.00 (Fixed)",
            presets: [1500],
            procedureTitle: "Orthodontic Consultation / Adjustment",
        };
    } else if (proc.includes("fluoride")) {
        return {
            min: 800,
            max: 800,
            isFixed: true,
            defaultFee: 800,
            label: "₱800.00 (Fixed)",
            presets: [800],
            procedureTitle: "Fluoride Treatment",
        };
    } else if (proc.includes("check-up") || proc.includes("consultation")) {
        return {
            min: 500,
            max: 500,
            isFixed: true,
            defaultFee: 500,
            label: "₱500.00 (Fixed)",
            presets: [500],
            procedureTitle: "Dental Check-up & Consultation",
        };
    }

    return {
        min: 800,
        max: 1000,
        isFixed: false,
        defaultFee: 800,
        label: "₱800.00 – ₱1,000.00",
        presets: [800, 1000],
        procedureTitle: procedureName,
    };
};

const currentFeeConfig = computed(() =>
    lookupProcedureFee(form.procedure_name),
);

const initialFeeConfig = lookupProcedureFee(initialProcedure);

const form = useForm({
    patient_id:
        props.preselected_patient_id ||
        (props.preselected_appointment
            ? props.preselected_appointment.patient_id
            : ""),
    dentist_id: props.preselected_appointment
        ? props.preselected_appointment.dentist_id || ""
        : "",
    appointment_id: props.preselected_appointment
        ? props.preselected_appointment.id
        : "",
    procedure_name: initialProcedure,
    custom_procedure_name: "",
    tooth_number: "",
    diagnosis: "",
    treatment_notes: "",
    prescription: "",
    cost: initialFeeConfig.defaultFee,
    status: "completed",
    treatment_date: props.today || new Date().toISOString().split("T")[0],
    redirect_to_patient: props.preselected_patient_id ? 1 : 0,
    medical_clearance_presented: false,
    medical_clearance_doctor: "",
    medical_clearance_date:
        props.today || new Date().toISOString().split("T")[0],
    medical_clearance_notes: "",
});

const feeError = ref("");

// Selected patient & medical history tracking
const selectedPatient = computed(() => {
    if (!form.patient_id) return null;
    return (
        (props.patients || []).find(
            (p) => String(p.id) === String(form.patient_id),
        ) || null
    );
});

const patientMedicalHistory = computed(() => {
    return selectedPatient.value?.medical_history || null;
});

// Check if patient has any recorded medical conditions / alerts
const patientHasMedicalConditions = computed(() => {
    const med = patientMedicalHistory.value;
    if (!med) return false;

    return !!(
        med.hypertension ||
        med.diabetes ||
        med.heart_disease ||
        med.bleeding_disorders ||
        med.asthma ||
        med.kidney_disease ||
        med.liver_disease ||
        med.hepatitis ||
        med.epilepsy ||
        med.is_pregnant ||
        med.has_allergies ||
        (med.allergies && String(med.allergies).trim()) ||
        (med.current_medications && String(med.current_medications).trim()) ||
        (med.past_surgeries && String(med.past_surgeries).trim()) ||
        (Array.isArray(med.other_conditions) && med.other_conditions.length > 0)
    );
});

// Badges detailing the patient's specific conditions for clinical awareness
const patientConditionBadges = computed(() => {
    const med = patientMedicalHistory.value;
    if (!med) return [];
    const list = [];
    if (med.heart_disease)
        list.push({ label: "Cardiac / Heart Condition", severity: "danger" });
    if (med.bleeding_disorders)
        list.push({
            label: "Bleeding Tendency / Disorder",
            severity: "danger",
        });
    if (med.hepatitis) list.push({ label: "Hepatitis", severity: "danger" });
    if (med.has_allergies || (med.allergies && String(med.allergies).trim())) {
        list.push({
            label: `Allergies: ${med.allergies || "Present"}`,
            severity: "danger",
        });
    }
    if (med.hypertension)
        list.push({ label: "Hypertension (High BP)", severity: "warning" });
    if (med.diabetes)
        list.push({ label: "Diabetes Mellitus", severity: "warning" });
    if (med.asthma)
        list.push({ label: "Asthma / Respiratory", severity: "warning" });
    if (med.kidney_disease)
        list.push({ label: "Kidney Disease", severity: "warning" });
    if (med.liver_disease)
        list.push({ label: "Liver Disease", severity: "warning" });
    if (med.epilepsy)
        list.push({ label: "Epilepsy / Seizures", severity: "warning" });
    if (med.is_pregnant)
        list.push({ label: "Pregnant / Nursing", severity: "info" });
    if (med.current_medications && String(med.current_medications).trim()) {
        list.push({
            label: `Meds: ${med.current_medications}`,
            severity: "neutral",
        });
    }
    return list;
});

const onPatientChange = () => {
    const med = patientMedicalHistory.value;
    if (med) {
        if (med.physician_name && !form.medical_clearance_doctor) {
            form.medical_clearance_doctor = med.physician_name;
        }
        if (med.medical_clearance_presented) {
            form.medical_clearance_presented = true;
            if (med.medical_clearance_doctor)
                form.medical_clearance_doctor = med.medical_clearance_doctor;
            if (med.medical_clearance_date)
                form.medical_clearance_date = med.medical_clearance_date;
            if (med.medical_clearance_notes)
                form.medical_clearance_notes = med.medical_clearance_notes;
        } else {
            form.medical_clearance_presented = false;
        }
    } else {
        form.medical_clearance_presented = false;
    }
    form.clearErrors("medical_clearance_presented", "medical_clearance_doctor");
};

// Dentist cannot give treatment if patient has medical history and medical clearance is not presented
const canSubmitTreatment = computed(() => {
    if (!form.patient_id) return false;
    if (patientHasMedicalConditions.value) {
        return Boolean(
            form.medical_clearance_presented &&
            form.medical_clearance_doctor &&
            form.medical_clearance_doctor.trim(),
        );
    }
    return true;
});

// Common dental tooth notation chips for rapid entry
const commonToothSelections = [
    "General / Mouth",
    "Upper Anterior (11-13, 21-23)",
    "Lower Anterior (31-33, 41-43)",
    "Upper Right Molar",
    "Upper Left Molar",
    "Lower Right Molar",
    "Lower Left Molar",
    "11",
    "12",
    "14",
    "16",
    "21",
    "24",
    "26",
    "36",
    "46",
];

const setTooth = (val) => {
    form.tooth_number = val;
};

// Automatic cost alignment based on selected procedure
const onProcedureChange = () => {
    const config = currentFeeConfig.value;
    form.cost = config.defaultFee;
    feeError.value = "";
};

const onFeeKeydown = (e) => {
    // Prevent typing negative numbers, plus signs, and scientific notation
    if (["-", "+", "e", "E"].includes(e.key)) {
        e.preventDefault();
    }
};

const validateAndClampFee = (isBlur = false, event = null) => {
    const config = currentFeeConfig.value;
    if (config.isFixed) {
        form.cost = config.min;
        if (event?.target) event.target.value = config.min;
        feeError.value = "";
        return;
    }

    const val = parseFloat(form.cost);
    if (isNaN(val)) {
        if (isBlur) {
            form.cost = config.min;
            if (event?.target) event.target.value = config.min;
        }
        feeError.value = "";
        return;
    }

    // Input cannot be higher than maximum
    if (val > config.max) {
        form.cost = config.max;
        if (event?.target) event.target.value = config.max;
        feeError.value = "";
        return;
    }

    // Input cannot be lower than zero
    if (val < 0) {
        form.cost = config.min;
        if (event?.target) event.target.value = config.min;
        feeError.value = "";
        return;
    }

    // On blur or change, input cannot be lower than minimum
    if (isBlur && val < config.min) {
        form.cost = config.min;
        if (event?.target) event.target.value = config.min;
    }
    feeError.value = "";
};

onMounted(() => {
    if (form.procedure_name) {
        onProcedureChange();
    }
    if (form.patient_id) {
        onPatientChange();
    }
    validateAndClampFee(true);
});

const submit = () => {
    // 1. Enforce medical clearance if patient has medical history
    if (
        patientHasMedicalConditions.value &&
        !form.medical_clearance_presented
    ) {
        form.errors.medical_clearance_presented =
            "The patient has a documented medical history. Medical clearance must be presented before the dentist can administer treatment.";
        return;
    }
    if (
        patientHasMedicalConditions.value &&
        !form.medical_clearance_doctor?.trim()
    ) {
        form.errors.medical_clearance_doctor =
            "Please specify the physician or doctor who issued the medical clearance.";
        return;
    }

    // 2. Enforce procedure fee bounds: strictly cannot be higher than max, cannot be lower than min
    const config = currentFeeConfig.value;
    if (config.isFixed) {
        form.cost = config.min;
    } else {
        const val = parseFloat(form.cost);
        if (isNaN(val) || val < config.min) {
            form.cost = config.min;
        } else if (val > config.max) {
            form.cost = config.max;
        }
    }
    feeError.value = "";

    form.post(route("treatments.store"), {
        preserveScroll: true,
    });
};
</script>

<template>
    <Head title="Record Clinical Treatment" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="font-bold text-xl text-gray-900 leading-tight">
                        Record Clinical Treatment
                    </h2>
                    <p class="text-xs text-gray-500 mt-1">
                        Document patient procedures, clinical diagnosis, tooth
                        notations, and prescriptions.
                    </p>
                </div>
                <Link
                    :href="
                        preselected_patient_id
                            ? route('patients.show', preselected_patient_id)
                            : route('treatments.index')
                    "
                    class="text-gray-600 hover:text-gray-900 text-sm font-medium flex items-center gap-1.5"
                >
                    <i class="pi pi-arrow-left text-xs"></i> Back
                </Link>
            </div>
        </template>

        <div class="py-8">
            <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
                <!-- Appointment Pre-fill Notice banner if linked -->
                <div
                    v-if="preselected_appointment"
                    class="mb-6 bg-teal-50 border border-teal-200 rounded-xl p-4 flex items-center justify-between text-teal-900 text-sm"
                >
                    <div class="flex items-center gap-3">
                        <div
                            class="h-9 w-9 bg-teal-600 text-white rounded-full flex items-center justify-center shrink-0"
                        >
                            <i class="pi pi-check text-sm"></i>
                        </div>
                        <div>
                            <p class="font-bold">
                                Linking Appointment #{{
                                    preselected_appointment.id
                                }}
                            </p>
                            <p class="text-xs text-teal-700">
                                Service: {{ preselected_appointment.service }} •
                                Scheduled:
                                {{ preselected_appointment.appointment_date }}
                                ({{ preselected_appointment.appointment_time }})
                            </p>
                        </div>
                    </div>
                    <span
                        class="text-xs font-semibold px-2.5 py-1 bg-teal-100 rounded-full text-teal-800"
                    >
                        Will mark appointment as Completed
                    </span>
                </div>

                <form
                    @submit.prevent="submit"
                    class="bg-white shadow-sm rounded-2xl border border-gray-200 overflow-hidden"
                >
                    <div class="p-6 sm:p-8 space-y-6">
                        <!-- Section: Patient & Clinician Details -->
                        <div>
                            <h3
                                class="text-sm font-bold uppercase tracking-wider text-teal-700 mb-4 flex items-center gap-2"
                            >
                                <i class="pi pi-user text-sm"></i> Patient &
                                Clinician Information
                            </h3>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                                <!-- Patient Selection -->
                                <div>
                                    <label
                                        class="block text-sm font-semibold text-gray-700 mb-1"
                                    >
                                        Patient
                                        <span class="text-red-500">*</span>
                                    </label>
                                    <select
                                        v-model="form.patient_id"
                                        @change="onPatientChange"
                                        required
                                        class="w-full rounded-lg border-gray-300 shadow-sm focus:border-teal-500 focus:ring-teal-500 text-sm"
                                    >
                                        <option value="" disabled>
                                            Select patient...
                                        </option>
                                        <option
                                            v-for="p in patients"
                                            :key="p.id"
                                            :value="p.id"
                                        >
                                            {{ p.first_name }}
                                            {{ p.last_name }} ({{
                                                p.patient_id
                                            }})
                                        </option>
                                    </select>
                                    <p
                                        v-if="form.errors.patient_id"
                                        class="text-xs text-red-600 mt-1"
                                    >
                                        {{ form.errors.patient_id }}
                                    </p>
                                </div>

                                <!-- Attending Dentist -->
                                <div>
                                    <label
                                        class="block text-sm font-semibold text-gray-700 mb-1"
                                    >
                                        Attending Dentist
                                    </label>
                                    <select
                                        v-model="form.dentist_id"
                                        class="w-full rounded-lg border-gray-300 shadow-sm focus:border-teal-500 focus:ring-teal-500 text-sm"
                                    >
                                        <option value="">
                                            Unassigned / Clinic Staff
                                        </option>
                                        <option
                                            v-for="d in dentists"
                                            :key="d.id"
                                            :value="d.id"
                                        >
                                            {{ d.name }}
                                        </option>
                                    </select>
                                    <p
                                        v-if="form.errors.dentist_id"
                                        class="text-xs text-red-600 mt-1"
                                    >
                                        {{ form.errors.dentist_id }}
                                    </p>
                                </div>
                            </div>

                            <!-- Section: Medical History & Clearance Safety Gate -->
                            <div v-if="form.patient_id" class="mt-5">
                                <!-- Case A: Patient HAS recorded medical history / systemic conditions -->
                                <div
                                    v-if="patientHasMedicalConditions"
                                    class="rounded-2xl border-2 border-amber-300 dark:border-amber-700/60 bg-amber-50/80 dark:bg-amber-950/30 p-5 sm:p-6 space-y-4"
                                >
                                    <div class="flex items-start gap-3.5">
                                        <div
                                            class="h-10 w-10 rounded-xl bg-amber-500 text-white flex items-center justify-center shrink-0 shadow-sm"
                                        >
                                            <i
                                                class="pi pi-exclamation-triangle text-lg"
                                            ></i>
                                        </div>
                                        <div class="flex-1 min-w-0">
                                            <div
                                                class="flex flex-wrap items-center justify-between gap-2"
                                            >
                                                <h4
                                                    class="text-sm font-black text-amber-950 dark:text-amber-200 tracking-tight uppercase flex items-center gap-1.5"
                                                >
                                                    Medical History Alert —
                                                    Physician Clearance Required
                                                </h4>
                                                <span
                                                    class="px-2.5 py-0.5 rounded-full text-[11px] font-black uppercase tracking-wider bg-rose-600 text-white shadow-2xs"
                                                >
                                                    Clearance Mandatory
                                                </span>
                                            </div>
                                            <p
                                                class="text-xs text-amber-900 dark:text-amber-300 mt-1 leading-relaxed"
                                            >
                                                Patient
                                                <strong
                                                    class="underline font-bold"
                                                    >{{
                                                        selectedPatient?.first_name
                                                    }}
                                                    {{
                                                        selectedPatient?.last_name
                                                    }}</strong
                                                >
                                                has documented medical
                                                conditions. In accordance with
                                                clinical safety protocols, a
                                                valid
                                                <strong
                                                    >Physician Medical
                                                    Clearance</strong
                                                >
                                                must be presented and verified
                                                before the dentist can
                                                administer any treatment.
                                            </p>

                                            <!-- Clinical Medical Condition Badges -->
                                            <div
                                                class="mt-3 flex flex-wrap gap-1.5"
                                            >
                                                <span
                                                    v-for="(
                                                        badge, idx
                                                    ) in patientConditionBadges"
                                                    :key="idx"
                                                    :class="[
                                                        'px-2.5 py-1 rounded-lg text-xs font-bold border flex items-center gap-1',
                                                        badge.severity ===
                                                        'danger'
                                                            ? 'bg-rose-100 text-rose-900 border-rose-300 dark:bg-rose-950/70 dark:text-rose-200 dark:border-rose-800'
                                                            : badge.severity ===
                                                                'warning'
                                                              ? 'bg-amber-100 text-amber-900 border-amber-300 dark:bg-amber-950/70 dark:text-amber-200 dark:border-amber-800'
                                                              : badge.severity ===
                                                                  'info'
                                                                ? 'bg-blue-100 text-blue-900 border-blue-300 dark:bg-blue-950/70 dark:text-blue-200 dark:border-blue-800'
                                                                : 'bg-slate-100 text-slate-800 border-slate-300 dark:bg-slate-800 dark:text-slate-300',
                                                    ]"
                                                >
                                                    <i
                                                        class="pi pi-heart text-[10px]"
                                                    ></i>
                                                    {{ badge.label }}
                                                </span>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Clearance Verification Form Box -->
                                    <div
                                        class="pt-4 border-t border-amber-200 dark:border-amber-800/60 bg-white/90 dark:bg-slate-900/90 -mx-5 -mb-5 sm:-mx-6 sm:-mb-6 p-5 sm:p-6 rounded-b-2xl space-y-4"
                                    >
                                        <div class="flex items-start gap-3">
                                            <input
                                                id="medical_clearance_presented"
                                                v-model="
                                                    form.medical_clearance_presented
                                                "
                                                type="checkbox"
                                                class="mt-0.5 h-5 w-5 rounded border-amber-400 text-teal-600 focus:ring-teal-500 cursor-pointer"
                                            />
                                            <div class="flex-1">
                                                <label
                                                    for="medical_clearance_presented"
                                                    class="text-sm font-bold text-gray-900 dark:text-white cursor-pointer select-none"
                                                >
                                                    Medical Clearance has been
                                                    presented and verified by
                                                    attending dentist
                                                    <span class="text-rose-600"
                                                        >*</span
                                                    >
                                                </label>
                                                <p
                                                    class="text-xs text-gray-500 dark:text-slate-400 mt-0.5"
                                                >
                                                    Confirm that the patient
                                                    presented written clearance
                                                    from their attending
                                                    physician / specialist
                                                    authorizing dental care.
                                                </p>
                                            </div>
                                        </div>

                                        <p
                                            v-if="
                                                form.errors
                                                    .medical_clearance_presented
                                            "
                                            class="text-xs text-rose-600 dark:text-rose-400 font-semibold flex items-center gap-1"
                                        >
                                            <i
                                                class="pi pi-times-circle text-[11px]"
                                            ></i>
                                            {{
                                                form.errors
                                                    .medical_clearance_presented
                                            }}
                                        </p>

                                        <!-- Clearance details input when checkbox is checked -->
                                        <div
                                            v-if="
                                                form.medical_clearance_presented
                                            "
                                            class="grid grid-cols-1 sm:grid-cols-2 gap-4 pt-3 border-t border-dashed border-gray-200 dark:border-slate-700"
                                        >
                                            <div>
                                                <label
                                                    class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1"
                                                >
                                                    Clearing Physician / Doctor
                                                    Name
                                                    <span class="text-rose-500"
                                                        >*</span
                                                    >
                                                </label>
                                                <input
                                                    v-model="
                                                        form.medical_clearance_doctor
                                                    "
                                                    type="text"
                                                    placeholder="e.g. Dr. Manuel Santos, MD (Cardiologist)"
                                                    required
                                                    class="w-full rounded-lg border-gray-300 dark:border-slate-700 dark:bg-slate-900 dark:text-white text-xs py-2 shadow-xs focus:border-teal-500 focus:ring-teal-500"
                                                />
                                                <p
                                                    v-if="
                                                        form.errors
                                                            .medical_clearance_doctor
                                                    "
                                                    class="text-xs text-rose-600 dark:text-rose-400 mt-1"
                                                >
                                                    {{
                                                        form.errors
                                                            .medical_clearance_doctor
                                                    }}
                                                </p>
                                            </div>

                                            <div>
                                                <label
                                                    class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1"
                                                >
                                                    Clearance Presentation Date
                                                    <span class="text-rose-500"
                                                        >*</span
                                                    >
                                                </label>
                                                <input
                                                    v-model="
                                                        form.medical_clearance_date
                                                    "
                                                    type="date"
                                                    required
                                                    class="w-full rounded-lg border-gray-300 dark:border-slate-700 dark:bg-slate-900 dark:text-white text-xs py-2 shadow-xs focus:border-teal-500 focus:ring-teal-500"
                                                />
                                            </div>

                                            <div class="sm:col-span-2">
                                                <label
                                                    class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1"
                                                >
                                                    Clearance Notes / Medical
                                                    Precautions
                                                </label>
                                                <input
                                                    v-model="
                                                        form.medical_clearance_notes
                                                    "
                                                    type="text"
                                                    placeholder="e.g. BP 120/80; Cleared for extraction; Epinephrine precautions recommended."
                                                    class="w-full rounded-lg border-gray-300 dark:border-slate-700 dark:bg-slate-900 dark:text-white text-xs py-2 shadow-xs focus:border-teal-500 focus:ring-teal-500"
                                                />
                                            </div>
                                        </div>

                                        <!-- Treatment Block Notice if clearance not yet presented -->
                                        <div
                                            v-else
                                            class="p-3.5 rounded-xl bg-rose-50 dark:bg-rose-950/40 border border-rose-200 dark:border-rose-800 text-rose-900 dark:text-rose-200 text-xs font-semibold flex items-center gap-2.5"
                                        >
                                            <i
                                                class="pi pi-ban text-rose-600 text-base shrink-0"
                                            ></i>
                                            <span>
                                                <strong
                                                    >Treatment Blocked:</strong
                                                >
                                                Dentist cannot administer
                                                treatment until the patient
                                                presents a verified medical
                                                clearance from their physician.
                                            </span>
                                        </div>
                                    </div>
                                </div>

                                <!-- Case B: Patient has NO medical conditions -->
                                <div
                                    v-else
                                    class="rounded-xl border border-emerald-200 dark:border-emerald-800/60 bg-emerald-50/60 dark:bg-emerald-950/20 p-3.5 flex items-center justify-between text-xs text-emerald-900 dark:text-emerald-300"
                                >
                                    <div
                                        class="flex items-center gap-2.5 font-medium"
                                    >
                                        <i
                                            class="pi pi-check-circle text-emerald-600 text-base"
                                        ></i>
                                        <span
                                            >No chronic systemic medical
                                            conditions recorded for this
                                            patient. Medical clearance not
                                            required.</span
                                        >
                                    </div>
                                    <span
                                        class="font-bold text-[11px] uppercase tracking-wider text-emerald-700 dark:text-emerald-400 bg-white dark:bg-slate-900 px-2.5 py-0.5 rounded-full border border-emerald-200 dark:border-emerald-800"
                                    >
                                        Clear to Treat
                                    </span>
                                </div>
                            </div>
                        </div>

                        <hr class="border-gray-100" />

                        <!-- Section: Clinical Procedure Details -->
                        <div>
                            <h3
                                class="text-sm font-bold uppercase tracking-wider text-teal-700 mb-4 flex items-center gap-2"
                            >
                                <i class="pi pi-heart text-sm"></i> Procedure &
                                Tooth Notation
                            </h3>

                            <div class="space-y-4">
                                <div
                                    class="grid grid-cols-1 sm:grid-cols-2 gap-5"
                                >
                                    <!-- Procedure Name -->
                                    <div>
                                        <label
                                            class="block text-sm font-semibold text-gray-700 mb-1"
                                        >
                                            Dental Procedure
                                            <span class="text-red-500">*</span>
                                        </label>
                                        <select
                                            v-model="form.procedure_name"
                                            @change="onProcedureChange"
                                            required
                                            class="w-full rounded-lg border-gray-300 shadow-sm focus:border-teal-500 focus:ring-teal-500 text-sm"
                                        >
                                            <option
                                                v-for="srv in availableServices"
                                                :key="srv"
                                                :value="srv"
                                            >
                                                {{ srv }}
                                            </option>
                                        </select>
                                        <p
                                            v-if="form.errors.procedure_name"
                                            class="text-xs text-red-600 mt-1"
                                        >
                                            {{ form.errors.procedure_name }}
                                        </p>
                                    </div>

                                    <!-- Tooth Number / Area -->
                                    <div>
                                        <label
                                            class="block text-sm font-semibold text-gray-700 mb-1"
                                        >
                                            Tooth # / Dental Area
                                        </label>
                                        <input
                                            v-model="form.tooth_number"
                                            type="text"
                                            placeholder="e.g. Tooth 14, 21, or Full Mouth"
                                            class="w-full rounded-lg border-gray-300 shadow-sm focus:border-teal-500 focus:ring-teal-500 text-sm"
                                        />
                                        <p
                                            v-if="form.errors.tooth_number"
                                            class="text-xs text-red-600 mt-1"
                                        >
                                            {{ form.errors.tooth_number }}
                                        </p>
                                    </div>
                                </div>

                                <!-- Quick Tooth Chips -->
                                <div>
                                    <span
                                        class="text-xs text-gray-500 block mb-1 font-medium"
                                        >Quick suggestions:</span
                                    >
                                    <div class="flex flex-wrap gap-1.5">
                                        <button
                                            v-for="chip in commonToothSelections"
                                            :key="chip"
                                            type="button"
                                            @click="setTooth(chip)"
                                            class="px-2.5 py-0.5 rounded-md text-xs border border-gray-200 bg-gray-50 hover:bg-teal-50 hover:border-teal-300 hover:text-teal-800 transition"
                                        >
                                            {{ chip }}
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <hr class="border-gray-100" />

                        <!-- Section: Date, Status & Cost -->
                        <div>
                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-5">
                                <!-- Treatment Date -->
                                <div>
                                    <label
                                        class="block text-sm font-semibold text-gray-700 mb-1"
                                    >
                                        Treatment Date
                                        <span class="text-red-500">*</span>
                                    </label>
                                    <input
                                        v-model="form.treatment_date"
                                        type="date"
                                        required
                                        class="w-full rounded-lg border-gray-300 shadow-sm focus:border-teal-500 focus:ring-teal-500 text-sm"
                                    />
                                    <p
                                        v-if="form.errors.treatment_date"
                                        class="text-xs text-red-600 mt-1"
                                    >
                                        {{ form.errors.treatment_date }}
                                    </p>
                                </div>

                                <!-- Status -->
                                <div>
                                    <label
                                        class="block text-sm font-semibold text-gray-700 mb-1"
                                    >
                                        Status
                                        <span class="text-red-500">*</span>
                                    </label>
                                    <select
                                        v-model="form.status"
                                        required
                                        class="w-full rounded-lg border-gray-300 shadow-sm focus:border-teal-500 focus:ring-teal-500 text-sm"
                                    >
                                        <option value="completed">
                                            Completed
                                        </option>
                                        <option value="in_progress">
                                            In Progress (Multi-visit)
                                        </option>
                                        <option value="referred">
                                            Referred to Specialist
                                        </option>
                                    </select>
                                    <p
                                        v-if="form.errors.status"
                                        class="text-xs text-red-600 mt-1"
                                    >
                                        {{ form.errors.status }}
                                    </p>
                                </div>

                                <!-- Cost / Fee (Strictly aligned to procedure catalog price) -->
                                <div>
                                    <div
                                        class="flex items-center justify-between mb-1"
                                    >
                                        <label
                                            class="block text-sm font-semibold text-gray-700 dark:text-gray-200"
                                        >
                                            Procedure Fee (₱)
                                            <span class="text-red-500">*</span>
                                        </label>
                                        <span
                                            v-if="currentFeeConfig.isFixed"
                                            class="inline-flex items-center gap-1 text-[11px] font-bold uppercase tracking-wider text-teal-800 bg-teal-50 dark:bg-teal-950/60 dark:text-teal-300 px-2 py-0.5 rounded border border-teal-200 dark:border-teal-800"
                                        >
                                            <i
                                                class="pi pi-lock text-[10px]"
                                            ></i>
                                            Locked Fixed Fee
                                        </span>
                                    </div>

                                    <div class="relative">
                                        <span
                                            class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-500 text-sm font-semibold"
                                            >₱</span
                                        >
                                        <!-- Fixed price: Readonly locked input -->
                                        <input
                                            v-if="currentFeeConfig.isFixed"
                                            :value="currentFeeConfig.min"
                                            type="number"
                                            readonly
                                            tabindex="-1"
                                            class="w-full pl-8 rounded-lg border-gray-300 dark:border-slate-700 bg-gray-100 dark:bg-slate-800 text-gray-700 dark:text-slate-300 text-sm font-bold shadow-xs cursor-not-allowed select-none"
                                            title="Fixed procedure fee aligned with clinic procedure catalog. Cannot be edited."
                                        />
                                        <!-- Variable price: Strictly bounded between min and max -->
                                        <input
                                            v-else
                                            v-model.number="form.cost"
                                            type="number"
                                            :min="currentFeeConfig.min"
                                            :max="currentFeeConfig.max"
                                            step="50"
                                            required
                                            @keydown="onFeeKeydown"
                                            @input="validateAndClampFee(false, $event)"
                                            @blur="validateAndClampFee(true, $event)"
                                            @change="validateAndClampFee(true, $event)"
                                            :class="[
                                                'w-full pl-8 rounded-lg border shadow-xs text-sm font-semibold text-gray-900 dark:text-white dark:bg-slate-900 transition-colors',
                                                feeError
                                                    ? 'border-red-500 focus:border-red-500 focus:ring-red-500'
                                                    : 'border-gray-300 dark:border-slate-700 focus:border-teal-500 focus:ring-teal-500',
                                            ]"
                                        />
                                    </div>

                                    <!-- Helper note on fee alignment rule -->
                                    <p
                                        v-if="currentFeeConfig.isFixed"
                                        class="text-xs text-teal-700 dark:text-teal-400 mt-1.5 flex items-center gap-1 font-medium"
                                    >
                                        <i class="pi pi-lock text-[11px]"></i>
                                        Procedure fee is locked to the official dental procedure catalog price.
                                    </p>
                                    <p
                                        v-else-if="!feeError"
                                        class="text-xs text-teal-700 dark:text-teal-400 mt-1.5 flex items-center gap-1 font-medium"
                                    >
                                        <i class="pi pi-shield text-[11px]"></i>
                                        Fee must align with the dental procedure catalog price.
                                    </p>

                                    <!-- Validation Error Warnings -->
                                    <p
                                        v-if="feeError"
                                        class="text-xs text-red-600 dark:text-red-400 mt-1.5 flex items-center gap-1 font-semibold"
                                    >
                                        <i
                                            class="pi pi-exclamation-triangle text-[11px]"
                                        ></i>
                                        {{ feeError }}
                                    </p>
                                    <p
                                        v-else-if="form.errors.cost"
                                        class="text-xs text-red-600 dark:text-red-400 mt-1.5"
                                    >
                                        {{ form.errors.cost }}
                                    </p>
                                </div>
                            </div>
                        </div>

                        <hr class="border-gray-100" />

                        <!-- Section: Clinical Findings & Notes -->
                        <div>
                            <h3
                                class="text-sm font-bold uppercase tracking-wider text-teal-700 mb-4 flex items-center gap-2"
                            >
                                <i class="pi pi-file-edit text-sm"></i> Clinical
                                Findings & Notes
                            </h3>

                            <div class="space-y-4">
                                <!-- Diagnosis -->
                                <div>
                                    <label
                                        class="block text-sm font-semibold text-gray-700 mb-1"
                                    >
                                        Clinical Diagnosis
                                    </label>
                                    <input
                                        v-model="form.diagnosis"
                                        type="text"
                                        placeholder="e.g. Chronic irreversible pulpitis, Moderate calculus accumulation, Class II dental caries"
                                        class="w-full rounded-lg border-gray-300 shadow-sm focus:border-teal-500 focus:ring-teal-500 text-sm"
                                    />
                                    <p
                                        v-if="form.errors.diagnosis"
                                        class="text-xs text-red-600 mt-1"
                                    >
                                        {{ form.errors.diagnosis }}
                                    </p>
                                </div>

                                <!-- Procedure Notes -->
                                <div>
                                    <label
                                        class="block text-sm font-semibold text-gray-700 mb-1"
                                    >
                                        Procedure Notes & Observations
                                    </label>
                                    <textarea
                                        v-model="form.treatment_notes"
                                        rows="3"
                                        placeholder="Detailed clinical notes (materials used, anesthetics given, patient tolerance, etc.)..."
                                        class="w-full rounded-lg border-gray-300 shadow-sm focus:border-teal-500 focus:ring-teal-500 text-sm"
                                    ></textarea>
                                    <p
                                        v-if="form.errors.treatment_notes"
                                        class="text-xs text-red-600 mt-1"
                                    >
                                        {{ form.errors.treatment_notes }}
                                    </p>
                                </div>

                                <!-- Prescription & Aftercare Instructions -->
                                <div>
                                    <label
                                        class="block text-sm font-semibold text-teal-800 mb-1 items-center gap-1.5"
                                    >
                                        <i class="pi pi-file text-xs"></i>
                                        Prescription & Aftercare Instructions
                                    </label>
                                    <textarea
                                        v-model="form.prescription"
                                        rows="2"
                                        placeholder="e.g. Amoxicillin 500mg TID for 7 days, Mefenamic Acid 500mg PRN for pain, soft diet for 48 hours..."
                                        class="w-full rounded-lg border-teal-300 bg-teal-50/30 shadow-sm focus:border-teal-500 focus:ring-teal-500 text-sm"
                                    ></textarea>
                                    <p
                                        v-if="form.errors.prescription"
                                        class="text-xs text-red-600 mt-1"
                                    >
                                        {{ form.errors.prescription }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Footer / Submit Button with Clearance Verification Gate -->
                    <div
                        class="px-6 py-4 bg-gray-50 dark:bg-slate-900 border-t border-gray-100 dark:border-slate-800 flex flex-col sm:flex-row items-center justify-between gap-3"
                    >
                        <div class="w-full sm:w-auto">
                            <!-- Warning text if treatment blocked due to clearance -->
                            <p
                                v-if="
                                    patientHasMedicalConditions &&
                                    !canSubmitTreatment
                                "
                                class="text-xs text-rose-600 dark:text-rose-400 font-bold flex items-center gap-1.5"
                            >
                                <i class="pi pi-ban text-rose-600 text-sm"></i>
                                <span
                                    >Medical clearance must be presented before
                                    giving treatment.</span
                                >
                            </p>
                            <p
                                v-else-if="
                                    patientHasMedicalConditions &&
                                    canSubmitTreatment
                                "
                                class="text-xs text-emerald-700 dark:text-emerald-400 font-bold flex items-center gap-1.5"
                            >
                                <i
                                    class="pi pi-check-circle text-emerald-600 text-sm"
                                ></i>
                                <span
                                    >Medical clearance verified by dentist.
                                    Authorized to proceed.</span
                                >
                            </p>
                        </div>

                        <div
                            class="flex items-center gap-3 w-full sm:w-auto justify-end"
                        >
                            <Link
                                :href="
                                    preselected_patient_id
                                        ? route(
                                              'patients.show',
                                              preselected_patient_id,
                                          )
                                        : route('treatments.index')
                                "
                                class="px-4 py-2 border border-gray-300 dark:border-slate-700 rounded-lg text-sm font-semibold text-gray-700 dark:text-gray-300 hover:bg-white dark:hover:bg-slate-800 transition"
                            >
                                Cancel
                            </Link>
                            <button
                                type="submit"
                                :disabled="
                                    form.processing || !canSubmitTreatment
                                "
                                :class="[
                                    'inline-flex items-center gap-2 rounded-lg px-6 py-2.5 text-sm font-semibold text-white shadow-sm transition focus:outline-none focus:ring-2 focus:ring-offset-2',
                                    !canSubmitTreatment
                                        ? 'bg-gray-400 dark:bg-slate-700 cursor-not-allowed opacity-60'
                                        : 'bg-teal-700 hover:bg-teal-800 focus:ring-teal-500',
                                ]"
                            >
                                <i
                                    v-if="form.processing"
                                    class="pi pi-spin pi-spinner text-xs"
                                ></i>
                                <i
                                    v-else-if="!canSubmitTreatment"
                                    class="pi pi-lock text-xs"
                                ></i>
                                <i v-else class="pi pi-check text-xs"></i>
                                <span v-if="!canSubmitTreatment">
                                    Present Medical Clearance to Proceed
                                </span>
                                <span v-else>Save Treatment Record</span>
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
