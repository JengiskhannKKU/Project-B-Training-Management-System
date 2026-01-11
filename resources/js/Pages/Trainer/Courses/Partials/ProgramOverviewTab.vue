<script setup lang="ts">
import { computed } from 'vue';

const props = defineProps<{
    program: {
        id?: number;
        name?: string;
        title?: string;
        code?: string;
        category?: string;
        level?: string;
        period?: string;
        time?: string;
        location?: string;
        trainer?: string;
        certificated?: string;
        status?: string;
        description?: string;
        image_url?: string | null;
        approval_status?: string;
        learning_outcomes?: string;
        target_audience?: string;
        prerequisites?: string;
        additional_info?: string;
        min_participants?: number;
        max_participants?: number;
        sessions_count?: number;
    };
    sessionsCount?: number;
    isAdmin?: boolean;
}>();

const emit = defineEmits<{
    'create-session': [];
}>();

const programData = computed(() => ({
    name: props.program?.name || props.program?.title || 'Program',
    description: props.program?.description || 'No description provided.',
    category: props.program?.category || 'General',
    level: props.program?.level || 'Beginner',
    period: props.program?.period || '—',
    time: props.program?.time || '—',
    location: props.program?.location || '—',
    trainer: props.program?.trainer || '—',
    certificated: props.program?.certificated || '—',
    status: props.program?.status || 'pending',
    approval_status: props.program?.approval_status || 'pending',
    learning_outcomes: props.program?.learning_outcomes || '',
    target_audience: props.program?.target_audience || '',
    prerequisites: props.program?.prerequisites || '',
    additional_info: props.program?.additional_info || '',
    min_participants: props.program?.min_participants || 1,
    max_participants: props.program?.max_participants || 20,
}));

const statusStyles = computed(() => {
    const s = programData.value.approval_status;
    if (s === 'approved') return { text: 'Approved', class: 'text-green-700', dot: 'bg-green-500' };
    if (s === 'rejected') return { text: 'Rejected', class: 'text-red-700', dot: 'bg-red-500' };
    return { text: 'Pending', class: 'text-amber-700', dot: 'bg-amber-500' };
});

const isIncomplete = computed(() => {
    const sessionsCount = props.sessionsCount ?? props.program?.sessions_count ?? 0;
    return props.program?.status === 'published' && sessionsCount === 0;
});

const learningOutcomesArray = computed(() => {
    if (!programData.value.learning_outcomes) return [];
    return programData.value.learning_outcomes
        .split('\n')
        .map(line => line.trim())
        .filter(line => line.length > 0);
});

const targetAudienceArray = computed(() => {
    if (!programData.value.target_audience) return [];
    return programData.value.target_audience
        .split('\n')
        .map(line => line.trim())
        .filter(line => line.length > 0);
});

const getLevelBadgeClass = computed(() => {
    const level = programData.value.level.toLowerCase();
    if (level === 'beginner') return 'bg-emerald-100 text-emerald-700';
    if (level === 'intermediate') return 'bg-amber-100 text-amber-700';
    if (level === 'advanced') return 'bg-red-100 text-red-700';
    return 'bg-gray-100 text-gray-700';
});
</script>

<template>
    <div class="space-y-6">
        <!-- Incomplete Course Banner -->
        <div v-if="isIncomplete" class="rounded-xl border-2 border-amber-200 bg-amber-50 p-6">
            <div class="flex items-start gap-4">
                <div class="rounded-full bg-amber-100 p-3">
                    <svg class="h-6 w-6 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                    </svg>
                </div>
                <div class="flex-1">
                    <h3 class="text-lg font-semibold text-amber-900 mb-2">This Course is Incomplete</h3>
                    <p class="text-sm text-amber-700 mb-4">
                        This course is published but has no sessions yet. It will not be visible to trainees until at least one session is created. Please navigate to the <strong>Sessions</strong> tab to create your first session.
                    </p>
                    <button
                        v-if="isAdmin"
                        @click="emit('create-session')"
                        class="bg-amber-600 hover:bg-amber-700 text-white px-6 py-2.5 rounded-lg font-medium transition-all flex items-center gap-2 shadow-sm hover:shadow-md"
                    >
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                        </svg>
                        <span>Create First Session</span>
                    </button>
                </div>
            </div>
        </div>

        <div :class="['grid gap-4 sm:gap-6', 'lg:grid-cols-3']">
            <!-- Main Content -->
            <div class="lg:col-span-2">
                <div class="rounded-lg bg-white p-6 shadow-sm">
                    <h2 class="mb-4 text-lg font-semibold text-gray-900">Description</h2>
                    <p class="text-gray-600 whitespace-pre-wrap">
                        {{ programData.description }}
                    </p>

                    <!-- Learning Outcomes -->
                    <div v-if="learningOutcomesArray.length > 0" class="mt-6">
                        <h3 class="mb-3 font-semibold text-gray-900">What You'll Learn</h3>
                        <ul class="space-y-2 text-gray-600">
                            <li v-for="(outcome, index) in learningOutcomesArray" :key="index" class="flex items-start gap-2">
                                <span class="text-teal-600 mt-1">•</span>
                                <span>{{ outcome }}</span>
                            </li>
                        </ul>
                    </div>

                    <!-- Target Audience -->
                    <div v-if="targetAudienceArray.length > 0" class="mt-6">
                        <h3 class="mb-3 font-semibold text-gray-900">Who Should Attend</h3>
                        <ul class="space-y-2 text-gray-600">
                            <li v-for="(audience, index) in targetAudienceArray" :key="index" class="flex items-start gap-2">
                                <span class="text-teal-600 mt-1">•</span>
                                <span>{{ audience }}</span>
                            </li>
                        </ul>
                    </div>

                    <!-- Prerequisites -->
                    <div v-if="programData.prerequisites" class="mt-6">
                        <h3 class="mb-3 font-semibold text-gray-900">Prerequisites</h3>
                        <p class="text-gray-600 whitespace-pre-wrap">{{ programData.prerequisites }}</p>
                    </div>

                    <!-- Additional Information -->
                    <div v-if="programData.additional_info" class="mt-6">
                        <h3 class="mb-3 font-semibold text-gray-900">Additional Information</h3>
                        <p class="text-gray-600 whitespace-pre-wrap">{{ programData.additional_info }}</p>
                    </div>
                </div>
            </div>

        <!-- Sidebar -->
        <div class="space-y-6">
            <!-- Course Information -->
            <div class="rounded-lg bg-white p-6 shadow-sm">
                <div class="mb-4 flex items-center justify-between">
                    <h3 class="font-semibold text-gray-900">Course Information</h3>
                    <button class="text-teal-600 hover:text-teal-700">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                        </svg>
                    </button>
                </div>

                <div class="space-y-3 text-sm">
                    <div>
                        <div class="text-gray-500">Category:</div>
                        <div class="font-medium text-gray-900">{{ programData.category }}</div>
                    </div>
                    <div>
                        <div class="text-gray-500">Level:</div>
                        <span :class="['rounded px-2 py-1 text-xs font-medium', getLevelBadgeClass]">{{ programData.level }}</span>
                    </div>
                    <div>
                        <div class="text-gray-500">Class Capacity:</div>
                        <div class="font-medium text-gray-900">{{ programData.min_participants }} - {{ programData.max_participants }} participants</div>
                    </div>
                    <div v-if="programData.period && programData.period !== '—'">
                        <div class="text-gray-500">Period:</div>
                        <div class="font-medium text-gray-900">{{ programData.period }}</div>
                    </div>
                    <div v-if="programData.time && programData.time !== '—'">
                        <div class="text-gray-500">Time:</div>
                        <div class="font-medium text-gray-900">{{ programData.time }}</div>
                    </div>
                    <div v-if="programData.location && programData.location !== '—'">
                        <div class="text-gray-500">Location:</div>
                        <div class="font-medium text-gray-900">{{ programData.location }}</div>
                    </div>
                    <div v-if="programData.trainer && programData.trainer !== '—'">
                        <div class="text-gray-500">Trainer:</div>
                        <div class="font-medium text-gray-900">{{ programData.trainer }}</div>
                    </div>
                    <div>
                        <div class="text-gray-500">Status:</div>
                        <div class="flex items-center gap-1">
                            <div :class="['h-2 w-2 rounded-full', statusStyles.dot]"></div>
                            <span :class="['font-medium', statusStyles.class]">{{ statusStyles.text }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Instructor -->
            <div class="rounded-lg bg-white p-6 shadow-sm">
                <h3 class="mb-4 font-semibold text-gray-900">Instructor</h3>

                <div class="flex items-start gap-3">
                    <img src="https://ui-avatars.com/api/?name=สบาย+โจ๊ก&background=0D8ABC&color=fff" alt="Instructor" class="h-16 w-16 rounded-full" />
                    <div class="flex-1">
                        <div class="font-medium text-gray-900">สบาย โจ๊ก</div>
                        <div class="mb-2 flex items-center gap-1">
                            <span class="text-lg font-semibold text-gray-900">4.8</span>
                            <svg class="h-4 w-4 text-yellow-400" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                            </svg>
                        </div>
                        <div class="text-xs text-gray-500">(16,124 reviews)</div>
                    </div>
                    <div class="flex h-12 w-12 items-center justify-center rounded-full bg-teal-100">
                        <span class="text-sm font-semibold text-teal-700">85%</span>
                    </div>
                </div>
                <div class="mt-2 text-xs text-gray-500">Positive</div>
            </div>

            <!-- Course URL -->
            <div class="rounded-lg bg-white p-6 shadow-sm">
                <h3 class="mb-4 font-semibold text-gray-900">Course URL</h3>

                <div class="mb-4 flex items-center gap-2 rounded-lg bg-gray-50 p-3">
                    <input type="text" value="https://example.com/courses/ux*de" readonly class="flex-1 border-0 bg-transparent text-sm text-gray-600 focus:ring-0" />
                    <button class="text-gray-400 hover:text-gray-600">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                        </svg>
                    </button>
                </div>

                <div class="grid grid-cols-3 gap-3">
                    <button class="flex flex-col items-center gap-1 rounded-lg border border-gray-200 p-3 hover:bg-gray-50">
                        <svg class="h-5 w-5 text-teal-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z"/>
                        </svg>
                        <span class="text-xs text-gray-600">Share</span>
                    </button>
                    <button class="flex flex-col items-center gap-1 rounded-lg border border-gray-200 p-3 hover:bg-gray-50">
                        <svg class="h-5 w-5 text-teal-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                        </svg>
                        <span class="text-xs text-gray-600">Email</span>
                    </button>
                    <button class="flex flex-col items-center gap-1 rounded-lg border border-gray-200 p-3 hover:bg-gray-50">
                        <svg class="h-5 w-5 text-teal-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"/>
                        </svg>
                        <span class="text-xs text-gray-600">QR Code</span>
                    </button>
                </div>
            </div>
        </div>
        </div>
    </div>
</template>
