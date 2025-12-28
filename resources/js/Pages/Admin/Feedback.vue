<script setup>
import { computed } from 'vue';
import { Head } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import OverallRating from '@/Components/OverallRating.vue';
import SentimentOverview from '@/Components/SentimentOverview.vue';
import CommentsPanel from '@/Components/CommentsPanel.vue';
import { mockFeedbacks, calculateFeedbackStats } from '@/mockData/feedbackData.js';

// Admin sees all feedbacks (no filtering by trainer)
const allFeedbacks = mockFeedbacks;

// Calculate statistics
const stats = computed(() => calculateFeedbackStats(allFeedbacks));
</script>

<template>
    <Head title="Feedback & Reviews" />
    <AdminLayout>
        <div class="space-y-6">
            <!-- Header -->
            <div>
                <h1 class="text-3xl font-bold text-gray-900">Feedback & Reviews</h1>
                <p class="mt-2 text-sm text-gray-600">Monitor and analyze student feedback across all courses</p>
            </div>

            <!-- Two Column Layout -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Left Column: Analytics Panel (1/3 width) -->
                <div class="lg:col-span-1 space-y-6">
                    <!-- Overall Rating Component -->
                    <OverallRating
                        :average-rating="stats.averageRating"
                        :total-reviews="stats.totalReviews"
                        :distribution-percentages="stats.distributionPercentages"
                    />

                    <!-- Sentiment Overview Component -->
                    <SentimentOverview
                        :sentiment-percentages="stats.sentimentPercentages"
                    />
                </div>

                <!-- Right Column: Comments Reader Panel (2/3 width) -->
                <div class="lg:col-span-2">
                    <CommentsPanel :reviews="allFeedbacks" />
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
