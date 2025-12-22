<script setup lang="ts">
import { ref, computed, watch, onMounted } from 'vue';
import axios from 'axios';
import { useToast } from 'vue-toastification';
import { Head } from '@inertiajs/vue3';
import TrainerLayout from '@/Layouts/TrainerLayout.vue';
import CourseCard from '@/Components/CourseCard.vue';
import CourseModal from '@/Components/CourseModal.vue';
import {
    Search,
    Archive,
    Calendar,
    ListFilterIcon,
    ArrowDownNarrowWide,
    Share,
} from 'lucide-vue-next';
import ExportModal from '@/Components/ExportModal.vue';
import FilterModal from '@/Components/FilterModal.vue';
import SortModal from '@/Components/SortModal.vue';

defineProps<{
    programs: Array<{
        id: number;
        name: string;
        code: string;
        category: string;
        duration_hours: number;
        description: string;
        status: string;
        image_url: string | null;
        rating?: number;
        level?: string;
        students_count?: number;
        price?: string;
        date?: string;
        time?: string;
        location?: string;
    }>;
}>();

const toast = useToast();

const searchQuery = ref('');
const selectedDepartment = ref('all');
const selectedStatus = ref('all');
const sortColumn = ref('');
const sortDirection = ref('asc');
const showExportModal = ref(false);
const showFilterModal = ref(false);
const showSortModal = ref(false);
const showCreateModal = ref(false);
const currentPage = ref(1);
const itemsPerPage = ref(9); // 9 cards per page for grid layout
const isSubmittingProgram = ref(false);
const showApiLogin = ref(false);
const apiEmail = ref('');
const apiPassword = ref('');
const apiLoginLoading = ref(false);
const apiLoginError = ref('');
const programs = ref<any[]>([]);
const isLoadingPrograms = ref(false);

// Get unique departments for filter
const departments = computed(() => {
    const seen = new Set<string>();
    const uniqueDepartments: string[] = [];

    programs.value.forEach((course) => {
        const department = course.department || 'General';

        if (!seen.has(department)) {
            seen.add(department);
            uniqueDepartments.push(department);
        }
    });

    return uniqueDepartments;
});

// Count courses
const totalCoursesCount = computed(() => {
    return programs.value.length;
});

// Filtered and sorted courses
const filteredCourses = computed(() => {
    let result = programs.value;

    // Filter by search query
    if (searchQuery.value) {
        const query = searchQuery.value.toLowerCase();
        result = result.filter(
            (course) =>
                course.name.toLowerCase().includes(query) ||
                (course.location && course.location.toLowerCase().includes(query)) ||
                (course.department && course.department.toLowerCase().includes(query))
        );
    }

    // Filter by department
    if (selectedDepartment.value !== 'all') {
        result = result.filter(
            (course) => course.department === selectedDepartment.value
        );
    }

    // Filter by status
    if (selectedStatus.value !== 'all') {
        result = result.filter(
            (course) => course.status === selectedStatus.value
        );
    }

    // Sort
    if (sortColumn.value) {
        result.sort((a, b) => {
            let aVal = a[sortColumn.value as keyof typeof a];
            let bVal = b[sortColumn.value as keyof typeof b];

            if (typeof aVal === 'string') {
                aVal = aVal.toLowerCase();
                bVal = (bVal as string).toLowerCase();
            }

            if (sortDirection.value === 'asc') {
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

const goToPage = (page: number) => {
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
        'ID',
        'Course Name',
        'Level',
        'Students',
        'Date',
        'Time',
        'Location',
        'Status',
    ];
    const csvData = filteredCourses.value.map((course) => [
        course.id,
        course.name,
        course.level,
        course.students_count,
        course.date,
        course.time,
        course.location,
        course.status,
    ]);

    const csvContent = [
        headers.join(','),
        ...csvData.map((row) => row.join(',')),
    ].join('\n');

    const blob = new Blob([csvContent], { type: 'text/csv' });
    const url = window.URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = 'trainer-courses.csv';
    a.click();
    window.URL.revokeObjectURL(url);
    showExportModal.value = false;
};

// Export to PDF
const exportToPDF = () => {
    alert('PDF export functionality - requires a PDF library like jsPDF');
    showExportModal.value = false;
};

// Apply filters
const applyFilters = (department: string, status: string) => {
    selectedDepartment.value = department;
    selectedStatus.value = status;
    showFilterModal.value = false;
};

// Apply sort
const applySort = (column: string) => {
    if (sortColumn.value === column) {
        sortDirection.value = sortDirection.value === 'asc' ? 'desc' : 'asc';
    } else {
        sortColumn.value = column;
        sortDirection.value = 'asc';
    }
    showSortModal.value = false;
};

// Reset filters
const resetFilters = () => {
    selectedDepartment.value = 'all';
    selectedStatus.value = 'all';
    showFilterModal.value = false;
};

// Reset sort
const resetSort = () => {
    sortColumn.value = '';
    sortDirection.value = 'asc';
    showSortModal.value = false;
};

const handleModalClose = () => {
    showCreateModal.value = false;
};

const handleModalSuccess = () => {
    // placeholder for compatibility
};

const handleCreateProgram = async (payload: Record<string, unknown> | undefined) => {
    if (!payload) return;
    isSubmittingProgram.value = true;
    try {
        await axios.get('/sanctum/csrf-cookie');
        await axios.post('/api/trainer/program-requests', {
            action: 'create',
            payload,
        });
        toast.success('Program request sent to admin for approval.');
        showCreateModal.value = false;
        await fetchPrograms();
    } catch (error: any) {
        const message =
            error?.response?.data?.message ||
            error?.message ||
            'Unable to submit program request.';
        toast.error(message);
        if ([401, 403, 419].includes(error?.response?.status)) {
            showApiLogin.value = true;
        }
    } finally {
        isSubmittingProgram.value = false;
    }
};

const setBearerToken = (token: string) => {
    localStorage.setItem('api_token', token);
    axios.defaults.headers.common.Authorization = `Bearer ${token}`;
};

const handleApiLogin = async () => {
    apiLoginError.value = '';
    apiLoginLoading.value = true;
    try {
        const { data } = await axios.post('/api/auth/login', {
            email: apiEmail.value,
            password: apiPassword.value,
        });
        const token = data?.data?.token || data?.token;
        if (token) {
            setBearerToken(token);
            toast.success('API token saved. Please resubmit your request.');
            showApiLogin.value = false;
        } else {
            apiLoginError.value = 'No token returned. Check credentials.';
        }
    } catch (error: any) {
        apiLoginError.value =
            error?.response?.data?.message ||
            error?.message ||
            'Login failed. Check credentials.';
    } finally {
        apiLoginLoading.value = false;
    }
};

const mapProgramFromRequest = (req: any) => {
    const payload = req.payload || {};
    return {
        id: req.target_id || req.id,
        request_id: req.id,
        name: payload.title || payload.name || `Program ${req.id}`,
        image_url: payload.image_url || '',
        rating: payload.rating || null,
        level: payload.level || '',
        students_count: payload.students_count || 0,
        price: payload.price || 'Free',
        date: payload.date || payload.registration_start || '',
        time: payload.time || '',
        location: payload.location || '',
        department: payload.category || 'General',
        status: req.status || 'pending'
    };
};

const ensureCsrf = () => axios.get('/sanctum/csrf-cookie');

const fetchPrograms = async () => {
    isLoadingPrograms.value = true;
    try {
        await ensureCsrf();
        const { data } = await axios.get('/api/trainer/requests');
        const list = data?.data || data || [];
        programs.value = list
            .filter((r: any) => r.target_type === 'program')
            .map(mapProgramFromRequest);
    } catch (error: any) {
        const message =
            error?.response?.data?.message ||
            error?.message ||
            'Unable to load programs.';
        toast.error(message);
        if ([401, 403, 419].includes(error?.response?.status)) {
            showApiLogin.value = true;
        }
    } finally {
        isLoadingPrograms.value = false;
    }
};

onMounted(() => {
    fetchPrograms();
});

// Mock data for demonstration
const mockPrograms = [
    {
        id: 1,
        name: 'Advanced UX Design',
        image_url: 'https://images.unsplash.com/photo-1522202176988-66273c2fd55f?w=400',
        rating: 4.7,
        level: 'Beginner',
        students_count: 32,
        price: 'Free',
        date: 'Oct 22,2024',
        time: '09:00 - 16:00',
        location: 'Smart Class room',
        department: 'Design',
        status: 'Active'
    },
    {
        id: 2,
        name: 'Design principle',
        image_url: 'https://images.unsplash.com/photo-1531482615713-2afd69097998?w=400',
        rating: 4.7,
        level: 'Advanced',
        students_count: 32,
        price: 'Free',
        date: 'Oct 22,2024',
        time: '09:00 - 16:00',
        location: 'Smart Class room',
        department: 'Design',
        status: 'Active'
    },
    {
        id: 3,
        name: 'Interaction Design',
        image_url: 'https://images.unsplash.com/photo-1573497019940-1c28c88b4f3e?w=400',
        rating: 4.7,
        level: 'Intermediate',
        students_count: 32,
        price: 'Free',
        date: 'Oct 22,2024',
        time: '09:00 - 16:00',
        location: 'Smart Class room',
        department: 'Design',
        status: 'Active'
    },
    {
        id: 4,
        name: 'Advanced UX Design',
        image_url: 'https://images.unsplash.com/photo-1573496359142-b8d87734a5a2?w=400',
        rating: 4.7,
        level: 'Intermediate',
        students_count: 32,
        price: 'Free',
        date: 'Oct 22,2024',
        time: '09:00 - 16:00',
        location: 'Smart Class room',
        department: 'IT',
        status: 'Upcoming'
    },
    {
        id: 5,
        name: 'Computer Engineering',
        image_url: 'https://images.unsplash.com/photo-1544717305-2782549b5136?w=400',
        rating: 4.7,
        level: 'Beginner',
        students_count: 32,
        price: 'Free',
        date: 'Oct 22,2024',
        time: '09:00 - 16:00',
        location: 'Smart Class room',
        department: 'IT',
        status: 'Active'
    },
    {
        id: 6,
        name: 'กฎ for beginning',
        image_url: 'https://images.unsplash.com/photo-1580894894513-541e068a3e2b?w=400',
        rating: 4.7,
        level: 'Beginner',
        students_count: 32,
        price: 'Free',
        date: 'Oct 22,2024',
        time: '09:00 - 16:00',
        location: 'Smart Class room',
        department: 'General',
        status: 'Active'
    },
];
</script>

<template>
    <Head title="My Courses" />
    <TrainerLayout>
        <div class="space-y-6">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900">My Courses</h1>
                    <p class="mt-2 text-sm text-gray-600">
                        Manage and track all your courses
                    </p>
                </div>
                <button
                    @click="showCreateModal = true"
                    class="inline-flex items-center gap-2 rounded-full bg-[#2f837d] px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-[#266a66]"
                >
                    <span class="text-lg leading-none">+</span>
                    Create Course
                </button>
            </div>

            <!-- API token fallback (for dev when session cookies fail) -->
            <div
                v-if="showApiLogin"
                class="border border-amber-200 bg-amber-50 text-amber-900 rounded-lg p-4 space-y-3"
            >
                <div class="flex items-center gap-2 font-semibold">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                    </svg>
                    API auth required (fallback)
                </div>
                <p class="text-sm">
                    Your browser session isn’t reaching the API. Enter trainer credentials to store an API token (Bearer) and retry.
                </p>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
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

            <!-- Search, Filter, and Export Controls -->
            <div
                class="bg-white rounded-[25px] shadow-sm p-6 border border-[#dfe5ef]"
            >
                <div class="flex items-center gap-3 mb-6">
                    <Calendar class="h-6 w-6 text-[#2f837d]" />
                    <h2 class="text-xl font-semibold text-gray-900">
                        My Programs ({{ totalCoursesCount }})
                    </h2>
                    <span v-if="isLoadingPrograms" class="text-sm text-gray-500">Loading...</span>
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
                        <CourseCard
                            v-for="course in paginatedCourses"
                            :key="course.request_id || course.id"
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

            <CourseModal
                :show="showCreateModal"
                :enable-preview-dialogs="false"
                @close="handleModalClose"
                @success="handleCreateProgram"
            />
        </div>
    </TrainerLayout>
</template>
