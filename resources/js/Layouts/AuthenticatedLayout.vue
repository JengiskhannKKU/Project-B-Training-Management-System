<script setup lang="ts">
import { computed, ref } from 'vue';
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import Dropdown from '@/Components/Dropdown.vue';
import DropdownLink from '@/Components/DropdownLink.vue';
import { Link, usePage } from '@inertiajs/vue3';

const showingNavigationDropdown = ref(false);

const page = usePage<PageProps>();
const roleName = computed<RoleName | null>(() => {
    const role = page.props.auth.user?.role?.name as RoleName | undefined;

    return role ?? null;
});
const currentPath = computed(() => page.url.split('?')[0]);

const roleLinks: Record<RoleName, { label: string; href: string }> = {
    admin: { label: 'Admin Portal', href: '/admin/dashboard' },
    trainer: { label: 'Trainer Portal', href: '/trainer' },
    student: { label: 'Student Portal', href: '/student' },
};

const menuLinks = computed(() => {
    const links = [];

    if (roleName.value) {
        links.push(roleLinks[roleName.value]);
    }

    links.push({ label: 'Profile', href: '/profile' });

    return links;
});

const isActive = (href: string) => currentPath.value === href;
</script>

<template>
    <div>
        <div class="min-h-screen bg-gray-100">
            <nav
                class="border-b border-gray-100 bg-white"
            >
                <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                    <div
                        class="flex flex-col gap-4 py-4 sm:flex-row sm:items-center sm:justify-between"
                    >
                        <Link href="/" class="flex items-center gap-2">
                            <ApplicationLogo
                                class="block h-9 w-auto fill-current text-gray-800"
                            />
                            <span class="text-lg font-semibold text-gray-900"
                                >Training Management</span
                            >
                        </Link>

                        <div class="flex items-center gap-4">
                            <div class="hidden sm:block text-right">
                                <p class="text-sm font-medium text-gray-900">
                                    {{ $page.props.auth.user.name }}
                                </p>
                                <p class="text-xs text-gray-500">
                                    {{ $page.props.auth.user.email }}
                                </p>
                            </div>

                            <Dropdown align="right" width="48">
                                <template #trigger>
                                    <span class="inline-flex rounded-md">
                                        <button
                                            type="button"
                                            class="inline-flex items-center rounded-md border border-transparent bg-white px-3 py-2 text-sm font-medium leading-4 text-gray-500 transition duration-150 ease-in-out hover:text-gray-700 focus:outline-none"
                                        >
                                            Menu
                                            <svg
                                                class="-me-0.5 ms-2 h-4 w-4"
                                                xmlns="http://www.w3.org/2000/svg"
                                                viewBox="0 0 20 20"
                                                fill="currentColor"
                                            >
                                                <path
                                                    fill-rule="evenodd"
                                                    d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"
                                                    clip-rule="evenodd"
                                                />
                                            </svg>
                                        </button>
                                    </span>
                                </template>

                                <template #content>
                            <DropdownLink
                                v-for="item in menuLinks"
                                :key="item.href"
                                :href="item.href"
                                :active="isActive(item.href)"
                            >
                                {{ item.label }}
                            </DropdownLink>

                                    <div class="my-2 border-t border-gray-100"></div>

                                    <DropdownLink
                                        :href="route('logout')"
                                        method="post"
                                        as="button"
                                    >
                                        Log Out
                                    </DropdownLink>
                                </template>
                            </Dropdown>

                            <button
                                class="rounded-md p-2 text-gray-500 hover:bg-gray-100 focus:bg-gray-100 focus:outline-none md:hidden"
                                @click="
                                    showingNavigationDropdown =
                                        !showingNavigationDropdown
                                "
                            >
                                <svg
                                    class="h-6 w-6"
                                    stroke="currentColor"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        :class="{
                                            hidden: showingNavigationDropdown,
                                            'inline-flex':
                                                !showingNavigationDropdown,
                                        }"
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M4 6h16M4 12h16M4 18h16"
                                    />
                                    <path
                                        :class="{
                                            hidden: !showingNavigationDropdown,
                                            'inline-flex':
                                                showingNavigationDropdown,
                                        }"
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M6 18L18 6M6 6l12 12"
                                    />
                                </svg>
                            </button>
                        </div>
                    </div>

                    <div
                        class="flex flex-col gap-3 pb-4 md:hidden"
                        v-if="showingNavigationDropdown"
                    >
                        <div class="flex flex-col gap-2">
                            <Link
                                v-for="item in menuLinks"
                                :key="item.href"
                                :href="item.href"
                                class="rounded-md px-4 py-2 text-sm font-medium transition"
                                :class="
                                    isActive(item.href)
                                        ? 'bg-indigo-600 text-white'
                                        : 'bg-gray-100 text-gray-700 hover:bg-gray-200'
                                "
                                @click="showingNavigationDropdown = false"
                            >
                                {{ item.label }}
                            </Link>
                        </div>

                        <div class="space-y-1 rounded-lg border border-gray-200 p-3">
                            <p class="text-sm font-medium text-gray-900">
                                {{ $page.props.auth.user.name }}
                            </p>
                            <p class="text-xs text-gray-500">
                                {{ $page.props.auth.user.email }}
                            </p>
                            <DropdownLink
                                :href="route('logout')"
                                method="post"
                                as="button"
                            >
                                Log Out
                            </DropdownLink>
                        </div>
                    </div>
                </div>
            </nav>

            <!-- Page Heading -->
            <header
                class="bg-white shadow"
                v-if="$slots.header"
            >
                <div class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
                    <slot name="header" />
                </div>
            </header>

            <!-- Page Content -->
            <main>
                <slot />
            </main>
        </div>
    </div>
</template>
