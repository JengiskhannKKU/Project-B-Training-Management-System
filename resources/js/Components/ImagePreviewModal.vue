<script setup>
import { X, ZoomIn } from 'lucide-vue-next';

const props = defineProps({
    show: {
        type: Boolean,
        required: true,
    },
    imageUrl: {
        type: String,
        required: true,
    },
    altText: {
        type: String,
        default: 'Preview Image',
    },
});

const emit = defineEmits(['close']);

const handleClose = () => {
    emit('close');
};

const handleBackdropClick = () => {
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
                class="fixed inset-0 z-[60] flex items-center justify-center bg-black/80 px-4"
                @click.self="handleBackdropClick"
            >
                <!-- Close button -->
                <button
                    type="button"
                    @click="handleClose"
                    class="absolute right-4 top-4 z-10 flex h-10 w-10 items-center justify-center rounded-full bg-white/10 text-white backdrop-blur-sm hover:bg-white/20"
                >
                    <X class="h-6 w-6" />
                </button>

                <!-- Image container -->
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
                        class="relative max-h-[90vh] max-w-[90vw] overflow-hidden rounded-2xl bg-white shadow-2xl"
                        @click.stop
                    >
                        <img
                            :src="imageUrl"
                            :alt="altText"
                            class="max-h-[90vh] max-w-[90vw] object-contain"
                        />
                    </div>
                </Transition>

                <!-- Hint text -->
                <div class="absolute bottom-6 text-center text-sm text-white/70">
                    คลิกที่พื้นหลังหรือปุ่ม X เพื่อปิด
                </div>
            </div>
        </Transition>
    </Teleport>
</template>
