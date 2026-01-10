<script setup lang="ts">
defineProps<{
    show: boolean;
}>();

const emit = defineEmits<{
    close: [];
    'view-course': [];
}>();
</script>

<template>
    <div v-if="show" class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50 p-4">
        <div class="w-full max-w-md rounded-lg bg-white p-6 shadow-xl">
            <div class="text-center">
                <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-red-100">
                    <svg class="h-6 w-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </div>
                <h3 class="mt-4 text-lg font-semibold text-gray-900">Course has rejected</h3>
                <p class="mt-2 text-sm text-gray-600">Leadership Fundamentals is not live</p>

                <div class="mt-4 rounded-lg bg-gray-50 p-4 text-left">
                    <h4 class="font-medium text-gray-900">What happens next</h4>
                    <p class="mt-2 text-sm text-gray-600">revise the program then send that request again</p>
                </div>

                <div class="mt-6 flex gap-3">
                    <button @click="emit('close')" class="flex-1 rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">
                        <svg class="mr-2 inline-block h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                        Close
                    </button>
                    <button @click="emit('view-course')" class="flex-1 rounded-lg bg-teal-600 px-4 py-2 text-sm font-medium text-white hover:bg-teal-700">
                        <svg class="mr-2 inline-block h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                        </svg>
                        View Course
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>
