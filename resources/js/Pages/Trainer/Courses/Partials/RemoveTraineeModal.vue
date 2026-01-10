<script setup lang="ts">
defineProps<{
    show: boolean;
    trainee: any;
}>();

const emit = defineEmits<{
    close: [];
    confirm: [];
}>();
</script>

<template>
    <div v-if="show" class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50 p-4">
        <div class="w-full max-w-md rounded-lg bg-white p-4 sm:p-6 shadow-xl">
            <div class="text-center">
                <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-red-100">
                    <svg class="h-6 w-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                    </svg>
                </div>
                <h3 class="mt-4 text-base sm:text-lg font-semibold text-gray-900">Confirm Remove Trainee</h3>
                <p class="mt-2 text-sm text-gray-600">Are you sure you want to remove this trainee?</p>

                <div class="mt-6 flex flex-col sm:flex-row gap-3">
                    <button @click="emit('close')" class="flex-1 rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 order-2 sm:order-1">No, Keep it</button>
                    <button @click="emit('confirm')" class="flex-1 rounded-lg bg-red-600 px-4 py-2 text-sm font-medium text-white hover:bg-red-700 order-1 sm:order-2">Yes, Remove!</button>
                </div>
            </div>
        </div>
    </div>
</template>
