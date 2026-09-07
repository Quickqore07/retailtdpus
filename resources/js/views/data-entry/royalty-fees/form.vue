<template>
  <div v-if="show" class="royalty-fee-form">
    <Panel :divider="true">
      <template #header>
        <div class="flex items-center justify-between">
          <h5 class="font-bold !mb-0">
            {{ mode === 'edit' ? 'Edit Royalty Fee' : 'Create Royalty Fee' }}
          </h5>
        </div>
      </template>

      <form @submit.prevent="handleSave" class="space-y-6">
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
          <DynamicDropdown
            label="Type"
            v-model="form.type"
            :custom-options="typeOptions"
            display-name="name"
            :required="true"
            :searchable="false"
            :error="errors.type ? errors.type[0] : null"
            placeholder="Select type"
          />

          <DynamicDropdown
            label="Company"
            v-model="form.company"
            resource="companies"
            display-name="name"
            :required="true"
            :error="errors.company_id ? errors.company_id[0] : null"
            placeholder="Select company"
          />

          <Input
            v-model="form.invoice_number"
            label="Invoice Number"
            placeholder="Enter invoice number"
            :error="errors.invoice_number ? errors.invoice_number[0] : null"
          />

          <Input
            v-model="form.invoice_date"
            type="date"
            label="Invoice Date"
            :required="true"
            :error="errors.invoice_date ? errors.invoice_date[0] : null"
          />

          <Input
            v-model="form.due_date"
            type="date"
            label="Due Date"
            :required="true"
            :error="errors.due_date ? errors.due_date[0] : null"
          />

          <Input
            v-model="form.amount"
            type="number"
            step="0.01"
            min="0"
            label="Amount"
            placeholder="0.00"
            :required="true"
            :error="errors.amount ? errors.amount[0] : null"
          />
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
          <Textarea
            v-model="form.description"
            label="Description"
            placeholder="Enter description"
            :rows="4"
            :error="errors.description ? errors.description[0] : null"
          />

          <div>
            <DocumentFileField
              v-model="invoicePdf"
              label="Invoice PDF"
              placeholder="Upload invoice PDF or image"
              accept=".pdf,.jpg,.jpeg,.png,application/pdf,image/jpeg,image/png"
              help-text="PDF or image (JPG, PNG) up to 10MB."
              :error="errors.invoice_pdf ? errors.invoice_pdf[0] : null"
            />
            <p
              v-if="mode === 'edit' && form.invoice_pdf_name"
              class="text-sm text-gray-500 dark:text-gray-400 mt-2"
            >
              Current file:
              <a
                v-if="form.invoice_pdf_url"
                :href="form.invoice_pdf_url"
                target="_blank"
                rel="noopener noreferrer"
                class="text-blue-600 hover:text-blue-900 dark:text-blue-400"
              >
                {{ form.invoice_pdf_name }}
              </a>
              <span v-else>{{ form.invoice_pdf_name }}</span>
            </p>
          </div>
        </div>

        <div class="flex items-center justify-end gap-3 pt-2">
          <Button variant="outline-secondary" size="md" @click="cancel" type="button">
            Cancel
          </Button>
          <Button
            variant="primary"
            size="md"
            type="submit"
            :loading="isSaving"
            v-if="mode === 'create' ? access.includes('create') : access.includes('update')"
          >
            {{ mode === 'edit' ? 'Update Royalty Fee' : 'Create Royalty Fee' }}
          </Button>
        </div>
      </form>
    </Panel>
  </div>

  <div v-else class="flex items-center justify-center min-h-[400px]">
    <Spinner size="md" text="Loading form..." centered />
  </div>
</template>

<script setup>
import { ref, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useFormable } from '@/composables/useFormable'
import { useRequest } from '@/services/api'
import { useMessage } from '@/composables/useMessage'
import Panel from '@/components/ui/panel.vue'
import Button from '@/components/ui/button.vue'
import Input from '@/components/ui/input.vue'
import Textarea from '@/components/ui/textarea.vue'
import Spinner from '@/components/ui/spinner.vue'
import DynamicDropdown from '@/components/ui/dynamic-dropdown.vue'
import DocumentFileField from '@/components/common/DocumentFileField.vue'
import { validateDocumentFileSize } from '@/utils/documentUpload'

const route = useRoute()
const router = useRouter()
const message = useMessage()
const resource = route.meta?.resource || 'data-entry/royalty-fees'

const { form, errors, show, mode, cancel, setData, access } = useFormable(
  resource,
  'data-entry/royalty-fees',
  'royalty-fees'
)

const isSaving = ref(false)
const invoicePdf = ref(null)

const typeOptions = [
  { id: 'Advertisement', name: 'Advertisement' },
  { id: 'Royalty', name: 'Royalty' },
]

watch(
  () => form.value,
  (newVal) => {
    if (!newVal) return
    if (newVal.type && typeof newVal.type === 'string') {
      form.value.type = typeOptions.find((option) => option.id === newVal.type) || newVal.type
    }
    if (newVal.invoice_date) {
      form.value.invoice_date = String(newVal.invoice_date).slice(0, 10)
    }
    if (newVal.due_date) {
      form.value.due_date = String(newVal.due_date).slice(0, 10)
    }
  },
  { immediate: true }
)

const handleSave = async () => {
  isSaving.value = true
  errors.value = {}

  if (invoicePdf.value) {
    const sizeError = validateDocumentFileSize(invoicePdf.value)
    if (sizeError) {
      errors.value = { invoice_pdf: [sizeError] }
      message.error(sizeError)
      isSaving.value = false
      return
    }
  }

  try {
    const payload = new FormData()
    payload.append('type', form.value.type?.id || form.value.type || '')
    payload.append('company_id', form.value.company?.id || form.value.company_id || '')
    payload.append('invoice_number', form.value.invoice_number || '')
    payload.append('invoice_date', form.value.invoice_date || '')
    payload.append('due_date', form.value.due_date || '')
    payload.append('amount', form.value.amount ?? 0)
    payload.append('description', form.value.description || '')

    if (invoicePdf.value) {
      payload.append('invoice_pdf', invoicePdf.value)
    }

    const url = mode.value === 'edit'
      ? `/${resource}/${route.params.id}`
      : `/${resource}`

    if (mode.value === 'edit') {
      payload.append('_method', 'PUT')
    }

    const response = await useRequest('post', url, payload, {
      headers: {
        'Content-Type': 'multipart/form-data',
      },
    })

    if (response.saved) {
      message.success(response.message || 'Saved successfully')
      router.push(`/data-entry/royalty-fees/${response.id}`)
    } else {
      message.error(response.message || 'Failed to save')
    }
  } catch (error) {
    if (error.response?.status === 422) {
      errors.value = error.response.data.errors || {}
    }
    message.error(error.response?.data?.message || 'Failed to save')
  } finally {
    isSaving.value = false
  }
}

defineExpose({
  setData
})
</script>
