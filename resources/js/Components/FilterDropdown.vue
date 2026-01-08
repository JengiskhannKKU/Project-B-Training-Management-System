<script setup>
import { ref, onMounted, onUnmounted, watch, computed } from "vue";
import { ListFilterIcon, X, Check } from "lucide-vue-next";

const props = defineProps({
    title: {
        type: String,
        default: "Filter",
    },
    selectedDepartment: {
        type: Array,
        required: true,
    },
    selectedStatus: {
        type: Array,
        required: true,
    },
    selectedAssignment: {
        type: String,
        default: "all",
    },
    departments: {
        type: Array,
        required: true,
    },
    statusOptions: {
        type: Array,
        default: () => ["Active", "Inactive"],
    },
    departmentLabel: {
        type: String,
        default: "Department",
    },
    showAssignmentFilter: {
        type: Boolean,
        default: false,
    },
});

const emit = defineEmits([
    "reset",
    "update:selectedDepartment",
    "update:selectedStatus",
    "update:selectedAssignment",
    "apply"
]);

const isOpen = ref(false);
const dropdownRef = ref(null);
const triggerRef = ref(null);

// Local state for deferred application
const localDepartment = ref([...props.selectedDepartment]);
const localStatus = ref([...props.selectedStatus]);
const localAssignment = ref(props.selectedAssignment);

watch(() => props.selectedDepartment, (val) => localDepartment.value = [...val]);
watch(() => props.selectedStatus, (val) => localStatus.value = [...val]);
watch(() => props.selectedAssignment, (val) => localAssignment.value = val);

const toggleDropdown = () => {
    isOpen.value = !isOpen.value;
    if (isOpen.value) {
        // Sync local state when opening
        localDepartment.value = [...props.selectedDepartment];
        localStatus.value = [...props.selectedStatus];
        localAssignment.value = props.selectedAssignment;
    }
};

const closeDropdown = () => {
    isOpen.value = false;
};

const handleApply = () => {
    emit("update:selectedDepartment", localDepartment.value);
    emit("update:selectedStatus", localStatus.value);
    emit("update:selectedAssignment", localAssignment.value);
    emit("apply");
    closeDropdown();
};

const handleReset = () => {
    localDepartment.value = [];
    localStatus.value = [];
    localAssignment.value = "all";
    emit("reset");
    closeDropdown(); // Ideally reset should likely be instant or apply immediately? Detailed plan says "reset and clear".
};

const toggleDepartment = (dept) => {
    const index = localDepartment.value.indexOf(dept);
    if (index === -1) {
        localDepartment.value.push(dept);
    } else {
        localDepartment.value.splice(index, 1);
    }
};

const toggleStatus = (status) => {
    const index = localStatus.value.indexOf(status);
    if (index === -1) {
        localStatus.value.push(status);
    } else {
        localStatus.value.splice(index, 1);
    }
};

const handleClickOutside = (event) => {
    if (dropdownRef.value && !dropdownRef.value.contains(event.target) && 
        triggerRef.value && !triggerRef.value.contains(event.target)) {
        closeDropdown();
    }
};

onMounted(() => {
    document.addEventListener("click", handleClickOutside);
});

onUnmounted(() => {
    document.removeEventListener("click", handleClickOutside);
});
</script>

<template>
    <div class="relative inline-block text-left">
        <div ref="triggerRef" @click="toggleDropdown">
            <slot name="trigger">
                <button
                    class="rounded-lg border border-[#d5dde7] inline-flex gap-2 items-center px-4 py-2 hover:bg-gray-50 transition-colors"
                >
                    <ListFilterIcon class="h-4 w-4" />
                    <p>Filter</p>
                </button>
            </slot>
        </div>

        <div
            v-if="isOpen"
            ref="dropdownRef"
            class="absolute right-0 mt-2 w-80 rounded-xl bg-white shadow-lg ring-1 ring-black ring-opacity-5 focus:outline-none z-50 p-4"
        >
            <div class="flex items-center justify-between mb-4">
                <h3 class="font-semibold text-gray-900">{{ title }}</h3>
                <button 
                    @click="closeDropdown"
                    class="text-gray-400 hover:text-gray-600 rounded-full p-1 hover:bg-gray-100"
                >
                    <X class="h-4 w-4" />
                </button>
            </div>

            <div class="space-y-6 max-h-[60vh] overflow-y-auto px-2 custom-scrollbar">
                <!-- Assignment Filter -->
                <div v-if="showAssignmentFilter">
                    <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-3">
                        Assignment
                    </label>
                    <div class="bg-gray-50 p-1 rounded-lg flex">
                        <button
                            v-for="option in [{ label: 'All', value: 'all' }, { label: 'My Courses', value: 'my' }]"
                            :key="option.value"
                            @click="localAssignment = option.value"
                            class="flex-1 py-1.5 text-sm font-medium rounded-md transition-all duration-200"
                            :class="localAssignment === option.value ? 'bg-white text-[#2f837d] shadow-sm' : 'text-gray-500 hover:text-gray-700'"
                        >
                            {{ option.label }}
                        </button>
                    </div>
                </div>

                <!-- Department Filter -->
                <div>
                    <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-3">
                        {{ departmentLabel }}
                    </label>
                    <div class="space-y-1">
                        <div 
                            v-for="dept in departments" 
                            :key="dept"
                            @click="toggleDepartment(dept)"
                            class="flex items-center justify-between px-3 py-2 rounded-lg cursor-pointer transition-all duration-200 group"
                            :class="localDepartment.includes(dept) ? 'bg-[#2f837d]/10 text-[#2f837d]' : 'hover:bg-gray-50 text-gray-700'"
                        >
                            <span class="text-sm font-medium">{{ dept }}</span>
                            <div 
                                class="h-5 w-5 rounded-full border flex items-center justify-center transition-colors"
                                :class="localDepartment.includes(dept) ? 'border-[#2f837d] bg-[#2f837d]' : 'border-gray-300 group-hover:border-gray-400'"
                            >
                                <Check v-if="localDepartment.includes(dept)" class="h-3 w-3 text-white" stroke-width="3" />
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Status Filter -->
                <div>
                    <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-3">
                        Status
                    </label>
                    <div class="space-y-1">
                        <div 
                            v-for="status in statusOptions" 
                            :key="status"
                            @click="toggleStatus(status)"
                            class="flex items-center justify-between px-3 py-2 rounded-lg cursor-pointer transition-all duration-200 group"
                            :class="localStatus.includes(status) ? 'bg-[#2f837d]/10 text-[#2f837d]' : 'hover:bg-gray-50 text-gray-700'"
                        >
                            <span class="text-sm font-medium">{{ status }}</span>
                            <div 
                                class="h-5 w-5 rounded-full border flex items-center justify-center transition-colors"
                                :class="localStatus.includes(status) ? 'border-[#2f837d] bg-[#2f837d]' : 'border-gray-300 group-hover:border-gray-400'"
                            >
                                <Check v-if="localStatus.includes(status)" class="h-3 w-3 text-white" stroke-width="3" />
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="mt-6 pt-4 border-t border-gray-100 flex gap-2">
                <button
                    @click="handleReset"
                    class="flex-1 px-3 py-2 text-sm text-gray-600 hover:text-gray-900 hover:bg-gray-100 rounded-lg transition-colors font-medium border border-transparent hover:border-gray-200"
                >
                    Clear All
                </button>
                <button
                    @click="handleApply"
                    class="flex-1 px-3 py-2 text-sm bg-gradient-to-r from-[#2f837d] to-[#257067] text-white rounded-lg hover:shadow-lg transition-all font-medium"
                >
                    Apply Filters
                </button>
            </div>
        </div>
    </div>
</template>

<style scoped>
.custom-scrollbar::-webkit-scrollbar {
    width: 4px;
}
.custom-scrollbar::-webkit-scrollbar-track {
    background: #f1f1f1;
}
.custom-scrollbar::-webkit-scrollbar-thumb {
    background: #d1d5db;
    border-radius: 4px;
}
.custom-scrollbar::-webkit-scrollbar-thumb:hover {
    background: #9ca3af;
}
</style>
