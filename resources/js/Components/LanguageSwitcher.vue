<template>
    <div class="language-switcher">
        <!-- Modern Toggle Switch Design -->
        <div class="relative inline-flex items-center bg-white rounded-full p-1 shadow-sm border border-gray-200">
            <!-- Background Slider -->
            <div 
                class="absolute inset-y-1 left-1 w-[calc(50%-0.25rem)] bg-gradient-to-r from-[#2F837D] to-[#3da094] rounded-full shadow-md transition-transform duration-300 ease-in-out"
                :class="currentLocale === 'en' ? 'translate-x-full' : 'translate-x-0'"
            ></div>

            <!-- Thai Button -->
            <a
                href="/language/th"
                class="relative z-10 px-4 py-2 text-sm font-medium rounded-full transition-all duration-300 ease-in-out flex items-center gap-2 focus:outline-none focus:ring-2 focus:ring-[#2F837D] focus:ring-offset-2 min-w-[90px] justify-center"
                :class="currentLocale === 'th' ? 'text-white' : 'text-gray-700 hover:text-[#2F837D]'"
                :aria-label="currentLocale === 'th' ? 'Currently in Thai' : 'Switch to Thai'"
                :title="currentLocale === 'th' ? 'ภาษาไทย (ปัจจุบัน)' : 'เปลี่ยนเป็นภาษาไทย'"
                role="button"
            >
                <span class="text-base" :class="currentLocale === 'th' ? 'drop-shadow-sm' : ''">🇹🇭</span>
                <span class="font-semibold">ไทย</span>
            </a>

            <!-- English Button -->
            <a
                href="/language/en"
                class="relative z-10 px-4 py-2 text-sm font-medium rounded-full transition-all duration-300 ease-in-out flex items-center gap-2 focus:outline-none focus:ring-2 focus:ring-[#2F837D] focus:ring-offset-2 min-w-[90px] justify-center"
                :class="currentLocale === 'en' ? 'text-white' : 'text-gray-700 hover:text-[#2F837D]'"
                :aria-label="currentLocale === 'en' ? 'Currently in English' : 'Switch to English'"
                :title="currentLocale === 'en' ? 'English (Current)' : 'Switch to English'"
                role="button"
            >
                <span class="text-base" :class="currentLocale === 'en' ? 'drop-shadow-sm' : ''">🇺🇸</span>
                <span class="font-semibold">EN</span>
            </a>
        </div>
    </div>
</template>

<script setup>
import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';
import { getActiveLanguage } from 'laravel-vue-i18n';

const page = usePage();
// Default to Thai language
const currentLocale = computed(() => page.props.locale || getActiveLanguage() || 'th');
</script>

<style scoped>
.language-switcher {
    /* Ensure smooth transitions and proper z-index stacking */
    position: relative;
}

/* Add subtle hover effect on the entire toggle */
.language-switcher > div:hover {
    box-shadow: 0 2px 8px rgba(47, 131, 125, 0.15);
}

/* Smooth gradient animation on the slider */
@keyframes slideGradient {
    0%, 100% {
        background-position: 0% 50%;
    }
    50% {
        background-position: 100% 50%;
    }
}
</style>
