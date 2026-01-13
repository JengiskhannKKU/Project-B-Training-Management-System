<script setup>
import { ref, computed } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import TrainerLayout from '@/Layouts/TrainerLayout.vue';
import {
    Search,
    Calendar,
    BookOpen,
    Star,
    MessageSquare,
    ChevronRight,
    Filter
} from 'lucide-vue-next';

const props = defineProps({
    sessions: {
        type: Array,
        default: () => []
    }
});

const searchQuery = ref('');
const selectedStatus = ref('all');

const filteredSessions = computed(() => {
    let filtered = props.sessions || [];

    // Search filter
    if (searchQuery.value) {
        const query = searchQuery.value.toLowerCase();
        filtered = filtered.filter(session =>
            session.title?.toLowerCase().includes(query) ||
            session.course_name?.toLowerCase().includes(query)
        );
    }

    // Status filter
    if (selectedStatus.value !== 'all') {
        filtered = filtered.filter(session => session.status === selectedStatus.value);
    }

    return filtered;
});

const viewEvaluations = (sessionId) => {
    router.visit(`/sessions/${sessionId}/evaluation`);
};

const getStatusBadge = (status) => {
    const badges = {
        completed: 'bg-emerald-100 text-emerald-700',
        ongoing: 'bg-blue-100 text-blue-700',
        upcoming: 'bg-amber-100 text-amber-700',
        cancelled: 'bg-red-100 text-red-700',
    };
    return badges[status] || 'bg-gray-100 text-gray-700';
};

const formatDate = (date) => {
    if (!date) return 'N/A';
    return new Date(date).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric'
    });
};
</script>

<template>
    <Head title="Session Feedback - Trainer" />

    <TrainerLayout>
        <div class="space-y-6">
            <!-- Header -->
            <div>
                <h1 class="text-3xl font-bold text-gray-900">My Session Feedback</h1>
                <p class="mt-2 text-sm text-gray-600">View and analyze feedback for your training sessions</p>
            </div>

            <!-- Filters -->
            <div class="bg-white rounded-2xl border border-gray-200 p-6 shadow-sm">
                <div class="grid gap-4 md:grid-cols-2">
                    <!-- Search -->
                    <div class="relative">
                        <Search class="absolute left-3 top-1/2 -translate-y-1/2 h-5 w-5 text-gray-400" />
                        <input
                            v-model="searchQuery"
                            type="text"
                            placeholder="Search by session or course..."
                            class="w-full pl-10 pr-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-transparent"
                        />
                    </div>

                    <!-- Status Filter -->
                    <div class="relative">
                        <Filter class="absolute left-3 top-1/2 -translate-y-1/2 h-5 w-5 text-gray-400" />
                        <select
                            v-model="selectedStatus"
                            class="w-full pl-10 pr-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-transparent appearance-none bg-white"
                        >
                            <option value="all">All Status</option>
                            <option value="completed">Completed</option>
                            <option value="ongoing">Ongoing</option>
                            <option value="upcoming">Upcoming</option>
                            <option value="cancelled">Cancelled</option>
                        </select>
                    </div>
                </div>

                <!-- Results count -->
                <div class="mt-4 text-sm text-gray-600">
                    Showing <span class="font-semibold text-gray-900">{{ filteredSessions.length }}</span>
                    of <span class="font-semibold text-gray-900">{{ sessions.length }}</span> sessions
                </div>
            </div>

            <!-- Sessions List -->
            <div class="space-y-4">
                <div v-if="filteredSessions.length === 0"
                     class="bg-white rounded-2xl border border-gray-200 p-12 text-center shadow-sm">
                    <MessageSquare class="h-16 w-16 mx-auto mb-4 text-gray-400" />
                    <h3 class="text-lg font-semibold text-gray-900 mb-2">No sessions found</h3>
                    <p class="text-gray-600">Try adjusting your search or filters</p>
                </div>

                <div
                    v-for="session in filteredSessions"
                    :key="session.id"
                    class="bg-white rounded-2xl border border-gray-200 p-6 shadow-sm hover:shadow-md hover:border-teal-300 transition-all duration-200 cursor-pointer group"
                    @click="viewEvaluations(session.id)"
                >
                    <div class="flex items-start justify-between">
                        <div class="flex-1 space-y-3">
                            <!-- Title and Status -->
                            <div class="flex items-start gap-3">
                                <div class="flex-1">
                                    <h3 class="text-lg font-semibold text-gray-900 group-hover:text-teal-600 transition-colors">
                                        {{ session.title }}
                                    </h3>
                                    <div class="flex items-center gap-2 mt-1">
                                        <span
                                            class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-semibold capitalize"
                                            :class="getStatusBadge(session.status)"
                                        >
                                            {{ session.status }}
                                        </span>
                                    </div>
                                </div>
                                <ChevronRight class="h-5 w-5 text-gray-400 group-hover:text-teal-600 group-hover:translate-x-1 transition-all" />
                            </div>

                            <!-- Course Info -->
                            <div class="grid gap-4 md:grid-cols-2">
                                <div class="flex items-center gap-2 text-sm text-gray-600">
                                    <BookOpen class="h-4 w-4 text-gray-400 flex-shrink-0" />
                                    <span class="truncate">{{ session.course_name || 'N/A' }}</span>
                                </div>
                                <div class="flex items-center gap-2 text-sm text-gray-600">
                                    <Calendar class="h-4 w-4 text-gray-400 flex-shrink-0" />
                                    <span>{{ formatDate(session.start_date) }}</span>
                                </div>
                            </div>

                            <!-- Feedback Stats -->
                            <div class="flex items-center gap-6 pt-3 border-t border-gray-100">
                                <div class="flex items-center gap-2">
                                    <MessageSquare class="h-4 w-4 text-blue-600" />
                                    <span class="text-sm font-medium text-gray-900">
                                        {{ session.total_evaluations || 0 }}
                                        <span class="text-gray-600">feedback{{ session.total_evaluations !== 1 ? 's' : '' }}</span>
                                    </span>
                                </div>
                                <div v-if="session.average_rating" class="flex items-center gap-2">
                                    <Star class="h-4 w-4 text-yellow-500 fill-current" />
                                    <span class="text-sm font-medium text-gray-900">
                                        {{ session.average_rating.toFixed(1) }}
                                        <span class="text-gray-600">average</span>
                                    </span>
                                </div>
                                <div v-else class="text-sm text-gray-500">
                                    No ratings yet
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </TrainerLayout>
</template>
