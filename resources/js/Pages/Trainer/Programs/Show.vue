<script setup lang="ts">
import { ref, computed, onMounted, watch } from 'vue';
import axios from 'axios';
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
        approval_status?: string;
        duration_hours?: number;
    };
}>();
type ProgramData = typeof props.program & { approval_status?: string; duration_hours?: number };

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
const isSubmitting = ref(false);
const isLoadingSessions = ref(false);
const sessionRequestRows = ref<any[]>([]);

const sessionForm = useForm({
    title: '',
    registration_start: '',
    registration_end: '',
    date: '',
    start_time: '',
    end_time: '',
    location: '',
    trainer: '',
    capacity: '',
    enrollment_limit: 'limited',
    trainer_photo_url: '',
    require_approval: false,
    send_confirmation_email: false,
    allow_waitlist: false,
    allow_cancel_enrollment: false,
});

const toast = useToast();
const page = usePage();
const handleApiError = (error: any, fallback = 'Something went wrong') => {
    const message =
        error?.response?.data?.message ||
        error?.message ||
        fallback;
    toast.error(message);
};
const ensureCsrf = () => axios.get('/sanctum/csrf-cookie');

// Determine back link based on current URL
const userRole = computed(() => (page.props.auth?.user as AppUser | undefined)?.role?.name || '');
const isTrainer = computed(() => userRole.value === 'trainer' || !userRole.value);

const backLink = computed(() => {
    const currentUrl = page.url;
    if (currentUrl.startsWith('/admin/') || userRole.value === 'admin') {
        return '/admin/my-courses';
    }
    return '/trainer/programs';
});

const backLinkText = computed(() => {
    const currentUrl = page.url;
    if (currentUrl.startsWith('/admin/') || userRole.value === 'admin') {
        return 'Back to Program Requests';
    }
    return 'Back to My Courses';
});

const editForm = useForm({
    title: 'Advanced UX Design for Enterprise Application',
    short_description: 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Integer vitae congue nullam consectetur ornare consectetur sed in leo.',
    full_description: 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Integer vitae congue nullam consectetur ornare consectetur sed in leo.\nEnim imperdiet urna tincidunt at integer nunc amet vitae orci.\nUltrices augue scelerisque.',
    category: 'Design',
    level: 'beginner',
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
    session_id: '',
});

const categories = ['Design', 'Development', 'Marketing', 'Business'];

// Sessions data
const sessions = ref<Array<any>>([]);

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
        avatar: 'https://ui-avatars.com/api/?name=Natthiya+Chakasw&background=0D8ABC&color=fff',
        session_id: 1,
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
        avatar: 'https://ui-avatars.com/api/?name=Manee+LoveU&background=0D8ABC&color=fff',
        session_id: 2,
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
        avatar: 'https://ui-avatars.com/api/?name=Hardtosay+yes&background=0D8ABC&color=fff',
        session_id: 3,
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
        avatar: 'https://ui-avatars.com/api/?name=Someone+like+you&background=0D8ABC&color=fff',
        session_id: 4,
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
    certificate_template: editForm.certificate_template,
    certificate_type: editForm.certificate_type,
}));

const handleEditModalClose = () => {
    showEditModal.value = false;
};

const handleEditModalSuccess = (payload?: Record<string, unknown>) => {
    if (!payload) {
        showEditModal.value = false;
        return;
    }

    ensureCsrf()
        .then(() => axios.post('/api/trainer/program-requests', {
            action: 'update',
            program_id: props.program.id,
            payload,
        }))
        .then(() => {
            toast.success('Program update request sent to admin.');
            showEditModal.value = false;
        })
        .catch((error) => handleApiError(error, 'Unable to submit program update.'));
};

const validateSessionForm = () => {
    sessionErrors.value = {};

    if (!sessionForm.title.trim()) {
        sessionErrors.value.title = 'Session title is required';
    }
    if (!sessionForm.registration_start) {
        sessionErrors.value.registration_start = 'Registration start date is required';
    }
    if (!sessionForm.registration_end) {
        sessionErrors.value.registration_end = 'Registration end date is required';
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
    if (sessionForm.enrollment_limit === 'limited' && !sessionForm.capacity) {
        sessionErrors.value.capacity = 'Capacity is required';
    }

    return Object.keys(sessionErrors.value).length === 0;
};

const submitAddSession = async () => {
    if (validateSessionForm()) {
        try {
            isSubmitting.value = true;
            await ensureCsrf();
            const capacityValue = sessionForm.enrollment_limit === 'unlimited' ? null : sessionForm.capacity;
            const payload = {
                title: sessionForm.title,
                registration_start: sessionForm.registration_start,
                registration_end: sessionForm.registration_end,
                date: sessionForm.date,
                start_time: sessionForm.start_time || null,
                end_time: sessionForm.end_time || null,
                location: sessionForm.location,
                trainer: sessionForm.trainer,
                enrollment_limit: sessionForm.enrollment_limit,
                capacity: capacityValue,
                trainer_photo_url: sessionForm.trainer_photo_url || null,
                require_approval: sessionForm.require_approval,
                send_confirmation_email: sessionForm.send_confirmation_email,
                allow_waitlist: sessionForm.allow_waitlist,
                allow_cancel_enrollment: sessionForm.allow_cancel_enrollment,
            };

            await axios.post('/api/trainer/session-requests', {
                action: 'create',
                program_id: displayProgram.value?.id || props.program.id,
                payload,
            });

            toast.success('Session request sent to admin for approval.');
            showAddSessionModal.value = false;
            sessionForm.reset();
            sessionErrors.value = {};
            fetchSessions();
        } catch (error) {
            handleApiError(error, 'Unable to submit session request.');
        } finally {
            isSubmitting.value = false;
        }
    }
};

const handleAddSession = () => {
    showAddSessionModal.value = true;
    sessionErrors.value = {};
    sessionForm.reset();
};

const handleEditSession = (session: any) => {
    selectedSession.value = session;
    sessionForm.title = session.session || session.title || '';
    sessionForm.registration_start = session.registration_start || session.registration_start_date || '';
    sessionForm.registration_end = session.registration_end || session.registration_end_date || '';
    sessionForm.date = session.date || '';
    sessionForm.start_time = session.start_time || session.startTime || '';
    sessionForm.end_time = session.end_time || session.endTime || '';
    sessionForm.location = session.location || '';
    sessionForm.trainer = session.trainer || '';
    const rawCapacity = session.capacity?.split?.('/')[1] || session.capacity || '';
    const capacityLabel = String(rawCapacity || '');
    const isUnlimited = capacityLabel.toLowerCase() === 'unlimited' || Number(capacityLabel) >= 9999;
    const sessionEnrollmentLimit = session.enrollment_limit || (isUnlimited ? 'unlimited' : 'limited');
    sessionForm.enrollment_limit = sessionEnrollmentLimit;
    sessionForm.capacity = sessionEnrollmentLimit === 'unlimited' ? '' : capacityLabel;
    sessionForm.trainer_photo_url = session.trainer_photo_url || '';
    sessionForm.require_approval = session.require_approval ?? false;
    sessionForm.send_confirmation_email = session.send_confirmation_email ?? false;
    sessionForm.allow_waitlist = session.allow_waitlist ?? false;
    sessionForm.allow_cancel_enrollment = session.allow_cancel_enrollment ?? false;
    showEditSessionModal.value = true;
};

const handleDeleteSession = (session: any) => {
    selectedSession.value = session;
    showDeleteSessionModal.value = true;
};

const submitEditSession = () => {
    if (validateSessionForm()) {
        const capacityValue = sessionForm.enrollment_limit === 'unlimited' ? null : sessionForm.capacity;
        ensureCsrf().then(() => axios.post('/api/trainer/session-requests', {
            action: 'update',
            session_id: selectedSession.value?.id,
            program_id: displayProgram.value?.id || props.program.id,
            payload: {
                title: sessionForm.title,
                registration_start: sessionForm.registration_start,
                registration_end: sessionForm.registration_end,
                date: sessionForm.date,
                start_time: sessionForm.start_time || null,
                end_time: sessionForm.end_time || null,
                location: sessionForm.location,
                trainer: sessionForm.trainer,
                enrollment_limit: sessionForm.enrollment_limit,
                capacity: capacityValue,
                trainer_photo_url: sessionForm.trainer_photo_url || null,
                require_approval: sessionForm.require_approval,
                send_confirmation_email: sessionForm.send_confirmation_email,
                allow_waitlist: sessionForm.allow_waitlist,
                allow_cancel_enrollment: sessionForm.allow_cancel_enrollment,
            },
        }))
            .then(() => {
                toast.success('Session update request sent to admin.');
                showEditSessionModal.value = false;
                sessionForm.reset();
                sessionErrors.value = {};
                selectedSession.value = null;
                fetchSessions();
            })
            .catch((error) => handleApiError(error, 'Unable to submit session update.'));
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
    if (!selectedSession.value) {
        return;
    }

    ensureCsrf().then(() => axios.post('/api/trainer/session-requests', {
        action: 'delete',
        session_id: selectedSession.value.id,
        program_id: displayProgram.value?.id || props.program.id,
        payload: {
            reason: 'Trainer requested deletion',
        },
    }))
        .then(() => {
            toast.success('Session deletion request sent to admin.');
            fetchSessions();
        })
        .catch((error) => handleApiError(error, 'Unable to submit delete request.'))
        .finally(() => {
            showDeleteSessionModal.value = false;
            selectedSession.value = null;
        });
};

const handleAddTrainee = () => {
    if (sessions.value.length && !traineeForm.session_id) {
        traineeForm.session_id = sessions.value[0].id;
    }
    showAddTraineeModal.value = true;
};

const submitAddTrainee = () => {
    if (!traineeForm.session_id) {
        toast.error('Please select a session for this trainee.');
        return;
    }

    ensureCsrf().then(() => axios.post('/api/trainer/trainee-requests', {
        action: 'add',
        session_id: traineeForm.session_id,
        payload: {
            ...traineeForm.data(),
        },
    }))
        .then(() => {
            toast.success('Trainee add request sent to admin.');
            showAddTraineeModal.value = false;
            traineeForm.reset();
        })
        .catch((error) => handleApiError(error, 'Unable to submit trainee request.'));
};

const handleRemoveTrainee = (trainee: any) => {
    selectedTrainee.value = trainee;
    showRemoveTraineeModal.value = true;
};

const removeTrainee = () => {
    if (!selectedTrainee.value) {
        return;
    }

    ensureCsrf().then(() => axios.post('/api/trainer/trainee-requests', {
        action: 'remove',
        session_id: selectedTrainee.value.session_id,
        trainee_id: selectedTrainee.value.id,
        payload: {
            name: selectedTrainee.value.name,
            email: selectedTrainee.value.email,
        },
    }))
        .then(() => {
            toast.success('Trainee removal request sent to admin.');
        })
        .catch((error) => handleApiError(error, 'Unable to submit remove request.'))
        .finally(() => {
            showRemoveTraineeModal.value = false;
            selectedTrainee.value = null;
        });
};

const mapSessionForDisplay = (session: any) => {
    const start = session.start_time ? session.start_time.slice(0, 5) : '--:--';
    const end = session.end_time ? session.end_time.slice(0, 5) : '--:--';
    const capacityTaken = session.active_enrollments_count ?? 0;
    const capacityTotal = session.capacity ?? 0;
    const capacityTotalNumber = Number(capacityTotal);
    const isUnlimited = !Number.isNaN(capacityTotalNumber) && capacityTotalNumber >= 9999;
    const capacityLabel = isUnlimited ? 'Unlimited' : (Number.isNaN(capacityTotalNumber) ? capacityTotal : capacityTotalNumber);

    return {
        id: session.id,
        display_id: session.display_id || `Se-${String(session.id).padStart(3, '0')}`,
        date: session.start_date || '',
        time: `${start} - ${end}`,
        session: session.title || 'Session',
        location: session.location || '',
        capacity: `${capacityTaken}/${capacityLabel || 0}`,
        status: session.status ? session.status.charAt(0).toUpperCase() + session.status.slice(1) : 'Open',
        trainer_photo_url: session.trainer_photo_url || '',
    };
};

const mapSessionRequest = (req: any) => {
    const payload = req.payload || {};
    const startTime = payload.start_time || payload.startTime || '';
    const endTime = payload.end_time || payload.endTime || '';
    const timeRange = startTime && endTime ? `${startTime} - ${endTime}` : `${startTime || '--:--'} - ${endTime || '--:--'}`;

    const statusMap: Record<string, string> = {
        approved: 'Approved',
        pending: 'Pending',
        rejected: 'Closed',
    };

    const enrollmentLimit = payload.enrollment_limit || payload.enrollmentLimit;
    const capacityDisplay = enrollmentLimit === 'unlimited'
        ? '0/Unlimited'
        : payload.capacity
            ? `0/${payload.capacity}`
            : '0/0';

    return {
        id: `req-${req.id}`,
        display_id: payload.code || `Pending-${req.id}`,
        date: payload.date || payload.start_date || '',
        time: timeRange,
        session: payload.title || payload.course || `Session ${req.id}`,
        location: payload.location || '',
        capacity: capacityDisplay,
        status: statusMap[req.status] || req.status || 'Pending',
        enrollment_limit: enrollmentLimit || 'limited',
        trainer_photo_url: payload.trainer_photo_url || '',
    };
};

// Move these declarations before functions that depend on them
const program = ref<ProgramData | null>(props.program || null);
const requestFallback = ref<any | null>(null);
const isLoadingProgram = ref(false);

const fetchSessionRequests = async () => {
    const requestUrl = isTrainer.value ? '/api/trainer/requests' : '/api/admin/requests';
    try {
        await ensureCsrf();
        const { data } = await axios.get(requestUrl);
        const list = data?.data || data || [];
        // Use requestFallback.id for pending programs (URL contains request ID)
        // Use program.value.id for approved programs (actual program ID)
        const programId = Number(
            program.value?.id || 
            requestFallback.value?.target_id || 
            requestFallback.value?.id || 
            props.program.id
        );
        // Also check against the request ID for pending programs
        const requestId = Number(props.program.id);
        sessionRequestRows.value = list
            .filter(
                (r: any) =>
                    r.target_type === 'session' &&
                    r.status === 'pending' &&
                    (Number(r.payload?.program_id || r.program_id) === programId ||
                     Number(r.payload?.program_id || r.program_id) === requestId)
            )
            .map(mapSessionRequest);
    } catch (error) {
        sessionRequestRows.value = [];
    }
};

const fetchSessions = async () => {
    isLoadingSessions.value = true;
    try {
        // For approved programs, use the actual program ID from the programs table
        // For pending programs, use the request ID to find session requests
        const programId = program.value?.id || requestFallback.value?.target_id || props.program.id;
        const { data } = await axios.get('/api/sessions', {
            params: { program_id: programId },
        });
        const apiSessions = data?.data ?? data ?? [];
        await fetchSessionRequests();
        sessions.value = [
            ...apiSessions.map(mapSessionForDisplay),
            ...sessionRequestRows.value,
        ];
    } catch (error) {
        handleApiError(error, 'Unable to load sessions.');
    } finally {
        isLoadingSessions.value = false;
    }
};

// Note: fetchSessions is called after fetchProgram completes via the watch on displayProgram.id

const fetchProgramRequestFallback = async () => {
    const tryFetch = async (url: string) => {
        const { data } = await axios.get(url);
        const list = data?.data || data || [];
        console.log('[DEBUG] Fetched requests from', url, ':', list);
        console.log('[DEBUG] Searching for request with id:', props.program.id, 'type:', typeof props.program.id);
        // Look for admin request by ID (the URL contains the request ID)
        const match = list.find((r: any) => {
            console.log('[DEBUG] Comparing request id:', r.id, 'type:', typeof r.id, 'with:', props.program.id);
            return r.id === Number(props.program.id);
        });
        console.log('[DEBUG] Match result:', match);
        return match;
    };

    try {
        await ensureCsrf();
        // Prefer trainer requests when the path is trainer, otherwise admin
        const isAdminPath = page.url.startsWith('/admin/');
        const firstUrl = isAdminPath ? '/api/admin/requests' : '/api/trainer/requests';
        const fallbackUrl = isAdminPath ? '/api/trainer/requests' : '/api/admin/requests';
        console.log('[DEBUG] isAdminPath:', isAdminPath, 'firstUrl:', firstUrl);

        let match = null;
        try {
            match = await tryFetch(firstUrl);
        } catch (err: any) {
            console.log('[DEBUG] Error fetching from firstUrl:', err?.message);
            // ignore and try fallback
        }
        if (!match) {
            try {
                match = await tryFetch(fallbackUrl);
            } catch (err: any) {
                console.log('[DEBUG] Error fetching from fallbackUrl:', err?.message);
                // ignore
            }
        }

        if (match) {
            console.log('[DEBUG] Setting requestFallback with payload:', match.payload);
            requestFallback.value = match;
            
            // If this request is approved and has a target_id, also fetch the actual program data
            if (match.status === 'approved' && match.target_id) {
                try {
                    const { data } = await axios.get(`/api/programs/${match.target_id}`);
                    program.value = (data?.data || data) as ProgramData;
                    console.log('[DEBUG] Loaded program from programs table:', program.value);
                } catch (err: any) {
                    console.log('[DEBUG] Failed to load program from programs table:', err?.message);
                    // Program might not exist yet, use payload as fallback
                }
            }
        } else {
            console.log('[DEBUG] No matching request found for id:', props.program.id);
        }
    } catch (error) {
        console.log('[DEBUG] Error in fetchProgramRequestFallback:', error);
        // ignore fallback errors
    }
};

const fetchProgram = async () => {
    if (!props.program?.id) return;
    isLoadingProgram.value = true;
    try {
        await ensureCsrf();
        
        // First, try to fetch the admin_request to get the program data
        // The URL ID is the admin_request.id, not the program.id
        await fetchProgramRequestFallback();
        
        // If we found a request with target_id, the program data was already loaded
        // If not, try to load from programs table as a fallback (in case URL has actual program ID)
        if (!program.value && !requestFallback.value) {
            try {
                const { data } = await axios.get(`/api/programs/${props.program.id}`);
                program.value = (data?.data || data || props.program) as ProgramData;
            } catch (error: any) {
                // Program not found - this is expected for pending requests
                if (![401, 403, 404].includes(error?.response?.status)) {
                    handleApiError(error, 'Unable to load program details.');
                }
            }
        }
    } catch (error: any) {
        if (![401, 403].includes(error?.response?.status)) {
            handleApiError(error, 'Unable to load program details.');
        }
    } finally {
        isLoadingProgram.value = false;
        // Load sessions after program data is available
        fetchSessions();
    }
};

onMounted(() => {
    fetchProgram();
});

const displayProgram = computed<ProgramData>(() => {
    // Check if program.value has actual data (not just an empty object from props)
    if (program.value && program.value.name) {
        return program.value as ProgramData;
    }
    // Use requestFallback if available (for pending/rejected requests)
    if (requestFallback.value?.payload) {
        const payload = requestFallback.value.payload;
        return {
            id: requestFallback.value.target_id || requestFallback.value.id,
            name: payload.title || payload.name || 'Program',
            code: payload.code || '',
            category: payload.category || 'General',
            level: payload.level || '',
            period: payload.period || '',
            time: payload.time || '',
            location: payload.location || '',
            trainer: payload.trainer || '',
            certificated: payload.certificated || '',
            status: payload.status || 'pending',
            description: payload.description || payload.full_description || '',
            image_url: payload.image_url || null,
            approval_status: requestFallback.value.status || 'pending',
            duration_hours: payload.duration_hours || 0,
        } as ProgramData;
    }
    return (props.program || {}) as ProgramData;
});

const programApprovalStatus = computed(() => displayProgram.value?.approval_status || requestFallback.value?.status || 'pending');

const statusBadge = computed(() => {
    const status = programApprovalStatus.value;
    if (status === 'approved') return { text: 'Approved', class: 'bg-green-100 text-green-700' };
    if (status === 'rejected') return { text: 'Rejected', class: 'bg-red-100 text-red-700' };
    return { text: 'Pending', class: 'bg-yellow-100 text-yellow-700' };
});

watch(
    () => displayProgram.value?.id,
    (newId, oldId) => {
        if (newId && newId !== oldId) {
            fetchSessions();
        }
    }
);

const resubmitProgram = async () => {
    try {
        await ensureCsrf();
        await axios.post('/api/trainer/program-requests', {
            action: 'update',
            program_id: displayProgram.value?.id,
            payload: displayProgram.value,
        });
        toast.success('Resubmitted program for approval.');
        fetchProgram();
    } catch (error) {
        handleApiError(error, 'Unable to resubmit program.');
    }
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
    <Head title="Program Details" />

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
                <!-- Show uploaded image if available -->
                <img
                    v-if="displayProgram.image_url"
                    :src="displayProgram.image_url"
                    :alt="displayProgram.name || 'Program'"
                    class="h-32 sm:h-48 md:h-64 lg:h-80 w-full object-cover"
                />
                <!-- Fallback gradient placeholder -->
                <div v-else class="h-32 sm:h-48 md:h-64 lg:h-80 w-full bg-gradient-to-br from-cyan-200 via-teal-300 to-teal-500">
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
                        <h1 class="text-xl sm:text-2xl lg:text-3xl font-bold text-gray-900">
                            {{ displayProgram.name || 'Program' }}
                        </h1>
                        <span class="rounded-full bg-red-100 px-3 py-1 text-xs font-medium text-red-700 w-fit">
                            {{ displayProgram.category || 'General' }}
                        </span>
                    </div>
                    <p class="mb-4 text-sm sm:text-base text-gray-600">
                        {{ displayProgram.description || 'No description provided.' }}
                    </p>

                    <div class="flex flex-wrap items-center gap-2 sm:gap-3">
                        <span class="flex items-center gap-1 text-xs sm:text-sm" :class="statusBadge.class">
                            {{ statusBadge.text }}
                        </span>
                        <span class="flex items-center gap-1 text-xs sm:text-sm text-blue-600">
                            Code: {{ displayProgram.code || '—' }}
                        </span>
                        <span class="flex items-center gap-1 text-xs sm:text-sm text-gray-600">
                            Duration: {{ displayProgram.duration_hours ? `${displayProgram.duration_hours} hrs` : '—' }}
                        </span>
                    </div>
                </div>

                <div class="flex flex-col sm:flex-row gap-2">
                    <button
                        v-if="activeTab === 'overview' && programApprovalStatus === 'rejected'"
                        @click="resubmitProgram"
                        class="flex items-center justify-center gap-2 rounded-lg bg-amber-500 px-4 py-2 text-sm font-medium text-white hover:bg-amber-600 w-full sm:w-auto"
                    >
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9M12 8v4l3 3"/>
                        </svg>
                        Submit Again
                    </button>
                    <button
                        v-if="activeTab === 'overview' && !page.url.startsWith('/admin/')"
                        @click="showEditModal = true"
                        class="flex items-center justify-center gap-2 rounded-lg bg-teal-600 px-4 py-2 text-sm font-medium text-white hover:bg-teal-700 w-full sm:w-auto"
                    >
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                        </svg>
                        Edit Program
                    </button>
                </div>
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
            <ProgramOverviewTab v-if="activeTab === 'overview'" :program="displayProgram" />
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
        :sessions="sessions"
        @close="showAddTraineeModal = false"
        @submit="submitAddTrainee"
    />


    <!-- Add Session Modal -->
    <SessionModal
        :show="showAddSessionModal"
        mode="add"
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
