<script setup>
import { computed } from "vue";
import { Link, useForm, usePage } from "@inertiajs/vue3";

const props = defineProps({
    patient: {
        type: Object,
        required: true,
    },
    mustVerifyEmail: {
        type: Boolean,
        default: false,
    },
    status: {
        type: String,
        default: "",
    },
});

const user = usePage().props.auth.user;

const form = useForm({
    first_name: props.patient?.first_name || "",
    middle_name: props.patient?.middle_name || "",
    last_name: props.patient?.last_name || "",
    suffix: props.patient?.suffix || "",
    date_of_birth: props.patient?.date_of_birth || "",
    gender: props.patient?.gender || "Other",
    contact_number: props.patient?.contact_number || "",
    guardian: props.patient?.guardian || "",
    address: props.patient?.address || "",
    email: user.email || "",
    name: user.name || "",
});

// Compute live age based on entered date of birth
const calculatedAge = computed(() => {
    if (!form.date_of_birth) return null;
    try {
        const dob = new Date(form.date_of_birth);
        if (isNaN(dob.getTime())) return null;
        const diffMs = Date.now() - dob.getTime();
        const ageDt = new Date(diffMs);
        const age = Math.abs(ageDt.getUTCFullYear() - 1970);
        return age >= 0 && age < 130 ? age : null;
    } catch {
        return null;
    }
});

const submit = () => {
    form.patch(route("profile.update"), {
        preserveScroll: true,
    });
};
</script>

<template>
    <section>
        <header class="border-b border-gray-100 pb-4">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-base font-bold text-gray-900">
                        Personal & Clinical Identification
                    </h2>
                    <p class="mt-0.5 text-xs text-gray-500">
                        Accurate personal information ensures proper identification, prescription matching, and dental chart management.
                    </p>
                </div>
                <span class="inline-flex items-center gap-1.5 rounded-lg bg-teal-50 px-2.5 py-1 text-xs font-bold text-teal-800 border border-teal-200">
                    <i class="pi pi-id-card text-xs text-teal-600"></i>
                    ID: {{ patient.patient_id }}
                </span>
            </div>
        </header>

        <form @submit.prevent="submit" class="mt-6 space-y-6">
            <!-- Full Name Breakdown -->
            <div>
                <label class="block text-xs font-semibold text-gray-700 uppercase tracking-wider mb-2">
                    Patient Legal Name
                </label>
                <div class="grid grid-cols-1 gap-3 sm:grid-cols-12">
                    <div class="sm:col-span-4">
                        <label class="block text-xs text-gray-500 mb-1">First Name <span class="text-red-500">*</span></label>
                        <input
                            v-model="form.first_name"
                            type="text"
                            required
                            placeholder="e.g. Maria"
                            class="w-full rounded-xl border-gray-300 text-sm focus:border-teal-500 focus:ring-teal-500 transition"
                        />
                        <p v-if="form.errors.first_name" class="mt-1 text-xs text-red-600">{{ form.errors.first_name }}</p>
                    </div>

                    <div class="sm:col-span-3">
                        <label class="block text-xs text-gray-500 mb-1">Middle Name</label>
                        <input
                            v-model="form.middle_name"
                            type="text"
                            placeholder="e.g. Santos"
                            class="w-full rounded-xl border-gray-300 text-sm focus:border-teal-500 focus:ring-teal-500 transition"
                        />
                        <p v-if="form.errors.middle_name" class="mt-1 text-xs text-red-600">{{ form.errors.middle_name }}</p>
                    </div>

                    <div class="sm:col-span-4">
                        <label class="block text-xs text-gray-500 mb-1">Last Name <span class="text-red-500">*</span></label>
                        <input
                            v-model="form.last_name"
                            type="text"
                            required
                            placeholder="e.g. Dela Cruz"
                            class="w-full rounded-xl border-gray-300 text-sm focus:border-teal-500 focus:ring-teal-500 transition"
                        />
                        <p v-if="form.errors.last_name" class="mt-1 text-xs text-red-600">{{ form.errors.last_name }}</p>
                    </div>

                    <div class="sm:col-span-1">
                        <label class="block text-xs text-gray-500 mb-1">Suffix</label>
                        <input
                            v-model="form.suffix"
                            type="text"
                            placeholder="Jr / III"
                            class="w-full rounded-xl border-gray-300 text-sm focus:border-teal-500 focus:ring-teal-500 transition text-center px-1"
                        />
                        <p v-if="form.errors.suffix" class="mt-1 text-xs text-red-600">{{ form.errors.suffix }}</p>
                    </div>
                </div>
            </div>

            <!-- Date of Birth & Gender -->
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <!-- Date of Birth -->
                <div>
                    <div class="flex items-center justify-between mb-1">
                        <label class="block text-xs font-semibold text-gray-700">
                            Date of Birth
                        </label>
                        <span
                            v-if="calculatedAge !== null"
                            class="inline-flex items-center rounded-md bg-teal-50 px-2 py-0.5 text-[11px] font-bold text-teal-800 border border-teal-200"
                        >
                            {{ calculatedAge }} years old
                        </span>
                    </div>
                    <input
                        v-model="form.date_of_birth"
                        type="date"
                        class="w-full rounded-xl border-gray-300 text-sm focus:border-teal-500 focus:ring-teal-500 transition"
                    />
                    <p class="mt-1 text-[11px] text-gray-400">Used for dosage precision and clinical developmental tracking.</p>
                    <p v-if="form.errors.date_of_birth" class="mt-1 text-xs text-red-600">{{ form.errors.date_of_birth }}</p>
                </div>

                <!-- Gender -->
                <div>
                    <label class="block text-xs font-semibold text-gray-700 mb-1">
                        Gender
                    </label>
                    <div class="grid grid-cols-3 gap-2">
                        <label
                            :class="[
                                'flex items-center justify-center gap-1.5 rounded-xl border p-2.5 text-xs font-semibold cursor-pointer transition text-center',
                                form.gender === 'Female'
                                    ? 'border-teal-600 bg-teal-50 text-teal-900 ring-2 ring-teal-500/20'
                                    : 'border-gray-200 bg-white text-gray-700 hover:bg-gray-50'
                            ]"
                        >
                            <input type="radio" value="Female" v-model="form.gender" class="sr-only" />
                            <i class="pi pi-user text-xs"></i>
                            <span>Female</span>
                        </label>

                        <label
                            :class="[
                                'flex items-center justify-center gap-1.5 rounded-xl border p-2.5 text-xs font-semibold cursor-pointer transition text-center',
                                form.gender === 'Male'
                                    ? 'border-teal-600 bg-teal-50 text-teal-900 ring-2 ring-teal-500/20'
                                    : 'border-gray-200 bg-white text-gray-700 hover:bg-gray-50'
                            ]"
                        >
                            <input type="radio" value="Male" v-model="form.gender" class="sr-only" />
                            <i class="pi pi-user text-xs"></i>
                            <span>Male</span>
                        </label>

                        <label
                            :class="[
                                'flex items-center justify-center gap-1.5 rounded-xl border p-2.5 text-xs font-semibold cursor-pointer transition text-center',
                                form.gender === 'Other'
                                    ? 'border-teal-600 bg-teal-50 text-teal-900 ring-2 ring-teal-500/20'
                                    : 'border-gray-200 bg-white text-gray-700 hover:bg-gray-50'
                            ]"
                        >
                            <input type="radio" value="Other" v-model="form.gender" class="sr-only" />
                            <i class="pi pi-id-card text-xs"></i>
                            <span>Other</span>
                        </label>
                    </div>
                    <p v-if="form.errors.gender" class="mt-1 text-xs text-red-600">{{ form.errors.gender }}</p>
                </div>
            </div>

            <!-- Contact Number & Guardian -->
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <!-- Contact Number -->
                <div>
                    <label class="block text-xs font-semibold text-gray-700 mb-1">
                        Contact / Mobile Number
                    </label>
                    <div class="relative">
                        <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3.5 text-gray-400">
                            <i class="pi pi-phone text-xs"></i>
                        </div>
                        <input
                            v-model="form.contact_number"
                            type="tel"
                            placeholder="e.g. 0917 123 4567"
                            class="w-full rounded-xl border-gray-300 pl-9 text-sm focus:border-teal-500 focus:ring-teal-500 transition"
                        />
                    </div>
                    <p class="mt-1 text-[11px] text-gray-400">For appointment reminders and clinical follow-ups.</p>
                    <p v-if="form.errors.contact_number" class="mt-1 text-xs text-red-600">{{ form.errors.contact_number }}</p>
                </div>

                <!-- Guardian (For minors / dependents) -->
                <div>
                    <label class="block text-xs font-semibold text-gray-700 mb-1">
                        Guardian / Legal Representative
                    </label>
                    <div class="relative">
                        <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3.5 text-gray-400">
                            <i class="pi pi-shield text-xs"></i>
                        </div>
                        <input
                            v-model="form.guardian"
                            type="text"
                            placeholder="Parent or Guardian name (if minor or assisted)"
                            class="w-full rounded-xl border-gray-300 pl-9 text-sm focus:border-teal-500 focus:ring-teal-500 transition"
                        />
                    </div>
                    <p class="mt-1 text-[11px] text-gray-400">Required for minors under 18 or assisted patients.</p>
                    <p v-if="form.errors.guardian" class="mt-1 text-xs text-red-600">{{ form.errors.guardian }}</p>
                </div>
            </div>

            <!-- Home Address -->
            <div>
                <label class="block text-xs font-semibold text-gray-700 mb-1">
                    Complete Home / Mailing Address
                </label>
                <div class="relative">
                    <div class="pointer-events-none absolute top-3 left-3 text-gray-400">
                        <i class="pi pi-map-marker text-xs"></i>
                    </div>
                    <textarea
                        v-model="form.address"
                        rows="2"
                        placeholder="House / Unit No., Street, Barangay, City, Province, Postal Code"
                        class="w-full rounded-xl border-gray-300 pl-9 text-sm focus:border-teal-500 focus:ring-teal-500 transition"
                    ></textarea>
                </div>
                <p v-if="form.errors.address" class="mt-1 text-xs text-red-600">{{ form.errors.address }}</p>
            </div>

            <!-- Email Address & Verification -->
            <div class="border-t border-gray-100 pt-4">
                <label class="block text-xs font-semibold text-gray-700 mb-1">
                    Portal Login & Billing Email <span class="text-red-500">*</span>
                </label>
                <div class="relative">
                    <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3.5 text-gray-400">
                        <i class="pi pi-envelope text-xs"></i>
                    </div>
                    <input
                        v-model="form.email"
                        type="email"
                        required
                        class="w-full rounded-xl border-gray-300 pl-9 text-sm focus:border-teal-500 focus:ring-teal-500 transition"
                    />
                </div>
                <p v-if="form.errors.email" class="mt-1 text-xs text-red-600">{{ form.errors.email }}</p>

                <!-- Email Verification Warning if applicable -->
                <div v-if="mustVerifyEmail && user.email_verified_at === null" class="mt-2.5 rounded-xl border border-amber-200 bg-amber-50 p-3 flex items-start gap-3">
                    <i class="pi pi-info-circle text-amber-600 text-sm mt-0.5"></i>
                    <div class="text-xs text-amber-800">
                        <span>Your email address is unverified. </span>
                        <Link
                            :href="route('verification.send')"
                            method="post"
                            as="button"
                            class="font-semibold underline hover:text-amber-950"
                        >
                            Click here to re-send the verification link.
                        </Link>
                        <p v-show="status === 'verification-link-sent'" class="mt-1 font-bold text-green-700">
                            A new verification link has been sent to your email.
                        </p>
                    </div>
                </div>
            </div>

            <!-- Submit Button & Feedback -->
            <div class="flex items-center justify-between border-t border-gray-100 pt-4">
                <div class="text-xs text-gray-500">
                    <span v-if="form.isDirty" class="text-amber-600 font-medium flex items-center gap-1">
                        <i class="pi pi-circle-fill text-[8px]"></i> Unsaved changes
                    </span>
                    <span v-else class="text-gray-400">
                        Profile information synchronized with clinical registry
                    </span>
                </div>

                <div class="flex items-center gap-3">
                    <Transition
                        enter-active-class="transition ease-in-out duration-300"
                        enter-from-class="opacity-0 translate-y-1"
                        leave-active-class="transition ease-in-out duration-300"
                        leave-to-class="opacity-0"
                    >
                        <span
                            v-if="form.recentlySuccessful"
                            class="inline-flex items-center gap-1.5 rounded-lg bg-emerald-50 px-3 py-1.5 text-xs font-bold text-emerald-700 border border-emerald-200"
                        >
                            <i class="pi pi-check text-xs"></i> Saved Successfully
                        </span>
                    </Transition>

                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="inline-flex items-center gap-2 rounded-xl bg-teal-700 px-6 py-2.5 text-xs font-bold text-white shadow-sm transition hover:bg-teal-800 focus:outline-none focus:ring-2 focus:ring-teal-500 focus:ring-offset-2 disabled:opacity-50"
                    >
                        <i v-if="form.processing" class="pi pi-spin pi-spinner text-xs"></i>
                        <i v-else class="pi pi-save text-xs"></i>
                        Save Patient Information
                    </button>
                </div>
            </div>
        </form>
    </section>
</template>

