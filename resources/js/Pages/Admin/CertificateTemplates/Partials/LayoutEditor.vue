<script setup>
import { ref, computed, watch, onMounted, onBeforeUnmount, nextTick } from "vue";
import { Move, Scan, Eye, RefreshCw, X, GripVertical, Type } from "lucide-vue-next";
import { useLayoutEditor } from "@/composables/useLayoutEditor";

const props = defineProps({
    backgroundUrl: {
        type: String,
        default: "",
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
    previewData: {
        type: Object,
        default: () => ({
            name: "Alex Morgan",
            program: "Advanced AI Bootcamp",
            session: "Cohort 3",
            issued_at: new Date().toLocaleDateString(),
            certificate_code: "CERT-8F4K2M",
        }),
    },
    initialLayoutConfig: {
        type: Object,
        default: null,
    },
    initialCanvasSize: {
        type: Object,
        default: () => ({ width: 1600, height: 1200 }),
    },
    qrSizeValue: {
        type: Number,
        default: 160,
    },
});

const emit = defineEmits([
    "update:layoutConfig",
    "update:canvasSize",
    "update:qrSize",
    "update:selectedPlaceholder",
    "preview",
    "reset",
]);

const {
    canvasRef,
    canvasDisplay,
    canvasNatural,
    qrSize,
    placeholderDefinitions,
    activePlaceholders,
    layoutPositions,
    placeholderStyles,
    selectedPlaceholder,
    draggingKey,
    isDraggingFromToolbar,
    draggedPlaceholder,
    setCanvasSize,
    updateDisplaySize,
    editorTransform,
    isPlaceholderActive,
    getPlaceholderDefinition,
    addPlaceholder,
    removePlaceholder,
    selectPlaceholder,
    getPlaceholderStyles,
    updatePlaceholderStyle,
    handleDragStart,
    handleDragMove,
    handleDragEnd,
    handleToolbarDragStart,
    handleCanvasDrop,
    buildLayoutConfig,
    applyLayoutConfig,
    resetLayout,
    updateQrSize,
    setupResizeObserver,
    cleanupResizeObserver,
    setupDragListeners,
    cleanupDragListeners,
} = useLayoutEditor({ initialCanvasSize: props.initialCanvasSize });

// Inactive placeholders (available in toolbar)
const inactivePlaceholders = computed(() =>
    placeholderDefinitions.filter((p) => !isPlaceholderActive(p.key))
);

// Active placeholder objects
const activeItems = computed(() =>
    activePlaceholders.value.map((key) => getPlaceholderDefinition(key)).filter(Boolean)
);

// Check if placeholder has custom styling
const hasCustomStyling = (key) => {
    const styles = getPlaceholderStyles(key);
    return !!(styles.color || styles.fontSize || (styles.fontStyle && styles.fontStyle !== 'normal'));
};

// Placeholder style calculation
const getStyle = (placeholder) => {
    const position = layoutPositions[placeholder.key];
    if (!position) return {};

    const { toDisplayX, toDisplayY } = editorTransform.value;

    const style = {
        left: `${toDisplayX(position.x)}px`,
        top: `${toDisplayY(position.y)}px`,
    };

    if (placeholder.isQr) {
        const size = position.size || qrSize.value || 160;
        style.width = `${toDisplayX(size)}px`;
        style.height = `${toDisplayY(size)}px`;
    } else {
        const baseSize = Number(props.fontSize) || 28;
        const height = Math.max(56, Math.round(baseSize * 2.4));
        const width = Math.max(320, Math.round(baseSize * 12));
        style.width = `${toDisplayX(width)}px`;
        style.height = `${toDisplayY(height)}px`;
        style.minWidth = `${toDisplayX(220)}px`;
        style.minHeight = `${toDisplayY(40)}px`;
    }

    return style;
};

// Editor text style - per placeholder
const getEditorTextStyle = (placeholderKey) => {
    const styles = getPlaceholderStyles(placeholderKey);
    const baseSize = Number(styles.fontSize || props.fontSize) || 28;
    const { getFontScale } = editorTransform.value;
    const fontSize = Math.max(12, baseSize * getFontScale());

    const style = {
        fontSize: `${fontSize}px`,
        color: styles.color || props.textColor || '#1f2937',
        fontFamily: styles.fontFamily || props.fontFamily || 'Prompt, sans-serif',
        whiteSpace: "nowrap",
        lineHeight: 1.1,
    };

    // Apply font style
    if (styles.fontStyle === 'bold') {
        style.fontWeight = 'bold';
    } else if (styles.fontStyle === 'italic') {
        style.fontStyle = 'italic';
    }

    return style;
};

// Handle drop on canvas
const onCanvasDrop = (event) => {
    handleCanvasDrop(event);
    emitLayoutUpdate();
};

// Handle drag end with removal check
const onCanvasDragEnd = (event) => {
    // Check if placeholder was dragged outside canvas
    if (draggingKey.value && canvasRef.value) {
        const rect = canvasRef.value.getBoundingClientRect();
        const x = event.clientX;
        const y = event.clientY;

        // If drop is outside canvas, remove the placeholder
        if (x < rect.left || x > rect.right || y < rect.top || y > rect.bottom) {
            removePlaceholder(draggingKey.value.key);
        }
    }

    handleDragEnd();
    emitLayoutUpdate();
};

// Position drag handler
const onPlaceholderDragStart = (key, event) => {
    handleDragStart(key, event);
};

// Handle placeholder selection
const onPlaceholderClick = (key, event) => {
    // Don't select if we're dragging
    if (draggingKey.value) return;

    selectPlaceholder(key);
    emit("update:selectedPlaceholder", key);
};

// Emit layout updates
const emitLayoutUpdate = () => {
    emit("update:layoutConfig", buildLayoutConfig());
    emit("update:canvasSize", { ...canvasNatural });
};

// Handle reset
const onReset = () => {
    resetLayout();
    emitLayoutUpdate();
    emit("reset");
};

// Handle preview
const onPreview = () => {
    emit("preview");
};

// Sync qrSize with prop
watch(() => props.qrSizeValue, (val) => {
    updateQrSize(val);
    emitLayoutUpdate();
});

// Watch for layout position changes
watch(layoutPositions, () => {
    emitLayoutUpdate();
}, { deep: true });

// Watch for placeholder styles changes
watch(placeholderStyles, () => {
    emitLayoutUpdate();
}, { deep: true });

// Watch for selected placeholder changes
watch(selectedPlaceholder, (newVal) => {
    emit("update:selectedPlaceholder", newVal);
});

// Track if initial config has been applied (to prevent infinite loop)
const initialConfigApplied = ref(false);

// Apply initial config (only once to prevent recursive updates)
watch(
    () => props.initialLayoutConfig,
    (config) => {
        // Only apply config once during initial load
        if (config && !initialConfigApplied.value) {
            applyLayoutConfig(config);
            initialConfigApplied.value = true;
            nextTick(updateDisplaySize);
        }
    },
    { immediate: true }
);

// Sync canvas size from background image
const syncCanvasFromBackground = (width, height) => {
    setCanvasSize(width, height, true);
    emit("update:canvasSize", { ...canvasNatural });
    nextTick(updateDisplaySize);
};

// Expose for parent
defineExpose({
    setCanvasSize: syncCanvasFromBackground,
    applyLayoutConfig,
    buildLayoutConfig,
    resetLayout: onReset,
    canvasNatural,
    layoutPositions,
    activePlaceholders,
    placeholderStyles,
    updatePlaceholderStyle,
    updateDisplaySize,
});

onMounted(() => {
    nextTick(() => {
        updateDisplaySize();
        setupResizeObserver();
        setupDragListeners();
    });
});

onBeforeUnmount(() => {
    cleanupResizeObserver();
    cleanupDragListeners();
});
</script>

<template>
    <div class="rounded-2xl border border-gray-200 bg-white shadow-sm space-y-4 overflow-hidden transition-shadow hover:shadow-md">
        <!-- Header -->
        <div class="flex flex-wrap items-center justify-between gap-3 px-6 pt-6">
            <div class="flex items-center gap-3">
                <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-gradient-to-br from-blue-100 to-blue-50">
                    <Move class="h-5 w-5 text-blue-600" />
                </div>
                <div>
                    <h2 class="text-lg font-semibold text-gray-900">Layout Editor</h2>
                    <p class="text-sm text-gray-500">
                        Drag placeholders from the toolbar onto the canvas.
                    </p>
                </div>
            </div>
            <div class="flex items-center gap-2">
                <button
                    type="button"
                    @click="onPreview"
                    class="inline-flex items-center gap-2 rounded-lg border border-gray-200 bg-white px-3 py-1.5 text-xs font-semibold text-gray-600 shadow-sm transition-all hover:bg-gray-50 hover:shadow"
                >
                    <Eye class="h-3.5 w-3.5" />
                    Preview
                </button>
                <button
                    type="button"
                    @click="onReset"
                    class="inline-flex items-center gap-2 rounded-lg border border-gray-200 bg-white px-3 py-1.5 text-xs font-semibold text-gray-600 shadow-sm transition-all hover:bg-gray-50 hover:shadow"
                >
                    <RefreshCw class="h-3.5 w-3.5" />
                    Reset layout
                </button>
            </div>
        </div>

        <div class="px-6 pb-6 space-y-4">
            <!-- Placeholder Toolbar -->
            <div class="flex flex-wrap gap-2 p-3 bg-gray-50 rounded-xl border border-dashed border-gray-200">
                <div class="w-full space-y-1 mb-1">
                    <div class="text-xs font-medium text-gray-700">
                        {{ inactivePlaceholders.length > 0 ? '📦 Drag placeholders to canvas' : '✓ All placeholders added' }}
                    </div>
                    <div class="text-[10px] text-gray-500">
                        {{ activePlaceholders.length > 0 ? '💡 Click a placeholder on canvas to customize its style' : '' }}
                    </div>
                </div>
                <div
                    v-for="placeholder in inactivePlaceholders"
                    :key="placeholder.key"
                    draggable="true"
                    @dragstart="handleToolbarDragStart(placeholder.key, $event)"
                    @dragend="handleDragEnd"
                    class="inline-flex items-center gap-1.5 rounded-lg px-3 py-2 text-xs font-semibold cursor-grab active:cursor-grabbing transition-all hover:scale-105 hover:shadow-md border"
                    :class="placeholder.color"
                >
                    <GripVertical class="h-3 w-3 opacity-50" />
                    {{ placeholder.label }}
                </div>
            </div>

            <!-- Canvas -->
            <div
                ref="canvasRef"
                class="relative w-full overflow-hidden rounded-2xl border-2 border-dashed transition-colors"
                :class="[
                    isDraggingFromToolbar
                        ? 'border-[#2f837d] bg-[#2f837d]/5'
                        : 'border-gray-200 bg-gray-50'
                ]"
                :style="{ aspectRatio: `${canvasNatural.width}/${canvasNatural.height}` }"
                @dragover.prevent
                @drop="onCanvasDrop"
            >
                <!-- Background Image -->
                <img
                    v-if="backgroundUrl"
                    :src="backgroundUrl"
                    alt="Certificate background"
                    class="absolute inset-0 h-full w-full object-cover"
                />
                <div v-else class="absolute inset-0 bg-[linear-gradient(135deg,#f5f7fa,transparent)]"></div>

                <!-- Drop Zone Indicator -->
                <div
                    v-if="isDraggingFromToolbar"
                    class="absolute inset-4 border-2 border-dashed border-[#2f837d] rounded-xl flex items-center justify-center bg-[#2f837d]/10 pointer-events-none"
                >
                    <span class="text-sm font-medium text-[#2f837d]">Drop here to add</span>
                </div>

                <!-- Active Placeholders -->
                <div class="absolute inset-0">
                    <div
                        v-for="placeholder in activeItems"
                        :key="placeholder.key"
                        class="absolute cursor-move group transition-shadow"
                        :class="[
                            draggingKey?.key === placeholder.key
                                ? 'z-50 shadow-2xl'
                                : selectedPlaceholder === placeholder.key
                                ? 'z-40 shadow-xl'
                                : 'z-10 hover:z-40 hover:shadow-xl'
                        ]"
                        :style="getStyle(placeholder)"
                        @mousedown="onPlaceholderDragStart(placeholder.key, $event)"
                        @mouseup="onCanvasDragEnd"
                        @click="onPlaceholderClick(placeholder.key, $event)"
                    >
                        <!-- Text Placeholder Container -->
                        <div
                            v-if="!placeholder.isQr"
                            class="relative w-full h-full rounded-xl transition-all overflow-hidden cursor-pointer"
                            :class="[
                                draggingKey?.key === placeholder.key
                                    ? 'border-[3px] border-[#2f837d] bg-[#2f837d]/15 shadow-lg'
                                    : selectedPlaceholder === placeholder.key
                                    ? 'border-[3px] border-[#2f837d] bg-[#2f837d]/10 shadow-lg ring-4 ring-[#2f837d]/30'
                                    : 'border-2 border-dashed border-[#2f837d]/60 bg-white/80 hover:border-[#2f837d] hover:bg-[#2f837d]/10 hover:border-solid'
                            ]"
                        >
                            <!-- Preview Text - Centered -->
                            <div
                                class="absolute inset-0 flex items-center justify-center px-3 overflow-hidden"
                            >
                                <span
                                    class="truncate text-center"
                                    :style="getEditorTextStyle(placeholder.key)"
                                >
                                    {{ previewData[placeholder.key] }}
                                </span>
                            </div>

                            <!-- Placeholder Label Badge - Bottom Left -->
                            <div
                                class="absolute left-1.5 bottom-1.5 z-10 inline-flex items-center gap-1 rounded-md px-1.5 py-0.5 text-[9px] font-semibold transition-all"
                                :class="[
                                    draggingKey?.key === placeholder.key
                                        ? 'bg-[#2f837d] text-white'
                                        : selectedPlaceholder === placeholder.key
                                        ? 'bg-[#2f837d] text-white ring-2 ring-white/50'
                                        : 'bg-gray-800/70 text-white/90 group-hover:bg-[#2f837d]'
                                ]"
                            >
                                <Move class="h-2.5 w-2.5" />
                                <span>{{ placeholder.label }}</span>
                                <span
                                    v-if="hasCustomStyling(placeholder.key)"
                                    class="ml-0.5 text-[8px]"
                                    title="Has custom styling"
                                >
                                    ✨
                                </span>
                            </div>

                            <!-- Click to Edit Badge - Top Center (shows when not selected) -->
                            <div
                                v-if="selectedPlaceholder !== placeholder.key"
                                class="absolute top-1.5 left-1/2 -translate-x-1/2 z-10 opacity-0 group-hover:opacity-100 transition-opacity inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-[8px] font-semibold bg-[#2f837d] text-white shadow-md"
                            >
                                <Type class="h-2 w-2" />
                                <span>Click to edit style</span>
                            </div>

                            <!-- Selected Badge - Top Center (shows when selected) -->
                            <div
                                v-if="selectedPlaceholder === placeholder.key"
                                class="absolute top-1.5 left-1/2 -translate-x-1/2 z-10 inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-[8px] font-semibold bg-[#2f837d] text-white shadow-lg animate-pulse"
                            >
                                <Type class="h-2 w-2" />
                                <span>Editing style →</span>
                            </div>
                        </div>

                        <!-- QR Placeholder Container -->
                        <div
                            v-if="placeholder.isQr"
                            class="relative w-full h-full rounded-xl transition-all overflow-hidden cursor-pointer"
                            :class="[
                                draggingKey?.key === placeholder.key
                                    ? 'border-[3px] border-teal-500 bg-teal-100/80 shadow-lg'
                                    : selectedPlaceholder === placeholder.key
                                    ? 'border-[3px] border-teal-500 bg-teal-100/70 shadow-lg ring-4 ring-teal-500/30'
                                    : 'border-2 border-dashed border-teal-400/60 bg-teal-50/70 hover:border-teal-500 hover:bg-teal-100/70 hover:border-solid'
                            ]"
                        >
                            <!-- QR Icon -->
                            <div class="absolute inset-0 flex flex-col items-center justify-center gap-1">
                                <Scan class="h-6 w-6 text-teal-600" />
                                <span class="text-[10px] font-semibold text-teal-700">QR Code</span>
                            </div>

                            <!-- QR Label Badge - Bottom Left -->
                            <div
                                class="absolute left-1.5 bottom-1.5 z-10 inline-flex items-center gap-1 rounded-md px-1.5 py-0.5 text-[9px] font-semibold transition-all"
                                :class="[
                                    draggingKey?.key === placeholder.key
                                        ? 'bg-teal-600 text-white'
                                        : 'bg-teal-700/70 text-white/90 group-hover:bg-teal-600'
                                ]"
                            >
                                <Move class="h-2.5 w-2.5" />
                                <span>{{ placeholder.label }}</span>
                            </div>
                        </div>

                        <!-- Remove Button - Top Right Corner -->
                        <button
                            type="button"
                            @click.stop="removePlaceholder(placeholder.key); emitLayoutUpdate();"
                            class="absolute -right-2 -top-2 z-30 opacity-0 group-hover:opacity-100 flex h-5 w-5 items-center justify-center rounded-full bg-red-500 text-white shadow-lg hover:bg-red-600 hover:scale-110 transition-all"
                        >
                            <X class="h-3 w-3" />
                        </button>
                    </div>
                </div>
            </div>

            <!-- Canvas Info -->
            <div class="flex flex-wrap items-center justify-between gap-3 text-xs text-gray-500">
                <div>
                    Canvas: {{ canvasNatural.width }} × {{ canvasNatural.height }} px
                </div>
                <div>
                    Preview: {{ Math.round(canvasDisplay.width) }} × {{ Math.round(canvasDisplay.height) }} px
                </div>
            </div>
        </div>
    </div>
</template>
