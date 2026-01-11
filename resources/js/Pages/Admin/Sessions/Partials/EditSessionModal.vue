<script setup>
import { ref, watch, computed } from 'vue';
import axios from 'axios';
import { useToast } from 'vue-toastification';
import { X, Loader2 } from 'lucide-vue-next';
import SearchableSelect from '@/Components/SearchableSelect.vue';

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
    course_id: '',
    title: '',
    start_at: '',
    end_at: '',
    min_participants: 1,
    max_participants: 30,
    registration_start: '',
    registration_end: '',
    trainer_id: '',
    location: '',
    online_link: '',
    mode: 'onsite'
});

const errors = ref({});

// Helper to format date for datetime-local input
const formatDateTime = (date, time) => {
    if (!date) return '';
    const d = new Date(date);
    const dateStr = d.toISOString().split('T')[0];
    return `${dateStr}T${time || '00:00'}`;
};

// Watch for session prop changes and populate form
watch(() => props.session, (newSession) => {
    if (newSession) {
        form.value = {
            course_id: newSession.course_id || '',
            title: newSession.title || '',
            start_at: formatDateTime(newSession.start_date, newSession.start_time),
            end_at: formatDateTime(newSession.end_date, newSession.end_time),
            min_participants: newSession.min_participants || 1,
            max_participants: newSession.max_participants || 30,
            registration_start: newSession.registration_start ? newSession.registration_start.slice(0, 16) : '',
            registration_end: newSession.registration_end ? newSession.registration_end.slice(0, 16) : '',
            trainer_id: newSession.trainer_id || '',
            location: newSession.location || '',
            online_link: newSession.online_link || '',
            mode: newSession.mode || 'onsite'
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
        const { data } = await axios.get('/api/admin/users?role=trainer');
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
        // Prepare payload
        const payload = { ...form.value };
        
        // Helper to split datetime
        const splitDateTime = (dateTimeStr) => {
            if (!dateTimeStr) return { date: null, time: null };
            const [date, time] = dateTimeStr.split('T');
            return { date, time };
        };

        if (payload.start_at) {
            const { date, time } = splitDateTime(payload.start_at);
            payload.start_date = date;
            payload.start_time = time;
        }

        if (payload.end_at) {
            const { date, time } = splitDateTime(payload.end_at);
            payload.end_date = date;
            payload.end_time = time;
        }

        await axios.get('/sanctum/csrf-cookie');

        const { data } = await axios.put(
            `/api/admin/sessions/${props.session.id}`,
            payload
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

const isOnline = computed(() => ['online', 'hybrid'].includes(form.value.mode));
const isOnsite = computed(() => ['onsite', 'hybrid'].includes(form.value.mode));
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
            <form @submit.prevent="handleSubmit" class="p-6 space-y-6">
                <!-- Course & Title -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            Course <span class="text-red-500">*</span>
                        </label>
                        <SearchableSelect
                            v-model="form.course_id"
                            :options="programs"
                            label-key="title"
                            value-key="id"
                            placeholder="Select a course"
                            :disabled="isSubmitting"
                            :error="errors.course_id ? errors.course_id[0] : ''"
                        />
                        <p v-if="errors.course_id" class="mt-1 text-sm text-red-600">
                            {{ errors.course_id[0] }}
                        </p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            Session Title
                        </label>
                        <input
                            v-model="form.title"
                            type="text"
                            :disabled="isSubmitting"
                            placeholder="e.g., Batch 1"
                            :class="[
                                'w-full rounded-lg shadow-sm focus:border-teal-500 focus:ring-teal-500',
                                errors.title ? 'border-red-300' : 'border-gray-300'
                            ]"
                        />
                        <p v-if="errors.title" class="mt-1 text-sm text-red-600">
                            {{ errors.title[0] }}
                        </p>
                    </div>
                </div>

                <!-- Trainer & Mode -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            Trainer <span class="text-red-500">*</span>
                        </label>
                        <select
                            v-model="form.trainer_id"
                            :disabled="isSubmitting || isLoadingTrainers"
                            :class="[
                                'w-full rounded-lg shadow-sm focus:border-teal-500 focus:ring-teal-500',
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
                            Mode <span class="text-red-500">*</span>
                        </label>
                        <select
                            v-model="form.mode"
                            :disabled="isSubmitting"
                            :class="[
                                'w-full rounded-lg shadow-sm focus:border-teal-500 focus:ring-teal-500',
                                errors.mode ? 'border-red-300' : 'border-gray-300'
                            ]"
                        >
                            <option value="onsite">Onsite</option>
                            <option value="online">Online</option>
                            <option value="hybrid">Hybrid</option>
                        </select>
                        <p v-if="errors.mode" class="mt-1 text-sm text-red-600">
                            {{ errors.mode[0] }}
                        </p>
                    </div>
                </div>

                <!-- Session Schedule -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            Start At <span class="text-red-500">*</span>
                        </label>
                        <input
                            v-model="form.start_at"
                            type="datetime-local"
                            :disabled="isSubmitting"
                            :class="[
                                'w-full rounded-lg shadow-sm focus:border-teal-500 focus:ring-teal-500',
                                errors.start_at ? 'border-red-300' : 'border-gray-300'
                            ]"
                        />
                        <p v-if="errors.start_at" class="mt-1 text-sm text-red-600">
                            {{ errors.start_at[0] }}
                        </p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            End At <span class="text-red-500">*</span>
                        </label>
                        <input
                            v-model="form.end_at"
                            type="datetime-local"
                            :disabled="isSubmitting"
                            :class="[
                                'w-full rounded-lg shadow-sm focus:border-teal-500 focus:ring-teal-500',
                                errors.end_at ? 'border-red-300' : 'border-gray-300'
                            ]"
                        />
                        <p v-if="errors.end_at" class="mt-1 text-sm text-red-600">
                            {{ errors.end_at[0] }}
                        </p>
                    </div>
                </div>

                <!-- Registration Period -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            Registration Start
                        </label>
                        <input
                            v-model="form.registration_start"
                            type="datetime-local"
                            :disabled="isSubmitting"
                            :class="[
                                'w-full rounded-lg shadow-sm focus:border-teal-500 focus:ring-teal-500',
                                errors.registration_start ? 'border-red-300' : 'border-gray-300'
                            ]"
                        />
                        <p v-if="errors.registration_start" class="mt-1 text-sm text-red-600">
                            {{ errors.registration_start[0] }}
                        </p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            Registration End
                        </label>
                        <input
                            v-model="form.registration_end"
                            type="datetime-local"
                            :disabled="isSubmitting"
                            :class="[
                                'w-full rounded-lg shadow-sm focus:border-teal-500 focus:ring-teal-500',
                                errors.registration_end ? 'border-red-300' : 'border-gray-300'
                            ]"
                        />
                        <p v-if="errors.registration_end" class="mt-1 text-sm text-red-600">
                            {{ errors.registration_end[0] }}
                        </p>
                    </div>
                </div>

                <!-- Location Details (Dynamic based on Mode) -->
                <div class="space-y-6">
                    <div v-if="isOnsite">
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            Location <span class="text-red-500">*</span>
                        </label>
                        <input
                            v-model="form.location"
                            type="text"
                            :disabled="isSubmitting"
                            placeholder="e.g., Training Room 404"
                            :class="[
                                'w-full rounded-lg shadow-sm focus:border-teal-500 focus:ring-teal-500',
                                errors.location ? 'border-red-300' : 'border-gray-300'
                            ]"
                        />
                        <p v-if="errors.location" class="mt-1 text-sm text-red-600">
                            {{ errors.location[0] }}
                        </p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            Online Link <span class="text-gray-400 text-xs">(Optional)</span>
                        </label>
                        <input
                            v-model="form.online_link"
                            type="text"
                            :disabled="isSubmitting"
                            placeholder="e.g., https://zoom.us/j/123456789"
                            :class="[
                                'w-full rounded-lg shadow-sm focus:border-teal-500 focus:ring-teal-500',
                                errors.online_link ? 'border-red-300' : 'border-gray-300'
                            ]"
                        />
                        <p v-if="errors.online_link" class="mt-1 text-sm text-red-600">
                            {{ errors.online_link[0] }}
                        </p>
                    </div>
                </div>

                <!-- Capacity (Min/Max) -->
                <div>
                    <h3 class="block text-sm font-medium text-gray-700 mb-2">
                        Class Capacity <span class="text-red-500">*</span>
                    </h3>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs text-gray-500 mb-1">
                                Min. Participants
                            </label>
                            <input
                                v-model="form.min_participants"
                                type="number"
                                min="1"
                                :disabled="isSubmitting"
                                placeholder="e.g., 5"
                                :class="[
                                    'w-full rounded-lg shadow-sm focus:border-teal-500 focus:ring-teal-500',
                                    errors.min_participants ? 'border-red-300' : 'border-gray-300'
                                ]"
                            />
                            <p v-if="errors.min_participants" class="mt-1 text-xs text-red-600">
                                {{ errors.min_participants[0] }}
                            </p>
                        </div>
                        <div>
                            <label class="block text-xs text-gray-500 mb-1">
                                Max. Participants
                            </label>
                            <input
                                v-model="form.max_participants"
                                type="number"
                                :min="form.min_participants"
                                :disabled="isSubmitting"
                                placeholder="e.g., 30"
                                :class="[
                                    'w-full rounded-lg shadow-sm focus:border-teal-500 focus:ring-teal-500',
                                    errors.max_participants ? 'border-red-300' : 'border-gray-300'
                                ]"
                            />
                            <p v-if="errors.max_participants" class="mt-1 text-xs text-red-600">
                                {{ errors.max_participants[0] }}
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="flex gap-3 pt-6 border-t mt-6">
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
                        class="flex-1 rounded-lg bg-teal-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-teal-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors inline-flex items-center justify-center gap-2"
                    >
                        <Loader2 v-if="isSubmitting" class="h-4 w-4 animate-spin" />
                        <span>{{ isSubmitting ? 'Updating...' : 'Update Session' }}</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</template>