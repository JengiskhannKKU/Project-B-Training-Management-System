<script setup>
import { ref, computed, watch, onMounted } from "vue";
import { Head } from "@inertiajs/vue3";
import AdminLayout from "@/Layouts/AdminLayout.vue";
import AttendanceCourseCard from "@/Components/AttendanceCourseCard.vue";
import {
    Search,
    Archive,
    Calendar,
    ListFilterIcon,
    ArrowDownNarrowWide,
    Share,
} from "lucide-vue-next";
import ExportModal from "@/Components/ExportModal.vue";
import FilterModal from "@/Components/FilterModal.vue";
import SortModal from "@/Components/SortModal.vue";
import SessionsModal from "@/Components/SessionsModal.vue";
import axios from "axios";

const courses = ref([]);
const loading = ref(true);
const error = ref(null);

// Fetch admin's courses from API
const fetchCourses = async () => {
    try {
        loading.value = true;
        error.value = null;
        const response = await axios.get("/api/admin/sessions");

        if (response.data && response.data.data) {
            courses.value = response.data.data;
        }
    } catch (err) {
        console.error("Error fetching courses:", err);
        error.value = err.response?.data?.message || "Failed to load courses";
    } finally {
        loading.value = false;
    }
};

onMounted(() => {
    fetchCourses();
});

const searchQuery = ref("");
const selectedDepartment = ref("all");
const selectedStatus = ref("all");
const sortColumn = ref("");
const sortDirection = ref("asc");
const showExportModal = ref(false);
const showFilterModal = ref(false);
const showSortModal = ref(false);
const showSessionsModal = ref(false);
const selectedCourse = ref(null);
const currentPage = ref(1);
const itemsPerPage = ref(9); // 9 cards per page for grid layout

// Get unique departments for filter
const departments = computed(() => {
    return [...new Set(courses.value.map((course) => course.department))];
});

// Count courses
const totalCoursesCount = computed(() => {
    return courses.value.length;
});

const activeCoursesCount = computed(() => {
    return courses.value.filter((course) => course.status === "Active").length;
});

// Filtered and sorted courses
const filteredCourses = computed(() => {
    let result = courses.value;

    // Filter by search query
    if (searchQuery.value) {
        const query = searchQuery.value.toLowerCase();
        result = result.filter(
            (course) =>
                course.name.toLowerCase().includes(query) ||
                course.instructor.toLowerCase().includes(query) ||
                course.location.toLowerCase().includes(query) ||
                course.department.toLowerCase().includes(query)
        );
    }

    // Filter by department
    if (selectedDepartment.value !== "all") {
        result = result.filter(
            (course) => course.department === selectedDepartment.value
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
watch([searchQuery, selectedDepartment, selectedStatus], () => {
    currentPage.value = 1;
});

// Export to CSV
const exportToCSV = () => {
    const headers = [
        "ID",
        "Course Name",
        "Instructor",
        "Department",
        "Students",
        "Date",
        "Time",
        "Location",
        "Status",
    ];
    const csvData = filteredCourses.value.map((course) => [
        course.id,
        course.name,
        course.instructor,
        course.department,
        course.students_count,
        course.date,
        course.time,
        course.location,
        course.status,
    ]);

    const csvContent = [
        headers.join(","),
        ...csvData.map((row) => row.join(",")),
    ].join("\n");

    const blob = new Blob([csvContent], { type: "text/csv" });
    const url = window.URL.createObjectURL(blob);
    const a = document.createElement("a");
    a.href = url;
    a.download = "attendance-courses.csv";
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

// Handle view sessions
const handleViewSessions = (courseId) => {
    const course = courses.value.find((c) => c.id === courseId);
    if (course) {
        selectedCourse.value = course;
        showSessionsModal.value = true;
    }
};

// Get selected course data
const selectedCourseSessions = computed(() => {
    return selectedCourse.value?.sessions || [];
});

const selectedCourseName = computed(() => {
    return selectedCourse.value?.name || "";
});
</script>

<template>
    <Head title="Attendance" />
    <AdminLayout>
        <div class="space-y-6">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900">Attendance</h1>
                    <p class="mt-2 text-sm text-gray-600">
                        Track and manage attendance for all courses
                    </p>
                </div>
            </div>

            <!-- Search, Filter, and Export Controls -->
            <div
                class="bg-white rounded-[25px] shadow-sm p-6 border border-[#dfe5ef]"
            >
                <div class="flex items-center gap-3 mb-6">
                    <Calendar class="h-6 w-6 text-[#2f837d]" />
                    <h2 class="text-xl font-semibold text-gray-900">
                        All Courses ({{ totalCoursesCount }})
                    </h2>
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

                <!-- Courses Grid -->
                <div
                    class="bg-white rounded-[25px] shadow-sm border border-[#dfe5ef] overflow-hidden p-6"
                >
                    <!-- Loading State -->
                    <div v-if="loading" class="text-center py-12">
                        <div
                            class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600 mx-auto"
                        ></div>
                        <p class="mt-4 text-sm text-gray-600">
                            Loading courses...
                        </p>
                    </div>

                    <!-- Error State -->
                    <div v-else-if="error" class="text-center py-12">
                        <div
                            class="mx-auto h-12 w-12 text-red-500 flex items-center justify-center"
                        >
                            <svg
                                class="h-12 w-12"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
                                />
                            </svg>
                        </div>
                        <h3 class="mt-2 text-sm font-medium text-gray-900">
                            Error loading courses
                        </h3>
                        <p class="mt-1 text-sm text-gray-500">{{ error }}</p>
                        <button
                            @click="fetchCourses"
                            class="mt-4 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors"
                        >
                            Try Again
                        </button>
                    </div>

                    <!-- Grid of Course Cards -->
                    <div
                        v-else-if="paginatedCourses.length > 0"
                        class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6"
                    >
                        <AttendanceCourseCard
                            v-for="course in paginatedCourses"
                            :key="course.id"
                            :id="course.id"
                            :name="course.name"
                            :image_url="course.image_url"
                            :rating="course.rating"
                            :level="course.level"
                            :students_count="course.students_count"
                            :price="course.price"
                            :date="course.date"
                            :time="course.time"
                            :location="course.location"
                            @viewSessions="handleViewSessions"
                        />
                    </div>

                    <!-- Empty State -->
                    <div
                        v-else-if="!loading && filteredCourses.length === 0"
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
                        class="flex flex-col sm:flex-row items-center justify-between gap-4 mt-6 pt-6 border-t border-gray-200"
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
                v-model:selectedDepartment="selectedDepartment"
                v-model:selectedStatus="selectedStatus"
                :departments="departments"
                :statusOptions="['Active', 'Upcoming', 'Completed']"
                departmentLabel="Department"
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
                        value: 'name',
                        label: 'Course Name',
                        directionLabels: { asc: 'A-Z', desc: 'Z-A' },
                    },
                    {
                        value: 'instructor',
                        label: 'Instructor',
                        directionLabels: { asc: 'A-Z', desc: 'Z-A' },
                    },
                    {
                        value: 'students_count',
                        label: 'Students',
                        directionLabels: {
                            asc: 'Low to High',
                            desc: 'High to Low',
                        },
                    },
                    {
                        value: 'rating',
                        label: 'Rating',
                        directionLabels: {
                            asc: 'Low to High',
                            desc: 'High to Low',
                        },
                    },
                    {
                        value: 'status',
                        label: 'Status',
                        directionLabels: {
                            asc: 'Active First',
                            desc: 'Upcoming First',
                        },
                    },
                ]"
                @close="showSortModal = false"
                @sort="applySort"
                @reset="resetSort"
            />

            <SessionsModal
                :show="showSessionsModal"
                :courseId="selectedCourse?.id || 0"
                :courseName="selectedCourseName"
                :sessions="selectedCourseSessions"
                baseUrl="/admin"
                @close="showSessionsModal = false"
            />
        </div>
    </AdminLayout>
</template>
