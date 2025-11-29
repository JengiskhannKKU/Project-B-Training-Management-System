<script setup>
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';

const props = defineProps({
    href: {
        type: String,
        required: true,
    },
    active: {
        type: Boolean,
        default: false,
    },
});

const classes = computed(() => {
    const base =
        'block w-full px-4 py-2 text-start text-sm leading-5 transition duration-150 ease-in-out focus:outline-none';

    return props.active
        ? `${base} bg-indigo-600 text-white hover:bg-indigo-600 focus:bg-indigo-600`
        : `${base} text-gray-700 hover:bg-gray-100 focus:bg-gray-100`;
});
</script>

<template>
    <Link
        :href="href"
        v-bind="$attrs"
        :class="classes"
    >
        <slot />
    </Link>
</template>
