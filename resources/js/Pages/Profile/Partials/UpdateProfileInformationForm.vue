<script setup>
import { Link, useForm, usePage } from "@inertiajs/vue3";

defineProps({
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
    name: user.name,
    email: user.email,
});
</script>

<template>
    <section>
        <header class="border-b border-gray-100 pb-4">
            <h2 class="text-base font-bold text-gray-900">
                Staff Profile Information
            </h2>
            <p class="mt-0.5 text-xs text-gray-500">
                Update your clinic team member display name and contact email address.
            </p>
        </header>

        <form
            @submit.prevent="form.patch(route('profile.update'), { preserveScroll: true })"
            class="mt-6 space-y-4"
        >
            <div>
                <label for="name" class="block text-xs font-semibold text-gray-700 mb-1">
                    Full Name <span class="text-red-500">*</span>
                </label>
                <div class="relative">
                    <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3.5 text-gray-400">
                        <i class="pi pi-user text-xs"></i>
                    </div>
                    <input
                        id="name"
                        v-model="form.name"
                        type="text"
                        required
                        class="w-full rounded-xl border-gray-300 pl-9 text-sm focus:border-teal-500 focus:ring-teal-500 transition"
                    />
                </div>
                <p v-if="form.errors.name" class="mt-1 text-xs text-red-600">{{ form.errors.name }}</p>
            </div>

            <div>
                <label for="email" class="block text-xs font-semibold text-gray-700 mb-1">
                    Email Address <span class="text-red-500">*</span>
                </label>
                <div class="relative">
                    <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3.5 text-gray-400">
                        <i class="pi pi-envelope text-xs"></i>
                    </div>
                    <input
                        id="email"
                        v-model="form.email"
                        type="email"
                        required
                        class="w-full rounded-xl border-gray-300 pl-9 text-sm focus:border-teal-500 focus:ring-teal-500 transition"
                    />
                </div>
                <p v-if="form.errors.email" class="mt-1 text-xs text-red-600">{{ form.errors.email }}</p>

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
                            Click here to re-send verification.
                        </Link>
                        <p v-show="status === 'verification-link-sent'" class="mt-1 font-bold text-green-700">
                            A new verification link has been sent to your email address.
                        </p>
                    </div>
                </div>
            </div>

            <div class="flex items-center justify-between border-t border-gray-100 pt-4">
                <span class="text-[11px] text-gray-400">
                    Clinic Role: <span class="font-bold uppercase text-teal-800">{{ user.role }}</span>
                </span>

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
                        class="inline-flex items-center gap-2 rounded-xl bg-teal-700 px-5 py-2.5 text-xs font-bold text-white shadow-sm transition hover:bg-teal-800 focus:outline-none focus:ring-2 focus:ring-teal-500 focus:ring-offset-2 disabled:opacity-50"
                    >
                        <i v-if="form.processing" class="pi pi-spin pi-spinner text-xs"></i>
                        <i v-else class="pi pi-save text-xs"></i>
                        Save Changes
                    </button>
                </div>
            </div>
        </form>
    </section>
</template>
