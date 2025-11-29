<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';

const stats = [
    { label: 'Active Trainings', value: 8, helper: '+2 vs last month' },
    { label: 'Trainers', value: 14, helper: '3 pending approvals' },
    { label: 'Students', value: 220, helper: '18 new signups' },
];

const reviews = [
    { title: 'Q2 Course Catalog', date: 'Due Mar 11' },
    { title: 'Trainer Certification Requests', date: 'Due Mar 15' },
    { title: 'Budget Alignment', date: 'Due Mar 20' },
];

const announcements = [
    'System maintenance scheduled for Friday 9PM.',
    'Remember to review trainer onboarding SOP v3.0.',
    'New compliance module ready for publishing.',
];
</script>

<template>
    <Head title="Admin Dashboard" />

    <AuthenticatedLayout>
        <section class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            <article
                v-for="stat in stats"
                :key="stat.label"
                class="rounded-xl border border-gray-200 bg-white px-5 py-4 shadow-sm"
            >
                <p class="text-sm font-medium text-gray-500">
                    {{ stat.label }}
                </p>
                <p class="mt-2 text-3xl font-semibold text-gray-900">
                    {{ stat.value }}
                </p>
                <p class="text-sm text-emerald-600">
                    {{ stat.helper }}
                </p>
            </article>
        </section>

        <section class="mt-10 grid gap-6 lg:grid-cols-2">
            <article class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
                <header class="mb-4 flex items-center justify-between">
                    <h2 class="text-lg font-semibold text-gray-900">
                        Reviews in Progress
                    </h2>
                    <a class="text-sm font-medium text-indigo-600 hover:text-indigo-500" href="#">
                        View all
                    </a>
                </header>
                <ul class="space-y-3">
                    <li
                        v-for="review in reviews"
                        :key="review.title"
                        class="flex items-center justify-between rounded-lg border border-gray-100 px-4 py-3"
                    >
                        <div>
                            <p class="font-medium text-gray-800">
                                {{ review.title }}
                            </p>
                            <p class="text-sm text-gray-500">
                                {{ review.date }}
                            </p>
                        </div>
                        <button
                            type="button"
                            class="rounded-md border border-gray-200 px-3 py-1 text-sm font-medium text-gray-600 hover:bg-gray-50"
                        >
                            Review
                        </button>
                    </li>
                </ul>
            </article>

            <article class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
                <header class="mb-4">
                    <h2 class="text-lg font-semibold text-gray-900">
                        Announcements
                    </h2>
                    <p class="text-sm text-gray-500">
                        Keep your trainers informed with timely updates.
                    </p>
                </header>
                <ul class="space-y-4">
                    <li
                        v-for="message in announcements"
                        :key="message"
                        class="rounded-lg border border-dashed border-gray-200 px-4 py-3 text-sm text-gray-700"
                    >
                        {{ message }}
                    </li>
                </ul>
            </article>
        </section>
    </AuthenticatedLayout>
</template>
