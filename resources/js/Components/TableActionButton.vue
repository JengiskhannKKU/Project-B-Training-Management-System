<template>
    <Link
        v-if="href"
        :href="href"
        :title="title"
        :class="[
            'text-gray-600 transition-colors inline-flex items-center gap-1',
            hoverColorClass
        ]"
    >
        <component :is="icon" class="h-4 w-4" />
    </Link>
    <button
        v-else
        type="button"
        :title="title"
        :class="[
            'text-gray-600 transition-colors inline-flex items-center gap-1',
            hoverColorClass
        ]"
        @click="handleClick"
    >
        <component :is="icon" class="h-4 w-4" />
    </button>
</template>

<script setup>
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';

const props = defineProps({
    icon: {
        type: [Object, Function],
        required: true,
    },
    title: {
        type: String,
        default: '',
    },
    variant: {
        type: String,
        default: 'default', // 'default', 'edit', 'delete'
        validator: (value) => ['default', 'edit', 'delete'].includes(value),
    },
    href: {
        type: String,
        default: null,
    },
});

const emit = defineEmits(['click']);

const handleClick = (event) => {
    console.log('TableActionButton (button) clicked!');
    console.log('Emitting click event for variant:', props.variant);
    emit('click', event);
};

const hoverColorClass = computed(() => {
    switch (props.variant) {
        case 'edit':
            return 'hover:text-[#257067]';
        case 'delete':
            return 'hover:text-red-800';
        default:
            return 'hover:text-gray-900';
    }
});
</script>
