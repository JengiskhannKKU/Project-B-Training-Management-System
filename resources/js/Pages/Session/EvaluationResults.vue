<script setup>
import { computed, onMounted, ref } from 'vue';
import { Head, Link, usePage } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import TrainerLayout from '@/Layouts/TrainerLayout.vue';
import {
    Star,
    TrendingUp,
    Users,
    ThumbsUp,
    ArrowLeft,
    BarChart3,
    MessageSquare,
    Award,
    Target
} from 'lucide-vue-next';
import axios from 'axios';

const props = defineProps({
    sessionId: {
        type: [Number, String],
        required: true,
    },
});

const page = usePage();
const loading = ref(true);
const error = ref(null);
const evaluationData = ref(null);

const roleName = computed(() =>
    page.props.auth?.user?.role?.name ||
    page.props.auth?.user?.role ||
    'trainee'
);

const LayoutComponent = computed(() => {
    if (roleName.value === 'admin') return AdminLayout;
    if (roleName.value === 'trainer') return TrainerLayout;
    return AdminLayout;
});

const backLink = computed(() => {
    if (roleName.value === 'admin') return '/admin/feedback';
    if (roleName.value === 'trainer') return '/trainer/feedback';
    return '/';
});

const difficultyBadgeClass = (level) => {
    const classes = {
        easy: 'bg-green-100 text-green-700',
        medium: 'bg-yellow-100 text-yellow-700',
        hard: 'bg-red-100 text-red-700',
    };
    return classes[level] || 'bg-gray-100 text-gray-700';
};

const ratingColor = (rating) => {
    if (rating >= 4.5) return 'text-emerald-600';
    if (rating >= 3.5) return 'text-blue-600';
    if (rating >= 2.5) return 'text-yellow-600';
    return 'text-red-600';
};

const ratingBgColor = (rating) => {
    if (rating >= 4.5) return 'from-emerald-400 to-emerald-600';
    if (rating >= 3.5) return 'from-blue-400 to-blue-600';
    if (rating >= 2.5) return 'from-yellow-400 to-yellow-600';
    return 'from-red-400 to-red-600';
};

// Fetch evaluation data from API
const fetchEvaluationData = async () => {
    try {
        loading.value = true;
        error.value = null;

        const response = await axios.get(`/api/sessions/${props.sessionId}/evaluation`);

        if (response.data.success) {
            evaluationData.value = response.data.data;
        } else {
            error.value = 'Failed to load evaluation data';
        }
    } catch (err) {
        console.error('Error fetching evaluation data:', err);
        if (err.response?.status === 403) {
            error.value = 'You do not have permission to view this evaluation';
        } else if (err.response?.status === 404) {
            error.value = 'Session not found';
        } else {
            error.value = 'Failed to load evaluation data. Please try again.';
        }
    } finally {
        loading.value = false;
    }
};

onMounted(() => {
    fetchEvaluationData();
});
</script>

<template>
    <Head title="Evaluation Results" />

    <component :is="LayoutComponent">
        <div class="mx-auto max-w-7xl space-y-6">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div>
                    <Link
                        :href="backLink"
                        class="inline-flex items-center gap-2 text-[#2f837d] hover:text-[#26685f] font-medium transition-colors mb-2"
                    >
                        <ArrowLeft :size="18" />
                        <span>Back to Feedback</span>
                    </Link>
                    <h1 class="text-2xl font-bold text-gray-900">
                        Evaluation Results
                    </h1>
                    <p v-if="evaluationData" class="text-gray-600 mt-1">
                        {{ evaluationData.session.course_name }} - {{ evaluationData.session.title }}
                    </p>
                    <p v-if="evaluationData" class="text-sm text-gray-500">
                        Trainer: {{ evaluationData.session.trainer_name }}
                    </p>
                </div>
            </div>

            <!-- Loading State -->
            <div v-if="loading" class="rounded-2xl border border-gray-200 bg-white p-12 text-center shadow-sm">
                <div class="flex flex-col items-center gap-4">
                    <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-teal-600"></div>
                    <p class="text-gray-600">Loading evaluation data...</p>
                </div>
            </div>

            <!-- Error State -->
            <div v-else-if="error" class="rounded-2xl border border-red-200 bg-red-50 p-6 text-red-700 shadow-sm">
                <div class="flex items-start gap-3">
                    <MessageSquare class="h-6 w-6 flex-shrink-0 mt-0.5" />
                    <div>
                        <h3 class="font-semibold">Error Loading Data</h3>
                        <p class="mt-1">{{ error }}</p>
                    </div>
                </div>
            </div>

            <!-- Data Display -->
            <template v-else-if="evaluationData">
                <!-- Summary Cards -->
                <div class="grid gap-6 md:grid-cols-4">
                    <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm hover:shadow-md transition-shadow">
                        <div class="flex items-center gap-3">
                            <div class="flex h-12 w-12 items-center justify-center rounded-full bg-blue-100">
                                <Users class="h-6 w-6 text-blue-600" />
                            </div>
                            <div>
                                <div class="text-sm text-gray-500">Total Responses</div>
                                <div class="text-2xl font-bold text-gray-900">
                                    {{ evaluationData.total_evaluations }}
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm hover:shadow-md transition-shadow">
                        <div class="flex items-center gap-3">
                            <div class="flex h-12 w-12 items-center justify-center rounded-full bg-yellow-100">
                                <Star class="h-6 w-6 text-yellow-600" />
                            </div>
                            <div>
                                <div class="text-sm text-gray-500">Overall Rating</div>
                                <div class="text-2xl font-bold" :class="ratingColor(evaluationData.averages.overall_rating)">
                                    {{ evaluationData.averages.overall_rating || 0 }}/5
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm hover:shadow-md transition-shadow">
                        <div class="flex items-center gap-3">
                            <div class="flex h-12 w-12 items-center justify-center rounded-full bg-emerald-100">
                                <TrendingUp class="h-6 w-6 text-emerald-600" />
                            </div>
                            <div>
                                <div class="text-sm text-gray-500">Trainer Quality</div>
                                <div class="text-2xl font-bold" :class="ratingColor(evaluationData.averages.trainer_quality)">
                                    {{ evaluationData.averages.trainer_quality || 0 }}/5
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm hover:shadow-md transition-shadow">
                        <div class="flex items-center gap-3">
                            <div class="flex h-12 w-12 items-center justify-center rounded-full bg-pink-100">
                                <ThumbsUp class="h-6 w-6 text-pink-600" />
                            </div>
                            <div>
                                <div class="text-sm text-gray-500">Would Recommend</div>
                                <div class="text-2xl font-bold text-pink-600">
                                    {{ evaluationData.averages.would_recommend_percentage || 0 }}%
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Average Ratings Detail -->
                <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm">
                    <div class="flex items-center gap-3 mb-6">
                        <div class="flex h-10 w-10 items-center justify-center rounded-full bg-purple-100">
                            <BarChart3 class="h-5 w-5 text-purple-600" />
                        </div>
                        <h2 class="text-lg font-semibold text-gray-900">Average Ratings by Category</h2>
                    </div>

                    <div class="grid gap-6 md:grid-cols-2">
                        <div class="space-y-2">
                            <div class="flex justify-between items-center">
                                <span class="text-sm font-medium text-gray-700">Overall Rating</span>
                                <span class="text-sm font-bold" :class="ratingColor(evaluationData.averages.overall_rating)">
                                    {{ evaluationData.averages.overall_rating || 0 }}/5
                                </span>
                            </div>
                            <div class="h-3 w-full bg-gray-200 rounded-full overflow-hidden">
                                <div
                                    class="h-full bg-gradient-to-r transition-all duration-500"
                                    :class="ratingBgColor(evaluationData.averages.overall_rating)"
                                    :style="{ width: `${((evaluationData.averages.overall_rating || 0) / 5) * 100}%` }"
                                ></div>
                            </div>
                        </div>

                        <div class="space-y-2">
                            <div class="flex justify-between items-center">
                                <span class="text-sm font-medium text-gray-700">Content Quality</span>
                                <span class="text-sm font-bold" :class="ratingColor(evaluationData.averages.content_quality)">
                                    {{ evaluationData.averages.content_quality || 0 }}/5
                                </span>
                            </div>
                            <div class="h-3 w-full bg-gray-200 rounded-full overflow-hidden">
                                <div
                                    class="h-full bg-gradient-to-r from-blue-400 to-blue-600 transition-all duration-500"
                                    :style="{ width: `${((evaluationData.averages.content_quality || 0) / 5) * 100}%` }"
                                ></div>
                            </div>
                        </div>

                        <div class="space-y-2">
                            <div class="flex justify-between items-center">
                                <span class="text-sm font-medium text-gray-700">Trainer Quality</span>
                                <span class="text-sm font-bold" :class="ratingColor(evaluationData.averages.trainer_quality)">
                                    {{ evaluationData.averages.trainer_quality || 0 }}/5
                                </span>
                            </div>
                            <div class="h-3 w-full bg-gray-200 rounded-full overflow-hidden">
                                <div
                                    class="h-full bg-gradient-to-r from-emerald-400 to-emerald-600 transition-all duration-500"
                                    :style="{ width: `${((evaluationData.averages.trainer_quality || 0) / 5) * 100}%` }"
                                ></div>
                            </div>
                        </div>

                        <div class="space-y-2">
                            <div class="flex justify-between items-center">
                                <span class="text-sm font-medium text-gray-700">Material Quality</span>
                                <span class="text-sm font-bold" :class="ratingColor(evaluationData.averages.material_quality)">
                                    {{ evaluationData.averages.material_quality || 0 }}/5
                                </span>
                            </div>
                            <div class="h-3 w-full bg-gray-200 rounded-full overflow-hidden">
                                <div
                                    class="h-full bg-gradient-to-r from-purple-400 to-purple-600 transition-all duration-500"
                                    :style="{ width: `${((evaluationData.averages.material_quality || 0) / 5) * 100}%` }"
                                ></div>
                            </div>
                        </div>

                        <div class="space-y-2">
                            <div class="flex justify-between items-center">
                                <span class="text-sm font-medium text-gray-700">Organization</span>
                                <span class="text-sm font-bold" :class="ratingColor(evaluationData.averages.organization)">
                                    {{ evaluationData.averages.organization || 0 }}/5
                                </span>
                            </div>
                            <div class="h-3 w-full bg-gray-200 rounded-full overflow-hidden">
                                <div
                                    class="h-full bg-gradient-to-r from-pink-400 to-pink-600 transition-all duration-500"
                                    :style="{ width: `${((evaluationData.averages.organization || 0) / 5) * 100}%` }"
                                ></div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Individual Evaluations -->
                <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm">
                    <div class="flex items-center gap-3 mb-6">
                        <div class="flex h-10 w-10 items-center justify-center rounded-full bg-indigo-100">
                            <MessageSquare class="h-5 w-5 text-indigo-600" />
                        </div>
                        <h2 class="text-lg font-semibold text-gray-900">Individual Feedback</h2>
                    </div>

                    <div v-if="evaluationData.evaluations.length === 0" class="text-center py-12 text-gray-500">
                        <MessageSquare class="h-16 w-16 mx-auto mb-4 text-gray-400" />
                        <h3 class="text-lg font-semibold text-gray-700 mb-2">No evaluations yet</h3>
                        <p>This session hasn't received any feedback yet.</p>
                    </div>

                    <div v-else class="space-y-6">
                        <div
                            v-for="evaluation in evaluationData.evaluations"
                            :key="evaluation.id"
                            class="border border-gray-200 rounded-xl p-6 hover:shadow-md transition-all duration-200"
                        >
                            <div class="flex items-start justify-between mb-4">
                                <div>
                                    <h3 class="font-semibold text-gray-900">{{ evaluation.trainee_name }}</h3>
                                    <p class="text-sm text-gray-500">{{ evaluation.submitted_at }}</p>
                                </div>
                                <div class="flex items-center gap-2">
                                    <Star class="h-5 w-5 text-yellow-500 fill-current" />
                                    <span class="text-lg font-bold text-gray-900">
                                        {{ evaluation.overall_rating }}/5
                                    </span>
                                </div>
                            </div>

                            <div class="grid grid-cols-2 md:grid-cols-5 gap-4 mb-4">
                                <div class="text-center p-3 bg-blue-50 rounded-lg">
                                    <div class="text-xs text-gray-600 mb-1">Content</div>
                                    <div class="text-lg font-bold text-blue-600">{{ evaluation.content_quality }}</div>
                                </div>
                                <div class="text-center p-3 bg-emerald-50 rounded-lg">
                                    <div class="text-xs text-gray-600 mb-1">Trainer</div>
                                    <div class="text-lg font-bold text-emerald-600">{{ evaluation.trainer_quality }}</div>
                                </div>
                                <div class="text-center p-3 bg-purple-50 rounded-lg">
                                    <div class="text-xs text-gray-600 mb-1">Materials</div>
                                    <div class="text-lg font-bold text-purple-600">{{ evaluation.material_quality }}</div>
                                </div>
                                <div class="text-center p-3 bg-pink-50 rounded-lg">
                                    <div class="text-xs text-gray-600 mb-1">Organization</div>
                                    <div class="text-lg font-bold text-pink-600">{{ evaluation.organization }}</div>
                                </div>
                                <div class="text-center p-3 bg-gray-50 rounded-lg">
                                    <div class="text-xs text-gray-600 mb-1">Difficulty</div>
                                    <span
                                        class="inline-flex rounded-full px-2 py-1 text-xs font-semibold capitalize"
                                        :class="difficultyBadgeClass(evaluation.difficulty_level)"
                                    >
                                        {{ evaluation.difficulty_level }}
                                    </span>
                                </div>
                            </div>

                            <div class="space-y-3">
                                <div class="flex items-center gap-2">
                                    <ThumbsUp
                                        class="h-4 w-4"
                                        :class="evaluation.would_recommend ? 'text-emerald-600' : 'text-gray-400'"
                                    />
                                    <span class="text-sm font-medium" :class="evaluation.would_recommend ? 'text-emerald-600' : 'text-gray-500'">
                                        {{ evaluation.would_recommend ? 'Would recommend this course' : 'Would not recommend this course' }}
                                    </span>
                                </div>

                                <div v-if="evaluation.strengths" class="bg-emerald-50 rounded-lg p-4 border border-emerald-100">
                                    <div class="text-sm font-semibold text-emerald-900 mb-2 flex items-center gap-2">
                                        <Award class="h-4 w-4" />
                                        Strengths
                                    </div>
                                    <p class="text-sm text-emerald-800">{{ evaluation.strengths }}</p>
                                </div>

                                <div v-if="evaluation.improvements" class="bg-amber-50 rounded-lg p-4 border border-amber-100">
                                    <div class="text-sm font-semibold text-amber-900 mb-2 flex items-center gap-2">
                                        <Target class="h-4 w-4" />
                                        Areas for Improvement
                                    </div>
                                    <p class="text-sm text-amber-800">{{ evaluation.improvements }}</p>
                                </div>

                                <div v-if="evaluation.comments" class="bg-blue-50 rounded-lg p-4 border border-blue-100">
                                    <div class="text-sm font-semibold text-blue-900 mb-2 flex items-center gap-2">
                                        <MessageSquare class="h-4 w-4" />
                                        Additional Comments
                                    </div>
                                    <p class="text-sm text-blue-800">{{ evaluation.comments }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </template>
        </div>
    </component>
</template>
