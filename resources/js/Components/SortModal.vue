<script setup>
import { ArrowDownNarrowWide, X } from "lucide-vue-next";

const props = defineProps({
    show: {
        type: Boolean,
        required: true,
    },
    title: {
        type: String,
        default: "Sort Users",
    },
    sortColumn: {
        type: String,
        default: "",
    },
    sortDirection: {
        type: String,
        default: "asc",
    },
    sortOptions: {
        type: Array,
        default: () => [
            { value: "name", label: "Name", directionLabels: { asc: "A-Z", desc: "Z-A" } },
            { value: "email", label: "Email", directionLabels: { asc: "A-Z", desc: "Z-A" } },
            { value: "department", label: "Department", directionLabels: { asc: "A-Z", desc: "Z-A" } },
            { value: "status", label: "Status", directionLabels: { asc: "Active First", desc: "Inactive First" } },
        ],
    },
});

const emit = defineEmits(["close", "sort", "reset"]);

const handleClose = () => {
    emit("close");
};

const handleSort = (column) => {
    emit("sort", column);
};

const handleReset = () => {
    emit("reset");
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
                            <ArrowDownNarrowWide class="h-5 w-5 text-white" />
                        </div>
                        <h3 class="text-xl font-bold text-gray-900">
                            {{ title }}
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
                <p class="text-sm text-gray-600 mb-4">
                    Choose a field to sort by:
                </p>

                <!-- Sort Options -->
                <div class="space-y-2">
                    <button
                        v-for="option in sortOptions"
                        :key="option.value"
                        @click="handleSort(option.value)"
                        :class="[
                            'w-full px-4 py-3 rounded-lg text-left transition-all flex items-center justify-between',
                            sortColumn === option.value
                                ? 'bg-[#2f837d] text-white'
                                : 'bg-gray-50 hover:bg-gray-100 text-gray-900',
                        ]"
                    >
                        <span class="font-medium">{{ option.label }}</span>
                        <span v-if="sortColumn === option.value" class="text-xs">
                            {{ sortDirection === "asc" ? option.directionLabels.asc : option.directionLabels.desc }}
                        </span>
                    </button>
                </div>

                <!-- Modal Footer -->
                <div class="mt-6 pt-4 border-t border-gray-200 flex gap-3">
                    <button
                        @click="handleReset"
                        class="flex-1 px-4 py-2 text-gray-600 hover:text-gray-900 hover:bg-gray-100 rounded-lg transition-colors font-medium"
                    >
                        Reset
                    </button>
                    <button
                        @click="handleClose"
                        class="flex-1 px-4 py-2 text-gray-600 hover:text-gray-900 hover:bg-gray-100 rounded-lg transition-colors font-medium"
                    >
                        Close
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
