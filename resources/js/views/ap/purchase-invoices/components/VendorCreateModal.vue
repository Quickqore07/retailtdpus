<template>
  <Modal
    v-model="isOpen"
    title="Create Vendor"
    size="2xl"
    :body-class="'max-h-[70vh] overflow-y-auto'"
    @close="handleClose"
  >
    <form @submit.prevent="handleSave" class="space-y-4">
      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <Input
          v-model="form.code"
          label="Code"
          placeholder="Enter vendor code"
          :error="errors.code?.[0] || null"
          icon-left="hash"
        />
        <Input
          v-model="form.name"
          label="Name"
          placeholder="Enter vendor name"
          :required="true"
          :error="errors.name?.[0] || null"
          icon-left="user"
        />
        <Input
          v-model="form.email"
          label="Email"
          placeholder="Enter email address"
          :error="errors.email?.[0] || null"
          icon-left="mail"
        />
        <Input
          v-model="form.mobile"
          label="Mobile Number"
          placeholder="Enter mobile number"
          :error="errors.mobile?.[0] || null"
          icon-left="phone"
        />
        <Input
          v-model="form.fax"
          label="Fax"
          placeholder="Enter fax number"
          :error="errors.fax?.[0] || null"
        />
        <Input
          v-model="form.credit_days"
          type="number"
          min="0"
          step="1"
          label="Credit Days"
          placeholder="e.g. 20"
          :error="errors.credit_days?.[0] || null"
        />
        <Input
          v-model="form.address_line_1"
          label="Address Line 1"
          placeholder="Street address"
          :error="errors.address_line_1?.[0] || null"
        />
        <Input
          v-model="form.address_line_2"
          label="Address Line 2"
          placeholder="Apartment, suite, etc."
          :error="errors.address_line_2?.[0] || null"
        />
        <Input
          v-model="form.city"
          label="City"
          placeholder="City"
          :error="errors.city?.[0] || null"
        />
        <Input
          v-model="form.state"
          label="State"
          placeholder="State"
          :error="errors.state?.[0] || null"
        />
        <Input
          v-model="form.country"
          label="Country"
          placeholder="Country"
          :error="errors.country?.[0] || null"
        />
      </div>

      <div class="flex items-center justify-end gap-3 pt-2">
        <Button variant="outline-secondary" size="md" type="button" @click="handleClose">
          Cancel
        </Button>
        <Button variant="primary" size="md" type="submit" :loading="isSaving">
          Create Vendor
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
const resource = 'ap/vendors'

const isOpen = computed({
  get: () => props.modelValue,
  set: (value) => emit('update:modelValue', value),
})

const isSaving = ref(false)
const errors = ref({})

const defaultForm = () => ({
  name: '',
  code: '',
  email: '',
  mobile: '',
  fax: '',
  credit_days: '',
  address_line_1: '',
  address_line_2: '',
  address_line_3: '',
  city: '',
  state: '',
  country: '',
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
    const payload = {
      ...form.value,
      credit_days: form.value.credit_days === '' ? null : form.value.credit_days,
    }
    const response = await useRequest('post', `/${resource}`, payload)

    if (response.saved) {
      message.success(response.message || 'Vendor created successfully')
      emit('created', {
        id: response.id,
        name: form.value.name,
        code: form.value.code,
        credit_days: form.value.credit_days === '' ? null : Number(form.value.credit_days),
      })
      handleClose()
    }
  } catch (error) {
    if (error.response?.status === 422) {
      errors.value = error.response.data.errors || {}
    }
    message.error(error.response?.data?.message || 'Failed to create vendor')
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
