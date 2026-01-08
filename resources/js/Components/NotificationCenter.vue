<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { Bell, Check, Trash2, X, BellOff } from 'lucide-vue-next';

const props = defineProps({
    notifications: {
        type: Array,
        default: () => [],
        validator: (value) => {
            return value.every(notification =>
                notification.id &&
                notification.title &&
                notification.message
            );
        },
    },
    maxHeight: {
        type: String,
        default: '400px',
    },
});

const emit = defineEmits(['mark-as-read', 'delete', 'clear-all']);

const isOpen = ref(false);
const panelRef = ref(null);

const unreadCount = computed(() => {
    return props.notifications.filter(n => !n.read).length;
});

const hasNotifications = computed(() => {
    return props.notifications.length > 0;
});

const togglePanel = () => {
    isOpen.value = !isOpen.value;
};

const handleMarkAsRead = (notificationId) => {
    emit('mark-as-read', notificationId);
};

const handleDelete = (notificationId) => {
    emit('delete', notificationId);
};

const handleClearAll = () => {
    emit('clear-all');
};

const handleClickOutside = (event) => {
    if (panelRef.value && !panelRef.value.contains(event.target)) {
        isOpen.value = false;
    }
};

onMounted(() => {
    document.addEventListener('click', handleClickOutside);
});

onUnmounted(() => {
    document.removeEventListener('click', handleClickOutside);
});
</script>

<template>
    <div class="relative" ref="panelRef">
        <!-- Trigger Button -->
        <button
            @click.stop="togglePanel"
            class="relative p-2 rounded-lg hover:bg-gray-100 transition-colors duration-200"
            aria-label="Notifications"
        >
            <Bell :size="24" class="text-gray-700" />

            <!-- Badge for unread count -->
            <span
                v-if="unreadCount > 0"
                class="absolute -top-1 -right-1 h-5 w-5 rounded-full bg-red-500 text-white text-xs font-bold flex items-center justify-center border-2 border-white"
            >
                {{ unreadCount > 9 ? '9+' : unreadCount }}
            </span>
        </button>

        <!-- Dropdown Panel -->
        <Transition
            enter-active-class="transition ease-out duration-200"
            enter-from-class="opacity-0 scale-95"
            enter-to-class="opacity-100 scale-100"
            leave-active-class="transition ease-in duration-150"
            leave-from-class="opacity-100 scale-100"
            leave-to-class="opacity-0 scale-95"
        >
            <div
                v-if="isOpen"
                class="absolute right-0 mt-2 w-96 bg-white rounded-xl shadow-lg border border-gray-200 z-50 overflow-hidden"
            >
                <!-- Header -->
                <div class="px-4 py-3 border-b border-gray-200 flex items-center justify-between">
                    <h3 class="font-semibold text-gray-900">{{ $t('Notifications') }}</h3>
                    <button
                        v-if="hasNotifications"
                        @click="handleClearAll"
                        class="text-xs text-[#2f837d] hover:text-[#266a66] font-medium transition-colors duration-200"
                    >
                        {{ $t('Clear All') }}
                    </button>
                </div>

                <!-- Notification List -->
                <div
                    :style="{ maxHeight: maxHeight }"
                    class="overflow-y-auto"
                >
                    <!-- Empty State -->
                    <div
                        v-if="!hasNotifications"
                        class="flex flex-col items-center justify-center py-12 px-4"
                    >
                        <BellOff :size="48" class="text-gray-300 mb-3" />
                        <p class="text-gray-500 text-sm font-medium">{{ $t('No notifications') }}</p>
                        <p class="text-gray-400 text-xs mt-1">{{ $t("You're all caught up!") }}</p>
                    </div>

                    <!-- Notification Items -->
                    <div v-else>
                        <div
                            v-for="notification in notifications"
                            :key="notification.id"
                            :class="[
                                'px-4 py-3 border-b border-gray-100 hover:bg-gray-50 transition-colors duration-150',
                                !notification.read ? 'bg-[#DAFFED]/30' : 'bg-white'
                            ]"
                        >
                            <div class="flex items-start gap-3">
                                <!-- Unread indicator -->
                                <div class="flex-shrink-0 mt-1">
                                    <div
                                        v-if="!notification.read"
                                        class="h-2 w-2 rounded-full bg-[#2f837d]"
                                    ></div>
                                    <div
                                        v-else
                                        class="h-2 w-2 rounded-full bg-transparent"
                                    ></div>
                                </div>

                                <!-- Content -->
                                <div class="flex-1 min-w-0">
                                    <p class="font-medium text-sm text-gray-900 truncate">
                                        {{ notification.title }}
                                    </p>
                                    <p class="text-sm text-gray-600 mt-1 line-clamp-2">
                                        {{ notification.message }}
                                    </p>
                                    <p
                                        v-if="notification.time"
                                        class="text-xs text-gray-400 mt-1"
                                    >
                                        {{ notification.time }}
                                    </p>
                                </div>

                                <!-- Actions -->
                                <div class="flex-shrink-0 flex items-center gap-1">
                                    <button
                                        v-if="!notification.read"
                                        @click="handleMarkAsRead(notification.id)"
                                        class="p-1.5 rounded hover:bg-gray-200 transition-colors duration-150"
                                        :title="$t('Mark as read')"
                                    >
                                        <Check :size="16" class="text-gray-600" />
                                    </button>
                                    <button
                                        @click="handleDelete(notification.id)"
                                        class="p-1.5 rounded hover:bg-gray-200 transition-colors duration-150"
                                        :title="$t('Delete')"
                                    >
                                        <Trash2 :size="16" class="text-gray-600" />
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </Transition>
    </div>
</template>

<style scoped>
/* Custom scrollbar */
.overflow-y-auto {
    scrollbar-width: thin;
    scrollbar-color: #d1d5db #f3f4f6;
}

.overflow-y-auto::-webkit-scrollbar {
    width: 6px;
}

.overflow-y-auto::-webkit-scrollbar-track {
    background: #f3f4f6;
}

.overflow-y-auto::-webkit-scrollbar-thumb {
    background: #d1d5db;
    border-radius: 3px;
}

.overflow-y-auto::-webkit-scrollbar-thumb:hover {
    background: #9ca3af;
}
</style>
