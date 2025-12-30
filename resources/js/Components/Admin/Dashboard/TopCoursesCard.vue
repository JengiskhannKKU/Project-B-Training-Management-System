<script setup>
import { Trophy, GraduationCap } from "lucide-vue-next";

defineProps({
    courses: {
        type: Array,
        required: true,
    },
});
</script>

<template>
    <div
        class="rounded-xl border border-gray-200/80 bg-white p-6 shadow-sm transition-all duration-300 hover:shadow-lg"
    >
        <div class="mb-6 flex items-center justify-between">
            <div>
                <h2 class="text-base font-bold text-gray-900">Top Courses</h2>
                <p class="mt-1 text-xs text-gray-500">
                    Highest enrollment numbers
                </p>
            </div>
            <div
                class="rounded-xl bg-gradient-to-br from-amber-500 to-amber-600 p-3 shadow-lg shadow-amber-500/25"
            >
                <Trophy :size="20" class="text-white" />
            </div>
        </div>
        <div class="space-y-3">
            <div
                v-for="course in courses"
                :key="course.rank"
                class="group flex gap-4 rounded-xl border border-gray-200/80 bg-gradient-to-r from-white to-gray-50/40 p-4 transition-all duration-300 hover:-translate-y-0.5 hover:border-gray-300 hover:shadow-md"
            >
                <div
                    class="flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-xl text-base font-bold text-white shadow-md transition-all duration-300 group-hover:scale-105 group-hover:shadow-lg"
                    :class="course.badgeColor"
                >
                    {{ course.rank }}
                </div>
                <div class="min-w-0 flex-1">
                    <h3 class="truncate text-sm font-bold text-gray-900">
                        {{ course.name }}
                    </h3>
                    <p class="mt-1 text-xs font-medium text-gray-500">
                        {{ course.category }}
                    </p>
                    <div
                        class="mt-2.5 flex w-fit items-center gap-1.5 rounded-full bg-blue-50 px-3 py-1.5 text-xs font-semibold text-blue-700 ring-1 ring-blue-100"
                    >
                        <GraduationCap :size="13" />
                        {{ course.enrollments }} enrolled
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
