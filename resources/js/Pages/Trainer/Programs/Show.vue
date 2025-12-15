<script setup lang="ts">
import { ref, computed } from 'vue';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { useToast } from 'vue-toastification';
import CourseModal from '@/Components/CourseModal.vue';

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

const editSession = (session: any) => {
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

const confirmDeleteSession = (session: any) => {
    selectedSession.value = session;
    showDeleteSessionModal.value = true;
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

const submitAddTrainee = () => {
    showAddTraineeModal.value = false;
};

const confirmRemoveTrainee = (trainee: any) => {
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
        <div class="bg-white px-8 py-4">
            <div class="flex items-center gap-2">
                <div class="flex h-10 w-10 items-center justify-center rounded bg-teal-500">
                    <svg class="h-6 w-6 text-white" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M10.394 2.08a1 1 0 00-.788 0l-7 3a1 1 0 000 1.84L5.25 8.051a.999.999 0 01.356-.257l4-1.714a1 1 0 11.788 1.838L7.667 9.088l1.94.831a1 1 0 00.787 0l7-3a1 1 0 000-1.838l-7-3zM3.31 9.397L5 10.12v4.102a8.969 8.969 0 00-1.05-.174 1 1 0 01-.89-.89 11.115 11.115 0 01.25-3.762zM9.3 16.573A9.026 9.026 0 007 14.935v-3.957l1.818.78a3 3 0 002.364 0l5.508-2.361a11.026 11.026 0 01.25 3.762 1 1 0 01-.89.89 8.968 8.968 0 00-5.35 2.524 1 1 0 01-1.4 0zM6 18a1 1 0 001-1v-2.065a8.935 8.935 0 00-2-.712V17a1 1 0 001 1z"/>
                    </svg>
                </div>
                <div>
                    <div class="text-xs font-semibold text-teal-600">Training Management System</div>
                </div>
            </div>
        </div>

        <div class="mx-auto max-w-7xl px-8 py-6">
            <!-- Back Link -->
            <Link :href="backLink" class="mb-6 flex items-center gap-2 text-sm text-teal-600 hover:text-teal-700">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                </svg>
                {{ backLinkText }}
            </Link>

            <!-- Hero Banner -->
            <div class="mb-6 overflow-hidden rounded-2xl">
                <div class="h-80 w-full bg-gradient-to-br from-cyan-200 via-teal-300 to-teal-500">
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
            <div class="mb-6 flex items-start justify-between">
                <div class="flex-1">
                    <div class="mb-3 flex items-center gap-3">
                        <h1 class="text-3xl font-bold text-gray-900">Advanced UX Design Principles</h1>
                        <span class="rounded-full bg-red-100 px-3 py-1 text-xs font-medium text-red-700">Management</span>
                    </div>
                    <p class="mb-4 text-gray-600">A comprehensive course on creating intuitive and beautiful user experience.</p>

                    <div class="flex items-center gap-3">
                        <span class="flex items-center gap-1 text-sm text-teal-600">
                            <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                            </svg>
                            Published
                        </span>
                        <span class="flex items-center gap-1 text-sm text-blue-600">
                            <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                            </svg>
                            Registration Open
                        </span>
                        <span class="flex items-center gap-1 text-sm text-orange-600">
                            <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd"/>
                            </svg>
                            Start in 15 days
                        </span>
                    </div>
                </div>

                <button v-if="activeTab === 'overview'" @click="showEditModal = true" class="flex items-center gap-2 rounded-lg bg-teal-600 px-4 py-2 text-sm font-medium text-white hover:bg-teal-700">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                    </svg>
                    Edit Course
                </button>
            </div>

            <!-- Tabs -->
            <div class="mb-6 border-b border-gray-200">
                <nav class="flex gap-8">
                    <button @click="activeTab = 'overview'" :class="['border-b-2 px-1 py-4 text-sm font-medium transition', activeTab === 'overview' ? 'border-teal-600 text-teal-600' : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700']">
                        Overview
                    </button>
                    <button @click="activeTab = 'sessions'" :class="['border-b-2 px-1 py-4 text-sm font-medium transition', activeTab === 'sessions' ? 'border-teal-600 text-teal-600' : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700']">
                        Sessions
                    </button>
                    <button @click="activeTab = 'trainees'" :class="['border-b-2 px-1 py-4 text-sm font-medium transition', activeTab === 'trainees' ? 'border-teal-600 text-teal-600' : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700']">
                        Trainees
                    </button>
                </nav>
            </div>

            <!-- Content Area -->
            <div class="grid grid-cols-3 gap-6">
                <!-- Main Content -->
                <div class="col-span-2">
                    <!-- Overview Tab -->
                    <div v-if="activeTab === 'overview'" class="space-y-6">
                        <div class="rounded-lg bg-white p-6 shadow-sm">
                            <h2 class="mb-4 text-lg font-semibold text-gray-900">Description</h2>
                            <p class="text-gray-600">
                                Lorem ipsum dolor sit amet, consectetur adipiscing elit. Integer vitae congue nullam consectetur ornare consectetur sed in leo. Enim imperdiet urna tincidunt at integer nunc amet vitae orci. Ultrices augue scelerisque.
                            </p>

                            <h3 class="mb-3 mt-6 font-semibold text-gray-900">What You'll Learn</h3>
                            <ul class="space-y-2 text-gray-600">
                                <li class="flex items-start gap-2">
                                    <span class="text-teal-600">•</span>
                                    <span>Lorem ipsum dolor sit amet, consectetur adipiscing elit.</span>
                                </li>
                                <li class="flex items-start gap-2">
                                    <span class="text-teal-600">•</span>
                                    <span>Integer vitae congue nullam consectetur ornare consectetur sed in leo.</span>
                                </li>
                                <li class="flex items-start gap-2">
                                    <span class="text-teal-600">•</span>
                                    <span>Enim imperdiet urna tincidunt at integer nunc amet vitae orci.</span>
                                </li>
                                <li class="flex items-start gap-2">
                                    <span class="text-teal-600">•</span>
                                    <span>Ultrices augue scelerisque.</span>
                                </li>
                            </ul>

                            <h3 class="mb-3 mt-6 font-semibold text-gray-900">Who Should Attend</h3>
                            <ul class="space-y-2 text-gray-600">
                                <li class="flex items-start gap-2">
                                    <span class="text-teal-600">•</span>
                                    <span>Lorem ipsum dolor sit amet, consectetur adipiscing elit.</span>
                                </li>
                                <li class="flex items-start gap-2">
                                    <span class="text-teal-600">•</span>
                                    <span>Integer vitae congue nullam consectetur ornare consectetur sed in leo.</span>
                                </li>
                                <li class="flex items-start gap-2">
                                    <span class="text-teal-600">•</span>
                                    <span>Enim imperdiet urna tincidunt at integer nunc amet vitae orci.</span>
                                </li>
                                <li class="flex items-start gap-2">
                                    <span class="text-teal-600">•</span>
                                    <span>Ultrices augue scelerisque.</span>
                                </li>
                                <li class="flex items-start gap-2">
                                    <span class="text-teal-600">•</span>
                                    <span>Lorem ipsum dolor sit amet, consectetur adipiscing elit.</span>
                                </li>
                                <li class="flex items-start gap-2">
                                    <span class="text-teal-600">•</span>
                                    <span>Integer vitae congue nullam consectetur ornare consectetur sed in leo.</span>
                                </li>
                                <li class="flex items-start gap-2">
                                    <span class="text-teal-600">•</span>
                                    <span>Enim imperdiet urna tincidunt at integer nunc amet vitae orci.</span>
                                </li>
                                <li class="flex items-start gap-2">
                                    <span class="text-teal-600">•</span>
                                    <span>Ultrices augue scelerisque.</span>
                                </li>
                            </ul>
                        </div>
                    </div>

                    <!-- Sessions Tab -->
                    <div v-if="activeTab === 'sessions'" class="space-y-4">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-2">
                                <svg class="h-5 w-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                </svg>
                                <h2 class="text-lg font-semibold text-gray-900">Sessions (5)</h2>
                            </div>
                            <div class="flex gap-2">
                                <input type="text" placeholder="Search sessions..." class="rounded-lg border-gray-300 px-3 py-1 text-sm focus:border-teal-500 focus:ring-teal-500" />
                                <button class="rounded-lg border border-gray-300 px-3 py-1 text-sm hover:bg-gray-50">
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h12a1 1 0 011 1v2a1 1 0 01-1 1H4a1 1 0 01-1-1V4zM3 10a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H4a1 1 0 01-1-1v-6zM14 9a1 1 0 011-1h2a1 1 0 011 1v8a1 1 0 01-1 1h-2a1 1 0 01-1-1V9z"/>
                                    </svg>
                                </button>
                                <button class="rounded-lg border border-gray-300 px-3 py-1 text-sm hover:bg-gray-50">Sort</button>
                                <button class="rounded-lg border border-gray-300 px-3 py-1 text-sm hover:bg-gray-50">
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                                    </svg>
                                </button>
                                <button @click="showAddSessionModal = true; sessionErrors = {}; sessionForm.reset();" class="flex items-center gap-2 rounded-lg bg-teal-600 px-4 py-1 text-sm font-medium text-white hover:bg-teal-700">
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                                    </svg>
                                    Add Sessions
                                </button>
                            </div>
                        </div>

                        <!-- Sessions Table -->
                        <div class="rounded-lg bg-white shadow-sm">
                            <table class="w-full">
                                <thead class="border-b border-gray-200 bg-gray-50">
                                    <tr>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500">ID</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500">Date</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500">Time</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500">Session</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500">Location</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500">Capacity</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500">Status</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200">
                                    <tr v-for="session in sessions" :key="session.id" class="hover:bg-gray-50">
                                        <td class="px-4 py-3 text-sm text-gray-900">{{ session.id }}</td>
                                        <td class="px-4 py-3 text-sm text-gray-600">{{ session.date }}</td>
                                        <td class="px-4 py-3 text-sm text-gray-600">{{ session.time }}</td>
                                        <td class="px-4 py-3 text-sm text-gray-900">{{ session.session }}</td>
                                        <td class="px-4 py-3 text-sm text-gray-600">{{ session.location }}</td>
                                        <td class="px-4 py-3 text-sm">
                                            <span :class="[
                                                'rounded-full px-2 py-1 text-xs font-medium',
                                                session.capacity.split('/')[0] === session.capacity.split('/')[1] ? 'bg-red-100 text-red-700' : 'bg-blue-100 text-blue-700'
                                            ]">
                                                {{ session.capacity }}
                                            </span>
                                        </td>
                                        <td class="px-4 py-3 text-sm">
                                            <span :class="[
                                                'rounded-full px-3 py-1 text-xs font-medium',
                                                session.status === 'Open' ? 'bg-teal-100 text-teal-700' : 'bg-red-100 text-red-700'
                                            ]">
                                                {{ session.status }}
                                            </span>
                                        </td>
                                        <td class="px-4 py-3">
                                            <div class="flex gap-2">
                                                <button @click="confirmDeleteSession(session)" class="text-gray-400 hover:text-red-600">
                                                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                                    </svg>
                                                </button>
                                                <button @click="editSession(session)" class="text-gray-400 hover:text-teal-600">
                                                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                                    </svg>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <!-- Pagination -->
                        <div class="flex items-center justify-between">
                            <button class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">Previous</button>
                            <span class="text-sm text-gray-600">Page 1 of 10</span>
                            <button class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">Next</button>
                        </div>
                    </div>

                    <!-- Trainees Tab -->
                    <div v-if="activeTab === 'trainees'" class="space-y-4">
                        <div class="flex items-center justify-between mb-4">
                            <div class="flex items-center gap-2">
                                <svg class="h-5 w-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
                                </svg>
                                <h2 class="text-lg font-semibold text-gray-900">ENROLLED TRAINEES (24)</h2>
                            </div>
                            <button @click="showAddTraineeModal = true" class="flex items-center gap-2 rounded-lg bg-teal-600 px-4 py-2 text-sm font-medium text-white hover:bg-teal-700">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                                </svg>
                                Add Manually
                            </button>
                        </div>

                        <div class="flex items-center gap-2 mb-4">
                            <input type="text" placeholder="Search trainees..." class="flex-1 rounded-lg border-gray-300 px-3 py-2 text-sm focus:border-teal-500 focus:ring-teal-500" />
                            <button class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 flex items-center gap-2">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/>
                                </svg>
                                Filter
                            </button>
                            <button class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 flex items-center gap-2">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4"/>
                                </svg>
                                Sort
                            </button>
                            <button class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 flex items-center gap-2">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                                </svg>
                                Export
                            </button>
                        </div>

                        <div class="rounded-lg bg-white shadow-sm">
                            <div class="border-b border-gray-200 px-4 py-3 flex items-center gap-2 text-sm font-medium text-teal-600">
                                <div class="h-2 w-2 rounded-full bg-teal-600"></div>
                                ENROLLED (4)
                            </div>

                            <div class="divide-y divide-gray-200">
                                <div v-for="trainee in trainees" :key="trainee.id" class="px-4 py-4 hover:bg-gray-50">
                                    <div class="flex items-center justify-between">
                                        <div class="flex items-center gap-4 flex-1">
                                            <img :src="trainee.avatar" :alt="trainee.name" class="h-12 w-12 rounded-full" />
                                            <div class="min-w-0 flex-1">
                                                <div class="font-medium text-gray-900">{{ trainee.name }}</div>
                                                <div class="text-sm text-gray-500">{{ trainee.email }}</div>
                                                <div class="text-xs text-gray-400">ID: {{ trainee.employee_id }}</div>
                                            </div>
                                        </div>

                                        <div class="flex items-center gap-8">
                                            <div class="text-sm text-center">
                                                <div class="text-gray-500 mb-1">Enrolled</div>
                                                <div class="font-medium text-gray-900">{{ trainee.enrolled_date }}</div>
                                            </div>

                                            <div class="text-sm text-center">
                                                <div class="text-gray-500 mb-1">Department</div>
                                                <div class="font-medium text-gray-900">{{ trainee.department }}</div>
                                            </div>

                                            <div class="text-sm text-center">
                                                <div class="text-gray-500 mb-1">Roles</div>
                                                <div class="font-medium text-gray-900">{{ trainee.role }}</div>
                                            </div>

                                            <div class="text-sm">
                                                <div class="text-gray-500 mb-1">Certificates</div>
                                                <select v-model="trainee.certificate_status" @change="updateCertificateStatus(trainee)" :class="['rounded-lg border px-3 py-1.5 text-xs font-medium focus:outline-none focus:ring-2 focus:ring-teal-500', getCertificateSelectColor(trainee.certificate_status)]">
                                                    <option value="Approved">✓ Approved</option>
                                                    <option value="Pending">⌛ Pending</option>
                                                    <option value="Not Eligible">✗ Not Eligible</option>
                                                </select>
                                            </div>

                                            <button @click="confirmRemoveTrainee(trainee)" class="text-red-600 hover:text-red-700">
                                                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                                </svg>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Sidebar -->
                <div class="space-y-6">
                    <!-- Course Information -->
                    <div class="rounded-lg bg-white p-6 shadow-sm">
                        <div class="mb-4 flex items-center justify-between">
                            <h3 class="font-semibold text-gray-900">Course Information</h3>
                            <button class="text-teal-600 hover:text-teal-700">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                </svg>
                            </button>
                        </div>

                        <div class="space-y-3 text-sm">
                            <div>
                                <div class="text-gray-500">Category:</div>
                                <div class="font-medium text-gray-900">Design</div>
                            </div>
                            <div>
                                <div class="text-gray-500">Level:</div>
                                <span class="rounded bg-red-100 px-2 py-1 text-xs font-medium text-red-700">Advanced</span>
                            </div>
                            <div>
                                <div class="text-gray-500">Period:</div>
                                <div class="font-medium text-gray-900">May 1 - MAY 2</div>
                            </div>
                            <div>
                                <div class="text-gray-500">Time:</div>
                                <div class="font-medium text-gray-900">09:00 - 12:00 AM</div>
                            </div>
                            <div>
                                <div class="text-gray-500">Location:</div>
                                <div class="font-medium text-gray-900">Smart Classrom</div>
                            </div>
                            <div>
                                <div class="text-gray-500">Trainer:</div>
                                <div class="font-medium text-gray-900">Natthiya Chakaew</div>
                            </div>
                            <div>
                                <div class="text-gray-500">Certificated:</div>
                                <div class="font-medium text-gray-900">Standard</div>
                            </div>
                            <div>
                                <div class="text-gray-500">Status:</div>
                                <div class="flex items-center gap-1">
                                    <div class="h-2 w-2 rounded-full bg-green-500"></div>
                                    <span class="font-medium text-green-700">OPEN</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Instructor -->
                    <div class="rounded-lg bg-white p-6 shadow-sm">
                        <h3 class="mb-4 font-semibold text-gray-900">Instructor</h3>

                        <div class="flex items-start gap-3">
                            <img src="https://ui-avatars.com/api/?name=สบาย+โจ๊ก&background=0D8ABC&color=fff" alt="Instructor" class="h-16 w-16 rounded-full" />
                            <div class="flex-1">
                                <div class="font-medium text-gray-900">สบาย โจ๊ก</div>
                                <div class="mb-2 flex items-center gap-1">
                                    <span class="text-lg font-semibold text-gray-900">4.8</span>
                                    <svg class="h-4 w-4 text-yellow-400" fill="currentColor" viewBox="0 0 20 20">
                                        <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                                    </svg>
                                </div>
                                <div class="text-xs text-gray-500">(16,124 reviews)</div>
                            </div>
                            <div class="flex h-12 w-12 items-center justify-center rounded-full bg-teal-100">
                                <span class="text-sm font-semibold text-teal-700">85%</span>
                            </div>
                        </div>
                        <div class="mt-2 text-xs text-gray-500">Positive</div>
                    </div>

                    <!-- Course URL -->
                    <div class="rounded-lg bg-white p-6 shadow-sm">
                        <h3 class="mb-4 font-semibold text-gray-900">Course URL</h3>

                        <div class="mb-4 flex items-center gap-2 rounded-lg bg-gray-50 p-3">
                            <input type="text" value="https://example.com/courses/ux*de" readonly class="flex-1 border-0 bg-transparent text-sm text-gray-600 focus:ring-0" />
                            <button class="text-gray-400 hover:text-gray-600">
                                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                                </svg>
                            </button>
                        </div>

                        <div class="grid grid-cols-3 gap-3">
                            <button class="flex flex-col items-center gap-1 rounded-lg border border-gray-200 p-3 hover:bg-gray-50">
                                <svg class="h-5 w-5 text-teal-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z"/>
                                </svg>
                                <span class="text-xs text-gray-600">Share</span>
                            </button>
                            <button class="flex flex-col items-center gap-1 rounded-lg border border-gray-200 p-3 hover:bg-gray-50">
                                <svg class="h-5 w-5 text-teal-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                                </svg>
                                <span class="text-xs text-gray-600">Email</span>
                            </button>
                            <button class="flex flex-col items-center gap-1 rounded-lg border border-gray-200 p-3 hover:bg-gray-50">
                                <svg class="h-5 w-5 text-teal-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"/>
                                </svg>
                                <span class="text-xs text-gray-600">QR Code</span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
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
    <div v-if="showRejectedModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50 p-4">
        <div class="w-full max-w-md rounded-lg bg-white p-6 shadow-xl">
            <div class="text-center">
                <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-red-100">
                    <svg class="h-6 w-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </div>
                <h3 class="mt-4 text-lg font-semibold text-gray-900">Course has rejected</h3>
                <p class="mt-2 text-sm text-gray-600">Leadership Fundamentals is not live</p>

                <div class="mt-4 rounded-lg bg-gray-50 p-4 text-left">
                    <h4 class="font-medium text-gray-900">What happens next</h4>
                    <p class="mt-2 text-sm text-gray-600">revise the program then send that request again</p>
                </div>

                <div class="mt-6 flex gap-3">
                    <button @click="showRejectedModal = false" class="flex-1 rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">
                        <svg class="mr-2 inline-block h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                        Close
                    </button>
                    <button @click="showRejectedModal = false" class="flex-1 rounded-lg bg-teal-600 px-4 py-2 text-sm font-medium text-white hover:bg-teal-700">
                        <svg class="mr-2 inline-block h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                        </svg>
                        View Course
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Add Trainee Modal -->
    <div v-if="showAddTraineeModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50 p-4" @click.self="showAddTraineeModal = false">
        <div class="w-full max-w-md rounded-lg bg-white p-6 shadow-xl">
            <h2 class="mb-2 text-center text-xl font-bold text-gray-900">Add Trainee Manually</h2>
            <p class="mb-6 text-center text-sm text-gray-500">Leadership Fundamentals | Current enrollment: 24/30 (6 seat available)</p>

            <form @submit.prevent="submitAddTrainee" class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700">Full Name <span class="text-red-500">*</span></label>
                    <input v-model="traineeForm.full_name" type="text" placeholder="e.g. Alex Doe" class="mt-1 block w-full rounded-md border-gray-300" required />
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">Email Address <span class="text-red-500">*</span></label>
                    <input v-model="traineeForm.email" type="email" placeholder="e.g. alex.doe@example.com" class="mt-1 block w-full rounded-md border-gray-300" required />
                    <p class="mt-1 text-xs text-gray-500">Login credentials will be sent to this email</p>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">Phone number <span class="text-red-500">*</span></label>
                    <div class="mt-1 flex gap-2">
                        <select v-model="traineeForm.phone_country" class="w-24 rounded-md border-gray-300">
                            <option value="TH">TH</option>
                        </select>
                        <input v-model="traineeForm.phone" type="tel" placeholder="+66 (555) 000-0000" class="flex-1 rounded-md border-gray-300" required />
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Roles</label>
                        <select v-model="traineeForm.role" class="mt-1 block w-full rounded-md border-gray-300">
                            <option value="Student">Student</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Employee/Student ID (Optional)</label>
                        <input v-model="traineeForm.employee_id" type="text" placeholder="e.g. 6530401114-3" class="mt-1 block w-full rounded-md border-gray-300" />
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Department</label>
                        <select v-model="traineeForm.department" class="mt-1 block w-full rounded-md border-gray-300">
                            <option value="Engineering">Engineering</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Position / Job Title</label>
                        <input v-model="traineeForm.position" type="text" placeholder="e.g. Software Engineer" class="mt-1 block w-full rounded-md border-gray-300" />
                    </div>
                </div>

                <div class="flex gap-3 pt-4">
                    <button type="button" @click="showAddTraineeModal = false" class="flex-1 rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">Cancel</button>
                    <button type="submit" class="flex-1 rounded-lg bg-teal-600 px-4 py-2 text-sm font-medium text-white hover:bg-teal-700">Save</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Add Session Modal -->
    <div v-if="showAddSessionModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50 p-4" @click.self="showAddSessionModal = false; sessionErrors = {}; sessionForm.reset();">
        <div class="w-full max-w-2xl rounded-lg bg-white shadow-xl">
            <div class="border-b px-6 py-4">
                <div class="flex items-center justify-between">
                    <div class="text-center flex-1">
                        <h2 class="text-xl font-bold text-gray-900">Add Sessions</h2>
                        <p class="mt-1 text-sm text-gray-500">Leadership Fundamentals | Current enrollment: 24/30 (6 seat available)</p>
                    </div>
                    <button @click="showAddSessionModal = false; sessionErrors = {}; sessionForm.reset();" class="rounded-lg p-2 hover:bg-gray-100">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>
            </div>

            <form @submit.prevent="submitAddSession" class="p-6 space-y-4">
                <!-- Course Dropdown -->
                <div>
                    <label class="block text-sm font-medium text-gray-700">Courses <span class="text-red-500">*</span></label>
                    <select v-model="sessionForm.course" :class="['mt-1 block w-full rounded-md focus:border-teal-500 focus:ring-teal-500', sessionErrors.course ? 'border-red-300' : 'border-gray-300']">
                        <option v-for="course in availableCourses" :key="course" :value="course">{{ course }}</option>
                    </select>
                    <p v-if="sessionErrors.course" class="mt-1 text-sm text-red-600">{{ sessionErrors.course }}</p>
                </div>

                <!-- Date and Time -->
                <div class="grid grid-cols-3 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Date <span class="text-red-500">*</span></label>
                        <input v-model="sessionForm.date" type="date" :class="['mt-1 block w-full rounded-md focus:border-teal-500 focus:ring-teal-500', sessionErrors.date ? 'border-red-300' : 'border-gray-300']" />
                        <p v-if="sessionErrors.date" class="mt-1 text-sm text-red-600">{{ sessionErrors.date }}</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Start Time <span class="text-red-500">*</span></label>
                        <input v-model="sessionForm.start_time" type="time" :class="['mt-1 block w-full rounded-md focus:border-teal-500 focus:ring-teal-500', sessionErrors.start_time ? 'border-red-300' : 'border-gray-300']" />
                        <p v-if="sessionErrors.start_time" class="mt-1 text-sm text-red-600">{{ sessionErrors.start_time }}</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">End Time <span class="text-red-500">*</span></label>
                        <input v-model="sessionForm.end_time" type="time" :class="['mt-1 block w-full rounded-md focus:border-teal-500 focus:ring-teal-500', sessionErrors.end_time ? 'border-red-300' : 'border-gray-300']" />
                        <p v-if="sessionErrors.end_time" class="mt-1 text-sm text-red-600">{{ sessionErrors.end_time }}</p>
                    </div>
                </div>

                <!-- Location -->
                <div>
                    <label class="block text-sm font-medium text-gray-700">Location <span class="text-red-500">*</span></label>
                    <div class="relative mt-1">
                        <input v-model="sessionForm.location" type="text" placeholder="e.g., Main Conference Room" :class="['block w-full rounded-md pr-10 focus:border-teal-500 focus:ring-teal-500', sessionErrors.location ? 'border-red-300' : 'border-gray-300']" />
                        <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-3">
                            <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                            </svg>
                        </div>
                    </div>
                    <p v-if="sessionErrors.location" class="mt-1 text-sm text-red-600">{{ sessionErrors.location }}</p>
                </div>

                <!-- Trainer -->
                <div>
                    <label class="block text-sm font-medium text-gray-700">Trainer</label>
                    <input v-model="sessionForm.trainer" type="text" placeholder="e.g., John Doe" class="mt-1 block w-full rounded-md border-gray-300 focus:border-teal-500 focus:ring-teal-500" />
                </div>

                <!-- Capacity and Status -->
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Capacity <span class="text-red-500">*</span></label>
                        <input v-model="sessionForm.capacity" type="number" min="1" placeholder="e.g., 30" :class="['mt-1 block w-full rounded-md focus:border-teal-500 focus:ring-teal-500', sessionErrors.capacity ? 'border-red-300' : 'border-gray-300']" />
                        <p v-if="sessionErrors.capacity" class="mt-1 text-sm text-red-600">{{ sessionErrors.capacity }}</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Status</label>
                        <select v-model="sessionForm.status" class="mt-1 block w-full rounded-md border-gray-300 focus:border-teal-500 focus:ring-teal-500">
                            <option value="Open">Open</option>
                            <option value="Close">Close</option>
                        </select>
                    </div>
                </div>

                <!-- Buttons -->
                <div class="flex gap-3 pt-4">
                    <button type="button" @click="showAddSessionModal = false; sessionErrors = {}; sessionForm.reset();" class="flex-1 rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">Cancel</button>
                    <button type="submit" class="flex-1 rounded-lg bg-teal-600 px-4 py-2 text-sm font-medium text-white hover:bg-teal-700">Save</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Edit Session Modal -->
    <div v-if="showEditSessionModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50 p-4" @click.self="showEditSessionModal = false; sessionErrors = {}; sessionForm.reset(); selectedSession = null;">
        <div class="w-full max-w-2xl rounded-lg bg-white shadow-xl">
            <div class="border-b px-6 py-4">
                <div class="flex items-center justify-between">
                    <div class="text-center flex-1">
                        <h2 class="text-xl font-bold text-gray-900">Edit Session</h2>
                        <p class="mt-1 text-sm text-gray-500">Leadership Fundamentals | Current enrollment: 24/30 (6 seat available)</p>
                    </div>
                    <button @click="showEditSessionModal = false; sessionErrors = {}; sessionForm.reset(); selectedSession = null;" class="rounded-lg p-2 hover:bg-gray-100">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>
            </div>

            <form @submit.prevent="submitEditSession" class="p-6 space-y-4">
                <!-- Course Dropdown -->
                <div>
                    <label class="block text-sm font-medium text-gray-700">Courses <span class="text-red-500">*</span></label>
                    <select v-model="sessionForm.course" :class="['mt-1 block w-full rounded-md focus:border-teal-500 focus:ring-teal-500', sessionErrors.course ? 'border-red-300' : 'border-gray-300']">
                        <option v-for="course in availableCourses" :key="course" :value="course">{{ course }}</option>
                    </select>
                    <p v-if="sessionErrors.course" class="mt-1 text-sm text-red-600">{{ sessionErrors.course }}</p>
                </div>

                <!-- Date and Time -->
                <div class="grid grid-cols-3 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Date <span class="text-red-500">*</span></label>
                        <input v-model="sessionForm.date" type="date" :class="['mt-1 block w-full rounded-md focus:border-teal-500 focus:ring-teal-500', sessionErrors.date ? 'border-red-300' : 'border-gray-300']" />
                        <p v-if="sessionErrors.date" class="mt-1 text-sm text-red-600">{{ sessionErrors.date }}</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Start Time <span class="text-red-500">*</span></label>
                        <input v-model="sessionForm.start_time" type="time" :class="['mt-1 block w-full rounded-md focus:border-teal-500 focus:ring-teal-500', sessionErrors.start_time ? 'border-red-300' : 'border-gray-300']" />
                        <p v-if="sessionErrors.start_time" class="mt-1 text-sm text-red-600">{{ sessionErrors.start_time }}</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">End Time <span class="text-red-500">*</span></label>
                        <input v-model="sessionForm.end_time" type="time" :class="['mt-1 block w-full rounded-md focus:border-teal-500 focus:ring-teal-500', sessionErrors.end_time ? 'border-red-300' : 'border-gray-300']" />
                        <p v-if="sessionErrors.end_time" class="mt-1 text-sm text-red-600">{{ sessionErrors.end_time }}</p>
                    </div>
                </div>

                <!-- Location -->
                <div>
                    <label class="block text-sm font-medium text-gray-700">Location <span class="text-red-500">*</span></label>
                    <div class="relative mt-1">
                        <input v-model="sessionForm.location" type="text" placeholder="e.g., Main Conference Room" :class="['block w-full rounded-md pr-10 focus:border-teal-500 focus:ring-teal-500', sessionErrors.location ? 'border-red-300' : 'border-gray-300']" />
                        <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-3">
                            <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                            </svg>
                        </div>
                    </div>
                    <p v-if="sessionErrors.location" class="mt-1 text-sm text-red-600">{{ sessionErrors.location }}</p>
                </div>

                <!-- Trainer -->
                <div>
                    <label class="block text-sm font-medium text-gray-700">Trainer</label>
                    <input v-model="sessionForm.trainer" type="text" placeholder="e.g., John Doe" class="mt-1 block w-full rounded-md border-gray-300 focus:border-teal-500 focus:ring-teal-500" />
                </div>

                <!-- Capacity and Status -->
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Capacity <span class="text-red-500">*</span></label>
                        <input v-model="sessionForm.capacity" type="number" min="1" placeholder="e.g., 30" :class="['mt-1 block w-full rounded-md focus:border-teal-500 focus:ring-teal-500', sessionErrors.capacity ? 'border-red-300' : 'border-gray-300']" />
                        <p v-if="sessionErrors.capacity" class="mt-1 text-sm text-red-600">{{ sessionErrors.capacity }}</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Status</label>
                        <select v-model="sessionForm.status" class="mt-1 block w-full rounded-md border-gray-300 focus:border-teal-500 focus:ring-teal-500">
                            <option value="Open">Open</option>
                            <option value="Close">Close</option>
                        </select>
                    </div>
                </div>

                <!-- Buttons -->
                <div class="flex gap-3 pt-4">
                    <button type="button" @click="showEditSessionModal = false; sessionErrors = {}; sessionForm.reset(); selectedSession = null;" class="flex-1 rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">Cancel</button>
                    <button type="submit" class="flex-1 rounded-lg bg-teal-600 px-4 py-2 text-sm font-medium text-white hover:bg-teal-700">Update</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Delete Session Confirmation Modal -->
    <div v-if="showDeleteSessionModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50 p-4">
        <div class="w-full max-w-md rounded-lg bg-white p-6 shadow-xl">
            <div class="text-center">
                <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-red-100">
                    <svg class="h-6 w-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                    </svg>
                </div>
                <h3 class="mt-4 text-lg font-semibold text-gray-900">Confirm Delete Session</h3>
                <p class="mt-2 text-sm text-gray-600">
                    Are you sure you want to delete session <strong>{{ selectedSession?.id }}</strong>?<br>
                    This action cannot be undone.
                </p>

                <div class="mt-6 flex gap-3">
                    <button @click="showDeleteSessionModal = false; selectedSession = null;" class="flex-1 rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">No, Keep it</button>
                    <button @click="deleteSession" class="flex-1 rounded-lg bg-red-600 px-4 py-2 text-sm font-medium text-white hover:bg-red-700">Yes, Delete !</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Remove Trainee Confirmation Modal -->
    <div v-if="showRemoveTraineeModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50 p-4">
        <div class="w-full max-w-md rounded-lg bg-white p-6 shadow-xl">
            <div class="text-center">
                <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-red-100">
                    <svg class="h-6 w-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                    </svg>
                </div>
                <h3 class="mt-4 text-lg font-semibold text-gray-900">Confirm Remove trainess</h3>
                <p class="mt-2 text-sm text-gray-600">Are you sure you want to remove this trainee?</p>

                <div class="mt-6 flex gap-3">
                    <button @click="showRemoveTraineeModal = false" class="flex-1 rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">No, Keep it</button>
                    <button @click="removeTrainee" class="flex-1 rounded-lg bg-red-600 px-4 py-2 text-sm font-medium text-white hover:bg-red-700">Yes, Remove !</button>
                </div>
            </div>
        </div>
    </div>
</template>
