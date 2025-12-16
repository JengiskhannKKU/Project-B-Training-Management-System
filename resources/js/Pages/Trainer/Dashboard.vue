<script setup lang="ts">
import TrainerLayout from '@/Layouts/TrainerLayout.vue';
import { Head } from '@inertiajs/vue3';

const sessions = [
    { title: 'Leadership 101', date: 'Mar 05 · 09:00 AM', location: 'Room 3A' },
    { title: 'Advanced Excel', date: 'Mar 06 · 01:30 PM', location: 'Room 2C' },
    { title: 'Coaching Clinic', date: 'Mar 08 · 10:00 AM', location: 'Zoom' },
];

const tasks = [
    { label: 'Upload lesson material', due: 'Today' },
    { label: 'Grade assignments', due: 'Tomorrow' },
    { label: 'Send recap email', due: 'Friday' },
];

const feedback = [
    { student: 'Olivia', quote: 'Loved the examples used in yesterday’s session!' },
    { student: 'Marcus', quote: 'Could we get a cheat-sheet for formulas?' },
];
</script>

<template>
    <Head title="Trainer Dashboard" />

    <TrainerLayout>
        <section class="rounded-2xl border border-indigo-100 bg-indigo-50 px-6 py-5 text-indigo-900">
            <h1 class="text-xl font-semibold">Welcome back, Coach!</h1>
            <p class="mt-1 text-sm text-indigo-700">
                Prep your next sessions and keep learners engaged.
            </p>
        </section>

        <section class="mt-8 grid gap-6 lg:grid-cols-3">
            <article class="lg:col-span-2 rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
                <h2 class="text-lg font-semibold text-gray-900">
                    Upcoming Sessions
                </h2>
                <ul class="mt-4 space-y-4">
                    <li
                        v-for="session in sessions"
                        :key="session.title"
                        class="flex items-center justify-between rounded-lg border border-gray-100 px-4 py-3"
                    >
                        <div>
                            <p class="font-medium text-gray-900">
                                {{ session.title }}
                            </p>
                            <p class="text-sm text-gray-500">
                                {{ session.date }} — {{ session.location }}
                            </p>
                        </div>
                        <button
                            type="button"
                            class="rounded-md border border-gray-200 px-3 py-1 text-sm font-medium text-gray-600 hover:bg-gray-50"
                        >
                            Manage
                        </button>
                    </li>
                </ul>
            </article>

            <article class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
                <h2 class="text-lg font-semibold text-gray-900">
                    Quick Tasks
                </h2>
                <ul class="mt-3 space-y-3">
                    <li
                        v-for="task in tasks"
                        :key="task.label"
                        class="flex items-center justify-between rounded-lg border border-dashed border-gray-200 px-4 py-2 text-sm text-gray-700"
                    >
                        <span>{{ task.label }}</span>
                        <span class="text-xs font-medium uppercase text-gray-500">
                            {{ task.due }}
                        </span>
                    </li>
                </ul>
            </article>
        </section>

        <section class="mt-8 rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
            <header class="mb-4 flex items-center justify-between">
                <div>
                    <h2 class="text-lg font-semibold text-gray-900">
                        Recent Feedback
                    </h2>
                    <p class="text-sm text-gray-500">
                        Pulse from participants in your latest cohorts.
                    </p>
                </div>
                <button
                    type="button"
                    class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-500"
                >
                    View all
                </button>
            </header>

            <ul class="space-y-4">
                <li
                    v-for="item in feedback"
                    :key="item.student"
                    class="rounded-lg border border-gray-100 px-4 py-3"
                >
                    <p class="font-semibold text-gray-800">
                        {{ item.student }}
                    </p>
                    <p class="text-sm text-gray-600">
                        {{ item.quote }}
                    </p>
                </li>
            </ul>
        </section>
    </TrainerLayout>
</template>
