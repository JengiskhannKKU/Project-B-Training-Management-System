<template>
  <div v-if="hasLinks" class="flex items-center justify-between border-t border-gray-200 bg-white px-4 py-3 sm:px-6">
    <div class="flex flex-1 justify-between sm:hidden">
      <button
        v-if="links.prev"
        @click="goToLink(links.prev.url)"
        class="relative inline-flex items-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50"
      >
        Previous
      </button>
      <button
        v-if="links.next"
        @click="goToLink(links.next.url)"
        class="relative ml-3 inline-flex items-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50"
      >
        Next
      </button>
    </div>
    <div class="hidden sm:flex sm:flex-1 sm:items-center sm:justify-between">
      <div>
        <p class="text-sm text-gray-700">
          Showing <span class="font-medium">{{ from }}</span> to <span class="font-medium">{{ to }}</span> of
          <span class="font-medium">{{ total }}</span> results
        </p>
      </div>
      <div>
        <nav class="isolate inline-flex -space-x-px rounded-md shadow-sm" aria-label="Pagination">
          <button
            v-for="(link, index) in linksData"
            :key="index"
            @click="link.url && goToLink(link.url)"
            :class="[
              link.url
                ? 'cursor-pointer hover:bg-gray-50'
                : 'cursor-not-allowed opacity-50',
              link.active
                ? 'relative z-10 inline-flex items-center border border-teal-600 bg-teal-600 px-4 py-2 text-sm font-semibold text-white focus:z-20 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-teal-600'
                : 'relative inline-flex items-center border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 focus:z-20 focus:outline-offset-0'
            ]"
            v-html="link.label"
            :disabled="!link.url"
          />
        </nav>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue';
import { router } from '@inertiajs/vue3';

const props = defineProps({
  links: {
    type: Object,
    default: () => ({}),
  },
});

const hasLinks = computed(() => {
  return props.links && props.links.data && props.links.data.length > 0;
});

const from = computed(() => props.links.from || 0);
const to = computed(() => props.links.to || 0);
const total = computed(() => props.links.total || 0);

const linksData = computed(() => {
  if (!props.links || !props.links.links) {
    return [];
  }
  return props.links.links.map(link => ({
    label: link.label,
    url: link.url,
    active: link.active,
  }));
});

const goToLink = (url) => {
  if (!url) return;

  // Parse URL to get query params
  const urlObj = new URL(url, window.location.origin);
  const params = {};
  for (const [key, value] of urlObj.searchParams) {
    params[key] = value;
  }

  // Navigate with the query params
  router.get(window.location.pathname, params, {
    preserveState: true,
    preserveScroll: true,
  });
};
</script>
