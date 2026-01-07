<script setup>
import { ref, onMounted } from "vue";
import { Link } from "@inertiajs/vue3";
import axios from "axios";
import { useToast } from "vue-toastification";
import { FileText, Search } from "lucide-vue-next";
import LoadingSpinner from "@/Components/LoadingSpinner.vue";
import StatusBadge from "@/Components/StatusBadge.vue";
import SimplePagination from "@/Components/SimplePagination.vue";
import PageHeader from "@/Components/PageHeader.vue";

const props = defineProps({
    apiEndpoint: { type: String, required: true },
    detailsRoute: { type: String, required: true },
    showTrainerColumn: { type: Boolean, default: false },
});

const toast = useToast();

const requests = ref([]);
const isLoading = ref(false);
const filters = ref({
    status: "",
    type: "",
    search: "",
});
const currentPage = ref(1);
const totalPages = ref(1);
const perPage = ref(15);

const fetchRequests = async () => {
    isLoading.value = true;
    try {
        const params = new URLSearchParams();
        if (filters.value.status) params.append("status", filters.value.status);
        if (filters.value.type) params.append("type", filters.value.type);
        if (filters.value.search) params.append("search", filters.value.search);
        params.append("page", currentPage.value);
        params.append("per_page", perPage.value);

        const response = await axios.get(`${props.apiEndpoint}?${params.toString()}`);

        if (response.data?.data?.data) {
            requests.value = response.data.data.data;
            totalPages.value = response.data.data.last_page || 1;
            currentPage.value = response.data.data.current_page || 1;
        } else {
            requests.value = [];
        }
    } catch (error) {
        requests.value = [];
        const message =
            error?.response?.data?.message ||
            "Unable to load certificate requests.";
        toast.error(message);
    } finally {
        isLoading.value = false;
    }
};

const applyFilters = () => {
    currentPage.value = 1;
    fetchRequests();
};

const resetFilters = () => {
    filters.value = {
        status: "",
        type: "",
        search: "",
    };
    currentPage.value = 1;
    fetchRequests();
};

const changePage = (page) => {
    if (page >= 1 && page <= totalPages.value) {
        currentPage.value = page;
        fetchRequests();
    }
};

const formatDate = (value) => {
    if (!value) return "—";
    const parsed = new Date(value);
    if (Number.isNaN(parsed.getTime())) {
        return value;
    }
    return parsed.toLocaleDateString("en-US", {
        year: "numeric",
        month: "short",
        day: "numeric",
    });
};

const getStudentName = (request) => {
    return request?.enrollment?.user?.name || "—";
};

const getTrainerName = (request) => {
    if (request.type === "session") {
        return request?.session?.trainer?.name || "—";
    }
    return request?.program?.creator?.name || "—";
};

const getSessionOrProgramName = (request) => {
    if (request.type === "session") {
        return request?.session?.name || request?.session?.program?.name || "—";
    }
    return request?.program?.name || "—";
};

const getDetailsUrl = (requestId) => {
    return route(props.detailsRoute, requestId);
};

onMounted(fetchRequests);
</script>

<template>
    <div class="space-y-6">
        <PageHeader
            title="Certificate Requests"
            description="Review and manage all certificate requests across the system."
        />

        <!-- Filters -->
        <div class="rounded-lg border border-gray-200 bg-white p-4">
            <div class="grid grid-cols-1 gap-4 md:grid-cols-4">
                <div>
                    <label
                        for="status-filter"
                        class="block text-sm font-medium text-gray-700 mb-1"
                    >
                        Status
                    </label>
                    <select
                        id="status-filter"
                        v-model="filters.status"
                        class="w-full rounded-lg border-gray-300 shadow-sm focus:border-[#2f837d] focus:ring-[#2f837d]"
                    >
                        <option value="">All Statuses</option>
                        <option value="pending">Pending</option>
                        <option value="approved">Approved</option>
                        <option value="rejected">Rejected</option>
                    </select>
                </div>

                <div>
                    <label
                        for="type-filter"
                        class="block text-sm font-medium text-gray-700 mb-1"
                    >
                        Type
                    </label>
                    <select
                        id="type-filter"
                        v-model="filters.type"
                        class="w-full rounded-lg border-gray-300 shadow-sm focus:border-[#2f837d] focus:ring-[#2f837d]"
                    >
                        <option value="">All Types</option>
                        <option value="session">Session</option>
                        <option value="program">Program</option>
                    </select>
                </div>

                <div>
                    <label
                        for="search-filter"
                        class="block text-sm font-medium text-gray-700 mb-1"
                    >
                        Search Student
                    </label>
                    <div class="relative">
                        <input
                            id="search-filter"
                            v-model="filters.search"
                            type="text"
                            placeholder="Student name..."
                            class="w-full rounded-lg border-gray-300 pl-10 shadow-sm focus:border-[#2f837d] focus:ring-[#2f837d]"
                            @keyup.enter="applyFilters"
                        />
                        <Search
                            class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400"
                        />
                    </div>
                </div>

                <div class="flex items-end gap-2">
                    <button
                        @click="applyFilters"
                        class="flex-1 rounded-lg bg-[#2f837d] px-4 py-2 text-sm font-semibold text-white hover:bg-[#266a66]"
                    >
                        Apply
                    </button>
                    <button
                        @click="resetFilters"
                        class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50"
                    >
                        Reset
                    </button>
                </div>
            </div>
        </div>

        <!-- Table -->
        <div class="rounded-lg border border-gray-200 bg-white overflow-hidden">
            <LoadingSpinner v-if="isLoading" />

            <div v-else-if="requests.length === 0" class="p-8 text-center">
                <FileText class="mx-auto h-12 w-12 text-gray-400" />
                <h3 class="mt-2 text-sm font-semibold text-gray-900">
                    No certificate requests
                </h3>
                <p class="mt-1 text-sm text-gray-500">
                    Certificate requests will appear here.
                </p>
            </div>

            <div v-else class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
                            >
                                ID
                            </th>
                            <th
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
                            >
                                Student
                            </th>
                            <th
                                v-if="showTrainerColumn"
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
                            >
                                Trainer
                            </th>
                            <th
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
                            >
                                Type
                            </th>
                            <th
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
                            >
                                Program/Session
                            </th>
                            <th
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
                            >
                                Status
                            </th>
                            <th
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
                            >
                                Requested
                            </th>
                            <th
                                class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider"
                            >
                                Actions
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <tr
                            v-for="request in requests"
                            :key="request.id"
                            class="hover:bg-gray-50"
                        >
                            <td
                                class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900"
                            >
                                #{{ request.id }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                {{ getStudentName(request) }}
                            </td>
                            <td
                                v-if="showTrainerColumn"
                                class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"
                            >
                                {{ getTrainerName(request) }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                <span class="capitalize">{{ request.type }}</span>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-900">
                                {{ getSessionOrProgramName(request) }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <StatusBadge :status="request.status" />
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                {{ formatDate(request.created_at) }}
                            </td>
                            <td
                                class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium"
                            >
                                <Link
                                    :href="getDetailsUrl(request.id)"
                                    class="text-[#2f837d] hover:text-[#266a66]"
                                >
                                    View Details
                                </Link>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <SimplePagination
                v-if="requests.length > 0"
                :currentPage="currentPage"
                :totalPages="totalPages"
                @change="changePage"
            />
        </div>
    </div>
</template>
