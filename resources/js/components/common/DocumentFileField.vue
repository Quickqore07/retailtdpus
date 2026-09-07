<template>
    <div class="flex flex-col gap-1.5">
        <label v-if="label" :for="inputId" class="block text-sm font-medium text-gray-700 dark:text-gray-200">
            {{ label }}
            <span v-if="required" class="text-red-500">*</span>
        </label>

        <div
            class="border-2 border-dashed rounded-lg p-4 text-center transition-colors cursor-pointer"
            :class="isDragging
                ? 'border-primary bg-primary/5 dark:bg-primary/10'
                : 'border-gray-300 dark:border-gray-600 hover:border-primary dark:hover:border-primary hover:bg-gray-50 dark:hover:bg-gray-800'"
            @dragover.prevent="isDragging = true"
            @dragleave.prevent="isDragging = false"
            @drop.prevent="handleDrop"
            @click="fileInput?.click()"
        >
            <input
                :id="inputId"
                ref="fileInput"
                type="file"
                class="hidden"
                :accept="accept"
                :disabled="disabled"
                @change="handleFileSelect"
            />

            <SvgIcon name="files" size="lg" class="mx-auto text-gray-400 dark:text-gray-500 mb-2" />

            <p v-if="selectedFileName" class="text-sm font-medium text-gray-900 dark:text-white !mb-1">
                {{ selectedFileName }}
            </p>
            <p v-else class="text-sm text-gray-600 dark:text-gray-300 !mb-1">
                {{ placeholder }}
            </p>
            <p v-if="helpText" class="text-xs text-gray-500 dark:text-gray-400 !mb-0">
                {{ helpText }}
            </p>
        </div>

        <div v-if="selectedFileName" class="flex justify-end">
            <Button
                type="button"
                variant="ghost"
                size="sm"
                @click.stop="clearFile"
            >
                Remove file
            </Button>
        </div>

        <p v-if="displayError" class="text-xs text-red-600 dark:text-red-400 mt-1">
            {{ displayError }}
        </p>
    </div>
</template>

<script setup>
import { computed, ref } from 'vue'
import SvgIcon from '@/components/SvgIcon.vue'
import Button from '@/components/ui/button.vue'
import {
  DOCUMENT_ACCEPT,
  DOCUMENT_HELP_TEXT,
  UPLOAD_MAX_SIZE_BYTES,
  UPLOAD_MAX_SIZE_NOTE,
  validateDocumentFileSize,
} from '@/utils/documentUpload'

const props = defineProps({
    modelValue: {
        type: File,
        default: null,
    },
    label: {
        type: String,
        default: '',
    },
    placeholder: {
        type: String,
        default: 'Click or drag a file here to upload',
    },
    helpText: {
        type: String,
        default: DOCUMENT_HELP_TEXT,
    },
    accept: {
        type: String,
        default: DOCUMENT_ACCEPT,
    },
    maxSize: {
        type: Number,
        default: UPLOAD_MAX_SIZE_BYTES,
    },
    required: {
        type: Boolean,
        default: false,
    },
    disabled: {
        type: Boolean,
        default: false,
    },
    error: {
        type: String,
        default: null,
    },
})

const emit = defineEmits(['update:modelValue'])

const fileInput = ref(null)
const isDragging = ref(false)
const validationError = ref(null)
const inputId = `document-file-${Math.random().toString(36).slice(2, 9)}`

const selectedFileName = computed(() => props.modelValue?.name || null)
const displayError = computed(() => props.error || validationError.value)

const resetInput = () => {
    if (fileInput.value) {
        fileInput.value.value = ''
    }
}

const setFile = (file) => {
    if (!file) {
        validationError.value = null
        emit('update:modelValue', null)
        return
    }

    const sizeError = validateDocumentFileSize(file, props.maxSize)
    if (sizeError) {
        validationError.value = sizeError
        resetInput()
        emit('update:modelValue', null)
        return
    }

    validationError.value = null
    emit('update:modelValue', file)
}

const handleFileSelect = (event) => {
    setFile(event.target?.files?.[0] || null)
}

const handleDrop = (event) => {
    isDragging.value = false
    setFile(event.dataTransfer?.files?.[0] || null)
}

const clearFile = () => {
    setFile(null)
    resetInput()
}
</script>
