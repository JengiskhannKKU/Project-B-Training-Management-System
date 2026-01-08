<template>
  <div class="flex items-center justify-between border-t border-gray-200 bg-white px-4 py-3">
    <div>
      <p class="text-sm text-gray-700">
        Page <span class="font-medium">{{ currentPage }}</span>
        of <span class="font-medium">{{ totalPages }}</span>
      </p>
    </div>
    <div class="flex gap-2">
      <button
        @click="$emit('change', currentPage - 1)"
        :disabled="currentPage === 1"
        class="px-4 py-2 text-sm border border-gray-300 rounded-md hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
      >
        Previous
      </button>
      <button
        @click="$emit('change', currentPage + 1)"
        :disabled="currentPage === totalPages"
        class="px-4 py-2 text-sm border border-gray-300 rounded-md hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
      >
        Next
      </button>
    </div>
  </div>
</template>

<script setup>
defineProps({
    currentPage: { type: Number, required: true },
    totalPages: { type: Number, required: true },
});

defineEmits(['change']);
</script>
