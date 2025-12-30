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
 * Filter sessions to show only those eligible for attendance checking:
 * - Status must be 'open' or 'closed'
 * - End date must have passed (or no end_date set)
 */
const eligibleSessions = computed(() => {
    return props.sessions.filter((session) => {
        const statusLower = session.status?.toLowerCase();
        const isValidStatus = statusLower === 'open' || statusLower === 'closed';

        if (!isValidStatus) return false;

        // If no end_date, allow attendance check based on status only
        if (!session.end_date) return true;

        // Check if end_date has passed
        const today = new Date();
        today.setHours(0, 0, 0, 0);
        const endDate = new Date(session.end_date);
        endDate.setHours(0, 0, 0, 0);

        return endDate <= today;
    });
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
                        Sessions must be 'open' or 'closed' status and past their end date to check attendance.
                    </p>
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
