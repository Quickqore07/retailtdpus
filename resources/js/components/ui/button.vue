<template>
  <component
    :is="tag"
    :class="buttonClasses"
    :type="nativeType"
    :disabled="isDisabled"
    v-bind="linkProps"
    @click="handleClick"
  >
    <!-- Loading spinner -->
    <span v-if="loading" class="button-spinner">
      <div class="inline-block animate-spin rounded-full h-4 w-4 border-b-2 border-current"></div>
    </span>

    <!-- Left icon -->
    <SvgIcon
      v-if="iconLeft"
      :name="iconLeft"
      :size="iconSize"
    />

    <!-- Button text -->
    <slot v-if="!iconOnly"></slot>

    <!-- Right icon -->
    <SvgIcon
      v-if="iconRight"
      :name="iconRight"
      :size="iconSize"
    />
  </component>
</template>

<script setup>
import { computed } from 'vue'
import SvgIcon from '../SvgIcon.vue'

const props = defineProps({
  // Button variant
  variant: {
    type: String,
    default: 'primary',
    validator: (value) => [
      'primary',
      'secondary',
      'success',
      'danger',
      'warning',
      'info',
      'outline',
      'outline-primary',
      'outline-secondary',
      'outline-success',
      'outline-danger',
      'outline-warning',
      'outline-info',
      'ghost',
      'purple'
    ].includes(value)
  },

  // Button size
  size: {
    type: String,
    default: 'md',
    validator: (value) => ['xs', 'sm', 'md', 'lg'].includes(value)
  },

  // Button type
  type: {
    type: String,
    default: 'button'
  },

  // Disabled state
  disabled: {
    type: Boolean,
    default: false
  },

  // Loading state
  loading: {
    type: Boolean,
    default: false
  },

  // Full width
  block: {
    type: Boolean,
    default: false
  },

  // Link props
  href: {
    type: String,
    default: null
  },

  to: {
    type: [String, Object],
    default: null
  },

  target: {
    type: String,
    default: null
  },

  // Icon props
  iconLeft: {
    type: String,
    default: null
  },

  iconRight: {
    type: String,
    default: null
  },

  iconSize: {
    type: String,
    default: 'sm'
  },

  // Icon only (no text)
  iconOnly: {
    type: Boolean,
    default: false
  },

  // Custom classes
  customClass: {
    type: String,
    default: ''
  }
})

const emit = defineEmits(['click'])

// Determine tag
const tag = computed(() => {
  if (props.to) return 'router-link'
  if (props.href) return 'a'
  return 'button'
})

// Native type
const nativeType = computed(() => {
  if (props.to || props.href) return undefined
  return props.type
})

// Disabled state
const isDisabled = computed(() => props.disabled || props.loading)

// Link props
const linkProps = computed(() => {
  const attrs = {}
  if (props.href) attrs.href = props.href
  if (props.to) attrs.to = props.to
  if (props.target) {
    attrs.target = props.target
    if (props.target === '_blank') {
      attrs.rel = 'noopener noreferrer'
    }
  }
  return attrs
})

// Button classes
const buttonClasses = computed(() => {
  const classes = ['btn']

  // Variant
  if (props.variant === 'primary') {
    classes.push('btn-primary')
  } else if (props.variant === 'secondary') {
    classes.push('btn-secondary')
  } else if (props.variant === 'success') {
    classes.push('btn-success')
  } else if (props.variant === 'danger') {
    classes.push('btn-danger')
  } else if (props.variant === 'warning') {
    classes.push('btn-warning')
  } else if (props.variant === 'info') {
    classes.push('btn-info')
  } else if (props.variant === 'outline') {
    classes.push('btn-outline')
  } else if (props.variant === 'outline-primary') {
    classes.push('btn-outline-primary')
  } else if (props.variant === 'outline-secondary') {
    classes.push('btn-outline-secondary')
  } else if (props.variant === 'outline-success') {
    classes.push('btn-outline-success')
  } else if (props.variant === 'outline-danger') {
    classes.push('btn-outline-danger')
  } else if (props.variant === 'outline-warning') {
    classes.push('btn-outline-warning')
  } else if (props.variant === 'outline-info') {
    classes.push('btn-outline-info')
  } else if (props.variant === 'ghost') {
    classes.push('btn-social')
  }

  // Size
  if (props.size === 'xs') {
    classes.push('btn-xs')
  } else if (props.size === 'sm') {
    classes.push('btn-sm')
  } else if (props.size === 'lg') {
    classes.push('btn-lg')
  }

  // Icon only
  if (props.iconOnly) {
    classes.push('btn-icon-only')
  }

  // Block
  if (props.block) {
    classes.push('btn-block')
  }

  // Custom classes
  if (props.customClass) {
    classes.push(props.customClass)
  }

  return classes.join(' ')
})

// Handle click
const handleClick = (event) => {
  if (isDisabled.value) {
    event.preventDefault()
    event.stopPropagation()
    return
  }
  emit('click', event)
}
</script>

<style scoped>
.button-spinner {
  display: inline-flex;
  margin-right: 0.5rem;
}
</style>

<style>
/* Prevent router-link default styling from affecting button text color */
a.btn,
a.btn:hover,
a.btn:focus,
a.btn:active,
a.btn:visited {
  text-decoration: none !important;
}

/* Ensure button variants maintain their text color when used as links */
a.btn-primary,
a.btn-primary:hover,
a.btn-primary:focus,
a.btn-primary:active,
a.btn-primary:visited {
  color: var(--color-white) !important;
}

a.btn-success,
a.btn-success:hover,
a.btn-success:focus,
a.btn-success:active,
a.btn-success:visited {
  color: var(--color-white) !important;
}

a.btn-danger,
a.btn-danger:hover,
a.btn-danger:focus,
a.btn-danger:active,
a.btn-danger:visited {
  color: var(--color-white) !important;
}

a.btn-warning,
a.btn-warning:hover,
a.btn-warning:focus,
a.btn-warning:active,
a.btn-warning:visited {
  color: var(--color-white) !important;
}

a.btn-info,
a.btn-info:hover,
a.btn-info:focus,
a.btn-info:active,
a.btn-info:visited {
  color: var(--color-white) !important;
}

a.btn-secondary,
a.btn-secondary:hover {
  color: var(--color-gray-700) !important;
}

a.btn-secondary:focus,
a.btn-secondary:active,
a.btn-secondary:visited {
  color: var(--color-gray-700) !important;
}

.dark a.btn-secondary,
.dark a.btn-secondary:hover,
.dark a.btn-secondary:focus,
.dark a.btn-secondary:active,
.dark a.btn-secondary:visited {
  color: rgba(255, 255, 255, 0.9) !important;
}

a.btn-outline,
a.btn-outline:visited {
  color: var(--color-primary) !important;
}

a.btn-outline:hover,
a.btn-outline:focus,
a.btn-outline:active {
  color: var(--color-white) !important;
}

a.btn-outline-primary,
a.btn-outline-primary:visited {
  color: var(--color-primary) !important;
}

a.btn-outline-primary:hover,
a.btn-outline-primary:focus,
a.btn-outline-primary:active {
  color: var(--color-white) !important;
}

a.btn-outline-secondary,
a.btn-outline-secondary:visited {
  color: var(--color-gray-700) !important;
}

a.btn-outline-secondary:hover,
a.btn-outline-secondary:focus,
a.btn-outline-secondary:active {
  color: var(--color-white) !important;
}

a.btn-outline-success,
a.btn-outline-success:visited {
  color: var(--color-success) !important;
}

a.btn-outline-success:hover,
a.btn-outline-success:focus,
a.btn-outline-success:active {
  color: var(--color-white) !important;
}

a.btn-outline-danger,
a.btn-outline-danger:visited {
  color: var(--color-danger) !important;
}

a.btn-outline-danger:hover,
a.btn-outline-danger:focus,
a.btn-outline-danger:active {
  color: var(--color-white) !important;
}

a.btn-outline-warning,
a.btn-outline-warning:visited {
  color: var(--color-warning) !important;
}

a.btn-outline-warning:hover,
a.btn-outline-warning:focus,
a.btn-outline-warning:active {
  color: var(--color-white) !important;
}

a.btn-outline-info,
a.btn-outline-info:visited {
  color: var(--color-info) !important;
}

a.btn-outline-info:hover,
a.btn-outline-info:focus,
a.btn-outline-info:active {
  color: var(--color-white) !important;
}

a.btn-social,
a.btn-social:hover,
a.btn-social:focus,
a.btn-social:active,
a.btn-social:visited {
  color: var(--color-gray-700) !important;
}

.dark a.btn-social,
.dark a.btn-social:hover,
.dark a.btn-social:focus,
.dark a.btn-social:active,
.dark a.btn-social:visited {
  color: rgba(255, 255, 255, 0.9) !important;
}
</style>

