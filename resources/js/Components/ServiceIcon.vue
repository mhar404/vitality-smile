<script setup>
import { computed } from "vue";

const props = defineProps({
    service: {
        type: Object,
        default: null,
    },
    category: {
        type: String,
        default: "",
    },
    icon: {
        type: String,
        default: "",
    },
    title: {
        type: String,
        default: "",
    },
    size: {
        type: String,
        default: "md", // 'sm' | 'md' | 'lg' | 'xl'
    },
    showContainer: {
        type: Boolean,
        default: true,
    },
    customClass: {
        type: String,
        default: "",
    },
});

// Determine procedure archetype / icon type
const iconType = computed(() => {
    const rawIcon = (props.service?.icon || props.icon || "").toLowerCase().trim();
    const title = (props.service?.title || props.service?.name || props.title || "").toLowerCase();
    const cat = (props.service?.category || props.category || "").toLowerCase();

    // 0. If a valid semantic archetype was directly specified (e.g. from DB or admin picker)
    const validArchetypes = ["tooth", "shield", "crown", "sparkles", "gem", "surgery", "root_canal", "denture"];
    if (validArchetypes.includes(rawIcon)) {
        return rawIcon;
    }

    // 1. Check title/name keywords first for exact procedure matching
    if (title.includes("prophylaxis") || title.includes("cleaning") || title.includes("fluoride") || title.includes("checkup")) {
        return "shield";
    }
    if (title.includes("whitening") || title.includes("bleaching") || title.includes("bright")) {
        return "sparkles";
    }
    if (title.includes("zirconia") || title.includes("e-max") || title.includes("tilite") || title.includes("gem")) {
        return "gem";
    }
    if (title.includes("crown") || title.includes("jacket") || title.includes("pfm") || title.includes("veneer") || title.includes("bridge")) {
        return "crown";
    }
    if (title.includes("root canal") || title.includes("endodontic") || title.includes("canal") || title.includes("nerve")) {
        return "root_canal";
    }
    if (title.includes("surgery") || title.includes("extraction") || title.includes("odontectomy") || title.includes("wisdom tooth") || title.includes("removal")) {
        return "surgery";
    }
    if (title.includes("denture") || title.includes("prosthodontics") || title.includes("acrylic") || title.includes("arch")) {
        return "denture";
    }
    if (title.includes("restoration") || title.includes("filling") || title.includes("composite") || title.includes("decay")) {
        return "tooth";
    }

    // 2. Fallback to category
    if (cat === "surgery") return "surgery";
    if (cat === "cosmetic") return "crown";
    if (cat === "prosthodontics") return "denture";
    if (cat === "restorative") return "tooth";
    if (cat === "general") return "shield";

    return "tooth";
});

// Category-themed color palettes for the container
const containerStyle = computed(() => {
    switch (iconType.value) {
        case "shield":
            return "bg-teal-50 text-teal-700 border-teal-200/80 group-hover:bg-teal-600 group-hover:text-white";
        case "tooth":
            return "bg-cyan-50 text-cyan-700 border-cyan-200/80 group-hover:bg-cyan-600 group-hover:text-white";
        case "sparkles":
            return "bg-amber-50 text-amber-700 border-amber-200/80 group-hover:bg-amber-500 group-hover:text-white";
        case "gem":
            return "bg-violet-50 text-violet-700 border-violet-200/80 group-hover:bg-violet-600 group-hover:text-white";
        case "crown":
            return "bg-blue-50 text-blue-700 border-blue-200/80 group-hover:bg-blue-600 group-hover:text-white";
        case "surgery":
            return "bg-rose-50 text-rose-700 border-rose-200/80 group-hover:bg-rose-600 group-hover:text-white";
        case "root_canal":
            return "bg-indigo-50 text-indigo-700 border-indigo-200/80 group-hover:bg-indigo-600 group-hover:text-white";
        case "denture":
            return "bg-purple-50 text-purple-700 border-purple-200/80 group-hover:bg-purple-600 group-hover:text-white";
        default:
            return "bg-teal-50 text-teal-700 border-teal-200/80 group-hover:bg-teal-600 group-hover:text-white";
    }
});

// Container dimension mapping
const containerSize = computed(() => {
    switch (props.size) {
        case "sm":
            return "h-8 w-8 rounded-lg";
        case "lg":
            return "h-14 w-14 rounded-2xl";
        case "xl":
            return "h-16 w-16 rounded-2xl";
        case "md":
        default:
            return "h-11 w-11 sm:h-12 sm:w-12 rounded-xl";
    }
});

// SVG dimensions
const svgSize = computed(() => {
    switch (props.size) {
        case "sm":
            return "h-4 w-4";
        case "lg":
            return "h-7 w-7";
        case "xl":
            return "h-8 w-8";
        case "md":
        default:
            return "h-5 w-5 sm:h-6 sm:w-6";
    }
});
</script>

<template>
    <div
        v-if="showContainer"
        :class="[
            'inline-flex shrink-0 items-center justify-center border shadow-xs transition-all duration-300',
            containerSize,
            containerStyle,
            customClass,
        ]"
    >
        <!-- 1. Tooth / Restoration / Filling -->
        <svg
            v-if="iconType === 'tooth'"
            :class="svgSize"
            viewBox="0 0 24 24"
            fill="none"
            stroke="currentColor"
            stroke-width="2"
            stroke-linecap="round"
            stroke-linejoin="round"
        >
            <path d="M12 2C8.5 2 6 4.5 6 7.5C6 9.5 7 11 7 13C7 16 6 18 6 20C6 21.5 7.5 22 9 22C10.5 22 11 20 12 17C13 20 13.5 22 15 22C16.5 22 18 21.5 18 20C18 18 17 16 17 13C17 11 18 9.5 18 7.5C18 4.5 15.5 2 12 2Z" />
            <path d="M9.5 7C10 6 11 5.5 12 5.5C13 5.5 14 6 14.5 7" stroke-width="1.5" />
        </svg>

        <!-- 2. Shield / Preventive / Cleaning -->
        <svg
            v-else-if="iconType === 'shield'"
            :class="svgSize"
            viewBox="0 0 24 24"
            fill="none"
            stroke="currentColor"
            stroke-width="2"
            stroke-linecap="round"
            stroke-linejoin="round"
        >
            <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z" />
            <path d="M9 12l2 2 4-4" />
        </svg>

        <!-- 3. Crown / Prosthetic / Jackets -->
        <svg
            v-else-if="iconType === 'crown'"
            :class="svgSize"
            viewBox="0 0 24 24"
            fill="none"
            stroke="currentColor"
            stroke-width="2"
            stroke-linecap="round"
            stroke-linejoin="round"
        >
            <path d="M3 8L6 19H18L21 8L15 12L12 4L9 12L3 8Z" />
            <circle cx="12" cy="15" r="1" fill="currentColor" />
        </svg>

        <!-- 4. Sparkles / Teeth Whitening / Aesthetic -->
        <svg
            v-else-if="iconType === 'sparkles'"
            :class="svgSize"
            viewBox="0 0 24 24"
            fill="none"
            stroke="currentColor"
            stroke-width="2"
            stroke-linecap="round"
            stroke-linejoin="round"
        >
            <path d="M12 3L13.8 8.2L19 10L13.8 11.8L12 17L10.2 11.8L5 10L10.2 8.2L12 3Z" />
            <path d="M19 16L19.9 18.1L22 19L19.9 19.9L19 22L18.1 19.9L16 19L18.1 18.1L19 16Z" />
            <path d="M6 3L6.6 4.4L8 5L6.6 5.6L6 7L5.4 5.6L4 5L5.4 4.4L6 3Z" />
        </svg>

        <!-- 5. Gem / Zirconia / E-Max Premium -->
        <svg
            v-else-if="iconType === 'gem'"
            :class="svgSize"
            viewBox="0 0 24 24"
            fill="none"
            stroke="currentColor"
            stroke-width="2"
            stroke-linecap="round"
            stroke-linejoin="round"
        >
            <path d="M6 3h12l4 7-10 11L2 10l4-7z" />
            <path d="M2 10h20" />
            <path d="M12 21L8 10l4-7 4 7-4 11z" />
        </svg>

        <!-- 6. Surgery / Extraction / Wisdom Tooth -->
        <svg
            v-else-if="iconType === 'surgery'"
            :class="svgSize"
            viewBox="0 0 24 24"
            fill="none"
            stroke="currentColor"
            stroke-width="2"
            stroke-linecap="round"
            stroke-linejoin="round"
        >
            <circle cx="12" cy="12" r="9" />
            <path d="M12 8v8" />
            <path d="M8 12h8" />
        </svg>

        <!-- 7. Root Canal / Endodontic Wave -->
        <svg
            v-else-if="iconType === 'root_canal'"
            :class="svgSize"
            viewBox="0 0 24 24"
            fill="none"
            stroke="currentColor"
            stroke-width="2"
            stroke-linecap="round"
            stroke-linejoin="round"
        >
            <path d="M13 2L3 14h9l-1 8 10-12h-9l1-8z" />
        </svg>

        <!-- 8. Denture / Dental Arch -->
        <svg
            v-else-if="iconType === 'denture'"
            :class="svgSize"
            viewBox="0 0 24 24"
            fill="none"
            stroke="currentColor"
            stroke-width="2"
            stroke-linecap="round"
            stroke-linejoin="round"
        >
            <path d="M4 14c0 4.418 3.582 8 8 8s8-3.582 8-8V9a2 2 0 0 0-2-2H6a2 2 0 0 0-2 2v5z" />
            <path d="M4 12h16" />
            <path d="M8 7v5" />
            <path d="M12 7v5" />
            <path d="M16 7v5" />
        </svg>
    </div>

    <!-- Bare SVG (without outer container) -->
    <template v-else>
        <svg
            v-if="iconType === 'tooth'"
            :class="[svgSize, customClass]"
            viewBox="0 0 24 24"
            fill="none"
            stroke="currentColor"
            stroke-width="2"
            stroke-linecap="round"
            stroke-linejoin="round"
        >
            <path d="M12 2C8.5 2 6 4.5 6 7.5C6 9.5 7 11 7 13C7 16 6 18 6 20C6 21.5 7.5 22 9 22C10.5 22 11 20 12 17C13 20 13.5 22 15 22C16.5 22 18 21.5 18 20C18 18 17 16 17 13C17 11 18 9.5 18 7.5C18 4.5 15.5 2 12 2Z" />
        </svg>
        <svg
            v-else-if="iconType === 'shield'"
            :class="[svgSize, customClass]"
            viewBox="0 0 24 24"
            fill="none"
            stroke="currentColor"
            stroke-width="2"
            stroke-linecap="round"
            stroke-linejoin="round"
        >
            <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z" />
            <path d="M9 12l2 2 4-4" />
        </svg>
        <svg
            v-else-if="iconType === 'crown'"
            :class="[svgSize, customClass]"
            viewBox="0 0 24 24"
            fill="none"
            stroke="currentColor"
            stroke-width="2"
            stroke-linecap="round"
            stroke-linejoin="round"
        >
            <path d="M3 8L6 19H18L21 8L15 12L12 4L9 12L3 8Z" />
        </svg>
        <svg
            v-else-if="iconType === 'sparkles'"
            :class="[svgSize, customClass]"
            viewBox="0 0 24 24"
            fill="none"
            stroke="currentColor"
            stroke-width="2"
            stroke-linecap="round"
            stroke-linejoin="round"
        >
            <path d="M12 3L13.8 8.2L19 10L13.8 11.8L12 17L10.2 11.8L5 10L10.2 8.2L12 3Z" />
        </svg>
        <svg
            v-else-if="iconType === 'gem'"
            :class="[svgSize, customClass]"
            viewBox="0 0 24 24"
            fill="none"
            stroke="currentColor"
            stroke-width="2"
            stroke-linecap="round"
            stroke-linejoin="round"
        >
            <path d="M6 3h12l4 7-10 11L2 10l4-7z" />
            <path d="M2 10h20" />
        </svg>
        <svg
            v-else-if="iconType === 'surgery'"
            :class="[svgSize, customClass]"
            viewBox="0 0 24 24"
            fill="none"
            stroke="currentColor"
            stroke-width="2"
            stroke-linecap="round"
            stroke-linejoin="round"
        >
            <circle cx="12" cy="12" r="9" />
            <path d="M12 8v8" />
            <path d="M8 12h8" />
        </svg>
        <svg
            v-else-if="iconType === 'root_canal'"
            :class="[svgSize, customClass]"
            viewBox="0 0 24 24"
            fill="none"
            stroke="currentColor"
            stroke-width="2"
            stroke-linecap="round"
            stroke-linejoin="round"
        >
            <path d="M13 2L3 14h9l-1 8 10-12h-9l1-8z" />
        </svg>
        <svg
            v-else-if="iconType === 'denture'"
            :class="[svgSize, customClass]"
            viewBox="0 0 24 24"
            fill="none"
            stroke="currentColor"
            stroke-width="2"
            stroke-linecap="round"
            stroke-linejoin="round"
        >
            <path d="M4 14c0 4.418 3.582 8 8 8s8-3.582 8-8V9a2 2 0 0 0-2-2H6a2 2 0 0 0-2 2v5z" />
        </svg>
    </template>
</template>
