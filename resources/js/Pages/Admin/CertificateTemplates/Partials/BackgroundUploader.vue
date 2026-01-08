<script setup>
import { ref } from "vue";
import { UploadCloud, ImageIcon, FileText, Trash2 } from "lucide-vue-next";

const props = defineProps({
    previewUrl: {
        type: String,
        default: "",
    },
    fileName: {
        type: String,
        default: "",
    },
    fileSize: {
        type: Number,
        default: 0,
    },
});

const emit = defineEmits(["upload", "remove"]);

const isDragging = ref(false);

const handleDragOver = (event) => {
    event.preventDefault();
    isDragging.value = true;
};

const handleDragLeave = () => {
    isDragging.value = false;
};

const handleDrop = (event) => {
    event.preventDefault();
    isDragging.value = false;
    handleUpload(event);
};

const handleUpload = (event) => {
    const file = event.target?.files?.[0] || event.dataTransfer?.files?.[0];
    if (file) {
        emit("upload", file);
    }
};

const handleRemove = () => {
    emit("remove");
};

const formatFileSize = (bytes) => {
    if (bytes < 1024) return `${bytes} B`;
    if (bytes < 1024 * 1024) return `${(bytes / 1024).toFixed(1)} KB`;
    return `${(bytes / (1024 * 1024)).toFixed(1)} MB`;
};
</script>

<template>
    <div class="rounded-2xl border border-gray-200 bg-white shadow-sm space-y-4 overflow-hidden transition-shadow hover:shadow-md">
        <!-- Header -->
        <div class="flex items-center gap-3 px-6 pt-6">
            <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-gradient-to-br from-amber-100 to-amber-50">
                <ImageIcon class="h-5 w-5 text-amber-600" />
            </div>
            <div>
                <h2 class="text-lg font-semibold text-gray-900">Background Image</h2>
                <p class="text-sm text-gray-500">Upload the certificate background artwork.</p>
            </div>
        </div>

        <div class="px-6 pb-6 space-y-4">
            <!-- Upload Zone -->
            <label
                class="flex flex-col items-center justify-center gap-3 rounded-xl border-2 border-dashed px-4 py-8 text-sm cursor-pointer transition-all duration-200"
                :class="[
                    isDragging
                        ? 'border-[#2f837d] bg-[#2f837d]/10 scale-[1.02]'
                        : 'border-gray-300 bg-gray-50 hover:border-[#2f837d] hover:bg-[#2f837d]/5'
                ]"
                @dragover="handleDragOver"
                @dragleave="handleDragLeave"
                @drop="handleDrop"
            >
                <div class="flex h-12 w-12 items-center justify-center rounded-full bg-[#2f837d]/10">
                    <UploadCloud class="h-6 w-6 text-[#2f837d]" />
                </div>
                <div class="text-center">
                    <span class="font-medium text-gray-700">Drop image here or click to upload</span>
                    <p class="mt-1 text-xs text-gray-500">PNG, JPG up to 10MB</p>
                </div>
                <input type="file" accept="image/*" class="hidden" @change="handleUpload" />
            </label>

            <!-- Preview -->
            <div v-if="previewUrl" class="rounded-xl border border-gray-200 p-3 space-y-3">
                <img
                    :src="previewUrl"
                    alt="Background preview"
                    class="h-36 w-full rounded-lg object-cover"
                />
                <div class="flex items-center justify-between">
                    <div v-if="fileName" class="flex items-center gap-2 text-sm">
                        <FileText class="h-4 w-4 text-gray-400" />
                        <span class="text-gray-700 truncate max-w-[150px]">{{ fileName }}</span>
                        <span class="text-gray-400">{{ formatFileSize(fileSize) }}</span>
                    </div>
                    <p v-else class="text-xs text-gray-500">Existing background image</p>
                    <button
                        type="button"
                        @click="handleRemove"
                        class="inline-flex items-center gap-1 rounded-lg px-2 py-1 text-xs font-medium text-red-600 hover:bg-red-50 transition-colors"
                    >
                        <Trash2 class="h-3.5 w-3.5" />
                        Remove
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>
