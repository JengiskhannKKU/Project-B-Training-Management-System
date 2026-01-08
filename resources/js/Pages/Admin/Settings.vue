<script setup>
import { ref } from 'vue';
import { Head } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import {
    Settings as SettingsIcon,
    Bell,
    BookOpen,
    Save,
    CheckCircle2,
    Shield,
    Globe,
    Mail
} from 'lucide-vue-next';

const activeTab = ref('general');
const isSaving = ref(false);
const showToast = ref(false);

const settings = ref({
    general: {
        siteName: 'Training Management System',
        siteDescription: 'A comprehensive platform for managing training programs',
        contactEmail: 'admin@training.com',
        timezone: 'UTC',
        language: 'en',
    },
    notifications: {
        emailNotifications: true,
        courseUpdates: true,
        attendanceAlerts: true,
        feedbackAlerts: false,
        marketingEmails: false,
    },
    courses: {
        autoApproval: false,
        maxStudentsPerCourse: 50,
        allowSelfEnrollment: true,
        requirePrerequisites: true,
    },
    security: {
        twoFactorAuth: false,
        sessionTimeout: 60,
        passwordExpiry: 90,
    }
});

const tabs = [
    { id: 'general', label: 'General', icon: Globe, description: 'Basic site configuration' },
    { id: 'notifications', label: 'Notifications', icon: Bell, description: 'Email and alerts' },
    { id: 'courses', label: 'Courses', icon: BookOpen, description: 'Training rules' },
    { id: 'security', label: 'Security', icon: Shield, description: 'Access control' },
];

const saveSettings = () => {
    isSaving.value = true;
    // Simulate API call
    setTimeout(() => {
        isSaving.value = false;
        showToast.value = true;
        setTimeout(() => {
            showToast.value = false;
        }, 3000);
    }, 800);
};
</script>

<template>
    <Head title="Settings" />
    <AdminLayout>
        <div class="space-y-6">
            <!-- Header -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900 tracking-tight">{{ $t('Settings') }}</h1>
                    <p class="mt-1 text-sm text-gray-500">{{ $t('Manage application preferences and configurations.') }}</p>
                </div>
                <div>
                    <button
                        @click="saveSettings"
                        :disabled="isSaving"
                        class="inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-lg shadow-sm transition-all focus:ring-4 focus:ring-blue-500/20 disabled:opacity-75 disabled:cursor-not-allowed"
                    >
                        <component
                            :is="isSaving ? 'div' : Save"
                            :class="[
                                isSaving ? 'animate-spin rounded-full h-4 w-4 border-2 border-white border-t-transparent' : 'w-4 h-4 mr-2'
                            ]"
                        />
                        {{ isSaving ? $t('Saving...') : $t('Save Changes') }}
                    </button>
                </div>
            </div>

            <div class="flex flex-col lg:flex-row gap-8">
                <!-- Sidebar Navigation -->
                <div class="lg:w-64 flex-shrink-0">
                    <nav class="space-y-1">
                        <button
                            v-for="tab in tabs"
                            :key="tab.id"
                            @click="activeTab = tab.id"
                            :class="[
                                activeTab === tab.id
                                    ? 'bg-blue-50 text-blue-700 shadow-sm ring-1 ring-blue-200'
                                    : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900',
                                'group flex items-center w-full px-3 py-2.5 text-sm font-medium rounded-xl transition-all duration-200'
                            ]"
                        >
                            <component
                                :is="tab.icon"
                                :class="[
                                    activeTab === tab.id ? 'text-blue-600' : 'text-gray-400 group-hover:text-gray-500',
                                    'flex-shrink-0 -ml-1 mr-3 h-5 w-5 transition-colors duration-200'
                                ]"
                            />
                            <div class="text-left">
                                <span class="block">{{ $t(tab.label) }}</span>
                                <span
                                    v-if="activeTab === tab.id"
                                    class="block text-xs opacity-75 font-normal mt-0.5"
                                >
                                    {{ $t(tab.description) }}
                                </span>
                            </div>
                        </button>
                    </nav>
                </div>

                <!-- Content Area -->
                <div class="flex-1 min-w-0">
                    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                        
                        <!-- General Settings -->
                        <div v-if="activeTab === 'general'" class="p-6 space-y-8 animate-fade-in">
                            <div>
                                <h2 class="text-lg font-semibold text-gray-900">{{ $t('General Information') }}</h2>
                                <p class="mt-1 text-sm text-gray-500">{{ $t('Basic details about your training platform.') }}</p>
                            </div>

                            <div class="grid grid-cols-1 gap-y-6 gap-x-4 sm:grid-cols-6">
                                <div class="sm:col-span-4">
                                    <label class="block text-sm font-medium text-gray-700">{{ $t('Site Name') }}</label>
                                    <div class="mt-1">
                                        <input
                                            v-model="settings.general.siteName"
                                            type="text"
                                            class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm"
                                        />
                                    </div>
                                </div>

                                <div class="sm:col-span-6">
                                    <label class="block text-sm font-medium text-gray-700">{{ $t('Description') }}</label>
                                    <div class="mt-1">
                                        <textarea
                                            v-model="settings.general.siteDescription"
                                            rows="3"
                                            class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm"
                                        ></textarea>
                                    </div>
                                    <p class="mt-2 text-xs text-gray-500">{{ $t('Brief description displayed in search results and meta tags.') }}</p>
                                </div>

                                <div class="sm:col-span-3">
                                    <label class="block text-sm font-medium text-gray-700">{{ $t('Contact Email') }}</label>
                                    <div class="mt-1 flex rounded-lg shadow-sm">
                                        <span class="inline-flex items-center px-3 rounded-l-lg border border-r-0 border-gray-300 bg-gray-50 text-gray-500 sm:text-sm">
                                            <Mail class="h-4 w-4" />
                                        </span>
                                        <input
                                            v-model="settings.general.contactEmail"
                                            type="email"
                                            class="flex-1 block w-full rounded-none rounded-r-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500 sm:text-sm"
                                        />
                                    </div>
                                </div>

                                <div class="sm:col-span-3">
                                    <label class="block text-sm font-medium text-gray-700">{{ $t('Timezone') }}</label>
                                    <div class="mt-1">
                                        <select
                                            v-model="settings.general.timezone"
                                            class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm"
                                        >
                                            <option value="UTC">UTC</option>
                                            <option value="America/New_York">Eastern Time</option>
                                            <option value="America/Chicago">Central Time</option>
                                            <option value="America/Denver">Mountain Time</option>
                                            <option value="America/Los_Angeles">Pacific Time</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Notifications Settings -->
                        <div v-else-if="activeTab === 'notifications'" class="p-6 space-y-8 animate-fade-in">
                            <div>
                                <h2 class="text-lg font-semibold text-gray-900">{{ $t('Notifications') }}</h2>
                                <p class="mt-1 text-sm text-gray-500">{{ $t('Decide how and when you want to be notified.') }}</p>
                            </div>

                            <div class="space-y-6 divide-y divide-gray-100">
                                <div class="flex items-center justify-between pt-4 first:pt-0">
                                    <div class="flex flex-col">
                                        <span class="text-sm font-medium text-gray-900">{{ $t('Email Notifications') }}</span>
                                        <span class="text-sm text-gray-500">{{ $t('Receive daily summaries of activity.') }}</span>
                                    </div>
                                    <button
                                        @click="settings.notifications.emailNotifications = !settings.notifications.emailNotifications"
                                        :class="[
                                            settings.notifications.emailNotifications ? 'bg-blue-600' : 'bg-gray-200',
                                            'relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2'
                                        ]"
                                    >
                                        <span
                                            :class="[
                                                settings.notifications.emailNotifications ? 'translate-x-5' : 'translate-x-0',
                                                'pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out'
                                            ]"
                                        />
                                    </button>
                                </div>

                                <div class="flex items-center justify-between pt-4">
                                    <div class="flex flex-col">
                                        <span class="text-sm font-medium text-gray-900">{{ $t('Course Updates') }}</span>
                                        <span class="text-sm text-gray-500">{{ $t('Get notified when trainers update course content.') }}</span>
                                    </div>
                                    <button
                                        @click="settings.notifications.courseUpdates = !settings.notifications.courseUpdates"
                                        :class="[
                                            settings.notifications.courseUpdates ? 'bg-blue-600' : 'bg-gray-200',
                                            'relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2'
                                        ]"
                                    >
                                        <span
                                            :class="[
                                                settings.notifications.courseUpdates ? 'translate-x-5' : 'translate-x-0',
                                                'pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out'
                                            ]"
                                        />
                                    </button>
                                </div>

                                <div class="flex items-center justify-between pt-4">
                                    <div class="flex flex-col">
                                        <span class="text-sm font-medium text-gray-900">{{ $t('Attendance Alerts') }}</span>
                                        <span class="text-sm text-gray-500">{{ $t('Alerts when attendance drops below threshold.') }}</span>
                                    </div>
                                    <button
                                        @click="settings.notifications.attendanceAlerts = !settings.notifications.attendanceAlerts"
                                        :class="[
                                            settings.notifications.attendanceAlerts ? 'bg-blue-600' : 'bg-gray-200',
                                            'relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2'
                                        ]"
                                    >
                                        <span
                                            :class="[
                                                settings.notifications.attendanceAlerts ? 'translate-x-5' : 'translate-x-0',
                                                'pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out'
                                            ]"
                                        />
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Course Settings -->
                        <div v-else-if="activeTab === 'courses'" class="p-6 space-y-8 animate-fade-in">
                            <div>
                                <h2 class="text-lg font-semibold text-gray-900">{{ $t('Course Configuration') }}</h2>
                                <p class="mt-1 text-sm text-gray-500">{{ $t('Global settings for training programs.') }}</p>
                            </div>

                            <div class="grid grid-cols-1 gap-y-6 gap-x-4 sm:grid-cols-6">
                                <div class="sm:col-span-3">
                                    <label class="block text-sm font-medium text-gray-700">{{ $t('Default Student Limit') }}</label>
                                    <div class="mt-1">
                                        <input
                                            v-model="settings.courses.maxStudentsPerCourse"
                                            type="number"
                                            class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm"
                                        />
                                    </div>
                                </div>

                                <div class="sm:col-span-6 border-t border-gray-100 pt-6">
                                    <div class="flex items-center justify-between">
                                        <div class="flex flex-col">
                                            <span class="text-sm font-medium text-gray-900">{{ $t('Auto-Approval') }}</span>
                                            <span class="text-sm text-gray-500">{{ $t('New courses published immediately') }}</span>
                                        </div>
                                        <button
                                            @click="settings.courses.autoApproval = !settings.courses.autoApproval"
                                            :class="[
                                                settings.courses.autoApproval ? 'bg-blue-600' : 'bg-gray-200',
                                                'relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2'
                                            ]"
                                        >
                                            <span
                                                :class="[
                                                    settings.courses.autoApproval ? 'translate-x-5' : 'translate-x-0',
                                                    'pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out'
                                                ]"
                                            />
                                        </button>
                                    </div>
                                </div>

                                <div class="sm:col-span-6 pt-4">
                                    <div class="flex items-center justify-between">
                                        <div class="flex flex-col">
                                            <span class="text-sm font-medium text-gray-900">{{ $t('Self-Enrollment') }}</span>
                                            <span class="text-sm text-gray-500">{{ $t('Allow students to enroll without approval') }}</span>
                                        </div>
                                        <button
                                            @click="settings.courses.allowSelfEnrollment = !settings.courses.allowSelfEnrollment"
                                            :class="[
                                                settings.courses.allowSelfEnrollment ? 'bg-blue-600' : 'bg-gray-200',
                                                'relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2'
                                            ]"
                                        >
                                            <span
                                                :class="[
                                                    settings.courses.allowSelfEnrollment ? 'translate-x-5' : 'translate-x-0',
                                                    'pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out'
                                                ]"
                                            />
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>

                         <!-- Security Settings -->
                        <div v-else-if="activeTab === 'security'" class="p-6 space-y-8 animate-fade-in">
                            <div>
                                <h2 class="text-lg font-semibold text-gray-900">{{ $t('Security') }}</h2>
                                <p class="mt-1 text-sm text-gray-500">{{ $t('Manage security protocols.') }}</p>
                            </div>

                            <div class="space-y-6">
                                <div class="flex items-center justify-between py-4 border-b border-gray-100">
                                    <div class="flex flex-col">
                                        <span class="text-sm font-medium text-gray-900">{{ $t('Two-Factor Authentication') }}</span>
                                        <span class="text-sm text-gray-500">{{ $t('Add an extra layer of security') }}</span>
                                    </div>
                                    <button
                                        @click="settings.security.twoFactorAuth = !settings.security.twoFactorAuth"
                                        :class="[
                                            settings.security.twoFactorAuth ? 'bg-green-600' : 'bg-gray-200',
                                            'relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2'
                                        ]"
                                    >
                                        <span
                                            :class="[
                                                settings.security.twoFactorAuth ? 'translate-x-5' : 'translate-x-0',
                                                'pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out'
                                            ]"
                                        />
                                    </button>
                                </div>
                                
                                <div class="grid grid-cols-1 gap-y-6 gap-x-4 sm:grid-cols-6">
                                    <div class="sm:col-span-3">
                                        <label class="block text-sm font-medium text-gray-700">{{ $t('Session Timeout (minutes)') }}</label>
                                        <input
                                            v-model="settings.security.sessionTimeout"
                                            type="number"
                                            class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm"
                                        />
                                    </div>
                                    <div class="sm:col-span-3">
                                        <label class="block text-sm font-medium text-gray-700">{{ $t('Password Expiry (days)') }}</label>
                                        <input
                                            v-model="settings.security.passwordExpiry"
                                            type="number"
                                            class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm"
                                        />
                                    </div>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>
            </div>
        </div>

        <!-- Toast Notification -->
        <Transition
            enter-active-class="transform ease-out duration-300 transition"
            enter-from-class="translate-y-2 opacity-0 sm:translate-y-0 sm:translate-x-2"
            enter-to-class="translate-y-0 opacity-100 sm:translate-x-0"
            leave-active-class="transition ease-in duration-100"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div v-if="showToast" class="fixed bottom-4 right-4 z-50">
                <div class="bg-gray-900 text-white rounded-lg shadow-lg px-4 py-3 flex items-center gap-3">
                    <CheckCircle2 class="w-5 h-5 text-green-400" />
                    <span class="text-sm font-medium">{{ $t('Settings saved successfully') }}</span>
                </div>
            </div>
        </Transition>
    </AdminLayout>
</template>

<style scoped>
@keyframes fadeIn {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
}
.animate-fade-in {
    animation: fadeIn 0.3s ease-out forwards;
}
</style>
