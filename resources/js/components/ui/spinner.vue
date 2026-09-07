<template>
  <div :class="containerClasses">
    <div :class="spinnerClasses"></div>
    <p v-if="text" :class="textClasses" class="!mt-5">{{ text }}</p>
  </div>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
  // Size of the spinner
  size: {
    type: String,
    default: 'md',
    validator: (value) => ['xs', 'sm', 'md', 'lg', 'xl'].includes(value)
  },
  
  // Optional text to display below spinner
  text: {
    type: String,
    default: null
  },
  
  // Color variant
  variant: {
    type: String,
    default: 'primary',
    validator: (value) => ['primary', 'secondary', 'white'].includes(value)
  },
  
  // Center in container
  centered: {
    type: Boolean,
    default: false
  },
  
  // Inline display (no flex container)
  inline: {
    type: Boolean,
    default: false
  }
})

// Container classes
const containerClasses = computed(() => {
  const classes = []
  
  if (props.inline) {
    classes.push('inline-block')
  } else if (props.centered) {
    classes.push('flex', 'flex-col', 'items-center', 'justify-center')
  } else if (props.text) {
    classes.push('flex', 'flex-col', 'items-center')
  } else {
    classes.push('flex', 'items-center', 'justify-center')
  }
  
  return classes.join(' ')
})

// Spinner classes
const spinnerClasses = computed(() => {
  const classes = [
    'inline-block',
    'animate-spin',
    'rounded-full',
    'border-b-2'
  ]
  
  // Size
  const sizeMap = {
    xs: 'h-3 w-3 border-b',
    sm: 'h-4 w-4 border-b',
    md: 'h-8 w-8 border-b-2',
    lg: 'h-12 w-12 border-b-2',
    xl: 'h-16 w-16 border-b-[3px]'
  }
  classes.push(sizeMap[props.size])
  
  // Color variant
  const colorMap = {
    primary: 'border-blue-600 dark:border-blue-500',
    secondary: 'border-gray-600 dark:border-gray-400',
    white: 'border-white'
  }
  classes.push(colorMap[props.variant])
  
  return classes.join(' ')
})

// Text classes
const textClasses = computed(() => {
  const classes = ['text-gray-600', 'dark:text-gray-400']
  
  // Text size based on spinner size
  const textSizeMap = {
    xs: 'text-xs mt-1',
    sm: 'text-xs mt-1.5',
    md: 'text-sm mt-4',
    lg: 'text-base mt-4',
    xl: 'text-lg mt-5'
  }
  classes.push(textSizeMap[props.size])
  
  return classes.join(' ')
})
</script>

