<template>
    <div class="relative">
        <div class="flex items-center space-x-2">
            <a
                href="/language/th"
                class="px-3 py-1 text-sm rounded-md transition-colors"
                :class="currentLocale === 'th' ? 'bg-indigo-100 text-indigo-700' : 'text-gray-600 hover:bg-gray-100'"
            >
                ไทย
            </a>
            <span class="text-gray-300">|</span>
            <a
                href="/language/en"
                class="px-3 py-1 text-sm rounded-md transition-colors"
                :class="currentLocale === 'en' ? 'bg-indigo-100 text-indigo-700' : 'text-gray-600 hover:bg-gray-100'"
            >
                English
            </a>
        </div>
    </div>
</template>

<script setup>
import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';
import { getActiveLanguage } from 'laravel-vue-i18n';

const page = usePage();
const currentLocale = computed(() => page.props.locale || getActiveLanguage() || 'th');
</script>
