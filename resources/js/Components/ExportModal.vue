<script setup>
import { Download, FileText, Share, ChevronUp, X } from "lucide-vue-next";

const props = defineProps({
    show: {
        type: Boolean,
        required: true,
    },
    activeTab: {
        type: String,
        default: "data",
    },
    dataType: {
        type: String,
        default: null,
    },
});

const emit = defineEmits(["close", "exportCSV", "exportPDF"]);

const handleClose = () => {
    emit("close");
};

const handleExportCSV = () => {
    emit("exportCSV");
};

const handleExportPDF = () => {
    emit("exportPDF");
};
</script>

<template>
    <Transition name="modal">
        <div
            v-if="show"
            class="fixed inset-0 z-50 overflow-y-auto"
            aria-labelledby="modal-title"
            role="dialog"
            aria-modal="true"
        >
            <!-- Background overlay -->
            <div
                class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity"
                @click="handleClose"
            ></div>

            <!-- Modal panel -->
            <div class="flex min-h-full items-center justify-center p-4">
                <div
                    class="relative bg-white rounded-[25px] shadow-2xl max-w-md w-full p-6 transform transition-all"
                    @click.stop
                >
                <!-- Modal Header -->
                <div class="flex items-center justify-between mb-6">
                    <div class="flex items-center gap-3">
                        <div
                            class="w-10 h-10 bg-gradient-to-br from-[#2f837d] to-[#257067] rounded-full flex items-center justify-center"
                        >
                            <Share class="h-5 w-5 text-white" />
                        </div>
                        <h3 class="text-xl font-bold text-gray-900">
                            Export Data
                        </h3>
                    </div>
                    <button
                        @click="handleClose"
                        class="text-gray-400 hover:text-gray-600 transition-colors rounded-full p-1 hover:bg-gray-100"
                    >
                        <X class="h-5 w-5" />
                    </button>
                </div>

                <!-- Modal Content -->
                <p class="text-sm text-gray-600 mb-6">
                    Choose your preferred export format to download the
                    {{ dataType || activeTab }} data.
                </p>

                <!-- Export Options -->
                <div class="space-y-3">
                    <button
                        @click="handleExportCSV"
                        class="w-full px-6 py-4 bg-gradient-to-r from-[#2f837d] to-[#257067] text-white rounded-xl hover:shadow-lg transform hover:-translate-y-0.5 transition-all duration-200 flex items-center justify-between group"
                    >
                        <div class="flex items-center gap-3">
                            <div
                                class="w-10 h-10 bg-white bg-opacity-20 rounded-lg flex items-center justify-center"
                            >
                                <Download class="h-5 w-5" />
                            </div>
                            <div class="text-left">
                                <div class="font-semibold">Export as CSV</div>
                                <div class="text-xs opacity-90">
                                    Download spreadsheet file
                                </div>
                            </div>
                        </div>
                        <ChevronUp
                            class="h-5 w-5 -rotate-90 group-hover:translate-x-1 transition-transform"
                        />
                    </button>

                    <button
                        @click="handleExportPDF"
                        class="w-full px-6 py-4 bg-white text-[#2f837d] border-2 border-[#2f837d] rounded-xl hover:bg-[#2f837d] hover:text-white hover:shadow-lg transform hover:-translate-y-0.5 transition-all duration-200 flex items-center justify-between group"
                    >
                        <div class="flex items-center gap-3">
                            <div
                                class="w-10 h-10 bg-[#2f837d] bg-opacity-10 rounded-lg flex items-center justify-center group-hover:bg-white group-hover:bg-opacity-20"
                            >
                                <FileText class="h-5 w-5" />
                            </div>
                            <div class="text-left">
                                <div class="font-semibold">Export as PDF</div>
                                <div class="text-xs opacity-75">
                                    Download document file
                                </div>
                            </div>
                        </div>
                        <ChevronUp
                            class="h-5 w-5 -rotate-90 group-hover:translate-x-1 transition-transform"
                        />
                    </button>
                </div>

                <!-- Modal Footer -->
                <div class="mt-6 pt-4 border-t border-gray-200">
                    <button
                        @click="handleClose"
                        class="w-full px-4 py-2 text-gray-600 hover:text-gray-900 hover:bg-gray-100 rounded-lg transition-colors font-medium"
                    >
                        Cancel
                    </button>
                </div>
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
    transform: scale(0.9);
}
</style>
