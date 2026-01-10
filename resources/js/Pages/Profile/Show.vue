<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { Head, useForm, usePage, router } from '@inertiajs/vue3';
import { useToast } from 'vue-toastification';
import axios from 'axios';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import TrainerLayout from '@/Layouts/TrainerLayout.vue';
import TraineeLayout from '@/Layouts/TraineeLayout.vue';
import ProfileSkeleton from '@/Pages/Profile/Partials/ProfileSkeleton.vue';
import ConfirmationDialog from '@/Components/ConfirmationDialog.vue';
import ImageCropperModal from '@/Components/ImageCropperModal.vue';
import ImagePreviewModal from '@/Components/ImagePreviewModal.vue';
import {
    User,
    Shield,
    Bell,
    Globe,
    BookOpen,
    Camera,
    Edit2,
    Save,
    X,
    Mail,
    Phone,
    Calendar,
    Briefcase,
    GraduationCap,
    MapPin,
    Hash,
    Award,
    CheckCircle2,
    Clock,
    Eye,
    Upload,
    Trash2
} from 'lucide-vue-next';

const toast = useToast();
const page = usePage();

const props = defineProps({
    user: { type: Object, required: true },
});

const isLoading = ref(false);
const isEditing = ref(false);
const activeTab = ref('profile');

// User Data
const apiUser = ref({
    ...props.user,
    profile: props.user.profile || {},
    avatar_present: props.user.profile?.has_avatar || false,
});

const roleName = computed(() => apiUser.value.role?.name || 'trainee');
const LayoutComponent = computed(() => {
    if (roleName.value === 'trainer') return TrainerLayout;
    if (roleName.value === 'trainee') return TraineeLayout;
    return AdminLayout;
});

// Avatar Management
const avatarPreview = ref(null);
const avatarVersion = ref(Date.now());
const showAvatarMenu = ref(false);
const isUploadingAvatar = ref(false);

// Cropper & Preview State
const showCropper = ref(false);
const cropperFile = ref(null);
const showPreview = ref(false);

const avatarUrl = computed(() => {
    if (avatarPreview.value) return avatarPreview.value;
    if (apiUser.value.avatar_present) return `/api/me/avatar?t=${avatarVersion.value}`;
    return '/default-avatar.svg';
});

// Forms
const form = useForm({
    name: '',
    phone: '',
    date_of_birth: '',
    gender: '',
    bio: '',
    prefix: '',
    first_name: '',
    last_name: '',
    sub_category: '',
    faculty: '',
    major: '',
    student_id: '',
    degree_level: '',
    year_of_study: '',
    personnel_id: '',
    organization: '',
    department: '',
    job_position: '',
    employment_status: '',
    personnel_type: '',
    category: '',
});

const passwordForm = useForm({
    current_password: '',
    password: '',
    password_confirmation: '',
});

// Admin Mock Settings
const adminSettings = ref({
    siteName: 'Training System',
    description: 'Management Platform',
    maintenanceMode: false,
});

// Load Profile Data
const loadProfile = async () => {
    isLoading.value = true;
    try {
        const { data } = await axios.get('/api/me');
        apiUser.value = { ...data.user, profile: data.profile || {}, avatar_present: data.avatar_present };
        
        // Populate Form
        const p = apiUser.value.profile;
        form.name = data.user.name;
        form.phone = p.phone || '';
        form.date_of_birth = p.date_of_birth || '';
        form.gender = p.gender || '';
        form.bio = p.bio || '';
        
        form.prefix = p.prefix || '';
        form.first_name = p.first_name || '';
        form.last_name = p.last_name || '';
        form.sub_category = p.sub_category || '';
        form.faculty = p.faculty || '';
        form.major = p.major || '';
        form.student_id = p.student_id || '';
        form.degree_level = p.degree_level || '';
        form.year_of_study = p.year_of_study || '';
        form.personnel_id = p.personnel_id || '';
        form.organization = p.organization || '';
        form.department = p.department || '';
        form.job_position = p.job_position || '';
        form.employment_status = p.employment_status || '';
        form.personnel_type = p.personnel_type || '';
        form.category = p.category || '';

    } catch (e) {
        toast.error('Failed to load profile');
    } finally {
        isLoading.value = false;
    }
};

const updateProfile = async () => {
    form.processing = true;
    try {
        await axios.put('/api/me/profile', form.data());
        toast.success('Character profile updated!');
        isEditing.value = false;
        await loadProfile();
    } catch (e) {
        toast.error('Failed to save changes.');
    } finally {
        form.processing = false;
    }
};

const updatePassword = () => {
    passwordForm.put(route('password.update'), {
        onSuccess: () => {
            passwordForm.reset();
            toast.success('Security protocols updated.');
        },
        onError: () => toast.error('Failed to update password.'),
    });
};

// Avatar Logic
const triggerFileUpload = () => {
    document.getElementById('avatar-upload-input')?.click();
    showAvatarMenu.value = false;
};

const onFileSelect = (e) => {
    const file = e.target.files[0];
    if (file) {
        cropperFile.value = file;
        showCropper.value = true;
    }
    // Reset input so same file can be selected again if needed
    e.target.value = '';
};

const uploadCroppedAvatar = async (blob) => {
    showCropper.value = false;
    isUploadingAvatar.value = true;
    
    // Preview immediately
    avatarPreview.value = URL.createObjectURL(blob);

    const formData = new FormData();
    formData.append('avatar', blob, 'avatar.jpg');

    try {
        await axios.post('/api/me/avatar', formData, {
            headers: { 'Content-Type': 'multipart/form-data' }
        });
        toast.success('Avatar updated');
        avatarVersion.value = Date.now();
        await loadProfile();
    } catch (e) {
        toast.error('Upload failed');
        avatarPreview.value = null; // Revert on fail
    } finally {
        isUploadingAvatar.value = false;
    }
};

const deleteAvatar = async () => {
    showAvatarMenu.value = false;
    if (!confirm('Are you sure you want to delete your avatar?')) return;
    
    try {
        await axios.delete('/api/me/avatar');
        avatarVersion.value = Date.now();
        avatarPreview.value = null;
        await loadProfile();
        toast.success('Avatar deleted');
    } catch (e) {
        toast.error('Failed to delete avatar');
    }
};

const toggleAvatarMenu = () => {
    showAvatarMenu.value = !showAvatarMenu.value;
};

// Close avatar menu when clicking outside
const closeAvatarMenuOnClickOutside = (event) => {
    const avatarMenu = document.getElementById('avatar-menu');
    const editButton = document.getElementById('edit-avatar-button');
    if (avatarMenu && !avatarMenu.contains(event.target) && !editButton.contains(event.target)) {
        showAvatarMenu.value = false;
    }
};

// Formatting
const formatDate = (d) => d ? new Date(d).toLocaleDateString('en-US', { dateStyle: 'medium' }) : '-';

onMounted(() => {
    loadProfile();
    document.addEventListener('click', closeAvatarMenuOnClickOutside);
});

onUnmounted(() => {
    document.removeEventListener('click', closeAvatarMenuOnClickOutside);
});
</script>

<template>
    <Head title="Character Profile" />
    
    <component :is="LayoutComponent">
        <div class="min-h-screen bg-gray-50/50 pb-12">
            <!-- Hero Banner -->
            <div class="h-48 w-full bg-gradient-to-r from-teal-800 to-emerald-600 relative overflow-hidden">
                <div class="absolute inset-0 bg-[url('/images/pattern.png')] opacity-10"></div>
                <div class="absolute bottom-0 left-0 w-full h-16 bg-gradient-to-t from-gray-50/50 to-transparent"></div>
            </div>

            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 -mt-20 relative z-10">
                <div class="flex flex-col lg:flex-row gap-8">
                    
                    <!-- Left Column: Character Card -->
                    <div class="w-full lg:w-1/3 flex flex-col gap-6">
                        <!-- Main Card -->
                        <div class="bg-white rounded-2xl shadow-xl border border-gray-100 overflow-visible">
                            <div class="pt-8 pb-6 px-6 flex flex-col items-center text-center relative">
                                <!-- Avatar Frame -->
                                <div class="relative group">
                                    <div class="w-32 h-32 rounded-full p-1 bg-white shadow-lg ring-4 ring-teal-500/20">
                                        <img 
                                            :src="avatarUrl" 
                                            class="w-full h-full rounded-full object-cover" 
                                        />
                                    </div>
                                    
                                    <!-- Edit Button -->
                                    <button 
                                        id="edit-avatar-button"
                                        @click="toggleAvatarMenu"
                                        class="absolute bottom-0 right-0 p-2 bg-teal-600 text-white rounded-full cursor-pointer shadow-md hover:bg-teal-700 transition-colors z-20"
                                    >
                                        <Edit2 class="w-4 h-4" />
                                    </button>

                                    <!-- Dropdown Menu -->
                                    <div 
                                        v-if="showAvatarMenu" 
                                        id="avatar-menu"
                                        class="absolute top-full left-1/2 -translate-x-1/2 mt-2 w-48 bg-white rounded-xl shadow-xl border border-gray-100 py-2 z-30 overflow-hidden text-left"
                                    >
                                        <button @click="showPreview = true; showAvatarMenu = false" class="w-full px-4 py-2.5 text-sm text-gray-700 hover:bg-gray-50 flex items-center transition-colors">
                                            <Eye class="w-4 h-4 mr-3 text-teal-600" /> View Avatar
                                        </button>
                                        <button @click="triggerFileUpload" class="w-full px-4 py-2.5 text-sm text-gray-700 hover:bg-gray-50 flex items-center transition-colors">
                                            <Upload class="w-4 h-4 mr-3 text-teal-600" /> Upload New
                                        </button>
                                        <div v-if="apiUser.avatar_present" class="h-px bg-gray-100 my-1"></div>
                                        <button v-if="apiUser.avatar_present" @click="deleteAvatar" class="w-full px-4 py-2.5 text-sm text-red-600 hover:bg-red-50 flex items-center transition-colors">
                                            <Trash2 class="w-4 h-4 mr-3" /> Delete
                                        </button>
                                    </div>
                                    
                                    <input id="avatar-upload-input" type="file" class="hidden" accept="image/*" @change="onFileSelect">
                                </div>

                                <h2 class="mt-4 text-2xl font-bold text-gray-900">
                                    {{ apiUser.name }}
                                </h2>
                                <p class="text-teal-600 font-medium bg-teal-50 px-3 py-1 rounded-full text-sm mt-2 inline-flex items-center">
                                    <Shield class="w-3 h-3 mr-1.5" />
                                    {{ roleName.toUpperCase() }}
                                </p>

                                <!-- Quick Stats -->
                                <div class="grid grid-cols-2 w-full gap-4 mt-6 pt-6 border-t border-gray-100">
                                    <div class="text-center">
                                        <span class="block text-2xl font-bold text-gray-800">1</span>
                                        <span class="text-xs text-gray-500 uppercase tracking-wider">Programs</span>
                                    </div>
                                    <div class="text-center">
                                        <span class="block text-2xl font-bold text-gray-800">0</span>
                                        <span class="text-xs text-gray-500 uppercase tracking-wider">Certificates</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Menu (Vertical Tabs) -->
                        <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                            <nav class="flex flex-col">
                                <button 
                                    v-for="tab in ['profile', 'security', 'notifications']" 
                                    :key="tab"
                                    @click="activeTab = tab"
                                    :class="[
                                        'flex items-center px-6 py-4 text-sm font-medium transition-colors border-l-4',
                                        activeTab === tab 
                                            ? 'border-teal-500 bg-teal-50 text-teal-700' 
                                            : 'border-transparent text-gray-600 hover:bg-gray-50 hover:text-gray-900'
                                    ]"
                                >
                                    <User v-if="tab === 'profile'" class="w-5 h-5 mr-3" />
                                    <Shield v-else-if="tab === 'security'" class="w-5 h-5 mr-3" />
                                    <Bell v-else class="w-5 h-5 mr-3" />
                                    <span class="capitalize">{{ tab }}</span>
                                </button>
                                
                                <!-- Admin Extra Tabs -->
                                <template v-if="roleName === 'admin'">
                                    <div class="my-2 border-t border-gray-100 mx-4"></div>
                                    <button 
                                        @click="activeTab = 'general'"
                                        :class="['flex items-center px-6 py-4 text-sm font-medium transition-colors border-l-4', activeTab === 'general' ? 'border-teal-500 bg-teal-50 text-teal-700' : 'border-transparent text-gray-600 hover:bg-gray-50']">
                                        <Globe class="w-5 h-5 mr-3" /> Site Settings
                                    </button>
                                </template>
                            </nav>
                        </div>
                    </div>

                    <!-- Right Column: Details Panel -->
                    <div class="w-full lg:w-2/3">
                        
                        <!-- PROFILE TAB -->
                        <div v-if="activeTab === 'profile'" class="bg-white rounded-2xl shadow-xl border border-gray-100 overflow-hidden min-h-[600px]">
                            <!-- Header -->
                            <div class="px-8 py-6 border-b border-gray-100 flex justify-between items-center bg-gray-50/30">
                                <div>
                                    <h3 class="text-xl font-bold text-gray-900">Character Attributes</h3>
                                    <p class="text-sm text-gray-500">Personal and professional statistics.</p>
                                </div>
                                <button 
                                    @click="isEditing = !isEditing"
                                    class="flex items-center px-4 py-2 rounded-lg text-sm font-medium transition-all"
                                    :class="isEditing ? 'bg-red-50 text-red-600 hover:bg-red-100' : 'bg-teal-50 text-teal-600 hover:bg-teal-100'"
                                >
                                    <component :is="isEditing ? X : Edit2" class="w-4 h-4 mr-2" />
                                    {{ isEditing ? 'Cancel Edit' : 'Edit Stats' }}
                                </button>
                            </div>

                            <div class="p-8">
                                <form @submit.prevent="updateProfile">
                                    <!-- Bio Section -->
                                    <div class="mb-8">
                                        <label class="block text-xs font-bold text-gray-400 uppercase tracking-wider mb-2">
                                            Biography / About
                                        </label>
                                        <div v-if="!isEditing" class="p-4 bg-gray-50 rounded-xl border border-gray-100 text-gray-700 leading-relaxed italic">
                                            "{{ form.bio || 'No biography recorded.' }}"
                                        </div>
                                        <textarea v-else v-model="form.bio" rows="3" class="w-full rounded-xl border-gray-200 focus:ring-teal-500 focus:border-teal-500" placeholder="Enter your bio..."></textarea>
                                    </div>

                                    <!-- Grid Layout for Stats -->
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-x-8 gap-y-8">
                                        
                                        <!-- Column 1: Identity -->
                                        <div>
                                            <h4 class="flex items-center text-lg font-bold text-gray-900 mb-4 pb-2 border-b border-gray-100">
                                                <User class="w-5 h-5 mr-2 text-teal-500" />
                                                Identity
                                            </h4>
                                            
                                            <div class="space-y-4">
                                                <div class="stat-group">
                                                    <label>Full Name</label>
                                                    <div v-if="!isEditing" class="value">{{ form.prefix }} {{ form.first_name }} {{ form.last_name }}</div>
                                                    <div v-else class="grid grid-cols-3 gap-2">
                                                        <input v-model="form.prefix" placeholder="Prefix" class="form-input col-span-1" />
                                                        <input v-model="form.first_name" placeholder="First" class="form-input col-span-1" />
                                                        <input v-model="form.last_name" placeholder="Last" class="form-input col-span-1" />
                                                    </div>
                                                </div>

                                                <div class="stat-group">
                                                    <label>Email Address</label>
                                                    <div class="value flex items-center">
                                                        <Mail class="w-4 h-4 mr-2 text-gray-400" />
                                                        {{ apiUser.email }}
                                                    </div>
                                                </div>

                                                <div class="stat-group">
                                                    <label>Phone Contact</label>
                                                    <div v-if="!isEditing" class="value">{{ form.phone || '-' }}</div>
                                                    <input v-else v-model="form.phone" type="tel" class="form-input" />
                                                </div>

                                                <div class="stat-group">
                                                    <label>Date of Birth</label>
                                                    <div v-if="!isEditing" class="value">{{ formatDate(form.date_of_birth) }}</div>
                                                    <input v-else v-model="form.date_of_birth" type="date" class="form-input" />
                                                </div>

                                                <div class="stat-group">
                                                    <label>Gender</label>
                                                    <div v-if="!isEditing" class="value">{{ form.gender || '-' }}</div>
                                                    <select v-else v-model="form.gender" class="form-input">
                                                        <option value="Male">Male</option>
                                                        <option value="Female">Female</option>
                                                        <option value="LGBTQ+">LGBTQ+</option>
                                                        <option value="Prefer not to say">Prefer not to say</option>
                                                        <option value="Other">Other</option>
                                                    </select>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Column 2: Affiliation (Dynamic) -->
                                        <div>
                                            <h4 class="flex items-center text-lg font-bold text-gray-900 mb-4 pb-2 border-b border-gray-100">
                                                <Briefcase class="w-5 h-5 mr-2 text-teal-500" />
                                                Affiliation & Class
                                            </h4>

                                            <div class="space-y-4">
                                                <!-- Student View -->
                                                <template v-if="apiUser.profile.sub_category === 'Student'">
                                                    <div class="stat-group">
                                                        <label>Faculty</label>
                                                        <div v-if="!isEditing" class="value">{{ form.faculty }}</div>
                                                        <input v-else v-model="form.faculty" class="form-input" />
                                                    </div>
                                                    <div class="stat-group">
                                                        <label>Major</label>
                                                        <div v-if="!isEditing" class="value">{{ form.major }}</div>
                                                        <input v-else v-model="form.major" class="form-input" />
                                                    </div>
                                                    <div class="stat-group">
                                                        <label>Student ID</label>
                                                        <div v-if="!isEditing" class="value font-mono bg-gray-100 inline-block px-2 py-0.5 rounded">{{ form.student_id }}</div>
                                                        <input v-else v-model="form.student_id" class="form-input" />
                                                    </div>
                                                    <div class="stat-group">
                                                        <label>Degree Level</label>
                                                        <div v-if="!isEditing" class="value">{{ form.degree_level }}</div>
                                                        <select v-else v-model="form.degree_level" class="form-input">
                                                            <option value="Bachelor">Bachelor</option>
                                                            <option value="Master">Master</option>
                                                            <option value="Doctoral">Doctoral</option>
                                                        </select>
                                                    </div>
                                                </template>

                                                <!-- Personnel View -->
                                                <template v-else-if="apiUser.profile.sub_category === 'Personnel'">
                                                    <div class="stat-group">
                                                        <label>Organization</label>
                                                        <div v-if="!isEditing" class="value">{{ form.organization }}</div>
                                                        <input v-else v-model="form.organization" class="form-input" />
                                                    </div>
                                                    <div class="stat-group">
                                                        <label>Job Position</label>
                                                        <div v-if="!isEditing" class="value">{{ form.job_position }}</div>
                                                        <input v-else v-model="form.job_position" class="form-input" />
                                                    </div>
                                                    <div class="stat-group">
                                                        <label>Personnel Type</label>
                                                        <div v-if="!isEditing" class="value">{{ form.personnel_type }}</div>
                                                        <select v-else v-model="form.personnel_type" class="form-input">
                                                            <option value="Government Officer">Government Officer</option>
                                                            <option value="University Employee">University Employee</option>
                                                            <option value="Temporary Employee">Temporary Employee</option>
                                                        </select>
                                                    </div>
                                                </template>

                                                <!-- External/Other View -->
                                                <template v-else>
                                                    <div class="stat-group">
                                                        <label>Category</label>
                                                        <div v-if="!isEditing" class="value">{{ form.category || 'Standard User' }}</div>
                                                        <input v-else v-model="form.category" class="form-input" />
                                                    </div>
                                                    <div class="stat-group">
                                                        <label>Organization / Affiliation</label>
                                                        <div v-if="!isEditing" class="value">{{ form.organization || '-' }}</div>
                                                        <input v-else v-model="form.organization" class="form-input" />
                                                    </div>
                                                </template>
                                            </div>
                                        </div>

                                    </div>

                                    <!-- Action Buttons -->
                                    <div v-if="isEditing" class="mt-8 flex justify-end gap-3 pt-6 border-t border-gray-100">
                                        <button type="button" @click="isEditing = false" class="px-6 py-2.5 rounded-xl text-gray-600 hover:bg-gray-100 font-medium transition-colors">
                                            Cancel
                                        </button>
                                        <button type="submit" :disabled="form.processing" class="flex items-center px-6 py-2.5 rounded-xl bg-teal-600 text-white font-medium hover:bg-teal-700 shadow-lg shadow-teal-500/30 transition-all">
                                            <Save class="w-4 h-4 mr-2" />
                                            Save Attributes
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>

                        <!-- SECURITY TAB -->
                        <div v-if="activeTab === 'security'" class="bg-white rounded-2xl shadow-xl border border-gray-100 overflow-hidden">
                            <div class="px-8 py-6 border-b border-gray-100 bg-gray-50/30">
                                <h3 class="text-xl font-bold text-gray-900">Security Protocols</h3>
                                <p class="text-sm text-gray-500">Manage your password and access settings.</p>
                            </div>
                            <div class="p-8 max-w-2xl">
                                <form @submit.prevent="updatePassword" class="space-y-6">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Current Password</label>
                                        <div class="relative">
                                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                                <Lock class="h-5 w-5 text-gray-400" />
                                            </div>
                                            <input v-model="passwordForm.current_password" type="password" class="pl-10 block w-full rounded-xl border-gray-200 focus:ring-teal-500 focus:border-teal-500" />
                                        </div>
                                        <p v-if="passwordForm.errors.current_password" class="text-red-500 text-xs mt-1">{{ passwordForm.errors.current_password }}</p>
                                    </div>
                                    <div class="grid grid-cols-2 gap-4">
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-1">New Password</label>
                                            <input v-model="passwordForm.password" type="password" class="block w-full rounded-xl border-gray-200 focus:ring-teal-500 focus:border-teal-500" />
                                            <p v-if="passwordForm.errors.password" class="text-red-500 text-xs mt-1">{{ passwordForm.errors.password }}</p>
                                        </div>
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-1">Confirm New</label>
                                            <input v-model="passwordForm.password_confirmation" type="password" class="block w-full rounded-xl border-gray-200 focus:ring-teal-500 focus:border-teal-500" />
                                        </div>
                                    </div>
                                    <div class="pt-2">
                                        <button type="submit" :disabled="passwordForm.processing" class="w-full flex justify-center py-2.5 px-4 border border-transparent rounded-xl shadow-sm text-sm font-medium text-white bg-gray-900 hover:bg-gray-800 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-900 transition-colors">
                                            Update Password
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>

                    </div>
                </div>
            </div>
        </div>

        <!-- Modals -->
        <ImageCropperModal 
            :show="showCropper" 
            :imageFile="cropperFile"
            @close="showCropper = false"
            @confirm="uploadCroppedAvatar"
        />

        <ImagePreviewModal
            :show="showPreview"
            :imageUrl="avatarUrl"
            @close="showPreview = false"
        />

    </component>
</template>

<style scoped>
.stat-group {
    @apply flex flex-col;
}
.stat-group label {
    @apply text-xs font-semibold text-gray-400 uppercase tracking-wide mb-1;
}
.stat-group .value {
    @apply text-base font-medium text-gray-900;
}
.form-input {
    @apply w-full rounded-lg border-gray-200 bg-gray-50 focus:bg-white focus:ring-teal-500 focus:border-teal-500 text-sm transition-all;
}
</style>