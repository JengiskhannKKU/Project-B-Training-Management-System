<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { Head, useForm, usePage, router } from '@inertiajs/vue3';
import { useToast } from 'vue-toastification';
import axios from 'axios';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import TrainerLayout from '@/Layouts/TrainerLayout.vue';
import StudentLayout from '@/Layouts/StudentLayout.vue';
import LoadingSpinner from '@/Components/LoadingSpinner.vue';
import ConfirmationDialog from '@/Components/ConfirmationDialog.vue';
import ImagePreviewModal from '@/Components/ImagePreviewModal.vue';
import { Bell, ShieldCheck, Settings, Trash2, X, Edit, Upload } from 'lucide-vue-next';

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
    const role = apiUser.value?.role?.name || props.user.role || page.props.auth?.user?.role?.name || page.props.auth?.user?.role || 'user';
    return role;
});

const LayoutComponent = computed(() => {
    if (roleName.value === 'admin') return AdminLayout;
    if (roleName.value === 'student') return StudentLayout;
    return TrainerLayout;
});

const activeTab = ref('profile');
const showAccountSettings = ref(false);
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

// Store original form data for comparison
const originalFormData = ref({
    name: '',
    phone: '',
    date_of_birth: '',
    gender: '',
    organization: '',
    department: '',
    bio: '',
});

// Check if form has changes
const hasFormChanges = computed(() => {
    return form.name !== originalFormData.value.name ||
        form.phone !== originalFormData.value.phone ||
        form.date_of_birth !== originalFormData.value.date_of_birth ||
        form.gender !== originalFormData.value.gender ||
        form.organization !== originalFormData.value.organization ||
        form.department !== originalFormData.value.department ||
        form.bio !== originalFormData.value.bio;
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
    confirmText: 'ยืนยัน',
    cancelText: 'ยกเลิก',
    confirmButtonClass: 'bg-[#2f837d] hover:bg-[#266a66]',
    onConfirm: () => {},
});

// Image preview state
const showImagePreview = ref(false);

const avatarUrl = computed(() => {
    if (avatarPreview.value) return avatarPreview.value;
    if (apiUser.value?.avatar_present) {
        return `/api/me/avatar?t=${avatarVersion.value}`;
    }
    // Return default avatar when no avatar is present
    return '/default-avatar.svg';
});

const loadProfile = async () => {
    isLoadingProfile.value = true;
    try {
        // Initialize CSRF cookie for Sanctum
        await axios.get('/sanctum/csrf-cookie');

        // Extract XSRF token from cookie and send as header
        const token = document.cookie
            .split('; ')
            .find(row => row.startsWith('XSRF-TOKEN='))
            ?.split('=')[1];

        // Load profile from GET /api/me
        const { data } = await axios.get('/api/me', {
            headers: {
                'X-XSRF-TOKEN': token ? decodeURIComponent(token) : '',
            },
        });

        // Set apiUser with avatar_present included
        apiUser.value = {
            ...data.user,
            avatar_present: data.avatar_present,
        };

        // Map API response to form: name, phone, bio, etc.
        form.name = data.user.name || '';
        form.phone = data.profile?.phone || '';
        form.date_of_birth = data.profile?.date_of_birth || '';
        form.gender = data.profile?.gender || '';
        form.organization = data.profile?.organization || '';
        form.department = data.profile?.department || '';
        form.bio = data.profile?.bio || '';

        // Store original data for comparison
        originalFormData.value = {
            name: data.user.name || '',
            phone: data.profile?.phone || '',
            date_of_birth: data.profile?.date_of_birth || '',
            gender: data.profile?.gender || '',
            organization: data.profile?.organization || '',
            department: data.profile?.department || '',
            bio: data.profile?.bio || '',
        };
    } catch (error) {
        toast.error('Failed to load profile data');
        console.error('Profile load error:', error.response || error);
    } finally {
        isLoadingProfile.value = false;
    }
};

// Close avatar menu when clicking outside
const handleClickOutside = (event) => {
    if (showAvatarMenu.value) {
        const menu = event.target.closest('.avatar-menu-container');
        if (!menu) {
            showAvatarMenu.value = false;
        }
    }
};

onMounted(() => {
    loadProfile();
    document.addEventListener('click', handleClickOutside);
});

onUnmounted(() => {
    document.removeEventListener('click', handleClickOutside);
});

const submitProfileForm = async () => {
    // Clear previous errors
    form.clearErrors();
    form.processing = true;

    try {
        // Extract XSRF token from cookie
        const token = document.cookie
            .split('; ')
            .find(row => row.startsWith('XSRF-TOKEN='))
            ?.split('=')[1];

        // Submit to PUT /api/me/profile
        await axios.put('/api/me/profile', {
            name: form.name,
            phone: form.phone,
            date_of_birth: form.date_of_birth,
            gender: form.gender,
            organization: form.organization,
            department: form.department,
            bio: form.bio,
        }, {
            headers: {
                'X-XSRF-TOKEN': token ? decodeURIComponent(token) : '',
            },
        });

        toast.success('Profile updated successfully.');

        // Reload profile data to get fresh data from server
        // Refresh CSRF token first, then reload
        await axios.get('/sanctum/csrf-cookie');
        await loadProfile();

        // Reload Inertia props to update header/navbar across all pages
        router.reload({ only: ['auth'] });

        // Close the account settings popup to show the updated data
        showAccountSettings.value = false;
    } catch (error) {
        // Handle validation errors
        if (error.response && error.response.status === 422) {
            const errors = error.response.data.errors || {};
            Object.keys(errors).forEach(key => {
                form.setError(key, errors[key][0]);
            });
            toast.error('Please check the form for errors.');
        } else {
            toast.error('Failed to update profile.');
        }
        console.error('Profile update error:', error.response || error);
    } finally {
        form.processing = false;
    }
};

const cancelProfileEdit = () => {
    // Check if there are unsaved changes
    if (hasFormChanges.value) {
        confirmDialogConfig.value = {
            title: 'ละทิ้งการเปลี่ยนแปลง?',
            message: 'คุณมีการแก้ไขที่ยังไม่ได้บันทึก คุณต้องการละทิ้งการเปลี่ยนแปลงหรือไม่?',
            confirmText: 'ละทิ้ง',
            cancelText: 'ยกเลิก',
            confirmButtonClass: 'bg-red-500 hover:bg-red-600',
            onConfirm: () => {
                // Reset form to original data
                form.name = originalFormData.value.name;
                form.phone = originalFormData.value.phone;
                form.date_of_birth = originalFormData.value.date_of_birth;
                form.gender = originalFormData.value.gender;
                form.organization = originalFormData.value.organization;
                form.department = originalFormData.value.department;
                form.bio = originalFormData.value.bio;

                // Clear errors and preview
                form.clearErrors();
                avatarPreview.value = null;
                showAccountSettings.value = false;
            },
        };
        showConfirmDialog.value = true;
        return;
    }

    // No changes, just close
    form.clearErrors();
    avatarPreview.value = null;
    showAccountSettings.value = false;
};

const deleteAvatar = () => {
    confirmDialogConfig.value = {
        title: 'ลบรูปโปรไฟล์?',
        message: 'คุณต้องการลบรูปโปรไฟล์และกลับไปใช้รูปเริ่มต้นหรือไม่?',
        confirmText: 'ลบรูป',
        cancelText: 'ยกเลิก',
        confirmButtonClass: 'bg-red-500 hover:bg-red-600',
        onConfirm: async () => {
            try {
                // Get CSRF cookie first
                await axios.get('/sanctum/csrf-cookie');

                // Extract XSRF token from cookie
                const token = document.cookie
                    .split('; ')
                    .find(row => row.startsWith('XSRF-TOKEN='))
                    ?.split('=')[1];

                // Call DELETE /api/me/avatar
                await axios.delete('/api/me/avatar', {
                    headers: {
                        'X-XSRF-TOKEN': token ? decodeURIComponent(token) : '',
                    },
                });

                // Update avatar version and clear preview
                avatarVersion.value = Date.now();
                avatarPreview.value = null;

                // Reload profile to update avatar_present flag
                await loadProfile();

                // Reload Inertia props to update header/navbar avatar
                router.reload({ only: ['auth'] });

                toast.success('ลบรูปโปรไฟล์สำเร็จ');
            } catch (error) {
                const message = error.response?.data?.message || 'Failed to delete avatar.';
                toast.error(message);
                console.error('Avatar delete error:', error.response || error);
            }
        },
    };
    showConfirmDialog.value = true;
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

const toggleAccountSettings = () => {
    showAccountSettings.value = !showAccountSettings.value;
};

const toggleAvatarMenu = () => {
    showAvatarMenu.value = !showAvatarMenu.value;
};

const triggerFileUpload = () => {
    showAvatarMenu.value = false;
    const fileInput = document.getElementById('avatar-upload-input');
    if (fileInput) {
        fileInput.click();
    }
};

const handleDeleteAvatar = () => {
    showAvatarMenu.value = false;
    deleteAvatar();
};

const onAvatarSelected = async (event) => {
    const file = event.target.files[0];
    if (!file) return;

    // Show preview
    const reader = new FileReader();
    reader.onload = (e) => {
        avatarPreview.value = e.target.result;
    };
    reader.readAsDataURL(file);

    isUploadingAvatar.value = true;

    try {
        // Get CSRF cookie first
        await axios.get('/sanctum/csrf-cookie');

        // Extract XSRF token from cookie
        const token = document.cookie
            .split('; ')
            .find(row => row.startsWith('XSRF-TOKEN='))
            ?.split('=')[1];

        // Create FormData and append file
        const formData = new FormData();
        formData.append('avatar', file);

        // Call POST /api/me/avatar
        await axios.post('/api/me/avatar', formData, {
            headers: {
                'X-XSRF-TOKEN': token ? decodeURIComponent(token) : '',
                'Content-Type': 'multipart/form-data',
            },
        });

        // Update avatar version to bust cache
        avatarVersion.value = Date.now();
        avatarPreview.value = null;
        event.target.value = '';

        // Reload profile to update avatar_present flag
        await loadProfile();

        // Reload Inertia props to update header/navbar avatar
        router.reload({ only: ['auth'] });

        toast.success('Avatar updated successfully');
    } catch (error) {
        const message = error.response?.data?.message || 'Failed to upload avatar.';
        toast.error(message);
        console.error('Avatar upload error:', error.response || error);
    } finally {
        isUploadingAvatar.value = false;
    }
};
</script>

<template>
    <Head title="Setting" />

    <component :is="LayoutComponent">
        <div class="px-4 pb-10 pt-6 sm:px-6 lg:px-10">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h1 class="text-3xl font-semibold text-gray-900">Setting</h1>
                    <p class="mt-1 text-sm text-gray-500">
                        Manage your account preferences and profile details.
                    </p>
                </div>
            </div>

            <div class="relative mt-8">
                <div class="rounded-3xl border border-gray-200 bg-white/90 p-6 shadow-sm">
                    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <h2 class="text-lg font-semibold text-gray-900">System Setting</h2>
                            <p class="text-xs text-gray-500">
                                General settings overview (display only).
                            </p>
                        </div>
                        <div class="flex flex-wrap items-center gap-3">
                            <button
                                type="button"
                                class="inline-flex items-center gap-2 rounded-full bg-[#2f837d]/10 px-4 py-2 text-sm font-semibold text-[#2f837d] shadow-sm hover:bg-[#2f837d]/20"
                                @click="toggleAccountSettings"
                            >
                                Account settings
                            </button>
                            <button
                                type="button"
                                class="inline-flex items-center gap-2 rounded-full bg-[#2f837d] px-4 py-2 text-sm font-semibold text-white shadow-sm opacity-60"
                                disabled
                            >
                                Save changes
                            </button>
                        </div>
                    </div>

                    <div class="mt-6 grid gap-4 lg:grid-cols-[2fr,1fr]">
                        <div class="rounded-2xl border border-gray-200 bg-white p-5">
                            <div class="flex items-center gap-2 text-sm font-semibold text-gray-700">
                                <Settings class="h-4 w-4 text-[#2f837d]" />
                                General
                            </div>
                            <div class="mt-4 space-y-3">
                                <div>
                                    <label class="text-xs font-medium text-gray-500">Name</label>
                                    <input
                                        type="text"
                                        value="Training Management System"
                                        class="mt-1 w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-2 text-sm"
                                        disabled
                                    />
                                </div>
                                <div>
                                    <label class="text-xs font-medium text-gray-500">Email</label>
                                    <input
                                        type="email"
                                        :value="apiUser?.email || props.user.email"
                                        class="mt-1 w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-2 text-sm"
                                        disabled
                                    />
                                </div>
                                <div>
                                    <label class="text-xs font-medium text-gray-500">Contact Phone</label>
                                    <input
                                        type="text"
                                        :value="form.phone || '02-123-456'"
                                        class="mt-1 w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-2 text-sm"
                                        disabled
                                    />
                                </div>
                            </div>
                        </div>

                        <div class="space-y-4">
                            <div class="rounded-2xl border border-gray-200 bg-white p-5">
                                <div class="flex items-center gap-2 text-sm font-semibold text-gray-700">
                                    <ShieldCheck class="h-4 w-4 text-[#2f837d]" />
                                    Policy
                                </div>
                                <div class="mt-4 space-y-3 text-sm text-gray-600">
                                    <div class="flex items-center justify-between">
                                        <span>Auto Approve</span>
                                        <input type="checkbox" class="h-5 w-9 accent-[#2f837d]" disabled />
                                    </div>
                                    <div class="flex items-center justify-between">
                                        <span>Allow Cancellation</span>
                                        <input type="checkbox" class="h-5 w-9 accent-[#2f837d]" checked disabled />
                                    </div>
                                    <div>
                                        <label class="text-xs text-gray-500">Cancel at least (days)</label>
                                        <input
                                            type="text"
                                            value="2"
                                            class="mt-1 w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-2 text-sm"
                                            disabled
                                        />
                                    </div>
                                </div>
                            </div>

                            <div class="rounded-2xl border border-gray-200 bg-white p-5">
                                <div class="flex items-center gap-2 text-sm font-semibold text-gray-700">
                                    <Bell class="h-4 w-4 text-[#2f837d]" />
                                    Notifications
                                </div>
                                <div class="mt-4 flex items-center justify-between text-sm text-gray-600">
                                    <span>Email Reminders</span>
                                    <input type="checkbox" class="h-5 w-9 accent-[#2f837d]" checked disabled />
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div
                    v-if="showAccountSettings"
                    class="absolute inset-x-0 top-16 flex justify-center px-4 sm:px-0"
                >
                    <div class="w-full max-w-3xl rounded-3xl border border-gray-200 bg-white p-6 shadow-2xl">
                        <div class="relative">
                            <h3 class="text-center text-2xl font-semibold text-gray-900">
                                Account Settings
                            </h3>
                            <button
                                type="button"
                                @click="cancelProfileEdit"
                                class="absolute right-0 top-0 flex h-8 w-8 items-center justify-center rounded-full text-gray-400 hover:bg-gray-100 hover:text-gray-600"
                            >
                                <X class="h-5 w-5" />
                            </button>
                        </div>
                        <div class="mt-6 flex items-center justify-center gap-10 border-b border-gray-200 text-sm font-semibold">
                            <button
                                type="button"
                                @click="activeTab = 'profile'"
                                :class="[
                                    'pb-3 transition',
                                    activeTab === 'profile'
                                        ? 'text-[#2f837d] border-b-2 border-[#2f837d]'
                                        : 'text-gray-400',
                                ]"
                            >
                                Profile
                            </button>
                            <button
                                type="button"
                                @click="activeTab = 'password'"
                                :class="[
                                    'pb-3 transition',
                                    activeTab === 'password'
                                        ? 'text-[#2f837d] border-b-2 border-[#2f837d]'
                                        : 'text-gray-400',
                                ]"
                            >
                                Password
                            </button>
                        </div>

                        <div v-if="activeTab === 'profile'" class="mt-6">
                            <div class="flex flex-col items-center">
                                <div class="relative">
                                    <div
                                        class="h-24 w-24 overflow-hidden rounded-full border border-gray-200 bg-gray-100 cursor-pointer group"
                                        @click="showImagePreview = true"
                                    >
                                        <img
                                            :src="avatarUrl"
                                            alt="Avatar"
                                            class="h-full w-full object-cover transition-transform group-hover:scale-110"
                                        />
                                        <div class="absolute inset-0 flex items-center justify-center rounded-full bg-black/0 transition-all group-hover:bg-black/30">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-white opacity-0 transition-opacity group-hover:opacity-100" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0zM10 7v3m0 0v3m0-3h3m-3 0H7" />
                                            </svg>
                                        </div>
                                    </div>
                                    <div v-if="isUploadingAvatar" class="absolute inset-0 flex items-center justify-center rounded-full bg-black/50">
                                        <LoadingSpinner
                                            variant="icon"
                                            size="md"
                                            color="white"
                                        />
                                    </div>

                                    <!-- Hidden file input -->
                                    <input
                                        id="avatar-upload-input"
                                        type="file"
                                        accept="image/*"
                                        class="hidden"
                                        :disabled="isUploadingAvatar"
                                        @change="onAvatarSelected"
                                    />

                                    <!-- Edit button with dropdown menu -->
                                    <div class="absolute -bottom-1 -right-1 avatar-menu-container">
                                        <button
                                            type="button"
                                            @click="toggleAvatarMenu"
                                            class="flex h-8 w-8 items-center justify-center rounded-full bg-[#2f837d] text-white shadow hover:bg-[#266a66]"
                                            :class="{ 'opacity-70': isUploadingAvatar }"
                                            :disabled="isUploadingAvatar"
                                        >
                                            <Edit class="h-4 w-4" />
                                        </button>

                                        <!-- Dropdown menu -->
                                        <div
                                            v-if="showAvatarMenu"
                                            class="absolute left-full top-0 ml-2 z-50 w-48 rounded-xl border border-gray-200 bg-white shadow-lg"
                                        >
                                            <button
                                                type="button"
                                                @click="triggerFileUpload"
                                                class="flex w-full items-center gap-2 px-4 py-3 text-left text-sm text-gray-700 hover:bg-gray-50 rounded-t-xl"
                                            >
                                                <Upload class="h-4 w-4 text-[#2f837d]" />
                                                <span>Upload Avatar</span>
                                            </button>
                                            <button
                                                v-if="apiUser?.avatar_present"
                                                type="button"
                                                @click="handleDeleteAvatar"
                                                class="flex w-full items-center gap-2 border-t border-gray-100 px-4 py-3 text-left text-sm text-red-600 hover:bg-red-50 rounded-b-xl"
                                            >
                                                <Trash2 class="h-4 w-4" />
                                                <span>Delete Avatar</span>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <form class="mt-6 space-y-4" @submit.prevent="submitProfileForm">
                                <div>
                                    <label class="text-sm font-medium text-gray-700">Name</label>
                                    <input
                                        v-model="form.name"
                                        type="text"
                                        class="mt-2 w-full rounded-xl border border-gray-200 px-4 py-2 text-sm focus:border-[#2f837d] focus:ring-[#2f837d]"
                                    />
                                    <p v-if="form.errors.name" class="mt-1 text-xs text-red-600">
                                        {{ form.errors.name }}
                                    </p>
                                </div>
                                <div>
                                    <label class="text-sm font-medium text-gray-700">Contact Phone</label>
                                    <input
                                        v-model="form.phone"
                                        type="tel"
                                        inputmode="numeric"
                                        pattern="[0-9]*"
                                        placeholder="0812345678"
                                        @input="form.phone = form.phone.replace(/[^0-9]/g, '')"
                                        class="mt-2 w-full rounded-xl border border-gray-200 px-4 py-2 text-sm focus:border-[#2f837d] focus:ring-[#2f837d]"
                                    />
                                    <p v-if="form.errors.phone" class="mt-1 text-xs text-red-600">
                                        {{ form.errors.phone }}
                                    </p>
                                </div>
                                <div>
                                    <label class="text-sm font-medium text-gray-700">Email</label>
                                    <input
                                        :value="apiUser?.email || props.user.email"
                                        type="email"
                                        class="mt-2 w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-2 text-sm"
                                        disabled
                                    />
                                </div>

                                <div class="mt-6 flex flex-col gap-3 sm:flex-row">
                                <button
                                    type="button"
                                    @click="cancelProfileEdit"
                                    class="w-full rounded-xl border border-gray-200 px-4 py-2 text-sm font-semibold text-gray-600 hover:bg-gray-50"
                                >
                                    Cancel
                                </button>
                                    <button
                                        type="submit"
                                        :disabled="form.processing"
                                        class="w-full rounded-xl bg-[#2f837d] px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-[#266a66] disabled:opacity-60"
                                    >
                                        Save
                                    </button>
                                </div>
                            </form>
                        </div>

                        <div v-else class="mt-6">
                            <div class="text-sm font-semibold text-gray-700">Change Password</div>
                            <form class="mt-4 space-y-4" @submit.prevent="submitPasswordForm">
                                <div>
                                    <input
                                        v-model="passwordForm.current_password"
                                        type="password"
                                        placeholder="Current password"
                                        class="w-full rounded-xl border border-gray-200 px-4 py-2 text-sm focus:border-[#2f837d] focus:ring-[#2f837d]"
                                    />
                                    <p v-if="passwordForm.errors.current_password" class="mt-1 text-xs text-red-600">
                                        {{ passwordForm.errors.current_password }}
                                    </p>
                                </div>
                                <div>
                                    <input
                                        v-model="passwordForm.password"
                                        type="password"
                                        placeholder="New password"
                                        class="w-full rounded-xl border border-gray-200 px-4 py-2 text-sm focus:border-[#2f837d] focus:ring-[#2f837d]"
                                    />
                                    <p v-if="passwordForm.errors.password" class="mt-1 text-xs text-red-600">
                                        {{ passwordForm.errors.password }}
                                    </p>
                                </div>
                                <div>
                                    <input
                                        v-model="passwordForm.password_confirmation"
                                        type="password"
                                        placeholder="Confirm new password"
                                        class="w-full rounded-xl border border-gray-200 px-4 py-2 text-sm focus:border-[#2f837d] focus:ring-[#2f837d]"
                                    />
                                    <p v-if="passwordForm.errors.password_confirmation" class="mt-1 text-xs text-red-600">
                                        {{ passwordForm.errors.password_confirmation }}
                                    </p>
                                </div>

                                <button
                                    type="submit"
                                    :disabled="passwordForm.processing"
                                    class="w-full rounded-xl bg-[#2f837d] px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-[#266a66] disabled:opacity-60"
                                >
                                    Update password
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <LoadingSpinner
            :show="isLoadingProfile"
            overlay
            size="xl"
            text="Loading profile..."
        />

        <ConfirmationDialog
            :show="showConfirmDialog"
            :title="confirmDialogConfig.title"
            :message="confirmDialogConfig.message"
            :confirm-text="confirmDialogConfig.confirmText"
            :cancel-text="confirmDialogConfig.cancelText"
            :confirm-button-class="confirmDialogConfig.confirmButtonClass"
            @confirm="confirmDialogConfig.onConfirm"
            @cancel="() => {}"
            @close="showConfirmDialog = false"
        />

        <ImagePreviewModal
            :show="showImagePreview"
            :image-url="avatarUrl"
            alt-text="Profile Avatar"
            @close="showImagePreview = false"
        />
    </component>
</template>
