<template>
    <Modal
        v-model="isOpen"
        :title="isEdit ? 'Edit Reason Code' : 'New Reason Code'"
        size="lg"
        @close="handleClose"
    >
        <form @submit.prevent="handleSave" class="space-y-4">
            <div class="grid grid-cols-1 gap-4">
                <Input
                    v-model="form.code"
                    label="Code"
                    placeholder="e.g. 4837, 10.4"
                    :required="true"
                    :error="errors.code?.[0] || null"
                />
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-1.5">
                        Description <span class="text-red-500">*</span>
                    </label>
                    <textarea
                        v-model="form.description"
                        rows="3"
                        placeholder="Enter reason code description"
                        class="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-3 py-2 text-sm text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary"
                    />
                    <p v-if="errors.description?.[0]" class="mt-1 text-xs text-red-600 dark:text-red-400">
                        {{ errors.description[0] }}
                    </p>
                </div>
            </div>

            <div class="flex items-center justify-end gap-3 pt-2">
                <Button variant="outline-secondary" size="md" type="button" @click="handleClose">
                    Cancel
                </Button>
                <Button variant="primary" size="md" type="submit" :loading="isSaving">
                    {{ isEdit ? 'Update Reason Code' : 'Create Reason Code' }}
                </Button>
            </div>
        </form>
    </Modal>
</template>

<script setup>
import { ref, computed, watch } from 'vue'
import Modal from '@/components/common/Modal.vue'
import Button from '@/components/ui/button.vue'
import Input from '@/components/ui/input.vue'
import { useRequest } from '@/services/api'
import { useMessage } from '@/composables/useMessage'

const props = defineProps({
    modelValue: {
        type: Boolean,
        default: false,
    },
    reasonCode: {
        type: Object,
        default: null,
    },
})

const emit = defineEmits(['update:modelValue', 'saved'])

const message = useMessage()
const resource = 'charge-back-reason-codes'

const isOpen = computed({
    get: () => props.modelValue,
    set: (value) => emit('update:modelValue', value),
})

const isEdit = computed(() => !!props.reasonCode?.id)
const isSaving = ref(false)
const errors = ref({})

const defaultForm = () => ({
    code: '',
    description: '',
})

const form = ref(defaultForm())

const loadForm = async () => {
    errors.value = {}

    if (props.reasonCode?.id) {
        try {
            const response = await useRequest('get', `/${resource}/${props.reasonCode.id}/edit`)
            form.value = { ...defaultForm(), ...(response.form || {}) }
        } catch {
            form.value = { ...defaultForm(), ...props.reasonCode }
        }
        return
    }

    try {
        const response = await useRequest('get', `/${resource}/create`)
        form.value = { ...defaultForm(), ...(response.form || {}) }
    } catch {
        form.value = defaultForm()
    }
}

const handleSave = async () => {
    isSaving.value = true
    errors.value = {}

    try {
        const response = isEdit.value
            ? await useRequest('put', `/${resource}/${props.reasonCode.id}`, form.value)
            : await useRequest('post', `/${resource}`, form.value)

        if (response.saved) {
            message.success(response.message || 'Reason code saved successfully')
            emit('saved')
            handleClose()
        }
    } catch (error) {
        if (error.response?.status === 422) {
            errors.value = error.response.data.errors || {}
        }
        message.error(error.response?.data?.message || 'Failed to save reason code')
    } finally {
        isSaving.value = false
    }
}

const handleClose = () => {
    form.value = defaultForm()
    errors.value = {}
    isOpen.value = false
}

watch(() => props.modelValue, (open) => {
    if (open) {
        loadForm()
    }
})
</script>
