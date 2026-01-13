<script setup lang="ts">
import { computed, ref } from 'vue';
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import ProfileDropdown from '@/Components/ProfileDropdown.vue';
import { Link, usePage } from '@inertiajs/vue3';

const showingNavigationDropdown = ref(false);

const page = usePage<PageProps>();
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
                            <!-- Profile Dropdown -->
                            <ProfileDropdown />

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
