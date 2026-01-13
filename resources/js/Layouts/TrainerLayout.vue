<script setup>
import { ref, computed, onMounted } from "vue";
import { Link, usePage } from "@inertiajs/vue3";
import {
    LayoutDashboard,
    BookOpen,
    BookCheck,
    Award,
    MessageSquare,
    Settings,
    LogOut,
    Calendar,
} from "lucide-vue-next";
import axios from "axios";
import NotificationCenter from "@/Components/NotificationCenter.vue";
import NotificationBadge from "@/Components/NotificationBadge.vue";
import LanguageSwitcher from "@/Components/LanguageSwitcher.vue";
import ProfileDropdown from "@/Components/ProfileDropdown.vue";
import Snackbar from "@/Components/Snackbar.vue";
import { useSnackbar } from "@/composables/useSnackbar";
import { trans as $t } from "laravel-vue-i18n";

const showingSidebar = ref(true);
const showingMobileMenu = ref(false);
const page = usePage();
const snackbar = useSnackbar();

// Notification Center state
const notifications = ref([
    // Example notifications - replace with actual data from API
]);

const currentPath = computed(() => page.url);

const navigationItems = computed(() => [
    {
        name: $t("My Courses"),
        path: "/trainer/courses",
        icon: BookOpen,
    },
    {
        name: $t("Sessions"),
        path: "/trainer/sessions",
        icon: Calendar,
    },
    {
        name: $t("Feedback"),
        path: "/trainer/feedback",
        icon: MessageSquare,
    },
    {
        name: $t("Setting"),
        path: "/me/profile",
        icon: Settings,
    },
]);

const isActive = (path) => {
    return currentPath.value === path;
};

const handleMarkAsRead = (notificationId) => {
    const notification = notifications.value.find(n => n.id === notificationId);
    if (notification) {
        notification.read = true;
    }
};

const handleDeleteNotification = (notificationId) => {
    notifications.value = notifications.value.filter(n => n.id !== notificationId);
};

const handleClearAllNotifications = () => {
    notifications.value = [];
};

onMounted(() => {
});

</script>

<template>
    <div class="min-h-screen bg-gray-100">
        <!-- Sidebar -->
        <aside
            :class="showingSidebar ? 'translate-x-0' : '-translate-x-full'"
            class="fixed top-0 left-0 z-20 w-64 h-screen transition-transform bg-white border-r border-gray-200"
        >
            <div class="h-full px-6 pb-4 overflow-y-auto bg-white flex flex-col">
                <div class="flex-1">
                    <Link href="/trainer/courses" class="flex pt-4 pb-14">
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
                                <span class="ml-3 flex-1">{{ item.name }}</span>
                            </Link>
                        </li>
                    </ul>
                </div>

                <!-- Logout Button -->
                <div class="mt-4 pt-4 border-t border-gray-200">
                    <Link
                        href="/logout"
                        method="post"
                        as="button"
                        class="flex items-center p-2 w-full rounded-lg group transition-colors text-[#2F837D] hover:bg-[#2F837D]/20"
                    >
                        <LogOut class="w-5 h-5 transition duration-75" />
                        <span class="ml-3">{{ $t('Logout') }}</span>
                    </Link>
                </div>
            </div>
        </aside>

        <!-- Main Content -->
        <div
            :class="showingSidebar ? 'ml-64' : 'ml-0'"
            class="transition-all duration-300"
        >
            <!-- Header with Profile -->
            <header>
                <div class="px-8 py-4 flex justify-end items-center">
                    <div class="flex items-center gap-4">
                        <!-- Notification Center -->
                        <NotificationCenter
                            :notifications="notifications"
                            @mark-as-read="handleMarkAsRead"
                            @delete="handleDeleteNotification"
                            @clear-all="handleClearAllNotifications"
                        />

                        <!-- Language Switcher -->
                        <LanguageSwitcher />

                        <!-- Profile Dropdown -->
                        <ProfileDropdown />
                    </div>
                </div>
            </header>

            <!-- Page Content -->
            <div class="p-8">
                <slot />
            </div>
        </div>

        <!-- Global Snackbar -->
        <Snackbar
            :show="snackbar.state.value.show"
            :message="snackbar.state.value.message"
            :variant="snackbar.state.value.variant"
            :action="snackbar.state.value.action"
            :position="snackbar.state.value.position"
            :duration="snackbar.state.value.duration"
            @close="snackbar.hide()"
        />
    </div>
</template>
