<script setup lang="ts">
import { Search, ListFilter, ArrowDownNarrowWide, Share, ChevronDown } from 'lucide-vue-next';
import { computed } from 'vue';

const props = defineProps<{
    trainees: Array<{
        id: number;
        name: string;
        email: string;
        employee_id: string;
        enrolled_date: string;
        department: string;
        role: string;
        certificate_status: string;
        avatar: string;
        session_id?: number | string;
    }>;
    sessions: Array<{
        id: number | string;
        session: string;
        date: string;
        time: string;
        display_id?: string;
    }>;
    getCertificateSelectColor: (status: string) => string;
    isAdmin: boolean;
}>();

const emit = defineEmits<{
    'add-trainee': [];
    'remove-trainee': [trainee: any];
    'update-certificate': [trainee: any];
}>();

const groupedTrainees = computed(() => {
    const groups: Record<string, { session: any, trainees: typeof props.trainees }> = {};
    
    // Initialize groups for all sessions to ensure empty sessions are visible (optional, but good for completeness)
    // Or maybe we only want to show sessions that have trainees? 
    // "For the Trainee tab, it should display which trainees are enrolled in each session, grouped by session."
    // Usually implies showing the session header.
    
    // Let's populate groups based on available sessions first
    if (props.sessions) {
        props.sessions.forEach(session => {
            groups[session.id] = {
                session: session,
                trainees: []
            };
        });
    }

    // Distribute trainees
    props.trainees.forEach(trainee => {
        const sessionId = trainee.session_id;
        if (sessionId && groups[sessionId]) {
            groups[sessionId].trainees.push(trainee);
        } else {
            // Handle unassigned or unknown session
            if (!groups['unassigned']) {
                groups['unassigned'] = {
                    session: { session: 'Unassigned', display_id: '-', date: '', time: '' },
                    trainees: []
                };
            }
            groups['unassigned'].trainees.push(trainee);
        }
    });

    // Filter out sessions with no trainees if desired?
    // User said "display which trainees are enrolled in each session". 
    // If a session has no trainees, maybe we shouldn't show it?
    // But "review and manage trainee information" might imply seeing empty sessions too.
    // I'll filter out empty groups to reduce clutter, or keep them?
    // Let's keep only groups that have trainees for now, or maybe all?
    // Let's filter out empty groups for better UI, unless the user specifically wants to see empty buckets.
    // Actually, seeing empty sessions tells you "no one is here". That's useful. I'll keep them.
    
    // BUT, the mock data has `session_id` 1, 2, 3, 4.
    // I need to make sure `sessions` prop has matching IDs.
    
    return groups;
});
</script>

<template>
    <div class="space-y-8">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-4">
            <div class="flex items-center gap-2">
                <svg class="h-5 w-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
                </svg>
                <h2 class="text-sm sm:text-base lg:text-lg font-semibold text-gray-900">ENROLLED TRAINEES ({{ trainees.length }})</h2>
            </div>
            <button v-if="isAdmin" @click="emit('add-trainee')" class="flex items-center justify-center gap-2 rounded-lg bg-teal-600 px-3 sm:px-4 py-2 text-sm font-medium text-white hover:bg-teal-700 w-full sm:w-auto">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                Add Manually
            </button>
        </div>

        <div class="flex flex-col sm:flex-row gap-4 items-start sm:items-center mb-4">
            <!-- Search Bar -->
            <div class="relative flex-1 max-w-md w-full">
                <input type="text" placeholder="Search trainees..." class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#2f837d] focus:border-transparent" />
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
            </div>
        </div>

        <div v-for="(group, sessionId) in groupedTrainees" :key="sessionId" class="space-y-2">
            <!-- Session Header -->
            <div class="flex items-center justify-between bg-gray-100 rounded-t-xl px-4 py-3 border border-gray-200 border-b-0">
                <div class="flex items-center gap-3">
                    <div class="bg-teal-100 text-teal-800 text-xs font-bold px-2 py-1 rounded uppercase">
                        {{ group.session.display_id || 'Session' }}
                    </div>
                    <h3 class="text-sm font-bold text-gray-800">{{ group.session.session }}</h3>
                    <span class="text-xs text-gray-500 hidden sm:inline-block">
                        {{ group.session.date }} • {{ group.session.time }}
                    </span>
                </div>
                <div class="text-xs font-medium text-gray-500">
                    {{ group.trainees.length }} Trainees
                </div>
            </div>

            <!-- Trainees List -->
            <div class="bg-white rounded-b-xl rounded-t-none shadow-sm border border-[#dfe5ef] overflow-hidden -mt-2">
                <div v-if="group.trainees.length === 0" class="p-4 text-center text-sm text-gray-500">
                    No trainees enrolled in this session.
                </div>
                <div v-else class="divide-y divide-gray-200">
                    <div
                        v-for="(trainee, index) in group.trainees"
                        :key="trainee.id"
                        :class="[
                            'px-3 sm:px-4 py-4 transition-colors',
                            index % 2 === 0 ? 'bg-white' : 'bg-gray-50',
                            'hover:bg-gray-100'
                        ]"
                    >
                        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
                            <div class="flex items-center gap-3 sm:gap-4 flex-1 min-w-0">
                                <img :src="trainee.avatar" :alt="trainee.name" class="h-10 w-10 sm:h-12 sm:w-12 rounded-full flex-shrink-0" />
                                <div class="min-w-0 flex-1">
                                    <div class="font-medium text-sm sm:text-base text-gray-900 truncate">{{ trainee.name }}</div>
                                    <div class="text-xs sm:text-sm text-gray-500 truncate">{{ trainee.email }}</div>
                                    <div class="text-xs text-gray-400">ID: {{ trainee.employee_id }}</div>
                                </div>
                            </div>

                            <div class="flex flex-wrap lg:flex-nowrap items-center gap-3 sm:gap-4 lg:gap-6">
                                <div class="text-xs sm:text-sm">
                                    <div class="text-gray-500 mb-1">Enrolled</div>
                                    <div class="font-medium text-gray-900">{{ trainee.enrolled_date }}</div>
                                </div>

                                <div class="text-xs sm:text-sm hidden sm:block">
                                    <div class="text-gray-500 mb-1">Department</div>
                                    <div class="font-medium text-gray-900">{{ trainee.department }}</div>
                                </div>

                                <div class="text-xs sm:text-sm hidden md:block">
                                    <div class="text-gray-500 mb-1">Roles</div>
                                    <div class="font-medium text-gray-900">{{ trainee.role }}</div>
                                </div>

                                <div class="text-xs sm:text-sm">
                                    <div class="text-gray-500 mb-1">Certificates</div>
                                    <div v-if="isAdmin" class="relative">
                                        <ChevronDown class="absolute left-2 sm:left-2.5 top-1/2 -translate-y-1/2 h-3 w-3 pointer-events-none z-10" />
                                        <select v-model="trainee.certificate_status" @change="emit('update-certificate', trainee)" :class="['rounded-lg border pl-6 sm:pl-7 pr-2 sm:pr-3 py-1 sm:py-1.5 text-xs font-medium text-center focus:outline-none focus:ring-2 focus:ring-teal-500 appearance-none', getCertificateSelectColor(trainee.certificate_status)]" style="background-image: none;">
                                            <option value="Approved">Approved</option>
                                            <option value="Pending">Pending</option>
                                            <option value="Not Eligible">Not Eligible</option>
                                        </select>
                                    </div>
                                    <div v-else :class="['rounded-lg border px-3 py-1.5 text-xs font-medium text-center', getCertificateSelectColor(trainee.certificate_status)]">
                                        {{ trainee.certificate_status }}
                                    </div>
                                </div>

                                <button v-if="isAdmin" @click="emit('remove-trainee', trainee)" class="text-red-600 hover:text-red-700 p-1">
                                    <svg class="h-4 w-4 sm:h-5 sm:w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                    </svg>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div v-if="!sessions || sessions.length === 0" class="text-center py-10 bg-gray-50 rounded-xl border border-gray-200 border-dashed">
            <p class="text-gray-500">No sessions available to display trainees.</p>
        </div>
    </div>
</template>
