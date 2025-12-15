<script setup>
import { ref } from 'vue';
import { Head } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Star, MessageSquare, Clock } from 'lucide-vue-next';

const feedbacks = ref([
    {
        id: 1,
        student: 'Alice Cooper',
        course: 'Advanced Laravel Development',
        rating: 5,
        comment: 'Excellent course! The instructor explained complex concepts very clearly.',
        date: '2025-12-10',
        status: 'Published'
    },
    {
        id: 2,
        student: 'Bob Martin',
        course: 'Vue.js Masterclass',
        rating: 4,
        comment: 'Great course overall, but some topics could use more examples.',
        date: '2025-12-09',
        status: 'Published'
    },
    {
        id: 3,
        student: 'Charlie Davis',
        course: 'Digital Marketing Strategy',
        rating: 3,
        comment: 'The content was good, but the pace was a bit too fast.',
        date: '2025-12-08',
        status: 'Pending'
    },
    {
        id: 4,
        student: 'Diana Prince',
        course: 'UI/UX Design Principles',
        rating: 5,
        comment: 'Outstanding! I learned so much from this course.',
        date: '2025-12-07',
        status: 'Published'
    },
]);

const averageRating = ref(4.25);
const totalFeedbacks = ref(124);
</script>

<template>
    <Head title="Feedback" />
    <AdminLayout>
        <div class="space-y-6">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-3xl font-bold text-gray-900">Feedback</h1>
                <p class="mt-2 text-sm text-gray-600">Monitor and respond to student feedback</p>
            </div>
        </div>

        <!-- Summary Cards -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-500">Average Rating</p>
                        <div class="mt-2 flex items-center">
                            <p class="text-3xl font-semibold text-gray-900">{{ averageRating }}</p>
                            <div class="ml-2 flex">
                                <Star
                                    v-for="i in 5"
                                    :key="i"
                                    :size="20"
                                    :class="i <= Math.round(averageRating) ? 'text-yellow-400' : 'text-gray-300'"
                                    :fill="i <= Math.round(averageRating) ? 'currentColor' : 'none'"
                                />
                            </div>
                        </div>
                    </div>
                    <div class="w-12 h-12 bg-yellow-100 rounded-lg flex items-center justify-center">
                        <Star :size="24" class="text-yellow-600" fill="currentColor" />
                    </div>
                </div>
            </div>
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-500">Total Feedback</p>
                        <p class="mt-2 text-3xl font-semibold text-gray-900">{{ totalFeedbacks }}</p>
                    </div>
                    <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center">
                        <MessageSquare :size="24" class="text-blue-600" />
                    </div>
                </div>
            </div>
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-500">Pending Review</p>
                        <p class="mt-2 text-3xl font-semibold text-orange-600">3</p>
                    </div>
                    <div class="w-12 h-12 bg-orange-100 rounded-lg flex items-center justify-center">
                        <Clock :size="24" class="text-orange-600" />
                    </div>
                </div>
            </div>
        </div>

        <!-- Feedback List -->
        <div class="space-y-4">
            <div
                v-for="feedback in feedbacks"
                :key="feedback.id"
                class="bg-white rounded-lg shadow-sm border border-gray-200 p-6"
            >
                <div class="flex items-start justify-between">
                    <div class="flex-1">
                        <div class="flex items-center space-x-3">
                            <div class="w-10 h-10 bg-gradient-to-r from-blue-500 to-purple-600 rounded-full flex items-center justify-center text-white font-semibold">
                                {{ feedback.student.charAt(0) }}
                            </div>
                            <div>
                                <h3 class="text-sm font-semibold text-gray-900">{{ feedback.student }}</h3>
                                <p class="text-xs text-gray-500">{{ feedback.course }}</p>
                            </div>
                        </div>
                        <div class="mt-3 flex items-center space-x-4">
                            <div class="flex">
                                <Star
                                    v-for="i in 5"
                                    :key="i"
                                    :size="16"
                                    :class="i <= feedback.rating ? 'text-yellow-400' : 'text-gray-300'"
                                    :fill="i <= feedback.rating ? 'currentColor' : 'none'"
                                />
                            </div>
                            <span class="text-xs text-gray-500">{{ feedback.date }}</span>
                            <span :class="[
                                'px-2 py-1 text-xs font-semibold rounded-full',
                                feedback.status === 'Published' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800'
                            ]">
                                {{ feedback.status }}
                            </span>
                        </div>
                        <p class="mt-3 text-sm text-gray-700">{{ feedback.comment }}</p>
                    </div>
                    <div class="flex space-x-2 ml-4">
                        <button
                            v-if="feedback.status === 'Pending'"
                            class="text-green-600 hover:text-green-800 px-3 py-1 rounded-lg border border-green-200 hover:bg-green-50 text-sm transition-colors"
                        >
                            Approve
                        </button>
                        <button class="text-blue-600 hover:text-blue-800 px-3 py-1 rounded-lg border border-blue-200 hover:bg-blue-50 text-sm transition-colors">
                            Reply
                        </button>
                    </div>
                </div>
            </div>
        </div>
        </div>
    </AdminLayout>
</template>
