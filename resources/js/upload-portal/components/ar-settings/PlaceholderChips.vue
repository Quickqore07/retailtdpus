<template>
  <div class="flex flex-wrap gap-2 items-center">
    <span class="text-xs text-gray-500 dark:text-gray-400 shrink-0">Insert:</span>
    <button
      v-for="p in items"
      :key="p.token || p.key"
      type="button"
      class="text-xs px-2 py-1 rounded-md border border-gray-200 dark:border-gray-600 bg-gray-50 dark:bg-gray-900/50 text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors font-mono"
      :title="insertTokenFor(p)"
      @click="$emit('insert', insertTokenFor(p))"
    >
      {{ p.label }}
    </button>
  </div>
</template>

<script setup>
defineProps({
  items: {
    type: Array,
    default: () => [],
  },
})

defineEmits(['insert'])

const insertTokenFor = (p) => {
  if (p.token) return p.token
  if (p.key) return `{{${p.key}}}`
  return ''
}
</script>
