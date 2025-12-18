<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import TextInput from '@/Components/TextInput.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';

const props = defineProps({
    user: {
        type: Object,
        required: true,
    },
});

const isEditing = ref(false);

const form = useForm({
    name: props.user.name || '',
    phone: props.user.profile?.phone || '',
    date_of_birth: props.user.profile?.date_of_birth || '',
    gender: props.user.profile?.gender || '',
    organization: props.user.profile?.organization || '',
    department: props.user.profile?.department || '',
    bio: props.user.profile?.bio || '',
});

const toggleEdit = () => {
    isEditing.value = !isEditing.value;
    if (!isEditing.value) {
        // Reset form when canceling
        form.reset();
    }
};

const submitForm = () => {
    form.put(route('me.profile.update'), {
        preserveScroll: true,
        onSuccess: () => {
            isEditing.value = false;
        },
    });
};

const isUploadingAvatar = ref(false);
const avatarPreview = ref(null);

const onAvatarSelected = async (event) => {
    const file = event.target.files[0];
    if (!file) return;

    // Show preview
    const reader = new FileReader();
    reader.onload = (e) => {
        avatarPreview.value = e.target.result;
    };
    reader.readAsDataURL(file);

    // Upload avatar
    isUploadingAvatar.value = true;

    const formData = new FormData();
    formData.append('avatar', file);

    try {
        await fetch('/api/me/avatar', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                'Accept': 'application/json',
            },
            body: formData,
            credentials: 'same-origin',
        });

        // Force reload avatar by adding timestamp
        avatarPreview.value = `/api/me/avatar?t=${Date.now()}`;

        // Reset file input
        event.target.value = '';
    } catch (error) {
        console.error('Avatar upload failed:', error);
        alert('Failed to upload avatar. Please try again.');
        avatarPreview.value = null;
    } finally {
        isUploadingAvatar.value = false;
    }
};
</script>

<template>
    <Head title="My Profile" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-semibold leading-tight text-gray-800">
                    My Profile
                </h2>
                <button
                    @click="toggleEdit"
                    class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition"
                >
                    {{ isEditing ? 'Cancel' : 'Edit Profile' }}
                </button>
            </div>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
                <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                    <div class="p-6 text-gray-900">
                        <!-- Avatar Section -->
                        <div class="mb-6 flex items-center space-x-6">
                            <div class="flex-shrink-0 relative">
                                <img
                                    v-if="avatarPreview || user.profile?.has_avatar"
                                    :src="avatarPreview || `/api/me/avatar?t=${Date.now()}`"
                                    alt="My avatar"
                                    class="h-24 w-24 rounded-full object-cover border-2 border-gray-200"
                                />
                                <div
                                    v-else
                                    class="h-24 w-24 rounded-full bg-gray-200 flex items-center justify-center border-2 border-gray-300"
                                >
                                    <span class="text-2xl font-semibold text-gray-600">
                                        {{ user.name.charAt(0).toUpperCase() }}
                                    </span>
                                </div>

                                <!-- Upload Button Overlay -->
                                <label
                                    class="absolute inset-0 flex items-center justify-center bg-black bg-opacity-50 rounded-full opacity-0 hover:opacity-100 transition-opacity cursor-pointer"
                                    :class="{ 'opacity-100': isUploadingAvatar }"
                                >
                                    <span class="text-white text-sm font-medium">
                                        {{ isUploadingAvatar ? 'Uploading...' : 'Change' }}
                                    </span>
                                    <input
                                        type="file"
                                        @change="onAvatarSelected"
                                        accept="image/*"
                                        class="hidden"
                                        :disabled="isUploadingAvatar"
                                    />
                                </label>
                            </div>
                            <div>
                                <h3 class="text-2xl font-bold text-gray-900">
                                    {{ user.name }}
                                </h3>
                                <p class="text-sm text-gray-500 capitalize">
                                    {{ user.role || 'User' }}
                                </p>
                                <p class="text-xs text-gray-400 mt-1">
                                    Click avatar to change photo
                                </p>
                            </div>
                        </div>

                        <!-- Edit Form -->
                        <form v-if="isEditing" @submit.prevent="submitForm" class="space-y-6">
                            <div class="border-t border-gray-200 pt-4">
                                <h4 class="text-lg font-semibold text-gray-800 mb-4">
                                    Edit Profile Information
                                </h4>

                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <!-- Name -->
                                    <div>
                                        <InputLabel for="name" value="Name *" />
                                        <TextInput
                                            id="name"
                                            v-model="form.name"
                                            type="text"
                                            class="mt-1 block w-full"
                                            required
                                        />
                                        <div v-if="form.errors.name" class="text-sm text-red-600 mt-1">
                                            {{ form.errors.name }}
                                        </div>
                                    </div>

                                    <!-- Phone -->
                                    <div>
                                        <InputLabel for="phone" value="Phone" />
                                        <TextInput
                                            id="phone"
                                            v-model="form.phone"
                                            type="text"
                                            class="mt-1 block w-full"
                                        />
                                        <div v-if="form.errors.phone" class="text-sm text-red-600 mt-1">
                                            {{ form.errors.phone }}
                                        </div>
                                    </div>

                                    <!-- Date of Birth -->
                                    <div>
                                        <InputLabel for="date_of_birth" value="Date of Birth" />
                                        <TextInput
                                            id="date_of_birth"
                                            v-model="form.date_of_birth"
                                            type="date"
                                            class="mt-1 block w-full"
                                        />
                                        <div v-if="form.errors.date_of_birth" class="text-sm text-red-600 mt-1">
                                            {{ form.errors.date_of_birth }}
                                        </div>
                                    </div>

                                    <!-- Gender -->
                                    <div>
                                        <InputLabel for="gender" value="Gender" />
                                        <select
                                            id="gender"
                                            v-model="form.gender"
                                            class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"
                                        >
                                            <option value="">Select Gender</option>
                                            <option value="male">Male</option>
                                            <option value="female">Female</option>
                                            <option value="other">Other</option>
                                        </select>
                                        <div v-if="form.errors.gender" class="text-sm text-red-600 mt-1">
                                            {{ form.errors.gender }}
                                        </div>
                                    </div>

                                    <!-- Organization -->
                                    <div>
                                        <InputLabel for="organization" value="Organization" />
                                        <TextInput
                                            id="organization"
                                            v-model="form.organization"
                                            type="text"
                                            class="mt-1 block w-full"
                                        />
                                        <div v-if="form.errors.organization" class="text-sm text-red-600 mt-1">
                                            {{ form.errors.organization }}
                                        </div>
                                    </div>

                                    <!-- Department -->
                                    <div>
                                        <InputLabel for="department" value="Department" />
                                        <TextInput
                                            id="department"
                                            v-model="form.department"
                                            type="text"
                                            class="mt-1 block w-full"
                                        />
                                        <div v-if="form.errors.department" class="text-sm text-red-600 mt-1">
                                            {{ form.errors.department }}
                                        </div>
                                    </div>
                                </div>

                                <!-- Bio -->
                                <div class="mt-4">
                                    <InputLabel for="bio" value="Bio" />
                                    <textarea
                                        id="bio"
                                        v-model="form.bio"
                                        rows="4"
                                        class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"
                                    ></textarea>
                                    <div v-if="form.errors.bio" class="text-sm text-red-600 mt-1">
                                        {{ form.errors.bio }}
                                    </div>
                                </div>

                                <!-- Submit Button -->
                                <div class="mt-6 flex items-center gap-4">
                                    <PrimaryButton :disabled="form.processing">
                                        {{ form.processing ? 'Saving...' : 'Save Changes' }}
                                    </PrimaryButton>
                                    <button
                                        type="button"
                                        @click="toggleEdit"
                                        class="px-4 py-2 bg-gray-300 text-gray-700 rounded-lg hover:bg-gray-400 transition"
                                    >
                                        Cancel
                                    </button>
                                </div>
                            </div>
                        </form>

                        <!-- View Mode -->
                        <div v-else class="space-y-4">
                            <div class="border-t border-gray-200 pt-4">
                                <h4 class="text-lg font-semibold text-gray-800 mb-4">
                                    Contact Information
                                </h4>

                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-600">
                                            Email
                                        </label>
                                        <p class="mt-1 text-gray-900">
                                            {{ user.email }}
                                        </p>
                                    </div>

                                    <div>
                                        <label class="block text-sm font-medium text-gray-600">
                                            Phone
                                        </label>
                                        <p class="mt-1 text-gray-900">
                                            {{ user.profile?.phone || '-' }}
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <div class="border-t border-gray-200 pt-4">
                                <h4 class="text-lg font-semibold text-gray-800 mb-4">
                                    Personal Information
                                </h4>

                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-600">
                                            Date of Birth
                                        </label>
                                        <p class="mt-1 text-gray-900">
                                            {{ user.profile?.date_of_birth || '-' }}
                                        </p>
                                    </div>

                                    <div>
                                        <label class="block text-sm font-medium text-gray-600">
                                            Gender
                                        </label>
                                        <p class="mt-1 text-gray-900 capitalize">
                                            {{ user.profile?.gender || '-' }}
                                        </p>
                                    </div>

                                    <div>
                                        <label class="block text-sm font-medium text-gray-600">
                                            Organization
                                        </label>
                                        <p class="mt-1 text-gray-900">
                                            {{ user.profile?.organization || '-' }}
                                        </p>
                                    </div>

                                    <div>
                                        <label class="block text-sm font-medium text-gray-600">
                                            Department
                                        </label>
                                        <p class="mt-1 text-gray-900">
                                            {{ user.profile?.department || '-' }}
                                        </p>
                                    </div>
                                </div>

                                <div class="mt-4">
                                    <label class="block text-sm font-medium text-gray-600">
                                        Bio
                                    </label>
                                    <p class="mt-1 text-gray-900 whitespace-pre-wrap">
                                        {{ user.profile?.bio || '-' }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
