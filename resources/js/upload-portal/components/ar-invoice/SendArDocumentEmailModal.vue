<template>
  <Modal
    :model-value="modelValue"
    :title="modalTitle"
    size="3xl"
    :show-footer="false"
    :close-on-backdrop="!sending && !loadingCompose"
    @update:model-value="$emit('update:modelValue', $event)"
  >
    <div v-if="loadingCompose" class="flex justify-center py-12">
      <Spinner size="lg" text="Loading…" />
    </div>

    <div v-else class="space-y-4">
      <p v-if="composeError" class="text-sm text-red-600 dark:text-red-400 !mb-0">
        {{ composeError }}
      </p>

      <div>
        <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Template</label>
        <select
          v-model="selectedTemplateId"
          class="w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-white text-sm px-3 py-2"
          :disabled="templates.length === 0 || previewLoading"
          @change="onTemplateChange"
        >
          <option v-if="templates.length === 0" disabled :value="null">No templates (edit subject and body manually)</option>
          <option v-for="t in templates" :key="t.id" :value="t.id">
            {{ t.name }}{{ t.is_default ? ' (default)' : '' }}
          </option>
        </select>
      </div>

      <Input
        v-model="toEmail"
        type="email"
        label="To"
        placeholder="customer@example.com"
        autocomplete="email"
      />

      <Input
        v-model="subject"
        label="Subject"
        placeholder="Email subject"
      />

      <div>
        <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Message</label>
        <textarea
          v-model="body"
          rows="10"
          class="w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-white text-sm px-3 py-2"
          placeholder="Email body"
        />
      </div>

      <div class="flex justify-end gap-2 pt-2 border-t border-gray-200 dark:border-gray-700">
        <Button
          type="button"
          variant="outline-secondary"
          size="md"
          :disabled="sending"
          @click="close"
        >
          Cancel
        </Button>
        <Button
          type="button"
          variant="primary"
          size="md"
          :disabled="sending || !canSubmit"
          :loading="sending"
          @click="submitSend"
        >
          Send
        </Button>
      </div>
    </div>
  </Modal>
</template>

<script setup>
import { ref, computed, watch } from 'vue'
import axios from '../../plugins/axios'
import Modal from '@/components/common/Modal.vue'
import Input from '@/components/ui/input.vue'
import Button from '@/components/ui/button.vue'
import Spinner from '@/components/ui/spinner.vue'
import { useMessage } from '@/composables/useMessage'

const props = defineProps({
  modelValue: { type: Boolean, default: false },
  /** 'invoice' | 'statement' */
  kind: { type: String, required: true },
  invoiceId: { type: [Number, String], default: null },
  customerId: { type: [Number, String], default: null },
  statementStart: { type: String, default: '' },
  statementEnd: { type: String, default: '' },
})

const emit = defineEmits(['update:modelValue', 'sent'])

const message = useMessage()

const loadingCompose = ref(false)
const previewLoading = ref(false)
const sending = ref(false)
const composeError = ref('')

const templates = ref([])
const selectedTemplateId = ref(null)
const toEmail = ref('')
const subject = ref('')
const body = ref('')

const modalTitle = computed(() =>
  props.kind === 'invoice' ? 'Send invoice by email' : 'Send account statement by email'
)

const canSubmit = computed(() => {
  const email = (toEmail.value || '').trim()
  if (!email) return false
  if (!(subject.value || '').trim()) return false
  return true
})

const resetLocal = () => {
  composeError.value = ''
  templates.value = []
  selectedTemplateId.value = null
  toEmail.value = ''
  subject.value = ''
  body.value = ''
}

const close = () => {
  if (sending.value) return
  emit('update:modelValue', false)
}

const loadCompose = async () => {
  loadingCompose.value = true
  composeError.value = ''
  try {
    if (props.kind === 'invoice') {
      if (!props.invoiceId) {
        composeError.value = 'Missing invoice.'
        return
      }
      const { data } = await axios.get(`/upload-portal/api/sales-invoices/${props.invoiceId}/email-compose`)
      if (!data?.success) {
        composeError.value = data?.message || 'Could not load email form'
        return
      }
      templates.value = data.templates || []
      selectedTemplateId.value = data.template_id || (templates.value[0]?.id ?? null)
      toEmail.value = data.to_email || ''
      subject.value = data.subject || ''
      body.value = data.body || ''
    } else {
      if (!props.customerId || !props.statementStart || !props.statementEnd) {
        composeError.value = 'Select a statement date range first.'
        return
      }
      const { data } = await axios.get(
        `/upload-portal/api/ar-customers/${props.customerId}/statement/email-compose`,
        { params: { start_date: props.statementStart, end_date: props.statementEnd } }
      )
      if (!data?.success) {
        composeError.value = data?.message || 'Could not load email form'
        return
      }
      templates.value = data.templates || []
      selectedTemplateId.value = data.template_id || (templates.value[0]?.id ?? null)
      toEmail.value = data.to_email || ''
      subject.value = data.subject || ''
      body.value = data.body || ''
    }
  } catch (e) {
    composeError.value = e.response?.data?.message || 'Could not load email form'
  } finally {
    loadingCompose.value = false
  }
}

const loadPreview = async () => {
  if (props.kind === 'invoice') {
    if (!props.invoiceId) return
    previewLoading.value = true
    try {
      const { data } = await axios.post(`/upload-portal/api/sales-invoices/${props.invoiceId}/email-preview`, {
        template_id: templateIdForApi(),
      })
      if (data?.success) {
        subject.value = data.subject || ''
        body.value = data.body || ''
      }
    } catch (e) {
      message.error(e.response?.data?.message || 'Could not update preview')
    } finally {
      previewLoading.value = false
    }
    return
  }

  if (!props.customerId || !props.statementStart || !props.statementEnd) return
  previewLoading.value = true
  try {
    const { data } = await axios.post(
      `/upload-portal/api/ar-customers/${props.customerId}/statement/email-preview`,
      {
        start_date: props.statementStart,
        end_date: props.statementEnd,
        template_id: templateIdForApi(),
      }
    )
    if (data?.success) {
      subject.value = data.subject || ''
      body.value = data.body || ''
    }
  } catch (e) {
    message.error(e.response?.data?.message || 'Could not update preview')
  } finally {
    previewLoading.value = false
  }
}

const onTemplateChange = () => {
  loadPreview()
}

const templateIdForApi = () => {
  const v = selectedTemplateId.value
  if (v === null || v === undefined || v === '') return null
  const n = Number(v)
  return Number.isFinite(n) && n > 0 ? n : null
}

const submitSend = async () => {
  if (!canSubmit.value || sending.value) return
  sending.value = true
  try {
    if (props.kind === 'invoice') {
      const { data } = await axios.post(`/upload-portal/api/sales-invoices/${props.invoiceId}/email-send`, {
        to_email: toEmail.value.trim(),
        subject: subject.value.trim(),
        body: body.value,
      })
      if (data?.success) {
        message.success(data.message || 'Invoice sent')
        emit('sent')
        emit('update:modelValue', false)
      } else {
        message.error(data?.message || 'Send failed')
      }
    } else {
      const { data } = await axios.post(
        `/upload-portal/api/ar-customers/${props.customerId}/statement/email-send`,
        {
          start_date: props.statementStart,
          end_date: props.statementEnd,
          to_email: toEmail.value.trim(),
          subject: subject.value.trim(),
          body: body.value,
        }
      )
      if (data?.success) {
        message.success(data.message || 'Statement sent')
        emit('sent')
        emit('update:modelValue', false)
      } else {
        message.error(data?.message || 'Send failed')
      }
    }
  } catch (e) {
    message.error(e.response?.data?.message || 'Send failed')
  } finally {
    sending.value = false
  }
}

watch(
  () => props.modelValue,
  (open) => {
    if (open) {
      resetLocal()
      loadCompose()
    }
  }
)
</script>
