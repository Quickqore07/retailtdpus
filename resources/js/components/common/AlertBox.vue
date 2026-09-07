<template>
    <Modal
        v-model="internalVisible"
        size="md"
        :show-footer="true"
        :show-cancel="true"
        :show-confirm="true"
        :cancel-text="cancelText"
        :confirm-text="confirmText"
        :close-on-backdrop="false"
        @close="handleCancel"
        @confirm="handleConfirm"
    >
        <template #header>
            <h5 class="text-lg font-bold !mb-0">{{ title }}</h5>
        </template>
        <p class="text-sm text-gray-700 dark:text-gray-200 !mb-0">
            {{ message }}
        </p>
    </Modal>
</template>

<script setup>
import { computed } from 'vue'
import Modal from '@/components/common/Modal.vue'

const props = defineProps({
    modelValue: {
        type: Boolean,
        default: false
    },
    title: {
        type: String,
        default: 'Confirm Action'
    },
    message: {
        type: String,
        default: ''
    },
    confirmText: {
        type: String,
        default: 'Okay'
    },
    cancelText: {
        type: String,
        default: 'Cancel'
    }
})

const emit = defineEmits(['update:modelValue', 'confirm', 'cancel'])

const internalVisible = computed({
    get: () => props.modelValue,
    set: (value) => emit('update:modelValue', value)
})

const handleConfirm = () => {
    emit('confirm')
    emit('update:modelValue', false)
}

const handleCancel = () => {
    emit('cancel')
    emit('update:modelValue', false)
}
</script>
