import { ref, reactive, computed, nextTick, onMounted, onBeforeUnmount } from "vue";

/**
 * Composable for managing certificate template layout editor
 * Handles canvas sizing, placeholder positioning, drag-and-drop, and coordinate transformations
 */
export function useLayoutEditor(options = {}) {
    const { initialCanvasSize = { width: 1600, height: 1200 } } = options;

    // Canvas dimensions
    const canvasRef = ref(null);
    const canvasDisplay = reactive({ width: 0, height: 0 });
    const canvasNatural = reactive({
        width: initialCanvasSize.width,
        height: initialCanvasSize.height,
    });

    // Available placeholder definitions
    const placeholderDefinitions = [
        { key: "name", label: "{{student_name}}", color: "bg-indigo-100 text-indigo-700 border-indigo-300" },
        { key: "program", label: "{{program_name}}", color: "bg-emerald-100 text-emerald-700 border-emerald-300" },
        { key: "session", label: "{{session_title}}", color: "bg-blue-100 text-blue-700 border-blue-300" },
        { key: "issued_at", label: "{{issued_date}}", color: "bg-amber-100 text-amber-700 border-amber-300" },
        { key: "certificate_code", label: "{{certificate_code}}", color: "bg-gray-100 text-gray-700 border-gray-300" },
        { key: "qr", label: "{{qr_code}}", color: "bg-teal-100 text-teal-700 border-teal-300", isQr: true },
    ];

    // Active placeholders (those added to the canvas)
    const activePlaceholders = ref([]);

    // Layout positions for active placeholders (natural coordinates)
    const layoutPositions = reactive({});

    // QR size
    const qrSize = ref(160);

    // Drag state
    const draggingKey = ref(null);
    const isDraggingFromToolbar = ref(false);
    const draggedPlaceholder = ref(null);

    // Resize observer
    let resizeObserver = null;

    // =====================
    // Canvas Size Management
    // =====================
    const setCanvasSize = (width, height, scalePositions = false) => {
        if (!width || !height) return;

        const prevWidth = canvasNatural.width || 1;
        const prevHeight = canvasNatural.height || 1;

        canvasNatural.width = width;
        canvasNatural.height = height;

        if (!scalePositions) return;

        const scaleX = width / prevWidth;
        const scaleY = height / prevHeight;

        Object.values(layoutPositions).forEach((position) => {
            position.x = Math.round((position.x || 0) * scaleX);
            position.y = Math.round((position.y || 0) * scaleY);
            if (position.width) position.width = Math.round(position.width * scaleX);
            if (position.height) position.height = Math.round(position.height * scaleY);
            if (position.size) position.size = Math.round(position.size * scaleX);
        });
    };

    const updateDisplaySize = () => {
        if (!canvasRef.value) return;
        const rect = canvasRef.value.getBoundingClientRect();
        canvasDisplay.width = rect.width;
        canvasDisplay.height = rect.height;
    };

    // =====================
    // Coordinate Transformations (unified for editor & preview)
    // =====================
    const createCoordinateTransform = (displayWidth, displayHeight) => {
        const toDisplayX = (value) => {
            if (!displayWidth || !canvasNatural.width) return value;
            return (value / canvasNatural.width) * displayWidth;
        };

        const toDisplayY = (value) => {
            if (!displayHeight || !canvasNatural.height) return value;
            return (value / canvasNatural.height) * displayHeight;
        };

        const toNaturalX = (value) => {
            if (!displayWidth || !canvasNatural.width) return value;
            return (value / displayWidth) * canvasNatural.width;
        };

        const toNaturalY = (value) => {
            if (!displayHeight || !canvasNatural.height) return value;
            return (value / displayHeight) * canvasNatural.height;
        };

        const getFontScale = () => {
            if (!displayWidth || !canvasNatural.width) return 1;
            return displayWidth / canvasNatural.width;
        };

        return { toDisplayX, toDisplayY, toNaturalX, toNaturalY, getFontScale };
    };

    // Editor coordinate transforms (bound to canvasDisplay)
    const editorTransform = computed(() =>
        createCoordinateTransform(canvasDisplay.width, canvasDisplay.height)
    );

    // =====================
    // Placeholder Management
    // =====================
    const isPlaceholderActive = (key) => activePlaceholders.value.includes(key);

    const getPlaceholderDefinition = (key) =>
        placeholderDefinitions.find((p) => p.key === key);

    const getDefaultPosition = (key) => {
        const isQr = key === "qr";
        if (isQr) {
            return {
                x: Math.round(canvasNatural.width * 0.72),
                y: Math.round(canvasNatural.height * 0.58),
                width: 160,
                height: 160,
                size: 160,
            };
        }
        // Default text position - staggered vertically
        const index = placeholderDefinitions.findIndex((p) => p.key === key);
        return {
            x: Math.round(canvasNatural.width * 0.12),
            y: Math.round(canvasNatural.height * (0.32 + index * 0.1)),
        };
    };

    const addPlaceholder = (key, position = null) => {
        if (isPlaceholderActive(key)) return;

        activePlaceholders.value.push(key);
        layoutPositions[key] = position || getDefaultPosition(key);

        if (key === "qr") {
            qrSize.value = layoutPositions[key].size || 160;
        }
    };

    const removePlaceholder = (key) => {
        const index = activePlaceholders.value.indexOf(key);
        if (index > -1) {
            activePlaceholders.value.splice(index, 1);
            delete layoutPositions[key];
        }
    };

    const updatePlaceholderPosition = (key, x, y) => {
        if (!layoutPositions[key]) return;
        layoutPositions[key].x = Math.round(x);
        layoutPositions[key].y = Math.round(y);
    };

    // =====================
    // Drag Handling - Reposition on Canvas
    // =====================
    const handleDragStart = (key, event) => {
        if (event.button !== 0) return;
        if (!layoutPositions[key] || !canvasRef.value) return;

        const rect = canvasRef.value.getBoundingClientRect();
        const startX = event.clientX - rect.left;
        const startY = event.clientY - rect.top;
        const { toDisplayX, toDisplayY } = editorTransform.value;

        draggingKey.value = {
            key,
            offsetX: startX - toDisplayX(layoutPositions[key].x),
            offsetY: startY - toDisplayY(layoutPositions[key].y),
        };

        isDraggingFromToolbar.value = false;
        document.body.style.userSelect = "none";
    };

    const handleDragMove = (event) => {
        if (!canvasRef.value) return;

        // Handle toolbar drag
        if (isDraggingFromToolbar.value && draggedPlaceholder.value) {
            // Just track position for visual feedback (handled separately)
            return;
        }

        // Handle canvas reposition drag
        if (!draggingKey.value) return;

        const rect = canvasRef.value.getBoundingClientRect();
        const { toNaturalX, toNaturalY } = editorTransform.value;

        const nextX = event.clientX - rect.left - draggingKey.value.offsetX;
        const nextY = event.clientY - rect.top - draggingKey.value.offsetY;

        const clampedX = Math.max(0, Math.min(nextX, canvasDisplay.width));
        const clampedY = Math.max(0, Math.min(nextY, canvasDisplay.height));

        updatePlaceholderPosition(
            draggingKey.value.key,
            toNaturalX(clampedX),
            toNaturalY(clampedY)
        );
    };

    const handleDragEnd = () => {
        if (draggingKey.value) {
            draggingKey.value = null;
            document.body.style.userSelect = "";
        }
        isDraggingFromToolbar.value = false;
        draggedPlaceholder.value = null;
    };

    // =====================
    // Drag from Toolbar
    // =====================
    const handleToolbarDragStart = (key, event) => {
        isDraggingFromToolbar.value = true;
        draggedPlaceholder.value = key;
        document.body.style.userSelect = "none";
    };

    const handleCanvasDrop = (event) => {
        if (!isDraggingFromToolbar.value || !draggedPlaceholder.value || !canvasRef.value) {
            handleDragEnd();
            return;
        }

        const rect = canvasRef.value.getBoundingClientRect();
        const dropX = event.clientX - rect.left;
        const dropY = event.clientY - rect.top;

        // Check if drop is within canvas bounds
        if (dropX >= 0 && dropX <= rect.width && dropY >= 0 && dropY <= rect.height) {
            const { toNaturalX, toNaturalY } = editorTransform.value;
            const def = getPlaceholderDefinition(draggedPlaceholder.value);
            const isQr = def?.isQr;

            const position = {
                x: Math.round(toNaturalX(dropX)),
                y: Math.round(toNaturalY(dropY)),
            };

            if (isQr) {
                position.width = qrSize.value;
                position.height = qrSize.value;
                position.size = qrSize.value;
            }

            addPlaceholder(draggedPlaceholder.value, position);
        }

        handleDragEnd();
    };

    // =====================
    // Layout Config I/O
    // =====================
    const buildLayoutConfig = () => {
        const config = {
            canvas: {
                width: Math.round(canvasNatural.width),
                height: Math.round(canvasNatural.height),
            },
        };

        activePlaceholders.value.forEach((key) => {
            const position = layoutPositions[key];
            if (!position) return;

            const def = getPlaceholderDefinition(key);
            if (def?.isQr) {
                const size = Math.round(position.size || qrSize.value || 160);
                config.qr = {
                    x: Math.round(position.x || 0),
                    y: Math.round(position.y || 0),
                    width: size,
                    height: size,
                    size,
                };
            } else {
                config[key] = {
                    x: Math.round(position.x || 0),
                    y: Math.round(position.y || 0),
                };
            }
        });

        return config;
    };

    const applyLayoutConfig = (config) => {
        // Clear current state
        activePlaceholders.value = [];
        Object.keys(layoutPositions).forEach((key) => delete layoutPositions[key]);

        if (!config) return;

        // Apply canvas size
        if (config.canvas?.width && config.canvas?.height) {
            canvasNatural.width = config.canvas.width;
            canvasNatural.height = config.canvas.height;
        }

        // Apply positions for each key found in config
        placeholderDefinitions.forEach((def) => {
            const key = def.key;
            const savedPos = config[key];
            if (!savedPos) return;

            activePlaceholders.value.push(key);
            layoutPositions[key] = {
                x: Number(savedPos.x ?? 0),
                y: Number(savedPos.y ?? 0),
                width: Number(savedPos.width ?? 160),
                height: Number(savedPos.height ?? 160),
                size: Number(savedPos.size ?? savedPos.width ?? 160),
            };

            if (key === "qr") {
                qrSize.value = layoutPositions[key].size || 160;
            }
        });
    };

    const resetLayout = () => {
        activePlaceholders.value = [];
        Object.keys(layoutPositions).forEach((key) => delete layoutPositions[key]);
    };

    // =====================
    // QR Size Sync
    // =====================
    const updateQrSize = (size) => {
        qrSize.value = size;
        if (layoutPositions.qr) {
            layoutPositions.qr.size = size;
            layoutPositions.qr.width = size;
            layoutPositions.qr.height = size;
        }
    };

    // =====================
    // Placeholder Style Calculation
    // =====================
    const getPlaceholderStyle = (key, displayWidth, displayHeight, fontSize = 28) => {
        const position = layoutPositions[key];
        if (!position) return {};

        const transform = createCoordinateTransform(displayWidth, displayHeight);
        const def = getPlaceholderDefinition(key);

        const style = {
            left: `${transform.toDisplayX(position.x)}px`,
            top: `${transform.toDisplayY(position.y)}px`,
        };

        if (def?.isQr) {
            const size = position.size || qrSize.value || 160;
            style.width = `${transform.toDisplayX(size)}px`;
            style.height = `${transform.toDisplayY(size)}px`;
        } else {
            // Text box sizing based on font
            const baseSize = Number(fontSize) || 28;
            const height = Math.max(56, Math.round(baseSize * 2.4));
            const width = Math.max(320, Math.round(baseSize * 12));
            style.width = `${transform.toDisplayX(width)}px`;
            style.height = `${transform.toDisplayY(height)}px`;
            style.minWidth = `${transform.toDisplayX(220)}px`;
            style.minHeight = `${transform.toDisplayY(40)}px`;
        }

        return style;
    };

    const getTextStyle = (key, displayWidth, fontSize = 28, textColor = "#1f2937", fontFamily = "Prompt, sans-serif") => {
        const position = layoutPositions[key];
        if (!position) return {};

        const transform = createCoordinateTransform(displayWidth, displayWidth * (canvasNatural.height / canvasNatural.width));
        const scaledFontSize = Math.max(12, fontSize * transform.getFontScale());

        return {
            left: `${transform.toDisplayX(position.x)}px`,
            top: `${transform.toDisplayY(position.y)}px`,
            fontSize: `${scaledFontSize}px`,
            color: textColor,
            fontFamily,
            whiteSpace: "nowrap",
            lineHeight: 1.1,
        };
    };

    // =====================
    // Lifecycle
    // =====================
    const setupResizeObserver = () => {
        if (!canvasRef.value) return;

        if (window.ResizeObserver) {
            resizeObserver = new ResizeObserver(() => updateDisplaySize());
            resizeObserver.observe(canvasRef.value);
        } else {
            window.addEventListener("resize", updateDisplaySize);
        }
    };

    const cleanupResizeObserver = () => {
        if (resizeObserver) {
            resizeObserver.disconnect();
            resizeObserver = null;
        } else {
            window.removeEventListener("resize", updateDisplaySize);
        }
    };

    const setupDragListeners = () => {
        window.addEventListener("mousemove", handleDragMove);
        window.addEventListener("mouseup", handleDragEnd);
    };

    const cleanupDragListeners = () => {
        window.removeEventListener("mousemove", handleDragMove);
        window.removeEventListener("mouseup", handleDragEnd);
    };

    return {
        // Refs
        canvasRef,
        canvasDisplay,
        canvasNatural,
        qrSize,

        // Placeholder data
        placeholderDefinitions,
        activePlaceholders,
        layoutPositions,

        // Drag state
        draggingKey,
        isDraggingFromToolbar,
        draggedPlaceholder,

        // Canvas size
        setCanvasSize,
        updateDisplaySize,

        // Coordinate transforms
        createCoordinateTransform,
        editorTransform,

        // Placeholder management
        isPlaceholderActive,
        getPlaceholderDefinition,
        addPlaceholder,
        removePlaceholder,
        updatePlaceholderPosition,

        // Drag handlers
        handleDragStart,
        handleDragMove,
        handleDragEnd,
        handleToolbarDragStart,
        handleCanvasDrop,

        // Layout config
        buildLayoutConfig,
        applyLayoutConfig,
        resetLayout,

        // QR size
        updateQrSize,

        // Styling
        getPlaceholderStyle,
        getTextStyle,

        // Lifecycle helpers
        setupResizeObserver,
        cleanupResizeObserver,
        setupDragListeners,
        cleanupDragListeners,
    };
}
