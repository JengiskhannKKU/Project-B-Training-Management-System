<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import DeleteUserForm from './Partials/DeleteUserForm.vue';
import UpdatePasswordForm from './Partials/UpdatePasswordForm.vue';
import { Head, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import { trans } from 'laravel-vue-i18n';

defineProps({
    mustVerifyEmail: {
        type: Boolean,
    },
    status: {
        type: String,
    },
});

const user = usePage().props.auth.user;
const profile = user.profile || {};

// Determine User Role Display
const roleLabel = computed(() => {
    if (user.role) {
        return user.role.label || user.role.name;
    }
    return 'User';
});

const roleColor = computed(() => {
    const role = (user.role?.name || '').toLowerCase();
    if (role === 'admin') return 'text-red-700 bg-red-50 border-red-200';
    if (role === 'trainer') return 'text-blue-700 bg-blue-50 border-blue-200';
    if (role === 'trainee') return 'text-green-700 bg-green-50 border-green-200';
    return 'text-gray-700 bg-gray-50 border-gray-200';
});

// Helper to check if a field has a value
const has = (value) => value && value !== '';

// Format Date
const formatDate = (dateString) => {
    if (!dateString) return '-';
    return new Date(dateString).toLocaleDateString('th-TH', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
    });
};

</script>

<template>
    <Head :title="$t('Profile')" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-2xl font-bold leading-tight text-[#2c3e50]">
                {{ $t('Character Profile') }}
            </h2>
        </template>

        <div class="py-12 bg-gray-100 min-h-screen">
            <div class="mx-auto max-w-7xl space-y-8 sm:px-6 lg:px-8">
                
                <!-- Character Card -->
                <div class="bg-white rounded-2xl shadow-xl overflow-hidden border border-gray-200">
                    <!-- Banner/Header -->
                    <div class="h-32 bg-gradient-to-r from-[#3D9792] to-[#2c7a7b] relative">
                         <div class="absolute -bottom-16 left-8 sm:left-12">
                            <div class="w-32 h-32 rounded-full border-4 border-white bg-gray-200 flex items-center justify-center overflow-hidden shadow-lg">
                                 <span v-if="!profile.avatar_image" class="text-4xl text-gray-400 font-bold">
                                    {{ user.name.charAt(0).toUpperCase() }}
                                 </span>
                                 <!-- Assuming avatar handling might be added later, simplified for now -->
                                 <img v-else :src="profile.avatar_image" class="w-full h-full object-cover" />
                            </div>
                        </div>
                    </div>

                    <div class="pt-20 pb-8 px-8 sm:px-12">
                        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center">
                            <div>
                                <h3 class="text-3xl font-extrabold text-gray-900">
                                    {{ profile.prefix ? $t(profile.prefix) + ' ' : '' }}{{ user.name }}
                                </h3>
                                <div class="flex flex-wrap gap-2 items-center mt-1">
                                    <span
                                        :class="roleColor"
                                        class="px-3 py-1 rounded-full text-sm font-semibold border inline-flex items-center"
                                    >
                                        <span class="inline-block w-2.5 h-2.5 rounded-full mr-2"
                                            :class="{
                                                'bg-red-600': user.role?.name?.toLowerCase() === 'admin',
                                                'bg-blue-600': user.role?.name?.toLowerCase() === 'trainer',
                                                'bg-green-600': user.role?.name?.toLowerCase() === 'trainee'
                                            }"
                                        ></span>
                                        {{ roleLabel }}
                                    </span>
                                    <span
                                        :class="user.type?.toLowerCase() === 'external'
                                            ? 'text-orange-800 bg-orange-100 border-orange-200'
                                            : 'text-indigo-800 bg-indigo-100 border-indigo-200'"
                                        class="px-3 py-1 rounded-full text-xs font-semibold border inline-block"
                                    >
                                        {{ user.type?.toUpperCase() || 'INTERNAL' }}
                                    </span>
                                    <span v-if="profile.sub_category" class="text-gray-500 text-sm">
                                        ({{ $t(profile.sub_category) }})
                                    </span>
                                    <span v-if="profile.category" class="text-gray-500 text-sm">
                                        ({{ $t(profile.category) }})
                                    </span>
                                </div>
                            </div>
                             <div class="mt-4 sm:mt-0">
                                <!-- Could add an 'Edit Profile' button here later -->
                                <span class="px-4 py-1 bg-gray-100 text-gray-600 rounded-full text-xs uppercase font-bold tracking-wide">
                                    {{ $t('Status') }}: {{ $t('Active') }}
                                </span>
                            </div>
                        </div>

                        <!-- Stats / Attributes Grid -->
                        <div class="mt-8 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                            
                            <!-- Identity / Contact Stats -->
                            <div class="bg-gray-50 p-5 rounded-xl border border-gray-100 relative overflow-hidden group hover:border-[#3D9792] transition-colors">
                                <div class="absolute top-0 right-0 p-3 opacity-10 group-hover:opacity-20 transition-opacity">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-16 w-16 text-[#3D9792]" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V8a2 2 0 00-2-2h-5m-4 0V5a2 2 0 114 0v1m-4 0a2 2 0 104 0m-5 8a2 2 0 100-4 2 2 0 000 4zm0 0c1.306 0 2.417.835 2.83 2M9 14a3.001 3.001 0 00-2.83 2M15 11h3m-3 4h2" /></svg>
                                </div>
                                <h4 class="text-sm font-bold text-gray-400 uppercase tracking-wider mb-4">{{ $t('Identity') }}</h4>
                                <ul class="space-y-3">
                                    <li class="flex justify-between">
                                        <span class="text-gray-600">{{ $t('Email') }}</span>
                                        <span class="font-medium text-gray-900">{{ user.email }}</span>
                                    </li>
                                    <li class="flex justify-between">
                                        <span class="text-gray-600">{{ $t('Phone') }}</span>
                                        <span class="font-medium text-gray-900">{{ profile.phone || '-' }}</span>
                                    </li>
                                    <li class="flex justify-between">
                                        <span class="text-gray-600">{{ $t('Date of Birth') }}</span>
                                        <span class="font-medium text-gray-900">{{ formatDate(profile.date_of_birth) }}</span>
                                    </li>
                                    <li class="flex justify-between">
                                        <span class="text-gray-600">{{ $t('Gender') }}</span>
                                        <span class="font-medium text-gray-900">{{ profile.gender ? $t(profile.gender) : '-' }}</span>
                                    </li>
                                </ul>
                            </div>

                            <!-- Class / Profession Stats (Dynamic based on Role/Category) -->
                            <div class="bg-gray-50 p-5 rounded-xl border border-gray-100 relative overflow-hidden group hover:border-blue-400 transition-colors">
                                <div class="absolute top-0 right-0 p-3 opacity-10 group-hover:opacity-20 transition-opacity">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-16 w-16 text-blue-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" /></svg>
                                </div>
                                <h4 class="text-sm font-bold text-gray-400 uppercase tracking-wider mb-4">{{ $t('Class & Affiliation') }}</h4>
                                
                                <ul class="space-y-3">
                                    <!-- Student Specific -->
                                    <template v-if="profile.sub_category === 'Student'">
                                        <li v-if="has(profile.faculty)" class="flex justify-between">
                                            <span class="text-gray-600">{{ $t('Faculty') }}</span>
                                            <span class="font-medium text-gray-900 text-right">{{ profile.faculty }}</span>
                                        </li>
                                        <li v-if="has(profile.major)" class="flex justify-between">
                                            <span class="text-gray-600">{{ $t('Major') }}</span>
                                            <span class="font-medium text-gray-900 text-right">{{ profile.major }}</span>
                                        </li>
                                        <li v-if="has(profile.student_id)" class="flex justify-between">
                                            <span class="text-gray-600">{{ $t('Student ID') }}</span>
                                            <span class="font-medium text-gray-900">{{ profile.student_id }}</span>
                                        </li>
                                        <li v-if="has(profile.degree_level)" class="flex justify-between">
                                            <span class="text-gray-600">{{ $t('Degree Level') }}</span>
                                            <span class="font-medium text-gray-900">{{ $t(profile.degree_level) }}</span>
                                        </li>
                                         <li v-if="has(profile.year_of_study)" class="flex justify-between">
                                            <span class="text-gray-600">{{ $t('Year') }}</span>
                                            <span class="font-medium text-gray-900">{{ profile.year_of_study }}</span>
                                        </li>
                                    </template>

                                    <!-- Personnel Specific -->
                                    <template v-else-if="profile.sub_category === 'Personnel'">
                                        <li v-if="has(profile.organization)" class="flex justify-between">
                                            <span class="text-gray-600">{{ $t('Organization') }}</span>
                                            <span class="font-medium text-gray-900 text-right">{{ profile.organization }}</span>
                                        </li>
                                        <li v-if="has(profile.department)" class="flex justify-between">
                                            <span class="text-gray-600">{{ $t('Department') }}</span>
                                            <span class="font-medium text-gray-900 text-right">{{ profile.department }}</span>
                                        </li>
                                        <li v-if="has(profile.job_position)" class="flex justify-between">
                                            <span class="text-gray-600">{{ $t('Position') }}</span>
                                            <span class="font-medium text-gray-900 text-right">{{ $t(profile.job_position) }}</span>
                                        </li>
                                        <li v-if="has(profile.personnel_type)" class="flex justify-between">
                                            <span class="text-gray-600">{{ $t('Type') }}</span>
                                            <span class="font-medium text-gray-900">{{ $t(profile.personnel_type) }}</span>
                                        </li>
                                    </template>

                                    <!-- External Specific -->
                                    <template v-else-if="has(profile.category)">
                                         <li class="flex justify-between">
                                            <span class="text-gray-600">{{ $t('Category') }}</span>
                                            <span class="font-medium text-gray-900">{{ $t(profile.category) }}</span>
                                        </li>
                                        <li v-if="has(profile.organization)" class="flex justify-between">
                                            <span class="text-gray-600">{{ $t('Affiliation') }}</span>
                                            <span class="font-medium text-gray-900 text-right">{{ profile.organization }}</span>
                                        </li>
                                    </template>
                                    
                                    <!-- Fallback / Admin -->
                                    <template v-else>
                                         <li class="flex justify-between">
                                            <span class="text-gray-600">{{ $t('Organization') }}</span>
                                            <span class="font-medium text-gray-900">{{ profile.organization || 'KKU' }}</span>
                                        </li>
                                    </template>
                                </ul>
                            </div>

                            <!-- XP / System Stats (Mockup for Gaming Feel) -->
                            <div class="bg-gray-50 p-5 rounded-xl border border-gray-100 relative overflow-hidden group hover:border-yellow-400 transition-colors">
                                <div class="absolute top-0 right-0 p-3 opacity-10 group-hover:opacity-20 transition-opacity">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-16 w-16 text-yellow-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" /></svg>
                                </div>
                                <h4 class="text-sm font-bold text-gray-400 uppercase tracking-wider mb-4">{{ $t('System Stats') }}</h4>
                                <div class="space-y-4">
                                    <div>
                                        <div class="flex justify-between text-sm mb-1">
                                            <span class="text-gray-700 font-medium">{{ $t('Profile Completion') }}</span>
                                            <span class="text-[#3D9792] font-bold">100%</span>
                                        </div>
                                        <div class="w-full bg-gray-200 rounded-full h-2.5">
                                            <div class="bg-[#3D9792] h-2.5 rounded-full" style="width: 100%"></div>
                                        </div>
                                    </div>
                                    
                                    <div class="pt-2">
                                        <p class="text-xs text-gray-500 uppercase tracking-wide">{{ $t('Member Since') }}</p>
                                        <p class="text-lg font-mono font-medium text-gray-800">{{ formatDate(user.created_at) }}</p>
                                    </div>
                                </div>
                            </div>

                        </div>
                    </div>
                </div>

                <!-- Settings / Actions Area -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="bg-white p-6 shadow-lg rounded-2xl border border-gray-100">
                         <h3 class="text-lg font-bold text-gray-900 mb-4 flex items-center">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" /></svg>
                            {{ $t('Security') }}
                        </h3>
                        <UpdatePasswordForm />
                    </div>

                    <div class="bg-white p-6 shadow-lg rounded-2xl border border-gray-100">
                         <h3 class="text-lg font-bold text-red-600 mb-4 flex items-center">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2 text-red-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                            {{ $t('Danger Zone') }}
                        </h3>
                        <DeleteUserForm />
                    </div>
                </div>

            </div>
        </div>
    </AuthenticatedLayout>
</template>
