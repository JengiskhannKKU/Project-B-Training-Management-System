<script setup>
import { ref, computed, watch, onBeforeUnmount } from 'vue';
import { X, AlertTriangle, AlertCircle, Info } from 'lucide-vue-next';

const props = defineProps({
    show: {
        type: Boolean,
        default: true,
    },
    message: {
        type: String,
        required: true,
    },
    title: {
        type: String,
        default: '',
    },
    variant: {
        type: String,
        default: 'error',
        validator: (value) => ['error', 'warning', 'info'].includes(value),
    },
    dismissible: {
        type: Boolean,
        default: true,
    },
    autoDismiss: {
        type: Number,
        default: 0,
    },
    position: {
        type: String,
        default: 'inline',
        validator: (value) => ['inline', 'top'].includes(value),
    },
});

const emit = defineEmits(['dismiss']);

const isVisible = ref(props.show);
let autoDismissTimer = null;

watch(() => props.show, (newVal) => {
    isVisible.value = newVal;
    if (newVal && props.autoDismiss > 0) {
        setupAutoDismiss();
    }
});

const variantConfig = computed(() => {
    const configs = {
        error: {
            containerClass: 'bg-red-50 border-red-200',
            iconClass: 'text-red-500',
            titleClass: 'text-red-900',
            messageClass: 'text-red-800',
            icon: AlertCircle,
        },
        warning: {
            containerClass: 'bg-amber-50 border-amber-200',
            iconClass: 'text-amber-500',
            titleClass: 'text-amber-900',
            messageClass: 'text-amber-800',
            icon: AlertTriangle,
        },
        info: {
            containerClass: 'bg-blue-50 border-blue-200',
            iconClass: 'text-blue-500',
            titleClass: 'text-blue-900',
            messageClass: 'text-blue-800',
            icon: Info,
        },
    };
    return configs[props.variant] || configs.error;
});

const positionClasses = computed(() => {
    if (props.position === 'top') {
        return 'fixed top-0 left-0 right-0 z-40 shadow-lg';
    }
    return 'relative';
});

const handleDismiss = () => {
    isVisible.value = false;
    emit('dismiss');
    clearAutoDismissTimer();
};

const setupAutoDismiss = () => {
    clearAutoDismissTimer();
    autoDismissTimer = setTimeout(() => {
        handleDismiss();
    }, props.autoDismiss);
};

const clearAutoDismissTimer = () => {
    if (autoDismissTimer) {
        clearTimeout(autoDismissTimer);
        autoDismissTimer = null;
    }
};

if (props.show && props.autoDismiss > 0) {
    setupAutoDismiss();
}

onBeforeUnmount(() => {
    clearAutoDismissTimer();
});
</script>

<template>
    <Transition
        enter-active-class="transition ease-out duration-200"
        enter-from-class="opacity-0 -translate-y-2"
        enter-to-class="opacity-100 translate-y-0"
        leave-active-class="transition ease-in duration-150"
        leave-from-class="opacity-100 translate-y-0"
        leave-to-class="opacity-0 -translate-y-2"
    >
        <div
            v-if="isVisible"
            :class="[positionClasses, variantConfig.containerClass, 'rounded-lg border p-4']"
            role="alert"
        >
            <div class="flex items-start gap-3">
                <component
                    :is="variantConfig.icon"
                    :size="20"
                    :class="[variantConfig.iconClass, 'flex-shrink-0 mt-0.5']"
                />

                <div class="flex-1 min-w-0">
                    <h3
                        v-if="title"
                        :class="[variantConfig.titleClass, 'text-sm font-semibold mb-1']"
                    >
                        {{ title }}
                    </h3>
                    <p :class="[variantConfig.messageClass, 'text-sm']">
                        {{ message }}
                    </p>
                </div>

                <button
                    v-if="dismissible"
                    type="button"
                    @click="handleDismiss"
                    class="flex-shrink-0 rounded-lg p-1 hover:bg-black/5 transition-colors"
                    aria-label="Dismiss"
                >
                    <X :size="16" :class="variantConfig.iconClass" />
                </button>
            </div>
        </div>
    </Transition>
</template>
