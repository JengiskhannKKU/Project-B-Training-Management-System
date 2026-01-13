<script setup>
import { computed } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';

const page = usePage();

const user = computed(() => page.props.auth?.user);

const userRole = computed(() => {
    const role = user.value?.role?.name || user.value?.role;
    if (!role) return '';
    return role.toUpperCase();
});

const roleColor = computed(() => {
    const role = (user.value?.role?.name || user.value?.role || '').toLowerCase();
    if (role === 'admin') return 'bg-red-100 text-red-700 border-red-200';
    if (role === 'trainer') return 'bg-blue-100 text-blue-700 border-blue-200';
    if (role === 'trainee') return 'bg-green-100 text-green-700 border-green-200';
    return 'bg-gray-100 text-gray-700 border-gray-200';
});

const userType = computed(() => {
    return user.value?.type || 'Internal';
});

const userTypeColor = computed(() => {
    const type = userType.value.toLowerCase();
    if (type === 'external') return 'bg-purple-100 text-purple-700 border-purple-200';
    return 'bg-teal-100 text-teal-700 border-teal-200';
});

// Split name into first and last name
const firstName = computed(() => {
    const name = user.value?.name || '';
    const parts = name.split(' ');
    return parts[0] || '';
});

const lastName = computed(() => {
    const name = user.value?.name || '';
    const parts = name.split(' ');
    return parts.slice(1).join(' ') || '';
});
</script>

<template>
    <Link
        href="/me/profile"
        class="flex items-center gap-3 px-4 py-2.5 bg-white rounded-xl border border-gray-200 shadow-sm hover:shadow-md hover:border-[#2F837D]/30 transition-all duration-200 cursor-pointer"
    >
        <!-- Avatar with online indicator -->
        <div class="relative flex-shrink-0">
            <div class="w-12 h-12 rounded-full overflow-hidden bg-gray-200 ring-2 ring-[#2F837D]/20">
                <img
                    :src="`/api/me/avatar?t=${Date.now()}`"
                    :alt="user?.name"
                    class="w-full h-full object-cover"
                    @error="$event.target.src = '/default-avatar.svg'"
                />
            </div>
            <!-- Online indicator dot -->
            <div class="absolute bottom-0 right-0 w-3 h-3 bg-green-500 border-2 border-white rounded-full"></div>
        </div>

        <!-- User Info -->
        <div class="flex-1 min-w-0">
            <!-- Name -->
            <div class="flex items-center gap-2 mb-1">
                <h3 class="text-sm font-semibold text-gray-900 truncate">
                    <span v-if="firstName">{{ firstName }}</span>
                    <span v-if="lastName" class="ml-1">{{ lastName }}</span>
                    <span v-if="!firstName && !lastName">{{ user?.name }}</span>
                </h3>
            </div>

            <!-- Email -->
            <p class="text-xs text-gray-600 truncate mb-1.5">
                {{ user?.email }}
            </p>

            <!-- Role and Type Badges -->
            <div class="flex items-center gap-2">
                <!-- Role Badge -->
                <span
                    :class="roleColor"
                    class="inline-flex items-center px-2 py-0.5 rounded-md text-xs font-medium border"
                >
                    {{ userRole }}
                </span>

                <!-- User Type Badge -->
                <span
                    :class="userTypeColor"
                    class="inline-flex items-center px-2 py-0.5 rounded-md text-xs font-medium border"
                >
                    {{ userType }}
                </span>
            </div>
        </div>
    </Link>
</template>
