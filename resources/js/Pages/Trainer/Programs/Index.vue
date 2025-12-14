<script setup lang="ts">
import { ref, computed } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import Swal from 'sweetalert2';
import { useToast } from 'vue-toastification';

const toast = useToast();

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

const searchQuery = ref('');
const activeTab = ref('all');
const showCreateModal = ref(false);
const errors = ref<Record<string, string>>({});

const tabs = [
    { key: 'all', label: 'หลักสูตรทั้งหมด' },
    { key: 'teaching', label: 'กำลังสอน' },
    { key: 'completed', label: 'เสร็จสิ้น' },
    { key: 'pending', label: 'อนุมัติรอ' },
];

const form = useForm({
    title: '',
    short_description: '',
    full_description: '',
    category: '',
    level: 'beginner',
    enrollment_limit: 'limited',
    max_participants: '',
    min_participants: '',
    date: '',
    start_time: '',
    end_time: '',
    location: '',
    thumbnail: null,
    registration_start: '',
    registration_end: '',
    require_approval: false,
    send_confirmation_email: false,
    allow_waitlist: false,
    allow_cancel_enrollment: false,
    certificate_template: 'standard',
    certificate_type: 'free',
});

const categories = [
    'Design',
    'Development',
    'Marketing',
    'Business',
    'IT & Software',
    'Professional Development',
];

const validateForm = () => {
    errors.value = {};

    // Required field validations
    if (!form.title.trim()) {
        errors.value.title = 'Course title is required';
    }

    if (!form.short_description.trim()) {
        errors.value.short_description = 'Short description is required';
    }

    if (!form.full_description.trim()) {
        errors.value.full_description = 'Full description is required';
    }

    if (!form.category) {
        errors.value.category = 'Please select a category';
    }

    if (form.enrollment_limit === 'limited' && !form.max_participants) {
        errors.value.max_participants = 'Maximum participants is required for limited enrollment';
    }

    if (!form.date) {
        errors.value.date = 'Date is required';
    }

    if (!form.start_time) {
        errors.value.start_time = 'Start time is required';
    }

    if (!form.end_time) {
        errors.value.end_time = 'End time is required';
    }

    if (!form.location.trim()) {
        errors.value.location = 'Location is required';
    }

    if (!form.registration_start) {
        errors.value.registration_start = 'Registration start date is required';
    }

    if (!form.registration_end) {
        errors.value.registration_end = 'Registration end date is required';
    }

    // Additional validations
    if (form.min_participants && form.max_participants) {
        const min = parseInt(form.min_participants);
        const max = parseInt(form.max_participants);
        if (min > max) {
            errors.value.min_participants = 'Minimum participants cannot exceed maximum';
        }
    }

    return Object.keys(errors.value).length === 0;
};

const handleSubmit = async () => {
    if (validateForm()) {
        // Show SweetAlert confirmation
        const result = await Swal.fire({
            title: 'Are you sure you want to request this course?',
            text: 'Please wait for admin approval after requesting this course.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#0d9488',
            cancelButtonColor: '#6b7280',
            confirmButtonText: 'Confirm',
            cancelButtonText: 'Cancel',
            customClass: {
                popup: 'rounded-lg',
                title: 'text-lg font-semibold',
                htmlContainer: 'text-sm text-gray-600',
            }
        });

        if (result.isConfirmed) {
            // Simulate successful creation
            showCreateModal.value = false;

            // Show success toast
            toast.success('Course published successfully! Registration is now open for students.', {
                timeout: 5000,
            });

            // Reset form
            form.reset();
            errors.value = {};
        }
    } else {
        // Scroll to first error
        const firstError = Object.keys(errors.value)[0];
        const element = document.getElementById(firstError);
        if (element) {
            element.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }
    }
};

const saveDraft = () => {
    console.log('Saving draft...');
};

const getLevelColor = (level: string) => {
    const colors = {
        'Beginner': 'bg-teal-100 text-teal-700',
        'Intermediate': 'bg-amber-100 text-amber-700',
        'Advanced': 'bg-rose-100 text-rose-700',
    };
    return colors[level as keyof typeof colors] || 'bg-gray-100 text-gray-700';
};

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
        location: 'Smart Class room'
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
        location: 'Smart Class room'
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
        location: 'Smart Class room'
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
        location: 'Smart Class room'
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
        location: 'Smart Class room'
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
        location: 'Smart Class room'
    },
];
</script>

<template>
    <Head title="My Courses Management" />

    <div class="flex min-h-screen bg-gray-50">
        <!-- Sidebar -->
        <aside class="w-64 bg-white shadow-sm">
            <div class="p-6">
                <div class="mb-8 flex items-center gap-2">
                    <div class="flex h-10 w-10 items-center justify-center rounded bg-teal-500">
                        <svg class="h-6 w-6 text-white" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M10.394 2.08a1 1 0 00-.788 0l-7 3a1 1 0 000 1.84L5.25 8.051a.999.999 0 01.356-.257l4-1.714a1 1 0 11.788 1.838L7.667 9.088l1.94.831a1 1 0 00.787 0l7-3a1 1 0 000-1.838l-7-3zM3.31 9.397L5 10.12v4.102a8.969 8.969 0 00-1.05-.174 1 1 0 01-.89-.89 11.115 11.115 0 01.25-3.762zM9.3 16.573A9.026 9.026 0 007 14.935v-3.957l1.818.78a3 3 0 002.364 0l5.508-2.361a11.026 11.026 0 01.25 3.762 1 1 0 01-.89.89 8.968 8.968 0 00-5.35 2.524 1 1 0 01-1.4 0zM6 18a1 1 0 001-1v-2.065a8.935 8.935 0 00-2-.712V17a1 1 0 001 1z"/>
                        </svg>
                    </div>
                    <div>
                        <div class="text-xs font-semibold text-teal-600">Training</div>
                        <div class="text-xs font-semibold text-teal-600">Management</div>
                        <div class="text-xs font-semibold text-teal-600">System</div>
                    </div>
                </div>

                <nav class="space-y-1">
                    <Link href="/trainer" class="flex items-center gap-3 rounded-lg px-3 py-2 text-sm text-gray-700 hover:bg-gray-100">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                        </svg>
                        Dashboard
                    </Link>
                    <Link href="/trainer/programs" class="flex items-center gap-3 rounded-lg bg-teal-50 px-3 py-2 text-sm font-medium text-teal-600">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                        </svg>
                        My courses
                    </Link>
                    <a href="#" class="flex items-center gap-3 rounded-lg px-3 py-2 text-sm text-gray-700 hover:bg-gray-100">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                        Attendance
                    </a>
                    <a href="#" class="flex items-center gap-3 rounded-lg px-3 py-2 text-sm text-gray-700 hover:bg-gray-100">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z"/>
                        </svg>
                        Feedback
                    </a>
                    <a href="#" class="flex items-center gap-3 rounded-lg px-3 py-2 text-sm text-gray-700 hover:bg-gray-100">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                        </svg>
                        Setting
                    </a>
                </nav>
            </div>
        </aside>

        <!-- Main Content -->
        <main class="flex-1">
            <!-- Header -->
            <header class="border-b bg-white px-8 py-6">
                <div class="flex items-start justify-between">
                    <div>
                        <h1 class="text-2xl font-bold text-gray-900">My Courses</h1>
                        <p class="mt-1 text-sm text-gray-500">Manage all my courses. Empower Your Training</p>
                    </div>
                    <div class="flex items-center gap-3">
                        <img src="https://ui-avatars.com/api/?name=Olivia+Rhye&background=0D8ABC&color=fff" alt="User" class="h-10 w-10 rounded-full">
                        <div class="text-sm">
                            <div class="font-medium text-gray-900">Olivia Rhye</div>
                            <div class="text-gray-500">olivia@untitledui.com</div>
                        </div>
                    </div>
                </div>
            </header>

            <!-- Content -->
            <div class="p-8">
                <!-- Search and Actions -->
                <div class="mb-6 flex items-center justify-between gap-4">
                    <div class="flex flex-1 items-center gap-4">
                        <div class="relative flex-1 max-w-md">
                            <svg class="absolute left-3 top-1/2 h-5 w-5 -translate-y-1/2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                            </svg>
                            <input
                                v-model="searchQuery"
                                type="text"
                                placeholder="Search courses..."
                                class="w-full rounded-lg border-gray-300 pl-10 pr-4 py-2 focus:border-teal-500 focus:ring-teal-500"
                            />
                        </div>
                    </div>
                    <button @click="showCreateModal = true" class="flex items-center gap-2 rounded-lg bg-teal-600 px-4 py-2 text-sm font-medium text-white hover:bg-teal-700">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                        </svg>
                        Create Course
                    </button>
                    <button class="rounded-lg border border-gray-300 p-2 hover:bg-gray-50">
                        <svg class="h-5 w-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"/>
                        </svg>
                    </button>
                </div>

                <!-- Tabs -->
                <div class="mb-6 flex gap-2">
                    <button
                        v-for="tab in tabs"
                        :key="tab.key"
                        @click="activeTab = tab.key"
                        :class="[
                            'rounded-lg px-4 py-2 text-sm font-medium transition',
                            activeTab === tab.key
                                ? 'bg-teal-100 text-teal-700'
                                : 'text-gray-600 hover:bg-gray-100'
                        ]"
                    >
                        {{ tab.label }}
                    </button>
                </div>

                <!-- Course Grid -->
                <div class="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
                    <Link
                        v-for="program in mockPrograms"
                        :key="program.id"
                        :href="`/trainer/programs/${program.id}`"
                        class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm transition hover:shadow-md cursor-pointer"
                    >
                        <div class="aspect-video w-full overflow-hidden">
                            <img
                                :src="program.image_url"
                                :alt="program.name"
                                class="h-full w-full object-cover"
                            />
                        </div>

                        <div class="p-4">
                            <div class="mb-2 flex items-center justify-between">
                                <div class="flex items-center gap-1">
                                    <svg v-for="i in 5" :key="i" class="h-4 w-4 text-yellow-400" fill="currentColor" viewBox="0 0 20 20">
                                        <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                                    </svg>
                                    <span class="ml-1 text-sm text-gray-600">({{ program.rating }})</span>
                                </div>
                                <span :class="getLevelColor(program.level || 'Beginner')" class="rounded px-2 py-1 text-xs font-medium">
                                    {{ program.level }}
                                </span>
                            </div>

                            <h3 class="mb-3 text-lg font-semibold text-gray-900">
                                {{ program.name }}
                            </h3>

                            <div class="space-y-2 text-sm text-gray-600">
                                <div class="flex items-center gap-2">
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
                                    </svg>
                                    {{ program.students_count }} students
                                    <svg class="ml-auto h-4 w-4 text-teal-600" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                    </svg>
                                    {{ program.price }}
                                </div>
                                <div class="flex items-center gap-2">
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                    </svg>
                                    {{ program.date }}
                                    <svg class="ml-auto h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                    </svg>
                                    {{ program.time }}
                                </div>
                                <div class="flex items-center gap-2">
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                                    </svg>
                                    {{ program.location }}
                                </div>
                            </div>
                        </div>
                    </Link>
                </div>

                <!-- Pagination -->
                <div class="mt-8 flex items-center justify-between">
                    <button class="flex items-center gap-2 rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                        </svg>
                        Previous
                    </button>

                    <div class="flex gap-2">
                        <button class="h-10 w-10 rounded-lg bg-teal-100 text-sm font-medium text-teal-700">1</button>
                        <button class="h-10 w-10 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-100">2</button>
                        <button class="h-10 w-10 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-100">3</button>
                        <span class="flex h-10 w-10 items-center justify-center text-sm text-gray-400">...</span>
                        <button class="h-10 w-10 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-100">8</button>
                        <button class="h-10 w-10 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-100">9</button>
                        <button class="h-10 w-10 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-100">10</button>
                    </div>

                    <button class="flex items-center gap-2 rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">
                        Next
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                        </svg>
                    </button>
                </div>
            </div>
        </main>
    </div>

    <!-- Create Course Modal -->
    <div v-if="showCreateModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50 p-4" @click.self="showCreateModal = false">
        <div class="relative max-h-[90vh] w-full max-w-3xl overflow-y-auto rounded-lg bg-white shadow-xl">
            <div class="sticky top-0 z-10 border-b bg-white px-6 py-4">
                <div class="flex items-center justify-between">
                    <div class="text-center flex-1">
                        <h2 class="text-xl font-bold text-gray-900">Create New Course</h2>
                        <p class="mt-1 text-sm text-gray-500">To create a training course, start by identifying the need and define clear learning objectives.</p>
                    </div>
                    <button @click="showCreateModal = false" class="rounded-lg p-2 hover:bg-gray-100">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>
            </div>

            <form @submit.prevent="handleSubmit" class="p-6 space-y-6">
                <!-- Course Title -->
                <div>
                    <label for="title" class="block text-sm font-medium text-gray-700">
                        Course Title <span class="text-red-500">*</span>
                    </label>
                    <input
                        id="title"
                        v-model="form.title"
                        type="text"
                        placeholder="e.g., Advanced UX Design for Enterprise Application"
                        :class="[
                            'mt-1 block w-full rounded-md shadow-sm focus:ring-teal-500',
                            errors.title ? 'border-red-300 focus:border-red-500' : 'border-gray-300 focus:border-teal-500'
                        ]"
                    />
                    <p v-if="errors.title" class="mt-1 text-sm text-red-600">{{ errors.title }}</p>
                </div>

                <!-- Short Description -->
                <div>
                    <div class="flex items-center justify-between">
                        <label for="short_description" class="block text-sm font-medium text-gray-700">
                            Short Description <span class="text-red-500">*</span>
                        </label>
                        <span class="text-xs text-gray-500">{{ form.short_description.length }}/300</span>
                    </div>
                    <textarea
                        id="short_description"
                        v-model="form.short_description"
                        rows="2"
                        maxlength="300"
                        placeholder="A brief summary of what the course is about."
                        :class="[
                            'mt-1 block w-full rounded-md shadow-sm focus:ring-teal-500',
                            errors.short_description ? 'border-red-300 focus:border-red-500' : 'border-gray-300 focus:border-teal-500'
                        ]"
                    ></textarea>
                    <p v-if="errors.short_description" class="mt-1 text-sm text-red-600">{{ errors.short_description }}</p>
                </div>

                <!-- Full Description -->
                <div>
                    <label for="full_description" class="block text-sm font-medium text-gray-700">
                        Full Description / Objectives <span class="text-red-500">*</span>
                    </label>
                    <div :class="[
                        'mt-1 rounded-md border',
                        errors.full_description ? 'border-red-300' : 'border-gray-300'
                    ]">
                        <div class="flex items-center gap-1 border-b border-gray-200 bg-gray-50 p-2">
                            <button type="button" class="rounded p-1 hover:bg-gray-200" title="Bold">
                                <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 20 20"><path d="M6 4v12h4.5a3.5 3.5 0 001.317-6.729A3.5 3.5 0 0010.5 4H6zm3 5.5H8v-3h1a1.5 1.5 0 110 3zm-1 2h1.5a2 2 0 110 4H8v-4z"/></svg>
                            </button>
                            <button type="button" class="rounded p-1 hover:bg-gray-200" title="Italic">
                                <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 20 20"><path d="M8 2h8v2h-3l-3 12h3v2H5v-2h3l3-12H8V2z"/></svg>
                            </button>
                            <button type="button" class="rounded p-1 hover:bg-gray-200" title="Underline">
                                <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 20 20"><path d="M10 2a5 5 0 00-5 5v4a5 5 0 0010 0V7a5 5 0 00-5-5zM4 17h12v2H4v-2z"/></svg>
                            </button>
                            <div class="mx-2 h-4 w-px bg-gray-300"></div>
                            <button type="button" class="rounded p-1 hover:bg-gray-200" title="Bullet List">
                                <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M3 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm0 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm0 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1z" clip-rule="evenodd"/></svg>
                            </button>
                            <button type="button" class="rounded p-1 hover:bg-gray-200" title="Numbered List">
                                <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 20 20"><path d="M3 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm0 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm0 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1z"/></svg>
                            </button>
                            <div class="mx-2 h-4 w-px bg-gray-300"></div>
                            <button type="button" class="rounded p-1 hover:bg-gray-200" title="Link">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/></svg>
                            </button>
                            <button type="button" class="rounded p-1 hover:bg-gray-200" title="Code">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4"/></svg>
                            </button>
                        </div>
                        <textarea
                            id="full_description"
                            v-model="form.full_description"
                            rows="4"
                            placeholder="Describe the course in detail. You can use Markdown for formatting."
                            class="block w-full border-0 p-3 focus:ring-0"
                        ></textarea>
                    </div>
                    <p v-if="errors.full_description" class="mt-1 text-sm text-red-600">{{ errors.full_description }}</p>
                </div>

                <!-- Course Category -->
                <div>
                    <label for="category" class="block text-sm font-medium text-gray-700">
                        Course Category <span class="text-red-500">*</span>
                    </label>
                    <select
                        id="category"
                        v-model="form.category"
                        :class="[
                            'mt-1 block w-full rounded-md shadow-sm focus:ring-teal-500',
                            errors.category ? 'border-red-300 focus:border-red-500' : 'border-gray-300 focus:border-teal-500'
                        ]"
                    >
                        <option value="">Select Category</option>
                        <option v-for="cat in categories" :key="cat" :value="cat">{{ cat }}</option>
                    </select>
                    <p v-if="errors.category" class="mt-1 text-sm text-red-600">{{ errors.category }}</p>
                </div>

                <!-- Course Level -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-3">Course Level</label>
                    <div class="grid grid-cols-3 gap-3">
                        <label class="relative flex cursor-pointer items-center justify-center rounded-lg border-2 px-4 py-3 transition" :class="form.level === 'beginner' ? 'border-teal-500 bg-teal-50' : 'border-gray-200 hover:border-gray-300'">
                            <input type="radio" v-model="form.level" value="beginner" class="sr-only" />
                            <div class="text-center">
                                <div class="text-sm font-medium" :class="form.level === 'beginner' ? 'text-teal-700' : 'text-gray-900'">Beginner</div>
                                <div class="text-xs text-gray-500">No prior experience required</div>
                            </div>
                        </label>
                        <label class="relative flex cursor-pointer items-center justify-center rounded-lg border-2 px-4 py-3 transition" :class="form.level === 'intermediate' ? 'border-teal-500 bg-teal-50' : 'border-gray-200 hover:border-gray-300'">
                            <input type="radio" v-model="form.level" value="intermediate" class="sr-only" />
                            <div class="text-center">
                                <div class="text-sm font-medium" :class="form.level === 'intermediate' ? 'text-teal-700' : 'text-gray-900'">Intermediate</div>
                                <div class="text-xs text-gray-500">Some experience required</div>
                            </div>
                        </label>
                        <label class="relative flex cursor-pointer items-center justify-center rounded-lg border-2 px-4 py-3 transition" :class="form.level === 'advanced' ? 'border-teal-500 bg-teal-50' : 'border-gray-200 hover:border-gray-300'">
                            <input type="radio" v-model="form.level" value="advanced" class="sr-only" />
                            <div class="text-center">
                                <div class="text-sm font-medium" :class="form.level === 'advanced' ? 'text-teal-700' : 'text-gray-900'">Advanced</div>
                                <div class="text-xs text-gray-500">Extensive experience required</div>
                            </div>
                        </label>
                    </div>
                </div>

                <!-- Enrollment Limit -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-3">Enrollment Limit</label>
                    <div class="grid grid-cols-2 gap-3">
                        <label class="relative flex cursor-pointer items-center rounded-lg border-2 px-4 py-3 transition" :class="form.enrollment_limit === 'limited' ? 'border-teal-500 bg-teal-50' : 'border-gray-200 hover:border-gray-300'">
                            <input type="radio" v-model="form.enrollment_limit" value="limited" class="sr-only" />
                            <div class="flex-1 text-sm font-medium" :class="form.enrollment_limit === 'limited' ? 'text-teal-700' : 'text-gray-900'">Limited</div>
                        </label>
                        <label class="relative flex cursor-pointer items-center rounded-lg border-2 px-4 py-3 transition" :class="form.enrollment_limit === 'unlimited' ? 'border-teal-500 bg-teal-50' : 'border-gray-200 hover:border-gray-300'">
                            <input type="radio" v-model="form.enrollment_limit" value="unlimited" class="sr-only" />
                            <div class="flex-1 text-sm font-medium" :class="form.enrollment_limit === 'unlimited' ? 'text-teal-700' : 'text-gray-900'">Unlimited</div>
                        </label>
                    </div>
                </div>

                <!-- Max/Min Participants -->
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label for="max_participants" class="block text-sm font-medium text-gray-700">
                            Maximum Participants
                            <span v-if="form.enrollment_limit === 'limited'" class="text-red-500">*</span>
                        </label>
                        <input
                            id="max_participants"
                            v-model="form.max_participants"
                            type="number"
                            :disabled="form.enrollment_limit === 'unlimited'"
                            :class="[
                                'mt-1 block w-full rounded-md shadow-sm focus:ring-teal-500 disabled:bg-gray-100',
                                errors.max_participants ? 'border-red-300 focus:border-red-500' : 'border-gray-300 focus:border-teal-500'
                            ]"
                        />
                        <p v-if="errors.max_participants" class="mt-1 text-sm text-red-600">{{ errors.max_participants }}</p>
                    </div>
                    <div>
                        <label for="min_participants" class="block text-sm font-medium text-gray-700">Minimum Participants (Optional)</label>
                        <input
                            id="min_participants"
                            v-model="form.min_participants"
                            type="number"
                            :class="[
                                'mt-1 block w-full rounded-md shadow-sm focus:ring-teal-500',
                                errors.min_participants ? 'border-red-300 focus:border-red-500' : 'border-gray-300 focus:border-teal-500'
                            ]"
                        />
                        <p v-if="errors.min_participants" class="mt-1 text-sm text-red-600">{{ errors.min_participants }}</p>
                    </div>
                </div>
                <p class="text-xs text-gray-500">*Registrants will close automatically when the roll is full</p>

                <!-- Date & Time -->
                <div class="grid grid-cols-3 gap-4">
                    <div>
                        <label for="date" class="block text-sm font-medium text-gray-700">
                            Date <span class="text-red-500">*</span>
                        </label>
                        <input
                            id="date"
                            v-model="form.date"
                            type="date"
                            :class="[
                                'mt-1 block w-full rounded-md shadow-sm focus:ring-teal-500',
                                errors.date ? 'border-red-300 focus:border-red-500' : 'border-gray-300 focus:border-teal-500'
                            ]"
                        />
                        <p v-if="errors.date" class="mt-1 text-sm text-red-600">{{ errors.date }}</p>
                    </div>
                    <div>
                        <label for="start_time" class="block text-sm font-medium text-gray-700">
                            Time <span class="text-red-500">*</span>
                        </label>
                        <input
                            id="start_time"
                            v-model="form.start_time"
                            type="time"
                            :class="[
                                'mt-1 block w-full rounded-md shadow-sm focus:ring-teal-500',
                                errors.start_time ? 'border-red-300 focus:border-red-500' : 'border-gray-300 focus:border-teal-500'
                            ]"
                        />
                        <p v-if="errors.start_time" class="mt-1 text-sm text-red-600">{{ errors.start_time }}</p>
                    </div>
                    <div>
                        <label for="end_time" class="block text-sm font-medium text-gray-700">
                            <span class="text-red-500">*</span>
                        </label>
                        <input
                            id="end_time"
                            v-model="form.end_time"
                            type="time"
                            :class="[
                                'mt-1 block w-full rounded-md shadow-sm focus:ring-teal-500',
                                errors.end_time ? 'border-red-300 focus:border-red-500' : 'border-gray-300 focus:border-teal-500'
                            ]"
                        />
                        <p v-if="errors.end_time" class="mt-1 text-sm text-red-600">{{ errors.end_time }}</p>
                    </div>
                </div>

                <!-- Location -->
                <div>
                    <label for="location" class="block text-sm font-medium text-gray-700">
                        Location <span class="text-red-500">*</span>
                    </label>
                    <div class="relative mt-1">
                        <input
                            id="location"
                            v-model="form.location"
                            type="text"
                            placeholder="e.g., Main Conference Room"
                            :class="[
                                'block w-full rounded-md pr-10 shadow-sm focus:ring-teal-500',
                                errors.location ? 'border-red-300 focus:border-red-500' : 'border-gray-300 focus:border-teal-500'
                            ]"
                        />
                        <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-3">
                            <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                            </svg>
                        </div>
                    </div>
                    <p v-if="errors.location" class="mt-1 text-sm text-red-600">{{ errors.location }}</p>
                </div>

                <!-- Course Thumbnail -->
                <div>
                    <label class="block text-sm font-medium text-gray-700">Course Thumbnail</label>
                    <div class="mt-1 flex justify-center rounded-lg border-2 border-dashed border-gray-300 px-6 py-10 hover:border-gray-400">
                        <div class="text-center">
                            <svg class="mx-auto h-12 w-12 text-teal-500" fill="none" stroke="currentColor" viewBox="0 0 48 48">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M28 8H12a4 4 0 00-4 4v20m32-12v8m0 0v8a4 4 0 01-4 4H12a4 4 0 01-4-4v-4m32-4l-3.172-3.172a4 4 0 00-5.656 0L28 28M8 32l9.172-9.172a4 4 0 015.656 0L28 28m0 0l4 4m4-24h8m-4-4v8m-12 4h.02"/>
                            </svg>
                            <div class="mt-4 flex text-sm text-gray-600">
                                <button type="button" class="font-medium text-teal-600 hover:text-teal-500">Click to upload</button>
                                <p class="pl-1">or drag and drop</p>
                            </div>
                            <p class="text-xs text-gray-500">JPG, JPEG, PNG less than 1MB (max 1280*720 px)</p>
                        </div>
                    </div>
                </div>

                <!-- Registration Period -->
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label for="registration_start" class="block text-sm font-medium text-gray-700">
                            Registration Period <span class="text-red-500">*</span>
                        </label>
                        <input
                            id="registration_start"
                            v-model="form.registration_start"
                            type="date"
                            :class="[
                                'mt-1 block w-full rounded-md shadow-sm focus:ring-teal-500',
                                errors.registration_start ? 'border-red-300 focus:border-red-500' : 'border-gray-300 focus:border-teal-500'
                            ]"
                        />
                        <p v-if="errors.registration_start" class="mt-1 text-sm text-red-600">{{ errors.registration_start }}</p>
                    </div>
                    <div>
                        <label for="registration_end" class="block text-sm font-medium text-gray-700">
                            <span class="text-red-500">*</span>
                        </label>
                        <input
                            id="registration_end"
                            v-model="form.registration_end"
                            type="date"
                            :class="[
                                'mt-1 block w-full rounded-md shadow-sm focus:ring-teal-500',
                                errors.registration_end ? 'border-red-300 focus:border-red-500' : 'border-gray-300 focus:border-teal-500'
                            ]"
                        />
                        <p v-if="errors.registration_end" class="mt-1 text-sm text-red-600">{{ errors.registration_end }}</p>
                    </div>
                </div>

                <!-- Enrollment Options -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-3">Enrollment Options</label>
                    <div class="space-y-2">
                        <label class="flex items-center">
                            <input type="checkbox" v-model="form.require_approval" class="rounded border-gray-300 text-teal-600 focus:ring-teal-500">
                            <span class="ml-2 text-sm text-gray-700">Require approval</span>
                        </label>
                        <label class="flex items-center">
                            <input type="checkbox" v-model="form.send_confirmation_email" class="rounded border-gray-300 text-teal-600 focus:ring-teal-500">
                            <span class="ml-2 text-sm text-gray-700">Send confirmation email</span>
                        </label>
                        <label class="flex items-center">
                            <input type="checkbox" v-model="form.allow_waitlist" class="rounded border-gray-300 text-teal-600 focus:ring-teal-500">
                            <span class="ml-2 text-sm text-gray-700">Allow waitlist</span>
                        </label>
                        <label class="flex items-center">
                            <input type="checkbox" v-model="form.allow_cancel_enrollment" class="rounded border-gray-300 text-teal-600 focus:ring-teal-500">
                            <span class="ml-2 text-sm text-gray-700">Allow participants to cancel enrollment</span>
                        </label>
                    </div>
                </div>

                <!-- Certificate Template -->
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label for="certificate_template" class="block text-sm font-medium text-gray-700">Certificate Template</label>
                        <select
                            id="certificate_template"
                            v-model="form.certificate_template"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-teal-500 focus:ring-teal-500"
                        >
                            <option value="standard">Standard</option>
                            <option value="premium">Premium</option>
                            <option value="custom">Custom</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Certificate Type</label>
                        <div class="mt-1 flex gap-4">
                            <label class="flex items-center">
                                <input type="radio" v-model="form.certificate_type" value="free" class="border-gray-300 text-teal-600 focus:ring-teal-500">
                                <span class="ml-2 text-sm text-gray-700">Free</span>
                            </label>
                            <label class="flex items-center">
                                <input type="radio" v-model="form.certificate_type" value="paid" class="border-gray-300 text-teal-600 focus:ring-teal-500">
                                <span class="ml-2 text-sm text-gray-700">Paid</span>
                            </label>
                        </div>
                    </div>
                </div>

                <!-- Buttons -->
                <div class="flex items-center justify-between border-t pt-6">
                    <button type="button" @click="saveDraft" class="rounded-lg border border-gray-300 px-6 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">
                        Save draft
                    </button>
                    <button type="submit" class="rounded-lg bg-teal-600 px-6 py-2 text-sm font-medium text-white hover:bg-teal-700">
                        Confirm
                    </button>
                </div>
            </form>
        </div>
    </div>

</template>
