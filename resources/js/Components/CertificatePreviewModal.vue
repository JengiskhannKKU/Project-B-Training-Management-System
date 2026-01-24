<script setup>
import { ref, watch, computed } from 'vue';
import { X, Eye, AlertTriangle, Loader2, Download, Languages } from 'lucide-vue-next';
import axios from 'axios';

const props = defineProps({
    show: {
        type: Boolean,
        default: false,
    },
    enrollmentId: {
        type: [Number, String],
        default: null,
    },
});

const emit = defineEmits(['close']);

const selectedLanguage = ref('th');
const isLoading = ref(false);
const previewUrl = ref(null);
const warnings = ref([]);
const errorMessage = ref('');

const hasWarnings = computed(() => warnings.value.length > 0);

// Reset state when modal opens
watch(() => props.show, (newVal) => {
    if (newVal) {
        selectedLanguage.value = 'th';
        isLoading.value = false;
        previewUrl.value = null;
        warnings.value = [];
        errorMessage.value = '';

        if (props.enrollmentId) {
            loadPreview();
        }
    }
});

// Reload preview when enrollment or language changes
watch([() => props.enrollmentId, selectedLanguage], () => {
    if (props.show && props.enrollmentId) {
        loadPreview();
    }
});

const loadPreview = async () => {
    if (!props.enrollmentId) {
        errorMessage.value = 'No enrollment selected';
        return;
    }

    isLoading.value = true;
    errorMessage.value = '';
    warnings.value = [];
    previewUrl.value = null;

    try {
        const response = await axios.post('/api/admin/certificates/preview', {
            enrollment_id: props.enrollmentId,
            language: selectedLanguage.value,
        }, {
            responseType: 'blob',
        });

        // Check for warnings in response header
        const warningsHeader = response.headers['x-certificate-warnings'];
        if (warningsHeader) {
            try {
                warnings.value = JSON.parse(warningsHeader);
            } catch (e) {
                console.warn('Failed to parse warnings header:', e);
            }
        }

        // Create blob URL for PDF
        const blob = new Blob([response.data], { type: 'application/pdf' });
        previewUrl.value = URL.createObjectURL(blob);
    } catch (error) {
        console.error('Preview error:', error);
        errorMessage.value = error.response?.data?.message || 'Failed to load certificate preview. Please try again.';
    } finally {
        isLoading.value = false;
    }
};

const handleClose = () => {
    // Revoke blob URL to free memory
    if (previewUrl.value) {
        URL.revokeObjectURL(previewUrl.value);
    }
    emit('close');
};

const downloadPreview = () => {
    if (!previewUrl.value) return;

    const link = document.createElement('a');
    link.href = previewUrl.value;
    link.download = `certificate-preview-${selectedLanguage.value}.pdf`;
    link.click();
};

const openInNewTab = () => {
    if (!previewUrl.value) return;
    window.open(previewUrl.value, '_blank');
};
</script>

<template>
    <!-- Modal Backdrop -->
    <Transition name="modal">
        <div
            v-if="show"
            class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4"
            @click="handleClose"
        >
            <!-- Modal Content -->
            <div
                class="bg-white rounded-2xl shadow-xl w-full max-w-6xl max-h-[90vh] overflow-hidden flex flex-col"
                @click.stop
            >
                <!-- Modal Header -->
                <div class="flex items-center justify-between p-6 border-b border-gray-200 bg-gradient-to-r from-[#2f837d] to-[#26685f] text-white">
                    <div class="flex items-center gap-3">
                        <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-white/20">
                            <Eye class="h-5 w-5" />
                        </div>
                        <div>
                            <h2 class="text-xl font-semibold">Certificate Preview</h2>
                            <p class="text-sm text-white/80 mt-0.5">Preview certificate before generation</p>
                        </div>
                    </div>
                    <button
                        @click="handleClose"
                        class="text-white/80 hover:text-white transition-colors p-1 rounded-lg hover:bg-white/10"
                    >
                        <X :size="24" />
                    </button>
                </div>

                <!-- Language Selector & Actions -->
                <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 bg-gray-50">
                    <div class="flex items-center gap-3">
                        <Languages class="h-5 w-5 text-gray-500" />
                        <label class="text-sm font-medium text-gray-700">Language:</label>
                        <div class="flex gap-2">
                            <button
                                @click="selectedLanguage = 'th'"
                                :class="[
                                    'px-4 py-2 rounded-lg text-sm font-semibold transition-all',
                                    selectedLanguage === 'th'
                                        ? 'bg-[#2f837d] text-white shadow-md'
                                        : 'bg-white text-gray-600 border border-gray-300 hover:bg-gray-50'
                                ]"
                                :disabled="isLoading"
                            >
                                ภาษาไทย (Thai)
                            </button>
                            <button
                                @click="selectedLanguage = 'en'"
                                :class="[
                                    'px-4 py-2 rounded-lg text-sm font-semibold transition-all',
                                    selectedLanguage === 'en'
                                        ? 'bg-[#2f837d] text-white shadow-md'
                                        : 'bg-white text-gray-600 border border-gray-300 hover:bg-gray-50'
                                ]"
                                :disabled="isLoading"
                            >
                                English
                            </button>
                        </div>
                    </div>

                    <div class="flex items-center gap-2">
                        <button
                            v-if="previewUrl"
                            @click="openInNewTab"
                            class="inline-flex items-center gap-2 px-4 py-2 rounded-lg border border-gray-300 text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 transition-colors"
                        >
                            <Eye class="h-4 w-4" />
                            Open in New Tab
                        </button>
                        <button
                            v-if="previewUrl"
                            @click="downloadPreview"
                            class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-gradient-to-r from-[#2f837d] to-[#26685f] text-sm font-medium text-white hover:shadow-lg transition-all"
                        >
                            <Download class="h-4 w-4" />
                            Download Preview
                        </button>
                    </div>
                </div>

                <!-- Warnings Banner -->
                <div v-if="hasWarnings" class="px-6 py-4 bg-amber-50 border-b border-amber-200">
                    <div class="flex items-start gap-3">
                        <AlertTriangle class="h-5 w-5 text-amber-600 flex-shrink-0 mt-0.5" />
                        <div class="flex-1">
                            <h3 class="text-sm font-semibold text-amber-900 mb-2">Certificate Warnings</h3>
                            <ul class="space-y-1 text-sm text-amber-800">
                                <li v-for="(warning, index) in warnings" :key="index" class="flex items-start gap-2">
                                    <span class="text-amber-600">•</span>
                                    <span>{{ warning }}</span>
                                </li>
                            </ul>
                            <p class="text-xs text-amber-700 mt-2 italic">
                                These warnings indicate content that may not fit properly on the certificate. Consider shortening the text.
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Modal Body -->
                <div class="flex-1 overflow-auto p-6 bg-gray-100">
                    <!-- Loading State -->
                    <div v-if="isLoading" class="flex flex-col items-center justify-center h-full min-h-[500px]">
                        <Loader2 class="h-12 w-12 text-[#2f837d] animate-spin mb-4" />
                        <p class="text-gray-600 font-medium">Generating preview...</p>
                        <p class="text-sm text-gray-500 mt-2">This may take a few moments</p>
                    </div>

                    <!-- Error State -->
                    <div v-else-if="errorMessage" class="flex flex-col items-center justify-center h-full min-h-[500px]">
                        <div class="rounded-2xl border-2 border-red-200 bg-red-50 p-8 text-center max-w-md">
                            <div class="flex justify-center mb-4">
                                <div class="flex h-16 w-16 items-center justify-center rounded-full bg-red-100 text-red-600">
                                    <AlertTriangle class="h-8 w-8" />
                                </div>
                            </div>
                            <h3 class="text-lg font-semibold text-red-900 mb-2">Preview Failed</h3>
                            <p class="text-sm text-red-700">{{ errorMessage }}</p>
                            <button
                                @click="loadPreview"
                                class="mt-4 px-4 py-2 rounded-lg bg-red-600 text-white text-sm font-medium hover:bg-red-700 transition-colors"
                            >
                                Try Again
                            </button>
                        </div>
                    </div>

                    <!-- PDF Preview -->
                    <div v-else-if="previewUrl" class="rounded-xl border-2 border-gray-300 bg-white shadow-lg overflow-hidden">
                        <iframe
                            :src="previewUrl"
                            class="w-full border-0"
                            style="min-height: 700px; height: calc(90vh - 300px);"
                            title="Certificate Preview"
                        ></iframe>
                    </div>

                    <!-- Empty State -->
                    <div v-else class="flex flex-col items-center justify-center h-full min-h-[500px]">
                        <div class="text-center">
                            <Eye class="h-16 w-16 text-gray-300 mx-auto mb-4" />
                            <p class="text-gray-500 font-medium">No preview available</p>
                            <p class="text-sm text-gray-400 mt-2">Select an enrollment to preview</p>
                        </div>
                    </div>
                </div>

                <!-- Modal Footer -->
                <div class="flex items-center justify-end gap-3 px-6 py-4 border-t border-gray-200 bg-gray-50">
                    <button
                        @click="handleClose"
                        class="px-5 py-2.5 rounded-lg border border-gray-300 text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 transition-colors"
                    >
                        Close
                    </button>
                </div>
            </div>
        </div>
    </Transition>
</template>

<style scoped>
.modal-enter-active,
.modal-leave-active {
    transition: opacity 0.3s ease;
}

.modal-enter-from,
.modal-leave-to {
    opacity: 0;
}

.modal-enter-active .bg-white,
.modal-leave-active .bg-white {
    transition: transform 0.3s ease;
}

.modal-enter-from .bg-white,
.modal-leave-to .bg-white {
    transform: scale(0.95);
}
</style>
