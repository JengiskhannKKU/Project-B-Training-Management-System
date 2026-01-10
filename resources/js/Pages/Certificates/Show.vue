<script setup>
import { ref, computed } from "vue";
import { Head, Link, usePage } from "@inertiajs/vue3";
import AdminLayout from "@/Layouts/AdminLayout.vue";
import TrainerLayout from "@/Layouts/TrainerLayout.vue";
import TraineeLayout from "@/Layouts/TraineeLayout.vue";
import LoadingSpinner from "@/Components/LoadingSpinner.vue";
import { Award, Download, Eye, ArrowLeft } from "lucide-vue-next";

const props = defineProps({
    certificateId: {
        type: [Number, String],
        required: true,
    },
    certificate: {
        type: Object,
        default: null,
    },
});

const page = usePage();
const certificate = ref(props.certificate);
const isLoading = ref(false);
const errorMessage = ref(props.certificate ? "" : "Certificate not found");

const roleName = computed(
    () =>
        page.props.auth?.user?.role?.name ||
        page.props.auth?.user?.role ||
        "trainee"
);

const LayoutComponent = computed(() => {
    if (roleName.value === "admin") return AdminLayout;
    if (roleName.value === "trainer") return TrainerLayout;
    return TraineeLayout;
});

const formatDate = (value) => {
    if (!value) return "—";
    const parsed = new Date(value);
    return Number.isNaN(parsed.getTime())
        ? value
        : parsed.toLocaleDateString();
};

const resolveProgramName = computed(() => {
    return (
        certificate.value?.program?.name ||
        certificate.value?.session?.program?.name ||
        "—"
    );
});

const resolveSessionTitle = computed(() => {
    return certificate.value?.session?.title || "—";
});

const statusBadgeClass = computed(() => {
    const status = (certificate.value?.status || "").toLowerCase();
    if (status === "valid") return "bg-emerald-100 text-emerald-700";
    if (status === "revoked") return "bg-rose-100 text-rose-700";
    return "bg-gray-100 text-gray-700";
});

const hasFile = computed(() => {
    const cert = certificate.value;
    return Boolean(
        cert?.file_data ||
            cert?.file_mime_type ||
            cert?.file_size ||
            cert?.generated_at
    );
});

const downloadLabel = computed(() =>
    hasFile.value ? "Download" : "Generate & Download"
);

const backLink = computed(() => {
    if (!certificate.value) return "/me/certificates";
    if (roleName.value === "admin") return "/admin/certificates";
    if (roleName.value === "trainer") {
        if (certificate.value.session_id) {
            return `/sessions/${certificate.value.session_id}/certificates`;
        }
        if (certificate.value.course_id) {
            return `/courses/${certificate.value.course_id}/certificates`;
        }
        return "/trainer/attendance";
    }
    return "/me/certificates";
});

const backLabel = computed(() =>
    roleName.value === "admin" ? "Back to Certificates" : "Back to My Certificates"
);
</script>

<template>
    <Head title="Certificate Detail" />

    <component :is="LayoutComponent">
        <div class="mx-auto max-w-4xl space-y-6">
            <Link
                :href="backLink"
                class="inline-flex items-center gap-2 text-[#2f837d] hover:text-[#26685f] font-medium transition-colors"
            >
                <ArrowLeft :size="18" />
                <span>{{ backLabel }}</span>
            </Link>

            <div v-if="isLoading" class="py-16">
                <LoadingSpinner size="lg" text="Loading certificate..." />
            </div>

            <div
                v-else-if="errorMessage"
                class="rounded-2xl border border-rose-200 bg-rose-50 p-6 text-rose-700"
            >
                {{ errorMessage }}
            </div>

            <div
                v-else
                class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm"
            >
                <div class="flex flex-wrap items-start justify-between gap-4">
                    <div class="flex items-center gap-3">
                        <div
                            class="flex h-12 w-12 items-center justify-center rounded-full bg-pink-100 text-pink-600"
                        >
                            <Award class="h-6 w-6" />
                        </div>
                        <div>
                            <div class="text-sm text-gray-500">
                                Certificate Detail
                            </div>
                            <div class="text-xl font-semibold text-gray-900">
                                {{ resolveProgramName }}
                            </div>
                        </div>
                    </div>
                    <span
                        class="inline-flex rounded-full px-3 py-1 text-xs font-semibold capitalize"
                        :class="statusBadgeClass"
                    >
                        {{ certificate?.status || "unknown" }}
                    </span>
                </div>

                <div class="mt-6 grid gap-4 md:grid-cols-2">
                    <div class="rounded-xl border border-gray-100 bg-gray-50 p-4">
                        <div class="text-xs font-semibold uppercase text-gray-400">
                            Holder
                        </div>
                        <div class="mt-1 text-base font-semibold text-gray-900">
                            {{ certificate?.user?.name || "—" }}
                        </div>
                        <div class="mt-3 text-xs font-semibold uppercase text-gray-400">
                            Session
                        </div>
                        <div class="mt-1 text-sm text-gray-700">
                            {{ resolveSessionTitle }}
                        </div>
                    </div>
                    <div class="rounded-xl border border-gray-100 bg-gray-50 p-4">
                        <div class="text-xs font-semibold uppercase text-gray-400">
                            Issued At
                        </div>
                        <div class="mt-1 text-sm text-gray-700">
                            {{ formatDate(certificate?.issued_at) }}
                        </div>
                        <div class="mt-3 text-xs font-semibold uppercase text-gray-400">
                            Certificate Code
                        </div>
                        <div class="mt-1 text-sm font-semibold text-gray-900">
                            {{ certificate?.certificate_code || "—" }}
                        </div>
                    </div>
                </div>

                <!-- Certificate Preview/Embed Section -->
                <div v-if="certificate" class="mt-6 rounded-xl border border-gray-200 bg-gray-50 p-6">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-sm font-semibold text-gray-700">Certificate Preview</h3>
                        <div class="flex items-center gap-2">
                            <a
                                :href="`/api/certificates/${certificate.id}/view`"
                                target="_blank"
                                rel="noopener"
                                class="inline-flex items-center gap-2 rounded-lg border border-gray-200 px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50"
                            >
                                <Eye class="h-4 w-4" />
                                Open in New Tab
                            </a>
                            <a
                                :href="`/api/certificates/${certificate.id}/download`"
                                class="inline-flex items-center gap-2 rounded-lg border border-emerald-400 px-4 py-2 text-sm font-semibold text-emerald-600 hover:bg-emerald-50"
                            >
                                <Download class="h-4 w-4" />
                                {{ downloadLabel }}
                            </a>
                        </div>
                    </div>

                    <!-- PDF/Image Embed Viewer -->
                    <div class="rounded-lg border border-gray-300 bg-white overflow-hidden">
                        <div v-if="!hasFile" class="flex flex-col items-center justify-center py-16 text-gray-500">
                            <Award class="h-16 w-16 mb-4 text-gray-400" />
                            <p class="text-sm font-medium">Certificate not generated yet</p>
                            <p class="text-xs mt-1">Click "Open in New Tab" to generate and view</p>
                        </div>
                        <div v-else class="relative w-full" style="min-height: 600px;">
                            <!-- PDF Viewer using iframe -->
                            <iframe
                                v-if="certificate.file_mime_type?.includes('pdf') || !certificate.file_mime_type"
                                :src="`/api/certificates/${certificate.id}/view`"
                                class="w-full h-full border-0"
                                style="min-height: 800px;"
                                title="Certificate PDF Viewer"
                            ></iframe>
                            <!-- PNG/Image Viewer -->
                            <img
                                v-else-if="certificate.file_mime_type?.includes('image')"
                                :src="`/api/certificates/${certificate.id}/view`"
                                alt="Certificate"
                                class="w-full h-auto"
                            />
                            <!-- Fallback for unknown types -->
                            <div v-else class="flex items-center justify-center py-16 text-gray-500">
                                <p class="text-sm">Unsupported file type. Please download to view.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </component>
</template>
