<template>
  <Modal
    v-model="isOpen"
    title="Create Expense Type"
    size="lg"
    @close="handleClose"
  >
    <form @submit.prevent="handleSave" class="space-y-4">
      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <Input
          v-model="form.name"
          label="Name"
          placeholder="Enter display name"
          :required="true"
          :error="errors.name?.[0] || null"
          icon-left="tag"
        />
        <Input
          v-model="form.amount_label"
          label="Amount Label"
          placeholder="e.g. Amount, Fees, Electric"
          :required="true"
          :error="errors.amount_label?.[0] || null"
        />
        <Input
          v-model="form.other_amount_label"
          label="Other Amount Label"
          placeholder="e.g. Tickets, Gas"
          :required="true"
          :error="errors.other_amount_label?.[0] || null"
        />

        <div class="flex flex-col gap-1.5">
          <label class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-2">
            Show Other Amount
          </label>
          <label class="flex items-center space-x-2 cursor-pointer">
            <input
              type="checkbox"
              v-model="form.show_other_amount"
              :true-value="true"
              :false-value="false"
              class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500 dark:focus:ring-blue-600 dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600"
            />
            <span class="text-sm text-gray-700 dark:text-gray-300">
              Show second amount field on purchase invoice
            </span>
          </label>
        </div>

        <div class="flex flex-col gap-1.5">
          <label class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-2">
            Status
          </label>
          <label class="flex items-center space-x-2 cursor-pointer">
            <input
              type="checkbox"
              v-model="form.active"
              :true-value="true"
              :false-value="false"
              class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500 dark:focus:ring-blue-600 dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600"
            />
            <span class="text-sm text-gray-700 dark:text-gray-300">
              Active
            </span>
          </label>
        </div>
      </div>

      <div class="flex items-center justify-end gap-3 pt-2">
        <Button variant="outline-secondary" size="md" type="button" @click="handleClose">
          Cancel
        </Button>
        <Button variant="primary" size="md" type="submit" :loading="isSaving">
          Create Expense Type
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
})

const emit = defineEmits(['update:modelValue', 'created'])

const message = useMessage()
const resource = 'ap/expense-types'

const isOpen = computed({
  get: () => props.modelValue,
  set: (value) => emit('update:modelValue', value),
})

const isSaving = ref(false)
const errors = ref({})

const defaultForm = () => ({
  name: '',
  amount_label: 'Amount',
  other_amount_label: 'Other Amount',
  show_other_amount: true,
  active: true,
})

const form = ref(defaultForm())

const loadDefaults = async () => {
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
    const response = await useRequest('post', `/${resource}`, form.value)

    if (response.saved) {
      const showResponse = await useRequest('get', `/${resource}/${response.id}`)
      const created = showResponse.model || { id: response.id, ...form.value }

      message.success(response.message || 'Expense type created successfully')
      emit('created', created)
      handleClose()
    }
  } catch (error) {
    if (error.response?.status === 422) {
      errors.value = error.response.data.errors || {}
    }
    message.error(error.response?.data?.message || 'Failed to create expense type')
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
    loadDefaults()
  }
})
</script>
