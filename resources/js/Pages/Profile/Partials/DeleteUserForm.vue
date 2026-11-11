<script setup>
import Modal from "@/Components/Modal.vue";
import { useForm } from "@inertiajs/vue3";
import { nextTick, ref } from "vue";

const confirmingUserDeletion = ref(false);
const passwordInput = ref(null);

const form = useForm({
    password: "",
});

const confirmUserDeletion = () => {
    confirmingUserDeletion.value = true;
    nextTick(() => passwordInput.value?.focus());
};

const deleteUser = () => {
    form.delete(route("profile.destroy"), {
        preserveScroll: true,
        onSuccess: () => closeModal(),
        onError: () => passwordInput.value?.focus(),
        onFinish: () => form.reset(),
    });
};

const closeModal = () => {
    confirmingUserDeletion.value = false;
    form.clearErrors();
    form.reset();
};
</script>

<template>
    <section>
        <div class="rounded-2xl border border-red-200 bg-red-50/50 p-6">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h3 class="text-sm font-bold text-red-900 flex items-center gap-2">
                        <i class="pi pi-exclamation-triangle text-red-600"></i>
                        Deactivate or Permanently Delete Account
                    </h3>
                    <p class="mt-1 text-xs text-red-700 max-w-xl">
                        Once your account is deleted, your portal credentials and access to billing statements will be removed. Dental records may be retained as mandated by health regulations.
                    </p>
                </div>
                <button
                    type="button"
                    @click="confirmUserDeletion"
                    class="shrink-0 inline-flex items-center gap-1.5 rounded-xl border border-red-300 bg-white px-4 py-2.5 text-xs font-bold text-red-700 shadow-xs hover:bg-red-100/50 transition"
                >
                    <i class="pi pi-trash text-xs"></i>
                    Delete Account
                </button>
            </div>
        </div>

        <Modal :show="confirmingUserDeletion" @close="closeModal">
            <div class="p-6">
                <div class="flex items-center gap-3 border-b border-gray-100 pb-4">
                    <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-red-100 text-red-700">
                        <i class="pi pi-exclamation-triangle text-lg"></i>
                    </div>
                    <div>
                        <h3 class="text-base font-bold text-gray-900">
                            Confirm Account Deletion
                        </h3>
                        <p class="text-xs text-gray-500">
                            This action is permanent and cannot be undone.
                        </p>
                    </div>
                </div>

                <p class="mt-4 text-xs text-gray-600 leading-relaxed">
                    Please confirm your password below to finalize the permanent deletion of your Vitality Smile portal account.
                </p>

                <div class="mt-4">
                    <label for="delete_password" class="block text-xs font-semibold text-gray-700 mb-1">
                        Confirm Current Password <span class="text-red-500">*</span>
                    </label>
                    <input
                        id="delete_password"
                        ref="passwordInput"
                        v-model="form.password"
                        type="password"
                        placeholder="••••••••"
                        class="w-full rounded-xl border-gray-300 text-sm focus:border-red-500 focus:ring-red-500"
                        @keyup.enter="deleteUser"
                    />
                    <p v-if="form.errors.password" class="mt-1 text-xs text-red-600">
                        {{ form.errors.password }}
                    </p>
                </div>

                <div class="mt-6 flex justify-end gap-2 border-t border-gray-100 pt-4">
                    <button
                        type="button"
                        @click="closeModal"
                        class="rounded-xl border border-gray-300 bg-white px-4 py-2 text-xs font-semibold text-gray-700 hover:bg-gray-50"
                    >
                        Cancel
                    </button>

                    <button
                        type="button"
                        :disabled="form.processing"
                        @click="deleteUser"
                        class="inline-flex items-center gap-1.5 rounded-xl bg-red-600 px-4 py-2 text-xs font-bold text-white hover:bg-red-700 disabled:opacity-50"
                    >
                        <i v-if="form.processing" class="pi pi-spin pi-spinner text-xs"></i>
                        <i v-else class="pi pi-trash text-xs"></i>
                        Permanently Delete
                    </button>
                </div>
            </div>
        </Modal>
    </section>
</template>
