<script setup>
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import { Head, Link } from "@inertiajs/vue3";

const props = defineProps({
    patient: Object,
    medicalHistory: Object,
});
</script>

<template>
    <Head title="My Medical History" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="font-bold text-xl text-gray-900 leading-tight">
                        My Medical History
                    </h2>
                    <p class="text-xs text-gray-500 mt-1">
                        Your health conditions, allergies, and emergency information on file with Vitality Smile.
                    </p>
                </div>
                <Link
                    :href="route('dashboard')"
                    class="text-gray-600 hover:text-gray-900 text-sm font-medium flex items-center gap-1.5"
                >
                    <i class="pi pi-arrow-left text-xs"></i> Back to Dashboard
                </Link>
            </div>
        </template>

        <div class="py-8">
            <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-6">

                <!-- Alert summary if critical conditions exist -->
                <div
                    v-if="medicalHistory?.critical_alerts && medicalHistory.critical_alerts.length > 0"
                    class="bg-amber-50 border border-amber-200 rounded-2xl p-5 flex items-start gap-3.5"
                >
                    <div class="h-10 w-10 bg-amber-500 text-white rounded-full flex items-center justify-center shrink-0">
                        <i class="pi pi-shield text-lg"></i>
                    </div>
                    <div>
                        <h4 class="font-bold text-amber-900 text-sm">Medical Alerts on File</h4>
                        <p class="text-xs text-amber-800 mt-0.5">
                            Our dental team takes special clinical precautions for the following recorded conditions:
                        </p>
                        <div class="mt-2.5 flex flex-wrap gap-2">
                            <span
                                v-for="(alert, idx) in medicalHistory.critical_alerts"
                                :key="idx"
                                :class="[
                                    alert.type === 'danger' ? 'bg-red-100 text-red-800 border-red-200' : 'bg-amber-100 text-amber-800 border-amber-200',
                                    'px-2.5 py-0.5 rounded-full text-xs font-bold border'
                                ]"
                            >
                                {{ alert.label }}
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Physician Medical Clearance Requirements Card -->
                <div
                    v-if="medicalHistory?.critical_alerts && medicalHistory.critical_alerts.length > 0"
                    class="rounded-2xl border-2 border-teal-200 bg-white p-6 shadow-sm space-y-4"
                >
                    <div class="flex items-center justify-between border-b border-gray-100 pb-3">
                        <div class="flex items-center gap-2.5">
                            <div class="h-8 w-8 rounded-lg bg-teal-100 text-teal-700 flex items-center justify-center">
                                <i class="pi pi-file-check text-sm font-bold"></i>
                            </div>
                            <div>
                                <h3 class="text-sm font-bold text-gray-900">Physician Medical Clearance Requirements</h3>
                                <p class="text-xs text-gray-500">Official protocol for patients with systemic medical conditions</p>
                            </div>
                        </div>
                        <span
                            :class="[
                                medicalHistory?.medical_clearance_presented
                                    ? 'bg-emerald-100 text-emerald-800 border-emerald-300'
                                    : 'bg-rose-100 text-rose-800 border-rose-300',
                                'px-3 py-1 rounded-full text-xs font-bold border'
                            ]"
                        >
                            {{ medicalHistory?.medical_clearance_presented ? 'Clearance Verified' : 'Clearance Required' }}
                        </span>
                    </div>

                    <!-- Verified Status Details -->
                    <div
                        v-if="medicalHistory?.medical_clearance_presented"
                        class="p-4 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-900 space-y-2 text-xs"
                    >
                        <div class="flex items-center gap-2 font-bold text-sm text-emerald-950">
                            <i class="pi pi-check-circle text-emerald-600 text-base"></i>
                            <span>Medical Clearance Verified by Dentist</span>
                        </div>
                        <p class="leading-relaxed">
                            Clearing Physician / Specialist: <strong class="underline">{{ medicalHistory.medical_clearance_doctor || 'Attending Physician' }}</strong>
                            <span v-if="medicalHistory.medical_clearance_date"> (Date: {{ new Date(medicalHistory.medical_clearance_date).toLocaleDateString() }})</span>
                        </p>
                        <p v-if="medicalHistory.medical_clearance_notes" class="text-gray-700 bg-white/80 p-2.5 rounded-lg border border-emerald-100 italic">
                            "{{ medicalHistory.medical_clearance_notes }}"
                        </p>
                    </div>

                    <!-- Pending Clearance Instructions -->
                    <div
                        v-else
                        class="p-4 rounded-xl bg-amber-50 border border-amber-200 space-y-3 text-xs"
                    >
                        <div class="flex items-center gap-2 font-bold text-sm text-amber-950">
                            <i class="pi pi-exclamation-circle text-amber-600 text-base"></i>
                            <span>Action Needed: Obtain Written Medical Clearance</span>
                        </div>
                        <p class="text-amber-900 leading-relaxed">
                            To ensure your safety during dental procedures (such as local anesthesia, tooth restoration, and extractions), our clinic requires a signed medical clearance from your attending physician before your dentist can begin treatment.
                        </p>
                        <div class="bg-white/90 p-3 rounded-lg border border-amber-200 text-gray-800 space-y-1.5">
                            <span class="font-bold text-[11px] uppercase tracking-wider text-amber-800 block">Clearance Letter Checklist:</span>
                            <ul class="space-y-1 pl-1">
                                <li class="flex items-center gap-1.5">
                                    <i class="pi pi-check text-teal-600 text-[10px]"></i>
                                    <span>Physician’s full name, medical specialty, and clinic contact info.</span>
                                </li>
                                <li class="flex items-center gap-1.5">
                                    <i class="pi pi-check text-teal-600 text-[10px]"></i>
                                    <span>Statement clearing you to undergo dental treatment and anesthesia.</span>
                                </li>
                                <li class="flex items-center gap-1.5">
                                    <i class="pi pi-check text-teal-600 text-[10px]"></i>
                                    <span>Specific precautions (e.g. antibiotic prophylaxis or blood pressure monitoring).</span>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>

                <!-- Main Content Card -->
                <div class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden">
                    <div class="p-6 sm:p-8 space-y-6">

                        <div class="flex items-center justify-between border-b border-gray-100 pb-4">
                            <div>
                                <h3 class="text-lg font-bold text-gray-900">Health Profile</h3>
                                <p class="text-xs text-gray-500">Record last updated: {{ medicalHistory ? new Date(medicalHistory.updated_at).toLocaleDateString() : 'Not yet documented' }}</p>
                            </div>
                            <Link
                                :href="route('medical-histories.edit', patient.id)"
                                class="inline-flex items-center gap-1.5 px-4 py-2 rounded-lg bg-teal-700 text-white text-xs font-semibold hover:bg-teal-800 transition shadow-sm"
                            >
                                <i class="pi pi-pencil text-xs"></i>
                                {{ medicalHistory ? 'Update Info' : 'Complete Intake' }}
                            </Link>
                        </div>

                        <div v-if="medicalHistory" class="space-y-6">
                            <!-- Blood Type & Allergies -->
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div class="bg-gray-50 p-4 rounded-xl border border-gray-100">
                                    <span class="text-xs font-semibold text-gray-500 uppercase tracking-wider block mb-1">Blood Type</span>
                                    <span class="text-xl font-bold text-teal-800">{{ medicalHistory.blood_type || 'Unknown / Not recorded' }}</span>
                                </div>

                                <div :class="medicalHistory.has_allergies ? 'bg-red-50 border-red-200 text-red-900' : 'bg-green-50 border-green-200 text-green-900'" class="p-4 rounded-xl border">
                                    <span class="text-xs font-semibold uppercase tracking-wider block mb-1">Drug & Substance Allergies</span>
                                    <p v-if="medicalHistory.has_allergies || medicalHistory.allergies" class="font-bold text-sm">
                                        {{ medicalHistory.allergies }}
                                    </p>
                                    <p v-else class="text-sm font-semibold text-green-800 flex items-center gap-1">
                                        <i class="pi pi-check text-xs"></i> No Known Drug Allergies (NKDA)
                                    </p>
                                </div>
                            </div>

                            <!-- Systemic Conditions -->
                            <div>
                                <h4 class="text-xs font-bold text-gray-500 uppercase tracking-wider mb-2">Medical Conditions</h4>
                                <div class="flex flex-wrap gap-2">
                                    <span v-if="medicalHistory.hypertension" class="px-2.5 py-1 rounded-lg text-xs font-semibold bg-gray-100 text-gray-800">Hypertension</span>
                                    <span v-if="medicalHistory.diabetes" class="px-2.5 py-1 rounded-lg text-xs font-semibold bg-gray-100 text-gray-800">Diabetes</span>
                                    <span v-if="medicalHistory.heart_disease" class="px-2.5 py-1 rounded-lg text-xs font-semibold bg-gray-100 text-gray-800">Heart Condition</span>
                                    <span v-if="medicalHistory.bleeding_disorders" class="px-2.5 py-1 rounded-lg text-xs font-semibold bg-gray-100 text-gray-800">Bleeding Disorder</span>
                                    <span v-if="medicalHistory.asthma" class="px-2.5 py-1 rounded-lg text-xs font-semibold bg-gray-100 text-gray-800">Asthma</span>
                                    <span v-if="medicalHistory.kidney_disease" class="px-2.5 py-1 rounded-lg text-xs font-semibold bg-gray-100 text-gray-800">Kidney Disease</span>
                                    <span v-if="medicalHistory.liver_disease" class="px-2.5 py-1 rounded-lg text-xs font-semibold bg-gray-100 text-gray-800">Liver Disease</span>
                                    <span v-if="medicalHistory.hepatitis" class="px-2.5 py-1 rounded-lg text-xs font-semibold bg-gray-100 text-gray-800">Hepatitis</span>
                                    <span v-if="medicalHistory.epilepsy" class="px-2.5 py-1 rounded-lg text-xs font-semibold bg-gray-100 text-gray-800">Epilepsy</span>
                                    <span v-if="medicalHistory.is_pregnant" class="px-2.5 py-1 rounded-lg text-xs font-semibold bg-blue-100 text-blue-800">Pregnant / Nursing</span>
                                    <span
                                        v-if="!medicalHistory.hypertension && !medicalHistory.diabetes && !medicalHistory.heart_disease && !medicalHistory.bleeding_disorders && !medicalHistory.asthma && !medicalHistory.kidney_disease && !medicalHistory.liver_disease && !medicalHistory.hepatitis && !medicalHistory.epilepsy && !medicalHistory.is_pregnant"
                                        class="text-xs text-gray-500 italic"
                                    >
                                        No chronic conditions reported.
                                    </span>
                                </div>
                            </div>

                            <!-- Current Medications -->
                            <div v-if="medicalHistory.current_medications">
                                <h4 class="text-xs font-bold text-gray-500 uppercase tracking-wider mb-1">Maintenance Medications</h4>
                                <p class="text-sm text-gray-800 bg-gray-50 p-3 rounded-xl border border-gray-100">
                                    {{ medicalHistory.current_medications }}
                                </p>
                            </div>

                            <!-- Emergency Contact -->
                            <div v-if="medicalHistory.emergency_contact_name" class="pt-2 border-t border-gray-100">
                                <h4 class="text-xs font-bold text-gray-500 uppercase tracking-wider mb-2">Emergency Contact</h4>
                                <p class="text-sm font-bold text-gray-900">
                                    {{ medicalHistory.emergency_contact_name }}
                                    <span v-if="medicalHistory.emergency_contact_relation" class="text-xs font-normal text-gray-500">
                                        ({{ medicalHistory.emergency_contact_relation }})
                                    </span>
                                </p>
                                <p class="text-xs text-gray-600 mt-0.5">{{ medicalHistory.emergency_contact_number }}</p>
                            </div>
                        </div>

                        <!-- Empty State -->
                        <div v-else class="text-center py-10 text-gray-500">
                            <i class="pi pi-file-medical text-4xl text-gray-300 mb-3"></i>
                            <h4 class="text-base font-bold text-gray-900">No Medical History Recorded</h4>
                            <p class="text-xs text-gray-500 mt-1 max-w-sm mx-auto">
                                Completing your medical history helps our dentists provide safe, personalized care tailored to your health.
                            </p>
                            <Link
                                :href="route('medical-histories.edit', patient.id)"
                                class="mt-4 inline-flex items-center gap-1.5 px-4 py-2 rounded-lg bg-teal-700 text-white text-xs font-semibold hover:bg-teal-800 transition shadow-sm"
                            >
                                <i class="pi pi-plus text-xs"></i> Complete Medical Questionnaire
                            </Link>
                        </div>

                    </div>
                </div>

            </div>
        </div>
    </AuthenticatedLayout>
</template>
