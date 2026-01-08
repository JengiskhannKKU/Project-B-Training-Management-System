<script setup>
import { ref, reactive, computed, watch, onMounted, nextTick } from "vue";
import { Link, router } from "@inertiajs/vue3";
import axios from "axios";
import { useToast } from "vue-toastification";
import { ArrowLeft, FileText } from "lucide-vue-next";
import LoadingSpinner from "@/Components/LoadingSpinner.vue";
import ProgressBar from "@/Components/ProgressBar.vue";
import LayoutEditor from "./LayoutEditor.vue";
import TemplateDetailsPanel from "./TemplateDetailsPanel.vue";
import StyleSettingsPanel from "./StyleSettingsPanel.vue";
import BackgroundUploader from "./BackgroundUploader.vue";
import TemplatePreviewModal from "./TemplatePreviewModal.vue";

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

// State
const isEditMode = computed(() => Boolean(props.templateId));
const isFetching = ref(false);
const isSubmitting = ref(false);
const uploadProgress = ref(0);
const errors = reactive({});

// Form data
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

// Programs & Sessions
const programs = ref([]);
const sessions = ref([]);

// Background image
const backgroundPreviewUrl = ref("");
const backgroundFile = ref(null);
const backgroundFileName = ref("");
const backgroundFileSize = ref(0);

// Layout state
const layoutEditorRef = ref(null);
const layoutConfig = ref(null);
const canvasSize = ref({ width: 1600, height: 1200 });
const qrSize = ref(160);

// Preview modal
const showPreview = ref(false);

// =====================
// Computed
// =====================
const selectedProgramName = computed(() =>
    programs.value.find((p) => String(p.id) === String(form.program_id))?.name || ""
);

const selectedSessionTitle = computed(() =>
    sessions.value.find((s) => String(s.id) === String(form.session_id))?.title || ""
);

const previewData = computed(() => ({
    name: "Alex Morgan",
    program: selectedProgramName.value || "Advanced AI Bootcamp",
    session: selectedSessionTitle.value || "Cohort 3",
    issued_at: new Date().toLocaleDateString(),
    certificate_code: "CERT-8F4K2M",
}));

const fontFamily = computed(() => {
    if (!form.font_family) return '"Prompt", sans-serif';
    if (form.font_family.includes("Prompt")) return '"Prompt", sans-serif';
    return '"Prompt", sans-serif';
});

// =====================
// API calls
// =====================
const ensureCsrf = () => axios.get("/sanctum/csrf-cookie");

const resetErrors = () => {
    Object.keys(errors).forEach((key) => delete errors[key]);
};

const fetchPrograms = async () => {
    try {
        const response = await axios.get(props.programsEndpoint);
        programs.value = response.data?.data ?? [];
    } catch {
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
        const response = await axios.get("/api/sessions", { params: { program_id: programId } });
        sessions.value = response.data?.data ?? [];
    } catch {
        sessions.value = [];
        toast.error("Unable to load sessions.");
    }
};

const loadBackgroundFromUrl = (url) =>
    new Promise((resolve) => {
        if (!url) {
            resolve();
            return;
        }
        const image = new Image();
        image.onload = () => {
            if (layoutEditorRef.value) {
                layoutEditorRef.value.setCanvasSize(image.naturalWidth, image.naturalHeight);
            }
            canvasSize.value = { width: image.naturalWidth, height: image.naturalHeight };
            resolve();
        };
        image.onerror = () => resolve();
        image.src = url;
    });

const fetchTemplate = async () => {
    if (!isEditMode.value) return;

    isFetching.value = true;
    try {
        const response = await axios.get(`${props.apiBase}/${props.templateId}`);
        const data = response.data?.data;
        if (!data) return;

        // Populate form
        form.name = data.name || "";
        form.scope = data.scope || "global";
        form.program_id = data.program_id ? String(data.program_id) : "";
        form.session_id = data.session_id ? String(data.session_id) : "";
        form.font_family = data.font_family || "";
        form.font_size = data.font_size || "";
        form.text_color = data.text_color || "";
        form.is_active = Boolean(data.is_active);

        // Background
        backgroundPreviewUrl.value = data.background_image_url || "";
        backgroundFile.value = null;

        // Layout config
        const savedLayout = data.layout_config || null;
        layoutConfig.value = savedLayout;

        // Set canvas size from layout or background
        if (savedLayout?.canvas?.width && savedLayout?.canvas?.height) {
            canvasSize.value = { width: savedLayout.canvas.width, height: savedLayout.canvas.height };
        }

        if (backgroundPreviewUrl.value) {
            await loadBackgroundFromUrl(backgroundPreviewUrl.value);
        }

        // Load sessions if needed
        if (form.program_id) {
            await fetchSessions(form.program_id);
        }

        await nextTick();
    } catch {
        toast.error("Unable to load certificate template.");
    } finally {
        isFetching.value = false;
    }
};

// =====================
// Handlers
// =====================
const handleBackgroundUpload = async (file) => {
    backgroundFile.value = file;
    backgroundFileName.value = file.name;
    backgroundFileSize.value = file.size;

    // Preview URL
    const reader = new FileReader();
    reader.onload = () => {
        backgroundPreviewUrl.value = reader.result;
    };
    reader.readAsDataURL(file);

    // Get image dimensions
    const objectUrl = URL.createObjectURL(file);
    const image = new Image();
    image.onload = () => {
        if (layoutEditorRef.value) {
            layoutEditorRef.value.setCanvasSize(image.naturalWidth, image.naturalHeight);
        }
        canvasSize.value = { width: image.naturalWidth, height: image.naturalHeight };
        URL.revokeObjectURL(objectUrl);
    };
    image.src = objectUrl;
};

const handleBackgroundRemove = () => {
    backgroundFile.value = null;
    backgroundFileName.value = "";
    backgroundFileSize.value = 0;
    backgroundPreviewUrl.value = "";
    canvasSize.value = { width: 1600, height: 1200 };
    if (layoutEditorRef.value) {
        layoutEditorRef.value.setCanvasSize(1600, 1200);
    }
};

const handleLayoutUpdate = (config) => {
    layoutConfig.value = config;
};

const handleCanvasSizeUpdate = (size) => {
    canvasSize.value = size;
};

const handlePreview = () => {
    showPreview.value = true;
};

const handleFormUpdate = (value) => {
    Object.assign(form, value);
};

const handleFetchSessions = (programId) => {
    fetchSessions(programId);
};

const handleSubmit = async () => {
    resetErrors();
    isSubmitting.value = true;
    uploadProgress.value = 0;

    try {
        await ensureCsrf();

        const payload = new FormData();
        payload.append("name", form.name);
        payload.append("scope", form.scope);
        payload.append("is_active", form.is_active ? "1" : "0");

        // Get current layout config from editor
        const currentLayout = layoutEditorRef.value?.buildLayoutConfig() || layoutConfig.value;
        if (currentLayout) {
            payload.append("layout_config", JSON.stringify(currentLayout));
        }

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

        const uploadConfig = {
            onUploadProgress: (progressEvent) => {
                if (progressEvent.total) {
                    uploadProgress.value = Math.round((progressEvent.loaded * 100) / progressEvent.total);
                }
            },
        };

        if (isEditMode.value) {
            payload.append("_method", "PUT");
            await axios.post(`${props.apiBase}/${props.templateId}`, payload, uploadConfig);
            toast.success("Certificate template updated.");
        } else {
            await axios.post(props.apiBase, payload, uploadConfig);
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
            const message = error?.response?.data?.message || "Unable to save certificate template.";
            toast.error(message);
        }
    } finally {
        isSubmitting.value = false;
    }
};

// =====================
// Watchers
// =====================
watch(
    () => form.scope,
    (value) => {
        if (value === "global") {
            form.program_id = "";
            form.session_id = "";
            sessions.value = [];
        } else if (value === "program") {
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

// =====================
// Lifecycle
// =====================
onMounted(async () => {
    await fetchPrograms();
    await fetchTemplate();
});
</script>

<template>
    <div class="space-y-6">
        <!-- Page Header -->
        <div class="relative">
            <div class="flex flex-wrap items-center justify-between gap-4">
                <div class="flex items-center gap-4">
                    <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-gradient-to-br from-[#2f837d] to-[#266a66] shadow-lg shadow-[#2f837d]/20">
                        <FileText class="h-6 w-6 text-white" />
                    </div>
                    <div>
                        <h1 class="text-2xl font-bold text-gray-900 tracking-tight">
                            {{ isEditMode ? "Edit Certificate Template" : "Create Certificate Template" }}
                        </h1>
                        <p class="text-sm text-gray-500">
                            Position placeholders and configure appearance for certificate layouts.
                        </p>
                    </div>
                </div>
                <Link
                    :href="props.indexPath"
                    class="inline-flex items-center gap-2 rounded-lg border border-gray-200 bg-white px-4 py-2 text-sm font-medium text-gray-600 shadow-sm transition-all hover:bg-gray-50 hover:text-gray-900 hover:shadow"
                >
                    <ArrowLeft class="h-4 w-4" />
                    Back to list
                </Link>
            </div>
            <div class="absolute bottom-0 left-0 right-0 h-px bg-gradient-to-r from-transparent via-[#2f837d]/20 to-transparent -mb-3" />
        </div>

        <!-- Loading State -->
        <div v-if="isFetching" class="rounded-2xl border border-gray-200 bg-white p-10 shadow-sm">
            <LoadingSpinner size="lg" text="Loading template..." />
        </div>

        <!-- Form -->
        <form v-else class="space-y-6" @submit.prevent="handleSubmit">
            <div class="grid gap-6 lg:grid-cols-3">
                <!-- Left Column: Layout Editor -->
                <div class="lg:col-span-2 space-y-6">
                    <LayoutEditor
                        ref="layoutEditorRef"
                        :background-url="backgroundPreviewUrl"
                        :font-size="form.font_size || 28"
                        :text-color="form.text_color || '#1f2937'"
                        :font-family="fontFamily"
                        :preview-data="previewData"
                        :initial-layout-config="layoutConfig"
                        :initial-canvas-size="canvasSize"
                        :qr-size-value="qrSize"
                        @update:layout-config="handleLayoutUpdate"
                        @update:canvas-size="handleCanvasSizeUpdate"
                        @update:qr-size="(val) => qrSize = val"
                        @preview="handlePreview"
                    />
                </div>

                <!-- Right Column: Settings Panels -->
                <div class="space-y-6">
                    <TemplateDetailsPanel
                        :model-value="form"
                        :programs="programs"
                        :sessions="sessions"
                        :errors="errors"
                        @update:model-value="handleFormUpdate"
                        @fetch-sessions="handleFetchSessions"
                    />

                    <StyleSettingsPanel
                        :font-family="form.font_family"
                        :font-size="form.font_size"
                        :text-color="form.text_color"
                        :qr-size="qrSize"
                        :errors="errors"
                        @update:font-family="(val) => form.font_family = val"
                        @update:font-size="(val) => form.font_size = val"
                        @update:text-color="(val) => form.text_color = val"
                        @update:qr-size="(val) => qrSize = val"
                    />

                    <BackgroundUploader
                        :preview-url="backgroundPreviewUrl"
                        :file-name="backgroundFileName"
                        :file-size="backgroundFileSize"
                        @upload="handleBackgroundUpload"
                        @remove="handleBackgroundRemove"
                    />
                </div>
            </div>

            <!-- Submit Section -->
            <div class="flex flex-wrap items-center justify-end gap-3">
                <!-- Upload Progress -->
                <div v-if="isSubmitting && uploadProgress > 0 && uploadProgress < 100" class="w-full mb-3">
                    <div class="flex items-center justify-between text-sm text-gray-600 mb-1">
                        <span>Uploading...</span>
                        <span>{{ uploadProgress }}%</span>
                    </div>
                    <ProgressBar
                        :value="uploadProgress"
                        :max="100"
                        variant="linear"
                        :show-label="false"
                        class="h-2"
                    />
                </div>

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

    <!-- Preview Modal -->
    <TemplatePreviewModal
        :show="showPreview"
        :background-url="backgroundPreviewUrl"
        :canvas-natural="canvasSize"
        :layout-positions="layoutEditorRef?.layoutPositions || {}"
        :active-placeholders="layoutEditorRef?.activePlaceholders || []"
        :font-size="form.font_size || 28"
        :text-color="form.text_color || '#1f2937'"
        :font-family="fontFamily"
        :selected-program-name="selectedProgramName"
        :selected-session-title="selectedSessionTitle"
        @close="showPreview = false"
    />
</template>
