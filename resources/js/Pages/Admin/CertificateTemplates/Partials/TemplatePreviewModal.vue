<script setup>
import { ref, reactive, computed, watch, nextTick, onBeforeUnmount } from "vue";
import { X, Scan } from "lucide-vue-next";
import Modal from "@/Components/Modal.vue";

const props = defineProps({
    show: {
        type: Boolean,
        default: false,
    },
    backgroundUrl: {
        type: String,
        default: "",
    },
    canvasNatural: {
        type: Object,
        default: () => ({ width: 1600, height: 1200 }),
    },
    layoutPositions: {
        type: Object,
        default: () => ({}),
    },
    activePlaceholders: {
        type: Array,
        default: () => [],
    },
    fontSize: {
        type: [Number, String],
        default: 28,
    },
    textColor: {
        type: String,
        default: "#1f2937",
    },
    fontFamily: {
        type: String,
        default: "Prompt, sans-serif",
    },
    selectedProgramName: {
        type: String,
        default: "",
    },
    selectedSessionTitle: {
        type: String,
        default: "",
    },
});

const emit = defineEmits(["close"]);

const previewCanvasRef = ref(null);
const previewDisplay = reactive({ width: 0, height: 0 });
let resizeObserver = null;

// Sample data for preview
const previewSample = reactive({
    name: "Alex Morgan",
    program: "",
    session: "",
    issued_at: new Date().toLocaleDateString(),
    certificate_code: "CERT-8F4K2M",
});

// Computed preview data with fallbacks
const previewData = computed(() => ({
    name: previewSample.name || "Alex Morgan",
    program: previewSample.program || props.selectedProgramName || "Advanced AI Bootcamp",
    session: previewSample.session || props.selectedSessionTitle || "Cohort 3",
    issued_at: previewSample.issued_at || new Date().toLocaleDateString(),
    certificate_code: previewSample.certificate_code || "CERT-8F4K2M",
}));

// Font scale for preview
const fontScale = computed(() => {
    if (!previewDisplay.width || !props.canvasNatural.width) return 1;
    return previewDisplay.width / props.canvasNatural.width;
});

// Coordinate transforms for preview
const toPreviewX = (value) => {
    if (!previewDisplay.width || !props.canvasNatural.width) return value;
    return (value / props.canvasNatural.width) * previewDisplay.width;
};

const toPreviewY = (value) => {
    if (!previewDisplay.height || !props.canvasNatural.height) return value;
    return (value / props.canvasNatural.height) * previewDisplay.height;
};

// Text styling
const getTextStyle = (key) => {
    const position = props.layoutPositions[key];
    if (!position) return {};

    const baseSize = Number(props.fontSize) || 28;
    const scaledFontSize = Math.max(12, baseSize * fontScale.value);

    return {
        left: `${toPreviewX(position.x)}px`,
        top: `${toPreviewY(position.y)}px`,
        fontSize: `${scaledFontSize}px`,
        color: props.textColor,
        fontFamily: props.fontFamily.includes("Prompt")
            ? '"Prompt", sans-serif'
            : props.fontFamily || '"Prompt", sans-serif',
        whiteSpace: "nowrap",
        lineHeight: 1.1,
    };
};

// QR styling
const qrStyle = computed(() => {
    const position = props.layoutPositions.qr;
    if (!position) return {};

    const size = position.size || position.width || 160;
    return {
        left: `${toPreviewX(position.x)}px`,
        top: `${toPreviewY(position.y)}px`,
        width: `${toPreviewX(size)}px`,
        height: `${toPreviewY(size)}px`,
    };
});

// Check if placeholder is active
const isActive = (key) => props.activePlaceholders.includes(key);

// Update display size
const updatePreviewDisplaySize = () => {
    if (!previewCanvasRef.value) return;
    const rect = previewCanvasRef.value.getBoundingClientRect();
    previewDisplay.width = rect.width;
    previewDisplay.height = rect.height;
};

// Watch show to setup/cleanup observer
watch(() => props.show, async (value) => {
    if (!value) {
        if (resizeObserver) {
            resizeObserver.disconnect();
            resizeObserver = null;
        }
        return;
    }

    // Sync sample data with selections
    if (!previewSample.program && props.selectedProgramName) {
        previewSample.program = props.selectedProgramName;
    }
    if (!previewSample.session && props.selectedSessionTitle) {
        previewSample.session = props.selectedSessionTitle;
    }

    await nextTick();
    updatePreviewDisplaySize();

    if (window.ResizeObserver) {
        resizeObserver = new ResizeObserver(() => updatePreviewDisplaySize());
        if (previewCanvasRef.value) {
            resizeObserver.observe(previewCanvasRef.value);
        }
    }
});

onBeforeUnmount(() => {
    if (resizeObserver) {
        resizeObserver.disconnect();
    }
});
</script>

<template>
    <Modal :show="show" max-width="2xl" @close="$emit('close')">
        <div class="p-6 space-y-4">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="text-lg font-semibold text-gray-900">Template Preview</h3>
                    <p class="text-sm text-gray-600">Sample data rendered with the current layout.</p>
                </div>
                <button
                    type="button"
                    @click="$emit('close')"
                    class="inline-flex h-9 w-9 items-center justify-center rounded-full border border-gray-200 text-gray-500 hover:bg-gray-50"
                >
                    <X class="h-4 w-4" />
                </button>
            </div>

            <!-- Sample Data Inputs -->
            <div class="grid gap-3 md:grid-cols-2">
                <div>
                    <label class="text-xs font-semibold text-gray-500">Student name</label>
                    <input
                        v-model="previewSample.name"
                        type="text"
                        class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-transparent focus:ring-2 focus:ring-[#2f837d]"
                    />
                </div>
                <div>
                    <label class="text-xs font-semibold text-gray-500">Program name</label>
                    <input
                        v-model="previewSample.program"
                        type="text"
                        class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-transparent focus:ring-2 focus:ring-[#2f837d]"
                        :placeholder="selectedProgramName || 'Advanced AI Bootcamp'"
                    />
                </div>
                <div>
                    <label class="text-xs font-semibold text-gray-500">Session title</label>
                    <input
                        v-model="previewSample.session"
                        type="text"
                        class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-transparent focus:ring-2 focus:ring-[#2f837d]"
                        :placeholder="selectedSessionTitle || 'Cohort 3'"
                    />
                </div>
                <div>
                    <label class="text-xs font-semibold text-gray-500">Issued date</label>
                    <input
                        v-model="previewSample.issued_at"
                        type="text"
                        class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-transparent focus:ring-2 focus:ring-[#2f837d]"
                        placeholder="2024-04-20"
                    />
                </div>
                <div>
                    <label class="text-xs font-semibold text-gray-500">Certificate code</label>
                    <input
                        v-model="previewSample.certificate_code"
                        type="text"
                        class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-transparent focus:ring-2 focus:ring-[#2f837d]"
                    />
                </div>
            </div>

            <!-- Preview Canvas -->
            <div
                ref="previewCanvasRef"
                class="relative w-full overflow-hidden rounded-2xl border border-dashed border-gray-200 bg-gray-50"
                :style="{ aspectRatio: `${canvasNatural.width}/${canvasNatural.height}` }"
            >
                <!-- Background Image -->
                <img
                    v-if="backgroundUrl"
                    :src="backgroundUrl"
                    alt="Certificate background"
                    class="absolute inset-0 h-full w-full object-cover"
                />
                <div v-else class="absolute inset-0 bg-[linear-gradient(135deg,#f5f7fa,transparent)]"></div>

                <!-- Positioned Text (only active placeholders) -->
                <div class="absolute inset-0">
                    <template v-for="(value, key) in previewData" :key="key">
                        <div
                            v-if="isActive(key) && layoutPositions[key]"
                            class="absolute text-xs font-semibold"
                            :style="getTextStyle(key)"
                        >
                            {{ value }}
                        </div>
                    </template>

                    <!-- QR Placeholder -->
                    <div
                        v-if="isActive('qr') && layoutPositions.qr"
                        class="absolute flex items-center justify-center rounded-xl border-2 border-dashed border-teal-300 bg-teal-50/70 text-[10px] font-semibold text-teal-600"
                        :style="qrStyle"
                    >
                        <Scan class="mr-1 h-3 w-3" />
                        QR
                    </div>
                </div>
            </div>

            <!-- Canvas Info -->
            <div class="flex flex-wrap items-center justify-between gap-3 text-xs text-gray-500">
                <div>
                    Canvas: {{ canvasNatural.width }} × {{ canvasNatural.height }} px
                </div>
                <div>
                    Preview: {{ Math.round(previewDisplay.width) }} × {{ Math.round(previewDisplay.height) }} px
                </div>
            </div>
        </div>
    </Modal>
</template>
