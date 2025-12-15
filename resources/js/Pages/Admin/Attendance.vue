<script setup>
import { ref } from 'vue';
import { Head } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Calendar, CheckCircle, XCircle } from 'lucide-vue-next';

const sessions = ref([
    {
        id: 1,
        course: 'Advanced Laravel Development',
        date: '2025-12-15',
        time: '10:00 AM',
        instructor: 'John Smith',
        totalStudents: 45,
        present: 42,
        absent: 3,
        status: 'Completed'
    },
    {
        id: 2,
        course: 'Vue.js Masterclass',
        date: '2025-12-15',
        time: '2:00 PM',
        instructor: 'Sarah Johnson',
        totalStudents: 32,
        present: 30,
        absent: 2,
        status: 'Completed'
    },
    {
        id: 3,
        course: 'Digital Marketing Strategy',
        date: '2025-12-16',
        time: '9:00 AM',
        instructor: 'Emily Brown',
        totalStudents: 56,
        present: 0,
        absent: 0,
        status: 'Upcoming'
    },
]);

const selectedDate = ref('2025-12-15');
</script>

<template>
    <Head title="Attendance" />
    <AdminLayout>
        <div class="space-y-6">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-3xl font-bold text-gray-900">Attendance</h1>
                <p class="mt-2 text-sm text-gray-600">Track and manage attendance for all sessions</p>
            </div>
            <div class="flex space-x-3">
                <input
                    v-model="selectedDate"
                    type="date"
                    class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                />
                <button class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg font-medium transition-colors">
                    Export Report
                </button>
            </div>
        </div>

        <!-- Summary Cards -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-500">Total Sessions</p>
                        <p class="mt-2 text-3xl font-semibold text-gray-900">{{ sessions.length }}</p>
                    </div>
                    <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center">
                        <Calendar :size="24" class="text-blue-600" />
                    </div>
                </div>
            </div>
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-500">Average Attendance</p>
                        <p class="mt-2 text-3xl font-semibold text-green-600">95%</p>
                    </div>
                    <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center">
                        <CheckCircle :size="24" class="text-green-600" />
                    </div>
                </div>
            </div>
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-500">Total Absences</p>
                        <p class="mt-2 text-3xl font-semibold text-red-600">5</p>
                    </div>
                    <div class="w-12 h-12 bg-red-100 rounded-lg flex items-center justify-center">
                        <XCircle :size="24" class="text-red-600" />
                    </div>
                </div>
            </div>
        </div>

        <!-- Sessions Table -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Course
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Date & Time
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Instructor
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Attendance
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Status
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Actions
                        </th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <tr v-for="session in sessions" :key="session.id" class="hover:bg-gray-50">
                        <td class="px-6 py-4">
                            <div class="text-sm font-medium text-gray-900">{{ session.course }}</div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm text-gray-900">{{ session.date }}</div>
                            <div class="text-sm text-gray-500">{{ session.time }}</div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm text-gray-900">{{ session.instructor }}</div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm text-gray-900">
                                <span class="text-green-600 font-semibold">{{ session.present }}</span> /
                                {{ session.totalStudents }}
                            </div>
                            <div class="text-xs text-gray-500">
                                {{ session.absent }} absent
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span :class="[
                                'px-2 py-1 text-xs font-semibold rounded-full',
                                session.status === 'Completed' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800'
                            ]">
                                {{ session.status }}
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                            <button
                                v-if="session.status === 'Completed'"
                                class="text-blue-600 hover:text-blue-900 mr-3"
                            >
                                View Details
                            </button>
                            <button
                                v-else
                                class="text-green-600 hover:text-green-900 mr-3"
                            >
                                Take Attendance
                            </button>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
        </div>
    </AdminLayout>
</template>
