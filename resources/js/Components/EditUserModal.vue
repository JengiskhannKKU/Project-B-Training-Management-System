<script setup>
import { ref, watch } from "vue";
import { X } from "lucide-vue-next";
import Swal from "sweetalert2";

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
    departmentOptions: {
        type: Array,
        required: true,
    },
});

const emit = defineEmits(["close", "save", "update:editForm"]);

const activeTab = ref("account");
const tabs = [
    { id: "account", label: "Account" },
    { id: "personal", label: "Personal" },
    { id: "education_work", label: "Education & Work" },
];

// Reset tab when modal opens
watch(
    () => props.show,
    (newVal) => {
        if (newVal) {
            activeTab.value = "account";
        }
    }
);

const closeModal = () => {
    emit("close");
};

const saveUser = () => {
    Swal.fire({
        title: "Are you sure?",
        text: "You are about to update this user's information.",
        icon: "warning",
        showCancelButton: true,
        confirmButtonColor: "#3d9792",
        cancelButtonColor: "#d33",
        confirmButtonText: "Yes, update it!",
    }).then((result) => {
        if (result.isConfirmed) {
            emit("save");
        }
    });
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
                    class="relative transform overflow-hidden rounded-[25px] bg-white shadow-xl transition-all w-full max-w-4xl max-h-[90vh] flex flex-col"
                    @click.stop
                >
                    <!-- Header -->
                    <div class="flex items-center justify-between px-8 pt-6 pb-4 border-b border-gray-100">
                        <h3
                            class="text-2xl font-semibold text-black"
                            id="modal-title"
                        >
                            Edit User
                        </h3>
                        <button
                            @click="closeModal"
                            class="text-gray-400 hover:text-gray-600 transition-colors"
                        >
                            <X class="h-6 w-6" />
                        </button>
                    </div>

                    <!-- Tabs -->
                    <div class="px-8 pt-2 border-b border-gray-100 bg-gray-50/50">
                        <nav class="-mb-px flex space-x-8" aria-label="Tabs">
                            <button
                                v-for="tab in tabs"
                                :key="tab.id"
                                @click="activeTab = tab.id"
                                :class="[
                                    activeTab === tab.id
                                        ? 'border-[#2f837d] text-[#2f837d]'
                                        : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300',
                                    'whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm transition-colors'
                                ]"
                            >
                                {{ tab.label }}
                            </button>
                        </nav>
                    </div>

                    <!-- Body (Scrollable) -->
                    <div class="px-8 py-6 overflow-y-auto flex-1 custom-scrollbar">
                        
                        <!-- Account Tab -->
                        <div v-show="activeTab === 'account'" class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <!-- Display Name -->
                            <div class="col-span-2">
                                <label class="block text-sm font-medium text-gray-700 mb-1">Display Name (System)</label>
                                <input
                                    :value="editForm.name"
                                    @input="updateField('name', $event.target.value)"
                                    type="text"
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#2f837d] focus:border-transparent"
                                />
                            </div>
                            
                            <!-- Email -->
                            <div class="col-span-2 md:col-span-1">
                                <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                                <input
                                    :value="editForm.email"
                                    @input="updateField('email', $event.target.value)"
                                    type="email"
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#2f837d] focus:border-transparent"
                                />
                            </div>

                            <!-- Phone (Account) -->
                            <div class="col-span-2 md:col-span-1">
                                <label class="block text-sm font-medium text-gray-700 mb-1">Phone</label>
                                <input
                                    :value="editForm.phone"
                                    @input="updateField('phone', $event.target.value)"
                                    type="tel"
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#2f837d] focus:border-transparent"
                                />
                            </div>

                            <!-- Role -->
                            <div class="col-span-2 md:col-span-1">
                                <label class="block text-sm font-medium text-gray-700 mb-1">Role</label>
                                <select
                                    :value="editForm.role"
                                    @change="updateField('role', $event.target.value)"
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#2f837d] focus:border-transparent"
                                >
                                    <option v-for="role in roleOptions" :key="role" :value="role">{{ role }}</option>
                                </select>
                            </div>

                            <!-- Status -->
                            <div class="col-span-2 md:col-span-1">
                                <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                                <select
                                    :value="editForm.status"
                                    @change="updateField('status', $event.target.value)"
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#2f837d] focus:border-transparent"
                                >
                                    <option value="Active">Active</option>
                                    <option value="Inactive">Inactive</option>
                                </select>
                            </div>

                             <!-- User Category -->
                            <div class="col-span-2">
                                <label class="block text-sm font-medium text-gray-700 mb-1">User Category</label>
                                <select
                                    :value="editForm.category"
                                    @change="updateField('category', $event.target.value)"
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#2f837d] focus:border-transparent"
                                >
                                    <option value="">Select Category</option>
                                    <option value="Student">Student (Internal)</option>
                                    <option value="Personnel">Personnel (Internal)</option>
                                    <option value="Outsider">Outsider (External)</option>
                                    <option value="Other">Other</option>
                                </select>
                                <p class="mt-1 text-xs text-gray-500">Changing this affects which fields are shown in reports.</p>
                            </div>
                        </div>

                        <!-- Personal Tab -->
                        <div v-show="activeTab === 'personal'" class="grid grid-cols-1 md:grid-cols-3 gap-6">
                            <!-- Prefix -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Prefix</label>
                                <input
                                    :value="editForm.prefix"
                                    @input="updateField('prefix', $event.target.value)"
                                    type="text"
                                    placeholder="e.g. Mr., Ms., Dr."
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#2f837d] focus:border-transparent"
                                />
                            </div>

                            <!-- First Name -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">First Name</label>
                                <input
                                    :value="editForm.first_name"
                                    @input="updateField('first_name', $event.target.value)"
                                    type="text"
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#2f837d] focus:border-transparent"
                                />
                            </div>

                            <!-- Last Name -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Last Name</label>
                                <input
                                    :value="editForm.last_name"
                                    @input="updateField('last_name', $event.target.value)"
                                    type="text"
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#2f837d] focus:border-transparent"
                                />
                            </div>

                            <!-- Date of Birth -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Date of Birth</label>
                                <input
                                    :value="editForm.date_of_birth"
                                    @input="updateField('date_of_birth', $event.target.value)"
                                    type="date"
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#2f837d] focus:border-transparent"
                                />
                            </div>

                            <!-- Gender -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Gender</label>
                                <select
                                    :value="editForm.gender"
                                    @change="updateField('gender', $event.target.value)"
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#2f837d] focus:border-transparent"
                                >
                                    <option value="">Select Gender</option>
                                    <option value="Male">Male</option>
                                    <option value="Female">Female</option>
                                    <option value="Other">Other</option>
                                </select>
                            </div>

                            <!-- Bio -->
                            <div class="col-span-3">
                                <label class="block text-sm font-medium text-gray-700 mb-1">Bio</label>
                                <textarea
                                    :value="editForm.bio"
                                    @input="updateField('bio', $event.target.value)"
                                    rows="3"
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#2f837d] focus:border-transparent"
                                ></textarea>
                            </div>
                        </div>

                        <!-- Education & Work Tab -->
                        <div v-show="activeTab === 'education_work'" class="space-y-6">
                            
                            <!-- Student Section -->
                            <div class="bg-blue-50/50 p-4 rounded-xl border border-blue-100">
                                <h4 class="text-sm font-bold text-blue-800 uppercase tracking-wider mb-4">Student Info</h4>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Student ID</label>
                                        <input
                                            :value="editForm.student_id"
                                            @input="updateField('student_id', $event.target.value)"
                                            type="text"
                                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#2f837d] focus:border-transparent"
                                        />
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Degree Level</label>
                                        <select
                                            :value="editForm.degree_level"
                                            @change="updateField('degree_level', $event.target.value)"
                                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#2f837d] focus:border-transparent"
                                        >
                                            <option value="">Select Level</option>
                                            <option value="Bachelor">Bachelor</option>
                                            <option value="Master">Master</option>
                                            <option value="Doctoral">Doctoral</option>
                                        </select>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Faculty</label>
                                        <input
                                            :value="editForm.faculty"
                                            @input="updateField('faculty', $event.target.value)"
                                            type="text"
                                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#2f837d] focus:border-transparent"
                                        />
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Major</label>
                                        <input
                                            :value="editForm.major"
                                            @input="updateField('major', $event.target.value)"
                                            type="text"
                                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#2f837d] focus:border-transparent"
                                        />
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Year of Study</label>
                                        <input
                                            :value="editForm.year_of_study"
                                            @input="updateField('year_of_study', $event.target.value)"
                                            type="text"
                                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#2f837d] focus:border-transparent"
                                        />
                                    </div>
                                </div>
                            </div>

                            <!-- Personnel Section -->
                            <div class="bg-purple-50/50 p-4 rounded-xl border border-purple-100">
                                <h4 class="text-sm font-bold text-purple-800 uppercase tracking-wider mb-4">Personnel Info</h4>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Personnel ID</label>
                                        <input
                                            :value="editForm.personnel_id"
                                            @input="updateField('personnel_id', $event.target.value)"
                                            type="text"
                                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#2f837d] focus:border-transparent"
                                        />
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Department</label>
                                        <input
                                            :value="editForm.department"
                                            @input="updateField('department', $event.target.value)"
                                            type="text"
                                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#2f837d] focus:border-transparent"
                                        />
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Job Position</label>
                                        <input
                                            :value="editForm.job_position"
                                            @input="updateField('job_position', $event.target.value)"
                                            type="text"
                                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#2f837d] focus:border-transparent"
                                        />
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Employment Status</label>
                                        <input
                                            :value="editForm.employment_status"
                                            @input="updateField('employment_status', $event.target.value)"
                                            type="text"
                                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#2f837d] focus:border-transparent"
                                        />
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Personnel Type</label>
                                        <select
                                            :value="editForm.personnel_type"
                                            @change="updateField('personnel_type', $event.target.value)"
                                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#2f837d] focus:border-transparent"
                                        >
                                            <option value="">Select Type</option>
                                            <option value="Academic">Academic</option>
                                            <option value="Support">Support</option>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <!-- External Section -->
                            <div class="bg-orange-50/50 p-4 rounded-xl border border-orange-100">
                                <h4 class="text-sm font-bold text-orange-800 uppercase tracking-wider mb-4">External / Organization Info</h4>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div class="col-span-2">
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Organization</label>
                                        <input
                                            :value="editForm.organization"
                                            @input="updateField('organization', $event.target.value)"
                                            type="text"
                                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#2f837d] focus:border-transparent"
                                        />
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Sub Category</label>
                                         <input
                                            :value="editForm.sub_category"
                                            @input="updateField('sub_category', $event.target.value)"
                                            type="text"
                                            placeholder="e.g. Alumni, Visitor"
                                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#2f837d] focus:border-transparent"
                                        />
                                    </div>
                                </div>
                            </div>

                        </div>
                    </div>

                    <!-- Footer -->
                    <div
                        class="px-8 py-4 flex items-center justify-end gap-3 border-t border-gray-100 bg-gray-50"
                    >
                        <button
                            @click="closeModal"
                            class="px-6 py-2 border border-gray-300 rounded-lg text-gray-700 font-medium hover:bg-gray-100 transition-colors"
                        >
                            Cancel
                        </button>
                        <button
                            @click="saveUser"
                            class="px-6 py-2 bg-[#3d9792] text-white rounded-lg font-medium hover:opacity-90 transition-opacity shadow-sm"
                        >
                            Update User
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </Teleport>
</template>

<style scoped>
.custom-scrollbar::-webkit-scrollbar {
    width: 6px;
}
.custom-scrollbar::-webkit-scrollbar-track {
    background: #f1f1f1;
}
.custom-scrollbar::-webkit-scrollbar-thumb {
    background: #cbd5e1;
    border-radius: 3px;
}
.custom-scrollbar::-webkit-scrollbar-thumb:hover {
    background: #94a3b8;
}
</style>
