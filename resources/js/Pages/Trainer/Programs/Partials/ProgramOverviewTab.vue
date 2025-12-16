<script setup lang="ts">
defineProps<{
    program: {
        id: number;
        name: string;
        code: string;
        category: string;
        level: string;
        period: string;
        time: string;
        location: string;
        trainer: string;
        certificated: string;
        status: string;
        description: string;
        image_url: string | null;
    };
}>();
</script>

<template>
    <div :class="['grid gap-4 sm:gap-6', 'lg:grid-cols-3']">
        <!-- Main Content -->
        <div class="lg:col-span-2">
            <div class="rounded-lg bg-white p-6 shadow-sm">
                <h2 class="mb-4 text-lg font-semibold text-gray-900">Description</h2>
                <p class="text-gray-600">
                    Lorem ipsum dolor sit amet, consectetur adipiscing elit. Integer vitae congue nullam consectetur ornare consectetur sed in leo. Enim imperdiet urna tincidunt at integer nunc amet vitae orci. Ultrices augue scelerisque.
                </p>

                <h3 class="mb-3 mt-6 font-semibold text-gray-900">What You'll Learn</h3>
                <ul class="space-y-2 text-gray-600">
                    <li class="flex items-start gap-2">
                        <span class="text-teal-600">•</span>
                        <span>Lorem ipsum dolor sit amet, consectetur adipiscing elit.</span>
                    </li>
                    <li class="flex items-start gap-2">
                        <span class="text-teal-600">•</span>
                        <span>Integer vitae congue nullam consectetur ornare consectetur sed in leo.</span>
                    </li>
                    <li class="flex items-start gap-2">
                        <span class="text-teal-600">•</span>
                        <span>Enim imperdiet urna tincidunt at integer nunc amet vitae orci.</span>
                    </li>
                    <li class="flex items-start gap-2">
                        <span class="text-teal-600">•</span>
                        <span>Ultrices augue scelerisque.</span>
                    </li>
                </ul>

                <h3 class="mb-3 mt-6 font-semibold text-gray-900">Who Should Attend</h3>
                <ul class="space-y-2 text-gray-600">
                    <li class="flex items-start gap-2">
                        <span class="text-teal-600">•</span>
                        <span>Lorem ipsum dolor sit amet, consectetur adipiscing elit.</span>
                    </li>
                    <li class="flex items-start gap-2">
                        <span class="text-teal-600">•</span>
                        <span>Integer vitae congue nullam consectetur ornare consectetur sed in leo.</span>
                    </li>
                    <li class="flex items-start gap-2">
                        <span class="text-teal-600">•</span>
                        <span>Enim imperdiet urna tincidunt at integer nunc amet vitae orci.</span>
                    </li>
                    <li class="flex items-start gap-2">
                        <span class="text-teal-600">•</span>
                        <span>Ultrices augue scelerisque.</span>
                    </li>
                    <li class="flex items-start gap-2">
                        <span class="text-teal-600">•</span>
                        <span>Lorem ipsum dolor sit amet, consectetur adipiscing elit.</span>
                    </li>
                    <li class="flex items-start gap-2">
                        <span class="text-teal-600">•</span>
                        <span>Integer vitae congue nullam consectetur ornare consectetur sed in leo.</span>
                    </li>
                    <li class="flex items-start gap-2">
                        <span class="text-teal-600">•</span>
                        <span>Enim imperdiet urna tincidunt at integer nunc amet vitae orci.</span>
                    </li>
                    <li class="flex items-start gap-2">
                        <span class="text-teal-600">•</span>
                        <span>Ultrices augue scelerisque.</span>
                    </li>
                </ul>
            </div>
        </div>

        <!-- Sidebar -->
        <div class="space-y-6">
            <!-- Course Information -->
            <div class="rounded-lg bg-white p-6 shadow-sm">
                <div class="mb-4 flex items-center justify-between">
                    <h3 class="font-semibold text-gray-900">Course Information</h3>
                    <button class="text-teal-600 hover:text-teal-700">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                        </svg>
                    </button>
                </div>

                <div class="space-y-3 text-sm">
                    <div>
                        <div class="text-gray-500">Category:</div>
                        <div class="font-medium text-gray-900">Design</div>
                    </div>
                    <div>
                        <div class="text-gray-500">Level:</div>
                        <span class="rounded bg-red-100 px-2 py-1 text-xs font-medium text-red-700">Advanced</span>
                    </div>
                    <div>
                        <div class="text-gray-500">Period:</div>
                        <div class="font-medium text-gray-900">May 1 - MAY 2</div>
                    </div>
                    <div>
                        <div class="text-gray-500">Time:</div>
                        <div class="font-medium text-gray-900">09:00 - 12:00 AM</div>
                    </div>
                    <div>
                        <div class="text-gray-500">Location:</div>
                        <div class="font-medium text-gray-900">Smart Classrom</div>
                    </div>
                    <div>
                        <div class="text-gray-500">Trainer:</div>
                        <div class="font-medium text-gray-900">Natthiya Chakaew</div>
                    </div>
                    <div>
                        <div class="text-gray-500">Certificated:</div>
                        <div class="font-medium text-gray-900">Standard</div>
                    </div>
                    <div>
                        <div class="text-gray-500">Status:</div>
                        <div class="flex items-center gap-1">
                            <div class="h-2 w-2 rounded-full bg-green-500"></div>
                            <span class="font-medium text-green-700">OPEN</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Instructor -->
            <div class="rounded-lg bg-white p-6 shadow-sm">
                <h3 class="mb-4 font-semibold text-gray-900">Instructor</h3>

                <div class="flex items-start gap-3">
                    <img src="https://ui-avatars.com/api/?name=สบาย+โจ๊ก&background=0D8ABC&color=fff" alt="Instructor" class="h-16 w-16 rounded-full" />
                    <div class="flex-1">
                        <div class="font-medium text-gray-900">สบาย โจ๊ก</div>
                        <div class="mb-2 flex items-center gap-1">
                            <span class="text-lg font-semibold text-gray-900">4.8</span>
                            <svg class="h-4 w-4 text-yellow-400" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                            </svg>
                        </div>
                        <div class="text-xs text-gray-500">(16,124 reviews)</div>
                    </div>
                    <div class="flex h-12 w-12 items-center justify-center rounded-full bg-teal-100">
                        <span class="text-sm font-semibold text-teal-700">85%</span>
                    </div>
                </div>
                <div class="mt-2 text-xs text-gray-500">Positive</div>
            </div>

            <!-- Course URL -->
            <div class="rounded-lg bg-white p-6 shadow-sm">
                <h3 class="mb-4 font-semibold text-gray-900">Course URL</h3>

                <div class="mb-4 flex items-center gap-2 rounded-lg bg-gray-50 p-3">
                    <input type="text" value="https://example.com/courses/ux*de" readonly class="flex-1 border-0 bg-transparent text-sm text-gray-600 focus:ring-0" />
                    <button class="text-gray-400 hover:text-gray-600">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                        </svg>
                    </button>
                </div>

                <div class="grid grid-cols-3 gap-3">
                    <button class="flex flex-col items-center gap-1 rounded-lg border border-gray-200 p-3 hover:bg-gray-50">
                        <svg class="h-5 w-5 text-teal-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z"/>
                        </svg>
                        <span class="text-xs text-gray-600">Share</span>
                    </button>
                    <button class="flex flex-col items-center gap-1 rounded-lg border border-gray-200 p-3 hover:bg-gray-50">
                        <svg class="h-5 w-5 text-teal-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                        </svg>
                        <span class="text-xs text-gray-600">Email</span>
                    </button>
                    <button class="flex flex-col items-center gap-1 rounded-lg border border-gray-200 p-3 hover:bg-gray-50">
                        <svg class="h-5 w-5 text-teal-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"/>
                        </svg>
                        <span class="text-xs text-gray-600">QR Code</span>
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>
