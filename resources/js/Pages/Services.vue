<script setup>
import { ref, computed } from "vue";
import Navbar from "@/Components/Navbar.vue";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import Modal from "@/Components/Modal.vue";
import ServiceIcon from "@/Components/ServiceIcon.vue";
import { Head, Link } from "@inertiajs/vue3";

const props = defineProps({
    services: {
        type: Array,
        default: () => [],
    },
    categories: {
        type: Array,
        default: () => [],
    },
    isStaff: {
        type: Boolean,
        default: false,
    },
});

const searchQuery = ref("");
const selectedCategory = ref("all");

// Service Details Modal State (Limited Action for Patients)
const showDetailsModal = ref(false);
const selectedService = ref(null);

const openDetailsModal = (service) => {
    selectedService.value = service;
    showDetailsModal.value = true;
};

const closeDetailsModal = () => {
    showDetailsModal.value = false;
    selectedService.value = null;
};

const defaultCategories = [
    { id: "all", name: "All Services" },
    { id: "general", name: "General & Preventive" },
    { id: "restorative", name: "Restorative & Endodontics" },
    { id: "cosmetic", name: "Cosmetic & Crowns" },
    { id: "surgery", name: "Oral Surgery" },
    { id: "prosthodontics", name: "Dentures" },
];

const defaultServices = [
    {
        name: "Oral Prophylaxis (₱800-₱1,000)",
        title: "Oral Prophylaxis (Dental Cleaning)",
        category: "general",
        price: "₱800 - ₱1,000",
        duration: "30 - 45 mins",
        icon: "shield",
        description:
            "Professional ultrasonic cleaning that removes stubborn plaque, tartar, and surface stains to preserve healthy gums and prevent periodontal issues.",
        highlights: [
            "Ultrasonic tartar removal",
            "Polishing to lift tea/coffee stains",
            "Gum health assessment",
            "Fresher breath & cleaner feel",
        ],
    },
    {
        name: "Tooth Restoration (₱800-₱1,000)",
        title: "Tooth Restoration (Composite Filling)",
        category: "restorative",
        price: "₱800 - ₱1,000",
        duration: "30 - 60 mins",
        icon: "tooth",
        description:
            "High-grade tooth-colored composite resin fillings that repair cavities, chipped edges, or tooth wear, seamlessly blending with your natural enamel.",
        highlights: [
            "Natural tooth-shade matching",
            "Durable composite bonding",
            "Protects against further decay",
            "Gentle, painless application",
        ],
    },
    {
        name: "Tooth Extraction (₱800-₱1,000)",
        title: "Routine Tooth Extraction",
        category: "surgery",
        price: "₱800 - ₱1,000",
        duration: "30 - 45 mins",
        icon: "surgery",
        description:
            "Safe, gentle removal of severely decayed, fractured, or compromised teeth performed with modern, effective local anesthesia.",
        highlights: [
            "Gentle, minimally invasive technique",
            "Effective pain-free anesthesia",
            "Complete post-extraction instructions",
            "Fast healing protocols",
        ],
    },
    {
        name: "Wisdom Tooth Removal (Erupted) (₱5,000-₱8,000)",
        title: "Wisdom Tooth Removal (Erupted)",
        category: "surgery",
        price: "₱5,000 - ₱8,000",
        duration: "45 - 60 mins",
        icon: "surgery",
        description:
            "Extraction of fully erupted third molars causing overcrowding, recurrent infections, or chewing discomfort.",
        highlights: [
            "Prevents bite misalignment & decay",
            "Targeted local numbing",
            "Immediate pressure & pain relief",
            "Personalized post-op care pack",
        ],
    },
    {
        name: "Odontectomy/Surgery (Impacted) (₱8,000 - ₱20,000)",
        title: "Odontectomy (Impacted Wisdom Tooth Surgery)",
        category: "surgery",
        price: "₱8,000 - ₱20,000",
        duration: "60 - 90 mins",
        icon: "surgery",
        description:
            "Specialized minor oral surgical procedure to extract impacted wisdom teeth trapped beneath the gum tissue or embedded within the jawbone.",
        highlights: [
            "Pre-surgical radiographic evaluation",
            "Precision surgical techniques",
            "Careful suturing for smooth recovery",
            "Prevents damage to adjacent molars",
        ],
    },
    {
        name: "Root Canal Treatment (₱8,000 per canal)",
        title: "Root Canal Treatment (Endodontic Therapy)",
        category: "restorative",
        price: "₱8,000 per canal",
        duration: "60 - 90 mins",
        icon: "root_canal",
        description:
            "Saves a severely infected or painful tooth from extraction by carefully removing infected pulp, disinfecting the canals, and hermetically sealing the space.",
        highlights: [
            "Eliminates severe toothache & abscess",
            "Preserves your natural tooth structure",
            "Advanced canal disinfection",
            "Prepares tooth for long-term crown",
        ],
    },
    {
        name: "Teeth Whitening - 2 Cycles (₱8,000-10,000)",
        title: "In-Clinic Teeth Whitening (2 Cycles)",
        category: "cosmetic",
        price: "₱8,000 - ₱10,000",
        duration: "60 mins",
        icon: "sparkles",
        description:
            "Professional LED light-activated bleaching system that dramatically brightens discolored or yellowed teeth by multiple shades in a single session.",
        highlights: [
            "Immediate, noticeable results",
            "Enamel-safe professional formula",
            "Gingival barrier to protect gums",
            "Lifts years of stains in one hour",
        ],
    },
    {
        name: "Plastic Jacket Crown (₱5,000 - ₱6,000 per unit/tooth)",
        title: "Plastic Jacket Crown",
        category: "cosmetic",
        price: "₱5,000 - ₱6,000 per unit",
        duration: "45 - 60 mins",
        icon: "crown",
        description:
            "Affordable tooth-colored aesthetic crown solution suited for restoring anterior or premolar teeth with moderate structural damage.",
        highlights: [
            "Cost-effective smile restoration",
            "Natural tooth contours",
            "Protects underlying tooth foundation",
            "Quick clinical fabrication time",
        ],
    },
    {
        name: "Porcelain Jacket Crown (₱7,000 - ₱8,000 per unit/tooth)",
        title: "Porcelain Jacket Crown",
        category: "cosmetic",
        price: "₱7,000 - ₱8,000 per unit",
        duration: "45 - 60 mins",
        icon: "crown",
        description:
            "All-ceramic dental crown offering superior translucency and lifelike aesthetics that beautifully mimic natural tooth enamel.",
        highlights: [
            "Exceptional lifelike translucency",
            "100% biocompatible & metal-free",
            "Stain-resistant porcelain surface",
            "Ideal for prominent front teeth",
        ],
    },
    {
        name: "Porcelain Fused to Metal (PFM) (₱7,000 - ₱8,000 per unit/tooth)",
        title: "Porcelain Fused to Metal (PFM) Crown",
        category: "restorative",
        price: "₱7,000 - ₱8,000 per unit",
        duration: "45 - 60 mins",
        icon: "crown",
        description:
            "Time-tested restorative crown combining the aesthetic appeal of porcelain with the superior compressive strength of an underlying metal substructure.",
        highlights: [
            "High structural durability",
            "Excellent chewing force resistance",
            "Great for molars and bridges",
            "Reliable long-term clinical track record",
        ],
    },
    {
        name: "Tilite Porcelain Crown (₱9,000 - ₱10,000 per unit/tooth)",
        title: "Tilite Porcelain Crown",
        category: "cosmetic",
        price: "₱9,000 - ₱10,000 per unit",
        duration: "45 - 60 mins",
        icon: "crown",
        description:
            "Premium medical-grade titanium alloy crown covered with exquisite porcelain, offering unparalleled biocompatibility and light weight.",
        highlights: [
            "Medical-grade titanium substructure",
            "Zero allergic gum reactions",
            "Remarkable strength & fit precision",
            "Resistant to galvanic corrosion",
        ],
    },
    {
        name: "E-max Porcelain Crown (₱12,000 - ₱14,000 per unit/tooth)",
        title: "E-max All-Ceramic Crown",
        category: "cosmetic",
        price: "₱12,000 - ₱14,000 per unit",
        duration: "45 - 60 mins",
        icon: "gem",
        description:
            "Top-tier lithium disilicate glass-ceramic crown celebrated worldwide for unmatched durability, high fracture resistance, and flawless beauty.",
        highlights: [
            "World-class lithium disilicate ceramic",
            "Unrivaled aesthetic shade blending",
            "High fracture resistance",
            "Minimally invasive tooth preparation",
        ],
    },
    {
        name: "Zirconia Crown (₱13,000 - ₱15,000 per unit/tooth)",
        title: "Zirconia All-Ceramic Crown",
        category: "cosmetic",
        price: "₱13,000 - ₱15,000 per unit",
        duration: "45 - 60 mins",
        icon: "gem",
        description:
            "Virtually indestructible monolithic zirconia crown engineered to endure the heaviest grinding and bite pressures with pristine aesthetics.",
        highlights: [
            "Diamond-hard zirconium dioxide",
            "Virtually chip-proof durability",
            "Gentle on opposing natural teeth",
            "Zero dark metal margin lines",
        ],
    },
    {
        name: "Ordinary Complete Denture - Acrylic (₱10,000 per arch)",
        title: "Complete Acrylic Denture (Per Arch)",
        category: "prosthodontics",
        price: "₱10,000 per arch",
        duration: "45 - 60 mins",
        icon: "denture",
        description:
            "Custom-fitted removable full or partial dentures constructed with resilient acrylic bases and high-grade Japanese plastic artificial teeth.",
        highlights: [
            "Custom contoured to your gum line",
            "Durable Japanese acrylic teeth",
            "Restores facial structure & confidence",
            "Easy maintenance and cleaning",
        ],
    },
];

const activeCategories = computed(() => {
    if (props.categories && props.categories.length > 0) {
        return props.categories;
    }
    return defaultCategories;
});

const activeServices = computed(() => {
    if (props.services && props.services.length > 0) {
        return props.services.map((srv) => ({
            ...srv,
            price:
                srv.formatted_price ||
                srv.price_display ||
                srv.price ||
                (srv.base_price
                    ? `₱${parseFloat(srv.base_price).toLocaleString()}`
                    : "Custom Quote"),
        }));
    }
    return defaultServices;
});

const filteredServices = computed(() => {
    return activeServices.value.filter((srv) => {
        const matchesCategory =
            selectedCategory.value === "all" ||
            srv.category === selectedCategory.value;
        const matchesSearch =
            searchQuery.value.trim() === "" ||
            (srv.title &&
                srv.title
                    .toLowerCase()
                    .includes(searchQuery.value.toLowerCase())) ||
            (srv.description &&
                srv.description
                    .toLowerCase()
                    .includes(searchQuery.value.toLowerCase()));
        return matchesCategory && matchesSearch;
    });
});
</script>

<template>
    <Head title="Clinic Dental Services - Vitality Smile" />

    <!-- 1. AUTHENTICATED PATIENT PORTAL LAYOUT (Sidebar active) -->
    <AuthenticatedLayout v-if="$page.props.auth?.user">
        <template #header>
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <div class="flex items-center gap-2.5">
                        <h2 class="font-bold text-xl text-gray-900 leading-tight">
                            Clinic Dental Services
                        </h2>
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-teal-50 text-teal-800 border border-teal-200">
                            Patient Catalog
                        </span>
                    </div>
                    <p class="text-xs text-gray-500 mt-1">
                        Explore treatments, transparent fees, and easily schedule your next appointment online.
                    </p>
                </div>
                <div class="flex items-center gap-2.5">
                    <Link
                        v-if="isStaff"
                        :href="route('services.index')"
                        class="inline-flex items-center gap-1.5 rounded-xl border border-teal-300 bg-teal-50 px-3.5 py-2 text-xs font-semibold text-teal-800 hover:bg-teal-100 transition"
                    >
                        <i class="pi pi-cog text-xs"></i>
                        <span>Manage Services</span>
                    </Link>
                    <Link
                        :href="route('appointments.index')"
                        class="inline-flex items-center gap-1.5 rounded-xl border border-gray-300 bg-white px-3.5 py-2 text-xs font-semibold text-gray-700 shadow-sm hover:bg-gray-50 transition"
                    >
                        <i class="pi pi-calendar text-xs text-teal-600"></i>
                        <span>My Appointments</span>
                    </Link>
                    <Link
                        :href="route('appointments.create')"
                        class="inline-flex items-center gap-1.5 rounded-xl bg-teal-700 px-4 py-2 text-xs font-semibold text-white shadow-sm hover:bg-teal-800 transition"
                    >
                        <i class="pi pi-calendar-plus text-xs"></i>
                        <span>Book Appointment</span>
                    </Link>
                </div>
            </div>
        </template>

        <div class="py-6 px-4 sm:px-6 lg:px-8 max-w-7xl mx-auto space-y-6">
            <!-- Patient Callout -->
            <div class="p-4 rounded-2xl bg-gradient-to-r from-teal-50 via-emerald-50 to-teal-50 border border-teal-200/80 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                <div class="flex items-center gap-3">
                    <div class="h-10 w-10 rounded-xl bg-teal-600 text-white flex items-center justify-center shrink-0 shadow-sm">
                        <i class="pi pi-info-circle text-lg"></i>
                    </div>
                    <div>
                        <h3 class="text-sm font-bold text-teal-950">
                            Available Treatments & Consultations
                        </h3>
                        <p class="text-xs text-teal-800 mt-0.5">
                            Click <strong>View Details</strong> to review treatment steps and preparation guides, or <strong>Book</strong> to secure your appointment slot.
                        </p>
                    </div>
                </div>
                <div class="shrink-0 text-xs font-semibold text-teal-700">
                    <span class="inline-flex items-center gap-1 bg-white px-3 py-1.5 rounded-xl border border-teal-200 shadow-sm">
                        <i class="pi pi-check text-teal-600"></i>
                        {{ filteredServices.length }} Services Available
                    </span>
                </div>
            </div>

            <!-- Search and Category Filters -->
            <div class="bg-white rounded-2xl border border-gray-200 p-4 shadow-sm space-y-4">
                <div class="relative">
                    <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3.5 text-gray-400">
                        <i class="pi pi-search text-sm"></i>
                    </span>
                    <input
                        v-model="searchQuery"
                        type="text"
                        placeholder="Search services (e.g. cleaning, extraction, whitening, root canal, denture)..."
                        class="block w-full rounded-xl border-gray-200 pl-10 pr-4 py-2.5 text-sm text-gray-900 placeholder:text-gray-400 focus:border-teal-500 focus:ring-teal-500 transition"
                    />
                </div>

                <!-- Category Pills -->
                <div class="flex flex-wrap items-center gap-2">
                    <button
                        v-for="cat in activeCategories"
                        :key="cat.id"
                        @click="selectedCategory = cat.id"
                        :class="[
                            selectedCategory === cat.id
                                ? 'bg-teal-700 text-white shadow-sm'
                                : 'bg-gray-50 text-gray-700 hover:bg-gray-100 border border-gray-200',
                            'rounded-xl px-3.5 py-1.5 text-xs font-semibold transition',
                        ]"
                    >
                        {{ cat.name }}
                    </button>
                    <button
                        v-if="selectedCategory !== 'all' || searchQuery"
                        @click="
                            selectedCategory = 'all';
                            searchQuery = '';
                        "
                        class="text-xs font-medium text-teal-700 hover:underline ml-auto"
                    >
                        Reset filters
                    </button>
                </div>
            </div>

            <!-- Services Cards Grid -->
            <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3">
                <div
                    v-for="service in filteredServices"
                    :key="service.name"
                    class="flex flex-col justify-between rounded-2xl border border-gray-200 bg-white p-5 shadow-sm transition-all duration-200 hover:-translate-y-1 hover:border-teal-500 hover:shadow-md"
                >
                    <div>
                        <!-- Header with Icon, Category & Price -->
                        <div class="flex items-start justify-between gap-3">
                            <ServiceIcon :service="service" size="md" />
                            <span class="rounded-lg bg-teal-50 border border-teal-100 px-3 py-1 text-xs font-bold text-teal-800">
                                {{ service.price }}
                            </span>
                        </div>

                        <!-- Title & Duration -->
                        <h3 class="mt-4 text-base font-bold text-gray-900 leading-snug">
                            {{ service.title }}
                        </h3>

                        <div class="mt-1 flex items-center gap-1.5 text-xs text-gray-500">
                            <i class="pi pi-clock text-[11px] text-teal-600"></i>
                            <span>Est. Duration: {{ service.duration || "30 - 45 mins" }}</span>
                        </div>

                        <!-- Description -->
                        <p class="mt-2.5 text-xs leading-relaxed text-gray-600 line-clamp-3">
                            {{ service.description }}
                        </p>

                        <!-- Highlights (Top 3) -->
                        <div v-if="service.highlights && service.highlights.length > 0" class="mt-3.5 border-t border-gray-100 pt-3">
                            <ul class="space-y-1">
                                <li
                                    v-for="(hl, i) in service.highlights.slice(0, 3)"
                                    :key="i"
                                    class="flex items-center gap-2 text-xs text-gray-600"
                                >
                                    <i class="pi pi-check text-[10px] font-bold text-teal-600 shrink-0"></i>
                                    <span class="truncate">{{ hl }}</span>
                                </li>
                            </ul>
                        </div>
                    </div>

                    <!-- Limited Patient Actions (View Details & Book) -->
                    <div class="mt-5 border-t border-gray-100 pt-4 flex items-center gap-2">
                        <button
                            type="button"
                            @click="openDetailsModal(service)"
                            class="flex-1 inline-flex items-center justify-center gap-1.5 rounded-xl border border-gray-200 bg-white py-2.5 px-3 text-center text-xs font-semibold text-gray-700 shadow-sm transition hover:bg-gray-50 hover:border-gray-300"
                        >
                            <i class="pi pi-info-circle text-xs text-gray-400"></i>
                            <span>Details</span>
                        </button>
                        <Link
                            :href="route('appointments.create', { service: service.name })"
                            class="flex-1 inline-flex items-center justify-center gap-1.5 rounded-xl bg-teal-700 py-2.5 px-3 text-center text-xs font-semibold text-white shadow-sm transition hover:bg-teal-800 focus:outline-none focus:ring-2 focus:ring-teal-500"
                        >
                            <i class="pi pi-calendar-plus text-xs"></i>
                            <span>Book Now</span>
                        </Link>
                    </div>
                </div>
            </div>

            <!-- Empty State -->
            <div
                v-if="filteredServices.length === 0"
                class="rounded-2xl border border-gray-200 bg-white p-12 text-center"
            >
                <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-full bg-gray-100 text-2xl text-gray-400">
                    <i class="pi pi-search text-xl"></i>
                </div>
                <h3 class="mt-3 text-lg font-bold text-gray-900">
                    No matching dental services found
                </h3>
                <p class="mt-1 text-sm text-gray-500">
                    Try searching for another treatment or select "All Services".
                </p>
                <button
                    @click="
                        selectedCategory = 'all';
                        searchQuery = '';
                    "
                    class="mt-4 rounded-xl bg-teal-700 px-4 py-2 text-xs font-semibold text-white hover:bg-teal-800 transition"
                >
                    View All Services
                </button>
            </div>
        </div>
    </AuthenticatedLayout>

    <!-- 2. PUBLIC MARKETING LAYOUT (For guest visitors) -->
    <div v-else class="min-h-screen bg-gray-50 text-gray-800 dark:bg-slate-950 dark:text-slate-100 transition-colors duration-150">
        <Navbar />

        <!-- Hero Section -->
        <section class="bg-gradient-to-b from-teal-50 to-white dark:from-slate-900/90 dark:to-slate-950 py-16 sm:py-24">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 text-center">
                <span class="inline-flex items-center gap-2 rounded-full bg-teal-100 px-3 py-1 text-xs font-semibold text-teal-800 uppercase tracking-wider mb-4">
                    <svg class="h-3.5 w-3.5 text-teal-700" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M12 2C7.5 2 4 4.5 4 8c0 2.5 1.5 5 2.5 8 .8 2.4 1.5 6 3.5 6 1.5 0 2-3 2-4.5 0 1.5.5 4.5 2 4.5 2 0 2.7-3.6 3.5-6 1-3 2.5-5.5 2.5-8 0-3.5-3.5-6-8-6z" />
                    </svg>
                    Vitality Smile Dental Care
                </span>

                <h1 class="text-3xl font-extrabold tracking-tight text-gray-900 sm:text-5xl">
                    Comprehensive Dental Services
                </h1>

                <p class="mx-auto mt-4 max-w-2xl text-base text-gray-600 sm:text-lg">
                    Transparent pricing, gentle modern procedures, and experienced dental professionals dedicated to your smile and confidence.
                </p>

                <!-- Search and Filter Bar -->
                <div class="mx-auto mt-10 max-w-3xl">
                    <div class="relative">
                        <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-4 text-gray-400">
                            <i class="pi pi-search text-base"></i>
                        </span>
                        <input
                            v-model="searchQuery"
                            type="text"
                            placeholder="Search dental treatments (e.g. cleaning, extraction, whitening, root canal)..."
                            class="block w-full rounded-2xl border border-gray-200 bg-white py-3.5 pl-11 pr-4 text-sm shadow-sm transition placeholder:text-gray-400 focus:border-teal-500 focus:outline-none focus:ring-2 focus:ring-teal-500"
                        />
                    </div>

                    <!-- Category Pills -->
                    <div class="mt-5 flex flex-wrap items-center justify-center gap-2">
                        <button
                            v-for="cat in activeCategories"
                            :key="cat.id"
                            @click="selectedCategory = cat.id"
                            :class="[
                                selectedCategory === cat.id
                                    ? 'bg-teal-700 text-white shadow-sm'
                                    : 'bg-white text-gray-600 hover:bg-gray-100 border border-gray-200',
                                'rounded-full px-4 py-2 text-xs font-semibold transition',
                            ]"
                        >
                            {{ cat.name }}
                        </button>
                    </div>
                </div>
            </div>
        </section>

        <!-- Services Grid Section -->
        <section class="py-12 sm:py-16">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="flex items-center justify-between mb-8">
                    <div>
                        <h2 class="text-xl font-bold text-gray-900 sm:text-2xl">
                            Available Treatments ({{ filteredServices.length }})
                        </h2>
                        <p class="text-xs text-gray-500 mt-1">
                            Click 'Book Appointment' to reserve your desired date and time slot.
                        </p>
                    </div>

                    <button
                        v-if="selectedCategory !== 'all' || searchQuery"
                        @click="
                            selectedCategory = 'all';
                            searchQuery = '';
                        "
                        class="text-xs font-semibold text-teal-700 hover:underline"
                    >
                        Reset filters
                    </button>
                </div>

                <!-- Cards Grid -->
                <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3">
                    <div
                        v-for="service in filteredServices"
                        :key="service.name"
                        class="flex flex-col justify-between rounded-2xl border border-gray-200 bg-white p-6 shadow-sm transition-all duration-200 hover:-translate-y-1 hover:border-teal-500 hover:shadow-lg"
                    >
                        <div>
                            <!-- Header with Icon & Price -->
                            <div class="flex items-start justify-between gap-4">
                                <ServiceIcon :service="service" size="md" />
                                <span class="rounded-lg bg-teal-50 px-3 py-1 text-xs font-bold text-teal-800">
                                    {{ service.price }}
                                </span>
                            </div>

                            <!-- Title & Duration -->
                            <h3 class="mt-4 text-lg font-bold text-gray-900 leading-snug">
                                {{ service.title }}
                            </h3>

                            <div class="mt-1 flex items-center gap-1.5 text-xs text-gray-500">
                                <i class="pi pi-clock text-[11px] text-teal-600"></i>
                                <span>Est. Duration: {{ service.duration }}</span>
                            </div>

                            <!-- Description -->
                            <p class="mt-3 text-sm leading-relaxed text-gray-600">
                                {{ service.description }}
                            </p>

                            <!-- Highlights -->
                            <div v-if="service.highlights && service.highlights.length > 0" class="mt-4 border-t border-gray-100 pt-3">
                                <ul class="space-y-1.5">
                                    <li
                                        v-for="(hl, i) in service.highlights"
                                        :key="i"
                                        class="flex items-center gap-2 text-xs text-gray-600"
                                    >
                                        <i class="pi pi-check text-[10px] font-bold text-teal-600"></i>
                                        <span>{{ hl }}</span>
                                    </li>
                                </ul>
                            </div>
                        </div>

                        <!-- Actions (Limited: View Details & Book) -->
                        <div class="mt-6 border-t border-gray-100 pt-4 flex items-center gap-2">
                            <button
                                type="button"
                                @click="openDetailsModal(service)"
                                class="flex-1 inline-flex items-center justify-center gap-1.5 rounded-xl border border-gray-200 bg-white py-2.5 px-3 text-center text-xs font-semibold text-gray-700 shadow-sm transition hover:bg-gray-50 hover:border-gray-300"
                            >
                                <i class="pi pi-info-circle text-xs text-gray-400"></i>
                                <span>Details</span>
                            </button>
                            <Link
                                :href="route('appointments.create', { service: service.name })"
                                class="flex-1 inline-flex items-center justify-center gap-1.5 rounded-xl bg-teal-700 py-2.5 px-3 text-center text-xs font-semibold text-white shadow-sm transition hover:bg-teal-800 focus:outline-none focus:ring-2 focus:ring-teal-500"
                            >
                                <i class="pi pi-calendar-plus text-xs"></i>
                                <span>Book</span>
                            </Link>
                        </div>
                    </div>
                </div>

                <!-- Empty State -->
                <div
                    v-if="filteredServices.length === 0"
                    class="rounded-2xl border border-gray-200 bg-white p-12 text-center"
                >
                    <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-full bg-gray-100 text-2xl text-gray-400">
                        <i class="pi pi-search text-xl"></i>
                    </div>
                    <h3 class="mt-3 text-lg font-bold text-gray-900">
                        No matching services found
                    </h3>
                    <p class="mt-1 text-sm text-gray-500">
                        Try searching for a different keyword or view all categories.
                    </p>
                    <button
                        @click="
                            selectedCategory = 'all';
                            searchQuery = '';
                        "
                        class="mt-4 rounded-lg bg-teal-700 px-4 py-2 text-xs font-semibold text-white hover:bg-teal-800"
                    >
                        View All Services
                    </button>
                </div>
            </div>
        </section>

        <!-- What to Expect Section -->
        <section class="bg-white py-16 sm:py-20 border-t border-gray-100">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="text-center max-w-3xl mx-auto mb-12">
                    <p class="text-xs font-semibold uppercase tracking-wider text-teal-700">
                        Patient Journey
                    </p>
                    <h2 class="mt-2 text-3xl font-bold text-gray-900">
                        What to Expect During Your Visit
                    </h2>
                    <p class="mt-3 text-sm text-gray-600">
                        We prioritize comfort, hygiene, and thorough communication at every step.
                    </p>
                </div>

                <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-4">
                    <div class="rounded-2xl border border-gray-100 bg-gray-50 p-6 text-center">
                        <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-xl bg-teal-100 text-teal-700 font-bold text-lg mb-4">
                            1
                        </div>
                        <h3 class="font-bold text-gray-900 mb-2">Book Online or Call</h3>
                        <p class="text-xs text-gray-600 leading-relaxed">
                            Select your procedure and preferred date slot via our patient portal or clinic phone.
                        </p>
                    </div>

                    <div class="rounded-2xl border border-gray-100 bg-gray-50 p-6 text-center">
                        <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-xl bg-teal-100 text-teal-700 font-bold text-lg mb-4">
                            2
                        </div>
                        <h3 class="font-bold text-gray-900 mb-2">Comfortable Check-In</h3>
                        <p class="text-xs text-gray-600 leading-relaxed">
                            Arrive at our welcoming clinic where staff prepares your health record and answers initial questions.
                        </p>
                    </div>

                    <div class="rounded-2xl border border-gray-100 bg-gray-50 p-6 text-center">
                        <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-xl bg-teal-100 text-teal-700 font-bold text-lg mb-4">
                            3
                        </div>
                        <h3 class="font-bold text-gray-900 mb-2">Gentle Examination</h3>
                        <p class="text-xs text-gray-600 leading-relaxed">
                            Our dentist performs an oral checkup and discusses all treatment options with you upfront.
                        </p>
                    </div>

                    <div class="rounded-2xl border border-gray-100 bg-gray-50 p-6 text-center">
                        <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-xl bg-teal-100 text-teal-700 font-bold text-lg mb-4">
                            4
                        </div>
                        <h3 class="font-bold text-gray-900 mb-2">Post-Care Support</h3>
                        <p class="text-xs text-gray-600 leading-relaxed">
                            Receive clear post-op guidelines, digital dental records, and friendly follow-up care.
                        </p>
                    </div>
                </div>
            </div>
        </section>

        <!-- Footer -->
        <footer class="bg-gray-900 py-8 text-center text-gray-400 sm:text-left">
            <div class="mx-auto flex max-w-7xl flex-col items-center justify-between gap-4 px-6 sm:flex-row lg:px-8">
                <p class="text-sm">
                    © 2026 Vitality Smile Dental Clinic. All rights reserved.
                </p>

                <div class="flex gap-6 text-sm">
                    <Link href="/" class="hover:text-white">Home</Link>
                    <Link href="/services" class="hover:text-white">Services</Link>
                    <Link href="/#about" class="hover:text-white">About</Link>
                    <Link href="/#contact" class="hover:text-white">Contact</Link>
                </div>
            </div>
        </footer>
    </div>

    <!-- SHARED SERVICE DETAILS MODAL (Limited Action for Patients) -->
    <Modal :show="showDetailsModal" @close="closeDetailsModal" maxWidth="2xl">
        <div v-if="selectedService" class="p-6">
            <div class="flex items-start justify-between border-b border-gray-100 pb-4">
                <div class="flex items-center gap-3">
                    <ServiceIcon :service="selectedService" size="lg" />
                    <div>
                        <span class="inline-block px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider bg-teal-100 text-teal-800 mb-1">
                            {{ selectedService.category }}
                        </span>
                        <h3 class="text-xl font-bold text-gray-900 leading-tight">
                            {{ selectedService.title }}
                        </h3>
                    </div>
                </div>
                <button
                    @click="closeDetailsModal"
                    class="rounded-lg p-1.5 text-gray-400 hover:bg-gray-100 hover:text-gray-600 transition"
                    aria-label="Close"
                >
                    <i class="pi pi-times text-lg"></i>
                </button>
            </div>

            <div class="mt-5 space-y-5">
                <!-- Fee & Duration Quick Info -->
                <div class="grid grid-cols-2 gap-3 p-4 rounded-xl bg-gray-50 border border-gray-100">
                    <div>
                        <div class="text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Estimated Fee
                        </div>
                        <div class="text-lg font-extrabold text-teal-800 mt-0.5">
                            {{ selectedService.price }}
                        </div>
                    </div>
                    <div>
                        <div class="text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Estimated Duration
                        </div>
                        <div class="text-sm font-semibold text-gray-800 mt-1 flex items-center gap-1.5">
                            <i class="pi pi-clock text-teal-600"></i>
                            <span>{{ selectedService.duration || "30 - 45 mins" }}</span>
                        </div>
                    </div>
                </div>

                <!-- Description -->
                <div>
                    <h4 class="text-xs font-bold uppercase tracking-wider text-gray-700 mb-1.5">
                        Treatment Overview
                    </h4>
                    <p class="text-sm leading-relaxed text-gray-600">
                        {{ selectedService.description }}
                    </p>
                </div>

                <!-- Highlights -->
                <div v-if="selectedService.highlights && selectedService.highlights.length > 0">
                    <h4 class="text-xs font-bold uppercase tracking-wider text-gray-700 mb-2">
                        Key Inclusions & Highlights
                    </h4>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                        <div
                            v-for="(hl, i) in selectedService.highlights"
                            :key="i"
                            class="flex items-start gap-2 p-2.5 rounded-lg bg-teal-50/50 border border-teal-100/60 text-xs text-gray-700"
                        >
                            <i class="pi pi-check-circle text-teal-600 text-sm mt-0.5 shrink-0"></i>
                            <span>{{ hl }}</span>
                        </div>
                    </div>
                </div>

                <!-- Patient Tip -->
                <div class="rounded-xl bg-amber-50/70 border border-amber-200/80 p-3.5 flex items-start gap-3">
                    <i class="pi pi-info-circle text-amber-600 text-base mt-0.5 shrink-0"></i>
                    <div class="text-xs text-amber-900 leading-relaxed">
                        <strong>Patient Tip:</strong> Please arrive 10 minutes prior to your scheduled appointment. If you are taking any regular medications or have existing medical conditions, please inform our clinic team during your check-in.
                    </div>
                </div>
            </div>

            <!-- Modal Action Buttons (Limited to booking and closing) -->
            <div class="mt-6 pt-4 border-t border-gray-100 flex items-center justify-end gap-3">
                <button
                    type="button"
                    @click="closeDetailsModal"
                    class="rounded-xl px-4 py-2.5 text-xs font-semibold text-gray-700 hover:bg-gray-100 transition"
                >
                    Close
                </button>
                <Link
                    :href="route('appointments.create', { service: selectedService.name })"
                    class="inline-flex items-center gap-2 rounded-xl bg-teal-700 px-5 py-2.5 text-xs font-semibold text-white shadow-sm hover:bg-teal-800 transition"
                >
                    <i class="pi pi-calendar-plus text-xs"></i>
                    <span>Book This Treatment</span>
                </Link>
            </div>
        </div>
    </Modal>
</template>
