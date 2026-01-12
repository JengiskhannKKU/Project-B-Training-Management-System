<script setup lang="ts">
import { GraduationCap, BookOpen, Users } from 'lucide-vue-next';

interface AdminCourseCardProps {
    id: number;
    code?: string;
    name: string;
    description?: string;
    category?: string;
    image_url: string;
    level?: string;
    sessions_count?: number;
    max_participants?: number;
}

defineProps<AdminCourseCardProps>();

const emit = defineEmits<{
    viewSessions: [courseId: number];
}>();

const formatLevel = (level: string) => {
    if (!level) return 'Beginner';
    const normalized = level.toLowerCase();
    return normalized.charAt(0).toUpperCase() + normalized.slice(1);
};

const getLevelColor = (level: string) => {
    const normalized = level?.toLowerCase() || 'beginner';
    const colors = {
        beginner: 'bg-emerald-100 text-emerald-700',
        intermediate: 'bg-amber-100 text-amber-700',
        advanced: 'bg-red-100 text-red-700',
    };
    return colors[normalized as keyof typeof colors] || 'bg-gray-100 text-gray-700';
};

const getCategoryColor = (category: string) => {
    const normalized = category?.toLowerCase() || '';
    const colors = {
        it: 'bg-blue-100 text-blue-700',
        management: 'bg-purple-100 text-purple-700',
        design: 'bg-pink-100 text-pink-700',
        marketing: 'bg-orange-100 text-orange-700',
        business: 'bg-indigo-100 text-indigo-700',
        'professional development': 'bg-teal-100 text-teal-700',
    };
    return colors[normalized as keyof typeof colors] || 'bg-gray-100 text-gray-700';
};
</script>

<template>
    <div
        class="flex flex-col h-full overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm transition hover:shadow-md"
    >
        <!-- Thumbnail Image -->
        <div class="aspect-video w-full overflow-hidden flex-shrink-0">
            <img
                v-if="image_url"
                :src="image_url"
                :alt="name"
                class="h-full w-full object-cover"
            />
            <!-- Placeholder when no image -->
            <div
                v-else
                class="h-full w-full bg-gradient-to-br from-cyan-200 via-teal-300 to-teal-500 flex items-center justify-center"
            >
                <GraduationCap :size="48" class="text-white/60" />
            </div>
        </div>

        <!-- Course Information -->
        <div class="flex flex-col flex-grow p-4">
            <!-- Course Code -->
            <div v-if="code" class="mb-2">
                <span class="text-xs font-mono text-gray-500">{{ code }}</span>
            </div>

            <!-- Category and Level Badges -->
            <div class="mb-3 flex items-center gap-2 flex-wrap">
                <span v-if="category" :class="getCategoryColor(category)" class="rounded px-2 py-1 text-xs font-medium">
                    {{ category }}
                </span>
                <span :class="getLevelColor(level || 'Beginner')" class="rounded px-2 py-1 text-xs font-medium">
                    {{ formatLevel(level || 'Beginner') }}
                </span>
            </div>

            <!-- Course Title -->
            <h3 class="mb-2 text-lg font-semibold text-gray-900 line-clamp-2">
                {{ name }}
            </h3>

            <!-- Description Preview -->
            <p v-if="description" class="mb-4 text-sm text-gray-600 line-clamp-2">
                {{ description }}
            </p>

            <!-- Course Stats -->
            <div class="mb-4 space-y-2 text-sm text-gray-600 flex-grow">
                <!-- Sessions Count -->
                <div class="flex items-center gap-2">
                    <BookOpen :size="16" class="text-[#2F837D] flex-shrink-0" />
                    <span>
                        {{ sessions_count || 0 }} {{ (sessions_count || 0) === 1 ? 'Session' : 'Sessions' }} Available
                    </span>
                </div>

                <!-- Max Participants -->
                <div v-if="max_participants" class="flex items-center gap-2">
                    <Users :size="16" class="text-[#2F837D] flex-shrink-0" />
                    <span>Max {{ max_participants }} Participants</span>
                </div>
            </div>

            <!-- Check Attendance Button - Stays at bottom -->
            <button
                @click="emit('viewSessions', id)"
                class="w-full inline-flex justify-center items-center bg-[#2f837d] hover:bg-[#26685f] text-white px-4 py-2.5 rounded-lg font-medium transition-all shadow-sm hover:shadow-md mt-auto"
            >
                Check Attendance
            </button>
        </div>
    </div>
</template>
