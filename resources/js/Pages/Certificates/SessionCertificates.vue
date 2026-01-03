<script setup>
import { ref, computed, onMounted } from "vue";
import { Head, Link, usePage } from "@inertiajs/vue3";
import axios from "axios";
import { useToast } from "vue-toastification";
import { ArrowLeft, Eye } from "lucide-vue-next";
import AdminLayout from "@/Layouts/AdminLayout.vue";
import TrainerLayout from "@/Layouts/TrainerLayout.vue";
import LoadingSpinner from "@/Components/LoadingSpinner.vue";

const props = defineProps({
    sessionId: {
        type: [Number, String],
        required: true,
    },
});

const page = usePage();
const toast = useToast();

const isAdmin = computed(() => page.props.auth?.user?.role?.name === "admin");
const layoutComponent = computed(() =>
    isAdmin.value ? AdminLayout : TrainerLayout
);
const backLink = computed(() =>
    isAdmin.value ? "/admin/attendance" : "/trainer/attendance"
);

const session = ref(null);
const certificates = ref([]);
const isLoading = ref(false);

const sessionStatusBadgeClass = computed(() => {
    const status = (session.value?.status || "").toLowerCase();
    if (status === "upcoming") return "bg-blue-100 text-blue-700";
    if (status === "open") return "bg-teal-100 text-teal-700";
    if (status === "closed") return "bg-amber-100 text-amber-700";
    if (status === "completed") return "bg-purple-100 text-purple-700";
    if (status === "cancelled") return "bg-red-100 text-red-700";
    return "bg-gray-100 text-gray-700";
});

const enrollmentStatusClass = (status) => {
    const normalized = (status || "").toLowerCase();
    if (normalized === "completed") return "bg-green-100 text-green-700";
    if (normalized === "confirmed") return "bg-blue-100 text-blue-700";
    if (normalized === "pending") return "bg-yellow-100 text-yellow-700";
    if (normalized === "cancelled") return "bg-red-100 text-red-700";
    return "bg-gray-100 text-gray-700";
};

const certificateStatusClass = (status) => {
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

const fetchData = async () => {
    isLoading.value = true;
    try {
        const [sessionResponse, certificateResponse] = await Promise.all([
            axios.get(`/api/sessions/${props.sessionId}`),
            axios.get(`/api/sessions/${props.sessionId}/certificates`),
        ]);
        session.value = sessionResponse.data?.data ?? null;
        certificates.value = certificateResponse.data?.data ?? [];
    } catch (error) {
        session.value = null;
        certificates.value = [];
        const message =
            error?.response?.data?.message ||
            "Unable to load session certificates.";
        toast.error(message);
    } finally {
        isLoading.value = false;
    }
};

onMounted(fetchData);
</script>

<template>
    <Head title="Session Certificates" />
    <component :is="layoutComponent">
        <div class="space-y-6">
            <Link
                :href="backLink"
                class="inline-flex items-center gap-2 text-[#2f837d] hover:text-[#266a66] text-sm font-semibold"
            >
                <ArrowLeft :size="18" />
                Back to Sessions
            </Link>

            <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm">
                <div class="flex flex-wrap items-center gap-3">
                    <h1 class="text-2xl font-semibold text-gray-900">
                        Session Certificates
                    </h1>
                    <span
                        class="rounded-full px-3 py-1 text-xs font-semibold capitalize"
                        :class="sessionStatusBadgeClass"
                    >
                        {{ session?.status || "unknown" }}
                    </span>
                </div>
                <p class="mt-2 text-sm text-gray-600">
                    {{ session?.title || "Session" }}
                    <span v-if="session?.program?.name">
                        • {{ session.program.name }}
                    </span>
                </p>
                <p class="mt-1 text-xs text-gray-500">
                    {{ formatDate(session?.start_date) }} -
                    {{ formatDate(session?.end_date) }}
                </p>
            </div>

            <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm">
                <div class="flex items-center justify-between">
                    <h2 class="text-lg font-semibold text-gray-900">
                        Certificates ({{ certificates.length }})
                    </h2>
                </div>

                <div v-if="isLoading" class="py-8">
                    <LoadingSpinner size="lg" text="Loading certificates..." />
                </div>

                <div v-else-if="certificates.length === 0" class="py-10 text-center text-sm text-gray-500">
                    No certificates available for this session.
                </div>

                <div v-else class="mt-4 overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">
                                    Learner
                                </th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">
                                    Enrollment Status
                                </th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">
                                    Certificate Status
                                </th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">
                                    Issued At
                                </th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">
                                    Action
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            <tr v-for="certificate in certificates" :key="certificate.id">
                                <td class="px-4 py-3 text-gray-900">
                                    {{ certificate.user?.name || "Unknown" }}
                                </td>
                                <td class="px-4 py-3">
                                    <span
                                        class="rounded-full px-3 py-1 text-xs font-semibold capitalize"
                                        :class="enrollmentStatusClass(certificate.enrollment?.status)"
                                    >
                                        {{ certificate.enrollment?.status || "N/A" }}
                                    </span>
                                </td>
                                <td class="px-4 py-3">
                                    <span
                                        class="rounded-full px-3 py-1 text-xs font-semibold capitalize"
                                        :class="certificateStatusClass(certificate.status)"
                                    >
                                        {{ certificate.status || "unknown" }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-gray-600">
                                    {{ formatDate(certificate.issued_at) }}
                                </td>
                                <td class="px-4 py-3">
                                    <a
                                        :href="`/certificates/${certificate.id}/view`"
                                        target="_blank"
                                        rel="noopener"
                                        class="inline-flex items-center gap-2 rounded-lg border border-gray-200 px-3 py-2 text-xs font-semibold text-gray-700 hover:bg-gray-50"
                                    >
                                        <Eye :size="14" />
                                        View Certificate
                                    </a>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </component>
</template>
