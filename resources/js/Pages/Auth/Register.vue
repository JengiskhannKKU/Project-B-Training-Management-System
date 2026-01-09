<script setup>
import { ref, watch, onMounted, computed } from "vue";
import InputError from "@/Components/InputError.vue";
import InputLabel from "@/Components/InputLabel.vue";
import PrimaryButton from "@/Components/PrimaryButton.vue";
import TextInput from "@/Components/TextInput.vue";
import LoadingSpinner from "@/Components/LoadingSpinner.vue";
import ErrorBanner from "@/Components/ErrorBanner.vue";
import LanguageSwitcher from "@/Components/LanguageSwitcher.vue";
import { Head, Link, useForm } from "@inertiajs/vue3";
import { trans } from 'laravel-vue-i18n';

const step = ref(0); // 0: Selection, 1: Account, 2: Personal
const userType = ref(null); // 'internal' | 'external'
const errorMessage = ref(null);
const localErrors = ref({});

const form = useForm({
    // Account
    email: "",
    password: "",
    password_confirmation: "",
    
    // Personal (Common)
    prefix: "",
    first_name_th: "",
    last_name_th: "",
    first_name_en: "",
    last_name_en: "",
    phone: "",
    birthdate: "",
    gender: "",
    
    // Internal Specific
    faculty: "",
    major: "",
    student_id: "",
    degree_level: "",
    
    // External Specific
    category: "", // Student, Personnel, Outsider, Other
    organization_name: "", // Maps to School, Department, Organization, Specify
    
    // Legacy support (will be populated on submit)
    name: "", 
});

const steps = computed(() => [
    { id: 0, name: trans('User Type'), isComplete: !!userType.value },
    { id: 1, name: trans('Account'), isComplete: checkAccountValidity() },
    { id: 2, name: trans('Personal'), isComplete: checkPersonalValidity() },
    { id: 3, name: userType.value === 'internal' ? trans('Education') : trans('Work/Affiliation'), isComplete: checkEducationWorkValidity() }
]);

const maxStep = ref(0);

// Auto-save logic
const STORAGE_KEY = 'registration_form_state';

onMounted(() => {
    const saved = localStorage.getItem(STORAGE_KEY);
    if (saved) {
        try {
            const parsed = JSON.parse(saved);
            // Only restore if user hasn't finished (simple check)
            if (parsed.email || parsed.first_name_en) {
                // Merge saved data into form
                Object.keys(parsed).forEach(key => {
                    if (key in form) {
                        form[key] = parsed[key];
                    }
                });
                if (parsed.userType) userType.value = parsed.userType;
                if (parsed.step) {
                    step.value = parsed.step;
                    maxStep.value = Math.max(parsed.step, parsed.maxStep || 0);
                }
            }
        } catch (e) {
            console.error("Failed to load saved form", e);
        }
    }
});

watch(
    () => [form.data(), step.value, userType.value],
    ([formData, currentStep, currentUserType]) => {
        if (currentStep > maxStep.value) {
            maxStep.value = currentStep;
        }
        
        const state = {
            ...formData,
            step: currentStep,
            userType: currentUserType,
            maxStep: maxStep.value
        };
        localStorage.setItem(STORAGE_KEY, JSON.stringify(state));
    },
    { deep: true }
);

const jumpToStep = (targetStep) => {
    // Allow navigation if:
    // 1. User type is selected (Step 0 is complete)
    // 2. OR targeting Step 0
    
    if (targetStep === step.value) return;

    if (targetStep === 0) {
        step.value = 0;
        userType.value = null;
        return;
    }

    if (userType.value) {
        step.value = targetStep;
    }
};

const clearStorage = () => {
    localStorage.removeItem(STORAGE_KEY);
};

// Validation Logic (Check Only)
const validateEmail = (email) => {
    return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
};

const validatePassword = (password) => {
    return password.length >= 8;
};

const checkAccountValidity = () => {
    if (!form.email || !validateEmail(form.email)) return false;
    if (!form.password || !validatePassword(form.password)) return false;
    if (form.password !== form.password_confirmation) return false;
    return true;
};

const checkPersonalValidity = () => {
    const requiredFields = [
        'prefix', 'first_name_th', 'last_name_th', 'first_name_en', 
        'last_name_en', 'phone', 'birthdate', 'gender'
    ];

    for (const field of requiredFields) {
        if (!form[field]) return false;
    }
    return true;
};

const checkEducationWorkValidity = () => {
    const requiredFields = [];
    
    if (userType.value === 'internal') {
        requiredFields.push('faculty', 'major', 'student_id', 'degree_level');
    } else {
        requiredFields.push('category', 'organization_name');
    }

    for (const field of requiredFields) {
        if (!form[field]) return false;
    }
    return true;
};

// Validation Logic (With Errors)
const validateStep = (currentStep) => {
    localErrors.value = {};
    let isValid = true;

    if (currentStep === 1) {
        if (!form.email || !validateEmail(form.email)) {
            localErrors.value.email = trans("Please enter a valid email address.");
            isValid = false;
        }
        if (!form.password || !validatePassword(form.password)) {
            localErrors.value.password = trans("Password must be at least 8 characters.");
            isValid = false;
        }
        if (form.password !== form.password_confirmation) {
            localErrors.value.password_confirmation = trans("Passwords do not match.");
            isValid = false;
        }
    } else if (currentStep === 2) {
        const requiredFields = [
            'prefix', 'first_name_th', 'last_name_th', 'first_name_en', 
            'last_name_en', 'phone', 'birthdate', 'gender'
        ];

        requiredFields.forEach(field => {
            if (!form[field]) {
                localErrors.value[field] = trans("This field is required.");
                isValid = false;
            }
        });
    } else if (currentStep === 3) {
         const requiredFields = [];
        
        if (userType.value === 'internal') {
            requiredFields.push('faculty', 'major', 'student_id', 'degree_level');
        } else {
            requiredFields.push('category', 'organization_name');
        }

        requiredFields.forEach(field => {
            if (!form[field]) {
                localErrors.value[field] = trans("This field is required.");
                isValid = false;
            }
        });
    }

    return isValid;
};

const nextStep = () => {
    if (step.value === 0) {
        if (userType.value) {
            step.value = 1;
        }
    } else if (step.value === 1) {
        // Optional: Validate before auto-moving, or just move
        if (validateStep(1)) {
            step.value = 2;
        }
    } else if (step.value === 2) {
        if (validateStep(2)) {
            step.value = 3;
        }
    }
};

const prevStep = () => {
    if (step.value > 0) {
        step.value--;
        if (step.value === 0) {
            userType.value = null;
        }
    }
};

const selectUserType = (type) => {
    userType.value = type;
    step.value = 1;
};

const submit = () => {
    // Validate ALL steps before submitting
    const isAccountValid = validateStep(1);
    const isPersonalValid = validateStep(2);
    const isEducationWorkValid = validateStep(3);

    if (!isAccountValid) {
        step.value = 1;
        errorMessage.value = trans("Please check the Account Information tab for errors.");
        return;
    }

    if (!isPersonalValid) {
        step.value = 2;
        errorMessage.value = trans("Please check the Personal Information tab for errors.");
        return;
    }

    if (!isEducationWorkValid) {
        step.value = 3;
        errorMessage.value = trans("Please check the Education/Work Information tab for errors.");
        return;
    }

    errorMessage.value = null;
    
    // Populate legacy name field
    form.transform((data) => ({
        ...data,
        name: `${data.first_name_en} ${data.last_name_en}`,
    })).post(route("register"), {
        onFinish: () => {
            form.reset("password", "password_confirmation");
        },
        onSuccess: () => {
            clearStorage();
        },
        onError: (errors) => {
            if (Object.keys(errors).length > 0) {
                errorMessage.value = trans("Please check the form for errors.");
            }
        },
    });
};

const organizationLabel = computed(() => {
    if (userType.value !== 'external') return '';
    switch (form.category) {
        case 'Student': return 'School Name';
        case 'Personnel': return 'Department';
        case 'Outsider': return 'Organization';
        case 'Other': return 'Please Specify';
        default: return 'Organization / School / Department';
    }
});
</script>

<template>
    <Head :title="$t('Register')" />

    <div class="relative min-h-screen bg-gray-50 flex flex-col justify-center py-12 sm:px-6 lg:px-8">
        <div class="absolute right-4 top-4 z-50">
            <LanguageSwitcher />
        </div>
        <div class="sm:mx-auto sm:w-full sm:max-w-md">
            <img
                src="/images/project_logo.png"
                alt="Project Logo"
                class="mx-auto h-16 w-auto"
            />
            <h2 class="mt-6 text-center text-3xl font-extrabold text-gray-900">
                {{ $t('Create your account') }}
            </h2>
        </div>

        <div class="mt-8 sm:mx-auto sm:w-full sm:max-w-lg"> <!-- Width increased for Step 2 -->
            <div class="bg-white py-8 px-4 shadow sm:rounded-lg sm:px-10">
                
                <!-- Stepper -->
                <nav aria-label="Progress" class="mb-8">
                    <ol role="list" class="flex items-center justify-between relative">
                        <div class="absolute left-0 top-[15px] w-full h-0.5 bg-gray-200 -z-0"></div>
                        <li v-for="(s, index) in steps" :key="s.id" class="relative z-10">
                             <button 
                                type="button"
                                @click="jumpToStep(s.id)"
                                :disabled="s.id > 0 && !userType"
                                class="group flex flex-col items-center focus:outline-none disabled:cursor-not-allowed disabled:opacity-50"
                            >
                                <span 
                                    class="h-8 w-8 rounded-full flex items-center justify-center border-2 transition-colors duration-200 bg-white"
                                    :class="[
                                        step === s.id ? 'border-[#3D9792] ring-2 ring-[#3D9792] ring-offset-2' : 
                                        s.isComplete ? 'bg-white border-[#3D9792] text-[#3D9792]' : 
                                        'border-gray-300 text-gray-500 group-hover:border-gray-400'
                                    ]"
                                >
                                    <svg v-if="s.isComplete" class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                                    </svg>
                                    <span v-else class="text-sm font-bold" :class="{ 'text-[#3D9792]': step === s.id }">{{ index + 1 }}</span>
                                </span>
                                <span 
                                    class="mt-2 text-xs font-medium uppercase tracking-wide"
                                    :class="step === s.id ? 'text-[#3D9792]' : 'text-gray-500'"
                                >
                                    {{ s.name }}
                                </span>
                            </button>
                        </li>
                    </ol>
                </nav>

                <ErrorBanner
                    :show="errorMessage !== null"
                    :message="errorMessage"
                    @dismiss="errorMessage = null"
                />

                <!-- Step 0: User Selection -->
                <div v-if="step === 0" class="space-y-4">
                    <button
                        @click="selectUserType('internal')"
                        class="w-full flex items-center justify-between p-6 border-2 border-gray-200 rounded-xl hover:border-[#3D9792] hover:bg-teal-50 transition-all duration-200 group"
                    >
                        <div class="flex items-center">
                            <div class="bg-teal-100 p-3 rounded-full group-hover:bg-[#3D9792] transition-colors">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-[#3D9792] group-hover:text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                                </svg>
                            </div>
                            <div class="ml-4 text-left">
                                <h3 class="text-lg font-medium text-gray-900">{{ $t('Internal User') }}</h3>
                                <p class="text-sm text-gray-500">{{ $t('Khon Kaen University (Student/Staff)') }}</p>
                            </div>
                        </div>
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-400 group-hover:text-[#3D9792]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                        </svg>
                    </button>

                    <button
                        @click="selectUserType('external')"
                        class="w-full flex items-center justify-between p-6 border-2 border-gray-200 rounded-xl hover:border-[#3D9792] hover:bg-teal-50 transition-all duration-200 group"
                    >
                        <div class="flex items-center">
                            <div class="bg-blue-100 p-3 rounded-full group-hover:bg-[#3D9792] transition-colors">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-blue-600 group-hover:text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9" />
                                </svg>
                            </div>
                            <div class="ml-4 text-left">
                                <h3 class="text-lg font-medium text-gray-900">{{ $t('External User') }}</h3>
                                <p class="text-sm text-gray-500">{{ $t('General Public / Other Organizations') }}</p>
                            </div>
                        </div>
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-400 group-hover:text-[#3D9792]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                        </svg>
                    </button>

                    <div class="mt-6">
                         <div class="relative">
                            <div class="absolute inset-0 flex items-center">
                                <div class="w-full border-t border-gray-300"></div>
                            </div>
                            <div class="relative flex justify-center text-sm">
                                <span class="px-2 bg-white text-gray-500">{{ $t('Already have an account?') }}</span>
                            </div>
                        </div>
                        <div class="mt-4 text-center">
                             <Link
                                :href="route('login')"
                                @click="clearStorage"
                                class="font-medium text-[#3D9792] hover:text-[#2d7773]"
                            >
                                {{ $t('Log in') }}
                            </Link>
                        </div>
                    </div>
                </div>

                <!-- Form Steps -->
                <form v-else @submit.prevent="submit" class="space-y-6">
                    
                    <!-- Step 1: Account Info -->
                    <div v-if="step === 1" class="space-y-4">
                        <div>
                            <InputLabel for="email" :value="$t('Email')" />
                            <TextInput
                                id="email"
                                type="email"
                                class="mt-1 block w-full"
                                v-model="form.email"
                                required
                                autofocus
                            />
                            <InputError :message="localErrors.email || form.errors.email" class="mt-2" />
                        </div>

                        <div>
                            <InputLabel for="password" :value="$t('Password')" />
                            <TextInput
                                id="password"
                                type="password"
                                class="mt-1 block w-full"
                                v-model="form.password"
                                required
                            />
                            <InputError :message="localErrors.password || form.errors.password" class="mt-2" />
                        </div>

                        <div>
                            <InputLabel for="password_confirmation" :value="$t('Confirm Password')" />
                            <TextInput
                                id="password_confirmation"
                                type="password"
                                class="mt-1 block w-full"
                                v-model="form.password_confirmation"
                                required
                            />
                            <InputError :message="localErrors.password_confirmation" class="mt-2" />
                        </div>
                    </div>

                    <!-- Step 2: Personal Info -->
                    <div v-if="step === 2" class="space-y-4">
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                             <!-- Prefix -->
                            <div class="col-span-1">
                                <InputLabel for="prefix" :value="$t('Prefix')" />
                                <select 
                                    id="prefix" 
                                    v-model="form.prefix"
                                    class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-[#3D9792] focus:ring-[#3D9792]"
                                >
                                    <option value="">{{ $t('Select...') }}</option>
                                    <option value="Mr.">{{ $t('Mr.') }}</option>
                                    <option value="Mrs.">{{ $t('Mrs.') }}</option>
                                    <option value="Ms.">{{ $t('Ms.') }}</option>
                                    <option value="Other">{{ $t('Other') }}</option>
                                </select>
                                <InputError :message="localErrors.prefix" class="mt-2" />
                            </div>
                            
                            <!-- Gender -->
                            <div class="col-span-1">
                                <InputLabel for="gender" :value="$t('Gender')" />
                                <select 
                                    id="gender" 
                                    v-model="form.gender"
                                    class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-[#3D9792] focus:ring-[#3D9792]"
                                >
                                    <option value="">{{ $t('Select...') }}</option>
                                    <option value="Male">{{ $t('Male') }}</option>
                                    <option value="Female">{{ $t('Female') }}</option>
                                    <option value="LGBTQ+">{{ $t('LGBTQ+') }}</option>
                                    <option value="Prefer not to say">{{ $t('Prefer not to say') }}</option>
                                </select>
                                <InputError :message="localErrors.gender" class="mt-2" />
                            </div>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <InputLabel for="first_name_th" :value="$t('First Name (TH)')" />
                                <TextInput id="first_name_th" type="text" class="mt-1 block w-full" v-model="form.first_name_th" />
                                <InputError :message="localErrors.first_name_th" class="mt-2" />
                            </div>
                            <div>
                                <InputLabel for="last_name_th" :value="$t('Last Name (TH)')" />
                                <TextInput id="last_name_th" type="text" class="mt-1 block w-full" v-model="form.last_name_th" />
                                <InputError :message="localErrors.last_name_th" class="mt-2" />
                            </div>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <InputLabel for="first_name_en" :value="$t('First Name (EN)')" />
                                <TextInput id="first_name_en" type="text" class="mt-1 block w-full" v-model="form.first_name_en" />
                                <InputError :message="localErrors.first_name_en" class="mt-2" />
                            </div>
                            <div>
                                <InputLabel for="last_name_en" :value="$t('Last Name (EN)')" />
                                <TextInput id="last_name_en" type="text" class="mt-1 block w-full" v-model="form.last_name_en" />
                                <InputError :message="localErrors.last_name_en" class="mt-2" />
                            </div>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <InputLabel for="phone" :value="$t('Phone Number')" />
                                <TextInput id="phone" type="tel" class="mt-1 block w-full" v-model="form.phone" />
                                <InputError :message="localErrors.phone" class="mt-2" />
                            </div>
                            <div>
                                <InputLabel for="birthdate" :value="$t('Date of Birth')" />
                                <TextInput id="birthdate" type="date" class="mt-1 block w-full" v-model="form.birthdate" />
                                <InputError :message="localErrors.birthdate" class="mt-2" />
                            </div>
                        </div>
                    </div>

                    <!-- Step 3: Education / Work Info -->
                    <!-- Internal Specific Fields -->
                    <div v-if="step === 3 && userType === 'internal'" class="space-y-4">
                            <h3 class="font-medium text-gray-900">{{ $t('University Information') }}</h3>
                            
                             <div>
                                <InputLabel for="faculty" :value="$t('Faculty')" />
                                <TextInput id="faculty" type="text" class="mt-1 block w-full" v-model="form.faculty" />
                                <InputError :message="localErrors.faculty" class="mt-2" />
                            </div>

                             <div>
                                <InputLabel for="major" :value="$t('Major / Department')" />
                                <TextInput id="major" type="text" class="mt-1 block w-full" v-model="form.major" />
                                <InputError :message="localErrors.major" class="mt-2" />
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <InputLabel for="student_id" :value="$t('Student / Staff ID')" />
                                    <TextInput id="student_id" type="text" class="mt-1 block w-full" v-model="form.student_id" />
                                    <InputError :message="localErrors.student_id" class="mt-2" />
                                </div>
                                <div>
                                    <InputLabel for="degree_level" :value="$t('Degree Level')" />
                                    <select 
                                        id="degree_level" 
                                        v-model="form.degree_level"
                                        class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-[#3D9792] focus:ring-[#3D9792]"
                                    >
                                        <option value="">{{ $t('Select...') }}</option>
                                        <option value="Bachelor">{{ $t('Bachelor') }}</option>
                                        <option value="Master">{{ $t('Master') }}</option>
                                        <option value="Doctoral">{{ $t('Doctoral') }}</option>
                                        <option value="Other">{{ $t('Other / Staff') }}</option>
                                    </select>
                                    <InputError :message="localErrors.degree_level" class="mt-2" />
                                </div>
                            </div>
                        </div>

                        <!-- External Specific Fields -->
                        <div v-if="step === 3 && userType === 'external'" class="space-y-4">
                            <h3 class="font-medium text-gray-900">{{ $t('Affiliation Information') }}</h3>

                            <div>
                                <InputLabel for="category" :value="$t('Category')" />
                                <select 
                                    id="category" 
                                    v-model="form.category"
                                    class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-[#3D9792] focus:ring-[#3D9792]"
                                >
                                    <option value="">{{ $t('Select...') }}</option>
                                    <option value="Student">{{ $t('Student') }}</option>
                                    <option value="Personnel">{{ $t('Personnel') }}</option>
                                    <option value="Outsider">{{ $t('Outsider') }}</option>
                                    <option value="Other">{{ $t('Other') }}</option>
                                </select>
                                <InputError :message="localErrors.category" class="mt-2" />
                            </div>

                            <div>
                                <InputLabel for="organization_name" :value="$t(organizationLabel)" />
                                <TextInput id="organization_name" type="text" class="mt-1 block w-full" v-model="form.organization_name" :placeholder="$t(organizationLabel)" />
                                <InputError :message="localErrors.organization_name" class="mt-2" />
                            </div>
                        </div>

                    <!-- Navigation Buttons -->
                    <div class="flex items-center justify-between pt-4">
                        <button
                            type="button"
                            @click="prevStep"
                            class="text-sm text-gray-600 hover:text-gray-900 font-medium"
                        >
                            <span v-if="step > 0">{{ $t('← Back') }}</span>
                            <span v-else>
                                <Link :href="route('login')" @click="clearStorage">{{ $t('Back to Login') }}</Link>
                            </span>
                        </button>

                        <PrimaryButton
                            v-if="step === 1 || step === 2"
                            type="button"
                            @click="nextStep"
                        >
                            {{ $t('Next Step') }}
                        </PrimaryButton>

                        <PrimaryButton
                            v-if="step === 3"
                            class="ml-4"
                            :class="{ 'opacity-25': form.processing }"
                            :disabled="form.processing"
                        >
                            <LoadingSpinner v-if="form.processing" size="sm" color="white" inline />
                            <span v-else>{{ $t('Complete Registration') }}</span>
                        </PrimaryButton>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>