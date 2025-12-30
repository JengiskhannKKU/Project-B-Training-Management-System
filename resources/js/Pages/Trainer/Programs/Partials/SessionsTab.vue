<script setup lang="ts">
import { Search, ListFilter, ArrowDownNarrowWide, Share, Award } from 'lucide-vue-next';

defineProps<{
    sessions: Array<{
        id: number | string;
        display_id?: string;
        date: string;
        time: string;
        session: string;
        location: string;
        capacity: string;
        status: string;
    }>;
}>();

const emit = defineEmits<{
    'add-session': [];
    'edit-session': [session: any];
    'delete-session': [session: any];
}>();

/**
 * Get badge styling for session status
 * Statuses: upcoming, open, closed, completed, cancelled
 */
const getStatusBadgeClass = (status: string) => {
    const statusLower = status.toLowerCase();

    switch (statusLower) {
        case 'upcoming':
            return 'bg-blue-100 text-blue-700';
        case 'open':
            return 'bg-teal-100 text-teal-700';
        case 'closed':
            return 'bg-amber-100 text-amber-700';
        case 'completed':
            return 'bg-purple-100 text-purple-700';
        case 'cancelled':
            return 'bg-red-100 text-red-700';
        default:
            return 'bg-gray-100 text-gray-700';
    }
};
</script>

<template>
    <div class="space-y-4">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div class="flex items-center gap-2">
                <svg class="h-5 w-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                </svg>
                <h2 class="text-base sm:text-lg font-semibold text-gray-900">Sessions ({{ sessions.length }})</h2>
            </div>
            <div class="flex flex-col sm:flex-row gap-4 flex-1 sm:flex-initial">
                <!-- Search Bar -->
                <div class="relative flex-1 max-w-md">
                    <input type="text" placeholder="Search sessions..." class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#2f837d] focus:border-transparent" />
                    <Search class="absolute left-3 top-2.5 h-5 w-5 text-gray-400" />
                </div>

                <div class="flex flex-wrap gap-2">
                    <!-- Filter button -->
                    <button class="rounded-lg border border-[#d5dde7] inline-flex gap-2 items-center px-4 py-2 hover:bg-gray-50 transition-colors">
                        <ListFilter class="h-4 w-4" />
                        <p>Filter</p>
                    </button>

                    <!-- Sort button -->
                    <button class="rounded-lg border border-[#d5dde7] inline-flex gap-2 items-center px-4 py-2 hover:bg-gray-50 transition-colors">
                        <ArrowDownNarrowWide class="h-4 w-4" />
                        <p>Sort</p>
                    </button>

                    <!-- Export button -->
                    <button class="rounded-lg border border-[#d5dde7] inline-flex gap-2 items-center px-4 py-2 hover:bg-gray-50 transition-colors">
                        <Share class="h-4 w-4" />
                        <p>Export</p>
                    </button>

                    <button @click="emit('add-session')" class="flex items-center gap-2 rounded-lg bg-teal-600 px-4 py-2 text-sm font-medium text-white hover:bg-teal-700">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                        </svg>
                        Add Sessions
                    </button>
                </div>
            </div>
        </div>

        <!-- Sessions Table -->
        <div class="rounded-lg bg-white shadow-sm overflow-x-auto">
            <table class="w-full min-w-[640px]">
                <thead class="border-b border-gray-200 bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500">ID</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500">Date</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500">Time</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500">Session</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500">Location</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500">Capacity</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500">Status</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    <tr v-for="session in sessions" :key="session.id" class="hover:bg-gray-50">
                        <td class="px-4 py-3 text-sm text-gray-900">{{ session.display_id || session.id }}</td>
                        <td class="px-4 py-3 text-sm text-gray-600">{{ session.date }}</td>
                        <td class="px-4 py-3 text-sm text-gray-600">{{ session.time }}</td>
                        <td class="px-4 py-3 text-sm text-gray-900">{{ session.session }}</td>
                        <td class="px-4 py-3 text-sm text-gray-600">{{ session.location }}</td>
                        <td class="px-4 py-3 text-sm">
                            <span :class="[
                                'rounded-full px-2 py-1 text-xs font-medium',
                                session.capacity.split('/')[0] === session.capacity.split('/')[1] ? 'bg-red-100 text-red-700' : 'bg-blue-100 text-blue-700'
                            ]">
                                {{ session.capacity }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-sm">
                            <div class="flex items-center gap-2">
                                <span :class="[
                                    'rounded-full px-3 py-1 text-xs font-medium inline-flex items-center gap-1.5',
                                    getStatusBadgeClass(session.status)
                                ]">
                                    {{ session.status }}
                                </span>
                                <Award
                                    v-if="session.status.toLowerCase() === 'completed'"
                                    :size="16"
                                    class="text-purple-600"
                                    title="Ready for certificate phase"
                                />
                            </div>
                        </td>
                        <td class="px-4 py-3">
                            <div class="flex gap-2">
                                <button @click="emit('delete-session', session)" class="text-gray-400 hover:text-red-600">
                                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                    </svg>
                                </button>
                                <button @click="emit('edit-session', session)" class="text-gray-400 hover:text-teal-600">
                                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                    </svg>
                                </button>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <div class="flex items-center justify-between gap-2">
            <button class="rounded-lg border border-gray-300 px-3 sm:px-4 py-2 text-xs sm:text-sm font-medium text-gray-700 hover:bg-gray-50">Previous</button>
            <span class="text-xs sm:text-sm text-gray-600">Page 1 of 10</span>
            <button class="rounded-lg border border-gray-300 px-3 sm:px-4 py-2 text-xs sm:text-sm font-medium text-gray-700 hover:bg-gray-50">Next</button>
        </div>
    </div>
</template>
