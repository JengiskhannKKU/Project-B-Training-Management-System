<script setup>
import { CheckCircle } from 'lucide-vue-next';

const props = defineProps({
    sessionDays: {
        type: Array,
        required: true,
    },
    selectedDayId: {
        type: Number,
        default: null,
    },
});

const emit = defineEmits(['select']);

const formatDate = (dateString) => {
    if (!dateString) return '';
    const date = new Date(dateString);
    return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
};
</script>

<template>
    <div class="border-b border-gray-200 bg-white">
        <nav class="flex space-x-2 overflow-x-auto px-4 py-2" aria-label="Session Days">
            <button
                v-for="day in sessionDays"
                :key="day.id"
                @click="$emit('select', day)"
                :disabled="!day.has_occurred && !day.is_today"
                :class="[
                    'flex items-center gap-2 px-4 py-3 border-b-2 font-medium text-sm whitespace-nowrap transition-colors rounded-t-lg',
                    selectedDayId === day.id
                        ? 'border-[#2f837d] bg-[#2f837d]/5 text-[#2f837d]'
                        : 'border-transparent text-gray-600 hover:text-gray-900 hover:bg-gray-50',
                    (!day.has_occurred && !day.is_today)
                        ? 'opacity-40 cursor-not-allowed'
                        : 'cursor-pointer hover:border-gray-300'
                ]"
                :title="!day.has_occurred && !day.is_today ? 'This day has not occurred yet' : ''"
            >
                <div class="flex flex-col items-start">
                    <div class="flex items-center gap-2">
                        <span class="font-semibold">Day {{ day.day_number }}</span>
                        <CheckCircle
                            v-if="day.attendance_count > 0"
                            :size="16"
                            class="text-green-500"
                        />
                    </div>
                    <span class="text-xs text-gray-500">{{ formatDate(day.date) }}</span>
                </div>
            </button>
        </nav>
    </div>
</template>
