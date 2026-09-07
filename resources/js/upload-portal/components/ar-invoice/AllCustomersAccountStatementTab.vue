<template>
  <div class="bg-white dark:bg-gray-800 border-2 border-gray-200 dark:border-gray-700 rounded-xl p-5 mb-6">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 border-b border-gray-200 dark:border-gray-700 pb-4 mb-4">
      <h5 class="text-lg font-semibold text-gray-900 dark:text-white !mb-0">
        Account statements — all customers
      </h5>
      <p v-if="statementLoaded" class="text-sm text-gray-600 dark:text-gray-400 !mb-0 sm:text-right">
        {{ statements.length }} customer{{ statements.length === 1 ? '' : 's' }}
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
      <div class="flex items-end">
        <label class="inline-flex items-center gap-2 text-sm text-gray-700 dark:text-gray-200">
          <input
            v-model="includeZero"
            type="checkbox"
            class="h-4 w-4 rounded border-gray-300 text-primary-600 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-800"
            @change="onIncludeZeroChange"
          />
          Show customers with no activity
        </label>
      </div>
      <Button type="button" variant="primary" size="sm" :disabled="loadingStatement" @click="loadStatements">
        {{ loadingStatement ? 'Loading…' : 'Run statements' }}
      </Button>
      <Button
        type="button"
        variant="outline-secondary"
        size="sm"
        icon-left="file-pdf"
        icon-size="sm"
        :disabled="!statementLoaded || loadingStatement || exportingPdf || statements.length === 0"
        :loading="exportingPdf"
        @click="exportStatementsPdf"
      >
        Export PDF
      </Button>
    </div>

    <div v-if="statementError" class="text-sm text-red-600 dark:text-red-400 mb-4">
      {{ statementError }}
    </div>

    <div v-else-if="statementLoaded" class="space-y-8 max-h-[700px] overflow-y-auto">
      <div
        v-for="statement in statements"
        :key="statement.customer_id"
        class="border border-gray-200 dark:border-gray-700 rounded-lg p-4"
      >
        <h6 class="text-base font-semibold text-gray-900 dark:text-white !mb-4">
          {{ statement.customer_name }}
        </h6>

        <div class="text-sm text-gray-700 dark:text-gray-300 mb-4">
          <span class="font-medium text-gray-900 dark:text-white">Opening balance</span>
          (before {{ formatDateUs(statementMeta.start) }}):
          <span class="tabular-nums font-medium">{{ formatStatementNumber(statement.opening_balance) }}</span>
        </div>

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
                v-for="(line, idx) in statement.lines"
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

        <div v-if="statement.lines.length === 0" class="text-sm text-gray-600 dark:text-gray-400">
          No invoice or payment activity in this date range.
        </div>

        <div class="text-sm text-gray-700 dark:text-gray-300 pt-2 mt-4 border-t border-gray-200 dark:border-gray-700">
          <span class="font-medium text-gray-900 dark:text-white">Closing balance</span>
          (through {{ formatDateUs(statementMeta.end) }}):
          <span class="tabular-nums font-medium">{{ formatStatementNumber(statement.closing_balance) }}</span>
        </div>
      </div>

      <div v-if="statements.length === 0" class="text-sm text-gray-600 dark:text-gray-400">
        {{ includeZero ? 'No customers found.' : 'No customers with activity in this date range.' }}
      </div>
    </div>

    <div v-else class="text-sm text-gray-500 dark:text-gray-400">
      Choose a date range and click Run statements.
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import axios from '../../plugins/axios'
import Button from '@/components/ui/button.vue'
import Th from '@/components/ui/th.vue'
import Td from '@/components/ui/td.vue'
import Input from '@/components/ui/input.vue'
import { useMessage } from '@/composables/useMessage'

const message = useMessage()

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
const includeZero = ref(false)
const loadingStatement = ref(false)
const exportingPdf = ref(false)
const statementError = ref('')
const statementLoaded = ref(false)
const statements = ref([])
const statementMeta = ref({ start: '', end: '' })

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

const loadStatements = async () => {
  if (!statementStart.value || !statementEnd.value) {
    statementError.value = 'Please select start and end dates.'
    return
  }
  statementError.value = ''
  loadingStatement.value = true
  try {
    const response = await axios.get('/upload-portal/api/ar-customers/statements', {
      params: {
        start_date: statementStart.value,
        end_date: statementEnd.value,
        include_zero: includeZero.value ? 1 : 0,
      },
    })
    if (response.data.success) {
      statements.value = response.data.statements || []
      statementMeta.value = {
        start: response.data.start_date,
        end: response.data.end_date,
      }
      statementLoaded.value = true
    } else {
      statementError.value = response.data.message || 'Could not load statements'
    }
  } catch (error) {
    statementError.value = error.response?.data?.message || 'Failed to load statements'
    if (error.response?.status !== 403) {
      message.error(statementError.value)
    }
    statementLoaded.value = false
  } finally {
    loadingStatement.value = false
  }
}

const onIncludeZeroChange = () => {
  if (statementLoaded.value) {
    loadStatements()
  }
}

const exportStatementsPdf = async () => {
  if (!statementLoaded.value || !statementStart.value || !statementEnd.value || statements.value.length === 0) {
    return
  }
  exportingPdf.value = true
  try {
    const response = await axios.get('/upload-portal/api/ar-customers/statements/pdf', {
      params: {
        start_date: statementStart.value,
        end_date: statementEnd.value,
        include_zero: includeZero.value ? 1 : 0,
      },
      responseType: 'blob',
    })
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
    link.download = `account-statements-all-${statementStart.value}-to-${statementEnd.value}.pdf`
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
  includeZero.value = false
  statementLoaded.value = false
  statements.value = []
  statementError.value = ''
  statementMeta.value = { start: '', end: '' }
}

defineExpose({
  loadStatements,
  reset,
})
</script>

<style scoped>
.statement-table :deep(th),
.statement-table :deep(td) {
  vertical-align: middle;
}
</style>
