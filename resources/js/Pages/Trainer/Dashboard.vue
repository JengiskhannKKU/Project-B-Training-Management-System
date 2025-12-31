<script setup>
import { Head } from '@inertiajs/vue3';
import TrainerLayout from '@/Layouts/TrainerLayout.vue';
import { Users, BookOpen, Award, TrendingUp } from 'lucide-vue-next';

// Component imports
import StatsCard from '@/Components/Dashboard/StatsCard.vue';
import TeachingHoursCard from '@/Components/Trainer/Dashboard/TeachingHoursCard.vue';
import TopCoursesCard from '@/Components/Dashboard/TopCoursesCard.vue';
import StudentEngagementCard from '@/Components/Trainer/Dashboard/StudentEngagementCard.vue';
import DepartmentDistributionCard from '@/Components/Dashboard/DepartmentDistributionCard.vue';

// Stats data for trainer-specific metrics
const stats = {
    trainees: {
        value: 156,
        growth: 15.2,
        trend: 'up',
        sparklineData: [95, 102, 108, 115, 122, 128, 135, 142, 148, 151, 154, 156],
    },
    courses: {
        total: 8,
        active: 6,
        pending: 2,
    },
    pendingCertifications: {
        value: 23,
        growth: -8.5,
        trend: 'down',
    },
};

// Teaching hours data (last 7 days)
const teachingHours = {
    days: ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'],
    hours: [6, 8, 5, 7, 6, 3, 0],
};

// Top 3 courses of this trainer
const topCourses = [
    {
        rank: 1,
        name: 'Leadership Fundamentals',
        category: 'Leadership & Management',
        enrollments: 87,
        badgeColor: 'bg-amber-500',
    },
    {
        rank: 2,
        name: 'Advanced Excel Mastery',
        category: 'Technical Skills',
        enrollments: 69,
        badgeColor: 'bg-gray-400',
    },
    {
        rank: 3,
        name: 'Effective Communication',
        category: 'Soft Skills',
        enrollments: 52,
        badgeColor: 'bg-orange-600',
    },
];

// Student engagement data
const engagement = {
    highlyEngaged: 89,
    moderate: 48,
    atRisk: 19,
};

// Department distribution of this trainer's courses
const departments = {
    data: [40, 30, 15, 10, 5],
    labels: ['Engineering', 'Sales', 'Marketing', 'HR', 'Operations'],
};
</script>

<template>
    <Head title="Trainer Dashboard" />

    <TrainerLayout>
        <div class="mx-auto max-w-[1600px] space-y-8">
            <!-- Header -->
            <div class="space-y-1">
                <h1 class="text-3xl font-bold tracking-tight text-gray-900">
                    Dashboard
                </h1>
                <p class="text-sm text-gray-600">
                    Welcome back! Here's an overview of your training activities and student progress.
                </p>
            </div>

            <!-- Main Content -->
            <div class="space-y-6 bg-white border border-[#DFE5EF] rounded-xl p-6">
                <!-- Top Section - Stat Cards -->
                <div class="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-3">
                    <!-- Total Trainees Card -->
                    <StatsCard
                        title="Total Trainees"
                        :value="stats.trainees.value"
                        :growth="stats.trainees.growth"
                        hover-color="blue"
                        sparkline-color="green"
                        :sparkline-data="stats.trainees.sparklineData"
                    >
                        <template #growth-icon>
                            <TrendingUp :size="12" />
                        </template>
                    </StatsCard>

                    <!-- Total Courses Card -->
                    <StatsCard
                        title="Total Courses"
                        :value="stats.courses.total"
                        :icon="BookOpen"
                        icon-color="purple"
                        hover-color="purple"
                        :badge="`${stats.courses.active} active`"
                    />

                    <!-- Pending Certifications Card -->
                    <StatsCard
                        title="Pending Certifications"
                        :value="stats.pendingCertifications.value"
                        :icon="Award"
                        icon-color="emerald"
                        hover-color="emerald"
                        :badge="stats.pendingCertifications.trend === 'down' ? 'Decreasing' : 'Increasing'"
                    />
                </div>

                <!-- Middle Section - Teaching Hours & Student Engagement -->
                <div class="grid items-start gap-6 lg:grid-cols-3">
                    <!-- Teaching Hours Graph (2 columns) -->
                    <div class="lg:col-span-2">
                        <TeachingHoursCard
                            :days="teachingHours.days"
                            :hours="teachingHours.hours"
                        />
                    </div>

                    <!-- Student Engagement -->
                    <div>
                        <StudentEngagementCard
                            :highly-engaged="engagement.highlyEngaged"
                            :moderate="engagement.moderate"
                            :at-risk="engagement.atRisk"
                        />
                    </div>
                </div>

                <!-- Bottom Section - Top Courses & Department Distribution -->
                <div class="grid gap-6 lg:grid-cols-2">
                    <!-- Top 3 Courses -->
                    <TopCoursesCard :courses="topCourses" />

                    <!-- Department Distribution -->
                    <DepartmentDistributionCard
                        :data="departments.data"
                        :labels="departments.labels"
                    />
                </div>
            </div>
        </div>
    </TrainerLayout>
</template>

<style scoped>
/* ApexCharts custom styling */
:deep(.apexcharts-tooltip) {
    background: white !important;
    border: 1px solid #e5e7eb !important;
    box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1),
        0 4px 6px -2px rgba(0, 0, 0, 0.05) !important;
    border-radius: 0.5rem !important;
}

:deep(.apexcharts-tooltip-title) {
    background: #f9fafb !important;
    border-bottom: 1px solid #e5e7eb !important;
    font-weight: 600 !important;
    padding: 8px 12px !important;
}

:deep(.apexcharts-legend-text) {
    color: #374151 !important;
}

:deep(.apexcharts-gridline) {
    stroke: #f1f5f9 !important;
}
</style>
