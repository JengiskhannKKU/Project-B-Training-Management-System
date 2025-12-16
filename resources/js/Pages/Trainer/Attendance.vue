<script setup>
import { Head } from '@inertiajs/vue3';
import TrainerLayout from '@/Layouts/TrainerLayout.vue';
</script>

<template>
    <Head title="Attendance - Trainer" />

    <TrainerLayout>
        <div class="bg-gray-50 min-h-screen">
            <!-- Header -->
            <header class="border-b bg-white px-8 py-6 -m-4 mb-4">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">Attendance</h1>
                    <p class="mt-1 text-sm text-gray-500">Manage course attendance and track student participation</p>
                </div>
            </header>

            <!-- Content -->
            <div class="p-8">
                <div class="bg-white rounded-lg shadow-sm p-6">
                    <div class="text-center py-12">
                        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                        <h3 class="mt-2 text-sm font-medium text-gray-900">Attendance Management</h3>
                        <p class="mt-1 text-sm text-gray-500">This page is under development</p>
                    </div>
                </div>
            </div>
        </div>
    </TrainerLayout>
</template>
