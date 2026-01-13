<script setup>
import { ref, watch, computed } from 'vue';
import axios from 'axios';
import { useToast } from 'vue-toastification';
import { X, Loader2, Plus, Trash2, Copy } from 'lucide-vue-next';
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
    courses: {
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
    min_participants: 1,
    capacity: 30,
    registration_start: '',
    registration_end: '',
    trainer_id: '',
    location: '',
    online_link: '',
    mode: 'onsite',
    status: '',
    session_days: [
        { id: null, date: '', start_time: '', end_time: '' }
    ]
});

const errors = ref({});

// Helper to format date for datetime-local input
const formatDateTime = (dateTime) => {
    if (!dateTime) return '';
    // If it's already in correct format, return it
    if (dateTime.includes('T') && dateTime.length === 16) return dateTime;

    const d = new Date(dateTime);
    // Format to YYYY-MM-DDTHH:mm
    const year = d.getFullYear();
    const month = String(d.getMonth() + 1).padStart(2, '0');
    const day = String(d.getDate()).padStart(2, '0');
    const hours = String(d.getHours()).padStart(2, '0');
    const minutes = String(d.getMinutes()).padStart(2, '0');
    return `${year}-${month}-${day}T${hours}:${minutes}`;
};

// Watch for session prop changes and populate form
watch(() => props.session, (newSession) => {
    if (newSession) {
        // Format session days for the form
        const sessionDays = newSession.session_days?.map(day => ({
            id: day.id,
            date: day.date,
            start_time: day.start_time?.substring(0, 5) || '', // HH:mm format
            end_time: day.end_time?.substring(0, 5) || ''
        })) || [{ id: null, date: '', start_time: '', end_time: '' }];

        form.value = {
            course_id: newSession.course_id || '',
            title: newSession.title || '',
            min_participants: newSession.min_participants || 1,
            capacity: newSession.capacity || 30,
            registration_start: newSession.registration_start ? formatDateTime(newSession.registration_start) : '',
            registration_end: newSession.registration_end ? formatDateTime(newSession.registration_end) : '',
            trainer_id: newSession.trainer_id || '',
            location: newSession.location || '',
            online_link: newSession.online_link || '',
            mode: newSession.mode || 'onsite',
            status: newSession.status || 'scheduled',
            session_days: sessionDays
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
        const { data } = await axios.get('/api/admin/users', {
            params: {
                role: 'trainer',
                per_page: 100
            }
        });
        trainers.value = data?.data?.data || data?.data || [];
    } catch (error) {
        toast.error('Failed to load trainers');
        trainers.value = [];
    } finally {
        isLoadingTrainers.value = false;
    }
};

const addSessionDay = () => {
    form.value.session_days.push({ id: null, date: '', start_time: '', end_time: '' });
};

const removeSessionDay = (index) => {
    if (form.value.session_days.length > 1) {
        form.value.session_days.splice(index, 1);
    } else {
        toast.error('At least one session date is required');
    }
};

const copySessionDay = (index) => {
    const dayToCopy = { ...form.value.session_days[index], id: null };
    form.value.session_days.splice(index + 1, 0, dayToCopy);
};

const handleSubmit = async () => {
    if (!props.session) return;

    isSubmitting.value = true;
    errors.value = {};

    try {
        // Prepare payload
        const payload = { ...form.value };

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

const getSessionDayError = (index, field) => {
    const fieldKey = `session_days.${index}.${field}`;
    return errors.value[fieldKey] ? errors.value[fieldKey][0] : '';
};

const hasSessionDayError = (index, field) => {
    return !!getSessionDayError(index, field);
};
</script>

<template>
    <div
        v-if="show"
        class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50 p-4"
        @click.self="handleClose"
    >
        <div class="w-full max-w-4xl rounded-lg bg-white shadow-xl max-h-[90vh] overflow-y-auto">
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
                            :options="courses"
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
                        <SearchableSelect
                            v-model="form.trainer_id"
                            :options="trainers"
                            label-key="name"
                            value-key="id"
                            placeholder="Select a trainer"
                            :disabled="isSubmitting || isLoadingTrainers"
                            :error="errors.trainer_id ? errors.trainer_id[0] : ''"
                        />
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

                <!-- Session Dates -->
                <div>
                    <div class="flex items-center justify-between mb-3">
                        <label class="block text-sm font-medium text-gray-700">
                            Session Dates <span class="text-red-500">*</span>
                        </label>
                        <button
                            type="button"
                            @click="addSessionDay"
                            :disabled="isSubmitting"
                            class="inline-flex items-center gap-1 text-sm text-teal-600 hover:text-teal-700 disabled:opacity-50"
                        >
                            <Plus :size="16" />
                            Add Date
                        </button>
                    </div>

                    <div v-if="errors.session_days" class="mb-2 text-sm text-red-600">
                        {{ errors.session_days[0] }}
                    </div>

                    <div class="space-y-3">
                        <div
                            v-for="(day, index) in form.session_days"
                            :key="index"
                            class="p-4 border border-gray-200 rounded-lg bg-gray-50"
                        >
                            <div class="grid grid-cols-1 md:grid-cols-4 gap-3">
                                <div>
                                    <label class="block text-xs font-medium text-gray-600 mb-1">
                                        Date <span class="text-red-500">*</span>
                                    </label>
                                    <input
                                        v-model="day.date"
                                        type="date"
                                        :disabled="isSubmitting"
                                        :class="[
                                            'w-full text-sm rounded-lg shadow-sm focus:border-teal-500 focus:ring-teal-500',
                                            hasSessionDayError(index, 'date') ? 'border-red-300' : 'border-gray-300'
                                        ]"
                                    />
                                    <p v-if="getSessionDayError(index, 'date')" class="mt-1 text-xs text-red-600">
                                        {{ getSessionDayError(index, 'date') }}
                                    </p>
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-gray-600 mb-1">
                                        Start Time <span class="text-red-500">*</span>
                                    </label>
                                    <input
                                        v-model="day.start_time"
                                        type="time"
                                        :disabled="isSubmitting"
                                        :class="[
                                            'w-full text-sm rounded-lg shadow-sm focus:border-teal-500 focus:ring-teal-500',
                                            hasSessionDayError(index, 'start_time') ? 'border-red-300' : 'border-gray-300'
                                        ]"
                                    />
                                    <p v-if="getSessionDayError(index, 'start_time')" class="mt-1 text-xs text-red-600">
                                        {{ getSessionDayError(index, 'start_time') }}
                                    </p>
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-gray-600 mb-1">
                                        End Time <span class="text-red-500">*</span>
                                    </label>
                                    <input
                                        v-model="day.end_time"
                                        type="time"
                                        :disabled="isSubmitting"
                                        :class="[
                                            'w-full text-sm rounded-lg shadow-sm focus:border-teal-500 focus:ring-teal-500',
                                            hasSessionDayError(index, 'end_time') ? 'border-red-300' : 'border-gray-300'
                                        ]"
                                    />
                                    <p v-if="getSessionDayError(index, 'end_time')" class="mt-1 text-xs text-red-600">
                                        {{ getSessionDayError(index, 'end_time') }}
                                    </p>
                                </div>
                                <div class="flex items-end gap-2">
                                    <button
                                        type="button"
                                        @click="copySessionDay(index)"
                                        :disabled="isSubmitting"
                                        class="flex-1 px-3 py-2 text-sm text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 disabled:opacity-50"
                                        title="Copy to new date"
                                    >
                                        <Copy :size="16" class="mx-auto" />
                                    </button>
                                    <button
                                        type="button"
                                        @click="removeSessionDay(index)"
                                        :disabled="isSubmitting || form.session_days.length <= 1"
                                        class="flex-1 px-3 py-2 text-sm text-red-600 bg-white border border-gray-300 rounded-lg hover:bg-red-50 disabled:opacity-50"
                                        title="Remove date"
                                    >
                                        <Trash2 :size="16" class="mx-auto" />
                                    </button>
                                </div>
                            </div>
                        </div>
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
                                v-model="form.capacity"
                                type="number"
                                :min="form.min_participants"
                                :disabled="isSubmitting"
                                placeholder="e.g., 30"
                                :class="[
                                    'w-full rounded-lg shadow-sm focus:border-teal-500 focus:ring-teal-500',
                                    errors.capacity ? 'border-red-300' : 'border-gray-300'
                                ]"
                            />
                            <p v-if="errors.capacity" class="mt-1 text-xs text-red-600">
                                {{ errors.capacity[0] }}
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Status -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        Status <span class="text-red-500">*</span>
                    </label>
                    <select
                        v-model="form.status"
                        :disabled="isSubmitting"
                        :class="[
                            'w-full rounded-lg shadow-sm focus:border-teal-500 focus:ring-teal-500',
                            errors.status ? 'border-red-300' : 'border-gray-300'
                        ]"
                    >
                        <option value="scheduled">Scheduled</option>
                        <option value="ongoing">Ongoing</option>
                        <option value="completed">Completed</option>
                        <option value="cancelled">Cancelled</option>
                    </select>
                    <p v-if="errors.status" class="mt-1 text-sm text-red-600">
                        {{ errors.status[0] }}
                    </p>
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
