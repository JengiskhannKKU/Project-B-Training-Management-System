<script setup>
import { ref, computed, watch, onMounted } from "vue";
import { Head, Link } from "@inertiajs/vue3";
import TrainerLayout from "@/Layouts/TrainerLayout.vue";
import axios from "axios";
import { useToast } from "vue-toastification";
import {
    Search,
    Archive,
    Calendar,
    Clock,
    MapPin,
    ChevronUp,
    ListFilterIcon,
    ArrowDownNarrowWide,
    Share,
    CheckCircle,
    XCircle,
    ArrowLeft,
} from "lucide-vue-next";
import ExportModal from "@/Components/ExportModal.vue";
import FilterModal from "@/Components/FilterModal.vue";
import SortModal from "@/Components/SortModal.vue";

// Props from route
const props = defineProps({
    courseId: {
        type: [Number, String],
        required: true
    },
    sessionId: {
        type: [Number, String],
        required: true
    }
});

// Toast
const toast = useToast();

// State for save functionality
const isLoading = ref(true);
const lastAutoSaved = ref(null);

// Data from backend
const sessionInfo = ref({
    id: null,
    title: "",
    program: null,
    start_date: "",
    end_date: "",
    start_time: "",
    end_time: "",
    location: "",
});

const trainees = ref([]);

// Attendance summary from API
const attendanceSummary = ref({
    total: 0,
    present: 0,
    absent: 0,
    late: 0,
    leave_early: 0,
    not_marked: 0,
});

const searchQuery = ref("");
const selectedDepartment = ref("all");
const selectedStatus = ref("all");
const sortColumn = ref("");
const sortDirection = ref("asc");
const showExportModal = ref(false);
const showFilterModal = ref(false);
const showSortModal = ref(false);
const currentPage = ref(1);
const itemsPerPage = ref(10);

// Format phone number to 012-345-6789
const formatPhoneNumber = (phone) => {
    if (!phone) return "";
    const cleaned = phone.replace(/\D/g, "");
    if (cleaned.length === 10) {
        return `${cleaned.slice(0, 3)}-${cleaned.slice(3, 6)}-${cleaned.slice(6)}`;
    }
    return phone;
};

// Get unique departments for filter
const departments = computed(() => {
    return [...new Set(trainees.value.map((trainee) => trainee.department))];
});

// Filtered and sorted trainees
const filteredTrainees = computed(() => {
    let result = trainees.value;

    // Filter by search query
    if (searchQuery.value) {
        const query = searchQuery.value.toLowerCase();
        result = result.filter(
            (trainee) =>
                trainee.name.toLowerCase().includes(query) ||
                trainee.email.toLowerCase().includes(query) ||
                trainee.department.toLowerCase().includes(query) ||
                trainee.contact.includes(query)
        );
    }

    // Filter by department
    if (selectedDepartment.value !== "all") {
        result = result.filter(
            (trainee) => trainee.department === selectedDepartment.value
        );
    }

    // Filter by status
    if (selectedStatus.value !== "all") {
        result = result.filter(
            (trainee) => trainee.status === selectedStatus.value
        );
    }

    // Sort
    if (sortColumn.value) {
        result.sort((a, b) => {
            let aVal = a[sortColumn.value];
            let bVal = b[sortColumn.value];

            if (typeof aVal === "string") {
                aVal = aVal.toLowerCase();
                bVal = bVal.toLowerCase();
            }

            if (sortDirection.value === "asc") {
                return aVal > bVal ? 1 : -1;
            } else {
                return aVal < bVal ? 1 : -1;
            }
        });
    }

    return result;
});

// Pagination
const totalResults = computed(() => filteredTrainees.value.length);
const totalPages = computed(() =>
    Math.ceil(totalResults.value / itemsPerPage.value)
);

const paginatedTrainees = computed(() => {
    const start = (currentPage.value - 1) * itemsPerPage.value;
    const end = start + itemsPerPage.value;
    return filteredTrainees.value.slice(start, end);
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
    }
};

// Reset to first page when filters change
watch([searchQuery, selectedDepartment, selectedStatus], () => {
    currentPage.value = 1;
});

// Auto-save timeout
let autoSaveTimeout = null;

// Set attendance status directly with auto-save
const setAttendanceStatus = (traineeId, status) => {
    const trainee = trainees.value.find((t) => t.id === traineeId);
    if (trainee) {
        trainee.status = status;
        trainee.checked = status === 'present';

        // Trigger auto-save after 500ms debounce
        if (autoSaveTimeout) {
            clearTimeout(autoSaveTimeout);
        }
        autoSaveTimeout = setTimeout(() => {
            autoSaveAttendance();
        }, 500);
    }
};

// Sort table column
const sort = (column) => {
    if (sortColumn.value === column) {
        sortDirection.value = sortDirection.value === "asc" ? "desc" : "asc";
    } else {
        sortColumn.value = column;
        sortDirection.value = "asc";
    }
};

// Export to CSV
const exportToCSV = () => {
    const headers = ["ID", "Name", "Email", "Contact", "Department", "Status"];
    const csvData = filteredTrainees.value.map((trainee) => [
        trainee.id,
        trainee.name,
        trainee.email,
        trainee.contact,
        trainee.department,
        trainee.status,
    ]);

    const csvContent = [
        headers.join(","),
        ...csvData.map((row) => row.join(",")),
    ].join("\n");

    const blob = new Blob([csvContent], { type: "text/csv" });
    const url = window.URL.createObjectURL(blob);
    const a = document.createElement("a");
    a.href = url;
    a.download = "session-attendance.csv";
    a.click();
    window.URL.revokeObjectURL(url);
    showExportModal.value = false;
};

// Export to PDF
const exportToPDF = () => {
    alert("PDF export functionality - requires a PDF library like jsPDF");
    showExportModal.value = false;
};

// Apply filters
const applyFilters = (department, status) => {
    selectedDepartment.value = department;
    selectedStatus.value = status;
    showFilterModal.value = false;
};

// Apply sort
const applySort = (column) => {
    if (sortColumn.value === column) {
        sortDirection.value = sortDirection.value === "asc" ? "desc" : "asc";
    } else {
        sortColumn.value = column;
        sortDirection.value = "asc";
    }
    showSortModal.value = false;
};

// Reset filters
const resetFilters = () => {
    selectedDepartment.value = "all";
    selectedStatus.value = "all";
    showFilterModal.value = false;
};

// Reset sort
const resetSort = () => {
    sortColumn.value = "";
    sortDirection.value = "asc";
    showSortModal.value = false;
};

// Fetch session info
const fetchSessionInfo = async () => {
    try {
        const response = await axios.get(`/api/sessions/${props.sessionId}`);
        sessionInfo.value = response.data.data;
    } catch (error) {
        console.error('Error fetching session info:', error);
        toast.error('Failed to load session information');
    }
};

// Fetch attendance summary
const fetchAttendanceSummary = async () => {
    try {
        const response = await axios.get(`/api/sessions/${props.sessionId}/attendance-summary`);
        attendanceSummary.value = response.data.data;
    } catch (error) {
        console.error('Error fetching attendance summary:', error);
    }
};

// Fetch enrollments and attendance data
const fetchAttendanceData = async () => {
    isLoading.value = true;
    try {
        const [enrollmentsResponse] = await Promise.all([
            axios.get(`/api/sessions/${props.sessionId}/enrollments-for-attendance`),
            fetchAttendanceSummary(),
            fetchSessionInfo()
        ]);

        const enrollments = enrollmentsResponse.data.data;

        // Map enrollments to trainees format
        trainees.value = enrollments.map(enrollment => {
            // Get the latest attendance record if exists (sorted by created_at desc)
            const sortedAttendances = enrollment.attendances?.sort((a, b) =>
                new Date(b.created_at) - new Date(a.created_at)
            );
            const latestAttendance = sortedAttendances?.[0];
            const status = latestAttendance?.status || 'not_marked';

            return {
                id: enrollment.id,
                enrollmentId: enrollment.id,
                name: enrollment.user?.name || 'Unknown',
                email: enrollment.user?.email || '',
                contact: enrollment.user?.phone_number || '',
                department: enrollment.user?.department || 'N/A',
                status: status,
                checked: status === 'present',
                attendanceId: latestAttendance?.id || null,
            };
        });
    } catch (error) {
        console.error('Error fetching attendance data:', error);
        toast.error('Failed to load attendance data');
    } finally {
        isLoading.value = false;
    }
};

// Auto-save attendance (silent, no toast on success)
const autoSaveAttendance = async () => {
    try {
        // Prepare bulk attendance data - only include marked attendances (not 'not_marked')
        const attendanceData = trainees.value
            .filter(trainee => trainee.status !== 'not_marked')
            .map(trainee => ({
                enrollment_id: trainee.enrollmentId,
                status: trainee.status,
            }));

        // Check if there's anything to save
        if (attendanceData.length === 0) {
            return;
        }

        // Send to API (silent save)
        await axios.post(`/api/sessions/${props.sessionId}/attendances/bulk`, {
            items: attendanceData
        });

        // Update last auto-saved time
        lastAutoSaved.value = new Date();

        // Silently refresh summary only
        await fetchAttendanceSummary();
    } catch (error) {
        console.error('Error auto-saving attendance:', error);
        toast.error('Failed to auto-save attendance.');
    }
};

// Load data on mount
onMounted(() => {
    fetchAttendanceData();
});
</script>

<template>
    <Head title="Session Attendance" />
    <TrainerLayout>
        <div class="space-y-6">
            <!-- Go Back Button -->
            <Link
                href="/trainer/attendance"
                class="inline-flex items-center gap-2 text-[#2f837d] hover:text-[#26685f] font-medium transition-colors"
            >
                <ArrowLeft :size="20" />
                <span>Go back to courses</span>
            </Link>

            <!-- Page Header -->
            <div class="flex items-center justify-between flex-wrap gap-4">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900">
                        Session Attendance
                    </h1>
                    <p class="mt-2 text-sm text-gray-600">
                        Track attendance for this training session
                    </p>
                </div>
                <!-- Auto-save indicator -->
                <div v-if="lastAutoSaved" class="text-sm text-gray-600 flex items-center gap-2 bg-green-50 px-4 py-2 rounded-lg border border-green-200">
                    <CheckCircle :size="16" class="text-green-600" />
                    <span class="font-medium">Auto-saved</span>
                </div>
            </div>

            <!-- Loading State -->
            <div v-if="isLoading" class="bg-white rounded-[25px] shadow-sm p-12 border border-[#dfe5ef] text-center">
                <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-[#2f837d] mx-auto mb-4"></div>
                <p class="text-gray-600">Loading attendance data...</p>
            </div>

            <!-- Session Info Card -->
            <div
                v-else
                class="bg-white rounded-[25px] shadow-sm p-6 border border-[#dfe5ef]"
            >
                <div class="flex items-center gap-3 mb-4">
                    <Calendar class="h-6 w-6 text-[#2f837d]" />
                    <h2 class="text-xl font-semibold text-gray-900">
                        {{ sessionInfo.title || 'Untitled Session' }}
                    </h2>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-4">
                    <div>
                        <p class="text-sm text-gray-500 mb-1">Program</p>
                        <p class="text-base font-medium text-gray-900">
                            {{ sessionInfo.program?.name || 'Not found' }}
                        </p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500 mb-1">Date</p>
                        <p class="text-base font-medium text-gray-900 flex items-center gap-2">
                            <Calendar :size="16" class="text-[#2f837d]" />
                            {{ sessionInfo.start_date || 'Not found' }}
                        </p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500 mb-1">Time</p>
                        <p class="text-base font-medium text-gray-900 flex items-center gap-2">
                            <Clock :size="16" class="text-[#2f837d]" />
                            {{ sessionInfo.start_time || 'Not found' }}
                        </p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500 mb-1">Location</p>
                        <p class="text-base font-medium text-gray-900 flex items-center gap-2">
                            <MapPin :size="16" class="text-[#2f837d]" />
                            {{ sessionInfo.location || 'Not found' }}
                        </p>
                    </div>
                </div>

                <!-- Program Description -->
                <div class="pt-4 border-t border-gray-200">
                    <p class="text-sm text-gray-500 mb-2">Description</p>
                    <p class="text-base text-gray-900">
                        {{ sessionInfo.program?.description || 'Not found' }}
                    </p>
                </div>

                <!-- Attendance Summary -->
                <div class="mt-6 pt-6 border-t border-gray-200">
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                        <div class="text-center">
                            <p class="text-2xl font-bold text-gray-900">
                                {{ attendanceSummary.total }}
                            </p>
                            <p class="text-sm text-gray-500">Total Trainees</p>
                        </div>
                        <div class="text-center">
                            <p class="text-2xl font-bold text-green-600">
                                {{ attendanceSummary.present }}
                            </p>
                            <p class="text-sm text-gray-500">Present</p>
                        </div>
                        <div class="text-center">
                            <p class="text-2xl font-bold text-red-600">
                                {{ attendanceSummary.absent }}
                            </p>
                            <p class="text-sm text-gray-500">Absent</p>
                        </div>
                        <div class="text-center">
                            <p class="text-2xl font-bold text-gray-600">
                                {{ attendanceSummary.not_marked }}
                            </p>
                            <p class="text-sm text-gray-500">Not Marked</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Search, Filter, and Export Controls -->
            <div
                class="bg-white rounded-[25px] shadow-sm p-6 border border-[#dfe5ef]"
            >
                <div
                    class="flex flex-col lg:flex-row gap-4 items-start lg:items-center justify-between mb-6"
                >
                    <!-- Left: Search Bar -->
                    <div class="relative w-full lg:max-w-md">
                        <input
                            v-model="searchQuery"
                            type="text"
                            placeholder="Search trainees..."
                            class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#2f837d] focus:border-transparent"
                        />
                        <Search
                            class="absolute left-3 top-2.5 h-5 w-5 text-gray-400"
                        />
                    </div>

                    <!-- Right: Filter, Sort, Export buttons -->
                    <div class="flex flex-row gap-4">
                        <!-- Filter button -->
                        <button
                            @click="showFilterModal = true"
                            class="rounded-lg border border-[#d5dde7] inline-flex gap-2 items-center px-4 py-2 hover:bg-gray-50 transition-colors"
                        >
                            <ListFilterIcon class="h-4 w-4" />
                            <p>Filter</p>
                        </button>

                        <!-- Sort button -->
                        <button
                            @click="showSortModal = true"
                            class="rounded-lg border border-[#d5dde7] inline-flex gap-2 items-center px-4 py-2 hover:bg-gray-50 transition-colors"
                        >
                            <ArrowDownNarrowWide class="h-4 w-4" />
                            <p>Sort</p>
                        </button>

                        <!-- Share/Export button -->
                        <button
                            @click="showExportModal = true"
                            class="rounded-lg border border-[#d5dde7] inline-flex gap-2 items-center px-4 py-2 hover:bg-gray-50 transition-colors"
                        >
                            <Share class="h-4 w-4" />
                            <p>Export</p>
                        </button>
                    </div>
                </div>

                <!-- Trainees Table -->
                <div
                    class="bg-white rounded-[25px] shadow-sm border border-[#dfe5ef] overflow-hidden"
                >
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th
                                        @click="sort('id')"
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer hover:bg-gray-100"
                                    >
                                        <div class="flex items-center gap-2">
                                            ID
                                            <ChevronUp
                                                v-if="sortColumn === 'id'"
                                                class="h-4 w-4"
                                                :class="{
                                                    'rotate-180':
                                                        sortDirection === 'desc',
                                                }"
                                            />
                                        </div>
                                    </th>
                                    <th
                                        @click="sort('name')"
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer hover:bg-gray-100"
                                    >
                                        <div class="flex items-center gap-2">
                                            Name
                                            <ChevronUp
                                                v-if="sortColumn === 'name'"
                                                class="h-4 w-4"
                                                :class="{
                                                    'rotate-180':
                                                        sortDirection === 'desc',
                                                }"
                                            />
                                        </div>
                                    </th>
                                    <th
                                        @click="sort('contact')"
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer hover:bg-gray-100"
                                    >
                                        <div class="flex items-center gap-2">
                                            Contact
                                            <ChevronUp
                                                v-if="sortColumn === 'contact'"
                                                class="h-4 w-4"
                                                :class="{
                                                    'rotate-180':
                                                        sortDirection === 'desc',
                                                }"
                                            />
                                        </div>
                                    </th>
                                    <th
                                        @click="sort('department')"
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer hover:bg-gray-100"
                                    >
                                        <div class="flex items-center gap-2">
                                            Department
                                            <ChevronUp
                                                v-if="sortColumn === 'department'"
                                                class="h-4 w-4"
                                                :class="{
                                                    'rotate-180':
                                                        sortDirection === 'desc',
                                                }"
                                            />
                                        </div>
                                    </th>
                                    <th
                                        @click="sort('status')"
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer hover:bg-gray-100"
                                    >
                                        <div class="flex items-center gap-2">
                                            Status
                                            <ChevronUp
                                                v-if="sortColumn === 'status'"
                                                class="h-4 w-4"
                                                :class="{
                                                    'rotate-180':
                                                        sortDirection === 'desc',
                                                }"
                                            />
                                        </div>
                                    </th>
                                    <th
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
                                    >
                                        Mark Attendance
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                <tr
                                    v-for="(trainee, index) in paginatedTrainees"
                                    :key="trainee.id"
                                    :class="[
                                        'transition-colors',
                                        index % 2 === 0 ? 'bg-white' : 'bg-gray-50',
                                        'hover:bg-gray-100'
                                    ]"
                                >
                                    <td
                                        class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"
                                    >
                                        {{ trainee.id }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center">
                                            <div
                                                class="flex-shrink-0 h-10 w-10 bg-gradient-to-r from-blue-500 to-purple-600 rounded-full flex items-center justify-center text-white font-semibold"
                                            >
                                                {{ trainee.name.charAt(0) }}
                                            </div>
                                            <div class="ml-4">
                                                <div
                                                    class="text-sm font-medium text-gray-900"
                                                >
                                                    {{ trainee.name }}
                                                </div>
                                                <div class="text-sm text-gray-500">
                                                    {{ trainee.email }}
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                    <td
                                        class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"
                                    >
                                        {{ formatPhoneNumber(trainee.contact) }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span>
                                            {{ trainee.department }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span
                                            :class="[
                                                'inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-medium',
                                                trainee.status === 'present'
                                                    ? 'bg-green-100 text-green-800'
                                                    : trainee.status === 'absent'
                                                    ? 'bg-red-100 text-red-800'
                                                    : 'bg-gray-100 text-gray-800'
                                            ]"
                                        >
                                            <component
                                                :is="trainee.status === 'present' ? CheckCircle : XCircle"
                                                :size="14"
                                            />
                                            {{
                                                trainee.status === 'present' ? 'Present' :
                                                trainee.status === 'absent' ? 'Absent' :
                                                'Not Marked'
                                            }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center gap-2">
                                            <!-- Present Button -->
                                            <button
                                                @click="setAttendanceStatus(trainee.id, 'present')"
                                                :class="[
                                                    'px-3 py-1.5 rounded-lg text-xs font-medium transition-all duration-200',
                                                    trainee.status === 'present'
                                                        ? 'bg-green-600 text-white shadow-md'
                                                        : 'bg-gray-100 text-gray-600 hover:bg-green-50 hover:text-green-600'
                                                ]"
                                                title="Mark as present"
                                            >
                                                <div class="flex items-center gap-1">
                                                    <CheckCircle :size="14" />
                                                    <span>Present</span>
                                                </div>
                                            </button>

                                            <!-- Absent Button -->
                                            <button
                                                @click="setAttendanceStatus(trainee.id, 'absent')"
                                                :class="[
                                                    'px-3 py-1.5 rounded-lg text-xs font-medium transition-all duration-200',
                                                    trainee.status === 'absent'
                                                        ? 'bg-red-600 text-white shadow-md'
                                                        : 'bg-gray-100 text-gray-600 hover:bg-red-50 hover:text-red-600'
                                                ]"
                                                title="Mark as absent"
                                            >
                                                <div class="flex items-center gap-1">
                                                    <XCircle :size="14" />
                                                    <span>Absent</span>
                                                </div>
                                            </button>

                                            <!-- Not Marked Button -->
                                            <button
                                                @click="setAttendanceStatus(trainee.id, 'not_marked')"
                                                :class="[
                                                    'px-3 py-1.5 rounded-lg text-xs font-medium transition-all duration-200',
                                                    trainee.status === 'not_marked'
                                                        ? 'bg-gray-600 text-white shadow-md'
                                                        : 'bg-gray-100 text-gray-600 hover:bg-gray-200'
                                                ]"
                                                title="Clear marking"
                                            >
                                                <span>Clear</span>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Empty State -->
                    <div
                        v-if="filteredTrainees.length === 0"
                        class="text-center py-12"
                    >
                        <Archive class="mx-auto h-12 w-12 text-gray-400" />
                        <h3 class="mt-2 text-sm font-medium text-gray-900">
                            No trainees found
                        </h3>
                        <p class="mt-1 text-sm text-gray-500">
                            Try adjusting your search or filter criteria.
                        </p>
                    </div>

                    <!-- Pagination and Result Counter -->
                    <div
                        v-if="filteredTrainees.length > 0"
                        class="flex flex-col sm:flex-row items-center justify-between gap-4 p-4 border-t border-gray-200"
                    >
                        <!-- Pagination (Left) -->
                        <div
                            class="flex items-center gap-2 flex-wrap justify-center"
                        >
                            <button
                                @click="goToPage(currentPage - 1)"
                                :disabled="currentPage === 1"
                                :class="[
                                    'px-3 py-1 rounded border transition-colors text-sm',
                                    currentPage === 1
                                        ? 'bg-gray-100 text-gray-400 cursor-not-allowed border-gray-200'
                                        : 'bg-white text-gray-700 border-gray-300 hover:bg-gray-50',
                                ]"
                            >
                                Previous
                            </button>

                            <div class="flex items-center gap-1">
                                <template v-for="page in totalPages" :key="page">
                                    <button
                                        v-if="
                                            page === 1 ||
                                            page === totalPages ||
                                            (page >= currentPage - 1 &&
                                                page <= currentPage + 1)
                                        "
                                        @click="goToPage(page)"
                                        :class="[
                                            'px-3 py-1 rounded border transition-colors text-sm',
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
                                        class="px-2 text-gray-500 text-sm"
                                    >
                                        ...
                                    </span>
                                </template>
                            </div>

                            <button
                                @click="goToPage(currentPage + 1)"
                                :disabled="currentPage === totalPages"
                                :class="[
                                    'px-3 py-1 rounded border transition-colors text-sm',
                                    currentPage === totalPages
                                        ? 'bg-gray-100 text-gray-400 cursor-not-allowed border-gray-200'
                                        : 'bg-white text-gray-700 border-gray-300 hover:bg-gray-50',
                                ]"
                            >
                                Next
                            </button>
                        </div>

                        <!-- Result Counter (Right) -->
                        <div class="text-sm text-gray-600 text-center sm:text-right">
                            Showing {{ startResult }}-{{ endResult }} of
                            {{ totalResults }} results
                        </div>
                    </div>
                </div>
            </div>

            <!-- Modals -->
            <ExportModal
                :show="showExportModal"
                activeTab="trainees"
                dataType="attendance"
                @close="showExportModal = false"
                @exportCSV="exportToCSV"
                @exportPDF="exportToPDF"
            />

            <FilterModal
                :show="showFilterModal"
                title="Filter Trainees"
                v-model:selectedDepartment="selectedDepartment"
                v-model:selectedStatus="selectedStatus"
                :departments="departments"
                :statusOptions="['present', 'absent', 'not_marked']"
                departmentLabel="Department"
                @close="showFilterModal = false"
                @reset="resetFilters"
            />

            <SortModal
                :show="showSortModal"
                title="Sort Trainees"
                :sortColumn="sortColumn"
                :sortDirection="sortDirection"
                :sortOptions="[
                    {
                        value: 'name',
                        label: 'Name',
                        directionLabels: { asc: 'A-Z', desc: 'Z-A' },
                    },
                    {
                        value: 'id',
                        label: 'ID',
                        directionLabels: {
                            asc: 'Low to High',
                            desc: 'High to Low',
                        },
                    },
                    {
                        value: 'department',
                        label: 'Department',
                        directionLabels: { asc: 'A-Z', desc: 'Z-A' },
                    },
                    {
                        value: 'status',
                        label: 'Status',
                        directionLabels: {
                            asc: 'Present First',
                            desc: 'Absent First',
                        },
                    },
                ]"
                @close="showSortModal = false"
                @sort="applySort"
                @reset="resetSort"
            />
        </div>
    </TrainerLayout>
</template>
