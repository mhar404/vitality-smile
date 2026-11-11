<script setup>
import { ref, computed, onMounted, onUnmounted } from "vue";
import Navbar from "@/Components/Navbar.vue";
import ServiceIcon from "@/Components/ServiceIcon.vue";
import { Link } from "@inertiajs/vue3";

const props = defineProps({
    services: {
        type: Array,
        default: () => [],
    },
});

const defaultServices = [
    {
        title: "Oral Prophylaxis (Dental Cleaning)",
        category: "general",
        icon: "shield",
        description:
            "Professional ultrasonic cleaning that removes stubborn plaque, tartar, and surface stains to preserve healthy gums.",
        formatted_price: "₱800 - ₱1,000",
    },
    {
        title: "Tooth Restoration (Composite Filling)",
        category: "restorative",
        icon: "tooth",
        description:
            "High-grade tooth-colored composite resin fillings that repair cavities and chipped edges, blending seamlessly with enamel.",
        formatted_price: "₱800 - ₱1,000",
    },
    {
        title: "Routine Tooth Extraction",
        category: "surgery",
        icon: "surgery",
        description:
            "Safe, gentle removal of severely decayed or compromised teeth performed with modern, effective local anesthesia.",
        formatted_price: "₱800 - ₱1,000",
    },
    {
        title: "Root Canal Treatment",
        category: "restorative",
        icon: "root_canal",
        description:
            "Specialized endodontic therapy that preserves infected or damaged teeth, relieving chronic pain and saving tooth structure.",
        formatted_price: "₱5,000 - ₱8,000",
    },
    {
        title: "Orthodontics / Braces",
        category: "orthodontics",
        icon: "denture",
        description:
            "Comprehensive bracket alignment and smile transformation solutions designed for both adults and teenagers.",
        formatted_price: "Consultation & Custom Quote",
    },
    {
        title: "In-Clinic Teeth Whitening",
        category: "cosmetic",
        icon: "sparkles",
        description:
            "Professional LED-activated whitening system that lifts deep coffee, tea, and aging stains by multiple shades safely.",
        formatted_price: "₱3,500 - ₱6,000",
    },
];

const displayedServices = computed(() => {
    return props.services && props.services.length > 0
        ? props.services
        : defaultServices;
});

const getCategoryBadgeClass = (category) => {
    const map = {
        general: "bg-emerald-50 text-emerald-700 border-emerald-200",
        restorative: "bg-blue-50 text-blue-700 border-blue-200",
        surgery: "bg-rose-50 text-rose-700 border-rose-200",
        cosmetic: "bg-purple-50 text-purple-700 border-purple-200",
        prosthodontics: "bg-amber-50 text-amber-700 border-amber-200",
        orthodontics: "bg-indigo-50 text-indigo-700 border-indigo-200",
    };
    return map[category] || "bg-teal-50 text-teal-700 border-teal-200";
};

const getCategoryLabel = (category) => {
    const map = {
        general: "General & Preventive",
        restorative: "Restorative",
        surgery: "Oral Surgery",
        cosmetic: "Cosmetic",
        prosthodontics: "Dentures",
        orthodontics: "Orthodontics",
    };
    return map[category] || category || "Dental Care";
};

// Hero Carousel State and Logic
const carouselSlides = [
    {
        image: "/images/carousel/carousel-1.jpg",
        title: "Modern Operatory Suite",
        subtitle: "Sterile clinical rooms with ultrasonic & diagnostic tech.",
        tag: "Sterile Operatory",
        badge: "State-of-the-Art",
        icon: "pi-shield",
    },
    {
        image: "/images/carousel/carousel-2.jpg",
        title: "Specialist Consultations",
        subtitle: "Personalized treatment plans tailored to your goals.",
        tag: "Expert Dentists",
        badge: "Licensed Care",
        icon: "pi-user",
    },
    {
        image: "/images/carousel/carousel-3.jpg",
        title: "Gentle Chairside Care",
        subtitle: "Relaxing, anxiety-free dentistry for the whole family.",
        tag: "Patient Comfort",
        badge: "Painless Focus",
        icon: "pi-heart",
    },
];

const currentSlide = ref(0);
const isPaused = ref(false);
let autoplayTimer = null;

const startAutoplay = () => {
    stopAutoplay();
    autoplayTimer = setInterval(() => {
        if (!isPaused.value) {
            nextSlide();
        }
    }, 5000);
};

const stopAutoplay = () => {
    if (autoplayTimer) {
        clearInterval(autoplayTimer);
        autoplayTimer = null;
    }
};

const nextSlide = () => {
    currentSlide.value = (currentSlide.value + 1) % carouselSlides.length;
    startAutoplay();
};

const prevSlide = () => {
    currentSlide.value =
        (currentSlide.value - 1 + carouselSlides.length) %
        carouselSlides.length;
    startAutoplay();
};

const goToSlide = (index) => {
    currentSlide.value = index;
    startAutoplay();
};

const pauseAutoplay = () => {
    isPaused.value = true;
};

const resumeAutoplay = () => {
    isPaused.value = false;
};

// Touch swipe support for mobile
let touchStartX = 0;
let touchEndX = 0;

const handleTouchStart = (e) => {
    touchStartX = e.changedTouches[0].screenX;
};

const handleTouchEnd = (e) => {
    touchEndX = e.changedTouches[0].screenX;
    if (touchStartX - touchEndX > 50) {
        nextSlide();
    } else if (touchEndX - touchStartX > 50) {
        prevSlide();
    }
};

// Interactive Contact & Inquiry Form State
const contactForm = ref({
    name: "",
    phone: "",
    email: "",
    service: "General Dental Consultation",
    message: "",
});

const inquirySent = ref(false);
const copiedInquiry = ref(false);

const sendEmailInquiry = () => {
    if (!contactForm.value.name.trim() || !contactForm.value.message.trim()) {
        return;
    }
    const subject = encodeURIComponent(
        `Dental Inquiry: ${contactForm.value.service} - ${contactForm.value.name}`,
    );
    const body = encodeURIComponent(
        `Dear Vitality Smile Dental Clinic,\n\n` +
            `Patient Name: ${contactForm.value.name}\n` +
            `Phone Number: ${contactForm.value.phone || "N/A"}\n` +
            `Email: ${contactForm.value.email || "N/A"}\n` +
            `Inquiry / Service: ${contactForm.value.service}\n\n` +
            `Message:\n${contactForm.value.message}\n\n` +
            `Clinic Location: Unit 1 Threo Commercial Building B2 L3A Casan Residencia Subdivision Barangay Malitlit, Sta Rosa Laguna 4026`,
    );
    window.location.href = `mailto:vitalitysmile2025@gmail.com?subject=${subject}&body=${body}`;
    inquirySent.value = true;
};

const copyInquiryDetails = () => {
    if (!contactForm.value.name.trim() || !contactForm.value.message.trim()) {
        return;
    }
    const text =
        `Vitality Smile Dental Clinic - Patient Inquiry\n` +
        `Name: ${contactForm.value.name}\n` +
        `Phone: ${contactForm.value.phone || "N/A"}\n` +
        `Email: ${contactForm.value.email || "N/A"}\n` +
        `Service: ${contactForm.value.service}\n\n` +
        `Message:\n${contactForm.value.message}\n\n` +
        `Destination: vitalitysmile2025@gmail.com / 0977-328-1243`;
    navigator.clipboard.writeText(text);
    copiedInquiry.value = true;
    setTimeout(() => {
        copiedInquiry.value = false;
    }, 2500);
};

onMounted(() => {
    startAutoplay();
});

onUnmounted(() => {
    stopAutoplay();
});
</script>

<template>
    <div
        class="min-h-screen bg-white text-gray-800 dark:bg-slate-950 dark:text-slate-100 transition-colors duration-150"
    >
        <Navbar />

        <!-- Hero Section -->
        <section
            class="relative overflow-hidden bg-gradient-to-b from-teal-50/70 via-slate-50/30 to-white dark:from-slate-900/80 dark:via-slate-950 dark:to-slate-950 py-12 sm:py-16 lg:py-20"
        >
            <!-- Background Decorative Grid Pattern & Ambient Glows -->
            <div
                class="pointer-events-none absolute inset-0 bg-[radial-gradient(#0d9488_1px,transparent_1px)] [background-size:28px_28px] opacity-[0.035]"
                aria-hidden="true"
            ></div>
            <div
                class="pointer-events-none absolute -top-28 left-1/4 h-[440px] w-[640px] rounded-full bg-teal-200/35 blur-3xl"
                aria-hidden="true"
            ></div>
            <div
                class="pointer-events-none absolute top-1/3 -right-24 h-[380px] w-[380px] rounded-full bg-emerald-100/40 blur-2xl"
                aria-hidden="true"
            ></div>

            <div
                class="relative mx-auto max-w-[1440px] px-4 sm:px-6 lg:px-8 xl:px-10"
            >
                <div
                    class="grid grid-cols-1 lg:grid-cols-12 gap-12 lg:gap-8 xl:gap-12 items-center"
                >
                    <!-- Left Column: Headline, Subtitle, CTAs, Guarantees & Trust Metrics -->
                    <div class="lg:col-span-5 xl:col-span-5 text-left">
                        <!-- Top Pill Badge -->
                        <div
                            class="inline-flex items-center gap-2.5 rounded-full border border-teal-200/90 dark:border-teal-800/80 bg-white/95 dark:bg-slate-900/95 px-4 py-1.5 shadow-xs backdrop-blur-md"
                        >
                            <span class="flex h-2 w-2 relative">
                                <span
                                    class="animate-ping absolute inline-flex h-full w-full rounded-full bg-teal-400 opacity-75"
                                ></span>
                                <span
                                    class="relative inline-flex rounded-full h-2 w-2 bg-teal-600 dark:bg-teal-400"
                                ></span>
                            </span>
                            <span
                                class="text-xs font-bold tracking-wide text-teal-900 dark:text-teal-300 uppercase"
                            >
                                Vitality Smile Dental Clinic
                            </span>
                            <span class="text-teal-300 dark:text-teal-700"
                                >•</span
                            >
                            <span
                                class="text-xs font-semibold text-teal-700 dark:text-teal-400"
                                >Gentle & Modern Dentistry</span
                            >
                        </div>

                        <!-- Main Headline -->
                        <h1
                            class="mt-6 text-4xl font-extrabold tracking-tight text-gray-900 dark:text-white sm:text-5xl xl:text-6xl leading-[1.12]"
                        >
                            Your Genuine Smile,
                            <span
                                class="block mt-1 bg-gradient-to-r from-teal-700 via-teal-600 to-emerald-600 dark:from-teal-400 dark:via-teal-300 dark:to-emerald-400 bg-clip-text text-transparent"
                            >
                                Our Vital Mission
                            </span>
                        </h1>

                        <!-- Subtitle -->
                        <p
                            class="mt-5 text-base sm:text-lg leading-relaxed text-gray-600 dark:text-slate-300 max-w-xl"
                        >
                            Experience personalized, compassionate dental care
                            tailored for your comfort. From routine checkups to
                            comprehensive smile makeovers in a calm, modern
                            operatory.
                        </p>

                        <!-- Action Buttons -->
                        <div
                            class="mt-8 flex flex-col sm:flex-row items-stretch sm:items-center gap-3.5"
                        >
                            <Link
                                href="/appointments/create"
                                class="group inline-flex items-center justify-center gap-2.5 rounded-2xl bg-teal-700 hover:bg-teal-800 dark:bg-teal-600 dark:hover:bg-teal-500 text-white px-7 py-4 text-sm font-bold shadow-lg shadow-teal-700/25 hover:shadow-xl hover:shadow-teal-700/35 hover:-translate-y-0.5 transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-teal-500 focus:ring-offset-2 dark:focus:ring-offset-slate-950"
                            >
                                <i class="pi pi-calendar-plus text-sm"></i>
                                <span>Book an Appointment</span>
                                <i
                                    class="pi pi-arrow-right text-xs transition-transform duration-200 group-hover:translate-x-1"
                                ></i>
                            </Link>

                            <a
                                href="#services"
                                class="inline-flex items-center justify-center gap-2 rounded-2xl border border-gray-300/80 dark:border-slate-700 bg-white/95 dark:bg-slate-900/95 hover:bg-teal-50/50 dark:hover:bg-slate-800 hover:border-teal-300 dark:hover:border-teal-500 text-gray-700 dark:text-slate-200 hover:text-teal-800 dark:hover:text-teal-300 px-6 py-4 text-sm font-semibold shadow-xs backdrop-blur-xs transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-teal-500 focus:ring-offset-2 dark:focus:ring-offset-slate-950"
                            >
                                <i
                                    class="pi pi-compass text-teal-600 dark:text-teal-400 text-xs"
                                ></i>
                                <span>Explore Services</span>
                            </a>
                        </div>

                        <!-- Quick Assurance Row -->
                        <div
                            class="mt-4 flex flex-wrap items-center gap-x-5 gap-y-2 text-xs text-gray-500 dark:text-slate-400"
                        >
                            <div class="flex items-center gap-1.5">
                                <i
                                    class="pi pi-check-circle text-teal-600 dark:text-teal-400 text-xs font-bold"
                                ></i>
                                <span>Instant Confirmation</span>
                            </div>
                            <div class="flex items-center gap-1.5">
                                <i
                                    class="pi pi-check-circle text-teal-600 dark:text-teal-400 text-xs font-bold"
                                ></i>
                                <span>Efficient Patient Service</span>
                            </div>
                            <div class="flex items-center gap-1.5">
                                <i
                                    class="pi pi-check-circle text-teal-600 dark:text-teal-400 text-xs font-bold"
                                ></i>
                                <span>Sterile & Pain-Free</span>
                            </div>
                        </div>

                        <!-- Trust Pillars -->
                        <div
                            class="mt-9 border-t border-teal-100/80 dark:border-slate-800 pt-6 grid grid-cols-1 sm:grid-cols-3 gap-3"
                        >
                            <div
                                class="flex items-center gap-3 rounded-2xl bg-white/90 dark:bg-slate-900/90 p-3.5 border border-teal-100/60 dark:border-slate-800 shadow-2xs backdrop-blur-xs hover:border-teal-200 dark:hover:border-slate-700 transition"
                            >
                                <div
                                    class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-teal-50 dark:bg-teal-950/60 text-teal-700 dark:text-teal-400 border border-teal-100/80 dark:border-teal-900/60"
                                >
                                    <i class="pi pi-shield text-base"></i>
                                </div>
                                <div>
                                    <p
                                        class="text-sm font-bold text-gray-900 dark:text-slate-100 leading-tight"
                                    >
                                        Gentle Care
                                    </p>
                                    <p
                                        class="text-xs text-gray-500 dark:text-slate-400 mt-0.5"
                                    >
                                        Painless approach
                                    </p>
                                </div>
                            </div>

                            <div
                                class="flex items-center gap-3 rounded-2xl bg-white/90 dark:bg-slate-900/90 p-3.5 border border-teal-100/60 dark:border-slate-800 shadow-2xs backdrop-blur-xs hover:border-teal-200 dark:hover:border-slate-700 transition"
                            >
                                <div
                                    class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-teal-50 dark:bg-teal-950/60 text-teal-700 dark:text-teal-400 border border-teal-100/80 dark:border-teal-900/60"
                                >
                                    <i class="pi pi-clock text-base"></i>
                                </div>
                                <div>
                                    <p
                                        class="text-sm font-bold text-gray-900 dark:text-slate-100 leading-tight"
                                    >
                                        Mon – Fri
                                    </p>
                                    <p
                                        class="text-xs text-gray-500 dark:text-slate-400 mt-0.5"
                                    >
                                        9:00 AM – 6:00 PM
                                    </p>
                                </div>
                            </div>

                            <div
                                class="flex items-center gap-3 rounded-2xl bg-white/90 dark:bg-slate-900/90 p-3.5 border border-teal-100/60 dark:border-slate-800 shadow-2xs backdrop-blur-xs hover:border-teal-200 dark:hover:border-slate-700 transition"
                            >
                                <div
                                    class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-teal-50 dark:bg-teal-950/60 text-teal-700 dark:text-teal-400 border border-teal-100/80 dark:border-teal-900/60"
                                >
                                    <i class="pi pi-verified text-base"></i>
                                </div>
                                <div>
                                    <p
                                        class="text-sm font-bold text-gray-900 dark:text-slate-100 leading-tight"
                                    >
                                        Licensed
                                    </p>
                                    <p
                                        class="text-xs text-gray-500 dark:text-slate-400 mt-0.5"
                                    >
                                        Dental specialists
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Right Column: Elevated Clinical Photo Showcase & Interactive Switcher -->
                    <div class="lg:col-span-7 xl:col-span-7 w-full">
                        <div
                            class="relative mx-auto max-w-2xl lg:max-w-none w-full"
                        >
                            <!-- Background Ambient Aura -->
                            <div
                                class="pointer-events-none absolute -inset-3 rounded-[2.5rem] bg-gradient-to-tr from-teal-500/20 via-emerald-400/20 to-teal-300/15 blur-2xl -z-10"
                                aria-hidden="true"
                            ></div>

                            <!-- Device/Gallery Showcase Frame -->
                            <div
                                class="relative overflow-hidden rounded-[2rem] sm:rounded-[2.5rem] border-4 sm:border-8 border-white dark:border-slate-800 bg-slate-900 shadow-2xl shadow-teal-950/15 transition-all duration-300 group"
                                @mouseenter="pauseAutoplay"
                                @mouseleave="resumeAutoplay"
                                @touchstart.passive="handleTouchStart"
                                @touchend.passive="handleTouchEnd"
                            >
                                <!-- Viewport with generous aspect ratio -->
                                <div
                                    class="relative h-[380px] sm:h-[460px] md:h-[500px] lg:h-[530px] xl:h-[560px] w-full overflow-hidden bg-slate-900"
                                >
                                    <!-- Transitions for Slides -->
                                    <transition-group
                                        name="carousel-fade"
                                        tag="div"
                                        class="relative h-full w-full"
                                    >
                                        <div
                                            v-for="(
                                                slide, index
                                            ) in carouselSlides"
                                            v-show="currentSlide === index"
                                            :key="slide.image"
                                            class="absolute inset-0 h-full w-full"
                                        >
                                            <img
                                                :src="slide.image"
                                                :alt="slide.title"
                                                class="h-full w-full object-cover object-center transform transition-transform duration-1000 ease-out group-hover:scale-105"
                                                loading="eager"
                                            />
                                            <!-- Light edge vignette so the full photo is clear and clean -->
                                            <div
                                                class="absolute inset-0 bg-gradient-to-t from-slate-950/40 via-transparent to-slate-950/25"
                                            ></div>
                                        </div>
                                    </transition-group>

                                    <!-- Floating Top-Right Slide Counter -->
                                    <div class="absolute top-4 right-4 z-20">
                                        <span
                                            class="rounded-full bg-slate-950/70 backdrop-blur-md px-3 py-1.5 text-xs font-bold text-white/95 border border-white/20 shadow-lg tracking-wider"
                                        >
                                            0{{ currentSlide + 1 }} / 0{{
                                                carouselSlides.length
                                            }}
                                        </span>
                                    </div>

                                    <!-- Left Navigation Arrow (Fade-in on desktop, visible on touch) -->
                                    <button
                                        type="button"
                                        @click.stop="prevSlide"
                                        class="absolute left-3.5 top-1/2 -translate-y-1/2 z-20 flex h-11 w-11 sm:h-12 sm:w-12 items-center justify-center rounded-full bg-white/90 hover:bg-white text-slate-800 dark:bg-slate-900/90 dark:hover:bg-slate-800 dark:text-slate-100 shadow-xl backdrop-blur-sm transition-all duration-200 hover:scale-110 active:scale-95 focus:outline-none focus:ring-2 focus:ring-teal-500 cursor-pointer sm:opacity-90 sm:group-hover:opacity-100"
                                        aria-label="Previous slide"
                                    >
                                        <i
                                            class="pi pi-chevron-left text-sm sm:text-base font-bold"
                                        ></i>
                                    </button>

                                    <!-- Right Navigation Arrow -->
                                    <button
                                        type="button"
                                        @click.stop="nextSlide"
                                        class="absolute right-3.5 top-1/2 -translate-y-1/2 z-20 flex h-11 w-11 sm:h-12 sm:w-12 items-center justify-center rounded-full bg-white/90 hover:bg-white text-slate-800 dark:bg-slate-900/90 dark:hover:bg-slate-800 dark:text-slate-100 shadow-xl backdrop-blur-sm transition-all duration-200 hover:scale-110 active:scale-95 focus:outline-none focus:ring-2 focus:ring-teal-500 cursor-pointer sm:opacity-90 sm:group-hover:opacity-100"
                                        aria-label="Next slide"
                                    >
                                        <i
                                            class="pi pi-chevron-right text-sm sm:text-base font-bold"
                                        ></i>
                                    </button>

                                    <!-- Floating Bottom-Left Patient Social Proof Card -->
                                    <div
                                        class="absolute bottom-4 left-4 z-20 max-w-[280px] rounded-2xl bg-white/95 dark:bg-slate-900/95 backdrop-blur-md p-3 shadow-xl border border-white/70 dark:border-slate-800"
                                    >
                                        <div class="flex items-center gap-2.5">
                                            <div
                                                class="flex -space-x-1.5 overflow-hidden"
                                            >
                                                <span
                                                    class="inline-flex h-8 w-8 items-center justify-center rounded-full bg-teal-100 text-teal-800 ring-2 ring-white dark:ring-slate-900 text-xs font-extrabold"
                                                    >VS</span
                                                >
                                                <span
                                                    class="inline-flex h-8 w-8 items-center justify-center rounded-full bg-amber-100 text-amber-700 ring-2 ring-white dark:ring-slate-900 text-xs font-bold"
                                                >
                                                    <i
                                                        class="pi pi-star-fill"
                                                    ></i>
                                                </span>
                                            </div>
                                            <div class="min-w-0">
                                                <p
                                                    class="text-xs font-bold text-gray-900 leading-tight"
                                                >
                                                    4.9 / 5.0 Rating
                                                </p>
                                                <p
                                                    class="text-[11px] text-gray-500 truncate mt-0.5"
                                                >
                                                    Over 100+ happy smiles
                                                </p>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Bottom-Right Slide Indicator Pill Bar -->
                                    <div
                                        class="absolute bottom-4 right-4 z-20 flex items-center gap-1.5 rounded-full bg-slate-950/60 backdrop-blur-md px-3 py-1.5 border border-white/20"
                                    >
                                        <button
                                            v-for="(
                                                slide, index
                                            ) in carouselSlides"
                                            :key="'dot-' + index"
                                            type="button"
                                            @click.stop="goToSlide(index)"
                                            :class="[
                                                'transition-all duration-300 rounded-full cursor-pointer focus:outline-none',
                                                currentSlide === index
                                                    ? 'w-7 h-2 bg-teal-400 ring-1 ring-white/50'
                                                    : 'w-2 h-2 bg-white/50 hover:bg-white/80',
                                            ]"
                                            :aria-label="
                                                'Go to slide ' + (index + 1)
                                            "
                                        ></button>
                                    </div>
                                </div>
                            </div>

                            <!-- Verified Clinic Operatory Pill -->
                            <div
                                class="mt-3 flex items-center justify-end px-2"
                            >
                                <div
                                    class="inline-flex items-center gap-1.5 text-xs text-teal-700 font-medium"
                                >
                                    <i class="pi pi-verified text-teal-600"></i>
                                    <span>Certified Operatory Standards</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Services Section (Dynamic) -->
        <section
            id="services"
            class="bg-gradient-to-b from-white via-teal-50/20 to-white dark:from-slate-950 dark:via-slate-900/50 dark:to-slate-950 py-20 scroll-mt-16"
        >
            <div class="mx-auto max-w-7xl px-6 lg:px-8">
                <div class="text-center max-w-3xl mx-auto">
                    <span
                        class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-teal-50 dark:bg-teal-950/60 border border-teal-200 dark:border-teal-800 text-teal-800 dark:text-teal-300 text-xs font-semibold uppercase tracking-wider"
                    >
                        <span>What We Offer</span>
                    </span>

                    <h2
                        class="mt-3 text-3xl font-extrabold tracking-tight text-gray-900 dark:text-white sm:text-4xl lg:text-5xl"
                    >
                        Our Services
                    </h2>

                    <p
                        class="mt-4 text-base leading-relaxed text-gray-600 dark:text-slate-300 sm:text-lg"
                    >
                        Professional dental treatments designed to keep your
                        teeth healthy, relieve discomfort, and make your smile
                        confident.
                    </p>
                </div>

                <!-- Dynamic Grid -->
                <div class="mt-14 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                    <div
                        v-for="service in displayedServices"
                        :key="service.id || service.title"
                        class="group relative rounded-2xl border border-gray-200/90 dark:border-slate-800 bg-white dark:bg-slate-900/95 p-6 sm:p-7 shadow-sm hover:shadow-xl hover:border-teal-400 dark:hover:border-teal-500 hover:-translate-y-1.5 transition-all duration-300 flex flex-col justify-between"
                    >
                        <div>
                            <!-- Header Row: Icon + Category Badge -->
                            <div class="flex items-center justify-between mb-4">
                                <ServiceIcon :service="service" size="md" />

                                <span
                                    :class="[
                                        'text-xs font-semibold px-2.5 py-1 rounded-full border',
                                        getCategoryBadgeClass(service.category),
                                    ]"
                                >
                                    {{ getCategoryLabel(service.category) }}
                                </span>
                            </div>

                            <!-- Title -->
                            <h3
                                class="text-lg font-bold text-gray-900 dark:text-white group-hover:text-teal-700 dark:group-hover:text-teal-400 transition"
                            >
                                {{ service.title || service.name }}
                            </h3>

                            <!-- Description -->
                            <p
                                class="mt-2.5 text-sm leading-relaxed text-gray-600 dark:text-slate-300 line-clamp-3"
                            >
                                {{ service.description }}
                            </p>

                            <!-- Highlights if present -->
                            <div
                                v-if="
                                    service.highlights &&
                                    service.highlights.length
                                "
                                class="mt-3.5 space-y-1 border-t border-gray-100 dark:border-slate-800 pt-3"
                            >
                                <div
                                    v-for="(
                                        hl, idx
                                    ) in service.highlights.slice(0, 2)"
                                    :key="idx"
                                    class="flex items-center gap-1.5 text-xs text-gray-600 dark:text-slate-400"
                                >
                                    <i
                                        class="pi pi-check text-teal-600 dark:text-teal-400 text-[10px]"
                                    ></i>
                                    <span class="truncate">{{ hl }}</span>
                                </div>
                            </div>
                        </div>

                        <!-- Footer: Price & Booking Action -->
                        <div
                            class="mt-6 pt-4 border-t border-gray-100 dark:border-slate-800 flex items-center justify-between"
                        >
                            <div>
                                <span
                                    class="block text-[11px] font-medium uppercase tracking-wider text-gray-400 dark:text-slate-400"
                                >
                                    Price Range
                                </span>
                                <span
                                    class="text-sm sm:text-base font-bold text-teal-700 dark:text-teal-400"
                                >
                                    {{
                                        service.formatted_price ||
                                        service.price_display ||
                                        "Consultation"
                                    }}
                                </span>
                            </div>

                            <Link
                                :href="
                                    route('appointments.create', {
                                        service: service.title || service.name,
                                    })
                                "
                                class="inline-flex items-center gap-1.5 rounded-xl bg-teal-50 dark:bg-slate-800 hover:bg-teal-700 dark:hover:bg-teal-600 text-teal-700 dark:text-teal-300 hover:text-white dark:hover:text-white px-3.5 py-2 text-xs font-semibold transition group/btn shadow-xs"
                            >
                                <span>Book</span>
                                <i
                                    class="pi pi-arrow-right text-[10px] group-hover/btn:translate-x-0.5 transition-transform"
                                ></i>
                            </Link>
                        </div>
                    </div>
                </div>

                <!-- Bottom CTA -->
                <div
                    class="mt-14 flex flex-col sm:flex-row items-center justify-center gap-4 text-center"
                >
                    <Link
                        :href="route('services.index')"
                        class="inline-flex items-center justify-center gap-2 rounded-xl bg-teal-700 hover:bg-teal-800 text-white px-7 py-3.5 text-sm font-semibold shadow-md shadow-teal-700/20 hover:shadow-lg hover:shadow-teal-700/30 transition"
                    >
                        <span>View All Services & Complete Price List</span>
                        <i class="pi pi-arrow-right text-xs"></i>
                    </Link>

                    <Link
                        href="/appointments/create"
                        class="inline-flex items-center justify-center gap-2 rounded-xl border border-gray-300 dark:border-slate-700 hover:border-teal-700 dark:hover:border-teal-500 bg-white dark:bg-slate-900 px-7 py-3.5 text-sm font-semibold text-gray-700 dark:text-slate-200 hover:text-teal-700 dark:hover:text-teal-400 hover:bg-teal-50/50 dark:hover:bg-slate-800 transition"
                    >
                        <span>Schedule an Appointment</span>
                    </Link>
                </div>
            </div>
        </section>

        <!-- Why Vitality Smile -->
        <section class="bg-gray-50 dark:bg-slate-900/60 py-20">
            <div class="mx-auto max-w-7xl px-6 lg:px-8">
                <div class="text-center">
                    <p
                        class="text-sm font-semibold uppercase tracking-wider text-teal-700 dark:text-teal-400"
                    >
                        Why Choose Us
                    </p>

                    <h2
                        class="mt-2 text-3xl font-bold text-gray-900 dark:text-white sm:text-4xl"
                    >
                        Why Vitality Smile
                    </h2>
                </div>

                <div class="mt-12 grid gap-8 md:grid-cols-3">
                    <div class="text-center">
                        <div
                            class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-teal-50 dark:bg-slate-800 border border-teal-200 dark:border-slate-700 text-teal-700 dark:text-teal-400 shadow-sm"
                        >
                            <svg
                                class="h-7 w-7"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke="currentColor"
                                stroke-width="2"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"
                                />
                            </svg>
                        </div>

                        <h3
                            class="mt-5 text-lg font-semibold text-gray-900 dark:text-white"
                        >
                            Professional Dental Care
                        </h3>

                        <p class="mt-2 text-gray-600 dark:text-slate-300">
                            Quality dental services provided with
                            professionalism and care.
                        </p>
                    </div>

                    <div class="text-center">
                        <div
                            class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-teal-50 dark:bg-slate-800 border border-teal-200 dark:border-slate-700 text-teal-700 dark:text-teal-400 shadow-sm"
                        >
                            <svg
                                class="h-7 w-7"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke="currentColor"
                                stroke-width="2"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"
                                />
                            </svg>
                        </div>

                        <h3
                            class="mt-5 text-lg font-semibold text-gray-900 dark:text-white"
                        >
                            Patient-Centered Service
                        </h3>

                        <p class="mt-2 text-gray-600 dark:text-slate-300">
                            We prioritize your comfort, needs, and overall
                            dental experience.
                        </p>
                    </div>

                    <div class="text-center">
                        <div
                            class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-teal-50 dark:bg-slate-800 border border-teal-200 dark:border-slate-700 text-teal-700 dark:text-teal-400 shadow-sm"
                        >
                            <svg
                                class="h-7 w-7"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke="currentColor"
                                stroke-width="2"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"
                                />
                            </svg>
                        </div>

                        <h3
                            class="mt-5 text-lg font-semibold text-gray-900 dark:text-white"
                        >
                            Organized Patient Management
                        </h3>

                        <p class="mt-2 text-gray-600 dark:text-slate-300">
                            Efficient appointment and patient management for a
                            better experience.
                        </p>
                    </div>
                </div>
            </div>
        </section>

        <!-- About Section -->
        <section
            id="about"
            class="bg-white dark:bg-slate-950 py-20 scroll-mt-16"
        >
            <div class="mx-auto max-w-4xl px-6 text-center lg:px-8">
                <p
                    class="text-sm font-semibold uppercase tracking-wider text-teal-700 dark:text-teal-400"
                >
                    About Us
                </p>

                <h2
                    class="mt-2 text-3xl font-bold text-gray-900 dark:text-white sm:text-4xl"
                >
                    About the Clinic
                </h2>

                <h3
                    class="mt-8 text-xl font-semibold text-gray-900 dark:text-white"
                >
                    Vitality Smile by Dr. Valdez
                </h3>

                <p
                    class="mx-auto mt-4 max-w-2xl leading-7 text-gray-600 dark:text-slate-300"
                >
                    Vitality Smile is committed to providing quality dental care
                    in a comfortable and patient-centered environment. Our goal
                    is to help every patient maintain a healthy and confident
                    smile.
                </p>
            </div>
        </section>

        <!-- Contact Section -->
        <section
            id="contact"
            class="relative overflow-hidden bg-gradient-to-b from-white via-teal-50/40 to-slate-50 dark:from-slate-950 dark:via-slate-900/60 dark:to-slate-950 py-20 lg:py-24 scroll-mt-16"
        >
            <!-- Subtle background decorative accents -->
            <div
                class="pointer-events-none absolute -bottom-24 -left-20 h-96 w-96 rounded-full bg-teal-200/20 blur-3xl"
                aria-hidden="true"
            ></div>
            <div
                class="pointer-events-none absolute top-10 right-0 h-80 w-80 rounded-full bg-emerald-100/30 blur-2xl"
                aria-hidden="true"
            ></div>

            <div class="relative mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <!-- Section Header -->
                <div class="text-center max-w-3xl mx-auto">
                    <span
                        class="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-full bg-teal-50 dark:bg-teal-950/60 border border-teal-200/90 dark:border-teal-800 text-teal-800 dark:text-teal-300 text-xs font-bold uppercase tracking-wider shadow-2xs"
                    >
                        <i
                            class="pi pi-phone text-[11px] text-teal-600 dark:text-teal-400"
                        ></i>
                        <span>Get In Touch</span>
                    </span>

                    <h2
                        class="mt-4 text-3xl font-extrabold tracking-tight text-gray-900 dark:text-white sm:text-4xl lg:text-5xl"
                    >
                        Visit or Contact Our Clinic
                    </h2>

                    <p
                        class="mt-4 text-base sm:text-lg leading-relaxed text-gray-600 dark:text-slate-300"
                    >
                        Have questions about treatments, schedule availability,
                        or need emergency guidance? Dr. Valdez and our friendly
                        team are here to help.
                    </p>
                </div>

                <!-- Contact Information Cards Grid -->
                <div class="mt-14 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                    <!-- Location Card -->
                    <div
                        class="group relative rounded-3xl border border-teal-100/80 dark:border-slate-800 bg-white/95 dark:bg-slate-900/95 p-7 sm:p-8 shadow-sm hover:shadow-xl hover:border-teal-400 dark:hover:border-teal-500 hover:-translate-y-1 transition-all duration-300 flex flex-col justify-between"
                    >
                        <div>
                            <div
                                class="flex h-14 w-14 items-center justify-center rounded-2xl bg-teal-50 dark:bg-slate-800 border border-teal-200/80 dark:border-slate-700 text-teal-700 dark:text-teal-400 shadow-xs group-hover:bg-teal-600 group-hover:text-white transition-colors duration-300"
                            >
                                <i class="pi pi-map-marker text-2xl"></i>
                            </div>

                            <span
                                class="mt-6 inline-block text-[11px] font-bold uppercase tracking-wider text-teal-700 dark:text-teal-400"
                            >
                                Clinic Address
                            </span>
                            <h3
                                class="mt-1 text-xl font-bold text-gray-900 dark:text-white"
                            >
                                Vitality Smile Dental Clinic
                            </h3>
                            <p
                                class="mt-2.5 text-sm leading-relaxed text-gray-600 dark:text-slate-300"
                            >
                                Unit 1, Threo Commercial Building, B2 L3A Casan
                                Residencia Subdivision, Barangay Malitlit, Santa
                                Rosa, Laguna 4026
                            </p>
                        </div>

                        <div
                            class="mt-6 pt-5 border-t border-gray-100 dark:border-slate-800 flex items-center justify-between text-xs text-gray-500 dark:text-slate-400"
                        >
                            <span
                                class="inline-flex items-center gap-1 text-teal-700 dark:text-teal-400 font-medium"
                            >
                                <i class="pi pi-car text-xs"></i>
                                Free Patient Parking
                            </span>
                            <a
                                href="https://www.google.com/maps/search/?api=1&query=Vitality+Smile+Dental+Clinic+Malitlit+Santa+Rosa+Laguna"
                                target="_blank"
                                rel="noopener noreferrer"
                                class="inline-flex items-center gap-1 font-semibold text-teal-700 dark:text-teal-400 hover:text-teal-900 dark:hover:text-teal-300 hover:underline"
                            >
                                <span>Get Directions</span>
                                <i class="pi pi-arrow-up-right text-[10px]"></i>
                            </a>
                        </div>
                    </div>

                    <!-- Hotline & Phone Card -->
                    <div
                        class="group relative rounded-3xl border border-teal-100/80 dark:border-slate-800 bg-white/95 dark:bg-slate-900/95 p-7 sm:p-8 shadow-sm hover:shadow-xl hover:border-teal-400 dark:hover:border-teal-500 hover:-translate-y-1 transition-all duration-300 flex flex-col justify-between"
                    >
                        <div>
                            <div
                                class="flex h-14 w-14 items-center justify-center rounded-2xl bg-teal-50 dark:bg-slate-800 border border-teal-200/80 dark:border-slate-700 text-teal-700 dark:text-teal-400 shadow-xs group-hover:bg-teal-600 group-hover:text-white transition-colors duration-300"
                            >
                                <i class="pi pi-phone text-2xl"></i>
                            </div>

                            <span
                                class="mt-6 inline-block text-[11px] font-bold uppercase tracking-wider text-teal-700 dark:text-teal-400"
                            >
                                Call / Message Us
                            </span>
                            <h3
                                class="mt-1 text-xl font-bold text-gray-900 dark:text-white"
                            >
                                Direct Phone & Mobile
                            </h3>
                            <div class="mt-3 space-y-1.5">
                                <a
                                    href="tel:09773281243"
                                    class="block text-base font-bold text-teal-700 dark:text-teal-300 hover:text-teal-800 dark:hover:text-teal-200 transition"
                                >
                                    0977-328-1243
                                    <span
                                        class="text-xs font-normal text-gray-500 dark:text-slate-400"
                                        >(Mobile / Viber / Booking)</span
                                    >
                                </a>
                                <p
                                    class="text-xs text-gray-500 dark:text-slate-400"
                                >
                                    Direct assistance with Dr. Valdez and clinic
                                    staff
                                </p>
                            </div>
                        </div>

                        <div
                            class="mt-6 pt-5 border-t border-gray-100 dark:border-slate-800 flex items-center justify-between text-xs text-gray-500 dark:text-slate-400"
                        >
                            <span
                                class="inline-flex items-center gap-1.5 text-emerald-700 dark:text-emerald-400 font-semibold"
                            >
                                <span
                                    class="h-2 w-2 rounded-full bg-emerald-500 animate-pulse"
                                ></span>
                                Live Booking Line
                            </span>
                            <a
                                href="tel:09773281243"
                                class="inline-flex items-center gap-1 font-semibold text-teal-700 dark:text-teal-400 hover:text-teal-900 dark:hover:text-teal-300 hover:underline"
                            >
                                <span>Call Now</span>
                                <i class="pi pi-arrow-right text-[10px]"></i>
                            </a>
                        </div>
                    </div>

                    <!-- Email & Hours Card -->
                    <div
                        class="group relative rounded-3xl border border-teal-100/80 dark:border-slate-800 bg-white/95 dark:bg-slate-900/95 p-7 sm:p-8 shadow-sm hover:shadow-xl hover:border-teal-400 dark:hover:border-teal-500 hover:-translate-y-1 transition-all duration-300 flex flex-col justify-between sm:col-span-2 lg:col-span-1"
                    >
                        <div>
                            <div
                                class="flex h-14 w-14 items-center justify-center rounded-2xl bg-teal-50 dark:bg-slate-800 border border-teal-200/80 dark:border-slate-700 text-teal-700 dark:text-teal-400 shadow-xs group-hover:bg-teal-600 group-hover:text-white transition-colors duration-300"
                            >
                                <i class="pi pi-envelope text-2xl"></i>
                            </div>

                            <span
                                class="mt-6 inline-block text-[11px] font-bold uppercase tracking-wider text-teal-700 dark:text-teal-400"
                            >
                                Email & Operating Hours
                            </span>
                            <h3
                                class="mt-1 text-xl font-bold text-gray-900 dark:text-white"
                            >
                                Email & Schedule
                            </h3>
                            <a
                                href="mailto:vitalitysmile2025@gmail.com"
                                class="mt-2 block text-sm font-semibold text-teal-700 dark:text-teal-300 hover:text-teal-800 dark:hover:text-teal-200 hover:underline break-all"
                            >
                                vitalitysmile2025@gmail.com
                            </a>

                            <!-- High-Contrast Operating Hours Box for Light & Dark Mode -->
                            <div
                                class="mt-3.5 rounded-2xl bg-teal-50/80 dark:bg-slate-800/90 p-3.5 border border-teal-200/80 dark:border-slate-700 shadow-2xs transition-colors"
                            >
                                <div
                                    class="flex items-center justify-between text-xs font-bold"
                                >
                                    <span
                                        class="text-gray-800 dark:text-slate-200"
                                        >Monday – Friday:</span
                                    >
                                    <span
                                        class="text-teal-800 dark:text-teal-300 font-extrabold"
                                        >9:00 AM – 6:00 PM</span
                                    >
                                </div>
                                <div
                                    class="mt-2 flex items-center justify-between text-xs"
                                >
                                    <span
                                        class="text-gray-600 dark:text-slate-400 font-medium"
                                        >Saturday & Sunday:</span
                                    >
                                    <span
                                        class="font-bold text-amber-700 dark:text-amber-400"
                                        >By Appt / Closed</span
                                    >
                                </div>
                            </div>
                        </div>

                        <div
                            class="mt-6 pt-5 border-t border-gray-100 dark:border-slate-800 flex items-center justify-between text-xs text-gray-500 dark:text-slate-400"
                        >
                            <span class="text-gray-500 dark:text-slate-400"
                                >Replies within 24 hours</span
                            >
                            <a
                                href="mailto:vitalitysmile2025@gmail.com"
                                class="inline-flex items-center gap-1 font-semibold text-teal-700 dark:text-teal-400 hover:text-teal-900 dark:hover:text-teal-300 hover:underline"
                            >
                                <span>Send Email</span>
                                <i class="pi pi-arrow-right text-[10px]"></i>
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Integrated Fast Action Callout Banner -->
                <div
                    class="mt-12 rounded-3xl bg-gradient-to-r from-teal-900 via-teal-800 to-slate-900 p-8 sm:p-10 text-white shadow-2xl shadow-teal-950/20 flex flex-col md:flex-row items-center justify-between gap-6"
                >
                    <div class="max-w-2xl text-center md:text-left">
                        <div
                            class="inline-flex items-center gap-2 rounded-full bg-teal-800/80 border border-teal-600/40 px-3 py-1 text-xs font-semibold text-teal-200"
                        >
                            <i class="pi pi-calendar text-[11px]"></i>
                            <span>Online Dental Reservation</span>
                        </div>
                        <h3
                            class="mt-3 text-2xl sm:text-3xl font-extrabold tracking-tight"
                        >
                            Ready for a Healthier, Brighter Smile?
                        </h3>
                        <p
                            class="mt-2 text-sm sm:text-base text-teal-100/90 leading-relaxed"
                        >
                            Book your consultation with Dr. Valdez in less than
                            2 minutes. Instant automated slot confirmation with
                            zero hassle.
                        </p>
                    </div>

                    <div
                        class="flex flex-col sm:flex-row items-center gap-3 shrink-0 w-full sm:w-auto"
                    >
                        <Link
                            href="/appointments/create"
                            class="w-full sm:w-auto inline-flex items-center justify-center gap-2.5 rounded-2xl bg-white hover:bg-teal-50 text-teal-900 px-7 py-4 text-sm font-bold shadow-lg hover:shadow-xl transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-white focus:ring-offset-2 focus:ring-offset-teal-900"
                        >
                            <span>Book an Appointment</span>
                            <i class="pi pi-arrow-right text-xs"></i>
                        </Link>
                        <a
                            href="tel:09773281243"
                            class="w-full sm:w-auto inline-flex items-center justify-center gap-2 rounded-2xl border border-teal-400/40 bg-teal-800/50 hover:bg-teal-800 text-white px-5 py-4 text-sm font-semibold transition"
                        >
                            <i class="pi pi-phone text-xs"></i>
                            <span>Call Clinic</span>
                        </a>
                    </div>
                </div>
            </div>
        </section>

        <!-- Footer -->
        <footer class="bg-slate-950 text-gray-300 border-t border-slate-900">
            <!-- Main 4-Column Footer -->
            <div class="mx-auto max-w-7xl px-6 py-16 lg:px-8">
                <div
                    class="grid grid-cols-1 gap-10 md:grid-cols-2 lg:grid-cols-12 lg:gap-8"
                >
                    <!-- Column 1: Brand & Clinic Identity (Span 4) -->
                    <div class="lg:col-span-4 text-left">
                        <Link href="/" class="inline-flex items-center gap-3">
                            <img
                                src="/images/brand/vitality-logo.png"
                                alt="Vitality Smile Logo"
                                class="h-10 w-auto object-contain shrink-0 rounded-lg bg-white/10 p-1"
                            />
                            <div class="flex flex-col">
                                <span
                                    class="text-xl font-extrabold tracking-tight text-white leading-none"
                                >
                                    VITALITY
                                    <span class="text-teal-400">SMILE</span>
                                </span>
                                <span
                                    class="mt-1 text-[10px] font-semibold tracking-wider text-teal-300 uppercase"
                                >
                                    General Dentistry & Orthodontics
                                </span>
                            </div>
                        </Link>

                        <p
                            class="mt-5 text-sm leading-relaxed text-gray-400 max-w-sm"
                        >
                            Providing gentle, modern, and personalized dental
                            healthcare led by Dr. Valdez. Committed to clinical
                            excellence, sterile operatory standards, and patient
                            comfort.
                        </p>

                        <div class="mt-6 flex flex-wrap items-center gap-2">
                            <span
                                class="inline-flex items-center gap-1.5 rounded-full bg-slate-900 border border-slate-800 px-3 py-1 text-xs text-teal-300 font-medium"
                            >
                                <i
                                    class="pi pi-verified text-[11px] text-teal-400"
                                ></i>
                                Licensed Clinic
                            </span>
                            <span
                                class="inline-flex items-center gap-1.5 rounded-full bg-slate-900 border border-slate-800 px-3 py-1 text-xs text-gray-300 font-medium"
                            >
                                <i
                                    class="pi pi-shield text-[11px] text-emerald-400"
                                ></i>
                                100% Sterile Standards
                            </span>
                        </div>
                    </div>

                    <!-- Column 2: Clinical Services (Span 3) -->
                    <div class="lg:col-span-3 text-left">
                        <h4
                            class="text-xs font-bold uppercase tracking-wider text-teal-400"
                        >
                            Dental Treatments
                        </h4>
                        <ul class="mt-4 space-y-2.5 text-sm text-gray-400">
                            <li>
                                <a
                                    href="#services"
                                    class="hover:text-white transition"
                                >
                                    Oral Prophylaxis (Cleaning)
                                </a>
                            </li>
                            <li>
                                <a
                                    href="#services"
                                    class="hover:text-white transition"
                                >
                                    Composite Tooth Restoration
                                </a>
                            </li>
                            <li>
                                <a
                                    href="#services"
                                    class="hover:text-white transition"
                                >
                                    Root Canal Therapy
                                </a>
                            </li>
                            <li>
                                <a
                                    href="#services"
                                    class="hover:text-white transition"
                                >
                                    Orthodontics / Braces
                                </a>
                            </li>
                            <li>
                                <a
                                    href="#services"
                                    class="hover:text-white transition"
                                >
                                    In-Clinic Teeth Whitening
                                </a>
                            </li>
                            <li>
                                <a
                                    href="#services"
                                    class="hover:text-white transition"
                                >
                                    Gentle Tooth Extractions
                                </a>
                            </li>
                        </ul>
                    </div>

                    <!-- Column 3: Patient Navigation & Portal (Span 2) -->
                    <div class="lg:col-span-2 text-left">
                        <h4
                            class="text-xs font-bold uppercase tracking-wider text-teal-400"
                        >
                            Quick Links
                        </h4>
                        <ul class="mt-4 space-y-2.5 text-sm text-gray-400">
                            <li>
                                <Link
                                    href="/"
                                    class="hover:text-white transition"
                                    >Home</Link
                                >
                            </li>
                            <li>
                                <Link
                                    href="/appointments/create"
                                    class="hover:text-white transition"
                                >
                                    Book Appointment
                                </Link>
                            </li>
                            <li>
                                <Link
                                    :href="route('services.index')"
                                    class="hover:text-white transition"
                                >
                                    All Services
                                </Link>
                            </li>
                            <li>
                                <a
                                    href="#about"
                                    class="hover:text-white transition"
                                    >About Clinic</a
                                >
                            </li>
                            <li>
                                <a
                                    href="#contact"
                                    class="hover:text-white transition"
                                    >Contact Us</a
                                >
                            </li>
                            <li>
                                <Link
                                    href="/login"
                                    class="text-teal-400 hover:text-teal-300 font-semibold transition"
                                >
                                    Patient Portal
                                </Link>
                            </li>
                        </ul>
                    </div>

                    <!-- Column 4: Clinic Schedule & Emergency (Span 3) -->
                    <div class="lg:col-span-3 text-left">
                        <h4
                            class="text-xs font-bold uppercase tracking-wider text-teal-400"
                        >
                            Clinic Schedule
                        </h4>
                        <div class="mt-4 space-y-2 text-sm text-gray-400">
                            <div
                                class="flex items-center justify-between border-b border-slate-900 pb-2"
                            >
                                <span>Mon – Fri:</span>
                                <span class="font-semibold text-white"
                                    >9:00 AM – 6:00 PM</span
                                >
                            </div>
                            <div class="flex items-center justify-between pb-2">
                                <span>Saturday - Sunday & Holidays:</span>
                                <span class="text-gray-500">Closed</span>
                            </div>
                        </div>

                        <div
                            class="mt-5 rounded-2xl bg-slate-900/90 p-3.5 border border-slate-800"
                        >
                            <p class="text-xs text-gray-400">
                                Emergency & Booking Inquiries:
                            </p>
                            <a
                                href="tel:09773281243"
                                class="mt-1 inline-flex items-center gap-1.5 text-sm font-bold text-teal-400 hover:text-teal-300 transition"
                            >
                                <i class="pi pi-phone text-xs"></i>
                                <span>0977-328-1243</span>
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Bottom Legal & Copyright Bar -->
            <div class="border-t border-slate-900 bg-black/40">
                <div
                    class="mx-auto flex max-w-7xl flex-col items-center justify-between gap-4 px-6 py-6 text-center sm:flex-row sm:text-left lg:px-8 text-xs text-gray-500"
                >
                    <p>
                        © 2026 Vitality Smile Dental Clinic by Dr. Valdez. All
                        rights reserved.
                    </p>

                    <div class="flex items-center gap-6">
                        <Link
                            href="/privacy"
                            class="hover:text-gray-300 transition"
                        >
                            Privacy Policy
                        </Link>
                        <span class="text-slate-800">•</span>
                        <Link
                            href="/terms"
                            class="hover:text-gray-300 transition"
                        >
                            Terms of Service
                        </Link>
                        <span class="text-slate-800">•</span>
                        <span class="text-gray-400">PHT (Asia/Manila)</span>
                    </div>
                </div>
            </div>
        </footer>
    </div>
</template>

<style scoped>
.carousel-fade-enter-active,
.carousel-fade-leave-active {
    transition: opacity 0.6s ease-in-out;
}

.carousel-fade-enter-from,
.carousel-fade-leave-to {
    opacity: 0;
}
</style>
