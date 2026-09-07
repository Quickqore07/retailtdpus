<template>
    <Modal
        v-model="isOpen"
        title="Upload sales receipt"
        size="md"
        :close-on-backdrop="!isUploading"
        :show-footer="true"
    >
        <div v-if="chargeback" class="space-y-4">
            <div class="rounded-lg border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800/50 p-3 text-sm">
                <div class="font-medium text-gray-900 dark:text-white">
                    {{ chargeback.case_number || chargeback.reference_number || `Chargeback #${chargeback.id}` }}
                </div>
                <div class="text-gray-500 dark:text-gray-400">
                    {{ chargeback.company?.name }}
                </div>
            </div>

            <DocumentFileField
                v-model="receiptFile"
                label="Sales Receipt"
                placeholder="Upload sales receipt"
                :error="error"
            />
        </div>

        <template #footer>
            <div class="flex items-center justify-end gap-3">
                <Button
                    type="button"
                    variant="outline-secondary"
                    size="md"
                    :disabled="isUploading"
                    @click="close"
                >
                    Cancel
                </Button>
                <Button
                    type="button"
                    variant="primary"
                    size="md"
                    :loading="isUploading"
                    :disabled="!receiptFile"
                    @click="handleUpload"
                >
                    Upload receipt
                </Button>
            </div>
        </template>
    </Modal>
</template>

<script setup>
import { computed, ref, watch } from 'vue'
import Modal from '@/components/common/Modal.vue'
import Button from '@/components/ui/button.vue'
import DocumentFileField from '@/components/common/DocumentFileField.vue'
import { validateDocumentFileSize } from '@/utils/documentUpload'
import { useRequest } from '@/services/api'
import { useMessage } from '@/composables/useMessage'

const props = defineProps({
    modelValue: {
        type: Boolean,
        default: false,
    },
    chargeback: {
        type: Object,
        default: null,
    },
})

const emit = defineEmits(['update:modelValue', 'uploaded'])

const message = useMessage()
const receiptFile = ref(null)
const isUploading = ref(false)
const error = ref(null)

const isOpen = computed({
    get: () => props.modelValue,
    set: (value) => emit('update:modelValue', value),
})

watch(isOpen, (open) => {
    if (!open) {
        receiptFile.value = null
        error.value = null
    }
})

const close = () => {
    isOpen.value = false
}

const handleUpload = async () => {
    if (!props.chargeback?.id || !receiptFile.value) {
        return
    }

    const sizeError = validateDocumentFileSize(receiptFile.value)
    if (sizeError) {
        error.value = sizeError
        return
    }

    isUploading.value = true
    error.value = null

    try {
        const payload = new FormData()
        payload.append('sales_receipt', receiptFile.value)

        const response = await useRequest(
            'post',
            `/charge-backs/${props.chargeback.id}/upload-sales-receipt`,
            payload,
            {
                headers: {
                    'Content-Type': 'multipart/form-data',
                },
            },
        )

        if (response.saved) {
            message.success(response.message || 'Sales receipt uploaded successfully.')
            emit('uploaded', response.model)
            close()
        }
    } catch (uploadError) {
        if (uploadError.response?.status === 422) {
            error.value = uploadError.response.data?.message
                || uploadError.response.data?.errors?.sales_receipt?.[0]
                || 'Validation error occurred.'
        } else {
            message.error(uploadError.response?.data?.message || 'Failed to upload sales receipt.')
        }
    } finally {
        isUploading.value = false
    }
}
</script>
