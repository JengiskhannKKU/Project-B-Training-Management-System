<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { X, Calendar, Clock, MapPin } from 'lucide-vue-next';

interface Session {
    id: number;
    name: string;
    date: string;
    time: string;
    location?: string;
}

interface Props {
    show: boolean;
    courseId: number;
    courseName: string;
    sessions: Session[];
    baseUrl?: string;
}

const props = withDefaults(defineProps<Props>(), {
    baseUrl: '/admin/attendance'
});

const emit = defineEmits<{
    close: [];
}>();

const handleClose = () => {
    emit('close');
};
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
                <div v-if="sessions.length > 0" class="space-y-4">
                    <Link
                        v-for="session in sessions"
                        :key="session.id"
                        :href="`${props.baseUrl}/${courseId}/${session.id}`"
                        class="block rounded-lg border border-gray-200 bg-white p-4 shadow-sm hover:shadow-md hover:border-[#2f837d] transition-all cursor-pointer"
                    >
                        <h3 class="text-lg font-semibold text-gray-900 mb-3">
                            {{ session.name }}
                        </h3>

                        <div class="space-y-2 text-sm text-gray-600">
                            <div class="flex items-center gap-2">
                                <Calendar :size="16" class="text-[#2f837d]" />
                                <span>{{ session.date }}</span>
                            </div>

                            <div class="flex items-center gap-2">
                                <Clock :size="16" class="text-[#2f837d]" />
                                <span>{{ session.time }}</span>
                            </div>

                            <div v-if="session.location" class="flex items-center gap-2">
                                <MapPin :size="16" class="text-[#2f837d]" />
                                <span>{{ session.location }}</span>
                            </div>
                        </div>
                    </Link>
                </div>

                <!-- Empty State -->
                <div v-else class="text-center py-12">
                    <Calendar class="mx-auto h-12 w-12 text-gray-400" />
                    <h3 class="mt-2 text-sm font-medium text-gray-900">
                        No sessions available
                    </h3>
                    <p class="mt-1 text-sm text-gray-500">
                        There are no sessions scheduled for this course yet.
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
