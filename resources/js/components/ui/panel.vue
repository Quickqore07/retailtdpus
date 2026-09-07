<template>
  <div :class="panelClasses">
    <!-- Header -->
    <div v-if="hasHeader" :class="headerClasses">
      <slot name="header">
        <h4 v-if="title" :class="titleClasses">
          {{ title }}
        </h4>
      </slot>
    </div>

    <!-- Body -->
    <div :class="bodyClasses">
      <slot></slot>
    </div>

    <!-- Footer -->
    <div v-if="hasFooter" :class="footerClasses">
      <slot name="footer"></slot>
    </div>
  </div>
</template>

<script setup>
import { computed, useSlots } from 'vue'

const props = defineProps({
  // Panel title
  title: {
    type: String,
    default: null
  },

  // Padding size
  padding: {
    type: String,
    default: 'md',
    validator: (value) => ['none', 'sm', 'md', 'lg'].includes(value)
  },

  // Header padding
  headerPadding: {
    type: String,
    default: null
  },

  // Body padding
  bodyPadding: {
    type: String,
    default: null
  },

  // Footer padding
  footerPadding: {
    type: String,
    default: null
  },

  // Shadow
  shadow: {
    type: String,
    default: 'sm',
    validator: (value) => ['none', 'sm', 'md', 'lg'].includes(value)
  },

  // Border
  border: {
    type: Boolean,
    default: true
  },

  // Rounded corners
  rounded: {
    type: Boolean,
    default: true
  },

  // Background color
  background: {
    type: String,
    default: 'white',
    validator: (value) => ['white', 'gray', 'transparent'].includes(value)
  },

  // Divider between sections
  divider: {
    type: Boolean,
    default: false
  },

  // Custom classes
  customClass: {
    type: String,
    default: ''
  },

  // Header background
  headerBackground: {
    type: String,
    default: null,
    validator: (value) => !value || ['white', 'gray'].includes(value)
  },

  // Footer background
  footerBackground: {
    type: String,
    default: null,
    validator: (value) => !value || ['white', 'gray'].includes(value)
  }
})

const slots = useSlots()

// Check if slots have content
const hasHeader = computed(() => {
  return !!slots.header || !!props.title
})

const hasFooter = computed(() => {
  return !!slots.footer
})

// Padding classes helper
const getPaddingClass = (size) => {
  const paddingMap = {
    none: '',
    sm: 'px-4 py-2',
    md: 'px-4 md:px-6 py-3 md:py-4',
    lg: 'px-8 py-4'
  }
  return paddingMap[size] || paddingMap.md
}

// Panel classes
const panelClasses = computed(() => {
  const classes = []

  // Background
  if (props.background === 'white') {
    classes.push('bg-white dark:bg-gray-800')
  } else if (props.background === 'gray') {
    classes.push('bg-gray-50 dark:bg-gray-900')
  }

  // Rounded
  if (props.rounded) {
    classes.push('rounded-lg')
  }

  // Shadow
  if (props.shadow === 'sm') {
    classes.push('shadow-sm')
  } else if (props.shadow === 'md') {
    classes.push('shadow-md')
  } else if (props.shadow === 'lg') {
    classes.push('shadow-lg')
  }

  // Border
  if (props.border) {
    classes.push('border border-gray-200 dark:border-gray-700')
  }

  // Custom classes
  if (props.customClass) {
    classes.push(props.customClass)
  }

  return classes.join(' ')
})

// Header classes
const headerClasses = computed(() => {
  const classes = []

  // Padding
  const padding = props.headerPadding || props.padding
  classes.push(getPaddingClass(padding))

  // Background
  if (props.headerBackground === 'gray') {
    classes.push('bg-gray-50 dark:bg-gray-900')
  } else if (props.headerBackground === 'white') {
    classes.push('bg-white dark:bg-gray-800')
  }

  // Divider
  if (props.divider && (hasFooter.value || slots.default)) {
    classes.push('border-b border-gray-200 dark:border-gray-700')
  }

  return classes.join(' ')
})

// Body classes
const bodyClasses = computed(() => {
  const classes = []

  // Padding
  const padding = props.bodyPadding || props.padding
  classes.push(getPaddingClass(padding))

  // Divider
  if (props.divider && hasFooter.value) {
    classes.push('border-b border-gray-200 dark:border-gray-700')
  }

  return classes.join(' ')
})

// Footer classes
const footerClasses = computed(() => {
  const classes = []

  // Padding
  const padding = props.footerPadding || props.padding
  classes.push(getPaddingClass(padding))

  // Background
  if (props.footerBackground === 'gray') {
    classes.push('bg-gray-50 dark:bg-gray-900')
  } else if (props.footerBackground === 'white') {
    classes.push('bg-white dark:bg-gray-800')
  }

  return classes.join(' ')
})

// Title classes
const titleClasses = computed(() => {
  return 'text-lg font-semibold text-gray-900 dark:text-white !mb-0'
})
</script>

