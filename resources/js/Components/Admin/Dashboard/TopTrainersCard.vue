<script setup>
import { Award, Star } from "lucide-vue-next";

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
        <div class="mb-6 flex items-center justify-between">
            <div>
                <h2 class="text-base font-bold text-gray-900">Top Trainers</h2>
                <p class="mt-1 text-xs text-gray-500">
                    Highest satisfaction ratings
                </p>
            </div>
            <div
                class="rounded-xl bg-gradient-to-br from-purple-500 to-purple-600 p-3 shadow-lg shadow-purple-500/25"
            >
                <Award :size="20" class="text-white" />
            </div>
        </div>
        <div class="space-y-3">
            <div
                v-for="trainer in trainers"
                :key="trainer.name"
                class="group space-y-2.5 rounded-xl border border-gray-200/80 bg-white p-4 transition-all duration-300 hover:-translate-y-0.5 hover:border-gray-300 hover:shadow-md"
            >
                <div class="flex items-center justify-between">
                    <div class="min-w-0 flex-1">
                        <p class="truncate text-sm font-bold text-gray-900">
                            {{ trainer.name }}
                        </p>
                        <p class="mt-1 text-xs font-medium text-gray-500">
                            {{ trainer.courses }} courses •
                            {{ trainer.students }} students
                        </p>
                    </div>
                    <div
                        class="flex items-center gap-1 rounded-full bg-amber-50 px-3 py-1.5 text-sm font-bold text-gray-900 ring-1 ring-amber-100"
                    >
                        <Star
                            :size="14"
                            class="fill-amber-400 text-amber-400 drop-shadow-sm"
                        />
                        {{ trainer.rating }}
                    </div>
                </div>
                <div
                    class="h-2.5 w-full overflow-hidden rounded-full bg-gray-100"
                >
                    <div
                        class="h-full rounded-full bg-gradient-to-r from-amber-400 to-amber-500 shadow-sm transition-all duration-500 group-hover:shadow-md"
                        :style="{
                            width: (trainer.rating / 5) * 100 + '%',
                        }"
                    ></div>
                </div>
            </div>
        </div>
    </div>
</template>
