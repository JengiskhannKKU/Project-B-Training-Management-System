<script setup>
import { ref, computed, watch, onMounted, onUnmounted } from 'vue';
import { ChevronsUpDown, Check, Search } from 'lucide-vue-next';

const props = defineProps({
    modelValue: {
        type: [String, Number],
        default: ''
    },
    options: {
        type: Array,
        default: () => []
    },
    labelKey: {
        type: String,
        default: 'name'
    },
    valueKey: {
        type: String,
        default: 'id'
    },
    placeholder: {
        type: String,
        default: 'Select an option'
    },
    disabled: {
        type: Boolean,
        default: false
    },
    error: {
        type: String,
        default: ''
    }
});

const emit = defineEmits(['update:modelValue', 'change']);

const isOpen = ref(false);
const searchQuery = ref('');
const containerRef = ref(null);

// Find the selected option object based on modelValue
const selectedOption = computed(() => {
    return props.options.find(opt => opt[props.valueKey] === props.modelValue);
});

// Display text for the input
const displayValue = computed(() => {
    if (selectedOption.value) {
        return selectedOption.value[props.labelKey] || selectedOption.value.title;
    }
    return '';
});

// Filter options based on search query
const filteredOptions = computed(() => {
    if (!searchQuery.value) return props.options;
    const query = searchQuery.value.toLowerCase();
    return props.options.filter(opt => {
        const label = (opt[props.labelKey] || opt.title || '').toString().toLowerCase();
        return label.includes(query);
    });
});

const toggleDropdown = () => {
    if (props.disabled) return;
    isOpen.value = !isOpen.value;
    if (isOpen.value) {
        // Focus search input or just clear query?
        // Let's keep the query empty to show all options initially
        searchQuery.value = '';
    }
};

const selectOption = (option) => {
    emit('update:modelValue', option[props.valueKey]);
    emit('change', option);
    isOpen.value = false;
    searchQuery.value = '';
};

// Close dropdown when clicking outside
const handleClickOutside = (event) => {
    if (containerRef.value && !containerRef.value.contains(event.target)) {
        isOpen.value = false;
    }
};

onMounted(() => {
    document.addEventListener('click', handleClickOutside);
});

onUnmounted(() => {
    document.removeEventListener('click', handleClickOutside);
});
</script>

<template>
    <div ref="containerRef" class="relative w-full">
        <!-- Trigger Button -->
        <button
            type="button"
            @click="toggleDropdown"
            :disabled="disabled"
            :class="[
                'w-full flex items-center justify-between rounded-lg border bg-white px-3 py-2 text-left shadow-sm focus:outline-none focus:ring-2 focus:ring-teal-500 focus:border-teal-500 sm:text-sm transition-colors',
                error ? 'border-red-300' : 'border-gray-300',
                disabled ? 'bg-gray-100 cursor-not-allowed text-gray-500' : 'hover:border-gray-400'
            ]"
        >
            <span class="block truncate" :class="!displayValue ? 'text-gray-500' : 'text-gray-900'">
                {{ displayValue || placeholder }}
            </span>
            <ChevronsUpDown class="h-4 w-4 text-gray-400" aria-hidden="true" />
        </button>

        <!-- Dropdown Menu -->
        <div
            v-if="isOpen"
            class="absolute z-50 mt-1 max-h-60 w-full overflow-auto rounded-md bg-white py-1 text-base shadow-lg ring-1 ring-black ring-opacity-5 focus:outline-none sm:text-sm"
        >
            <!-- Search Input -->
            <div class="sticky top-0 z-10 bg-white px-2 py-2 border-b border-gray-100">
                <div class="relative">
                    <Search class="absolute left-2 top-2.5 h-4 w-4 text-gray-400" />
                    <input
                        v-model="searchQuery"
                        type="text"
                        class="w-full rounded-md border border-gray-200 py-1.5 pl-8 pr-3 text-sm placeholder:text-gray-400 focus:border-teal-500 focus:ring-teal-500"
                        placeholder="Search..."
                        @click.stop
                    />
                </div>
            </div>

            <!-- Options List -->
            <ul class="py-1">
                <li
                    v-for="option in filteredOptions"
                    :key="option[valueKey]"
                    @click="selectOption(option)"
                    class="relative cursor-default select-none py-2 pl-3 pr-9 text-gray-900 hover:bg-teal-50 hover:text-teal-900 cursor-pointer"
                >
                    <span :class="['block truncate', option[valueKey] === modelValue ? 'font-semibold' : 'font-normal']">
                        {{ option[labelKey] || option.title }}
                    </span>

                    <span
                        v-if="option[valueKey] === modelValue"
                        class="absolute inset-y-0 right-0 flex items-center pr-4 text-teal-600"
                    >
                        <Check class="h-4 w-4" aria-hidden="true" />
                    </span>
                </li>

                <li v-if="filteredOptions.length === 0" class="relative cursor-default select-none py-2 pl-3 pr-9 text-gray-500">
                    No results found.
                </li>
            </ul>
        </div>
        
        <!-- Error Message (Optional, mirroring the parent's error display style usually) -->
        <!-- We let the parent handle the error text display below the component -->
    </div>
</template>
