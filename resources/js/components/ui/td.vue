<template>
  <td 
    :class="[
      'px-6 py-1.5 whitespace-nowrap',
      alignmentClass,
      weightClass,
      colorClass,
      sizeClass,
      customClass
    ]"
    :style="style"
  >
    <slot />
  </td>
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
  style: {
    type: Object,
    default: {},
  },
  // Font weight
  weight: {
    type: String,
    default: 'normal',
    validator: (value) => ['normal', 'medium', 'semibold', 'bold'].includes(value)
  },
  
  // Text color variant
  color: {
    type: String,
    default: 'default',
    validator: (value) => ['default', 'primary', 'secondary', 'muted'].includes(value)
  },
  
  // Text size
  size: {
    type: String,
    default: 'sm',
    validator: (value) => ['xs', 'sm', 'base', 'lg'].includes(value)
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

const weightClass = computed(() => {
  const weights = {
    normal: 'font-normal',
    medium: 'font-medium',
    semibold: 'font-semibold',
    bold: 'font-bold'
  }
  return weights[props.weight]
})

const colorClass = computed(() => {
  const colors = {
    default: 'text-gray-900 dark:text-gray-100',
    primary: 'text-gray-900 dark:text-gray-100',
    secondary: 'text-gray-600 dark:text-gray-400',
    muted: 'text-gray-400 dark:text-gray-500'
  }
  return colors[props.color]
})

const sizeClass = computed(() => {
  const sizes = {
    xs: 'text-xs',
    sm: 'text-sm',
    base: 'text-base',
    lg: 'text-lg'
  }
  return sizes[props.size]
})
</script>

