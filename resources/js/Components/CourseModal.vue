<script setup lang="ts">
import { ref, computed, watch, onMounted } from 'vue';
import { useForm } from '@inertiajs/vue3';
import { useToast } from 'vue-toastification';
import axios from 'axios';
import {
    X,
    UploadCloud,
    Trash2,
    Loader2,
    Users,
    Layers,
    BookOpen,
    Info,
    CheckCircle2
} from 'lucide-vue-next';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import InputError from '@/Components/InputError.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';

const toast = useToast();

interface Props {
    show: boolean;
    uploadUrlPrefix?: string;
    course?: Record<string, any> | null;
}

const props = withDefaults(defineProps<Props>(), {
    show: false,
    course: null,
    uploadUrlPrefix: 'admin',
});

const emit = defineEmits<{
    close: [];
    success: [payload: Record<string, unknown>];
}>();

const imagePreview = ref<string | null>(null);
const imageUploading = ref(false);
const thumbnailPath = ref<string>('');
const fileInputRef = ref<HTMLInputElement | null>(null);

// Categories from API
interface Category {
    id: number;
    name: string;
    icon_name?: string;
    color?: string;
}
const categories = ref<Category[]>([]);
const categoriesLoading = ref(false);

const fetchCategories = async () => {
    categoriesLoading.value = true;
    try {
        const { data } = await axios.get('/api/categories');
        categories.value = data.data || [];
    } catch (error) {
        console.error('Failed to fetch categories:', error);
    } finally {
        categoriesLoading.value = false;
    }
};

onMounted(() => {
    fetchCategories();
});

// Form initialization
const form = useForm({
    title: props.course?.title || '',
    description: props.course?.description || '', // Maps to Short Description
    category_id: props.course?.category_id || null,
    level: props.course?.level || 'beginner',
    learning_outcomes: props.course?.learning_outcomes || '',
    target_audience: props.course?.target_audience || '',
    prerequisites: props.course?.prerequisites || '',
    additional_info: props.course?.additional_info || '',
    status: props.course?.status || 'published',
});

const isEditMode = computed(() => !!props.course?.id);

// Optimized character counters using computed properties
const titleLength = computed(() => form.title?.length || 0);
const descriptionLength = computed(() => form.description?.length || 0);
const learningOutcomesLength = computed(() => form.learning_outcomes?.length || 0);
const targetAudienceLength = computed(() => form.target_audience?.length || 0);
const prerequisitesLength = computed(() => form.prerequisites?.length || 0);
const additionalInfoLength = computed(() => form.additional_info?.length || 0);

// Watch for course prop changes to update form if modal is reused
watch(() => props.course, (newCourse) => {
    if (newCourse) {
        form.title = newCourse.title || '';
        form.description = newCourse.description || '';
        form.category_id = newCourse.category_id || null;
        form.level = newCourse.level || 'beginner';
        form.learning_outcomes = newCourse.learning_outcomes || '';
        form.target_audience = newCourse.target_audience || '';
        form.prerequisites = newCourse.prerequisites || '';
        form.additional_info = newCourse.additional_info || '';
        form.status = newCourse.status || 'published';
        thumbnailPath.value = newCourse.thumbnail_path || '';
    } else {
        form.reset();
        thumbnailPath.value = '';
        imagePreview.value = null;
    }
}, { immediate: true });

const levels = [
    { id: 'beginner', label: 'Beginner', desc: 'No prior experience required' },
    { id: 'intermediate', label: 'Intermediate', desc: 'Some experience required' },
    { id: 'advanced', label: 'Advanced', desc: 'Extensive experience required' },
];

const handleSubmit = async () => {
    // Clear previous errors
    form.clearErrors();

    if (!form.title || !form.category_id) {
        if (!form.title) form.setError('title', 'Course title is required');
        if (!form.category_id) form.setError('category_id', 'Category is required');
        toast.error('Please fill in all required fields');
        return;
    }

    const payload = {
        title: form.title,
        description: form.description,
        category_id: form.category_id,
        level: form.level,
        learning_outcomes: form.learning_outcomes,
        target_audience: form.target_audience,
        prerequisites: form.prerequisites,
        additional_info: form.additional_info,
        thumbnail_path: thumbnailPath.value || props.course?.thumbnail_path || null,
        status: form.status,
    };

    form.processing = true;

    try {
        await axios.get('/sanctum/csrf-cookie');

        if (isEditMode.value) {
            await axios.put(`/api/courses/${props.course.id}`, payload);
            toast.success('Course updated successfully!');
        } else {
            await axios.post('/api/courses', payload);
            toast.success('Course created successfully!');
        }

        // Only emit success and close if API call succeeded
        emit('success', payload);
        handleClose();
    } catch (error: any) {
        // Handle validation errors from API
        if (error.response?.status === 422 && error.response?.data?.errors) {
            const errors = error.response.data.errors;
            Object.keys(errors).forEach(field => {
                form.setError(field, errors[field][0]);
            });
            toast.error('Please fix the validation errors');
        } else if (error.response?.status === 403) {
            toast.error('Unauthorized: Only admins can create/edit courses');
        } else {
            const message = error?.response?.data?.message || error?.message ||
                `Failed to ${isEditMode.value ? 'update' : 'create'} course`;
            toast.error(message);
        }
    } finally {
        form.processing = false;
    }
};

const handleClose = () => {
    form.reset();
    form.clearErrors();
    removeImage();
    emit('close');
};

const handleImageUpload = async (event: Event) => {
    const target = event.target as HTMLInputElement;
    const file = target.files?.[0];
    if (!file) return;

    if (file.size > 2 * 1024 * 1024) {
        toast.error('Image size must be less than 2MB');
        return;
    }

    const reader = new FileReader();
    reader.onload = (e) => { imagePreview.value = e.target?.result as string; };
    reader.readAsDataURL(file);

    imageUploading.value = true;
    try {
        await axios.get('/sanctum/csrf-cookie');
        const formData = new FormData();
        formData.append('image', file);

        const { data } = await axios.post(`/api/${props.uploadUrlPrefix}/upload/image`, formData, {
            headers: { 'Content-Type': 'multipart/form-data' },
        });

        thumbnailPath.value = data.data.path || data.data.url;
        toast.success('Thumbnail uploaded');
    } catch (error) {
        toast.error('Upload failed');
        imagePreview.value = null;
    } finally {
        imageUploading.value = false;
    }
};

const removeImage = () => {
    imagePreview.value = null;
    thumbnailPath.value = '';
    if (fileInputRef.value) fileInputRef.value.value = '';
};

const triggerFileInput = () => fileInputRef.value?.click();
</script>

<template>
    <Teleport to="body">
        <div v-if="show" class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-sm p-4 transition-opacity" @click.self="handleClose">
            <div class="relative max-h-[90vh] w-full max-w-4xl overflow-y-auto rounded-2xl bg-white shadow-2xl flex flex-col" style="will-change: transform; contain: layout style paint;" @click.stop>
            
            <!-- Sticky Header -->
            <div class="sticky top-0 z-20 border-b border-gray-100 bg-white/95 px-8 py-5 backdrop-blur flex items-center justify-between">
                <div>
                    <h2 class="text-2xl font-bold text-gray-900 tracking-tight">{{ isEditMode ? 'Edit Program' : 'Create New Program' }}</h2>
                    <p class="text-sm text-gray-500 mt-0.5">Fill in the details to configure your course structure.</p>
                </div>
                <button @click="handleClose" class="rounded-full p-2 hover:bg-gray-100 text-gray-400 hover:text-gray-600 transition-colors">
                    <X :size="24" />
                </button>
            </div>

            <form @submit.prevent="handleSubmit" class="p-8 space-y-8 flex-1" style="content-visibility: auto;">
                
                <!-- Main Info Section -->
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                    <!-- Left Column: Image & Status -->
                    <div class="space-y-6">
                        <!-- Thumbnail -->
                        <div class="space-y-2">
                            <InputLabel value="Course Thumbnail" />
                            <input ref="fileInputRef" type="file" accept="image/*" class="hidden" @change="handleImageUpload" />

                            <div
                                v-if="!imagePreview && !props.course?.thumbnail_path && !thumbnailPath"
                                @click="triggerFileInput"
                                class="aspect-video w-full rounded-xl border-2 border-dashed border-gray-300 bg-gray-50 hover:bg-gray-100 hover:border-teal-500 transition-all cursor-pointer flex flex-col items-center justify-center group"
                            >
                                <div class="p-4 rounded-full bg-white shadow-sm group-hover:scale-110 transition-transform mb-3">
                                    <UploadCloud class="text-teal-600" :size="24" />
                                </div>
                                <span class="text-sm font-medium text-gray-600 group-hover:text-teal-700">Click to upload</span>
                                <span class="text-xs text-gray-400 mt-1">PNG, JPG up to 2MB</span>
                            </div>

                            <div v-else class="relative aspect-video w-full rounded-xl overflow-hidden border border-gray-200 shadow-sm group">
                                <img :src="imagePreview || thumbnailPath || props.course?.thumbnail_path" class="w-full h-full object-cover" />
                                <div class="absolute inset-0 bg-black/40 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center gap-2">
                                    <button type="button" @click="triggerFileInput" class="p-2 bg-white/20 hover:bg-white/30 backdrop-blur rounded-full text-white transition-colors" title="Change">
                                        <UploadCloud :size="18" />
                                    </button>
                                    <button type="button" @click="removeImage" class="p-2 bg-red-500/80 hover:bg-red-600 backdrop-blur rounded-full text-white transition-colors" title="Remove">
                                        <Trash2 :size="18" />
                                    </button>
                                </div>
                                <div v-if="imageUploading" class="absolute inset-0 bg-black/60 flex items-center justify-center text-white backdrop-blur-sm">
                                    <Loader2 class="animate-spin" />
                                </div>
                            </div>
                            <InputError :message="form.errors.thumbnail_path" />
                        </div>

                        <!-- Status -->
                        <div class="space-y-2">
                            <InputLabel value="Status" required />
                            <select v-model="form.status" class="w-full rounded-xl border-gray-300 focus:border-teal-500 focus:ring-teal-500 shadow-sm py-2.5">
                                <option value="draft">Draft - Save for later</option>
                                <option value="published">Published - Ready and active</option>
                                <option v-if="isEditMode" value="archived">Archived - Store for records</option>
                            </select>
                            <InputError :message="form.errors.status" />
                            <div v-if="form.status === 'draft'" class="text-xs text-gray-600 mt-1">
                                💾 Course saved but not active. Use this when details are still uncertain or subject to change.
                            </div>
                            <div v-if="form.status === 'published'" class="text-xs text-teal-600 mt-1">
                                ✓ Course is ready and sessions can be created immediately.
                            </div>
                            <div v-if="form.status === 'archived' && isEditMode" class="text-xs text-gray-600 mt-1">
                                📦 Course stored for records. Used for completed courses or courses you want to keep archived.
                            </div>
                        </div>

                        <!-- Category -->
                        <div class="space-y-2">
                            <InputLabel value="Category" required />
                            <select v-model="form.category_id" class="w-full rounded-xl border-gray-300 focus:border-teal-500 focus:ring-teal-500 shadow-sm py-2.5" :disabled="categoriesLoading">
                                <option :value="null">{{ categoriesLoading ? 'Loading...' : 'Select Category' }}</option>
                                <option v-for="cat in categories" :key="cat.id" :value="cat.id">{{ cat.name }}</option>
                            </select>
                            <InputError :message="form.errors.category_id" />
                        </div>
                    </div>

                    <!-- Right Column: Details -->
                    <div class="lg:col-span-2 space-y-6">
                        <!-- Title -->
                        <div class="space-y-2">
                            <div class="flex justify-between items-center">
                                <InputLabel value="Course Title" required />
                                <span class="text-xs text-gray-400">{{ titleLength }}/50 chars</span>
                            </div>
                            <TextInput 
                                v-model="form.title" 
                                type="text" 
                                class="w-full" 
                                placeholder="e.g. Mastering Vue.js Architecture" 
                                maxlength="50"
                            />
                            <InputError :message="form.errors.title" />
                        </div>

                        <!-- Short Description -->
                        <div class="space-y-2">
                            <div class="flex justify-between items-center">
                                <InputLabel value="Short Description" />
                                <span class="text-xs text-gray-400">{{ descriptionLength }}/100 chars</span>
                            </div>
                            <textarea 
                                v-model="form.description" 
                                rows="3" 
                                class="w-full rounded-xl border-gray-300 focus:border-teal-500 focus:ring-teal-500 shadow-sm text-sm"
                                placeholder="A brief summary of what students will learn..."
                                maxlength="100"
                            ></textarea>
                            <InputError :message="form.errors.description" />
                        </div>

                        <!-- Level Selection (Cards) -->
                        <div class="space-y-3">
                            <h3 class="text-sm font-semibold text-gray-900 flex items-center gap-2">
                                <Layers :size="16" class="text-teal-600" />
                                Difficulty Level
                            </h3>
                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                                <label
                                    v-for="lvl in levels"
                                    :key="lvl.id"
                                    class="relative flex flex-col cursor-pointer rounded-xl border-2 p-4 transition-all hover:border-teal-300"
                                    :class="form.level === lvl.id ? 'border-teal-500 bg-teal-50/50 ring-1 ring-teal-500' : 'border-gray-200 bg-white'"
                                >
                                    <input type="radio" v-model="form.level" :value="lvl.id" class="sr-only" />
                                    <div class="flex justify-between items-start mb-1">
                                        <span class="font-bold text-sm" :class="form.level === lvl.id ? 'text-teal-800' : 'text-gray-900'">
                                            {{ lvl.label }}
                                        </span>
                                        <CheckCircle2 v-if="form.level === lvl.id" :size="16" class="text-teal-600" />
                                    </div>
                                    <span class="text-xs text-gray-500 leading-tight">{{ lvl.desc }}</span>
                                </label>
                            </div>
                            <InputError :message="form.errors.level" />
                        </div>
                    </div>
                </div>

                <!-- Full Description Section -->
                <div class="rounded-2xl border border-gray-200 bg-gray-50/50 p-6 space-y-6" style="content-visibility: auto; contain-intrinsic-size: 0 500px;">
                    <div class="flex items-center gap-2 pb-4 border-b border-gray-200">
                        <BookOpen :size="20" class="text-teal-600" />
                        <h3 class="text-lg font-bold text-gray-900">Full Description & Syllabus</h3>
                    </div>

                    <div class="grid grid-cols-1 gap-6">
                        <!-- Learning Outcomes -->
                        <div class="space-y-2">
                            <div class="flex justify-between items-center">
                                <InputLabel value="Learning Outcomes" />
                                <span class="text-xs text-gray-400">{{ learningOutcomesLength }}/500 chars</span>
                            </div>
                            <textarea
                                v-model="form.learning_outcomes"
                                rows="4"
                                class="w-full rounded-xl border-gray-300 focus:border-teal-500 focus:ring-teal-500 shadow-sm text-sm"
                                placeholder="• Understand the core concepts..."
                                maxlength="500"
                            ></textarea>
                            <InputError :message="form.errors.learning_outcomes" />
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <!-- Target Audience -->
                            <div class="space-y-2">
                                <div class="flex justify-between items-center">
                                    <InputLabel value="Target Audience" />
                                    <span class="text-xs text-gray-400">{{ targetAudienceLength }}/500 chars</span>
                                </div>
                                <textarea
                                    v-model="form.target_audience"
                                    rows="3"
                                    class="w-full rounded-xl border-gray-300 focus:border-teal-500 focus:ring-teal-500 shadow-sm text-sm"
                                    placeholder="Who is this course designed for?"
                                    maxlength="500"
                                ></textarea>
                                <InputError :message="form.errors.target_audience" />
                            </div>

                            <!-- Prerequisites -->
                            <div class="space-y-2">
                                <div class="flex justify-between items-center">
                                    <InputLabel value="Prerequisites" />
                                    <span class="text-xs text-gray-400">{{ prerequisitesLength }}/500 chars</span>
                                </div>
                                <textarea
                                    v-model="form.prerequisites"
                                    rows="3"
                                    class="w-full rounded-xl border-gray-300 focus:border-teal-500 focus:ring-teal-500 shadow-sm text-sm"
                                    placeholder="Required skills or equipment..."
                                    maxlength="500"
                                ></textarea>
                                <InputError :message="form.errors.prerequisites" />
                            </div>
                        </div>

                        <!-- Additional Information -->
                        <div class="space-y-2">
                            <div class="flex justify-between items-center">
                                <InputLabel value="Additional Information" />
                                <span class="text-xs text-gray-400">{{ additionalInfoLength }}/500 chars</span>
                            </div>
                            <textarea
                                v-model="form.additional_info"
                                rows="3"
                                class="w-full rounded-xl border-gray-300 focus:border-teal-500 focus:ring-teal-500 shadow-sm text-sm"
                                placeholder="Any other details course creators want to include..."
                                maxlength="500"
                            ></textarea>
                            <InputError :message="form.errors.additional_info" />
                        </div>
                    </div>
                </div>

            </form>

            <!-- Sticky Footer -->
            <div class="sticky bottom-0 z-20 border-t border-gray-100 bg-white px-8 py-4 flex justify-end gap-3">
                <SecondaryButton @click="handleClose">
                    Cancel
                </SecondaryButton>
                <PrimaryButton
                    @click="handleSubmit"
                    :class="{ 'opacity-25': form.processing }"
                    :disabled="form.processing"
                    class="bg-[#2f837d] hover:bg-[#26685f]"
                >
                    {{ isEditMode ? 'Update Program' : 'Create Program' }}
                </PrimaryButton>
            </div>
        </div>
    </div>
    </Teleport>
</template>
