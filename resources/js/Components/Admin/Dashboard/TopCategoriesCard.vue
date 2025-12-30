<script setup>
import { ArrowRight } from "lucide-vue-next";

defineProps({
    categories: {
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
            <h2 class="text-base font-bold text-gray-900">Top Categories</h2>
            <p class="mt-1 text-xs text-gray-500">
                Most popular course categories
            </p>
        </div>
        <div class="space-y-4">
            <div
                v-for="category in categories"
                :key="category.name"
                class="group space-y-2"
            >
                <div class="flex items-center justify-between">
                    <span class="text-sm font-semibold text-gray-800">
                        {{ category.name }}
                    </span>
                    <span class="text-sm font-bold tabular-nums text-gray-900">
                        {{ category.percentage }}%
                    </span>
                </div>
                <div
                    class="h-2.5 w-full overflow-hidden rounded-full bg-gray-100"
                >
                    <div
                        class="h-full rounded-full shadow-sm transition-all duration-500 group-hover:scale-y-110 group-hover:shadow-md"
                        :class="category.color"
                        :style="{
                            width: category.percentage + '%',
                        }"
                    ></div>
                </div>
                <p class="text-xs font-medium text-gray-500">
                    {{ category.count }} courses
                </p>
            </div>
        </div>
        <a
            href="#"
            class="group mt-6 flex items-center justify-center gap-2 rounded-lg border border-gray-200 bg-white py-2.5 text-xs font-semibold text-gray-700 shadow-sm transition-all hover:border-gray-300 hover:bg-gray-50 hover:shadow"
        >
            View all categories
            <ArrowRight
                :size="14"
                class="transition-transform duration-200 group-hover:translate-x-0.5"
            />
        </a>
    </div>
</template>
