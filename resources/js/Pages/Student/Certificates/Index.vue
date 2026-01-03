<script setup>
import { ref, onMounted } from "vue";
import { Head, Link } from "@inertiajs/vue3";
import axios from "axios";
import StudentLayout from "@/Layouts/StudentLayout.vue";
import LoadingSpinner from "@/Components/LoadingSpinner.vue";
import { Award, Download, Eye } from "lucide-vue-next";

const certificates = ref([]);
const isLoading = ref(false);

const fetchCertificates = async () => {
    isLoading.value = true;
    try {
        const { data } = await axios.get("/api/me/certificates");
        certificates.value = Array.isArray(data?.data) ? data.data : [];
    } catch (error) {
        certificates.value = [];
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

const getStatusBadge = (status) => {
    const normalized = (status || "").toLowerCase();
    if (normalized === "valid") {
        return "bg-emerald-100 text-emerald-700";
    }
    if (normalized === "revoked") {
        return "bg-rose-100 text-rose-700";
    }
    return "bg-gray-100 text-gray-700";
};

const resolveProgramName = (certificate) => {
    return (
        certificate?.program?.name ||
        certificate?.session?.program?.name ||
        "—"
    );
};

const resolveSessionTitle = (certificate) => {
    return certificate?.session?.title || "—";
};

onMounted(fetchCertificates);
</script>

<template>
    <Head title="My Certificates" />

    <StudentLayout>
        <div class="mx-auto max-w-5xl space-y-6">
            <div>
                <h1 class="text-2xl font-semibold text-gray-900">
                    My Certificates
                </h1>
                <p class="mt-1 text-sm text-gray-500">
                    View and download your certificates.
                </p>
            </div>

            <div v-if="isLoading" class="py-16">
                <LoadingSpinner size="lg" text="Loading certificates..." />
            </div>

            <div
                v-else
                class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm"
            >
                <div class="flex items-center gap-3 mb-6">
                    <div
                        class="flex h-10 w-10 items-center justify-center rounded-full bg-pink-100 text-pink-600"
                    >
                        <Award class="h-5 w-5" />
                    </div>
                    <div>
                        <div class="text-xl font-semibold text-gray-900">
                            Certificates ({{ certificates.length }})
                        </div>
                        <div class="text-sm text-gray-500">
                            Issued after completing sessions.
                        </div>
                    </div>
                </div>

                <div
                    v-if="certificates.length === 0"
                    class="rounded-xl border border-dashed border-gray-200 bg-gray-50 px-6 py-12 text-center"
                >
                    <div class="text-lg font-semibold text-gray-900">
                        No certificates yet
                    </div>
                    <p class="mt-2 text-sm text-gray-500">
                        Complete a session to receive your certificate.
                    </p>
                    <Link
                        href="/me/enrollments"
                        class="mt-4 inline-flex items-center rounded-full border border-emerald-400 px-5 py-2 text-sm font-semibold text-emerald-600 hover:bg-emerald-50"
                    >
                        Go to My Courses
                    </Link>
                </div>

                <div v-else class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500">
                                    Program
                                </th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500">
                                    Session
                                </th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500">
                                    Issued
                                </th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500">
                                    Status
                                </th>
                                <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500">
                                    Actions
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            <tr v-for="certificate in certificates" :key="certificate.id">
                                <td class="px-4 py-3 font-medium text-gray-900">
                                    {{ resolveProgramName(certificate) }}
                                </td>
                                <td class="px-4 py-3 text-gray-600">
                                    {{ resolveSessionTitle(certificate) }}
                                </td>
                                <td class="px-4 py-3 text-gray-600">
                                    {{ formatDate(certificate.issued_at) }}
                                </td>
                                <td class="px-4 py-3">
                                    <span
                                        class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold capitalize"
                                        :class="getStatusBadge(certificate.status)"
                                    >
                                        {{ certificate.status || "unknown" }}
                                    </span>
                                </td>
                                <td class="px-4 py-3">
                                    <div class="flex items-center justify-end gap-2">
                                        <a
                                            :href="`/api/certificates/${certificate.id}/view`"
                                            target="_blank"
                                            rel="noopener"
                                            class="inline-flex items-center gap-1 rounded-full border border-gray-200 px-3 py-1.5 text-xs font-semibold text-gray-700 hover:bg-gray-50"
                                        >
                                            <Eye class="h-4 w-4" />
                                            View
                                        </a>
                                        <a
                                            :href="`/api/certificates/${certificate.id}/download`"
                                            class="inline-flex items-center gap-1 rounded-full border border-emerald-400 px-3 py-1.5 text-xs font-semibold text-emerald-600 hover:bg-emerald-50"
                                        >
                                            <Download class="h-4 w-4" />
                                            Download
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </StudentLayout>
</template>
