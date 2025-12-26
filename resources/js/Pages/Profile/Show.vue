<script setup>
import { ref, computed } from 'vue';
import { Head, useForm, usePage } from '@inertiajs/vue3';
import { useToast } from 'vue-toastification';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import TrainerLayout from '@/Layouts/TrainerLayout.vue';
import { Bell, ShieldCheck, Settings, Camera } from 'lucide-vue-next';

const toast = useToast();
const page = usePage();

const props = defineProps({
    user: {
        type: Object,
        required: true,
    },
});

const roleName = computed(() =>
    props.user.role || page.props.auth?.user?.role?.name || page.props.auth?.user?.role || 'user'
);

const LayoutComponent = computed(() =>
    roleName.value === 'admin' ? AdminLayout : TrainerLayout
);

const activeTab = ref('profile');
const showAccountSettings = ref(false);
const avatarPreview = ref(null);
const avatarVersion = ref(Date.now());
const isUploadingAvatar = ref(false);

const form = useForm({
    name: props.user.name || '',
    phone: props.user.profile?.phone || '',
    date_of_birth: props.user.profile?.date_of_birth || '',
    gender: props.user.profile?.gender || '',
    organization: props.user.profile?.organization || '',
    department: props.user.profile?.department || '',
    bio: props.user.profile?.bio || '',
});

const passwordForm = useForm({
    current_password: '',
    password: '',
    password_confirmation: '',
});

const userInitials = computed(() => {
    const name = props.user.name || '';
    return name
        .split(' ')
        .filter(Boolean)
        .map((word) => word[0])
        .join('')
        .toUpperCase()
        .slice(0, 2) || 'U';
});

const avatarUrl = computed(() => {
    if (avatarPreview.value) return avatarPreview.value;
    if (props.user.profile?.has_avatar) {
        return `/api/me/avatar?t=${avatarVersion.value}`;
    }
    return null;
});

const submitProfileForm = () => {
    form.put(route('me.profile.update'), {
        preserveScroll: true,
        onSuccess: () => {
            toast.success('Profile updated successfully.');
        },
    });
};

const cancelProfileEdit = () => {
    form.reset();
    avatarPreview.value = null;
    showAccountSettings.value = false;
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

const onAvatarSelected = async (event) => {
    const file = event.target.files[0];
    if (!file) return;

    const reader = new FileReader();
    reader.onload = (e) => {
        avatarPreview.value = e.target.result;
    };
    reader.readAsDataURL(file);

    isUploadingAvatar.value = true;

    const formData = new FormData();
    formData.append('avatar', file);

    try {
        const response = await fetch('/api/me/avatar', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN':
                    document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                Accept: 'application/json',
            },
            body: formData,
            credentials: 'same-origin',
        });

        if (!response.ok) {
            const data = await response.json().catch(() => ({}));
            const message = data?.message || 'Failed to upload avatar.';
            throw new Error(message);
        }

        avatarVersion.value = Date.now();
        avatarPreview.value = null;
        event.target.value = '';
        toast.success('Avatar updated successfully');
    } catch (error) {
        toast.error(error?.message || 'Failed to upload avatar.');
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
                                        :value="props.user.email"
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
                        <h3 class="text-center text-2xl font-semibold text-gray-900">
                            Account Settings
                        </h3>
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
                                    <div class="h-24 w-24 overflow-hidden rounded-full border border-gray-200 bg-gray-100">
                                        <img
                                            v-if="avatarUrl"
                                            :src="avatarUrl"
                                            alt="Avatar"
                                            class="h-full w-full object-cover"
                                        />
                                        <div
                                            v-else
                                            class="flex h-full w-full items-center justify-center text-lg font-semibold text-gray-600"
                                        >
                                            {{ userInitials }}
                                        </div>
                                    </div>
                                    <label
                                        class="absolute -bottom-1 -right-1 flex h-8 w-8 cursor-pointer items-center justify-center rounded-full bg-[#2f837d] text-white shadow"
                                        :class="{ 'opacity-70': isUploadingAvatar }"
                                    >
                                        <Camera class="h-4 w-4" />
                                        <input
                                            type="file"
                                            accept="image/*"
                                            class="hidden"
                                            :disabled="isUploadingAvatar"
                                            @change="onAvatarSelected"
                                        />
                                    </label>
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
                                        type="text"
                                        class="mt-2 w-full rounded-xl border border-gray-200 px-4 py-2 text-sm focus:border-[#2f837d] focus:ring-[#2f837d]"
                                    />
                                    <p v-if="form.errors.phone" class="mt-1 text-xs text-red-600">
                                        {{ form.errors.phone }}
                                    </p>
                                </div>
                                <div>
                                    <label class="text-sm font-medium text-gray-700">Email</label>
                                    <input
                                        :value="props.user.email"
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
    </component>
</template>
