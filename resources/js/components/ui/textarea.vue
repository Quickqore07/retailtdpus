<template>
  <div class="flex flex-col gap-1.5">
    <label v-if="label" :for="textareaId" class="block text-sm font-medium text-gray-700 dark:text-gray-200 ">
      {{ label }}
      <span v-if="required" class="text-red-500">*</span>
    </label>
    
    <div class="relative flex items-start text-gray-400 dark:text-gray-500">
      <!-- Left Icon -->
      <SvgIcon
        v-if="iconLeft"
        :name="iconLeft"
        :size="iconSize"
        class="absolute left-3 top-3 pointer-events-none"
      />
      
      <!-- Textarea Field -->
      <textarea
        :id="textareaId"
        :value="modelValue"
        @input="handleInput"
        @blur="handleBlur"
        @focus="handleFocus"
        :placeholder="placeholder"
        :disabled="disabled"
        :readonly="readonly"
        :required="required"
        :rows="rows"
        :class="textareaClasses"
      />
      
      <!-- Right Icon -->
      <SvgIcon
        v-if="iconRight"
        :name="iconRight"
        :size="iconSize"
        class="absolute right-3 top-3 text-gray-400 dark:text-gray-500 pointer-events-none"
      />
    </div>
    
    <!-- Error Message -->
    <p v-if="error" class="text-xs text-red-600 dark:text-red-400 mt-1">
      {{ error }}
    </p>
    
    <!-- Help Text -->
    <p v-if="helpText && !error" class="text-xs text-gray-500 dark:text-gray-400 mt-1">
      {{ helpText }}
    </p>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import SvgIcon from '../SvgIcon.vue'

const props = defineProps({
  // v-model binding
  modelValue: {
    type: [String, Number],
    default: ''
  },
  
  // Input size
  size: {
    type: String,
    default: 'md',
    validator: (value) => ['sm', 'md', 'lg'].includes(value)
  },
  
  // Label
  label: {
    type: String,
    default: null
  },
  
  // Placeholder
  placeholder: {
    type: String,
    default: ''
  },
  
  // Disabled state
  disabled: {
    type: Boolean,
    default: false
  },
  
  // Readonly state
  readonly: {
    type: Boolean,
    default: false
  },
  
  // Required field
  required: {
    type: Boolean,
    default: false
  },
  
  // Error message
  error: {
    type: String,
    default: null
  },
  
  // Help text
  helpText: {
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
  
  // Number of visible rows
  rows: {
    type: [Number, String],
    default: 3
  },
  
  // Full width
  block: {
    type: Boolean,
    default: false
  },
  
  // Custom ID
  id: {
    type: String,
    default: null
  }
})

const emit = defineEmits(['update:modelValue', 'blur', 'focus', 'input'])

// Generate unique ID
const textareaId = computed(() => props.id || `textarea-${Math.random().toString(36).substr(2, 9)}`)

// Textarea classes
const textareaClasses = computed(() => {
  const classes = [
    'w-full',
    'px-3',
    'py-2',
    'text-sm',
    'text-gray-900',
    'dark:text-white',
    'bg-white',
    'dark:bg-gray-800/50',
    'border',
    'rounded-md',
    'transition-colors',
    'duration-200',
    '!focus:outline-none',
    '!focus:ring-0',
    'placeholder:text-gray-400',
    'dark:placeholder:text-gray-500',
    'resize-y',
    'min-h-[80px]'
  ]
  
  // Size
  if (props.size === 'sm') {
    classes.push('py-1.5', 'text-xs')
  } else if (props.size === 'lg') {
    classes.push('py-3', 'text-base')
  }
  
  // Error state
  if (props.error) {
    classes.push(
      'border-red-500',
      'dark:border-red-400',
      'focus:border-red-500'
    )
  } else {
    classes.push(
      'border-gray-300',
      'dark:border-gray-600',
      'focus:border-blue-500',
      'dark:focus:border-blue-400'
    )
  }
  
  // Icons
  if (props.iconLeft) {
    classes.push('pl-10')
  }
  
  if (props.iconRight) {
    classes.push('pr-10')
  }
  
  // Disabled
  if (props.disabled) {
    classes.push(
      'bg-gray-100',
      'dark:bg-gray-900',
      'cursor-not-allowed',
      'opacity-60'
    )
  }
  
  // Readonly
  if (props.readonly) {
    classes.push(
      'bg-gray-50',
      'dark:bg-gray-900/50',
      'cursor-default'
    )
  }
  
  // Block
  if (props.block) {
    classes.push('w-full')
  }
  
  return classes.join(' ')
})

// Handle input
const handleInput = (event) => {
  emit('update:modelValue', event.target.value)
  emit('input', event)
}

// Handle blur
const handleBlur = (event) => {
  emit('blur', event)
}

// Handle focus
const handleFocus = (event) => {
  emit('focus', event)
}
</script>

<style scoped>
textarea:focus {
  outline: none !important;
  outline-offset: 0 !important;
}
</style>
