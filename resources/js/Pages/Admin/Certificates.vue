<script setup>
import { ref, onMounted, computed } from "vue";
import { Head, Link } from "@inertiajs/vue3";
import axios from "axios";
import { useToast } from "vue-toastification";
import { Eye, Trash2, Search, Download, Zap, RefreshCw, CheckCircle, XCircle, Loader2, Clock } from "lucide-vue-next";
import AdminLayout from "@/Layouts/AdminLayout.vue";
import Skeleton from "@/Components/Skeleton.vue";
import { useAlert } from "@/composables/useAlert";

const toast = useToast();
const alert = useAlert();

const certificates = ref([]);
const isLoading = ref(false);
const isRevoking = ref(false);

// Batch generation state
const showBatchModal = ref(false);
const batchForm = ref({
    type: 'session',
    course_id: '',
    session_id: '',
    language: 'th',
    eager_generate: false,
});
const isBatchGenerating = ref(false);
const batchProgress = ref(null);

const filters = ref({
    course_id: "",
    session_id: "",
    user_id: "",
    status: "",
    issued_by: "",
});

const ensureCsrf = () => axios.get("/sanctum/csrf-cookie");

const buildParams = () => {
    const params = {};
    Object.entries(filters.value).forEach(([key, value]) => {
        if (value !== "" && value !== null && value !== undefined) {
            params[key] = value;
        }
    });
    return params;
};

const fetchCertificates = async () => {
    isLoading.value = true;
    try {
        const response = await axios.get("/api/admin/certificates", {
            params: buildParams(),
        });
        certificates.value = response.data?.data ?? [];
    } catch (error) {
        certificates.value = [];
        const message =
            error?.response?.data?.message ||
            "Unable to load certificates.";
        toast.error(message);
    } finally {
        isLoading.value = false;
    }
};

const resetFilters = async () => {
    filters.value = {
        course_id: "",
        session_id: "",
        user_id: "",
        status: "",
        issued_by: "",
    };
    await fetchCertificates();
};

// Batch generation functions
const openBatchModal = () => {
    batchForm.value = {
        type: 'session',
        course_id: '',
        session_id: '',
        language: 'th',
        eager_generate: false,
    };
    batchProgress.value = null;
    showBatchModal.value = true;
};

const closeBatchModal = () => {
    if (isBatchGenerating.value) {
        toast.warning('Please wait for batch generation to complete');
        return;
    }
    showBatchModal.value = false;
};

const generateBatch = async () => {
    // Validate form
    if (batchForm.value.type === 'session' && !batchForm.value.session_id) {
        toast.error('Please enter a session ID');
        return;
    }
    if (batchForm.value.type === 'course' && !batchForm.value.course_id) {
        toast.error('Please enter a course ID');
        return;
    }

    isBatchGenerating.value = true;
    batchProgress.value = null;

    try {
        await ensureCsrf();
        const response = await axios.post('/api/admin/certificates/batch', {
            type: batchForm.value.type,
            course_id: batchForm.value.course_id || undefined,
            session_id: batchForm.value.session_id || undefined,
            language: batchForm.value.language,
            eager_generate: batchForm.value.eager_generate,
        });

        batchProgress.value = response.data.data;
        toast.success(`Generated ${batchProgress.value.generated_count} certificates successfully!`);

        // Refresh certificates list
        await fetchCertificates();

        // Close modal after a delay
        setTimeout(() => {
            showBatchModal.value = false;
        }, 2000);
    } catch (error) {
        const message = error?.response?.data?.message || 'Failed to generate certificates';
        toast.error(message);
        console.error('Batch generation error:', error);
    } finally {
        isBatchGenerating.value = false;
    }
};

const progressPercentage = computed(() => {
    if (!batchProgress.value) return 0;
    return Math.round(batchProgress.value.progress_percentage || 0);
});

const successRate = computed(() => {
    if (!batchProgress.value) return 0;
    return Math.round(batchProgress.value.success_rate || 0);
});

const revokeCertificate = async (certificate) => {
    const confirmed = await alert.confirm({
        title: 'Revoke Certificate',
        message: 'Are you sure you want to revoke this certificate? This action cannot be undone.',
        confirmText: 'Revoke',
        cancelText: 'Cancel'
    });

    if (!confirmed) return;

    isRevoking.value = true;
    try {
        await ensureCsrf();
        await axios.post(`/api/admin/certificates/${certificate.id}/revoke`);
        toast.success("Certificate revoked.");
        certificate.status = "revoked";
    } catch (error) {
        const message =
            error?.response?.data?.message ||
            "Failed to revoke certificate.";
        toast.error(message);
    } finally {
        isRevoking.value = false;
    }
};

const statusBadgeClass = (status) => {
    return status === "valid"
        ? "bg-green-100 text-green-700"
        : "bg-red-100 text-red-700";
};

const formatDate = (value) => {
    if (!value) return "—";
    const parsed = new Date(value);
    if (Number.isNaN(parsed.getTime())) {
        return value;
    }
    return parsed.toLocaleDateString();
};

onMounted(fetchCertificates);
</script>

<template>
    <Head title="Certificates" />
    <AdminLayout>
        <div class="space-y-6">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-semibold text-gray-900">Certificates</h1>
                    <p class="text-sm text-gray-600">
                        Review issued certificates and manage revocations.
                    </p>
                </div>
                <button
                    @click="openBatchModal"
                    class="inline-flex items-center gap-2 rounded-lg bg-gradient-to-r from-[#2f837d] to-[#26685f] px-5 py-2.5 text-sm font-semibold text-white shadow-md hover:shadow-lg transition-all"
                >
                    <Zap class="h-4 w-4" />
                    <span>Batch Generate</span>
                </button>
            </div>

            <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm">
                <div class="flex flex-wrap items-center gap-4">
                    <div class="relative flex-1 min-w-[180px]">
                        <Search class="absolute left-3 top-2.5 h-4 w-4 text-gray-400" />
                        <input
                            v-model="filters.course_id"
                            type="text"
                            placeholder="Course ID"
                            class="w-full rounded-lg border border-gray-300 py-2 pl-9 pr-3 text-sm focus:border-transparent focus:ring-2 focus:ring-[#2f837d]"
                        />
                    </div>
                    <div class="relative flex-1 min-w-[180px]">
                        <input
                            v-model="filters.session_id"
                            type="text"
                            placeholder="Session ID"
                            class="w-full rounded-lg border border-gray-300 py-2 px-3 text-sm focus:border-transparent focus:ring-2 focus:ring-[#2f837d]"
                        />
                    </div>
                    <div class="relative flex-1 min-w-[180px]">
                        <input
                            v-model="filters.user_id"
                            type="text"
                            placeholder="Student ID"
                            class="w-full rounded-lg border border-gray-300 py-2 px-3 text-sm focus:border-transparent focus:ring-2 focus:ring-[#2f837d]"
                        />
                    </div>
                    <div class="relative flex-1 min-w-[180px]">
                        <select
                            v-model="filters.status"
                            class="w-full rounded-lg border border-gray-300 py-2 px-3 text-sm focus:border-transparent focus:ring-2 focus:ring-[#2f837d]"
                        >
                            <option value="">All Status</option>
                            <option value="valid">Valid</option>
                            <option value="revoked">Revoked</option>
                        </select>
                    </div>
                    <div class="relative flex-1 min-w-[180px]">
                        <input
                            v-model="filters.issued_by"
                            type="text"
                            placeholder="Issued By (User ID)"
                            class="w-full rounded-lg border border-gray-300 py-2 px-3 text-sm focus:border-transparent focus:ring-2 focus:ring-[#2f837d]"
                        />
                    </div>
                    <div class="flex items-center gap-2">
                        <button
                            type="button"
                            @click="fetchCertificates"
                            class="rounded-lg bg-[#2f837d] px-4 py-2 text-sm font-semibold text-white hover:bg-[#266a66]"
                        >
                            Apply Filters
                        </button>
                        <button
                            type="button"
                            @click="resetFilters"
                            class="rounded-lg border border-gray-200 px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50"
                        >
                            Reset
                        </button>
                    </div>
                </div>
            </div>

            <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm">
                <div class="flex items-center justify-between">
                    <h2 class="text-lg font-semibold text-gray-900">
                        Results ({{ certificates.length }})
                    </h2>
                </div>

                <!-- Skeleton Loading State -->
                <div v-if="isLoading" class="mt-4 overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Certificate Code</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Recipient</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Course</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Session</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Status</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Issued At</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Issued By</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            <tr v-for="n in 5" :key="n" :class="n % 2 === 0 ? 'bg-gray-50' : 'bg-white'">
                                <td class="px-4 py-3"><Skeleton variant="text" width="100px" height="16px" /></td>
                                <td class="px-4 py-3"><Skeleton variant="text" width="100px" height="16px" /></td>
                                <td class="px-4 py-3"><Skeleton variant="text" width="120px" height="16px" /></td>
                                <td class="px-4 py-3"><Skeleton variant="text" width="100px" height="16px" /></td>
                                <td class="px-4 py-3"><Skeleton variant="rectangular" width="60px" height="22px" /></td>
                                <td class="px-4 py-3"><Skeleton variant="text" width="80px" height="16px" /></td>
                                <td class="px-4 py-3"><Skeleton variant="text" width="80px" height="16px" /></td>
                                <td class="px-4 py-3"><Skeleton variant="text" width="100px" height="16px" /></td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div v-else-if="certificates.length === 0" class="py-10 text-center text-sm text-gray-500">
                    No certificates found.
                </div>

                <div v-else class="mt-4 overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">
                                    Certificate Code
                                </th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">
                                    Recipient
                                </th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">
                                    Course
                                </th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">
                                    Session
                                </th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">
                                    Status
                                </th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">
                                    Issued At
                                </th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">
                                    Issued By
                                </th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">
                                    Action
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            <tr v-for="certificate in certificates" :key="certificate.id">
                                <td class="px-4 py-3 text-gray-900">
                                    {{ certificate.certificate_code || "—" }}
                                </td>
                                <td class="px-4 py-3 text-gray-900">
                                    {{ certificate.user?.name || "Unknown" }}
                                </td>
                                <td class="px-4 py-3 text-gray-600">
                                    {{ certificate.course?.title || "—" }}
                                </td>
                                <td class="px-4 py-3 text-gray-600">
                                    {{ certificate.session?.title || "—" }}
                                </td>
                                <td class="px-4 py-3">
                                    <span
                                        class="rounded-full px-3 py-1 text-xs font-semibold capitalize"
                                        :class="statusBadgeClass(certificate.status)"
                                    >
                                        {{ certificate.status || "unknown" }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-gray-600">
                                    {{ formatDate(certificate.issued_at) }}
                                </td>
                                <td class="px-4 py-3 text-gray-600">
                                    {{ certificate.issuer?.name || "—" }}
                                </td>
                                <td class="px-4 py-3">
                                    <div class="flex flex-wrap gap-2">
                                        <Link
                                            :href="`/certificates/${certificate.id}`"
                                            class="inline-flex items-center gap-1 rounded-lg border border-gray-200 px-3 py-2 text-xs font-semibold text-gray-700 hover:bg-gray-50"
                                        >
                                            <Eye :size="14" />
                                            View
                                        </Link>
                                        <a
                                            :href="`/api/certificates/${certificate.id}/download`"
                                            class="inline-flex items-center gap-1 rounded-lg border border-emerald-400 px-3 py-2 text-xs font-semibold text-emerald-600 hover:bg-emerald-50"
                                        >
                                            <Download :size="14" />
                                            Download
                                        </a>
                                        <button
                                            type="button"
                                            :disabled="certificate.status === 'revoked' || isRevoking"
                                            @click="revokeCertificate(certificate)"
                                            class="inline-flex items-center gap-2 rounded-lg border border-red-200 px-3 py-2 text-xs font-semibold text-red-600 hover:bg-red-50 disabled:opacity-60"
                                        >
                                            <Trash2 :size="14" />
                                            Revoke
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Batch Generation Modal -->
        <Transition name="modal">
            <div
                v-if="showBatchModal"
                class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4"
                @click="closeBatchModal"
            >
                <div
                    class="bg-white rounded-2xl shadow-xl w-full max-w-2xl overflow-hidden"
                    @click.stop
                >
                    <!-- Modal Header -->
                    <div class="flex items-center justify-between p-6 border-b border-gray-200 bg-gradient-to-r from-[#2f837d] to-[#26685f] text-white">
                        <div class="flex items-center gap-3">
                            <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-white/20">
                                <Zap class="h-5 w-5" />
                            </div>
                            <div>
                                <h2 class="text-xl font-semibold">Batch Certificate Generation</h2>
                                <p class="text-sm text-white/80 mt-0.5">Generate certificates for multiple enrollments at once</p>
                            </div>
                        </div>
                    </div>

                    <!-- Modal Body -->
                    <div class="p-6 space-y-5">
                        <!-- Generation Type -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Generation Type
                            </label>
                            <div class="flex gap-3">
                                <button
                                    @click="batchForm.type = 'session'"
                                    :class="[
                                        'flex-1 px-4 py-3 rounded-lg border-2 text-sm font-semibold transition-all',
                                        batchForm.type === 'session'
                                            ? 'border-[#2f837d] bg-[#2f837d]/5 text-[#2f837d]'
                                            : 'border-gray-200 text-gray-600 hover:border-gray-300'
                                    ]"
                                    :disabled="isBatchGenerating"
                                >
                                    By Session
                                </button>
                                <button
                                    @click="batchForm.type = 'course'"
                                    :class="[
                                        'flex-1 px-4 py-3 rounded-lg border-2 text-sm font-semibold transition-all',
                                        batchForm.type === 'course'
                                            ? 'border-[#2f837d] bg-[#2f837d]/5 text-[#2f837d]'
                                            : 'border-gray-200 text-gray-600 hover:border-gray-300'
                                    ]"
                                    :disabled="isBatchGenerating"
                                >
                                    By Course
                                </button>
                            </div>
                        </div>

                        <!-- Session ID (shown when type = session) -->
                        <div v-if="batchForm.type === 'session'">
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Session ID
                            </label>
                            <input
                                v-model="batchForm.session_id"
                                type="text"
                                placeholder="Enter session ID"
                                class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm focus:border-[#2f837d] focus:outline-none focus:ring-2 focus:ring-[#2f837d]/20"
                                :disabled="isBatchGenerating"
                            />
                            <p class="mt-1 text-xs text-gray-500">
                                Generate certificates for all completed enrollments in this session
                            </p>
                        </div>

                        <!-- Course ID (shown when type = course) -->
                        <div v-if="batchForm.type === 'course'">
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Course ID
                            </label>
                            <input
                                v-model="batchForm.course_id"
                                type="text"
                                placeholder="Enter course ID"
                                class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm focus:border-[#2f837d] focus:outline-none focus:ring-2 focus:ring-[#2f837d]/20"
                                :disabled="isBatchGenerating"
                            />
                            <p class="mt-1 text-xs text-gray-500">
                                Generate certificates for all completed enrollments across all sessions in this course
                            </p>
                        </div>

                        <!-- Language Selection -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Certificate Language
                            </label>
                            <div class="flex gap-3">
                                <button
                                    @click="batchForm.language = 'th'"
                                    :class="[
                                        'flex-1 px-4 py-2.5 rounded-lg border text-sm font-semibold transition-all',
                                        batchForm.language === 'th'
                                            ? 'border-[#2f837d] bg-[#2f837d] text-white'
                                            : 'border-gray-300 text-gray-600 hover:bg-gray-50'
                                    ]"
                                    :disabled="isBatchGenerating"
                                >
                                    ภาษาไทย (Thai)
                                </button>
                                <button
                                    @click="batchForm.language = 'en'"
                                    :class="[
                                        'flex-1 px-4 py-2.5 rounded-lg border text-sm font-semibold transition-all',
                                        batchForm.language === 'en'
                                            ? 'border-[#2f837d] bg-[#2f837d] text-white'
                                            : 'border-gray-300 text-gray-600 hover:bg-gray-50'
                                    ]"
                                    :disabled="isBatchGenerating"
                                >
                                    English
                                </button>
                            </div>
                        </div>

                        <!-- Eager Generation Option -->
                        <div class="flex items-start gap-3 p-4 rounded-lg bg-blue-50 border border-blue-100">
                            <input
                                id="eager-generate"
                                v-model="batchForm.eager_generate"
                                type="checkbox"
                                class="mt-0.5 h-4 w-4 rounded border-gray-300 text-[#2f837d] focus:ring-[#2f837d]"
                                :disabled="isBatchGenerating"
                            />
                            <label for="eager-generate" class="flex-1 text-sm">
                                <span class="font-medium text-gray-900">Generate PDF files immediately</span>
                                <p class="mt-1 text-xs text-gray-600">
                                    If enabled, PDF files will be generated and stored in the database immediately.
                                    Otherwise, PDFs will be generated on-demand when first accessed (recommended for large batches).
                                </p>
                            </label>
                        </div>

                        <!-- Progress Display -->
                        <div v-if="batchProgress" class="space-y-3 p-4 rounded-lg bg-green-50 border border-green-200">
                            <div class="flex items-center justify-between">
                                <span class="text-sm font-medium text-green-900">Generation Complete!</span>
                                <CheckCircle class="h-5 w-5 text-green-600" />
                            </div>
                            <div class="grid grid-cols-3 gap-3 text-center">
                                <div class="p-3 rounded-lg bg-white">
                                    <div class="text-2xl font-bold text-gray-900">{{ batchProgress.total_count }}</div>
                                    <div class="text-xs text-gray-500 mt-1">Total</div>
                                </div>
                                <div class="p-3 rounded-lg bg-white">
                                    <div class="text-2xl font-bold text-green-600">{{ batchProgress.generated_count }}</div>
                                    <div class="text-xs text-gray-500 mt-1">Generated</div>
                                </div>
                                <div class="p-3 rounded-lg bg-white">
                                    <div class="text-2xl font-bold text-red-600">{{ batchProgress.failed_count }}</div>
                                    <div class="text-xs text-gray-500 mt-1">Failed</div>
                                </div>
                            </div>
                            <div class="text-center text-sm text-green-700">
                                Success Rate: <span class="font-semibold">{{ successRate }}%</span>
                            </div>
                        </div>
                    </div>

                    <!-- Modal Footer -->
                    <div class="flex items-center justify-end gap-3 px-6 py-4 border-t border-gray-200 bg-gray-50">
                        <button
                            @click="closeBatchModal"
                            :disabled="isBatchGenerating"
                            class="px-5 py-2.5 rounded-lg border border-gray-300 text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                        >
                            Cancel
                        </button>
                        <button
                            @click="generateBatch"
                            :disabled="isBatchGenerating"
                            class="inline-flex items-center gap-2 px-5 py-2.5 rounded-lg bg-gradient-to-r from-[#2f837d] to-[#26685f] text-sm font-semibold text-white hover:shadow-lg transition-all disabled:opacity-50 disabled:cursor-not-allowed"
                        >
                            <Loader2 v-if="isBatchGenerating" class="h-4 w-4 animate-spin" />
                            <Zap v-else class="h-4 w-4" />
                            <span>{{ isBatchGenerating ? 'Generating...' : 'Generate Certificates' }}</span>
                        </button>
                    </div>
                </div>
            </div>
        </Transition>
    </AdminLayout>
</template>

<style scoped>
.modal-enter-active,
.modal-leave-active {
    transition: opacity 0.3s ease;
}

.modal-enter-from,
.modal-leave-to {
    opacity: 0;
}

.modal-enter-active .bg-white,
.modal-leave-active .bg-white {
    transition: transform 0.3s ease;
}

.modal-enter-from .bg-white,
.modal-leave-to .bg-white {
    transform: scale(0.95);
}
</style>
