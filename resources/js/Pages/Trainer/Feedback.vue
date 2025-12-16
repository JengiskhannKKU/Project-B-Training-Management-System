<script setup>
import { Head } from '@inertiajs/vue3';
import TrainerLayout from '@/Layouts/TrainerLayout.vue';
</script>

<template>
    <Head title="Feedback - Trainer" />

    <TrainerLayout>
        <div class="bg-gray-50 min-h-screen">
            <!-- Header -->
            <header class="border-b bg-white px-8 py-6 -m-4 mb-4">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">Feedback</h1>
                    <p class="mt-1 text-sm text-gray-500">View and manage course feedback from students</p>
                </div>
            </header>

            <!-- Content -->
            <div class="p-8">
                <div class="bg-white rounded-lg shadow-sm p-6">
                    <div class="text-center py-12">
                        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z"/>
                        </svg>
                        <h3 class="mt-2 text-sm font-medium text-gray-900">Feedback Management</h3>
                        <p class="mt-1 text-sm text-gray-500">This page is under development</p>
                    </div>
                </div>
            </div>
        </div>
    </TrainerLayout>
</template>
