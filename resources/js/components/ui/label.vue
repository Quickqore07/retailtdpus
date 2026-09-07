<template>
  <div :class="labelGroupClasses">
    <dt :class="labelClasses">
      {{ label }}
    </dt>
    <dd :class="valueClasses">
      <slot name="default">
        {{ value || '-' }}
      </slot>
    </dd>
  </div>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
  // Label text
  label: {
    type: String,
    required: true
  },

  // Value text
  value: {
    type: [String, Number],
    default: null
  },

  // Layout direction
  direction: {
    type: String,
    default: 'vertical',
    validator: (value) => ['horizontal', 'vertical'].includes(value)
  },

  // Label weight
  labelWeight: {
    type: String,
    default: 'medium',
    validator: (value) => ['normal', 'medium', 'semibold', 'bold'].includes(value)
  },

  // Label color
  labelColor: {
    type: String,
    default: 'gray',
    validator: (value) => ['gray', 'dark', 'primary'].includes(value)
  },

  // Value color
  valueColor: {
    type: String,
    default: 'dark',
    validator: (value) => ['gray', 'dark', 'primary'].includes(value)
  },

  // Value weight
  valueWeight: {
    type: String,
    default: 'normal',
    validator: (value) => ['normal', 'medium', 'semibold', 'bold'].includes(value)
  },

  // Spacing between label and value
  spacing: {
    type: String,
    default: 'sm',
    validator: (value) => ['xs', 'sm', 'md', 'lg'].includes(value)
  },

  // Custom class for the container
  customClass: {
    type: String,
    default: ''
  },
  valueCustomClass: {
    type: String,
    default: ''
  }
})

// Label group classes
const labelGroupClasses = computed(() => {
  const classes = []

  if (props.direction === 'horizontal') {
    classes.push('flex items-start gap-4')
  } else {
    // Vertical layout
    const spacingMap = {
      xs: 'space-y-0.5',
      sm: 'space-y-1',
      md: 'space-y-2',
      lg: 'space-y-3'
    }
    classes.push(spacingMap[props.spacing] || spacingMap.sm)
  }

  if (props.customClass) {
    classes.push(props.customClass)
  }

  return classes.join(' ')
})

// Label classes
const labelClasses = computed(() => {
  const classes = ['text-sm']

  // Weight
  const weightMap = {
    normal: 'font-normal',
    medium: 'font-medium',
    semibold: 'font-semibold',
    bold: 'font-bold'
  }
  classes.push(weightMap[props.labelWeight] || weightMap.medium)

  // Color
  const colorMap = {
    gray: 'text-gray-500 dark:text-gray-400',
    dark: 'text-gray-900 dark:text-gray-200',
    primary: 'text-blue-600 dark:text-blue-400'
  }
  classes.push(colorMap[props.labelColor] || colorMap.gray)

  // Horizontal layout specific
  if (props.direction === 'horizontal') {
    classes.push('min-w-[120px] flex-shrink-0')
  }

  return classes.join(' ')
})

// Value classes
const valueClasses = computed(() => {
  const classes = ['text-sm']

  // Weight
  const weightMap = {
    normal: 'font-normal',
    medium: 'font-medium',
    semibold: 'font-semibold',
    bold: 'font-bold'
  }
  classes.push(weightMap[props.valueWeight] || weightMap.normal)

  // Color
  const colorMap = {
    gray: 'text-gray-500 dark:text-gray-400',
    dark: 'text-gray-900 dark:text-gray-200',
    primary: 'text-blue-600 dark:text-blue-400'
  }
  classes.push(colorMap[props.valueColor] || colorMap.dark)
  if (props.valueCustomClass) {
    classes.push(props.valueCustomClass)
  }

  return classes.join(' ')
})
</script>

