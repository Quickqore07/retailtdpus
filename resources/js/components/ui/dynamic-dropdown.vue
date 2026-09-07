<template>
  <div class="select-form flex flex-col gap-1.5" :class="customClass">
    <label v-if="label" :for="label" class="block text-sm font-medium text-gray-700 dark:text-gray-200 ">
      {{ label }}
      <span v-if="required" class="text-red-500">*</span>
    </label>
    <div 
      :class="['select-input', { 'select-disabled': disabled, 'select-error': error }]"
      :style="{ height: 'auto' }" 
      :tabindex="disabled ? -1 : tabindex" 
      ref="toggleRef" 
      @click="toggle"
      @keydown.down.prevent="onKeydownMain"
    >
      <div class="select-text max-h-40 overflow-y-auto" v-if="multiple">
        <div class="select-tags" v-if="modelValue && modelValue.length">
          <div class="tag tag-primary" v-for="(item, i) in modelValue" :key="item.id || i">
            <span class="tag-text">
              {{ item[displayName] }}
            </span>
            <SvgIcon name="x" size="sm" class="tag-close" @mousedown.prevent="remove(item, i)" />
          </div>
        </div>
        <div v-else>{{ placeholder || 'Type or select' }}</div>
      </div>
      <div class="select-text" v-else>
        {{ modelValue && modelValue[displayName] ? modelValue[displayName] : (placeholder || 'Select') }}
      </div>
      <span 
        v-if="removable && modelValue && modelValue.id" 
        class="select-remove icon icon-trash-a" 
        @click.stop="removeVal"
      ></span>
      <span 
        v-else 
        :class="[`select-icon icon icon-arrow-${showDropdown ? 'up-b' : 'down-b'}`]"
      ></span>
    </div>

    <Teleport to="body">
      <div
        v-if="showDropdown"
        ref="dropdownRef"
        class="select-dropdown"
        :style="dropdownStyle"
      >
        <div class="select-inner">
          <div class="select-search-wrap" v-if="searchable">
            <input 
              type="text" 
              ref="searchRef" 
              class="select-search" 
              :placeholder="searchPlaceholder || 'Search...'"
              @keydown.down.prevent="onDownKey" 
              @keydown.enter.prevent="onEnter" 
              @keydown.up.prevent="onUpKey"
              @keydown.esc="onBlur" 
              @input="onSearch" 
              @blur="onBlur"
            >
          </div>
          <div class="select-items" ref="itemsRef">
            <div 
              v-if="isLoading" 
              class="select-item select-loading"
            >
              <span>Loading...</span>
            </div>
            <div 
              v-else-if="!availableOptions.length" 
              class="select-item select-empty"
            >
              <span>No results found</span>
            </div>
            <div 
              v-else
              v-for="(option, i) in availableOptions"
              :key="option.id || i"
              :class="[
                'select-item', 
                selectIndex === i ? 'select-active' : '',
                isSelected(option) ? 'select-selected' : ''
              ]" 
              @mouseover.prevent="onMouse(i)"
              @mousedown.prevent="select(option)" 
            >
              <span>{{ option[displayName] }}</span>
            </div>
          </div>
        </div>
      </div>
    </Teleport>
    <div class="select-error-message" v-if="error">{{ error }}</div>
  </div>
</template>

<script setup>
import { ref, computed, watch, onMounted, onBeforeUnmount, nextTick } from 'vue'
import axios from 'axios'
import SvgIcon from '@/components/SvgIcon.vue'

// Simple debounce utility function
const debounce = (func, wait) => {
  let timeout
  return function executedFunction(...args) {
    const later = () => {
      clearTimeout(timeout)
      func(...args)
    }
    clearTimeout(timeout)
    timeout = setTimeout(later, wait)
  }
}

const props = defineProps({
  label: {
    type: String,
    default: null
  },
  required: {
    type: Boolean,
    default: false
  },
  error: {
    type: String,
    default: null
  },
  modelValue: {
    type: [Object, Array],
    default: null
  },
  resource: {
    type: String,
    required: false
  },
  column: {
    type: String,
    default: 'name'
  },
  tabindex: {
    type: Number,
    default: 0
  },
  disabled: {
    type: Boolean,
    default: false
  },
  multiple: {
    type: Boolean,
    default: false
  },
  removable: {
    type: Boolean,
    default: false
  },
  displayName: {
    type: String,
    default: 'name'
  },
  placeholder: {
    type: String,
    default: ''
  },
  searchPlaceholder: {
    type: String,
    default: 'Search...'
  },
  params: {
    type: Object,
    default: () => ({})
  },
  // Custom options if you want to pass options directly instead of fetching
  customOptions: {
    type: Array,
    default: null
  },
  searchable: {
    type: Boolean,
    default: true
  },
  removeNullOption: {
    type: Boolean,
    default: false
  },
  customClass: {
    type: String,
    default: ''
  }
})

const emit = defineEmits(['update:modelValue', 'change'])

// Refs
const toggleRef = ref(null)
const searchRef = ref(null)
const itemsRef = ref(null)
const dropdownRef = ref(null)

// State
const isLoading = ref(false)
const showDropdown = ref(false)
const selectIndex = ref(-1)
const search = ref('')
const options = ref([])
const selectedValue = ref([])
const dropdownStyle = ref({})

// Computed
const availableOptions = computed(() => {
  // if (props.multiple && Array.isArray(props.modelValue)) {
  //   return options.value.filter(option => 
  //     !selectedValue.value.includes(option.id)
  //   )
  // }
  return options.value
})

// Watch for modelValue changes
watch(() => props.modelValue, (newVal) => {
  if (props.multiple && Array.isArray(newVal)) {
    selectedValue.value = newVal.map(item => item.id)
  }
}, { immediate: true, deep: true })

// Methods
const removeVal = () => {
  const payload = props.multiple ? [] : { id: null }
  emit('update:modelValue', payload)
  emit('change', payload)
}

const remove = (item, index) => {
  if (!props.multiple) return
  
  const payload = [...props.modelValue]
  payload.splice(index, 1)
  selectedValue.value = payload.map(item => item.id)
  emit('update:modelValue', payload)
  emit('change', payload)
}

const onSearch = (event) => {
  search.value = event.target.value
  if (props.customOptions) {
    filterCustomOptions(search.value)
  } else {
    fetch(search.value)
  }
}

const filterCustomOptions = (query) => {
  if (!query) {
    options.value = prepareOptions(props.customOptions)
    return
  }
  
  const filtered = props.customOptions.filter(option => 
    option[props.displayName]
      ?.toLowerCase()
      .includes(query.toLowerCase())
  )
  options.value = prepareOptions(filtered)
}

const fetch = debounce(function (q) {
  if (props.customOptions) {
    filterCustomOptions(q)
    return
  }

  isLoading.value = true
  
  axios.get(`/api/search/${props.resource}`, {
    params: {
      query: q,
      column: props.column,
      ...props.params
    }
  })
    .then((res) => {
      if (res.data) {
        const odata = res.data.collection || res.data.data || res.data || []
        options.value = prepareOptions(odata)
        // Set the selected index after options are loaded
        setInitialSelectedIndex()
        updateDropdownPosition()
      }
      isLoading.value = false
    })
    .catch((error) => {
      console.error('Error fetching options:', error)
      options.value = []
      isLoading.value = false
    })
}, 500)

const prepareOptions = (data) => {
  const preparedData = [...data]
  
  if (props.multiple && !props.customOptions?.length) {
    // Add "Select All" option for multiple select
    preparedData.unshift({
      [props.displayName]: 'Select All',
      id: '0',
      value: '0'
    })
  } else if (!props.customOptions?.length && !props.removeNullOption) {
    // Add "None" option for single select
    preparedData.unshift({
      [props.displayName]: 'None',
      id: '',
      value: '',
      description: 'None',
      code: 'None'
    })
  }
  
  return preparedData
}

const onUpKey = () => {
  if (props.disabled) return

  if (selectIndex.value > 0) {
    selectIndex.value--
    if (selectIndex.value > 4) {
      itemsRef.value.scrollTop -= 28
    }
  } else {
    selectIndex.value = options.value.length - 1
    itemsRef.value.scrollTop = selectIndex.value * 28
  }
}

const onDownKey = () => {
  if (props.disabled) return

  if (!showDropdown.value) {
    open()
  }

  if (options.value.length - 1 > selectIndex.value) {
    selectIndex.value++
    if (selectIndex.value > 4) {
      itemsRef.value.scrollTop += 28
    }
  } else {
    selectIndex.value = 0
    itemsRef.value.scrollTop = 0
  }
}

const onKeydownMain = () => {
  open()
}

const select = (option) => {
  if (props.multiple) {
    const current = [...(props.modelValue || [])]

    // Handle "Select All"
    if (option.id === '0') {
      const allOptions = availableOptions.value.filter(item => item.id !== '0')
      const existingIds = new Set(current.map(item => item.id))
      const payload = [
        ...current,
        ...allOptions.filter(item => !existingIds.has(item.id)),
      ]
      selectedValue.value = payload.map(item => item.id)
      emit('update:modelValue', payload)
      emit('change', payload)
      close()
      return
    }

    const existingIndex = current.findIndex(item => item.id === option.id)
    const payload = existingIndex >= 0
      ? current.filter((_, index) => index !== existingIndex)
      : [...current, option]
    selectedValue.value = payload.map(item => item.id)
    emit('update:modelValue', payload)
    emit('change', payload)
  } else {
    emit('update:modelValue', option)
    emit('change', option)
  }
  
  if (!props.multiple) {
    close()
  }
}

const onEnter = () => {
  if (props.disabled) return
  if (selectIndex.value < 0) return

  const option = options.value[selectIndex.value]
  select(option)
}

const onBlur = () => {
  // Delay to allow click events to fire
  setTimeout(() => {
    close()
  }, 200)
}

const onMouse = (index) => {
  selectIndex.value = index
}

const close = () => {
  showDropdown.value = false
  selectIndex.value = -1
  search.value = ''
  dropdownStyle.value = {}
  if (!props.customOptions) {
    options.value = []
  }
}

const updateDropdownPosition = () => {
  if (!showDropdown.value || !toggleRef.value) return

  const rect = toggleRef.value.getBoundingClientRect()
  const dropdownEl = dropdownRef.value
  const menuHeight = dropdownEl?.offsetHeight ?? 300
  const gap = 4
  const pad = 8
  const width = rect.width

  let top = rect.bottom + gap
  let left = rect.left

  if (top + menuHeight > window.innerHeight - pad && rect.top - menuHeight - gap >= pad) {
    top = rect.top - menuHeight - gap
  }

  left = Math.max(pad, Math.min(left, window.innerWidth - width - pad))

  dropdownStyle.value = {
    position: 'fixed',
    top: `${top}px`,
    left: `${left}px`,
    width: `${width}px`,
    zIndex: 10000,
  }
}

const onScrollOrResize = () => {
  if (showDropdown.value) {
    updateDropdownPosition()
  }
}

const open = () => {
  if (props.disabled) return
  
  showDropdown.value = true
  
  // Use nextTick equivalent with setTimeout
  setTimeout(() => {
    searchRef.value?.focus()
    if (props.customOptions) {
      options.value = prepareOptions(props.customOptions)
      // Set the selected index for custom options
      setInitialSelectedIndex()
    } else if (!options.value.length) {
      fetch('')
      // setInitialSelectedIndex() will be called after fetch completes
    } else {
      // Options already loaded, set the selected index
      setInitialSelectedIndex()
    }

    nextTick(() => {
      updateDropdownPosition()
      requestAnimationFrame(() => updateDropdownPosition())
    })
  }, 0)
}

const isSelected = (option) => {
  if (!props.modelValue) return false
  
  if (props.multiple && Array.isArray(props.modelValue)) {
    return props.modelValue.some(item => item.id === option.id)
  }
  
  return props.modelValue?.id === option.id
}

const setInitialSelectedIndex = () => {
  if (!props.modelValue) {
    selectIndex.value = -1
    return
  }
  
  if (props.multiple) {
    selectIndex.value = 0
    return
  }
  
  // Find the index of the currently selected value
  const currentValueId = props.modelValue?.id
  if (currentValueId) {
    const index = options.value.findIndex(opt => opt.id === currentValueId)
    if (index !== -1) {
      selectIndex.value = index
      // Scroll to the selected item
      setTimeout(() => {
        if (itemsRef.value) {
          const itemHeight = 28 // Approximate height of each item
          itemsRef.value.scrollTop = Math.max(0, (index - 2) * itemHeight)
        }
      }, 10)
    }
  }
}

const toggle = () => {
  if (props.disabled) return

  if (showDropdown.value) {
    close()
  } else {
    open()
  }
}

// Click outside handler
const handleClickOutside = (event) => {
  if (!toggleRef.value || !dropdownRef.value) return
  
  const target = event.target
  
  // Check if click is outside both the toggle and dropdown
  if (!toggleRef.value.contains(target) && 
      (!dropdownRef.value || !dropdownRef.value.contains(target))) {
    close()
  }
}

// Setup click outside listener
onMounted(() => {
  document.addEventListener('mousedown', handleClickOutside)
  window.addEventListener('scroll', onScrollOrResize, true)
  window.addEventListener('resize', onScrollOrResize)
})

onBeforeUnmount(() => {
  document.removeEventListener('mousedown', handleClickOutside)
  window.removeEventListener('scroll', onScrollOrResize, true)
  window.removeEventListener('resize', onScrollOrResize)
})

watch(showDropdown, (isOpen) => {
  if (isOpen) {
    nextTick(() => {
      updateDropdownPosition()
      requestAnimationFrame(() => updateDropdownPosition())
    })
  }
})

watch(() => props.customOptions, () => {
  if (showDropdown.value && props.customOptions) {
    filterCustomOptions(search.value)
    nextTick(() => updateDropdownPosition())
  }
})

// Expose methods if needed
defineExpose({
  open,
  close,
  toggle
})
</script>

<style scoped>
.select-form {
  position: relative;
  width: 100%;
}

.select-input {
  position: relative;
  display: flex;
  align-items: center;
  min-height: 36px;
  padding: 6px 12px;
  background: white;
  border: 1px solid #d1d5db;
  border-radius: 6px;
  cursor: pointer;
  transition: all 0.2s;
}

.select-input:hover:not(.select-disabled) {
  border-color: #9ca3af;
}

.select-input:focus {
  outline: none;
  border-color: #3b82f6;
  box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

.select-input.select-error {
  border-color: #ef4444;
  box-shadow: 0 0 0 3px rgba(239, 68, 68, 0.1);
}

.select-input.select-disabled {
  background-color: #f3f4f6;
  cursor: not-allowed;
  opacity: 0.6;
}

.select-text {
  flex: 1;
  font-size: 14px;
  color: #374151;
}

.select-tags {
  display: flex;
  flex-wrap: wrap;
  gap: 6px;
}

.tag {
  display: inline-flex;
  align-items: center;
  padding: 4px 8px;
  background-color: #3b82f6;
  color: white;
  border-radius: 4px;
  font-size: 13px;
  gap: 6px;
}

.tag-text {
  max-width: 150px;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.tag-close {
  cursor: pointer;
  font-size: 12px;
  opacity: 0.8;
  transition: opacity 0.2s;
}

.tag-close:hover {
  opacity: 1;
}

.select-icon,
.select-remove {
  font-size: 16px;
  color: #6b7280;
  transition: color 0.2s;
}

.select-remove {
  cursor: pointer;
}

.select-remove:hover {
  color: #ef4444;
}

.select-dropdown {
  background: white;
  border: 1px solid #d1d5db;
  border-radius: 6px;
  box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
  max-height: 300px;
  overflow: hidden;
}

.select-inner {
  display: flex;
  flex-direction: column;
  height: 100%;
}

.select-search-wrap {
  padding: 8px;
  border-bottom: 1px solid #e5e7eb;
}

.select-search {
  width: 100%;
  padding: 8px 12px;
  border: 1px solid #d1d5db;
  border-radius: 4px;
  font-size: 14px;
  outline: none;
  transition: border-color 0.2s;
}

.select-search:focus {
  border-color: #3b82f6;
}

.select-items {
  max-height: 250px;
  overflow-y: auto;
}

.select-item {
  padding: 8px 12px;
  cursor: pointer;
  font-size: 14px;
  color: #374151;
  transition: background-color 0.15s;
}

.select-item:hover {
  background-color: #f3f4f6;
}

.select-item.select-active {
  background-color: #dbeafe;
  color: #1e40af;
}

.select-item.select-selected {
  background-color: #e0e7ff;
  color: #4338ca;
  font-weight: 500;
}

.select-item.select-selected.select-active {
  background-color: #c7d2fe;
  color: #3730a3;
}

.select-loading,
.select-empty {
  text-align: center;
  color: #6b7280;
  font-style: italic;
  cursor: default;
}

.select-loading:hover,
.select-empty:hover {
  background-color: transparent;
}

/* Dark mode support */
:deep(.dark) .select-input,
.dark .select-input {
  background: #1f2937;
  border-color: #374151;
}

:deep(.dark) .select-input:hover:not(.select-disabled),
.dark .select-input:hover:not(.select-disabled) {
  border-color: #4b5563;
}

:deep(.dark) .select-input.select-disabled,
.dark .select-input.select-disabled {
  background-color: #111827;
}

:deep(.dark) .select-text,
.dark .select-text {
  color: #e5e7eb;
}

:deep(.dark) .select-dropdown,
.dark .select-dropdown {
  background: #1f2937;
  border-color: #374151;
}

:deep(.dark) .select-search-wrap,
.dark .select-search-wrap {
  border-bottom-color: #374151;
}

:deep(.dark) .select-search,
.dark .select-search {
  background: #111827;
  border-color: #374151;
  color: #e5e7eb;
}

:deep(.dark) .select-item,
.dark .select-item {
  color: #e5e7eb;
}

:deep(.dark) .select-item:hover,
.dark .select-item:hover {
  background-color: #374151;
}

:deep(.dark) .select-item.select-active,
.dark .select-item.select-active {
  background-color: #1e40af;
  color: white;
}

:deep(.dark) .select-item.select-selected,
.dark .select-item.select-selected {
  background-color: #4338ca;
  color: white;
  font-weight: 500;
}

:deep(.dark) .select-item.select-selected.select-active,
.dark .select-item.select-selected.select-active {
  background-color: #4f46e5;
  color: white;
}

:deep(.dark) .select-icon,
:deep(.dark) .select-remove,
.dark .select-icon,
.dark .select-remove {
  color: #9ca3af;
}

:deep(.dark) .select-remove:hover,
.dark .select-remove:hover {
  color: #f87171;
}

/* Dark mode scrollbar */
:deep(.dark) .select-items::-webkit-scrollbar-track,
.dark .select-items::-webkit-scrollbar-track {
  background: #1f2937;
}

:deep(.dark) .select-items::-webkit-scrollbar-thumb,
.dark .select-items::-webkit-scrollbar-thumb {
  background: #4b5563;
}

:deep(.dark) .select-items::-webkit-scrollbar-thumb:hover,
.dark .select-items::-webkit-scrollbar-thumb:hover {
  background: #6b7280;
}

/* Scrollbar styling */
.select-items::-webkit-scrollbar {
  width: 8px;
}

.select-items::-webkit-scrollbar-track {
  background: #f1f1f1;
}

.select-items::-webkit-scrollbar-thumb {
  background: #888;
  border-radius: 4px;
}

.select-items::-webkit-scrollbar-thumb:hover {
  background: #555;
}

.select-error-message {
  color: #ef4444;
  font-size: 12px;
  margin-top: 4px;
}
</style>

