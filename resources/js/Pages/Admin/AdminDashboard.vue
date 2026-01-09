<script setup>
import { Head } from "@inertiajs/vue3";
import AdminLayout from "@/Layouts/AdminLayout.vue";
import { GraduationCap, BookOpen, TrendingUp, Star, Award } from "lucide-vue-next";

// Component imports
import StatsCard from "@/Components/Dashboard/StatsCard.vue";
import DepartmentDistributionCard from "@/Components/Dashboard/DepartmentDistributionCard.vue";
import RegistrationTrendCard from "@/Components/Admin/Dashboard/RegistrationTrendCard.vue";
import TopCategoriesCard from "@/Components/Admin/Dashboard/TopCategoriesCard.vue";
import TopCoursesCard from "@/Components/Dashboard/TopCoursesCard.vue";
import TopTrainersCard from "@/Components/Admin/Dashboard/TopTrainersCard.vue";

// Receive data from backend
const props = defineProps({
    stats: {
        type: Object,
        required: true,
    },
    registrationData: {
        type: Object,
        required: true,
    },
    topCategories: {
        type: Array,
        required: true,
    },
    topCourses: {
        type: Array,
        required: true,
    },
    topTrainers: {
        type: Array,
        required: true,
    },
});
</script>

<template>
    <Head title="Dashboard" />
    <AdminLayout>
        <div class="mx-auto max-w-[1600px] space-y-8">
            <!-- Header -->
            <div class="space-y-1">
                <h1 class="text-3xl font-bold tracking-tight text-gray-900">
                    {{ $t('Dashboard') }}
                </h1>
                <p class="text-sm text-gray-600">
                    {{ $t('Admin Welcome') }}
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
                        :title="$t('Total Trainers')"
                        :value="props.stats.trainers.value"
                        :growth="props.stats.trainers.growth"
                        hover-color="emerald"
                        sparkline-color="emerald"
                        :sparkline-data="props.stats.trainers.sparklineData"
                    >
                        <template #growth-icon>
                            <TrendingUp :size="12" />
                        </template>
                    </StatsCard>

                    <!-- Total Trainees Card -->
                    <StatsCard
                        :title="$t('Total Trainees')"
                        :value="props.stats.trainees.value.toLocaleString()"
                        :growth="props.stats.trainees.growth"
                        :icon="Award"
                        icon-color="blue"
                        hover-color="blue"
                    >
                        <template #growth-icon>
                            <TrendingUp :size="12" />
                        </template>
                    </StatsCard>

                    <!-- Courses Card -->
                    <StatsCard
                        :title="$t('Courses')"
                        :value="props.stats.courses.total"
                        :icon="BookOpen"
                        icon-color="purple"
                        hover-color="purple"
                        :badge="`${props.stats.courses.pending} ${$t('Pending')}`"
                    />
                </div>

                <!-- Second Row: Completion, Satisfaction, Department -->
                <div
                    class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-3"
                >
                    <!-- Completion Rate Card -->
                    <StatsCard
                        :title="$t('Completion Rate')"
                        :value="`${props.stats.completion.percentage}%`"
                        :growth="props.stats.completion.growth"
                        hover-color="emerald"
                        :progress-bar="props.stats.completion.percentage"
                    >
                        <template #growth-icon>
                            <TrendingUp :size="12" />
                        </template>
                    </StatsCard>

                    <!-- Satisfaction Card -->
                    <StatsCard
                        :title="$t('Satisfaction')"
                        :value="props.stats.satisfaction.rating"
                        hover-color="amber"
                        :stars="props.stats.satisfaction.rating"
                        :max-stars="props.stats.satisfaction.maxRating"
                        :subtitle="$t('Based on reviews', { count: props.stats.satisfaction.totalReviews.toLocaleString() })"
                    >
                        <template #stars>
                            <Star
                                v-for="i in 5"
                                :key="i"
                                :size="18"
                                :class="[
                                    i <= Math.floor(props.stats.satisfaction.rating)
                                        ? 'fill-amber-400 text-amber-400 drop-shadow-sm'
                                        : 'fill-gray-200 text-gray-200',
                                    'md:h-5 md:w-5 lg:h-6 lg:w-6'
                                ]"
                            />
                        </template>
                    </StatsCard>

                    <!-- Department Distribution Card -->
                    <DepartmentDistributionCard
                        :data="props.stats.departments.data"
                        :labels="props.stats.departments.labels"
                    />
                </div>

                <!-- Middle Section - Registration Trend & Categories -->
                <div class="grid gap-6 lg:grid-cols-3">
                    <!-- Registration Trend -->
                    <RegistrationTrendCard
                        :months="props.registrationData.months"
                        :series="props.registrationData.series"
                    />

                    <!-- Categories Card -->
                    <TopCategoriesCard :categories="props.topCategories" />
                </div>

                <!-- Bottom Section - Additional Insights -->
                <div class="grid gap-6 lg:grid-cols-2">
                    <!-- Top Courses Card -->
                    <TopCoursesCard :courses="props.topCourses" />

                    <!-- Top Trainer Performance Card -->
                    <TopTrainersCard :trainers="props.topTrainers" />
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
