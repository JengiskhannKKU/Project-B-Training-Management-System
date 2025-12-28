<script setup lang="ts">
import { ref, watch } from 'vue';
import axios from 'axios';
import { useToast } from 'vue-toastification';
import { UploadCloud, Trash2, Loader2 } from 'lucide-vue-next';

const toast = useToast();

const props = defineProps<{
    show: boolean;
    mode: 'add' | 'edit';
    session?: any;
    sessionForm: {
        title: string;
        registration_start: string;
        registration_end: string;
        date: string;
        start_time: string;
        end_time: string;
        location: string;
        trainer: string;
        capacity: string;
        enrollment_limit: string;
        trainer_photo_url: string;
        require_approval: boolean;
        send_confirmation_email: boolean;
        allow_waitlist: boolean;
        allow_cancel_enrollment: boolean;
    };
    sessionErrors: Record<string, string>;
}>();

const emit = defineEmits<{
    close: [];
    submit: [];
}>();

const trainerPhotoPreview = ref<string | null>(props.sessionForm.trainer_photo_url || null);
const trainerPhotoUploading = ref(false);
const trainerPhotoInput = ref<HTMLInputElement | null>(null);

watch(
    () => props.sessionForm.trainer_photo_url,
    (value) => {
        trainerPhotoPreview.value = value || null;
    }
);

watch(
    () => props.sessionForm.enrollment_limit,
    (value) => {
        if (value === 'unlimited') {
            props.sessionForm.capacity = '';
        }
    }
);

const openTrainerPhotoPicker = () => {
    trainerPhotoInput.value?.click();
};

const removeTrainerPhoto = () => {
    trainerPhotoPreview.value = null;
    props.sessionForm.trainer_photo_url = '';
    if (trainerPhotoInput.value) {
        trainerPhotoInput.value.value = '';
    }
};

const handleTrainerPhotoUpload = async (event: Event) => {
    const target = event.target as HTMLInputElement;
    const file = target.files?.[0];
    if (!file) return;

    const allowedTypes = ['image/jpeg', 'image/png', 'image/jpg', 'image/gif', 'image/webp'];
    if (!allowedTypes.includes(file.type)) {
        toast.error('Please select a valid image file (JPEG, PNG, GIF, or WebP)');
        return;
    }

    if (file.size > 2 * 1024 * 1024) {
        toast.error('Image file size must be less than 2MB');
        return;
    }

    const reader = new FileReader();
    reader.onload = (e) => {
        trainerPhotoPreview.value = e.target?.result as string;
    };
    reader.readAsDataURL(file);

    trainerPhotoUploading.value = true;
    try {
        await axios.get('/sanctum/csrf-cookie');
        const formData = new FormData();
        formData.append('image', file);

        const { data } = await axios.post('/api/trainer/upload/image', formData, {
            headers: {
                'Content-Type': 'multipart/form-data',
            },
        });

        props.sessionForm.trainer_photo_url = data.data.url;
        toast.success('Trainer photo uploaded successfully');
    } catch (error: any) {
        const message = error?.response?.data?.message || error?.message || 'Failed to upload trainer photo';
        toast.error(message);
        trainerPhotoPreview.value = null;
        props.sessionForm.trainer_photo_url = '';
    } finally {
        trainerPhotoUploading.value = false;
    }
};
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
                <!-- Session Title -->
                <div>
                    <label class="block text-sm font-medium text-gray-700">
                        Session Title <span class="text-red-500">*</span>
                    </label>
                    <input
                        v-model="sessionForm.title"
                        type="text"
                        placeholder="e.g., Design Thinking Workshop"
                        :class="[
                            'mt-1 block w-full rounded-md focus:border-teal-500 focus:ring-teal-500',
                            sessionErrors.title ? 'border-red-300' : 'border-gray-300'
                        ]"
                    />
                    <p v-if="sessionErrors.title" class="mt-1 text-sm text-red-600">{{ sessionErrors.title }}</p>
                </div>

                <!-- Registration Period -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">
                            Registration Period <span class="text-red-500">*</span>
                        </label>
                        <input
                            v-model="sessionForm.registration_start"
                            type="date"
                            :class="[
                                'mt-1 block w-full rounded-md focus:border-teal-500 focus:ring-teal-500',
                                sessionErrors.registration_start ? 'border-red-300' : 'border-gray-300'
                            ]"
                        />
                        <p v-if="sessionErrors.registration_start" class="mt-1 text-sm text-red-600">{{ sessionErrors.registration_start }}</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">
                            Registration End <span class="text-red-500">*</span>
                        </label>
                        <input
                            v-model="sessionForm.registration_end"
                            type="date"
                            :class="[
                                'mt-1 block w-full rounded-md focus:border-teal-500 focus:ring-teal-500',
                                sessionErrors.registration_end ? 'border-red-300' : 'border-gray-300'
                            ]"
                        />
                        <p v-if="sessionErrors.registration_end" class="mt-1 text-sm text-red-600">{{ sessionErrors.registration_end }}</p>
                    </div>
                </div>

                <!-- Date -->
                <div>
                    <label class="block text-sm font-medium text-gray-700">
                        Date <span class="text-red-500">*</span>
                    </label>
                    <input
                        v-model="sessionForm.date"
                        type="date"
                        :class="[
                            'mt-1 block w-full rounded-md focus:border-teal-500 focus:ring-teal-500',
                            sessionErrors.date ? 'border-red-300' : 'border-gray-300'
                        ]"
                    />
                    <p v-if="sessionErrors.date" class="mt-1 text-sm text-red-600">{{ sessionErrors.date }}</p>
                </div>

                <!-- Time Period -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Start Time</label>
                        <input
                            v-model="sessionForm.start_time"
                            type="time"
                            :class="[
                                'mt-1 block w-full rounded-md focus:border-teal-500 focus:ring-teal-500',
                                sessionErrors.start_time ? 'border-red-300' : 'border-gray-300'
                            ]"
                        />
                        <p v-if="sessionErrors.start_time" class="mt-1 text-sm text-red-600">{{ sessionErrors.start_time }}</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">End Time</label>
                        <input
                            v-model="sessionForm.end_time"
                            type="time"
                            :class="[
                                'mt-1 block w-full rounded-md focus:border-teal-500 focus:ring-teal-500',
                                sessionErrors.end_time ? 'border-red-300' : 'border-gray-300'
                            ]"
                        />
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

                <!-- Enrollment Options -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-3">Enrollment Options</label>
                    <div class="space-y-2">
                        <label class="flex items-center">
                            <input type="checkbox" v-model="sessionForm.require_approval" class="rounded border-gray-300 text-teal-600 focus:ring-teal-500">
                            <span class="ml-2 text-sm text-gray-700">Require approval</span>
                        </label>
                        <label class="flex items-center">
                            <input type="checkbox" v-model="sessionForm.send_confirmation_email" class="rounded border-gray-300 text-teal-600 focus:ring-teal-500">
                            <span class="ml-2 text-sm text-gray-700">Send confirmation email</span>
                        </label>
                        <label class="flex items-center">
                            <input type="checkbox" v-model="sessionForm.allow_waitlist" class="rounded border-gray-300 text-teal-600 focus:ring-teal-500">
                            <span class="ml-2 text-sm text-gray-700">Allow waitlist</span>
                        </label>
                        <label class="flex items-center">
                            <input type="checkbox" v-model="sessionForm.allow_cancel_enrollment" class="rounded border-gray-300 text-teal-600 focus:ring-teal-500">
                            <span class="ml-2 text-sm text-gray-700">Allow participants to cancel enrollment</span>
                        </label>
                    </div>
                </div>

                <!-- Enrollment Limit and Capacity -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Enrollment Limit</label>
                        <div class="mt-2 flex flex-wrap gap-3">
                            <label class="flex items-center gap-2 rounded-lg border border-gray-200 px-3 py-2 text-sm text-gray-700">
                                <input
                                    v-model="sessionForm.enrollment_limit"
                                    type="radio"
                                    value="limited"
                                    class="text-teal-600 focus:ring-teal-500"
                                />
                                Limited
                            </label>
                            <label class="flex items-center gap-2 rounded-lg border border-gray-200 px-3 py-2 text-sm text-gray-700">
                                <input
                                    v-model="sessionForm.enrollment_limit"
                                    type="radio"
                                    value="unlimited"
                                    class="text-teal-600 focus:ring-teal-500"
                                />
                                Unlimited
                            </label>
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">
                            Capacity <span v-if="sessionForm.enrollment_limit === 'limited'" class="text-red-500">*</span>
                        </label>
                        <input
                            v-if="sessionForm.enrollment_limit === 'limited'"
                            v-model="sessionForm.capacity"
                            type="number"
                            min="1"
                            placeholder="e.g., 30"
                            :class="[
                                'mt-1 block w-full rounded-md focus:border-teal-500 focus:ring-teal-500',
                                sessionErrors.capacity ? 'border-red-300' : 'border-gray-300'
                            ]"
                        />
                        <p v-else class="mt-2 text-sm text-gray-500">Unlimited capacity selected.</p>
                        <p v-if="sessionErrors.capacity && sessionForm.enrollment_limit === 'limited'" class="mt-1 text-sm text-red-600">{{ sessionErrors.capacity }}</p>
                    </div>
                </div>

                <!-- Trainer and Photo -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Trainer</label>
                        <input
                            v-model="sessionForm.trainer"
                            type="text"
                            placeholder="e.g., John Doe"
                            class="mt-1 block w-full rounded-md border-gray-300 focus:border-teal-500 focus:ring-teal-500"
                        />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Trainer Photo</label>
                        <div class="mt-2 flex items-center gap-3">
                            <div class="h-16 w-16 overflow-hidden rounded-lg border border-gray-200 bg-gray-50 flex items-center justify-center">
                                <img v-if="trainerPhotoPreview" :src="trainerPhotoPreview" alt="Trainer preview" class="h-full w-full object-cover" />
                                <UploadCloud v-else class="h-6 w-6 text-gray-400" />
                            </div>
                            <div class="flex-1">
                                <input ref="trainerPhotoInput" type="file" class="hidden" accept="image/*" @change="handleTrainerPhotoUpload" />
                                <div class="flex flex-wrap gap-2">
                                    <button type="button" @click="openTrainerPhotoPicker" class="inline-flex items-center gap-2 rounded-lg border border-gray-300 px-3 py-2 text-xs font-medium text-gray-700 hover:bg-gray-50">
                                        <Loader2 v-if="trainerPhotoUploading" class="h-4 w-4 animate-spin" />
                                        <UploadCloud v-else class="h-4 w-4" />
                                        {{ trainerPhotoUploading ? 'Uploading' : 'Upload' }}
                                    </button>
                                    <button type="button" @click="removeTrainerPhoto" class="inline-flex items-center gap-2 rounded-lg border border-gray-300 px-3 py-2 text-xs font-medium text-gray-700 hover:bg-gray-50">
                                        <Trash2 class="h-4 w-4 text-red-500" />
                                        Remove
                                    </button>
                                </div>
                                <p class="mt-1 text-xs text-gray-500">JPG, PNG, GIF, or WebP up to 2MB.</p>
                            </div>
                        </div>
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
