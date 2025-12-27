<script setup>
import { ref, computed } from 'vue';
import { Head } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import RatingOverview from '@/Components/RatingOverview.vue';
import SentimentOverview from '@/Components/SentimentOverview.vue';
import ReviewCard from '@/Components/ReviewCard.vue';
import { MessageSquare, Search, ListFilter, ArrowUpDown } from 'lucide-vue-next';

// Mock Data
const averageRating = ref(4.8);
const totalReviews = ref(347);

const ratingDistribution = ref({
    5: 58,
    4: 25,
    3: 11,
    2: 4,
    1: 2
});

const sentiments = ref({
    positive: 72,
    neutral: 21,
    negative: 7
});

const reviews = ref([
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
        id: 3,
        userName: 'Emily Rodriguez',
        courseName: 'Digital Marketing Strategy',
        rating: 4,
        comment: 'Great course overall with practical insights into modern marketing. The SEO and social media modules were excellent. Would love to see more case studies in future updates.',
        date: '2025-12-18',
        helpfulCount: 12
    },
    {
        id: 4,
        userName: 'David Kim',
        courseName: 'UI/UX Design Principles',
        rating: 5,
        comment: 'As a developer transitioning into design, this course was perfect. The instructor explained design thinking and user research methodologies clearly. The Figma tutorials were a bonus!',
        date: '2025-12-17',
        helpfulCount: 31
    },
    {
        id: 5,
        userName: 'Jessica Martinez',
        courseName: 'Python for Data Science',
        rating: 4,
        comment: 'Comprehensive introduction to data science with Python. The pandas and matplotlib sections were very thorough. Could benefit from more advanced machine learning topics.',
        date: '2025-12-16',
        helpfulCount: 15
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
        id: 7,
        userName: 'Amanda Lee',
        courseName: 'React Native Mobile Development',
        rating: 3,
        comment: 'Good foundational content, but the pace felt rushed in later modules. The navigation and state management sections could use more examples. Still learned a lot though.',
        date: '2025-12-14',
        helpfulCount: 8
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
        id: 9,
        userName: 'Nicole Wang',
        courseName: 'Digital Marketing Strategy',
        rating: 4,
        comment: 'Really enjoyed the content marketing and analytics sections. The tools and frameworks introduced are immediately applicable. Looking forward to implementing these strategies.',
        date: '2025-12-12',
        helpfulCount: 11
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
        id: 11,
        userName: 'Sophia Anderson',
        courseName: 'UI/UX Design Principles',
        rating: 4,
        comment: 'Well-structured course with great visual examples. The user testing module was particularly valuable. Would appreciate more content on accessibility design.',
        date: '2025-12-10',
        helpfulCount: 14
    },
    {
        id: 12,
        userName: 'Daniel Garcia',
        courseName: 'AWS Cloud Architecture',
        rating: 5,
        comment: 'Comprehensive overview of AWS services with practical deployment examples. The serverless architecture section was brilliant. Helped me pass my AWS certification!',
        date: '2025-12-09',
        helpfulCount: 33
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
        id: 14,
        userName: 'Ethan Moore',
        courseName: 'Machine Learning Basics',
        rating: 4,
        comment: 'Solid introduction to ML concepts. The neural network visualizations really helped with understanding. Would love a follow-up course on deep learning applications.',
        date: '2025-12-07',
        helpfulCount: 16
    },
    {
        id: 15,
        userName: 'Isabella Thomas',
        courseName: 'Agile Project Management',
        rating: 5,
        comment: 'Transformed how I approach project management! The scrum and kanban comparisons were insightful. The templates provided are super helpful for implementation.',
        date: '2025-12-06',
        helpfulCount: 21
    }
]);

// Filter, Sort, and Search controls
const searchQuery = ref('');
const selectedCourse = ref('all');
const sortBy = ref('date'); // Options: 'date', 'rating'

// Get unique course names for filter
const uniqueCourses = computed(() => {
    const courses = [...new Set(reviews.value.map(review => review.courseName))];
    return courses.sort();
});

// Filtered and sorted reviews
const filteredReviews = computed(() => {
    let result = [...reviews.value];

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
    <AdminLayout>
        <div class="space-y-6">
            <!-- Page Header -->
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900">Feedback</h1>
                    <p class="mt-2 text-sm text-gray-600">
                        Monitor student feedback and course ratings
                    </p>
                </div>
            </div>

            <!-- Two Column Layout -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Left Column: Rating and Sentiment -->
                <div class="lg:col-span-1 space-y-6">
                    <!-- Overall Rating Section -->
                    <RatingOverview
                        :averageRating="averageRating"
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
                                    <option value="all">All Courses</option>
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
    </AdminLayout>
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
