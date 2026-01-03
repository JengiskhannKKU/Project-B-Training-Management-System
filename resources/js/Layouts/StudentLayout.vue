<script setup>
import { ref, computed } from "vue";
import { Link, usePage } from "@inertiajs/vue3";
import { BookOpen, BookCheck, Award, Settings, LogOut } from "lucide-vue-next";

const showingSidebar = ref(true);
const page = usePage();

const currentUser = computed(() => page.props.auth?.user || null);

const currentPath = computed(() => page.url);

const navigationItems = [
    {
        name: "Courses",
        path: "/programs",
        icon: BookOpen,
    },
    {
        name: "My courses",
        path: "/me/enrollments",
        icon: BookCheck,
    },
    {
        name: "Certificates",
        path: "/me/certificates",
        icon: Award,
    },
    {
        name: "Setting",
        path: "/me/profile",
        icon: Settings,
    },
];

const isActive = (path) => {
    if (path === "/programs") {
        return currentPath.value.startsWith("/programs");
    }
    if (path === "/me/enrollments") {
        return currentPath.value.startsWith("/me/enrollments");
    }
    if (path === "/me/certificates") {
        return currentPath.value.startsWith("/me/certificates");
    }
    if (path === "/me/profile") {
        return currentPath.value.startsWith("/me/profile");
    }
    return currentPath.value === path;
};

const userRole = computed(() => {
    const role = page.props.auth?.user?.role?.name || page.props.auth?.user?.role;
    if (!role) return '';
    return role.toUpperCase();
});

const roleColor = computed(() => {
    const role = (page.props.auth?.user?.role?.name || page.props.auth?.user?.role || '').toLowerCase();
    if (role === 'admin') return 'text-red-600';
    if (role === 'trainer') return 'text-blue-600';
    if (role === 'student') return 'text-green-600';
    return 'text-gray-600';
});
</script>

<template>
    <div class="min-h-screen bg-gray-100">
        <aside
            :class="showingSidebar ? 'translate-x-0' : '-translate-x-full'"
            class="fixed top-0 left-0 z-20 w-64 h-screen transition-transform bg-white border-r border-gray-200"
        >
            <div class="h-full px-6 pb-4 overflow-y-auto bg-white flex flex-col">
                <div class="flex-1">
                    <Link href="/programs" class="flex pt-4 pb-14">
                        <img
                            src="/images/project_logo.png"
                            class="h-12 w-auto object-contain max-w-full"
                            alt="Logo"
                        />
                    </Link>
                    <ul class="space-y-2">
                        <li v-for="item in navigationItems" :key="item.path">
                            <Link
                                :href="item.path"
                                :class="[
                                    'flex items-center p-2 rounded-lg group transition-colors',
                                    isActive(item.path)
                                        ? 'bg-[#DAFFED] text-[#2F837D]'
                                        : 'text-gray-900 hover:bg-gray-100',
                                ]"
                            >
                                <component
                                    :is="item.icon"
                                    :class="[
                                        'w-5 h-5 transition duration-75',
                                        isActive(item.path)
                                            ? 'text-[#2F837D]'
                                            : 'text-gray-500 group-hover:text-gray-900',
                                    ]"
                                />
                                <span class="ml-3">{{ item.name }}</span>
                            </Link>
                        </li>
                    </ul>
                </div>

                <div
                    v-if="currentUser"
                    class="mt-4 pt-4 border-t border-gray-200"
                >
                    <Link
                        href="/logout"
                        method="post"
                        as="button"
                        class="flex items-center p-2 w-full rounded-lg group transition-colors text-[#2F837D] hover:bg-[#2F837D]/20"
                    >
                        <LogOut class="w-5 h-5 transition duration-75" />
                        <span class="ml-3">Logout</span>
                    </Link>
                </div>
            </div>
        </aside>

        <div
            :class="showingSidebar ? 'ml-64' : 'ml-0'"
            class="transition-all duration-300"
        >
            <header v-if="currentUser">
                <div class="px-8 py-4 flex justify-end items-center">
                    <div class="flex items-center gap-3">
                        <div class="relative">
                            <div class="w-10 h-10 rounded-full overflow-hidden bg-gray-200">
                                <img
                                    :src="`/api/me/avatar?t=${Date.now()}`"
                                    alt="Avatar"
                                    class="w-full h-full object-cover"
                                    @error="$event.target.src = '/default-avatar.svg'"
                                />
                            </div>
                            <div
                                class="absolute bottom-0 right-0 w-3 h-3 bg-green-500 border-2 border-white rounded-full"
                            ></div>
                        </div>
                        <div class="hidden sm:block text-left">
                            <p class="text-sm font-medium text-gray-900">
                                {{ currentUser?.name }}
                            </p>
                            <p class="text-xs text-gray-500">
                                {{ currentUser?.email }}
                            </p>
                            <p class="text-xs font-medium" :class="roleColor">
                                Role: {{ userRole }}
                            </p>
                        </div>
                    </div>
                </div>
            </header>

            <div class="p-8">
                <slot />
            </div>
        </div>
    </div>
</template>
