<script setup>
import { ref } from 'vue';
import { Head } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Settings as SettingsIcon, Bell, BookOpen } from 'lucide-vue-next';

const settings = ref({
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
        feedbackAlerts: false,
    },
    courses: {
        autoApproval: false,
        maxStudentsPerCourse: 50,
        allowSelfEnrollment: true,
    },
});

const saveSettings = () => {
    alert('Settings saved successfully!');
};
</script>

<template>
    <Head title="Settings" />
    <AdminLayout>
        <div class="space-y-6">
        <div>
            <h1 class="text-3xl font-bold text-gray-900">Settings</h1>
            <p class="mt-2 text-sm text-gray-600">Manage your application settings and preferences</p>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Settings Navigation -->
            <div class="lg:col-span-1">
                <nav class="bg-white rounded-lg shadow-sm border border-gray-200 p-4 space-y-1">
                    <a href="#general" class="flex items-center px-3 py-2 text-sm font-medium rounded-lg bg-blue-50 text-blue-700">
                        <SettingsIcon :size="20" class="mr-3" />
                        General
                    </a>
                    <a href="#notifications" class="flex items-center px-3 py-2 text-sm font-medium rounded-lg text-gray-700 hover:bg-gray-50">
                        <Bell :size="20" class="mr-3" />
                        Notifications
                    </a>
                    <a href="#courses" class="flex items-center px-3 py-2 text-sm font-medium rounded-lg text-gray-700 hover:bg-gray-50">
                        <BookOpen :size="20" class="mr-3" />
                        Courses
                    </a>
                </nav>
            </div>

            <!-- Settings Content -->
            <div class="lg:col-span-2 space-y-6">
                <!-- General Settings -->
                <div id="general" class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                    <h2 class="text-xl font-semibold text-gray-900 mb-4">General Settings</h2>
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Site Name</label>
                            <input
                                v-model="settings.general.siteName"
                                type="text"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                            />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Site Description</label>
                            <textarea
                                v-model="settings.general.siteDescription"
                                rows="3"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                            ></textarea>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Contact Email</label>
                            <input
                                v-model="settings.general.contactEmail"
                                type="email"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                            />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Timezone</label>
                            <select
                                v-model="settings.general.timezone"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
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

                <!-- Notification Settings -->
                <div id="notifications" class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                    <h2 class="text-xl font-semibold text-gray-900 mb-4">Notification Settings</h2>
                    <div class="space-y-4">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-900">Email Notifications</p>
                                <p class="text-sm text-gray-500">Receive email notifications for important updates</p>
                            </div>
                            <label class="relative inline-flex items-center cursor-pointer">
                                <input v-model="settings.notifications.emailNotifications" type="checkbox" class="sr-only peer">
                                <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600"></div>
                            </label>
                        </div>
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-900">Course Updates</p>
                                <p class="text-sm text-gray-500">Get notified about course changes and updates</p>
                            </div>
                            <label class="relative inline-flex items-center cursor-pointer">
                                <input v-model="settings.notifications.courseUpdates" type="checkbox" class="sr-only peer">
                                <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600"></div>
                            </label>
                        </div>
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-900">Attendance Alerts</p>
                                <p class="text-sm text-gray-500">Receive alerts for attendance issues</p>
                            </div>
                            <label class="relative inline-flex items-center cursor-pointer">
                                <input v-model="settings.notifications.attendanceAlerts" type="checkbox" class="sr-only peer">
                                <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600"></div>
                            </label>
                        </div>
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-900">Feedback Alerts</p>
                                <p class="text-sm text-gray-500">Get notified when new feedback is submitted</p>
                            </div>
                            <label class="relative inline-flex items-center cursor-pointer">
                                <input v-model="settings.notifications.feedbackAlerts" type="checkbox" class="sr-only peer">
                                <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600"></div>
                            </label>
                        </div>
                    </div>
                </div>

                <!-- Course Settings -->
                <div id="courses" class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                    <h2 class="text-xl font-semibold text-gray-900 mb-4">Course Settings</h2>
                    <div class="space-y-4">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-900">Auto Approval</p>
                                <p class="text-sm text-gray-500">Automatically approve new courses without review</p>
                            </div>
                            <label class="relative inline-flex items-center cursor-pointer">
                                <input v-model="settings.courses.autoApproval" type="checkbox" class="sr-only peer">
                                <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600"></div>
                            </label>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Max Students Per Course</label>
                            <input
                                v-model="settings.courses.maxStudentsPerCourse"
                                type="number"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                            />
                        </div>
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-900">Allow Self Enrollment</p>
                                <p class="text-sm text-gray-500">Let students enroll in courses without approval</p>
                            </div>
                            <label class="relative inline-flex items-center cursor-pointer">
                                <input v-model="settings.courses.allowSelfEnrollment" type="checkbox" class="sr-only peer">
                                <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600"></div>
                            </label>
                        </div>
                    </div>
                </div>

                <!-- Save Button -->
                <div class="flex justify-end">
                    <button
                        @click="saveSettings"
                        class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-lg font-medium transition-colors"
                    >
                        Save Settings
                    </button>
                </div>
            </div>
        </div>
        </div>
    </AdminLayout>
</template>
