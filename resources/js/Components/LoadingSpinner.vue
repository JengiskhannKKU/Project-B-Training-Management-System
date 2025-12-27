<script setup>
import { computed } from 'vue';
import { Loader2 } from 'lucide-vue-next';

const props = defineProps({
    show: {
        type: Boolean,
        default: true,
    },
    size: {
        type: String,
        default: 'md',
        validator: (value) => ['xs', 'sm', 'md', 'lg', 'xl'].includes(value),
    },
    variant: {
        type: String,
        default: 'spinner',
        validator: (value) => ['spinner', 'icon'].includes(value),
    },
    color: {
        type: String,
        default: 'primary',
    },
    text: {
        type: String,
        default: '',
    },
    overlay: {
        type: Boolean,
        default: false,
    },
    inline: {
        type: Boolean,
        default: false,
    },
});

const sizeClasses = computed(() => {
    const sizes = {
        xs: 'h-3 w-3 border',
        sm: 'h-4 w-4 border-2',
        md: 'h-6 w-6 border-2',
        lg: 'h-8 w-8 border-2',
        xl: 'h-12 w-12 border-[3px]',
    };
    return sizes[props.size] || sizes.md;
});

const colorClasses = computed(() => {
    const colors = {
        primary: 'border-[#2f837d] border-t-transparent',
        white: 'border-white border-t-transparent',
        gray: 'border-gray-600 border-t-transparent',
    };

    return colors[props.color] || colors.primary;
});

const iconSizeClasses = computed(() => {
    const sizes = {
        xs: 12,
        sm: 16,
        md: 24,
        lg: 32,
        xl: 48,
    };
    return sizes[props.size] || sizes.md;
});

const iconColorClasses = computed(() => {
    if (props.color === 'white') return 'text-white';
    if (props.color === 'gray') return 'text-gray-600';
    return 'text-[#2f837d]';
});

const textSizeClasses = computed(() => {
    const sizes = {
        xs: 'text-xs',
        sm: 'text-xs',
        md: 'text-sm',
        lg: 'text-base',
        xl: 'text-lg',
    };
    return sizes[props.size] || sizes.md;
});

const containerClasses = computed(() => {
    if (props.overlay) {
        return 'fixed inset-0 z-50 flex items-center justify-center bg-black/20 backdrop-blur-sm';
    }
    if (props.inline) {
        return 'inline-flex items-center gap-2';
    }
    return 'flex flex-col items-center justify-center gap-3';
});
</script>

<template>
    <div v-if="show" :class="containerClasses">
        <span
            v-if="variant === 'spinner'"
            :class="[sizeClasses, colorClasses, 'animate-spin rounded-full']"
            role="status"
            aria-label="Loading"
        ></span>

        <Loader2
            v-else
            :size="iconSizeClasses"
            :class="['animate-spin', iconColorClasses]"
        />

        <span
            v-if="text"
            :class="[
                textSizeClasses,
                'font-medium',
                color === 'white' ? 'text-white' : 'text-gray-700'
            ]"
        >
            {{ text }}
        </span>
    </div>
</template>
