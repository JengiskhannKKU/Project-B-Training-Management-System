<script setup>
import { ref, computed } from 'vue';
import { X } from 'lucide-vue-next';
import { useSnackbar } from '@/composables/useSnackbar';
import { useForm } from '@inertiajs/vue3';

const props = defineProps({
    show: {
        type: Boolean,
        default: false
    },
    session: {
        type: Object,
        default: null
    }
});

const emit = defineEmits(['close', 'submit']);

const { showSnackbar } = useSnackbar();

const form = useForm({
    overall_rating: 0,
    content_quality: 0,
    trainer_quality: 0,
    material_quality: 0,
    organization: 0,
    strengths: '',
    improvements: '',
    comments: '',
    would_recommend: null,
    difficulty_level: null
});

const errors = ref({});

const isValid = computed(() => {
    return form.overall_rating > 0 &&
           form.content_quality > 0 &&
           form.trainer_quality > 0 &&
           form.material_quality > 0 &&
           form.organization > 0 &&
           form.strengths.trim() !== '' &&
           form.improvements.trim() !== '' &&
           form.would_recommend !== null &&
           form.difficulty_level !== null;
});

function validateForm() {
    errors.value = {};

    if (form.overall_rating === 0) errors.value.overall_rating = 'Required';
    if (form.content_quality === 0) errors.value.content_quality = 'Required';
    if (form.trainer_quality === 0) errors.value.trainer_quality = 'Required';
    if (form.material_quality === 0) errors.value.material_quality = 'Required';
    if (form.organization === 0) errors.value.organization = 'Required';
    if (!form.strengths.trim()) errors.value.strengths = 'Required';
    if (!form.improvements.trim()) errors.value.improvements = 'Required';
    if (form.would_recommend === null) errors.value.would_recommend = 'Required';
    if (form.difficulty_level === null) errors.value.difficulty_level = 'Required';

    return Object.keys(errors.value).length === 0;
}

function handleSubmit() {
    if (!validateForm()) {
        showSnackbar('Please complete all required fields', 'error');
        return;
    }

    // POST /sessions/{id}/evaluation
    form.post(route('sessions.evaluation.store', { id: props.session.id }), {
        onSuccess: () => {
            showSnackbar('Thank you for your feedback!', 'success');
            resetForm();
            emit('close');
        },
        onError: (errors) => {
            showSnackbar('Failed to submit feedback. Please try again.', 'error');
            console.error('Submission errors:', errors);
        }
    });
}

function resetForm() {
    form.reset();
    errors.value = {};
}

function handleClose() {
    resetForm();
    emit('close');
}
</script>

<template>
    <Teleport to="body">
        <Transition
            enter-active-class="transition ease-out duration-200"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="transition ease-in duration-150"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div
                v-if="show"
                class="fixed inset-0 z-50 flex items-start justify-center overflow-y-auto bg-gray-900/50 px-4 py-8"
                @click.self="handleClose"
            >
                <div class="relative w-full max-w-2xl rounded-xl bg-white shadow-2xl">
                    <!-- Header -->
                    <div class="flex items-center justify-between border-b border-gray-200 px-6 py-4">
                        <div>
                            <h2 class="text-xl font-semibold text-gray-900">Course Feedback</h2>
                            <p class="mt-1 text-sm text-gray-600">{{ session?.course_name }}</p>
                        </div>
                        <button
                            type="button"
                            @click="handleClose"
                            class="rounded-lg p-2 text-gray-400 hover:bg-gray-100 hover:text-gray-600"
                        >
                            <X :size="20" />
                        </button>
                    </div>

                    <!-- Content -->
                    <div class="max-h-[calc(100vh-200px)] overflow-y-auto px-6 py-6">
                        <!-- Session Info -->
                        <div class="mb-6 rounded-lg bg-emerald-50 p-4">
                            <div class="space-y-1 text-sm">
                                <p><span class="font-medium text-gray-700">Trainer:</span> <span class="text-gray-600">{{ session?.trainer_name }}</span></p>
                                <p><span class="font-medium text-gray-700">Completed:</span> <span class="text-gray-600">{{ session?.completed_date }}</span></p>
                                <p><span class="font-medium text-gray-700">Attendance:</span> <span class="text-emerald-600 font-medium">{{ session?.attendance_rate }}%</span></p>
                            </div>
                        </div>

                        <!-- Rating Sections -->
                        <div class="space-y-6">
                            <div>
                                <h3 class="mb-4 text-lg font-semibold text-gray-900">Rate Your Experience</h3>

                                <!-- Overall Experience -->
                                <div class="space-y-4">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1 text-center">
                                            Overall Experience <span class="text-red-500">*</span>
                                        </label>
                                        <p class="text-xs text-gray-500 text-center mb-3">(Your overall satisfaction with the course)</p>
                                        <div class="flex justify-center gap-4">
                                            <button
                                                v-for="rating in [1, 2, 3, 4, 5]"
                                                :key="`overall-${rating}`"
                                                type="button"
                                                @click="form.overall_rating = rating"
                                                :class="[
                                                    'h-12 w-12 rounded-lg border-2 text-lg font-semibold transition',
                                                    form.overall_rating === rating
                                                        ? 'border-emerald-500 bg-emerald-500 text-white'
                                                        : 'border-gray-300 bg-white text-gray-700 hover:border-emerald-300 hover:bg-emerald-50'
                                                ]"
                                            >
                                                {{ rating }}
                                            </button>
                                        </div>
                                        <p v-if="errors.rating_overall" class="mt-2 text-center text-xs text-red-500">{{ errors.rating_overall }}</p>
                                    </div>

                                    <!-- Content Quality -->
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1 text-center">
                                            Content Quality <span class="text-red-500">*</span>
                                        </label>
                                        <p class="text-xs text-gray-500 text-center mb-3">(Relevance and usefulness of course content)</p>
                                        <div class="flex justify-center gap-4">
                                            <button
                                                v-for="rating in [1, 2, 3, 4, 5]"
                                                :key="`content-${rating}`"
                                                type="button"
                                                @click="form.content_quality = rating"
                                                :class="[
                                                    'h-12 w-12 rounded-lg border-2 text-lg font-semibold transition',
                                                    form.content_quality === rating
                                                        ? 'border-emerald-500 bg-emerald-500 text-white'
                                                        : 'border-gray-300 bg-white text-gray-700 hover:border-emerald-300 hover:bg-emerald-50'
                                                ]"
                                            >
                                                {{ rating }}
                                            </button>
                                        </div>
                                        <p v-if="errors.rating_content" class="mt-2 text-center text-xs text-red-500">{{ errors.rating_content }}</p>
                                    </div>

                                    <!-- Trainer Performance -->
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1 text-center">
                                            Trainer Performance <span class="text-red-500">*</span>
                                        </label>
                                        <p class="text-xs text-gray-500 text-center mb-3">(Teaching effectiveness and engagement)</p>
                                        <div class="flex justify-center gap-4">
                                            <button
                                                v-for="rating in [1, 2, 3, 4, 5]"
                                                :key="`trainer-${rating}`"
                                                type="button"
                                                @click="form.trainer_quality = rating"
                                                :class="[
                                                    'h-12 w-12 rounded-lg border-2 text-lg font-semibold transition',
                                                    form.trainer_quality === rating
                                                        ? 'border-emerald-500 bg-emerald-500 text-white'
                                                        : 'border-gray-300 bg-white text-gray-700 hover:border-emerald-300 hover:bg-emerald-50'
                                                ]"
                                            >
                                                {{ rating }}
                                            </button>
                                        </div>
                                        <p v-if="errors.rating_trainer" class="mt-2 text-center text-xs text-red-500">{{ errors.rating_trainer }}</p>
                                    </div>

                                    <!-- Learning Materials -->
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1 text-center">
                                            Learning Materials <span class="text-red-500">*</span>
                                        </label>
                                        <p class="text-xs text-gray-500 text-center mb-3">(Quality of slides, handouts, and resources)</p>
                                        <div class="flex justify-center gap-4">
                                            <button
                                                v-for="rating in [1, 2, 3, 4, 5]"
                                                :key="`material-${rating}`"
                                                type="button"
                                                @click="form.material_quality = rating"
                                                :class="[
                                                    'h-12 w-12 rounded-lg border-2 text-lg font-semibold transition',
                                                    form.material_quality === rating
                                                        ? 'border-emerald-500 bg-emerald-500 text-white'
                                                        : 'border-gray-300 bg-white text-gray-700 hover:border-emerald-300 hover:bg-emerald-50'
                                                ]"
                                            >
                                                {{ rating }}
                                            </button>
                                        </div>
                                        <p v-if="errors.rating_material" class="mt-2 text-center text-xs text-red-500">{{ errors.rating_material }}</p>
                                    </div>

                                    <!-- Organization -->
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1 text-center">
                                            Organization <span class="text-red-500">*</span>
                                        </label>
                                        <p class="text-xs text-gray-500 text-center mb-3">(Time management and course structure)</p>
                                        <div class="flex justify-center gap-4">
                                            <button
                                                v-for="rating in [1, 2, 3, 4, 5]"
                                                :key="`organization-${rating}`"
                                                type="button"
                                                @click="form.organization = rating"
                                                :class="[
                                                    'h-12 w-12 rounded-lg border-2 text-lg font-semibold transition',
                                                    form.organization === rating
                                                        ? 'border-emerald-500 bg-emerald-500 text-white'
                                                        : 'border-gray-300 bg-white text-gray-700 hover:border-emerald-300 hover:bg-emerald-50'
                                                ]"
                                            >
                                                {{ rating }}
                                            </button>
                                        </div>
                                        <p v-if="errors.rating_organization" class="mt-2 text-center text-xs text-red-500">{{ errors.rating_organization }}</p>
                                    </div>
                                </div>
                            </div>

                            <!-- Divider -->
                            <div class="border-t border-gray-200"></div>

                            <!-- Open-ended Questions -->
                            <div class="space-y-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">
                                        What did you like most? <span class="text-red-500">*</span>
                                    </label>
                                    <textarea
                                        v-model="form.strengths"
                                        rows="3"
                                        class="w-full rounded-lg border-gray-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500"
                                        placeholder="Share what you enjoyed about this course..."
                                    ></textarea>
                                    <p v-if="errors.strengths" class="mt-1 text-xs text-red-500">{{ errors.strengths }}</p>
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">
                                        What should be improved? <span class="text-red-500">*</span>
                                    </label>
                                    <textarea
                                        v-model="form.improvements"
                                        rows="3"
                                        class="w-full rounded-lg border-gray-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500"
                                        placeholder="Suggestions for improvement..."
                                    ></textarea>
                                    <p v-if="errors.improvements" class="mt-1 text-xs text-red-500">{{ errors.improvements }}</p>
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">
                                        Additional comments
                                    </label>
                                    <textarea
                                        v-model="form.comments"
                                        rows="3"
                                        class="w-full rounded-lg border-gray-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500"
                                        placeholder="Any other feedback..."
                                    ></textarea>
                                </div>
                            </div>

                            <!-- Divider -->
                            <div class="border-t border-gray-200"></div>

                            <!-- Radio Questions -->
                            <div class="space-y-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-3">
                                        Would you recommend this course? <span class="text-red-500">*</span>
                                    </label>
                                    <div class="flex gap-4">
                                        <label class="flex items-center">
                                            <input
                                                type="radio"
                                                v-model="form.would_recommend"
                                                :value="true"
                                                class="h-4 w-4 border-gray-300 text-emerald-600 focus:ring-emerald-500"
                                            />
                                            <span class="ml-2 text-sm text-gray-700">Yes</span>
                                        </label>
                                        <label class="flex items-center">
                                            <input
                                                type="radio"
                                                v-model="form.would_recommend"
                                                :value="false"
                                                class="h-4 w-4 border-gray-300 text-emerald-600 focus:ring-emerald-500"
                                            />
                                            <span class="ml-2 text-sm text-gray-700">No</span>
                                        </label>
                                    </div>
                                    <p v-if="errors.would_recommend" class="mt-1 text-xs text-red-500">{{ errors.would_recommend }}</p>
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-3">
                                        Difficulty level: <span class="text-red-500">*</span>
                                    </label>
                                    <div class="flex gap-4">
                                        <label class="flex items-center">
                                            <input
                                                type="radio"
                                                v-model="form.difficulty_level"
                                                value="easy"
                                                class="h-4 w-4 border-gray-300 text-emerald-600 focus:ring-emerald-500"
                                            />
                                            <span class="ml-2 text-sm text-gray-700">Easy</span>
                                        </label>
                                        <label class="flex items-center">
                                            <input
                                                type="radio"
                                                v-model="form.difficulty_level"
                                                value="medium"
                                                class="h-4 w-4 border-gray-300 text-emerald-600 focus:ring-emerald-500"
                                            />
                                            <span class="ml-2 text-sm text-gray-700">Medium</span>
                                        </label>
                                        <label class="flex items-center">
                                            <input
                                                type="radio"
                                                v-model="form.difficulty_level"
                                                value="hard"
                                                class="h-4 w-4 border-gray-300 text-emerald-600 focus:ring-emerald-500"
                                            />
                                            <span class="ml-2 text-sm text-gray-700">Hard</span>
                                        </label>
                                    </div>
                                    <p v-if="errors.difficulty_level" class="mt-1 text-xs text-red-500">{{ errors.difficulty_level }}</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Footer -->
                    <div class="flex items-center justify-end gap-3 border-t border-gray-200 px-6 py-4">
                        <button
                            type="button"
                            @click="handleClose"
                            class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50"
                        >
                            Cancel
                        </button>
                        <button
                            type="button"
                            @click="handleSubmit"
                            :disabled="!isValid"
                            :class="[
                                'rounded-lg px-4 py-2 text-sm font-medium text-white',
                                isValid
                                    ? 'bg-emerald-600 hover:bg-emerald-700'
                                    : 'bg-gray-300 cursor-not-allowed'
                            ]"
                        >
                            Submit Feedback
                        </button>
                    </div>
                </div>
            </div>
        </Transition>
    </Teleport>
</template>
