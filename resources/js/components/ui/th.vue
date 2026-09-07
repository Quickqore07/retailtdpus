<template>
  <th 
    :class="[
      'px-6 py-3 text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider bg-gray-50 dark:bg-gray-900',
      alignmentClass,
      customClass
    ]"
  >
    <slot />
  </th>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
  // Text alignment
  align: {
    type: String,
    default: 'left',
    validator: (value) => ['left', 'center', 'right'].includes(value)
  },
  
  // Custom classes
  customClass: {
    type: String,
    default: ''
  }
})

const alignmentClass = computed(() => {
  const alignments = {
    left: 'text-left',
    center: 'text-center',
    right: 'text-right'
  }
  return alignments[props.align]
})
</script>

