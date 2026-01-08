<script setup>
import { ref, computed, watch } from "vue";
import { Star } from "lucide-vue-next";

const props = defineProps({
    modelValue: {
        type: Number,
        default: 0,
    },
    readonly: {
        type: Boolean,
        default: false,
    },
    size: {
        type: String,
        default: "md", // sm, md, lg
    },
});

const emit = defineEmits(["update:modelValue"]);

const hoverRating = ref(0);
const rating = computed({
    get: () => props.modelValue,
    set: (value) => emit("update:modelValue", value),
});

const sizeClasses = computed(() => {
    switch (props.size) {
        case "sm":
            return "w-4 h-4";
        case "lg":
            return "w-8 h-8";
        default:
            return "w-6 h-6";
    }
});

const gapClass = computed(() => {
    switch (props.size) {
        case "sm":
            return "gap-0.5";
        case "lg":
            return "gap-2";
        default:
            return "gap-1";
    }
});

const handleClick = (star) => {
    if (!props.readonly) {
        rating.value = star;
    }
};

const handleMouseEnter = (star) => {
    if (!props.readonly) {
        hoverRating.value = star;
    }
};

const handleMouseLeave = () => {
    hoverRating.value = 0;
};

const displayRating = computed(() => {
    return hoverRating.value || rating.value;
});
</script>

<template>
    <div
        :class="['flex items-center', gapClass, { 'cursor-pointer': !readonly }]"
        @mouseleave="handleMouseLeave"
    >
        <button
            v-for="star in 5"
            :key="star"
            type="button"
            :class="[
                'transition-colors duration-150 focus:outline-none',
                { 'hover:scale-110': !readonly },
            ]"
            :disabled="readonly"
            @click="handleClick(star)"
            @mouseenter="handleMouseEnter(star)"
        >
            <Star
                :class="[
                    sizeClasses,
                    star <= displayRating
                        ? 'fill-amber-400 text-amber-400'
                        : 'fill-gray-200 text-gray-300',
                ]"
            />
        </button>
    </div>
</template>
