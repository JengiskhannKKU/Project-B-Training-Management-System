<script setup lang="ts">
import { ref } from 'vue';
import { Head } from '@inertiajs/vue3';
import TrainerLayout from '@/Layouts/TrainerLayout.vue';
import CourseCard from '@/Components/CourseCard.vue';
import CourseModal from '@/Components/CourseModal.vue';
import {
    Search,
    Plus,
    Menu,
    ChevronLeft,
    ChevronRight
} from 'lucide-vue-next';

defineProps<{
    programs: Array<{
        id: number;
        name: string;
        code: string;
        category: string;
        duration_hours: number;
        description: string;
        status: string;
        image_url: string | null;
        rating?: number;
        level?: string;
        students_count?: number;
        price?: string;
        date?: string;
        time?: string;
        location?: string;
    }>;
}>();

const searchQuery = ref('');
const activeTab = ref('all');
const showCreateModal = ref(false);

const tabs = [
    { key: 'all', label: 'หลักสูตรทั้งหมด' },
    { key: 'teaching', label: 'กำลังสอน' },
    { key: 'completed', label: 'เสร็จสิ้น' },
    { key: 'pending', label: 'อนุมัติรอ' },
];

const handleModalClose = () => {
    showCreateModal.value = false;
};

const handleModalSuccess = () => {
    // Refresh courses or perform any action after successful creation/update
    console.log('Course created/updated successfully');
};

// Mock data for demonstration
const mockPrograms = [
    {
        id: 1,
        name: 'Advanced UX Design',
        image_url: 'https://images.unsplash.com/photo-1522202176988-66273c2fd55f?w=400',
        rating: 4.7,
        level: 'Beginner',
        students_count: 32,
        price: 'Free',
        date: 'Oct 22,2024',
        time: '09:00 - 16:00',
        location: 'Smart Class room'
    },
    {
        id: 2,
        name: 'Design principle',
        image_url: 'https://images.unsplash.com/photo-1531482615713-2afd69097998?w=400',
        rating: 4.7,
        level: 'Advanced',
        students_count: 32,
        price: 'Free',
        date: 'Oct 22,2024',
        time: '09:00 - 16:00',
        location: 'Smart Class room'
    },
    {
        id: 3,
        name: 'Interaction Design',
        image_url: 'https://images.unsplash.com/photo-1573497019940-1c28c88b4f3e?w=400',
        rating: 4.7,
        level: 'Intermediate',
        students_count: 32,
        price: 'Free',
        date: 'Oct 22,2024',
        time: '09:00 - 16:00',
        location: 'Smart Class room'
    },
    {
        id: 4,
        name: 'Advanced UX Design',
        image_url: 'https://images.unsplash.com/photo-1573496359142-b8d87734a5a2?w=400',
        rating: 4.7,
        level: 'Intermediate',
        students_count: 32,
        price: 'Free',
        date: 'Oct 22,2024',
        time: '09:00 - 16:00',
        location: 'Smart Class room'
    },
    {
        id: 5,
        name: 'Computer Engineering',
        image_url: 'https://images.unsplash.com/photo-1544717305-2782549b5136?w=400',
        rating: 4.7,
        level: 'Beginner',
        students_count: 32,
        price: 'Free',
        date: 'Oct 22,2024',
        time: '09:00 - 16:00',
        location: 'Smart Class room'
    },
    {
        id: 6,
        name: 'กฎ for beginning',
        image_url: 'https://images.unsplash.com/photo-1580894894513-541e068a3e2b?w=400',
        rating: 4.7,
        level: 'Beginner',
        students_count: 32,
        price: 'Free',
        date: 'Oct 22,2024',
        time: '09:00 - 16:00',
        location: 'Smart Class room'
    },
];
</script>

<template>
    <Head title="My Courses Management" />

    <TrainerLayout>
        <!-- Main Content -->
        <div class="bg-gray-50 min-h-screen">
            <!-- Header -->
            <header class="border-b bg-white px-8 py-6 -m-4 mb-4">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">My Courses</h1>
                    <p class="mt-1 text-sm text-gray-500">Manage all my courses. Empower Your Training</p>
                </div>
            </header>

            <!-- Content -->
            <div class="p-8">
                <!-- Search and Actions -->
                <div class="mb-6 flex items-center justify-between gap-4">
                    <div class="flex flex-1 items-center gap-4">
                        <div class="relative flex-1 max-w-md">
                            <Search :size="20" class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400" />
                            <input
                                v-model="searchQuery"
                                type="text"
                                placeholder="Search courses..."
                                class="w-full rounded-lg border-gray-300 pl-10 pr-4 py-2 focus:border-teal-500 focus:ring-teal-500"
                            />
                        </div>
                    </div>
                    <button @click="showCreateModal = true" class="flex items-center gap-2 rounded-lg bg-teal-600 px-4 py-2 text-sm font-medium text-white hover:bg-teal-700">
                        <Plus :size="20" />
                        Create Course
                    </button>
                    <button class="rounded-lg border border-gray-300 p-2 hover:bg-gray-50">
                        <Menu :size="20" class="text-gray-600" />
                    </button>
                </div>

                <!-- Tabs -->
                <div class="mb-6 flex gap-2">
                    <button
                        v-for="tab in tabs"
                        :key="tab.key"
                        @click="activeTab = tab.key"
                        :class="[
                            'rounded-lg px-4 py-2 text-sm font-medium transition',
                            activeTab === tab.key
                                ? 'bg-teal-100 text-teal-700'
                                : 'text-gray-600 hover:bg-gray-100'
                        ]"
                    >
                        {{ tab.label }}
                    </button>
                </div>

                <!-- Course Grid -->
                <div class="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
                    <CourseCard
                        v-for="program in mockPrograms"
                        :key="program.id"
                        :id="program.id"
                        :name="program.name"
                        :image_url="program.image_url"
                        :rating="program.rating"
                        :level="program.level"
                        :students_count="program.students_count"
                        :price="program.price"
                        :date="program.date"
                        :time="program.time"
                        :location="program.location"
                    />
                </div>

                <!-- Pagination -->
                <div class="mt-8 flex items-center justify-between">
                    <button class="flex items-center gap-2 rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">
                        <ChevronLeft :size="20" />
                        Previous
                    </button>

                    <div class="flex gap-2">
                        <button class="h-10 w-10 rounded-lg bg-teal-100 text-sm font-medium text-teal-700">1</button>
                        <button class="h-10 w-10 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-100">2</button>
                        <button class="h-10 w-10 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-100">3</button>
                        <span class="flex h-10 w-10 items-center justify-center text-sm text-gray-400">...</span>
                        <button class="h-10 w-10 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-100">8</button>
                        <button class="h-10 w-10 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-100">9</button>
                        <button class="h-10 w-10 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-100">10</button>
                    </div>

                    <button class="flex items-center gap-2 rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">
                        Next
                        <ChevronRight :size="20" />
                    </button>
                </div>
            </div>
        </div>
    </TrainerLayout>

    <!-- Course Modal -->
    <CourseModal
        :show="showCreateModal"
        @close="handleModalClose"
        @success="handleModalSuccess"
    />

</template>
