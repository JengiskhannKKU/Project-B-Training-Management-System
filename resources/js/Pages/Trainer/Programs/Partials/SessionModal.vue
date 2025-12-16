<script setup lang="ts">
defineProps<{
    show: boolean;
    mode: 'add' | 'edit';
    session?: any;
    availableCourses: string[];
    sessionForm: {
        course: string;
        date: string;
        start_time: string;
        end_time: string;
        location: string;
        trainer: string;
        capacity: string;
        status: string;
    };
    sessionErrors: Record<string, string>;
}>();

const emit = defineEmits<{
    close: [];
    submit: [];
}>();
</script>

<template>
    <div v-if="show" class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50 p-4" @click.self="emit('close')">
        <div class="w-full max-w-2xl rounded-lg bg-white shadow-xl max-h-[90vh] overflow-y-auto">
            <div class="border-b px-4 sm:px-6 py-3 sm:py-4 sticky top-0 bg-white z-10">
                <div class="flex items-start sm:items-center justify-between gap-2">
                    <div class="flex-1 min-w-0">
                        <h2 class="text-lg sm:text-xl font-bold text-gray-900">{{ mode === 'add' ? 'Add Sessions' : 'Edit Session' }}</h2>
                        <p class="mt-1 text-xs sm:text-sm text-gray-500 truncate">Leadership Fundamentals | 24/30 (6 available)</p>
                    </div>
                    <button @click="emit('close')" class="rounded-lg p-2 hover:bg-gray-100 flex-shrink-0">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>
            </div>

            <form @submit.prevent="emit('submit')" class="p-4 sm:p-6 space-y-4">
                <!-- Course Dropdown -->
                <div>
                    <label class="block text-sm font-medium text-gray-700">Courses <span class="text-red-500">*</span></label>
                    <select v-model="sessionForm.course" :class="['mt-1 block w-full rounded-md focus:border-teal-500 focus:ring-teal-500 text-sm sm:text-base', sessionErrors.course ? 'border-red-300' : 'border-gray-300']">
                        <option v-for="course in availableCourses" :key="course" :value="course">{{ course }}</option>
                    </select>
                    <p v-if="sessionErrors.course" class="mt-1 text-sm text-red-600">{{ sessionErrors.course }}</p>
                </div>

                <!-- Date and Time -->
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Date <span class="text-red-500">*</span></label>
                        <input v-model="sessionForm.date" type="date" :class="['mt-1 block w-full rounded-md focus:border-teal-500 focus:ring-teal-500', sessionErrors.date ? 'border-red-300' : 'border-gray-300']" />
                        <p v-if="sessionErrors.date" class="mt-1 text-sm text-red-600">{{ sessionErrors.date }}</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Start Time <span class="text-red-500">*</span></label>
                        <input v-model="sessionForm.start_time" type="time" :class="['mt-1 block w-full rounded-md focus:border-teal-500 focus:ring-teal-500', sessionErrors.start_time ? 'border-red-300' : 'border-gray-300']" />
                        <p v-if="sessionErrors.start_time" class="mt-1 text-sm text-red-600">{{ sessionErrors.start_time }}</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">End Time <span class="text-red-500">*</span></label>
                        <input v-model="sessionForm.end_time" type="time" :class="['mt-1 block w-full rounded-md focus:border-teal-500 focus:ring-teal-500', sessionErrors.end_time ? 'border-red-300' : 'border-gray-300']" />
                        <p v-if="sessionErrors.end_time" class="mt-1 text-sm text-red-600">{{ sessionErrors.end_time }}</p>
                    </div>
                </div>

                <!-- Location -->
                <div>
                    <label class="block text-sm font-medium text-gray-700">Location <span class="text-red-500">*</span></label>
                    <div class="relative mt-1">
                        <input v-model="sessionForm.location" type="text" placeholder="e.g., Main Conference Room" :class="['block w-full rounded-md pr-10 focus:border-teal-500 focus:ring-teal-500', sessionErrors.location ? 'border-red-300' : 'border-gray-300']" />
                        <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-3">
                            <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                            </svg>
                        </div>
                    </div>
                    <p v-if="sessionErrors.location" class="mt-1 text-sm text-red-600">{{ sessionErrors.location }}</p>
                </div>

                <!-- Trainer -->
                <div>
                    <label class="block text-sm font-medium text-gray-700">Trainer</label>
                    <input v-model="sessionForm.trainer" type="text" placeholder="e.g., John Doe" class="mt-1 block w-full rounded-md border-gray-300 focus:border-teal-500 focus:ring-teal-500" />
                </div>

                <!-- Capacity and Status -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Capacity <span class="text-red-500">*</span></label>
                        <input v-model="sessionForm.capacity" type="number" min="1" placeholder="e.g., 30" :class="['mt-1 block w-full rounded-md focus:border-teal-500 focus:ring-teal-500', sessionErrors.capacity ? 'border-red-300' : 'border-gray-300']" />
                        <p v-if="sessionErrors.capacity" class="mt-1 text-sm text-red-600">{{ sessionErrors.capacity }}</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Status</label>
                        <select v-model="sessionForm.status" class="mt-1 block w-full rounded-md border-gray-300 focus:border-teal-500 focus:ring-teal-500">
                            <option value="Open">Open</option>
                            <option value="Close">Close</option>
                        </select>
                    </div>
                </div>

                <!-- Buttons -->
                <div class="flex flex-col sm:flex-row gap-3 pt-4">
                    <button type="button" @click="emit('close')" class="flex-1 rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 order-2 sm:order-1">Cancel</button>
                    <button type="submit" class="flex-1 rounded-lg bg-teal-600 px-4 py-2 text-sm font-medium text-white hover:bg-teal-700 order-1 sm:order-2">{{ mode === 'add' ? 'Save' : 'Update' }}</button>
                </div>
            </form>
        </div>
    </div>
</template>
