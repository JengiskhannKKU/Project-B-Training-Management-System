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

// Receive data from backend
const props = defineProps({
    stats: {
        type: Object,
        required: true,
    },
    teachingHours: {
        type: Object,
        required: true,
    },
    topCourses: {
        type: Array,
        required: true,
    },
    engagement: {
        type: Object,
        required: true,
    },
    departments: {
        type: Object,
        required: true,
    },
});
</script>

<template>
    <Head title="Trainer Dashboard" />

    <TrainerLayout>
        <div class="mx-auto max-w-[1600px] space-y-8">
            <!-- Header -->
            <div class="space-y-1">
                <h1 class="text-3xl font-bold tracking-tight text-gray-900">
                    {{ $t('Dashboard') }}
                </h1>
                <p class="text-sm text-gray-600">
                    {{ $t('Welcome Back') }}
                </p>
            </div>

            <!-- Main Content -->
            <div class="space-y-6 bg-white border border-[#DFE5EF] rounded-xl p-6">
                <!-- Top Section - Stat Cards -->
                <div class="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-3">
                    <!-- Total Trainees Card -->
                    <StatsCard
                        :title="$t('Total Trainees')"
                        :value="props.stats.trainees.value"
                        :growth="props.stats.trainees.growth"
                        hover-color="blue"
                        sparkline-color="green"
                        :sparkline-data="props.stats.trainees.sparklineData"
                    >
                        <template #growth-icon>
                            <TrendingUp :size="12" />
                        </template>
                    </StatsCard>

                    <!-- Total Courses Card -->
                    <StatsCard
                        :title="$t('Total Courses')"
                        :value="props.stats.courses.total"
                        :icon="BookOpen"
                        icon-color="purple"
                        hover-color="purple"
                        :badge="`${props.stats.courses.active} ${$t('active')}`"
                    />

                    <!-- Pending Certifications Card -->
                    <StatsCard
                        :title="$t('Pending Certifications')"
                        :value="props.stats.pendingCertifications.value"
                        :icon="Award"
                        icon-color="emerald"
                        hover-color="emerald"
                        :badge="props.stats.pendingCertifications.trend === 'down' ? $t('Decreasing') : $t('Increasing')"
                    />
                </div>

                <!-- Middle Section - Teaching Hours & Student Engagement -->
                <div class="grid items-start gap-6 lg:grid-cols-3">
                    <!-- Teaching Hours Graph (2 columns) -->
                    <div class="lg:col-span-2">
                        <TeachingHoursCard
                            :days="props.teachingHours.days"
                            :hours="props.teachingHours.hours"
                        />
                    </div>

                    <!-- Student Engagement -->
                    <div>
                        <StudentEngagementCard
                            :highly-engaged="props.engagement.highlyEngaged"
                            :moderate="props.engagement.moderate"
                            :at-risk="props.engagement.atRisk"
                        />
                    </div>
                </div>

                <!-- Bottom Section - Top Courses & Department Distribution -->
                <div class="grid gap-6 lg:grid-cols-2">
                    <!-- Top 3 Courses -->
                    <TopCoursesCard :courses="props.topCourses" />

                    <!-- Department Distribution -->
                    <DepartmentDistributionCard
                        :data="props.departments.data"
                        :labels="props.departments.labels"
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
