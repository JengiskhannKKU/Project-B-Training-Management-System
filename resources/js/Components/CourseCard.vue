<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { Star, GraduationCap, Gem, CalendarClock, Clock, MapPin } from 'lucide-vue-next';

interface CourseCardProps {
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
    href?: string;
}

defineProps<CourseCardProps>();

const formatLevel = (level: string) => {
    if (!level) return 'Beginner';
    const normalized = level.toLowerCase();
    return normalized.charAt(0).toUpperCase() + normalized.slice(1);
};

const getLevelColor = (level: string) => {
    const normalized = level.toLowerCase();
    const colors = {
        beginner: 'bg-emerald-100 text-emerald-700',
        intermediate: 'bg-amber-100 text-amber-700',
        advanced: 'bg-red-100 text-red-700',
    };
    return colors[normalized as keyof typeof colors] || 'bg-gray-100 text-gray-700';
};

const getStarType = (index: number, rating: number) => {
    if (rating >= index) {
        return 'fill'; // Filled star
    } else if (rating > index - 1) {
        return 'half'; // Partial star
    }
    return 'empty'; // Empty star
};
</script>

<template>
    <Link
        :href="href || `/trainer/programs/${id}`"
        class="flex flex-col h-full overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm transition hover:shadow-md cursor-pointer"
    >
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

        <div class="flex flex-col flex-grow p-4">
            <div class="mb-2 flex items-center justify-between flex-wrap gap-2">
                <div class="flex items-center gap-1">
                    <div v-for="i in 5" :key="i" class="relative inline-block" style="width: 16px; height: 16px;">
                        <Star
                            :size="16"
                            :class="getStarType(i, rating || 0) === 'empty' ? 'text-gray-300' : 'text-yellow-400'"
                            class="absolute top-0 left-0"
                        />
                        <Star
                            v-if="getStarType(i, rating || 0) !== 'empty'"
                            :size="16"
                            class="text-yellow-400 fill-yellow-400 absolute top-0 left-0"
                            :style="getStarType(i, rating || 0) === 'half' ? 'clip-path: inset(0 50% 0 0);' : ''"
                        />
                    </div>
                    <span class="ml-1 text-sm text-gray-600">({{ rating }})</span>
                </div>
                <span :class="getLevelColor(level || 'Beginner')" class="rounded px-2 py-1 text-xs font-medium">
                    {{ formatLevel(level || 'Beginner') }}
                </span>
            </div>

            <h3 class="mb-3 text-lg font-semibold text-gray-900 line-clamp-2">
                {{ name }}
            </h3>

            <div class="space-y-2 text-sm text-gray-600 mb-4 flex-grow">
                <div class="flex items-center justify-between gap-2 flex-wrap">
                    <div class="flex items-center gap-2 min-w-0">
                        <GraduationCap :size="16" class="text-[#2F837D] flex-shrink-0" />
                        <span class="truncate">{{ students_count }} students</span>
                    </div>
                    <div class="flex items-center gap-2 min-w-0">
                        <Gem :size="16" class="text-[#2F837D] flex-shrink-0" />
                        <span class="truncate">{{ price }}</span>
                    </div>
                </div>
                <div class="flex items-center justify-between gap-2 flex-wrap">
                    <div class="flex items-center gap-2 min-w-0">
                        <CalendarClock :size="16" class="text-[#2F837D] flex-shrink-0" />
                        <span class="truncate">{{ date }}</span>
                    </div>
                    <div class="flex items-center gap-2 min-w-0">
                        <Clock :size="16" class="text-[#2F837D] flex-shrink-0" />
                        <span class="truncate">{{ time }}</span>
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    <MapPin :size="16" class="text-[#2F837D] flex-shrink-0" />
                    <span class="truncate">{{ location }}</span>
                </div>
            </div>
        </div>
    </Link>
</template>
