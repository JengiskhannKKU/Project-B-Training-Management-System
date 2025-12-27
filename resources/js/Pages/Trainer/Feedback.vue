<script setup>
import { ref, computed } from 'vue';
import { Head } from '@inertiajs/vue3';
import TrainerLayout from '@/Layouts/TrainerLayout.vue';
import RatingOverview from '@/Components/RatingOverview.vue';
import SentimentOverview from '@/Components/SentimentOverview.vue';
import ReviewCard from '@/Components/ReviewCard.vue';
import { MessageSquare, Search, ListFilter, ArrowUpDown } from 'lucide-vue-next';

// Mock Data - Trainer's courses only
// In production, this would come from the backend filtered by trainer_id
const trainerCourses = ref([
    'Advanced Laravel Development',
    'Vue.js Masterclass',
    'Cybersecurity Fundamentals'
]);

// All reviews filtered to only show trainer's courses
const allReviews = ref([
    {
        id: 1,
        userName: 'Sarah Johnson',
        courseName: 'Advanced Laravel Development',
        rating: 5,
        comment: 'This course exceeded my expectations! The instructor broke down complex Laravel concepts into digestible lessons. The hands-on projects really helped solidify my understanding. Highly recommend to anyone looking to master Laravel.',
        date: '2025-12-20',
        helpfulCount: 24
    },
    {
        id: 2,
        userName: 'Michael Chen',
        courseName: 'Vue.js Masterclass',
        rating: 5,
        comment: 'Outstanding content and delivery. The Vue 3 composition API section was particularly well explained. I appreciate the real-world examples and best practices shared throughout the course.',
        date: '2025-12-19',
        helpfulCount: 18
    },
    {
        id: 6,
        userName: 'Ryan Thompson',
        courseName: 'Advanced Laravel Development',
        rating: 5,
        comment: 'Incredible depth of knowledge shared by the instructor. The queue management and testing sections were game-changers for my workflow. Worth every minute!',
        date: '2025-12-15',
        helpfulCount: 22
    },
    {
        id: 8,
        userName: 'Christopher Brown',
        courseName: 'Cybersecurity Fundamentals',
        rating: 5,
        comment: 'Eye-opening course on modern security practices. The hands-on labs for penetration testing were fantastic. Instructor clearly has industry experience and shares valuable insights.',
        date: '2025-12-13',
        helpfulCount: 27
    },
    {
        id: 10,
        userName: 'James Wilson',
        courseName: 'Vue.js Masterclass',
        rating: 5,
        comment: 'Best Vue.js course I have taken! Clear explanations, excellent pacing, and the final project ties everything together perfectly. The instructor is very responsive to questions too.',
        date: '2025-12-11',
        helpfulCount: 19
    },
    {
        id: 13,
        userName: 'Olivia Taylor',
        courseName: 'Advanced Laravel Development',
        rating: 3,
        comment: 'Good content but assumes a lot of prior knowledge. Beginners might struggle. The API development section was well done, but I found the package development part confusing.',
        date: '2025-12-08',
        helpfulCount: 6
    },
    {
        id: 16,
        userName: 'Marcus Rodriguez',
        courseName: 'Cybersecurity Fundamentals',
        rating: 4,
        comment: 'Great practical approach to security. The modules on network security and encryption were particularly strong. Would love to see more content on cloud security.',
        date: '2025-12-05',
        helpfulCount: 13
    },
    {
        id: 17,
        userName: 'Emma Thompson',
        courseName: 'Vue.js Masterclass',
        rating: 4,
        comment: 'Solid course with good examples. The state management section using Pinia was very helpful. Some parts could use more in-depth explanation.',
        date: '2025-12-04',
        helpfulCount: 10
    },
    {
        id: 18,
        userName: 'Alex Chen',
        courseName: 'Advanced Laravel Development',
        rating: 5,
        comment: 'Absolutely fantastic! The instructor has a gift for explaining complex topics. The real-world examples and best practices are invaluable.',
        date: '2025-12-02',
        helpfulCount: 28
    },
    {
        id: 19,
        userName: 'Sophie Martinez',
        courseName: 'Cybersecurity Fundamentals',
        rating: 5,
        comment: 'This course opened my eyes to the importance of security in development. The practical exercises were challenging but rewarding.',
        date: '2025-12-01',
        helpfulCount: 15
    }
]);

// Calculate statistics based on trainer's reviews only
const averageRating = computed(() => {
    const total = allReviews.value.reduce((sum, review) => sum + review.rating, 0);
    return (total / allReviews.value.length).toFixed(1);
});

const totalReviews = ref(allReviews.value.length);

const ratingDistribution = computed(() => {
    const distribution = { 5: 0, 4: 0, 3: 0, 2: 0, 1: 0 };
    allReviews.value.forEach(review => {
        distribution[review.rating]++;
    });

    // Convert to percentages
    const total = allReviews.value.length;
    return {
        5: Math.round((distribution[5] / total) * 100),
        4: Math.round((distribution[4] / total) * 100),
        3: Math.round((distribution[3] / total) * 100),
        2: Math.round((distribution[2] / total) * 100),
        1: Math.round((distribution[1] / total) * 100)
    };
});

const sentiments = computed(() => {
    const positive = allReviews.value.filter(r => r.rating >= 4).length;
    const neutral = allReviews.value.filter(r => r.rating === 3).length;
    const negative = allReviews.value.filter(r => r.rating <= 2).length;
    const total = allReviews.value.length;

    return {
        positive: Math.round((positive / total) * 100),
        neutral: Math.round((neutral / total) * 100),
        negative: Math.round((negative / total) * 100)
    };
});

// Filter, Sort, and Search controls
const searchQuery = ref('');
const selectedCourse = ref('all');
const sortBy = ref('date'); // Options: 'date', 'rating'

// Get unique course names for filter (only trainer's courses)
const uniqueCourses = computed(() => {
    return [...trainerCourses.value].sort();
});

// Filtered and sorted reviews
const filteredReviews = computed(() => {
    let result = [...allReviews.value];

    // Filter by course
    if (selectedCourse.value !== 'all') {
        result = result.filter(review => review.courseName === selectedCourse.value);
    }

    // Filter by search query
    if (searchQuery.value) {
        const query = searchQuery.value.toLowerCase();
        result = result.filter(review =>
            review.comment.toLowerCase().includes(query) ||
            review.userName.toLowerCase().includes(query) ||
            review.courseName.toLowerCase().includes(query)
        );
    }

    // Sort
    if (sortBy.value === 'date') {
        result.sort((a, b) => new Date(b.date) - new Date(a.date));
    } else if (sortBy.value === 'rating') {
        result.sort((a, b) => b.rating - a.rating);
    }

    return result;
});
</script>

<template>
    <Head title="Feedback" />
    <TrainerLayout>
        <div class="space-y-6">
            <!-- Page Header -->
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900">My Course Feedback</h1>
                    <p class="mt-2 text-sm text-gray-600">
                        Monitor student feedback for your courses
                    </p>
                </div>
            </div>

            <!-- Two Column Layout -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Left Column: Rating and Sentiment -->
                <div class="lg:col-span-1 space-y-6">
                    <!-- Overall Rating Section -->
                    <RatingOverview
                        :averageRating="parseFloat(averageRating)"
                        :totalReviews="totalReviews"
                        :ratingDistribution="ratingDistribution"
                    />

                    <!-- Sentiment Overview Section -->
                    <SentimentOverview :sentiments="sentiments" />
                </div>

                <!-- Right Column: Comments Reader -->
                <div class="lg:col-span-2">
                    <div class="bg-white rounded-[25px] shadow-sm border border-[#dfe5ef] p-6">
                        <!-- Header -->
                        <div class="flex items-center gap-3 mb-6">
                            <MessageSquare class="h-6 w-6 text-[#2f837d]" />
                            <h2 class="text-xl font-semibold text-gray-900">
                                Recent Reviews
                            </h2>
                            <span class="ml-auto text-sm text-gray-600">
                                {{ filteredReviews.length }} reviews
                            </span>
                        </div>

                        <!-- Filter, Sort, and Search Controls -->
                        <div class="flex flex-col sm:flex-row gap-3 mb-6">
                            <!-- Search Bar -->
                            <div class="relative flex-1">
                                <input
                                    v-model="searchQuery"
                                    type="text"
                                    placeholder="Search reviews..."
                                    class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#2f837d] focus:border-transparent text-sm"
                                />
                                <Search class="absolute left-3 top-2.5 h-4 w-4 text-gray-400" />
                            </div>

                            <!-- Filter by Course -->
                            <div class="relative">
                                <select
                                    v-model="selectedCourse"
                                    class="appearance-none pl-10 pr-10 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#2f837d] focus:border-transparent text-sm bg-white cursor-pointer min-w-[180px]"
                                >
                                    <option value="all">All My Courses</option>
                                    <option
                                        v-for="course in uniqueCourses"
                                        :key="course"
                                        :value="course"
                                    >
                                        {{ course }}
                                    </option>
                                </select>
                                <ListFilter class="absolute left-3 top-2.5 h-4 w-4 text-gray-400 pointer-events-none" />
                                <svg class="absolute right-3 top-3 h-4 w-4 text-gray-400 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                </svg>
                            </div>

                            <!-- Sort By -->
                            <div class="relative">
                                <select
                                    v-model="sortBy"
                                    class="appearance-none pl-10 pr-10 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#2f837d] focus:border-transparent text-sm bg-white cursor-pointer min-w-[150px]"
                                >
                                    <option value="date">Newest First</option>
                                    <option value="rating">Highest Rating</option>
                                </select>
                                <ArrowUpDown class="absolute left-3 top-2.5 h-4 w-4 text-gray-400 pointer-events-none" />
                                <svg class="absolute right-3 top-3 h-4 w-4 text-gray-400 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                </svg>
                            </div>
                        </div>

                        <!-- Scrollable Reviews Feed -->
                        <div class="space-y-4 max-h-[calc(100vh-380px)] overflow-y-auto pr-2 custom-scrollbar">
                            <ReviewCard
                                v-for="review in filteredReviews"
                                :key="review.id"
                                :review="review"
                            />

                            <!-- No Results Message -->
                            <div v-if="filteredReviews.length === 0" class="text-center py-12">
                                <MessageSquare class="mx-auto h-12 w-12 text-gray-400" />
                                <h3 class="mt-2 text-sm font-medium text-gray-900">No reviews found</h3>
                                <p class="mt-1 text-sm text-gray-500">Try adjusting your search or filter criteria.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </TrainerLayout>
</template>

<style scoped>
/* Custom Scrollbar */
.custom-scrollbar::-webkit-scrollbar {
    width: 6px;
}

.custom-scrollbar::-webkit-scrollbar-track {
    background: #f1f5f9;
    border-radius: 10px;
}

.custom-scrollbar::-webkit-scrollbar-thumb {
    background: #cbd5e1;
    border-radius: 10px;
}

.custom-scrollbar::-webkit-scrollbar-thumb:hover {
    background: #94a3b8;
}
</style>
