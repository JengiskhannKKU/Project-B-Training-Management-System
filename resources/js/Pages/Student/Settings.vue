<script setup>
import { ref } from 'vue';
import { Head } from '@inertiajs/vue3';
import StudentLayout from '@/Layouts/StudentLayout.vue';
import { Settings as SettingsIcon, Bell, BookOpen } from 'lucide-vue-next';

const settings = ref({
    notifications: {
        emailNotifications: true,
        courseUpdates: true,
        attendanceAlerts: true,
        enrollmentConfirmations: true,
    },
    preferences: {
        language: 'en',
        timezone: 'UTC',
        emailDigest: 'daily',
    },
});

const saveSettings = () => {
    alert('Settings saved successfully!');
};
</script>

<template>
    <Head title="Settings" />
    <StudentLayout>
        <div class="space-y-6">
        <div>
            <h1 class="text-3xl font-bold text-gray-900">Settings</h1>
            <p class="mt-2 text-sm text-gray-600">Manage your preferences and notifications</p>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Settings Navigation -->
            <div class="lg:col-span-1">
                <nav class="bg-white rounded-lg shadow-sm border border-gray-200 p-4 space-y-1">
                    <a href="#notifications" class="flex items-center px-3 py-2 text-sm font-medium rounded-lg bg-emerald-50 text-emerald-700">
                        <Bell :size="20" class="mr-3" />
                        Notifications
                    </a>
                    <a href="#preferences" class="flex items-center px-3 py-2 text-sm font-medium rounded-lg text-gray-700 hover:bg-gray-50">
                        <SettingsIcon :size="20" class="mr-3" />
                        Preferences
                    </a>
                </nav>
            </div>

            <!-- Settings Content -->
            <div class="lg:col-span-2 space-y-6">
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
                                <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-emerald-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-emerald-600"></div>
                            </label>
                        </div>
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-900">Course Updates</p>
                                <p class="text-sm text-gray-500">Get notified about course changes and updates</p>
                            </div>
                            <label class="relative inline-flex items-center cursor-pointer">
                                <input v-model="settings.notifications.courseUpdates" type="checkbox" class="sr-only peer">
                                <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-emerald-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-emerald-600"></div>
                            </label>
                        </div>
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-900">Attendance Alerts</p>
                                <p class="text-sm text-gray-500">Receive alerts about your attendance</p>
                            </div>
                            <label class="relative inline-flex items-center cursor-pointer">
                                <input v-model="settings.notifications.attendanceAlerts" type="checkbox" class="sr-only peer">
                                <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-emerald-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-emerald-600"></div>
                            </label>
                        </div>
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-900">Enrollment Confirmations</p>
                                <p class="text-sm text-gray-500">Get notified when you successfully enroll in a course</p>
                            </div>
                            <label class="relative inline-flex items-center cursor-pointer">
                                <input v-model="settings.notifications.enrollmentConfirmations" type="checkbox" class="sr-only peer">
                                <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-emerald-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-emerald-600"></div>
                            </label>
                        </div>
                    </div>
                </div>

                <!-- Preference Settings -->
                <div id="preferences" class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                    <h2 class="text-xl font-semibold text-gray-900 mb-4">Preferences</h2>
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Language</label>
                            <select
                                v-model="settings.preferences.language"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-transparent"
                            >
                                <option value="en">English</option>
                                <option value="th">ไทย</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Timezone</label>
                            <select
                                v-model="settings.preferences.timezone"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-transparent"
                            >
                                <option value="UTC">UTC</option>
                                <option value="Asia/Bangkok">Asia/Bangkok</option>
                                <option value="America/New_York">Eastern Time</option>
                                <option value="America/Chicago">Central Time</option>
                                <option value="America/Denver">Mountain Time</option>
                                <option value="America/Los_Angeles">Pacific Time</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Email Digest</label>
                            <select
                                v-model="settings.preferences.emailDigest"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-transparent"
                            >
                                <option value="realtime">Real-time</option>
                                <option value="daily">Daily</option>
                                <option value="weekly">Weekly</option>
                                <option value="never">Never</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Save Button -->
                <div class="flex justify-end">
                    <button
                        @click="saveSettings"
                        class="bg-emerald-600 hover:bg-emerald-700 text-white px-6 py-2 rounded-lg font-medium transition-colors"
                    >
                        Save Settings
                    </button>
                </div>
            </div>
        </div>
        </div>
    </StudentLayout>
</template>
