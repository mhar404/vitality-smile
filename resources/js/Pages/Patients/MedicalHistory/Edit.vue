<script setup>
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import { Head, Link, useForm } from "@inertiajs/vue3";

const props = defineProps({
    patient: Object,
    medicalHistory: Object,
});

const form = useForm({
    blood_type: props.medicalHistory?.blood_type || "",
    has_allergies: Boolean(props.medicalHistory?.has_allergies),
    allergies: props.medicalHistory?.allergies || "",
    hypertension: Boolean(props.medicalHistory?.hypertension),
    diabetes: Boolean(props.medicalHistory?.diabetes),
    heart_disease: Boolean(props.medicalHistory?.heart_disease),
    bleeding_disorders: Boolean(props.medicalHistory?.bleeding_disorders),
    asthma: Boolean(props.medicalHistory?.asthma),
    kidney_disease: Boolean(props.medicalHistory?.kidney_disease),
    liver_disease: Boolean(props.medicalHistory?.liver_disease),
    hepatitis: Boolean(props.medicalHistory?.hepatitis),
    epilepsy: Boolean(props.medicalHistory?.epilepsy),
    is_pregnant: Boolean(props.medicalHistory?.is_pregnant),
    current_medications: props.medicalHistory?.current_medications || "",
    past_surgeries: props.medicalHistory?.past_surgeries || "",
    physician_name: props.medicalHistory?.physician_name || "",
    physician_contact: props.medicalHistory?.physician_contact || "",
    emergency_contact_name: props.medicalHistory?.emergency_contact_name || "",
    emergency_contact_number: props.medicalHistory?.emergency_contact_number || "",
    emergency_contact_relation: props.medicalHistory?.emergency_contact_relation || "",
    notes: props.medicalHistory?.notes || "",
});

const commonAllergies = [
    "Penicillin",
    "Local Anesthetics (Lidocaine / Epinephrine)",
    "Latex",
    "Aspirin / NSAIDs",
    "Sulfa Drugs",
    "Erythromycin",
    "Codeine",
];

const toggleAllergyChip = (item) => {
    form.has_allergies = true;
    let current = form.allergies ? form.allergies.split(",").map((s) => s.trim()).filter(Boolean) : [];
    if (current.includes(item)) {
        current = current.filter((s) => s !== item);
    } else {
        current.push(item);
    }
    form.allergies = current.join(", ");
    if (current.length === 0) {
        form.has_allergies = false;
    }
};

const isAllergySelected = (item) => {
    if (!form.allergies) return false;
    return form.allergies.toLowerCase().includes(item.toLowerCase());
};

const submit = () => {
    form.post(route("medical-histories.store", props.patient.id), {
        preserveScroll: true,
    });
};
</script>

<template>
    <Head :title="`Medical History - ${patient.first_name} ${patient.last_name}`" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="font-bold text-xl text-gray-900 leading-tight">
                        Dental Medical History & Health Intake
                    </h2>
                    <p class="text-xs text-gray-500 mt-1">
                        Patient: <span class="font-semibold text-gray-800">{{ patient.first_name }} {{ patient.last_name }}</span> ({{ patient.patient_id }})
                    </p>
                </div>
                <Link
                    :href="route('patients.show', patient.id)"
                    class="text-gray-600 hover:text-gray-900 text-sm font-medium flex items-center gap-1.5"
                >
                    <i class="pi pi-arrow-left text-xs"></i> Back to Patient Profile
                </Link>
            </div>
        </template>

        <div class="py-8">
            <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
                <!-- Safety notice banner -->
                <div class="mb-6 bg-amber-50 border border-amber-200 rounded-2xl p-4 flex items-start gap-3.5 text-amber-900 text-sm">
                    <div class="h-9 w-9 bg-amber-500 text-white rounded-full flex items-center justify-center shrink-0">
                        <i class="pi pi-shield text-base"></i>
                    </div>
                    <div>
                        <p class="font-bold">Clinical Safety Protocol</p>
                        <p class="text-xs text-amber-800 mt-0.5">
                            Accurate medical histories help dentists avoid drug contraindications, bleeding complications, and adverse anesthetic reactions.
                        </p>
                    </div>
                </div>

                <form @submit.prevent="submit" class="bg-white shadow-sm rounded-2xl border border-gray-200 overflow-hidden">
                    <div class="p-6 sm:p-8 space-y-8">

                        <!-- 1. Blood Type & Emergency Contact -->
                        <div>
                            <h3 class="text-sm font-bold uppercase tracking-wider text-teal-700 mb-4 flex items-center gap-2">
                                <i class="pi pi-id-card text-sm"></i> General Medical & Emergency Information
                            </h3>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 mb-1">
                                        Blood Type
                                    </label>
                                    <select
                                        v-model="form.blood_type"
                                        class="w-full rounded-lg border-gray-300 shadow-sm focus:border-teal-500 focus:ring-teal-500 text-sm"
                                    >
                                        <option value="">Unknown / Not Tested</option>
                                        <option value="A+">A+</option>
                                        <option value="A-">A-</option>
                                        <option value="B+">B+</option>
                                        <option value="B-">B-</option>
                                        <option value="AB+">AB+</option>
                                        <option value="AB-">AB-</option>
                                        <option value="O+">O+</option>
                                        <option value="O-">O-</option>
                                    </select>
                                    <p v-if="form.errors.blood_type" class="text-xs text-red-600 mt-1">{{ form.errors.blood_type }}</p>
                                </div>

                                <div v-if="patient.gender === 'Female' || patient.gender === 'Other'">
                                    <label class="block text-sm font-semibold text-gray-700 mb-1">
                                        Pregnancy Status
                                    </label>
                                    <div class="mt-2 flex items-center gap-3">
                                        <label class="inline-flex items-center gap-2 cursor-pointer">
                                            <input
                                                type="checkbox"
                                                v-model="form.is_pregnant"
                                                class="rounded border-gray-300 text-teal-600 shadow-sm focus:ring-teal-500"
                                            />
                                            <span class="text-sm text-gray-700 font-medium">Currently Pregnant or Nursing</span>
                                        </label>
                                    </div>
                                </div>
                            </div>

                            <!-- Emergency Contact Fields -->
                            <div class="mt-5 pt-4 border-t border-gray-100">
                                <p class="text-xs font-bold text-gray-600 uppercase tracking-wider mb-3">Emergency Contact</p>
                                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                                    <div>
                                        <label class="block text-xs font-medium text-gray-700 mb-1">Contact Name</label>
                                        <input
                                            v-model="form.emergency_contact_name"
                                            type="text"
                                            placeholder="e.g. Maria James"
                                            class="w-full rounded-lg border-gray-300 shadow-sm focus:border-teal-500 focus:ring-teal-500 text-sm"
                                        />
                                    </div>
                                    <div>
                                        <label class="block text-xs font-medium text-gray-700 mb-1">Phone Number</label>
                                        <input
                                            v-model="form.emergency_contact_number"
                                            type="text"
                                            placeholder="e.g. 0917-123-4567"
                                            class="w-full rounded-lg border-gray-300 shadow-sm focus:border-teal-500 focus:ring-teal-500 text-sm"
                                        />
                                    </div>
                                    <div>
                                        <label class="block text-xs font-medium text-gray-700 mb-1">Relationship</label>
                                        <input
                                            v-model="form.emergency_contact_relation"
                                            type="text"
                                            placeholder="e.g. Spouse / Mother / Sibling"
                                            class="w-full rounded-lg border-gray-300 shadow-sm focus:border-teal-500 focus:ring-teal-500 text-sm"
                                        />
                                    </div>
                                </div>
                            </div>
                        </div>

                        <hr class="border-gray-100" />

                        <!-- 2. Allergies (High Clinical Alert) -->
                        <div>
                            <div class="flex items-center justify-between mb-2">
                                <h3 class="text-sm font-bold uppercase tracking-wider text-red-600 flex items-center gap-2">
                                    <i class="pi pi-exclamation-triangle text-sm"></i> Allergies & Adverse Reactions
                                </h3>
                                <label class="inline-flex items-center gap-2 cursor-pointer">
                                    <input
                                        type="checkbox"
                                        v-model="form.has_allergies"
                                        class="rounded border-gray-300 text-red-600 shadow-sm focus:ring-red-500"
                                    />
                                    <span class="text-xs font-bold text-gray-700">Patient Has Known Allergies</span>
                                </label>
                            </div>

                            <p class="text-xs text-gray-500 mb-3">
                                Check all known drug, anesthetic, or substance allergies.
                            </p>

                            <!-- Quick Chips -->
                            <div class="flex flex-wrap gap-2 mb-3">
                                <button
                                    v-for="allergy in commonAllergies"
                                    :key="allergy"
                                    type="button"
                                    @click="toggleAllergyChip(allergy)"
                                    :class="[
                                        isAllergySelected(allergy)
                                            ? 'bg-red-100 text-red-800 border-red-300 font-bold ring-1 ring-red-400'
                                            : 'bg-gray-50 text-gray-700 border-gray-200 hover:bg-gray-100',
                                        'px-3 py-1.5 rounded-lg text-xs border transition'
                                    ]"
                                >
                                    <i v-if="isAllergySelected(allergy)" class="pi pi-check text-[10px] mr-1 text-red-600"></i>
                                    {{ allergy }}
                                </button>
                            </div>

                            <div>
                                <textarea
                                    v-model="form.allergies"
                                    rows="2"
                                    placeholder="Specify all allergies and known reaction details (e.g. Penicillin causes severe hives, Latex allergy)..."
                                    class="w-full rounded-lg border-gray-300 shadow-sm focus:border-red-500 focus:ring-red-500 text-sm"
                                ></textarea>
                                <p v-if="form.errors.allergies" class="text-xs text-red-600 mt-1">{{ form.errors.allergies }}</p>
                            </div>
                        </div>

                        <hr class="border-gray-100" />

                        <!-- 3. Systemic Medical Conditions -->
                        <div>
                            <h3 class="text-sm font-bold uppercase tracking-wider text-teal-700 mb-3 flex items-center gap-2">
                                <i class="pi pi-heart-fill text-sm"></i> Systemic Conditions & Medical History
                            </h3>
                            <p class="text-xs text-gray-500 mb-4">
                                Select any active or chronic conditions:
                            </p>

                            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-4">
                                <!-- Hypertension -->
                                <label class="flex items-start gap-3 p-3 rounded-xl border border-gray-200 hover:bg-teal-50/50 cursor-pointer transition">
                                    <input
                                        type="checkbox"
                                        v-model="form.hypertension"
                                        class="mt-0.5 rounded border-gray-300 text-teal-600 shadow-sm focus:ring-teal-500"
                                    />
                                    <div>
                                        <span class="text-sm font-bold text-gray-900 block">Hypertension</span>
                                        <span class="text-[11px] text-gray-500">High Blood Pressure</span>
                                    </div>
                                </label>

                                <!-- Diabetes -->
                                <label class="flex items-start gap-3 p-3 rounded-xl border border-gray-200 hover:bg-teal-50/50 cursor-pointer transition">
                                    <input
                                        type="checkbox"
                                        v-model="form.diabetes"
                                        class="mt-0.5 rounded border-gray-300 text-teal-600 shadow-sm focus:ring-teal-500"
                                    />
                                    <div>
                                        <span class="text-sm font-bold text-gray-900 block">Diabetes</span>
                                        <span class="text-[11px] text-gray-500">Type 1 / Type 2</span>
                                    </div>
                                </label>

                                <!-- Cardiac / Heart Disease -->
                                <label class="flex items-start gap-3 p-3 rounded-xl border border-gray-200 hover:bg-teal-50/50 cursor-pointer transition">
                                    <input
                                        type="checkbox"
                                        v-model="form.heart_disease"
                                        class="mt-0.5 rounded border-gray-300 text-teal-600 shadow-sm focus:ring-teal-500"
                                    />
                                    <div>
                                        <span class="text-sm font-bold text-gray-900 block">Heart Disease</span>
                                        <span class="text-[11px] text-gray-500">Murmurs, Angina, Stents</span>
                                    </div>
                                </label>

                                <!-- Bleeding Disorders -->
                                <label class="flex items-start gap-3 p-3 rounded-xl border border-gray-200 hover:bg-teal-50/50 cursor-pointer transition">
                                    <input
                                        type="checkbox"
                                        v-model="form.bleeding_disorders"
                                        class="mt-0.5 rounded border-gray-300 text-teal-600 shadow-sm focus:ring-teal-500"
                                    />
                                    <div>
                                        <span class="text-sm font-bold text-gray-900 block">Bleeding Disorders</span>
                                        <span class="text-[11px] text-gray-500">Hemophilia, Prolonged Bleeding</span>
                                    </div>
                                </label>

                                <!-- Asthma -->
                                <label class="flex items-start gap-3 p-3 rounded-xl border border-gray-200 hover:bg-teal-50/50 cursor-pointer transition">
                                    <input
                                        type="checkbox"
                                        v-model="form.asthma"
                                        class="mt-0.5 rounded border-gray-300 text-teal-600 shadow-sm focus:ring-teal-500"
                                    />
                                    <div>
                                        <span class="text-sm font-bold text-gray-900 block">Asthma</span>
                                        <span class="text-[11px] text-gray-500">Respiratory / Inhaler Use</span>
                                    </div>
                                </label>

                                <!-- Kidney Disease -->
                                <label class="flex items-start gap-3 p-3 rounded-xl border border-gray-200 hover:bg-teal-50/50 cursor-pointer transition">
                                    <input
                                        type="checkbox"
                                        v-model="form.kidney_disease"
                                        class="mt-0.5 rounded border-gray-300 text-teal-600 shadow-sm focus:ring-teal-500"
                                    />
                                    <div>
                                        <span class="text-sm font-bold text-gray-900 block">Kidney Disease</span>
                                        <span class="text-[11px] text-gray-500">Renal failure, Dialysis</span>
                                    </div>
                                </label>

                                <!-- Liver Disease / Hepatitis -->
                                <label class="flex items-start gap-3 p-3 rounded-xl border border-gray-200 hover:bg-teal-50/50 cursor-pointer transition">
                                    <input
                                        type="checkbox"
                                        v-model="form.liver_disease"
                                        class="mt-0.5 rounded border-gray-300 text-teal-600 shadow-sm focus:ring-teal-500"
                                    />
                                    <div>
                                        <span class="text-sm font-bold text-gray-900 block">Liver Disease</span>
                                        <span class="text-[11px] text-gray-500">Cirrhosis, Jaundice</span>
                                    </div>
                                </label>

                                <!-- Hepatitis -->
                                <label class="flex items-start gap-3 p-3 rounded-xl border border-gray-200 hover:bg-teal-50/50 cursor-pointer transition">
                                    <input
                                        type="checkbox"
                                        v-model="form.hepatitis"
                                        class="mt-0.5 rounded border-gray-300 text-teal-600 shadow-sm focus:ring-teal-500"
                                    />
                                    <div>
                                        <span class="text-sm font-bold text-gray-900 block">Hepatitis</span>
                                        <span class="text-[11px] text-gray-500">Hep A, B, or C</span>
                                    </div>
                                </label>

                                <!-- Epilepsy / Seizures -->
                                <label class="flex items-start gap-3 p-3 rounded-xl border border-gray-200 hover:bg-teal-50/50 cursor-pointer transition">
                                    <input
                                        type="checkbox"
                                        v-model="form.epilepsy"
                                        class="mt-0.5 rounded border-gray-300 text-teal-600 shadow-sm focus:ring-teal-500"
                                    />
                                    <div>
                                        <span class="text-sm font-bold text-gray-900 block">Epilepsy</span>
                                        <span class="text-[11px] text-gray-500">Seizure disorders</span>
                                    </div>
                                </label>
                            </div>
                        </div>

                        <hr class="border-gray-100" />

                        <!-- 4. Current Medications & Surgeries -->
                        <div>
                            <h3 class="text-sm font-bold uppercase tracking-wider text-teal-700 mb-4 flex items-center gap-2">
                                <i class="pi pi-file-edit text-sm"></i> Medications & Surgical History
                            </h3>

                            <div class="space-y-4">
                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 mb-1">
                                        Current Maintenance Medications & Supplements
                                    </label>
                                    <textarea
                                        v-model="form.current_medications"
                                        rows="2"
                                        placeholder="e.g. Amlodipine 5mg daily, Metformin 500mg, Aspirin 81mg (Blood thinner), Multivitamins..."
                                        class="w-full rounded-lg border-gray-300 shadow-sm focus:border-teal-500 focus:ring-teal-500 text-sm"
                                    ></textarea>
                                    <p v-if="form.errors.current_medications" class="text-xs text-red-600 mt-1">{{ form.errors.current_medications }}</p>
                                </div>

                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 mb-1">
                                        Past Surgeries, Hospitalizations, or Medical Procedures
                                    </label>
                                    <textarea
                                        v-model="form.past_surgeries"
                                        rows="2"
                                        placeholder="e.g. Appendectomy in 2020, Knee Arthroscopy in 2023..."
                                        class="w-full rounded-lg border-gray-300 shadow-sm focus:border-teal-500 focus:ring-teal-500 text-sm"
                                    ></textarea>
                                    <p v-if="form.errors.past_surgeries" class="text-xs text-red-600 mt-1">{{ form.errors.past_surgeries }}</p>
                                </div>
                            </div>
                        </div>

                        <hr class="border-gray-100" />

                        <!-- 5. Attending Physician & Clinical Precautions -->
                        <div>
                            <h3 class="text-sm font-bold uppercase tracking-wider text-teal-700 mb-4 flex items-center gap-2">
                                <i class="pi pi-user-plus text-sm"></i> Attending Physician & Clinical Notes
                            </h3>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-4">
                                <div>
                                    <label class="block text-xs font-semibold text-gray-700 mb-1">Physician Name</label>
                                    <input
                                        v-model="form.physician_name"
                                        type="text"
                                        placeholder="Dr. John Doe, MD (Cardiologist)"
                                        class="w-full rounded-lg border-gray-300 shadow-sm focus:border-teal-500 focus:ring-teal-500 text-sm"
                                    />
                                </div>
                                <div>
                                    <label class="block text-xs font-semibold text-gray-700 mb-1">Physician Contact / Clinic</label>
                                    <input
                                        v-model="form.physician_contact"
                                        type="text"
                                        placeholder="Hospital / Clinic Contact number"
                                        class="w-full rounded-lg border-gray-300 shadow-sm focus:border-teal-500 focus:ring-teal-500 text-sm"
                                    />
                                </div>
                            </div>

                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-1">
                                    Special Dental Precautions & Clinical Notes
                                </label>
                                <textarea
                                    v-model="form.notes"
                                    rows="2"
                                    placeholder="e.g. Pre-medicate with amoxicillin 2g 1 hour before dental cleanings/extractions, monitor blood pressure prior to anesthesia..."
                                    class="w-full rounded-lg border-gray-300 shadow-sm focus:border-teal-500 focus:ring-teal-500 text-sm"
                                ></textarea>
                                <p v-if="form.errors.notes" class="text-xs text-red-600 mt-1">{{ form.errors.notes }}</p>
                            </div>
                        </div>

                    </div>

                    <!-- Footer / Submit Button -->
                    <div class="px-6 py-4 bg-gray-50 border-t border-gray-100 flex items-center justify-end gap-3">
                        <Link
                            :href="route('patients.show', patient.id)"
                            class="px-4 py-2 border border-gray-300 rounded-lg text-sm font-semibold text-gray-700 hover:bg-white transition"
                        >
                            Cancel
                        </Link>
                        <button
                            type="submit"
                            :disabled="form.processing"
                            class="inline-flex items-center gap-2 rounded-lg bg-teal-700 px-6 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-teal-800 focus:outline-none focus:ring-2 focus:ring-teal-500 focus:ring-offset-2 disabled:opacity-50"
                        >
                            <i v-if="form.processing" class="pi pi-spin pi-spinner text-xs"></i>
                            <i v-else class="pi pi-save text-xs"></i>
                            Save Medical History
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
