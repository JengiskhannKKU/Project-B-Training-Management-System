<script setup>
import { ref } from 'vue';
import axios from 'axios';
import { useToast } from 'vue-toastification';
import { X, Loader2, AlertTriangle } from 'lucide-vue-next';

const toast = useToast();

const props = defineProps({
    show: {
        type: Boolean,
        default: false
    },
    session: {
        type: Object,
        default: null
    }
});

const emit = defineEmits(['close', 'deleted']);

const isDeleting = ref(false);

const handleDelete = async () => {
    if (!props.session) return;

    isDeleting.value = true;

    try {
        await axios.get('/sanctum/csrf-cookie');
        await axios.delete(`/api/sessions/${props.session.id}`);

        toast.success('Session deleted successfully');
        emit('deleted');
        emit('close');
    } catch (error) {
        toast.error(error?.response?.data?.message || 'Failed to delete session');
    } finally {
        isDeleting.value = false;
    }
};

const handleClose = () => {
    if (!isDeleting.value) {
        emit('close');
    }
};
</script>

<template>
    <div
        v-if="show"
        class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50 p-4"
        @click.self="handleClose"
    >
        <div class="w-full max-w-md rounded-lg bg-white shadow-xl">
            <!-- Header -->
            <div class="border-b px-6 py-4">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <div class="flex h-10 w-10 items-center justify-center rounded-full bg-red-100">
                            <AlertTriangle class="h-5 w-5 text-red-600" />
                        </div>
                        <div>
                            <h2 class="text-lg font-bold text-gray-900">Delete Session</h2>
                            <p class="mt-0.5 text-sm text-gray-500">This action cannot be undone</p>
                        </div>
                    </div>
                    <button
                        @click="handleClose"
                        :disabled="isDeleting"
                        class="rounded-lg p-2 hover:bg-gray-100 transition-colors disabled:opacity-50"
                    >
                        <X :size="20" />
                    </button>
                </div>
            </div>

            <!-- Content -->
            <div class="p-6 space-y-4">
                <p class="text-sm text-gray-600">
                    Are you sure you want to delete this session? All enrollments and attendance records will be removed.
                </p>

                <div v-if="session" class="rounded-lg bg-gray-50 p-4 space-y-2">
                    <div class="flex justify-between text-sm">
                        <span class="font-medium text-gray-700">Session:</span>
                        <span class="text-gray-900">{{ session.title }}</span>
                    </div>
                    <div class="flex justify-between text-sm">
                        <span class="font-medium text-gray-700">Date:</span>
                        <span class="text-gray-900">{{ session.start_date }}</span>
                    </div>
                    <div class="flex justify-between text-sm">
                        <span class="font-medium text-gray-700">Location:</span>
                        <span class="text-gray-900">{{ session.location || 'N/A' }}</span>
                    </div>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="border-t px-6 py-4 flex gap-3">
                <button
                    type="button"
                    @click="handleClose"
                    :disabled="isDeleting"
                    class="flex-1 rounded-lg border border-gray-300 px-4 py-2.5 text-sm font-semibold text-gray-700 hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
                >
                    Cancel
                </button>
                <button
                    type="button"
                    @click="handleDelete"
                    :disabled="isDeleting"
                    class="flex-1 rounded-lg bg-red-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-red-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors inline-flex items-center justify-center gap-2"
                >
                    <Loader2 v-if="isDeleting" class="h-4 w-4 animate-spin" />
                    <span>{{ isDeleting ? 'Deleting...' : 'Delete Session' }}</span>
                </button>
            </div>
        </div>
    </div>
</template>
