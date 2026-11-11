<script setup>
import { ref, computed, watch } from "vue";
import GuestLayout from "@/Layouts/GuestLayout.vue";
import InputError from "@/Components/InputError.vue";
import { Head, Link, useForm } from "@inertiajs/vue3";

const props = defineProps({
    max_birth_date: {
        type: String,
        default: () => {
            const d = new Date();
            d.setFullYear(d.getFullYear() - 3);
            return d.toISOString().split("T")[0];
        },
    },
});

const form = useForm({
    first_name: "",
    middle_name: "",
    last_name: "",
    suffix: "",
    date_of_birth: "",
    gender: "Male",
    guardian: "",
    contact_number: "",
    address: "",
    email: "",
    password: "",
    password_confirmation: "",
    confirm_adult: true,
});

const showPassword = ref(false);
const showConfirmPassword = ref(false);

const calculatedAge = computed(() => {
    if (!form.date_of_birth) return null;
    const dob = new Date(form.date_of_birth);
    if (isNaN(dob.getTime())) return null;
    const today = new Date();
    let age = today.getFullYear() - dob.getFullYear();
    const m = today.getMonth() - dob.getMonth();
    if (m < 0 || (m === 0 && today.getDate() < dob.getDate())) {
        age--;
    }
    return age;
});

const isTooYoung = computed(() => {
    return calculatedAge.value !== null && calculatedAge.value <= 2;
});

const isUnder18 = computed(() => {
    return (
        calculatedAge.value !== null &&
        calculatedAge.value >= 3 &&
        calculatedAge.value < 18
    );
});

watch(
    () => form.date_of_birth,
    (newVal) => {
        if (newVal) {
            if (isTooYoung.value) {
                form.setError(
                    "date_of_birth",
                    "Patients must be at least 3 years old to register. Patients 2 years old and below cannot register online.",
                );
            } else if (form.errors.date_of_birth) {
                form.clearErrors("date_of_birth");
            }
        }
    },
);

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
    if (isTooYoung.value) {
        form.setError(
            "date_of_birth",
            "Patients must be at least 3 years old to register. Patients 2 years old and below cannot register online.",
        );
        return;
    }

    if (isUnder18.value && !form.guardian?.trim()) {
        form.setError(
            "guardian",
            "Parent or legal guardian name is required for patients under 18.",
        );
        return;
    }

    form.post(route("register"), {
        onFinish: () => form.reset("password", "password_confirmation"),
    });
};
</script>

<template>
    <GuestLayout maxWidth="max-w-3xl">
        <Head title="Patient Registration - Vitality Smile" />

        <!-- Header -->
        <div class="mb-8 text-center">
            <div
                class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-teal-50 border border-teal-200 text-teal-800 text-xs font-semibold uppercase tracking-wider mb-2"
            >
                <i class="pi pi-shield text-teal-600"></i>
                <span>Patient Registration</span>
            </div>
            <h2
                class="text-2xl sm:text-3xl font-extrabold tracking-tight text-gray-900"
            >
                Create Your Patient Record
            </h2>
            <p class="mt-1.5 text-sm text-gray-500 max-w-lg mx-auto">
                Fill in your medical contact information to set up your online
                dental profile and book clinic appointments.
            </p>
        </div>

        <!-- Quick Google Signup -->
        <div
            class="mb-6 flex flex-col sm:flex-row items-center justify-between gap-3 p-3.5 rounded-2xl bg-slate-50 border border-gray-200"
        >
            <div class="flex items-center gap-2.5 text-xs text-gray-600">
                <i class="pi pi-bolt text-teal-600 text-sm"></i>
                <span
                    >Have a Google Account? Sign in quickly without password
                    setup.</span
                >
            </div>
            <a
                href="/auth/google"
                class="w-full sm:w-auto inline-flex items-center justify-center gap-2 px-3.5 py-1.5 rounded-xl border border-gray-300 bg-white text-xs font-semibold text-gray-700 shadow-sm hover:bg-gray-50 hover:border-gray-400 transition"
            >
                <svg class="h-4 w-4" viewBox="0 0 24 24" aria-hidden="true">
                    <path
                        d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"
                        fill="#4285F4"
                    />
                    <path
                        d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"
                        fill="#34A853"
                    />
                    <path
                        d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.06H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.94l2.85-2.22.81-.63z"
                        fill="#FBBC05"
                    />
                    <path
                        d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.06l3.66 2.84c.87-2.6 3.3-4.52 6.16-4.52z"
                        fill="#EA4335"
                    />
                </svg>
                <span>Continue with Google</span>
            </a>
        </div>

        <form @submit.prevent="submit" class="space-y-8">
            <!-- SECTION 1: Personal Information -->
            <div class="space-y-4">
                <div
                    class="flex items-center gap-2 border-b border-gray-100 pb-2"
                >
                    <div
                        class="h-7 w-7 rounded-lg bg-teal-100 text-teal-700 flex items-center justify-center text-xs"
                    >
                        <i class="pi pi-user font-bold"></i>
                    </div>
                    <h3
                        class="text-sm font-bold uppercase tracking-wider text-gray-800"
                    >
                        1. Personal Details
                    </h3>
                </div>

                <div
                    class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4"
                >
                    <!-- First Name -->
                    <div class="sm:col-span-1 lg:col-span-2">
                        <label
                            for="first_name"
                            class="block text-xs font-semibold uppercase tracking-wider text-gray-700 mb-1.5"
                        >
                            First Name <span class="text-red-500">*</span>
                        </label>
                        <input
                            id="first_name"
                            type="text"
                            v-model="form.first_name"
                            required
                            autofocus
                            placeholder="e.g. Maria"
                            class="block w-full rounded-xl border-gray-200 px-3.5 py-2.5 text-sm text-gray-900 placeholder:text-gray-400 focus:border-teal-500 focus:ring-teal-500 transition"
                        />
                        <InputError
                            class="mt-1.5"
                            :message="form.errors.first_name"
                        />
                    </div>

                    <!-- Middle Name -->
                    <div class="sm:col-span-1 lg:col-span-2">
                        <label
                            for="middle_name"
                            class="block text-xs font-semibold uppercase tracking-wider text-gray-700 mb-1.5"
                        >
                            Middle Name
                            <span class="text-gray-400 font-normal"
                                >(Optional)</span
                            >
                        </label>
                        <input
                            id="middle_name"
                            type="text"
                            v-model="form.middle_name"
                            placeholder="e.g. Santos"
                            class="block w-full rounded-xl border-gray-200 px-3.5 py-2.5 text-sm text-gray-900 placeholder:text-gray-400 focus:border-teal-500 focus:ring-teal-500 transition"
                        />
                        <InputError
                            class="mt-1.5"
                            :message="form.errors.middle_name"
                        />
                    </div>

                    <!-- Last Name -->
                    <div class="sm:col-span-1 lg:col-span-3">
                        <label
                            for="last_name"
                            class="block text-xs font-semibold uppercase tracking-wider text-gray-700 mb-1.5"
                        >
                            Last Name <span class="text-red-500">*</span>
                        </label>
                        <input
                            id="last_name"
                            type="text"
                            v-model="form.last_name"
                            required
                            placeholder="e.g. Dela Cruz"
                            class="block w-full rounded-xl border-gray-200 px-3.5 py-2.5 text-sm text-gray-900 placeholder:text-gray-400 focus:border-teal-500 focus:ring-teal-500 transition"
                        />
                        <InputError
                            class="mt-1.5"
                            :message="form.errors.last_name"
                        />
                    </div>

                    <!-- Suffix -->
                    <div class="sm:col-span-1 lg:col-span-1">
                        <label
                            for="suffix"
                            class="block text-xs font-semibold uppercase tracking-wider text-gray-700 mb-1.5"
                        >
                            Suffix
                        </label>
                        <select
                            id="suffix"
                            v-model="form.suffix"
                            class="block w-full rounded-xl border-gray-200 px-3 py-2.5 text-sm text-gray-900 focus:border-teal-500 focus:ring-teal-500 transition"
                        >
                            <option value="">None</option>
                            <option value="Jr.">Jr.</option>
                            <option value="Sr.">Sr.</option>
                            <option value="II">II</option>
                            <option value="III">III</option>
                            <option value="IV">IV</option>
                        </select>
                        <InputError
                            class="mt-1.5"
                            :message="form.errors.suffix"
                        />
                    </div>

                    <!-- Date of Birth -->
                    <div class="sm:col-span-1 lg:col-span-2">
                        <div class="flex items-center justify-between mb-1.5">
                            <label
                                for="date_of_birth"
                                class="block text-xs font-semibold uppercase tracking-wider text-gray-700"
                            >
                                Date of Birth
                                <span class="text-red-500">*</span>
                            </label>
                            <!-- Live Age Badge -->
                            <span
                                v-if="calculatedAge !== null"
                                :class="[
                                    'text-xs font-medium px-2 py-0.5 rounded-full inline-flex items-center gap-1',
                                    isTooYoung
                                        ? 'bg-red-50 text-red-700 border border-red-200'
                                        : isUnder18
                                        ? 'bg-amber-50 text-amber-700 border border-amber-200'
                                        : 'bg-emerald-50 text-emerald-700 border border-emerald-200',
                                ]"
                            >
                                <i
                                    :class="
                                        isTooYoung
                                            ? 'pi pi-times-circle text-red-600'
                                            : isUnder18
                                            ? 'pi pi-info-circle text-amber-600'
                                            : 'pi pi-check text-emerald-600'
                                    "
                                ></i>
                                {{ calculatedAge }} years old
                                {{
                                    isTooYoung
                                        ? '(Too young • Age 3+ Required)'
                                        : isUnder18
                                        ? '(Minor • Guardian Required)'
                                        : '(Adult)'
                                }}
                            </span>
                        </div>
                        <input
                            id="date_of_birth"
                            type="date"
                            v-model="form.date_of_birth"
                            :max="max_birth_date"
                            required
                            :class="[
                                'block w-full rounded-xl px-3.5 py-2.5 text-sm text-gray-900 focus:ring-teal-500 transition',
                                isTooYoung
                                    ? 'border-red-300 bg-red-50/30 focus:border-red-500 focus:ring-red-500'
                                    : 'border-gray-200 focus:border-teal-500',
                            ]"
                        />
                        <InputError
                            class="mt-1.5"
                            :message="form.errors.date_of_birth"
                        />

                        <!-- Too Young Notice -->
                        <div
                            v-if="isTooYoung"
                            class="mt-2.5 p-3 rounded-xl bg-red-50 border border-red-200 text-red-900 text-xs flex items-start gap-2.5 animate-in fade-in"
                        >
                            <i
                                class="pi pi-exclamation-triangle text-red-600 text-sm mt-0.5 shrink-0"
                            ></i>
                            <div>
                                <p class="font-bold">
                                    Online registration is not permitted for children 2 years old and below.
                                </p>
                                <p class="mt-0.5 text-red-700 leading-relaxed">
                                    Patients must be at least 3 years old to register an online dental profile. For infant or toddler dental care, please contact or visit Vitality Smile Dental Clinic directly.
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Gender (Dropdown) -->
                    <div class="sm:col-span-1 lg:col-span-2">
                        <label
                            for="gender"
                            class="block text-xs font-semibold uppercase tracking-wider text-gray-700 mb-1.5"
                        >
                            Gender <span class="text-red-500">*</span>
                        </label>
                        <select
                            id="gender"
                            v-model="form.gender"
                            required
                            class="block w-full rounded-xl border-gray-200 px-3.5 py-2.5 text-sm text-gray-900 bg-white focus:border-teal-500 focus:ring-teal-500 transition"
                        >
                            <option value="Male">Male</option>
                            <option value="Female">Female</option>
                            <option value="Other">Other</option>
                        </select>
                        <InputError
                            class="mt-1.5"
                            :message="form.errors.gender"
                        />
                    </div>

                    <!-- Guardian Field (Displayed if patient is below 18) -->
                    <div
                        v-if="isUnder18"
                        class="sm:col-span-2 lg:col-span-4 p-4 rounded-xl bg-amber-50/70 border border-amber-200"
                    >
                        <div class="flex items-center gap-2 mb-1.5">
                            <i
                                class="pi pi-user-plus text-amber-600 text-sm"
                            ></i>
                            <label
                                for="guardian"
                                class="block text-xs font-bold uppercase tracking-wider text-amber-900"
                            >
                                Parent / Legal Guardian Name
                                <span class="text-red-500">*</span>
                            </label>
                        </div>
                        <p class="text-xs text-amber-700 mb-2.5">
                            Because this patient is below 18 years old, please
                            enter the full name of their parent or legal
                            guardian.
                        </p>
                        <div class="relative rounded-xl shadow-sm">
                            <div
                                class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3.5 text-amber-500"
                            >
                                <i class="pi pi-id-card text-sm"></i>
                            </div>
                            <input
                                id="guardian"
                                type="text"
                                v-model="form.guardian"
                                :required="isUnder18"
                                placeholder="e.g. Maria Santos (Parent / Guardian)"
                                class="block w-full rounded-xl border-amber-300 bg-white pl-10 pr-3.5 py-2.5 text-sm text-gray-900 placeholder:text-gray-400 focus:border-amber-500 focus:ring-amber-500 transition"
                            />
                        </div>
                        <InputError
                            class="mt-1.5"
                            :message="form.errors.guardian"
                        />
                    </div>
                </div>
            </div>

            <!-- SECTION 2: Contact & Address -->
            <div class="space-y-4">
                <div
                    class="flex items-center gap-2 border-b border-gray-100 pb-2"
                >
                    <div
                        class="h-7 w-7 rounded-lg bg-teal-100 text-teal-700 flex items-center justify-center text-xs"
                    >
                        <i class="pi pi-phone font-bold"></i>
                    </div>
                    <h3
                        class="text-sm font-bold uppercase tracking-wider text-gray-800"
                    >
                        2. Contact & Address
                    </h3>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <!-- Contact Number (Numbers Only) -->
                    <div>
                        <label
                            for="contact_number"
                            class="block text-xs font-semibold uppercase tracking-wider text-gray-700 mb-1.5"
                        >
                            Contact / Mobile Number
                            <span class="text-red-500">*</span>
                        </label>
                        <div class="relative rounded-xl shadow-sm">
                            <div
                                class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3.5 text-gray-400"
                            >
                                <i class="pi pi-phone text-sm"></i>
                            </div>
                            <input
                                id="contact_number"
                                type="tel"
                                inputmode="numeric"
                                pattern="[0-9]*"
                                maxlength="15"
                                :value="form.contact_number"
                                @input="handleContactInput"
                                @keypress="handleContactKeyPress"
                                required
                                placeholder="09123456789"
                                class="block w-full rounded-xl border-gray-200 pl-10 pr-3.5 py-2.5 text-sm text-gray-900 placeholder:text-gray-400 focus:border-teal-500 focus:ring-teal-500 transition"
                            />
                        </div>
                        <InputError
                            class="mt-1.5"
                            :message="form.errors.contact_number"
                        />
                    </div>

                    <!-- Address -->
                    <div>
                        <label
                            for="address"
                            class="block text-xs font-semibold uppercase tracking-wider text-gray-700 mb-1.5"
                        >
                            Residential Address
                            <span class="text-gray-400 font-normal"
                                >(Optional)</span
                            >
                        </label>
                        <div class="relative rounded-xl shadow-sm">
                            <div
                                class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3.5 text-gray-400"
                            >
                                <i class="pi pi-map-marker text-sm"></i>
                            </div>
                            <input
                                id="address"
                                type="text"
                                v-model="form.address"
                                placeholder="Street, Barangay, City"
                                class="block w-full rounded-xl border-gray-200 pl-10 pr-3.5 py-2.5 text-sm text-gray-900 placeholder:text-gray-400 focus:border-teal-500 focus:ring-teal-500 transition"
                            />
                        </div>
                        <InputError
                            class="mt-1.5"
                            :message="form.errors.address"
                        />
                    </div>
                </div>
            </div>

            <!-- SECTION 3: Account Credentials -->
            <div class="space-y-4">
                <div
                    class="flex items-center gap-2 border-b border-gray-100 pb-2"
                >
                    <div
                        class="h-7 w-7 rounded-lg bg-teal-100 text-teal-700 flex items-center justify-center text-xs"
                    >
                        <i class="pi pi-lock font-bold"></i>
                    </div>
                    <h3
                        class="text-sm font-bold uppercase tracking-wider text-gray-800"
                    >
                        3. Portal Login Credentials
                    </h3>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <!-- Email -->
                    <div class="sm:col-span-2">
                        <label
                            for="email"
                            class="block text-xs font-semibold uppercase tracking-wider text-gray-700 mb-1.5"
                        >
                            Email Address <span class="text-red-500">*</span>
                        </label>
                        <div class="relative rounded-xl shadow-sm">
                            <div
                                class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3.5 text-gray-400"
                            >
                                <i class="pi pi-envelope text-sm"></i>
                            </div>
                            <input
                                id="email"
                                type="email"
                                v-model="form.email"
                                required
                                autocomplete="username"
                                placeholder="you@example.com"
                                class="block w-full rounded-xl border-gray-200 pl-10 pr-3.5 py-2.5 text-sm text-gray-900 placeholder:text-gray-400 focus:border-teal-500 focus:ring-teal-500 transition"
                            />
                        </div>
                        <InputError
                            class="mt-1.5"
                            :message="form.errors.email"
                        />
                    </div>

                    <!-- Password -->
                    <div>
                        <label
                            for="password"
                            class="block text-xs font-semibold uppercase tracking-wider text-gray-700 mb-1.5"
                        >
                            Password <span class="text-red-500">*</span>
                        </label>
                        <div class="relative rounded-xl shadow-sm">
                            <div
                                class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3.5 text-gray-400"
                            >
                                <i class="pi pi-lock text-sm"></i>
                            </div>
                            <input
                                id="password"
                                :type="showPassword ? 'text' : 'password'"
                                v-model="form.password"
                                required
                                autocomplete="new-password"
                                placeholder="Minimum 8 characters"
                                class="block w-full rounded-xl border-gray-200 pl-10 pr-10 py-2.5 text-sm text-gray-900 placeholder:text-gray-400 focus:border-teal-500 focus:ring-teal-500 transition"
                            />
                            <button
                                type="button"
                                @click="showPassword = !showPassword"
                                class="absolute inset-y-0 right-0 flex items-center pr-3.5 text-gray-400 hover:text-gray-600 focus:outline-none"
                                tabindex="-1"
                                :aria-label="
                                    showPassword
                                        ? 'Hide password'
                                        : 'Show password'
                                "
                            >
                                <i
                                    :class="
                                        showPassword
                                            ? 'pi pi-eye-slash'
                                            : 'pi pi-eye'
                                    "
                                    class="text-sm"
                                ></i>
                            </button>
                        </div>
                        <InputError
                            class="mt-1.5"
                            :message="form.errors.password"
                        />
                    </div>

                    <!-- Confirm Password -->
                    <div>
                        <label
                            for="password_confirmation"
                            class="block text-xs font-semibold uppercase tracking-wider text-gray-700 mb-1.5"
                        >
                            Confirm Password <span class="text-red-500">*</span>
                        </label>
                        <div class="relative rounded-xl shadow-sm">
                            <div
                                class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3.5 text-gray-400"
                            >
                                <i class="pi pi-lock text-sm"></i>
                            </div>
                            <input
                                id="password_confirmation"
                                :type="
                                    showConfirmPassword ? 'text' : 'password'
                                "
                                v-model="form.password_confirmation"
                                required
                                autocomplete="new-password"
                                placeholder="Re-enter password"
                                class="block w-full rounded-xl border-gray-200 pl-10 pr-10 py-2.5 text-sm text-gray-900 placeholder:text-gray-400 focus:border-teal-500 focus:ring-teal-500 transition"
                            />
                            <button
                                type="button"
                                @click="
                                    showConfirmPassword = !showConfirmPassword
                                "
                                class="absolute inset-y-0 right-0 flex items-center pr-3.5 text-gray-400 hover:text-gray-600 focus:outline-none"
                                tabindex="-1"
                                :aria-label="
                                    showConfirmPassword
                                        ? 'Hide password'
                                        : 'Show password'
                                "
                            >
                                <i
                                    :class="
                                        showConfirmPassword
                                            ? 'pi pi-eye-slash'
                                            : 'pi pi-eye'
                                    "
                                    class="text-sm"
                                ></i>
                            </button>
                        </div>
                        <InputError
                            class="mt-1.5"
                            :message="form.errors.password_confirmation"
                        />
                    </div>
                </div>
            </div>

            <!-- SECTION 4: Terms & Acknowledgment -->
            <div class="rounded-xl bg-teal-50/70 border border-teal-200/70 p-4">
                <label
                    class="flex items-start gap-3 cursor-pointer select-none"
                >
                    <input
                        type="checkbox"
                        v-model="form.confirm_adult"
                        required
                        class="mt-0.5 h-4 w-4 rounded border-gray-300 text-teal-600 focus:ring-teal-500 transition"
                    />
                    <span class="text-xs text-gray-700 leading-relaxed">
                        <template v-if="isUnder18">
                            I confirm that I have
                            <strong class="text-gray-900"
                                >parent or legal guardian consent</strong
                            >, and all personal and guardian information
                            provided above is accurate for creating the official
                            patient medical record at Vitality Smile Dental
                            Clinic.
                        </template>
                        <template v-else>
                            I confirm that I am
                            <strong class="text-gray-900"
                                >at least 18 years of age</strong
                            >, and the personal information provided above is
                            accurate for creating my official patient medical
                            record at Vitality Smile Dental Clinic.
                        </template>
                    </span>
                </label>
            </div>

            <!-- Submit Button -->
            <div class="pt-2">
                <button
                    type="submit"
                    :disabled="form.processing"
                    class="w-full flex items-center justify-center gap-2 rounded-xl bg-gradient-to-r from-teal-600 to-emerald-600 hover:from-teal-700 hover:to-emerald-700 text-white font-semibold py-3 px-6 shadow-lg shadow-teal-600/25 hover:shadow-teal-600/35 focus:outline-none focus:ring-2 focus:ring-teal-500 focus:ring-offset-2 disabled:opacity-50 disabled:cursor-not-allowed transition duration-150"
                >
                    <i
                        v-if="form.processing"
                        class="pi pi-spin pi-spinner text-base"
                    ></i>
                    <i v-else class="pi pi-user-plus text-base"></i>
                    <span>{{
                        form.processing
                            ? "Registering Patient Record..."
                            : "Complete Patient Registration"
                    }}</span>
                </button>
            </div>
        </form>

        <!-- Divider & Sign In Callout -->
        <div class="mt-8 pt-5 border-t border-gray-100 text-center">
            <p class="text-xs text-gray-500">
                Already registered with Vitality Smile?
                <Link
                    :href="route('login')"
                    class="font-semibold text-teal-600 hover:text-teal-700 hover:underline ml-1"
                >
                    Sign in to your account
                </Link>
            </p>
        </div>
    </GuestLayout>
</template>
