<script setup>
import { computed } from 'vue';
import { ChevronDown, Check, X, MoreVertical } from 'lucide-vue-next';

const props = defineProps({
    requestId: {
        type: [Number, String],
        required: true
    },
    currentStatus: {
        type: String,
        required: true
    },
    size: {
        type: String,
        default: 'md', // 'sm' or 'md'
        validator: (value) => ['sm', 'md'].includes(value)
    },
    isOpen: {
        type: Boolean,
        default: false
    },
    position: {
        type: Object,
        default: () => ({ top: 0, left: 0 })
    }
});

const emit = defineEmits(['toggle', 'status-change']);

const toggleDropdown = (event) => {
    emit('toggle', { requestId: props.requestId, event });
};

const handleStatusChange = (status) => {
    emit('status-change', { requestId: props.requestId, status });
};

const buttonClass = props.size === 'sm'
    ? 'inline-flex items-center gap-1 rounded-md border border-gray-300 bg-white px-3 py-1 text-xs text-gray-700 hover:bg-gray-50 transition-colors'
    : 'inline-flex items-center gap-2 rounded-md border border-gray-300 bg-white px-3 py-1.5 text-sm text-gray-700 hover:bg-gray-50 transition-colors';

const iconSize = props.size === 'sm' ? 'h-3 w-3' : 'h-4 w-4';
const menuWidth = props.size === 'sm' ? 'w-40' : 'w-44';
const menuItemClass = props.size === 'sm'
    ? 'w-full flex items-center gap-2 px-3 py-1.5 text-xs transition-colors'
    : 'w-full flex items-center gap-2 px-4 py-2 text-sm transition-colors';
</script>

<template>
    <div>
        <!-- Trigger Button -->
        <button
            @click="toggleDropdown"
            class="actions-dropdown-trigger"
            :class="buttonClass"
        >
            <span>Change Status</span>
            <ChevronDown :class="iconSize" />
        </button>

        <!-- Dropdown Menu -->
        <Teleport to="body">
            <div
                v-if="isOpen"
                class="actions-dropdown-menu fixed rounded-lg bg-white shadow-lg border border-gray-200 z-50"
                :class="menuWidth"
                :style="{
                    top: position.top + 'px',
                    left: position.left + 'px'
                }"
            >
                <div class="py-1">
                    <button
                        v-if="currentStatus !== 'approved'"
                        @click="handleStatusChange('approve')"
                        :class="menuItemClass"
                        class="text-green-700 hover:bg-green-50"
                    >
                        <Check :class="iconSize" />
                        Approve
                    </button>
                    <button
                        v-if="currentStatus !== 'pending'"
                        @click="handleStatusChange('pending')"
                        :class="menuItemClass"
                        class="text-yellow-700 hover:bg-yellow-50"
                    >
                        <MoreVertical :class="iconSize" />
                        Pending
                    </button>
                    <button
                        v-if="currentStatus !== 'rejected'"
                        @click="handleStatusChange('reject')"
                        :class="menuItemClass"
                        class="text-red-700 hover:bg-red-50"
                    >
                        <X :class="iconSize" />
                        Reject
                    </button>
                </div>
            </div>
        </Teleport>
    </div>
</template>
