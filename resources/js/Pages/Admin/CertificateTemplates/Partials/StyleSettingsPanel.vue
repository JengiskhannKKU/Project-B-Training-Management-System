<script setup>
import { ref, watch, computed } from "vue";
import { Palette } from "lucide-vue-next";

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

const displayColor = computed(() => props.textColor || "#1f2937");

const openColorPicker = () => {
    colorPicker.value?.click();
};
</script>

<template>
    <div class="rounded-2xl border border-gray-200 bg-white shadow-sm space-y-4 overflow-hidden transition-shadow hover:shadow-md">
        <!-- Header -->
        <div class="flex items-center gap-3 px-6 pt-6">
            <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-gradient-to-br from-purple-100 to-purple-50">
                <Palette class="h-5 w-5 text-purple-600" />
            </div>
            <div>
                <h2 class="text-lg font-semibold text-gray-900">Style Settings</h2>
                <p class="text-sm text-gray-500">Global fonts and colors for text.</p>
            </div>
        </div>

        <div class="px-6 pb-6 space-y-4">
            <!-- Font Family -->
            <div>
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

            <!-- Font Size -->
            <div>
                <label class="block text-sm font-medium text-gray-700">Font size</label>
                <input
                    :value="fontSize"
                    @input="$emit('update:fontSize', $event.target.value)"
                    type="number"
                    min="8"
                    max="200"
                    class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-transparent focus:ring-2 focus:ring-[#2f837d] transition-shadow"
                    placeholder="24"
                />
                <p v-if="errors.font_size" class="mt-1 text-xs text-red-500">{{ errors.font_size }}</p>
            </div>

            <!-- Text Color -->
            <div>
                <label class="block text-sm font-medium text-gray-700">Text color</label>
                <div class="mt-1 flex items-center gap-2">
                    <div
                        class="h-9 w-9 rounded-lg border border-gray-300 shadow-sm flex-shrink-0 cursor-pointer transition-transform hover:scale-105"
                        :style="{ backgroundColor: displayColor }"
                        @click="openColorPicker"
                    />
                    <input
                        ref="colorPicker"
                        :value="textColor"
                        @input="$emit('update:textColor', $event.target.value)"
                        type="color"
                        class="sr-only"
                    />
                    <input
                        :value="textColor"
                        @input="$emit('update:textColor', $event.target.value)"
                        type="text"
                        class="flex-1 rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-transparent focus:ring-2 focus:ring-[#2f837d] transition-shadow"
                        placeholder="#2f837d"
                    />
                </div>
                <div class="mt-2 flex flex-wrap gap-1.5">
                    <button
                        v-for="color in presetColors"
                        :key="color"
                        type="button"
                        class="h-6 w-6 rounded-md border border-gray-200 transition-transform hover:scale-110 focus:ring-2 focus:ring-offset-1 focus:ring-[#2f837d]"
                        :style="{ backgroundColor: color }"
                        :class="{ 'ring-2 ring-[#2f837d] ring-offset-1': textColor === color }"
                        @click="$emit('update:textColor', color)"
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
