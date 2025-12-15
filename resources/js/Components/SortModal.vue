<script setup>
import { ArrowDownNarrowWide, X } from "lucide-vue-next";

const props = defineProps({
    show: {
        type: Boolean,
        required: true,
    },
    sortColumn: {
        type: String,
        default: "",
    },
    sortDirection: {
        type: String,
        default: "asc",
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
                            Sort Users
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
                        @click="handleSort('name')"
                        :class="[
                            'w-full px-4 py-3 rounded-lg text-left transition-all flex items-center justify-between',
                            sortColumn === 'name'
                                ? 'bg-[#2f837d] text-white'
                                : 'bg-gray-50 hover:bg-gray-100 text-gray-900',
                        ]"
                    >
                        <span class="font-medium">Name</span>
                        <span v-if="sortColumn === 'name'" class="text-xs">
                            {{ sortDirection === "asc" ? "A-Z" : "Z-A" }}
                        </span>
                    </button>

                    <button
                        @click="handleSort('email')"
                        :class="[
                            'w-full px-4 py-3 rounded-lg text-left transition-all flex items-center justify-between',
                            sortColumn === 'email'
                                ? 'bg-[#2f837d] text-white'
                                : 'bg-gray-50 hover:bg-gray-100 text-gray-900',
                        ]"
                    >
                        <span class="font-medium">Email</span>
                        <span v-if="sortColumn === 'email'" class="text-xs">
                            {{ sortDirection === "asc" ? "A-Z" : "Z-A" }}
                        </span>
                    </button>

                    <button
                        @click="handleSort('department')"
                        :class="[
                            'w-full px-4 py-3 rounded-lg text-left transition-all flex items-center justify-between',
                            sortColumn === 'department'
                                ? 'bg-[#2f837d] text-white'
                                : 'bg-gray-50 hover:bg-gray-100 text-gray-900',
                        ]"
                    >
                        <span class="font-medium">Department</span>
                        <span
                            v-if="sortColumn === 'department'"
                            class="text-xs"
                        >
                            {{ sortDirection === "asc" ? "A-Z" : "Z-A" }}
                        </span>
                    </button>

                    <button
                        @click="handleSort('status')"
                        :class="[
                            'w-full px-4 py-3 rounded-lg text-left transition-all flex items-center justify-between',
                            sortColumn === 'status'
                                ? 'bg-[#2f837d] text-white'
                                : 'bg-gray-50 hover:bg-gray-100 text-gray-900',
                        ]"
                    >
                        <span class="font-medium">Status</span>
                        <span v-if="sortColumn === 'status'" class="text-xs">
                            {{
                                sortDirection === "asc"
                                    ? "Active First"
                                    : "Inactive First"
                            }}
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
