<script setup>
import { computed } from "vue";
import StarRating from "@/Components/StarRating.vue";
import { MessageSquare, User } from "lucide-vue-next";

const props = defineProps({
    reviews: {
        type: Array,
        default: () => [],
    },
    averageRating: {
        type: Number,
        default: null,
    },
    totalReviews: {
        type: Number,
        default: 0,
    },
    showTitle: {
        type: Boolean,
        default: true,
    },
});

const formatDate = (dateString) => {
    const date = new Date(dateString);
    return date.toLocaleDateString("en-US", {
        year: "numeric",
        month: "short",
        day: "numeric",
    });
};

const getRatingDistribution = computed(() => {
    const distribution = { 5: 0, 4: 0, 3: 0, 2: 0, 1: 0 };
    props.reviews.forEach((review) => {
        if (distribution[review.rating] !== undefined) {
            distribution[review.rating]++;
        }
    });
    return distribution;
});
</script>

<template>
    <div class="space-y-6">
        <!-- Header with average rating -->
        <div v-if="showTitle || averageRating" class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <h3 v-if="showTitle" class="text-lg font-semibold text-gray-900">
                Reviews & Ratings
            </h3>
            
            <div v-if="averageRating && totalReviews > 0" class="flex items-center gap-3">
                <div class="flex items-center gap-2">
                    <span class="text-2xl font-bold text-gray-900">{{ averageRating }}</span>
                    <StarRating :model-value="Math.round(averageRating)" readonly size="sm" />
                </div>
                <span class="text-sm text-gray-500">
                    ({{ totalReviews }} {{ totalReviews === 1 ? "review" : "reviews" }})
                </span>
            </div>
        </div>

        <!-- Empty State -->
        <div
            v-if="reviews.length === 0"
            class="rounded-xl border border-dashed border-gray-300 bg-gray-50 p-8 text-center"
        >
            <MessageSquare class="mx-auto h-12 w-12 text-gray-400" />
            <h4 class="mt-4 text-base font-medium text-gray-900">No reviews yet</h4>
            <p class="mt-1 text-sm text-gray-500">
                Be the first to share your experience!
            </p>
        </div>

        <!-- Reviews List -->
        <div v-else class="space-y-4">
            <div
                v-for="review in reviews"
                :key="review.id"
                class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm"
            >
                <div class="flex items-start justify-between">
                    <div class="flex items-center gap-3">
                        <div class="flex h-10 w-10 items-center justify-center rounded-full bg-teal-100">
                            <User class="h-5 w-5 text-teal-600" />
                        </div>
                        <div>
                            <p class="font-medium text-gray-900">
                                {{ review.user?.name || "Anonymous" }}
                            </p>
                            <p class="text-xs text-gray-500">
                                {{ formatDate(review.created_at) }}
                            </p>
                        </div>
                    </div>
                    <StarRating :model-value="review.rating" readonly size="sm" />
                </div>
                
                <p v-if="review.comment" class="mt-4 text-sm leading-relaxed text-gray-700">
                    {{ review.comment }}
                </p>
            </div>
        </div>
    </div>
</template>
