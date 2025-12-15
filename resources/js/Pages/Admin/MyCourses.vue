<script setup>
import { ref } from 'vue';
import { Head } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Tag, User, Users } from 'lucide-vue-next';

const courses = ref([
    {
        id: 1,
        title: 'Advanced Laravel Development',
        category: 'Programming',
        instructor: 'John Smith',
        students: 45,
        status: 'Active',
        progress: 75
    },
    {
        id: 2,
        title: 'Vue.js Masterclass',
        category: 'Programming',
        instructor: 'Sarah Johnson',
        students: 32,
        status: 'Active',
        progress: 60
    },
    {
        id: 3,
        title: 'UI/UX Design Principles',
        category: 'Design',
        instructor: 'Mike Davis',
        students: 28,
        status: 'Draft',
        progress: 40
    },
    {
        id: 4,
        title: 'Digital Marketing Strategy',
        category: 'Marketing',
        instructor: 'Emily Brown',
        students: 56,
        status: 'Active',
        progress: 90
    },
]);

const filterStatus = ref('all');
</script>

<template>
    <Head title="My Courses" />
    <AdminLayout>
        <div class="space-y-6">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-3xl font-bold text-gray-900">My Courses</h1>
                <p class="mt-2 text-sm text-gray-600">Manage and monitor all training courses</p>
            </div>
            <button class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg font-medium transition-colors">
                Create Course
            </button>
        </div>

        <!-- Filter Tabs -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
            <div class="flex space-x-4">
                <button
                    @click="filterStatus = 'all'"
                    :class="[
                        'px-4 py-2 rounded-lg font-medium transition-colors',
                        filterStatus === 'all' ? 'bg-blue-100 text-blue-700' : 'text-gray-600 hover:bg-gray-100'
                    ]"
                >
                    All Courses
                </button>
                <button
                    @click="filterStatus = 'active'"
                    :class="[
                        'px-4 py-2 rounded-lg font-medium transition-colors',
                        filterStatus === 'active' ? 'bg-blue-100 text-blue-700' : 'text-gray-600 hover:bg-gray-100'
                    ]"
                >
                    Active
                </button>
                <button
                    @click="filterStatus = 'draft'"
                    :class="[
                        'px-4 py-2 rounded-lg font-medium transition-colors',
                        filterStatus === 'draft' ? 'bg-blue-100 text-blue-700' : 'text-gray-600 hover:bg-gray-100'
                    ]"
                >
                    Draft
                </button>
            </div>
        </div>

        <!-- Courses List -->
        <div class="space-y-4">
            <div
                v-for="course in courses"
                :key="course.id"
                class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 hover:shadow-md transition-shadow"
            >
                <div class="flex items-start justify-between">
                    <div class="flex-1">
                        <div class="flex items-center space-x-3">
                            <h3 class="text-lg font-semibold text-gray-900">{{ course.title }}</h3>
                            <span :class="[
                                'px-2 py-1 text-xs font-semibold rounded-full',
                                course.status === 'Active' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800'
                            ]">
                                {{ course.status }}
                            </span>
                        </div>
                        <div class="mt-2 flex items-center space-x-4 text-sm text-gray-600">
                            <span class="flex items-center">
                                <Tag :size="16" class="mr-1" />
                                {{ course.category }}
                            </span>
                            <span class="flex items-center">
                                <User :size="16" class="mr-1" />
                                {{ course.instructor }}
                            </span>
                            <span class="flex items-center">
                                <Users :size="16" class="mr-1" />
                                {{ course.students }} students
                            </span>
                        </div>
                        <div class="mt-4">
                            <div class="flex items-center justify-between text-sm mb-1">
                                <span class="text-gray-600">Course Progress</span>
                                <span class="font-medium text-gray-900">{{ course.progress }}%</span>
                            </div>
                            <div class="w-full bg-gray-200 rounded-full h-2">
                                <div
                                    class="bg-blue-600 h-2 rounded-full transition-all"
                                    :style="{ width: course.progress + '%' }"
                                ></div>
                            </div>
                        </div>
                    </div>
                    <div class="flex space-x-2 ml-4">
                        <button class="text-blue-600 hover:text-blue-800 px-3 py-1 rounded-lg border border-blue-200 hover:bg-blue-50 transition-colors">
                            View
                        </button>
                        <button class="text-gray-600 hover:text-gray-800 px-3 py-1 rounded-lg border border-gray-200 hover:bg-gray-50 transition-colors">
                            Edit
                        </button>
                    </div>
                </div>
            </div>
        </div>
        </div>
    </AdminLayout>
</template>
