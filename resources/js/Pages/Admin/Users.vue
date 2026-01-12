<script setup>
import { ref, computed, watch, onMounted } from "vue";
import { Head, router } from "@inertiajs/vue3";
import axios from "axios";
import { useToast } from "vue-toastification";
import AdminLayout from "@/Layouts/AdminLayout.vue";
import {
    Search,
    Archive,
    Users,
    ListFilterIcon,
    ArrowDownNarrowWide,
    Share,
    ChevronUp,
    ChevronDown,
    Pencil,
    Trash2,
} from "lucide-vue-next";
import ExportModal from "@/Components/ExportModal.vue";
import FilterDropdown from "@/Components/FilterDropdown.vue";
import SortDropdown from "@/Components/SortDropdown.vue";
import EditUserModal from "@/Components/EditUserModal.vue";
import TableActionButton from "@/Components/TableActionButton.vue";
import LoadingSpinner from "@/Components/LoadingSpinner.vue";
import jsPDF from "jspdf";
import "jspdf-autotable";

const props = defineProps({
    editUserId: {
        type: [String, Number],
        default: null,
    },
});

const toast = useToast();
const users = ref([]);
const isLoading = ref(false);

const searchQuery = ref("");
const selectedDepartment = ref([]);
const selectedStatus = ref([]);
const activeTab = ref("trainees");
const sortColumn = ref("");
const sortDirection = ref("asc");
const showExportModal = ref(false);
const currentPage = ref(1);
const itemsPerPage = ref(10);
const openStatusDropdown = ref(null);
const dropdownPosition = ref({ top: 0, left: 0 });
const showEditModal = ref(false);
const editingUser = ref(null);
const editForm = ref({
    name: "",
    email: "",
    role: "",
    status: "",
    // Profile Fields
    prefix: "",
    first_name: "",
    last_name: "",
    phone: "",
    date_of_birth: "",
    gender: "",
    sub_category: "",
    faculty: "",
    major: "",
    student_id: "",
    degree_level: "",
    year_of_study: "",
    personnel_id: "",
    organization: "",
    department: "",
    job_position: "",
    employment_status: "",
    personnel_type: "",
    category: "",
    bio: "",
});

// Fetch users from API
const fetchUsers = async () => {
    isLoading.value = true;
    try {
        const response = await axios.get('/api/admin/users', {
            params: {
                per_page: 100 // Get more users for client-side filtering
            }
        });

        // Transform API data to match component structure
        const apiUsers = response.data?.data?.data || response.data?.data || [];
        users.value = apiUsers.map(user => {
            const rawRole = user.role?.name || 'trainee';
            // Check if role is effectively a student/trainee
            const isStudent = rawRole === 'student' || rawRole === 'trainee';

            // Determine Category and Type
            let category = user.profile?.category;
            let type = 'External'; // Default

            // If category is set in DB
            if (category) {
                if (['Student', 'Personnel'].includes(category)) {
                    type = 'Internal';
                } else {
                    type = 'External';
                }
            } else {
                // Fallback inference
                if (user.profile?.faculty || user.profile?.student_id || user.profile?.personnel_id) {
                    type = 'Internal';
                    category = user.profile?.student_id ? 'Student' : 'Personnel';
                }
            }

            return {
                id: user.id,
                name: user.name,
                email: user.email,
                contact: user.profile?.phone || user.phone || '-',
                
                // Context fields
                type: type,
                category: category || (isStudent ? 'Student' : 'Outsider'),
                is_student: isStudent,
                student_id: user.profile?.student_id,
                personnel_id: user.profile?.personnel_id,
                faculty: user.profile?.faculty,
                major: user.profile?.major,
                job_position: user.profile?.job_position,
                organization: user.profile?.organization,
                real_department: user.profile?.department, // Actual department field
                joined_at: user.created_at,

                // Full Profile for Editing
                profile: user.profile || {},

                // For Filter/Sort compatibility (Polymorphic 'Department' column)
                department: user.profile?.department || user.profile?.faculty || '-',
                
                role: user.role?.name ? capitalizeRole(user.role.name) : 'Trainee',
                status: user.status === 'active' ? 'Active' : 'Inactive',
            };
        });
    } catch (error) {
        console.error('Error fetching users:', error);
        toast.error('Unable to load users');
        users.value = [];
    } finally {
        isLoading.value = false;
    }
};

// Helper function to capitalize role names
const capitalizeRole = (role) => {
    if (role === 'student') return 'Trainee';
    return role.charAt(0).toUpperCase() + role.slice(1);
};

// Available options for dropdowns
const roleOptions = ["Admin", "Trainer", "Trainee"];
const statusOptions = ["Active", "Inactive"]; // Used for status dropdown in main table
const departmentOptions = computed(() => {
    return [...new Set(users.value.map((user) => user.department))].filter(d => d !== '-');
});

// Format phone number to 012-345-6789
const formatPhoneNumber = (phone) => {
    if (!phone) return "";
    const cleaned = phone.replace(/\D/g, "");
    if (cleaned.length === 10) {
        return `${cleaned.slice(0, 3)}-${cleaned.slice(3, 6)}-${cleaned.slice(
            6
        )}`;
    }
    return phone;
};

// Get unique departments for filter
const departments = computed(() => {
    const depts = [...new Set(users.value.map((user) => user.department))];
    return depts;
});

// Count users by role
const traineesCount = computed(() => {
    return users.value.filter((user) => user.role === "Trainee").length;
});

const trainersCount = computed(() => {
    return users.value.filter((user) => user.role === "Trainer").length;
});

const adminsCount = computed(() => {
    return users.value.filter((user) => user.role === "Admin").length;
});

// Filtered and sorted users
const filteredUsers = computed(() => {
    let result = users.value;

    // Filter by active tab
    if (activeTab.value === "trainees") {
        result = result.filter((user) => user.role === "Trainee");
    } else if (activeTab.value === "trainers") {
        result = result.filter((user) => user.role === "Trainer");
    } else if (activeTab.value === "admins") {
        result = result.filter((user) => user.role === "Admin");
    }

    // Filter by search query
    if (searchQuery.value) {
        const query = searchQuery.value.toLowerCase();
        result = result.filter(
            (user) =>
                user.name.toLowerCase().includes(query) ||
                user.email.toLowerCase().includes(query) ||
                user.contact.toLowerCase().includes(query) ||
                user.department.toLowerCase().includes(query) ||
                (user.student_id && user.student_id.toLowerCase().includes(query)) ||
                (user.job_position && user.job_position.toLowerCase().includes(query)) ||
                (user.major && user.major.toLowerCase().includes(query)) ||
                (user.organization && user.organization.toLowerCase().includes(query))
        );
    }

    // Filter by department
    if (selectedDepartment.value.length > 0) {
        result = result.filter((user) =>
            selectedDepartment.value.includes(user.department)
        );
    }

    // Filter by status
    if (selectedStatus.value.length > 0) {
        result = result.filter((user) =>
            selectedStatus.value.includes(user.status)
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
const totalResults = computed(() => filteredUsers.value.length);
const totalPages = computed(() =>
    Math.ceil(totalResults.value / itemsPerPage.value)
);

const paginatedUsers = computed(() => {
    const start = (currentPage.value - 1) * itemsPerPage.value;
    const end = start + itemsPerPage.value;
    return filteredUsers.value.slice(start, end);
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
watch([searchQuery, selectedDepartment, selectedStatus, activeTab], () => {
    currentPage.value = 1;
});

// Close dropdown when clicking outside
watch(openStatusDropdown, (newVal) => {
    if (newVal) {
        const handleClickOutside = (e) => {
            if (!e.target.closest('.status-dropdown-trigger') && !e.target.closest('.status-dropdown-menu')) {
                openStatusDropdown.value = null;
                document.removeEventListener('click', handleClickOutside);
            }
        };
        setTimeout(() => {
            document.addEventListener('click', handleClickOutside);
        }, 0);
    }
});

// Sort function (for headers - toggles)
const sort = (column) => {
    if (sortColumn.value === column) {
        sortDirection.value = sortDirection.value === "asc" ? "desc" : "asc";
    } else {
        sortColumn.value = column;
        sortDirection.value = "asc";
    }
};

// Sort handler for Dropdown (explicit direction)
const handleSort = ({ column, direction }) => {
    sortColumn.value = column;
    sortDirection.value = direction;
};

// Export to CSV
const exportToCSV = () => {
    const headers = ["ID", "Name", "Email", "Contact", "Type", "Department", "Status"];
    const csvData = filteredUsers.value.map((user) => [
        user.id,
        user.name,
        user.email,
        user.contact,
        user.type,
        user.department,
        user.status,
    ]);

    const csvContent = [
        headers.join(","),
        ...csvData.map((row) => row.join(",")),
    ].join("\n");

    const blob = new Blob([csvContent], { type: "text/csv" });
    const url = window.URL.createObjectURL(blob);
    const a = document.createElement("a");
    a.href = url;
    a.download = "users.csv";
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
        doc.text("Users Report", 14, 20);

        // Add generation date
        doc.setFontSize(10);
        doc.text(`Generated: ${new Date().toLocaleDateString()}`, 14, 28);

        // Prepare table data with null-safe values
        const headers = [["ID", "Name", "Email", "Contact", "Type", "Department", "Status"]];
        const data = filteredUsers.value.map((user) => [
            user.id ?? '',
            user.name ?? '',
            user.email ?? '',
            user.contact ?? '',
            user.type ?? '',
            user.department ?? '',
            user.status ?? '',
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
        doc.save("users.pdf");
    } catch (error) {
        console.error('Error generating PDF:', error);
        alert(`Failed to generate PDF: ${error?.message || 'Unknown error'}. Please check the console for details.`);
    } finally {
        showExportModal.value = false;
    }
};

// Reset filters
const resetFilters = () => {
    selectedDepartment.value = [];
    selectedStatus.value = [];
};

// Reset sort
const resetSort = () => {
    sortColumn.value = "";
    sortDirection.value = "asc";
};

// Toggle status dropdown
const toggleStatusDropdown = (userId, event) => {
    if (openStatusDropdown.value === userId) {
        openStatusDropdown.value = null;
    } else {
        openStatusDropdown.value = userId;
        const rect = event.target.getBoundingClientRect();
        dropdownPosition.value = {
            top: rect.bottom + window.scrollY,
            left: rect.left + window.scrollX,
        };
    }
};

// Change user status
const changeUserStatus = async (userId, newStatus) => {
    const user = users.value.find((u) => u.id === userId);
    if (!user) return;

    try {
        const statusMapping = {
            'Active': 'active',
            'Inactive': 'inactive'
        };

        // Call API to update user status
        await axios.put(`/api/admin/users/${userId}`, {
            status: statusMapping[newStatus] || newStatus.toLowerCase()
        });

        // Update local state after successful API call
        user.status = newStatus;
        toast.success(`User status updated to ${newStatus}`);
    } catch (error) {
        console.error('Error updating user status:', error);
        const errorMessage = error?.response?.data?.message || 'Failed to update user status';
        toast.error(errorMessage);
    } finally {
        openStatusDropdown.value = null;
    }
};

// Format date for input (YYYY-MM-DD)
const formatDateForInput = (dateString) => {
    if (!dateString) return "";
    // Handle both ISO string and simple date string
    return dateString.split('T')[0];
};

// Open edit modal
const openEditModal = (user) => {
    editingUser.value = user;
    
    // Map user and profile data to edit form
    editForm.value = {
        name: user.name,
        email: user.email,
        role: user.role,
        status: user.status || 'Active', // Ensure status is mapped
        
        // Profile fields
        prefix: user.profile?.prefix || "",
        first_name: user.profile?.first_name || "",
        last_name: user.profile?.last_name || "",
        phone: user.profile?.phone || "",
        date_of_birth: formatDateForInput(user.profile?.date_of_birth),
        gender: user.profile?.gender || "",
        sub_category: user.profile?.sub_category || "",
        faculty: user.profile?.faculty || "",
        major: user.profile?.major || "",
        student_id: user.profile?.student_id || "",
        degree_level: user.profile?.degree_level || "",
        year_of_study: user.profile?.year_of_study || "",
        personnel_id: user.profile?.personnel_id || "",
        organization: user.profile?.organization || "",
        department: user.profile?.department || "",
        job_position: user.profile?.job_position || "",
        employment_status: user.profile?.employment_status || "",
        personnel_type: user.profile?.personnel_type || "",
        category: user.profile?.category || "",
        bio: user.profile?.bio || "",
    };
    
    showEditModal.value = true;
    // Update URL without reloading
    router.visit(`/admin/users/${user.id}/edit`, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
    });
};

// Close edit modal
const closeEditModal = () => {
    showEditModal.value = false;
    editingUser.value = null;
    // Reset form
    editForm.value = {
        name: "",
        email: "",
        role: "",
        status: "",
        prefix: "",
        first_name: "",
        last_name: "",
        phone: "",
        date_of_birth: "",
        gender: "",
        sub_category: "",
        faculty: "",
        major: "",
        student_id: "",
        degree_level: "",
        year_of_study: "",
        personnel_id: "",
        organization: "",
        department: "",
        job_position: "",
        employment_status: "",
        personnel_type: "",
        category: "",
        bio: "",
    };
    // Update URL back to users page
    router.visit("/admin/users", {
        preserveState: true,
        preserveScroll: true,
        replace: true,
    });
};

// Save edited user
const saveUser = async () => {
    if (!editingUser.value) return;

    try {
        // Convert role to lowercase for API (Admin -> admin, Trainer -> trainer, Trainee -> trainee)
        const roleMapping = {
            'Admin': 'admin',
            'Trainer': 'trainer',
            'Trainee': 'trainee'
        };
        
        const statusMapping = {
            'Active': 'active',
            'Inactive': 'inactive'
        };

        const payload = {
            ...editForm.value,
            role: roleMapping[editForm.value.role] || editForm.value.role.toLowerCase(),
            status: statusMapping[editForm.value.status] || editForm.value.status.toLowerCase(),
        };

        // Call API to update user
        const response = await axios.put(`/api/admin/users/${editingUser.value.id}`, payload);
        const updatedUser = response.data?.data;

        // Update local state after successful API call
        const userIndex = users.value.findIndex((u) => u.id === editingUser.value.id);
        if (userIndex !== -1 && updatedUser) {
            // Re-transform the updated user to match the component structure
            const rawRole = updatedUser.role?.name || 'trainee';
            const isStudent = rawRole === 'student' || rawRole === 'trainee';
            let category = updatedUser.profile?.category;
            let type = 'External';
            if (category) {
                if (['Student', 'Personnel'].includes(category)) {
                    type = 'Internal';
                } else {
                    type = 'External';
                }
            } else {
                if (updatedUser.profile?.faculty || updatedUser.profile?.student_id || updatedUser.profile?.personnel_id) {
                    type = 'Internal';
                    category = updatedUser.profile?.student_id ? 'Student' : 'Personnel';
                }
            }

            users.value[userIndex] = {
                id: updatedUser.id,
                name: updatedUser.name,
                email: updatedUser.email,
                contact: updatedUser.profile?.phone || updatedUser.phone || '-',
                type: type,
                category: category || (isStudent ? 'Student' : 'Outsider'),
                is_student: isStudent,
                student_id: updatedUser.profile?.student_id,
                personnel_id: updatedUser.profile?.personnel_id,
                faculty: updatedUser.profile?.faculty,
                major: updatedUser.profile?.major,
                job_position: updatedUser.profile?.job_position,
                organization: updatedUser.profile?.organization,
                real_department: updatedUser.profile?.department,
                joined_at: updatedUser.created_at,
                profile: updatedUser.profile || {},
                department: updatedUser.profile?.department || updatedUser.profile?.faculty || '-',
                role: updatedUser.role?.name ? capitalizeRole(updatedUser.role.name) : 'Trainee',
                status: updatedUser.status === 'active' ? 'Active' : 'Inactive',
            };
        }

        toast.success('User updated successfully');
        closeEditModal();
    } catch (error) {
        console.error('Error updating user:', error);
        const errorMessage = error?.response?.data?.message || 'Failed to update user';
        toast.error(errorMessage);
    }
};

// Watch for editUserId prop to open modal when navigating directly to edit URL
watch(
    () => props.editUserId,
    (newId) => {
        if (newId) {
            const user = users.value.find((u) => u.id === parseInt(newId));
            if (user) {
                openEditModal(user);
            }
        }
    },
    { immediate: true }
);

// Fetch users on component mount
onMounted(() => {
    fetchUsers();
});
</script>

<template>
    <Head title="Users" />
    <AdminLayout>
        <div class="space-y-6">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900">{{ $t('Users') }}</h1>
                    <p class="mt-2 text-sm text-gray-600">
                        {{ $t('Manage users description') }}
                    </p>
                </div>
            </div>

            <!-- Switch -->
            <div
                class="bg-white rounded-[25px] shadow-sm p-4 border border-[#dfe5ef]"
            >
                <div
                    class="inline-flex bg-[#f1f5f9] rounded-[10px] p-1 relative"
                >
                    <!-- Sliding background -->
                    <div
                        class="absolute top-1 bottom-1 bg-white rounded-[10px] shadow-sm transition-all duration-300 ease-in-out"
                        :style="{
                            left: activeTab === 'trainees' ? '4px' : activeTab === 'trainers' ? '33.33%' : '66.66%',
                            width: activeTab === 'trainees' || activeTab === 'trainers' || activeTab === 'admins' ? '33.33%' : '33.33%',
                        }"
                    ></div>

                    <!-- Buttons -->
                    <button
                        :class="[
                            'px-6 py-2 rounded-md font-medium transition-colors duration-300 relative z-10 flex-1',
                            activeTab === 'trainees'
                                ? 'text-[#2f837d]'
                                : 'text-[#64748b] hover:text-gray-900',
                        ]"
                        @click="activeTab = 'trainees'"
                    >
                        {{ $t('Trainees') }}
                    </button>
                    <button
                        :class="[
                            'px-6 py-2 rounded-md font-medium transition-colors duration-300 relative z-10 flex-1',
                            activeTab === 'trainers'
                                ? 'text-[#2f837d]'
                                : 'text-[#64748b] hover:text-gray-900',
                        ]"
                        @click="activeTab = 'trainers'"
                    >
                        {{ $t('Trainers') }}
                    </button>
                    <button
                        :class="[
                            'px-6 py-2 rounded-md font-medium transition-colors duration-300 relative z-10 flex-1',
                            activeTab === 'admins'
                                ? 'text-[#2f837d]'
                                : 'text-[#64748b] hover:text-gray-900',
                        ]"
                        @click="activeTab = 'admins'"
                    >
                        {{ $t('Admins') }}
                    </button>
                </div>
            </div>

            <!-- Search, Filter, and Export Controls -->
            <div
                class="bg-white rounded-[25px] shadow-sm p-6 border border-[#dfe5ef]"
            >
                <div class="flex items-center gap-3 mb-6">
                    <Users class="h-6 w-6 text-[#2f837d]" />
                    <h2 class="text-xl font-semibold text-gray-900">
                        <template v-if="activeTab === 'trainees'">
                            Trainees ({{ traineesCount }})
                        </template>
                        <template v-else-if="activeTab === 'trainers'">
                            Trainers ({{ trainersCount }})
                        </template>
                        <template v-else>
                            Admins ({{ adminsCount }})
                        </template>
                    </h2>
                </div>

                <div
                    class="flex flex-col lg:flex-row gap-4 items-start lg:items-center justify-between mb-6"
                >
                    <!-- Left: Search Bar -->
                    <div class="relative w-full lg:max-w-md">
                        <input
                            v-model="searchQuery"
                            type="text"
                            :placeholder="$t('Search users...')"
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
                            @reset="resetFilters"
                        >
                            <template #trigger>
                                <button
                                    class="rounded-lg border transition-all duration-200 inline-flex gap-2 items-center px-4 py-2"
                                    :class="selectedDepartment.length + selectedStatus.length > 0 ? 'bg-[#2f837d]/10 border-[#2f837d] text-[#2f837d]' : 'border-[#d5dde7] hover:bg-gray-50 text-gray-700'"
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
                <!-- Loading State -->
                <div v-if="isLoading" class="flex items-center justify-center py-12">
                    <LoadingSpinner size="lg" text="Loading users..." />
                </div>

                <!-- Users Table -->
                <div
                    v-else
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
                                            {{ $t('ID') }}
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
                                        @click="sort('name')"
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer hover:bg-gray-100"
                                    >
                                        <div class="flex items-center gap-2">
                                            {{ $t('Name') }}
                                            <ChevronUp
                                                v-if="sortColumn === 'name'"
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
                                        @click="sort('contact')"
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer hover:bg-gray-100"
                                    >
                                        <div class="flex items-center gap-2">
                                            {{ $t('Contact') }}
                                            <ChevronUp
                                                v-if="sortColumn === 'contact'"
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
                                        @click="sort('type')"
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer hover:bg-gray-100"
                                    >
                                        <div class="flex items-center gap-2">
                                            {{ $t('Type') }}
                                            <ChevronUp
                                                v-if="sortColumn === 'type'"
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
                                        @click="sort('department')"
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer hover:bg-gray-100"
                                    >
                                        <div class="flex items-center gap-2">
                                            {{ activeTab === 'trainees' ? 'Education / Affiliation' : 'Position / Department' }}
                                            <ChevronUp
                                                v-if="
                                                    sortColumn === 'department'
                                                "
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
                                        @click="sort('joined_at')"
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer hover:bg-gray-100"
                                    >
                                        <div class="flex items-center gap-2">
                                            {{ $t('Joined') }}
                                            <ChevronUp
                                                v-if="sortColumn === 'joined_at'"
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
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer hover:bg-gray-100"
                                    >
                                        <div class="flex items-center gap-2">
                                            {{ $t('Status') }}
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
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
                                    >
                                        {{ $t('Actions') }}
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                <tr
                                    v-for="(user, index) in paginatedUsers"
                                    :key="user.id"
                                    :class="[
                                        'transition-colors',
                                        index % 2 === 0 ? 'bg-white' : 'bg-gray-50',
                                        'hover:bg-gray-100'
                                    ]"
                                >
                                    <td
                                        class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"
                                    >
                                        {{ user.id }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center">
                                            <div
                                                class="flex-shrink-0 h-10 w-10 bg-gradient-to-r from-blue-500 to-purple-600 rounded-full flex items-center justify-center text-white font-semibold"
                                            >
                                                {{ user.name.charAt(0) }}
                                            </div>
                                            <div class="ml-4">
                                                <div
                                                    class="text-sm font-medium text-gray-900"
                                                >
                                                    {{ user.name }}
                                                </div>
                                                <div
                                                    class="text-sm text-gray-500"
                                                >
                                                    {{ user.role }}
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                    <td
                                        class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"
                                    >
                                        <div class="space-y-1">
                                            <div class="flex items-center gap-1.5">
                                                <span class="text-xs font-bold text-gray-400 uppercase w-12 italic">Email:</span>
                                                <span class="font-medium truncate max-w-[180px]" :title="user.email">{{ user.email }}</span>
                                            </div>
                                            <div class="flex items-center gap-1.5">
                                                <span class="text-xs font-bold text-gray-400 uppercase w-12 italic">Phone:</span>
                                                <span class="text-gray-600">{{ formatPhoneNumber(user.contact) || '-' }}</span>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span
                                            class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
                                            :class="user.type === 'Internal' ? 'bg-indigo-100 text-indigo-800' : 'bg-orange-100 text-orange-800'"
                                        >
                                            {{ user.type }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <!-- Internal User View -->
                                        <div v-if="user.type === 'Internal'" class="space-y-1">
                                            <!-- Student -->
                                            <template v-if="user.category === 'Student'">
                                                <div v-if="user.student_id" class="flex items-center gap-1.5">
                                                    <span class="text-xs font-bold text-gray-400 uppercase w-14 italic">ID:</span>
                                                    <span class="text-xs font-mono bg-blue-50 text-blue-700 px-1.5 py-0.5 rounded leading-none">{{ user.student_id }}</span>
                                                </div>
                                                <div class="flex items-center gap-1.5">
                                                    <span class="text-xs font-bold text-gray-400 uppercase w-14 italic">Faculty:</span>
                                                    <span class="text-sm text-gray-900">{{ user.faculty || '-' }}</span>
                                                </div>
                                                <div class="flex items-center gap-1.5">
                                                    <span class="text-xs font-bold text-gray-400 uppercase w-14 italic">Major:</span>
                                                    <span class="text-xs text-gray-500">{{ user.major || '-' }}</span>
                                                </div>
                                            </template>
                                            <!-- Personnel -->
                                            <template v-else>
                                                 <div v-if="user.personnel_id" class="flex items-center gap-1.5">
                                                    <span class="text-xs font-bold text-gray-400 uppercase w-14 italic">ID:</span>
                                                    <span class="text-xs font-mono bg-purple-50 text-purple-700 px-1.5 py-0.5 rounded leading-none">{{ user.personnel_id }}</span>
                                                </div>
                                                <div class="flex items-center gap-1.5">
                                                    <span class="text-xs font-bold text-gray-400 uppercase w-14 italic">Dept:</span>
                                                    <span class="text-sm text-gray-900">{{ user.real_department || '-' }}</span>
                                                </div>
                                                <div class="flex items-center gap-1.5">
                                                    <span class="text-xs font-bold text-gray-400 uppercase w-14 italic">Position:</span>
                                                    <span class="text-xs text-gray-500">{{ user.job_position || '-' }}</span>
                                                </div>
                                            </template>
                                        </div>
                                        <!-- External User View -->
                                        <div v-else class="space-y-1">
                                            <div class="flex items-center gap-1.5">
                                                <span class="text-xs font-bold text-gray-400 uppercase w-14 italic">Org:</span>
                                                <span class="text-sm font-medium text-gray-900">{{ user.organization || '-' }}</span>
                                            </div>
                                            <div class="flex items-center gap-1.5">
                                                <span class="text-xs font-bold text-gray-400 uppercase w-14 italic">Position:</span>
                                                <span class="text-xs text-gray-600">{{ user.job_position || '-' }}</span>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        {{ user.joined_at ? new Date(user.joined_at).toLocaleDateString() : '-' }}
                                    </td>
                                    <td
                                        class="px-6 py-4 whitespace-nowrap"
                                    >
                                        <button
                                            @click="
                                                toggleStatusDropdown(user.id, $event)
                                            "
                                            :class="[
                                                'status-dropdown-trigger px-3 py-2 inline-flex items-center justify-center gap-1 leading-5 rounded-md cursor-pointer hover:opacity-80 transition-opacity w-[100px]',
                                                user.status === 'Active'
                                                    ? 'bg-green-100 text-green-800'
                                                    : 'bg-gray-100 text-gray-800',
                                            ]"
                                        >
                                            {{ user.status }}
                                            <ChevronDown class="h-3 w-3" />
                                        </button>

                                        <!-- Dropdown Menu (Teleported to body) -->
                                        <Teleport to="body">
                                            <div
                                                v-if="
                                                    openStatusDropdown === user.id
                                                "
                                                class="status-dropdown-menu fixed z-50 bg-white border border-gray-200 rounded-lg shadow-lg py-1 min-w-[120px]"
                                                :style="{
                                                    top: dropdownPosition.top + 'px',
                                                    left: dropdownPosition.left + 'px',
                                                }"
                                            >
                                                <button
                                                    @click="
                                                        changeUserStatus(
                                                            user.id,
                                                            'Active'
                                                        )
                                                    "
                                                    class="w-full text-left px-4 py-2 text-sm hover:bg-gray-50 flex items-center gap-2"
                                                    :class="
                                                        user.status === 'Active'
                                                            ? 'bg-green-50 text-green-800'
                                                            : 'text-gray-700'
                                                    "
                                                >
                                                    <span
                                                        class="h-2 w-2 rounded-full bg-green-500"
                                                    ></span>
                                                    Active
                                                </button>
                                                <button
                                                    @click="
                                                        changeUserStatus(
                                                            user.id,
                                                            'Inactive'
                                                        )
                                                    "
                                                    class="w-full text-left px-4 py-2 text-sm hover:bg-gray-50 flex items-center gap-2"
                                                    :class="
                                                        user.status === 'Inactive'
                                                            ? 'bg-gray-50 text-gray-800'
                                                            : 'text-gray-700'
                                                    "
                                                >
                                                    <span
                                                        class="h-2 w-2 rounded-full bg-gray-500"
                                                    ></span>
                                                    Inactive
                                                </button>
                                            </div>
                                        </Teleport>
                                    </td>
                                    <td
                                        class="px-6 py-4 whitespace-nowrap text-sm font-medium space-x-6"
                                    >
                                        <TableActionButton
                                            :icon="Trash2"
                                            variant="delete"
                                            title="Delete"
                                        />
                                        <TableActionButton
                                            :icon="Pencil"
                                            variant="edit"
                                            title="Edit"
                                            @click="openEditModal(user)"
                                        />
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Empty State -->
                    <div
                        v-if="filteredUsers.length === 0"
                        class="text-center py-12"
                    >
                        <Archive class="mx-auto h-12 w-12 text-gray-400" />
                        <h3 class="mt-2 text-sm font-medium text-gray-900">
                            No users found
                        </h3>
                        <p class="mt-1 text-sm text-gray-500">
                            Try adjusting your search or filter criteria.
                        </p>
                    </div>

                    <!-- Pagination and Result Counter -->
                    <div
                        v-if="filteredUsers.length > 0"
                        class="flex items-center justify-between px-6 py-4 bg-gray-50 border-t border-gray-200"
                    >
                        <!-- Pagination (Left) -->
                        <div class="flex items-center gap-2">
                            <button
                                @click="goToPage(currentPage - 1)"
                                :disabled="currentPage === 1"
                                :class="[
                                    'px-3 py-1 rounded border transition-colors',
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
                                            'px-3 py-1 rounded border transition-colors',
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
                                        class="px-2 text-gray-500"
                                    >
                                        ...
                                    </span>
                                </template>
                            </div>

                            <button
                                @click="goToPage(currentPage + 1)"
                                :disabled="currentPage === totalPages"
                                :class="[
                                    'px-3 py-1 rounded border transition-colors',
                                    currentPage === totalPages
                                        ? 'bg-gray-100 text-gray-400 cursor-not-allowed border-gray-200'
                                        : 'bg-white text-gray-700 border-gray-300 hover:bg-gray-50',
                                ]"
                            >
                                Next
                            </button>
                        </div>

                        <!-- Result Counter (Right) -->
                        <div class="text-sm text-gray-600">
                            Showing {{ startResult }}-{{ endResult }} of
                            {{ totalResults }} results
                        </div>
                    </div>
                </div>
            </div>

            <!-- Modals -->
            <ExportModal
                :show="showExportModal"
                :activeTab="activeTab"
                :dataType="activeTab"
                @close="showExportModal = false"
                @exportCSV="exportToCSV"
                @exportPDF="exportToPDF"
            />



            <!-- Edit User Modal -->
            <EditUserModal
                :show="showEditModal"
                :editing-user="editingUser"
                v-model:edit-form="editForm"
                :role-options="roleOptions"
                :department-options="departmentOptions"
                @close="closeEditModal"
                @save="saveUser"
            />
        </div>
    </AdminLayout>
</template>
