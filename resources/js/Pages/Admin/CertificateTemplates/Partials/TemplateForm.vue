<script setup>
import {
    ref,
    reactive,
    computed,
    watch,
    onMounted,
    onBeforeUnmount,
    nextTick,
} from "vue";
import { Link, router } from "@inertiajs/vue3";
import axios from "axios";
import { useToast } from "vue-toastification";
import {
    ArrowLeft,
    UploadCloud,
    RefreshCw,
    Move,
    Scan,
    Eye,
    X,
} from "lucide-vue-next";
import LoadingSpinner from "@/Components/LoadingSpinner.vue";
import Modal from "@/Components/Modal.vue";

const props = defineProps({
    templateId: {
        type: [String, Number],
        default: null,
    },
    apiBase: {
        type: String,
        default: "/api/admin/certificate-templates",
    },
    indexPath: {
        type: String,
        default: "/admin/certificate-templates",
    },
    programsEndpoint: {
        type: String,
        default: "/api/programs",
    },
});

const toast = useToast();

const isEditMode = computed(() => Boolean(props.templateId));
const isFetching = ref(false);
const isSubmitting = ref(false);
const errors = reactive({});

const form = reactive({
    name: "",
    scope: "global",
    program_id: "",
    session_id: "",
    font_family: "",
    font_size: "",
    text_color: "",
    is_active: true,
});

const programs = ref([]);
const sessions = ref([]);

const backgroundPreviewUrl = ref("");
const backgroundFile = ref(null);

const canvasRef = ref(null);
const canvasDisplay = reactive({ width: 0, height: 0 });
const canvasNatural = reactive({ width: 1600, height: 1200 });
const showPreview = ref(false);
const previewCanvasRef = ref(null);
const previewDisplay = reactive({ width: 0, height: 0 });
let previewObserver = null;
const draggingKey = ref(null);

const placeholders = [
    { key: "name", label: "{{student_name}}", color: "bg-indigo-100 text-indigo-700" },
    { key: "program", label: "{{program_name}}", color: "bg-emerald-100 text-emerald-700" },
    { key: "session", label: "{{session_title}}", color: "bg-blue-100 text-blue-700" },
    { key: "issued_at", label: "{{issued_date}}", color: "bg-amber-100 text-amber-700" },
    { key: "certificate_code", label: "{{certificate_code}}", color: "bg-gray-100 text-gray-700" },
    { key: "qr", label: "{{qr_code}}", color: "bg-teal-100 text-teal-700", isQr: true },
];

const layoutPositions = reactive({});
const qrSize = ref(160);
let resizeObserver = null;

const fontOptions = [
    { label: "Default (GD built-in)", value: "" },
    { label: "Prompt Regular (Prompt-Regular.ttf)", value: "Prompt-Regular.ttf" },
    { label: "Prompt Medium (Prompt-Medium.ttf)", value: "Prompt-Medium.ttf" },
    { label: "Prompt Bold (Prompt-Bold.ttf)", value: "Prompt-Bold.ttf" },
    { label: "Custom font file...", value: "__custom__" },
];

const fontSelection = ref("");
const customFontValue = ref("");

const ensureCsrf = () => axios.get("/sanctum/csrf-cookie");

const resetErrors = () => {
    Object.keys(errors).forEach((key) => {
        delete errors[key];
    });
};

const fetchPrograms = async () => {
    try {
        const response = await axios.get(props.programsEndpoint);
        programs.value = response.data?.data ?? [];
    } catch (error) {
        programs.value = [];
        toast.error("Unable to load programs.");
    }
};

const fetchSessions = async (programId) => {
    if (!programId) {
        sessions.value = [];
        return;
    }

    try {
        const response = await axios.get("/api/sessions", {
            params: { program_id: programId },
        });
        sessions.value = response.data?.data ?? [];
    } catch (error) {
        sessions.value = [];
        toast.error("Unable to load sessions.");
    }
};

const setCanvasSize = (width, height, scalePositions = false) => {
    if (!width || !height) {
        return;
    }

    const prevWidth = canvasNatural.width || 1;
    const prevHeight = canvasNatural.height || 1;

    canvasNatural.width = width;
    canvasNatural.height = height;

    if (!scalePositions) {
        return;
    }

    const scaleX = width / prevWidth;
    const scaleY = height / prevHeight;

    Object.values(layoutPositions).forEach((position) => {
        position.x = Math.round((position.x || 0) * scaleX);
        position.y = Math.round((position.y || 0) * scaleY);
        if (position.width) {
            position.width = Math.round(position.width * scaleX);
        }
        if (position.height) {
            position.height = Math.round(position.height * scaleY);
        }
        if (position.size) {
            position.size = Math.round(position.size * scaleX);
        }
    });
};

const buildDefaultLayout = (width, height) => ({
    name: { x: Math.round(width * 0.12), y: Math.round(height * 0.32) },
    program: { x: Math.round(width * 0.12), y: Math.round(height * 0.42) },
    session: { x: Math.round(width * 0.12), y: Math.round(height * 0.52) },
    issued_at: { x: Math.round(width * 0.12), y: Math.round(height * 0.62) },
    certificate_code: { x: Math.round(width * 0.12), y: Math.round(height * 0.72) },
    qr: {
        x: Math.round(width * 0.72),
        y: Math.round(height * 0.58),
        width: 160,
        height: 160,
        size: 160,
    },
});

const applyLayoutConfig = (config) => {
    const defaults = buildDefaultLayout(canvasNatural.width, canvasNatural.height);
    const source = config || {};

    placeholders.forEach((placeholder) => {
        const key = placeholder.key;
        const fallback = defaults[key] || { x: 0, y: 0 };
        const item = source[key] || fallback;

        layoutPositions[key] = {
            x: Number(item.x ?? fallback.x ?? 0),
            y: Number(item.y ?? fallback.y ?? 0),
            width: Number(item.width ?? fallback.width ?? 160),
            height: Number(item.height ?? fallback.height ?? 160),
            size: Number(item.size ?? item.width ?? fallback.size ?? 160),
        };
    });

    qrSize.value = layoutPositions.qr?.size || 160;
};

const resetLayout = () => {
    applyLayoutConfig(null);
};

const updateDisplaySize = () => {
    if (!canvasRef.value) {
        return;
    }
    const rect = canvasRef.value.getBoundingClientRect();
    canvasDisplay.width = rect.width;
    canvasDisplay.height = rect.height;
};

const updatePreviewDisplaySize = () => {
    if (!previewCanvasRef.value) {
        return;
    }
    const rect = previewCanvasRef.value.getBoundingClientRect();
    previewDisplay.width = rect.width;
    previewDisplay.height = rect.height;
};

const toDisplayX = (value) => {
    if (!canvasDisplay.width || !canvasNatural.width) {
        return value;
    }
    return (value / canvasNatural.width) * canvasDisplay.width;
};

const toDisplayY = (value) => {
    if (!canvasDisplay.height || !canvasNatural.height) {
        return value;
    }
    return (value / canvasNatural.height) * canvasDisplay.height;
};

const toDisplayWidth = (value) => {
    if (!canvasDisplay.width || !canvasNatural.width) {
        return value;
    }
    return (value / canvasNatural.width) * canvasDisplay.width;
};

const toDisplayHeight = (value) => {
    if (!canvasDisplay.height || !canvasNatural.height) {
        return value;
    }
    return (value / canvasNatural.height) * canvasDisplay.height;
};

const toPreviewX = (value) => {
    if (!previewDisplay.width || !canvasNatural.width) {
        return value;
    }
    return (value / canvasNatural.width) * previewDisplay.width;
};

const toPreviewY = (value) => {
    if (!previewDisplay.height || !canvasNatural.height) {
        return value;
    }
    return (value / canvasNatural.height) * previewDisplay.height;
};

const toNaturalX = (value) => {
    if (!canvasDisplay.width || !canvasNatural.width) {
        return value;
    }
    return (value / canvasDisplay.width) * canvasNatural.width;
};

const toNaturalY = (value) => {
    if (!canvasDisplay.height || !canvasNatural.height) {
        return value;
    }
    return (value / canvasDisplay.height) * canvasNatural.height;
};

const resolveTextBoxSize = () => {
    const baseSize = Number(form.font_size) || 28;
    const height = Math.max(56, Math.round(baseSize * 2.4));
    const width = Math.max(320, Math.round(baseSize * 12));
    return { width, height };
};

const placeholderStyle = (placeholder) => {
    const position = layoutPositions[placeholder.key];
    if (!position) {
        return {};
    }

    const style = {
        left: `${toDisplayX(position.x)}px`,
        top: `${toDisplayY(position.y)}px`,
    };

    if (placeholder.isQr) {
        const width = position.width || position.size || 160;
        const height = position.height || position.size || 160;
        style.width = `${toDisplayX(width)}px`;
        style.height = `${toDisplayY(height)}px`;
    } else {
        const { width, height } = resolveTextBoxSize();
        style.width = `${toDisplayWidth(width)}px`;
        style.height = `${toDisplayHeight(height)}px`;
        style.minWidth = `${toDisplayWidth(220)}px`;
        style.minHeight = `${toDisplayHeight(40)}px`;
    }

    return style;
};

const previewSample = reactive({
    name: "Alex Morgan",
    program: "",
    session: "",
    issued_at: new Date().toLocaleDateString(),
    certificate_code: "CERT-8F4K2M",
});

const selectedProgramName = computed(() => {
    return (
        programs.value.find(
            (program) => String(program.id) === String(form.program_id)
        )?.name || ""
    );
});

const selectedSessionTitle = computed(() => {
    return (
        sessions.value.find(
            (session) => String(session.id) === String(form.session_id)
        )?.title || ""
    );
});

const previewData = computed(() => {
    const programName = previewSample.program || selectedProgramName.value || "Advanced AI Bootcamp";
    const sessionTitle = previewSample.session || selectedSessionTitle.value || "Cohort 3";

    return {
        name: previewSample.name || "Alex Morgan",
        program: programName,
        session: sessionTitle,
        issued_at: previewSample.issued_at || new Date().toLocaleDateString(),
        certificate_code: previewSample.certificate_code || "CERT-8F4K2M",
    };
});

const previewFontFamily = computed(() => {
    if (!form.font_family) {
        return "\"Prompt\", sans-serif";
    }
    if (form.font_family.includes("Prompt")) {
        return "\"Prompt\", sans-serif";
    }
    return "\"Prompt\", sans-serif";
});

const previewTextColor = computed(() => form.text_color || "#1f2937");

const previewFontScale = computed(() => {
    if (!previewDisplay.width || !canvasNatural.width) {
        return 1;
    }
    return previewDisplay.width / canvasNatural.width;
});

const editorFontScale = computed(() => {
    if (!canvasDisplay.width || !canvasNatural.width) {
        return 1;
    }
    return canvasDisplay.width / canvasNatural.width;
});

const previewTextStyle = (key) => {
    const position = layoutPositions[key];
    if (!position) {
        return {};
    }
    const baseSize = Number(form.font_size) || 28;
    const fontSize = Math.max(12, baseSize * previewFontScale.value);

    return {
        left: `${toPreviewX(position.x)}px`,
        top: `${toPreviewY(position.y)}px`,
        fontSize: `${fontSize}px`,
        color: previewTextColor.value,
        fontFamily: previewFontFamily.value,
        whiteSpace: "nowrap",
        lineHeight: 1.1,
    };
};

const editorTextStyle = () => {
    const baseSize = Number(form.font_size) || 28;
    const fontSize = Math.max(12, baseSize * editorFontScale.value);

    return {
        fontSize: `${fontSize}px`,
        color: previewTextColor.value,
        fontFamily: previewFontFamily.value,
        whiteSpace: "nowrap",
        lineHeight: 1.1,
    };
};

const previewQrStyle = computed(() => {
    const position = layoutPositions.qr;
    if (!position) {
        return {};
    }
    const size = position.size || position.width || 160;
    return {
        left: `${toPreviewX(position.x)}px`,
        top: `${toPreviewY(position.y)}px`,
        width: `${toPreviewX(size)}px`,
        height: `${toPreviewY(size)}px`,
    };
});

const handleDragStart = (placeholderKey, event) => {
    if (event.button !== 0) {
        return;
    }

    const position = layoutPositions[placeholderKey];
    if (!position || !canvasRef.value) {
        return;
    }

    const rect = canvasRef.value.getBoundingClientRect();
    const startX = event.clientX - rect.left;
    const startY = event.clientY - rect.top;

    draggingKey.value = {
        key: placeholderKey,
        offsetX: startX - toDisplayX(position.x),
        offsetY: startY - toDisplayY(position.y),
    };

    document.body.style.userSelect = "none";
};

const handleDragMove = (event) => {
    if (!draggingKey.value || !canvasRef.value) {
        return;
    }

    const rect = canvasRef.value.getBoundingClientRect();
    const nextX = event.clientX - rect.left - draggingKey.value.offsetX;
    const nextY = event.clientY - rect.top - draggingKey.value.offsetY;

    const clampedX = Math.max(0, Math.min(nextX, canvasDisplay.width));
    const clampedY = Math.max(0, Math.min(nextY, canvasDisplay.height));

    const naturalX = Math.round(toNaturalX(clampedX));
    const naturalY = Math.round(toNaturalY(clampedY));

    const position = layoutPositions[draggingKey.value.key];
    if (position) {
        position.x = naturalX;
        position.y = naturalY;
    }
};

const handleDragEnd = () => {
    if (draggingKey.value) {
        draggingKey.value = null;
        document.body.style.userSelect = "";
    }
};

const buildLayoutConfig = () => {
    const config = {
        canvas: {
            width: Math.round(canvasNatural.width),
            height: Math.round(canvasNatural.height),
        },
    };

    placeholders.forEach((placeholder) => {
        const position = layoutPositions[placeholder.key];
        if (!position) {
            return;
        }

        if (placeholder.isQr) {
            const size = Math.round(position.size || qrSize.value || 160);
            config.qr = {
                x: Math.round(position.x || 0),
                y: Math.round(position.y || 0),
                width: size,
                height: size,
                size,
            };
        } else {
            config[placeholder.key] = {
                x: Math.round(position.x || 0),
                y: Math.round(position.y || 0),
            };
        }
    });

    return config;
};

const handleBackgroundChange = async (event) => {
    const file = event.target.files?.[0];
    if (!file) {
        return;
    }

    backgroundFile.value = file;

    const reader = new FileReader();
    reader.onload = () => {
        backgroundPreviewUrl.value = reader.result;
        nextTick(updateDisplaySize);
    };
    reader.readAsDataURL(file);

    const objectUrl = URL.createObjectURL(file);
    const image = new Image();
    image.onload = () => {
        setCanvasSize(image.naturalWidth, image.naturalHeight, true);
        URL.revokeObjectURL(objectUrl);
        nextTick(updateDisplaySize);
    };
    image.src = objectUrl;
};

const loadBackgroundFromUrl = (url) => new Promise((resolve) => {
    if (!url) {
        resolve();
        return;
    }

    const image = new Image();
    image.onload = () => {
        setCanvasSize(image.naturalWidth, image.naturalHeight, false);
        resolve();
    };
    image.onerror = () => resolve();
    image.src = url;
});

const fetchTemplate = async () => {
    if (!isEditMode.value) {
        return;
    }

    isFetching.value = true;
    try {
        const response = await axios.get(`${props.apiBase}/${props.templateId}`);
        const data = response.data?.data;
        if (!data) {
            return;
        }

        form.name = data.name || "";
        form.scope = data.scope || "global";
        form.program_id = data.program_id ? String(data.program_id) : "";
        form.session_id = data.session_id ? String(data.session_id) : "";
        form.font_family = data.font_family || "";
        form.font_size = data.font_size || "";
        form.text_color = data.text_color || "";
        form.is_active = Boolean(data.is_active);

        backgroundPreviewUrl.value = data.background_image_url || "";
        backgroundFile.value = null;

        const layoutConfig = data.layout_config || null;
        if (!backgroundPreviewUrl.value && layoutConfig?.canvas?.width && layoutConfig?.canvas?.height) {
            setCanvasSize(layoutConfig.canvas.width, layoutConfig.canvas.height, false);
        }

        if (backgroundPreviewUrl.value) {
            await loadBackgroundFromUrl(backgroundPreviewUrl.value);
        }

        applyLayoutConfig(layoutConfig);

        if (form.program_id) {
            await fetchSessions(form.program_id);
        }

        await nextTick();
        updateDisplaySize();
    } catch (error) {
        toast.error("Unable to load certificate template.");
    } finally {
        isFetching.value = false;
    }
};

const handleSubmit = async () => {
    resetErrors();
    isSubmitting.value = true;

    try {
        await ensureCsrf();

        const payload = new FormData();
        payload.append("name", form.name);
        payload.append("scope", form.scope);
        payload.append("is_active", form.is_active ? "1" : "0");
        payload.append("layout_config", JSON.stringify(buildLayoutConfig()));

        if (form.scope !== "global" && form.program_id) {
            payload.append("program_id", form.program_id);
        }

        if (form.scope === "session" && form.session_id) {
            payload.append("session_id", form.session_id);
        }

        if (form.font_family) {
            payload.append("font_family", form.font_family);
        }

        if (form.font_size) {
            payload.append("font_size", form.font_size);
        }

        if (form.text_color) {
            payload.append("text_color", form.text_color);
        }

        if (backgroundFile.value) {
            payload.append("background_image", backgroundFile.value);
        }

        if (isEditMode.value) {
            payload.append("_method", "PUT");
            await axios.post(`${props.apiBase}/${props.templateId}`, payload);
            toast.success("Certificate template updated.");
        } else {
            await axios.post(props.apiBase, payload);
            toast.success("Certificate template created.");
        }

        router.visit(props.indexPath);
    } catch (error) {
        if (error?.response?.status === 422) {
            const responseErrors = error?.response?.data?.errors || {};
            Object.entries(responseErrors).forEach(([key, value]) => {
                errors[key] = Array.isArray(value) ? value[0] : value;
            });
        } else {
            const message =
                error?.response?.data?.message ||
                "Unable to save certificate template.";
            toast.error(message);
        }
    } finally {
        isSubmitting.value = false;
    }
};

watch(
    () => form.scope,
    (value) => {
        if (value === "global") {
            form.program_id = "";
            form.session_id = "";
            sessions.value = [];
        }

        if (value === "program") {
            form.session_id = "";
            sessions.value = [];
        }
    }
);

watch(
    () => form.program_id,
    (value) => {
        if (form.scope === "session") {
            fetchSessions(value);
        }
    }
);

watch(
    () => qrSize.value,
    (value) => {
        const position = layoutPositions.qr;
        if (!position) {
            return;
        }
        position.size = Number(value) || 160;
        position.width = position.size;
        position.height = position.size;
    }
);

watch(fontSelection, (value) => {
    if (value === "__custom__") {
        form.font_family = customFontValue.value;
        return;
    }
    form.font_family = value;
    customFontValue.value = "";
});

watch(customFontValue, (value) => {
    if (fontSelection.value === "__custom__") {
        form.font_family = value;
    }
});

watch(
    () => form.font_family,
    (value) => {
        if (!value) {
            fontSelection.value = "";
            customFontValue.value = "";
            return;
        }
        const matched = fontOptions.find((option) => option.value === value);
        if (matched) {
            fontSelection.value = matched.value;
            customFontValue.value = "";
            return;
        }
        fontSelection.value = "__custom__";
        customFontValue.value = value;
    },
    { immediate: true }
);

watch(showPreview, async (value) => {
    if (!value) {
        if (previewObserver) {
            previewObserver.disconnect();
            previewObserver = null;
        }
        return;
    }

    if (!previewSample.program && selectedProgramName.value) {
        previewSample.program = selectedProgramName.value;
    }
    if (!previewSample.session && selectedSessionTitle.value) {
        previewSample.session = selectedSessionTitle.value;
    }

    await nextTick();
    updatePreviewDisplaySize();

    if (window.ResizeObserver) {
        previewObserver = new ResizeObserver(() => updatePreviewDisplaySize());
        if (previewCanvasRef.value) {
            previewObserver.observe(previewCanvasRef.value);
        }
    }
});

onMounted(async () => {
    await fetchPrograms();
    await fetchTemplate();

    await nextTick();
    updateDisplaySize();

    if (window.ResizeObserver) {
        resizeObserver = new ResizeObserver(() => updateDisplaySize());
        if (canvasRef.value) {
            resizeObserver.observe(canvasRef.value);
        }
    } else {
        window.addEventListener("resize", updateDisplaySize);
    }

    window.addEventListener("mousemove", handleDragMove);
    window.addEventListener("mouseup", handleDragEnd);

    if (!isEditMode.value) {
        resetLayout();
    }
});

onBeforeUnmount(() => {
    if (resizeObserver) {
        resizeObserver.disconnect();
    } else {
        window.removeEventListener("resize", updateDisplaySize);
    }

    if (previewObserver) {
        previewObserver.disconnect();
    }

    window.removeEventListener("mousemove", handleDragMove);
    window.removeEventListener("mouseup", handleDragEnd);
});
</script>

<template>
    <div class="space-y-6">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div>
                <h1 class="text-2xl font-semibold text-gray-900">
                    {{ isEditMode ? "Edit Certificate Template" : "Create Certificate Template" }}
                </h1>
                <p class="text-sm text-gray-600">
                    Position placeholders and configure appearance for certificate layouts.
                </p>
            </div>
            <Link
                :href="props.indexPath"
                class="inline-flex items-center gap-2 text-sm font-semibold text-gray-600 hover:text-gray-900"
            >
                <ArrowLeft class="h-4 w-4" />
                Back to list
            </Link>
        </div>

        <div v-if="isFetching" class="rounded-2xl border border-gray-200 bg-white p-10 shadow-sm">
            <LoadingSpinner size="lg" text="Loading template..." />
        </div>

        <form v-else class="space-y-6" @submit.prevent="handleSubmit">
            <div class="grid gap-6 lg:grid-cols-3">
                <div class="lg:col-span-2 space-y-6">
                    <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm space-y-4">
                        <div class="flex flex-wrap items-center justify-between gap-3">
                            <div>
                                <h2 class="text-lg font-semibold text-gray-900">Layout Editor</h2>
                                <p class="text-sm text-gray-600">
                                    Drag placeholders onto the certificate canvas.
                                </p>
                            </div>
                            <div class="flex items-center gap-2">
                                <button
                                    type="button"
                                    @click="showPreview = true"
                                    class="inline-flex items-center gap-2 rounded-lg border border-gray-200 px-3 py-1.5 text-xs font-semibold text-gray-600 hover:bg-gray-50"
                                >
                                    <Eye class="h-3.5 w-3.5" />
                                    Preview
                                </button>
                                <button
                                    type="button"
                                    @click="resetLayout"
                                    class="inline-flex items-center gap-2 rounded-lg border border-gray-200 px-3 py-1.5 text-xs font-semibold text-gray-600 hover:bg-gray-50"
                                >
                                    <RefreshCw class="h-3.5 w-3.5" />
                                    Reset layout
                                </button>
                            </div>
                        </div>

                        <div class="flex flex-wrap gap-2">
                            <span
                                v-for="placeholder in placeholders"
                                :key="placeholder.key"
                                class="inline-flex items-center gap-1 rounded-full px-2 py-1 text-xs font-semibold"
                                :class="placeholder.color"
                            >
                                <Move class="h-3 w-3" />
                                {{ placeholder.label }}
                            </span>
                        </div>

                        <div
                            ref="canvasRef"
                            class="relative w-full overflow-hidden rounded-2xl border border-dashed border-gray-200 bg-gray-50"
                            :style="{ aspectRatio: `${canvasNatural.width}/${canvasNatural.height}` }"
                        >
                            <img
                                v-if="backgroundPreviewUrl"
                                :src="backgroundPreviewUrl"
                                alt="Certificate background"
                                class="absolute inset-0 h-full w-full object-cover"
                            />
                            <div v-else class="absolute inset-0 bg-[linear-gradient(135deg,#f5f7fa,transparent)]"></div>

                            <div class="absolute inset-0">
                                <div
                                    v-for="placeholder in placeholders"
                                    :key="placeholder.key"
                                    class="absolute relative cursor-move"
                                    :style="placeholderStyle(placeholder)"
                                    @mousedown="handleDragStart(placeholder.key, $event)"
                                >
                                    <div
                                        v-if="!placeholder.isQr"
                                        class="absolute inset-0 rounded-xl border-2 border-dashed border-[#2f837d] bg-[#2f837d]/10 pointer-events-none shadow-[0_0_0_1px_rgba(47,131,125,0.15)]"
                                    >
                                        <div class="absolute right-2 top-2 rounded-full bg-white/80 px-2 py-0.5 text-[10px] font-semibold text-[#2f837d]">
                                            Text
                                        </div>
                                    </div>
                                    <div
                                        class="absolute left-2 top-2 z-10 inline-flex items-center gap-1 rounded-full border border-white/70 bg-white/90 px-2 py-0.5 text-[10px] font-semibold text-gray-700 shadow-sm"
                                        :class="{
                                            'ring-2 ring-[#2f837d]': draggingKey?.key === placeholder.key,
                                        }"
                                    >
                                        <Move class="h-3 w-3 text-gray-400" />
                                        <span>{{ placeholder.label }}</span>
                                    </div>

                                    <div
                                        v-if="placeholder.isQr"
                                        class="absolute inset-0 flex items-center justify-center rounded-xl border-2 border-dashed border-teal-300 bg-teal-50/70 text-[10px] font-semibold text-teal-600 pointer-events-none"
                                    >
                                        <Scan class="mr-1 h-3 w-3" />
                                        QR
                                    </div>

                                    <div
                                        v-if="!placeholder.isQr"
                                        class="absolute left-0 top-0 z-[1] pointer-events-none"
                                        :style="editorTextStyle()"
                                    >
                                        {{ previewData[placeholder.key] }}
                                    </div>
                                </div>
                            </div>
                        </div>

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

                <div class="space-y-6">
                    <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm space-y-4">
                        <div>
                            <h2 class="text-lg font-semibold text-gray-900">Template Details</h2>
                            <p class="text-sm text-gray-600">Core metadata and scope.</p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700">Template name</label>
                            <input
                                v-model="form.name"
                                type="text"
                                class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-transparent focus:ring-2 focus:ring-[#2f837d]"
                                placeholder="Default Program Certificate"
                                required
                            />
                            <p v-if="errors.name" class="mt-1 text-xs text-red-500">{{ errors.name }}</p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700">Scope</label>
                            <select
                                v-model="form.scope"
                                class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-transparent focus:ring-2 focus:ring-[#2f837d]"
                            >
                                <option value="global">Global</option>
                                <option value="program">Program</option>
                                <option value="session">Session</option>
                            </select>
                            <p v-if="errors.scope" class="mt-1 text-xs text-red-500">{{ errors.scope }}</p>
                        </div>

                        <div v-if="form.scope !== 'global'">
                            <label class="block text-sm font-medium text-gray-700">Program</label>
                            <select
                                v-model="form.program_id"
                                class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-transparent focus:ring-2 focus:ring-[#2f837d]"
                                :required="form.scope !== 'global'"
                            >
                                <option value="">Select program</option>
                                <option v-for="program in programs" :key="program.id" :value="String(program.id)">
                                    {{ program.name }}
                                </option>
                            </select>
                            <p v-if="errors.program_id" class="mt-1 text-xs text-red-500">{{ errors.program_id }}</p>
                        </div>

                        <div v-if="form.scope === 'session'">
                            <label class="block text-sm font-medium text-gray-700">Session</label>
                            <select
                                v-model="form.session_id"
                                class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-transparent focus:ring-2 focus:ring-[#2f837d]"
                                :disabled="!form.program_id"
                                required
                            >
                                <option value="">Select session</option>
                                <option v-for="session in sessions" :key="session.id" :value="String(session.id)">
                                    {{ session.title }}
                                </option>
                            </select>
                            <p v-if="errors.session_id" class="mt-1 text-xs text-red-500">{{ errors.session_id }}</p>
                            <p v-if="!form.program_id" class="mt-1 text-xs text-gray-500">
                                Select a program to load sessions.
                            </p>
                        </div>

                        <div class="flex items-center gap-2">
                            <input
                                id="is_active"
                                v-model="form.is_active"
                                type="checkbox"
                                class="h-4 w-4 rounded border-gray-300 text-[#2f837d] focus:ring-[#2f837d]"
                            />
                            <label for="is_active" class="text-sm text-gray-700">Active template</label>
                        </div>
                    </div>

                    <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm space-y-4">
                        <div>
                            <h2 class="text-lg font-semibold text-gray-900">Style Settings</h2>
                            <p class="text-sm text-gray-600">Global fonts and colors for text.</p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700">Font family (optional)</label>
                            <select
                                v-model="fontSelection"
                                class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-transparent focus:ring-2 focus:ring-[#2f837d]"
                            >
                                <option v-for="option in fontOptions" :key="option.value" :value="option.value">
                                    {{ option.label }}
                                </option>
                            </select>
                            <input
                                v-if="fontSelection === '__custom__'"
                                v-model="customFontValue"
                                type="text"
                                class="mt-2 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-transparent focus:ring-2 focus:ring-[#2f837d]"
                                placeholder="storage/app/fonts/YourFont.ttf"
                            />
                            <p class="mt-1 text-xs text-gray-500">
                                Upload TTF files to <span class="font-semibold">storage/app/fonts</span>.
                            </p>
                            <p v-if="errors.font_family" class="mt-1 text-xs text-red-500">{{ errors.font_family }}</p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700">Font size</label>
                            <input
                                v-model="form.font_size"
                                type="number"
                                min="8"
                                max="200"
                                class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-transparent focus:ring-2 focus:ring-[#2f837d]"
                                placeholder="24"
                            />
                            <p v-if="errors.font_size" class="mt-1 text-xs text-red-500">{{ errors.font_size }}</p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700">Text color</label>
                            <input
                                v-model="form.text_color"
                                type="text"
                                class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-transparent focus:ring-2 focus:ring-[#2f837d]"
                                placeholder="#2f837d"
                            />
                            <p v-if="errors.text_color" class="mt-1 text-xs text-red-500">{{ errors.text_color }}</p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700">QR size (px)</label>
                            <input
                                v-model.number="qrSize"
                                type="number"
                                min="80"
                                max="600"
                                class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-transparent focus:ring-2 focus:ring-[#2f837d]"
                            />
                        </div>
                    </div>

                    <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm space-y-4">
                        <div>
                            <h2 class="text-lg font-semibold text-gray-900">Background Image</h2>
                            <p class="text-sm text-gray-600">Upload the certificate background artwork.</p>
                        </div>

                        <label class="flex flex-col items-center justify-center gap-2 rounded-xl border border-dashed border-gray-300 bg-gray-50 px-4 py-6 text-sm text-gray-600 cursor-pointer hover:border-[#2f837d] hover:bg-[#2f837d]/5">
                            <UploadCloud class="h-5 w-5 text-[#2f837d]" />
                            <span>Click to upload (PNG/JPG)</span>
                            <input type="file" accept="image/*" class="hidden" @change="handleBackgroundChange" />
                        </label>

                        <div v-if="backgroundPreviewUrl" class="rounded-xl border border-gray-200 p-3">
                            <img
                                :src="backgroundPreviewUrl"
                                alt="Background preview"
                                class="h-36 w-full rounded-lg object-cover"
                            />
                            <p class="mt-2 text-xs text-gray-500">
                                New uploads replace the existing background.
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="flex flex-wrap items-center justify-end gap-3">
                <Link
                    :href="props.indexPath"
                    class="rounded-lg border border-gray-200 px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50"
                >
                    Cancel
                </Link>
                <button
                    type="submit"
                    class="rounded-lg bg-[#2f837d] px-5 py-2 text-sm font-semibold text-white hover:bg-[#266a66] disabled:opacity-60"
                    :disabled="isSubmitting"
                >
                    {{ isSubmitting ? "Saving..." : isEditMode ? "Update Template" : "Create Template" }}
                </button>
            </div>
        </form>
    </div>

    <Modal :show="showPreview" max-width="2xl" @close="showPreview = false">
        <div class="p-6 space-y-4">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="text-lg font-semibold text-gray-900">Template Preview</h3>
                    <p class="text-sm text-gray-600">Sample data rendered with the current layout.</p>
                </div>
                <button
                    type="button"
                    @click="showPreview = false"
                    class="inline-flex h-9 w-9 items-center justify-center rounded-full border border-gray-200 text-gray-500 hover:bg-gray-50"
                >
                    <X class="h-4 w-4" />
                </button>
            </div>

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

            <div
                ref="previewCanvasRef"
                class="relative w-full overflow-hidden rounded-2xl border border-dashed border-gray-200 bg-gray-50"
                :style="{ aspectRatio: `${canvasNatural.width}/${canvasNatural.height}` }"
            >
                <img
                    v-if="backgroundPreviewUrl"
                    :src="backgroundPreviewUrl"
                    alt="Certificate background"
                    class="absolute inset-0 h-full w-full object-cover"
                />
                <div v-else class="absolute inset-0 bg-[linear-gradient(135deg,#f5f7fa,transparent)]"></div>

                <div class="absolute inset-0">
                    <template v-for="(value, key) in previewData" :key="key">
                        <div
                            v-if="layoutPositions[key]"
                            class="absolute text-xs font-semibold"
                            :style="previewTextStyle(key)"
                        >
                            {{ value }}
                        </div>
                    </template>
                    <div
                        v-if="layoutPositions.qr"
                        class="absolute flex items-center justify-center rounded-xl border-2 border-dashed border-teal-300 bg-teal-50/70 text-[10px] font-semibold text-teal-600"
                        :style="previewQrStyle"
                    >
                        <Scan class="mr-1 h-3 w-3" />
                        QR
                    </div>
                </div>
            </div>

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
