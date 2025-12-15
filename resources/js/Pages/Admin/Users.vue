<script setup>
import { ref, computed, watch, onMounted } from "vue";
import { Head, router } from "@inertiajs/vue3";
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
import FilterModal from "@/Components/FilterModal.vue";
import SortModal from "@/Components/SortModal.vue";
import EditUserModal from "@/Components/EditUserModal.vue";

const props = defineProps({
    editUserId: {
        type: [String, Number],
        default: null,
    },
});

const users = ref([
    {
        id: 1,
        name: "John Doe",
        email: "john@example.com",
        contact: "0123456789",
        department: "IT",
        role: "Admin",
        status: "Active",
    },
    {
        id: 2,
        name: "Jane Smith",
        email: "jane@example.com",
        contact: "0123456791",
        department: "HR",
        role: "Trainer",
        status: "Active",
    },
    {
        id: 3,
        name: "Bob Johnson",
        email: "bob@example.com",
        contact: "0123456792",
        department: "Engineering",
        role: "Trainee",
        status: "Active",
    },
    {
        id: 4,
        name: "Alice Williams",
        email: "alice@example.com",
        contact: "0123456793",
        department: "Marketing",
        role: "Trainee",
        status: "Inactive",
    },
    {
        id: 5,
        name: "Charlie Brown",
        email: "charlie@example.com",
        contact: "0123456794",
        department: "IT",
        role: "Trainer",
        status: "Active",
    },
]);

const searchQuery = ref("");
const selectedDepartment = ref("all");
const selectedStatus = ref("all");
const activeTab = ref("trainees");
const sortColumn = ref("");
const sortDirection = ref("asc");
const showExportModal = ref(false);
const showFilterModal = ref(false);
const showSortModal = ref(false);
const currentPage = ref(1);
const itemsPerPage = ref(10);
const openStatusDropdown = ref(null);
const dropdownPosition = ref({ top: 0, left: 0 });
const showEditModal = ref(false);
const editingUser = ref(null);
const editForm = ref({
    name: "",
    email: "",
    phone: "",
    role: "",
    status: "",
    department: "",
});

// Available options for dropdowns
const roleOptions = ["Admin", "Trainer", "Trainee"];
const statusOptions = ["Active", "Inactive"];
const departmentOptions = computed(() => {
    return [...new Set(users.value.map((user) => user.department))];
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

// Filtered and sorted users
const filteredUsers = computed(() => {
    let result = users.value;

    // Filter by active tab
    if (activeTab.value === "trainees") {
        result = result.filter((user) => user.role === "Trainee");
    } else if (activeTab.value === "trainers") {
        result = result.filter((user) => user.role === "Trainer");
    }

    // Filter by search query
    if (searchQuery.value) {
        const query = searchQuery.value.toLowerCase();
        result = result.filter(
            (user) =>
                user.name.toLowerCase().includes(query) ||
                user.email.toLowerCase().includes(query) ||
                user.contact.toLowerCase().includes(query) ||
                user.department.toLowerCase().includes(query)
        );
    }

    // Filter by department
    if (selectedDepartment.value !== "all") {
        result = result.filter(
            (user) => user.department === selectedDepartment.value
        );
    }

    // Filter by status
    if (selectedStatus.value !== "all") {
        result = result.filter((user) => user.status === selectedStatus.value);
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
    const headers = ["ID", "Name", "Email", "Contact", "Department", "Status"];
    const csvData = filteredUsers.value.map((user) => [
        user.id,
        user.name,
        user.email,
        user.contact,
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
const changeUserStatus = (userId, newStatus) => {
    const user = users.value.find((u) => u.id === userId);
    if (user) {
        user.status = newStatus;
    }
    openStatusDropdown.value = null;
};

// Open edit modal
const openEditModal = (user) => {
    editingUser.value = user;
    editForm.value = {
        name: user.name,
        email: user.email,
        phone: user.contact,
        role: user.role,
        status: user.status,
        department: user.department,
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
    editForm.value = {
        name: "",
        email: "",
        phone: "",
        role: "",
        status: "",
        department: "",
    };
    // Update URL back to users page
    router.visit("/admin/users", {
        preserveState: true,
        preserveScroll: true,
        replace: true,
    });
};

// Save edited user
const saveUser = () => {
    if (editingUser.value) {
        const user = users.value.find((u) => u.id === editingUser.value.id);
        if (user) {
            user.name = editForm.value.name;
            user.email = editForm.value.email;
            user.contact = editForm.value.phone;
            user.role = editForm.value.role;
            user.status = editForm.value.status;
            user.department = editForm.value.department;
        }
    }
    closeEditModal();
};

// Watch for editUserId prop to open modal when navigating directly to edit URL
watch(
    () => props.editUserId,
    (newId) => {
        if (newId) {
            const user = users.value.find((u) => u.id === parseInt(newId));
            if (user) {
                editingUser.value = user;
                editForm.value = {
                    name: user.name,
                    email: user.email,
                    phone: user.contact,
                    role: user.role,
                    status: user.status,
                    department: user.department,
                };
                showEditModal.value = true;
            }
        }
    },
    { immediate: true }
);
</script>

<template>
    <Head title="Users" />
    <AdminLayout>
        <div class="space-y-6">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900">Users</h1>
                    <p class="mt-2 text-sm text-gray-600">
                        Manage all users in the system
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
                            left: activeTab === 'trainees' ? '4px' : '50%',
                            right: activeTab === 'trainees' ? '50%' : '4px',
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
                        Trainees
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
                        Trainers
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
                        <template v-else>
                            Trainers ({{ trainersCount }})
                        </template>
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
                                placeholder="Search users..."
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
                <!-- Users Table -->
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
                                            Name
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
                                            Contact
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
                                        @click="sort('department')"
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer hover:bg-gray-100"
                                    >
                                        <div class="flex items-center gap-2">
                                            Department
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
                                                        sortDirection ===
                                                        'desc',
                                                }"
                                            />
                                        </div>
                                    </th>
                                    <th
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
                                    >
                                        Actions
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
                                        <div>
                                            <div class="font-medium">
                                                {{ user.email }}
                                            </div>
                                            <div class="text-gray-500">
                                                {{
                                                    formatPhoneNumber(
                                                        user.contact
                                                    )
                                                }}
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span>
                                            {{ user.department }}
                                        </span>
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
                                        <button
                                            class="text-gray-600 hover:text-red-800 transition-colors inline-flex items-center gap-1"
                                            title="Delete"
                                        >
                                            <Trash2 class="h-4 w-4" />
                                        </button>
                                        <button
                                            @click="openEditModal(user)"
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
                @close="showExportModal = false"
                @exportCSV="exportToCSV"
                @exportPDF="exportToPDF"
            />

            <FilterModal
                :show="showFilterModal"
                v-model:selectedDepartment="selectedDepartment"
                v-model:selectedStatus="selectedStatus"
                :departments="departments"
                @close="showFilterModal = false"
                @reset="resetFilters"
            />

            <SortModal
                :show="showSortModal"
                :sortColumn="sortColumn"
                :sortDirection="sortDirection"
                @close="showSortModal = false"
                @sort="applySort"
                @reset="resetSort"
            />

            <!-- Edit User Modal -->
            <EditUserModal
                :show="showEditModal"
                :editing-user="editingUser"
                v-model:edit-form="editForm"
                :role-options="roleOptions"
                :status-options="statusOptions"
                :department-options="departmentOptions"
                @close="closeEditModal"
                @save="saveUser"
            />
        </div>
    </AdminLayout>
</template>
