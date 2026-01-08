<template>
    <div class="language-switcher">
        <!-- Minimal Modern Toggle -->
        <div class="inline-flex items-center gap-1 bg-gray-100 rounded-full p-1">
            <!-- Thai Button -->
            <button
                @click="switchLanguage('th')"
                type="button"
                :class="[
                    'relative px-3 py-1.5 rounded-full text-sm font-medium transition-all duration-300 ease-out flex items-center gap-1.5 min-w-[70px] justify-center focus:outline-none',
                    currentLocale === 'th' 
                        ? 'bg-[#2F837D] text-white shadow-sm scale-105' 
                        : 'text-gray-600 hover:text-gray-900 hover:bg-gray-200/50'
                ]"
                :aria-label="currentLocale === 'th' ? $t('Currently in Thai') : $t('Switch to Thai')"
                :disabled="currentLocale === 'th'"
            >
                <Transition
                    enter-active-class="transition-all duration-300 ease-out"
                    enter-from-class="opacity-0 scale-75"
                    enter-to-class="opacity-100 scale-100"
                    leave-active-class="transition-all duration-200 ease-in"
                    leave-from-class="opacity-100 scale-100"
                    leave-to-class="opacity-0 scale-75"
                    mode="out-in"
                >
                    <span :key="currentLocale === 'th' ? 'active' : 'inactive'" class="flex items-center gap-1.5">
                        <span class="text-sm">🇹🇭</span>
                        <span>ไทย</span>
                    </span>
                </Transition>
            </button>

            <!-- English Button -->
            <button
                @click="switchLanguage('en')"
                type="button"
                :class="[
                    'relative px-3 py-1.5 rounded-full text-sm font-medium transition-all duration-300 ease-out flex items-center gap-1.5 min-w-[70px] justify-center focus:outline-none',
                    currentLocale === 'en' 
                        ? 'bg-[#2F837D] text-white shadow-sm scale-105' 
                        : 'text-gray-600 hover:text-gray-900 hover:bg-gray-200/50'
                ]"
                :aria-label="currentLocale === 'en' ? $t('Currently in English') : $t('Switch to English')"
                :disabled="currentLocale === 'en'"
            >
                <Transition
                    enter-active-class="transition-all duration-300 ease-out"
                    enter-from-class="opacity-0 scale-75"
                    enter-to-class="opacity-100 scale-100"
                    leave-active-class="transition-all duration-200 ease-in"
                    leave-from-class="opacity-100 scale-100"
                    leave-to-class="opacity-0 scale-75"
                    mode="out-in"
                >
                    <span :key="currentLocale === 'en' ? 'active' : 'inactive'" class="flex items-center gap-1.5">
                        <span class="text-sm">🇺🇸</span>
                        <span>EN</span>
                    </span>
                </Transition>
            </button>
        </div>
    </div>
</template>

<script setup>
import { computed } from 'vue';
import { router, usePage } from '@inertiajs/vue3';
import { getActiveLanguage, loadLanguageAsync } from 'laravel-vue-i18n';

const page = usePage();
const currentLocale = computed(() => page.props.locale || getActiveLanguage() || 'th');

const switchLanguage = async (locale) => {
    if (currentLocale.value === locale) return;
    
    await loadLanguageAsync(locale);
    router.visit(`/language/${locale}`);
}
</script>

<style scoped>
.language-switcher {
    position: relative;
}

/* Smooth hover lift effect */
.language-switcher button:hover:not(:disabled) {
    transform: translateY(-1px);
}

/* Active button gets subtle pulse */
.language-switcher button:not(:disabled):active {
    transform: scale(0.98);
}
</style>
