<script setup>
import { ref, computed, onMounted } from "vue";
import { Head, Link, usePage } from "@inertiajs/vue3";
import axios from "axios";
import AdminLayout from "@/Layouts/AdminLayout.vue";
import TrainerLayout from "@/Layouts/TrainerLayout.vue";
import StudentLayout from "@/Layouts/StudentLayout.vue";
import LoadingSpinner from "@/Components/LoadingSpinner.vue";
import { Award, Download, Eye, ArrowLeft } from "lucide-vue-next";

const props = defineProps({
    certificateId: {
        type: [Number, String],
        required: true,
    },
});

const page = usePage();
const certificate = ref(null);
const isLoading = ref(false);
const errorMessage = ref("");

const roleName = computed(
    () =>
        page.props.auth?.user?.role?.name ||
        page.props.auth?.user?.role ||
        "student"
);

const LayoutComponent = computed(() => {
    if (roleName.value === "admin") return AdminLayout;
    if (roleName.value === "trainer") return TrainerLayout;
    return StudentLayout;
});

const fetchCertificate = async () => {
    isLoading.value = true;
    errorMessage.value = "";
    try {
        const { data } = await axios.get(
            `/api/certificates/${props.certificateId}`
        );
        certificate.value = data?.data || null;
    } catch (error) {
        certificate.value = null;
        errorMessage.value =
            error?.response?.data?.message ||
            error?.message ||
            "Unable to load certificate.";
    } finally {
        isLoading.value = false;
    }
};

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

const viewLabel = computed(() =>
    hasFile.value ? "View" : "Generate & View"
);

const backLink = computed(() => {
    if (!certificate.value) return "/me/certificates";
    if (roleName.value === "admin") return "/admin/certificates";
    if (roleName.value === "trainer") {
        if (certificate.value.session_id) {
            return `/sessions/${certificate.value.session_id}/certificates`;
        }
        if (certificate.value.program_id) {
            return `/programs/${certificate.value.program_id}/certificates`;
        }
        return "/trainer/attendance";
    }
    return "/me/certificates";
});

const backLabel = computed(() =>
    roleName.value === "admin" ? "Back to Certificates" : "Back to My Certificates"
);

onMounted(fetchCertificate);
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

                <div class="mt-6 flex flex-wrap items-center gap-3">
                    <a
                        v-if="certificate"
                        :href="`/api/certificates/${certificate.id}/view`"
                        target="_blank"
                        rel="noopener"
                        class="inline-flex items-center gap-2 rounded-full border border-gray-200 px-5 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50"
                    >
                        <Eye class="h-4 w-4" />
                        {{ viewLabel }}
                    </a>
                    <a
                        v-if="certificate"
                        :href="`/api/certificates/${certificate.id}/download`"
                        class="inline-flex items-center gap-2 rounded-full border border-emerald-400 px-5 py-2 text-sm font-semibold text-emerald-600 hover:bg-emerald-50"
                    >
                        <Download class="h-4 w-4" />
                        {{ downloadLabel }}
                    </a>
                    <div v-if="!hasFile" class="text-xs text-gray-500">
                        File will be generated when you view or download.
                    </div>
                </div>
            </div>
        </div>
    </component>
</template>
