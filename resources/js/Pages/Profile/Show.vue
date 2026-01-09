<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { Head, useForm, usePage, router } from '@inertiajs/vue3';
import { useToast } from 'vue-toastification';
import axios from 'axios';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import TrainerLayout from '@/Layouts/TrainerLayout.vue';
import TraineeLayout from '@/Layouts/TraineeLayout.vue';
import ProfileSkeleton from '@/Pages/Profile/Partials/ProfileSkeleton.vue';
import ConfirmationDialog from '@/Components/ConfirmationDialog.vue';
import ImagePreviewModal from '@/Components/ImagePreviewModal.vue';
import {
    Bell,
    Shield,
    Globe,
    BookOpen,
    User,
    Lock,
    Save,
    CheckCircle2,
    Mail,
    Phone,
    Briefcase,
    Building,
    MapPin,
    Camera,
    Upload,
    Trash2,
    X
} from 'lucide-vue-next';

const toast = useToast();
const page = usePage();

const props = defineProps({
    user: {
        type: Object,
        required: true,
    },
});

const isLoadingProfile = ref(false);
const apiUser = ref({
    ...props.user,
    avatar_present: props.user.profile?.has_avatar || false,
});

const roleName = computed(() => {
    return apiUser.value?.role?.name || props.user?.role || page.props.auth?.user?.role?.name || 'trainee';
});

const LayoutComponent = computed(() => {
    if (roleName.value === 'trainer') return TrainerLayout;
    if (roleName.value === 'trainee') return TraineeLayout;
    return AuthenticatedLayout;
});

// Admin mocked settings
const adminSettings = ref({
    general: {
        siteName: 'Training Management System',
        siteDescription: 'A comprehensive platform for managing training programs',
        contactEmail: 'admin@training.com',
        timezone: 'UTC',
    },
    notifications: {
        emailNotifications: true,
        courseUpdates: true,
        attendanceAlerts: true,
    },
    courses: {
        maxStudentsPerCourse: 50,
        autoApproval: false,
        allowSelfEnrollment: true,
    },
    security: {
        twoFactorAuth: false,
        sessionTimeout: 60,
        passwordExpiry: 90,
    }
});

// Student/Trainer notification settings (mock)
const notificationForm = useForm({
    email_updates: true,
    session_reminders: true,
    marketing: false,
});

const adminTabs = [
    { id: 'general', label: 'General', icon: Globe, description: 'Basic site configuration' },
    { id: 'notifications', label: 'Notifications', icon: Bell, description: 'Email and alerts' },
    { id: 'courses', label: 'Courses', icon: BookOpen, description: 'Training rules' },
    { id: 'security', label: 'Security', icon: Shield, description: 'Access control' },
];

const userTabs = [
    { id: 'profile', label: 'My Profile', icon: User, description: 'Personal details' },
    { id: 'security', label: 'Security', icon: Lock, description: 'Password & protection' },
    { id: 'notifications', label: 'Notifications', icon: Bell, description: 'Alert preferences' },
];

const tabs = computed(() => roleName.value === 'admin' ? adminTabs : userTabs);
const activeTab = ref(roleName.value === 'admin' ? 'general' : 'profile');

const avatarPreview = ref(null);
const avatarVersion = ref(Date.now());
const isUploadingAvatar = ref(false);
const showAvatarMenu = ref(false);

const form = useForm({
    name: '',
    phone: '',
    date_of_birth: '',
    gender: '',
    organization: '',
    department: '',
    bio: '',
});

const passwordForm = useForm({
    current_password: '',
    password: '',
    password_confirmation: '',
});

// Confirmation dialog state
const showConfirmDialog = ref(false);
const confirmDialogConfig = ref({
    title: '',
    message: '',
    confirmText: 'Confirm',
    cancelText: 'Cancel',
    confirmButtonClass: 'bg-[#2f837d] hover:bg-[#266a66]',
    onConfirm: () => { },
});

const avatarUrl = computed(() => {
    if (avatarPreview.value) return avatarPreview.value;
    if (apiUser.value?.avatar_present) {
        return `/api/me/avatar?t=${avatarVersion.value}`;
    }
    return '/default-avatar.svg';
});

const loadProfile = async () => {
    isLoadingProfile.value = true;
    try {
        await axios.get('/sanctum/csrf-cookie');
        const token = document.cookie.split('; ').find(row => row.startsWith('XSRF-TOKEN='))?.split('=')[1];
        const { data } = await axios.get('/api/me', {
            headers: { 'X-XSRF-TOKEN': token ? decodeURIComponent(token) : '' },
        });

        apiUser.value = { ...data.user, avatar_present: data.avatar_present };
        form.name = data.user.name || '';
        form.phone = data.profile?.phone || '';
        form.date_of_birth = data.profile?.date_of_birth || '';
        form.gender = data.profile?.gender || '';
        form.organization = data.profile?.organization || '';
        form.department = data.profile?.department || '';
        form.bio = data.profile?.bio || '';

    } catch (error) {
        toast.error('Failed to load profile data');
    } finally {
        isLoadingProfile.value = false;
    }
};

const submitProfileForm = async () => {
    form.clearErrors();
    form.processing = true;
    try {
        const token = document.cookie.split('; ').find(row => row.startsWith('XSRF-TOKEN='))?.split('=')[1];
        await axios.put('/api/me/profile', { ...form.data() }, {
            headers: { 'X-XSRF-TOKEN': token ? decodeURIComponent(token) : '' },
        });
        toast.success('Profile updated successfully.');
        await loadProfile();
        router.reload({ only: ['auth'] });
    } catch (error) {
        if (error.response?.status === 422) {
            const errors = error.response.data.errors || {};
            Object.keys(errors).forEach(key => form.setError(key, errors[key][0]));
            toast.error('Please check the form for errors.');
        } else {
            toast.error('Failed to update profile.');
        }
    } finally {
        form.processing = false;
    }
};

const submitPasswordForm = () => {
    passwordForm.put(route('password.update'), {
        preserveScroll: true,
        onSuccess: () => {
            passwordForm.reset();
            toast.success('Password updated successfully.');
        },
    });
};

const onAvatarSelected = async (event) => {
    const file = event.target.files[0];
    if (!file) return;

    const reader = new FileReader();
    reader.onload = (e) => avatarPreview.value = e.target.result;
    reader.readAsDataURL(file);

    isUploadingAvatar.value = true;
    try {
        const token = document.cookie.split('; ').find(row => row.startsWith('XSRF-TOKEN='))?.split('=')[1];
        const formData = new FormData();
        formData.append('avatar', file);
        await axios.post('/api/me/avatar', formData, {
            headers: {
                'X-XSRF-TOKEN': token ? decodeURIComponent(token) : '',
                'Content-Type': 'multipart/form-data',
            },
        });
        avatarVersion.value = Date.now();
        avatarPreview.value = null;
        event.target.value = '';
        await loadProfile();
        router.reload({ only: ['auth'] });
        toast.success('Avatar updated successfully');
    } catch (error) {
        toast.error('Failed to upload avatar.');
    } finally {
        isUploadingAvatar.value = false;
    }
};

const deleteAvatar = async () => {
    try {
        const token = document.cookie.split('; ').find(row => row.startsWith('XSRF-TOKEN='))?.split('=')[1];
        await axios.delete('/api/me/avatar', {
            headers: { 'X-XSRF-TOKEN': token ? decodeURIComponent(token) : '' },
        });
        avatarVersion.value = Date.now();
        avatarPreview.value = null;
        await loadProfile();
        router.reload({ only: ['auth'] });
        toast.success('Avatar deleted successfully');
    } catch (error) {
        toast.error('Failed to delete avatar.');
    }
};

const triggerFileUpload = () => {
    document.getElementById('avatar-upload-input')?.click();
    showAvatarMenu.value = false;
};

const toggleAvatarMenu = () => {
    showAvatarMenu.value = !showAvatarMenu.value;
};

const handleDeleteAvatar = () => {
    showAvatarMenu.value = false;
    deleteAvatar();
};

// Close avatar menu when clicking outside
const closeAvatarMenuOnClickOutside = (event) => {
    const avatarMenu = document.getElementById('avatar-menu');
    const cameraButton = document.getElementById('camera-button');
    if (avatarMenu && !avatarMenu.contains(event.target) && !cameraButton.contains(event.target)) {
        showAvatarMenu.value = false;
    }
};

onMounted(() => {
    loadProfile();
    document.addEventListener('click', closeAvatarMenuOnClickOutside);
});

onUnmounted(() => {
    document.removeEventListener('click', closeAvatarMenuOnClickOutside);
});
</script>

<template>

    <Head title="Profile & Settings" />
    <component :is="LayoutComponent">
        <ProfileSkeleton v-if="isLoadingProfile" />
        <div v-else class="space-y-6">
            <!-- Header -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900 tracking-tight">{{ $t('Settings') }}</h1>
                    <p class="mt-1 text-sm text-gray-500">{{ $t('Manage your profile, preferences and security.') }}</p>
                </div>
            </div>

            <div class="flex flex-col lg:flex-row gap-8">
                <!-- Sidebar -->
                <div class="lg:w-72 flex-shrink-0">
                    <!-- User Card -->
                    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 mb-6">
                        <div class="flex flex-col items-center text-center">
                            <div class="relative mb-4">
                                <div
                                    class="w-24 h-24 rounded-full bg-gray-100 p-1 border border-gray-100 flex items-center justify-center overflow-hidden">
                                    <img :src="avatarUrl" alt="Avatar"
                                        class="w-full h-full rounded-full object-cover" />
                                </div>
                                <div class="absolute bottom-0 right-0">
                                    <button 
                                        id="camera-button"
                                        @click="toggleAvatarMenu"
                                        class="p-1.5 bg-[#2f837d] text-white rounded-full hover:bg-[#266a66] transition-colors shadow-sm"
                                        title="Avatar Options">
                                        <Camera class="w-4 h-4" />
                                    </button>
                                    <!-- Avatar Menu Dropdown -->
                                    <Transition
                                        enter-active-class="transition ease-out duration-100"
                                        enter-from-class="transform opacity-0 scale-95"
                                        enter-to-class="transform opacity-100 scale-100"
                                        leave-active-class="transition ease-in duration-75"
                                        leave-from-class="transform opacity-100 scale-100"
                                        leave-to-class="transform opacity-0 scale-95">
                                        <div 
                                            v-if="showAvatarMenu" 
                                            id="avatar-menu"
                                            class="absolute right-0 mt-2 w-48 bg-white rounded-lg shadow-lg border border-gray-200 py-1 z-50">
                                            <button 
                                                @click="triggerFileUpload"
                                                :disabled="isUploadingAvatar"
                                                class="w-full px-4 py-2 text-left text-sm text-gray-700 hover:bg-gray-50 flex items-center disabled:opacity-50 disabled:cursor-not-allowed">
                                                <Upload class="w-4 h-4 mr-2 text-[#2f837d]" />
                                                {{ $t('Upload Avatar') }}
                                            </button>
                                            <button 
                                                v-if="apiUser.avatar_present"
                                                @click="handleDeleteAvatar"
                                                class="w-full px-4 py-2 text-left text-sm text-red-600 hover:bg-red-50 flex items-center">
                                                <Trash2 class="w-4 h-4 mr-2" />
                                                {{ $t('Delete Avatar') }}
                                            </button>
                                        </div>
                                    </Transition>
                                </div>
                                <input id="avatar-upload-input" type="file" accept="image/*" class="hidden"
                                    @change="onAvatarSelected" :disabled="isUploadingAvatar" />
                            </div>
                            <h2 class="text-lg font-bold text-gray-900">{{ apiUser.name }}</h2>
                            <p class="text-sm text-gray-500">{{ apiUser.email }}</p>
                            <div
                                class="mt-3 inline-flex px-3 py-1 rounded-full bg-[#2f837d]/10 text-[#2f837d] text-xs font-semibold uppercase tracking-wide">
                                {{ roleName }}
                            </div>
                        </div>
                    </div>

                    <!-- Navigation -->
                    <nav class="space-y-1">
                        <button v-for="tab in tabs" :key="tab.id" @click="activeTab = tab.id" :class="[
                            activeTab === tab.id
                                ? 'bg-[#2f837d]/5 text-[#2f837d] shadow-sm ring-1 ring-[#2f837d]/20'
                                : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900',
                            'group flex items-center w-full px-3 py-3 text-sm font-medium rounded-xl transition-all duration-200'
                        ]">
                            <component :is="tab.icon" :class="[
                                activeTab === tab.id ? 'text-[#2f837d]' : 'text-gray-400 group-hover:text-gray-500',
                                'flex-shrink-0 mr-3 h-5 w-5 transition-colors duration-200'
                            ]" />
                            <div class="text-left">
                                <span class="block">{{ $t(tab.label) }}</span>
                            </div>
                        </button>
                    </nav>
                </div>

                <!-- Content Area -->
                <div class="flex-1 min-w-0">
                    <div
                        class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden min-h-[500px] relative">
                        <Transition enter-active-class="transition duration-300 ease-out"
                            enter-from-class="transform opacity-0 translate-x-4"
                            enter-to-class="transform opacity-100 translate-x-0"
                            leave-active-class="transition duration-200 ease-in"
                            leave-from-class="transform opacity-100 translate-x-0"
                            leave-to-class="transform opacity-0 -translate-x-4" mode="out-in">
                            <!-- Start Admin Specific Tabs -->
                            <div v-if="activeTab === 'general' && roleName === 'admin'" key="admin-general" class="p-8">
                                <h3 class="text-xl font-bold text-gray-900 mb-6">{{ $t('General Information') }}</h3>
                                <div class="grid grid-cols-1 gap-y-6 sm:grid-cols-2 gap-x-6">
                                    <div class="sm:col-span-2">
                                        <label class="form-label">{{ $t('Site Name') }}</label>
                                        <input v-model="adminSettings.general.siteName" type="text"
                                            class="form-input" />
                                    </div>
                                    <div class="sm:col-span-2">
                                        <label class="form-label">{{ $t('Description') }}</label>
                                        <textarea v-model="adminSettings.general.siteDescription" rows="3"
                                            class="form-input"></textarea>
                                    </div>
                                    <div>
                                        <label class="form-label">{{ $t('Contact Email') }}</label>
                                        <input v-model="adminSettings.general.contactEmail" type="email"
                                            class="form-input" />
                                    </div>
                                    <div>
                                        <label class="form-label">{{ $t('Timezone') }}</label>
                                        <select v-model="adminSettings.general.timezone" class="form-input">
                                            <option value="UTC">UTC</option>
                                            <option value="EST">EST</option>
                                        </select>
                                    </div>
                                    <div class="sm:col-span-2">
                                        <button class="btn-primary">{{ $t('Save General Settings') }}</button>
                                    </div>
                                </div>
                            </div>

                            <div v-else-if="activeTab === 'courses' && roleName === 'admin'" key="admin-courses"
                                class="p-8">
                                <h3 class="text-xl font-bold text-gray-900 mb-6">{{ $t('Course Configuration') }}</h3>
                                <div class="space-y-6 max-w-2xl">
                                    <div class="flex items-center justify-between py-4 border-b border-gray-50">
                                        <div>
                                            <p class="font-medium text-gray-900">{{ $t('Auto-Approval') }}</p>
                                            <p class="text-sm text-gray-500">{{ $t('New courses published immediately') }}</p>
                                        </div>
                                        <button
                                            @click="adminSettings.courses.autoApproval = !adminSettings.courses.autoApproval"
                                            :class="adminSettings.courses.autoApproval ? 'bg-[#2f837d]' : 'bg-gray-200'"
                                            class="toggle-switch">
                                            <span
                                                :class="adminSettings.courses.autoApproval ? 'translate-x-5' : 'translate-x-0'"
                                                class="toggle-knob"></span>
                                        </button>
                                    </div>
                                    <div class="flex items-center justify-between py-4">
                                        <div>
                                            <p class="font-medium text-gray-900">{{ $t('Self-Enrollment') }}</p>
                                            <p class="text-sm text-gray-500">{{ $t('Allow students to enroll without approval') }}</p>
                                        </div>
                                        <button
                                            @click="adminSettings.courses.allowSelfEnrollment = !adminSettings.courses.allowSelfEnrollment"
                                            :class="adminSettings.courses.allowSelfEnrollment ? 'bg-[#2f837d]' : 'bg-gray-200'"
                                            class="toggle-switch">
                                            <span
                                                :class="adminSettings.courses.allowSelfEnrollment ? 'translate-x-5' : 'translate-x-0'"
                                                class="toggle-knob"></span>
                                        </button>
                                    </div>
                                </div>
                            </div>
                            <!-- End Admin Specific Tabs -->

                            <!-- User Profile Tab -->
                            <div v-else-if="activeTab === 'profile'" key="profile" class="p-8">
                                <div class="flex items-center justify-between mb-8">
                                    <div>
                                        <h3 class="text-xl font-bold text-gray-900">{{ $t('Profile Information') }}</h3>
                                        <p class="text-sm text-gray-500 mt-1">{{ $t('Update your personal details.') }}</p>
                                    </div>
                                    <button @click="submitProfileForm" :disabled="form.processing" class="btn-primary">
                                        <Save class="w-4 h-4 mr-2" />
                                        {{ $t('Save Changes') }}
                                    </button>
                                </div>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <div>
                                        <label class="form-label">{{ $t('Full Name') }}</label>
                                        <div class="relative">
                                            <User
                                                class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 w-5 h-5" />
                                            <input v-model="form.name" type="text" class="form-input pl-10" />
                                        </div>
                                        <p v-if="form.errors.name" class="text-xs text-red-600 mt-1">{{ form.errors.name
                                            }}</p>
                                    </div>
                                    <div>
                                        <label class="form-label">Email</label>
                                        <div class="relative">
                                            <Mail
                                                class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 w-5 h-5" />
                                            <input :value="apiUser.email" type="email"
                                                class="form-input pl-10 bg-gray-50" disabled />
                                        </div>
                                    </div>
                                    <div>
                                        <label class="form-label">{{ $t('Phone') }}</label>
                                        <div class="relative">
                                            <Phone
                                                class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 w-5 h-5" />
                                            <input v-model="form.phone" type="tel" class="form-input pl-10" />
                                        </div>
                                    </div>
                                    <div>
                                        <label class="form-label">{{ $t('Department') }}</label>
                                        <div class="relative">
                                            <Building
                                                class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 w-5 h-5" />
                                            <input v-model="form.department" type="text" class="form-input pl-10" />
                                        </div>
                                    </div>
                                    <div class="md:col-span-2">
                                        <label class="form-label">{{ $t('Bio') }}</label>
                                        <div class="relative">
                                            <textarea v-model="form.bio" rows="4" class="form-input"
                                                placeholder="Tell us about yourself..."></textarea>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Security Tab (Shared) -->
                            <div v-else-if="activeTab === 'security'" key="security" class="p-8">
                                <h3 class="text-xl font-bold text-gray-900 mb-6">{{ $t('Security') }}</h3>
                                <div class="max-w-xl space-y-6">
                                    <!-- Admin Security Settings if Admin -->
                                    <div v-if="roleName === 'admin'"
                                        class="p-4 bg-gray-50 rounded-xl border border-gray-100 mb-8">
                                        <div class="flex items-center justify-between mb-4">
                                            <div>
                                                <p class="font-medium text-gray-900">{{ $t('Two-Factor Authentication') }}</p>
                                                <p class="text-sm text-gray-500">{{ $t('Add an extra layer of security') }}</p>
                                            </div>
                                            <button
                                                @click="adminSettings.security.twoFactorAuth = !adminSettings.security.twoFactorAuth"
                                                :class="adminSettings.security.twoFactorAuth ? 'bg-[#2f837d]' : 'bg-gray-200'"
                                                class="toggle-switch">
                                                <span
                                                    :class="adminSettings.security.twoFactorAuth ? 'translate-x-5' : 'translate-x-0'"
                                                    class="toggle-knob"></span>
                                            </button>
                                        </div>
                                    </div>

                                    <div>
                                        <h4 class="text-base font-semibold text-gray-900 mb-4">{{ $t('Update Password') }}</h4>
                                        <form @submit.prevent="submitPasswordForm" class="space-y-4">
                                            <div>
                                                <label class="form-label">{{ $t('Current Password') }}</label>
                                                <input v-model="passwordForm.current_password" type="password"
                                                    class="form-input" />
                                                <p v-if="passwordForm.errors.current_password"
                                                    class="text-xs text-red-600 mt-1">{{
                                                    passwordForm.errors.current_password }}</p>
                                            </div>
                                            <div>
                                                <label class="form-label">{{ $t('New Password') }}</label>
                                                <input v-model="passwordForm.password" type="password"
                                                    class="form-input" />
                                                <p v-if="passwordForm.errors.password"
                                                    class="text-xs text-red-600 mt-1">{{ passwordForm.errors.password }}
                                                </p>
                                            </div>
                                            <div>
                                                <label class="form-label">{{ $t('Confirm New Password') }}</label>
                                                <input v-model="passwordForm.password_confirmation" type="password"
                                                    class="form-input" />
                                            </div>
                                            <button type="submit" :disabled="passwordForm.processing"
                                                class="btn-primary">
                                                {{ $t('Update Password') }}
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </div>

                            <!-- Notifications Tab (Shared) -->
                            <div v-else-if="activeTab === 'notifications'" key="notifications" class="p-8">
                                <h3 class="text-xl font-bold text-gray-900 mb-6">{{ $t('Notifications') }}</h3>
                                <div class="space-y-6 max-w-2xl">
                                    <!-- Admin Notifications -->
                                    <div v-if="roleName === 'admin'" class="space-y-6">
                                        <div class="flex items-center justify-between py-4 border-b border-gray-50">
                                            <div>
                                                <p class="font-medium text-gray-900">{{ $t('Email Notifications') }}</p>
                                                <p class="text-sm text-gray-500">{{ $t('Receive daily summaries of activity.') }}</p>
                                            </div>
                                            <button
                                                @click="adminSettings.notifications.emailNotifications = !adminSettings.notifications.emailNotifications"
                                                :class="adminSettings.notifications.emailNotifications ? 'bg-[#2f837d]' : 'bg-gray-200'"
                                                class="toggle-switch">
                                                <span
                                                    :class="adminSettings.notifications.emailNotifications ? 'translate-x-5' : 'translate-x-0'"
                                                    class="toggle-knob"></span>
                                            </button>
                                        </div>
                                    </div>

                                    <!-- User Notifications -->
                                    <div v-else class="space-y-6">
                                        <div class="flex items-center justify-between py-4 border-b border-gray-50">
                                            <div>
                                                <p class="font-medium text-gray-900">{{ $t('Email Updates') }}</p>
                                                <p class="text-sm text-gray-500">{{ $t('Receive daily summaries of your sessions.') }}</p>
                                            </div>
                                            <button
                                                @click="notificationForm.email_updates = !notificationForm.email_updates"
                                                :class="notificationForm.email_updates ? 'bg-[#2f837d]' : 'bg-gray-200'"
                                                class="toggle-switch">
                                                <span
                                                    :class="notificationForm.email_updates ? 'translate-x-5' : 'translate-x-0'"
                                                    class="toggle-knob"></span>
                                            </button>
                                        </div>
                                        <div class="flex items-center justify-between py-4 border-b border-gray-50">
                                            <div>
                                                <p class="font-medium text-gray-900">Marketing</p>
                                                <p class="text-sm text-gray-500">Receive marketing tips.</p>
                                            </div>
                                            <button @click="notificationForm.marketing = !notificationForm.marketing"
                                                :class="notificationForm.marketing ? 'bg-[#2f837d]' : 'bg-gray-200'"
                                                class="toggle-switch">
                                                <span
                                                    :class="notificationForm.marketing ? 'translate-x-5' : 'translate-x-0'"
                                                    class="toggle-knob"></span>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </Transition>
                    </div>
                </div>
            </div>
        </div>


        <ConfirmationDialog :show="showConfirmDialog" :title="confirmDialogConfig.title"
            :message="confirmDialogConfig.message" :confirm-text="confirmDialogConfig.confirmText"
            :cancel-text="confirmDialogConfig.cancelText" :confirm-button-class="confirmDialogConfig.confirmButtonClass"
            @confirm="confirmDialogConfig.onConfirm" @close="showConfirmDialog = false" />
    </component>
</template>

<style scoped>
.form-label {
    @apply block text-sm font-medium text-gray-700 mb-1.5;
}

.form-input {
    @apply block w-full rounded-xl border-gray-200 shadow-sm focus:border-[#2f837d] focus:ring-[#2f837d] sm:text-sm py-2.5 transition-shadow duration-200;
}

.btn-primary {
    @apply inline-flex items-center px-4 py-2 bg-[#2f837d] hover:bg-[#266a66] text-white text-sm font-medium rounded-xl shadow-sm transition-all focus:ring-4 focus:ring-[#2f837d]/20 disabled:opacity-75 disabled:cursor-not-allowed;
}

.toggle-switch {
    @apply relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-[#2f837d] focus:ring-offset-2;
}

.toggle-knob {
    @apply pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out;
}
</style>
