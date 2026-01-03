<script setup>
import { ref, onMounted } from "vue";
import { Head } from "@inertiajs/vue3";
import axios from "axios";
import { useToast } from "vue-toastification";
import { Eye, Trash2, Search } from "lucide-vue-next";
import AdminLayout from "@/Layouts/AdminLayout.vue";
import LoadingSpinner from "@/Components/LoadingSpinner.vue";
import ConfirmationDialog from "@/Components/ConfirmationDialog.vue";

const toast = useToast();

const certificates = ref([]);
const isLoading = ref(false);
const showRevokeModal = ref(false);
const revokeTarget = ref(null);
const isRevoking = ref(false);

const filters = ref({
    program_id: "",
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
        program_id: "",
        session_id: "",
        user_id: "",
        status: "",
        issued_by: "",
    };
    await fetchCertificates();
};

const openRevokeModal = (certificate) => {
    revokeTarget.value = certificate;
    showRevokeModal.value = true;
};

const revokeCertificate = async () => {
    if (!revokeTarget.value) {
        return;
    }

    isRevoking.value = true;
    try {
        await ensureCsrf();
        await axios.post(`/api/admin/certificates/${revokeTarget.value.id}/revoke`);
        toast.success("Certificate revoked.");
        revokeTarget.value.status = "revoked";
        showRevokeModal.value = false;
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
            <div>
                <h1 class="text-2xl font-semibold text-gray-900">Certificates</h1>
                <p class="text-sm text-gray-600">
                    Review issued certificates and manage revocations.
                </p>
            </div>

            <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm">
                <div class="flex flex-wrap items-center gap-4">
                    <div class="relative flex-1 min-w-[180px]">
                        <Search class="absolute left-3 top-2.5 h-4 w-4 text-gray-400" />
                        <input
                            v-model="filters.program_id"
                            type="text"
                            placeholder="Program ID"
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

                <div v-if="isLoading" class="py-10">
                    <LoadingSpinner size="lg" text="Loading certificates..." />
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
                                    Program
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
                                    {{ certificate.program?.name || "—" }}
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
                                        <a
                                            :href="`/api/certificates/${certificate.id}/view`"
                                            target="_blank"
                                            rel="noopener"
                                            class="inline-flex items-center gap-2 rounded-lg border border-gray-200 px-3 py-2 text-xs font-semibold text-gray-700 hover:bg-gray-50"
                                        >
                                            <Eye :size="14" />
                                            View
                                        </a>
                                        <button
                                            type="button"
                                            :disabled="certificate.status === 'revoked' || isRevoking"
                                            @click="openRevokeModal(certificate)"
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

            <ConfirmationDialog
                :show="showRevokeModal"
                title="Revoke Certificate"
                message="Are you sure you want to revoke this certificate? This action cannot be undone."
                confirmText="Revoke"
                confirmButtonClass="bg-red-600 hover:bg-red-700"
                @confirm="revokeCertificate"
                @close="showRevokeModal = false"
                @cancel="showRevokeModal = false"
            />
        </div>
    </AdminLayout>
</template>
