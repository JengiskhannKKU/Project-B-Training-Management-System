<script setup>
import Skeleton from '@/Components/Skeleton.vue';
</script>

<template>
    <div class="animate-pulse space-y-6">
        <!-- Header Skeleton -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <Skeleton width="150px" height="36px" class="mb-2" />
                <Skeleton width="250px" height="20px" />
            </div>
        </div>

        <div class="flex flex-col lg:flex-row gap-8">
            <!-- Sidebar Skeleton -->
            <div class="lg:w-72 flex-shrink-0">
                <!-- User Card Skeleton -->
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 mb-6">
                    <div class="flex flex-col items-center">
                        <Skeleton variant="circular" width="96px" height="96px" class="mb-4" />
                        <Skeleton width="120px" height="24px" class="mb-2" />
                        <Skeleton width="160px" height="16px" class="mb-3" />
                        <Skeleton width="80px" height="24px" class="rounded-full" />
                    </div>
                </div>

                <!-- Nav Skeleton -->
                <div class="space-y-1">
                    <div v-for="i in 3" :key="i" class="flex items-center w-full px-3 py-3 rounded-xl">
                        <Skeleton width="20px" height="20px" class="mr-3" />
                        <Skeleton width="100px" height="20px" />
                    </div>
                </div>
            </div>

            <!-- Content Area Skeleton -->
            <div class="flex-1 min-w-0">
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 min-h-[500px] p-8">
                    <div class="flex items-center justify-between mb-8">
                        <div>
                            <Skeleton width="200px" height="28px" class="mb-2" />
                            <Skeleton width="300px" height="20px" />
                        </div>
                        <Skeleton width="120px" height="36px" class="rounded-lg" />
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div v-for="j in 4" :key="j">
                            <Skeleton width="80px" height="16px" class="mb-2" />
                            <Skeleton width="100%" height="42px" class="rounded-xl" />
                        </div>
                        <div class="md:col-span-2">
                            <Skeleton width="60px" height="16px" class="mb-2" />
                            <Skeleton width="100%" height="100px" class="rounded-xl" />
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
