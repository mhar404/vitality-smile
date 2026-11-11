<script setup>
import { computed, ref, onMounted, onUnmounted, watch } from 'vue';
import GuestLayout from '@/Layouts/GuestLayout.vue';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';

const props = defineProps({
    status: {
        type: String,
        default: '',
    },
});

const page = usePage();
const form = useForm({});

const user = computed(() => page.props.auth?.user || {});
const userEmail = computed(() => user.value?.email || 'your registered email');

const verificationLinkSent = computed(
    () => props.status === 'verification-link-sent',
);

// Cooldown timer logic for resending emails
const cooldownSeconds = ref(0);
let timerInterval = null;

const startCooldown = (seconds = 60) => {
    cooldownSeconds.value = seconds;
    if (timerInterval) clearInterval(timerInterval);
    timerInterval = setInterval(() => {
        if (cooldownSeconds.value > 0) {
            cooldownSeconds.value--;
        } else {
            clearInterval(timerInterval);
            timerInterval = null;
        }
    }, 1000);
};

// If page loads or updates with verification-link-sent status, trigger cooldown
watch(
    () => props.status,
    (newVal) => {
        if (newVal === 'verification-link-sent') {
            startCooldown(60);
        }
    },
    { immediate: true },
);

onUnmounted(() => {
    if (timerInterval) clearInterval(timerInterval);
});

const submit = () => {
    if (cooldownSeconds.value > 0 || form.processing) return;

    form.post(route('verification.send'), {
        onSuccess: () => {
            startCooldown(60);
        },
    });
};

// Smart webmail provider link based on email domain
const webmailProvider = computed(() => {
    const email = (user.value?.email || '').toLowerCase();
    if (email.endsWith('@gmail.com')) {
        return { name: 'Open Gmail', url: 'https://mail.google.com', icon: 'pi pi-google' };
    }
    if (email.endsWith('@yahoo.com') || email.endsWith('@ymail.com')) {
        return { name: 'Open Yahoo Mail', url: 'https://mail.yahoo.com', icon: 'pi pi-envelope' };
    }
    if (email.endsWith('@outlook.com') || email.endsWith('@hotmail.com') || email.endsWith('@live.com')) {
        return { name: 'Open Outlook', url: 'https://outlook.live.com', icon: 'pi pi-microsoft' };
    }
    if (email.endsWith('@icloud.com')) {
        return { name: 'Open iCloud Mail', url: 'https://www.icloud.com/mail', icon: 'pi pi-apple' };
    }
    return null;
});
</script>

<template>
    <GuestLayout maxWidth="max-w-lg">
        <Head title="Verify Email Address - Vitality Smile" />

        <div class="text-center">
            <!-- Animated Icon Hero Badge -->
            <div class="relative mx-auto mb-6 flex h-20 w-20 items-center justify-center">
                <!-- Ambient outer glow ring -->
                <div
                    :class="[
                        'absolute inset-0 rounded-3xl blur-xl transition-all duration-500',
                        verificationLinkSent
                            ? 'bg-emerald-400/40 animate-pulse'
                            : 'bg-teal-400/30'
                    ]"
                ></div>

                <!-- Icon Container -->
                <div
                    :class="[
                        'relative flex h-20 w-20 items-center justify-center rounded-3xl border shadow-lg transition-all duration-500',
                        verificationLinkSent
                            ? 'border-emerald-300 bg-gradient-to-tr from-emerald-500 to-teal-400 text-white shadow-emerald-500/20'
                            : 'border-teal-200/80 bg-gradient-to-tr from-teal-600 via-teal-500 to-emerald-400 text-white shadow-teal-600/20'
                    ]"
                >
                    <svg
                        v-if="!verificationLinkSent"
                        class="h-9 w-9 transform transition-transform duration-300 hover:scale-110"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke="currentColor"
                        stroke-width="1.8"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"
                        />
                    </svg>
                    <svg
                        v-else
                        class="h-10 w-10 text-white animate-bounce"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke="currentColor"
                        stroke-width="2.2"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"
                        />
                    </svg>

                    <!-- Small Status Indicator Pill -->
                    <span
                        v-if="verificationLinkSent"
                        class="absolute -top-1.5 -right-1.5 flex h-6 w-6 items-center justify-center rounded-full bg-emerald-600 text-white ring-4 ring-white shadow-md"
                    >
                        <i class="pi pi-check text-[11px] font-bold"></i>
                    </span>
                </div>
            </div>

            <!-- Header Titles -->
            <h2 class="text-2xl font-extrabold tracking-tight text-gray-900 sm:text-3xl">
                <span v-if="verificationLinkSent" class="text-emerald-700">Verification Email Sent!</span>
                <span v-else>Verify Your Email</span>
            </h2>

            <p class="mt-2 text-sm text-gray-600 leading-relaxed max-w-md mx-auto">
                <span v-if="verificationLinkSent">
                    We just dispatched a fresh verification link. Please check your inbox and click the button to activate your account.
                </span>
                <span v-else>
                    Thanks for joining Vitality Smile! Before accessing your patient dashboard, please verify your email address to ensure your medical records stay secure.
                </span>
            </p>

            <!-- Recipient Email Pill -->
            <div class="mt-4 inline-flex items-center gap-2 rounded-full bg-slate-100 border border-slate-200/80 px-4 py-1.5 text-xs sm:text-sm font-medium text-slate-800 shadow-inner">
                <i class="pi pi-at text-teal-600"></i>
                <span class="truncate max-w-[260px] sm:max-w-xs">{{ userEmail }}</span>
                <span class="inline-block h-1.5 w-1.5 rounded-full bg-teal-500"></span>
            </div>
        </div>

        <!-- Success Alert Callout Banner (when link is sent) -->
        <transition
            enter-active-class="transition ease-out duration-300"
            enter-from-class="opacity-0 translate-y-2 scale-95"
            enter-to-class="opacity-100 translate-y-0 scale-100"
            leave-active-class="transition ease-in duration-200"
            leave-from-class="opacity-100 translate-y-0"
            leave-to-class="opacity-0 translate-y-2"
        >
            <div
                v-if="verificationLinkSent"
                class="mt-6 rounded-2xl bg-emerald-50/90 border border-emerald-200/90 p-4 text-left shadow-sm shadow-emerald-500/5"
            >
                <div class="flex items-start gap-3">
                    <div class="mt-0.5 flex h-7 w-7 shrink-0 items-center justify-center rounded-xl bg-emerald-600 text-white shadow-sm">
                        <i class="pi pi-check text-xs font-bold"></i>
                    </div>
                    <div class="flex-1 text-sm text-emerald-900">
                        <div class="font-bold text-emerald-950">New Link Dispatched</div>
                        <p class="mt-0.5 text-xs text-emerald-800/90 leading-relaxed">
                            A new verification link has been sent to your email. It may take a couple of minutes to arrive. Please also check your spam or promotions folder.
                        </p>
                    </div>
                </div>
            </div>
        </transition>

        <!-- Step-by-Step Instructions Card -->
        <div class="mt-6 rounded-2xl bg-gradient-to-b from-slate-50 to-teal-50/30 border border-teal-100/70 p-4 sm:p-5 text-left">
            <h3 class="text-xs font-bold uppercase tracking-wider text-teal-800/80 flex items-center gap-1.5 mb-3">
                <i class="pi pi-info-circle text-teal-600"></i>
                Next Steps to Activate Your Account
            </h3>

            <ol class="space-y-3 text-xs sm:text-sm text-gray-700">
                <li class="flex items-start gap-3">
                    <span class="flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-teal-600 font-bold text-white text-[11px] shadow-sm">
                        1
                    </span>
                    <span>
                        Open the verification email sent by <strong class="text-gray-900">Vitality Smile Dental Clinic</strong>.
                    </span>
                </li>
                <li class="flex items-start gap-3">
                    <span class="flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-teal-600 font-bold text-white text-[11px] shadow-sm">
                        2
                    </span>
                    <span>
                        Click the <strong class="text-teal-700">"Verify Email Address"</strong> button in the message.
                    </span>
                </li>
                <li class="flex items-start gap-3">
                    <span class="flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-teal-600 font-bold text-white text-[11px] shadow-sm">
                        3
                    </span>
                    <span>
                        You'll be automatically redirected to your verified <strong class="text-gray-900">Patient Dashboard</strong>.
                    </span>
                </li>
            </ol>
        </div>

        <!-- Quick Webmail Launcher Button (if detected) -->
        <div v-if="webmailProvider" class="mt-5">
            <a
                :href="webmailProvider.url"
                target="_blank"
                rel="noopener noreferrer"
                class="w-full flex items-center justify-center gap-2 rounded-xl border border-teal-200 bg-white py-2.5 px-4 text-xs sm:text-sm font-semibold text-teal-700 shadow-sm hover:bg-teal-50 hover:border-teal-300 focus:outline-none focus:ring-2 focus:ring-teal-500 focus:ring-offset-1 transition group"
            >
                <i :class="webmailProvider.icon" class="text-teal-600 group-hover:scale-110 transition-transform"></i>
                <span>{{ webmailProvider.name }}</span>
                <i class="pi pi-external-link text-[11px] text-teal-500 ml-0.5"></i>
            </a>
        </div>

        <!-- Action Buttons -->
        <div class="mt-6 pt-5 border-t border-slate-100 flex flex-col sm:flex-row items-center justify-between gap-3">
            <!-- Resend Verification Email Form -->
            <form @submit.prevent="submit" class="w-full sm:w-auto">
                <button
                    type="submit"
                    :disabled="form.processing || cooldownSeconds > 0"
                    :class="[
                        'w-full sm:w-auto inline-flex items-center justify-center gap-2 rounded-xl py-2.5 px-5 text-sm font-semibold text-white shadow-md transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-teal-500 focus:ring-offset-2',
                        cooldownSeconds > 0 || form.processing
                            ? 'bg-slate-300 text-slate-500 cursor-not-allowed shadow-none'
                            : 'bg-gradient-to-r from-teal-600 via-teal-700 to-emerald-700 hover:from-teal-700 hover:to-emerald-800 shadow-teal-700/25 active:scale-[0.98]'
                    ]"
                >
                    <i
                        v-if="form.processing"
                        class="pi pi-spin pi-spinner text-sm"
                    ></i>
                    <i
                        v-else-if="cooldownSeconds > 0"
                        class="pi pi-clock text-sm"
                    ></i>
                    <i
                        v-else
                        class="pi pi-send text-sm"
                    ></i>

                    <span>
                        <template v-if="form.processing">Sending link...</template>
                        <template v-else-if="cooldownSeconds > 0">Resend in {{ cooldownSeconds }}s</template>
                        <template v-else-if="verificationLinkSent">Send Another Email</template>
                        <template v-else>Resend Verification Email</template>
                    </span>
                </button>
            </form>

            <!-- Logout Link -->
            <Link
                :href="route('logout')"
                method="post"
                as="button"
                class="w-full sm:w-auto inline-flex items-center justify-center gap-1.5 py-2.5 px-4 text-xs sm:text-sm font-medium text-slate-600 hover:text-slate-900 hover:bg-slate-100 rounded-xl transition focus:outline-none focus:ring-2 focus:ring-slate-300"
            >
                <i class="pi pi-sign-out text-xs"></i>
                <span>Log Out</span>
            </Link>
        </div>

        <!-- Help / Support Footer Note -->
        <div class="mt-6 text-center text-xs text-gray-500">
            <p>
                Didn't see the email? Please inspect your <span class="font-medium text-gray-700">Spam or Junk</span> folder, or wait a minute before requesting a new link.
            </p>
        </div>
    </GuestLayout>
</template>
