<script setup>
import { Head } from "@inertiajs/vue3";
import AdminLayout from "@/Layouts/AdminLayout.vue";
import { GraduationCap, BookOpen, TrendingUp, Star } from "lucide-vue-next";

// Component imports
import StatsCard from "@/Components/Admin/Dashboard/StatsCard.vue";
import DepartmentDistributionCard from "@/Components/Admin/Dashboard/DepartmentDistributionCard.vue";
import RegistrationTrendCard from "@/Components/Admin/Dashboard/RegistrationTrendCard.vue";
import TopCategoriesCard from "@/Components/Admin/Dashboard/TopCategoriesCard.vue";
import TopCoursesCard from "@/Components/Admin/Dashboard/TopCoursesCard.vue";
import TopTrainersCard from "@/Components/Admin/Dashboard/TopTrainersCard.vue";

// Stats data with growth indicators
const stats = {
    trainers: {
        value: 142,
        growth: 12.5,
        trend: "up",
        sparklineData: [28, 40, 36, 52, 38, 60, 55, 65, 72, 85, 98, 142],
    },
    trainees: {
        value: 2847,
        icon: GraduationCap,
        growth: 8.2,
        trend: "up",
    },
    courses: {
        total: 38,
        pending: 3,
        active: 35,
    },
    completion: {
        percentage: 87.5,
        growth: 5.3,
        trend: "up",
    },
    satisfaction: {
        rating: 4.8,
        maxRating: 5.0,
        totalReviews: 1234,
    },
    departments: {
        data: [35, 25, 20, 12, 8],
        labels: ["Engineering", "Sales", "Marketing", "HR", "Operations"],
    },
};

// Registration trend data
const registrationData = {
    months: ["Jul", "Aug", "Sep", "Oct", "Nov", "Dec"],
    series: [
        {
            name: "2023",
            data: [120, 145, 160, 155, 180, 195],
        },
        {
            name: "2024",
            data: [180, 210, 245, 265, 290, 320],
        },
        {
            name: "2025",
            data: [240, 280, 310, 340, 380, 420],
        },
    ],
};

// Top categories data
const topCategories = [
    {
        name: "Technical Skills",
        percentage: 28,
        count: 11,
        color: "bg-blue-500",
    },
    {
        name: "Leadership & Management",
        percentage: 24,
        count: 9,
        color: "bg-purple-500",
    },
    {
        name: "Compliance & Safety",
        percentage: 18,
        count: 7,
        color: "bg-emerald-500",
    },
    {
        name: "Sales & Marketing",
        percentage: 16,
        count: 6,
        color: "bg-amber-500",
    },
    { name: "Soft Skills", percentage: 14, count: 5, color: "bg-pink-500" },
];

// Top courses data
const topCourses = [
    {
        rank: 1,
        name: "Advanced JavaScript Development",
        category: "Technical Skills",
        enrollments: 487,
        badgeColor: "bg-amber-500",
    },
    {
        rank: 2,
        name: "Leadership Fundamentals",
        category: "Leadership & Management",
        enrollments: 421,
        badgeColor: "bg-gray-400",
    },
    {
        rank: 3,
        name: "Data Analytics & Visualization",
        category: "Technical Skills",
        enrollments: 398,
        badgeColor: "bg-orange-600",
    },
];

// Top trainers data
const topTrainers = [
    { name: "Sarah Johnson", rating: 4.9, courses: 12, students: 245 },
    { name: "Michael Chen", rating: 4.8, courses: 8, students: 189 },
    { name: "Emily Rodriguez", rating: 4.8, courses: 10, students: 212 },
    { name: "David Kim", rating: 4.7, courses: 6, students: 156 },
    { name: "Jessica Taylor", rating: 4.7, courses: 9, students: 198 },
    { name: "Robert Anderson", rating: 4.6, courses: 7, students: 167 },
];
</script>

<template>
    <Head title="Dashboard" />
    <AdminLayout>
        <div class="mx-auto max-w-[1600px] space-y-8">
            <!-- Header -->
            <div class="space-y-1">
                <h1 class="text-3xl font-bold tracking-tight text-gray-900">
                    Dashboard
                </h1>
                <p class="text-sm text-gray-600">
                    Welcome back! Here's an overview of your training management
                    system.
                </p>
            </div>

            <!-- Top Section - Summary Cards Grid -->
            <div class="space-y-4 bg-white border border-[#DFE5EF] rounded-xl p-6">
                <!-- First Row: Trainers, Trainees, Courses -->
                <div
                    class="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-3"
                >
                    <!-- Total Trainers Card -->
                    <StatsCard
                        title="Total Trainers"
                        :value="stats.trainers.value"
                        :growth="stats.trainers.growth"
                        hover-color="emerald"
                        sparkline-color="emerald"
                        :sparkline-data="stats.trainers.sparklineData"
                    >
                        <template #growth-icon>
                            <TrendingUp :size="12" />
                        </template>
                    </StatsCard>

                    <!-- Total Trainees Card -->
                    <StatsCard
                        title="Total Trainees"
                        :value="stats.trainees.value.toLocaleString()"
                        :growth="stats.trainees.growth"
                        :icon="GraduationCap"
                        icon-color="blue"
                        hover-color="blue"
                    >
                        <template #growth-icon>
                            <TrendingUp :size="12" />
                        </template>
                    </StatsCard>

                    <!-- Courses Card -->
                    <StatsCard
                        title="Courses"
                        :value="stats.courses.total"
                        :icon="BookOpen"
                        icon-color="purple"
                        hover-color="purple"
                        :badge="`${stats.courses.pending} pending`"
                    />
                </div>

                <!-- Second Row: Completion, Satisfaction, Department -->
                <div
                    class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-[minmax(280px,1fr)_minmax(280px,1fr)_minmax(320px,600px)]"
                >
                    <!-- Completion Rate Card -->
                    <StatsCard
                        title="Completion Rate"
                        :value="`${stats.completion.percentage}%`"
                        :growth="stats.completion.growth"
                        hover-color="emerald"
                        :progress-bar="stats.completion.percentage"
                    >
                        <template #growth-icon>
                            <TrendingUp :size="12" />
                        </template>
                    </StatsCard>

                    <!-- Satisfaction Card -->
                    <StatsCard
                        title="Satisfaction"
                        :value="stats.satisfaction.rating"
                        hover-color="amber"
                        :stars="stats.satisfaction.rating"
                        :max-stars="stats.satisfaction.maxRating"
                        :subtitle="`Based on ${stats.satisfaction.totalReviews.toLocaleString()} reviews`"
                    >
                        <template #stars>
                            <Star
                                v-for="i in 5"
                                :key="i"
                                :size="18"
                                :class="
                                    i <= Math.floor(stats.satisfaction.rating)
                                        ? 'fill-amber-400 text-amber-400 drop-shadow-sm'
                                        : 'fill-gray-200 text-gray-200'
                                "
                            />
                        </template>
                    </StatsCard>

                    <!-- Department Distribution Card -->
                    <DepartmentDistributionCard
                        :data="stats.departments.data"
                        :labels="stats.departments.labels"
                    />
                </div>

                <!-- Middle Section - Registration Trend & Categories -->
                <div class="grid gap-6 lg:grid-cols-3">
                    <!-- Registration Trend -->
                    <RegistrationTrendCard
                        :months="registrationData.months"
                        :series="registrationData.series"
                    />

                    <!-- Categories Card -->
                    <TopCategoriesCard :categories="topCategories" />
                </div>

                <!-- Bottom Section - Additional Insights -->
                <div class="grid gap-6 lg:grid-cols-2">
                    <!-- Top Courses Card -->
                    <TopCoursesCard :courses="topCourses" />

                    <!-- Top Trainer Performance Card -->
                    <TopTrainersCard :trainers="topTrainers" />
                </div>
            </div>
        </div>
    </AdminLayout>
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
