<script setup>
import { ref, watch } from 'vue';

const props = defineProps({
    show: {
        type: Boolean,
        default: false
    },
    action: {
        type: String,
        required: true,
        validator: (value) => ['approve', 'reject', 'pending'].includes(value)
    }
});

const emit = defineEmits(['close', 'confirm']);

const note = ref('');

watch(() => props.show, (newVal) => {
    if (newVal) {
        note.value = '';
    }
});

const handleClose = () => {
    note.value = '';
    emit('close');
};

const handleConfirm = () => {
    emit('confirm', note.value);
    note.value = '';
};

const getTitle = () => {
    switch (props.action) {
        case 'approve':
            return 'Approve request?';
        case 'reject':
            return 'Reject request?';
        case 'pending':
            return 'Set to pending?';
        default:
            return 'Confirm action?';
    }
};

const getDescription = () => {
    switch (props.action) {
        case 'approve':
            return 'This will create/update the program from this request.';
        case 'reject':
            return 'This will mark this request as rejected.';
        case 'pending':
            return 'This will mark this request as pending.';
        default:
            return 'This action cannot be undone.';
    }
};
</script>

<template>
    <Teleport to="body">
        <div
            v-if="show"
            class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4"
            @click.self="handleClose"
        >
            <div class="w-full max-w-md rounded-xl bg-white p-6 shadow-2xl">
                <h3 class="text-lg font-semibold text-gray-900 mb-2">
                    {{ getTitle() }}
                </h3>
                <p class="text-sm text-gray-600 mb-4">
                    {{ getDescription() }}
                </p>

                <!-- Admin Note Input -->
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        Admin note (optional)
                    </label>
                    <textarea
                        v-model="note"
                        rows="3"
                        placeholder="Add a note for the trainer..."
                        class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-[#2f837d] focus:border-transparent resize-none"
                    ></textarea>
                    <p class="mt-1 text-xs text-gray-500">
                        This note will be visible to the trainer.
                    </p>
                </div>

                <div class="flex items-center gap-3">
                    <button
                        @click="handleClose"
                        class="rounded-lg border border-gray-200 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50"
                    >
                        Cancel
                    </button>
                    <button
                        @click="handleConfirm"
                        class="rounded-lg bg-[#2f837d] px-4 py-2 text-sm font-medium text-white hover:bg-[#266a66]"
                    >
                        Confirm
                    </button>
                </div>
            </div>
        </div>
    </Teleport>
</template>
