<script setup lang="ts">
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';
import { Plus, User, Calendar, MapPin, Clock, Users, BookOpen, AlertCircle } from 'lucide-vue-next';

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
        trainer_email?: string;
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
        created_at?: string;
    };
    sessionsCount?: number;
    isAdmin?: boolean;
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
    trainer_email: props.program?.trainer_email || '',
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
        <!-- Incomplete Course Banner (Admin Only) -->
        <div v-if="isIncomplete && isAdmin" class="rounded-xl border-2 border-amber-200 bg-amber-50 p-6">
            <div class="flex items-start gap-4">
                <div class="rounded-full bg-amber-100 p-3">
                    <svg class="h-6 w-6 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                    </svg>
                </div>
                <div class="flex-1">
                    <h3 class="text-lg font-semibold text-amber-900 mb-2">This Course is Incomplete</h3>
                    <p class="text-sm text-amber-700 mb-4">
                        This course is published but has no sessions yet. It will not be visible to trainees until at least one session is created. Please create your first session to make this course available.
                    </p>
                    <Link
                        :href="`/admin/sessions?create_session=true&course_id=${program.id}`"
                        class="inline-flex items-center px-4 py-2 bg-orange-600 hover:bg-orange-700 text-white rounded-lg text-sm font-medium transition-colors"
                    >
                        <Plus class="w-4 h-4 mr-2" />
                        <span>Create First Session</span>
                    </Link>
                </div>
            </div>
        </div>

        <div :class="['grid gap-4 sm:gap-6', 'lg:grid-cols-3']">
            <!-- Main Content -->
            <div class="lg:col-span-2 space-y-6">
                <!-- Learning Outcomes -->
                <div v-if="learningOutcomesArray.length > 0" class="rounded-xl bg-white p-6 shadow-sm border border-gray-100">
                    <h3 class="mb-4 text-lg font-semibold text-gray-900 flex items-center gap-2">
                        <svg class="h-5 w-5 text-teal-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        What You'll Learn
                    </h3>
                    <ul class="space-y-3 text-gray-700">
                        <li v-for="(outcome, index) in learningOutcomesArray" :key="index" class="flex items-start gap-3">
                            <div class="mt-0.5 flex-shrink-0">
                                <div class="h-5 w-5 rounded-full bg-teal-100 flex items-center justify-center">
                                    <svg class="h-3 w-3 text-teal-600" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                    </svg>
                                </div>
                            </div>
                            <span class="flex-1">{{ outcome }}</span>
                        </li>
                    </ul>
                </div>

                <!-- Target Audience -->
                <div v-if="targetAudienceArray.length > 0" class="rounded-xl bg-white p-6 shadow-sm border border-gray-100">
                    <h3 class="mb-4 text-lg font-semibold text-gray-900 flex items-center gap-2">
                        <Users class="h-5 w-5 text-teal-600" />
                        Who Should Attend
                    </h3>
                    <ul class="space-y-3 text-gray-700">
                        <li v-for="(audience, index) in targetAudienceArray" :key="index" class="flex items-start gap-3">
                            <div class="mt-0.5 flex-shrink-0">
                                <div class="h-5 w-5 rounded-full bg-blue-100 flex items-center justify-center">
                                    <svg class="h-3 w-3 text-blue-600" fill="currentColor" viewBox="0 0 20 20">
                                        <path d="M9 6a3 3 0 11-6 0 3 3 0 016 0zM17 6a3 3 0 11-6 0 3 3 0 016 0zM12.93 17c.046-.327.07-.66.07-1a6.97 6.97 0 00-1.5-4.33A5 5 0 0119 16v1h-6.07zM6 11a5 5 0 015 5v1H1v-1a5 5 0 015-5z"/>
                                    </svg>
                                </div>
                            </div>
                            <span class="flex-1">{{ audience }}</span>
                        </li>
                    </ul>
                </div>

                <!-- Prerequisites -->
                <div v-if="programData.prerequisites" class="rounded-xl bg-white p-6 shadow-sm border border-gray-100">
                    <h3 class="mb-4 text-lg font-semibold text-gray-900 flex items-center gap-2">
                        <svg class="h-5 w-5 text-teal-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                        </svg>
                        Prerequisites
                    </h3>
                    <div class="rounded-lg bg-amber-50 border border-amber-100 p-4">
                        <p class="text-gray-700 whitespace-pre-wrap">{{ programData.prerequisites }}</p>
                    </div>
                </div>

                <!-- Additional Information -->
                <div v-if="programData.additional_info" class="rounded-xl bg-white p-6 shadow-sm border border-gray-100">
                    <h3 class="mb-4 text-lg font-semibold text-gray-900 flex items-center gap-2">
                        <svg class="h-5 w-5 text-teal-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        Additional Information
                    </h3>
                    <p class="text-gray-700 whitespace-pre-wrap leading-relaxed">{{ programData.additional_info }}</p>
                </div>

                <!-- No Content Fallback -->
                <div v-if="learningOutcomesArray.length === 0 && targetAudienceArray.length === 0 && !programData.prerequisites && !programData.additional_info" class="rounded-xl bg-white p-8 shadow-sm border border-gray-100 text-center">
                    <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-gray-100 mb-4">
                        <BookOpen class="h-8 w-8 text-gray-400" />
                    </div>
                    <h3 class="text-lg font-semibold text-gray-900 mb-2">No Details Available</h3>
                    <p class="text-gray-500">This course doesn't have additional details yet.</p>
                </div>
            </div>

        <!-- Sidebar -->
        <div class="space-y-6">
            <!-- Course Information -->
            <div class="rounded-xl bg-white p-6 shadow-sm border border-gray-100">
                <div class="mb-5 flex items-center justify-between">
                    <h3 class="text-lg font-semibold text-gray-900 flex items-center gap-2">
                        <BookOpen class="h-5 w-5 text-teal-600" />
                        Course Information
                    </h3>
                </div>

                <div class="space-y-4 text-sm">
                    <div class="flex items-start gap-3">
                        <div class="rounded-lg bg-gray-50 p-2">
                            <svg class="h-4 w-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/>
                            </svg>
                        </div>
                        <div class="flex-1">
                            <div class="text-gray-500 text-xs uppercase tracking-wide">Category</div>
                            <div class="font-semibold text-gray-900 mt-0.5">{{ programData.category }}</div>
                        </div>
                    </div>

                    <div class="flex items-start gap-3">
                        <div class="rounded-lg bg-gray-50 p-2">
                            <svg class="h-4 w-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/>
                            </svg>
                        </div>
                        <div class="flex-1">
                            <div class="text-gray-500 text-xs uppercase tracking-wide">Level</div>
                            <div class="mt-0.5">
                                <span :class="['inline-block rounded-lg px-3 py-1 text-xs font-semibold', getLevelBadgeClass]">{{ programData.level }}</span>
                            </div>
                        </div>
                    </div>

                    <div class="flex items-start gap-3">
                        <div class="rounded-lg bg-gray-50 p-2">
                            <Users class="h-4 w-4 text-gray-500" />
                        </div>
                        <div class="flex-1">
                            <div class="text-gray-500 text-xs uppercase tracking-wide">Class Capacity</div>
                            <div class="font-semibold text-gray-900 mt-0.5">{{ programData.min_participants }} - {{ programData.max_participants }} participants</div>
                        </div>
                    </div>

                    <div v-if="programData.period && programData.period !== '—'" class="flex items-start gap-3">
                        <div class="rounded-lg bg-gray-50 p-2">
                            <Calendar class="h-4 w-4 text-gray-500" />
                        </div>
                        <div class="flex-1">
                            <div class="text-gray-500 text-xs uppercase tracking-wide">Period</div>
                            <div class="font-semibold text-gray-900 mt-0.5">{{ programData.period }}</div>
                        </div>
                    </div>

                    <div v-if="programData.time && programData.time !== '—'" class="flex items-start gap-3">
                        <div class="rounded-lg bg-gray-50 p-2">
                            <Clock class="h-4 w-4 text-gray-500" />
                        </div>
                        <div class="flex-1">
                            <div class="text-gray-500 text-xs uppercase tracking-wide">Time</div>
                            <div class="font-semibold text-gray-900 mt-0.5">{{ programData.time }}</div>
                        </div>
                    </div>

                    <div v-if="programData.location && programData.location !== '—'" class="flex items-start gap-3">
                        <div class="rounded-lg bg-gray-50 p-2">
                            <MapPin class="h-4 w-4 text-gray-500" />
                        </div>
                        <div class="flex-1">
                            <div class="text-gray-500 text-xs uppercase tracking-wide">Location</div>
                            <div class="font-semibold text-gray-900 mt-0.5">{{ programData.location }}</div>
                        </div>
                    </div>

                    <div class="flex items-start gap-3">
                        <div class="rounded-lg p-2" :class="statusStyles.dot.replace('bg-', 'bg-opacity-20 bg-').replace('-500', '-100')">
                            <div :class="['h-4 w-4 rounded-full', statusStyles.dot]"></div>
                        </div>
                        <div class="flex-1">
                            <div class="text-gray-500 text-xs uppercase tracking-wide">Status</div>
                            <div class="font-semibold mt-0.5" :class="statusStyles.class">{{ statusStyles.text }}</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Instructor -->
            <div class="rounded-xl bg-white p-6 shadow-sm border border-gray-100">
                <h3 class="mb-4 text-lg font-semibold text-gray-900 flex items-center gap-2">
                    <User class="h-5 w-5 text-teal-600" />
                    Instructor
                </h3>

                <div v-if="programData.trainer && programData.trainer !== '—'" class="space-y-4">
                    <div class="flex items-center gap-4 p-3 rounded-xl bg-gradient-to-r from-teal-50 to-cyan-50 border border-teal-100">
                        <img :src="`https://ui-avatars.com/api/?name=${encodeURIComponent(programData.trainer)}&background=0D8ABC&color=fff&size=128`" :alt="programData.trainer" class="h-14 w-14 rounded-full shadow-sm" />
                        <div class="flex-1 min-w-0">
                            <div class="font-semibold text-gray-900 truncate">{{ programData.trainer }}</div>
                            <div v-if="programData.trainer_email" class="text-sm text-gray-600 truncate">{{ programData.trainer_email }}</div>
                        </div>
                    </div>
                </div>
                <div v-else class="flex items-center gap-3 p-4 rounded-xl bg-gray-50 border border-gray-100">
                    <div class="rounded-full bg-gray-200 p-2">
                        <User class="h-4 w-4 text-gray-400" />
                    </div>
                    <span class="text-sm text-gray-500">No instructor assigned</span>
                </div>
            </div>

            <!-- Course Details -->
            <div class="rounded-xl bg-white p-6 shadow-sm border border-gray-100">
                <h3 class="mb-4 text-lg font-semibold text-gray-900 flex items-center gap-2">
                    <svg class="h-5 w-5 text-teal-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    Course Details
                </h3>

                <div class="space-y-3 text-sm">
                    <div v-if="props.program?.code" class="flex items-center justify-between p-3 rounded-lg bg-gray-50">
                        <span class="text-gray-500">Course Code</span>
                        <span class="font-mono font-semibold text-gray-900">{{ props.program.code }}</span>
                    </div>
                    <div v-if="props.program?.created_at" class="flex items-center justify-between p-3 rounded-lg bg-gray-50">
                        <span class="text-gray-500">Created</span>
                        <span class="font-semibold text-gray-900">{{ props.program.created_at }}</span>
                    </div>
                </div>
            </div>
        </div>
        </div>
    </div>
</template>
