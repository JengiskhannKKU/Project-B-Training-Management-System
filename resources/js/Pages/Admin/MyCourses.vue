<script setup lang="ts">
import { ref, computed, watch, onMounted } from 'vue';
import axios from 'axios';
import { useToast } from 'vue-toastification';
import { Head, usePage } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import CourseCard from '@/Components/CourseCard.vue';
import CourseModal from '@/Components/CourseModal.vue';
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

const toast = useToast();
const page = usePage();

const searchQuery = ref('');
const selectedDepartment = ref([]);
const selectedStatus = ref([]);
const selectedAssignment = ref('all'); // Assignment is typically singular mode switch
const sortColumn = ref('created_at');
const sortDirection = ref('desc');
const showExportModal = ref(false);
const showCreateModal = ref(false);
const editingCourse = ref<any>(null);
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

    // Filter by assignment (My Courses vs All Courses)
    if (selectedAssignment.value === 'my') {
        // Filter to show only courses created by or assigned to the current admin
        // Note: Adjust the field name based on your API response structure
        // Common field names: created_by_id, admin_id, trainer_id, owner_id
        const currentUserId = page.props.auth?.user?.id;
        result = result.filter(
            (course) => course.created_by_id === currentUserId ||
                       course.admin_id === currentUserId ||
                       course.trainer_id === currentUserId
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
watch([searchQuery, selectedDepartment, selectedStatus, selectedAssignment], () => {
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
    a.download = 'admin-courses.csv';
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
        doc.text('Courses Report', 14, 20);

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
        (doc as any).autoTable({
            head: headers,
            body: data,
            startY: 35,
            theme: 'grid',
            headStyles: { fillColor: [59, 130, 246] },
            styles: { fontSize: 8 },
        });

        // Save the PDF
        doc.save('admin-courses.pdf');
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
    selectedAssignment.value = 'all';
};

// Reset sort
const resetSort = () => {
    sortColumn.value = '';
    sortDirection.value = 'asc';
};

const handleModalClose = () => {
    showCreateModal.value = false;
    editingCourse.value = null;
};

const handleEditCourse = (course: any) => {
    editingCourse.value = {
        id: course.id,
        title: course.title,
        description: course.description,
        category: course.category,
        level: course.level,
        learning_outcomes: course.learning_outcomes,
        target_audience: course.target_audience,
        prerequisites: course.prerequisites,
        additional_info: course.additional_info,
        min_participants: course.min_participants,
        max_participants: course.max_participants,
        thumbnail_path: course.thumbnail_path,
        status: course.status,
    };
    showCreateModal.value = true;
};

const handleDeleteCourse = async (courseId: number) => {
    if (!confirm('Are you sure you want to delete this course? This action cannot be undone.')) {
        return;
    }

    try {
        await axios.get('/sanctum/csrf-cookie');
        await axios.delete(`/api/courses/${courseId}`);
        toast.success('Course deleted successfully!');
        await fetchPrograms();
    } catch (error: any) {
        const message =
            error?.response?.data?.message ||
            error?.message ||
            'Failed to delete course.';
        toast.error(message);
    }
};

const handleCreateProgram = async (payload: Record<string, unknown> | undefined) => {
    // The API call now happens in CourseModal itself
    // This handler is called only on success, so just refresh the list
    showCreateModal.value = false;
    editingCourse.value = null;
    await fetchPrograms();
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
    const resolvedProgramId =
        req.status === 'approved' && req.target_id ? req.target_id : req.id;
    return {
        // Use program ID when approved; fall back to request ID for pending/rejected
        id: resolvedProgramId,
        request_id: req.id,
        // Keep target_id for reference to actual program in programs table
        program_id: req.target_id,
        name: payload.title || payload.name || `Program ${req.id}`,
        image_url: payload.image_url || '',
        rating: payload.rating || null,
        level: payload.level || '',
        trainees_count: payload.trainees_count || 0,
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

        // Fetch actual courses
        const { data } = await axios.get('/api/courses');
        const list = data?.data || data || [];

        // Transform and map API data to match CourseCard props
        const mappedList = list.map((course: any) => {
            const hasSessions = (course.sessions_count || 0) > 0;
            const isPublished = course.status === 'published';
            // A course is incomplete only if it's published but has no sessions
            const isIncomplete = isPublished && !hasSessions;

            return {
                id: course.id,
                name: course.title, // Map title to name for CourseCard
                image_url: course.thumbnail_path || '',
                rating: 0,
                level: course.level || 'beginner',
                trainees_count: 0,
                price: 'Free',
                date: course.created_at || '',
                time: '',
                location: 'Online',
                department: course.category || 'General',
                status: course.status,
                isIncomplete: isIncomplete,
                created_by_id: course.owner_id,
                created_at: course.created_at,
                sessions_count: course.sessions_count || 0,
                // Raw fields for editing
                title: course.title,
                description: course.description,
                category: course.category,
                learning_outcomes: course.learning_outcomes,
                target_audience: course.target_audience,
                prerequisites: course.prerequisites,
                additional_info: course.additional_info,
                min_participants: course.min_participants,
                max_participants: course.max_participants,
                thumbnail_path: course.thumbnail_path,
            };
        });

        // Sort programs by ID or created_at in descending order (newest first)
        const sortedList = mappedList.sort((a: any, b: any) => {
            // Try to sort by created_at if available, otherwise by id
            if (a.created_at && b.created_at) {
                return new Date(b.created_at).getTime() - new Date(a.created_at).getTime();
            }
            return (b.id || 0) - (a.id || 0);
        });

        programs.value = sortedList;
    } catch (error: any) {
        const message =
            error?.response?.data?.message ||
            error?.message ||
            'Unable to load courses.';
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
</script>

<template>
    <Head title="All Courses" />
    <AdminLayout>
        <div class="space-y-6">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900">{{ $t('All Courses') }}</h1>
                    <p class="mt-2 text-sm text-gray-600">
                        {{ $t('Manage and track all courses') }}
                    </p>
                </div>
                <button
                    @click="showCreateModal = true"
                    class="bg-[#2f837d] hover:bg-[#26685f] text-white px-6 py-2.5 rounded-lg font-medium transition-all flex items-center gap-2 shadow-sm hover:shadow-md"
                >
                    <Plus :size="20" />
                    <span>{{ $t('Create Course') }}</span>
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
                    {{ $t('API Auth Required') }}
                </div>
                <p class="text-sm">
                    {{ $t('API Auth Description') }}
                </p>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
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
                        @click="handleApiLogin"
                        :disabled="apiLoginLoading"
                        class="inline-flex items-center gap-2 rounded-md bg-[#2f837d] px-4 py-2 text-sm font-semibold text-white hover:bg-[#266a66] disabled:opacity-60"
                    >
                        <span v-if="apiLoginLoading" class="h-4 w-4 animate-spin rounded-full border-2 border-white border-t-transparent"></span>
                        {{ $t('Save API Token') }}
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
                        {{ $t('All Programs') }} ({{ totalCoursesCount }})
                    </h2>
                    <span v-if="isLoadingPrograms" class="text-sm text-gray-500">{{ $t('Loading...') }}</span>
                </div>

                <div
                    class="flex flex-col lg:flex-row gap-4 items-start lg:items-center justify-between mb-6"
                >
                    <!-- Left: Search Bar -->
                    <div class="relative w-full lg:max-w-md">
                        <input
                            v-model="searchQuery"
                            type="text"
                            :placeholder="$t('Search courses...')"
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
                            v-model:selectedAssignment="selectedAssignment"
                            :departments="departments"
                            :statusOptions="['Active', 'Upcoming', 'Completed']"
                            departmentLabel="Department"
                            :showAssignmentFilter="true"
                            @reset="resetFilters"
                        >
                            <template #trigger>
                                <button
                                    class="rounded-lg border transition-all duration-200 inline-flex gap-2 items-center px-4 py-2"
                                    :class="selectedDepartment.length + selectedStatus.length > 0 || selectedAssignment !== 'all' ? 'bg-[#2f837d]/10 border-[#2f837d] text-[#2f837d]' : 'border-[#d5dde7] hover:bg-gray-50 text-gray-700'"
                                >
                                    <ListFilterIcon class="h-4 w-4" />
                                    <p>
                                        {{ $t('Filter') }}
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
                                        {{ $t('Sort') }}
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
                            <p>{{ $t('Export') }}</p>
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
                            :href="`/admin/my-courses/${course.id}`"
                            :name="course.name"
                            :image_url="course.image_url"
                            :rating="course.rating"
                            :level="course.level"
                            :trainees_count="course.trainees_count"
                            :price="course.price"
                            :date="course.date"
                            :time="course.time"
                            :location="course.location"
                            :is-incomplete="course.isIncomplete"
                            :status="course.status"
                            :show-actions="true"
                            @edit="handleEditCourse(course)"
                            @delete="handleDeleteCourse(course.id)"
                        />
                    </div>

                    <!-- Empty State -->
                    <div
                        v-if="filteredCourses.length === 0"
                        class="text-center py-12"
                    >
                        <Archive class="mx-auto h-12 w-12 text-gray-400" />
                        <h3 class="mt-2 text-sm font-medium text-gray-900">
                            {{ $t('No courses found') }}
                        </h3>
                        <p class="mt-1 text-sm text-gray-500">
                            {{ $t('Adjust criteria') }}
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
                                {{ $t('Previous') }}
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
                                {{ $t('Next') }}
                            </button>
                        </div>

                        <!-- Result Counter (Right) -->
                        <div
                            class="text-sm text-gray-600 text-center sm:text-right"
                        >
                            {{ $t('Showing results', { start: String(startResult), end: String(endResult), total: String(totalResults) }) }}
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
                :show="showCreateModal"
                :course="editingCourse"
                uploadUrlPrefix="admin"
                :enable-preview-dialogs="false"
                @close="handleModalClose"
                @success="handleCreateProgram"
            />
        </div>
    </AdminLayout>
</template>
