<script setup>
import { ref, computed } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import { Search, CheckCircle, XCircle, AlertCircle, Award, Calendar, Building2, User } from 'lucide-vue-next';
import axios from 'axios';

const certificateCode = ref('');
const isVerifying = ref(false);
const verificationResult = ref(null);
const errorMessage = ref('');

const hasResult = computed(() => verificationResult.value !== null);
const isValid = computed(() => verificationResult.value?.is_valid === true);
const isRevoked = computed(() => verificationResult.value?.status === 'revoked');

const verifyCertificate = async () => {
    if (!certificateCode.value.trim()) {
        errorMessage.value = 'Please enter a certificate code';
        return;
    }

    isVerifying.value = true;
    errorMessage.value = '';
    verificationResult.value = null;

    try {
        const response = await axios.get(`/api/verify/${encodeURIComponent(certificateCode.value.trim())}`);
        verificationResult.value = response.data.data;
    } catch (error) {
        if (error.response?.status === 404) {
            verificationResult.value = {
                certificate_code: certificateCode.value.trim(),
                is_valid: false,
                status: 'not_found',
            };
        } else {
            errorMessage.value = error.response?.data?.message || 'Failed to verify certificate. Please try again.';
        }
    } finally {
        isVerifying.value = false;
    }
};

const handleKeyPress = (event) => {
    if (event.key === 'Enter') {
        verifyCertificate();
    }
};

const reset = () => {
    certificateCode.value = '';
    verificationResult.value = null;
    errorMessage.value = '';
};

const formatDate = (dateString) => {
    if (!dateString) return '—';
    const date = new Date(dateString);
    return date.toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'long',
        day: 'numeric'
    });
};

const statusBadgeClass = computed(() => {
    if (!verificationResult.value) return '';

    if (isRevoked.value) {
        return 'bg-red-100 text-red-800 border-red-200';
    }

    if (isValid.value) {
        return 'bg-emerald-100 text-emerald-800 border-emerald-200';
    }

    return 'bg-gray-100 text-gray-800 border-gray-200';
});

const statusIcon = computed(() => {
    if (!verificationResult.value) return null;

    if (isRevoked.value) return XCircle;
    if (isValid.value) return CheckCircle;
    return XCircle;
});

const statusText = computed(() => {
    if (!verificationResult.value) return '';

    if (verificationResult.value.status === 'not_found') {
        return 'Certificate Not Found';
    }

    if (isRevoked.value) {
        return 'Certificate Revoked';
    }

    if (isValid.value) {
        return 'Valid Certificate';
    }

    return 'Invalid Certificate';
});

const statusMessage = computed(() => {
    if (!verificationResult.value) return '';

    if (verificationResult.value.status === 'not_found') {
        return 'No certificate found with this code. Please verify the certificate code and try again.';
    }

    if (isRevoked.value) {
        return `This certificate was revoked on ${formatDate(verificationResult.value.revoked_at)}.`;
    }

    if (isValid.value) {
        return 'This certificate is authentic and has been verified.';
    }

    return 'This certificate is not valid.';
});
</script>

<template>
    <Head title="Verify Certificate" />

    <div class="min-h-screen bg-gradient-to-br from-blue-50 via-white to-teal-50">
        <!-- Header -->
        <header class="border-b border-gray-200 bg-white/80 backdrop-blur-sm">
            <div class="mx-auto max-w-7xl px-4 py-4 sm:px-6 lg:px-8">
                <div class="flex items-center justify-between">
                    <Link href="/" class="flex items-center gap-3">
                        <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-gradient-to-br from-[#2f837d] to-[#26685f] text-white">
                            <Award class="h-6 w-6" />
                        </div>
                        <div>
                            <div class="text-lg font-bold text-gray-900">Certificate Verification</div>
                            <div class="text-xs text-gray-500">Training Management System</div>
                        </div>
                    </Link>
                    <Link
                        href="/"
                        class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 transition-colors hover:bg-gray-50"
                    >
                        Back to Home
                    </Link>
                </div>
            </div>
        </header>

        <!-- Main Content -->
        <main class="mx-auto max-w-4xl px-4 py-12 sm:px-6 lg:px-8">
            <div class="text-center mb-12">
                <h1 class="text-4xl font-bold text-gray-900 mb-3">Verify Certificate</h1>
                <p class="text-lg text-gray-600">
                    Enter the certificate code to verify its authenticity
                </p>
            </div>

            <!-- Search Box -->
            <div class="mx-auto max-w-2xl mb-8">
                <div class="rounded-2xl border-2 border-gray-200 bg-white p-6 shadow-lg">
                    <label for="certificate-code" class="block text-sm font-semibold text-gray-700 mb-3">
                        Certificate Code
                    </label>
                    <div class="flex gap-3">
                        <div class="relative flex-1">
                            <input
                                id="certificate-code"
                                v-model="certificateCode"
                                type="text"
                                placeholder="e.g., CERT-2026-0001"
                                class="w-full rounded-lg border-2 border-gray-300 px-4 py-3 text-base focus:border-[#2f837d] focus:outline-none focus:ring-2 focus:ring-[#2f837d]/20 transition-all"
                                :disabled="isVerifying"
                                @keypress="handleKeyPress"
                            />
                        </div>
                        <button
                            @click="verifyCertificate"
                            :disabled="isVerifying || !certificateCode.trim()"
                            class="flex items-center gap-2 rounded-lg bg-gradient-to-r from-[#2f837d] to-[#26685f] px-6 py-3 font-semibold text-white transition-all hover:shadow-lg disabled:opacity-50 disabled:cursor-not-allowed"
                        >
                            <Search class="h-5 w-5" />
                            <span>{{ isVerifying ? 'Verifying...' : 'Verify' }}</span>
                        </button>
                    </div>

                    <!-- Error Message -->
                    <div v-if="errorMessage" class="mt-4 flex items-start gap-2 rounded-lg bg-red-50 border border-red-200 p-3 text-sm text-red-700">
                        <AlertCircle class="h-5 w-5 flex-shrink-0 mt-0.5" />
                        <span>{{ errorMessage }}</span>
                    </div>
                </div>
            </div>

            <!-- Verification Result -->
            <div v-if="hasResult" class="mx-auto max-w-2xl">
                <div class="rounded-2xl border-2 bg-white shadow-xl overflow-hidden" :class="statusBadgeClass">
                    <!-- Status Header -->
                    <div class="p-6 text-center border-b" :class="statusBadgeClass">
                        <component :is="statusIcon" class="mx-auto h-16 w-16 mb-3" />
                        <h2 class="text-2xl font-bold mb-2">{{ statusText }}</h2>
                        <p class="text-sm opacity-90">{{ statusMessage }}</p>
                    </div>

                    <!-- Certificate Details -->
                    <div v-if="isValid && !isRevoked" class="p-6 bg-white">
                        <div class="space-y-4">
                            <!-- Certificate Code -->
                            <div class="flex items-start gap-3 pb-4 border-b border-gray-100">
                                <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-blue-50 text-blue-600 flex-shrink-0">
                                    <Award class="h-5 w-5" />
                                </div>
                                <div class="flex-1 min-w-0">
                                    <div class="text-xs font-semibold text-gray-500 uppercase mb-1">Certificate Code</div>
                                    <div class="text-lg font-mono font-bold text-gray-900">
                                        {{ verificationResult.certificate_code }}
                                    </div>
                                </div>
                            </div>

                            <!-- Holder Name -->
                            <div class="flex items-start gap-3 pb-4 border-b border-gray-100">
                                <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-purple-50 text-purple-600 flex-shrink-0">
                                    <User class="h-5 w-5" />
                                </div>
                                <div class="flex-1 min-w-0">
                                    <div class="text-xs font-semibold text-gray-500 uppercase mb-1">Certificate Holder</div>
                                    <div class="text-base font-semibold text-gray-900">
                                        {{ verificationResult.holder_name || '—' }}
                                    </div>
                                </div>
                            </div>

                            <!-- Course -->
                            <div class="flex items-start gap-3 pb-4 border-b border-gray-100">
                                <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-teal-50 text-teal-600 flex-shrink-0">
                                    <Award class="h-5 w-5" />
                                </div>
                                <div class="flex-1 min-w-0">
                                    <div class="text-xs font-semibold text-gray-500 uppercase mb-1">Course</div>
                                    <div class="text-base font-semibold text-gray-900">
                                        {{ verificationResult.course || '—' }}
                                    </div>
                                    <div v-if="verificationResult.session" class="text-sm text-gray-600 mt-1">
                                        Session: {{ verificationResult.session }}
                                    </div>
                                </div>
                            </div>

                            <!-- Issued Date -->
                            <div class="flex items-start gap-3 pb-4 border-b border-gray-100">
                                <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-amber-50 text-amber-600 flex-shrink-0">
                                    <Calendar class="h-5 w-5" />
                                </div>
                                <div class="flex-1 min-w-0">
                                    <div class="text-xs font-semibold text-gray-500 uppercase mb-1">Issued Date</div>
                                    <div class="text-base text-gray-900">
                                        {{ formatDate(verificationResult.issued_at) }}
                                    </div>
                                </div>
                            </div>

                            <!-- Organization -->
                            <div class="flex items-start gap-3">
                                <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-indigo-50 text-indigo-600 flex-shrink-0">
                                    <Building2 class="h-5 w-5" />
                                </div>
                                <div class="flex-1 min-w-0">
                                    <div class="text-xs font-semibold text-gray-500 uppercase mb-1">Issued By</div>
                                    <div class="text-base text-gray-900">
                                        {{ verificationResult.organization || '—' }}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Revoked Details -->
                    <div v-else-if="isRevoked" class="p-6 bg-white">
                        <div class="space-y-4">
                            <!-- Certificate Code -->
                            <div class="pb-4 border-b border-gray-100">
                                <div class="text-xs font-semibold text-gray-500 uppercase mb-1">Certificate Code</div>
                                <div class="text-lg font-mono font-bold text-gray-900">
                                    {{ verificationResult.certificate_code }}
                                </div>
                            </div>

                            <!-- Revoked Date -->
                            <div class="pb-4 border-b border-gray-100">
                                <div class="text-xs font-semibold text-gray-500 uppercase mb-1">Revoked On</div>
                                <div class="text-base text-gray-900">
                                    {{ formatDate(verificationResult.revoked_at) }}
                                </div>
                            </div>

                            <!-- Original Issue Date -->
                            <div v-if="verificationResult.issued_at">
                                <div class="text-xs font-semibold text-gray-500 uppercase mb-1">Originally Issued</div>
                                <div class="text-base text-gray-900">
                                    {{ formatDate(verificationResult.issued_at) }}
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Not Found Details -->
                    <div v-else class="p-6 bg-white">
                        <div class="text-center py-8">
                            <p class="text-gray-600 mb-4">
                                The certificate code <span class="font-mono font-semibold">{{ verificationResult.certificate_code }}</span> was not found in our records.
                            </p>
                            <p class="text-sm text-gray-500">
                                Please check the code and try again, or contact the issuing organization for assistance.
                            </p>
                        </div>
                    </div>

                    <!-- Verify Another Button -->
                    <div class="p-4 bg-gray-50 border-t border-gray-200 text-center">
                        <button
                            @click="reset"
                            class="text-sm font-medium text-[#2f837d] hover:text-[#26685f] transition-colors"
                        >
                            Verify Another Certificate
                        </button>
                    </div>
                </div>
            </div>

            <!-- Instructions -->
            <div v-else class="mx-auto max-w-2xl mt-12">
                <div class="rounded-xl bg-blue-50 border border-blue-100 p-6">
                    <h3 class="text-sm font-semibold text-blue-900 mb-3">How to Verify</h3>
                    <ul class="space-y-2 text-sm text-blue-800">
                        <li class="flex items-start gap-2">
                            <span class="flex h-5 w-5 items-center justify-center rounded-full bg-blue-200 text-xs font-bold flex-shrink-0">1</span>
                            <span>Enter the certificate code found on your certificate (e.g., CERT-2026-0001)</span>
                        </li>
                        <li class="flex items-start gap-2">
                            <span class="flex h-5 w-5 items-center justify-center rounded-full bg-blue-200 text-xs font-bold flex-shrink-0">2</span>
                            <span>Click the "Verify" button or press Enter</span>
                        </li>
                        <li class="flex items-start gap-2">
                            <span class="flex h-5 w-5 items-center justify-center rounded-full bg-blue-200 text-xs font-bold flex-shrink-0">3</span>
                            <span>View the verification results including certificate details and status</span>
                        </li>
                    </ul>
                </div>
            </div>
        </main>

        <!-- Footer -->
        <footer class="border-t border-gray-200 bg-white/80 backdrop-blur-sm mt-16">
            <div class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
                <p class="text-center text-sm text-gray-500">
                    © 2026 Training Management System. All rights reserved.
                </p>
            </div>
        </footer>
    </div>
</template>
