<script setup>
import { ref } from 'vue';
import { Head, usePage, useForm } from '@inertiajs/vue3';
import TrainerLayout from '@/Layouts/TrainerLayout.vue';
import {
    User,
    Lock,
    Bell,
    Camera,
    Save,
    CheckCircle2,
    Mail,
    Phone,
    Building,
    MapPin,
    Calendar,
    Briefcase
} from 'lucide-vue-next';

const user = usePage().props.auth.user;
const activeTab = ref('profile');
const showToast = ref(false);
const isSaving = ref(false);

// Profile Form
const profileForm = useForm({
    name: user.name || '',
    email: user.email || '',
    phone: '', // Would normally come from user.profile.phone
    title: 'Senior Trainer', // Mock data
    bio: 'Experienced corporate trainer with 10+ years of experience.',
    department: 'Software Engineering',
    location: 'New York, USA',
});

// Password Form
const passwordForm = useForm({
    current_password: '',
    password: '',
    password_confirmation: '',
});

// Notifications Form
const notificationForm = useForm({
    email_updates: true,
    session_reminders: true,
    student_messages: true,
    marketing: false,
});

const saveProfile = () => {
    isSaving.value = true;
    // Simulate API call since we might not have the backend ready
    setTimeout(() => {
        isSaving.value = false;
        showToast.value = true;
        setTimeout(() => showToast.value = false, 3000);
    }, 1000);
};

const updatePassword = () => {
    isSaving.value = true;
    setTimeout(() => {
        isSaving.value = false;
        showToast.value = true;
        passwordForm.reset();
        setTimeout(() => showToast.value = false, 3000);
    }, 1000);
};

const tabs = [
    { id: 'profile', label: 'My Profile', icon: User },
    { id: 'security', label: 'Security', icon: Lock },
    { id: 'notifications', label: 'Notifications', icon: Bell },
];
</script>

<template>
    <Head title="Settings - Trainer" />

    <TrainerLayout>
        <div class="min-h-screen bg-gray-50/50 pb-12">
            <!-- Header Background -->
            <div class="h-48 bg-gradient-to-r from-blue-600 to-indigo-700 w-full mb-8"></div>

            <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 -mt-24">
                <div class="flex flex-col md:flex-row gap-8">
                    
                    <!-- Sidebar / User Card -->
                    <div class="w-full md:w-80 flex-shrink-0">
                        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden sticky top-8">
                            <div class="p-6 text-center border-b border-gray-100">
                                <div class="relative inline-block">
                                    <div class="w-24 h-24 rounded-full bg-blue-100 flex items-center justify-center text-blue-600 text-3xl font-bold mx-auto border-4 border-white shadow-md">
                                        {{ user.name.charAt(0) }}
                                    </div>
                                    <button class="absolute bottom-0 right-0 p-1.5 bg-blue-600 text-white rounded-full hover:bg-blue-700 transition-colors shadow-sm">
                                        <Camera class="w-4 h-4" />
                                    </button>
                                </div>
                                <h2 class="mt-4 text-xl font-bold text-gray-900">{{ user.name }}</h2>
                                <p class="text-sm text-gray-500">{{ user.email }}</p>
                                <div class="mt-4 inline-flex px-3 py-1 rounded-full bg-blue-50 text-blue-700 text-xs font-semibold uppercase tracking-wide">
                                    Trainer
                                </div>
                            </div>
                            
                            <nav class="p-4 space-y-1">
                                <button
                                    v-for="tab in tabs"
                                    :key="tab.id"
                                    @click="activeTab = tab.id"
                                    :class="[
                                        activeTab === tab.id
                                            ? 'bg-blue-50 text-blue-700 font-medium'
                                            : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900',
                                        'w-full flex items-center px-4 py-3 rounded-xl transition-all duration-200'
                                    ]"
                                >
                                    <component :is="tab.icon" class="w-5 h-5 mr-3" :class="activeTab === tab.id ? 'text-blue-600' : 'text-gray-400'" />
                                    {{ tab.label }}
                                </button>
                            </nav>
                        </div>
                    </div>

                    <!-- Main Content -->
                    <div class="flex-1">
                        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden min-h-[500px] relative">
                            
                            <Transition
                                enter-active-class="transition duration-300 ease-out"
                                enter-from-class="transform opacity-0 translate-x-4"
                                enter-to-class="transform opacity-100 translate-x-0"
                                leave-active-class="transition duration-200 ease-in"
                                leave-from-class="transform opacity-100 translate-x-0"
                                leave-to-class="transform opacity-0 -translate-x-4"
                                mode="out-in"
                            >
                                <!-- Profile Tab -->
                                <div v-if="activeTab === 'profile'" key="profile" class="p-8">
                                    <div class="flex items-center justify-between mb-8">
                                        <div>
                                            <h3 class="text-xl font-bold text-gray-900">Profile Information</h3>
                                            <p class="text-sm text-gray-500 mt-1">Update your personal details and professional info.</p>
                                        </div>
                                        <button @click="saveProfile" :disabled="isSaving" class="btn-primary">
                                            <Save class="w-4 h-4 mr-2" />
                                            Save Changes
                                        </button>
                                    </div>

                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                                        <div class="space-y-6">
                                            <div>
                                                <label class="form-label">Full Name</label>
                                                <div class="relative">
                                                    <User class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 w-5 h-5" />
                                                    <input v-model="profileForm.name" type="text" class="form-input pl-10" />
                                                </div>
                                            </div>
                                            <div>
                                                <label class="form-label">Email Address</label>
                                                <div class="relative">
                                                    <Mail class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 w-5 h-5" />
                                                    <input v-model="profileForm.email" type="email" class="form-input pl-10 text-gray-500 bg-gray-50" disabled />
                                                </div>
                                            </div>
                                            <div>
                                                <label class="form-label">Phone Number</label>
                                                <div class="relative">
                                                    <Phone class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 w-5 h-5" />
                                                    <input v-model="profileForm.phone" type="tel" class="form-input pl-10" placeholder="+1 (555) 000-0000" />
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <div class="space-y-6">
                                            <div>
                                                <label class="form-label">Job Title</label>
                                                <div class="relative">
                                                    <Briefcase class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 w-5 h-5" />
                                                    <input v-model="profileForm.title" type="text" class="form-input pl-10" />
                                                </div>
                                            </div>
                                            <div>
                                                <label class="form-label">Department</label>
                                                <div class="relative">
                                                    <Building class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 w-5 h-5" />
                                                    <input v-model="profileForm.department" type="text" class="form-input pl-10" />
                                                </div>
                                            </div>
                                            <div>
                                                <label class="form-label">Location</label>
                                                <div class="relative">
                                                    <MapPin class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 w-5 h-5" />
                                                    <input v-model="profileForm.location" type="text" class="form-input pl-10" />
                                                </div>
                                            </div>
                                        </div>

                                        <div class="md:col-span-2">
                                            <label class="form-label">Bio</label>
                                            <textarea v-model="profileForm.bio" rows="4" class="form-input" placeholder="Tell us about yourself..."></textarea>
                                            <p class="text-xs text-gray-500 mt-2">Brief description of your expertise and background.</p>
                                        </div>
                                    </div>
                                </div>

                                <!-- Security Tab -->
                                <div v-else-if="activeTab === 'security'" key="security" class="p-8">
                                    <div class="flex items-center justify-between mb-8">
                                        <div>
                                            <h3 class="text-xl font-bold text-gray-900">Security</h3>
                                            <p class="text-sm text-gray-500 mt-1">Manage your password and account security.</p>
                                        </div>
                                    </div>

                                    <div class="max-w-2xl">
                                        <div class="space-y-6">
                                            <div>
                                                <label class="form-label">Current Password</label>
                                                <input v-model="passwordForm.current_password" type="password" class="form-input" />
                                            </div>
                                            <div>
                                                <label class="form-label">New Password</label>
                                                <input v-model="passwordForm.password" type="password" class="form-input" />
                                            </div>
                                            <div>
                                                <label class="form-label">Confirm New Password</label>
                                                <input v-model="passwordForm.password_confirmation" type="password" class="form-input" />
                                            </div>

                                            <div class="pt-4">
                                                <button @click="updatePassword" :disabled="isSaving" class="btn-primary">
                                                    Update Password
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Notifications Tab -->
                                <div v-else-if="activeTab === 'notifications'" key="notifications" class="p-8">
                                    <div class="flex items-center justify-between mb-8">
                                        <div>
                                            <h3 class="text-xl font-bold text-gray-900">Notifications</h3>
                                            <p class="text-sm text-gray-500 mt-1">Choose what updates you want to receive.</p>
                                        </div>
                                        <button @click="saveProfile" :disabled="isSaving" class="btn-outline">
                                            Save Preferences
                                        </button>
                                    </div>

                                    <div class="space-y-6 max-w-2xl">
                                        <div class="flex items-center justify-between py-4 border-b border-gray-100">
                                            <div>
                                                <p class="font-medium text-gray-900">Email Updates</p>
                                                <p class="text-sm text-gray-500">Receive daily summaries of your sessions.</p>
                                            </div>
                                            <button 
                                                @click="notificationForm.email_updates = !notificationForm.email_updates"
                                                :class="[
                                                    notificationForm.email_updates ? 'bg-blue-600' : 'bg-gray-200',
                                                    'relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2'
                                                ]"
                                            >
                                                <span
                                                    :class="[
                                                        notificationForm.email_updates ? 'translate-x-5' : 'translate-x-0',
                                                        'pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out'
                                                    ]"
                                                />
                                            </button>
                                        </div>

                                        <div class="flex items-center justify-between py-4 border-b border-gray-100">
                                            <div>
                                                <p class="font-medium text-gray-900">Session Reminders</p>
                                                <p class="text-sm text-gray-500">Get notified 1 hour before sessions start.</p>
                                            </div>
                                            <button 
                                                @click="notificationForm.session_reminders = !notificationForm.session_reminders"
                                                :class="[
                                                    notificationForm.session_reminders ? 'bg-blue-600' : 'bg-gray-200',
                                                    'relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2'
                                                ]"
                                            >
                                                <span
                                                    :class="[
                                                        notificationForm.session_reminders ? 'translate-x-5' : 'translate-x-0',
                                                        'pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out'
                                                    ]"
                                                />
                                            </button>
                                        </div>

                                        <div class="flex items-center justify-between py-4">
                                            <div>
                                                <p class="font-medium text-gray-900">Marketing & Tips</p>
                                                <p class="text-sm text-gray-500">Receive tips on how to be a better trainer.</p>
                                            </div>
                                            <button 
                                                @click="notificationForm.marketing = !notificationForm.marketing"
                                                :class="[
                                                    notificationForm.marketing ? 'bg-blue-600' : 'bg-gray-200',
                                                    'relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2'
                                                ]"
                                            >
                                                <span
                                                    :class="[
                                                        notificationForm.marketing ? 'translate-x-5' : 'translate-x-0',
                                                        'pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out'
                                                    ]"
                                                />
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </Transition>

                        </div>
                    </div>
                </div>
            </div>

            <!-- Global Toast -->
            <Transition
                enter-active-class="transform ease-out duration-300 transition"
                enter-from-class="translate-y-2 opacity-0 sm:translate-y-0 sm:translate-x-2"
                enter-to-class="translate-y-0 opacity-100 sm:translate-x-0"
                leave-active-class="transition ease-in duration-100"
                leave-from-class="opacity-100"
                leave-to-class="opacity-0"
            >
                <div v-if="showToast" class="fixed bottom-6 right-6 z-50">
                    <div class="bg-gray-900 text-white rounded-lg shadow-xl px-4 py-3 flex items-center gap-3">
                        <CheckCircle2 class="w-5 h-5 text-green-400" />
                        <span class="text-sm font-medium">Changes saved successfully</span>
                    </div>
                </div>
            </Transition>
        </div>
    </TrainerLayout>
</template>

<style scoped>
.form-label {
    @apply block text-sm font-medium text-gray-700 mb-1.5;
}
.form-input {
    @apply block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm py-2.5 transition-shadow duration-200;
}
.btn-primary {
    @apply inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-lg shadow-sm transition-all focus:ring-4 focus:ring-blue-500/20 disabled:opacity-75 disabled:cursor-not-allowed;
}
.btn-outline {
    @apply inline-flex items-center px-4 py-2 bg-white border border-gray-300 text-gray-700 text-sm font-medium rounded-lg shadow-sm hover:bg-gray-50 transition-all focus:ring-4 focus:ring-gray-200 disabled:opacity-75 disabled:cursor-not-allowed;
}
.toggle-switch {
    @apply relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2;
}
.toggle-knob {
    @apply pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out;
}
@keyframes fadeIn {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
}
.animate-fade-in {
    animation: fadeIn 0.3s ease-out forwards;
}
</style>
