<template>
  <button :class="classes" :disabled="disabled">
    <slot />
  </button>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
    variant: { type: String, default: 'primary' },
    size: { type: String, default: 'md' },
    rounded: { type: String, default: 'normal' },
    disabled: { type: Boolean, default: false },
});

const classes = computed(() => {
    const base = 'inline-flex items-center justify-center font-semibold transition-colors gap-2';

    const variants = {
        primary: 'bg-[#2f837d] text-white hover:bg-[#266a66]',
        secondary: 'bg-white text-gray-700 border border-gray-300 hover:bg-gray-50',
        danger: 'bg-red-600 text-white hover:bg-red-700',
    };

    const sizes = {
        sm: 'px-3 py-1.5 text-sm',
        md: 'px-4 py-2 text-sm',
        lg: 'px-6 py-3 text-base',
    };

    const roundedStyles = {
        normal: 'rounded-lg',
        full: 'rounded-full',
    };

    const disabledClass = props.disabled ? 'opacity-50 cursor-not-allowed' : '';

    return [
        base,
        variants[props.variant],
        sizes[props.size],
        roundedStyles[props.rounded],
        disabledClass,
    ].join(' ');
});
</script>
