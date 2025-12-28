<script setup>
import { X } from 'lucide-vue-next';

const props = defineProps({
    show: {
        type: Boolean,
        required: true,
    },
    title: {
        type: String,
        default: 'ยืนยันการดำเนินการ',
    },
    message: {
        type: String,
        required: true,
    },
    confirmText: {
        type: String,
        default: 'ยืนยัน',
    },
    cancelText: {
        type: String,
        default: 'ยกเลิก',
    },
    confirmButtonClass: {
        type: String,
        default: 'bg-[#2f837d] hover:bg-[#266a66]',
    },
});

const emit = defineEmits(['confirm', 'cancel', 'close']);

const handleConfirm = () => {
    emit('confirm');
    emit('close');
};

const handleCancel = () => {
    emit('cancel');
    emit('close');
};

const handleBackdropClick = () => {
    emit('cancel');
    emit('close');
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
                class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 px-4"
                @click.self="handleBackdropClick"
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
                        class="w-full max-w-md rounded-2xl border border-gray-200 bg-white p-6 shadow-2xl"
                        @click.stop
                    >
                        <div class="relative mb-4">
                            <h3 class="text-center text-xl font-semibold text-gray-900">
                                {{ title }}
                            </h3>
                            <button
                                type="button"
                                @click="handleCancel"
                                class="absolute right-0 top-0 flex h-7 w-7 items-center justify-center rounded-full text-gray-400 hover:bg-gray-100 hover:text-gray-600"
                            >
                                <X class="h-4 w-4" />
                            </button>
                        </div>

                        <div class="mb-6 text-center text-sm text-gray-600">
                            {{ message }}
                        </div>

                        <div class="flex gap-3">
                            <button
                                type="button"
                                @click="handleCancel"
                                class="flex-1 rounded-xl border border-gray-200 px-4 py-2.5 text-sm font-semibold text-gray-700 hover:bg-gray-50"
                            >
                                {{ cancelText }}
                            </button>
                            <button
                                type="button"
                                @click="handleConfirm"
                                :class="[
                                    'flex-1 rounded-xl px-4 py-2.5 text-sm font-semibold text-white shadow-sm',
                                    confirmButtonClass
                                ]"
                            >
                                {{ confirmText }}
                            </button>
                        </div>
                    </div>
                </Transition>
            </div>
        </Transition>
    </Teleport>
</template>
