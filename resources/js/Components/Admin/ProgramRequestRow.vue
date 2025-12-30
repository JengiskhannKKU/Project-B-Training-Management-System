<script setup>
import { ref } from 'vue';
import { ChevronDown, ChevronUp, Eye } from 'lucide-vue-next';
import ActionsDropdown from './ActionsDropdown.vue';

const props = defineProps({
    course: {
        type: Object,
        required: true
    },
    index: {
        type: Number,
        required: true
    },
    getCategoryClasses: {
        type: Function,
        required: true
    },
    getStatusClasses: {
        type: Function,
        required: true
    },
    openDropdownId: {
        type: [Number, String, null],
        default: null
    },
    dropdownPosition: {
        type: Object,
        default: () => ({ top: 0, left: 0 })
    }
});

const emit = defineEmits(['status-change', 'toggle-sessions', 'toggle-dropdown']);

const isExpanded = ref(false);

const toggleSessions = () => {
    isExpanded.value = !isExpanded.value;
    emit('toggle-sessions', props.course.id);
};

const handleStatusChange = ({ requestId, status }) => {
    emit('status-change', { requestId, status });
};

const handleToggleDropdown = ({ requestId, event }) => {
    emit('toggle-dropdown', { requestId, event });
};
</script>

<template>
    <!-- Main Program Row -->
    <tr
        :class="[
            'transition-colors',
            index % 2 === 0 ? 'bg-white' : 'bg-gray-50',
            'hover:bg-gray-100',
        ]"
    >
        <td class="px-3 sm:px-6 py-4 whitespace-nowrap text-sm text-gray-900">
            {{ course.id }}
        </td>
        <td class="px-3 sm:px-6 py-4">
            <div class="max-w-xs lg:max-w-md">
                <div class="text-sm font-medium text-gray-900">
                    {{ course.title }}
                </div>
                <div class="text-xs text-gray-500">
                    Type: {{ course.target_type }} | Action: {{ course.action }}
                    <template v-if="course.target_type === 'session' && course.program_label">
                        • Program: {{ course.program_label }}
                    </template>
                </div>
            </div>
        </td>
        <td class="px-3 sm:px-6 py-4 whitespace-nowrap">
            <span class="text-sm text-gray-700 capitalize">{{ course.target_type }}</span>
        </td>
        <td class="px-3 sm:px-6 py-4 whitespace-nowrap">
            <span
                :class="[
                    'px-3 py-1 inline-flex text-sm leading-5 font-medium rounded-lg w-32 justify-center',
                    getCategoryClasses(course.category),
                ]"
            >
                {{ course.category }}
            </span>
        </td>
        <td class="px-3 sm:px-6 py-4 whitespace-nowrap">
            <span
                class="px-3 py-2 inline-flex items-center justify-center gap-1 text-sm leading-5 rounded-md w-32"
                :class="getStatusClasses(course.status)"
            >
                {{ course.status }}
            </span>
        </td>
        <td class="px-3 sm:px-6 py-4 whitespace-nowrap text-sm text-gray-700">
            <div class="text-sm font-medium text-gray-900">
                {{ course.requester_name }}
            </div>
            <div class="text-xs text-gray-500">
                {{ course.requester_email }}
            </div>
        </td>
        <td class="px-3 sm:px-6 py-4 whitespace-nowrap text-sm text-gray-700">
            {{ course.created_at ? course.created_at.substring(0, 10) : '—' }}
        </td>
        <td class="px-3 sm:px-6 py-4 whitespace-nowrap text-sm font-medium">
            <div class="flex items-center gap-2 flex-wrap">
                <button
                    v-if="course.sessions?.length"
                    @click="toggleSessions"
                    class="inline-flex items-center gap-1 rounded-md border border-gray-200 bg-white px-3 py-1 text-sm text-gray-700 hover:bg-gray-50"
                >
                    <ChevronDown v-if="!isExpanded" class="h-4 w-4" />
                    <ChevronUp v-else class="h-4 w-4" />
                    Sessions ({{ course.sessions.length }})
                </button>

                <!-- View Button -->
                <a
                    v-if="course.target_type !== 'session'"
                    :href="course.admin_link"
                    target="_blank"
                    rel="noopener"
                    class="inline-flex items-center gap-1 rounded-md border border-gray-300 bg-white px-3 py-1.5 text-sm text-gray-700 hover:bg-gray-50 transition-colors"
                    title="View request detail"
                >
                    <Eye class="h-4 w-4" />
                    View
                </a>

                <!-- Status Change Dropdown -->
                <ActionsDropdown
                    :request-id="course.id"
                    :current-status="course.status"
                    :is-open="openDropdownId === course.id"
                    :position="dropdownPosition"
                    size="md"
                    @toggle="handleToggleDropdown"
                    @status-change="handleStatusChange"
                />
            </div>
        </td>
    </tr>

    <!-- Expanded Sessions Row -->
    <tr v-if="course.sessions?.length && isExpanded" class="bg-gray-50">
        <td colspan="8" class="px-3 sm:px-6 py-4">
            <div class="pl-4 border-l-2 border-teal-200 space-y-3">
                <div class="text-sm font-semibold text-gray-700">
                    Session Requests
                </div>
                <div
                    v-for="session in course.sessions"
                    :key="session.id"
                    class="flex flex-col gap-3 rounded-xl border border-gray-200 bg-white px-4 py-3 sm:flex-row sm:items-center sm:justify-between"
                >
                    <div>
                        <div class="text-sm font-semibold text-gray-900">
                            {{ session.title }}
                        </div>
                        <div class="text-xs text-gray-500">
                            Request #{{ session.id }}
                            <span v-if="session.session_date">• {{ session.session_date }}</span>
                            <span v-if="session.session_time">• {{ session.session_time }}</span>
                            <span v-if="session.session_location">• {{ session.session_location }}</span>
                            <span v-if="session.session_capacity">• {{ session.session_capacity }}</span>
                        </div>
                    </div>
                    <div class="flex flex-wrap items-center gap-2">
                        <span
                            class="px-3 py-1 text-xs font-semibold rounded-md capitalize"
                            :class="getStatusClasses(session.status)"
                        >
                            {{ session.status }}
                        </span>

                        <!-- Status Change Dropdown for Sessions -->
                        <ActionsDropdown
                            :request-id="`session-${session.id}`"
                            :current-status="session.status"
                            :is-open="openDropdownId === `session-${session.id}`"
                            :position="dropdownPosition"
                            size="sm"
                            @toggle="handleToggleDropdown"
                            @status-change="(data) => handleStatusChange({ requestId: session.id, status: data.status })"
                        />
                    </div>
                </div>
            </div>
        </td>
    </tr>
</template>
