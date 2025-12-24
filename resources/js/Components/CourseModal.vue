<script setup lang="ts">
import { ref, computed } from 'vue';
import { useForm } from '@inertiajs/vue3';
import { useToast } from 'vue-toastification';
import axios from 'axios';
import {
    X,
    Bold,
    Italic,
    Underline,
    List,
    ListOrdered,
    Link as LinkIcon,
    Code,
    MapPin,
    Image,
    AlertTriangle,
    CheckCircle2,
    XCircle,
    Paperclip,
    UploadCloud,
    Trash2,
    Loader2,
} from 'lucide-vue-next';

const toast = useToast();

interface Props {
    show: boolean;
    enablePreviewDialogs?: boolean;
    course?: {
        id?: number;
        title?: string;
        short_description?: string;
        full_description?: string;
        category?: string;
        level?: string;
        enrollment_limit?: string;
        max_participants?: string;
        min_participants?: string;
        date?: string;
        start_time?: string;
        end_time?: string;
        location?: string;
        registration_start?: string;
        registration_end?: string;
        require_approval?: boolean;
        send_confirmation_email?: boolean;
        allow_waitlist?: boolean;
        allow_cancel_enrollment?: boolean;
        certificate_template?: string;
        certificate_type?: string;
    } | null;
}

const props = withDefaults(defineProps<Props>(), {
    show: false,
    course: null,
    enablePreviewDialogs: true,
});

const emit = defineEmits<{
    close: [];
    success: [payload?: Record<string, unknown>];
}>();

const errors = ref<Record<string, string>>({});
const showConfirmDialog = ref(false);
const showSuccessDialog = ref(false);
const showRejectedDialog = ref(false);
const showAttachmentsModal = ref(false);
const outcomePreview = ref<'success' | 'rejected'>('success');

// Image upload state
const imagePreview = ref<string | null>(null);
const imageUploading = ref(false);
const imageUrl = ref<string>('');
const imagePath = ref<string>('');
const fileInputRef = ref<HTMLInputElement | null>(null);

const form = useForm({
    title: props.course?.title || '',
    short_description: props.course?.short_description || '',
    full_description: props.course?.full_description || '',
    category: props.course?.category || '',
    level: props.course?.level || 'beginner',
    enrollment_limit: props.course?.enrollment_limit || 'limited',
    max_participants: props.course?.max_participants || '',
    min_participants: props.course?.min_participants || '',
    date: props.course?.date || '',
    start_time: props.course?.start_time || '',
    end_time: props.course?.end_time || '',
    location: props.course?.location || '',
    thumbnail: null,
    registration_start: props.course?.registration_start || '',
    registration_end: props.course?.registration_end || '',
    require_approval: props.course?.require_approval || false,
    send_confirmation_email: props.course?.send_confirmation_email || false,
    allow_waitlist: props.course?.allow_waitlist || false,
    allow_cancel_enrollment: props.course?.allow_cancel_enrollment || false,
    certificate_template: props.course?.certificate_template || 'standard',
    certificate_type: props.course?.certificate_type || 'free',
});

const attachments = ref([
    { id: 1, name: 'Tech design requirements.png', size: '200 KB', progress: 100, status: 'uploaded' as const },
    { id: 2, name: 'Dashboard prototype recording.jpg', size: '16 MB', progress: 40, status: 'uploading' as const },
]);

const categories = [
    'Design',
    'Development',
    'Marketing',
    'Business',
    'IT & Software',
    'Professional Development',
];

const isEditMode = computed(() => !!props.course?.id);

const validateForm = () => {
    errors.value = {};

    // Required field validations
    if (!form.title.trim()) {
        errors.value.title = 'Course title is required';
    }

    if (!form.short_description.trim()) {
        errors.value.short_description = 'Short description is required';
    }

    if (!form.full_description.trim()) {
        errors.value.full_description = 'Full description is required';
    }

    if (!form.category) {
        errors.value.category = 'Please select a category';
    }

    if (form.enrollment_limit === 'limited' && !form.max_participants) {
        errors.value.max_participants = 'Maximum participants is required for limited enrollment';
    }

    if (!form.date) {
        errors.value.date = 'Date is required';
    }

    if (!form.start_time) {
        errors.value.start_time = 'Start time is required';
    }

    if (!form.end_time) {
        errors.value.end_time = 'End time is required';
    }

    if (!form.location.trim()) {
        errors.value.location = 'Location is required';
    }

    if (!form.registration_start) {
        errors.value.registration_start = 'Registration start date is required';
    }

    if (!form.registration_end) {
        errors.value.registration_end = 'Registration end date is required';
    }

    // Additional validations
    if (form.min_participants && form.max_participants) {
        const min = parseInt(form.min_participants);
        const max = parseInt(form.max_participants);
        if (min > max) {
            errors.value.min_participants = 'Minimum participants cannot exceed maximum';
        }
    }

    return Object.keys(errors.value).length === 0;
};

const handleSubmit = async () => {
    if (validateForm()) {
        showConfirmDialog.value = true;
    } else {
        const firstError = Object.keys(errors.value)[0];
        const element = document.getElementById(firstError);
        if (element) {
            element.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }
    }
};

const saveDraft = () => {
    toast.info('Draft saved locally. Connect API to persist.', { timeout: 3500 });
};

const confirmRequest = () => {
    showConfirmDialog.value = false;
    const formPayload = typeof form.data === 'function' ? form.data() : { ...form };
    
    // Add image_url to payload if uploaded
    const payload = {
        ...formPayload,
        image_url: imageUrl.value || null,
    };

    // If consumer wants to skip preview overlays, emit directly
    if (!props.enablePreviewDialogs) {
        emit('success', payload);
        form.reset();
        errors.value = {};
        removeImage();
        return;
    }

    if (outcomePreview.value === 'rejected') {
        showRejectedDialog.value = true;
        toast.error('Course request rejected (UI preview)', { timeout: 4000 });
    } else {
        showSuccessDialog.value = true;
        toast.success('Course published successfully (UI preview)', { timeout: 4000 });
    }

    emit('success', payload);
    form.reset();
    errors.value = {};
};

const closeAllDialogs = () => {
    showConfirmDialog.value = false;
    showSuccessDialog.value = false;
    showRejectedDialog.value = false;
};

const openAttachments = () => {
    showAttachmentsModal.value = true;
};

const removeAttachment = (id: number) => {
    attachments.value = attachments.value.filter((file) => file.id !== id);
};

const simulateUpload = () => {
    const nextId = attachments.value.length + 1;
    attachments.value.push({
        id: nextId,
        name: `New attachment ${nextId}.png`,
        size: '400 KB',
        progress: 60,
        status: 'uploading',
    });
};

const handleClose = () => {
    closeAllDialogs();
    emit('close');
};

// Handle image file selection and upload
const handleImageUpload = async (event: Event) => {
    const target = event.target as HTMLInputElement;
    const file = target.files?.[0];
    if (!file) return;

    // Validate file type
    const allowedTypes = ['image/jpeg', 'image/png', 'image/jpg', 'image/gif', 'image/webp'];
    if (!allowedTypes.includes(file.type)) {
        toast.error('Please select a valid image file (JPEG, PNG, GIF, or WebP)');
        return;
    }

    // Validate file size (2MB max)
    if (file.size > 2 * 1024 * 1024) {
        toast.error('Image file size must be less than 2MB');
        return;
    }

    // Show preview immediately
    const reader = new FileReader();
    reader.onload = (e) => {
        imagePreview.value = e.target?.result as string;
    };
    reader.readAsDataURL(file);

    // Upload to server
    imageUploading.value = true;
    try {
        await axios.get('/sanctum/csrf-cookie');
        const formData = new FormData();
        formData.append('image', file);
        
        const { data } = await axios.post('/api/trainer/upload/image', formData, {
            headers: {
                'Content-Type': 'multipart/form-data',
            },
        });
        
        imageUrl.value = data.data.url;
        imagePath.value = data.data.path;
        toast.success('Image uploaded successfully');
    } catch (error: any) {
        const message = error?.response?.data?.message || error?.message || 'Failed to upload image';
        toast.error(message);
        imagePreview.value = null;
    } finally {
        imageUploading.value = false;
    }
};

const removeImage = () => {
    imagePreview.value = null;
    imageUrl.value = '';
    imagePath.value = '';
    if (fileInputRef.value) {
        fileInputRef.value.value = '';
    }
};

const triggerFileInput = () => {
    fileInputRef.value?.click();
};
</script>

<template>
    <!-- Course Modal -->
    <div v-if="show" class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50 p-4" @click.self="handleClose">
        <div class="relative max-h-[90vh] w-full max-w-3xl overflow-y-auto rounded-lg bg-white shadow-xl">
            <div class="sticky top-0 z-10 border-b bg-white px-6 py-4">
                <div class="flex items-center justify-between">
                    <div class="text-center flex-1">
                        <h2 class="text-xl font-bold text-gray-900">
                            {{ isEditMode ? 'Edit Course' : 'Create New Course' }}
                        </h2>
                        <p class="mt-1 text-sm text-gray-500">
                            {{ isEditMode
                                ? 'Update the course information below.'
                                : 'To create a training course, start by identifying the need and define clear learning objectives.'
                            }}
                        </p>
                    </div>
                    <button @click="handleClose" class="rounded-lg p-2 hover:bg-gray-100">
                        <X :size="20" />
                    </button>
                </div>
            </div>

            <form @submit.prevent="handleSubmit" class="p-6 space-y-6">
                <!-- Course Title -->
                <div>
                    <label for="title" class="block text-sm font-medium text-gray-700">
                        Course Title <span class="text-red-500">*</span>
                    </label>
                    <input
                        id="title"
                        v-model="form.title"
                        type="text"
                        placeholder="e.g., Advanced UX Design for Enterprise Application"
                        :class="[
                            'mt-1 block w-full rounded-md shadow-sm focus:ring-teal-500',
                            errors.title ? 'border-red-300 focus:border-red-500' : 'border-gray-300 focus:border-teal-500'
                        ]"
                    />
                    <p v-if="errors.title" class="mt-1 text-sm text-red-600">{{ errors.title }}</p>
                </div>

                <!-- Short Description -->
                <div>
                    <div class="flex items-center justify-between">
                        <label for="short_description" class="block text-sm font-medium text-gray-700">
                            Short Description <span class="text-red-500">*</span>
                        </label>
                        <span class="text-xs text-gray-500">{{ form.short_description.length }}/300</span>
                    </div>
                    <textarea
                        id="short_description"
                        v-model="form.short_description"
                        rows="2"
                        maxlength="300"
                        placeholder="A brief summary of what the course is about."
                        :class="[
                            'mt-1 block w-full rounded-md shadow-sm focus:ring-teal-500',
                            errors.short_description ? 'border-red-300 focus:border-red-500' : 'border-gray-300 focus:border-teal-500'
                        ]"
                    ></textarea>
                    <p v-if="errors.short_description" class="mt-1 text-sm text-red-600">{{ errors.short_description }}</p>
                </div>

                <!-- Full Description -->
                <div>
                    <label for="full_description" class="block text-sm font-medium text-gray-700">
                        Full Description / Objectives <span class="text-red-500">*</span>
                    </label>
                    <div :class="[
                        'mt-1 rounded-md border',
                        errors.full_description ? 'border-red-300' : 'border-gray-300'
                    ]">
                        <div class="flex items-center gap-1 border-b border-gray-200 bg-gray-50 p-2">
                            <button type="button" class="rounded p-1 hover:bg-gray-200" title="Bold">
                                <Bold :size="16" />
                            </button>
                            <button type="button" class="rounded p-1 hover:bg-gray-200" title="Italic">
                                <Italic :size="16" />
                            </button>
                            <button type="button" class="rounded p-1 hover:bg-gray-200" title="Underline">
                                <Underline :size="16" />
                            </button>
                            <div class="mx-2 h-4 w-px bg-gray-300"></div>
                            <button type="button" class="rounded p-1 hover:bg-gray-200" title="Bullet List">
                                <List :size="16" />
                            </button>
                            <button type="button" class="rounded p-1 hover:bg-gray-200" title="Numbered List">
                                <ListOrdered :size="16" />
                            </button>
                            <div class="mx-2 h-4 w-px bg-gray-300"></div>
                            <button type="button" class="rounded p-1 hover:bg-gray-200" title="Link">
                                <LinkIcon :size="16" />
                            </button>
                            <button type="button" class="rounded p-1 hover:bg-gray-200" title="Code">
                                <Code :size="16" />
                            </button>
                        </div>
                        <textarea
                            id="full_description"
                            v-model="form.full_description"
                            rows="4"
                            placeholder="Describe the course in detail. You can use Markdown for formatting."
                            class="block w-full border-0 p-3 focus:ring-0"
                        ></textarea>
                    </div>
                    <p v-if="errors.full_description" class="mt-1 text-sm text-red-600">{{ errors.full_description }}</p>
                </div>

                <!-- Course Category -->
                <div>
                    <label for="category" class="block text-sm font-medium text-gray-700">
                        Course Category <span class="text-red-500">*</span>
                    </label>
                    <select
                        id="category"
                        v-model="form.category"
                        :class="[
                            'mt-1 block w-full rounded-md shadow-sm focus:ring-teal-500',
                            errors.category ? 'border-red-300 focus:border-red-500' : 'border-gray-300 focus:border-teal-500'
                        ]"
                    >
                        <option value="">Select Category</option>
                        <option v-for="cat in categories" :key="cat" :value="cat">{{ cat }}</option>
                    </select>
                    <p v-if="errors.category" class="mt-1 text-sm text-red-600">{{ errors.category }}</p>
                </div>

                <!-- Course Level -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-3">Course Level</label>
                    <div class="grid grid-cols-3 gap-3">
                        <label class="relative flex cursor-pointer items-center justify-center rounded-lg border-2 px-4 py-3 transition" :class="form.level === 'beginner' ? 'border-teal-500 bg-teal-50' : 'border-gray-200 hover:border-gray-300'">
                            <input type="radio" v-model="form.level" value="beginner" class="sr-only" />
                            <div class="text-center">
                                <div class="text-sm font-medium" :class="form.level === 'beginner' ? 'text-teal-700' : 'text-gray-900'">Beginner</div>
                                <div class="text-xs text-gray-500">No prior experience required</div>
                            </div>
                        </label>
                        <label class="relative flex cursor-pointer items-center justify-center rounded-lg border-2 px-4 py-3 transition" :class="form.level === 'intermediate' ? 'border-teal-500 bg-teal-50' : 'border-gray-200 hover:border-gray-300'">
                            <input type="radio" v-model="form.level" value="intermediate" class="sr-only" />
                            <div class="text-center">
                                <div class="text-sm font-medium" :class="form.level === 'intermediate' ? 'text-teal-700' : 'text-gray-900'">Intermediate</div>
                                <div class="text-xs text-gray-500">Some experience required</div>
                            </div>
                        </label>
                        <label class="relative flex cursor-pointer items-center justify-center rounded-lg border-2 px-4 py-3 transition" :class="form.level === 'advanced' ? 'border-teal-500 bg-teal-50' : 'border-gray-200 hover:border-gray-300'">
                            <input type="radio" v-model="form.level" value="advanced" class="sr-only" />
                            <div class="text-center">
                                <div class="text-sm font-medium" :class="form.level === 'advanced' ? 'text-teal-700' : 'text-gray-900'">Advanced</div>
                                <div class="text-xs text-gray-500">Extensive experience required</div>
                            </div>
                        </label>
                    </div>
                </div>

                <!-- Enrollment Limit -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-3">Enrollment Limit</label>
                    <div class="grid grid-cols-2 gap-3">
                        <label class="relative flex cursor-pointer items-center rounded-lg border-2 px-4 py-3 transition" :class="form.enrollment_limit === 'limited' ? 'border-teal-500 bg-teal-50' : 'border-gray-200 hover:border-gray-300'">
                            <input type="radio" v-model="form.enrollment_limit" value="limited" class="sr-only" />
                            <div class="flex-1 text-sm font-medium" :class="form.enrollment_limit === 'limited' ? 'text-teal-700' : 'text-gray-900'">Limited</div>
                        </label>
                        <label class="relative flex cursor-pointer items-center rounded-lg border-2 px-4 py-3 transition" :class="form.enrollment_limit === 'unlimited' ? 'border-teal-500 bg-teal-50' : 'border-gray-200 hover:border-gray-300'">
                            <input type="radio" v-model="form.enrollment_limit" value="unlimited" class="sr-only" />
                            <div class="flex-1 text-sm font-medium" :class="form.enrollment_limit === 'unlimited' ? 'text-teal-700' : 'text-gray-900'">Unlimited</div>
                        </label>
                    </div>
                </div>

                <!-- Max/Min Participants -->
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label for="max_participants" class="block text-sm font-medium text-gray-700">
                            Maximum Participants
                            <span v-if="form.enrollment_limit === 'limited'" class="text-red-500">*</span>
                        </label>
                        <input
                            id="max_participants"
                            v-model="form.max_participants"
                            type="number"
                            :disabled="form.enrollment_limit === 'unlimited'"
                            :class="[
                                'mt-1 block w-full rounded-md shadow-sm focus:ring-teal-500 disabled:bg-gray-100',
                                errors.max_participants ? 'border-red-300 focus:border-red-500' : 'border-gray-300 focus:border-teal-500'
                            ]"
                        />
                        <p v-if="errors.max_participants" class="mt-1 text-sm text-red-600">{{ errors.max_participants }}</p>
                    </div>
                    <div>
                        <label for="min_participants" class="block text-sm font-medium text-gray-700">Minimum Participants (Optional)</label>
                        <input
                            id="min_participants"
                            v-model="form.min_participants"
                            type="number"
                            :class="[
                                'mt-1 block w-full rounded-md shadow-sm focus:ring-teal-500',
                                errors.min_participants ? 'border-red-300 focus:border-red-500' : 'border-gray-300 focus:border-teal-500'
                            ]"
                        />
                        <p v-if="errors.min_participants" class="mt-1 text-sm text-red-600">{{ errors.min_participants }}</p>
                    </div>
                </div>
                <p class="text-xs text-gray-500">*Registrants will close automatically when the roll is full</p>

                <!-- Date & Time -->
                <div class="grid grid-cols-3 gap-4">
                    <div>
                        <label for="date" class="block text-sm font-medium text-gray-700">
                            Date <span class="text-red-500">*</span>
                        </label>
                        <input
                            id="date"
                            v-model="form.date"
                            type="date"
                            :class="[
                                'mt-1 block w-full rounded-md shadow-sm focus:ring-teal-500',
                                errors.date ? 'border-red-300 focus:border-red-500' : 'border-gray-300 focus:border-teal-500'
                            ]"
                        />
                        <p v-if="errors.date" class="mt-1 text-sm text-red-600">{{ errors.date }}</p>
                    </div>
                    <div>
                        <label for="start_time" class="block text-sm font-medium text-gray-700">
                            Time <span class="text-red-500">*</span>
                        </label>
                        <input
                            id="start_time"
                            v-model="form.start_time"
                            type="time"
                            :class="[
                                'mt-1 block w-full rounded-md shadow-sm focus:ring-teal-500',
                                errors.start_time ? 'border-red-300 focus:border-red-500' : 'border-gray-300 focus:border-teal-500'
                            ]"
                        />
                        <p v-if="errors.start_time" class="mt-1 text-sm text-red-600">{{ errors.start_time }}</p>
                    </div>
                    <div>
                        <label for="end_time" class="block text-sm font-medium text-gray-700">
                            <span class="text-red-500">*</span>
                        </label>
                        <input
                            id="end_time"
                            v-model="form.end_time"
                            type="time"
                            :class="[
                                'mt-1 block w-full rounded-md shadow-sm focus:ring-teal-500',
                                errors.end_time ? 'border-red-300 focus:border-red-500' : 'border-gray-300 focus:border-teal-500'
                            ]"
                        />
                        <p v-if="errors.end_time" class="mt-1 text-sm text-red-600">{{ errors.end_time }}</p>
                    </div>
                </div>

                <!-- Location -->
                <div>
                    <label for="location" class="block text-sm font-medium text-gray-700">
                        Location <span class="text-red-500">*</span>
                    </label>
                    <div class="relative mt-1">
                        <input
                            id="location"
                            v-model="form.location"
                            type="text"
                            placeholder="e.g., Main Conference Room"
                            :class="[
                                'block w-full rounded-md pr-10 shadow-sm focus:ring-teal-500',
                                errors.location ? 'border-red-300 focus:border-red-500' : 'border-gray-300 focus:border-teal-500'
                            ]"
                        />
                        <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-3">
                            <MapPin :size="20" class="text-gray-400" />
                        </div>
                    </div>
                    <p v-if="errors.location" class="mt-1 text-sm text-red-600">{{ errors.location }}</p>
                </div>

                <!-- Course Thumbnail -->
                <div>
                    <label class="block text-sm font-medium text-gray-700">Course Thumbnail</label>
                    
                    <!-- Hidden file input -->
                    <input
                        ref="fileInputRef"
                        type="file"
                        accept="image/jpeg,image/png,image/jpg,image/gif,image/webp"
                        class="hidden"
                        @change="handleImageUpload"
                    />
                    
                    <!-- Upload area / Preview -->
                    <div v-if="!imagePreview" class="mt-1">
                        <div
                            @click="triggerFileInput"
                            class="flex cursor-pointer justify-center rounded-lg border-2 border-dashed border-gray-300 px-6 py-10 hover:border-teal-400 transition-colors"
                        >
                            <div class="text-center">
                                <UploadCloud :size="48" class="mx-auto text-teal-500" />
                                <div class="mt-4 flex text-sm text-gray-600">
                                    <span class="font-medium text-teal-600 hover:text-teal-500">Click to upload</span>
                                    <p class="pl-1">or drag and drop</p>
                                </div>
                                <p class="text-xs text-gray-500 mt-1">JPG, PNG, GIF, WebP (max 2MB)</p>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Image preview with remove option -->
                    <div v-else class="mt-1 relative">
                        <div class="relative rounded-lg overflow-hidden border border-gray-200">
                            <img
                                :src="imagePreview"
                                alt="Course thumbnail preview"
                                class="w-full h-48 object-cover"
                            />
                            <!-- Upload progress overlay -->
                            <div v-if="imageUploading" class="absolute inset-0 bg-black/50 flex items-center justify-center">
                                <div class="text-center text-white">
                                    <Loader2 :size="32" class="animate-spin mx-auto" />
                                    <p class="mt-2 text-sm">Uploading...</p>
                                </div>
                            </div>
                            <!-- Remove button -->
                            <button
                                v-if="!imageUploading"
                                type="button"
                                @click="removeImage"
                                class="absolute top-2 right-2 rounded-full bg-red-500 p-2 text-white hover:bg-red-600 shadow-lg"
                                title="Remove image"
                            >
                                <Trash2 :size="16" />
                            </button>
                        </div>
                        <p v-if="imageUrl" class="mt-2 text-xs text-green-600 flex items-center gap-1">
                            <CheckCircle2 :size="14" />
                            Image uploaded successfully
                        </p>
                    </div>
                </div>

                <!-- Registration Period -->
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label for="registration_start" class="block text-sm font-medium text-gray-700">
                            Registration Period <span class="text-red-500">*</span>
                        </label>
                        <input
                            id="registration_start"
                            v-model="form.registration_start"
                            type="date"
                            :class="[
                                'mt-1 block w-full rounded-md shadow-sm focus:ring-teal-500',
                                errors.registration_start ? 'border-red-300 focus:border-red-500' : 'border-gray-300 focus:border-teal-500'
                            ]"
                        />
                        <p v-if="errors.registration_start" class="mt-1 text-sm text-red-600">{{ errors.registration_start }}</p>
                    </div>
                    <div>
                        <label for="registration_end" class="block text-sm font-medium text-gray-700">
                            <span class="text-red-500">*</span>
                        </label>
                        <input
                            id="registration_end"
                            v-model="form.registration_end"
                            type="date"
                            :class="[
                                'mt-1 block w-full rounded-md shadow-sm focus:ring-teal-500',
                                errors.registration_end ? 'border-red-300 focus:border-red-500' : 'border-gray-300 focus:border-teal-500'
                            ]"
                        />
                        <p v-if="errors.registration_end" class="mt-1 text-sm text-red-600">{{ errors.registration_end }}</p>
                    </div>
                </div>

                <!-- Enrollment Options -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-3">Enrollment Options</label>
                    <div class="space-y-2">
                        <label class="flex items-center">
                            <input type="checkbox" v-model="form.require_approval" class="rounded border-gray-300 text-teal-600 focus:ring-teal-500">
                            <span class="ml-2 text-sm text-gray-700">Require approval</span>
                        </label>
                        <label class="flex items-center">
                            <input type="checkbox" v-model="form.send_confirmation_email" class="rounded border-gray-300 text-teal-600 focus:ring-teal-500">
                            <span class="ml-2 text-sm text-gray-700">Send confirmation email</span>
                        </label>
                        <label class="flex items-center">
                            <input type="checkbox" v-model="form.allow_waitlist" class="rounded border-gray-300 text-teal-600 focus:ring-teal-500">
                            <span class="ml-2 text-sm text-gray-700">Allow waitlist</span>
                        </label>
                        <label class="flex items-center">
                            <input type="checkbox" v-model="form.allow_cancel_enrollment" class="rounded border-gray-300 text-teal-600 focus:ring-teal-500">
                            <span class="ml-2 text-sm text-gray-700">Allow participants to cancel enrollment</span>
                        </label>
                    </div>
                </div>

                <!-- Certificate Template -->
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label for="certificate_template" class="block text-sm font-medium text-gray-700">Certificate Template</label>
                        <select
                            id="certificate_template"
                            v-model="form.certificate_template"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-teal-500 focus:ring-teal-500"
                        >
                            <option value="standard">Standard</option>
                            <option value="premium">Premium</option>
                            <option value="custom">Custom</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Certificate Type</label>
                        <div class="mt-1 flex gap-4">
                            <label class="flex items-center">
                                <input type="radio" v-model="form.certificate_type" value="free" class="border-gray-300 text-teal-600 focus:ring-teal-500">
                                <span class="ml-2 text-sm text-gray-700">Free</span>
                            </label>
                            <label class="flex items-center">
                                <input type="radio" v-model="form.certificate_type" value="paid" class="border-gray-300 text-teal-600 focus:ring-teal-500">
                                <span class="ml-2 text-sm text-gray-700">Paid</span>
                            </label>
                        </div>
                    </div>
                </div>

                <!-- Buttons -->
                <div class="flex flex-col gap-3 border-t pt-6">
                    <div class="flex flex-wrap items-center gap-3">
                        <span class="text-sm font-medium text-gray-700">Preview admin response:</span>
                        <label class="inline-flex items-center gap-2 rounded-full border px-3 py-1 text-sm"
                            :class="outcomePreview === 'success' ? 'border-teal-300 text-teal-700' : 'border-gray-200 text-gray-600'">
                            <input type="radio" class="sr-only" value="success" v-model="outcomePreview" />
                            <CheckCircle2 :size="16" />
                            Success
                        </label>
                        <label class="inline-flex items-center gap-2 rounded-full border px-3 py-1 text-sm"
                            :class="outcomePreview === 'rejected' ? 'border-rose-300 text-rose-700' : 'border-gray-200 text-gray-600'">
                            <input type="radio" class="sr-only" value="rejected" v-model="outcomePreview" />
                            <XCircle :size="16" />
                            Rejected
                        </label>
                        <span class="text-xs text-gray-500">UI-only preview; will be wired to real API later.</span>
                    </div>
                    <div class="flex items-center justify-between gap-4">
                        <button type="button" @click="saveDraft" class="rounded-lg border border-gray-300 px-6 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">
                            Save draft
                        </button>
                        <button type="submit" class="rounded-lg bg-teal-600 px-6 py-2 text-sm font-medium text-white hover:bg-teal-700">
                            Confirm
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Confirm Request Dialog -->
    <div v-if="showConfirmDialog" class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4" @click.self="closeAllDialogs">
        <div class="w-full max-w-md rounded-xl bg-white p-6 shadow-2xl">
            <div class="mx-auto mb-3 flex h-12 w-12 items-center justify-center rounded-full bg-amber-50 text-amber-500">
                <AlertTriangle :size="28" />
            </div>
            <h3 class="text-center text-lg font-semibold text-gray-900">Are you sure you want to request this course?</h3>
            <p class="mt-2 text-center text-sm text-gray-600">
                Please wait for admin approval after requesting this course.
            </p>
            <div class="mt-6 flex items-center justify-center gap-3">
                <button @click="closeAllDialogs" class="w-full rounded-lg border border-gray-200 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">
                    Cancel
                </button>
                <button @click="confirmRequest" class="w-full rounded-lg bg-teal-600 px-4 py-2 text-sm font-medium text-white hover:bg-teal-700">
                    Confirm
                </button>
            </div>
        </div>
    </div>

    <!-- Success Dialog -->
    <div v-if="showSuccessDialog" class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4" @click.self="closeAllDialogs">
        <div class="w-full max-w-xl rounded-xl bg-white p-6 shadow-2xl">
            <div class="mx-auto mb-3 flex h-12 w-12 items-center justify-center rounded-full bg-emerald-50 text-emerald-500">
                <CheckCircle2 :size="28" />
            </div>
            <h3 class="text-center text-lg font-semibold text-gray-900">Course published successfully !</h3>
            <p class="mt-1 text-center text-sm text-gray-600">Leadership Fundamentals is now live</p>
            <div class="mt-5 space-y-3 rounded-xl border border-emerald-100 bg-emerald-50/40 p-4">
                <p class="text-sm font-semibold text-emerald-800">What happens next</p>
                <div class="space-y-2 text-sm text-emerald-900">
                    <div class="flex items-start gap-2">
                        <CheckCircle2 :size="16" class="mt-0.5" />
                        <span>Registration is now open for students.</span>
                    </div>
                    <div class="flex items-start gap-2">
                        <CheckCircle2 :size="16" class="mt-0.5" />
                        <span>Students can now view and enroll in the course.</span>
                    </div>
                    <div class="flex items-start gap-2">
                        <CheckCircle2 :size="16" class="mt-0.5" />
                        <span>Notifications have been sent to relevant group.</span>
                    </div>
                </div>
            </div>
            <div class="mt-6 flex items-center justify-center gap-3">
                <button @click="closeAllDialogs" class="w-full rounded-lg border border-gray-200 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">
                    Close
                </button>
                <button @click="closeAllDialogs" class="w-full rounded-lg bg-teal-600 px-4 py-2 text-sm font-medium text-white hover:bg-teal-700">
                    View Course
                </button>
            </div>
        </div>
    </div>

    <!-- Rejected Dialog -->
    <div v-if="showRejectedDialog" class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4" @click.self="closeAllDialogs">
        <div class="w-full max-w-xl rounded-xl bg-white p-6 shadow-2xl">
            <div class="mx-auto mb-3 flex h-12 w-12 items-center justify-center rounded-full bg-rose-50 text-rose-500">
                <XCircle :size="28" />
            </div>
            <h3 class="text-center text-lg font-semibold text-gray-900">Course has rejected</h3>
            <p class="mt-1 text-center text-sm text-gray-600">Leadership Fundamentals is not live</p>
            <div class="mt-5 space-y-3 rounded-xl border border-rose-100 bg-rose-50/60 p-4">
                <p class="text-sm font-semibold text-rose-800">What happens next</p>
                <p class="text-sm text-rose-900">Revise the program then send the request again.</p>
            </div>
            <div class="mt-6 flex items-center justify-center gap-3">
                <button @click="closeAllDialogs" class="w-full rounded-lg border border-gray-200 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">
                    Close
                </button>
                <button @click="closeAllDialogs" class="w-full rounded-lg bg-teal-600 px-4 py-2 text-sm font-medium text-white hover:bg-teal-700">
                    View Course
                </button>
            </div>
        </div>
    </div>

    <!-- Attachments Modal -->
    <div v-if="showAttachmentsModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4" @click.self="showAttachmentsModal = false">
        <div class="w-full max-w-xl rounded-xl bg-white p-6 shadow-2xl">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <h3 class="text-lg font-semibold text-gray-900">Upload and attach files</h3>
                    <p class="text-sm text-gray-600">Upload and attach files to this project.</p>
                </div>
                <button @click="showAttachmentsModal = false" class="rounded p-2 hover:bg-gray-100">
                    <X :size="18" />
                </button>
            </div>
            <div class="mt-4 rounded-lg border border-dashed border-gray-300 bg-gray-50 p-6 text-center">
                <UploadCloud class="mx-auto text-teal-500" :size="36" />
                <p class="mt-2 text-sm font-semibold text-gray-800">Click to upload</p>
                <p class="text-xs text-gray-500">SVG, PNG, JPG or GIF (max. 1280×720px)</p>
                <button
                    type="button"
                    @click="simulateUpload"
                    class="mt-3 rounded-lg border border-teal-200 px-4 py-2 text-sm font-medium text-teal-700 hover:bg-teal-50"
                >
                    Simulate upload
                </button>
            </div>

            <div class="mt-4 space-y-3">
                <div
                    v-for="file in attachments"
                    :key="file.id"
                    class="flex items-center gap-3 rounded-lg border border-gray-200 p-3"
                >
                    <div class="flex h-10 w-10 items-center justify-center rounded-full bg-teal-50 text-teal-600">
                        <Paperclip :size="18" />
                    </div>
                    <div class="flex-1">
                        <div class="flex items-center justify-between text-sm font-medium text-gray-900">
                            <span>{{ file.name }}</span>
                            <span class="text-xs text-gray-500">{{ file.size }}</span>
                        </div>
                        <div class="mt-2 h-2 rounded-full bg-gray-100">
                            <div
                                class="h-2 rounded-full transition-all"
                                :class="file.status === 'uploaded' ? 'bg-teal-500' : 'bg-amber-400'"
                                :style="{ width: `${file.progress}%` }"
                            ></div>
                        </div>
                    </div>
                    <button
                        type="button"
                        class="text-gray-400 hover:text-rose-500"
                        @click="removeAttachment(file.id)"
                    >
                        <X :size="16" />
                    </button>
                </div>
            </div>

            <div class="mt-5 flex items-center justify-end gap-3">
                <button @click="showAttachmentsModal = false" class="rounded-lg border border-gray-200 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">
                    Cancel
                </button>
                <button @click="showAttachmentsModal = false" class="rounded-lg bg-teal-600 px-4 py-2 text-sm font-medium text-white hover:bg-teal-700">
                    Attach files
                </button>
            </div>
        </div>
    </div>
</template>
