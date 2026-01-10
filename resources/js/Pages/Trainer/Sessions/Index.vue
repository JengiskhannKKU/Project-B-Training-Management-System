<script setup>
import { ref, watch, onMounted } from "vue";
import { useForm, Link, router } from "@inertiajs/vue3";
import TrainerLayout from "@/Layouts/TrainerLayout.vue";
import Pagination from "@/Components/SimplePagination.vue";
import { useToast } from "vue-toastification";
import axios from "axios";
import {
    Search,
    Filter,
    Calendar,
    MapPin,
    Users,
    Clock,
    MoreHorizontal,
    Eye,
    FileText,
    CheckCircle2,
    XCircle,
    AlertCircle,
    UserCheck
} from "lucide-vue-next";
import StatusBadge from "@/Components/StatusBadge.vue";

const toast = useToast();

const props = defineProps({
    sessions: Object,
    filters: Object,
});

const search = ref(props.filters?.search || "");
const status = ref(props.filters?.status || "");
const date = ref(props.filters?.date || "");
const isLoading = ref(false);
const courses = ref([]);
const sessions = ref({ data: [] });

const filters = ref({
    search: props.filters?.search || "",
    status: props.filters?.status || "",
    course_id: props.filters?.course_id || "",
    date_from: props.filters?.date_from || "",
    date_to: props.filters?.date_to || "",
});

const applyFilters = () => {
    fetchSessions();
};

const resetFilters = () => {
    filters.value = {
        search: "",
        status: "",
        course_id: "",
        date_from: "",
        date_to: "",
    };
    applyFilters();
};

const fetchCourses = async () => {
    try {
        const { data } = await axios.get("/api/courses");
        courses.value = data || [];
    } catch (error) {
        toast.error("Failed to load courses");
    }
};

const fetchSessions = async () => {
    isLoading.value = true;
    try {
        const params = new URLSearchParams();
        if (filters.value.search) params.append("search", filters.value.search);
        if (filters.value.status) params.append("status", filters.value.status);
        if (filters.value.course_id) params.append("course_id", filters.value.course_id);
        if (filters.value.date_from) params.append("date_from", filters.value.date_from);
        if (filters.value.date_to) params.append("date_to", filters.value.date_to);

        const url = params.toString() ? `/api/sessions?${params}` : "/api/sessions";
        const { data } = await axios.get(url);
        sessions.value = { data: data?.data || data || [] };
    } catch (error) {
        toast.error("Failed to load sessions");
        sessions.value = { data: [] };
    } finally {
        isLoading.value = false;
    }
};

watch(filters, () => {
    // Debounce search
}, { deep: true });

onMounted(() => {
    fetchCourses();
    fetchSessions();
});
</script>

<template>
    <TrainerLayout>
        <div class="space-y-6">
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">My Sessions</h1>
                    <p class="text-gray-500 mt-1">Manage your training sessions and attendance</p>
                </div>
                <!-- Actions -->
            </div>

            <!-- Filters -->
            <div class="bg-white p-4 rounded-xl shadow-sm border border-gray-100 space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                    <!-- Search -->
                    <div class="relative">
                        <Search class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 w-4 h-4" />
                        <input
                            v-model="filters.search"
                            type="text"
                            placeholder="Search sessions..."
                            class="w-full pl-9 pr-4 py-2 border border-gray-200 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-transparent text-sm"
                            @keyup.enter="applyFilters"
                        />
                    </div>

                    <!-- Course Filter -->
                    <div>
                        <select
                            v-model="filters.course_id"
                            class="w-full px-3 py-2 border border-gray-200 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-transparent text-sm"
                            @change="applyFilters"
                        >
                            <option value="">All Courses</option>
                            <option v-for="course in courses" :key="course.id" :value="course.id">
                                {{ course.title }}
                            </option>
                        </select>
                    </div>

                    <!-- Status Filter -->
                    <div>
                        <select
                            v-model="filters.status"
                            class="w-full px-3 py-2 border border-gray-200 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-transparent text-sm"
                            @change="applyFilters"
                        >
                            <option value="">All Statuses</option>
                            <option value="upcoming">Upcoming</option>
                            <option value="open">Open</option>
                            <option value="closed">Closed</option>
                            <option value="completed">Completed</option>
                            <option value="cancelled">Cancelled</option>
                        </select>
                    </div>

                    <div class="flex gap-2">
                        <button
                            @click="applyFilters"
                            class="flex-1 bg-teal-600 text-white px-4 py-2 rounded-lg hover:bg-teal-700 text-sm font-medium transition-colors"
                        >
                            Apply
                        </button>
                        <button
                            @click="resetFilters"
                            class="px-4 py-2 border border-gray-200 rounded-lg hover:bg-gray-50 text-gray-600 text-sm font-medium transition-colors"
                        >
                            Reset
                        </button>
                    </div>
                </div>
            </div>

            <!-- Sessions List -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-sm text-left">
                        <thead class="bg-gray-50 text-gray-600 font-medium border-b border-gray-100">
                            <tr>
                                <th class="px-6 py-4">Session Title</th>
                                <th class="px-6 py-4">Course</th>
                                <th class="px-6 py-4">Schedule</th>
                                <th class="px-6 py-4">Location</th>
                                <th class="px-6 py-4">Enrollment</th>
                                <th class="px-6 py-4">Status</th>
                                <th class="px-6 py-4 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <tr v-for="session in sessions.data" :key="session.id" class="hover:bg-gray-50/50 transition-colors">
                                <td class="px-6 py-4 font-medium text-gray-900">
                                    {{ session.title }}
                                </td>
                                <td class="px-6 py-4 text-gray-600">
                                    {{ session.course?.title || "—" }}
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex flex-col gap-1">
                                        <div class="flex items-center text-gray-900">
                                            <Calendar class="w-3.5 h-3.5 mr-1.5 text-gray-400" />
                                            {{ session.start_date }}
                                        </div>
                                        <div class="flex items-center text-gray-500 text-xs">
                                            <Clock class="w-3.5 h-3.5 mr-1.5" />
                                            {{ session.start_time }} - {{ session.end_time }}
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 text-gray-600">
                                    <div class="flex items-center">
                                        <MapPin class="w-3.5 h-3.5 mr-1.5 text-gray-400" />
                                        {{ session.location || "Online" }}
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-2">
                                        <div class="w-full max-w-[80px] h-1.5 bg-gray-100 rounded-full overflow-hidden">
                                            <div 
                                                class="h-full rounded-full bg-teal-500"
                                                :style="{ width: `${Math.min((session.enrollments_count / session.capacity) * 100, 100)}%` }"
                                            ></div>
                                        </div>
                                        <span class="text-xs text-gray-600">{{ session.enrollments_count }}/{{ session.capacity }}</span>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <StatusBadge :status="session.status" />
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <div class="flex items-center justify-end gap-2">
                                        <Link 
                                            :href="`/trainer/${session.course_id}/sessions/${session.id}/attendance`"
                                            class="p-2 hover:bg-teal-50 text-teal-600 rounded-lg transition-colors"
                                            title="Attendance"
                                        >
                                            <UserCheck class="w-4 h-4" />
                                        </Link>
                                        <!-- Add more actions like edit/view details if needed -->
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="!sessions.data.length">
                                <td colspan="7" class="px-6 py-12 text-center text-gray-500">
                                    No sessions found.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <Pagination :links="sessions.links" />
            </div>
        </div>
    </TrainerLayout>
</template>