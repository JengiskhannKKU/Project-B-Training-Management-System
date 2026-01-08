<script setup>
import { computed, watch, onUnmounted } from 'vue';
import { X } from 'lucide-vue-next';

const props = defineProps({
    show: {
        type: Boolean,
        default: false,
    },
    message: {
        type: String,
        required: true,
    },
    variant: {
        type: String,
        default: 'default',
        validator: (value) => ['default', 'success', 'error', 'warning', 'info'].includes(value),
    },
    action: {
        type: Object,
        default: null,
        validator: (value) => {
            if (!value) return true;
            return value.label && typeof value.onClick === 'function';
        },
    },
    duration: {
        type: Number,
        default: 3000,
    },
    position: {
        type: String,
        default: 'bottom-center',
        validator: (value) => ['bottom-left', 'bottom-center', 'bottom-right'].includes(value),
    },
});

const emit = defineEmits(['close']);

let timeout = null;

const variantClasses = computed(() => {
    const variants = {
        default: 'bg-gray-900 text-white',
        success: 'bg-[#2f837d] text-white',
        error: 'bg-red-600 text-white',
        warning: 'bg-amber-500 text-white',
        info: 'bg-blue-600 text-white',
    };
    return variants[props.variant] || variants.default;
});

const positionClasses = computed(() => {
    const positions = {
        'bottom-left': 'left-4',
        'bottom-center': 'left-1/2 -translate-x-1/2',
        'bottom-right': 'right-4',
    };
    return positions[props.position] || positions['bottom-center'];
});

const actionButtonClasses = computed(() => {
    const variants = {
        default: 'text-white hover:text-gray-200',
        success: 'text-[#DAFFED] hover:text-white',
        error: 'text-red-100 hover:text-white',
        warning: 'text-amber-100 hover:text-white',
        info: 'text-blue-100 hover:text-white',
    };
    return variants[props.variant] || variants.default;
});

const handleClose = () => {
    emit('close');
    clearTimeout(timeout);
};

const handleAction = () => {
    if (props.action && props.action.onClick) {
        props.action.onClick();
    }
    handleClose();
};

watch(() => props.show, (newValue) => {
    if (newValue && props.duration > 0) {
        clearTimeout(timeout);
        timeout = setTimeout(() => {
            handleClose();
        }, props.duration);
    }
});

onUnmounted(() => {
    clearTimeout(timeout);
});
</script>

<template>
    <Teleport to="body">
        <Transition
            enter-active-class="transition-all duration-200 ease-out"
            enter-from-class="opacity-0 translate-y-2"
            enter-to-class="opacity-100 translate-y-0"
            leave-active-class="transition-all duration-150 ease-in"
            leave-from-class="opacity-100 translate-y-0"
            leave-to-class="opacity-0 translate-y-2"
        >
            <div
                v-if="show"
                :class="[
                    'fixed bottom-4 z-50 max-w-md mx-4 rounded-lg shadow-lg px-4 py-3',
                    'flex items-center gap-3 font-medium text-sm',
                    variantClasses,
                    positionClasses
                ]"
                role="alert"
            >
                <!-- Message -->
                <span class="flex-1">{{ message }}</span>

                <!-- Action Button -->
                <button
                    v-if="action"
                    @click="handleAction"
                    :class="[
                        'font-semibold uppercase text-xs tracking-wide transition-colors duration-200',
                        actionButtonClasses
                    ]"
                >
                    {{ action.label }}
                </button>

                <!-- Close Button -->
                <button
                    @click="handleClose"
                    class="ml-auto p-1 rounded hover:bg-white/10 transition-colors duration-200"
                    aria-label="Close"
                >
                    <X :size="16" />
                </button>
            </div>
        </Transition>
    </Teleport>
</template>
