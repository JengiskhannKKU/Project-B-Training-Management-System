<script setup>
import { X } from "lucide-vue-next";

const props = defineProps({
    show: {
        type: Boolean,
        required: true,
    },
    editingUser: {
        type: Object,
        default: null,
    },
    editForm: {
        type: Object,
        required: true,
    },
    roleOptions: {
        type: Array,
        required: true,
    },
    statusOptions: {
        type: Array,
        required: true,
    },
    departmentOptions: {
        type: Array,
        required: true,
    },
});

const emit = defineEmits(["close", "save", "update:editForm"]);

const closeModal = () => {
    emit("close");
};

const saveUser = () => {
    emit("save");
};

const updateField = (field, value) => {
    emit("update:editForm", { ...props.editForm, [field]: value });
};
</script>

<template>
    <Teleport to="body">
        <div
            v-if="show"
            class="fixed inset-0 z-50 overflow-y-auto"
            aria-labelledby="modal-title"
            role="dialog"
            aria-modal="true"
        >
            <!-- Background overlay -->
            <div
                class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity"
                @click="closeModal"
            ></div>

            <!-- Modal panel -->
            <div class="flex min-h-full items-center justify-center p-4">
                <div
                    class="relative transform overflow-hidden rounded-[25px] bg-white shadow-xl transition-all w-full max-w-2xl"
                    @click.stop
                >
                    <!-- Header -->
                    <div class="flex items-center justify-center pt-6">
                        <h3
                            class="text-2xl font-semibold text-black"
                            id="modal-title"
                        >
                            Edit User
                        </h3>
                        <button
                            @click="closeModal"
                            class="text-white hover:text-gray-200 transition-colors"
                        >
                            <X class="h-6 w-6" />
                        </button>
                    </div>

                    <!-- Body -->
                    <div class="px-10 py-6 space-y-4 flex flex-col">
                        <!-- Name Field -->
                        <div>
                            <label
                                for="name"
                                class="block text-sm font-medium text-gray-700 mb-1"
                            >
                                Name
                            </label>
                            <input
                                id="name"
                                :value="editForm.name"
                                @input="updateField('name', $event.target.value)"
                                type="text"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#2f837d] focus:border-transparent"
                                placeholder="Enter name"
                            />
                        </div>

                        <!-- Email Field -->
                        <div>
                            <label
                                for="email"
                                class="block text-sm font-medium text-gray-700 mb-1"
                            >
                                Email
                            </label>
                            <input
                                id="email"
                                :value="editForm.email"
                                @input="updateField('email', $event.target.value)"
                                type="email"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#2f837d] focus:border-transparent"
                                placeholder="Enter email"
                            />
                        </div>

                        <!-- Phone Field -->
                        <div>
                            <label
                                for="phone"
                                class="block text-sm font-medium text-gray-700 mb-1"
                            >
                                Phone
                            </label>
                            <input
                                id="phone"
                                :value="editForm.phone"
                                @input="updateField('phone', $event.target.value)"
                                type="tel"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#2f837d] focus:border-transparent"
                                placeholder="Enter phone number"
                            />
                        </div>

                        <!-- Role and Status on same line -->
                        <div class="flex gap-4">
                            <!-- Role Dropdown -->
                            <div class="flex-1">
                                <label
                                    for="role"
                                    class="block text-sm font-medium text-gray-700 mb-1"
                                >
                                    Role
                                </label>
                                <select
                                    id="role"
                                    :value="editForm.role"
                                    @change="updateField('role', $event.target.value)"
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#2f837d] focus:border-transparent"
                                >
                                    <option value="" disabled>
                                        Select role
                                    </option>
                                    <option
                                        v-for="role in roleOptions"
                                        :key="role"
                                        :value="role"
                                    >
                                        {{ role }}
                                    </option>
                                </select>
                            </div>

                            <!-- Status Dropdown -->
                            <div class="flex-1">
                                <label
                                    for="status"
                                    class="block text-sm font-medium text-gray-700 mb-1"
                                >
                                    Status
                                </label>
                                <select
                                    id="status"
                                    :value="editForm.status"
                                    @change="updateField('status', $event.target.value)"
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#2f837d] focus:border-transparent"
                                >
                                    <option value="" disabled>
                                        Select status
                                    </option>
                                    <option
                                        v-for="status in statusOptions"
                                        :key="status"
                                        :value="status"
                                    >
                                        {{ status }}
                                    </option>
                                </select>
                            </div>
                        </div>

                        <!-- Department Field -->
                        <div>
                            <label
                                for="department"
                                class="block text-sm font-medium text-gray-700 mb-1"
                            >
                                Department
                            </label>
                            <select
                                id="department"
                                :value="editForm.department"
                                @change="updateField('department', $event.target.value)"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#2f837d] focus:border-transparent"
                            >
                                <option value="" disabled>
                                    Select department
                                </option>
                                <option
                                    v-for="dept in departmentOptions"
                                    :key="dept"
                                    :value="dept"
                                >
                                    {{ dept }}
                                </option>
                            </select>
                        </div>
                    </div>

                    <!-- Footer -->
                    <div
                        class="px-6 py-4 flex items-center justify-around gap-3 rounded-b-[25px]"
                    >
                        <button
                            @click="closeModal"
                            class="flex-1 px-6 py-2 border border-gray-300 rounded-lg text-gray-700 font-medium hover:bg-gray-50 transition-colors"
                        >
                            Cancel
                        </button>
                        <button
                            @click="saveUser"
                            class="flex-1 px-6 py-2 bg-[#3d9792] text-white rounded-lg font-medium hover:opacity-90 transition-opacity"
                        >
                            Save
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </Teleport>
</template>
