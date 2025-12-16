<script setup lang="ts">
defineProps<{
    show: boolean;
    traineeForm: {
        full_name: string;
        email: string;
        phone: string;
        phone_country: string;
        role: string;
        employee_id: string;
        department: string;
        position: string;
    };
}>();

const emit = defineEmits<{
    close: [];
    submit: [];
}>();
</script>

<template>
    <div v-if="show" class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50 p-4" @click.self="emit('close')">
        <div class="w-full max-w-md rounded-lg bg-white p-4 sm:p-6 shadow-xl max-h-[90vh] overflow-y-auto">
            <h2 class="mb-2 text-center text-lg sm:text-xl font-bold text-gray-900">Add Trainee Manually</h2>
            <p class="mb-4 sm:mb-6 text-center text-xs sm:text-sm text-gray-500">Leadership Fundamentals | 24/30 (6 available)</p>

            <form @submit.prevent="emit('submit')" class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700">Full Name <span class="text-red-500">*</span></label>
                    <input v-model="traineeForm.full_name" type="text" placeholder="e.g. Alex Doe" class="mt-1 block w-full rounded-md border-gray-300 text-sm sm:text-base" required />
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">Email Address <span class="text-red-500">*</span></label>
                    <input v-model="traineeForm.email" type="email" placeholder="e.g. alex.doe@example.com" class="mt-1 block w-full rounded-md border-gray-300 text-sm sm:text-base" required />
                    <p class="mt-1 text-xs text-gray-500">Login credentials will be sent to this email</p>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">Phone number <span class="text-red-500">*</span></label>
                    <div class="mt-1 flex gap-2">
                        <select v-model="traineeForm.phone_country" class="w-16 sm:w-24 rounded-md border-gray-300 text-sm sm:text-base">
                            <option value="TH">TH</option>
                        </select>
                        <input v-model="traineeForm.phone" type="tel" placeholder="+66 (555) 000-0000" class="flex-1 rounded-md border-gray-300 text-sm sm:text-base" required />
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Roles</label>
                        <select v-model="traineeForm.role" class="mt-1 block w-full rounded-md border-gray-300 text-sm sm:text-base">
                            <option value="Student">Student</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Employee/Student ID <span class="text-gray-400 text-xs">(Optional)</span></label>
                        <input v-model="traineeForm.employee_id" type="text" placeholder="e.g. 6530401114-3" class="mt-1 block w-full rounded-md border-gray-300 text-sm sm:text-base" />
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Department</label>
                        <select v-model="traineeForm.department" class="mt-1 block w-full rounded-md border-gray-300 text-sm sm:text-base">
                            <option value="Engineering">Engineering</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Position / Job Title</label>
                        <input v-model="traineeForm.position" type="text" placeholder="e.g. Software Engineer" class="mt-1 block w-full rounded-md border-gray-300 text-sm sm:text-base" />
                    </div>
                </div>

                <div class="flex flex-col sm:flex-row gap-3 pt-4">
                    <button type="button" @click="emit('close')" class="flex-1 rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 order-2 sm:order-1">Cancel</button>
                    <button type="submit" class="flex-1 rounded-lg bg-teal-600 px-4 py-2 text-sm font-medium text-white hover:bg-teal-700 order-1 sm:order-2">Save</button>
                </div>
            </form>
        </div>
    </div>
</template>
