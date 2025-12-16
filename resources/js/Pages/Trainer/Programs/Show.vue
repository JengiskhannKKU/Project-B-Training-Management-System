<script setup lang="ts">
import { ref, computed } from 'vue';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { useToast } from 'vue-toastification';
import CourseModal from '@/Components/CourseModal.vue';
import ProgramOverviewTab from './Partials/ProgramOverviewTab.vue';
import SessionsTab from './Partials/SessionsTab.vue';
import TraineesTab from './Partials/TraineesTab.vue';
import SessionModal from './Partials/SessionModal.vue';
import DeleteSessionModal from './Partials/DeleteSessionModal.vue';
import AddTraineeModal from './Partials/AddTraineeModal.vue';
import RemoveTraineeModal from './Partials/RemoveTraineeModal.vue';
import RejectedModal from './Partials/RejectedModal.vue';

const props = defineProps<{
    program: {
        id: number;
        name: string;
        code: string;
        category: string;
        level: string;
        period: string;
        time: string;
        location: string;
        trainer: string;
        certificated: string;
        status: string;
        description: string;
        image_url: string | null;
    };
}>();

const activeTab = ref('overview');
const showEditModal = ref(false);
const showRejectedModal = ref(false);
const showAddTraineeModal = ref(false);
const showRemoveTraineeModal = ref(false);
const showAddSessionModal = ref(false);
const showEditSessionModal = ref(false);
const showDeleteSessionModal = ref(false);
const selectedTrainee = ref<any>(null);
const selectedSession = ref<any>(null);
const sessionErrors = ref<Record<string, string>>({});

const sessionForm = useForm({
    course: 'UX/UI Design Fundamentals',
    date: '',
    start_time: '',
    end_time: '',
    location: '',
    trainer: '',
    capacity: '',
    status: 'Open',
});

const toast = useToast();
const page = usePage();

// Determine back link based on current URL
const backLink = computed(() => {
    const currentUrl = page.url;
    if (currentUrl.startsWith('/admin/')) {
        return '/admin/my-courses';
    }
    return '/trainer/programs';
});

const backLinkText = computed(() => {
    const currentUrl = page.url;
    if (currentUrl.startsWith('/admin/')) {
        return 'Back to My Courses';
    }
    return 'Back to My Courses';
});

const availableCourses = [
    'UX/UI Design Fundamentals',
    'Leadership Fundamentals',
    'Data Analysis',
    'Advanced Computer Programming',
    'Design Thinking Workshop'
];

const editForm = useForm({
    title: 'Advanced UX Design for Enterprise Application',
    short_description: 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Integer vitae congue nullam consectetur ornare consectetur sed in leo.',
    full_description: 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Integer vitae congue nullam consectetur ornare consectetur sed in leo.\nEnim imperdiet urna tincidunt at integer nunc amet vitae orci.\nUltrices augue scelerisque.',
    category: 'Design',
    level: 'beginner',
    date: '10/15/2024',
    start_time: '09:00 AM',
    end_time: '10:30 AM',
    location: 'Main Conference Room',
    thumbnail: null,
    registration_start: '12/02/2548',
    registration_end: '13/02/2548',
    require_approval: false,
    send_confirmation_email: false,
    allow_waitlist: false,
    allow_cancel_enrollment: false,
    certificate_template: 'standard',
    certificate_type: 'free',
});

const traineeForm = useForm({
    full_name: '',
    email: '',
    phone: '',
    phone_country: 'TH',
    role: 'Student',
    employee_id: '',
    department: 'Engineering',
    position: 'Software Engineer',
});

const categories = ['Design', 'Development', 'Marketing', 'Business'];

// Mock sessions data
const sessions = ref([
    {
        id: 'Se-001',
        date: '2024-03-15',
        time: '08:00 - 16:00',
        session: 'Session 101',
        location: 'Room 101',
        capacity: '45/50',
        status: 'Open'
    },
    {
        id: 'Se-002',
        date: '2024-04-15',
        time: '08:00 - 16:00',
        session: 'Session 102',
        location: 'EN18303',
        capacity: '20/30',
        status: 'Open'
    },
    {
        id: 'Se-003',
        date: '2024-03-15',
        time: '09:00 - 16:00',
        session: 'Session 103',
        location: 'Room3032',
        capacity: '32/45',
        status: 'Open'
    },
    {
        id: 'Se-004',
        date: '2024-03-15',
        time: '08:00 - 16:00',
        session: 'Data Analysis',
        location: 'Room3032',
        capacity: '20/20',
        status: 'Close'
    },
    {
        id: 'Se-005',
        date: '2024-03-15',
        time: '08:00 - 16:00',
        session: 'UX/UI design',
        location: 'Room3032',
        capacity: '45/50',
        status: 'Close'
    },
]);

// Mock trainees data
const trainees = ref([
    {
        id: 1,
        name: 'Natthiya Chakasw',
        email: 'Natthiya.c@kumail.com',
        employee_id: 'TRN-001',
        enrolled_date: 'May 15',
        department: 'Computer Engineering',
        role: 'Students',
        certificate_status: 'Approved',
        avatar: 'https://ui-avatars.com/api/?name=Natthiya+Chakasw&background=0D8ABC&color=fff'
    },
    {
        id: 2,
        name: 'Manee LoveU',
        email: 'Natthiya.c@kumail.com',
        employee_id: 'TRN-002',
        enrolled_date: 'May 12',
        department: 'Computer Engineering',
        role: 'Students',
        certificate_status: 'Pending',
        avatar: 'https://ui-avatars.com/api/?name=Manee+LoveU&background=0D8ABC&color=fff'
    },
    {
        id: 3,
        name: 'Hardtosay yes',
        email: 'Kewsee@kumail.com',
        employee_id: 'TRN-003',
        enrolled_date: 'May 13',
        department: 'Computer Engineering',
        role: 'Students',
        certificate_status: 'Not Eligible',
        avatar: 'https://ui-avatars.com/api/?name=Hardtosay+yes&background=0D8ABC&color=fff'
    },
    {
        id: 4,
        name: 'Someone like you',
        email: 'Kewsee@kumail.com',
        employee_id: 'TRN-004',
        enrolled_date: 'May 14',
        department: 'Computer Engineering',
        role: 'Students',
        certificate_status: 'Pending',
        avatar: 'https://ui-avatars.com/api/?name=Someone+like+you&background=0D8ABC&color=fff'
    },
]);

// Create course data for the modal
const courseDataForModal = computed(() => ({
    id: props.program.id,
    title: editForm.title,
    short_description: editForm.short_description,
    full_description: editForm.full_description,
    category: editForm.category,
    level: editForm.level,
    date: editForm.date,
    start_time: editForm.start_time,
    end_time: editForm.end_time,
    location: editForm.location,
    registration_start: editForm.registration_start,
    registration_end: editForm.registration_end,
    require_approval: editForm.require_approval,
    send_confirmation_email: editForm.send_confirmation_email,
    allow_waitlist: editForm.allow_waitlist,
    allow_cancel_enrollment: editForm.allow_cancel_enrollment,
    certificate_template: editForm.certificate_template,
    certificate_type: editForm.certificate_type,
}));

const handleEditModalClose = () => {
    showEditModal.value = false;
};

const handleEditModalSuccess = () => {
    // Refresh course data or perform any action after successful edit
    console.log('Course edited successfully');
};

const validateSessionForm = () => {
    sessionErrors.value = {};

    if (!sessionForm.course) {
        sessionErrors.value.course = 'Please select a course';
    }
    if (!sessionForm.date) {
        sessionErrors.value.date = 'Date is required';
    }
    if (!sessionForm.start_time) {
        sessionErrors.value.start_time = 'Start time is required';
    }
    if (!sessionForm.end_time) {
        sessionErrors.value.end_time = 'End time is required';
    }
    if (!sessionForm.location.trim()) {
        sessionErrors.value.location = 'Location is required';
    }
    if (!sessionForm.capacity) {
        sessionErrors.value.capacity = 'Capacity is required';
    }

    return Object.keys(sessionErrors.value).length === 0;
};

const submitAddSession = () => {
    if (validateSessionForm()) {
        // Generate new session ID
        const newId = `Se-${String(sessions.value.length + 1).padStart(3, '0')}`;

        // Add new session to the list
        sessions.value.push({
            id: newId,
            date: sessionForm.date,
            time: `${sessionForm.start_time} - ${sessionForm.end_time}`,
            session: sessionForm.course,
            location: sessionForm.location,
            capacity: `0/${sessionForm.capacity}`,
            status: sessionForm.status,
        });

        showAddSessionModal.value = false;
        sessionForm.reset();
        sessionErrors.value = {};

        // Show success toast
        toast.success('Session added successfully!');
    }
};

const handleAddSession = () => {
    showAddSessionModal.value = true;
    sessionErrors.value = {};
    sessionForm.reset();
};

const handleEditSession = (session: any) => {
    selectedSession.value = session;
    sessionForm.course = session.session;
    sessionForm.date = session.date;
    const times = session.time.split(' - ');
    sessionForm.start_time = times[0];
    sessionForm.end_time = times[1];
    sessionForm.location = session.location;
    sessionForm.capacity = session.capacity.split('/')[1];
    sessionForm.status = session.status;
    showEditSessionModal.value = true;
};

const handleDeleteSession = (session: any) => {
    selectedSession.value = session;
    showDeleteSessionModal.value = true;
};

const submitEditSession = () => {
    if (validateSessionForm()) {
        if (selectedSession.value) {
            const index = sessions.value.findIndex(s => s.id === selectedSession.value.id);
            if (index > -1) {
                sessions.value[index] = {
                    ...sessions.value[index],
                    date: sessionForm.date,
                    time: `${sessionForm.start_time} - ${sessionForm.end_time}`,
                    session: sessionForm.course,
                    location: sessionForm.location,
                    capacity: `${sessions.value[index].capacity.split('/')[0]}/${sessionForm.capacity}`,
                    status: sessionForm.status,
                };
            }
        }
        showEditSessionModal.value = false;
        sessionForm.reset();
        sessionErrors.value = {};
        selectedSession.value = null;

        // Show success toast
        toast.success('Session updated successfully!');
    }
};

const closeSessionModal = () => {
    showAddSessionModal.value = false;
    showEditSessionModal.value = false;
    sessionErrors.value = {};
    sessionForm.reset();
    selectedSession.value = null;
};

const deleteSession = () => {
    if (selectedSession.value) {
        const index = sessions.value.findIndex(s => s.id === selectedSession.value.id);
        if (index > -1) {
            sessions.value.splice(index, 1);

            // Show success toast
            toast.success('Session deleted successfully!');
        }
    }
    showDeleteSessionModal.value = false;
    selectedSession.value = null;
};

const handleAddTrainee = () => {
    showAddTraineeModal.value = true;
};

const submitAddTrainee = () => {
    showAddTraineeModal.value = false;
};

const handleRemoveTrainee = (trainee: any) => {
    selectedTrainee.value = trainee;
    showRemoveTraineeModal.value = true;
};

const removeTrainee = () => {
    if (selectedTrainee.value) {
        const index = trainees.value.findIndex(t => t.id === selectedTrainee.value.id);
        if (index > -1) {
            trainees.value.splice(index, 1);
        }
    }
    showRemoveTraineeModal.value = false;
    selectedTrainee.value = null;
};

const getCertificateSelectColor = (status: string) => {
    const colors: Record<string, string> = {
        'Approved': 'bg-teal-50 text-teal-700 border-teal-300',
        'Pending': 'bg-gray-50 text-gray-700 border-gray-300',
        'Not Eligible': 'bg-red-50 text-red-700 border-red-300',
    };
    return colors[status] || 'bg-gray-50 text-gray-700 border-gray-300';
};

const updateCertificateStatus = (trainee: any) => {
    // Show success toast
    toast.success(`Certificate status updated to ${trainee.certificate_status}!`);
};

const getCertificateStatusColor = (status: string) => {
    const colors: Record<string, string> = {
        'Approved': 'bg-teal-100 text-teal-700 border-teal-200',
        'Pending': 'bg-gray-100 text-gray-700 border-gray-200',
        'Not Eligible': 'bg-red-100 text-red-700 border-red-200',
    };
    return colors[status] || 'bg-gray-100 text-gray-700';
};
</script>

<template>
    <Head title="Course Details" />

    <div class="min-h-screen bg-gray-50">
        <!-- Header with Logo -->
        <div class="px-4 sm:px-6 lg:px-8 py-4">
            <img src="/images/project_logo.png" alt="Training Management System" class="h-10 sm:h-12 w-auto object-contain" />
        </div>

        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 py-4 sm:py-6">
            <!-- Back Link -->
            <Link :href="backLink" class="mb-6 flex items-center gap-2 text-sm text-teal-600 hover:text-teal-700">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                </svg>
                {{ backLinkText }}
            </Link>

            <!-- Hero Banner -->
            <div class="mb-4 sm:mb-6 overflow-hidden rounded-xl sm:rounded-2xl">
                <div class="h-32 sm:h-48 md:h-64 lg:h-80 w-full bg-gradient-to-br from-cyan-200 via-teal-300 to-teal-500">
                    <!-- Decorative waves -->
                    <svg class="h-full w-full" viewBox="0 0 1200 400" preserveAspectRatio="none">
                        <defs>
                            <linearGradient id="grad1" x1="0%" y1="0%" x2="0%" y2="100%">
                                <stop offset="0%" style="stop-color:rgb(207,250,254);stop-opacity:0.3" />
                                <stop offset="100%" style="stop-color:rgb(45,212,191);stop-opacity:0.3" />
                            </linearGradient>
                        </defs>
                        <path fill="url(#grad1)" d="M0,128 C150,150 350,100 600,120 C850,140 1000,160 1200,140 L1200,400 L0,400 Z" opacity="0.5"/>
                        <path fill="url(#grad1)" d="M0,192 C200,170 400,220 600,200 C800,180 1000,190 1200,200 L1200,400 L0,400 Z" opacity="0.5"/>
                        <path fill="rgba(255,255,255,0.1)" d="M0,256 C200,230 400,270 600,250 C800,230 1000,250 1200,240 L1200,400 L0,400 Z"/>
                    </svg>
                </div>
            </div>

            <!-- Course Header -->
            <div class="mb-4 sm:mb-6 flex flex-col sm:flex-row sm:items-start sm:justify-between gap-4">
                <div class="flex-1">
                    <div class="mb-3 flex flex-col sm:flex-row sm:items-center gap-2 sm:gap-3">
                        <h1 class="text-xl sm:text-2xl lg:text-3xl font-bold text-gray-900">Advanced UX Design Principles</h1>
                        <span class="rounded-full bg-red-100 px-3 py-1 text-xs font-medium text-red-700 w-fit">Management</span>
                    </div>
                    <p class="mb-4 text-sm sm:text-base text-gray-600">A comprehensive course on creating intuitive and beautiful user experience.</p>

                    <div class="flex flex-wrap items-center gap-2 sm:gap-3">
                        <span class="flex items-center gap-1 text-xs sm:text-sm text-teal-600">
                            <svg class="h-3 w-3 sm:h-4 sm:w-4" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                            </svg>
                            Published
                        </span>
                        <span class="flex items-center gap-1 text-xs sm:text-sm text-blue-600">
                            <svg class="h-3 w-3 sm:h-4 sm:w-4" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                            </svg>
                            Registration Open
                        </span>
                        <span class="flex items-center gap-1 text-xs sm:text-sm text-orange-600">
                            <svg class="h-3 w-3 sm:h-4 sm:w-4" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd"/>
                            </svg>
                            <span class="hidden xs:inline">Start in 15 days</span>
                            <span class="xs:hidden">15 days</span>
                        </span>
                    </div>
                </div>

                <button v-if="activeTab === 'overview'" @click="showEditModal = true" class="flex items-center justify-center gap-2 rounded-lg bg-teal-600 px-4 py-2 text-sm font-medium text-white hover:bg-teal-700 w-full sm:w-auto">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                    </svg>
                    Edit Course
                </button>
            </div>

            <!-- Tabs -->
            <div class="mb-4 sm:mb-6 border-b border-gray-200 -mx-4 sm:mx-0 px-4 sm:px-0">
                <nav class="flex gap-4 sm:gap-8 overflow-x-auto scrollbar-hide">
                    <button @click="activeTab = 'overview'" :class="['border-b-2 px-1 py-3 sm:py-4 text-sm font-medium transition whitespace-nowrap', activeTab === 'overview' ? 'border-teal-600 text-teal-600' : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700']">
                        Overview
                    </button>
                    <button @click="activeTab = 'sessions'" :class="['border-b-2 px-1 py-3 sm:py-4 text-sm font-medium transition whitespace-nowrap', activeTab === 'sessions' ? 'border-teal-600 text-teal-600' : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700']">
                        Sessions
                    </button>
                    <button @click="activeTab = 'trainees'" :class="['border-b-2 px-1 py-3 sm:py-4 text-sm font-medium transition whitespace-nowrap', activeTab === 'trainees' ? 'border-teal-600 text-teal-600' : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700']">
                        Trainees
                    </button>
                </nav>
            </div>

            <!-- Content Area -->
            <ProgramOverviewTab v-if="activeTab === 'overview'" :program="program" />
            <SessionsTab
                v-if="activeTab === 'sessions'"
                :sessions="sessions"
                @add-session="handleAddSession"
                @edit-session="handleEditSession"
                @delete-session="handleDeleteSession"
            />
            <TraineesTab
                v-if="activeTab === 'trainees'"
                :trainees="trainees"
                :get-certificate-select-color="getCertificateSelectColor"
                @add-trainee="handleAddTrainee"
                @remove-trainee="handleRemoveTrainee"
                @update-certificate="updateCertificateStatus"
            />
        </div>
    </div>

    <!-- Edit Course Modal -->
    <CourseModal
        :show="showEditModal"
        :course="courseDataForModal"
        @close="handleEditModalClose"
        @success="handleEditModalSuccess"
    />

    <!-- Course Rejected Modal -->
    <RejectedModal
        :show="showRejectedModal"
        @close="showRejectedModal = false"
        @view-course="showRejectedModal = false"
    />

    <!-- Add Trainee Modal -->
    <AddTraineeModal
        :show="showAddTraineeModal"
        :trainee-form="traineeForm"
        @close="showAddTraineeModal = false"
        @submit="submitAddTrainee"
    />

    <!-- Add Session Modal -->
    <SessionModal
        :show="showAddSessionModal"
        mode="add"
        :available-courses="availableCourses"
        :session-form="sessionForm"
        :session-errors="sessionErrors"
        @close="closeSessionModal"
        @submit="submitAddSession"
    />

    <!-- Edit Session Modal -->
    <SessionModal
        :show="showEditSessionModal"
        mode="edit"
        :session="selectedSession"
        :available-courses="availableCourses"
        :session-form="sessionForm"
        :session-errors="sessionErrors"
        @close="closeSessionModal"
        @submit="submitEditSession"
    />

    <!-- Delete Session Confirmation Modal -->
    <DeleteSessionModal
        :show="showDeleteSessionModal"
        :session="selectedSession"
        @close="showDeleteSessionModal = false; selectedSession = null;"
        @confirm="deleteSession"
    />

    <!-- Remove Trainee Confirmation Modal -->
    <RemoveTraineeModal
        :show="showRemoveTraineeModal"
        :trainee="selectedTrainee"
        @close="showRemoveTraineeModal = false"
        @confirm="removeTrainee"
    />
</template>
