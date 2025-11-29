<script setup lang="ts">
import { computed } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';

const page = usePage<PageProps>();
const roleName = computed(() => page.props.auth.user?.role?.name ?? null);

const links = [
    { role: 'admin', label: 'Admin', href: '/admin' },
    { role: 'trainer', label: 'Trainer', href: '/trainer' },
    { role: 'student', label: 'Student', href: '/student' },
] as const;

const currentLink = computed(() =>
    links.find((link) => link.role === roleName.value) ?? null,
);
</script>

<template>
    <div class="min-h-screen bg-gray-50">
        <nav class="border-b border-gray-200 bg-white">
            <div
                class="mx-auto flex max-w-5xl items-center justify-between px-6 py-4"
            >
                <p class="text-lg font-semibold text-gray-900">
                    Training Management
                </p>

                <div class="flex items-center gap-4">
                    <Link
                        v-if="currentLink"
                        :href="currentLink.href"
                        class="text-sm font-medium text-gray-600 transition hover:text-gray-900"
                    >
                        {{ currentLink.label }} Portal
                    </Link>

                    <span
                        v-if="!roleName"
                        class="text-sm text-gray-400"
                    >
                        Role pending
                    </span>
                </div>
            </div>
        </nav>

        <main class="mx-auto max-w-5xl px-6 py-8">
            <slot />
        </main>
    </div>
</template>
