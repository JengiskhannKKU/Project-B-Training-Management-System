<script setup>
import { ref, watch, computed } from 'vue';
import axios from 'axios';
import { useToast } from 'vue-toastification';
import { X, Loader2 } from 'lucide-vue-next';

const toast = useToast();

const props = defineProps({
    show: {
        type: Boolean,
        default: false
    },
    session: {
        type: Object,
        default: null
    },
    programs: {
        type: Array,
        default: () => []
    }
});

const emit = defineEmits(['close', 'updated']);

const isSubmitting = ref(false);
const trainers = ref([]);
const isLoadingTrainers = ref(false);

const form = ref({
    program_id: '',
    title: '',
    start_date: '',
    end_date: '',
    start_time: '',
    end_time: '',
    capacity: '',
    trainer_id: '',
    trainer_name: '',
    trainer_photo_url: '',
    location: '',
    status: 'upcoming'
});

const errors = ref({});

// Watch for session prop changes and populate form
watch(() => props.session, (newSession) => {
    if (newSession) {
        form.value = {
            program_id: newSession.program_id || '',
            title: newSession.title || '',
            start_date: newSession.start_date || '',
            end_date: newSession.end_date || '',
            start_time: newSession.start_time || '',
            end_time: newSession.end_time || '',
            capacity: newSession.capacity || '',
            trainer_id: newSession.trainer_id || '',
            trainer_name: newSession.trainer_name || '',
            trainer_photo_url: newSession.trainer_photo_url || '',
            location: newSession.location || '',
            status: newSession.status || 'upcoming'
        };
    }
}, { immediate: true });

// Fetch trainers when modal opens
watch(() => props.show, (isShown) => {
    if (isShown) {
        fetchTrainers();
    }
});

const fetchTrainers = async () => {
    isLoadingTrainers.value = true;
    try {
        const { data } = await axios.get('/api/users?role=trainer');
        trainers.value = data?.data || [];
    } catch (error) {
        toast.error('Failed to load trainers');
        trainers.value = [];
    } finally {
        isLoadingTrainers.value = false;
    }
};

const handleSubmit = async () => {
    if (!props.session) return;

    isSubmitting.value = true;
    errors.value = {};

    try {
        await axios.get('/sanctum/csrf-cookie');

        const { data } = await axios.put(
            `/api/admin/sessions/${props.session.id}`,
            form.value
        );

        toast.success('Session updated successfully');
        emit('updated', data.data);
        emit('close');
    } catch (error) {
        if (error?.response?.status === 422) {
            errors.value = error.response.data.errors || {};
        }
        toast.error(error?.response?.data?.message || 'Failed to update session');
    } finally {
        isSubmitting.value = false;
    }
};

const handleClose = () => {
    if (!isSubmitting.value) {
        errors.value = {};
        emit('close');
    }
};
</script>

<template>
    <div
        v-if="show"
        class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50 p-4"
        @click.self="handleClose"
    >
        <div class="w-full max-w-3xl rounded-lg bg-white shadow-xl max-h-[90vh] overflow-y-auto">
            <!-- Header -->
            <div class="border-b px-6 py-4 sticky top-0 bg-white z-10">
                <div class="flex items-center justify-between">
                    <div>
                        <h2 class="text-xl font-bold text-gray-900">Edit Session</h2>
                        <p class="mt-1 text-sm text-gray-500">Update session details and settings</p>
                    </div>
                    <button
                        @click="handleClose"
                        :disabled="isSubmitting"
                        class="rounded-lg p-2 hover:bg-gray-100 transition-colors disabled:opacity-50"
                    >
                        <X :size="20" />
                    </button>
                </div>
            </div>

            <!-- Form -->
            <form @submit.prevent="handleSubmit" class="p-6 space-y-5">
                <!-- Program -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        Program <span class="text-red-500">*</span>
                    </label>
                    <select
                        v-model="form.program_id"
                        :disabled="isSubmitting"
                        :class="[
                            'w-full rounded-lg shadow-sm focus:border-[#2f837d] focus:ring-[#2f837d]',
                            errors.program_id ? 'border-red-300' : 'border-gray-300'
                        ]"
                    >
                        <option value="">Select a program</option>
                        <option
                            v-for="program in programs"
                            :key="program.id"
                            :value="program.id"
                        >
                            {{ program.name }}
                        </option>
                    </select>
                    <p v-if="errors.program_id" class="mt-1 text-sm text-red-600">
                        {{ errors.program_id[0] }}
                    </p>
                </div>

                <!-- Session Title -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        Session Title <span class="text-red-500">*</span>
                    </label>
                    <input
                        v-model="form.title"
                        type="text"
                        :disabled="isSubmitting"
                        placeholder="e.g., Leadership Workshop - Week 1"
                        :class="[
                            'w-full rounded-lg shadow-sm focus:border-[#2f837d] focus:ring-[#2f837d]',
                            errors.title ? 'border-red-300' : 'border-gray-300'
                        ]"
                    />
                    <p v-if="errors.title" class="mt-1 text-sm text-red-600">
                        {{ errors.title[0] }}
                    </p>
                </div>

                <!-- Date Range -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            Start Date <span class="text-red-500">*</span>
                        </label>
                        <input
                            v-model="form.start_date"
                            type="date"
                            :disabled="isSubmitting"
                            :class="[
                                'w-full rounded-lg shadow-sm focus:border-[#2f837d] focus:ring-[#2f837d]',
                                errors.start_date ? 'border-red-300' : 'border-gray-300'
                            ]"
                        />
                        <p v-if="errors.start_date" class="mt-1 text-sm text-red-600">
                            {{ errors.start_date[0] }}
                        </p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            End Date <span class="text-red-500">*</span>
                        </label>
                        <input
                            v-model="form.end_date"
                            type="date"
                            :disabled="isSubmitting"
                            :class="[
                                'w-full rounded-lg shadow-sm focus:border-[#2f837d] focus:ring-[#2f837d]',
                                errors.end_date ? 'border-red-300' : 'border-gray-300'
                            ]"
                        />
                        <p v-if="errors.end_date" class="mt-1 text-sm text-red-600">
                            {{ errors.end_date[0] }}
                        </p>
                    </div>
                </div>

                <!-- Time Range -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            Start Time
                        </label>
                        <input
                            v-model="form.start_time"
                            type="time"
                            :disabled="isSubmitting"
                            :class="[
                                'w-full rounded-lg shadow-sm focus:border-[#2f837d] focus:ring-[#2f837d]',
                                errors.start_time ? 'border-red-300' : 'border-gray-300'
                            ]"
                        />
                        <p v-if="errors.start_time" class="mt-1 text-sm text-red-600">
                            {{ errors.start_time[0] }}
                        </p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            End Time
                        </label>
                        <input
                            v-model="form.end_time"
                            type="time"
                            :disabled="isSubmitting"
                            :class="[
                                'w-full rounded-lg shadow-sm focus:border-[#2f837d] focus:ring-[#2f837d]',
                                errors.end_time ? 'border-red-300' : 'border-gray-300'
                            ]"
                        />
                        <p v-if="errors.end_time" class="mt-1 text-sm text-red-600">
                            {{ errors.end_time[0] }}
                        </p>
                    </div>
                </div>

                <!-- Location -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        Location
                    </label>
                    <input
                        v-model="form.location"
                        type="text"
                        :disabled="isSubmitting"
                        placeholder="e.g., Conference Room A"
                        :class="[
                            'w-full rounded-lg shadow-sm focus:border-[#2f837d] focus:ring-[#2f837d]',
                            errors.location ? 'border-red-300' : 'border-gray-300'
                        ]"
                    />
                    <p v-if="errors.location" class="mt-1 text-sm text-red-600">
                        {{ errors.location[0] }}
                    </p>
                </div>

                <!-- Trainer and Capacity -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            Trainer <span class="text-red-500">*</span>
                        </label>
                        <select
                            v-model="form.trainer_id"
                            :disabled="isSubmitting || isLoadingTrainers"
                            :class="[
                                'w-full rounded-lg shadow-sm focus:border-[#2f837d] focus:ring-[#2f837d]',
                                errors.trainer_id ? 'border-red-300' : 'border-gray-300'
                            ]"
                        >
                            <option value="">
                                {{ isLoadingTrainers ? 'Loading trainers...' : 'Select a trainer' }}
                            </option>
                            <option
                                v-for="trainer in trainers"
                                :key="trainer.id"
                                :value="trainer.id"
                            >
                                {{ trainer.name }}
                            </option>
                        </select>
                        <p v-if="errors.trainer_id" class="mt-1 text-sm text-red-600">
                            {{ errors.trainer_id[0] }}
                        </p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            Capacity <span class="text-red-500">*</span>
                        </label>
                        <input
                            v-model="form.capacity"
                            type="number"
                            min="1"
                            :disabled="isSubmitting"
                            placeholder="e.g., 30"
                            :class="[
                                'w-full rounded-lg shadow-sm focus:border-[#2f837d] focus:ring-[#2f837d]',
                                errors.capacity ? 'border-red-300' : 'border-gray-300'
                            ]"
                        />
                        <p v-if="errors.capacity" class="mt-1 text-sm text-red-600">
                            {{ errors.capacity[0] }}
                        </p>
                    </div>
                </div>

                <!-- Status -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        Status
                    </label>
                    <select
                        v-model="form.status"
                        :disabled="isSubmitting"
                        :class="[
                            'w-full rounded-lg shadow-sm focus:border-[#2f837d] focus:ring-[#2f837d]',
                            errors.status ? 'border-red-300' : 'border-gray-300'
                        ]"
                    >
                        <option value="upcoming">Upcoming</option>
                        <option value="open">Open</option>
                        <option value="closed">Closed</option>
                        <option value="completed">Completed</option>
                        <option value="cancelled">Cancelled</option>
                    </select>
                    <p v-if="errors.status" class="mt-1 text-sm text-red-600">
                        {{ errors.status[0] }}
                    </p>
                </div>

                <!-- Trainer Name Override (Optional) -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        Trainer Name Override
                    </label>
                    <input
                        v-model="form.trainer_name"
                        type="text"
                        :disabled="isSubmitting"
                        placeholder="Leave empty to use trainer's profile name"
                        :class="[
                            'w-full rounded-lg shadow-sm focus:border-[#2f837d] focus:ring-[#2f837d]',
                            errors.trainer_name ? 'border-red-300' : 'border-gray-300'
                        ]"
                    />
                    <p class="mt-1 text-xs text-gray-500">
                        Optional: Use a different name to display for this session
                    </p>
                    <p v-if="errors.trainer_name" class="mt-1 text-sm text-red-600">
                        {{ errors.trainer_name[0] }}
                    </p>
                </div>

                <!-- Action Buttons -->
                <div class="flex gap-3 pt-4 border-t">
                    <button
                        type="button"
                        @click="handleClose"
                        :disabled="isSubmitting"
                        class="flex-1 rounded-lg border border-gray-300 px-4 py-2.5 text-sm font-semibold text-gray-700 hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
                    >
                        Cancel
                    </button>
                    <button
                        type="submit"
                        :disabled="isSubmitting"
                        class="flex-1 rounded-lg bg-[#2f837d] px-4 py-2.5 text-sm font-semibold text-white hover:bg-[#266a66] disabled:opacity-50 disabled:cursor-not-allowed transition-colors inline-flex items-center justify-center gap-2"
                    >
                        <Loader2 v-if="isSubmitting" class="h-4 w-4 animate-spin" />
                        <span>{{ isSubmitting ? 'Updating...' : 'Update Session' }}</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</template>
