<template>
  <div class="flex flex-wrap items-center justify-between gap-4 mb-6">
    <div>
      <h1 class="text-2xl font-semibold text-gray-900">{{ title }}</h1>
      <p v-if="description" class="mt-1 text-sm text-gray-600">{{ description }}</p>
    </div>
    <div class="flex gap-3">
      <slot name="actions" />
    </div>
  </div>
</template>

<script setup>
defineProps({
    title: { type: String, required: true },
    description: String,
});
</script>
