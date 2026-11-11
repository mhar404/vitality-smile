<script setup>
import { useForm } from "@inertiajs/vue3";
import { ref } from "vue";

const passwordInput = ref(null);
const currentPasswordInput = ref(null);

const form = useForm({
    current_password: "",
    password: "",
    password_confirmation: "",
});

const updatePassword = () => {
    form.put(route("password.update"), {
        preserveScroll: true,
        onSuccess: () => form.reset(),
        onError: () => {
            if (form.errors.password) {
                form.reset("password", "password_confirmation");
                passwordInput.value?.focus();
            }
            if (form.errors.current_password) {
                form.reset("current_password");
                currentPasswordInput.value?.focus();
            }
        },
    });
};
</script>

<template>
    <section>
        <header class="border-b border-gray-100 pb-4">
            <h2 class="text-base font-bold text-gray-900">
                Security & Password Update
            </h2>
            <p class="mt-0.5 text-xs text-gray-500">
                Ensure your dental portal account uses a strong, unique password with at least 8 characters.
            </p>
        </header>

        <form @submit.prevent="updatePassword" class="mt-6 space-y-4">
            <div>
                <label for="current_password" class="block text-xs font-semibold text-gray-700 mb-1">
                    Current Password
                </label>
                <div class="relative">
                    <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3.5 text-gray-400">
                        <i class="pi pi-lock text-xs"></i>
                    </div>
                    <input
                        id="current_password"
                        ref="currentPasswordInput"
                        v-model="form.current_password"
                        type="password"
                        placeholder="••••••••"
                        class="w-full rounded-xl border-gray-300 pl-9 text-sm focus:border-teal-500 focus:ring-teal-500 transition"
                        autocomplete="current-password"
                    />
                </div>
                <p v-if="form.errors.current_password" class="mt-1 text-xs text-red-600">
                    {{ form.errors.current_password }}
                </p>
            </div>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                    <label for="password" class="block text-xs font-semibold text-gray-700 mb-1">
                        New Password
                    </label>
                    <div class="relative">
                        <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3.5 text-gray-400">
                            <i class="pi pi-key text-xs"></i>
                        </div>
                        <input
                            id="password"
                            ref="passwordInput"
                            v-model="form.password"
                            type="password"
                            placeholder="At least 8 characters"
                            class="w-full rounded-xl border-gray-300 pl-9 text-sm focus:border-teal-500 focus:ring-teal-500 transition"
                            autocomplete="new-password"
                        />
                    </div>
                    <p v-if="form.errors.password" class="mt-1 text-xs text-red-600">
                        {{ form.errors.password }}
                    </p>
                </div>

                <div>
                    <label for="password_confirmation" class="block text-xs font-semibold text-gray-700 mb-1">
                        Confirm New Password
                    </label>
                    <div class="relative">
                        <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3.5 text-gray-400">
                            <i class="pi pi-check-circle text-xs"></i>
                        </div>
                        <input
                            id="password_confirmation"
                            v-model="form.password_confirmation"
                            type="password"
                            placeholder="Repeat new password"
                            class="w-full rounded-xl border-gray-300 pl-9 text-sm focus:border-teal-500 focus:ring-teal-500 transition"
                            autocomplete="new-password"
                        />
                    </div>
                    <p v-if="form.errors.password_confirmation" class="mt-1 text-xs text-red-600">
                        {{ form.errors.password_confirmation }}
                    </p>
                </div>
            </div>

            <div class="flex items-center justify-between border-t border-gray-100 pt-4">
                <span class="text-[11px] text-gray-400">
                    Your password protects confidential dental charts and billing records.
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
                            <i class="pi pi-check text-xs"></i> Password Changed
                        </span>
                    </Transition>

                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="inline-flex items-center gap-2 rounded-xl bg-teal-700 px-5 py-2.5 text-xs font-bold text-white shadow-sm transition hover:bg-teal-800 focus:outline-none focus:ring-2 focus:ring-teal-500 focus:ring-offset-2 disabled:opacity-50"
                    >
                        <i v-if="form.processing" class="pi pi-spin pi-spinner text-xs"></i>
                        <i v-else class="pi pi-shield text-xs"></i>
                        Update Password
                    </button>
                </div>
            </div>
        </form>
    </section>
</template>
