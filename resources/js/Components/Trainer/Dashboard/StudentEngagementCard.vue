<script setup>
import { Users } from 'lucide-vue-next';

const props = defineProps({
    highlyEngaged: {
        type: Number,
        required: true,
        default: 0,
    },
    moderate: {
        type: Number,
        required: true,
        default: 0,
    },
    atRisk: {
        type: Number,
        required: true,
        default: 0,
    },
});

const total = props.highlyEngaged + props.moderate + props.atRisk;
const highlyEngagedPercent = total > 0 ? ((props.highlyEngaged / total) * 100).toFixed(1) : 0;
const moderatePercent = total > 0 ? ((props.moderate / total) * 100).toFixed(1) : 0;
const atRiskPercent = total > 0 ? ((props.atRisk / total) * 100).toFixed(1) : 0;
</script>

<template>
    <div class="rounded-xl border border-gray-200/80 bg-white p-6 shadow-sm transition-all duration-300 hover:shadow-lg">
        <!-- Header -->
        <div class="mb-6 flex items-start justify-between">
            <div class="flex items-center gap-2">
                <div class="rounded-lg bg-purple-100 p-2">
                    <Users :size="20" class="text-purple-600" />
                </div>
                <div>
                    <h3 class="text-base font-bold text-gray-900">
                        {{ $t('Student Engagement') }}
                    </h3>
                    <p class="text-sm text-gray-500">
                        {{ $t('Participation trends') }}
                    </p>
                </div>
            </div>
        </div>

        <!-- Engagement Levels -->
        <div class="space-y-5">
            <!-- Highly Engaged -->
            <div>
                <div class="mb-2 flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <div class="h-2 w-2 rounded-full bg-emerald-500"></div>
                        <span class="text-sm font-semibold text-gray-700">
                            {{ $t('Active students') }}
                        </span>
                    </div>
                    <span class="text-sm font-bold text-emerald-600">
                        {{ highlyEngagedPercent }}%
                    </span>
                </div>
                <div class="h-3 w-full overflow-hidden rounded-full bg-gray-100">
                    <div
                        class="h-full rounded-full bg-gradient-to-r from-emerald-500 to-emerald-400 transition-all duration-500"
                        :style="{ width: `${highlyEngagedPercent}%` }"
                    ></div>
                </div>
                <p class="mt-1 text-xs text-gray-500">
                    {{ highlyEngaged }} {{ $t('students enrolled') }}
                </p>
            </div>

            <!-- Moderate -->
            <div>
                <div class="mb-2 flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <div class="h-2 w-2 rounded-full bg-amber-500"></div>
                        <span class="text-sm font-semibold text-gray-700">
                            {{ $t('Needs attention') }}
                        </span>
                    </div>
                    <span class="text-sm font-bold text-amber-600">
                        {{ moderatePercent }}%
                    </span>
                </div>
                <div class="h-3 w-full overflow-hidden rounded-full bg-gray-100">
                    <div
                        class="h-full rounded-full bg-gradient-to-r from-amber-500 to-amber-400 transition-all duration-500"
                        :style="{ width: `${moderatePercent}%` }"
                    ></div>
                </div>
                <p class="mt-1 text-xs text-gray-500">
                    {{ moderate }} {{ $t('students enrolled') }}
                </p>
            </div>

            <!-- At Risk -->
            <div>
                <div class="mb-2 flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <div class="h-2 w-2 rounded-full bg-red-500"></div>
                        <span class="text-sm font-semibold text-gray-700">
                            {{ $t('At risk') }}
                        </span>
                    </div>
                    <span class="text-sm font-bold text-red-600">
                        {{ atRiskPercent }}%
                    </span>
                </div>
                <div class="h-3 w-full overflow-hidden rounded-full bg-gray-100">
                    <div
                        class="h-full rounded-full bg-gradient-to-r from-red-500 to-red-400 transition-all duration-500"
                        :style="{ width: `${atRiskPercent}%` }"
                    ></div>
                </div>
                <p class="mt-1 text-xs text-gray-500">
                    {{ atRisk }} {{ $t('students at risk') }}
                </p>
            </div>
        </div>

        <!-- Summary -->
        <div class="mt-6 rounded-lg bg-gray-50 p-4">
            <div class="flex items-center justify-between">
                <span class="text-sm font-medium text-gray-600">{{ $t('Total Trainees') }}</span>
                <span class="text-lg font-bold text-gray-900">{{ total }}</span>
            </div>
        </div>
    </div>
</template>
