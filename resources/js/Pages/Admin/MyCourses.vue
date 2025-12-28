<script setup>
import { ref, computed, watch, onMounted } from "vue";
import { Head, router } from "@inertiajs/vue3";
import axios from "axios";
import { useToast } from "vue-toastification";
import AdminLayout from "@/Layouts/AdminLayout.vue";
import {
    Search,
    Archive,
    BookOpen,
    ListFilterIcon,
    ArrowDownNarrowWide,
    Share,
    ChevronUp,
    ChevronDown,
    Pencil,
    Trash2,
    Plus,
} from "lucide-vue-next";
import ExportModal from "@/Components/ExportModal.vue";
import FilterModal from "@/Components/FilterModal.vue";
import SortModal from "@/Components/SortModal.vue";

const toast = useToast();
const rawRequests = ref([]);
const isLoading = ref(false);
const actionNote = ref("");
const showApiLogin = ref(false);
const apiEmail = ref("");
const apiPassword = ref("");
const apiLoginLoading = ref(false);
const apiLoginError = ref("");

const searchQuery = ref("");
const selectedCategory = ref("all");
const selectedStatus = ref("all");
const sortColumn = ref("");
const sortDirection = ref("asc");
const showExportModal = ref(false);
const showFilterModal = ref(false);
const showSortModal = ref(false);
const currentPage = ref(1);
const itemsPerPage = ref(10);
const openStatusDropdown = ref(null);
const dropdownPosition = ref({ top: 0, left: 0 });
const expandedPrograms = ref(new Set());

// Available options for dropdowns
const statusOptions = ["approved", "pending", "rejected"];
const categories = computed(() => {
    return [
        ...new Set(
            programRows.value.map((req) => req.category || "General")
        ),
    ];
});

// Count courses by status
const activeCoursesCount = computed(() => {
    return programRows.value.filter((course) => course.status === "approved")
        .length;
});

const totalCoursesCount = computed(() => {
    return programRows.value.length;
});

// Filtered and sorted courses
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
    if (selectedCategory.value !== "all") {
        result = result.filter(
            (course) => course.category === selectedCategory.value
        );
    }

    // Filter by status
    if (selectedStatus.value !== "all") {
        result = result.filter(
            (course) => course.status === selectedStatus.value
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

const goToPage = (page) => {
    if (page >= 1 && page <= totalPages.value) {
        currentPage.value = page;
    }
};

// Reset to first page when filters change
watch([searchQuery, selectedCategory, selectedStatus], () => {
    currentPage.value = 1;
});

// Close dropdown when clicking outside
watch(openStatusDropdown, (newVal) => {
    if (newVal) {
        const handleClickOutside = (e) => {
            if (
                !e.target.closest(".status-dropdown-trigger") &&
                !e.target.closest(".status-dropdown-menu")
            ) {
                openStatusDropdown.value = null;
                document.removeEventListener("click", handleClickOutside);
            }
        };
        setTimeout(() => {
            document.addEventListener("click", handleClickOutside);
        }, 0);
    }
});

// Sort function
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
    const headers = [
        "ID",
        "Course Title",
        "Category",
        "Status",
        "Instructor",
        "Students",
    ];
    const csvData = filteredCourses.value.map((course) => [
        course.id,
        course.title,
        course.category,
        course.status,
        course.instructor,
        course.students,
    ]);

    const csvContent = [
        headers.join(","),
        ...csvData.map((row) => row.join(",")),
    ].join("\n");

    const blob = new Blob([csvContent], { type: "text/csv" });
    const url = window.URL.createObjectURL(blob);
    const a = document.createElement("a");
    a.href = url;
    a.download = "courses.csv";
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
const applyFilters = (category, status) => {
    selectedCategory.value = category;
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
    selectedCategory.value = "all";
    selectedStatus.value = "all";
    showFilterModal.value = false;
};

// Reset sort
const resetSort = () => {
    sortColumn.value = "";
    sortDirection.value = "asc";
    showSortModal.value = false;
};

// Toggle status dropdown
const toggleStatusDropdown = (courseId, event) => {
    if (openStatusDropdown.value === courseId) {
        openStatusDropdown.value = null;
    } else {
        openStatusDropdown.value = courseId;
        const rect = event.target.getBoundingClientRect();
        dropdownPosition.value = {
            top: rect.bottom + window.scrollY,
            left: rect.left + window.scrollX,
        };
    }
};

const toggleSessions = (courseId) => {
    const next = new Set(expandedPrograms.value);
    if (next.has(courseId)) {
        next.delete(courseId);
    } else {
        next.add(courseId);
    }
    expandedPrograms.value = next;
};

const isProgramExpanded = (courseId) => expandedPrograms.value.has(courseId);

// Change course status
const changeCourseStatus = (courseId, newStatus) => {
    const course = rawRequests.value.find((c) => c.id === courseId);
    if (course) {
        course.status = newStatus;
    }
    openStatusDropdown.value = null;
};

// Handle actions
const openCreateModal = () => {
    toast.info("Create course from admin not implemented yet.");
};

const editCourse = (courseId) => {
    router.visit(`/admin/my-courses/${courseId}`);
};

const deleteCourse = (courseId) => {
    if (confirm("Are you sure you want to delete this course?")) {
        const index = rawRequests.value.findIndex((c) => c.id === courseId);
        if (index !== -1) {
            rawRequests.value.splice(index, 1);
        }
    }
};

// Get category color classes
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
        link:
            req.target_type === "session"
                ? `/trainer/programs/${payload.program_id || req.program_id || req.target_id || req.id}`
                : `/trainer/programs/${req.id}`,
        // Link for admin view - always use the request ID for correct lookup
        admin_link: `/admin/my-courses/${req.id}`,
    };
};

const programRows = computed(() => {
    const programRequests = rawRequests.value
        .filter((req) => req.target_type === "program")
        .map(mapRequestToRow);
    const sessionRequests = rawRequests.value
        .filter((req) => req.target_type === "session")
        .map(mapRequestToRow);

    return programRequests.map((programRow) => {
        // Match sessions to programs:
        // - If program has target_id (approved), only match sessions whose parent_program_id equals target_id
        // - If program has no target_id (pending), match sessions whose parent_program_id equals request_id
        // This prevents collisions where Program.id of one program equals AdminRequest.id of another
        const sessions = sessionRequests.filter((session) => {
            if (!session.parent_program_id) {
                return false;
            }
            const parentId = String(session.parent_program_id);
            
            // If program has been approved (has target_id), only match if session references that actual Program.id
            if (programRow.program_key && programRow.program_key !== programRow.request_id) {
                return parentId === String(programRow.program_key);
            }
            // If program hasn't been approved yet, match by request_id
            return parentId === String(programRow.request_id);
        });

        return {
            ...programRow,
            sessions,
        };
    });
});

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

const ensureCsrf = () => axios.get("/sanctum/csrf-cookie");

const fetchRequests = async () => {
    isLoading.value = true;
    try {
        await ensureCsrf();
        const { data } = await axios.get("/api/admin/requests");
        const list = data?.data || data || [];
        rawRequests.value = list;
    } catch (error) {
        if ([401, 403, 419].includes(error?.response?.status)) {
            showApiLogin.value = true;
        }
        toast.error(
            error?.response?.data?.message ||
                error?.message ||
                "Unable to load requests."
        );
    } finally {
        isLoading.value = false;
    }
};

const approveRequest = async (id) => {
    try {
        await ensureCsrf();
        await axios.post(`/api/admin/requests/${id}/approve`, {
            admin_note: actionNote.value || null,
        });
        toast.success("Request approved.");
        await fetchRequests();
    } catch (error) {
        if ([401, 403, 419].includes(error?.response?.status)) {
            showApiLogin.value = true;
        }
        toast.error(
            error?.response?.data?.message ||
                error?.message ||
                "Unable to approve request."
        );
    }
};

const rejectRequest = async (id) => {
    try {
        await ensureCsrf();
        await axios.post(`/api/admin/requests/${id}/reject`, {
            admin_note: actionNote.value || null,
        });
        toast.success("Request rejected.");
        await fetchRequests();
    } catch (error) {
        if ([401, 403, 419].includes(error?.response?.status)) {
            showApiLogin.value = true;
        }
        toast.error(
            error?.response?.data?.message ||
                error?.message ||
                "Unable to reject request."
        );
    }
};

const showConfirm = ref(false);
const confirmAction = ref("");
const confirmRequestId = ref(null);

const openConfirm = (id, action) => {
    confirmRequestId.value = id;
    confirmAction.value = action;
    showConfirm.value = true;
};

const performConfirmedAction = () => {
    if (!confirmRequestId.value || !confirmAction.value) {
        showConfirm.value = false;
        return;
    }
    const id = confirmRequestId.value;
    const action = confirmAction.value;
    showConfirm.value = false;
    confirmRequestId.value = null;
    confirmAction.value = "";

    if (action === "approve") {
        approveRequest(id);
    } else if (action === "reject") {
        rejectRequest(id);
    }
};

const setBearerToken = (token) => {
    localStorage.setItem("api_token", token);
    axios.defaults.headers.common.Authorization = `Bearer ${token}`;
};

const handleApiLogin = async () => {
    apiLoginError.value = "";
    apiLoginLoading.value = true;
    try {
        await ensureCsrf();
        const { data } = await axios.post("/api/auth/login", {
            email: apiEmail.value,
            password: apiPassword.value,
        });
        const token = data?.data?.token || data?.token;
        if (token) {
            setBearerToken(token);
            toast.success("API token saved. Retry your action.");
            showApiLogin.value = false;
        } else {
            apiLoginError.value = "No token returned. Check credentials.";
        }
    } catch (error) {
        apiLoginError.value =
            error?.response?.data?.message ||
            error?.message ||
            "Login failed. Check credentials.";
    } finally {
        apiLoginLoading.value = false;
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
            <div v-if="showApiLogin" class="mb-4 rounded-lg border border-amber-200 bg-amber-50 p-4 text-amber-900 space-y-3">
                    <div class="font-semibold">API auth required (fallback)</div>
                    <p class="text-sm">Enter admin credentials to store a Bearer token and retry.</p>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label class="text-xs text-gray-700">Email</label>
                            <input v-model="apiEmail" type="email" class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2 text-sm" />
                        </div>
                        <div>
                            <label class="text-xs text-gray-700">Password</label>
                            <input v-model="apiPassword" type="password" class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2 text-sm" />
                        </div>
                    </div>
                    <div class="flex items-center gap-3">
                        <button
                            @click="handleApiLogin"
                            :disabled="apiLoginLoading"
                            class="inline-flex items-center gap-2 rounded-md bg-[#2f837d] px-4 py-2 text-sm font-semibold text-white hover:bg-[#266a66] disabled:opacity-60"
                        >
                            <span v-if="apiLoginLoading" class="h-4 w-4 animate-spin rounded-full border-2 border-white border-t-transparent"></span>
                            Save API Token
                        </button>
                        <p v-if="apiLoginError" class="text-sm text-red-600">{{ apiLoginError }}</p>
                    </div>
                </div>
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900">Program Requests</h1>
                    <p class="mt-2 text-sm text-gray-600">
                        Manage trainer-submitted programs: approve or reject with notes.
                    </p>
                </div>
            </div>

            <!-- Search, Filter, and Export Controls -->
            <div
                class="bg-white rounded-[25px] shadow-sm p-6 border border-[#dfe5ef]"
            >
                <div class="flex items-center justify-between mb-6">
                    <div class="flex items-center gap-3">
                        <BookOpen class="h-6 w-6 text-[#2f837d]" />
                        <div>
                        <h2 class="text-xl font-semibold text-gray-900">
                            Program Requests ({{ totalCoursesCount }})
                        </h2>
                        <p class="text-sm text-gray-500">
                            Approve or reject trainer submissions
                        </p>
                    </div>
                </div>
                
                    <div class="flex items-center gap-2">
                        <label class="text-sm text-gray-600">Admin note</label>
                        <input
                            v-model="actionNote"
                            type="text"
                            placeholder="Optional note"
                            class="rounded-md border border-gray-300 px-3 py-2 text-sm"
                        />
                    </div>
                </div>

                <div
                    class="flex flex-col lg:flex-row gap-4 items-start lg:items-center justify-between mb-6"
                >
                    <!-- Left: Search and Filters -->
                    <div
                        class="flex flex-col sm:flex-row gap-4 flex-1 w-full lg:w-auto"
                    >
                        <!-- Search Bar -->
                        <div class="relative flex-1 max-w-md">
                            <input
                                v-model="searchQuery"
                                type="text"
                                placeholder="Search courses..."
                                class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#2f837d] focus:border-transparent"
                            />
                            <Search
                                class="absolute left-3 top-2.5 h-5 w-5 text-gray-400"
                            />
                        </div>

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

                <!-- Courses Table -->
                <div
                    class="bg-white rounded-[25px] shadow-sm border border-[#dfe5ef] overflow-hidden"
                >
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
                                                :class="{
                                                    'rotate-180':
                                                        sortDirection ===
                                                        'desc',
                                                }"
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
                                                :class="{
                                                    'rotate-180':
                                                        sortDirection ===
                                                        'desc',
                                                }"
                                            />
                                        </div>
                                    </th>
                                    <th
                                        @click="sort('target_type')"
                                        class="px-3 sm:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer hover:bg-gray-100"
                                    >
                                        <div class="flex items-center gap-2">
                                            Type
                                            <ChevronUp
                                                v-if="sortColumn === 'target_type'"
                                                class="h-4 w-4"
                                                :class="{
                                                    'rotate-180':
                                                        sortDirection ===
                                                        'desc',
                                                }"
                                            />
                                        </div>
                                    </th>
                                    <th
                                        @click="sort('category')"
                                        class="px-3 sm:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer hover:bg-gray-100"
                                    >
                                        <div class="flex items-center gap-2">
                                            Category
                                            <ChevronUp
                                                v-if="sortColumn === 'category'"
                                                class="h-4 w-4"
                                                :class="{
                                                    'rotate-180':
                                                        sortDirection ===
                                                        'desc',
                                                }"
                                            />
                                        </div>
                                    </th>
                                    <th
                                        @click="sort('status')"
                                        class="px-3 sm:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer hover:bg-gray-100"
                                    >
                                        <div class="flex items-center gap-2">
                                            Status
                                            <ChevronUp
                                                v-if="sortColumn === 'status'"
                                                class="h-4 w-4"
                                                :class="{
                                                    'rotate-180':
                                                        sortDirection ===
                                                        'desc',
                                                }"
                                            />
                                        </div>
                                    </th>
                                    <th
                                        class="px-3 sm:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
                                    >
                                        Requester
                                    </th>
                                    <th
                                        @click="sort('created_at')"
                                        class="px-3 sm:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer hover:bg-gray-100"
                                    >
                                        <div class="flex items-center gap-2">
                                            Created
                                            <ChevronUp
                                                v-if="sortColumn === 'created_at'"
                                                class="h-4 w-4"
                                                :class="{
                                                    'rotate-180':
                                                        sortDirection ===
                                                        'desc',
                                                }"
                                            />
                                        </div>
                                    </th>
                                    <th
                                        class="px-3 sm:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
                                    >
                                        Actions
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                <template
                                    v-for="(course, index) in paginatedCourses"
                                    :key="course.id"
                                >
                                    <tr
                                        :class="[
                                            'transition-colors',
                                            index % 2 === 0
                                                ? 'bg-white'
                                                : 'bg-gray-50',
                                            'hover:bg-gray-100',
                                        ]"
                                    >
                                    <td
                                        class="px-3 sm:px-6 py-4 whitespace-nowrap text-sm text-gray-900"
                                    >
                                        {{ course.id }}
                                    </td>
                                    <td class="px-3 sm:px-6 py-4">
                                        <div class="max-w-xs lg:max-w-md">
                                            <div
                                                class="text-sm font-medium text-gray-900"
                                            >
                                                {{ course.title }}
                                            </div>
                                            <div class="text-xs text-gray-500">
                                                Type: {{ course.target_type }} | Action: {{ course.action }}
                                                <template v-if="course.target_type === 'session' && course.program_label">
                                                    • Program: {{ course.program_label }}
                                                </template>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-3 sm:px-6 py-4 whitespace-nowrap">
                                        <span class="text-sm text-gray-700 capitalize">{{ course.target_type }}</span>
                                    </td>
                                    <td
                                        class="px-3 sm:px-6 py-4 whitespace-nowrap"
                                    >
                                        <span
                                            :class="[
                                                'px-3 py-1 inline-flex text-sm leading-5 font-medium rounded-lg w-32 justify-center',
                                                getCategoryClasses(
                                                    course.category
                                                ),
                                            ]"
                                        >
                                            {{ course.category }}
                                        </span>
                                    </td>
                                    <td
                                        class="px-3 sm:px-6 py-4 whitespace-nowrap"
                                    >
                                        <span
                                            class="px-3 py-2 inline-flex items-center justify-center gap-1 text-sm leading-5 rounded-md w-32"
                                            :class="getStatusClasses(course.status)"
                                        >
                                            {{ course.status }}
                                        </span>
                                    </td>
                                    <td class="px-3 sm:px-6 py-4 whitespace-nowrap text-sm text-gray-700">
                                        <div class="text-sm font-medium text-gray-900">
                                            {{ course.requester_name }}
                                        </div>
       									<div class="text-xs text-gray-500">
                                            {{ course.requester_email }}
                                        </div>
                                    </td>
                                    <td class="px-3 sm:px-6 py-4 whitespace-nowrap text-sm text-gray-700">
                                        {{ course.created_at ? course.created_at.substring(0, 10) : '—' }}
                                    </td>
                                    <td
                                        class="px-3 sm:px-6 py-4 whitespace-nowrap text-sm font-medium space-x-3 sm:space-x-6"
                                    >
                                        <div class="flex items-center gap-2 flex-wrap">
                                            <button
                                                v-if="course.sessions?.length"
                                                @click="toggleSessions(course.id)"
                                                class="inline-flex items-center gap-1 rounded-md border border-gray-200 bg-white px-3 py-1 text-sm text-gray-700 hover:bg-gray-50"
                                            >
                                                <ChevronDown
                                                    v-if="!isProgramExpanded(course.id)"
                                                    class="h-4 w-4"
                                                />
                                                <ChevronUp
                                                    v-else
                                                    class="h-4 w-4"
                                                />
                                                Sessions ({{ course.sessions.length }})
                                            </button>
                                            <a
                                                v-if="course.target_type !== 'session'"
                                                :href="course.admin_link"
                                                target="_blank"
                                                rel="noopener"
                                                class="text-[#2f837d] hover:text-[#266a66] transition-colors inline-flex items-center gap-1 border border-[#2f837d]/20 bg-[#daffed] px-3 py-1 rounded-md"
                                                title="View request detail"
                                            >
                                                View
                                            </a>
                                            <button
                                                @click="openConfirm(course.id, 'approve')"
                                                class="text-green-700 hover:text-green-900 transition-colors inline-flex items-center gap-1 border border-green-200 bg-green-50 px-3 py-1 rounded-md"
                                                title="Approve"
                                            >
                                                <Pencil class="h-4 w-4" />
                                                Approve
                                            </button>
                                            <button
                                                @click="openConfirm(course.id, 'reject')"
                                                class="text-red-700 hover:text-red-900 transition-colors inline-flex items-center gap-1 border border-red-200 bg-red-50 px-3 py-1 rounded-md"
                                                title="Reject"
                                            >
                                                <Trash2 class="h-4 w-4" />
                                                Reject
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                <tr
                                    v-if="course.sessions?.length && isProgramExpanded(course.id)"
                                    :key="`sessions-${course.id}`"
                                    class="bg-gray-50"
                                >
                                    <td colspan="8" class="px-3 sm:px-6 py-4">
                                        <div class="pl-4 border-l-2 border-teal-200 space-y-3">
                                            <div class="text-sm font-semibold text-gray-700">
                                                Session Requests
                                            </div>
                                            <div
                                                v-for="session in course.sessions"
                                                :key="session.id"
                                                class="flex flex-col gap-3 rounded-xl border border-gray-200 bg-white px-4 py-3 sm:flex-row sm:items-center sm:justify-between"
                                            >
                                                <div>
                                                    <div class="text-sm font-semibold text-gray-900">
                                                        {{ session.title }}
                                                    </div>
                                                    <div class="text-xs text-gray-500">
                                                        Request #{{ session.id }}
                                                        <span v-if="session.session_date">• {{ session.session_date }}</span>
                                                        <span v-if="session.session_time">• {{ session.session_time }}</span>
                                                        <span v-if="session.session_location">• {{ session.session_location }}</span>
                                                        <span v-if="session.session_capacity">• {{ session.session_capacity }}</span>
                                                    </div>
                                                </div>
                                                <div class="flex flex-wrap items-center gap-2">
                                                    <span
                                                        class="px-3 py-1 text-xs font-semibold rounded-md capitalize"
                                                        :class="getStatusClasses(session.status)"
                                                    >
                                                        {{ session.status }}
                                                    </span>
                                                    <button
                                                        @click="openConfirm(session.id, 'approve')"
                                                        class="text-green-700 hover:text-green-900 transition-colors inline-flex items-center gap-1 border border-green-200 bg-green-50 px-3 py-1 rounded-md"
                                                    >
                                                        <Pencil class="h-4 w-4" />
                                                        Approve
                                                    </button>
                                                    <button
                                                        @click="openConfirm(session.id, 'reject')"
                                                        class="text-red-700 hover:text-red-900 transition-colors inline-flex items-center gap-1 border border-red-200 bg-red-50 px-3 py-1 rounded-md"
                                                    >
                                                        <Trash2 class="h-4 w-4" />
                                                        Reject
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            </template>
                            </tbody>
                        </table>
                    </div>

                    <!-- Empty State -->
                    <div
                        v-if="filteredCourses.length === 0"
                        class="text-center py-12"
                    >
                        <Archive class="mx-auto h-12 w-12 text-gray-400" />
                        <h3 class="mt-2 text-sm font-medium text-gray-900">
                            No courses found
                        </h3>
                        <p class="mt-1 text-sm text-gray-500">
                            Try adjusting your search or filter criteria.
                        </p>
                    </div>

                    <!-- Pagination and Result Counter -->
                    <div
                        v-if="filteredCourses.length > 0"
                        class="flex flex-col sm:flex-row items-center justify-between gap-4 px-3 sm:px-6 py-4 bg-gray-50 border-t border-gray-200"
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
                        <div
                            class="text-sm text-gray-600 text-center sm:text-right"
                        >
                            Showing {{ startResult }}-{{ endResult }} of
                            {{ totalResults }} results
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

            <FilterModal
                :show="showFilterModal"
                title="Filter Courses"
                v-model:selectedDepartment="selectedCategory"
                v-model:selectedStatus="selectedStatus"
                :departments="categories"
                :statusOptions="['Approved', 'Pending', 'Rejected']"
                departmentLabel="Category"
                @close="showFilterModal = false"
                @reset="resetFilters"
            />

            <SortModal
                :show="showSortModal"
                title="Sort Courses"
                :sortColumn="sortColumn"
                :sortDirection="sortDirection"
                :sortOptions="[
                    {
                        value: 'id',
                        label: 'ID',
                        directionLabels: {
                            asc: 'Low to High',
                            desc: 'High to Low',
                        },
                    },
                    {
                        value: 'title',
                        label: 'Course Title',
                        directionLabels: { asc: 'A-Z', desc: 'Z-A' },
                    },
                    {
                        value: 'category',
                        label: 'Category',
                        directionLabels: { asc: 'A-Z', desc: 'Z-A' },
                    },
                    {
                        value: 'status',
                        label: 'Status',
                        directionLabels: {
                            asc: 'Approved First',
                            desc: 'Rejected First',
                        },
                    },
                    {
                        value: 'instructor',
                        label: 'Instructor',
                        directionLabels: { asc: 'A-Z', desc: 'Z-A' },
                    },
                    {
                        value: 'students',
                        label: 'Students',
                        directionLabels: {
                            asc: 'Low to High',
                            desc: 'High to Low',
                        },
                    },
                ]"
                @close="showSortModal = false"
                @sort="applySort"
                @reset="resetSort"
            />
        </div>
    </AdminLayout>

    <!-- Confirm modal -->
    <div v-if="showConfirm" class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4" @click.self="showConfirm = false">
        <div class="w-full max-w-md rounded-xl bg-white p-6 shadow-2xl">
            <h3 class="text-lg font-semibold text-gray-900 mb-2">
                {{ confirmAction === 'approve' ? 'Approve request?' : 'Reject request?' }}
            </h3>
            <p class="text-sm text-gray-600 mb-4">
                This will {{ confirmAction === 'approve' ? 'create/update the program from this request.' : 'mark this request as rejected.' }}
            </p>
            <div class="flex items-center gap-3">
                <button
                    @click="showConfirm = false"
                    class="rounded-lg border border-gray-200 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50"
                >
                    Cancel
                </button>
                <button
                    @click="performConfirmedAction"
                    class="rounded-lg bg-[#2f837d] px-4 py-2 text-sm font-medium text-white hover:bg-[#266a66]"
                >
                    Confirm
                </button>
            </div>
        </div>
    </div>
</template>
