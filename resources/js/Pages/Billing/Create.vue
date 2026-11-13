<script setup>
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import { Head, Link, useForm } from "@inertiajs/vue3";
import { computed, ref, watch, onMounted } from "vue";

const props = defineProps({
    patients: {
        type: Array,
        default: () => [],
    },
    preselected_patient_id: [Number, String],
    preselected_treatment: Object,
    preselected_appointment: Object,
    next_invoice_number: String,
    today: String,
    services: {
        type: Array,
        default: () => [],
    },
});

// Quick dental service templates
const quickServices = computed(() => {
    if (props.services && props.services.length > 0) {
        return props.services.map((s) => ({
            name: s.title || s.name,
            price: parseFloat(s.base_price) || 0,
            base_price: parseFloat(s.base_price) || 0,
            max_price: s.max_price ? parseFloat(s.max_price) : parseFloat(s.base_price) || 0,
        }));
    }
    return [
        { name: "Oral Prophylaxis (Cleaning)", price: 800, base_price: 800, max_price: 1000 },
        { name: "Composite Dental Filling (Per Tooth)", price: 800, base_price: 800, max_price: 1000 },
        { name: "Simple Tooth Extraction", price: 800, base_price: 800, max_price: 1000 },
        { name: "Surgical Wisdom Tooth Extraction", price: 8000, base_price: 8000, max_price: 20000 },
        { name: "Root Canal Therapy (Per Canal)", price: 8000, base_price: 8000, max_price: 8000 },
        { name: "Porcelain / Zirconia Dental Crown", price: 7000, base_price: 7000, max_price: 8000 },
        { name: "In-Office Teeth Whitening Session", price: 8000, base_price: 8000, max_price: 10000 },
        { name: "Orthodontic Monthly Adjustment", price: 1500, base_price: 1500, max_price: 1500 },
    ];
});

// Comprehensive catalog price matcher ensuring procedure fee strictly aligns with procedure catalog
const lookupItemFee = (description) => {
    if (!description || !description.trim()) {
        return {
            min: 0,
            max: null,
            isFixed: false,
            defaultFee: 0,
        };
    }

    const clean = description.trim();

    // 1. Check in props.services if available
    const catalog = props.services || [];
    let matched = catalog.find(
        (s) =>
            (s.name && s.name.toLowerCase() === clean.toLowerCase()) ||
            (s.title && s.title.toLowerCase() === clean.toLowerCase())
    );

    if (!matched) {
        const lower = clean.toLowerCase();
        matched = catalog.find(
            (s) =>
                (s.name && s.name.toLowerCase() === lower) ||
                (s.title && s.title.toLowerCase() === lower) ||
                (s.name && lower.includes(s.name.toLowerCase())) ||
                (s.title && lower.includes(s.title.toLowerCase())) ||
                (s.name && s.name.toLowerCase().includes(lower)) ||
                (s.title && s.title.toLowerCase().includes(lower))
        );
    }

    if (matched) {
        const min = parseFloat(matched.base_price) || 0;
        const max = matched.max_price ? parseFloat(matched.max_price) : min;
        return {
            min,
            max,
            isFixed: min === max,
            defaultFee: min,
        };
    }

    // 2. Parse from description string if contains price range or single price like ₱800-₱1,000 or ₱1,500
    const rangeMatch = clean.match(
        /₱\s*([0-9,]+)\s*[-–]\s*₱?\s*([0-9,]+)/
    );
    if (rangeMatch) {
        const min = parseFloat(rangeMatch[1].replace(/,/g, ""));
        const max = parseFloat(rangeMatch[2].replace(/,/g, ""));
        return {
            min,
            max,
            isFixed: min === max,
            defaultFee: min,
        };
    }

    const singleMatch = clean.match(/₱\s*([0-9,]+)/);
    if (singleMatch) {
        const price = parseFloat(singleMatch[1].replace(/,/g, ""));
        return {
            min: price,
            max: price,
            isFixed: true,
            defaultFee: price,
        };
    }

    // 3. Fallback keywords based on standard Vitality Smile clinic catalog
    const proc = clean.toLowerCase();
    if (proc.includes("prophylaxis") || proc.includes("cleaning")) {
        return { min: 800, max: 1000, isFixed: false, defaultFee: 800 };
    } else if (proc.includes("restoration") || proc.includes("filling")) {
        return { min: 800, max: 1000, isFixed: false, defaultFee: 800 };
    } else if (
        proc.includes("extraction") &&
        !proc.includes("wisdom") &&
        !proc.includes("odontectomy")
    ) {
        return { min: 800, max: 1000, isFixed: false, defaultFee: 800 };
    } else if (proc.includes("wisdom tooth") || proc.includes("erupted")) {
        return { min: 5000, max: 8000, isFixed: false, defaultFee: 5000 };
    } else if (
        proc.includes("odontectomy") ||
        proc.includes("surgery") ||
        proc.includes("impacted")
    ) {
        return { min: 8000, max: 20000, isFixed: false, defaultFee: 8000 };
    } else if (proc.includes("root canal")) {
        return { min: 8000, max: 8000, isFixed: true, defaultFee: 8000 };
    } else if (proc.includes("whitening")) {
        return { min: 8000, max: 10000, isFixed: false, defaultFee: 8000 };
    } else if (proc.includes("plastic jacket")) {
        return { min: 5000, max: 6000, isFixed: false, defaultFee: 5000 };
    } else if (
        proc.includes("porcelain") ||
        proc.includes("metal jacket") ||
        proc.includes("pfm")
    ) {
        return { min: 7000, max: 8000, isFixed: false, defaultFee: 7000 };
    } else if (proc.includes("bridge")) {
        return { min: 7000, max: 8000, isFixed: false, defaultFee: 7000 };
    } else if (proc.includes("denture")) {
        return { min: 10000, max: 15000, isFixed: false, defaultFee: 10000 };
    } else if (
        proc.includes("orthodontic") ||
        proc.includes("adjustment") ||
        proc.includes("braces")
    ) {
        return { min: 1500, max: 1500, isFixed: true, defaultFee: 1500 };
    } else if (proc.includes("fluoride")) {
        return { min: 800, max: 800, isFixed: true, defaultFee: 800 };
    } else if (proc.includes("check-up") || proc.includes("consultation")) {
        return { min: 500, max: 500, isFixed: true, defaultFee: 500 };
    }

    return {
        min: 0,
        max: null,
        isFixed: false,
        defaultFee: 0,
    };
};

const onFeeKeydown = (e) => {
    // Prevent typing negative numbers, plus signs, and scientific notation
    if (["-", "+", "e", "E"].includes(e.key)) {
        e.preventDefault();
    }
};

const validateAndClampItemPrice = (index, isBlur = false, event = null) => {
    const item = form.items[index];
    if (!item) return;

    const config = lookupItemFee(item.description);
    if (config.isFixed) {
        item.unit_price = config.min;
        if (event?.target) event.target.value = config.min;
        return;
    }

    const val = parseFloat(item.unit_price);
    if (isNaN(val)) {
        if (isBlur) {
            item.unit_price = config.min;
            if (event?.target) event.target.value = config.min;
        }
        return;
    }

    // Input cannot be higher than maximum
    if (config.max !== null && config.max !== undefined && val > config.max) {
        item.unit_price = config.max;
        if (event?.target) event.target.value = config.max;
        return;
    }

    // Input cannot be lower than zero
    if (val < 0) {
        item.unit_price = config.min;
        if (event?.target) event.target.value = config.min;
        return;
    }

    // On blur or change, input cannot be lower than minimum
    if (isBlur && val < config.min) {
        item.unit_price = config.min;
        if (event?.target) event.target.value = config.min;
    }
};

const onDescriptionChange = (index) => {
    const item = form.items[index];
    if (!item) return;
    const config = lookupItemFee(item.description);
    if (config.isFixed) {
        item.unit_price = config.min;
    } else {
        const val = parseFloat(item.unit_price);
        if (isNaN(val) || val === 0 || val < config.min) {
            item.unit_price = config.defaultFee || config.min;
        } else if (config.max !== null && val > config.max) {
            item.unit_price = config.max;
        }
    }
};

// Initialize initial line item
const initialItems = [];
if (props.preselected_treatment) {
    let desc = props.preselected_treatment.procedure_name;
    if (props.preselected_treatment.tooth_number) {
        desc += ` (Tooth #${props.preselected_treatment.tooth_number})`;
    }
    const config = lookupItemFee(desc);
    let cost = parseFloat(props.preselected_treatment.cost);
    if (isNaN(cost)) {
        cost = config.defaultFee;
    } else if (config.isFixed) {
        cost = config.min;
    } else {
        if (config.min !== null && cost < config.min) cost = config.min;
        if (config.max !== null && cost > config.max) cost = config.max;
    }
    initialItems.push({
        description: desc,
        quantity: 1,
        unit_price: cost,
        treatment_id: props.preselected_treatment.id,
    });
} else if (props.preselected_appointment) {
    const aptService = props.preselected_appointment.service || "Dental Consultation";
    const config = lookupItemFee(aptService);
    let estCost = config.defaultFee || 800;
    initialItems.push({
        description: aptService,
        quantity: 1,
        unit_price: estCost,
        treatment_id: null,
    });
} else {
    initialItems.push({
        description: "",
        quantity: 1,
        unit_price: 0,
        treatment_id: null,
    });
}

const form = useForm({
    patient_id: props.preselected_patient_id ? Number(props.preselected_patient_id) : "",
    appointment_id: props.preselected_appointment?.id ? Number(props.preselected_appointment.id) : null,
    treatment_id: props.preselected_treatment?.id ? Number(props.preselected_treatment.id) : null,
    invoice_date: props.today || new Date().toISOString().split("T")[0],
    due_date: new Date(Date.now() + 15 * 86400000).toISOString().split("T")[0],
    discount: 0,
    tax: 0,
    notes: "Thank you for choosing Vitality Smile Dental Clinic. Payment is due upon service rendering or as specified above.",
    items: initialItems,
});

// Watch preselected_patient_id in case it arrives late or changes
watch(
    () => props.preselected_patient_id,
    (newVal) => {
        if (newVal && !form.patient_id) {
            form.patient_id = Number(newVal);
        }
    },
    { immediate: true }
);

// Selected patient object
const selectedPatient = computed(() => {
    return props.patients.find((p) => p.id === Number(form.patient_id));
});

// Line items manipulation
const addItem = () => {
    form.items.push({
        description: "",
        quantity: 1,
        unit_price: 0,
        treatment_id: null,
    });
};

const removeItem = (index) => {
    if (form.items.length > 1) {
        form.items.splice(index, 1);
    }
};

const applyTemplate = (svc, index) => {
    form.items[index].description = svc.name;
    const config = lookupItemFee(svc.name);
    form.items[index].unit_price = config.defaultFee || svc.price;
    validateAndClampItemPrice(index, true);
};

// Computations
const subtotal = computed(() => {
    return form.items.reduce((sum, item) => {
        const qty = parseFloat(item.quantity) || 0;
        const config = lookupItemFee(item.description);
        const price = config.isFixed ? config.min : (parseFloat(item.unit_price) || 0);
        return sum + qty * price;
    }, 0);
});

const netAmount = computed(() => {
    const disc = parseFloat(form.discount) || 0;
    const tax = parseFloat(form.tax) || 0;
    const net = subtotal.value - disc + tax;
    return net > 0 ? net : 0;
});

const formatCurrency = (val) => {
    const num = parseFloat(val) || 0;
    return "₱" + num.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
};

onMounted(() => {
    form.items.forEach((item, idx) => {
        if (item.description) {
            onDescriptionChange(idx);
            validateAndClampItemPrice(idx, true);
        }
    });
});

const submit = () => {
    form.transform((data) => ({
        ...data,
        patient_id: data.patient_id ? Number(data.patient_id) : null,
        appointment_id: data.appointment_id ? Number(data.appointment_id) : null,
        treatment_id: data.treatment_id ? Number(data.treatment_id) : null,
        discount: data.discount !== "" && data.discount !== null ? Math.max(0, parseFloat(data.discount) || 0) : 0,
        tax: data.tax !== "" && data.tax !== null ? Math.max(0, parseFloat(data.tax) || 0) : 0,
        items: data.items.map((item) => {
            const config = lookupItemFee(item.description);
            let price = parseFloat(item.unit_price);
            if (isNaN(price)) {
                price = config.defaultFee || config.min;
            } else {
                if (config.isFixed) {
                    price = config.min;
                } else {
                    if (config.min !== null && price < config.min) {
                        price = config.min;
                    } else if (config.max !== null && price > config.max) {
                        price = config.max;
                    }
                }
            }
            return {
                description: (item.description || "").trim(),
                quantity: Math.max(1, parseInt(item.quantity) || 1),
                unit_price: Math.max(0, price),
                treatment_id: item.treatment_id ? Number(item.treatment_id) : null,
            };
        }),
    })).post(route("billing.store"), {
        preserveScroll: true,
    });
};
</script>

<template>
    <Head title="Create Dental Invoice" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <div class="flex items-center gap-2">
                        <Link
                            :href="route('billing.index')"
                            class="text-xs font-semibold text-teal-700 hover:text-teal-900 inline-flex items-center gap-1"
                        >
                            <i class="pi pi-arrow-left text-[10px]"></i> Back to Invoices
                        </Link>
                    </div>
                    <h2 class="text-xl font-bold text-gray-900 sm:text-2xl mt-1">
                        Create Dental Invoice
                    </h2>
                    <p class="mt-1 text-sm text-gray-500">
                        Generate a new billing statement and itemized receipt for clinical dental services.
                    </p>
                </div>

                <div class="flex items-center gap-2 text-xs text-gray-500 bg-teal-50 border border-teal-200 px-3.5 py-2 rounded-xl">
                    <i class="pi pi-receipt text-teal-700 text-sm"></i>
                    <div>
                        <span class="font-semibold text-teal-900">Assigned Number:</span>
                        <span class="font-mono font-bold text-teal-800 ml-1.5">{{ next_invoice_number }}</span>
                    </div>
                </div>
            </div>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-5xl px-4 sm:px-6 lg:px-8">
                <!-- Context alert if generated directly from a treatment or appointment -->
                <div
                    v-if="preselected_treatment || preselected_appointment"
                    class="mb-6 bg-teal-50 border border-teal-200 rounded-xl p-4 flex items-start gap-3"
                >
                    <i class="pi pi-info-circle text-teal-700 text-lg mt-0.5"></i>
                    <div class="text-xs text-teal-900">
                        <p class="font-bold">Linked Clinical Record</p>
                        <p class="mt-0.5 text-teal-800">
                            <span v-if="preselected_treatment">
                                Generating invoice for treatment:
                                <strong class="underline">{{ preselected_treatment.procedure_name }}</strong>
                                (Tooth #{{ preselected_treatment.tooth_number || "All" }})
                            </span>
                            <span v-if="preselected_appointment" class="ml-2">
                                • Appointment Date: {{ preselected_appointment.appointment_date }}
                            </span>
                        </p>
                    </div>
                </div>

                <form @submit.prevent="submit" class="space-y-6">
                    <!-- Validation Error Summary Banner -->
                    <div
                        v-if="form.hasErrors"
                        class="p-4 rounded-xl border border-red-200 bg-red-50 flex items-start gap-3"
                    >
                        <i class="pi pi-exclamation-circle text-red-600 text-lg mt-0.5"></i>
                        <div class="space-y-1">
                            <h4 class="text-xs font-bold text-red-900">
                                Please check the required fields before generating the invoice:
                            </h4>
                            <ul class="list-disc list-inside text-xs text-red-700 space-y-0.5">
                                <li v-for="(errMsg, errField) in form.errors" :key="errField">
                                    {{ errMsg }}
                                </li>
                            </ul>
                        </div>
                    </div>

                    <!-- 1. Patient & Invoice Header Card -->
                    <div class="bg-white rounded-2xl border border-gray-200 shadow-sm p-6 space-y-6">
                        <h3 class="text-base font-bold text-gray-900 border-b border-gray-100 pb-3 flex items-center gap-2">
                            <i class="pi pi-user text-teal-700"></i> Patient & Billing Terms
                        </h3>

                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5">
                            <!-- Patient Selector -->
                            <div class="lg:col-span-1">
                                <label class="block text-xs font-semibold text-gray-700 mb-1">
                                    Patient <span class="text-red-500">*</span>
                                </label>
                                <select
                                    v-model="form.patient_id"
                                    required
                                    class="w-full rounded-lg border-gray-300 shadow-sm focus:border-teal-500 focus:ring-teal-500 text-sm"
                                >
                                    <option value="" disabled>-- Select Patient --</option>
                                    <option v-for="p in patients" :key="p.id" :value="p.id">
                                        {{ p.last_name }}, {{ p.first_name }} ({{ p.patient_id }})
                                    </option>
                                </select>
                                <p v-if="form.errors.patient_id" class="text-xs text-red-600 mt-1">{{ form.errors.patient_id }}</p>

                                <div v-if="selectedPatient" class="mt-2 text-xs text-gray-500 bg-gray-50 p-2.5 rounded-lg border border-gray-100 space-y-1">
                                    <p><strong class="text-gray-700">ID:</strong> {{ selectedPatient.patient_id }}</p>
                                    <p><strong class="text-gray-700">Contact:</strong> {{ selectedPatient.contact_number || "None" }}</p>
                                </div>
                            </div>

                            <!-- Invoice Date -->
                            <div>
                                <label class="block text-xs font-semibold text-gray-700 mb-1">
                                    Invoice Date <span class="text-red-500">*</span>
                                </label>
                                <input
                                    v-model="form.invoice_date"
                                    type="date"
                                    required
                                    class="w-full rounded-lg border-gray-300 shadow-sm focus:border-teal-500 focus:ring-teal-500 text-sm"
                                />
                                <p v-if="form.errors.invoice_date" class="text-xs text-red-600 mt-1">{{ form.errors.invoice_date }}</p>
                            </div>

                            <!-- Due Date -->
                            <div>
                                <label class="block text-xs font-semibold text-gray-700 mb-1">
                                    Payment Due Date
                                </label>
                                <input
                                    v-model="form.due_date"
                                    type="date"
                                    class="w-full rounded-lg border-gray-300 shadow-sm focus:border-teal-500 focus:ring-teal-500 text-sm"
                                />
                                <p v-if="form.errors.due_date" class="text-xs text-red-600 mt-1">{{ form.errors.due_date }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- 2. Line Items Card -->
                    <div class="bg-white rounded-2xl border border-gray-200 shadow-sm p-6 space-y-6">
                        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 border-b border-gray-100 pb-3">
                            <div>
                                <h3 class="text-base font-bold text-gray-900 flex items-center gap-2">
                                    <i class="pi pi-list text-teal-700"></i> Dental Services & Line Items
                                </h3>
                                <p class="text-xs text-gray-500 mt-0.5">Specify procedure items, quantities, and service fees.</p>
                            </div>

                            <button
                                type="button"
                                @click="addItem"
                                class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-teal-50 text-teal-700 hover:bg-teal-100 text-xs font-semibold transition"
                            >
                                <i class="pi pi-plus text-[10px]"></i> Add Another Item
                            </button>
                        </div>

                        <p v-if="form.errors.items" class="text-xs text-red-600 font-semibold">
                            {{ form.errors.items }}
                        </p>

                        <div class="space-y-4">
                            <div
                                v-for="(item, idx) in form.items"
                                :key="idx"
                                class="p-4 rounded-xl border border-gray-200 bg-gray-50/50 space-y-3"
                            >
                                <div class="flex items-center justify-between">
                                    <span class="text-xs font-bold text-teal-800 uppercase tracking-wider">
                                        Line Item #{{ idx + 1 }}
                                    </span>
                                    <button
                                        v-if="form.items.length > 1"
                                        type="button"
                                        @click="removeItem(idx)"
                                        class="text-xs font-semibold text-red-600 hover:text-red-800 inline-flex items-center gap-1"
                                    >
                                        <i class="pi pi-trash text-[10px]"></i> Remove
                                    </button>
                                </div>

                                <div class="grid grid-cols-1 sm:grid-cols-12 gap-3 items-start">
                                    <!-- Service Description & Template Picker -->
                                    <div class="sm:col-span-6 space-y-1.5">
                                        <label class="block text-xs font-semibold text-gray-700">
                                            Description / Service <span class="text-red-500">*</span>
                                        </label>
                                        <input
                                            v-model="item.description"
                                            type="text"
                                            placeholder="e.g. Oral Prophylaxis, Light-cure Composite Filling..."
                                            required
                                            @input="onDescriptionChange(idx)"
                                            @change="onDescriptionChange(idx)"
                                            class="w-full rounded-lg border-gray-300 shadow-sm focus:border-teal-500 focus:ring-teal-500 text-sm"
                                        />
                                        <p v-if="form.errors[`items.${idx}.description`]" class="text-xs text-red-600 mt-1">
                                            {{ form.errors[`items.${idx}.description`] }}
                                        </p>
                                        <!-- Quick Template helper buttons -->
                                        <div class="flex flex-wrap gap-1 pt-1">
                                            <span class="text-[10px] text-gray-400 font-medium">Quick Pick:</span>
                                            <button
                                                v-for="svc in quickServices.slice(0, 4)"
                                                :key="svc.name"
                                                type="button"
                                                @click="applyTemplate(svc, idx)"
                                                class="text-[10px] px-1.5 py-0.5 rounded bg-gray-200 hover:bg-teal-100 hover:text-teal-800 text-gray-600 transition"
                                            >
                                                {{ svc.name.split(" ")[0] }}
                                            </button>
                                        </div>
                                    </div>

                                    <!-- Quantity -->
                                    <div class="sm:col-span-2">
                                        <label class="block text-xs font-semibold text-gray-700 mb-1">
                                            Qty <span class="text-red-500">*</span>
                                        </label>
                                        <input
                                            v-model.number="item.quantity"
                                            type="number"
                                            min="1"
                                            required
                                            class="w-full rounded-lg border-gray-300 shadow-sm focus:border-teal-500 focus:ring-teal-500 text-sm text-center font-medium"
                                        />
                                        <p v-if="form.errors[`items.${idx}.quantity`]" class="text-xs text-red-600 mt-1">
                                            {{ form.errors[`items.${idx}.quantity`] }}
                                        </p>
                                    </div>

                                    <!-- Unit Price -->
                                    <div class="sm:col-span-2">
                                        <label class="block text-xs font-semibold text-gray-700 mb-1">
                                            Unit Price (₱) <span class="text-red-500">*</span>
                                        </label>
                                        <!-- Fixed price: Readonly locked input -->
                                        <input
                                            v-if="lookupItemFee(item.description).isFixed"
                                            :value="lookupItemFee(item.description).min"
                                            type="number"
                                            readonly
                                            tabindex="-1"
                                            class="w-full rounded-lg border-gray-300 bg-gray-100 text-gray-700 text-sm font-bold shadow-sm cursor-not-allowed select-none"
                                            title="Fixed procedure fee aligned with clinic procedure catalog. Cannot be edited."
                                        />
                                        <!-- Variable price: Strictly bounded between min and max -->
                                        <input
                                            v-else
                                            v-model.number="item.unit_price"
                                            type="number"
                                            step="50"
                                            :min="lookupItemFee(item.description).min"
                                            :max="lookupItemFee(item.description).max || undefined"
                                            required
                                            @keydown="onFeeKeydown"
                                            @input="validateAndClampItemPrice(idx, false, $event)"
                                            @blur="validateAndClampItemPrice(idx, true, $event)"
                                            @change="validateAndClampItemPrice(idx, true, $event)"
                                            class="w-full rounded-lg border-gray-300 shadow-sm focus:border-teal-500 focus:ring-teal-500 text-sm font-semibold"
                                        />
                                        <p v-if="form.errors[`items.${idx}.unit_price`]" class="text-xs text-red-600 mt-1">
                                            {{ form.errors[`items.${idx}.unit_price`] }}
                                        </p>
                                    </div>

                                    <!-- Line Total -->
                                    <div class="sm:col-span-2">
                                        <label class="block text-xs font-semibold text-gray-700 mb-1">
                                            Total
                                        </label>
                                        <div class="h-[38px] flex items-center px-3 rounded-lg bg-gray-100 border border-gray-200 text-sm font-bold text-gray-900">
                                            {{ formatCurrency((item.quantity || 0) * (lookupItemFee(item.description).isFixed ? lookupItemFee(item.description).min : (item.unit_price || 0))) }}
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- 3. Totals & Discount Card -->
                        <div class="border-t border-gray-200 pt-5 grid grid-cols-1 sm:grid-cols-2 gap-6">
                            <!-- Notes & Remarks -->
                            <div>
                                <label class="block text-xs font-semibold text-gray-700 mb-1">
                                    Invoice Remarks & Payment Instructions
                                </label>
                                <textarea
                                    v-model="form.notes"
                                    rows="4"
                                    class="w-full rounded-lg border-gray-300 shadow-sm focus:border-teal-500 focus:ring-teal-500 text-xs"
                                    placeholder="Enter bank payment details, warranties, or patient notes..."
                                ></textarea>
                            </div>

                            <!-- Calculations Table -->
                            <div class="bg-gray-50 rounded-xl p-4 border border-gray-200 space-y-3">
                                <div class="flex justify-between text-xs text-gray-600">
                                    <span>Subtotal:</span>
                                    <span class="font-semibold text-gray-900">{{ formatCurrency(subtotal) }}</span>
                                </div>

                                <div class="flex items-center justify-between gap-3 text-xs">
                                    <span class="text-gray-600">Discount (₱):</span>
                                    <input
                                        v-model.number="form.discount"
                                        type="number"
                                        step="0.01"
                                        min="0"
                                        class="w-28 rounded-md border-gray-300 py-1 text-xs text-right font-medium focus:border-teal-500 focus:ring-teal-500"
                                    />
                                </div>

                                <div class="flex items-center justify-between gap-3 text-xs">
                                    <span class="text-gray-600">Tax / VAT (₱):</span>
                                    <input
                                        v-model.number="form.tax"
                                        type="number"
                                        step="0.01"
                                        min="0"
                                        class="w-28 rounded-md border-gray-300 py-1 text-xs text-right font-medium focus:border-teal-500 focus:ring-teal-500"
                                    />
                                </div>

                                <div class="border-t border-gray-200 pt-3 flex justify-between items-center">
                                    <span class="text-sm font-bold text-gray-900">Total Due:</span>
                                    <span class="text-xl font-extrabold text-teal-800">{{ formatCurrency(netAmount) }}</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Action Buttons -->
                    <div class="flex items-center justify-end gap-3 pt-2">
                        <Link
                            :href="route('billing.index')"
                            class="rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-xs font-semibold text-gray-700 shadow-sm hover:bg-gray-50 transition"
                        >
                            Cancel
                        </Link>
                        <button
                            type="submit"
                            :disabled="form.processing"
                            class="rounded-lg bg-teal-700 px-6 py-2.5 text-xs font-semibold text-white shadow-sm hover:bg-teal-800 focus:outline-none focus:ring-2 focus:ring-teal-500 focus:ring-offset-2 transition disabled:opacity-50 inline-flex items-center gap-2"
                        >
                            <i class="pi pi-check text-xs"></i>
                            Generate Official Invoice
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
