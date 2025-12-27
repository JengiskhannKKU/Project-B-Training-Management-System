<script setup>
import { Smile, Meh, Frown } from 'lucide-vue-next';

const props = defineProps({
    sentiments: {
        type: Object,
        default: () => ({
            positive: 72,
            neutral: 21,
            negative: 7
        })
    }
});
</script>

<template>
    <div class="bg-white rounded-[25px] shadow-sm border border-[#dfe5ef] p-6">
        <h2 class="text-xl font-semibold text-gray-900 mb-6">Sentiment Overview</h2>

        <div class="space-y-4">
            <!-- Positive Sentiment -->
            <div class="flex items-center justify-between p-4 bg-green-50 rounded-xl border border-green-200">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 bg-green-100 rounded-full flex items-center justify-center">
                        <Smile :size="20" class="text-green-600" />
                    </div>
                    <span class="text-sm font-medium text-gray-900">Positive</span>
                </div>
                <div class="text-2xl font-bold text-green-600">
                    {{ sentiments.positive }}%
                </div>
            </div>

            <!-- Neutral Sentiment -->
            <div class="flex items-center justify-between p-4 bg-gray-50 rounded-xl border border-gray-200">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 bg-gray-100 rounded-full flex items-center justify-center">
                        <Meh :size="20" class="text-gray-600" />
                    </div>
                    <span class="text-sm font-medium text-gray-900">Neutral</span>
                </div>
                <div class="text-2xl font-bold text-gray-600">
                    {{ sentiments.neutral }}%
                </div>
            </div>

            <!-- Negative Sentiment -->
            <div class="flex items-center justify-between p-4 bg-red-50 rounded-xl border border-red-200">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 bg-red-100 rounded-full flex items-center justify-center">
                        <Frown :size="20" class="text-red-600" />
                    </div>
                    <span class="text-sm font-medium text-gray-900">Negative</span>
                </div>
                <div class="text-2xl font-bold text-red-600">
                    {{ sentiments.negative }}%
                </div>
            </div>
        </div>
    </div>
</template>
