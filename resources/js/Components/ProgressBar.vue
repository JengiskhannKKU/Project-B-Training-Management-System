<script setup>
import { computed } from 'vue';

const props = defineProps({
    variant: {
        type: String,
        default: 'linear',
        validator: (value) => ['linear', 'circular'].includes(value),
    },
    value: {
        type: Number,
        default: 0,
    },
    max: {
        type: Number,
        default: 100,
    },
    color: {
        type: String,
        default: 'primary',
        validator: (value) => ['primary', 'success', 'warning', 'error'].includes(value),
    },
    size: {
        type: String,
        default: 'md',
        validator: (value) => ['sm', 'md', 'lg'].includes(value),
    },
    showLabel: {
        type: Boolean,
        default: false,
    },
    indeterminate: {
        type: Boolean,
        default: false,
    },
});

const percentage = computed(() => {
    if (props.indeterminate) return 0;
    return Math.min(Math.max((props.value / props.max) * 100, 0), 100);
});

const colorClasses = computed(() => {
    const colors = {
        primary: 'bg-gradient-to-r from-[#3D9792] to-[#2d7773]',
        success: 'bg-green-500',
        warning: 'bg-amber-500',
        error: 'bg-red-500',
    };
    return colors[props.color] || colors.primary;
});

const sizeClasses = computed(() => {
    if (props.variant === 'circular') {
        const sizes = {
            sm: { size: 40, stroke: 3 },
            md: { size: 60, stroke: 4 },
            lg: { size: 80, stroke: 5 },
        };
        return sizes[props.size] || sizes.md;
    }

    const sizes = {
        sm: 'h-1',
        md: 'h-2',
        lg: 'h-3',
    };
    return sizes[props.size] || sizes.md;
});

const circularProgress = computed(() => {
    const { size, stroke } = sizeClasses.value;
    const radius = (size - stroke) / 2;
    const circumference = radius * 2 * Math.PI;
    const offset = circumference - (percentage.value / 100) * circumference;

    return {
        size,
        stroke,
        radius,
        circumference,
        offset,
        center: size / 2,
    };
});

const strokeColor = computed(() => {
    const colors = {
        primary: '#2f837d',
        success: '#10b981',
        warning: '#f59e0b',
        error: '#ef4444',
    };
    return colors[props.color] || colors.primary;
});
</script>

<template>
    <!-- Linear Progress Bar -->
    <div v-if="variant === 'linear'" class="w-full">
        <div v-if="showLabel" class="flex justify-between items-center mb-1">
            <span class="text-sm font-medium text-gray-700">
                {{ Math.round(percentage) }}%
            </span>
        </div>

        <div
            :class="[
                'w-full bg-gray-200 rounded-full overflow-hidden',
                sizeClasses
            ]"
        >
            <div
                v-if="indeterminate"
                :class="[
                    'h-full rounded-full animate-pulse',
                    colorClasses
                ]"
                style="width: 100%"
            ></div>
            <div
                v-else
                :class="[
                    'h-full rounded-full transition-all duration-300 ease-in-out',
                    colorClasses
                ]"
                :style="{ width: percentage + '%' }"
            ></div>
        </div>
    </div>

    <!-- Circular Progress Bar -->
    <div v-else-if="variant === 'circular'" class="inline-flex items-center justify-center">
        <svg
            :width="circularProgress.size"
            :height="circularProgress.size"
            class="transform -rotate-90"
        >
            <!-- Background circle -->
            <circle
                :cx="circularProgress.center"
                :cy="circularProgress.center"
                :r="circularProgress.radius"
                stroke="#e5e7eb"
                :stroke-width="circularProgress.stroke"
                fill="none"
            />
            <!-- Progress circle -->
            <circle
                v-if="!indeterminate"
                :cx="circularProgress.center"
                :cy="circularProgress.center"
                :r="circularProgress.radius"
                :stroke="strokeColor"
                :stroke-width="circularProgress.stroke"
                fill="none"
                :stroke-dasharray="circularProgress.circumference"
                :stroke-dashoffset="circularProgress.offset"
                stroke-linecap="round"
                class="transition-all duration-300 ease-in-out"
            />
            <!-- Indeterminate animation -->
            <circle
                v-else
                :cx="circularProgress.center"
                :cy="circularProgress.center"
                :r="circularProgress.radius"
                :stroke="strokeColor"
                :stroke-width="circularProgress.stroke"
                fill="none"
                :stroke-dasharray="`${circularProgress.circumference * 0.25} ${circularProgress.circumference * 0.75}`"
                stroke-linecap="round"
                class="animate-spin origin-center"
            />
        </svg>
        <span
            v-if="showLabel && !indeterminate"
            class="absolute text-sm font-semibold text-gray-700"
        >
            {{ Math.round(percentage) }}%
        </span>
    </div>
</template>
