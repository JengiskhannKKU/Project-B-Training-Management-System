<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';

const enrollments = [
    { title: 'Design Thinking', progress: 72, facilitator: 'Amelia Howard' },
    { title: 'Project Management', progress: 45, facilitator: 'Sanjay Patel' },
    { title: 'Data Storytelling', progress: 10, facilitator: 'Jerome Vance' },
];

const assignments = [
    { course: 'Design Thinking', item: 'Prototype critique', status: 'Due in 2 days' },
    { course: 'Project Management', item: 'Sprint demo deck', status: 'Due Friday' },
];

const resources = [
    { label: 'Community forum', href: '#' },
    { label: 'Office hours', href: '#' },
    { label: 'Download apps', href: '#' },
];
</script>

<template>
    <Head title="Student Dashboard" />

    <AuthenticatedLayout>
        <section class="rounded-2xl border border-emerald-100 bg-emerald-50 px-6 py-5">
            <h1 class="text-xl font-semibold text-emerald-900">
                Ready to learn?
            </h1>
            <p class="mt-1 text-sm text-emerald-800">
                Pick up where you left off or explore new modules below.
            </p>
        </section>

        <section class="mt-8 grid gap-6 lg:grid-cols-3">
            <article class="lg:col-span-2 rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
                <h2 class="text-lg font-semibold text-gray-900">
                    Current Courses
                </h2>
                <ul class="mt-4 space-y-4">
                    <li
                        v-for="course in enrollments"
                        :key="course.title"
                        class="rounded-xl border border-gray-100 px-5 py-4"
                    >
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-base font-semibold text-gray-900">
                                    {{ course.title }}
                                </p>
                                <p class="text-sm text-gray-500">
                                    Facilitator: {{ course.facilitator }}
                                </p>
                            </div>
                            <span class="text-sm font-medium text-gray-600">
                                {{ course.progress }}%
                            </span>
                        </div>
                        <div class="mt-3 h-2 rounded-full bg-gray-200">
                            <div
                                class="h-full rounded-full bg-emerald-500"
                                :style="{ width: `${course.progress}%` }"
                            />
                        </div>
                    </li>
                </ul>
            </article>

            <article class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
                <h2 class="text-lg font-semibold text-gray-900">
                    Quick Resources
                </h2>
                <ul class="mt-4 space-y-3">
                    <li
                        v-for="link in resources"
                        :key="link.label"
                    >
                        <a
                            class="flex items-center justify-between rounded-lg border border-dashed border-gray-200 px-4 py-2 text-sm font-medium text-gray-700 hover:border-emerald-200 hover:text-emerald-600"
                            :href="link.href"
                        >
                            {{ link.label }}
                            <span aria-hidden="true">→</span>
                        </a>
                    </li>
                </ul>
            </article>
        </section>

        <section class="mt-8 rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
            <h2 class="text-lg font-semibold text-gray-900">
                Assignments
            </h2>
            <ul class="mt-4 space-y-3">
                <li
                    v-for="assignment in assignments"
                    :key="assignment.item"
                    class="flex items-center justify-between rounded-lg border border-gray-100 px-4 py-3 text-sm text-gray-700"
                >
                    <div>
                        <p class="font-medium text-gray-900">
                            {{ assignment.item }}
                        </p>
                        <p class="text-xs uppercase tracking-wide text-gray-500">
                            {{ assignment.course }}
                        </p>
                    </div>
                    <span class="text-xs font-semibold text-emerald-600">
                        {{ assignment.status }}
                    </span>
                </li>
            </ul>
        </section>
    </AuthenticatedLayout>
</template>
