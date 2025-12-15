<script setup>
import { ListFilterIcon, X } from "lucide-vue-next";

const props = defineProps({
    show: {
        type: Boolean,
        required: true,
    },
    selectedDepartment: {
        type: String,
        required: true,
    },
    selectedStatus: {
        type: String,
        required: true,
    },
    departments: {
        type: Array,
        required: true,
    },
});

const emit = defineEmits([
    "close",
    "reset",
    "update:selectedDepartment",
    "update:selectedStatus",
]);

const handleClose = () => {
    emit("close");
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
                            <ListFilterIcon class="h-5 w-5 text-white" />
                        </div>
                        <h3 class="text-xl font-bold text-gray-900">
                            Filter Users
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
                <div class="space-y-4">
                    <!-- Department Filter -->
                    <div>
                        <label
                            class="block text-sm font-medium text-gray-700 mb-2"
                        >
                            Department
                        </label>
                        <select
                            :value="selectedDepartment"
                            @input="
                                $emit(
                                    'update:selectedDepartment',
                                    $event.target.value
                                )
                            "
                            class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#2f837d] focus:border-transparent"
                        >
                            <option value="all">All Departments</option>
                            <option
                                v-for="dept in departments"
                                :key="dept"
                                :value="dept"
                            >
                                {{ dept }}
                            </option>
                        </select>
                    </div>

                    <!-- Status Filter -->
                    <div>
                        <label
                            class="block text-sm font-medium text-gray-700 mb-2"
                        >
                            Status
                        </label>
                        <select
                            :value="selectedStatus"
                            @input="
                                $emit(
                                    'update:selectedStatus',
                                    $event.target.value
                                )
                            "
                            class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#2f837d] focus:border-transparent"
                        >
                            <option value="all">All Status</option>
                            <option value="Active">Active</option>
                            <option value="Inactive">Inactive</option>
                        </select>
                    </div>
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
                        class="flex-1 px-4 py-2 bg-gradient-to-r from-[#2f837d] to-[#257067] text-white rounded-lg hover:shadow-lg transition-all font-medium"
                    >
                        Apply
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
