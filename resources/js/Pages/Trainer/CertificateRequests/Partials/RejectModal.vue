<script setup>
import { ref, watch } from "vue";
import { X } from "lucide-vue-next";

const props = defineProps({
    show: {
        type: Boolean,
        default: false,
    },
});

const emit = defineEmits(["close", "reject"]);

const note = ref("");
const isSubmitting = ref(false);
const error = ref("");

watch(
    () => props.show,
    (newValue) => {
        if (newValue) {
            note.value = "";
            error.value = "";
            isSubmitting.value = false;
        }
    }
);

const handleSubmit = async () => {
    if (!note.value.trim()) {
        error.value = "Rejection note is required";
        return;
    }

    if (note.value.trim().length < 10) {
        error.value = "Rejection note must be at least 10 characters";
        return;
    }

    isSubmitting.value = true;
    error.value = "";

    try {
        await emit("reject", note.value);
        // Success - parent will close modal
    } catch (err) {
        error.value = err?.response?.data?.message || "Failed to reject request";
        isSubmitting.value = false;
    }
};

const handleClose = () => {
    if (!isSubmitting.value) {
        emit("close");
    }
};
</script>

<template>
    <Teleport to="body">
        <Transition
            enter-active-class="transition ease-out duration-200"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="transition ease-in duration-150"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div
                v-if="show"
                class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50 p-4"
                @click.self="handleClose"
            >
                <Transition
                    enter-active-class="transition ease-out duration-200"
                    enter-from-class="opacity-0 scale-95"
                    enter-to-class="opacity-100 scale-100"
                    leave-active-class="transition ease-in duration-150"
                    leave-from-class="opacity-100 scale-100"
                    leave-to-class="opacity-0 scale-95"
                >
                    <div
                        v-if="show"
                        class="relative w-full max-w-md rounded-lg bg-white shadow-xl"
                    >
                        <!-- Header -->
                        <div
                            class="flex items-center justify-between border-b border-gray-200 p-6"
                        >
                            <h3 class="text-lg font-semibold text-gray-900">
                                Reject Certificate Request
                            </h3>
                            <button
                                @click="handleClose"
                                :disabled="isSubmitting"
                                class="text-gray-400 hover:text-gray-600 disabled:opacity-50"
                            >
                                <X class="h-5 w-5" />
                            </button>
                        </div>

                        <!-- Body -->
                        <form @submit.prevent="handleSubmit" class="p-6">
                            <div class="space-y-4">
                                <div>
                                    <label
                                        for="rejection-note"
                                        class="block text-sm font-medium text-gray-700 mb-2"
                                    >
                                        Reason for Rejection
                                        <span class="text-red-500">*</span>
                                    </label>
                                    <textarea
                                        id="rejection-note"
                                        v-model="note"
                                        rows="4"
                                        placeholder="Please provide a reason for rejecting this certificate request (minimum 10 characters)..."
                                        class="w-full rounded-lg border-gray-300 shadow-sm focus:border-[#2f837d] focus:ring-[#2f837d]"
                                        :class="{ 'border-red-300': error }"
                                        :disabled="isSubmitting"
                                        required
                                    ></textarea>
                                    <p class="mt-1 text-xs text-gray-500">
                                        Minimum 10 characters required
                                    </p>
                                    <p v-if="error" class="mt-1 text-sm text-red-600">
                                        {{ error }}
                                    </p>
                                </div>
                            </div>

                            <!-- Footer -->
                            <div class="mt-6 flex justify-end gap-3">
                                <button
                                    type="button"
                                    @click="handleClose"
                                    :disabled="isSubmitting"
                                    class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50 disabled:opacity-50"
                                >
                                    Cancel
                                </button>
                                <button
                                    type="submit"
                                    :disabled="isSubmitting || !note.trim()"
                                    class="rounded-lg bg-red-600 px-4 py-2 text-sm font-semibold text-white hover:bg-red-700 disabled:opacity-50"
                                >
                                    {{ isSubmitting ? "Rejecting..." : "Reject Request" }}
                                </button>
                            </div>
                        </form>
                    </div>
                </Transition>
            </div>
        </Transition>
    </Teleport>
</template>
