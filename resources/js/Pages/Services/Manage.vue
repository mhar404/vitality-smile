<script setup>
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import Modal from "@/Components/Modal.vue";
import ServiceIcon from "@/Components/ServiceIcon.vue";
import { Head, Link, router, useForm } from "@inertiajs/vue3";
import { computed, ref, watch } from "vue";

const props = defineProps({
    services: Object,
    filters: {
        type: Object,
        default: () => ({}),
    },
    metrics: {
        type: Object,
        default: () => ({
            total_services: 0,
            active_services: 0,
            inactive_services: 0,
            categories_count: 0,
            avg_base_price: 0,
        }),
    },
    categories: {
        type: Array,
        default: () => [],
    },
});

// Search & filters
const search = ref(props.filters.search || "");
const selectedCategory = ref(props.filters.category || "all");
const selectedStatus = ref(props.filters.status || "all");

let filterTimeout = null;
const applyFilters = () => {
    if (filterTimeout) clearTimeout(filterTimeout);
    filterTimeout = setTimeout(() => {
        router.get(
            route("services.index"),
            {
                search: search.value,
                category: selectedCategory.value,
                status: selectedStatus.value,
            },
            { preserveState: true, replace: true }
        );
    }, 300);
};

watch([search, selectedCategory, selectedStatus], () => {
    applyFilters();
});

const resetFilters = () => {
    search.value = "";
    selectedCategory.value = "all";
    selectedStatus.value = "all";
};

// Modal state
const isModalOpen = ref(false);
const isEditing = ref(false);
const editingService = ref(null);

// Form for create / update
const form = useForm({
    title: "",
    name: "",
    category: "general",
    base_price: 800,
    max_price: null,
    price_display: "",
    duration: "30 - 45 mins",
    duration_minutes: 60,
    icon: "tooth",
    description: "",
    highlights: [""],
    is_active: true,
    sort_order: 0,
});

// Clinical vector icon archetypes
const clinicalIcons = [
    { key: "tooth", label: "Tooth / Exam" },
    { key: "shield", label: "Cleaning / Preventive" },
    { key: "sparkles", label: "Teeth Whitening" },
    { key: "crown", label: "Crown & Bridge" },
    { key: "gem", label: "Veneers / Zirconia" },
    { key: "surgery", label: "Oral Surgery" },
    { key: "root_canal", label: "Root Canal" },
    { key: "denture", label: "Dentures & Prosthetics" },
];

// Open Create Modal
const openCreateModal = () => {
    isEditing.value = false;
    editingService.value = null;
    form.reset();
    form.clearErrors();
    form.category = "general";
    form.base_price = 800;
    form.max_price = null;
    form.duration = "30 - 45 mins";
    form.duration_minutes = 60;
    form.icon = "tooth";
    form.is_active = true;
    form.highlights = [""];
    isModalOpen.value = true;
};

// Open Edit Modal
const openEditModal = (service) => {
    isEditing.value = true;
    editingService.value = service;
    form.clearErrors();
    form.title = service.title;
    form.name = service.name;
    form.category = service.category;
    form.base_price = service.base_price;
    form.max_price = service.max_price;
    form.price_display = service.price_display || "";
    form.duration = service.duration;
    form.duration_minutes = service.duration_minutes || 60;
    form.icon = service.icon || "tooth";
    form.description = service.description || "";
    form.highlights = service.highlights && service.highlights.length > 0 ? [...service.highlights] : [""];
    form.is_active = !!service.is_active;
    form.sort_order = service.sort_order || 0;
    isModalOpen.value = true;
};

// Dynamic highlights
const addHighlight = () => {
    form.highlights.push("");
};

const removeHighlight = (index) => {
    if (form.highlights.length > 1) {
        form.highlights.splice(index, 1);
    } else {
        form.highlights[0] = "";
    }
};

// Auto-fill booking identifier if user hasn't explicitly customized it
const onTitleChange = () => {
    if (!isEditing.value && !form.name) {
        form.name = form.title;
    }
};

// Submit Create/Edit
const submitForm = () => {
    // If identifier is blank, default to title
    if (!form.name && form.title) {
        form.name = form.title;
    }

    if (isEditing.value && editingService.value) {
        form.put(route("services.update", editingService.value.id), {
            onSuccess: () => {
                isModalOpen.value = false;
            },
        });
    } else {
        form.post(route("services.store"), {
            onSuccess: () => {
                isModalOpen.value = false;
            },
        });
    }
};

// Toggle status switch
const toggleStatus = (service) => {
    router.patch(route("services.toggle-status", service.id), {}, {
        preserveScroll: true,
    });
};

// Delete confirmation modal
const isDeleteModalOpen = ref(false);
const serviceToDelete = ref(null);

const confirmDelete = (service) => {
    serviceToDelete.value = service;
    isDeleteModalOpen.value = true;
};

const deleteService = () => {
    if (!serviceToDelete.value) return;
    router.delete(route("services.destroy", serviceToDelete.value.id), {
        onSuccess: () => {
            isDeleteModalOpen.value = false;
            serviceToDelete.value = null;
        },
    });
};

// Category label helper
const getCategoryLabel = (catId) => {
    const found = props.categories.find((c) => c.id === catId);
    return found ? found.name : catId;
};

// Format Currency
const formatCurrency = (val) => {
    const num = parseFloat(val) || 0;
    return "₱" + num.toLocaleString("en-US", { minimumFractionDigits: 0, maximumFractionDigits: 0 });
};
</script>

<template>
    <Head title="Dental Services Management - Vitality Smile" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h2 class="text-xl font-bold leading-tight text-gray-900 sm:text-2xl">
                        Dental Services & Pricing
                    </h2>
                    <p class="mt-1 text-xs text-gray-500 sm:text-sm">
                        Manage clinic dental procedures, active appointment booking services, pricing rates, and patient-facing descriptions.
                    </p>
                </div>
                <div class="flex items-center gap-3">
                    <Link
                        :href="route('services.index') + '?view=public'"
                        target="_blank"
                        class="inline-flex items-center gap-2 rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-xs font-semibold text-gray-700 shadow-sm transition hover:bg-gray-50 focus:outline-none"
                    >
                        <i class="pi pi-external-link text-xs text-teal-600"></i>
                        <span>View Public Catalog</span>
                    </Link>
                    <button
                        v-if="$page.props.auth.user.role !== 'staff'"
                        @click="openCreateModal"
                        class="inline-flex items-center gap-2 rounded-xl bg-teal-700 px-4 py-2.5 text-xs font-semibold text-white shadow-sm transition hover:bg-teal-800 focus:outline-none focus:ring-2 focus:ring-teal-500 focus:ring-offset-2"
                    >
                        <i class="pi pi-plus text-xs"></i>
                        <span>Add Service</span>
                    </button>
                </div>
            </div>
        </template>

        <div class="py-6 sm:py-8">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 space-y-6">
                <!-- Metrics Summary Cards -->
                <div class="grid grid-cols-2 gap-4 sm:grid-cols-4">
                    <div class="rounded-2xl border border-gray-200 bg-white p-4 sm:p-5 shadow-sm">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-xs font-semibold uppercase tracking-wider text-gray-500">Total Services</p>
                                <p class="mt-1 text-2xl font-bold text-gray-900">{{ metrics.total_services }}</p>
                            </div>
                            <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-teal-50 text-teal-700 text-lg">
                                <i class="pi pi-list"></i>
                            </div>
                        </div>
                    </div>

                    <div class="rounded-2xl border border-gray-200 bg-white p-4 sm:p-5 shadow-sm">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-xs font-semibold uppercase tracking-wider text-gray-500">Active for Booking</p>
                                <p class="mt-1 text-2xl font-bold text-emerald-600">{{ metrics.active_services }}</p>
                            </div>
                            <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-emerald-50 text-emerald-600 text-lg">
                                <i class="pi pi-check-circle"></i>
                            </div>
                        </div>
                    </div>

                    <div class="rounded-2xl border border-gray-200 bg-white p-4 sm:p-5 shadow-sm">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-xs font-semibold uppercase tracking-wider text-gray-500">Inactive / Hidden</p>
                                <p class="mt-1 text-2xl font-bold text-amber-600">{{ metrics.inactive_services }}</p>
                            </div>
                            <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-amber-50 text-amber-600 text-lg">
                                <i class="pi pi-eye-slash"></i>
                            </div>
                        </div>
                    </div>

                    <div class="rounded-2xl border border-gray-200 bg-white p-4 sm:p-5 shadow-sm">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-xs font-semibold uppercase tracking-wider text-gray-500">Avg Base Fee</p>
                                <p class="mt-1 text-2xl font-bold text-teal-900">{{ formatCurrency(metrics.avg_base_price) }}</p>
                            </div>
                            <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-teal-50 text-teal-700 text-lg">
                                <i class="pi pi-tag"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Filters & Search Toolbar -->
                <div class="rounded-2xl border border-gray-200 bg-white p-4 sm:p-5 shadow-sm space-y-4">
                    <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                        <!-- Search Box -->
                        <div class="relative flex-1 max-w-md">
                            <i class="pi pi-search absolute left-3.5 top-1/2 -translate-y-1/2 text-gray-400 text-sm"></i>
                            <input
                                v-model="search"
                                type="text"
                                placeholder="Search services by procedure name, keyword, or category..."
                                class="w-full rounded-xl border border-gray-300 py-2 pl-10 pr-4 text-sm focus:border-teal-500 focus:outline-none focus:ring-1 focus:ring-teal-500"
                            />
                            <button
                                v-if="search"
                                @click="search = ''"
                                class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600"
                            >
                                <i class="pi pi-times text-xs"></i>
                            </button>
                        </div>

                        <!-- Status Filter -->
                        <div class="flex items-center gap-2">
                            <span class="text-xs font-medium text-gray-500">Status:</span>
                            <div class="inline-flex rounded-xl border border-gray-200 bg-gray-50 p-1">
                                <button
                                    type="button"
                                    @click="selectedStatus = 'all'"
                                    :class="[
                                        'rounded-lg px-3 py-1.5 text-xs font-semibold transition',
                                        selectedStatus === 'all' ? 'bg-white text-teal-700 shadow-sm' : 'text-gray-600 hover:text-gray-900'
                                    ]"
                                >
                                    All
                                </button>
                                <button
                                    type="button"
                                    @click="selectedStatus = 'active'"
                                    :class="[
                                        'rounded-lg px-3 py-1.5 text-xs font-semibold transition',
                                        selectedStatus === 'active' ? 'bg-white text-emerald-600 shadow-sm' : 'text-gray-600 hover:text-gray-900'
                                    ]"
                                >
                                    Active
                                </button>
                                <button
                                    type="button"
                                    @click="selectedStatus = 'inactive'"
                                    :class="[
                                        'rounded-lg px-3 py-1.5 text-xs font-semibold transition',
                                        selectedStatus === 'inactive' ? 'bg-white text-amber-600 shadow-sm' : 'text-gray-600 hover:text-gray-900'
                                    ]"
                                >
                                    Inactive
                                </button>
                            </div>

                            <button
                                v-if="search || selectedCategory !== 'all' || selectedStatus !== 'all'"
                                @click="resetFilters"
                                class="ml-2 text-xs font-medium text-teal-700 hover:text-teal-900 hover:underline"
                            >
                                Reset
                            </button>
                        </div>
                    </div>

                    <!-- Category Pills -->
                    <div class="flex flex-wrap items-center gap-1.5 border-t border-gray-100 pt-3">
                        <span class="text-xs font-medium text-gray-400 mr-1">Categories:</span>
                        <button
                            v-for="cat in categories"
                            :key="cat.id"
                            type="button"
                            @click="selectedCategory = cat.id"
                            :class="[
                                'rounded-full px-3 py-1 text-xs font-medium transition',
                                selectedCategory === cat.id
                                    ? 'bg-teal-700 text-white shadow-sm'
                                    : 'bg-gray-100 text-gray-600 hover:bg-gray-200'
                            ]"
                        >
                            {{ cat.name }}
                        </button>
                    </div>
                </div>

                <!-- Services Data Table -->
                <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 text-left text-sm">
                            <thead class="bg-gray-50 text-xs font-semibold uppercase tracking-wider text-gray-600">
                                <tr>
                                    <th scope="col" class="py-3.5 pl-4 pr-3 sm:pl-6">Procedure / Service</th>
                                    <th scope="col" class="px-3 py-3.5">Category</th>
                                    <th scope="col" class="px-3 py-3.5">Pricing</th>
                                    <th scope="col" class="px-3 py-3.5">Duration</th>
                                    <th scope="col" class="px-3 py-3.5 text-center">Status</th>
                                    <th scope="col" class="py-3.5 pl-3 pr-4 sm:pr-6 text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 bg-white">
                                <tr
                                    v-for="service in services.data"
                                    :key="service.id"
                                    :class="[
                                        'transition hover:bg-gray-50/80',
                                        !service.is_active ? 'opacity-60 bg-gray-50/40' : ''
                                    ]"
                                >
                                    <!-- Procedure info with Icon -->
                                    <td class="py-4 pl-4 pr-3 sm:pl-6">
                                        <div class="flex items-start gap-3">
                                            <ServiceIcon :service="service" size="sm" class="shrink-0 mt-0.5" />
                                            <div class="min-w-0">
                                                <div class="font-bold text-gray-900 leading-snug">
                                                    {{ service.title }}
                                                </div>
                                                <div class="mt-0.5 line-clamp-1 text-xs text-gray-500">
                                                    {{ service.description || 'No description provided.' }}
                                                </div>
                                                <div class="mt-1 flex items-center gap-2">
                                                    <span class="inline-flex items-center gap-1 text-[11px] font-mono text-gray-400">
                                                        <i class="pi pi-key text-[10px]"></i>
                                                        {{ service.name }}
                                                    </span>
                                                    <span
                                                        v-if="service.highlights && service.highlights.length"
                                                        class="inline-flex items-center gap-1 rounded-md bg-teal-50 px-1.5 py-0.5 text-[10px] font-medium text-teal-700"
                                                    >
                                                        <i class="pi pi-check text-[9px]"></i>
                                                        {{ service.highlights.length }} highlights
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                    </td>

                                    <!-- Category Badge -->
                                    <td class="whitespace-nowrap px-3 py-4">
                                        <span class="inline-flex items-center rounded-lg bg-gray-100 px-2.5 py-1 text-xs font-semibold capitalize text-gray-700">
                                            {{ getCategoryLabel(service.category) }}
                                        </span>
                                    </td>

                                    <!-- Pricing -->
                                    <td class="whitespace-nowrap px-3 py-4">
                                        <div class="font-bold text-teal-800">
                                            {{ service.formatted_price }}
                                        </div>
                                        <div class="text-[11px] text-gray-400">
                                            Base: {{ formatCurrency(service.base_price) }}
                                            <span v-if="service.max_price"> &bull; Max: {{ formatCurrency(service.max_price) }}</span>
                                        </div>
                                    </td>

                                    <!-- Duration -->
                                    <td class="whitespace-nowrap px-3 py-4 text-xs text-gray-600">
                                        <div class="inline-flex items-center gap-1.5 rounded-lg bg-gray-50 px-2.5 py-1 font-medium">
                                            <i class="pi pi-clock text-[11px] text-teal-600"></i>
                                            <span>{{ service.duration }}</span>
                                            <span class="rounded bg-teal-100/70 px-1 py-0.5 text-[10px] font-bold text-teal-800">
                                                {{ service.duration_minutes || 60 }}m
                                            </span>
                                        </div>
                                    </td>

                                    <!-- Active Toggle -->
                                    <td class="whitespace-nowrap px-3 py-4 text-center">
                                        <button
                                            v-if="$page.props.auth.user.role !== 'staff'"
                                            type="button"
                                            @click="toggleStatus(service)"
                                            :title="service.is_active ? 'Click to deactivate' : 'Click to activate'"
                                            :class="[
                                                'relative inline-flex h-6 w-11 shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none',
                                                service.is_active ? 'bg-emerald-600' : 'bg-gray-300'
                                            ]"
                                        >
                                            <span
                                                :class="[
                                                    'pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out',
                                                    service.is_active ? 'translate-x-5' : 'translate-x-0'
                                                ]"
                                            />
                                        </button>
                                        <div
                                            v-else
                                            class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-medium"
                                            :class="service.is_active ? 'bg-emerald-50 text-emerald-700' : 'bg-gray-100 text-gray-600'"
                                        >
                                            <span class="h-1.5 w-1.5 rounded-full" :class="service.is_active ? 'bg-emerald-500' : 'bg-gray-400'"></span>
                                            {{ service.is_active ? 'Active' : 'Inactive' }}
                                        </div>
                                        <div v-if="$page.props.auth.user.role !== 'staff'" class="text-[10px] font-medium text-gray-500 mt-0.5">
                                            {{ service.is_active ? 'Active' : 'Inactive' }}
                                        </div>
                                    </td>

                                    <!-- Actions -->
                                    <td class="whitespace-nowrap py-4 pl-3 pr-4 sm:pr-6 text-right">
                                        <div v-if="$page.props.auth.user.role !== 'staff'" class="flex items-center justify-end gap-1.5">
                                            <button
                                                type="button"
                                                @click="openEditModal(service)"
                                                class="rounded-lg p-2 text-gray-500 hover:bg-teal-50 hover:text-teal-700 transition"
                                                title="Edit Service"
                                            >
                                                <i class="pi pi-pencil text-sm"></i>
                                            </button>
                                            <button
                                                type="button"
                                                @click="confirmDelete(service)"
                                                class="rounded-lg p-2 text-gray-500 hover:bg-red-50 hover:text-red-600 transition"
                                                title="Delete / Deactivate Service"
                                            >
                                                <i class="pi pi-trash text-sm"></i>
                                            </button>
                                        </div>
                                        <span v-else class="text-xs text-gray-400 italic">View only</span>
                                    </td>
                                </tr>

                                <!-- Empty State -->
                                <tr v-if="!services.data || services.data.length === 0">
                                    <td colspan="6" class="py-12 text-center text-gray-500">
                                        <div class="flex flex-col items-center justify-center">
                                            <i class="pi pi-inbox text-4xl text-gray-300"></i>
                                            <p class="mt-2 text-sm font-semibold text-gray-700">No dental services found</p>
                                            <p class="text-xs text-gray-400 mt-0.5">Try adjusting your filters or click "Add Service" to create one.</p>
                                            <button
                                                v-if="$page.props.auth.user.role !== 'staff'"
                                                @click="openCreateModal"
                                                class="mt-4 rounded-xl bg-teal-700 px-4 py-2 text-xs font-semibold text-white hover:bg-teal-800"
                                            >
                                                + Add First Service
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <div
                        v-if="services.links && services.links.length > 3"
                        class="flex items-center justify-between border-t border-gray-200 bg-white px-4 py-3 sm:px-6"
                    >
                        <div class="text-xs text-gray-500">
                            Showing <span class="font-medium text-gray-700">{{ services.from || 0 }}</span> to
                            <span class="font-medium text-gray-700">{{ services.to || 0 }}</span> of
                            <span class="font-medium text-gray-700">{{ services.total || 0 }}</span> services
                        </div>
                        <div class="flex items-center gap-1">
                            <Link
                                v-for="(link, idx) in services.links"
                                :key="idx"
                                :href="link.url || '#'"
                                :class="[
                                    'rounded-lg px-3 py-1.5 text-xs font-semibold transition',
                                    link.active
                                        ? 'bg-teal-700 text-white shadow-sm'
                                        : link.url
                                        ? 'text-gray-700 hover:bg-gray-100'
                                        : 'cursor-not-allowed text-gray-300'
                                ]"
                                v-html="link.label"
                            />
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Add / Edit Service Modal -->
        <Modal :show="isModalOpen" @close="isModalOpen = false" max-width="2xl">
            <div class="p-6">
                <div class="flex items-center justify-between border-b border-gray-100 pb-4">
                    <div class="flex items-center gap-3">
                        <ServiceIcon :icon="form.icon" :title="form.title" :category="form.category" size="md" />
                        <div>
                            <h3 class="text-lg font-bold text-gray-900">
                                {{ isEditing ? 'Edit Dental Service' : 'Add New Dental Service' }}
                            </h3>
                            <p class="text-xs text-gray-500">
                                Configure the procedure details, pricing tier, and patient highlights.
                            </p>
                        </div>
                    </div>
                    <button
                        @click="isModalOpen = false"
                        class="rounded-lg p-2 text-gray-400 hover:bg-gray-100 hover:text-gray-600"
                    >
                        <i class="pi pi-times"></i>
                    </button>
                </div>

                <form @submit.prevent="submitForm" class="mt-5 space-y-4">
                    <!-- Title & Identifier -->
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div>
                            <label class="block text-xs font-semibold text-gray-700 mb-1">
                                Service Title <span class="text-red-500">*</span>
                            </label>
                            <input
                                v-model="form.title"
                                @input="onTitleChange"
                                type="text"
                                required
                                placeholder="e.g. Oral Prophylaxis (Dental Cleaning)"
                                class="w-full rounded-xl border-gray-300 text-sm focus:border-teal-500 focus:ring-teal-500"
                            />
                            <p v-if="form.errors.title" class="mt-1 text-xs text-red-600">{{ form.errors.title }}</p>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-gray-700 mb-1">
                                Booking Key / Identifier <span class="text-red-500">*</span>
                            </label>
                            <input
                                v-model="form.name"
                                type="text"
                                required
                                placeholder="e.g. Oral Prophylaxis (₱800-₱1,000)"
                                class="w-full rounded-xl border-gray-300 text-sm focus:border-teal-500 focus:ring-teal-500"
                            />
                            <p class="mt-0.5 text-[11px] text-gray-400">Used by appointment booking and invoice selection.</p>
                            <p v-if="form.errors.name" class="mt-1 text-xs text-red-600">{{ form.errors.name }}</p>
                        </div>
                    </div>

                    <!-- Category & Duration -->
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                        <div>
                            <label class="block text-xs font-semibold text-gray-700 mb-1">
                                Category <span class="text-red-500">*</span>
                            </label>
                            <select
                                v-model="form.category"
                                class="w-full rounded-xl border-gray-300 text-sm focus:border-teal-500 focus:ring-teal-500"
                            >
                                <option value="general">General & Preventive</option>
                                <option value="restorative">Restorative & Endodontics</option>
                                <option value="cosmetic">Cosmetic & Crowns</option>
                                <option value="surgery">Oral Surgery</option>
                                <option value="prosthodontics">Dentures & Prosthetics</option>
                                <option value="orthodontics">Orthodontics & Braces</option>
                            </select>
                            <p v-if="form.errors.category" class="mt-1 text-xs text-red-600">{{ form.errors.category }}</p>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-gray-700 mb-1">
                                Display Duration <span class="text-red-500">*</span>
                            </label>
                            <input
                                v-model="form.duration"
                                type="text"
                                required
                                placeholder="e.g. 30 - 45 mins"
                                class="w-full rounded-xl border-gray-300 text-sm focus:border-teal-500 focus:ring-teal-500"
                            />
                            <p v-if="form.errors.duration" class="mt-1 text-xs text-red-600">{{ form.errors.duration }}</p>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-gray-700 mb-1">
                                Slot Minutes <span class="text-red-500">*</span>
                            </label>
                            <input
                                v-model.number="form.duration_minutes"
                                type="number"
                                min="15"
                                max="480"
                                step="15"
                                required
                                placeholder="60"
                                class="w-full rounded-xl border-gray-300 text-sm focus:border-teal-500 focus:ring-teal-500"
                            />
                            <p class="mt-0.5 text-[10px] text-gray-500">Duration blocked in schedule</p>
                            <p v-if="form.errors.duration_minutes" class="mt-1 text-xs text-red-600">{{ form.errors.duration_minutes }}</p>
                        </div>
                    </div>

                    <!-- Clinical Vector Icon Archetype Selection -->
                    <div>
                        <div class="flex items-center justify-between mb-1.5">
                            <label class="block text-xs font-semibold text-gray-700">
                                Clinical Icon Archetype
                            </label>
                            <span class="text-[11px] text-gray-400">
                                Vector badge styling updates automatically
                            </span>
                        </div>
                        <div class="grid grid-cols-2 sm:grid-cols-4 gap-2">
                            <button
                                v-for="ico in clinicalIcons"
                                :key="ico.key"
                                type="button"
                                @click="form.icon = ico.key"
                                :class="[
                                    'flex items-center gap-2.5 rounded-xl p-2 border transition text-left text-xs',
                                    form.icon === ico.key
                                        ? 'border-teal-600 bg-teal-50/80 ring-2 ring-teal-500/20 font-semibold text-teal-900 shadow-sm'
                                        : 'border-gray-200 bg-white hover:bg-gray-50/90 text-gray-700'
                                ]"
                            >
                                <ServiceIcon :icon="ico.key" size="sm" />
                                <span class="truncate text-xs font-medium">{{ ico.label }}</span>
                            </button>
                        </div>
                    </div>

                    <!-- Pricing Details -->
                    <div class="rounded-xl border border-teal-100 bg-teal-50/50 p-4 space-y-3">
                        <div class="text-xs font-bold uppercase tracking-wider text-teal-900">
                            Pricing Configuration
                        </div>
                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                            <div>
                                <label class="block text-xs font-semibold text-gray-700 mb-1">
                                    Base Price (₱) <span class="text-red-500">*</span>
                                </label>
                                <div class="relative">
                                    <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-400 text-xs">₱</span>
                                    <input
                                        v-model="form.base_price"
                                        type="number"
                                        step="0.01"
                                        min="0"
                                        required
                                        placeholder="0.00"
                                        class="w-full rounded-xl border-gray-300 pl-7 text-sm focus:border-teal-500 focus:ring-teal-500 bg-white"
                                    />
                                </div>
                                <p v-if="form.errors.base_price" class="mt-1 text-xs text-red-600">{{ form.errors.base_price }}</p>
                            </div>

                            <div>
                                <label class="block text-xs font-semibold text-gray-700 mb-1">
                                    Max Price (₱) <span class="text-gray-400 font-normal">(Optional for ranges)</span>
                                </label>
                                <div class="relative">
                                    <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-400 text-xs">₱</span>
                                    <input
                                        v-model="form.max_price"
                                        type="number"
                                        step="0.01"
                                        min="0"
                                        placeholder="Optional range max"
                                        class="w-full rounded-xl border-gray-300 pl-7 text-sm focus:border-teal-500 focus:ring-teal-500 bg-white"
                                    />
                                </div>
                                <p v-if="form.errors.max_price" class="mt-1 text-xs text-red-600">{{ form.errors.max_price }}</p>
                            </div>

                            <div>
                                <label class="block text-xs font-semibold text-gray-700 mb-1">
                                    Custom Price Label <span class="text-gray-400 font-normal">(Override)</span>
                                </label>
                                <input
                                    v-model="form.price_display"
                                    type="text"
                                    placeholder="e.g. ₱8,000 per canal"
                                    class="w-full rounded-xl border-gray-300 text-sm focus:border-teal-500 focus:ring-teal-500 bg-white"
                                />
                                <p class="mt-0.5 text-[10px] text-gray-500">Overrides automatic ₱800 - ₱1,000 label</p>
                            </div>
                        </div>
                    </div>

                    <!-- Description -->
                    <div>
                        <label class="block text-xs font-semibold text-gray-700 mb-1">
                            Procedure Description
                        </label>
                        <textarea
                            v-model="form.description"
                            rows="2"
                            placeholder="Briefly describe what this treatment entails for clinic staff and patients..."
                            class="w-full rounded-xl border-gray-300 text-sm focus:border-teal-500 focus:ring-teal-500"
                        ></textarea>
                        <p v-if="form.errors.description" class="mt-1 text-xs text-red-600">{{ form.errors.description }}</p>
                    </div>

                    <!-- Highlights List -->
                    <div>
                        <div class="flex items-center justify-between mb-1.5">
                            <label class="block text-xs font-semibold text-gray-700">
                                Patient Highlights & Key Points
                            </label>
                            <button
                                type="button"
                                @click="addHighlight"
                                class="inline-flex items-center gap-1 text-xs font-semibold text-teal-700 hover:text-teal-900"
                            >
                                <i class="pi pi-plus text-[10px]"></i>
                                <span>Add Point</span>
                            </button>
                        </div>
                        <div class="space-y-2">
                            <div
                                v-for="(hl, idx) in form.highlights"
                                :key="idx"
                                class="flex items-center gap-2"
                            >
                                <i class="pi pi-check text-xs text-teal-600"></i>
                                <input
                                    v-model="form.highlights[idx]"
                                    type="text"
                                    placeholder="e.g. Ultrasonic tartar removal"
                                    class="w-full rounded-lg border-gray-300 text-xs py-1.5 focus:border-teal-500 focus:ring-teal-500"
                                />
                                <button
                                    type="button"
                                    @click="removeHighlight(idx)"
                                    class="p-1.5 text-gray-400 hover:text-red-500"
                                    title="Remove point"
                                >
                                    <i class="pi pi-trash text-xs"></i>
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Active Toggle -->
                    <div class="flex items-center justify-between border-t border-gray-100 pt-3">
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input
                                v-model="form.is_active"
                                type="checkbox"
                                class="rounded border-gray-300 text-teal-600 focus:ring-teal-500"
                            />
                            <span class="text-xs font-semibold text-gray-700">Active and visible for patient booking & clinical records</span>
                        </label>

                        <div class="flex items-center gap-2">
                            <label class="text-xs text-gray-500">Sort Order:</label>
                            <input
                                v-model="form.sort_order"
                                type="number"
                                class="w-16 text-center text-xs rounded-lg border-gray-300 py-1"
                            />
                        </div>
                    </div>

                    <!-- Modal Actions -->
                    <div class="flex items-center justify-end gap-3 border-t border-gray-100 pt-4">
                        <button
                            type="button"
                            @click="isModalOpen = false"
                            class="rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-xs font-semibold text-gray-700 hover:bg-gray-50"
                        >
                            Cancel
                        </button>
                        <button
                            type="submit"
                            :disabled="form.processing"
                            class="inline-flex items-center gap-2 rounded-xl bg-teal-700 px-5 py-2.5 text-xs font-semibold text-white shadow-sm hover:bg-teal-800 focus:outline-none focus:ring-2 focus:ring-teal-500 disabled:opacity-50"
                        >
                            <i v-if="form.processing" class="pi pi-spin pi-spinner text-xs"></i>
                            <span>{{ isEditing ? 'Save Changes' : 'Create Service' }}</span>
                        </button>
                    </div>
                </form>
            </div>
        </Modal>

        <!-- Delete / Deactivate Confirmation Modal -->
        <Modal :show="isDeleteModalOpen" @close="isDeleteModalOpen = false" max-width="md">
            <div class="p-6 text-center">
                <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-red-100 text-red-600 mb-4">
                    <i class="pi pi-exclamation-triangle text-xl"></i>
                </div>
                <h3 class="text-lg font-bold text-gray-900">
                    Delete Dental Service
                </h3>
                <p class="mt-2 text-xs text-gray-500">
                    Are you sure you want to remove <span class="font-bold text-gray-700">"{{ serviceToDelete?.title }}"</span>?
                    <br /><br />
                    If this service has been used in past appointments or dental treatments, it will be safely <span class="font-semibold text-amber-700">deactivated</span> rather than permanently deleted to preserve clinical history.
                </p>
                <div class="mt-6 flex items-center justify-center gap-3">
                    <button
                        type="button"
                        @click="isDeleteModalOpen = false"
                        class="rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-xs font-semibold text-gray-700 hover:bg-gray-50"
                    >
                        Cancel
                    </button>
                    <button
                        type="button"
                        @click="deleteService"
                        class="rounded-xl bg-red-600 px-4 py-2.5 text-xs font-semibold text-white shadow-sm hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500"
                    >
                        Confirm Delete / Deactivate
                    </button>
                </div>
            </div>
        </Modal>
    </AuthenticatedLayout>
</template>
