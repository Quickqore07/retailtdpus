<template>
  <div class="bg-white dark:bg-gray-800 border-2 border-gray-200 dark:border-gray-700 rounded-xl p-5 mb-6">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 border-b border-gray-200 dark:border-gray-700 pb-4 mb-4">
      <h5 class="text-lg font-semibold text-gray-900 dark:text-white !mb-0">
        Account statement
      </h5>
      <p v-if="customerName" class="text-sm text-gray-600 dark:text-gray-400 !mb-0 sm:text-right">
        {{ customerName }}
      </p>
    </div>

    <div class="flex flex-col sm:flex-row flex-wrap items-end gap-3 mb-6">
      <div>
        <Input
          v-model="statementStart"
          type="date"
          label="Start date"
        />
      </div>
      <div>
        <Input
          v-model="statementEnd"
          type="date"
          label="End date"
        />
      </div>
      <Button type="button" variant="primary" size="sm" :disabled="loadingStatement" @click="loadStatement">
        {{ loadingStatement ? 'Loading…' : 'Run statement' }}
      </Button>
      <Button
        type="button"
        variant="outline-secondary"
        size="sm"
        icon-left="file-pdf"
        icon-size="sm"
        :disabled="!statementLoaded || loadingStatement || exportingPdf"
        :loading="exportingPdf"
        @click="exportStatementPdf"
      >
        Export PDF
      </Button>
      <Button
        v-if="canSendStatement"
        type="button"
        variant="primary"
        size="sm"
        icon-left="mail"
        icon-size="sm"
        :disabled="!statementLoaded || loadingStatement"
        @click="showSendEmailModal = true"
      >
        Send email
      </Button>
    </div>

    <div v-if="statementError" class="text-sm text-red-600 dark:text-red-400 mb-4">
      {{ statementError }}
    </div>

    <div v-else-if="statementLoaded" class="space-y-4">
      <div class="overflow-x-auto">
        <table class="w-full border-collapse text-sm statement-table">
          <thead>
            <tr class="border-b-2 border-gray-900 dark:border-gray-100">
              <Th align="center" custom-class="py-3 px-3 font-semibold border-r border-gray-300 dark:border-gray-600">Date</Th>
              <Th align="center" custom-class="py-3 px-3 font-semibold border-r border-gray-300 dark:border-gray-600">Transaction</Th>
              <Th align="center" custom-class="py-3 px-3 font-semibold border-r border-gray-300 dark:border-gray-600">Amount</Th>
              <Th align="center" custom-class="py-3 px-3 font-semibold">Balance</Th>
            </tr>
          </thead>
          <tbody>
            <tr
              v-for="(line, idx) in statementLines"
              :key="idx"
              class="border-b border-gray-200 dark:border-gray-700"
            >
              <Td align="left" custom-class="py-2.5 px-3 border-r border-gray-200 dark:border-gray-700 align-top">
                {{ formatDateUs(line.date) }}
              </Td>
              <Td align="left" custom-class="py-2.5 px-3 border-r border-gray-200 dark:border-gray-700">
                {{ line.transaction }}
              </Td>
              <Td align="right" custom-class="py-2.5 px-3 border-r border-gray-200 dark:border-gray-700 tabular-nums">
                {{ formatStatementNumber(line.amount) }}
              </Td>
              <Td align="right" custom-class="py-2.5 px-3 tabular-nums">
                {{ formatStatementNumber(line.balance) }}
              </Td>
            </tr>
          </tbody>
        </table>
      </div>

      <div v-if="statementLines.length === 0" class="text-sm text-gray-600 dark:text-gray-400">
        No outstanding invoices or customer credit on account.
      </div>

      <div class="text-sm text-gray-700 dark:text-gray-300 pt-2 border-t border-gray-200 dark:border-gray-700">
        <span class="font-medium text-gray-900 dark:text-white">Balance due</span>
        (through {{ formatDateUs(statementMeta.end) }}):
        <span class="tabular-nums font-medium">{{ formatStatementNumber(statementMeta.closing) }}</span>
        <span v-if="statementMeta.credit > 0" class="text-gray-600 dark:text-gray-400">
          · Customer credit: {{ formatStatementNumber(statementMeta.credit) }}
        </span>
      </div>
    </div>

    <div v-else class="text-sm text-gray-500 dark:text-gray-400">
      Choose a date range and click Run statement.
    </div>

    <SendArDocumentEmailModal
      v-model="showSendEmailModal"
      kind="statement"
      :customer-id="customerId"
      :statement-start="statementStart"
      :statement-end="statementEnd"
    />
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import axios from '../../plugins/axios'
import Button from '@/components/ui/button.vue'
import Th from '@/components/ui/th.vue'
import Td from '@/components/ui/td.vue'
import Input from '@/components/ui/input.vue'
import SendArDocumentEmailModal from './SendArDocumentEmailModal.vue'
import { useMessage } from '@/composables/useMessage'
import { useUser } from '../../composables/useUser'

const { can } = useUser()
const canSendStatement = computed(() => can('upload-portal-customer', 'send-statement'))

const props = defineProps({
  customerId: {
    type: [String, Number],
    required: true,
  },
  customerName: {
    type: String,
    default: '',
  },
})

const message = useMessage()

const showSendEmailModal = ref(false)

const defaultStatementRange = () => {
  const end = new Date()
  const start = new Date(end.getFullYear(), end.getMonth(), 1)
  const ymd = (d) => {
    const y = d.getFullYear()
    const m = String(d.getMonth() + 1).padStart(2, '0')
    const day = String(d.getDate()).padStart(2, '0')
    return `${y}-${m}-${day}`
  }
  return { start: ymd(start), end: ymd(end) }
}

const statementStart = ref(defaultStatementRange().start)
const statementEnd = ref(defaultStatementRange().end)
const loadingStatement = ref(false)
const exportingPdf = ref(false)
const statementError = ref('')
const statementLoaded = ref(false)
const statementLines = ref([])
const statementMeta = ref({ start: '', end: '', opening: 0, closing: 0, credit: 0 })

const formatDateUs = (ymd) => {
  if (!ymd) return '—'
  const part = String(ymd).includes('T') ? String(ymd).split('T')[0] : String(ymd).split(' ')[0]
  const [y, m, d] = part.split('-')
  if (!y || !m || !d) return '—'
  return `${m}/${d}/${y}`
}

const formatStatementNumber = (n) => {
  if (n === null || n === undefined || Number.isNaN(Number(n))) return '—'
  const num = Number(n)
  const neg = num < 0
  const abs = Math.abs(num)
  const [intPart, dec = '00'] = abs.toFixed(2).split('.')
  const withCommas = intPart.replace(/\B(?=(\d{3})+(?!\d))/g, ',')
  return (neg ? '-' : '') + `${withCommas}.${dec}`
}

const loadStatement = async () => {
  if (!props.customerId || !statementStart.value || !statementEnd.value) {
    statementError.value = 'Please select start and end dates.'
    return
  }
  statementError.value = ''
  loadingStatement.value = true
  try {
    const response = await axios.get(`/upload-portal/api/ar-customers/${props.customerId}/statement`, {
      params: {
        start_date: statementStart.value,
        end_date: statementEnd.value,
      },
    })
    if (response.data.success) {
      statementLines.value = response.data.lines || []
      statementMeta.value = {
        start: response.data.start_date,
        end: response.data.end_date,
        opening: Number(response.data.opening_balance),
        closing: Number(response.data.closing_balance),
        credit: Number(response.data.credit_balance ?? 0),
      }
      statementLoaded.value = true
    } else {
      statementError.value = response.data.message || 'Could not load statement'
    }
  } catch (error) {
    statementError.value = error.response?.data?.message || 'Failed to load statement'
    if (error.response?.status !== 403) {
      message.error(statementError.value)
    }
    statementLoaded.value = false
  } finally {
    loadingStatement.value = false
  }
}

const exportStatementPdf = async () => {
  if (!props.customerId || !statementLoaded.value || !statementStart.value || !statementEnd.value) {
    return
  }
  exportingPdf.value = true
  try {
    const response = await axios.get(
      `/upload-portal/api/ar-customers/${props.customerId}/statement/pdf`,
      {
        params: {
          start_date: statementStart.value,
          end_date: statementEnd.value,
        },
        responseType: 'blob',
      }
    )
    const contentType = (response.headers['content-type'] || '').toLowerCase()
    if (contentType.includes('application/json')) {
      const text = await response.data.text()
      try {
        const json = JSON.parse(text)
        message.error(json.message || 'Could not generate PDF')
      } catch {
        message.error('Could not generate PDF')
      }
      return
    }
    const blob = new Blob([response.data], { type: 'application/pdf' })
    const url = URL.createObjectURL(blob)
    const link = document.createElement('a')
    link.href = url
    const safeName = (props.customerName || 'customer').replace(/[^\w\s-]/g, '').trim().replace(/\s+/g, '-') || 'customer'
    link.download = `account-statement-${safeName}-${statementStart.value}-to-${statementEnd.value}.pdf`
    document.body.appendChild(link)
    link.click()
    document.body.removeChild(link)
    URL.revokeObjectURL(url)
    message.success('PDF download started')
  } catch (error) {
    const data = error.response?.data
    if (data instanceof Blob) {
      try {
        const text = await data.text()
        const json = JSON.parse(text)
        const fromErrors = json.errors ? Object.values(json.errors).flat().join(' ') : ''
        message.error(json.message || fromErrors || 'Failed to download PDF')
      } catch {
        message.error('Failed to download PDF')
      }
    } else {
      message.error(error.response?.data?.message || 'Failed to download PDF')
    }
  } finally {
    exportingPdf.value = false
  }
}

const reset = () => {
  const r = defaultStatementRange()
  statementStart.value = r.start
  statementEnd.value = r.end
  statementLoaded.value = false
  statementLines.value = []
  statementError.value = ''
  statementMeta.value = { start: '', end: '', opening: 0, closing: 0, credit: 0 }
  showSendEmailModal.value = false
}

defineExpose({
  loadStatement,
  reset,
})
</script>

<style scoped>
.statement-table :deep(th),
.statement-table :deep(td) {
  vertical-align: middle;
}
</style>
