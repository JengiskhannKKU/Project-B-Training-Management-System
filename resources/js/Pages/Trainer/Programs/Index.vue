<script setup lang="ts">
import { ref, computed, watch, onMounted } from 'vue';
import axios from 'axios';
import { useToast } from 'vue-toastification';
import { Head, usePage } from '@inertiajs/vue3';
import TrainerLayout from '@/Layouts/TrainerLayout.vue';
import CourseCard from '@/Components/CourseCard.vue';
import Skeleton from '@/Components/Skeleton.vue';
import {
    Search,
    Archive,
    Calendar,
    ListFilterIcon,
    ArrowDownNarrowWide,
    Share,
    Plus,
} from 'lucide-vue-next';
import ExportModal from '@/Components/ExportModal.vue';
import FilterDropdown from '@/Components/FilterDropdown.vue';
import SortDropdown from '@/Components/SortDropdown.vue';
import jsPDF from 'jspdf';
import 'jspdf-autotable';

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
        trainees_count?: number;
        price?: string;
        date?: string;
        time?: string;
        location?: string;
    }>;
}>();

const toast = useToast();
const page = usePage();

const searchQuery = ref('');
const selectedDepartment = ref([]);
const selectedStatus = ref([]);
const sortColumn = ref('created_at');
const sortDirection = ref('desc');
const showExportModal = ref(false);
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
    if (selectedDepartment.value.length > 0) {
        result = result.filter(
            (course) => selectedDepartment.value.includes(course.department)
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
            let aVal = a[sortColumn.value as keyof typeof a];
            let bVal = b[sortColumn.value as keyof typeof b];

            if (sortColumn.value === 'created_at') {
                 // Date sorting
                 const dateA = a.created_at ? new Date(a.created_at).getTime() : 0;
                 const dateB = b.created_at ? new Date(b.created_at).getTime() : 0;
                 aVal = dateA;
                 bVal = dateB;
            } else if (typeof aVal === 'string') {
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
        course.trainees_count,
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
    try {
        const doc = new jsPDF();

        // Add title
        doc.setFontSize(16);
        doc.text('Trainer Courses Report', 14, 20);

        // Add generation date
        doc.setFontSize(10);
        doc.text(`Generated: ${new Date().toLocaleDateString()}`, 14, 28);

        // Prepare table data with null-safe values
        const headers = [['ID', 'Course Name', 'Level', 'Students', 'Date', 'Time', 'Location', 'Status']];
        const data = filteredCourses.value.map((course) => [
            course.id ?? '',
            course.name ?? '',
            course.level ?? '',
            course.trainees_count ?? 0,
            course.date ?? '',
            course.time ?? '',
            course.location ?? '',
            course.status ?? '',
        ]);

        // Generate table
        doc.autoTable({
            head: headers,
            body: data,
            startY: 35,
            theme: 'grid',
            headStyles: { fillColor: [59, 130, 246] },
            styles: { fontSize: 8 },
        });

        // Save the PDF
        doc.save('trainer-courses.pdf');
    } catch (error) {
        console.error('Error generating PDF:', error);
        alert('Failed to generate PDF. Please try again.');
    } finally {
        showExportModal.value = false;
    }
};

// Apply sort
const handleSort = ({ column, direction }: { column: string, direction: string }) => {
    sortColumn.value = column;
    sortDirection.value = direction;
};

// Reset filters
const resetFilters = () => {
    selectedDepartment.value = [];
    selectedStatus.value = [];
};

// Reset sort
const resetSort = () => {
    sortColumn.value = '';
    sortDirection.value = 'asc';
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

const ensureCsrf = () => axios.get('/sanctum/csrf-cookie');

const fetchPrograms = async () => {
    isLoadingPrograms.value = true;
    try {
        await ensureCsrf();
        const { data } = await axios.get('/api/programs');
        const list = data?.data || data || [];

        // Filter to show only programs created by this trainer
        programs.value = list
            .filter((program: any) => program.created_by === page.props.auth?.user?.id)
            .map((program: any) => ({
                id: program.id,
                name: program.name,
                image_url: program.image_url || '',
                rating: program.rating || null,
                level: program.level || '',
                trainees_count: program.trainees_count || 0,
                price: program.price || 'Free',
                date: program.date || program.registration_start || '',
                time: program.time || '',
                location: program.location || '',
                department: program.category || 'General',
                status: program.status || 'active',
                created_at: program.created_at
            }))
            .sort((a: any, b: any) => {
                if (a.created_at && b.created_at) {
                    return new Date(b.created_at).getTime() - new Date(a.created_at).getTime();
                }
                return (b.id || 0) - (a.id || 0);
            });
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
        trainees_count: 32,
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
        trainees_count: 32,
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
        trainees_count: 32,
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
        trainees_count: 32,
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
        trainees_count: 32,
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
        trainees_count: 32,
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
                    <!-- Left: Search Bar -->
                    <div class="relative w-full lg:max-w-md">
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

                    <!-- Right: Filter, Sort, Export buttons -->
                    <div class="flex flex-row gap-4">
                        <!-- Filter button -->
                        <FilterDropdown
                            v-model:selectedDepartment="selectedDepartment"
                            v-model:selectedStatus="selectedStatus"
                            :departments="departments"
                            :statusOptions="['Active', 'Upcoming', 'Completed']"
                            departmentLabel="Department"
                            @reset="resetFilters"
                        >
                            <template #trigger>
                                <button
                                    class="rounded-lg border transition-all duration-200 inline-flex gap-2 items-center px-4 py-2"
                                    :class="selectedDepartment.length + selectedStatus.length > 0 ? 'bg-[#2f837d]/10 border-[#2f837d] text-[#2f837d]' : 'border-[#d5dde7] hover:bg-gray-50 text-gray-700'"
                                >
                                    <ListFilterIcon class="h-4 w-4" />
                                    <p>
                                        Filter
                                        <span v-if="selectedDepartment.length + selectedStatus.length > 0" class="ml-1 font-semibold">
                                            ({{ selectedDepartment.length + selectedStatus.length }})
                                        </span>
                                    </p>
                                </button>
                            </template>
                        </FilterDropdown>

                        <!-- Sort button -->
                        <SortDropdown
                            :sortColumn="sortColumn"
                            :sortDirection="sortDirection"
                            :sortOptions="[
                                { value: 'created_at', label: 'Date' },
                                { value: 'name', label: 'Course Name' },
                                { value: 'trainees_count', label: 'Students' },
                                { value: 'rating', label: 'Rating' },
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
                    <!-- Skeleton Loading State -->
                    <div
                        v-if="isLoadingPrograms"
                        class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6"
                    >
                        <div
                            v-for="n in 6"
                            :key="n"
                            class="bg-white rounded-xl border border-gray-200 overflow-hidden"
                        >
                            <!-- Image skeleton -->
                            <Skeleton variant="rectangular" width="100%" height="192px" />
                            <div class="p-4 space-y-3">
                                <!-- Title skeleton -->
                                <Skeleton variant="text" width="80%" height="20px" />
                                <!-- Rating skeleton -->
                                <Skeleton variant="text" width="40%" height="16px" />
                                <!-- Description skeleton -->
                                <Skeleton variant="text" :rows="2" />
                                <!-- Details skeleton -->
                                <div class="flex gap-4">
                                    <Skeleton variant="text" width="30%" height="16px" />
                                    <Skeleton variant="text" width="30%" height="16px" />
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Grid of Course Cards -->
                    <div
                        v-else-if="paginatedCourses.length > 0"
                        class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6"
                    >
                        <CourseCard
                            v-for="course in paginatedCourses"
                            :key="course.id"
                            :id="course.id"
                            :href="`/trainer/programs/${course.id}`"
                            :name="course.name"
                            :image_url="course.image_url"
                            :rating="course.rating"
                            :level="course.level"
                            :trainees_count="course.trainees_count"
                            :price="course.price"
                            :date="course.date"
                            :time="course.time"
                            :location="course.location"
                        />
                    </div>

                    <!-- Empty State -->
                    <div
                        v-if="filteredCourses.length === 0 && !isLoadingPrograms"
                        class="text-center py-12"
                    >
                        <Archive class="mx-auto h-12 w-12 text-gray-400" />
                        <h3 class="mt-2 text-sm font-medium text-gray-900">
                            {{ searchQuery || selectedDepartment.length || selectedStatus.length ? 'No courses found' : 'No courses yet' }}
                        </h3>
                        <p class="mt-1 text-sm text-gray-500">
                            {{ searchQuery || selectedDepartment.length || selectedStatus.length
                                ? 'Try adjusting your search or filter criteria.'
                                : 'Only administrators can create new courses and sessions. Contact your admin to create courses.' }}
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
            
            <!-- Filter Dropdown replaced Modal -->

            <!-- Sort Dropdown replaced Modal -->
        </div>
    </TrainerLayout>
</template>
