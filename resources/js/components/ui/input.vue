<template>
  <div class="flex flex-col gap-1.5">
    <label v-if="label" :for="inputId" class="block text-sm font-medium text-gray-700 dark:text-gray-200 ">
      {{ label }}
      <span v-if="required" class="text-red-500">*</span>
    </label>
    
    <div class="relative flex items-center text-gray-400 dark:text-gray-500">
      <!-- Left Icon -->
      <SvgIcon
        v-if="iconLeft"
        :name="iconLeft"
        :size="iconSize"
        class="absolute left-3  pointer-events-none"
      />
      
      <!-- Input Field -->
      <input
        v-if="!disabled && !readonly"
        :id="inputId"
        :name="name"
        :type="computedType"
        :value="modelValue"
        @input="handleInput"
        @blur="handleBlur"
        @focus="handleFocus"
        :placeholder="placeholder"
        :disabled="disabled"
        :readonly="readonly"
        :required="required"
        :min="min"
        :max="max"
        :step="step"
        :class="inputClasses"
        @wheel.prevent
      />
      <div v-else-if="disabled || readonly" :class="iconLeft ? 'pl-10' : 'pl-3'" class=" w-full min-h-[33px] px-3 py-1.5 text-sm text-gray-900 dark:text-white bg-gray-100 dark:bg-gray-900/50 border rounded-md transition-colors duration-200 !focus:outline-none !focus:ring-0 placeholder:text-gray-400 dark:placeholder:text-gray-500 border-gray-300 dark:border-gray-600 focus:border-blue-500 dark:focus:border-blue-400">
        {{ modelValue }}
      </div>
      
      <!-- Password Toggle Button -->
      <button
        v-if="type === 'password'"
        type="button"
        @click="togglePasswordVisibility"
        class="absolute flex items-center justify-center right-3 text-gray-400 dark:text-gray-500 hover:text-gray-600 dark:hover:text-gray-300 transition-colors cursor-pointer"
        style="outline: none !important; box-shadow: none !important; border: none !important; background: transparent !important;"
        :disabled="disabled"
      >
        <SvgIcon
          :name="showPassword ? 'eye-off' : 'eye'"
          :size="iconSize"
        />
      </button>
      
      <!-- Right Icon -->
      <SvgIcon
        v-else-if="iconRight"
        :name="iconRight"
        :size="iconSize"
        class="absolute right-3 text-gray-400 dark:text-gray-500 pointer-events-none"
      />
    </div>
    
    <!-- Error Message -->
    <p v-if="error" class="text-xs text-red-600 dark:text-red-400 mt-1">
      {{ error }}
    </p>
    
    <!-- Help Text -->
    <p v-if="helpText && !error" class="text-xs text-gray-500 dark:text-gray-400 mt-1 !mb-0">
      {{ helpText }}
    </p>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import SvgIcon from '../SvgIcon.vue'

const props = defineProps({
  // v-model binding
  modelValue: {
    type: [String, Number],
    default: ''
  },
  
  // Input type
  type: {
    type: String,
    default: 'text',
    validator: (value) => [
      'text',
      'email',
      'password',
      'number',
      'tel',
      'url',
      'search',
      'date',
      'time',
      'datetime-local'
    ].includes(value)
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
  
  // Number input specific
  min: {
    type: [Number, String],
    default: null
  },
  
  max: {
    type: [Number, String],
    default: null
  },
  
  step: {
    type: [Number, String],
    default: null
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
  },
  name: {
    type: String,
    default: null
  }
})

const emit = defineEmits(['update:modelValue', 'blur', 'focus', 'input'])

// Generate unique ID
const inputId = computed(() => props.id || `input-${Math.random().toString(36).substr(2, 9)}`)

// Password visibility toggle
const showPassword = ref(false)

const computedType = computed(() => {
  if (props.type === 'password' && showPassword.value) {
    return 'text'
  }
  return props.type
})

const togglePasswordVisibility = () => {
  showPassword.value = !showPassword.value
}

// Input classes
const inputClasses = computed(() => {
  const classes = [
    'w-full',
    'px-3',
    'py-1',
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
    'dark:placeholder:text-gray-500'
  ]
  
  // Size
  if (props.size === 'sm') {
    classes.push('py-1', 'text-xs')
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
  
  if (props.iconRight || props.type === 'password') {
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
  let value = event.target.value
  
  // Convert to number for number inputs
  if (props.type === 'number' && value !== '') {
    value = Number(value)
  }
  
  emit('update:modelValue', value)
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
input:focus {
  outline: none !important;
  outline-offset: 0 !important;
}
</style>

