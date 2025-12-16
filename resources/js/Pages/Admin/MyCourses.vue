<script setup>
import { ref, computed, watch } from "vue";
import { Head, router } from "@inertiajs/vue3";
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

const courses = ref([
    {
        id: 1,
        title: "Advanced Laravel Development",
        description: "Master advanced Laravel concepts and best practices",
        category: "Programming",
        instructor: "John Smith",
        students: 45,
        status: "Approved",
        progress: 75,
    },
    {
        id: 2,
        title: "Vue.js Masterclass",
        description: "Build modern web applications with Vue.js",
        category: "Programming",
        instructor: "Sarah Johnson",
        students: 32,
        status: "Approved",
        progress: 60,
    },
    {
        id: 3,
        title: "UI/UX Design Principles",
        description: "Learn fundamental design principles for better UX",
        category: "Design",
        instructor: "Mike Davis",
        students: 28,
        status: "Pending",
        progress: 40,
    },
    {
        id: 4,
        title: "Digital Marketing Strategy",
        description: "Comprehensive digital marketing strategies and tactics",
        category: "Marketing",
        instructor: "Emily Brown",
        students: 56,
        status: "Approved",
        progress: 90,
    },
    {
        id: 5,
        title: "Python for Data Science",
        description: "Data analysis and visualization with Python",
        category: "Programming",
        instructor: "David Lee",
        students: 38,
        status: "Pending",
        progress: 55,
    },
    {
        id: 6,
        title: "Mobile App Development",
        description: "Create cross-platform mobile applications",
        category: "Programming",
        instructor: "Lisa Wang",
        students: 42,
        status: "Rejected",
        progress: 30,
    },
]);

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

// Available options for dropdowns
const statusOptions = ["Approved", "Pending", "Rejected"];
const categories = computed(() => {
    return [...new Set(courses.value.map((course) => course.category))];
});

// Count courses by status
const activeCoursesCount = computed(() => {
    return courses.value.filter((course) => course.status === "Approved")
        .length;
});

const totalCoursesCount = computed(() => {
    return courses.value.length;
});

// Filtered and sorted courses
const filteredCourses = computed(() => {
    let result = courses.value;

    // Filter by search query
    if (searchQuery.value) {
        const query = searchQuery.value.toLowerCase();
        result = result.filter(
            (course) =>
                course.title.toLowerCase().includes(query) ||
                course.category.toLowerCase().includes(query) ||
                course.instructor.toLowerCase().includes(query)
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

// Change course status
const changeCourseStatus = (courseId, newStatus) => {
    const course = courses.value.find((c) => c.id === courseId);
    if (course) {
        course.status = newStatus;
    }
    openStatusDropdown.value = null;
};

// Handle actions
const openCreateModal = () => {
    // Navigate to create course page or open modal for creation
    // This can be implemented later if needed
    alert("Create course functionality - to be implemented");
};

const editCourse = (courseId) => {
    // Navigate to the course detail page
    router.visit(`/admin/my-courses/${courseId}`);
};

const deleteCourse = (courseId) => {
    if (confirm("Are you sure you want to delete this course?")) {
        const index = courses.value.findIndex((c) => c.id === courseId);
        if (index !== -1) {
            courses.value.splice(index, 1);
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
    };
    return colorMap[category] || "bg-gray-100 text-gray-800";
};
</script>

<template>
    <Head title="My Courses" />
    <AdminLayout>
        <div class="space-y-6">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900">My Courses</h1>
                    <p class="mt-2 text-sm text-gray-600">
                        Manage and monitor all training courses
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
                        <h2 class="text-xl font-semibold text-gray-900">
                            All Courses ({{ totalCoursesCount }})
                        </h2>
                    </div>
                    <button
                        @click="openCreateModal"
                        class="bg-[#2f837d] hover:bg-[#26685f] text-white px-6 py-2.5 rounded-lg font-medium transition-all flex items-center gap-2 shadow-sm hover:shadow-md"
                    >
                        <Plus class="h-4 w-4" />
                        Create Course
                    </button>
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
                                            Course Title
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
                                        Actions
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                <tr
                                    v-for="(course, index) in paginatedCourses"
                                    :key="course.id"
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
                                            <div
                                                class="text-sm text-gray-500 truncate"
                                            >
                                                {{ course.description }}
                                            </div>
                                        </div>
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
                                        <button
                                            @click="
                                                toggleStatusDropdown(
                                                    course.id,
                                                    $event
                                                )
                                            "
                                            :class="[
                                                'status-dropdown-trigger px-3 py-2 inline-flex items-center justify-center gap-1 text-sm leading-5 rounded-md cursor-pointer hover:opacity-80 transition-opacity w-32',
                                                course.status === 'Approved'
                                                    ? 'bg-green-100 text-green-800'
                                                    : course.status ===
                                                      'Pending'
                                                    ? 'bg-yellow-100 text-yellow-800'
                                                    : 'bg-red-100 text-red-800',
                                            ]"
                                        >
                                            {{ course.status }}
                                            <ChevronDown class="h-3 w-3" />
                                        </button>

                                        <!-- Dropdown Menu (Teleported to body) -->
                                        <Teleport to="body">
                                            <div
                                                v-if="
                                                    openStatusDropdown ===
                                                    course.id
                                                "
                                                class="status-dropdown-menu fixed z-50 bg-white border border-gray-200 rounded-lg shadow-lg py-1 min-w-[120px]"
                                                :style="{
                                                    top:
                                                        dropdownPosition.top +
                                                        'px',
                                                    left:
                                                        dropdownPosition.left +
                                                        'px',
                                                }"
                                            >
                                                <button
                                                    @click="
                                                        changeCourseStatus(
                                                            course.id,
                                                            'Approved'
                                                        )
                                                    "
                                                    class="w-full text-left px-4 py-2 text-sm hover:bg-gray-50 flex items-center gap-2"
                                                    :class="
                                                        course.status ===
                                                        'Approved'
                                                            ? 'bg-green-50 text-green-800'
                                                            : 'text-gray-700'
                                                    "
                                                >
                                                    <span
                                                        class="h-2 w-2 rounded-full bg-green-500"
                                                    ></span>
                                                    Approved
                                                </button>
                                                <button
                                                    @click="
                                                        changeCourseStatus(
                                                            course.id,
                                                            'Pending'
                                                        )
                                                    "
                                                    class="w-full text-left px-4 py-2 text-sm hover:bg-gray-50 flex items-center gap-2"
                                                    :class="
                                                        course.status ===
                                                        'Pending'
                                                            ? 'bg-yellow-50 text-yellow-800'
                                                            : 'text-gray-700'
                                                    "
                                                >
                                                    <span
                                                        class="h-2 w-2 rounded-full bg-yellow-500"
                                                    ></span>
                                                    Pending
                                                </button>
                                                <button
                                                    @click="
                                                        changeCourseStatus(
                                                            course.id,
                                                            'Rejected'
                                                        )
                                                    "
                                                    class="w-full text-left px-4 py-2 text-sm hover:bg-gray-50 flex items-center gap-2"
                                                    :class="
                                                        course.status ===
                                                        'Rejected'
                                                            ? 'bg-red-50 text-red-800'
                                                            : 'text-gray-700'
                                                    "
                                                >
                                                    <span
                                                        class="h-2 w-2 rounded-full bg-red-500"
                                                    ></span>
                                                    Rejected
                                                </button>
                                            </div>
                                        </Teleport>
                                    </td>
                                    <td
                                        class="px-3 sm:px-6 py-4 whitespace-nowrap text-sm font-medium space-x-3 sm:space-x-6"
                                    >
                                        <button
                                            @click="deleteCourse(course.id)"
                                            class="text-gray-600 hover:text-red-800 transition-colors inline-flex items-center gap-1"
                                            title="Delete"
                                        >
                                            <Trash2 class="h-4 w-4" />
                                        </button>
                                        <button
                                            @click="editCourse(course.id)"
                                            class="text-gray-600 hover:text-[#257067] transition-colors inline-flex items-center gap-1"
                                            title="Edit"
                                        >
                                            <Pencil class="h-4 w-4" />
                                        </button>
                                    </td>
                                </tr>
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
</template>
