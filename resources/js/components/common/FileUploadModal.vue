<template>
  <Modal 
    v-model="isOpen" 
    :title="title" 
    :size="size"
    :close-on-backdrop="!isUploading"
    :show-footer="true"
  >
    <div class="space-y-6">
      <!-- Upload Instructions -->
      <div 
        v-if="showInstructions && !pendingDateConfirmation"
        class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-3 mb-1"
      >
        <div class="flex items-start gap-3">
          <SvgIcon name="info" size="lg" class="text-blue-600 dark:text-blue-400 mt-0.5" />
          <div class="text-sm text-blue-800 dark:text-blue-300">
            <p class="font-medium mb-2">{{ instructionsTitle }}</p>
            <ul class="list-disc list-inside space-y-1 text-blue-700 dark:text-blue-400">
              <li v-for="(instruction, index) in instructions" :key="index">
                {{ instruction }}
              </li>
            </ul>
          </div>
        </div>
      </div>
      <div v-if="allowDownloadTemplate" class="flex items-start gap-3">
        <div v-for="template in templates" :key="template.name">
          <a :href="`/api/export-template/${template.name}`" target="_blank" class="hover:!underline">
            {{ template.label }}
          </a>
        </div>
      </div>

      <!-- Date Selector (Optional) -->
      <div v-if="requireDate" class="space-y-2">
        <Input
        label="Date"
          v-model="selectedDate"
          type="date"
          :required="dateRequired"
          :error="dateError ? dateError[0] : null"
        />
      </div>
      <slot name="extra"></slot>

      <!-- Import Type Dropdown (Optional, dynamic) -->
      <div v-if="importTypeOptions && importTypeOptions.length" class="space-y-2">
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">
          {{ importTypeLabel }}
          <span v-if="importTypeRequired" class="text-red-500">*</span>
        </label>
        <select
          v-model="selectedImportType"
          :required="importTypeRequired"
          :class="[
            'w-full px-3 py-2 text-sm rounded-md border transition-colors',
            'bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100',
            'border-gray-300 dark:border-gray-600',
            'focus:ring-2 focus:ring-primary/50 focus:border-primary',
            importTypeError ? 'border-red-500 dark:border-red-500' : ''
          ]"
        >
          <option value="">{{ importTypePlaceholder }}</option>
          <option
            v-for="opt in importTypeOptions"
            :key="opt.value"
            :value="opt.value"
          >
            {{ opt.label }}
          </option>
        </select>
        <p v-if="importTypeError" class="text-xs text-red-600 dark:text-red-400 mt-1">
          {{ importTypeError }}
        </p>
      </div>

        <!-- Date Confirmation -->
        <div
          v-if="pendingDateConfirmation"
          class="bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 rounded-lg p-4"
        >
          <div class="flex items-start gap-3">
            <SvgIcon name="info" size="lg" class="text-amber-600 dark:text-amber-400 mt-0.5" />
            <div class="flex-1 text-sm text-amber-900 dark:text-amber-200">
              <p class="font-medium mb-2">Confirm dates found in the sheet</p>
              <p class="mb-3 text-amber-800 dark:text-amber-300">
                Existing imported entries for these dates may be updated. Please review and confirm to continue.
              </p>
              <div class="flex flex-wrap gap-2 max-h-40 overflow-y-auto">
                <span
                  v-for="date in confirmationDates"
                  :key="date"
                  class="inline-flex items-center px-2.5 py-1 rounded-md bg-white dark:bg-gray-800 border border-amber-200 dark:border-amber-700 text-amber-900 dark:text-amber-100 text-xs font-medium"
                >
                  {{ date }}
                </span>
              </div>
            </div>
          </div>
        </div>

        <!-- File Upload Area -->
        <div v-if="!pendingDateConfirmation" class="space-y-4">
          <div
            @dragover.prevent="isDragging = true"
            @dragleave.prevent="isDragging = false"
            @drop.prevent="handleFileDrop"
            :class="[
              'border-2 border-dashed rounded-lg p-8 text-center transition-all cursor-pointer',
              isDragging 
                ? 'border-primary bg-primary/5 dark:bg-primary/10' 
                : 'border-gray-300 dark:border-gray-600 hover:border-primary dark:hover:border-primary hover:bg-gray-50 dark:hover:bg-gray-800'
            ]"
            @click="$refs.fileInput.click()"
          >
            <input
              ref="fileInput"
              type="file"
              :accept="acceptedFormats"
              :multiple="allowMultiple"
              class="hidden"
              @change="handleFileSelect"
              :disabled="isUploading"
            />

            <div v-if="!hasFiles" class="space-y-3">
              <div class="flex justify-center">
                <div class="w-16 h-16 rounded-full bg-gray-100 dark:bg-gray-700 flex items-center justify-center">
                  <SvgIcon :name="uploadIcon" size="xl" class="text-gray-400 dark:text-gray-500" />
                </div>
              </div>
              <div>
                <p class="text-base font-medium text-gray-900 dark:text-gray-100">
                  {{ isDragging ? dragText : uploadText }}
                </p>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                  {{ formatText }} (max. {{ maxSizeText }})
                </p>
              </div>
            </div>

            <div v-else class="space-y-3">
              <!-- Single File Display -->
              <div v-if="!allowMultiple && selectedFiles.length === 1">
                <div class="flex justify-center">
                  <div class="w-16 h-16 rounded-full bg-green-100 dark:bg-green-900/30 flex items-center justify-center">
                    <SvgIcon :name="successIcon" size="xl" class="text-green-600 dark:text-green-400" />
                  </div>
                </div>
                <div>
                  <p class="text-base font-medium text-gray-900 dark:text-gray-100 truncate">
                    {{ selectedFiles[0].name }}
                  </p>
                  <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                    {{ formatFileSize(selectedFiles[0].size) }}
                  </p>
                </div>
                <button
                  v-if="!isUploading"
                  @click.stop="removeAllFiles"
                  class="text-sm text-red-600 dark:text-red-400 hover:text-red-700 dark:hover:text-red-300 font-medium"
                >
                  Remove File
                </button>
              </div>

              <!-- Multiple Files Display -->
              <div v-else class="space-y-2">
                <div class="flex justify-center mb-3">
                  <div class="w-16 h-16 rounded-full bg-green-100 dark:bg-green-900/30 flex items-center justify-center">
                    <SvgIcon :name="successIcon" size="xl" class="text-green-600 dark:text-green-400" />
                  </div>
                </div>
                <p class="text-base font-medium text-gray-900 dark:text-gray-100 mb-3">
                  {{ selectedFiles.length }}{{ props.maxFiles != null ? ` of ${props.maxFiles}` : '' }} file(s) selected
                </p>
                <div class="max-h-40 overflow-y-auto space-y-2">
                  <div
                    v-for="(file, index) in selectedFiles"
                    :key="index"
                    class="flex items-center justify-between bg-gray-50 dark:bg-gray-700 rounded-lg p-2 text-left"
                  >
                    <div class="flex-1 min-w-0">
                      <p class="text-sm font-medium text-gray-900 dark:text-gray-100 truncate">
                        {{ file.name }}
                      </p>
                      <p class="text-xs text-gray-500 dark:text-gray-400">
                        {{ formatFileSize(file.size) }}
                      </p>
                    </div>
                    <button
                      v-if="!isUploading"
                      @click.stop="removeFile(index)"
                      class="ml-2 text-red-600 dark:text-red-400 hover:text-red-700 dark:hover:text-red-300"
                    >
                      <SvgIcon name="trash" size="sm" />
                    </button>
                  </div>
                </div>
                <button
                  v-if="!isUploading"
                  @click.stop="removeAllFiles"
                  class="text-sm text-red-600 dark:text-red-400 hover:text-red-700 dark:hover:text-red-300 font-medium mt-2"
                >
                  Remove All Files
                </button>
              </div>
            </div>
          </div>
        </div>

        <!-- Error Message -->
        <div v-if="uploadError" class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg p-4">
          <div class="flex items-start gap-3">
            <SvgIcon name="exclamation-circle" size="lg" class="text-red-600 dark:text-red-400 mt-0.5" />
            <div class="text-sm text-red-800 dark:text-red-300">
              <p class="font-medium">{{ errorTitle }}</p>
              <p class="mt-1">{{ uploadError }}</p>
            </div>
          </div>
        </div>

        <!-- Success Message -->
        <div v-if="uploadSuccess" class="bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg p-4">
          <div class="flex items-start gap-3">
            <SvgIcon name="check-circle" size="lg" class="text-green-600 dark:text-green-400 mt-0.5" />
            <div class="text-sm text-green-800 dark:text-green-300">
              <p class="font-medium">{{ successTitle }}</p>
              <p class="mt-1">{{ uploadSuccess }}</p>
            </div>
          </div>
        </div>

        <!-- Progress Bar -->
        <div v-if="isUploading" class="space-y-2">
          <div class="flex items-center justify-between text-sm">
            <span class="text-gray-700 dark:text-gray-300">{{ uploadingText }}</span>
            <span class="text-gray-500 dark:text-gray-400">{{ uploadProgress }}%</span>
          </div>
          <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2 overflow-hidden">
            <div 
              class="bg-primary h-full transition-all duration-300 ease-out"
              :style="{ width: uploadProgress + '%' }"
            ></div>
          </div>
        </div>
      </div>

    <!-- Footer -->
    <template #footer>
      <Button 
        :variant="cancelButtonVariant" 
        :size="buttonSize"
        @click="handleCancel"
        :disabled="isUploading"
      >
        {{ cancelButtonText }}
      </Button>
      <Button
        v-if="pendingDateConfirmation"
        :variant="uploadButtonVariant"
        :size="buttonSize"
        @click="handleConfirmDatesUpload"
        :disabled="isUploading"
      >
        {{ isUploading ? uploadingButtonText : 'Confirm & Upload' }}
      </Button>
      <Button 
        v-else
        :variant="uploadButtonVariant" 
        :size="buttonSize"
        @click="handleUpload"
        :disabled="!hasFiles || isUploading"
      >
        {{ isUploading ? uploadingButtonText : uploadButtonText }}
      </Button>
    </template>
  </Modal>
</template>

<script setup>
import { ref, computed, watch } from 'vue'
import Modal from './Modal.vue'
import Button from '@/components/ui/button.vue'
import SvgIcon from '@/components/SvgIcon.vue'
import { useRequest } from '@/services/api'
import Input from '../ui/input.vue'
import { UPLOAD_MAX_SIZE_BYTES, UPLOAD_MAX_SIZE_NOTE } from '@/utils/documentUpload'

const props = defineProps({
  modelValue: {
    type: Boolean,
    default: false
  },
  title: {
    type: String,
    default: 'Upload File'
  },
  size: {
    type: String,
    default: 'lg',
    validator: (value) => ['xs', 'sm', 'md', 'lg', 'xl', '2xl', 'full'].includes(value)
  },
  uploadUrl: {
    type: String,
    required: true
  },
  timeout: {
    type: Number,
    default: null
  },
  acceptedFormats: {
    type: String,
    default: '.csv,.xlsx,.xls'
  },
  acceptedMimeTypes: {
    type: Array,
    default: () => [
      'text/csv',
      'application/vnd.ms-excel',
      'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
    ]
  },
  acceptedExtensions: {
    type: Array,
    default: () => ['csv', 'xlsx', 'xls']
  },
  maxSize: {
    type: Number,
    default: UPLOAD_MAX_SIZE_BYTES
  },
  allowMultiple: {
    type: Boolean,
    default: false
  },
  maxFiles: {
    type: Number,
    default: 14
  },
  showInstructions: {
    type: Boolean,
    default: true
  },
  instructionsTitle: {
    type: String,
    default: 'Upload Guidelines:'
  },
  instructions: {
    type: Array,
    default: () => [
      'Supported formats: CSV, Excel (.xlsx, .xls)',
      UPLOAD_MAX_SIZE_NOTE,
      'Ensure your file has the required columns'
    ]
  },
  uploadText: {
    type: String,
    default: 'Click to upload or drag and drop'
  },
  dragText: {
    type: String,
    default: 'Drop your file here'
  },
  formatText: {
    type: String,
    default: 'CSV, XLSX, XLS'
  },
  uploadingText: {
    type: String,
    default: 'Uploading...'
  },
  errorTitle: {
    type: String,
    default: 'Upload Failed'
  },
  successTitle: {
    type: String,
    default: 'Upload Successful'
  },
  defaultSuccessMessage: {
    type: String,
    default: 'File uploaded successfully!'
  },
  cancelButtonText: {
    type: String,
    default: 'Cancel'
  },
  uploadButtonText: {
    type: String,
    default: 'Upload'
  },
  uploadingButtonText: {
    type: String,
    default: 'Uploading...'
  },
  cancelButtonVariant: {
    type: String,
    default: 'secondary'
  },
  uploadButtonVariant: {
    type: String,
    default: 'primary'
  },
  buttonSize: {
    type: String,
    default: 'sm'
  },
  uploadIcon: {
    type: String,
    default: 'upload'
  },
  successIcon: {
    type: String,
    default: 'file-check'
  },
  autoCloseOnSuccess: {
    type: Boolean,
    default: true
  },
  additionalData: {
    type: Object,
    default: () => ({})
  },
  axios: {
    type: Object,
    default: null
  },
  requireDate: {
    type: Boolean,
    default: false
  },
  dateLabel: {
    type: String,
    default: 'Select Date'
  },
  dateRequired: {
    type: Boolean,
    default: true
  },
  dateValue: {
    type: String,
    default: ''
  },
  // Dynamic import type dropdown: pass array of { value, label } to show dropdown
  importTypeOptions: {
    type: Array,
    default: () => null
  },
  importTypeLabel: {
    type: String,
    default: 'Import Type'
  },
  importTypePlaceholder: {
    type: String,
    default: 'Select type'
  },
  importTypeRequired: {
    type: Boolean,
    default: false
  },
  importTypeFieldName: {
    type: String,
    default: 'import_type'
  },
  templates: {
    type: Array,
    default: () => []
  },
  allowDownloadTemplate: {
    type: Boolean,
    default: false
  }
})

const emit = defineEmits(['update:modelValue', 'upload-success', 'upload-error', 'cancel', 'close', 'update:dateValue', 'update:importType'])

// Local state
const selectedFiles = ref([])
const isDragging = ref(false)
const isUploading = ref(false)
const uploadProgress = ref(0)
const uploadError = ref('')
const uploadSuccess = ref('')
const fileInput = ref(null)
const selectedDate = ref('')
const dateError = ref('')
const selectedImportType = ref( props.importTypeOptions ? props.importTypeOptions[0].value : '')
const importTypeError = ref('')
const pendingDateConfirmation = ref(false)
const confirmationDates = ref([])

// Computed
const isOpen = computed({
  get: () => props.modelValue,
  set: (value) => emit('update:modelValue', value)
})

const hasFiles = computed(() => selectedFiles.value.length > 0)

const maxSizeText = computed(() => {
  return formatFileSize(props.maxSize)
})

// Methods
const handleFileSelect = (event) => {
  const files = Array.from(event.target.files)
  if (files.length > 0) {
    if (props.allowMultiple) {
      files.forEach(file => validateAndAddFile(file))
    } else {
      validateAndSetFile(files[0])
    }
  }
}

const handleFileDrop = (event) => {
  isDragging.value = false
  const files = Array.from(event.dataTransfer.files)
  if (files.length > 0) {
    if (props.allowMultiple) {
      files.forEach(file => validateAndAddFile(file))
    } else {
      validateAndSetFile(files[0])
    }
  }
}

const validateAndSetFile = (file) => {
  // Reset messages
  uploadError.value = ''
  uploadSuccess.value = ''

  if (validateFile(file)) {
    selectedFiles.value = [file]
  }
}

const validateAndAddFile = (file) => {
  // Reset messages
  uploadError.value = ''
  uploadSuccess.value = ''

  if (props.maxFiles != null && selectedFiles.value.length >= props.maxFiles) {
    uploadError.value = `Maximum ${props.maxFiles} files allowed. Please remove some files before adding more.`
    return
  }

  if (validateFile(file)) {
    // Check if file already exists
    const exists = selectedFiles.value.some(f => f.name === file.name && f.size === file.size)
    if (!exists) {
      selectedFiles.value.push(file)
    }
  }
}

const validateFile = (file) => {
  // Validate file type
  const fileExtension = file.name.split('.').pop().toLowerCase()

  if (!props.acceptedMimeTypes.includes(file.type) && !props.acceptedExtensions.includes(fileExtension)) {
    uploadError.value = `Please upload a valid file. Accepted formats: ${props.acceptedExtensions.join(', ')}`
    return false
  }

  // Validate file size
  if (file.size > props.maxSize) {
    uploadError.value = `File size exceeds ${maxSizeText.value}. Please upload a smaller file.`
    return false
  }

  return true
}

const removeFile = (index) => {
  selectedFiles.value.splice(index, 1)
  uploadError.value = ''
  uploadSuccess.value = ''
}

const removeAllFiles = () => {
  selectedFiles.value = []
  uploadError.value = ''
  uploadSuccess.value = ''
  if (fileInput.value) {
    fileInput.value.value = ''
  }
}

const formatFileSize = (bytes) => {
  if (bytes === 0) return '0 Bytes'
  const k = 1024
  const sizes = ['Bytes', 'KB', 'MB', 'GB']
  const i = Math.floor(Math.log(bytes) / Math.log(k))
  return Math.round(bytes / Math.pow(k, i) * 100) / 100 + ' ' + sizes[i]
}

const handleUpload = async (options = {}) => {
  if (selectedFiles.value.length === 0) return

  const withConfirmation = options.confirmed === true

  // Validate date if required
  if (props.requireDate && props.dateRequired && !selectedDate.value) {
    dateError.value = 'Please select a date'
    return
  }

  // Validate import type if required
  if (props.importTypeOptions?.length && props.importTypeRequired && !selectedImportType.value) {
    importTypeError.value = `Please select ${props.importTypeLabel.toLowerCase()}`
    return
  }

  uploadError.value = ''
  uploadSuccess.value = ''
  dateError.value = ''
  importTypeError.value = ''
  isUploading.value = true
  uploadProgress.value = 0

  const formData = new FormData()
  
  // Add files (use files[] for Laravel to receive as array)
  if (props.allowMultiple) {
    selectedFiles.value.forEach((file) => {
      formData.append('files[]', file)
    })
  } else {
    formData.append('file', selectedFiles.value[0])
  }

  // Add date if provided
  if (props.requireDate && selectedDate.value) {
    formData.append('date', selectedDate.value)
  }

  // Add import type if dropdown is used and a value is selected
  if (props.importTypeOptions?.length && selectedImportType.value) {
    formData.append(props.importTypeFieldName, selectedImportType.value)
  }

  if (withConfirmation) {
    formData.append('confirmed', '1')
  }

  // Add additional data
  Object.keys(props.additionalData).forEach(key => {
    formData.append(key, props.additionalData[key])
  })

  try {
    const response = await useRequest('post', props.uploadUrl, formData, {
      headers: {
        'Content-Type': 'multipart/form-data'
      },
      timeout: props.timeout || undefined,
      onUploadProgress: (progressEvent) => {
        uploadProgress.value = Math.round((progressEvent.loaded * 100) / progressEvent.total)
      }
    })

    if (response?.needs_confirmation && Array.isArray(response.dates) && !withConfirmation) {
      pendingDateConfirmation.value = true
      confirmationDates.value = response.dates
      uploadProgress.value = 0
      return
    }

    pendingDateConfirmation.value = false
    confirmationDates.value = []
    uploadSuccess.value = response.message || props.defaultSuccessMessage
    emit('upload-success', response)

    if (props.autoCloseOnSuccess) {
      handleClose()
    }

  } catch (error) {
    let message = error.response?.data?.message || 'Failed to upload file. Please try again.'
    if(message && message.includes('Allowed memory size')){
      message = 'File is too large. Please upload a smaller file.'
    }
    uploadError.value = message
    emit('upload-error', error)
  } finally {
    isUploading.value = false
  }
}

const handleConfirmDatesUpload = () => {
  handleUpload({ confirmed: true })
}

const resetConfirmationState = () => {
  pendingDateConfirmation.value = false
  confirmationDates.value = []
}

const handleCancel = () => {
  emit('cancel')
  handleClose()
}

const handleClose = () => {
  isOpen.value = false
  emit('close')
  // Reset state after modal close animation
  setTimeout(() => {
    removeAllFiles()
    uploadProgress.value = 0
    selectedDate.value = ''
    dateError.value = ''
    selectedImportType.value = ''
    importTypeError.value = ''
    resetConfirmationState()
  }, 300)
}

// Watch for modal close
watch(isOpen, (newValue) => {
  if (!newValue && !isUploading.value) {
    setTimeout(() => {
      removeAllFiles()
      uploadProgress.value = 0
      selectedDate.value = ''
      dateError.value = ''
      selectedImportType.value = ''
      importTypeError.value = ''
      resetConfirmationState()
    }, 300)
  }
})

// Watch for date value prop changes
watch(() => props.dateValue, (newValue) => {
  selectedDate.value = newValue
}, { immediate: true })

// Emit date changes to parent
watch(selectedDate, (newValue) => {
  emit('update:dateValue', newValue)
  if (dateError.value) {
    dateError.value = ''
  }
})

// Emit import type changes to parent
watch(selectedImportType, (newValue) => {
  emit('update:importType', newValue)
  if (importTypeError.value) {
    importTypeError.value = ''
  }
})
</script>

<style scoped>
.bg-primary {
  background-color: var(--color-primary);
}
</style>

