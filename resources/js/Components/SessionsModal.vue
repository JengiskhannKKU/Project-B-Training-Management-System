<script setup lang="ts">
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';
import { X, Calendar, Clock, MapPin } from 'lucide-vue-next';

interface Session {
    id: number;
    name: string;
    date: string;
    time: string;
    location?: string;
    status?: string;
    end_date?: string;
}

interface Props {
    show: boolean;
    courseId: number;
    courseName: string;
    sessions: Session[];
    baseUrl?: string;
}

const props = withDefaults(defineProps<Props>(), {
    baseUrl: '/admin'
});

const emit = defineEmits<{
    close: [];
}>();

const handleClose = () => {
    emit('close');
};

/**
 * Filter sessions for attendance display:
 * - Show open/closed/completed/upcoming sessions
 * - Hide cancelled sessions
 */
const eligibleSessions = computed(() => {
    return props.sessions.filter((session) => {
        const statusLower = session.status?.toLowerCase();
        if (!statusLower) return true;
        if (statusLower === 'cancelled') return false;
        return ['open', 'closed', 'completed', 'upcoming'].includes(statusLower);
    });
});

const programBaseUrl = computed(() => {
    if (props.baseUrl === '/admin') {
        return '/admin/my-courses';
    }

    if (props.baseUrl === '/trainer') {
        return '/trainer/programs';
    }

    return `${props.baseUrl}/programs`;
});
</script>

<template>
    <div
        v-if="show"
        class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50 p-4"
        @click.self="handleClose"
    >
        <div class="relative max-h-[90vh] w-full max-w-2xl overflow-y-auto rounded-lg bg-white shadow-xl">
            <!-- Header -->
            <div class="sticky top-0 z-10 border-b bg-white px-6 py-4">
                <div class="flex items-center justify-between">
                    <div class="flex-1">
                        <h2 class="text-xl font-bold text-gray-900">
                            Course Sessions
                        </h2>
                        <p class="mt-1 text-sm text-gray-500">
                            {{ courseName }}
                        </p>
                    </div>
                    <button
                        @click="handleClose"
                        class="rounded-lg p-2 hover:bg-gray-100 transition-colors"
                    >
                        <X :size="20" />
                    </button>
                </div>
            </div>

            <!-- Sessions List -->
            <div class="p-6">
                <div v-if="eligibleSessions.length > 0" class="space-y-3">
                    <Link
                        v-for="session in eligibleSessions"
                        :key="session.id"
                        :href="`${props.baseUrl}/${courseId}/sessions/${session.id}/attendance`"
                        class="flex items-center justify-between px-4 py-3 rounded-lg border border-gray-200 bg-white hover:bg-gray-50 hover:border-[#2f837d] transition-all cursor-pointer"
                    >
                        <div class="flex items-center gap-3">
                            <Calendar :size="18" class="text-[#2f837d]" />
                            <span class="font-medium text-gray-900">{{ session.name }}</span>
                        </div>
                        <div class="flex items-center gap-2 text-sm text-gray-600">
                            <Clock :size="16" class="text-gray-400" />
                            <span>{{ session.date }} | {{ session.time }}</span>
                        </div>
                    </Link>
                </div>

                <!-- Empty State -->
                <div v-else class="text-center py-12">
                    <Calendar class="mx-auto h-12 w-12 text-gray-400" />
                    <h3 class="mt-2 text-sm font-medium text-gray-900">
                        No sessions available for attendance
                    </h3>
                    <p class="mt-1 text-sm text-gray-500">
                        No sessions are available to display for this course yet.
                    </p>
                    <Link
                        :href="`${programBaseUrl}/${courseId}`"
                        class="mt-4 inline-flex items-center gap-2 rounded-lg bg-[#2f837d] px-4 py-2.5 text-sm font-medium text-white hover:bg-[#26685f] transition-colors"
                    >
                        Create Session
                    </Link>
                </div>
            </div>

            <!-- Footer -->
            <div class="sticky bottom-0 border-t bg-white px-6 py-4">
                <button
                    @click="handleClose"
                    class="w-full rounded-lg bg-[#2f837d] px-4 py-2.5 text-sm font-medium text-white hover:bg-[#26685f] transition-colors"
                >
                    Close
                </button>
            </div>
        </div>
    </div>
</template>
