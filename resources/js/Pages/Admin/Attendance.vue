<script setup>
import { ref, computed, onMounted, watch } from "vue";
import { Head, Link } from "@inertiajs/vue3";
import axios from "axios";
import { useToast } from "vue-toastification";
import { Calendar, Search, ClipboardCheck } from "lucide-vue-next";
import AdminLayout from "@/Layouts/AdminLayout.vue";
import PageHeader from "@/Components/PageHeader.vue";
import StatusBadge from "@/Components/StatusBadge.vue";
import LoadingSpinner from "@/Components/LoadingSpinner.vue";

const toast = useToast();

const sessions = ref([]);
const programs = ref([]);
const isLoading = ref(false);
const currentPage = ref(1);
const totalPages = ref(1);
const totalResults = ref(0);
const itemsPerPage = ref(10);

const filters = ref({
    program_id: "",
    status: "",
    search: "",
});

const startResult = computed(() => {
    if (totalResults.value === 0) return 0;
    return (currentPage.value - 1) * itemsPerPage.value + 1;
});

const endResult = computed(() => {
    const end = currentPage.value * itemsPerPage.value;
    return end > totalResults.value ? totalResults.value : end;
});

const goToPage = (page) => {
    if (page >= 1 && page <= totalPages.value) {
        currentPage.value = page;
        fetchSessions();
    }
};

const fetchSessions = async () => {
    isLoading.value = true;
    try {
        const params = new URLSearchParams();
        if (filters.value.program_id) params.append("program_id", filters.value.program_id);
        if (filters.value.status) params.append("status", filters.value.status);
        if (filters.value.search) params.append("search", filters.value.search);
        params.append("page", currentPage.value);

        const { data } = await axios.get(`/api/admin/attendance/sessions?${params.toString()}`);

        sessions.value = data?.data?.data || [];
        totalPages.value = data?.data?.last_page || 1;
        currentPage.value = data?.data?.current_page || 1;
        totalResults.value = data?.data?.total || 0;
        itemsPerPage.value = data?.data?.per_page || 10;
    } catch (error) {
        toast.error(error?.response?.data?.message || "Failed to load sessions");
        sessions.value = [];
    } finally {
        isLoading.value = false;
    }
};

const fetchPrograms = async () => {
    try {
        const { data } = await axios.get("/api/programs");
        programs.value = data?.data || [];
    } catch (error) {
        toast.error("Failed to load programs");
    }
};

const applyFilters = () => {
    currentPage.value = 1;
    fetchSessions();
};

const resetFilters = () => {
    filters.value = {
        program_id: "",
        status: "",
        search: "",
    };
    currentPage.value = 1;
    fetchSessions();
};


const formatDate = (value) => {
    if (!value) return "—";
    const parsed = new Date(value);
    if (Number.isNaN(parsed.getTime())) return value;
    return parsed.toLocaleDateString("en-US", {
        year: "numeric",
        month: "short",
        day: "numeric",
    });
};

const getAttendanceRate = (session) => {
    if (!session.enrollments_count || session.enrollments_count === 0) return "—";
    const rate = (session.attendances_count / session.enrollments_count) * 100;
    return `${rate.toFixed(0)}%`;
};

onMounted(() => {
    fetchSessions();
    fetchPrograms();
});
</script>

<template>
    <Head title="Attendance Management" />
    <AdminLayout>
        <div class="space-y-6">
            <PageHeader
                title="Attendance Management"
                description="View and manage attendance for all training sessions."
            />

            <!-- Filters -->
            <div class="rounded-lg border border-gray-200 bg-white p-4">
                <div class="grid grid-cols-1 gap-4 md:grid-cols-4">
                    <div>
                        <label
                            for="program-filter"
                            class="block text-sm font-medium text-gray-700 mb-1"
                        >
                            Program
                        </label>
                        <select
                            id="program-filter"
                            v-model="filters.program_id"
                            class="w-full rounded-lg border-gray-300 shadow-sm focus:border-[#2f837d] focus:ring-[#2f837d]"
                        >
                            <option value="">All Programs</option>
                            <option
                                v-for="program in programs"
                                :key="program.id"
                                :value="program.id"
                            >
                                {{ program.name }}
                            </option>
                        </select>
                    </div>

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
                            <option value="upcoming">Upcoming</option>
                            <option value="open">Open</option>
                            <option value="closed">Closed</option>
                            <option value="completed">Completed</option>
                            <option value="cancelled">Cancelled</option>
                        </select>
                    </div>

                    <div>
                        <label
                            for="search-filter"
                            class="block text-sm font-medium text-gray-700 mb-1"
                        >
                            Search
                        </label>
                        <div class="relative">
                            <input
                                id="search-filter"
                                v-model="filters.search"
                                type="text"
                                placeholder="Session title..."
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

            <!-- Sessions Table -->
            <div class="bg-white rounded-[25px] shadow-sm border border-[#dfe5ef] overflow-hidden">
                <LoadingSpinner v-if="isLoading" />

                <div v-else-if="sessions.length === 0" class="p-8 text-center">
                    <Calendar class="mx-auto h-12 w-12 text-gray-400" />
                    <h3 class="mt-2 text-sm font-semibold text-gray-900">No sessions found</h3>
                    <p class="mt-1 text-sm text-gray-500">
                        No training sessions match your current filters.
                    </p>
                </div>

                <div v-else class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
                                >
                                    Session
                                </th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
                                >
                                    Program
                                </th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
                                >
                                    Date
                                </th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
                                >
                                    Trainer
                                </th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
                                >
                                    Enrolled
                                </th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
                                >
                                    Present
                                </th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
                                >
                                    Rate
                                </th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
                                >
                                    Status
                                </th>
                                <th
                                    class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider"
                                >
                                    Actions
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            <tr
                                v-for="(session, index) in sessions"
                                :key="session.id"
                                :class="[
                                    'transition-colors',
                                    index % 2 === 0 ? 'bg-white' : 'bg-gray-50',
                                    'hover:bg-gray-100'
                                ]"
                            >
                                <td class="px-6 py-4 text-sm font-medium text-gray-900">
                                    {{ session.title }}
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-500">
                                    {{ session.program?.name || "—" }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    {{ formatDate(session.start_date) }}
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-500">
                                    {{ session.trainer?.name || "—" }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    {{ session.enrollments_count || 0 }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    {{ session.attendances_count || 0 }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    {{ getAttendanceRate(session) }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <StatusBadge :status="session.status || 'upcoming'" />
                                </td>
                                <td
                                    class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium"
                                >
                                    <Link
                                        :href="`/admin/${session.program_id}/sessions/${session.id}/attendance`"
                                        class="inline-flex items-center gap-1.5 text-[#2f837d] hover:text-[#266a66] p-1 rounded hover:bg-gray-100 transition-colors"
                                        title="Mark Attendance"
                                    >
                                        <ClipboardCheck class="h-5 w-5" />
                                    </Link>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination and Result Counter -->
                <div
                    v-if="sessions.length > 0"
                    class="flex items-center justify-between px-6 py-4 bg-gray-50 border-t border-gray-200"
                >
                    <!-- Pagination (Left) -->
                    <div class="flex items-center gap-2">
                        <button
                            @click="goToPage(currentPage - 1)"
                            :disabled="currentPage === 1"
                            :class="[
                                'px-3 py-1 rounded border transition-colors',
                                currentPage === 1
                                    ? 'bg-gray-100 text-gray-400 cursor-not-allowed border-gray-200'
                                    : 'bg-white text-gray-700 border-gray-300 hover:bg-gray-50',
                            ]"
                        >
                            Previous
                        </button>

                        <div class="flex items-center gap-1">
                            <template
                                v-for="page in totalPages"
                                :key="page"
                            >
                                <button
                                    v-if="
                                        page === 1 ||
                                        page === totalPages ||
                                        (page >= currentPage - 1 &&
                                            page <= currentPage + 1)
                                    "
                                    @click="goToPage(page)"
                                    :class="[
                                        'px-3 py-1 rounded border transition-colors',
                                        currentPage === page
                                            ? 'bg-[#2f837d] text-white border-[#2f837d]'
                                            : 'bg-white text-gray-700 border-gray-300 hover:bg-gray-50',
                                    ]"
                                >
                                    {{ page }}
                                </button>
                                <span
                                    v-else-if="
                                        page === currentPage - 2 ||
                                        page === currentPage + 2
                                    "
                                    class="px-2 text-gray-500"
                                >
                                    ...
                                </span>
                            </template>
                        </div>

                        <button
                            @click="goToPage(currentPage + 1)"
                            :disabled="currentPage === totalPages"
                            :class="[
                                'px-3 py-1 rounded border transition-colors',
                                currentPage === totalPages
                                    ? 'bg-gray-100 text-gray-400 cursor-not-allowed border-gray-200'
                                    : 'bg-white text-gray-700 border-gray-300 hover:bg-gray-50',
                            ]"
                        >
                            Next
                        </button>
                    </div>

                    <!-- Result Counter (Right) -->
                    <div class="text-sm text-gray-600">
                        Showing {{ startResult }}-{{ endResult }} of
                        {{ totalResults }} results
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
