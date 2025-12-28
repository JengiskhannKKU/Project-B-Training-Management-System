<script setup>
import { Smile, Meh, Frown } from 'lucide-vue-next';

const props = defineProps({
    sentimentPercentages: {
        type: Object,
        required: true
    }
});
</script>

<template>
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
        <h2 class="text-lg font-semibold text-gray-900 mb-4">Sentiment Overview</h2>

        <div class="space-y-4">
            <!-- Positive Sentiment -->
            <div class="flex items-center justify-between p-3 bg-green-50 rounded-lg">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 bg-green-100 rounded-full flex items-center justify-center">
                        <Smile :size="20" class="text-green-600" />
                    </div>
                    <div>
                        <p class="text-sm font-medium text-gray-900">Positive</p>
                        <p class="text-xs text-gray-500">Happy customers</p>
                    </div>
                </div>
                <div class="text-2xl font-bold text-green-600">
                    {{ sentimentPercentages.positive }}%
                </div>
            </div>

            <!-- Neutral Sentiment -->
            <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 bg-gray-100 rounded-full flex items-center justify-center">
                        <Meh :size="20" class="text-gray-600" />
                    </div>
                    <div>
                        <p class="text-sm font-medium text-gray-900">Neutral</p>
                        <p class="text-xs text-gray-500">Mixed feelings</p>
                    </div>
                </div>
                <div class="text-2xl font-bold text-gray-600">
                    {{ sentimentPercentages.neutral }}%
                </div>
            </div>

            <!-- Negative Sentiment -->
            <div class="flex items-center justify-between p-3 bg-red-50 rounded-lg">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 bg-red-100 rounded-full flex items-center justify-center">
                        <Frown :size="20" class="text-red-600" />
                    </div>
                    <div>
                        <p class="text-sm font-medium text-gray-900">Negative</p>
                        <p class="text-xs text-gray-500">Needs improvement</p>
                    </div>
                </div>
                <div class="text-2xl font-bold text-red-600">
                    {{ sentimentPercentages.negative }}%
                </div>
            </div>
        </div>
    </div>
</template>
