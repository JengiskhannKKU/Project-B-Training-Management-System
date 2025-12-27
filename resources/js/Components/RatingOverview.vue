<script setup>
import { Star } from 'lucide-vue-next';

const props = defineProps({
    averageRating: {
        type: Number,
        default: 4.8
    },
    totalReviews: {
        type: Number,
        default: 0
    },
    ratingDistribution: {
        type: Object,
        default: () => ({
            5: 58,
            4: 25,
            3: 11,
            2: 4,
            1: 2
        })
    }
});

const getStarPercentage = (starCount) => {
    return props.ratingDistribution[starCount] || 0;
};
</script>

<template>
    <div class="bg-white rounded-[25px] shadow-sm border border-[#dfe5ef] p-6">
        <h2 class="text-xl font-semibold text-gray-900 mb-6">Overall Rating</h2>

        <!-- Large Rating Display -->
        <div class="text-center mb-6">
            <div class="text-6xl font-bold text-gray-900 mb-3">
                {{ averageRating.toFixed(1) }}
            </div>

            <!-- Star Rating Display -->
            <div class="flex justify-center items-center gap-1 mb-2">
                <Star
                    v-for="i in 5"
                    :key="i"
                    :size="24"
                    :class="i <= Math.round(averageRating) ? 'text-yellow-400' : 'text-gray-300'"
                    :fill="i <= Math.round(averageRating) ? 'currentColor' : 'none'"
                />
            </div>

            <!-- Review Count -->
            <p class="text-sm text-gray-600">
                Based on {{ totalReviews }} reviews
            </p>
        </div>

        <!-- Rating Distribution Bars -->
        <div class="space-y-3">
            <div
                v-for="star in [5, 4, 3, 2, 1]"
                :key="star"
                class="flex items-center gap-3"
            >
                <!-- Star Label -->
                <div class="flex items-center gap-1 w-12">
                    <span class="text-sm font-medium text-gray-700">{{ star }}</span>
                    <Star :size="14" class="text-yellow-400" fill="currentColor" />
                </div>

                <!-- Progress Bar -->
                <div class="flex-1 bg-gray-200 rounded-full h-2.5 overflow-hidden">
                    <div
                        class="bg-yellow-400 h-full rounded-full transition-all duration-300"
                        :style="{ width: `${getStarPercentage(star)}%` }"
                    ></div>
                </div>

                <!-- Percentage -->
                <div class="w-12 text-right">
                    <span class="text-sm font-medium text-gray-700">
                        {{ getStarPercentage(star) }}%
                    </span>
                </div>
            </div>
        </div>
    </div>
</template>
