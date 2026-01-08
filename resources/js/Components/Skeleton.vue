<script setup>
import { computed } from 'vue';

const props = defineProps({
    variant: {
        type: String,
        default: 'rectangular',
        validator: (value) => ['text', 'circular', 'rectangular', 'card'].includes(value),
    },
    width: {
        type: String,
        default: '100%',
    },
    height: {
        type: String,
        default: '20px',
    },
    rows: {
        type: Number,
        default: 1,
    },
    animated: {
        type: Boolean,
        default: true,
    },
});

const variantClasses = computed(() => {
    const variants = {
        text: 'rounded h-4',
        circular: 'rounded-full',
        rectangular: 'rounded',
        card: 'rounded-lg',
    };
    return variants[props.variant] || variants.rectangular;
});

const animationClass = computed(() => {
    return props.animated ? 'animate-pulse' : '';
});

const sizeStyles = computed(() => {
    if (props.variant === 'text') {
        return {};
    }
    return {
        width: props.width,
        height: props.height,
    };
});
</script>

<template>
    <div v-if="variant === 'text'" class="space-y-2">
        <div
            v-for="row in rows"
            :key="row"
            :class="[
                'bg-gray-200',
                variantClasses,
                animationClass,
                row === rows && rows > 1 ? 'w-4/5' : 'w-full'
            ]"
        ></div>
    </div>

    <div
        v-else
        :class="[
            'bg-gray-200',
            variantClasses,
            animationClass
        ]"
        :style="sizeStyles"
    ></div>
</template>
