<script setup>
import { ref, onMounted, onUnmounted } from "vue";
import { ArrowDownNarrowWide, ArrowUp, ArrowDown, Check } from "lucide-vue-next";

const props = defineProps({
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
            { value: "name", label: "Name" },
            { value: "email", label: "Email" },
            { value: "department", label: "Department" },
            { value: "status", label: "Status" },
        ],
    },
});

const emit = defineEmits(["sort", "reset"]);

const isOpen = ref(false);
const dropdownRef = ref(null);
const triggerRef = ref(null);

const toggleDropdown = () => {
    isOpen.value = !isOpen.value;
};

const closeDropdown = () => {
    isOpen.value = false;
};

const handleSort = (column) => {
    let direction = "asc";
    if (props.sortColumn === column) {
        direction = props.sortDirection === "asc" ? "desc" : "asc";
    }
    emit("sort", { column, direction });
    closeDropdown();
};

const handleReset = () => {
    emit("reset");
    closeDropdown();
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
                    <ArrowDownNarrowWide class="h-4 w-4" />
                    <p>Sort</p>
                </button>
            </slot>
        </div>

        <div
            v-if="isOpen"
            ref="dropdownRef"
            class="absolute right-0 mt-2 w-56 rounded-xl bg-white shadow-lg ring-1 ring-black ring-opacity-5 focus:outline-none z-50 py-2"
        >
            <div class="px-4 py-2 border-b border-gray-100 flex justify-between items-center mb-1">
                <span class="text-xs font-bold text-gray-500 uppercase tracking-wider">Sort By</span>
                <button 
                    v-if="sortColumn" 
                    @click.stop="handleReset"
                    class="text-xs text-red-500 hover:text-red-700 font-medium transition-colors"
                >
                    Clear
                </button>
            </div>
            
            <div class="py-1">
                <button
                    v-for="option in sortOptions"
                    :key="option.value"
                    @click="handleSort(option.value)"
                    class="w-full px-4 py-3 flex items-center justify-between hover:bg-gray-50 transition-all duration-200 group"
                    :class="{ 'bg-gray-50': sortColumn === option.value }"
                >
                    <span 
                        class="text-sm font-medium transition-colors"
                        :class="sortColumn === option.value ? 'text-[#2f837d]' : 'text-gray-700'"
                    >
                        {{ option.label }}
                    </span>
                    
                    <div v-if="sortColumn === option.value" class="text-[#2f837d] animate-in fade-in zoom-in duration-200">
                        <ArrowUp v-if="sortDirection === 'asc'" class="h-4 w-4" />
                        <ArrowDown v-else class="h-4 w-4" />
                    </div>
                </button>
            </div>
        </div>
    </div>
</template>
