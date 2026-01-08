<script setup>
import { ref, computed, onMounted, onUnmounted } from "vue";
import { Link, usePage } from "@inertiajs/vue3";
import {
    LayoutDashboard,
    Users,
    Tags,
    BookOpen,
    BookCheck,
    MessageSquare,
    Settings,
    LogOut,
    FileCheck,
    FileBadge,
    Award,
    Calendar,
} from "lucide-vue-next";
import axios from "axios";
import NotificationCenter from "@/Components/NotificationCenter.vue";
import NotificationBadge from "@/Components/NotificationBadge.vue";
import LanguageSwitcher from "@/Components/LanguageSwitcher.vue";
import Snackbar from "@/Components/Snackbar.vue";
import { useSnackbar } from "@/composables/useSnackbar";

const showingSidebar = ref(true);
const showingMobileMenu = ref(false);
const showingProfileDropdown = ref(false);
const pendingRequestCount = ref(0);
const page = usePage();
const snackbar = useSnackbar();

// Notification Center state
const notifications = ref([
    // Example notifications - replace with actual data from API
    // { id: 1, title: 'New Request', message: 'You have a new program request', time: '5 min ago', read: false, type: 'info' },
]);

const currentPath = computed(() => page.url);

const navigationItems = [
    {
        name: "Dashboard",
        path: "/admin/dashboard",
        icon: LayoutDashboard,
    },
    {
        name: "Users",
        path: "/admin/users",
        icon: Users,
    },
    {
        name: "Categories",
        path: "/admin/categories",
        icon: Tags,
    },
    {
        name: "Requests",
        path: "/admin/requests",
        icon: FileCheck,
    },
    {
        name: "Certificate Templates",
        path: "/admin/certificate-templates",
        icon: Award,
    },
    {
        name: "Certificate Requests",
        path: "/admin/certificate-requests",
        icon: FileBadge,
    },
    {
        name: "All Courses",
        path: "/admin/my-courses",
        icon: BookOpen,
    },
    {
        name: "Sessions",
        path: "/admin/sessions",
        icon: Calendar,
    },
    {
        name: "Attendance",
        path: "/admin/attendance",
        icon: BookCheck,
    },
    {
        name: "Feedback",
        path: "/admin/feedback",
        icon: MessageSquare,
    },
    {
        name: "Settings",
        path: "/me/profile",
        icon: Settings,
    },
];

const isActive = (path) => {
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

const fetchPendingRequestCount = async () => {
    try {
        const response = await axios.get('/api/admin/requests/pending-count');
        if (response.data?.data?.count !== undefined) {
            pendingRequestCount.value = response.data.data.count;
        }
    } catch (error) {
        console.error('Error fetching pending request count:', error);
        // Silently fail - this is just a badge count
    }
};

const handleMarkAsRead = (notificationId) => {
    const notification = notifications.value.find(n => n.id === notificationId);
    if (notification) {
        notification.read = true;
        // TODO: Make API call to mark as read
    }
};

const handleDeleteNotification = (notificationId) => {
    notifications.value = notifications.value.filter(n => n.id !== notificationId);
    // TODO: Make API call to delete notification
};

const handleClearAllNotifications = () => {
    notifications.value = [];
    // TODO: Make API call to clear all notifications
};

onMounted(() => {
    fetchPendingRequestCount();
    // Refresh count every 30 seconds
    const intervalId = setInterval(fetchPendingRequestCount, 30000);

    // Listen for manual refresh events from the Requests page
    window.addEventListener('refresh-pending-count', fetchPendingRequestCount);

    onUnmounted(() => {
        clearInterval(intervalId);
        window.removeEventListener('refresh-pending-count', fetchPendingRequestCount);
    });
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
                    <Link href="/admin/dashboard" class="flex pt-4 pb-14">
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
                                <span class="ml-3 flex-1">{{ $t(item.name) }}</span>
                                <NotificationBadge
                                    v-if="item.name === 'Requests' && pendingRequestCount > 0"
                                    :count="pendingRequestCount"
                                    color="danger"
                                    size="sm"
                                    class="ml-2"
                                />
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

                        <!-- Avatar with online indicator -->
                        <div class="relative">
                            <div class="w-10 h-10 rounded-full overflow-hidden bg-gray-200">
                                <img
                                    :src="`/api/me/avatar?t=${Date.now()}`"
                                    alt="Avatar"
                                    class="w-full h-full object-cover"
                                    @error="$event.target.src = '/default-avatar.svg'"
                                />
                            </div>
                            <!-- Online indicator dot -->
                            <div
                                class="absolute bottom-0 right-0 w-3 h-3 bg-green-500 border-2 border-white rounded-full"
                            ></div>
                        </div>
                        <div class="hidden sm:block text-left">
                            <p class="text-sm font-medium text-gray-900">
                                {{ page.props.auth?.user?.name }}
                            </p>
                            <p class="text-xs text-gray-500">
                                {{ page.props.auth?.user?.email }}
                            </p>
                            <p class="text-xs font-medium" :class="roleColor">
                                {{ $t('Role') }}: {{ userRole }}
                            </p>
                        </div>
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
