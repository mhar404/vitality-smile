<script setup>
import { computed } from "vue";
import { Link } from "@inertiajs/vue3";

const props = defineProps({
    patient: {
        type: Object,
        required: true,
    },
    medicalHistory: {
        type: Object,
        default: null,
    },
});

const criticalAlerts = computed(() => {
    return props.medicalHistory?.critical_alerts || [];
});

const conditions = computed(() => {
    if (!props.medicalHistory) return [];
    const list = [];
    if (props.medicalHistory.hypertension) list.push("Hypertension");
    if (props.medicalHistory.diabetes) list.push("Diabetes");
    if (props.medicalHistory.heart_disease) list.push("Heart / Cardiac Disease");
    if (props.medicalHistory.bleeding_disorders) list.push("Bleeding Disorders");
    if (props.medicalHistory.asthma) list.push("Asthma");
    if (props.medicalHistory.kidney_disease) list.push("Kidney Disease");
    if (props.medicalHistory.liver_disease) list.push("Liver Disease");
    if (props.medicalHistory.hepatitis) list.push("Hepatitis");
    if (props.medicalHistory.epilepsy) list.push("Epilepsy / Seizures");
    if (props.medicalHistory.is_pregnant) list.push("Pregnant");
    return list;
});
</script>

<template>
    <div class="space-y-6">
        <!-- Top Medical Alert Warning (If conditions exist) -->
        <div
            v-if="criticalAlerts.length > 0"
            class="flex items-start gap-4 rounded-2xl border border-red-200 bg-red-50/80 p-5 shadow-sm"
        >
            <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-red-600 text-white shadow-sm">
                <i class="pi pi-exclamation-triangle text-lg"></i>
            </div>
            <div class="min-w-0 flex-1">
                <h4 class="text-sm font-bold text-red-900">
                    High-Priority Medical Alerts on Record
                </h4>
                <p class="mt-0.5 text-xs text-red-700">
                    Our dental surgeons and staff consult these alerts prior to local anesthesia, tooth extractions, and restorative procedures.
                </p>
                <div class="mt-3 flex flex-wrap gap-2">
                    <span
                        v-for="(alert, idx) in criticalAlerts"
                        :key="idx"
                        class="inline-flex items-center gap-1.5 rounded-lg border border-red-300 bg-white px-2.5 py-1 text-xs font-bold text-red-800 shadow-xs"
                    >
                        <i class="pi pi-shield text-[10px] text-red-600"></i>
                        {{ alert.label }}
                    </span>
                </div>
            </div>
        </div>

        <div
            v-else
            class="flex items-center gap-3 rounded-2xl border border-emerald-200 bg-emerald-50/80 p-4 shadow-sm"
        >
            <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-emerald-600 text-white">
                <i class="pi pi-check text-sm"></i>
            </div>
            <div>
                <h4 class="text-xs font-bold text-emerald-900">
                    No High-Risk Medical Alerts Flagged
                </h4>
                <p class="text-[11px] text-emerald-700">
                    No critical bleeding, cardiovascular, or severe allergic alerts are currently logged in your file.
                </p>
            </div>
        </div>

        <!-- 3 Key Clinical Metric Cards -->
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
            <!-- Blood Type -->
            <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Blood Type</span>
                    <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-rose-50 text-rose-600">
                        <i class="pi pi-heart text-sm"></i>
                    </span>
                </div>
                <div class="mt-3 flex items-baseline gap-2">
                    <span class="text-2xl font-extrabold text-gray-900">
                        {{ medicalHistory?.blood_type || 'Unspecified' }}
                    </span>
                </div>
                <p class="mt-1 text-xs text-gray-400">
                    Useful for surgical interventions & emergencies
                </p>
            </div>

            <!-- Allergies Summary -->
            <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Allergies</span>
                    <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-amber-50 text-amber-600">
                        <i class="pi pi-shield text-sm"></i>
                    </span>
                </div>
                <div class="mt-3">
                    <span
                        v-if="medicalHistory?.has_allergies || medicalHistory?.allergies"
                        class="inline-flex items-center rounded-md bg-amber-100 px-2 py-1 text-xs font-bold text-amber-800"
                    >
                        {{ medicalHistory.allergies || 'Present (Listed in Record)' }}
                    </span>
                    <span v-else class="text-sm font-semibold text-gray-700">
                        No Known Allergies (NKA)
                    </span>
                </div>
                <p class="mt-1 text-xs text-gray-400">
                    Latex, Penicillin, Anesthetic precautions
                </p>
            </div>

            <!-- Pre-existing Conditions -->
            <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Conditions</span>
                    <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-teal-50 text-teal-600">
                        <i class="pi pi-file text-sm"></i>
                    </span>
                </div>
                <div class="mt-3">
                    <span class="text-2xl font-extrabold text-teal-800">
                        {{ conditions.length }}
                    </span>
                    <span class="text-xs font-medium text-gray-500 ml-1">recorded</span>
                </div>
                <p class="mt-1 text-xs text-gray-400 truncate">
                    {{ conditions.length ? conditions.join(', ') : 'None documented' }}
                </p>
            </div>
        </div>

        <!-- Emergency & Primary Care Contact Grid -->
        <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
            <!-- Emergency Contact -->
            <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm">
                <div class="flex items-center gap-2.5 border-b border-gray-100 pb-3">
                    <span class="flex h-7 w-7 items-center justify-center rounded-lg bg-red-50 text-red-600">
                        <i class="pi pi-phone text-xs"></i>
                    </span>
                    <h3 class="text-sm font-bold text-gray-900">Designated Emergency Contact</h3>
                </div>

                <div class="mt-4 space-y-3">
                    <div class="flex items-center justify-between text-xs">
                        <span class="text-gray-500">Full Name</span>
                        <span class="font-semibold text-gray-900">
                            {{ medicalHistory?.emergency_contact_name || patient.guardian || 'Not specified' }}
                        </span>
                    </div>
                    <div class="flex items-center justify-between text-xs">
                        <span class="text-gray-500">Relationship</span>
                        <span class="font-medium text-gray-700">
                            {{ medicalHistory?.emergency_contact_relation || (patient.guardian ? 'Guardian / Parent' : 'Not specified') }}
                        </span>
                    </div>
                    <div class="flex items-center justify-between text-xs">
                        <span class="text-gray-500">Contact Number</span>
                        <span class="font-mono font-medium text-teal-700">
                            {{ medicalHistory?.emergency_contact_number || patient.contact_number || 'None on file' }}
                        </span>
                    </div>
                </div>
            </div>

            <!-- Primary Care Physician -->
            <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm">
                <div class="flex items-center gap-2.5 border-b border-gray-100 pb-3">
                    <span class="flex h-7 w-7 items-center justify-center rounded-lg bg-teal-50 text-teal-600">
                        <i class="pi pi-user text-xs"></i>
                    </span>
                    <h3 class="text-sm font-bold text-gray-900">Primary Care Physician</h3>
                </div>

                <div class="mt-4 space-y-3">
                    <div class="flex items-center justify-between text-xs">
                        <span class="text-gray-500">Physician Name</span>
                        <span class="font-semibold text-gray-900">
                            {{ medicalHistory?.physician_name || 'Dr. Valdez / Clinic General' }}
                        </span>
                    </div>
                    <div class="flex items-center justify-between text-xs">
                        <span class="text-gray-500">Clinic / Hospital</span>
                        <span class="font-medium text-gray-700">
                            Vitality Smile Partner Network
                        </span>
                    </div>
                    <div class="flex items-center justify-between text-xs">
                        <span class="text-gray-500">Contact / Phone</span>
                        <span class="font-mono font-medium text-gray-700">
                            {{ medicalHistory?.physician_contact || '(+63) 912-345-6789' }}
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Call to Action Banner to Edit Detailed Intake -->
        <div class="rounded-2xl border border-teal-200 bg-gradient-to-r from-teal-50 to-cyan-50 p-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div class="space-y-1">
                <h4 class="text-sm font-bold text-teal-950">
                    Comprehensive Clinical Medical Intake Form
                </h4>
                <p class="text-xs text-teal-800 max-w-xl">
                    Need to update your current medications, past oral surgeries, pregnancy status, or detailed health conditions? Access the full clinical intake form below.
                </p>
            </div>
            <div class="flex items-center gap-2 shrink-0">
                <Link
                    :href="route('medical-histories.my')"
                    class="inline-flex items-center gap-1.5 rounded-xl bg-teal-700 px-4 py-2.5 text-xs font-bold text-white shadow-sm transition hover:bg-teal-800"
                >
                    <i class="pi pi-eye text-xs"></i>
                    View Full Medical History
                </Link>
                <Link
                    :href="route('medical-histories.edit', patient.id)"
                    class="inline-flex items-center gap-1.5 rounded-xl border border-teal-300 bg-white px-4 py-2.5 text-xs font-bold text-teal-800 shadow-xs transition hover:bg-teal-50"
                >
                    <i class="pi pi-pencil text-xs"></i>
                    Update Details
                </Link>
            </div>
        </div>
    </div>
</template>

