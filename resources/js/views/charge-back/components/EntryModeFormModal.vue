<template>
    <Modal
        v-model="isOpen"
        :title="isEdit ? 'Edit Entry Mode' : 'New Entry Mode'"
        size="md"
        @close="handleClose"
    >
        <form @submit.prevent="handleSave" class="space-y-4">
            <Input
                v-model="form.name"
                label="Name"
                placeholder="e.g. Key entered, Swiped"
                :required="true"
                :error="errors.name?.[0] || null"
            />

            <div class="flex items-center justify-end gap-3 pt-2">
                <Button variant="outline-secondary" size="md" type="button" @click="handleClose">
                    Cancel
                </Button>
                <Button variant="primary" size="md" type="submit" :loading="isSaving">
                    {{ isEdit ? 'Update Entry Mode' : 'Create Entry Mode' }}
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
    entryMode: {
        type: Object,
        default: null,
    },
})

const emit = defineEmits(['update:modelValue', 'saved'])

const message = useMessage()
const resource = 'charge-back-entry-modes'

const isOpen = computed({
    get: () => props.modelValue,
    set: (value) => emit('update:modelValue', value),
})

const isEdit = computed(() => !!props.entryMode?.id)
const isSaving = ref(false)
const errors = ref({})

const defaultForm = () => ({
    name: '',
})

const form = ref(defaultForm())

const loadForm = async () => {
    errors.value = {}

    if (props.entryMode?.id) {
        try {
            const response = await useRequest('get', `/${resource}/${props.entryMode.id}/edit`)
            form.value = { ...defaultForm(), ...(response.form || {}) }
        } catch {
            form.value = { ...defaultForm(), ...props.entryMode }
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
            ? await useRequest('put', `/${resource}/${props.entryMode.id}`, form.value)
            : await useRequest('post', `/${resource}`, form.value)

        if (response.saved) {
            message.success(response.message || 'Entry mode saved successfully')
            emit('saved')
            handleClose()
        }
    } catch (error) {
        if (error.response?.status === 422) {
            errors.value = error.response.data.errors || {}
        }
        message.error(error.response?.data?.message || 'Failed to save entry mode')
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
