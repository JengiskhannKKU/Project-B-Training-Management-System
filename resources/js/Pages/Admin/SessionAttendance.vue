<script setup>
import { ref, computed, watch, onMounted } from "vue";
import { Head, Link, usePage } from "@inertiajs/vue3";
import AdminLayout from "@/Layouts/AdminLayout.vue";
import axios from "axios";
import { useToast } from "vue-toastification";
import {
    Search,
    Archive,
    Calendar,
    Clock,
    MapPin,
    ChevronUp,
    ListFilterIcon,
    ArrowDownNarrowWide,
    Share,
    CheckCircle,
    XCircle,
    ArrowLeft,
    Award,
    Eye,
    Download,
    UserRoundCheck,
    UserRoundX,
} from "lucide-vue-next";
import ExportModal from "@/Components/ExportModal.vue";
import FilterDropdown from "@/Components/FilterDropdown.vue";
import SortDropdown from "@/Components/SortDropdown.vue";
import ConfirmationDialog from "@/Components/ConfirmationDialog.vue";
import SessionDayTabs from "@/Components/SessionDayTabs.vue";
import { formatDate, formatTime } from "@/utils/dateFormatter";
import jsPDF from "jspdf";
import "jspdf-autotable";

// Props from route
const props = defineProps({
    courseCode: {
        type: String,
        required: true
    },
    sessionId: {
        type: [Number, String],
        required: true
    }
});

// Toast
const toast = useToast();
const page = usePage();
const ensureCsrf = () => axios.get('/sanctum/csrf-cookie');

// State for save functionality
const isLoading = ref(true);
const lastAutoSaved = ref(null);

// Data from backend
const sessionInfo = ref({
    id: null,
    title: "",
    program: null,
    start_date: "",
    end_date: "",
    start_time: "",
    end_time: "",
    location: "",
    status: "",
    trainer_id: null,
});

const trainees = ref([]);
const sessionDays = ref([]);
const selectedDay = ref(null);
const attendanceMatrix = ref({});

// Attendance summary from API
const attendanceSummary = ref({
    total: 0,
    present: 0,
    absent: 0,
    late: 0,
    leave_early: 0,
    not_marked: 0,
});

const certificates = ref([]);
const isLoadingCertificates = ref(false);
const showGenerateCertificatesModal = ref(false);
const isGeneratingCertificates = ref(false);
const generateCertificatesResult = ref(null);

const searchQuery = ref("");
const selectedDepartment = ref([]);
const selectedStatus = ref([]);
const sortColumn = ref("");
const sortDirection = ref("asc");
const showExportModal = ref(false);
const currentPage = ref(1);
const itemsPerPage = ref(10);

// Format phone number to 012-345-6789
const formatPhoneNumber = (phone) => {
    if (!phone) return "";
    const cleaned = phone.replace(/\D/g, "");
    if (cleaned.length === 10) {
        return `${cleaned.slice(0, 3)}-${cleaned.slice(3, 6)}-${cleaned.slice(6)}`;
    }
    return phone;
};

// Get unique departments for filter
const departments = computed(() => {
    return [...new Set(trainees.value.map((trainee) => trainee.department))];
});

// Select a session day
const selectDay = (day) => {
    selectedDay.value = day;
};

// Filtered and sorted trainees (uses currentDayTrainees for multi-day)
const filteredTrainees = computed(() => {
    let result = showDayTabs.value ? currentDayTrainees.value : trainees.value;

    // Filter by search query
    if (searchQuery.value) {
        const query = searchQuery.value.toLowerCase();
        result = result.filter(
            (trainee) =>
                trainee.name.toLowerCase().includes(query) ||
                trainee.email.toLowerCase().includes(query) ||
                trainee.department.toLowerCase().includes(query) ||
                trainee.contact.includes(query)
        );
    }

    // Filter by department
    if (selectedDepartment.value.length > 0) {
        result = result.filter(
            (trainee) => selectedDepartment.value.includes(trainee.department)
        );
    }

    // Filter by status
    if (selectedStatus.value.length > 0) {
        result = result.filter(
            (trainee) => selectedStatus.value.includes(trainee.status)
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
const totalResults = computed(() => filteredTrainees.value.length);
const totalPages = computed(() =>
    Math.ceil(totalResults.value / itemsPerPage.value)
);

const paginatedTrainees = computed(() => {
    const start = (currentPage.value - 1) * itemsPerPage.value;
    const end = start + itemsPerPage.value;
    return filteredTrainees.value.slice(start, end);
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

const isSessionCompleted = computed(() => {
    return sessionInfo.value?.status?.toLowerCase() === 'completed';
});

const canGenerateCertificates = computed(() => {
    const user = page.props.auth?.user;
    if (!user || !isSessionCompleted.value) {
        return false;
    }
    if (user.role?.name === 'admin') {
        return true;
    }
    return sessionInfo.value?.trainer_id === user.id;
});

// Check if trainee is eligible for certificate (80% attendance)
const isEligibleForCertificate = (trainee) => {
    return trainee.attendancePercent >= 80;
};

// Get eligibility badge class
const eligibilityBadgeClass = (trainee) => {
    return isEligibleForCertificate(trainee)
        ? 'bg-green-100 text-green-700'
        : 'bg-red-100 text-red-700';
};

const sessionStatus = computed(() => (sessionInfo.value?.status || 'unknown').toLowerCase());
const sessionStatusBadgeClass = computed(() => {
    switch (sessionStatus.value) {
        case 'upcoming':
            return 'bg-blue-100 text-blue-700';
        case 'open':
            return 'bg-teal-100 text-teal-700';
        case 'closed':
            return 'bg-amber-100 text-amber-700';
        case 'completed':
            return 'bg-purple-100 text-purple-700';
        case 'cancelled':
            return 'bg-red-100 text-red-700';
        default:
            return 'bg-gray-100 text-gray-700';
    }
});

// Multi-day support
const showDayTabs = computed(() => sessionDays.value.length > 1);

const currentDayTrainees = computed(() => {
    if (!selectedDay.value) return trainees.value;

    return trainees.value.map(trainee => {
        const dayAttendance = attendanceMatrix.value[trainee.enrollmentId]?.[selectedDay.value.id] || {};

        return {
            ...trainee,
            status: dayAttendance.status || 'not_marked',
            checked: dayAttendance.status === 'present',
        };
    });
});

// Reset to first page when filters change
watch([searchQuery, selectedDepartment, selectedStatus], () => {
    currentPage.value = 1;
});

// Auto-save timeout
let autoSaveTimeout = null;

// Set attendance status directly with auto-save
const setAttendanceStatus = (traineeId, status) => {
    const trainee = trainees.value.find((t) => t.id === traineeId);
    if (trainee) {
        trainee.status = status;
        trainee.checked = status === 'present';

        // Trigger auto-save after 500ms debounce
        if (autoSaveTimeout) {
            clearTimeout(autoSaveTimeout);
        }
        autoSaveTimeout = setTimeout(() => {
            autoSaveAttendance();
        }, 500);
    }
};

// Toggle attendance with auto-save
const toggleAttendance = (traineeId) => {
    console.log('=== TOGGLE ATTENDANCE DEBUG ===');
    console.log('Trainee ID:', traineeId);
    console.log('All trainees:', trainees.value);
    
    const trainee = trainees.value.find((t) => t.id === traineeId);
    console.log('Found trainee:', trainee);
    
    if (trainee) {
        const previousChecked = trainee.checked;
        const previousStatus = trainee.status;
        
        trainee.checked = !trainee.checked;
        trainee.status = trainee.checked ? "present" : "absent";
        
        console.log('Previous state:', { checked: previousChecked, status: previousStatus });
        console.log('New state:', { checked: trainee.checked, status: trainee.status });
        console.log('Selected day:', selectedDay.value);
        console.log('Show day tabs:', showDayTabs.value);

        // IMPORTANT: Update attendance matrix immediately for multi-day sessions
        // This ensures currentDayTrainees computed property reflects the change
        if (selectedDay.value) {
            if (!attendanceMatrix.value[trainee.enrollmentId]) {
                attendanceMatrix.value[trainee.enrollmentId] = {};
            }
            attendanceMatrix.value[trainee.enrollmentId][selectedDay.value.id] = {
                status: trainee.status,
                checked_at: new Date().toISOString(),
                note: trainee.note || null,
            };
            console.log('Updated attendance matrix immediately:', attendanceMatrix.value[trainee.enrollmentId][selectedDay.value.id]);
        }

        // Trigger auto-save after 500ms debounce
        if (autoSaveTimeout) {
            clearTimeout(autoSaveTimeout);
            console.log('Cleared previous auto-save timeout');
        }
        autoSaveTimeout = setTimeout(() => {
            console.log('Triggering auto-save...');
            autoSaveAttendance();
        }, 500);
    } else {
        console.error('Trainee not found with ID:', traineeId);
    }
    console.log('=== END TOGGLE DEBUG ===');
};

// Sort table column
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
    const csvData = filteredTrainees.value.map((trainee) => [
        trainee.id,
        trainee.name,
        trainee.email,
        trainee.contact,
        trainee.department,
        trainee.status,
    ]);

    const csvContent = [
        headers.join(","),
        ...csvData.map((row) => row.join(",")),
    ].join("\n");

    const blob = new Blob([csvContent], { type: "text/csv" });
    const url = window.URL.createObjectURL(blob);
    const a = document.createElement("a");
    a.href = url;
    a.download = "session-attendance.csv";
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
        doc.text(`Session Attendance Report`, 14, 20);

        // Add session info
        doc.setFontSize(10);
        if (sessionInfo.value.title) {
            doc.text(`Session: ${sessionInfo.value.title}`, 14, 28);
        }
        doc.text(`Generated: ${new Date().toLocaleDateString()}`, 14, 34);

        // Prepare table data with null-safe values
        const headers = [["ID", "Name", "Email", "Contact", "Department", "Status"]];
        const data = filteredTrainees.value.map((trainee) => [
            trainee.id ?? '',
            trainee.name ?? '',
            trainee.email ?? '',
            trainee.contact ?? '',
            trainee.department ?? '',
            trainee.status ?? '',
        ]);

        // Generate table
        doc.autoTable({
            head: headers,
            body: data,
            startY: 40,
            theme: 'grid',
            headStyles: { fillColor: [59, 130, 246] },
            styles: { fontSize: 9 },
        });

        // Save the PDF
        doc.save("session-attendance.pdf");
    } catch (error) {
        console.error('Error generating PDF:', error);
        alert('Failed to generate PDF. Please try again.');
    } finally {
        showExportModal.value = false;
    }
};

// Apply sort
const handleSort = ({ column, direction }) => {
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
    sortColumn.value = "";
    sortDirection.value = "asc";
};

// Fetch session info
const fetchSessionInfo = async () => {
    try {
        const response = await axios.get(`/api/sessions/${props.sessionId}`);
        sessionInfo.value = response.data.data;
    } catch (error) {
        console.error('Error fetching session info:', error);
        toast.error('Failed to load session information');
    }
};

// Fetch attendance summary
const fetchAttendanceSummary = async () => {
    try {
        const response = await axios.get(`/api/sessions/${props.sessionId}/attendance-summary`);
        attendanceSummary.value = response.data.data;
    } catch (error) {
        console.error('Error fetching attendance summary:', error);
    }
};

// Fetch enrollments and attendance data (multi-day support)
const fetchAttendanceData = async () => {
    isLoading.value = true;
    try {
        const response = await axios.get(`/api/sessions/${props.sessionId}/attendance-days`);
        const data = response.data.data;

        sessionInfo.value = data.session;
        sessionDays.value = data.session_days || [];
        attendanceMatrix.value = data.attendance_matrix || {};

        // Map enrollments to trainees format
        trainees.value = (data.enrollments || []).map(enrollment => {
            return {
                id: enrollment.id,
                enrollmentId: enrollment.id,
                userId: enrollment.user_id,
                name: enrollment.user?.name || 'Unknown',
                email: enrollment.user?.email || '',
                contact: enrollment.user?.phone_number || '',
                department: enrollment.user?.department || 'N/A',
                status: 'not_marked',
                checked: false,
                attendancePercent: enrollment.attendance_percent || 0,
            };
        });

        // Auto-select today's day or first day
        if (sessionDays.value.length > 0) {
            const today = sessionDays.value.find(d => d.is_today);
            selectedDay.value = today || sessionDays.value[0];
        }

        await Promise.all([fetchAttendanceSummary(), fetchCertificates()]);
    } catch (error) {
        console.error('Error fetching attendance data:', error);
        toast.error('Failed to load attendance data');
    } finally {
        isLoading.value = false;
    }
};

const fetchCertificates = async () => {
    isLoadingCertificates.value = true;
    try {
        const response = await axios.get(`/api/sessions/${props.sessionId}/certificates`);
        certificates.value = response.data.data ?? response.data ?? [];
    } catch (error) {
        certificates.value = [];
        console.error('Error fetching certificates:', error);
    } finally {
        isLoadingCertificates.value = false;
    }
};

// Auto-save attendance (silent, no toast on success) - multi-day support
const autoSaveAttendance = async () => {
    console.log('=== AUTO-SAVE ATTENDANCE DEBUG ===');
    console.log('Selected day:', selectedDay.value);
    
    if (!selectedDay.value) {
        console.warn('No selected day, skipping auto-save');
        return;
    }

    try {
        console.log('Current day trainees:', currentDayTrainees.value);
        
        // Prepare bulk attendance data for the selected day
        const records = currentDayTrainees.value
            .filter(trainee => trainee.status !== 'not_marked')
            .map(trainee => ({
                user_id: trainee.userId,
                status: trainee.status,
                note: trainee.note || null,
            }));

        console.log('Records to save:', records);
        console.log('Number of records:', records.length);

        // Check if there's anything to save
        if (records.length === 0) {
            console.warn('No records to save (all marked as "not_marked")');
            return;
        }

        const apiUrl = `/api/session-days/${selectedDay.value.id}/attendance/bulk`;
        console.log('API URL:', apiUrl);
        console.log('Request payload:', { records });
        
        // Send to per-day API endpoint
        const response = await axios.post(apiUrl, {
            records
        });
        
        console.log('API Response:', response.data);

        // Update attendance matrix with saved data
        records.forEach(record => {
            const trainee = trainees.value.find(t => t.userId === record.user_id);
            if (trainee && attendanceMatrix.value[trainee.enrollmentId]) {
                attendanceMatrix.value[trainee.enrollmentId][selectedDay.value.id] = {
                    status: record.status,
                    checked_at: new Date().toISOString(),
                    note: record.note,
                };
                console.log('Updated attendance matrix for trainee:', trainee.name);
            }
        });

        // Update last auto-saved time
        lastAutoSaved.value = new Date();
        console.log('Auto-save successful at:', lastAutoSaved.value);

        // Silently refresh summary only
        await fetchAttendanceSummary();
        console.log('Attendance summary refreshed');
    } catch (error) {
        console.error('=== ERROR AUTO-SAVING ATTENDANCE ===');
        console.error('Error details:', error);
        console.error('Error response:', error?.response);
        console.error('Error message:', error?.message);
        toast.error('Failed to auto-save attendance.');
    }
    console.log('=== END AUTO-SAVE DEBUG ===');
};

const generateCertificates = async () => {
    if (!canGenerateCertificates.value) {
        return;
    }

    isGeneratingCertificates.value = true;

    try {
        await ensureCsrf();
        const response = await axios.post(`/api/sessions/${props.sessionId}/certificates/generate`);
        const result = response.data.data ?? response.data ?? {};
        generateCertificatesResult.value = result;
        toast.success(`สร้างใบรับรองใหม่ ${result.created ?? 0} ใบ (ข้าม ${result.skipped ?? 0} ใบที่มีอยู่แล้ว)`);
        await fetchCertificates();
    } catch (error) {
        console.error('Error generating certificates:', error);
        const message = error?.response?.data?.message || 'Failed to generate certificates.';
        toast.error(message);
    } finally {
        isGeneratingCertificates.value = false;
    }
};

// Load data on mount
onMounted(() => {
    fetchAttendanceData();
});
</script>

<template>

    <Head title="Session Attendance" />
    <AdminLayout>
        <div class="space-y-6">
            <!-- Go Back Button -->
            <Link href="/admin/attendance"
                class="inline-flex items-center gap-2 text-[#2f837d] hover:text-[#26685f] font-medium transition-colors">
                <ArrowLeft :size="20" />
                <span>Go back to courses</span>
            </Link>

            <!-- Page Header -->
            <div class="flex items-center justify-between flex-wrap gap-4">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900">
                        Session Attendance
                    </h1>
                    <p class="mt-2 text-sm text-gray-600">
                        Track attendance for this training session
                    </p>
                </div>
                <div class="flex items-center gap-3">
                    <!-- Auto-save indicator -->
                    <div v-if="lastAutoSaved"
                        class="text-sm text-gray-600 flex items-center gap-2 bg-green-50 px-4 py-2 rounded-lg border border-green-200">
                        <CheckCircle :size="16" class="text-green-600" />
                        <span class="font-medium">Auto-saved</span>
                    </div>
                    <button v-if="canGenerateCertificates" @click="showGenerateCertificatesModal = true"
                        :disabled="isGeneratingCertificates"
                        class="inline-flex items-center gap-2 rounded-lg bg-purple-600 px-5 py-3 text-sm font-medium text-white hover:bg-purple-700 disabled:opacity-60">
                        <Award :size="18" />
                        <span v-if="isGeneratingCertificates">Generating...</span>
                        <span v-else>Generate Certificates</span>
                    </button>
                </div>
            </div>

            <div v-if="generateCertificatesResult"
                class="rounded-lg border border-purple-200 bg-purple-50 px-4 py-3 text-sm text-purple-700">
                สร้างใบรับรองใหม่ {{ generateCertificatesResult.created ?? 0 }} ใบ
                (ข้าม {{ generateCertificatesResult.skipped ?? 0 }} ใบที่มีอยู่แล้ว)
            </div>

            <!-- Loading State -->
            <div v-if="isLoading" class="bg-white rounded-[25px] shadow-sm p-12 border border-[#dfe5ef] text-center">
                <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-[#2f837d] mx-auto mb-4"></div>
                <p class="text-gray-600">Loading attendance data...</p>
            </div>

            <!-- Session Info Card -->
            <div v-else class="bg-white rounded-[25px] shadow-sm p-6 border border-[#dfe5ef]">
                <div class="flex items-center gap-3 mb-4">
                    <Calendar class="h-6 w-6 text-[#2f837d]" />
                    <div class="flex flex-wrap items-center gap-3">
                        <h2 class="text-xl font-semibold text-gray-900">
                            {{ sessionInfo.title || 'Untitled Session' }}
                        </h2>
                        <span class="rounded-full px-3 py-1 text-xs font-semibold capitalize"
                            :class="sessionStatusBadgeClass">
                            {{ sessionInfo.status || 'unknown' }}
                        </span>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-4">
                    <div>
                        <p class="text-sm text-gray-500 mb-1">Program</p>
                        <p class="text-base font-medium text-gray-900">
                            {{ sessionInfo.program?.name || 'Not found' }}
                        </p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500 mb-1">Date</p>
                        <p class="text-base font-medium text-gray-900 flex items-center gap-2">
                            <Calendar :size="16" class="text-[#2f837d]" />
                            {{ formatDate(sessionInfo.start_date) || 'Not found' }}
                        </p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500 mb-1">Time</p>
                        <p class="text-base font-medium text-gray-900 flex items-center gap-2">
                            <Clock :size="16" class="text-[#2f837d]" />
                            {{ formatTime(sessionInfo.start_time) || 'Not found' }}
                        </p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500 mb-1">Location</p>
                        <p class="text-base font-medium text-gray-900 flex items-center gap-2">
                            <MapPin :size="16" class="text-[#2f837d]" />
                            {{ sessionInfo.location || 'Not found' }}
                        </p>
                    </div>
                </div>

                <!-- Program Description -->
                <div class="pt-4 border-t border-gray-200">
                    <p class="text-sm text-gray-500 mb-2">Description</p>
                    <p class="text-base text-gray-900">
                        {{ sessionInfo.program?.description || 'Not found' }}
                    </p>
                </div>

                <!-- Attendance Summary -->
                <div class="mt-6 pt-6 border-t border-gray-200">
                    <div class="grid grid-cols-3 gap-4">
                        <div class="text-center">
                            <p class="text-2xl font-bold text-gray-900">
                                {{ attendanceSummary.total }}
                            </p>
                            <p class="text-sm text-gray-500">Total Trainees</p>
                        </div>
                        <div class="text-center">
                            <p class="text-2xl font-bold text-green-600">
                                {{ attendanceSummary.present }}
                            </p>
                            <p class="text-sm text-gray-500">Present</p>
                        </div>
                        <div class="text-center">
                            <p class="text-2xl font-bold text-red-600">
                                {{ attendanceSummary.absent }}
                            </p>
                            <p class="text-sm text-gray-500">Absent</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Multi-Day Session Tabs -->
            <div v-if="showDayTabs" class="bg-white rounded-[25px] shadow-sm border border-[#dfe5ef] overflow-hidden">
                <SessionDayTabs
                    :session-days="sessionDays"
                    :selected-day-id="selectedDay?.id"
                    @select="selectDay"
                />
            </div>

            <div class="bg-white rounded-[25px] shadow-sm p-6 border border-[#dfe5ef]">
                <div class="flex items-center justify-between gap-3 mb-4">
                    <h2 class="text-lg font-semibold text-gray-900">
                        Certificates ({{ certificates.length }})
                    </h2>
                    <span v-if="isLoadingCertificates" class="text-sm text-gray-500">Loading...</span>
                </div>
                <div v-if="!isSessionCompleted"
                    class="mb-4 rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-700">
                    ต้องปิด Session (Completed) ก่อนออกใบรับรอง
                </div>
                <div v-if="certificates.length === 0" class="text-sm text-gray-500">
                    No certificates generated yet.
                </div>
                <div v-else class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-2 text-left text-xs font-semibold text-gray-500">Recipient</th>
                                <th class="px-4 py-2 text-left text-xs font-semibold text-gray-500">Code</th>
                                <th class="px-4 py-2 text-left text-xs font-semibold text-gray-500">Status</th>
                                <th class="px-4 py-2 text-left text-xs font-semibold text-gray-500">Issued</th>
                                <th class="px-4 py-2 text-right text-xs font-semibold text-gray-500">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            <tr v-for="certificate in certificates" :key="certificate.id">
                                <td class="px-4 py-2 text-gray-900">
                                    {{ certificate.user?.name || 'Unknown' }}
                                </td>
                                <td class="px-4 py-2 text-gray-600">
                                    {{ certificate.certificate_code || '—' }}
                                </td>
                                <td class="px-4 py-2">
                                    <span class="rounded-full px-2 py-1 text-xs font-semibold"
                                        :class="certificate.status === 'valid' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'">
                                        {{ certificate.status }}
                                    </span>
                                </td>
                                <td class="px-4 py-2 text-gray-600">
                                    {{ certificate.issued_at || '—' }}
                                </td>
                                <td class="px-4 py-2">
                                    <div class="flex items-center justify-end gap-2">
                                        <Link
                                            :href="`/certificates/${certificate.id}`"
                                            class="inline-flex items-center gap-1 rounded-lg border border-gray-200 px-3 py-1.5 text-xs font-semibold text-gray-700 hover:bg-gray-50"
                                        >
                                            <Eye :size="14" />
                                            View
                                        </Link>
                                        <a
                                            :href="`/api/certificates/${certificate.id}/download`"
                                            class="inline-flex items-center gap-1 rounded-lg border border-emerald-400 px-3 py-1.5 text-xs font-semibold text-emerald-600 hover:bg-emerald-50"
                                        >
                                            <Download :size="14" />
                                            Download
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Search, Filter, and Export Controls -->
            <div class="bg-white rounded-[25px] shadow-sm p-6 border border-[#dfe5ef]">
                <div class="flex flex-col lg:flex-row gap-4 items-start lg:items-center justify-between mb-6">
                    <!-- Left: Search Bar -->
                    <div class="relative w-full lg:max-w-md">
                        <input v-model="searchQuery" type="text" placeholder="Search trainees..."
                            class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#2f837d] focus:border-transparent" />
                        <Search class="absolute left-3 top-2.5 h-5 w-5 text-gray-400" />
                    </div>

                    <!-- Right: Filter, Sort, Export buttons -->
                    <div class="flex flex-row gap-4">
                        <!-- Filter button -->
                        <FilterDropdown
                            v-model:selectedDepartment="selectedDepartment"
                            v-model:selectedStatus="selectedStatus"
                            :departments="departments"
                            :statusOptions="['present', 'absent']"
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
                                { value: 'id', label: 'ID' },
                                { value: 'name', label: 'Name' },
                                { value: 'contact', label: 'Contact' },
                                { value: 'department', label: 'Department' },
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
                        <button @click="showExportModal = true"
                            class="rounded-lg border border-[#d5dde7] inline-flex gap-2 items-center px-4 py-2 hover:bg-gray-50 transition-colors">
                            <Share class="h-4 w-4" />
                            <p>Export</p>
                        </button>
                    </div>
                </div>

                <!-- Trainees Table -->
                <div class="bg-white rounded-[25px] shadow-sm border border-[#dfe5ef] overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th @click="sort('id')"
                                        class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer hover:bg-gray-100">
                                        <div class="flex items-center gap-2">
                                            ID
                                            <ChevronUp v-if="sortColumn === 'id'" class="h-4 w-4" :class="{
                                                'rotate-180':
                                                    sortDirection === 'desc',
                                            }" />
                                        </div>
                                    </th>
                                    <th @click="sort('name')"
                                        class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer hover:bg-gray-100">
                                        <div class="flex items-center gap-2">
                                            Name
                                            <ChevronUp v-if="sortColumn === 'name'" class="h-4 w-4" :class="{
                                                'rotate-180':
                                                    sortDirection === 'desc',
                                            }" />
                                        </div>
                                    </th>
                                    <th @click="sort('contact')"
                                        class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer hover:bg-gray-100">
                                        <div class="flex items-center gap-2">
                                            Contact
                                            <ChevronUp v-if="sortColumn === 'contact'" class="h-4 w-4" :class="{
                                                'rotate-180':
                                                    sortDirection === 'desc',
                                            }" />
                                        </div>
                                    </th>
                                    <th @click="sort('department')"
                                        class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer hover:bg-gray-100">
                                        <div class="flex items-center gap-2">
                                            Department
                                            <ChevronUp v-if="sortColumn === 'department'" class="h-4 w-4" :class="{
                                                'rotate-180':
                                                    sortDirection === 'desc',
                                            }" />
                                        </div>
                                    </th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Attendance %
                                    </th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Eligibility
                                    </th>
                                    <th @click="sort('status')"
                                        class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer hover:bg-gray-100">
                                        <div class="flex items-center gap-2">
                                            Status
                                            <ChevronUp v-if="sortColumn === 'status'" class="h-4 w-4" :class="{
                                                'rotate-180':
                                                    sortDirection === 'desc',
                                            }" />
                                        </div>
                                    </th>
                                    <th
                                        class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Check
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                <tr v-for="(trainee, index) in paginatedTrainees" :key="trainee.id" :class="[
                                    'transition-colors',
                                    index % 2 === 0 ? 'bg-white' : 'bg-gray-50',
                                    'hover:bg-gray-100'
                                ]">
                                    <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-900">
                                        {{ trainee.id }}
                                    </td>
                                    <td class="px-4 py-4 whitespace-nowrap">
                                        <div class="flex items-center">
                                            <div
                                                class="flex-shrink-0 h-10 w-10 bg-gradient-to-r from-blue-500 to-purple-600 rounded-full flex items-center justify-center text-white font-semibold">
                                                {{ trainee.name.charAt(0) }}
                                            </div>
                                            <div class="ml-4">
                                                <div class="text-sm font-medium text-gray-900">
                                                    {{ trainee.name }}
                                                </div>
                                                <div class="text-sm text-gray-500">
                                                    {{ trainee.email }}
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-900">
                                        {{ formatPhoneNumber(trainee.contact) }}
                                    </td>
                                    <td class="px-4 py-4 whitespace-nowrap">
                                        <span>
                                            {{ trainee.department }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-4 whitespace-nowrap text-sm">
                                        <div class="flex items-center gap-2">
                                            <span :class="[
                                                'font-semibold',
                                                trainee.attendancePercent >= 80 ? 'text-green-600' : 'text-red-600'
                                            ]">
                                                {{ trainee.attendancePercent }}%
                                            </span>
                                        </div>
                                    </td>
                                    <td class="px-4 py-4 whitespace-nowrap">
                                        <span :class="[
                                            'inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-medium',
                                            eligibilityBadgeClass(trainee)
                                        ]">
                                            <component :is="isEligibleForCertificate(trainee) ? CheckCircle : XCircle"
                                                :size="14" />
                                            {{ isEligibleForCertificate(trainee) ? 'Eligible' : 'Not Eligible' }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-4 whitespace-nowrap">
                                        <span :class="[
                                            'inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-medium',
                                            trainee.status === 'present'
                                                ? 'bg-green-100 text-green-800'
                                                : 'bg-red-100 text-red-800'
                                        ]">
                                            <component :is="trainee.status === 'present' ? CheckCircle : XCircle"
                                                :size="14" />
                                            {{ trainee.status === 'present' ? 'Present' : 'Absent' }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-4 whitespace-nowrap">
                                        <button @click="console.log('Button clicked for trainee:', trainee); toggleAttendance(trainee.id)" :class="[
                                            'p-2 rounded-lg transition-all duration-200 hover:scale-110',
                                            trainee.checked
                                                ? 'text-green-600 bg-green-50'
                                                : 'text-red-600 bg-red-50'
                                        ]" :title="trainee.checked ? 'Mark as absent' : 'Mark as present'">
                                            <UserRoundCheck v-if="trainee.checked" :size="24" class="stroke-[2]" />
                                            <UserRoundX v-else :size="24" class="stroke-[2]" />
                                        </button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Empty State -->
                    <div v-if="filteredTrainees.length === 0" class="text-center py-12">
                        <Archive class="mx-auto h-12 w-12 text-gray-400" />
                        <h3 class="mt-2 text-sm font-medium text-gray-900">
                            No trainees found
                        </h3>
                        <p class="mt-1 text-sm text-gray-500">
                            Try adjusting your search or filter criteria.
                        </p>
                    </div>

                    <!-- Pagination and Result Counter -->
                    <div v-if="filteredTrainees.length > 0"
                        class="flex flex-col sm:flex-row items-center justify-between gap-4 p-4 border-t border-gray-200">
                        <!-- Pagination (Left) -->
                        <div class="flex items-center gap-2 flex-wrap justify-center">
                            <button @click="goToPage(currentPage - 1)" :disabled="currentPage === 1" :class="[
                                'px-3 py-1 rounded border transition-colors text-sm',
                                currentPage === 1
                                    ? 'bg-gray-100 text-gray-400 cursor-not-allowed border-gray-200'
                                    : 'bg-white text-gray-700 border-gray-300 hover:bg-gray-50',
                            ]">
                                Previous
                            </button>

                            <div class="flex items-center gap-1">
                                <template v-for="page in totalPages" :key="page">
                                    <button v-if="
                                        page === 1 ||
                                        page === totalPages ||
                                        (page >= currentPage - 1 &&
                                            page <= currentPage + 1)
                                    " @click="goToPage(page)" :class="[
                                        'px-3 py-1 rounded border transition-colors text-sm',
                                        currentPage === page
                                            ? 'bg-[#2f837d] text-white border-[#2f837d]'
                                            : 'bg-white text-gray-700 border-gray-300 hover:bg-gray-50',
                                    ]">
                                        {{ page }}
                                    </button>
                                    <span v-else-if="
                                        page === currentPage - 2 ||
                                        page === currentPage + 2
                                    " class="px-2 text-gray-500 text-sm">
                                        ...
                                    </span>
                                </template>
                            </div>

                            <button @click="goToPage(currentPage + 1)" :disabled="currentPage === totalPages" :class="[
                                'px-3 py-1 rounded border transition-colors text-sm',
                                currentPage === totalPages
                                    ? 'bg-gray-100 text-gray-400 cursor-not-allowed border-gray-200'
                                    : 'bg-white text-gray-700 border-gray-300 hover:bg-gray-50',
                            ]">
                                Next
                            </button>
                        </div>

                        <!-- Result Counter (Right) -->
                        <div class="text-sm text-gray-600 text-center sm:text-right">
                            Showing {{ startResult }}-{{ endResult }} of
                            {{ totalResults }} results
                        </div>
                    </div>
                </div>
            </div>

            <!-- Modals -->
            <ExportModal :show="showExportModal" activeTab="trainees" dataType="attendance"
                @close="showExportModal = false" @exportCSV="exportToCSV" @exportPDF="exportToPDF" />



            <ConfirmationDialog :show="showGenerateCertificatesModal" title="Generate Certificates"
                message="Generate certificates for all eligible trainees in this session?" confirmText="Generate"
                confirmButtonClass="bg-purple-600 hover:bg-purple-700" @confirm="generateCertificates"
                @close="showGenerateCertificatesModal = false" @cancel="showGenerateCertificatesModal = false" />
        </div>
    </AdminLayout>
</template>
