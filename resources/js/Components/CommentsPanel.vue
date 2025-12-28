<script setup>
import { ref, computed } from 'vue';
import { Search, Filter, ArrowUpDown } from 'lucide-vue-next';
import ReviewCard from '@/Components/ReviewCard.vue';

const props = defineProps({
    reviews: {
        type: Array,
        required: true
    }
});

// Filter and search state
const selectedFilter = ref('all');
const selectedSort = ref('date');
const searchQuery = ref('');

// Filter options
const filterOptions = [
    { value: 'all', label: 'All Feedback' },
    { value: 'positive', label: 'Positive' },
    { value: 'neutral', label: 'Neutral' },
    { value: 'negative', label: 'Negative' }
];

// Sort options
const sortOptions = [
    { value: 'date', label: 'By Date (Newest)' },
    { value: 'date_old', label: 'By Date (Oldest)' },
    { value: 'rating_high', label: 'By Rating (High)' },
    { value: 'rating_low', label: 'By Rating (Low)' }
];

// Filtered and sorted reviews
const filteredReviews = computed(() => {
    let result = [...props.reviews];

    // Apply sentiment filter
    if (selectedFilter.value !== 'all') {
        result = result.filter(review => review.sentiment === selectedFilter.value);
    }

    // Apply search filter
    if (searchQuery.value.trim()) {
        const query = searchQuery.value.toLowerCase();
        result = result.filter(review =>
            review.reviewText.toLowerCase().includes(query) ||
            review.courseName.toLowerCase().includes(query) ||
            review.reviewerName.toLowerCase().includes(query)
        );
    }

    // Apply sorting
    switch (selectedSort.value) {
        case 'date':
            result.sort((a, b) => new Date(b.date) - new Date(a.date));
            break;
        case 'date_old':
            result.sort((a, b) => new Date(a.date) - new Date(b.date));
            break;
        case 'rating_high':
            result.sort((a, b) => b.rating - a.rating);
            break;
        case 'rating_low':
            result.sort((a, b) => a.rating - b.rating);
            break;
    }

    return result;
});
</script>

<template>
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 h-full flex flex-col">
        <!-- Header Controls -->
        <div class="p-4 border-b border-gray-200 space-y-3">
            <h2 class="text-lg font-semibold text-gray-900">Reviews</h2>

            <!-- Filter and Sort Row -->
            <div class="flex items-center gap-3 flex-wrap">
                <!-- Filter Dropdown -->
                <div class="flex-1 min-w-[150px]">
                    <label class="sr-only">Filter</label>
                    <div class="relative">
                        <Filter class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400" :size="16" />
                        <select
                            v-model="selectedFilter"
                            class="w-full pl-9 pr-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-transparent appearance-none bg-white"
                        >
                            <option
                                v-for="option in filterOptions"
                                :key="option.value"
                                :value="option.value"
                            >
                                {{ option.label }}
                            </option>
                        </select>
                    </div>
                </div>

                <!-- Sort Dropdown -->
                <div class="flex-1 min-w-[150px]">
                    <label class="sr-only">Sort</label>
                    <div class="relative">
                        <ArrowUpDown class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400" :size="16" />
                        <select
                            v-model="selectedSort"
                            class="w-full pl-9 pr-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-transparent appearance-none bg-white"
                        >
                            <option
                                v-for="option in sortOptions"
                                :key="option.value"
                                :value="option.value"
                            >
                                {{ option.label }}
                            </option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Search Bar -->
            <div class="relative">
                <Search class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400" :size="18" />
                <input
                    v-model="searchQuery"
                    type="text"
                    placeholder="Search by review text or course name..."
                    class="w-full pl-10 pr-4 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-transparent"
                />
            </div>
        </div>

        <!-- Reviews List (Scrollable) -->
        <div class="flex-1 overflow-y-auto p-4 space-y-4">
            <div v-if="filteredReviews.length === 0" class="text-center py-12">
                <p class="text-gray-500">No reviews found matching your criteria.</p>
            </div>

            <ReviewCard
                v-for="review in filteredReviews"
                :key="review.id"
                :review="review"
            />
        </div>

        <!-- Results Counter -->
        <div class="p-3 border-t border-gray-200 bg-gray-50">
            <p class="text-xs text-gray-600 text-center">
                Showing {{ filteredReviews.length }} of {{ reviews.length }} review{{ reviews.length !== 1 ? 's' : '' }}
            </p>
        </div>
    </div>
</template>

<style scoped>
/* Custom scrollbar styling */
.overflow-y-auto::-webkit-scrollbar {
    width: 6px;
}

.overflow-y-auto::-webkit-scrollbar-track {
    background: #f1f1f1;
}

.overflow-y-auto::-webkit-scrollbar-thumb {
    background: #cbd5e0;
    border-radius: 3px;
}

.overflow-y-auto::-webkit-scrollbar-thumb:hover {
    background: #a0aec0;
}
</style>
