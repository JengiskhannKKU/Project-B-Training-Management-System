<script setup>
import { ref, onMounted, computed } from "vue";
import { Head, Link, router } from "@inertiajs/vue3";
import axios from "axios";
import { useToast } from "vue-toastification";
import {
    ArrowLeft,
    CheckCircle,
    XCircle,
    AlertCircle,
    FileText,
    User,
    Calendar,
    Award,
} from "lucide-vue-next";
import TrainerLayout from "@/Layouts/TrainerLayout.vue";
import LoadingSpinner from "@/Components/LoadingSpinner.vue";
import RejectModal from "./Partials/RejectModal.vue";

const props = defineProps({
    id: {
        type: [String, Number],
        required: true,
    },
});

const toast = useToast();

const request = ref(null);
const validation = ref(null);
const attendance = ref(null);
const certificateTemplate = ref(null);
const isLoading = ref(false);
const isApproving = ref(false);
const showRejectModal = ref(false);

const ensureCsrf = () => axios.get("/sanctum/csrf-cookie");

const fetchRequest = async () => {
    isLoading.value = true;
    try {
        const response = await axios.get(
            `/api/trainer/certificate-requests/${props.id}`
        );

        if (response.data?.data) {
            request.value = response.data.data.request;
            validation.value = response.data.data.validation;
            attendance.value = response.data.data.attendance;
            certificateTemplate.value = response.data.data.certificate_template;
        }
    } catch (error) {
        const message =
            error?.response?.data?.message ||
            "Unable to load certificate request.";
        toast.error(message);
        router.visit("/trainer/certificate-requests");
    } finally {
        isLoading.value = false;
    }
};

const approveRequest = async () => {
    if (!canApprove.value) {
        toast.error("This request cannot be approved.");
        return;
    }

    isApproving.value = true;
    try {
        await ensureCsrf();
        const response = await axios.post(
            `/api/trainer/certificate-requests/${props.id}/approve`
        );

        toast.success("Certificate request approved successfully!");
        await fetchRequest(); // Refresh to show updated status
    } catch (error) {
        const message =
            error?.response?.data?.message ||
            error?.response?.data?.errors?.request?.[0] ||
            "Failed to approve certificate request.";
        toast.error(message);
    } finally {
        isApproving.value = false;
    }
};

const handleReject = async (note) => {
    try {
        await ensureCsrf();
        await axios.post(`/api/trainer/certificate-requests/${props.id}/reject`, {
            note,
        });

        toast.success("Certificate request rejected.");
        showRejectModal.value = false;
        await fetchRequest(); // Refresh to show updated status
    } catch (error) {
        const message =
            error?.response?.data?.message ||
            error?.response?.data?.errors?.note?.[0] ||
            "Failed to reject certificate request.";
        toast.error(message);
        throw error; // Let modal handle it
    }
};

const canApprove = computed(() => {
    return (
        request.value?.status === "pending" &&
        validation.value?.is_eligible === true
    );
});

const statusBadgeClass = computed(() => {
    switch (request.value?.status) {
        case "pending":
            return "bg-yellow-100 text-yellow-800";
        case "approved":
            return "bg-green-100 text-green-800";
        case "rejected":
            return "bg-red-100 text-red-800";
        default:
            return "bg-gray-100 text-gray-800";
    }
});

const formatDate = (value) => {
    if (!value) return "—";
    const parsed = new Date(value);
    if (Number.isNaN(parsed.getTime())) {
        return value;
    }
    return parsed.toLocaleDateString("en-US", {
        year: "numeric",
        month: "long",
        day: "numeric",
    });
};

onMounted(fetchRequest);
</script>

<template>
    <Head title="Certificate Request Details" />
    <TrainerLayout>
        <div class="space-y-6">
            <div class="flex items-center gap-4">
                <Link
                    href="/trainer/certificate-requests"
                    class="text-gray-600 hover:text-gray-900"
                >
                    <ArrowLeft class="h-5 w-5" />
                </Link>
                <div>
                    <h1 class="text-2xl font-semibold text-gray-900">
                        Certificate Request #{{ id }}
                    </h1>
                    <p class="text-sm text-gray-600">
                        Review and take action on this certificate request.
                    </p>
                </div>
            </div>

            <LoadingSpinner v-if="isLoading" />

            <div v-else-if="request" class="space-y-6">
                <!-- Status Badge -->
                <div class="flex items-center justify-between">
                    <span
                        :class="statusBadgeClass"
                        class="inline-flex rounded-full px-3 py-1 text-sm font-semibold capitalize"
                    >
                        {{ request.status }}
                    </span>
                    <span class="text-sm text-gray-500">
                        Requested on {{ formatDate(request.created_at) }}
                    </span>
                </div>

                <!-- Validation Warnings -->
                <div
                    v-if="validation && !validation.is_eligible"
                    class="rounded-lg border border-red-200 bg-red-50 p-4"
                >
                    <div class="flex items-start gap-3">
                        <AlertCircle class="h-5 w-5 text-red-600 mt-0.5" />
                        <div>
                            <h3 class="text-sm font-semibold text-red-800">
                                Cannot Approve Request
                            </h3>
                            <ul class="mt-2 space-y-1 text-sm text-red-700">
                                <li
                                    v-for="warning in validation.warnings"
                                    :key="warning"
                                >
                                    {{ warning }}
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>

                <!-- Program/Session Information -->
                <div class="rounded-lg border border-gray-200 bg-white p-6">
                    <h2 class="text-lg font-semibold text-gray-900 mb-4 flex items-center gap-2">
                        <FileText class="h-5 w-5" />
                        {{ request.type === "session" ? "Session" : "Program" }}
                        Information
                    </h2>
                    <dl class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div>
                            <dt class="text-sm font-medium text-gray-500">
                                {{ request.type === "session" ? "Session" : "Program" }}
                                Name
                            </dt>
                            <dd class="mt-1 text-sm text-gray-900">
                                {{
                                    request.type === "session"
                                        ? request.session?.name
                                        : request.program?.name
                                }}
                            </dd>
                        </div>
                        <div v-if="request.type === 'session'">
                            <dt class="text-sm font-medium text-gray-500">Program</dt>
                            <dd class="mt-1 text-sm text-gray-900">
                                {{ request.session?.program?.name || "—" }}
                            </dd>
                        </div>
                        <div v-if="request.session">
                            <dt class="text-sm font-medium text-gray-500">
                                Session Dates
                            </dt>
                            <dd class="mt-1 text-sm text-gray-900">
                                {{ formatDate(request.session?.start_date) }} -
                                {{ formatDate(request.session?.end_date) }}
                            </dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Type</dt>
                            <dd class="mt-1 text-sm text-gray-900 capitalize">
                                {{ request.type }} Certificate
                            </dd>
                        </div>
                    </dl>
                </div>

                <!-- Student Information -->
                <div class="rounded-lg border border-gray-200 bg-white p-6">
                    <h2 class="text-lg font-semibold text-gray-900 mb-4 flex items-center gap-2">
                        <User class="h-5 w-5" />
                        Student Information
                    </h2>
                    <dl class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Name</dt>
                            <dd class="mt-1 text-sm text-gray-900">
                                {{ request.enrollment?.user?.name || "—" }}
                            </dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Email</dt>
                            <dd class="mt-1 text-sm text-gray-900">
                                {{ request.enrollment?.user?.email || "—" }}
                            </dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">
                                Enrollment Status
                            </dt>
                            <dd class="mt-1 text-sm text-gray-900 capitalize">
                                {{ request.enrollment?.status || "—" }}
                            </dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">
                                Completion Date
                            </dt>
                            <dd class="mt-1 text-sm text-gray-900">
                                {{ formatDate(request.enrollment?.completed_at) }}
                            </dd>
                        </div>
                    </dl>
                </div>

                <!-- Completion Details -->
                <div
                    v-if="attendance"
                    class="rounded-lg border border-gray-200 bg-white p-6"
                >
                    <h2 class="text-lg font-semibold text-gray-900 mb-4 flex items-center gap-2">
                        <Calendar class="h-5 w-5" />
                        Attendance Details
                    </h2>
                    <dl class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                        <div>
                            <dt class="text-sm font-medium text-gray-500">
                                Days Attended
                            </dt>
                            <dd class="mt-1 text-sm text-gray-900">
                                {{ attendance.attendance_count }} /
                                {{ attendance.total_days }}
                            </dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">
                                Attendance Rate
                            </dt>
                            <dd class="mt-1 text-sm text-gray-900">
                                {{ attendance.attendance_rate }}%
                            </dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Status</dt>
                            <dd class="mt-1">
                                <span
                                    v-if="attendance.attendance_rate >= 80"
                                    class="inline-flex items-center gap-1 text-sm text-green-700"
                                >
                                    <CheckCircle class="h-4 w-4" />
                                    Meets Requirements
                                </span>
                                <span
                                    v-else
                                    class="inline-flex items-center gap-1 text-sm text-red-700"
                                >
                                    <XCircle class="h-4 w-4" />
                                    Below 80%
                                </span>
                            </dd>
                        </div>
                    </dl>
                </div>

                <!-- Certificate Template -->
                <div
                    v-if="certificateTemplate"
                    class="rounded-lg border border-gray-200 bg-white p-6"
                >
                    <h2 class="text-lg font-semibold text-gray-900 mb-4 flex items-center gap-2">
                        <Award class="h-5 w-5" />
                        Certificate Template
                    </h2>
                    <dl class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div>
                            <dt class="text-sm font-medium text-gray-500">
                                Template Name
                            </dt>
                            <dd class="mt-1 text-sm text-gray-900">
                                {{ certificateTemplate.name }}
                            </dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Scope</dt>
                            <dd class="mt-1 text-sm text-gray-900 capitalize">
                                {{ certificateTemplate.scope }}
                            </dd>
                        </div>
                    </dl>
                </div>

                <!-- Approval Note (if rejected or approved) -->
                <div
                    v-if="request.note && request.status !== 'pending'"
                    class="rounded-lg border border-gray-200 bg-white p-6"
                >
                    <h2 class="text-lg font-semibold text-gray-900 mb-2">
                        {{ request.status === "approved" ? "Approval" : "Rejection" }}
                        Note
                    </h2>
                    <p class="text-sm text-gray-700">{{ request.note }}</p>
                    <p class="mt-2 text-xs text-gray-500">
                        By {{ request.approver?.name || "Admin" }} on
                        {{ formatDate(request.approved_at) }}
                    </p>
                </div>

                <!-- Actions -->
                <div
                    v-if="request.status === 'pending'"
                    class="flex flex-wrap gap-4 border-t border-gray-200 bg-gray-50 p-6 rounded-lg"
                >
                    <button
                        @click="approveRequest"
                        :disabled="!canApprove || isApproving"
                        class="inline-flex items-center gap-2 rounded-lg bg-green-600 px-6 py-3 text-sm font-semibold text-white hover:bg-green-700 disabled:opacity-50 disabled:cursor-not-allowed"
                    >
                        <CheckCircle class="h-5 w-5" />
                        {{ isApproving ? "Approving..." : "Approve Request" }}
                    </button>
                    <button
                        @click="showRejectModal = true"
                        :disabled="isApproving"
                        class="inline-flex items-center gap-2 rounded-lg bg-red-600 px-6 py-3 text-sm font-semibold text-white hover:bg-red-700 disabled:opacity-50"
                    >
                        <XCircle class="h-5 w-5" />
                        Reject Request
                    </button>
                </div>
            </div>
        </div>

        <!-- Reject Modal -->
        <RejectModal
            :show="showRejectModal"
            @close="showRejectModal = false"
            @reject="handleReject"
        />
    </TrainerLayout>
</template>
