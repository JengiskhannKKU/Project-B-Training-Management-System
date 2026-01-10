<script setup>
import { ref, computed, watch, onMounted } from "vue";
import { Head, router } from "@inertiajs/vue3";
import axios from "axios";
import AdminLayout from "@/Layouts/AdminLayout.vue";
import {
    Search,
    Archive,
    BookOpen,
    ListFilterIcon,
    ArrowDownNarrowWide,
    Share,
    ChevronUp,
} from "lucide-vue-next";
import ExportModal from "@/Components/ExportModal.vue";
import FilterDropdown from "@/Components/FilterDropdown.vue";
import SortDropdown from "@/Components/SortDropdown.vue";
import CourseModal from "@/Components/CourseModal.vue";
import CourseRequestRow from "@/Components/Admin/CourseRequestRow.vue";
import ConfirmActionModal from "@/Components/Admin/ConfirmActionModal.vue";
import { useAdminRequests } from "@/composables/useAdminRequests";
import jsPDF from "jspdf";
import "jspdf-autotable";

// Use composable for API logic
const {
    rawRequests,
    isLoading,
    showApiLogin,
    fetchRequests,
    handleStatusChange,
    handleApiLogin: apiLogin,
} = useAdminRequests();

// API Login state
const apiEmail = ref("");
const apiPassword = ref("");
const apiLoginLoading = ref(false);
const apiLoginError = ref("");

// Search, filter, and sort state
const searchQuery = ref("");
const selectedCategory = ref([]);
const selectedStatus = ref([]);
const sortColumn = ref("");
const sortDirection = ref("asc");

// Modal states
const showExportModal = ref(false);
const showCourseModal = ref(false);

// Pagination
const currentPage = ref(1);
const itemsPerPage = ref(10);

// Confirmation modal
const showConfirm = ref(false);
const confirmAction = ref("");
const confirmRequestId = ref(null);

// Dropdown state
const openDropdownId = ref(null);
const dropdownPosition = ref({ top: 0, left: 0 });
const dropdownCleanupFns = ref([]);

// Computed
const categories = computed(() => {
    return [
        ...new Set(
            programRows.value.map((req) => req.category || "General")
        ),
    ];
});

const totalCoursesCount = computed(() => programRows.value.length);

const filteredCourses = computed(() => {
    let result = programRows.value;

    // Filter by search query
    if (searchQuery.value) {
        const query = searchQuery.value.toLowerCase();
        result = result.filter(
            (course) =>
                (course.title || "").toLowerCase().includes(query) ||
                (course.category || "").toLowerCase().includes(query) ||
                (course.requester_email || "").toLowerCase().includes(query)
        );
    }

    // Filter by category
    if (selectedCategory.value.length > 0) {
        result = result.filter(
            (course) => selectedCategory.value.includes(course.category || "General")
        );
    }

    // Filter by status
    if (selectedStatus.value.length > 0) {
        result = result.filter(
            (course) => selectedStatus.value.includes(course.status)
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

const totalResults = computed(() => filteredCourses.value.length);
const totalPages = computed(() =>
    Math.ceil(totalResults.value / itemsPerPage.value)
);

const paginatedCourses = computed(() => {
    const start = (currentPage.value - 1) * itemsPerPage.value;
    const end = start + itemsPerPage.value;
    return filteredCourses.value.slice(start, end);
});

const startResult = computed(() => {
    if (totalResults.value === 0) return 0;
    return (currentPage.value - 1) * itemsPerPage.value + 1;
});

const endResult = computed(() => {
    const end = currentPage.value * itemsPerPage.value;
    return end > totalResults.value ? totalResults.value : end;
});

// Map requests to program rows
const mapRequestToRow = (req) => {
    const payload = req.payload || {};
    const startTime = payload.start_time || payload.startTime || "";
    const endTime = payload.end_time || payload.endTime || "";
    const timeRange =
        startTime || endTime
            ? `${startTime || "--:--"} - ${endTime || "--:--"}`
            : "";

    const enrollmentLimit = payload.enrollment_limit || payload.enrollmentLimit;
    const capacityDisplay =
        enrollmentLimit === "unlimited"
            ? "0/Unlimited"
            : payload.capacity
                ? `0/${payload.capacity}`
                : "";

    const programRequestLink =
        req.target_type === "program"
            ? `/admin/my-courses/${req.id}`
            : null;

    return {
        id: req.id,
        request_id: req.id,
        program_key:
            req.target_type === "program" ? req.target_id || req.id : null,
        parent_program_id:
            req.target_type === "session"
                ? payload.program_id || req.program_id || req.target_id
                : null,
        title:
            (req.target_type === "session"
                ? payload.title || payload.course
                : payload.title || payload.name) ||
            `${req.target_type} ${req.id}`,
        program_label:
            req.target_type === "session"
                ? payload.program_title ||
                  payload.program_name ||
                  payload.program ||
                  (payload.program_id ? `Program #${payload.program_id}` : "")
                : "",
        session_date: payload.date || payload.start_date || "",
        session_time: timeRange,
        session_location: payload.location || "",
        session_capacity: capacityDisplay,
        category: payload.category || "General",
        status: req.status || "pending",
        requester_email: req.requester?.email || "N/A",
        requester_name: req.requester?.name || "N/A",
        created_at: req.created_at,
        target_type: req.target_type,
        action: req.action,
        admin_link: programRequestLink,
    };
};

const programRows = computed(() => {
    const programRequests = rawRequests.value
        .filter((req) => req.target_type === "program")
        .map(mapRequestToRow);
    const sessionRequests = rawRequests.value
        .filter((req) => req.target_type === "session")
        .map(mapRequestToRow);

    const programsWithSessions = programRequests.map((programRow) => {
        const sessions = sessionRequests.filter((session) => {
            if (!session.parent_program_id) {
                return false;
            }
            const parentId = String(session.parent_program_id);

            if (programRow.program_key && programRow.program_key !== programRow.request_id) {
                return parentId === String(programRow.program_key);
            }
            return parentId === String(programRow.request_id);
        });

        // Find the most recent date between program and its sessions
        let mostRecentDate = programRow.created_at;
        if (sessions.length > 0) {
            sessions.forEach(session => {
                if (session.created_at > mostRecentDate) {
                    mostRecentDate = session.created_at;
                }
            });
        }

        return {
            ...programRow,
            sessions,
            most_recent_date: mostRecentDate,
        };
    });

    // Sort by most recent date (program or any of its sessions)
    return programsWithSessions.sort((a, b) => {
        const dateA = new Date(a.most_recent_date || a.created_at);
        const dateB = new Date(b.most_recent_date || b.created_at);
        return dateB - dateA; // Newest first
    });
});

// Functions
const goToPage = (page) => {
    if (page >= 1 && page <= totalPages.value) {
        currentPage.value = page;
    }
};

watch([searchQuery, selectedCategory, selectedStatus], () => {
    currentPage.value = 1;
});

// Close dropdown when clicking outside, scrolling, or resizing
watch(openDropdownId, (newVal, oldVal) => {
    // Cleanup previous event listeners
    if (oldVal && dropdownCleanupFns.value.length > 0) {
        dropdownCleanupFns.value.forEach(fn => fn());
        dropdownCleanupFns.value = [];
    }

    if (newVal) {
        const handleClickOutside = (e) => {
            if (!e.target.closest('.actions-dropdown-trigger') && !e.target.closest('.actions-dropdown-menu')) {
                openDropdownId.value = null;
            }
        };

        const handleScrollOrResize = () => {
            openDropdownId.value = null;
        };

        setTimeout(() => {
            document.addEventListener('click', handleClickOutside);
            window.addEventListener('scroll', handleScrollOrResize, true);
            window.addEventListener('resize', handleScrollOrResize);

            // Store cleanup functions
            dropdownCleanupFns.value = [
                () => document.removeEventListener('click', handleClickOutside),
                () => window.removeEventListener('scroll', handleScrollOrResize, true),
                () => window.removeEventListener('resize', handleScrollOrResize)
            ];
        }, 0);
    }
});

const sort = (column) => {
    if (sortColumn.value === column) {
        sortDirection.value = sortDirection.value === "asc" ? "desc" : "asc";
    } else {
        sortColumn.value = column;
        sortDirection.value = "asc";
    }
};

const exportToCSV = () => {
    const headers = [
        "ID",
        "Course Title",
        "Category",
        "Status",
        "Requester",
    ];
    const csvData = filteredCourses.value.map((course) => [
        course.id,
        course.title,
        course.category,
        course.status,
        course.requester_name,
    ]);

    const csvContent = [
        headers.join(","),
        ...csvData.map((row) => row.join(",")),
    ].join("\n");

    const blob = new Blob([csvContent], { type: "text/csv" });
    const url = window.URL.createObjectURL(blob);
    const a = document.createElement("a");
    a.href = url;
    a.download = "requests.csv";
    a.click();
    window.URL.revokeObjectURL(url);
    showExportModal.value = false;
};

const exportToPDF = () => {
    try {
        const doc = new jsPDF();

        // Add title
        doc.setFontSize(16);
        doc.text('Course Requests Report', 14, 20);

        // Add generation date
        doc.setFontSize(10);
        doc.text(`Generated: ${new Date().toLocaleDateString()}`, 14, 28);

        // Prepare table data with null-safe values
        const headers = [['ID', 'Course Title', 'Category', 'Status', 'Requester']];
        const data = filteredCourses.value.map((course) => [
            course.id ?? '',
            course.title ?? '',
            course.category ?? '',
            course.status ?? '',
            course.requester_name ?? '',
        ]);

        // Generate table
        doc.autoTable({
            head: headers,
            body: data,
            startY: 35,
            theme: 'grid',
            headStyles: { fillColor: [59, 130, 246] },
            styles: { fontSize: 9 },
        });

        // Save the PDF
        doc.save('requests.pdf');
    } catch (error) {
        console.error('Error generating PDF:', error);
        alert('Failed to generate PDF. Please try again.');
    } finally {
        showExportModal.value = false;
    }
};

const handleSort = ({ column, direction }) => {
    sortColumn.value = column;
    sortDirection.value = direction;
};

const resetFilters = () => {
    selectedCategory.value = [];
    selectedStatus.value = [];
};

const resetSort = () => {
    sortColumn.value = "";
    sortDirection.value = "asc";
};

const getCategoryClasses = (category) => {
    const colorMap = {
        Programming: "bg-blue-100 text-blue-800",
        Design: "bg-purple-100 text-purple-800",
        Marketing: "bg-orange-100 text-orange-800",
        Business: "bg-green-100 text-green-800",
        "Data Science": "bg-indigo-100 text-indigo-800",
        Finance: "bg-emerald-100 text-emerald-800",
        Management: "bg-teal-100 text-teal-800",
        Technology: "bg-cyan-100 text-cyan-800",
        General: "bg-gray-100 text-gray-800",
    };
    return colorMap[category] || "bg-gray-100 text-gray-800";
};

const getStatusClasses = (status) => {
    const normalized = (status || "").toLowerCase();
    if (normalized === "approved") {
        return "bg-green-100 text-green-800";
    }
    if (normalized === "pending") {
        return "bg-yellow-100 text-yellow-800";
    }
    if (normalized === "rejected" || normalized === "closed") {
        return "bg-red-100 text-red-800";
    }
    return "bg-gray-100 text-gray-700";
};

const handleCourseModalClose = () => {
    showCourseModal.value = false;
};


const handleCreateProgram = async (payload) => {
    if (!payload) return;

    try {
        await axios.get('/sanctum/csrf-cookie');
        await axios.post('/api/admin/program-requests', {
            action: 'create',
            payload,
        });
        showCourseModal.value = false;
        await fetchRequests();
    } catch (error) {
        console.error('Error creating program:', error);
    }
};


// Dropdown toggle handler
const onToggleDropdown = ({ requestId, event }) => {
    if (openDropdownId.value === requestId) {
        openDropdownId.value = null;
    } else {
        openDropdownId.value = requestId;
        const button = event.target.closest('button');
        const rect = button.getBoundingClientRect();
        const isSmall = button.classList.contains('text-xs');
        const width = isSmall ? 160 : 176; // w-40 = 160px, w-44 = 176px
        const estimatedHeight = 120; // Approximate dropdown height

        // Calculate available space
        const spaceBelow = window.innerHeight - rect.bottom;
        const spaceAbove = rect.top;

        // Determine if dropdown should open upward or downward
        const shouldOpenUpward = spaceBelow < estimatedHeight && spaceAbove > spaceBelow;

        // Calculate vertical position (fixed positioning, so no scroll offset needed)
        let top;
        if (shouldOpenUpward) {
            // Position above the button
            top = rect.top - estimatedHeight;
        } else {
            // Position below the button
            top = rect.bottom;
        }

        // Calculate horizontal position (align to right edge of button)
        let left = rect.right - width;

        // Ensure dropdown doesn't overflow left edge of viewport
        if (left < 10) {
            left = 10;
        }

        // Ensure dropdown doesn't overflow right edge of viewport
        if (left + width > window.innerWidth - 10) {
            left = window.innerWidth - width - 10;
        }

        dropdownPosition.value = { top, left };
    }
};

// Status change handlers
const onStatusChange = ({ requestId, status }) => {
    confirmRequestId.value = requestId;
    confirmAction.value = status;
    showConfirm.value = true;
    openDropdownId.value = null; // Close dropdown when opening confirm modal
};

const onConfirmAction = async (adminNote) => {
    if (!confirmRequestId.value || !confirmAction.value) {
        showConfirm.value = false;
        return;
    }

    await handleStatusChange(confirmRequestId.value, confirmAction.value, adminNote);

    // Trigger refresh of pending count in the layout
    window.dispatchEvent(new CustomEvent('refresh-pending-count'));

    showConfirm.value = false;
    confirmRequestId.value = null;
    confirmAction.value = "";
};

// API Login handler
const handleApiLoginSubmit = async () => {
    apiLoginError.value = "";
    apiLoginLoading.value = true;
    const result = await apiLogin(apiEmail.value, apiPassword.value);
    apiLoginLoading.value = false;

    if (!result.success) {
        apiLoginError.value = result.error;
    }
};

onMounted(() => {
    fetchRequests();
});
</script>

<template>
    <Head title="Program Requests" />
    <AdminLayout>
        <div class="space-y-6">
            <!-- API Login Banner -->
            <div v-if="showApiLogin" class="mb-4 rounded-lg border border-amber-200 bg-amber-50 p-4 text-amber-900 space-y-3">
                <div class="font-semibold">{{ $t('API Auth Required') }}</div>
                <p class="text-sm">{{ $t('API Auth Description') }}</p>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="text-xs text-gray-700">{{ $t('Email') }}</label>
                        <input v-model="apiEmail" type="email" class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2 text-sm" />
                    </div>
                    <div>
                        <label class="text-xs text-gray-700">{{ $t('Password') }}</label>
                        <input v-model="apiPassword" type="password" class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2 text-sm" />
                    </div>
                </div>
                <div class="flex items-center gap-3">
                    <button
                        @click="handleApiLoginSubmit"
                        :disabled="apiLoginLoading"
                        class="inline-flex items-center gap-2 rounded-md bg-[#2f837d] px-4 py-2 text-sm font-semibold text-white hover:bg-[#266a66] disabled:opacity-60"
                    >
                        <span v-if="apiLoginLoading" class="h-4 w-4 animate-spin rounded-full border-2 border-white border-t-transparent"></span>
                        {{ $t('Save API Token') }}
                    </button>
                    <p v-if="apiLoginError" class="text-sm text-red-600">{{ apiLoginError }}</p>
                </div>
            </div>

            <!-- Header -->
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900">{{ $t('Program Requests') }}</h1>
                    <p class="mt-2 text-sm text-gray-600">
                        {{ $t('Manage requests description') }}
                    </p>
                </div>
            </div>

            <!-- Main Content Card -->
            <div class="bg-white rounded-[25px] shadow-sm p-6 border border-[#dfe5ef]">
                <div class="flex items-center gap-3 mb-6">
                    <BookOpen class="h-6 w-6 text-[#2f837d]" />
                    <div>
                        <h2 class="text-xl font-semibold text-gray-900">
                            {{ $t('Program Requests') }} ({{ totalCoursesCount }})
                        </h2>
                        <p class="text-sm text-gray-500">
                            {{ $t('Requests count description') }}
                        </p>
                    </div>
                </div>

                <!-- Search and Controls -->
                <div class="flex flex-col lg:flex-row gap-4 items-start lg:items-center justify-between mb-6">
                    <!-- Search Bar -->
                    <div class="relative w-full lg:max-w-md">
                        <input
                            v-model="searchQuery"
                            type="text"
                            :placeholder="$t('Search requests...')"
                            class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#2f837d] focus:border-transparent"
                        />
                        <Search class="absolute left-3 top-2.5 h-5 w-5 text-gray-400" />
                    </div>

                    <!-- Action Buttons -->
                    <div class="flex flex-row gap-4">
                        <FilterDropdown
                            v-model:selectedDepartment="selectedCategory"
                            v-model:selectedStatus="selectedStatus"
                            :departments="categories"
                            :statusOptions="['Approved', 'Pending', 'Rejected']"
                            departmentLabel="Category"
                            @reset="resetFilters"
                        >
                            <template #trigger>
                                <button
                                    class="rounded-lg border transition-all duration-200 inline-flex gap-2 items-center px-4 py-2"
                                    :class="selectedCategory.length + selectedStatus.length > 0 ? 'bg-[#2f837d]/10 border-[#2f837d] text-[#2f837d]' : 'border-[#d5dde7] hover:bg-gray-50 text-gray-700'"
                                >
                                    <ListFilterIcon class="h-4 w-4" />
                                    <p>
                                        Filter
                                        <span v-if="selectedCategory.length + selectedStatus.length > 0" class="ml-1 font-semibold">
                                            ({{ selectedCategory.length + selectedStatus.length }})
                                        </span>
                                    </p>
                                </button>
                            </template>
                        </FilterDropdown>

                        <SortDropdown
                            :sortColumn="sortColumn"
                            :sortDirection="sortDirection"
                            :sortOptions="[
                                { value: 'id', label: 'ID' },
                                { value: 'title', label: 'Title' },
                                { value: 'category', label: 'Category' },
                                { value: 'status', label: 'Status' },
                            ]"
                            @sort="handleSort"
                            @reset="resetSort"
                        >
                            <template #trigger>
                                <button
                                    class="rounded-lg border transition-all duration-200 inline-flex gap-2 items-center px-4 py-2"
                                    :class="sortColumn ? 'bg-[#2f837d]/10 border-[#2f837d] text-[#2f837d]' : 'border-[#d5dde7] hover:bg-gray-50 text-gray-700'"
                                >
                                    <ArrowDownNarrowWide class="h-4 w-4" />
                                    <p>
                                        Sort
                                        <span v-if="sortColumn" class="ml-1 font-medium text-xs opacity-90">
                                            : {{ sortColumn.charAt(0).toUpperCase() + sortColumn.slice(1) }}
                                        </span>
                                    </p>
                                </button>
                            </template>
                        </SortDropdown>

                        <button
                            @click="showExportModal = true"
                            class="rounded-lg border border-[#d5dde7] inline-flex gap-2 items-center px-4 py-2 hover:bg-gray-50 transition-colors"
                        >
                            <Share class="h-4 w-4" />
                            <p>Export</p>
                        </button>
                    </div>
                </div>

                <!-- Table -->
                <div class="bg-white rounded-[25px] shadow-sm border border-[#dfe5ef] overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th
                                        @click="sort('id')"
                                        class="px-3 sm:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer hover:bg-gray-100"
                                    >
                                        <div class="flex items-center gap-2">
                                            ID
                                            <ChevronUp
                                                v-if="sortColumn === 'id'"
                                                class="h-4 w-4"
                                                :class="{ 'rotate-180': sortDirection === 'desc' }"
                                            />
                                        </div>
                                    </th>
                                    <th
                                        @click="sort('title')"
                                        class="px-3 sm:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer hover:bg-gray-100"
                                    >
                                        <div class="flex items-center gap-2">
                                            Program Title
                                            <ChevronUp
                                                v-if="sortColumn === 'title'"
                                                class="h-4 w-4"
                                                :class="{ 'rotate-180': sortDirection === 'desc' }"
                                            />
                                        </div>
                                    </th>
                                    <th class="px-3 sm:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Type
                                    </th>
                                    <th class="px-3 sm:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Category
                                    </th>
                                    <th class="px-3 sm:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Status
                                    </th>
                                    <th class="px-3 sm:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Requester
                                    </th>
                                    <th class="px-3 sm:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Created
                                    </th>
                                    <th class="px-3 sm:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Actions
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                <CourseRequestRow
                                    v-for="(course, index) in paginatedCourses"
                                    :key="course.id"
                                    :course="course"
                                    :index="index"
                                    :open-dropdown-id="openDropdownId"
                                    :dropdown-position="dropdownPosition"
                                    :get-category-classes="getCategoryClasses"
                                    :get-status-classes="getStatusClasses"
                                    @toggle-dropdown="onToggleDropdown"
                                    @status-change="onStatusChange"
                                />
                            </tbody>
                        </table>
                    </div>

                    <!-- Empty State -->
                    <div v-if="filteredCourses.length === 0" class="text-center py-12">
                        <Archive class="mx-auto h-12 w-12 text-gray-400" />
                        <h3 class="mt-2 text-sm font-medium text-gray-900">
                            No requests found
                        </h3>
                        <p class="mt-1 text-sm text-gray-500">
                            Try adjusting your search or filter criteria.
                        </p>
                    </div>

                    <!-- Pagination -->
                    <div
                        v-if="filteredCourses.length > 0"
                        class="flex flex-col sm:flex-row items-center justify-between gap-4 px-3 sm:px-6 py-4 bg-gray-50 border-t border-gray-200"
                    >
                        <div class="flex items-center gap-2 flex-wrap justify-center">
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
                                            (page >= currentPage - 1 && page <= currentPage + 1)
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
                                        v-else-if="page === currentPage - 2 || page === currentPage + 2"
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

                        <div class="text-sm text-gray-600 text-center sm:text-right">
                            Showing {{ startResult }}-{{ endResult }} of {{ totalResults }} results
                        </div>
                    </div>
                </div>
            </div>

            <!-- Modals -->
            <ExportModal
                :show="showExportModal"
                activeTab="courses"
                dataType="courses"
                @close="showExportModal = false"
                @exportCSV="exportToCSV"
                @exportPDF="exportToPDF"
            />

            <!-- Filter Dropdown replaced Modal -->
            
            <!-- Sort Dropdown replaced Modal -->

            <CourseModal
                :show="showCourseModal"
                uploadUrlPrefix="admin"
                @close="handleCourseModalClose"
                @success="handleCreateProgram"
            />

            <ConfirmActionModal
                :show="showConfirm"
                :action="confirmAction"
                @close="showConfirm = false"
                @confirm="onConfirmAction"
            />
        </div>
    </AdminLayout>
</template>
