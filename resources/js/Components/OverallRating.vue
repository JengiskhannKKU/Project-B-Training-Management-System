<script setup>
import { Star } from 'lucide-vue-next';

const props = defineProps({
    averageRating: {
        type: Number,
        required: true
    },
    totalReviews: {
        type: Number,
        required: true
    },
    distributionPercentages: {
        type: Object,
        required: true
    }
});

// Generate filled/partial stars based on average rating
const getStarFill = (index) => {
    const rating = props.averageRating;
    if (rating >= index) {
        return 'fill'; // Fully filled
    } else if (rating > index - 1) {
        return 'partial'; // Partially filled
    }
    return 'empty'; // Empty
};
</script>

<template>
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
        <h2 class="text-lg font-semibold text-gray-900 mb-4">Overall Rating</h2>

        <!-- Large Rating Display -->
        <div class="text-center mb-6">
            <div class="text-5xl font-bold text-gray-900 mb-2">
                {{ averageRating.toFixed(1) }}
            </div>

            <!-- Star Rating Visualization -->
            <div class="flex items-center justify-center gap-1 mb-2">
                <div v-for="i in 5" :key="i" class="relative inline-block" style="width: 24px; height: 24px;">
                    <Star
                        :size="24"
                        :class="getStarFill(i) === 'empty' ? 'text-gray-300' : 'text-yellow-400'"
                        class="absolute top-0 left-0"
                    />
                    <Star
                        v-if="getStarFill(i) !== 'empty'"
                        :size="24"
                        class="text-yellow-400 fill-yellow-400 absolute top-0 left-0"
                        :style="getStarFill(i) === 'partial' ? 'clip-path: inset(0 50% 0 0);' : ''"
                    />
                </div>
            </div>

            <p class="text-sm text-gray-500">
                Based on {{ totalReviews }} review{{ totalReviews !== 1 ? 's' : '' }}
            </p>
        </div>

        <!-- Rating Distribution -->
        <div class="space-y-3">
            <div v-for="rating in [5, 4, 3, 2, 1]" :key="rating" class="flex items-center gap-3">
                <!-- Star Label -->
                <div class="flex items-center gap-1 w-12">
                    <span class="text-sm font-medium text-gray-700">{{ rating }}</span>
                    <Star :size="14" class="text-yellow-400 fill-yellow-400" />
                </div>

                <!-- Progress Bar -->
                <div class="flex-1 h-2 bg-gray-200 rounded-full overflow-hidden">
                    <div
                        class="h-full bg-yellow-400 transition-all duration-300"
                        :style="{ width: `${distributionPercentages[rating] || 0}%` }"
                    ></div>
                </div>

                <!-- Percentage -->
                <span class="text-sm text-gray-600 w-12 text-right">
                    {{ distributionPercentages[rating] || 0 }}%
                </span>
            </div>
        </div>
    </div>
</template>
