<script setup>
import Checkbox from "@/Components/Checkbox.vue";
import InputError from "@/Components/InputError.vue";
import InputLabel from "@/Components/InputLabel.vue";
import PrimaryButton from "@/Components/PrimaryButton.vue";
import TextInput from "@/Components/TextInput.vue";
import { Head, Link, useForm } from "@inertiajs/vue3";

defineProps({
    canResetPassword: {
        type: Boolean,
    },
    status: {
        type: String,
    },
});

const form = useForm({
    email: "",
    password: "",
    remember: false,
});

const submit = () => {
    form.post(route("login"), {
        onFinish: () => form.reset("password"),
    });
};
</script>

<template>
    <Head title="Log in" />

    <div class="pt-4 pl-6 z-50 w-auto">
        <img
            src="/images/project_logo.png"
            alt="Project Logo"
            class="h-16 w-auto"
        />
    </div>

    <!-- Parent Grid Container -->
    <div class="grid min-h-screen lg:grid-cols-2 grid-cols-1 gap-0 bg-white">
        <!-- div1: Left Side (Hidden on mobile) -->
        <div
            class="hidden lg:flex items-center justify-center p-8 bg-white relative overflow-hidden"
        >
            <div class="absolute inset-0 opacity-10">
                <div class="absolute top-0 left-0 w-96 h-96 bg-white rounded-full blur-3xl -translate-x-1/2 -translate-y-1/2"></div>
                <div class="absolute bottom-0 right-0 w-96 h-96 bg-white rounded-full blur-3xl translate-x-1/2 translate-y-1/2"></div>
            </div>
        </div>

        <!-- div2: Right Side with Login Form -->
        <div
            class="flex items-center justify-center px-6 py-12 sm:px-8 lg:px-12"
        >
            <div class="w-full max-w-md space-y-8">
                <!-- Header -->
                <div class="text-center lg:text-left">
                    <h2 class="text-4xl font-bold tracking-tight text-gray-900 sm:text-5xl">
                        Log in
                    </h2>
                    <p class="mt-3 text-base text-gray-600">
                        Welcome back! Please enter your details.
                    </p>
                </div>

                <!-- Status Message -->
                <div
                    v-if="status"
                    class="rounded-lg bg-green-50 p-4 text-sm font-medium text-green-600 border border-green-200 shadow-sm"
                >
                    {{ status }}
                </div>

                <!-- Login Form -->
                <form @submit.prevent="submit" class="mt-8 space-y-6">
                    <div class="space-y-5">
                        <div>
                            <InputLabel for="email" value="Email" class="text-sm font-semibold text-gray-700" />
                            <TextInput
                                id="email"
                                type="email"
                                class="mt-2 block w-full px-4 py-3 rounded-lg border-gray-300 shadow-sm focus:border-[#3D9792] focus:ring-[#3D9792] focus:ring-2 transition-all duration-200"
                                v-model="form.email"
                                required
                                autofocus
                                autocomplete="username"
                                placeholder="Enter your email"
                            />
                            <InputError class="mt-2" :message="form.errors.email" />
                        </div>

                        <div>
                            <InputLabel for="password" value="Password" class="text-sm font-semibold text-gray-700" />
                            <TextInput
                                id="password"
                                type="password"
                                class="mt-2 block w-full px-4 py-3 rounded-lg border-gray-300 shadow-sm focus:border-[#3D9792] focus:ring-[#3D9792] focus:ring-2 transition-all duration-200"
                                v-model="form.password"
                                required
                                autocomplete="current-password"
                                placeholder="Enter your password"
                            />
                            <InputError
                                class="mt-2"
                                :message="form.errors.password"
                            />
                        </div>
                    </div>

                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                        <label class="flex items-center cursor-pointer group">
                            <Checkbox
                                name="remember"
                                v-model:checked="form.remember"
                                class="transition-all duration-200"
                            />
                            <span class="ms-2 text-sm font-medium text-gray-700 group-hover:text-gray-900 transition-colors duration-200"
                                >Remember for 30 days</span
                            >
                        </label>

                        <Link
                            v-if="canResetPassword"
                            :href="route('password.request')"
                            class="text-sm font-semibold text-[#3D9792] hover:text-[#2d7773] focus:outline-none focus:underline transition-colors duration-200"
                        >
                            Forgot password?
                        </Link>
                    </div>

                    <div class="pt-2">
                        <PrimaryButton
                            class="w-full justify-center py-3 text-base font-semibold shadow-lg hover:shadow-xl transform hover:-translate-y-0.5 transition-all duration-200"
                            :class="{ 'opacity-50 cursor-not-allowed': form.processing }"
                            :disabled="form.processing"
                        >
                            <span v-if="form.processing" class="flex items-center">
                                <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                Signing in...
                            </span>
                            <span v-else>Sign in</span>
                        </PrimaryButton>
                    </div>
                </form>

                <!-- Divider -->
                <div class="relative">
                    <div class="absolute inset-0 flex items-center">
                        <div class="w-full border-t border-gray-300"></div>
                    </div>
                    <div class="relative flex justify-center text-sm">
                        <span class="px-4 bg-white font-medium">Or continue with</span>
                    </div>
                </div>

                <!-- Google Login Button -->
                <a
                    :href="route('google.redirect')"
                    class="flex w-full items-center justify-center gap-3 rounded-lg border-2 border-gray-300 bg-white px-4 py-3 text-sm font-semibold text-gray-700 shadow-md hover:shadow-lg transition-all duration-200 hover:border-gray-400 hover:bg-gray-50 hover:-translate-y-0.5 focus:outline-none focus:ring-2 focus:ring-[#3D9792] focus:ring-offset-2"
                >
                    <svg class="h-5 w-5" viewBox="0 0 24 24">
                        <path
                            fill="#4285F4"
                            d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"
                        />
                        <path
                            fill="#34A853"
                            d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"
                        />
                        <path
                            fill="#FBBC05"
                            d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"
                        />
                        <path
                            fill="#EA4335"
                            d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"
                        />
                    </svg>
                    Continue with Google
                </a>

                <!-- Sign Up Link -->
                <p class="text-center text-sm text-gray-600">
                    Don't have an account?
                    <Link
                        :href="route('register')"
                        class="font-semibold text-[#3D9792] hover:text-[#2d7773] transition-colors duration-200"
                    >
                        Sign up
                    </Link>
                </p>
            </div>
        </div>
    </div>
</template>
