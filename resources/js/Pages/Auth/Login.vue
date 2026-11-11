<script setup>
import { ref } from 'vue';
import GuestLayout from '@/Layouts/GuestLayout.vue';
import InputError from '@/Components/InputError.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

defineProps({
    canResetPassword: {
        type: Boolean,
    },
    status: {
        type: String,
    },
});

const form = useForm({
    email: '',
    password: '',
    remember: false,
});

const showPassword = ref(false);

const submit = () => {
    form.post(route('login'), {
        onFinish: () => form.reset('password'),
    });
};
</script>

<template>
    <GuestLayout maxWidth="max-w-md">
        <Head title="Sign In - Vitality Smile" />

        <!-- Header -->
        <div class="mb-6 text-center">
            <h2 class="text-2xl font-bold tracking-tight text-gray-900">
                Welcome Back
            </h2>
            <p class="mt-1.5 text-sm text-gray-500">
                Sign in to manage your appointments and clinic records
            </p>
        </div>

        <!-- Flash Status Notification -->
        <div
            v-if="status"
            class="mb-5 flex items-center gap-2 rounded-xl bg-emerald-50 border border-emerald-200/80 p-3.5 text-sm font-medium text-emerald-800"
        >
            <i class="pi pi-check-circle text-emerald-600 text-base"></i>
            <span>{{ status }}</span>
        </div>

        <!-- OAuth Flash Error Notification -->
        <div
            v-if="$page.props.errors && $page.props.errors.email && !form.errors.email"
            class="mb-5 flex items-center gap-2 rounded-xl bg-red-50 border border-red-200/80 p-3.5 text-sm font-medium text-red-800"
        >
            <i class="pi pi-exclamation-circle text-red-600 text-base"></i>
            <span>{{ $page.props.errors.email }}</span>
        </div>

        <!-- Google Sign In Button -->
        <div class="mb-5">
            <a
                href="/auth/google"
                class="w-full flex items-center justify-center gap-3 rounded-xl border border-gray-300 bg-white py-2.5 px-4 text-sm font-semibold text-gray-700 shadow-sm hover:bg-gray-50 hover:border-gray-400 focus:outline-none focus:ring-2 focus:ring-teal-500 focus:ring-offset-2 transition duration-150 group"
            >
                <svg class="h-5 w-5" viewBox="0 0 24 24" aria-hidden="true">
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
                <span class="group-hover:text-gray-900 transition">Continue with Google</span>
            </a>
        </div>

        <!-- Divider -->
        <div class="relative my-5">
            <div class="absolute inset-0 flex items-center">
                <div class="w-full border-t border-gray-200"></div>
            </div>
            <div class="relative flex justify-center text-xs uppercase">
                <span class="bg-white px-3 text-gray-400 font-semibold tracking-wider">
                    Or sign in with email
                </span>
            </div>
        </div>

        <form @submit.prevent="submit" class="space-y-4">
            <!-- Email Field -->
            <div>
                <label for="email" class="block text-xs font-semibold uppercase tracking-wider text-gray-700 mb-1.5">
                    Email Address
                </label>
                <div class="relative rounded-xl shadow-sm">
                    <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3.5 text-gray-400">
                        <i class="pi pi-envelope text-sm"></i>
                    </div>
                    <input
                        id="email"
                        type="email"
                        v-model="form.email"
                        required
                        autofocus
                        autocomplete="username"
                        placeholder="you@example.com"
                        class="block w-full rounded-xl border-gray-200 pl-10 pr-3.5 py-2.5 text-sm text-gray-900 placeholder:text-gray-400 focus:border-teal-500 focus:ring-teal-500 transition"
                    />
                </div>
                <InputError class="mt-1.5" :message="form.errors.email" />
            </div>

            <!-- Password Field -->
            <div>
                <div class="flex items-center justify-between mb-1.5">
                    <label for="password" class="block text-xs font-semibold uppercase tracking-wider text-gray-700">
                        Password
                    </label>
                    <Link
                        v-if="canResetPassword"
                        :href="route('password.request')"
                        class="text-xs font-medium text-teal-600 hover:text-teal-700 hover:underline transition"
                    >
                        Forgot password?
                    </Link>
                </div>
                <div class="relative rounded-xl shadow-sm">
                    <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3.5 text-gray-400">
                        <i class="pi pi-lock text-sm"></i>
                    </div>
                    <input
                        id="password"
                        :type="showPassword ? 'text' : 'password'"
                        v-model="form.password"
                        required
                        autocomplete="current-password"
                        placeholder="••••••••"
                        class="block w-full rounded-xl border-gray-200 pl-10 pr-10 py-2.5 text-sm text-gray-900 placeholder:text-gray-400 focus:border-teal-500 focus:ring-teal-500 transition"
                    />
                    <button
                        type="button"
                        @click="showPassword = !showPassword"
                        class="absolute inset-y-0 right-0 flex items-center pr-3.5 text-gray-400 hover:text-gray-600 focus:outline-none"
                        tabindex="-1"
                        :aria-label="showPassword ? 'Hide password' : 'Show password'"
                    >
                        <i :class="showPassword ? 'pi pi-eye-slash' : 'pi pi-eye'" class="text-sm"></i>
                    </button>
                </div>
                <InputError class="mt-1.5" :message="form.errors.password" />
            </div>

            <!-- Remember Me -->
            <div class="flex items-center justify-between pt-1">
                <label class="flex items-center cursor-pointer select-none">
                    <input
                        type="checkbox"
                        v-model="form.remember"
                        class="h-4 w-4 rounded border-gray-300 text-teal-600 focus:ring-teal-500 transition"
                    />
                    <span class="ml-2 text-xs font-medium text-gray-600">Remember this device</span>
                </label>
            </div>

            <!-- Submit Button -->
            <div class="pt-2">
                <button
                    type="submit"
                    :disabled="form.processing"
                    class="w-full flex items-center justify-center gap-2 rounded-xl bg-gradient-to-r from-teal-600 to-emerald-600 hover:from-teal-700 hover:to-emerald-700 text-white font-semibold py-2.5 px-4 shadow-lg shadow-teal-600/25 hover:shadow-teal-600/35 focus:outline-none focus:ring-2 focus:ring-teal-500 focus:ring-offset-2 disabled:opacity-50 transition duration-150"
                >
                    <i v-if="form.processing" class="pi pi-spin pi-spinner text-sm"></i>
                    <i v-else class="pi pi-sign-in text-sm"></i>
                    <span>{{ form.processing ? 'Signing in...' : 'Sign In' }}</span>
                </button>
            </div>
        </form>

        <!-- Divider & Register Callout -->
        <div class="mt-6 pt-5 border-t border-gray-100 text-center">
            <p class="text-xs text-gray-500">
                New to Vitality Smile?
                <Link
                    :href="route('register')"
                    class="font-semibold text-teal-600 hover:text-teal-700 hover:underline ml-1"
                >
                    Register as a Patient (18+)
                </Link>
            </p>
        </div>
    </GuestLayout>
</template>

