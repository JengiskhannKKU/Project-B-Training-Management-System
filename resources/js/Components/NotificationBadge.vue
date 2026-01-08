<script setup>
import { computed } from 'vue';

const props = defineProps({
    count: {
        type: Number,
        default: 0,
    },
    max: {
        type: Number,
        default: 99,
    },
    dot: {
        type: Boolean,
        default: false,
    },
    color: {
        type: String,
        default: 'error',
        validator: (value) => ['primary', 'error', 'warning', 'success'].includes(value),
    },
    size: {
        type: String,
        default: 'md',
        validator: (value) => ['sm', 'md', 'lg'].includes(value),
    },
    position: {
        type: String,
        default: 'top-right',
        validator: (value) => ['top-right', 'top-left', 'bottom-right', 'bottom-left'].includes(value),
    },
    ping: {
        type: Boolean,
        default: false,
    },
});

const displayCount = computed(() => {
    if (props.dot) return '';
    if (props.count > props.max) return `${props.max}+`;
    return props.count;
});

const shouldShow = computed(() => {
    return props.dot || props.count > 0;
});

const colorClasses = computed(() => {
    const colors = {
        primary: 'bg-[#2f837d]',
        error: 'bg-red-500',
        warning: 'bg-amber-500',
        success: 'bg-green-500',
    };
    return colors[props.color] || colors.error;
});

const sizeClasses = computed(() => {
    if (props.dot) {
        const dotSizes = {
            sm: 'h-2 w-2',
            md: 'h-2.5 w-2.5',
            lg: 'h-3 w-3',
        };
        return dotSizes[props.size] || dotSizes.md;
    }

    const sizes = {
        sm: 'h-4 w-4 text-[10px]',
        md: 'h-5 w-5 text-xs',
        lg: 'h-6 w-6 text-sm',
    };
    return sizes[props.size] || sizes.md;
});

const positionClasses = computed(() => {
    const positions = {
        'top-right': '-top-1 -right-1',
        'top-left': '-top-1 -left-1',
        'bottom-right': '-bottom-1 -right-1',
        'bottom-left': '-bottom-1 -left-1',
    };
    return positions[props.position] || positions['top-right'];
});
</script>

<template>
    <div class="relative inline-flex">
        <slot />

        <span
            v-if="shouldShow"
            :class="[
                'absolute rounded-full border-2 border-white flex items-center justify-center font-bold text-white',
                colorClasses,
                sizeClasses,
                positionClasses
            ]"
        >
            <span v-if="!dot" class="leading-none">{{ displayCount }}</span>
        </span>

        <!-- Ping animation (optional) -->
        <span
            v-if="ping && shouldShow"
            :class="[
                'absolute rounded-full animate-ping opacity-75',
                colorClasses,
                sizeClasses,
                positionClasses
            ]"
        ></span>
    </div>
</template>
