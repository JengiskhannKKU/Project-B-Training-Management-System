<script setup>
import { ref, computed } from 'vue';
import { Head } from '@inertiajs/vue3';
import TrainerLayout from '@/Layouts/TrainerLayout.vue';
import OverallRating from '@/Components/OverallRating.vue';
import SentimentOverview from '@/Components/SentimentOverview.vue';
import CommentsPanel from '@/Components/CommentsPanel.vue';
import { mockFeedbacks, calculateFeedbackStats, filterFeedbacksByTrainer } from '@/mockData/feedbackData.js';

// Simulating trainer ID - in a real app, this would come from auth
const trainerId = ref(2);

// Filter feedbacks for this trainer
const trainerFeedbacks = computed(() => filterFeedbacksByTrainer(mockFeedbacks, trainerId.value));

// Calculate statistics
const stats = computed(() => calculateFeedbackStats(trainerFeedbacks.value));
</script>

<template>
    <Head title="Feedback & Reviews - Trainer" />

    <TrainerLayout>
        <div class="space-y-6">
            <!-- Header -->
            <div>
                <h1 class="text-3xl font-bold text-gray-900">Feedback & Reviews</h1>
                <p class="mt-2 text-sm text-gray-600">Analyze and respond to student feedback for your courses</p>
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
                    <CommentsPanel :reviews="trainerFeedbacks" />
                </div>
            </div>
        </div>
    </TrainerLayout>
</template>
