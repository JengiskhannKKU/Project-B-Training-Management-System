<template>
  <span :class="classes">{{ status }}</span>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
    status: { type: String, required: true },
    size: { type: String, default: 'md' },
});

const classes = computed(() => {
    const statusColors = {
        pending: 'bg-yellow-100 text-yellow-800',
        approved: 'bg-green-100 text-green-800',
        rejected: 'bg-red-100 text-red-800',
        active: 'bg-blue-100 text-blue-800',
        inactive: 'bg-gray-100 text-gray-800',
        completed: 'bg-green-100 text-green-800',
        upcoming: 'bg-purple-100 text-purple-800',
        open: 'bg-green-100 text-green-800',
        closed: 'bg-gray-100 text-gray-800',
        cancelled: 'bg-red-100 text-red-800',
    };

    const sizes = {
        sm: 'px-2 py-0.5 text-xs',
        md: 'px-2 py-1 text-xs',
    };

    return [
        'inline-flex rounded-full font-semibold capitalize',
        statusColors[props.status] || 'bg-gray-100 text-gray-800',
        sizes[props.size],
    ].join(' ');
});
</script>
