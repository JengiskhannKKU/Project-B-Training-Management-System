<script setup>
import { Trophy, Star } from "lucide-vue-next";

defineProps({
    trainers: {
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
                    <h2 class="text-lg font-bold text-gray-900">Top Trainers</h2>
                    <p class="mt-0.5 text-xs text-gray-500">
                        Highest satisfaction ratings
                    </p>
                </div>
            </div>
        </div>
        <div class="space-y-4">
            <div
                v-for="trainer in trainers"
                :key="trainer.name"
                class="flex items-center gap-4"
            >
                <div class="min-w-0 flex-shrink-0" style="width: 200px">
                    <p class="truncate text-sm font-semibold text-gray-900">
                        {{ trainer.name }}
                    </p>
                    <p class="mt-0.5 text-xs text-gray-500">
                        {{ trainer.courses }} courses • {{ trainer.students }} students
                    </p>
                </div>
                <div
                    class="h-2 flex-1 overflow-hidden rounded-full bg-gray-100"
                >
                    <div
                        class="h-full rounded-full"
                        style="background-color: #FFD25D"
                        :style="{
                            width: (40 + ((trainer.rating - 4.0) / 1.0) * 60) + '%',
                        }"
                    ></div>
                </div>
                <div class="flex items-center gap-1 text-sm font-semibold text-gray-900">
                    <Star
                        :size="14"
                        class="fill-amber-400 text-amber-400"
                    />
                    {{ trainer.rating }}
                </div>
            </div>
        </div>
    </div>
</template>
