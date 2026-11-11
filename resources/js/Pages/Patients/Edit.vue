<script setup>
import { computed } from "vue";
import { Head, useForm, Link } from "@inertiajs/vue3";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";

const props = defineProps({
    patient: {
        type: Object,
        required: true,
    },
});

const form = useForm({
    patient_id: props.patient.patient_id,
    first_name: props.patient.first_name,
    middle_name: props.patient.middle_name || "",
    last_name: props.patient.last_name,
    suffix: props.patient.suffix || "",
    date_of_birth: props.patient.date_of_birth,
    gender: props.patient.gender,
    guardian: props.patient.guardian || "",
    contact_number: props.patient.contact_number,
    email: props.patient.email || "",
    address: props.patient.address || "",
    status: props.patient.status,
});

const isUnder18 = computed(() => {
    if (!form.date_of_birth) return false;
    const dob = new Date(form.date_of_birth);
    if (isNaN(dob.getTime())) return false;
    const today = new Date();
    let age = today.getFullYear() - dob.getFullYear();
    const m = today.getMonth() - dob.getMonth();
    if (m < 0 || (m === 0 && today.getDate() < dob.getDate())) {
        age--;
    }
    return age < 18;
});

const handleContactInput = (e) => {
    const numeric = e.target.value.replace(/\D/g, "");
    form.contact_number = numeric;
    if (e.target.value !== numeric) {
        e.target.value = numeric;
    }
};

const handleContactKeyPress = (e) => {
    if (!/[0-9]/.test(e.key)) {
        e.preventDefault();
    }
};

const submit = () => {
    form.put(route("patients.update", props.patient.id));
};
</script>

<template>
    <Head :title="`Edit ${patient.first_name} ${patient.last_name} - Vitality Smile`" />

    <AuthenticatedLayout>
        <!-- Header Banner -->
        <template #header>
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <!-- Breadcrumbs Pill -->
                    <div class="flex items-center gap-2">
                        <Link
                            :href="route('patients.show', patient.id)"
                            class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-white text-slate-600 hover:text-teal-700 border border-slate-200 shadow-2xs transition"
                        >
                            <i class="pi pi-arrow-left text-[10px]"></i>
                            <span>View Dossier</span>
                        </Link>
                        <span class="text-xs text-slate-400 font-medium">• Edit Profile</span>
                    </div>

                    <h1 class="mt-2 text-2xl font-black text-slate-900 tracking-tight sm:text-3xl">
                        Edit Patient Profile
                    </h1>
                    <p class="mt-1 text-sm text-slate-500">
                        Update personal details, demographics, and contact information for {{ patient.first_name }} {{ patient.last_name }}.
                    </p>
                </div>
            </div>
        </template>

        <!-- Main Body -->
        <div class="py-6 sm:py-8">
            <div class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8">
                <form @submit.prevent="submit" class="space-y-6">

                    <!-- Section 1: Demographics & Identity -->
                    <div class="rounded-3xl border border-slate-200/90 bg-white p-6 sm:p-8 shadow-2xs space-y-6">
                        <div class="flex items-center gap-3 border-b border-slate-100 pb-4">
                            <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-teal-50 text-teal-700 border border-teal-100 shadow-2xs">
                                <i class="pi pi-id-card text-lg"></i>
                            </div>
                            <div>
                                <h2 class="text-base font-bold text-slate-900">
                                    Patient Identity & Demographics
                                </h2>
                                <p class="text-xs text-slate-500">
                                    Official identification, birth record, and demographic details.
                                </p>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5">
                            <!-- Patient ID -->
                            <div>
                                <label class="block text-xs font-bold uppercase tracking-wider text-slate-600 mb-1.5">
                                    Patient ID <span class="text-rose-500">*</span>
                                </label>
                                <input
                                    v-model="form.patient_id"
                                    type="text"
                                    required
                                    class="w-full rounded-xl border border-slate-200 px-3.5 py-2.5 text-sm font-mono font-bold text-slate-900 placeholder:text-slate-400 focus:border-teal-500 focus:ring-2 focus:ring-teal-500/20 shadow-2xs"
                                />
                                <div v-if="form.errors.patient_id" class="text-rose-600 text-xs mt-1 font-medium">
                                    {{ form.errors.patient_id }}
                                </div>
                            </div>

                            <!-- First Name -->
                            <div>
                                <label class="block text-xs font-bold uppercase tracking-wider text-slate-600 mb-1.5">
                                    First Name <span class="text-rose-500">*</span>
                                </label>
                                <input
                                    v-model="form.first_name"
                                    type="text"
                                    required
                                    class="w-full rounded-xl border border-slate-200 px-3.5 py-2.5 text-sm font-medium text-slate-900 placeholder:text-slate-400 focus:border-teal-500 focus:ring-2 focus:ring-teal-500/20 shadow-2xs"
                                />
                                <div v-if="form.errors.first_name" class="text-rose-600 text-xs mt-1 font-medium">
                                    {{ form.errors.first_name }}
                                </div>
                            </div>

                            <!-- Middle Name -->
                            <div>
                                <label class="block text-xs font-bold uppercase tracking-wider text-slate-600 mb-1.5">
                                    Middle Name
                                </label>
                                <input
                                    v-model="form.middle_name"
                                    type="text"
                                    class="w-full rounded-xl border border-slate-200 px-3.5 py-2.5 text-sm font-medium text-slate-900 placeholder:text-slate-400 focus:border-teal-500 focus:ring-2 focus:ring-teal-500/20 shadow-2xs"
                                />
                                <div v-if="form.errors.middle_name" class="text-rose-600 text-xs mt-1 font-medium">
                                    {{ form.errors.middle_name }}
                                </div>
                            </div>

                            <!-- Last Name -->
                            <div>
                                <label class="block text-xs font-bold uppercase tracking-wider text-slate-600 mb-1.5">
                                    Last Name <span class="text-rose-500">*</span>
                                </label>
                                <input
                                    v-model="form.last_name"
                                    type="text"
                                    required
                                    class="w-full rounded-xl border border-slate-200 px-3.5 py-2.5 text-sm font-medium text-slate-900 placeholder:text-slate-400 focus:border-teal-500 focus:ring-2 focus:ring-teal-500/20 shadow-2xs"
                                />
                                <div v-if="form.errors.last_name" class="text-rose-600 text-xs mt-1 font-medium">
                                    {{ form.errors.last_name }}
                                </div>
                            </div>

                            <!-- Suffix -->
                            <div>
                                <label class="block text-xs font-bold uppercase tracking-wider text-slate-600 mb-1.5">
                                    Suffix
                                </label>
                                <input
                                    v-model="form.suffix"
                                    type="text"
                                    class="w-full rounded-xl border border-slate-200 px-3.5 py-2.5 text-sm font-medium text-slate-900 placeholder:text-slate-400 focus:border-teal-500 focus:ring-2 focus:ring-teal-500/20 shadow-2xs"
                                />
                                <div v-if="form.errors.suffix" class="text-rose-600 text-xs mt-1 font-medium">
                                    {{ form.errors.suffix }}
                                </div>
                            </div>

                            <!-- Date of Birth -->
                            <div>
                                <label class="block text-xs font-bold uppercase tracking-wider text-slate-600 mb-1.5">
                                    Date of Birth <span class="text-rose-500">*</span>
                                </label>
                                <input
                                    v-model="form.date_of_birth"
                                    type="date"
                                    required
                                    class="w-full rounded-xl border border-slate-200 px-3.5 py-2.5 text-sm font-medium text-slate-900 focus:border-teal-500 focus:ring-2 focus:ring-teal-500/20 shadow-2xs"
                                />
                                <div v-if="form.errors.date_of_birth" class="text-rose-600 text-xs mt-1 font-medium">
                                    {{ form.errors.date_of_birth }}
                                </div>
                            </div>

                            <!-- Gender -->
                            <div>
                                <label class="block text-xs font-bold uppercase tracking-wider text-slate-600 mb-1.5">
                                    Gender <span class="text-rose-500">*</span>
                                </label>
                                <select
                                    v-model="form.gender"
                                    required
                                    class="w-full rounded-xl border border-slate-200 px-3.5 py-2.5 text-sm font-semibold text-slate-900 focus:border-teal-500 focus:ring-2 focus:ring-teal-500/20 shadow-2xs"
                                >
                                    <option value="Male">Male</option>
                                    <option value="Female">Female</option>
                                    <option value="Other">Other</option>
                                </select>
                                <div v-if="form.errors.gender" class="text-rose-600 text-xs mt-1 font-medium">
                                    {{ form.errors.gender }}
                                </div>
                            </div>
                        </div>

                        <!-- Minor / Pediatric Guardian Notice -->
                        <div
                            v-if="isUnder18"
                            class="rounded-2xl border border-amber-300 bg-amber-50/70 p-4 space-y-2 animate-in fade-in"
                        >
                            <div class="flex items-center gap-2">
                                <i class="pi pi-shield text-amber-700"></i>
                                <label class="text-xs font-extrabold uppercase tracking-wide text-amber-900">
                                    Parent / Legal Guardian Name <span class="text-rose-600">*</span>
                                </label>
                            </div>
                            <p class="text-xs text-amber-800">
                                Patient is a minor (under 18 years old). Please provide the full name of their parent or legal guardian for dental consent.
                            </p>
                            <input
                                v-model="form.guardian"
                                type="text"
                                :required="isUnder18"
                                placeholder="e.g. Maria Santos (Mother / Legal Guardian)"
                                class="w-full rounded-xl border border-amber-300 bg-white px-3.5 py-2.5 text-sm font-medium text-slate-900 focus:border-amber-500 focus:ring-2 focus:ring-amber-500/20 shadow-2xs"
                            />
                            <div v-if="form.errors.guardian" class="text-rose-600 text-xs mt-1 font-medium">
                                {{ form.errors.guardian }}
                            </div>
                        </div>
                    </div>

                    <!-- Section 2: Contact & Address -->
                    <div class="rounded-3xl border border-slate-200/90 bg-white p-6 sm:p-8 shadow-2xs space-y-6">
                        <div class="flex items-center gap-3 border-b border-slate-100 pb-4">
                            <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-blue-50 text-blue-700 border border-blue-100 shadow-2xs">
                                <i class="pi pi-phone text-lg"></i>
                            </div>
                            <div>
                                <h2 class="text-base font-bold text-slate-900">
                                    Contact & Residential Information
                                </h2>
                                <p class="text-xs text-slate-500">
                                    Phone number for SMS reminders, email for receipts, and residential address.
                                </p>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                            <!-- Contact Phone -->
                            <div>
                                <label class="block text-xs font-bold uppercase tracking-wider text-slate-600 mb-1.5">
                                    Contact Number <span class="text-rose-500">*</span>
                                </label>
                                <div class="relative">
                                    <i class="pi pi-phone absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i>
                                    <input
                                        :value="form.contact_number"
                                        @input="handleContactInput"
                                        @keypress="handleContactKeyPress"
                                        type="tel"
                                        inputmode="numeric"
                                        pattern="[0-9]*"
                                        maxlength="15"
                                        required
                                        placeholder="09123456789"
                                        class="w-full rounded-xl border border-slate-200 pl-10 pr-3.5 py-2.5 text-sm font-mono font-medium text-slate-900 placeholder:text-slate-400 focus:border-teal-500 focus:ring-2 focus:ring-teal-500/20 shadow-2xs"
                                    />
                                </div>
                                <p class="text-[11px] text-slate-400 mt-1">Philippine mobile number (digits only)</p>
                                <div v-if="form.errors.contact_number" class="text-rose-600 text-xs mt-1 font-medium">
                                    {{ form.errors.contact_number }}
                                </div>
                            </div>

                            <!-- Email Address -->
                            <div>
                                <label class="block text-xs font-bold uppercase tracking-wider text-slate-600 mb-1.5">
                                    Email Address
                                </label>
                                <div class="relative">
                                    <i class="pi pi-envelope absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i>
                                    <input
                                        v-model="form.email"
                                        type="email"
                                        placeholder="patient@example.com"
                                        class="w-full rounded-xl border border-slate-200 pl-10 pr-3.5 py-2.5 text-sm font-medium text-slate-900 placeholder:text-slate-400 focus:border-teal-500 focus:ring-2 focus:ring-teal-500/20 shadow-2xs"
                                    />
                                </div>
                                <div v-if="form.errors.email" class="text-rose-600 text-xs mt-1 font-medium">
                                    {{ form.errors.email }}
                                </div>
                            </div>

                            <!-- Complete Address -->
                            <div class="sm:col-span-2">
                                <label class="block text-xs font-bold uppercase tracking-wider text-slate-600 mb-1.5">
                                    Complete Residential Address
                                </label>
                                <textarea
                                    v-model="form.address"
                                    rows="3"
                                    class="w-full rounded-xl border border-slate-200 p-3.5 text-sm font-medium text-slate-900 placeholder:text-slate-400 focus:border-teal-500 focus:ring-2 focus:ring-teal-500/20 shadow-2xs leading-relaxed"
                                ></textarea>
                                <div v-if="form.errors.address" class="text-rose-600 text-xs mt-1 font-medium">
                                    {{ form.errors.address }}
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Section 3: Clinic Status -->
                    <div class="rounded-3xl border border-slate-200/90 bg-white p-6 sm:p-8 shadow-2xs space-y-4">
                        <div class="flex items-center gap-3 border-b border-slate-100 pb-4">
                            <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-purple-50 text-purple-700 border border-purple-100 shadow-2xs">
                                <i class="pi pi-check-circle text-lg"></i>
                            </div>
                            <div>
                                <h2 class="text-base font-bold text-slate-900">
                                    Registry Status
                                </h2>
                                <p class="text-xs text-slate-500">
                                    Update patient's standing in the clinic directory.
                                </p>
                            </div>
                        </div>

                        <div class="max-w-xs">
                            <label class="block text-xs font-bold uppercase tracking-wider text-slate-600 mb-1.5">
                                Account Status <span class="text-rose-500">*</span>
                            </label>
                            <select
                                v-model="form.status"
                                required
                                class="w-full rounded-xl border border-slate-200 px-3.5 py-2.5 text-sm font-semibold text-slate-900 focus:border-teal-500 focus:ring-2 focus:ring-teal-500/20 shadow-2xs"
                            >
                                <option value="Active">Active (Current Patient)</option>
                                <option value="Inactive">Inactive</option>
                                <option value="Archived">Archived</option>
                            </select>
                            <div v-if="form.errors.status" class="text-rose-600 text-xs mt-1 font-medium">
                                {{ form.errors.status }}
                            </div>
                        </div>
                    </div>

                    <!-- Form Buttons -->
                    <div class="flex items-center justify-end gap-3 pt-2">
                        <Link
                            :href="route('patients.show', patient.id)"
                            class="px-5 py-2.5 rounded-xl text-xs font-bold text-slate-700 bg-white border border-slate-200 hover:bg-slate-50 transition shadow-2xs"
                        >
                            Cancel
                        </Link>
                        <button
                            type="submit"
                            :disabled="form.processing"
                            class="inline-flex items-center gap-2 px-6 py-2.5 rounded-xl text-xs font-bold text-white bg-gradient-to-r from-teal-600 to-emerald-600 hover:from-teal-700 hover:to-emerald-700 shadow-sm transition hover:shadow-md disabled:opacity-50"
                        >
                            <i class="pi pi-check text-xs"></i>
                            <span>{{ form.processing ? 'Updating...' : 'Save Changes' }}</span>
                        </button>
                    </div>

                </form>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
