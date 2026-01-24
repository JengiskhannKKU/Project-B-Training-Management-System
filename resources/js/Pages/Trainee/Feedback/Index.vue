<script setup>
import { ref, computed } from 'vue';
import TraineeLayout from '@/Layouts/TraineeLayout.vue';
import { Head } from '@inertiajs/vue3';
import { Calendar, User, CheckCircle, MessageSquare } from 'lucide-vue-next';
import FeedbackModal from '@/Components/FeedbackModal.vue';

const props = defineProps({
    sessions: {
        type: Array,
        default: () => []
    }
});

const showModal = ref(false);
const selectedSession = ref(null);

const availableSessions = computed(() => {
    return props.sessions.filter(s => !s.feedback_submitted);
});

const submittedSessions = computed(() => {
    return props.sessions.filter(s => s.feedback_submitted);
});

function openFeedbackModal(session) {
    selectedSession.value = session;
    showModal.value = true;
}

function closeModal() {
    showModal.value = false;
    selectedSession.value = null;
}
</script>

<template>
    <Head title="Course Feedback" />

    <TraineeLayout>
        <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
            <div class="mb-8">
                <h1 class="text-3xl font-bold text-gray-900">Course Feedback</h1>
                <p class="mt-2 text-sm text-gray-600">
                    Share your thoughts and help us improve our courses
                </p>
            </div>

            <!-- Sessions List -->
            <div v-if="availableSessions.length > 0" class="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
                <div
                    v-for="session in availableSessions"
                    :key="session.id"
                    class="rounded-lg border border-gray-200 bg-white p-6 shadow-sm transition hover:shadow-md"
                >
                    <div class="mb-4">
                        <h3 class="text-lg font-semibold text-gray-900">
                            {{ session.course_name }}
                        </h3>
                    </div>

                    <div class="space-y-2 text-sm text-gray-600">
                        <div class="flex items-center gap-2">
                            <User :size="16" class="text-gray-400" />
                            <span>{{ session.trainer_name }}</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <Calendar :size="16" class="text-gray-400" />
                            <span>{{ session.completed_date }}</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <CheckCircle :size="16" class="text-emerald-500" />
                            <span class="font-medium text-emerald-600">
                                Attendance: {{ session.attendance_rate }}%
                            </span>
                        </div>
                    </div>

                    <div class="mt-6">
                        <button
                            @click="openFeedbackModal(session)"
                            class="flex w-full items-center justify-center gap-2 rounded-lg bg-emerald-600 px-4 py-2.5 text-sm font-medium text-white transition hover:bg-emerald-700"
                        >
                            <MessageSquare :size="16" />
                            Give Feedback
                        </button>
                    </div>
                </div>
            </div>

            <!-- Empty State -->
            <div v-else class="rounded-lg border border-gray-200 bg-white p-12 text-center shadow-sm">
                <div class="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-full bg-gray-100">
                    <MessageSquare :size="32" class="text-gray-400" />
                </div>
                <h3 class="mb-2 text-lg font-semibold text-gray-900">No courses available for feedback</h3>
                <p class="text-sm text-gray-600">
                    Complete your courses with at least 80% attendance to provide feedback
                </p>
            </div>

            <!-- Submitted Feedback List -->
            <div v-if="submittedSessions.length > 0" class="mt-8">
                <h2 class="mb-4 text-xl font-semibold text-gray-900">Feedback Submitted</h2>
                <div class="space-y-3">
                    <div
                        v-for="session in submittedSessions"
                        :key="`submitted-${session.id}`"
                        class="flex items-center justify-between rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3"
                    >
                        <div class="flex items-center gap-3">
                            <CheckCircle :size="20" class="text-emerald-600" />
                            <div>
                                <p class="font-medium text-gray-900">{{ session.course_name }}</p>
                                <p class="text-sm text-gray-600">{{ session.trainer_name }}</p>
                            </div>
                        </div>
                        <span class="text-sm font-medium text-emerald-600">Feedback Submitted</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Feedback Modal -->
        <FeedbackModal
            :show="showModal"
            :session="selectedSession"
            @close="closeModal"
        />
    </TraineeLayout>
</template>

