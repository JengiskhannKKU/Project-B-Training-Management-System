<script setup lang="ts">
import { Star, Users, CheckCircle, Calendar, Clock, MapPin } from 'lucide-vue-next';

interface AdminCourseCardProps {
    id: number;
    name: string;
    image_url: string;
    rating?: number;
    level?: string;
    students_count?: number;
    price?: string;
    date?: string;
    time?: string;
    location?: string;
}

defineProps<AdminCourseCardProps>();

const emit = defineEmits<{
    viewSessions: [courseId: number];
}>();

const getLevelColor = (level: string) => {
    const colors = {
        'Beginner': 'bg-teal-100 text-teal-700',
        'Intermediate': 'bg-amber-100 text-amber-700',
        'Advanced': 'bg-rose-100 text-rose-700',
    };
    return colors[level as keyof typeof colors] || 'bg-gray-100 text-gray-700';
};
</script>

<template>
    <div
        class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm transition hover:shadow-md"
    >
        <div class="aspect-video w-full overflow-hidden">
            <img
                :src="image_url"
                :alt="name"
                class="h-full w-full object-cover"
            />
        </div>

        <div class="p-4">
            <div class="mb-2 flex items-center justify-between">
                <div class="flex items-center gap-1">
                    <Star v-for="i in 5" :key="i" :size="16" class="text-yellow-400 fill-yellow-400" />
                    <span class="ml-1 text-sm text-gray-600">({{ rating }})</span>
                </div>
                <span :class="getLevelColor(level || 'Beginner')" class="rounded px-2 py-1 text-xs font-medium">
                    {{ level }}
                </span>
            </div>

            <h3 class="mb-3 text-lg font-semibold text-gray-900">
                {{ name }}
            </h3>

            <div class="space-y-2 text-sm text-gray-600">
                <div class="flex items-center gap-2">
                    <Users :size="16" />
                    {{ students_count }} students
                    <CheckCircle :size="16" class="ml-auto text-teal-600 fill-teal-600" />
                    {{ price }}
                </div>
                <div class="flex items-center gap-2">
                    <Calendar :size="16" />
                    {{ date }}
                    <Clock :size="16" class="ml-auto" />
                    {{ time }}
                </div>
                <div class="flex items-center gap-2">
                    <MapPin :size="16" />
                    {{ location }}
                </div>
            </div>

            <!-- Sessions Button -->
            <button
                @click="emit('viewSessions', id)"
                class="mt-4 w-full inline-flex justify-center items-center bg-[#2f837d] hover:bg-[#26685f] text-white px-4 py-2.5 rounded-lg font-medium transition-all shadow-sm hover:shadow-md"
            >
                Sessions
            </button>
        </div>
    </div>
</template>
