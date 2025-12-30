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
        <div class="mb-6">
            <div class="flex items-center gap-3.5">
                <div class="flex items-center justify-center rounded-lg p-2.5">
                    <Trophy :size="24" class="text-[#F1C644]" />
                </div>
                <div>
                    <h2 class="text-lg font-bold text-gray-900">Top Courses</h2>
                    <p class="mt-0.5 text-xs text-gray-500">
                        Highest enrollment numbers
                    </p>
                </div>
            </div>
        </div>
        <div class="space-y-4">
            <div
                v-for="course in courses"
                :key="course.rank"
                class="flex items-center gap-5 rounded-xl p-5"
            >
                <div
                    class="flex h-14 w-14 flex-shrink-0 items-center justify-center rounded-full bg-[#DAFFED] text-lg font-bold text-[#2F837D]"
                >
                    {{ course.rank }}
                </div>
                <div class="flex min-w-0 flex-1 items-center justify-between gap-6">
                    <div class="min-w-0 flex-1">
                        <h3 class="truncate text-sm font-bold text-gray-900">
                            {{ course.name }}
                        </h3>
                        <p class="mt-1.5 text-xs font-medium text-gray-500">
                            {{ course.category }}
                        </p>
                    </div>
                    <div class="flex flex-shrink-0 flex-col items-end">
                        <p class="text-lg font-semibold text-[#2F837D]">
                            {{ course.enrollments }}
                        </p>
                        <p class="text-xs font-medium text-gray-500">enrolled</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
