<script setup>
import { ref, watch, computed } from "vue";
import { Palette, Type, X } from "lucide-vue-next";

const props = defineProps({
    fontFamily: {
        type: String,
        default: "",
    },
    fontSize: {
        type: [Number, String],
        default: "",
    },
    textColor: {
        type: String,
        default: "",
    },
    qrSize: {
        type: Number,
        default: 160,
    },
    selectedPlaceholder: {
        type: String,
        default: null,
    },
    placeholderStyles: {
        type: Object,
        default: () => ({}),
    },
    errors: {
        type: Object,
        default: () => ({}),
    },
});

const emit = defineEmits([
    "update:fontFamily",
    "update:fontSize",
    "update:textColor",
    "update:qrSize",
    "update:placeholderStyle",
]);

const colorPicker = ref(null);

const fontOptions = [
    { label: "Default (GD built-in)", value: "" },
    { label: "Prompt Regular (Prompt-Regular.ttf)", value: "Prompt-Regular.ttf" },
    { label: "Prompt Medium (Prompt-Medium.ttf)", value: "Prompt-Medium.ttf" },
    { label: "Prompt Bold (Prompt-Bold.ttf)", value: "Prompt-Bold.ttf" },
    { label: "Custom font file...", value: "__custom__" },
];

const presetColors = [
    "#1f2937",
    "#111827",
    "#2f837d",
    "#1e40af",
    "#7c3aed",
    "#b91c1c",
];

const fontSelection = ref("");
const customFontValue = ref("");

// Sync font selection with prop
watch(
    () => props.fontFamily,
    (value) => {
        if (!value) {
            fontSelection.value = "";
            customFontValue.value = "";
            return;
        }
        const matched = fontOptions.find((opt) => opt.value === value);
        if (matched) {
            fontSelection.value = matched.value;
            customFontValue.value = "";
        } else {
            fontSelection.value = "__custom__";
            customFontValue.value = value;
        }
    },
    { immediate: true }
);

watch(fontSelection, (value) => {
    if (value === "__custom__") {
        emit("update:fontFamily", customFontValue.value);
    } else {
        emit("update:fontFamily", value);
        customFontValue.value = "";
    }
});

watch(customFontValue, (value) => {
    if (fontSelection.value === "__custom__") {
        emit("update:fontFamily", value);
    }
});

// Font style options
const fontStyleOptions = [
    { label: "Normal", value: "normal" },
    { label: "Bold", value: "bold" },
    { label: "Italic", value: "italic" },
];

// Get label for selected placeholder
const placeholderLabels = {
    name: "Student Name",
    program: "Program Name",
    session: "Session Title",
    issued_at: "Issued Date",
    certificate_code: "Certificate Code",
};

const selectedPlaceholderLabel = computed(() => {
    return props.selectedPlaceholder ? placeholderLabels[props.selectedPlaceholder] : null;
});

// Get current styles (from placeholder or global)
const currentStyles = computed(() => {
    if (props.selectedPlaceholder && props.placeholderStyles[props.selectedPlaceholder]) {
        return props.placeholderStyles[props.selectedPlaceholder];
    }
    return {
        color: null,
        fontSize: null,
        fontStyle: 'normal',
        fontFamily: null,
    };
});

// Computed values for inputs
const currentColor = computed(() => {
    return currentStyles.value.color || props.textColor || "#1f2937";
});

const currentFontSize = computed(() => {
    return currentStyles.value.fontSize || props.fontSize || "";
});

const currentFontStyle = computed(() => {
    return currentStyles.value.fontStyle || 'normal';
});

const displayColor = computed(() => currentColor.value);

const openColorPicker = () => {
    colorPicker.value?.click();
};

// Update placeholder or global style
const updateStyle = (property, value) => {
    if (props.selectedPlaceholder) {
        emit("update:placeholderStyle", {
            key: props.selectedPlaceholder,
            property,
            value
        });
    } else {
        // Update global style
        if (property === 'color') emit("update:textColor", value);
        if (property === 'fontSize') emit("update:fontSize", value);
        if (property === 'fontFamily') emit("update:fontFamily", value);
    }
};

// Clear all custom styles for selected placeholder
const clearCustomStyles = () => {
    if (!props.selectedPlaceholder) return;

    emit("update:placeholderStyle", { key: props.selectedPlaceholder, property: 'color', value: null });
    emit("update:placeholderStyle", { key: props.selectedPlaceholder, property: 'fontSize', value: null });
    emit("update:placeholderStyle", { key: props.selectedPlaceholder, property: 'fontStyle', value: 'normal' });
    emit("update:placeholderStyle", { key: props.selectedPlaceholder, property: 'fontFamily', value: null });
};

// Check if placeholder has any custom styles
const hasAnyCustomStyle = computed(() => {
    if (!props.selectedPlaceholder) return false;
    const styles = currentStyles.value;
    return !!(styles.color || styles.fontSize || (styles.fontStyle && styles.fontStyle !== 'normal'));
});
</script>

<template>
    <div class="rounded-2xl border border-gray-200 bg-white shadow-sm space-y-4 overflow-hidden transition-shadow hover:shadow-md">
        <!-- Header -->
        <div class="flex items-center gap-3 px-6 pt-6">
            <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-gradient-to-br from-purple-100 to-purple-50">
                <Palette class="h-5 w-5 text-purple-600" />
            </div>
            <div class="flex-1">
                <h2 class="text-lg font-semibold text-gray-900">Style Settings</h2>
                <p v-if="!selectedPlaceholder" class="text-sm text-gray-500">
                    Global fonts and colors for text placeholders.
                </p>
                <div v-else class="flex items-center gap-2">
                    <div class="inline-flex items-center gap-1.5 px-2.5 py-1 bg-[#2f837d]/10 border border-[#2f837d]/30 rounded-lg">
                        <Type class="h-3.5 w-3.5 text-[#2f837d] animate-pulse" />
                        <span class="text-xs font-semibold text-[#2f837d]">{{ selectedPlaceholderLabel }}</span>
                    </div>
                    <span class="text-xs text-gray-500">← Customizing this placeholder</span>
                </div>
            </div>
        </div>

        <div class="px-6 pb-6 space-y-4">
            <!-- Empty State - No Placeholder Selected -->
            <div v-if="!selectedPlaceholder" class="mb-4 p-4 bg-gradient-to-br from-purple-50 to-blue-50 rounded-xl border border-purple-200">
                <div class="flex items-start gap-3">
                    <div class="flex-shrink-0 w-10 h-10 bg-purple-100 rounded-lg flex items-center justify-center">
                        <Type class="h-5 w-5 text-purple-600" />
                    </div>
                    <div class="flex-1">
                        <h3 class="text-sm font-semibold text-gray-900 mb-1">Customize Individual Placeholders</h3>
                        <p class="text-xs text-gray-600 leading-relaxed">
                            <strong>Step 1:</strong> Drag placeholders from the toolbar to the canvas<br>
                            <strong>Step 2:</strong> Click on any placeholder to customize its color, size, and style<br>
                            <strong>Tip:</strong> Placeholders with custom styling will show a ✨ icon
                        </p>
                    </div>
                </div>
            </div>

            <!-- Font Family (hidden for individual placeholder) -->
            <div v-if="!selectedPlaceholder">
                <label class="block text-sm font-medium text-gray-700">Font family (optional)</label>
                <select
                    v-model="fontSelection"
                    class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-transparent focus:ring-2 focus:ring-[#2f837d] transition-shadow"
                >
                    <option v-for="option in fontOptions" :key="option.value" :value="option.value">
                        {{ option.label }}
                    </option>
                </select>
                <input
                    v-if="fontSelection === '__custom__'"
                    v-model="customFontValue"
                    type="text"
                    class="mt-2 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-transparent focus:ring-2 focus:ring-[#2f837d] transition-shadow"
                    placeholder="storage/app/fonts/YourFont.ttf"
                />
                <p class="mt-1 text-xs text-gray-500">
                    Upload TTF files to <span class="font-semibold">storage/app/fonts</span>.
                </p>
                <p v-if="errors.font_family" class="mt-1 text-xs text-red-500">{{ errors.font_family }}</p>
            </div>

            <!-- Section Divider for Individual Placeholder Styles -->
            <div v-if="selectedPlaceholder" class="pt-3 border-t border-gray-200">
                <div class="flex items-center justify-between mb-3">
                    <div class="flex items-center gap-2">
                        <div class="h-1 w-1 rounded-full bg-[#2f837d]"></div>
                        <span class="text-xs font-semibold text-gray-700 uppercase tracking-wide">Individual Placeholder Style</span>
                    </div>
                    <button
                        v-if="hasAnyCustomStyle"
                        type="button"
                        @click="clearCustomStyles"
                        class="text-[10px] text-red-600 hover:text-red-700 font-medium flex items-center gap-1 px-2 py-1 rounded hover:bg-red-50 transition-colors"
                        title="Reset to global styles"
                    >
                        <X class="h-3 w-3" />
                        Reset
                    </button>
                </div>
            </div>

            <!-- Font Size -->
            <div>
                <label class="block text-sm font-medium text-gray-700">
                    Font size
                    <span v-if="selectedPlaceholder" class="text-xs font-medium text-[#2f837d]">(override global: {{ fontSize || '28' }}px)</span>
                </label>
                <input
                    :value="currentFontSize"
                    @input="updateStyle('fontSize', Number($event.target.value))"
                    type="number"
                    min="8"
                    max="200"
                    class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-transparent focus:ring-2 focus:ring-[#2f837d] transition-shadow"
                    :placeholder="selectedPlaceholder ? `${fontSize || '24'} (global)` : '24'"
                />
                <p v-if="errors.font_size" class="mt-1 text-xs text-red-500">{{ errors.font_size }}</p>
            </div>

            <!-- Font Style (only for placeholder) -->
            <div v-if="selectedPlaceholder">
                <label class="block text-sm font-medium text-gray-700">Font style</label>
                <div class="mt-1 flex gap-2">
                    <button
                        v-for="style in fontStyleOptions"
                        :key="style.value"
                        type="button"
                        @click="updateStyle('fontStyle', style.value)"
                        class="flex-1 px-3 py-2 text-sm font-medium rounded-lg border transition-all"
                        :class="[
                            currentFontStyle === style.value
                                ? 'bg-[#2f837d] text-white border-[#2f837d] shadow-md'
                                : 'bg-white text-gray-700 border-gray-300 hover:border-[#2f837d] hover:bg-gray-50'
                        ]"
                    >
                        <span :class="{ 'font-bold': style.value === 'bold', 'italic': style.value === 'italic' }">
                            {{ style.label }}
                        </span>
                    </button>
                </div>
            </div>

            <!-- Text Color -->
            <div>
                <label class="block text-sm font-medium text-gray-700">
                    Text color
                    <span v-if="selectedPlaceholder" class="text-xs font-medium text-[#2f837d]">(override global: {{ textColor || '#1f2937' }})</span>
                </label>
                <div class="mt-1 flex items-center gap-2">
                    <div
                        class="h-9 w-9 rounded-lg border border-gray-300 shadow-sm flex-shrink-0 cursor-pointer transition-transform hover:scale-105"
                        :style="{ backgroundColor: displayColor }"
                        @click="openColorPicker"
                    />
                    <input
                        ref="colorPicker"
                        :value="currentColor"
                        @input="updateStyle('color', $event.target.value)"
                        type="color"
                        class="sr-only"
                    />
                    <input
                        :value="currentColor"
                        @input="updateStyle('color', $event.target.value)"
                        type="text"
                        class="flex-1 rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-transparent focus:ring-2 focus:ring-[#2f837d] transition-shadow"
                        :placeholder="selectedPlaceholder ? `${textColor || '#2f837d'} (global)` : '#2f837d'"
                    />
                </div>
                <div class="mt-2 flex flex-wrap gap-1.5">
                    <button
                        v-for="color in presetColors"
                        :key="color"
                        type="button"
                        class="h-6 w-6 rounded-md border border-gray-200 transition-transform hover:scale-110 focus:ring-2 focus:ring-offset-1 focus:ring-[#2f837d]"
                        :style="{ backgroundColor: color }"
                        :class="{ 'ring-2 ring-[#2f837d] ring-offset-1': currentColor === color }"
                        @click="updateStyle('color', color)"
                    />
                </div>
                <p v-if="errors.text_color" class="mt-1 text-xs text-red-500">{{ errors.text_color }}</p>
            </div>

            <!-- QR Size -->
            <div>
                <label class="block text-sm font-medium text-gray-700">QR size (px)</label>
                <input
                    :value="qrSize"
                    @input="$emit('update:qrSize', Number($event.target.value))"
                    type="number"
                    min="80"
                    max="600"
                    class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-transparent focus:ring-2 focus:ring-[#2f837d] transition-shadow"
                />
            </div>
        </div>
    </div>
</template>
