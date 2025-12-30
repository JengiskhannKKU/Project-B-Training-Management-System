<script setup>
import { ref, computed, watch } from "vue";
import { Head } from "@inertiajs/vue3";
import TrainerLayout from "@/Layouts/TrainerLayout.vue";
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

const courses = ref([
    {
        id: 1,
        name: "Advanced Laravel Development",
        image_url: "https://images.unsplash.com/photo-1555066931-4365d14bab8c?w=800",
        rating: 4.8,
        level: "Advanced",
        students_count: 45,
        price: "RM 1,200",
        date: "Dec 15-20, 2025",
        time: "10:00 AM - 12:00 PM",
        location: "Room A101",
        instructor: "John Smith",
        department: "IT",
        status: "Active",
        sessions: [
            {
                id: 1,
                name: "Session 1: Introduction to Laravel",
                date: "Dec 15, 2025",
                time: "10:00 AM - 12:00 PM",
                location: "Room A101",
                status: "open",
                end_date: "2025-12-15",
            },
            {
                id: 2,
                name: "Session 2: Eloquent ORM",
                date: "Dec 16, 2025",
                time: "10:00 AM - 12:00 PM",
                location: "Room A101",
                status: "closed",
                end_date: "2025-12-16",
            },
            {
                id: 3,
                name: "Session 3: Advanced Routing",
                date: "Dec 17, 2025",
                time: "10:00 AM - 12:00 PM",
                location: "Room A101",
                status: "upcoming",
                end_date: "2025-12-17",
            },
            {
                id: 4,
                name: "Session 4: Authentication & Authorization",
                date: "Dec 18, 2025",
                time: "10:00 AM - 12:00 PM",
                location: "Room A101",
                status: "open",
                end_date: "2026-01-18",
            },
        ],
    },
    {
        id: 2,
        name: "Vue.js Masterclass",
        image_url: "https://images.unsplash.com/photo-1633356122544-f134324a6cee?w=800",
        rating: 4.9,
        level: "Intermediate",
        students_count: 32,
        price: "RM 1,000",
        date: "Dec 18-22, 2025",
        time: "2:00 PM - 4:00 PM",
        location: "Room B205",
        instructor: "Sarah Johnson",
        department: "IT",
        status: "Active",
        sessions: [
            {
                id: 5,
                name: "Session 1: Vue Fundamentals",
                date: "Dec 18, 2025",
                time: "2:00 PM - 4:00 PM",
                location: "Room B205",
                status: "closed",
                end_date: "2025-12-18",
            },
            {
                id: 6,
                name: "Session 2: Component Design",
                date: "Dec 19, 2025",
                time: "2:00 PM - 4:00 PM",
                location: "Room B205",
                status: "open",
                end_date: "2025-12-19",
            },
            {
                id: 7,
                name: "Session 3: State Management",
                date: "Dec 20, 2025",
                time: "2:00 PM - 4:00 PM",
                location: "Room B205",
                status: "completed",
                end_date: "2025-12-20",
            },
        ],
    },
    {
        id: 3,
        name: "UI/UX Design Principles",
        image_url: "https://images.unsplash.com/photo-1561070791-2526d30994b5?w=800",
        rating: 4.7,
        level: "Beginner",
        students_count: 28,
        price: "RM 800",
        date: "Dec 20-25, 2025",
        time: "9:00 AM - 11:00 AM",
        location: "Design Lab",
        instructor: "Mike Davis",
        department: "Design",
        status: "Active",
        sessions: [
            {
                id: 8,
                name: "Session 1: Design Thinking",
                date: "Dec 20, 2025",
                time: "9:00 AM - 11:00 AM",
                location: "Design Lab",
            },
            {
                id: 9,
                name: "Session 2: User Research",
                date: "Dec 21, 2025",
                time: "9:00 AM - 11:00 AM",
                location: "Design Lab",
            },
        ],
    },
    {
        id: 4,
        name: "Digital Marketing Strategy",
        image_url: "https://images.unsplash.com/photo-1460925895917-afdab827c52f?w=800",
        rating: 4.6,
        level: "Intermediate",
        students_count: 56,
        price: "RM 1,500",
        date: "Jan 5-10, 2026",
        time: "1:00 PM - 3:00 PM",
        location: "Marketing Hub",
        instructor: "Emily Brown",
        department: "Marketing",
        status: "Active",
        sessions: [
            {
                id: 10,
                name: "Session 1: Digital Marketing Overview",
                date: "Jan 5, 2026",
                time: "1:00 PM - 3:00 PM",
                location: "Marketing Hub",
            },
            {
                id: 11,
                name: "Session 2: SEO & SEM",
                date: "Jan 6, 2026",
                time: "1:00 PM - 3:00 PM",
                location: "Marketing Hub",
            },
            {
                id: 12,
                name: "Session 3: Social Media Marketing",
                date: "Jan 7, 2026",
                time: "1:00 PM - 3:00 PM",
                location: "Marketing Hub",
            },
        ],
    },
    {
        id: 5,
        name: "Python for Data Science",
        image_url: "https://images.unsplash.com/photo-1526374965328-7f61d4dc18c5?w=800",
        rating: 4.9,
        level: "Advanced",
        students_count: 38,
        price: "RM 1,300",
        date: "Jan 8-15, 2026",
        time: "10:00 AM - 1:00 PM",
        location: "Data Lab",
        instructor: "David Lee",
        department: "IT",
        status: "Upcoming",
        sessions: [
            {
                id: 13,
                name: "Session 1: Python Basics for Data Science",
                date: "Jan 8, 2026",
                time: "10:00 AM - 1:00 PM",
                location: "Data Lab",
            },
            {
                id: 14,
                name: "Session 2: NumPy and Pandas",
                date: "Jan 9, 2026",
                time: "10:00 AM - 1:00 PM",
                location: "Data Lab",
            },
        ],
    },
    {
        id: 6,
        name: "Mobile App Development",
        image_url: "https://images.unsplash.com/photo-1551650975-87deedd944c3?w=800",
        rating: 4.5,
        level: "Intermediate",
        students_count: 42,
        price: "RM 1,100",
        date: "Jan 12-18, 2026",
        time: "3:00 PM - 5:00 PM",
        location: "Tech Center",
        instructor: "Lisa Wang",
        department: "IT",
        status: "Upcoming",
        sessions: [
            {
                id: 15,
                name: "Session 1: Mobile Development Fundamentals",
                date: "Jan 12, 2026",
                time: "3:00 PM - 5:00 PM",
                location: "Tech Center",
            },
            {
                id: 16,
                name: "Session 2: React Native Basics",
                date: "Jan 13, 2026",
                time: "3:00 PM - 5:00 PM",
                location: "Tech Center",
            },
            {
                id: 17,
                name: "Session 3: Building Your First App",
                date: "Jan 14, 2026",
                time: "3:00 PM - 5:00 PM",
                location: "Tech Center",
            },
        ],
    },
]);

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
    <TrainerLayout>
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
                    <!-- Grid of Course Cards -->
                    <div
                        v-if="paginatedCourses.length > 0"
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
                baseUrl="/trainer"
                @close="showSessionsModal = false"
            />
        </div>
    </TrainerLayout>
</template>
